<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use App\Models\GameStateModel;
use App\Models\MissionModel;
use App\Models\UnitModel;

class GameTickService
{
    protected $db;
    protected string $gameDate;
    protected GameStateModel $gameStateModel;

    public function __construct(BaseConnection $db)
    {
        $this->db        = $db;
        $this->gameStateModel = new GameStateModel();
        $this->gameDate  = $this->gameStateModel->getProperty('current_date') ?? '3025-01-01';
    }

    public function processTick(int $days = 1): void
    {
        $this->advanceDate($days);
        $this->gameDate = $this->gameStateModel->getProperty('current_date') ?? '3025-01-01';

        $this->processMorale();
        $this->processMissions();
        $this->processDispersedUnits(); // add this
        $this->updateTick();
    }

    protected function advanceDate(int $days): void
    {
        $dateObj = new \DateTime($this->gameDate);
        $dateObj->modify("+{$days} days");
        $this->gameStateModel->setProperty('current_date', $dateObj->format('Y-m-d'));
    }

    protected function updateTick(): void
    {
        $tickCount = (int)$this->gameStateModel->getProperty('tick_count') ?? 0;
        $this->gameStateModel->setProperty('tick_count', $tickCount + 1);
    }

    protected function processMorale(): void
    {
        $personnel = $this->db->table('personnel')->get()->getResultArray();
        foreach ($personnel as $p) {
            $morale = $p['morale'] ?? 100;
            if ($morale < 100) {
                $morale = min(100, $morale + 2.0);
                $this->db->table('personnel')
                    ->where('personnel_id', $p['personnel_id'])
                    ->update(['morale' => $morale]);
            }
        }
    }

    protected function processMissions(): void
    {
        $missionModel = new MissionModel();

        $missions = $this->db->table('missions')
            ->where('status', 'In Transit')
            ->get()
            ->getResultArray();

        foreach ($missions as $mission) {
            $elapsed     = (int)$mission['days_elapsed'] + 1;
            $transitDays = (int)$mission['transit_days'];
            $progress    = min(1.0, $elapsed / max(1, $transitDays));

            $origin = $this->db->table('locations')
                ->where('location_id', $mission['origin_location_id'])
                ->get()->getRowArray();

            $dest = $this->db->table('locations')
                ->where('location_id', $mission['destination_location_id'])
                ->get()->getRowArray();

            $currentX = $origin['coord_x'] + ($progress * ($dest['coord_x'] - $origin['coord_x']));
            $currentY = $origin['coord_y'] + ($progress * ($dest['coord_y'] - $origin['coord_y']));

            if ($elapsed >= $transitDays) {
                $this->completeMission($mission, $dest);
            } else {
                $this->db->table('missions')
                    ->where('mission_id', $mission['mission_id'])
                    ->update([
                        'days_elapsed'    => $elapsed,
                        'current_coord_x' => round($currentX, 4),
                        'current_coord_y' => round($currentY, 4),
                    ]);
            }
        }
    }

    protected function completeMission(array $mission, array $dest): void
    {
        $missionModel     = new MissionModel();
        $unitModel        = new UnitModel();
        $destLocationId   = $mission['destination_location_id'];
        $originLocationId = $mission['origin_location_id'];
        $destFactionId    = (int)($dest['controlled_by'] ?? 0);
        $missionFactionId = (int)$mission['faction_id'];
        $missionType      = $mission['mission_type'];

        $unitIds = array_column(
            $this->db->table('mission_units')
                ->where('mission_id', $mission['mission_id'])
                ->get()->getResultArray(),
            'unit_id'
        );

        // Determine if this is an HQ move (Battalion/Regiment)
        $isHQMove = !empty(array_filter($unitIds, function ($uid) {
            $u = $this->db->table('units')
                ->where('unit_id', $uid)->get()->getRowArray();
            return in_array($u['unit_type'] ?? '', ['Battalion', 'Regiment']);
        }));

        // Check if enemy has units garrisoned at destination
        $enemyPresent = false;
        if ($destFactionId !== 0 && $destFactionId !== $missionFactionId) {
            $enemyPresent = $this->db->table('units')
                ->where('location_id', $destLocationId)
                ->where('faction_id !=', $missionFactionId)
                ->where('status', 'Garrisoned')
                ->countAllResults() > 0;
        }

        $isFriendlyNow  = $destFactionId === $missionFactionId;
        $isUncontrolled = $destFactionId === 0;
        $isEnemy        = !$isFriendlyNow && !$isUncontrolled;

        // ================================
        // Transfer / Resupply / HQ Move
        // Intended for friendly destination — handle if captured during transit
        // ================================
        if (in_array($missionType, ['Transfer', 'Resupply'])) {
            if ($isFriendlyNow) {
                // Destination still friendly — normal arrival
                if ($isHQMove) {
                    $this->arriveHQ($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "HQ relocated to {$dest['name']}. Subunits unaffected."
                    );
                } else {
                    $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Mission arrived at {$dest['name']}. " . count($unitIds) . " units transferred."
                    );
                }
            } elseif ($isUncontrolled) {
                // Captured and abandoned during transit — take it, arrive
                $this->takeControl($destLocationId, $missionFactionId);
                if ($isHQMove) {
                    $this->arriveHQ($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                } else {
                    $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                }
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Transfer arrived at {$dest['name']} — location became uncontrolled during transit. Units secured it."
                );
            } else {
                // Enemy took it during transit
                if ($enemyPresent) {
                    // Enemy garrison present — return to origin
                    $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Aborted',
                        "Transfer to {$dest['name']} aborted — location captured by enemy during transit with active garrison. Units returning to origin."
                    );
                } else {
                    // Enemy claimed it on paper but no garrison — take it back
                    $this->takeControl($destLocationId, $missionFactionId);
                    if ($isHQMove) {
                        $this->arriveHQ($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    } else {
                        $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    }
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Transfer arrived at {$dest['name']} — location claimed by enemy during transit but no garrison present. Units secured it."
                    );
                }
            }
            return;
        }

        if ($missionType === 'Withdrawal') {
            if ($isFriendlyNow) {
                $this->arriveHQ($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Units withdrew to {$dest['name']}."
                );
            } elseif ($isUncontrolled) {
                // Withdrew to uncontrolled territory — take it and dig in
                $this->takeControl($destLocationId, $missionFactionId);
                $this->arriveHQ($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Units withdrew to {$dest['name']} — location was uncontrolled, now secured."
                );
            } else {
                // Destination captured during withdrawal — find nearest friendly and reroute
                $nearest = $this->findNearestFriendlyLocation($destLocationId, $missionFactionId);
                if ($nearest) {
                    $this->returnToOrigin($unitIds, $nearest['location_id'], $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Aborted',
                        "Withdrawal destination {$dest['name']} captured during transit — rerouting to {$nearest['name']}."
                    );
                } else {
                    // No friendly locations left on planet — units are trapped
                    $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Withdrawal to {$dest['name']} failed — destination captured and no friendly locations available. Units trapped. [COMBAT TODO]"
                    );
                }
            }
            return;
        }
        // ================================
        // Uncontrolled destination
        // ================================
        if ($isUncontrolled) {
            if ($missionType === 'Recon') {
                $this->takeControl($destLocationId, $missionFactionId);
                $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Recon mission completed. {$dest['name']} is uncontrolled — player takes control. Units returning to origin."
                );
            } elseif ($missionType === 'Assault') {
                $this->takeControl($destLocationId, $missionFactionId);
                $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Assault on uncontrolled {$dest['name']} successful. Units now garrisoning location."
                );
            }
            return;
        }

        // ================================
        // Enemy destination
        // ================================
        if ($isEnemy) {
            if ($missionType === 'Recon') {
                if ($enemyPresent) {
                    $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Recon mission reached {$dest['name']}. Enemy forces present — intel gathered. Units returning to origin. [COMBAT TODO]"
                    );
                } else {
                    $this->takeControl($destLocationId, $missionFactionId);
                    $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Recon mission reached {$dest['name']}. No enemy forces present — player takes control. Units returning to origin."
                    );
                }
            } elseif ($missionType === 'Assault') {
                if ($enemyPresent) {
                    $this->db->table('missions')
                        ->where('mission_id', $mission['mission_id'])
                        ->update([
                            'status'          => 'Combat',
                            'arrived_date'    => $this->gameDate,
                            'current_coord_x' => $dest['coord_x'],
                            'current_coord_y' => $dest['coord_y'],
                            'days_elapsed'    => $mission['transit_days'],
                        ]);
                    $unitModel->setMissionStatus($unitIds, 'Combat', $mission['mission_id'], $destLocationId);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Combat',
                        "Assault force arrived at {$dest['name']}. Enemy forces present — combat initiated. [COMBAT TODO]"
                    );
                    return;
                } else {
                    $this->takeControl($destLocationId, $missionFactionId);
                    $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Assault on {$dest['name']} successful — no enemy forces present. Player takes control. Units garrisoning location."
                    );
                }
            } elseif ($missionType === 'Harass') {
                $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Harassment mission completed at {$dest['name']}. Units returning to origin. [HARASS TODO]"
                );
            }
        }
    }

    // ================================
    // Helper methods
    // ================================

    protected function takeControl(int $locationId, int $factionId): void
    {
        $this->db->table('locations')
            ->where('location_id', $locationId)
            ->update(['controlled_by' => $factionId]);
    }

    protected function arriveAtLocation(
        array $unitIds,
        int $locationId,
        array $mission,
        array $destCoords,  // ['coord_x' => x, 'coord_y' => y]
        MissionModel $missionModel,
        UnitModel $unitModel
    ): void {
        if (!empty($unitIds)) {
            // Move units and personnel to destination
            $unitModel->setMissionStatus($unitIds, 'Garrisoned', null, $locationId);

            foreach ($unitIds as $unitId) {
                $personnelIds = array_column(
                    $this->db->table('personnel_assignments')
                        ->select('personnel_id')
                        ->where('unit_id', $unitId)
                        ->where('date_released IS NULL', null, false)
                        ->get()->getResultArray(),
                    'personnel_id'
                );
                if (!empty($personnelIds)) {
                    $this->db->table('personnel')
                        ->whereIn('personnel_id', $personnelIds)
                        ->update(['location_id' => $locationId]);
                }
            }

            $this->db->table('equipment')
                ->whereIn('assigned_unit_id', $unitIds)
                ->update(['location_id' => $locationId]);
        }

        $this->db->table('missions')
            ->where('mission_id', $mission['mission_id'])
            ->update([
                'status'          => 'Arrived',
                'arrived_date'    => $this->gameDate,
                'current_coord_x' => $destCoords['coord_x'],
                'current_coord_y' => $destCoords['coord_y'],
                'days_elapsed'    => $mission['transit_days'],
            ]);
    }

    protected function returnToOrigin(
        array $unitIds,
        int $originLocationId,
        array $mission,
        MissionModel $missionModel,
        UnitModel $unitModel
    ): void {
        // Get current position for distance calculation
        $currentX = (float)$mission['current_coord_x'];
        $currentY = (float)$mission['current_coord_y'];

        $origin = $this->db->table('locations')
            ->where('location_id', $originLocationId)
            ->get()->getRowArray();

        $returnDistance = $missionModel->calculateDistance(
            $currentX,
            $currentY,
            (float)$origin['coord_x'],
            (float)$origin['coord_y']
        );
        $returnDays = $missionModel->calculateTransitDays(
            $returnDistance,
            (float)$mission['slowest_speed']
        );

        $etaDate = new \DateTime($this->gameDate);
        $etaDate->modify("+{$returnDays} days");

        // Flip mission to return journey
        $this->db->table('missions')
            ->where('mission_id', $mission['mission_id'])
            ->update([
                'status'                  => 'In Transit',
                'origin_location_id'      => $mission['destination_location_id'],
                'destination_location_id' => $originLocationId,
                'transit_days'            => $returnDays,
                'days_elapsed'            => 0,
                'eta_date'                => $etaDate->format('Y-m-d'),
                'notes'                   => ($mission['notes'] ?? '') . ' [RETURNING TO BASE]',
            ]);

        // Units stay In Transit with mission
        $unitModel->setMissionStatus($unitIds, 'In Transit', $mission['mission_id']);
    }

    // In completeMission() after takeControl()
    protected function checkForForcedWithdrawals(int $locationId, int $capturingFactionId): void
    {
        $hqUnits = $this->db->table('units')
            ->whereIn('unit_type', ['Battalion', 'Regiment'])
            ->where('location_id', $locationId)
            ->where('status', 'Garrisoned')
            ->where('faction_id !=', $capturingFactionId)
            ->get()->getResultArray();

        if (empty($hqUnits)) return;

        $missionModel = new MissionModel();
        $from         = $this->db->table('locations')
            ->where('location_id', $locationId)
            ->get()->getRowArray();

        foreach ($hqUnits as $hq) {
            $nearest = $this->findNearestFriendlyLocation($locationId, $hq['faction_id']);

            if (!$nearest) {
                // Log trapped HQ — no friendly locations available
                // Could create a combat mission here in future
                continue;
            }

            // Calculate transit
            $distance    = $missionModel->calculateDistance(
                (float)$from['coord_x'],
                (float)$from['coord_y'],
                (float)$nearest['coord_x'],
                (float)$nearest['coord_y']
            );

            // Use slowest equipment on HQ unit, default 64kph if no equipment
            $speedRow     = $this->db->table('equipment e')
                ->select('MIN(ch.speed) AS min_speed')
                ->join('chassis ch', 'ch.chassis_id = e.chassis_id')
                ->where('e.assigned_unit_id', $hq['unit_id'])
                ->get()->getRowArray();
            $slowestSpeed = (float)($speedRow['min_speed'] ?? 64.0);
            $transitDays  = $missionModel->calculateTransitDays($distance, $slowestSpeed);

            $etaDate = new \DateTime($this->gameDate);
            $etaDate->modify("+{$transitDays} days");

            $missionId = $missionModel->createMission([
                'name'                    => "Withdrawal — {$hq['name']}",
                'mission_type'            => 'Withdrawal',
                'status'                  => 'In Transit',
                'origin_location_id'      => $locationId,
                'destination_location_id' => $nearest['location_id'],
                'faction_id'              => $hq['faction_id'],
                'notes'                   => 'Forced withdrawal — HQ location captured.',
                'launched_date'           => $this->gameDate,
                'eta_date'                => $etaDate->format('Y-m-d'),
                'distance'                => $distance,
                'transit_days'            => $transitDays,
                'days_elapsed'            => 0,
                'slowest_speed'           => $slowestSpeed,
                'current_coord_x'         => $from['coord_x'],
                'current_coord_y'         => $from['coord_y'],
            ], [$hq['unit_id']]);

            // Set HQ to In Transit
            $unitModel = new UnitModel();
            $unitModel->setMissionStatus([$hq['unit_id']], 'In Transit', $missionId);

            $missionModel->logEvent(
                $missionId,
                $this->gameDate,
                'Withdrawal',
                "{$hq['name']} HQ forced to withdraw from {$from['name']} — location captured. Routing to {$nearest['name']} ({$transitDays} days)."
            );
        }
    }

    protected function findNearestFriendlyLocation(int $fromLocationId, int $factionId): ?array
    {
        $from = $this->db->table('locations')
            ->where('location_id', $fromLocationId)
            ->get()->getRowArray();

        if (!$from) return null;

        // Find closest location controlled by this faction on same planet
        $candidates = $this->db->table('locations')
            ->where('controlled_by', $factionId)
            ->where('planet_id', $from['planet_id'])
            ->where('location_id !=', $fromLocationId)
            ->get()->getResultArray();

        if (empty($candidates)) return null;

        // Sort by distance
        usort($candidates, function ($a, $b) use ($from) {
            $distA = sqrt(
                pow($a['coord_x'] - $from['coord_x'], 2) +
                    pow($a['coord_y'] - $from['coord_y'], 2)
            );
            $distB = sqrt(
                pow($b['coord_x'] - $from['coord_x'], 2) +
                    pow($b['coord_y'] - $from['coord_y'], 2)
            );
            return $distA <=> $distB;
        });

        return $candidates[0];
    }

    protected function processDispersedUnits(): void
    {
        $companies = $this->db->table('units')
            ->whereIn('unit_type', ['Company', 'Platoon'])
            ->where('status !=', 'Deactivated')
            ->get()->getResultArray();

        foreach ($companies as $company) {
            $children = $this->db->table('units')
                ->select('location_id, status')
                ->where('parent_unit_id', $company['unit_id'])
                ->where('status !=', 'Deactivated')
                ->get()->getResultArray();

            if (empty($children)) continue;

            $locations = array_unique(array_filter(
                array_column($children, 'location_id')
            ));

            $allStatuses = array_column($children, 'status');
            $allInTransit = count(array_filter(
                $allStatuses,
                fn($s) => in_array($s, ['In Transit', 'Combat'])
            )) === count($children);

            if (count($locations) === 1 && !$allInTransit) {
                // All children co-located — consolidate
                $this->db->table('units')
                    ->where('unit_id', $company['unit_id'])
                    ->update([
                        'status'      => 'Garrisoned',
                        'location_id' => $locations[0],
                    ]);
            } elseif (count($locations) > 1 || empty($locations)) {
                // Children spread across locations — disperse
                $this->db->table('units')
                    ->where('unit_id', $company['unit_id'])
                    ->update([
                        'status'      => 'Dispersed',
                        'location_id' => null,
                    ]);
            }
        }
    }
    protected function arriveHQ(
        array $unitIds,
        int $locationId,
        array $mission,
        array $destCoords,
        MissionModel $missionModel,
        UnitModel $unitModel
    ): void {
        if (!empty($unitIds)) {
            $unitModel->setMissionStatus($unitIds, 'Garrisoned', null, $locationId);

            foreach ($unitIds as $unitId) {
                $personnelIds = array_column(
                    $this->db->table('personnel_assignments')
                        ->select('personnel_id')
                        ->where('unit_id', $unitId)
                        ->where('date_released IS NULL', null, false)
                        ->get()->getResultArray(),
                    'personnel_id'
                );
                if (!empty($personnelIds)) {
                    $this->db->table('personnel')
                        ->whereIn('personnel_id', $personnelIds)
                        ->update(['location_id' => $locationId]);
                }
                // Note: equipment NOT moved — HQ staff equipment stays with them
                // but subunit equipment is untouched
            }
        }

        $this->db->table('missions')
            ->where('mission_id', $mission['mission_id'])
            ->update([
                'status'          => 'Arrived',
                'arrived_date'    => $this->gameDate,
                'current_coord_x' => $destCoords['coord_x'],
                'current_coord_y' => $destCoords['coord_y'],
                'days_elapsed'    => $mission['transit_days'],
            ]);
    }
}
