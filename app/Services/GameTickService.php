<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use App\Models\GameStateModel;
use App\Models\MissionModel;
use App\Models\UnitModel;
use App\Services\CombatService;

class GameTickService
{
    protected $db;
    protected string $gameDate;
    protected int    $currentHour;
    protected bool   $dayRollover = false;
    protected GameStateModel $gameStateModel;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db             = $db ?? db_connect();
        $this->gameStateModel = new GameStateModel();
        $this->gameDate       = $this->gameStateModel->getProperty('current_date') ?? '3025-01-01';
        $this->currentHour    = (int)($this->gameStateModel->getProperty('current_hour') ?? 0);
    }

    public function processTick(): void
    {
        $this->advanceTime();

        // Every tick
        $this->processCombat();
        $this->processMissions();
        $this->updateMissionCoords();

        // Once per game day only
        if ($this->dayRollover) {
            //$this->processMorale();
            $this->processDispersedUnits();
            $this->updateDayCount();
        }
    }

    protected function advanceTime(): void
    {
        $hoursPerTick    = (int)($this->gameStateModel->getProperty('hours_per_tick') ?? 3);
        $newHour         = $this->currentHour + $hoursPerTick;

        if ($newHour >= 24) {
            $newHour          -= 24;
            $this->dayRollover = true;
            $this->advanceDate(1);
            $this->gameDate    = $this->gameStateModel->getProperty('current_date') ?? $this->gameDate;
        }

        $this->currentHour = $newHour;
        $this->gameStateModel->setProperty('current_hour', (string)$newHour);
    }

    protected function advanceDate(int $days): void
    {
        $dateObj = new \DateTime($this->gameDate);
        $dateObj->modify("+{$days} days");
        $this->gameStateModel->setProperty('current_date', $dateObj->format('Y-m-d'));
    }


    protected function updateDayCount(): void
    {
        $count = (int)($this->gameStateModel->getProperty('day_count') ?? 0);
        $this->gameStateModel->setProperty('day_count', $count + 1);
    }

    protected function processCombat(): void
    {
        $combatService = new CombatService($this->db);
        $combatService->processAllCombat();
    }

    protected function updateMissionCoords(): void
    {
        $missions = $this->db->table('missions')
            ->where('status', 'In Transit')
            ->get()->getResultArray();

        foreach ($missions as $mission) {
            $hoursElapsed = (int)$mission['hours_elapsed'];
            $transitHours = (int)$mission['transit_hours'];

            if ($transitHours === 0 && (int)$mission['transit_days'] > 0) {
                $transitHours = (int)$mission['transit_days'] * 24;
            }

            if ($transitHours <= 0) continue;

            $progress = min(1.0, $hoursElapsed / $transitHours);

            $origin = $this->db->table('locations')
                ->where('location_id', $mission['origin_location_id'])
                ->get()->getRowArray();

            $dest = $this->db->table('locations')
                ->where('location_id', $mission['destination_location_id'])
                ->get()->getRowArray();

            if (!$origin || !$dest) continue;

            $currentX = $origin['coord_x'] + ($progress * ($dest['coord_x'] - $origin['coord_x']));
            $currentY = $origin['coord_y'] + ($progress * ($dest['coord_y'] - $origin['coord_y']));

            $this->db->table('missions')
                ->where('mission_id', $mission['mission_id'])
                ->update([
                    'current_coord_x' => round($currentX, 4),
                    'current_coord_y' => round($currentY, 4),
                ]);
        }
    }


    protected function processMorale(): void
    {
        // Only recover morale for personnel NOT in active combat
        $personnel = $this->db->query("
            SELECT p.personnel_id, p.morale
            FROM personnel p
            WHERE p.status = 'Active'
            AND p.morale < 100
            AND p.personnel_id NOT IN (
                SELECT DISTINCT pe.personnel_id
                FROM personnel_equipment pe
                JOIN equipment e ON e.equipment_id = pe.equipment_id
                JOIN units u ON u.unit_id = e.assigned_unit_id
                WHERE u.status = 'Combat'
                AND pe.date_released IS NULL
            )
        ")->getResultArray();

        foreach ($personnel as $p) {
            $newMorale = min(100, (float)$p['morale'] + 2.0);
            $this->db->table('personnel')
                ->where('personnel_id', $p['personnel_id'])
                ->update(['morale' => $newMorale]);
        }
    }

    protected function processMissions(): void
    {
        $hoursPerTick = (int)($this->gameStateModel->getProperty('hours_per_tick') ?? 3);

        $missions = $this->db->table('missions')
            ->where('status', 'In Transit')
            ->get()->getResultArray();

        foreach ($missions as $mission) {
            $hoursElapsed = (int)$mission['hours_elapsed'] + $hoursPerTick;
            $transitHours = (int)$mission['transit_hours'];

            // Fallback for missions without transit_hours
            if ($transitHours === 0 && (int)$mission['transit_days'] > 0) {
                $transitHours = (int)$mission['transit_days'] * 24;
            }

            $dest = $this->db->table('locations')
                ->where('location_id', $mission['destination_location_id'])
                ->get()->getRowArray();

            if ($hoursElapsed >= $transitHours) {
                $this->completeMission($mission, $dest);
            } else {
                $this->db->table('missions')
                    ->where('mission_id', $mission['mission_id'])
                    ->update([
                        'hours_elapsed' => $hoursElapsed,
                        'days_elapsed'  => (int)floor($hoursElapsed / 24),
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
            $u = $this->db->table('units')->where('unit_id', $uid)->get()->getRowArray();
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
        // Transfer / Resupply
        // ================================
        if (in_array($missionType, ['Transfer', 'Resupply'])) {
            $isReturningFromDefeat = str_contains($mission['notes'] ?? '', '[DEFEATED — RETURNING]');

            if ($isFriendlyNow) {
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
                        $isReturningFromDefeat
                            ? "Surviving units returned to {$dest['name']} after failed assault."
                            : "Mission arrived at {$dest['name']}. " . count($unitIds) . " units transferred."
                    );
                }
            } elseif ($isUncontrolled) {
                $this->takeControl($destLocationId, $missionFactionId);
                $isHQMove
                    ? $this->arriveHQ($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel)
                    : $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Transfer arrived at {$dest['name']} — location became uncontrolled during transit. Units secured it."
                );
            } else {
                // Enemy took it during transit
                if ($enemyPresent) {
                    $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Aborted',
                        "Transfer to {$dest['name']} aborted — location captured by enemy with active garrison. Units returning to origin."
                    );
                } else {
                    $this->takeControl($destLocationId, $missionFactionId);
                    $isHQMove
                        ? $this->arriveHQ($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel)
                        : $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Transfer arrived at {$dest['name']} — enemy claimed it during transit but no garrison. Units secured it."
                    );
                }
            }
            return;
        }

        // ================================
        // Withdrawal
        // ================================
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
                $this->takeControl($destLocationId, $missionFactionId);
                $this->arriveHQ($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Units withdrew to {$dest['name']} — location was uncontrolled, now secured."
                );
            } else {
                $nearest = $this->findNearestFriendlyLocation($destLocationId, $missionFactionId);
                if ($nearest) {
                    $this->returnToOrigin($unitIds, $nearest['location_id'], $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Aborted',
                        "Withdrawal destination {$dest['name']} captured — rerouting to {$nearest['name']}."
                    );
                } else {
                    $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Withdrawal to {$dest['name']} failed — no friendly locations available. Units trapped. [COMBAT TODO]"
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
                    "Recon completed. {$dest['name']} uncontrolled — player takes control. Units returning to origin."
                );
            } elseif ($missionType === 'Assault') {
                $this->takeControl($destLocationId, $missionFactionId);
                $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Assault on uncontrolled {$dest['name']} successful. Units garrisoning location."
                );
            }
            return;
        }

        // ================================
        // Target became friendly during transit
        // ================================
        if ($isFriendlyNow) {
            if (in_array($missionType, ['Assault', 'Harass'])) {
                $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "{$missionType} mission arrived at {$dest['name']} — now under friendly control. Units garrisoned."
                );
            } elseif ($missionType === 'Recon') {
                $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Recon reached {$dest['name']} — now under friendly control. Units returning to origin."
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
                        "Recon reached {$dest['name']}. Enemy present — intel gathered. Units returning. [COMBAT TODO]"
                    );
                } else {
                    $this->takeControl($destLocationId, $missionFactionId);
                    $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Recon reached {$dest['name']}. No enemy — player takes control. Units returning to origin."
                    );
                }
            } elseif ($missionType === 'Assault') {
                if ($enemyPresent) {
                    $combatService = new CombatService($this->db);
                    $combatService->initiateCombat($mission, $dest);
                    return;
                } else {
                    $this->takeControl($destLocationId, $missionFactionId);
                    $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'],
                        $this->gameDate,
                        'Arrived',
                        "Assault on {$dest['name']} — no enemy present. Player takes control. Units garrisoning."
                    );
                }
            } elseif ($missionType === 'Harass') {
                $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'],
                    $this->gameDate,
                    'Arrived',
                    "Harassment at {$dest['name']} completed. Units returning to origin. [HARASS TODO]"
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

        $unitModel->syncDispersedStatus();
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
                // Subunit equipment stays in place — only HQ personnel move
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

    protected function returnToOrigin(
        array $unitIds,
        int $originLocationId,
        array $mission,
        MissionModel $missionModel,
        UnitModel $unitModel
    ): void {
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

        $returnHours = $missionModel->calculateTransitHours(
            $returnDistance,
            (float)$mission['slowest_speed']
        );
        $returnDays  = (int)ceil($returnHours / 24);

        $etaDate = new \DateTime($this->gameDate);
        $etaDate->modify("+{$returnDays} days");

        $this->db->table('missions')
            ->where('mission_id', $mission['mission_id'])
            ->update([
                'status'                  => 'In Transit',
                'origin_location_id'      => $mission['destination_location_id'],
                'destination_location_id' => $originLocationId,
                'transit_hours'           => $returnHours,
                'transit_days'            => $returnDays,
                'hours_elapsed'           => 0,
                'days_elapsed'            => 0,
                'eta_date'                => $etaDate->format('Y-m-d'),
                'notes'                   => ($mission['notes'] ?? '') . ' [RETURNING TO BASE]',
            ]);

        $unitModel->setMissionStatus($unitIds, 'In Transit', $mission['mission_id']);
        $unitModel->syncDispersedStatus();
    }

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
            if (!$nearest) continue;

            $distance    = $missionModel->calculateDistance(
                (float)$from['coord_x'],
                (float)$from['coord_y'],
                (float)$nearest['coord_x'],
                (float)$nearest['coord_y']
            );
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

        $candidates = $this->db->table('locations')
            ->where('controlled_by', $factionId)
            ->where('planet_id', $from['planet_id'])
            ->where('location_id !=', $fromLocationId)
            ->get()->getResultArray();

        if (empty($candidates)) return null;

        usort($candidates, function ($a, $b) use ($from) {
            $distA = sqrt(pow($a['coord_x'] - $from['coord_x'], 2) + pow($a['coord_y'] - $from['coord_y'], 2));
            $distB = sqrt(pow($b['coord_x'] - $from['coord_x'], 2) + pow($b['coord_y'] - $from['coord_y'], 2));
            return $distA <=> $distB;
        });

        return $candidates[0];
    }

    protected function processDispersedUnits(): void
    {
        $unitModel = new UnitModel();
        $unitModel->syncDispersedStatus();
    }
}
