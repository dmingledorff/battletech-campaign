<?php

namespace App\Models;

use CodeIgniter\Model;

class CombatModel extends Model
{
    protected $table      = 'missions';
    protected $primaryKey = 'mission_id';

    public function getActiveCombat(int $factionId): array
    {
        return $this->db->table('missions m')
            ->select('m.*,
            ol.name AS origin_name,
            dl.name AS destination_name,
            dl.terrain,
            op.name AS origin_planet,
            dp.name AS destination_planet,
            f.name AS enemy_faction_name,
            f.color AS enemy_faction_color')
            ->join('locations ol', 'ol.location_id = m.origin_location_id')
            ->join('locations dl', 'dl.location_id = m.destination_location_id')
            ->join('planets op',   'op.planet_id = ol.planet_id')
            ->join('planets dp',   'dp.planet_id = dl.planet_id')
            ->join('factions f',   'f.faction_id = dl.controlled_by', 'left')
            ->where('m.status', 'Combat')
            ->groupStart()
            ->where('m.faction_id', $factionId)           // attacker
            ->orWhere('dl.controlled_by', $factionId)     // defender
            ->groupEnd()
            ->orderBy('m.arrived_date', 'DESC')
            ->get()->getResultArray();
    }

    public function getCompletedCombat(int $factionId, int $limit = 20): array
    {
        return $this->db->table('missions m')
            ->select('m.*,
                ol.name AS origin_name,
                dl.name AS destination_name,
                dl.terrain,
                op.name AS origin_planet,
                dp.name AS destination_planet')
            ->join('locations ol', 'ol.location_id = m.origin_location_id')
            ->join('locations dl', 'dl.location_id = m.destination_location_id')
            ->join('planets op',   'op.planet_id = ol.planet_id')
            ->join('planets dp',   'dp.planet_id = dl.planet_id')
            ->where('m.faction_id', $factionId)
            ->whereIn('m.status', ['Arrived', 'Aborted'])
            ->where('m.mission_type', 'Assault')
            ->where('m.combat_round >', 0)
            ->orderBy('m.arrived_date', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    public function getCombatMission(int $missionId): ?array
    {
        return $this->db->table('missions m')
            ->select('m.*,
                ol.name AS origin_name,
                dl.name AS destination_name,
                dl.terrain, dl.type AS location_type,
                dl.controlled_by AS current_controller,
                dl.location_id AS dest_location_id,
                op.name AS origin_planet,
                dp.name AS destination_planet,
                fc.name AS controlling_faction_name,
                fc.color AS controlling_faction_color')
            ->join('locations ol', 'ol.location_id = m.origin_location_id')
            ->join('locations dl', 'dl.location_id = m.destination_location_id')
            ->join('planets op',   'op.planet_id = ol.planet_id')
            ->join('planets dp',   'dp.planet_id = dl.planet_id')
            ->join('factions fc',  'fc.faction_id = dl.controlled_by', 'left')
            ->where('m.mission_id', $missionId)
            ->get()->getRowArray() ?: null;
    }

    public function getMissionUnitCount(int $missionId): int
    {
        return $this->db->table('mission_units')
            ->where('mission_id', $missionId)
            ->countAllResults();
    }

    public function hasFortification(int $locationId): bool
    {
        return $this->db->table('buildings')
            ->where('location_id', $locationId)
            ->where('type', 'Fortification')
            ->where('status', 'Operational')
            ->countAllResults() > 0;
    }

    public function getAttackerCombatants(int $missionId): array
    {
        // Get all unit IDs in the mission, then resolve to leaves
        $missionUnitIds = array_column(
            $this->db->table('mission_units')
                ->select('unit_id')
                ->where('mission_id', $missionId)
                ->get()->getResultArray(),
            'unit_id'
        );

        $leafIds = [];
        foreach ($missionUnitIds as $uid) {
            $this->resolveLeafIds((int)$uid, $leafIds);
        }

        if (empty($leafIds)) return [];
        $idList = implode(',', $leafIds);

        return $this->db->query("
            SELECT u.unit_id, u.name AS unit_name, u.unit_type, u.role,
                e.equipment_id, e.current_armor, e.current_structure,
                e.max_armor, e.max_structure, e.combat_status,
                e.equipment_status, e.salvage_status,
                c.name AS chassis_name, c.variant, c.as_type, c.as_size,
                c.as_mv, c.as_tmm, c.as_dmg_s, c.as_dmg_m, c.as_dmg_l,
                c.as_specials,
                p.first_name, p.last_name, p.experience, p.morale,
                p.status AS pilot_status,
                r.abbreviation AS rank_abbr
            FROM units u
            LEFT JOIN equipment e
                ON e.assigned_unit_id = u.unit_id
                AND e.equipment_status != 'Destroyed'
            LEFT JOIN chassis c ON c.chassis_id = e.chassis_id
            LEFT JOIN personnel_equipment pe
                ON pe.equipment_id = e.equipment_id
                AND pe.date_released IS NULL
            LEFT JOIN personnel p ON p.personnel_id = pe.personnel_id
            LEFT JOIN ranks r ON r.id = p.rank_id
            WHERE u.unit_id IN ({$idList})
            ORDER BY u.name, c.name
        ")->getResultArray();
    }

    public function getDefenderCombatants(int $missionId): array
    {
        $mission = $this->db->table('missions')
            ->select('destination_location_id, faction_id')
            ->where('mission_id', $missionId)
            ->get()->getRowArray();

        if (!$mission) return [];

        $locationId       = (int)$mission['destination_location_id'];
        $missionFactionId = (int)$mission['faction_id'];

        // Get all defender units at location
        $topUnits = $this->db->table('units')
            ->select('unit_id')
            ->where('location_id', $locationId)
            ->where('faction_id !=', $missionFactionId)
            ->whereIn('status', ['Garrisoned', 'Combat'])
            ->get()->getResultArray();

        if (empty($topUnits)) return [];

        // Resolve to leaf units
        $leafIds = [];
        foreach ($topUnits as $unit) {
            $this->resolveLeafIds((int)$unit['unit_id'], $leafIds);
        }

        // Deduplicate
        $leafIds = array_unique($leafIds);

        if (empty($leafIds)) return [];
        $idList = implode(',', $leafIds);

        return $this->db->query("
            SELECT u.unit_id, u.name AS unit_name, u.unit_type, u.role,
                u.faction_id,
                f.name AS faction_name, f.color AS faction_color,
                e.equipment_id, e.current_armor, e.current_structure,
                e.max_armor, e.max_structure, e.combat_status,
                e.equipment_status, e.salvage_status,
                c.name AS chassis_name, c.variant, c.as_type, c.as_size,
                c.as_mv, c.as_tmm, c.as_dmg_s, c.as_dmg_m, c.as_dmg_l,
                c.as_specials,
                p.first_name, p.last_name, p.experience, p.morale,
                p.status AS pilot_status,
                r.abbreviation AS rank_abbr, r.full_name AS rank_full
            FROM units u
            JOIN factions f ON f.faction_id = u.faction_id
            LEFT JOIN equipment e
                ON e.assigned_unit_id = u.unit_id
                AND e.equipment_status != 'Destroyed'
            LEFT JOIN chassis c ON c.chassis_id = e.chassis_id
            LEFT JOIN personnel_equipment pe
                ON pe.equipment_id = e.equipment_id
                AND pe.date_released IS NULL
            LEFT JOIN personnel p ON p.personnel_id = pe.personnel_id
            LEFT JOIN ranks r ON r.id = p.rank_id
            WHERE u.unit_id IN ({$idList})
            ORDER BY u.faction_id, u.name, c.name
        ")->getResultArray();
    }

    protected function resolveLeafIds(int $unitId, array &$leafIds): void
    {
        $children = $this->db->table('units')
            ->select('unit_id')
            ->where('parent_unit_id', $unitId)
            ->whereIn('unit_type', ['Lance', 'Squad', 'Company', 'Platoon'])
            ->get()->getResultArray();

        if (empty($children)) {
            $leafIds[] = $unitId;
            return;
        }

        foreach ($children as $child) {
            $this->resolveLeafIds((int)$child['unit_id'], $leafIds);
        }
    }
}
