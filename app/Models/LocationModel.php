<?php namespace App\Models;

use CodeIgniter\Model;

class LocationModel extends Model
{
    protected $table      = 'locations';
    protected $primaryKey = 'location_id';
    protected $allowedFields = [
        'name', 'type', 'terrain', 'planet_id',
        'coord_x', 'coord_y', 'controlled_by', 'supply_cache'
    ];

    public function getByPlanet(int $planetId): array
    {
        return $this->where('planet_id', $planetId)->findAll();
    }

    public function getLocation(int $locationId): ?array
    {
        return $this->db->table('locations l')
            ->select('l.*, p.name AS planet_name, p.planet_id,
                      f.name AS controlled_by_name, f.color AS controlled_by_color,
                      f.faction_id AS controlled_by_faction_id')
            ->join('planets p', 'p.planet_id = l.planet_id', 'left')
            ->join('factions f', 'f.faction_id = l.controlled_by', 'left')
            ->where('l.location_id', $locationId)
            ->get()
            ->getRowArray() ?: null;
    }

    public function getBuildings(int $locationId): array
    {
        return $this->db->table('buildings')
            ->where('location_id', $locationId)
            ->orderBy('type')
            ->get()
            ->getResultArray();
    }

    public function getUnitsAtLocation(int $locationId): array
    {
        // Only lowest level units (no children)
        return $this->db->table('units u')
            ->select('u.unit_id, u.name, u.unit_type, u.role,
                      u.parent_unit_id, f.name AS faction_name,
                      f.color AS faction_color')
            ->join('factions f', 'f.faction_id = u.faction_id', 'left')
            ->where('u.location_id', $locationId)
            ->where('u.unit_id NOT IN (
                SELECT DISTINCT parent_unit_id FROM units
                WHERE parent_unit_id IS NOT NULL
            )', null, false)
            ->orderBy('f.name')
            ->orderBy('u.unit_type')
            ->get()
            ->getResultArray();
    }

    public function getPersonnelAtLocation(int $locationId): array
    {
        return $this->db->table('personnel p')
            ->select('p.personnel_id, p.first_name, p.last_name,
                      p.status, p.mos, p.morale,
                      r.abbreviation AS rank_abbr,
                      u.unit_id, u.name AS unit_name,
                      f.name AS faction_name')
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->join('personnel_assignments pa',
                'pa.personnel_id = p.personnel_id AND pa.date_released IS NULL', 'left')
            ->join('units u', 'u.unit_id = pa.unit_id', 'left')
            ->join('factions f', 'f.faction_id = p.faction_id', 'left')
            ->where('p.location_id', $locationId)
            ->orderBy('r.grade', 'DESC')
            ->orderBy('p.last_name')
            ->get()
            ->getResultArray();
    }

    public function getEquipmentAtLocation(int $locationId): array
    {
        return $this->db->table('equipment e')
            ->select('e.equipment_id, e.serial_number, e.equipment_status,
                      e.damage_percentage, e.assigned_unit_id,
                      c.name AS chassis_name, c.variant AS chassis_variant,
                      c.type AS chassis_type, c.weight_class,
                      u.name AS unit_name,
                      f.name AS faction_name')
            ->join('chassis c', 'c.chassis_id = e.chassis_id')
            ->join('units u', 'u.unit_id = e.assigned_unit_id', 'left')
            ->join('factions f', 'f.faction_id = e.faction_id', 'left')
            ->where('e.location_id', $locationId)
            ->orderBy('f.name')
            ->orderBy('c.type')
            ->orderBy('c.weight_class')
            ->get()
            ->getResultArray();
    }

    public function getLocationsWithFactions(int $planetId): array
    {
        return $this->db->table('locations l')
            ->select('l.*, f.name AS faction_name, f.emblem_path AS faction_emblem,
                    f.color AS faction_color, f.faction_id AS controlling_faction_id')
            ->join('factions f', 'f.faction_id = l.controlled_by', 'left')
            ->where('l.planet_id', $planetId)
            ->orderBy('l.display_order')
            ->get()
            ->getResultArray();
    }
}