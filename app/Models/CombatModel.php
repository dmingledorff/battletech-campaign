<?php

namespace App\Models;

use CodeIgniter\Model;

class CombatModel extends Model
{
    protected $table      = 'missions';
    protected $primaryKey = 'mission_id';

    public function getActiveCombatMissions(int $factionId): array
    {
        return $this->db->query("
            SELECT DISTINCT m.*,
                l.name AS destination_name, l.terrain,
                pl.name AS destination_planet,
                f.name AS faction_name, f.color AS faction_color,
                f.emblem_path AS faction_emblem
            FROM missions m
            JOIN locations l ON l.location_id = m.destination_location_id
            JOIN planets pl ON pl.planet_id = l.planet_id
            JOIN factions f ON f.faction_id = m.faction_id
            WHERE m.status = 'Combat'
            AND (
                m.faction_id = {$factionId}
                OR m.mission_id IN (
                    SELECT DISTINCT cp.mission_id
                    FROM combat_pool cp
                    JOIN units u ON u.unit_id = cp.unit_id
                    WHERE u.faction_id = {$factionId}
                )
            )
            ORDER BY m.launched_date DESC
        ")->getResultArray();
    }

    public function getConcludedCombatMissions(int $factionId): array
    {
        return $this->db->query("
            SELECT DISTINCT m.*,
                l.name AS destination_name, l.terrain,
                pl.name AS destination_planet,
                f.name AS faction_name, f.color AS faction_color,
                f.emblem_path AS faction_emblem
            FROM missions m
            JOIN locations l ON l.location_id = m.destination_location_id
            JOIN planets pl ON pl.planet_id = l.planet_id
            JOIN factions f ON f.faction_id = m.faction_id
            WHERE m.status IN ('Arrived', 'Aborted')
            AND m.mission_type = 'Assault'
            AND (
                m.faction_id = {$factionId}
                OR m.mission_id IN (
                    SELECT DISTINCT cp.mission_id
                    FROM combat_pool cp
                    JOIN units u ON u.unit_id = cp.unit_id
                    WHERE u.faction_id = {$factionId}
                )
            )
            ORDER BY m.arrived_date DESC
            LIMIT 20
        ")->getResultArray();
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
        return $this->db->query("
        SELECT u.unit_id, u.name AS unit_name, u.unit_type, u.role,
               e.equipment_id, e.equipment_status, e.salvage_status,
               e.combat_status,
               cp.current_armor, cp.current_structure,
               cp.max_armor, cp.max_structure,
               c.name AS chassis_name, c.variant, c.as_type, c.as_size,
               c.as_mv, c.as_tmm, c.as_dmg_s, c.as_dmg_m, c.as_dmg_l,
               c.as_specials,
               cp.pilot_first_name   AS first_name,
               cp.pilot_last_name    AS last_name,
               cp.pilot_rank_abbr    AS rank_abbr,
               cp.pilot_experience   AS experience,
               cp.pilot_morale       AS morale,
               cp.pilot_final_status AS pilot_status,
               cp.status             AS pool_status
        FROM combat_pool cp
        JOIN units u ON u.unit_id = cp.unit_id
        JOIN equipment e ON e.equipment_id = cp.equipment_id
        JOIN chassis c ON c.chassis_id = e.chassis_id
        WHERE cp.mission_id = {$missionId}
        AND cp.side = 'attacker'
        AND cp.participant_type = 'equipment'
        ORDER BY u.name, c.name
    ")->getResultArray();
    }

    public function getDefenderCombatants(int $missionId): array
    {
        return $this->db->query("
        SELECT u.unit_id, u.name AS unit_name, u.unit_type, u.role,
               e.equipment_id, e.equipment_status, e.salvage_status,
               e.combat_status,
               cp.current_armor, cp.current_structure,
               cp.max_armor, cp.max_structure,
               c.name AS chassis_name, c.variant, c.as_type, c.as_size,
               c.as_mv, c.as_tmm, c.as_dmg_s, c.as_dmg_m, c.as_dmg_l,
               c.as_specials,
               cp.pilot_first_name   AS first_name,
               cp.pilot_last_name    AS last_name,
               cp.pilot_rank_abbr    AS rank_abbr,
               cp.pilot_experience   AS experience,
               cp.pilot_morale       AS morale,
               cp.pilot_final_status AS pilot_status,
               cp.status             AS pool_status
        FROM combat_pool cp
        JOIN units u ON u.unit_id = cp.unit_id
        JOIN equipment e ON e.equipment_id = cp.equipment_id
        JOIN chassis c ON c.chassis_id = e.chassis_id
        WHERE cp.mission_id = {$missionId}
        AND cp.side = 'defender'
        AND cp.participant_type = 'equipment'
        ORDER BY u.name, c.name
    ")->getResultArray();
    }

    public function getAttackerFaction(int $missionId): array
    {
        $row = $this->db->query("
            SELECT DISTINCT f.faction_id, f.name, f.color, f.emblem_path
            FROM combat_pool cp
            JOIN units u ON u.unit_id = cp.unit_id
            JOIN factions f ON f.faction_id = u.faction_id
            WHERE cp.mission_id = {$missionId}
            AND cp.side = 'attacker'
            LIMIT 1
        ")->getRowArray();

        return $row ?? [];
    }

    public function getDefenderFaction(int $missionId): array
    {
        $row = $this->db->query("
            SELECT DISTINCT f.faction_id, f.name, f.color, f.emblem_path
            FROM combat_pool cp
            JOIN units u ON u.unit_id = cp.unit_id
            JOIN factions f ON f.faction_id = u.faction_id
            WHERE cp.mission_id = {$missionId}
            AND cp.side = 'defender'
            LIMIT 1
        ")->getRowArray();

        return $row ?? [];
    }

    public function getBattleBalance(int $missionId, ?array $location = null): array
    {
        $terrain = $location['terrain'] ?? 'Plains';
        $isUrban = in_array($terrain, ['Urban', 'Dense Urban']);

        $hasFortification = $this->db->table('buildings')
            ->where('location_id', $location['location_id'] ?? 0)
            ->where('type', 'Fortification')
            ->where('status', 'Operational')
            ->countAllResults() > 0;

        $infantryMultiplier = 1.0;
        if ($isUrban)        $infantryMultiplier *= 1.5;
        if ($hasFortification) $infantryMultiplier *= 2.0;

        $sizeWeights = ['Light' => 1, 'Medium' => 2, 'Heavy' => 3, 'Assault' => 4];
        $baseInfantryScore = 18; // equivalent to a full-health Light mech

        $scores = ['attacker' => 0.0, 'defender' => 0.0];

        // Equipment
        $equipment = $this->db->query("
            SELECT cp.side, cp.current_armor, cp.current_structure,
                c.weight_class
            FROM combat_pool cp
            JOIN equipment e ON e.equipment_id = cp.equipment_id
            JOIN chassis c ON c.chassis_id = e.chassis_id
            WHERE cp.mission_id = {$missionId}
            AND cp.participant_type = 'equipment'
            AND cp.status IN ('Active', 'Crippled')
            AND cp.resolved = 0
        ")->getResultArray();

        foreach ($equipment as $row) {
            $weight = $sizeWeights[$row['weight_class']] ?? 2;
            $scores[$row['side']] += ($row['current_armor'] + $row['current_structure']) * $weight;
        }

        // Infantry
        $infantry = $this->db->query("
            SELECT cp.side, cp.unit_id
            FROM combat_pool cp
            WHERE cp.mission_id = {$missionId}
            AND cp.participant_type = 'infantry'
            AND cp.status IN ('Active', 'Crippled')
            AND cp.resolved = 0
        ")->getResultArray();

        foreach ($infantry as $row) {
            $unitId = (int)$row['unit_id'];

            $strength = (int)($this->db->query("
                SELECT COUNT(*) AS cnt
                FROM personnel_assignments pa
                JOIN personnel p ON p.personnel_id = pa.personnel_id
                WHERE pa.unit_id = {$unitId}
                AND pa.date_released IS NULL
                AND p.status = 'Active'
            ")->getRowArray()['cnt'] ?? 0);

            $maxStrength = (int)($this->db->query("
                SELECT COUNT(*) AS cnt
                FROM personnel_assignments pa
                WHERE pa.unit_id = {$unitId}
            ")->getRowArray()['cnt'] ?? 1);

            $ratio = $maxStrength > 0 ? $strength / $maxStrength : 0;
            $scores[$row['side']] += $ratio * $baseInfantryScore * $infantryMultiplier;
        }

        $total = $scores['attacker'] + $scores['defender'];
        $attackerPct = $total > 0 ? round(($scores['attacker'] / $total) * 100) : 50;

        return [
            'attacker_score' => round($scores['attacker']),
            'defender_score' => round($scores['defender']),
            'attacker_pct'   => $attackerPct,
            'defender_pct'   => 100 - $attackerPct,
        ];
    }
}
