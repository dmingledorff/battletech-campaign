<?php namespace App\Models;

use CodeIgniter\Model;

class MissionModel extends Model
{
    protected $table      = 'missions';
    protected $primaryKey = 'mission_id';
    protected $allowedFields = [
        'name', 'mission_type', 'status',
        'origin_location_id', 'destination_location_id',
        'launched_date', 'eta_date', 'arrived_date',
        'distance', 'transit_days', 'days_elapsed',
        'slowest_speed', 'current_coord_x', 'current_coord_y',
        'faction_id', 'notes'
    ];

    public function getActiveMissions(int $factionId): array
    {
        return $this->db->table('missions m')
            ->select('m.*,
                ol.name AS origin_name, ol.coord_x AS origin_x, ol.coord_y AS origin_y,
                dl.name AS destination_name, dl.coord_x AS dest_x, dl.coord_y AS dest_y,
                op.name AS origin_planet, dp.name AS destination_planet')
            ->join('locations ol', 'ol.location_id = m.origin_location_id')
            ->join('locations dl', 'dl.location_id = m.destination_location_id')
            ->join('planets op', 'op.planet_id = ol.planet_id')
            ->join('planets dp', 'dp.planet_id = dl.planet_id')
            ->where('m.faction_id', $factionId)
            ->whereIn('m.status', ['Planning', 'In Transit'])
            ->orderBy('m.launched_date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getMission(int $missionId): ?array
    {
        return $this->db->table('missions m')
            ->select('m.*,
                ol.name AS origin_name, ol.coord_x AS origin_x, ol.coord_y AS origin_y,
                dl.name AS destination_name, dl.coord_x AS dest_x, dl.coord_y AS dest_y,
                ol.controlled_by AS origin_faction_id,
                dl.controlled_by AS dest_faction_id')
            ->join('locations ol', 'ol.location_id = m.origin_location_id')
            ->join('locations dl', 'dl.location_id = m.destination_location_id')
            ->where('m.mission_id', $missionId)
            ->get()
            ->getRowArray() ?: null;
    }

    public function getMissionUnits(int $missionId): array
    {
        return $this->db->table('mission_units mu')
            ->select('u.unit_id, u.name, u.unit_type, u.role')
            ->join('units u', 'u.unit_id = mu.unit_id')
            ->where('mu.mission_id', $missionId)
            ->get()
            ->getResultArray();
    }

    public function getMissionsByStatus(int $factionId, string $status): array
    {
        return $this->db->table('missions m')
            ->select('m.*,
                ol.name AS origin_name,
                dl.name AS destination_name,
                op.name AS origin_planet,
                dp.name AS destination_planet')
            ->join('locations ol', 'ol.location_id = m.origin_location_id')
            ->join('locations dl', 'dl.location_id = m.destination_location_id')
            ->join('planets op', 'op.planet_id = ol.planet_id')
            ->join('planets dp', 'dp.planet_id = dl.planet_id')
            ->where('m.faction_id', $factionId)
            ->where('m.status', $status)
            ->orderBy('m.launched_date', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function createMission(array $data, array $unitIds): int
    {
        $this->db->transStart();
        $this->insert($data);
        $missionId = $this->db->insertID();

        foreach ($unitIds as $unitId) {
            $this->db->table('mission_units')->insert([
                'mission_id' => $missionId,
                'unit_id'    => (int)$unitId,
            ]);
        }

        $this->db->transComplete();
        return $missionId;
    }

    public function updateMission(int $missionId, array $data, array $unitIds): void
    {
        $this->db->transStart();
        $this->update($missionId, $data);

        // Replace unit list
        $this->db->table('mission_units')
            ->where('mission_id', $missionId)
            ->delete();

        foreach ($unitIds as $unitId) {
            $this->db->table('mission_units')->insert([
                'mission_id' => $missionId,
                'unit_id'    => (int)$unitId,
            ]);
        }

        $this->db->transComplete();
    }

    public function getAvailableUnits(int $locationId, int $excludeMissionId = 0): array
    {
        $builder = $this->db->table('units u')
            ->select('u.unit_id, u.name, u.unit_type, u.role')
            ->where('u.location_id', $locationId)
            ->where('u.unit_id NOT IN (
                SELECT DISTINCT parent_unit_id FROM units
                WHERE parent_unit_id IS NOT NULL
            )', null, false);

        // Exclude units already on another active mission
        $builder->where('u.unit_id NOT IN (
            SELECT mu.unit_id FROM mission_units mu
            JOIN missions m ON m.mission_id = mu.mission_id
            WHERE m.status IN (\'Planning\', \'In Transit\')
            AND m.mission_id != ' . (int)$excludeMissionId . '
        )', null, false);

        return $builder->orderBy('u.unit_type')->orderBy('u.name')->get()->getResultArray();
    }

    public function getSlowestSpeed(array $unitIds): float
    {
        if (empty($unitIds)) return 0.0;

        $row = $this->db->table('equipment e')
            ->select('MIN(c.speed) AS slowest')
            ->join('chassis c', 'c.chassis_id = e.chassis_id')
            ->whereIn('e.assigned_unit_id', $unitIds)
            ->where('e.equipment_status', 'Active')
            ->get()
            ->getRowArray();

        return (float)($row['slowest'] ?? 0.0);
    }

    public function calculateDistance(float $x1, float $y1, float $x2, float $y2): float
    {
        return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    }

    public function calculateTransitDays(float $distance, float $speedKph): int
    {
        $gameState      = new \App\Models\GameStateModel();
        $kmPerCoord     = (float)$gameState->getProperty('km_per_coord_unit') ?? 40;
        $speedEfficiency = (float)$gameState->getProperty('speed_efficiency') ?? 0.7;

        if ($speedKph <= 0) return 999;
        $km          = $distance * $kmPerCoord;
        $effectiveKph = $speedKph * $speedEfficiency;
        $days        = $km / ($effectiveKph * 24);
        return (int)ceil($days);
    }

    public function logEvent(int $missionId, string $gameDate, string $eventType, string $description): void
    {
        $this->db->table('mission_log')->insert([
            'mission_id'  => $missionId,
            'game_date'   => $gameDate,
            'event_type'  => $eventType,
            'description' => $description,
        ]);
    }

    public function getLog(int $missionId): array
    {
        return $this->db->table('mission_log')
            ->where('mission_id', $missionId)
            ->orderBy('game_date', 'DESC')
            ->get()
            ->getResultArray();
    }
}