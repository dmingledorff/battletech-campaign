<?php namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use App\Models\GameStateModel;

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
        $missionModel = new \App\Models\MissionModel();

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
        $missionModel    = new \App\Models\MissionModel();
        $destLocationId  = $mission['destination_location_id'];
        $destFactionId   = (int)($dest['controlled_by'] ?? 0);
        $missionFactionId = (int)$mission['faction_id'];
        $isFriendly      = ($destFactionId === $missionFactionId || $destFactionId === 0);

        $unitIds = array_column(
            $this->db->table('mission_units')
                ->where('mission_id', $mission['mission_id'])
                ->get()->getResultArray(),
            'unit_id'
        );

        if ($isFriendly) {
            if (!empty($unitIds)) {
                $this->db->table('units')
                    ->whereIn('unit_id', $unitIds)
                    ->update(['location_id' => $destLocationId]);

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

                $this->db->table('equipment')
                    ->whereIn('assigned_unit_id', $unitIds)
                    ->update(['location_id' => $destLocationId]);
            }

            $this->db->table('missions')
                ->where('mission_id', $mission['mission_id'])
                ->update([
                    'status'          => 'Arrived',
                    'arrived_date'    => $this->gameDate,
                    'current_coord_x' => $dest['coord_x'],
                    'current_coord_y' => $dest['coord_y'],
                    'days_elapsed'    => $mission['transit_days'],
                ]);

            $missionModel->logEvent(
                $mission['mission_id'],
                $this->gameDate,
                'Arrived',
                "Mission arrived at {$dest['name']}. " . count($unitIds) . " units transferred."
            );
        } else {
            $this->db->table('missions')
                ->where('mission_id', $mission['mission_id'])
                ->update(['status' => 'Combat']);

            $missionModel->logEvent(
                $mission['mission_id'],
                $this->gameDate,
                'Combat',
                "Mission arrived at {$dest['name']} but destination is now enemy controlled. Combat initiated."
            );
        }
    }
}
