<?php namespace App\Services;

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
        // Refresh game date after advancing
        $this->gameDate = $this->gameStateModel->getProperty('current_date') ?? '3025-01-01';

        $this->processMorale();
        $this->processMissions();
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

        // Check if enemy has units garrisoned at destination
        $enemyPresent = false;
        if ($destFactionId !== 0 && $destFactionId !== $missionFactionId) {
            $enemyPresent = $this->db->table('units')
                ->where('location_id', $destLocationId)
                ->where('faction_id !=', $missionFactionId)
                ->where('status', 'Garrisoned')
                ->countAllResults() > 0;
        }

        $isFriendly     = $destFactionId === $missionFactionId;
        $isUncontrolled = $destFactionId === 0;
        $isEnemy        = !$isFriendly && !$isUncontrolled;

        // ================================
        // Friendly destination — transfer
        // ================================
        if ($isFriendly) {
            $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
            $missionModel->logEvent(
                $mission['mission_id'], $this->gameDate, 'Arrived',
                "Mission arrived at {$dest['name']}. " . count($unitIds) . " units transferred."
            );
            return;
        }

        // ================================
        // Uncontrolled destination
        // ================================
        if ($isUncontrolled) {
            if ($missionType === 'Recon') {
                // Take control, return units to origin
                $this->takeControl($destLocationId, $missionFactionId);
                $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'], $this->gameDate, 'Arrived',
                    "Recon mission completed. {$dest['name']} is uncontrolled — player takes control. Units returning to origin."
                );

            } elseif ($missionType === 'Assault') {
                // Take control, garrison destination
                $this->takeControl($destLocationId, $missionFactionId);
                $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'], $this->gameDate, 'Arrived',
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
                    // TODO: possible combat — for now units return with log entry
                    $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'], $this->gameDate, 'Arrived',
                        "Recon mission reached {$dest['name']}. Enemy forces present — intel gathered. Units returning to origin. [COMBAT TODO]"
                    );
                } else {
                    // Ungarrisoned enemy location — take control, return units
                    $this->takeControl($destLocationId, $missionFactionId);
                    $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'], $this->gameDate, 'Arrived',
                        "Recon mission reached {$dest['name']}. No enemy forces present — player takes control. Units returning to origin."
                    );
                }

            } elseif ($missionType === 'Assault') {
                if ($enemyPresent) {
                    // TODO: combat logic — stub for now, mark as Combat status
                    $this->db->table('missions')
                        ->where('mission_id', $mission['mission_id'])
                        ->update([
                            'status'          => 'Combat',
                            'arrived_date'    => $this->gameDate,
                            'current_coord_x' => $dest['coord_x'],
                            'current_coord_y' => $dest['coord_y'],
                            'days_elapsed'    => $mission['transit_days'],
                        ]);
                    // Units arrive at location for combat staging
                    $unitModel->setMissionStatus($unitIds, 'Combat', $mission['mission_id'], $destLocationId);
                    $missionModel->logEvent(
                        $mission['mission_id'], $this->gameDate, 'Combat',
                        "Assault force arrived at {$dest['name']}. Enemy forces present — combat initiated. [COMBAT TODO]"
                    );
                    return; // Don't fall through to standard arrival

                } else {
                    // Ungarrisoned — take control and garrison
                    $this->takeControl($destLocationId, $missionFactionId);
                    $this->arriveAtLocation($unitIds, $destLocationId, $mission, $dest, $missionModel, $unitModel);
                    $missionModel->logEvent(
                        $mission['mission_id'], $this->gameDate, 'Arrived',
                        "Assault on {$dest['name']} successful — no enemy forces present. Player takes control. Units garrisoning location."
                    );
                }

            } elseif ($missionType === 'Harass') {
                // TODO: harass logic — units return after harassment
                $this->returnToOrigin($unitIds, $originLocationId, $mission, $missionModel, $unitModel);
                $missionModel->logEvent(
                    $mission['mission_id'], $this->gameDate, 'Arrived',
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
            $currentX, $currentY,
            (float)$origin['coord_x'], (float)$origin['coord_y']
        );
        $returnDays = $missionModel->calculateTransitDays(
            $returnDistance, (float)$mission['slowest_speed']
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
}
