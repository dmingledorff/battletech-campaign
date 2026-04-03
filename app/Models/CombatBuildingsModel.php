<?php

namespace App\Models;

use CodeIgniter\Model;

class CombatBuildingsModel extends Model
{
    protected $table      = 'combat_buildings';
    protected $primaryKey = 'combat_building_id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'mission_id',
        'building_id',
        'name',
        'type',
        'current_integrity',
        'max_integrity',
        'current_armor',
        'max_armor',
        'as_dmg_s',
        'as_dmg_m',
        'as_dmg_l',
        'as_specials',
        'as_tmm',
        'status',
        'capacity'
    ];

    public function getForMission(int $missionId): array
    {
        return $this->where('mission_id', $missionId)
            ->orderBy('type', 'ASC')
            ->findAll();
    }

    public function getFortificationAssignments(int $missionId): array
    {
        $rows = $this->db->query("
            SELECT fa.combat_building_id, u.name AS unit_name, u.unit_id
            FROM fortification_assignments fa
            JOIN units u ON u.unit_id = fa.unit_id
            WHERE fa.mission_id = {$missionId}
        ")->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['combat_building_id']][] = $row;
        }
        return $grouped;
    }

    public function getActiveFortifications(int $missionId): array
    {
        return $this->where('mission_id', $missionId)
            ->where('type', 'Fortification')
            ->whereNotIn('status', ['Destroyed'])
            ->orderBy('current_integrity', 'DESC')
            ->findAll();
    }

    public function getActiveTurrets(int $missionId): array
    {
        return $this->db->query("
            SELECT cb.*
            FROM combat_buildings cb
            WHERE cb.mission_id = {$missionId}
            AND cb.status != 'Destroyed'
            AND cb.as_dmg_s IS NOT NULL
        ")->getResultArray();
    }

    public function syncToBuildings(int $missionId): void
    {
        $combatBuildings = $this->where('mission_id', $missionId)->findAll();

        foreach ($combatBuildings as $cb) {
            $this->db->table('buildings')
                ->where('building_id', $cb['building_id'])
                ->update([
                    'current_integrity' => $cb['current_integrity'],
                    'current_armor'     => $cb['current_armor'],
                    'status'            => $cb['status'],
                ]);
        }
    }
}
