<?php namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use App\Models\GameStateModel;

class GameTickService
{
    protected $db;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Main entry point to process a tick.
     */
    public function processTick(int $days = 1): void
    {
        // Step 1: Advance game date
        $this->advanceDate($days);

        // Step 2: Run subsystems
        //$this->processSupplies();
        $this->processMorale();
        //$this->processMaintenance();
        $this->processMissions();
        //$this->processEvents();

        // Add more subsystems here as needed
        $this->updateTick();
    }

    protected function updateTick() {
        $gameState = new GameStateModel();
        // Increment tick count
        $tickCount = (int)$gameState->getProperty('tick_count', 0);
        $gameState->setProperty('tick_count', $tickCount + 1);
    }

    protected function advanceDate(int $days): void
    {
        $gameState = new GameStateModel();

        $currentDate = $gameState->getProperty('current_date');
        $dateObj = new \DateTime($currentDate);

        $dateObj->modify("+{$days} days");

        $gameState->setProperty('current_date', $dateObj->format('Y-m-d'));
    }

    protected function processSupplies(): void
    {
        // Example: Deduct daily supply usage for each unit
        $units = $this->db->table('units')->get()->getResultArray();
        foreach ($units as $unit) {
            $dailyUse = $unit['daily_supply_use'] ?? 0;
            if ($dailyUse > 0) {
                $newSupply = max(0, $unit['current_supply'] - $dailyUse);
                $this->db->table('units')
                    ->where('unit_id', $unit['unit_id'])
                    ->update(['current_supply' => $newSupply]);
            }
        }
    }

    protected function processMorale(): void
    {
        // Example: Slowly increase morale if supplies are good
        $personnel = $this->db->table('personnel')->get()->getResultArray();
        foreach ($personnel as $p) {
            $morale = $p['morale'] ?? 100;
            if ($morale < 100) {
                $morale = min(100, $morale + 2.0); // tiny recovery
                $this->db->table('personnel')
                    ->where('personnel_id', $p['personnel_id'])
                    ->update(['morale' => $morale]);
            }
        }
    }

    protected function processMaintenance(): void
    {
        // Example: track equipment wear or repair jobs
    }

    protected function processMissions(): void
    {
        $gameState   = new \App\Models\GameStateModel();
        $gameDate    = $gameState->getProperty('current_date');
        $missionModel = new \App\Models\MissionModel();

        // Get all in-transit missions
        $missions = $this->db->table('missions')
            ->where('status', 'In Transit')
            ->get()
            ->getResultArray();

        foreach ($missions as $mission) {
            $elapsed     = (int)$mission['days_elapsed'] + 1;
            $transitDays = (int)$mission['transit_days'];
            $progress = min(1.0, $elapsed / max(1, $transitDays));

            // Get origin/dest coords from locations
            $origin = $this->db->table('locations')
                ->where('location_id', $mission['origin_location_id'])
                ->get()->getRowArray();
            $dest = $this->db->table('locations')
                ->where('location_id', $mission['destination_location_id'])
                ->get()->getRowArray();

            $currentX = $origin['coord_x'] + ($progress * ($dest['coord_x'] - $origin['coord_x']));
            $currentY = $origin['coord_y'] + ($progress * ($dest['coord_y'] - $origin['coord_y']));

            if ($elapsed >= $transitDays) {
                // Mission arrived
                $this->completeMission($mission, $gameDate, $dest);
            } else {
                // Update progress
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

    protected function completeMission(array $mission, string $gameDate, array $dest): void
    {
        $missionModel = new \App\Models\MissionModel();

        // Check if destination is still friendly
        $destLocationId    = $mission['destination_location_id'];
        $destFactionId     = (int)$dest['controlled_by'];
        $missionFactionId  = (int)$mission['faction_id'];
        $destinationFriendly = ($destFactionId === $missionFactionId || $destFactionId === 0);

        // Get mission units
        $unitIds = array_column(
            $this->db->table('mission_units')
                ->where('mission_id', $mission['mission_id'])
                ->get()->getResultArray(),
            'unit_id'
        );

        if ($destinationFriendly) {
            // Move all units and personnel to destination
            $this->db->table('units')
                ->whereIn('unit_id', $unitIds)
                ->update(['location_id' => $destLocationId]);

            // Update personnel location
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
                        ->update(['location_id' => $destLocationId]);
                }
            }

            // Update equipment location
            $this->db->table('equipment')
                ->whereIn('assigned_unit_id', $unitIds)
                ->update(['location_id' => $destLocationId]);

            // Mark mission complete
            $this->db->table('missions')
                ->where('mission_id', $mission['mission_id'])
                ->update([
                    'status'          => 'Arrived',
                    'arrived_date'    => $gameDate,
                    'current_coord_x' => $dest['coord_x'],
                    'current_coord_y' => $dest['coord_y'],
                    'days_elapsed'    => $mission['transit_days'],
                ]);

            $missionModel->logEvent(
                $mission['mission_id'],
                $gameDate,
                'Arrived',
                "Mission arrived at {$dest['name']}. " . count($unitIds) . " units transferred."
            );

        } else {
            // Destination taken by enemy — flag for combat (handle later)
            $this->db->table('missions')
                ->where('mission_id', $mission['mission_id'])
                ->update(['status' => 'Combat']);

            $missionModel->logEvent(
                $mission['mission_id'],
                $gameDate,
                'Combat',
                "Mission arrived at {$dest['name']} but destination is now enemy controlled. Combat initiated."
            );
        }
    }

    protected function processEvents(): void
    {
        // Example: trigger random events based on date
    }
}
