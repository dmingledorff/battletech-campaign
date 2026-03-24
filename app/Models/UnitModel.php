<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\GameStateModel;

class UnitModel extends Model
{
    protected $table = 'units';
    protected $primaryKey = 'unit_id';
    protected $allowedFields = [
        'name',
        'nickname',
        'unit_type',
        'role',
        'parent_unit_id',
        'commander_id',
        'location_id',
        'template_id',
        'current_supply'
    ];

    protected function getGameDate(): string
    {
        $state = new GameStateModel();
        return $state->getProperty('current_date') ?? date('3025-01-01');
    }

    public function getSummary()
    {
        return $this->db->table('units')
            ->select('units.unit_id, units.name, units.unit_type, units.parent_unit_id, units.nickname,
                      units.current_supply,
                      COUNT(DISTINCT personnel.personnel_id) AS personnel_count,
                      COUNT(DISTINCT equipment.equipment_id) AS equipment_count,
                      COALESCE(SUM(chassis.supply_consumption),0) AS required_supply')
            ->join('personnel_assignments', 'personnel_assignments.unit_id=units.unit_id', 'left')
            ->join('personnel', 'personnel.personnel_id=personnel_assignments.personnel_id', 'left')
            ->join('equipment', 'equipment.assigned_unit_id=units.unit_id', 'left')
            ->join('chassis', 'chassis.chassis_id=equipment.chassis_id', 'left')
            ->groupBy('units.unit_id')
            ->get()->getResultArray();
    }

    public function getAllChildren()
    {
        $units = $this->orderBy('parent_unit_id')->findAll();
        $children = [];

        foreach ($units as $u) {
            $children[$u['parent_unit_id']][] = $u;
        }

        // Sort each child group by name (natural order: 1st, 2nd, 10th…)
        foreach ($children as &$group) {
            usort($group, function ($a, $b) {
                return strnatcmp($a['name'], $b['name']);
            });
        }

        return $children;
    }

    public function getUnit($unit_id)
    {
        return $this->db->table('units u')
            ->select('u.*, p.first_name, p.last_name, r.full_name AS rank_full, r.abbreviation AS rank_abbr')
            ->join('personnel p', 'u.commander_id = p.personnel_id', 'left')
            ->join('ranks r', 'p.rank_id = r.id', 'left')
            ->where('u.unit_id', $unit_id)
            ->get()
            ->getRowArray();
    }

    public function getPersonnel($unitId)
    {
        return $this->db->table('personnel p')
            ->select('
                p.*,
                r.full_name AS rank_full,
                r.abbreviation AS rank_abbr,
                r.grade AS rank_grade,
                u.name AS unit_name,
                ANY_VALUE(pa.unit_id) as unit_id,
                MAX(pa.date_assigned) as date_assigned
            ')
            ->join('personnel_assignments pa', 'pa.personnel_id = p.personnel_id')
            ->join('ranks r', 'p.rank_id = r.id', 'left')
            ->join('units u', 'pa.unit_id = u.unit_id')
            ->where('pa.unit_id', $unitId)
            ->where('pa.date_released IS NULL')
            ->groupBy('p.personnel_id, r.full_name, r.abbreviation, r.grade, p.first_name, p.last_name, p.status')
            ->orderBy('r.grade', 'DESC') // optional if you add grade column in ranks
            ->get()
            ->getResultArray();
    }

    public function getEquipment($unitId)
    {
        return $this->db->table('equipment')
            ->select('equipment.*, 
            chassis.name as chassis_name, 
            chassis.type as chassis_type, 
            chassis.weight_class,
            chassis.variant as chassis_variant')
            ->join('chassis', 'chassis.chassis_id=equipment.chassis_id')
            ->where('equipment.assigned_unit_id', $unitId)
            ->get()->getResultArray();
    }

    public function getStrengthAll(): array
    {
        // One query: authorized slots per unit
        $authorized = $this->db->query("
            SELECT u.unit_id,
                SUM(s.slot_type = 'Personnel') AS auth_personnel,
                SUM(s.slot_type = 'Equipment') AS auth_equipment
            FROM units u
            JOIN toe_slots s ON s.template_id = u.template_id
            GROUP BY u.unit_id
        ")->getResultArray();

        // One query: assigned personnel per unit
        $personnel = $this->db->query("
            SELECT unit_id, COUNT(*) AS assigned
            FROM personnel_assignments
            WHERE date_released IS NULL
            GROUP BY unit_id
        ")->getResultArray();

        // One query: active equipment per unit
        $equipment = $this->db->query("
            SELECT assigned_unit_id AS unit_id, COUNT(*) AS assigned
            FROM equipment
            WHERE equipment_status = 'Active'
            AND assigned_unit_id IS NOT NULL
            GROUP BY assigned_unit_id
        ")->getResultArray();

        // Index by unit_id
        $auth = array_column($authorized, null, 'unit_id');
        $pers = array_column($personnel, 'assigned', 'unit_id');
        $equp = array_column($equipment, 'assigned', 'unit_id');

        // Build flat strength map
        $strength = [];
        $allUnits = $this->findAll();
        foreach ($allUnits as $u) {
            $id = $u['unit_id'];
            $strength[$id] = [
                'auth_personnel'  => (int)($auth[$id]['auth_personnel'] ?? 0),
                'auth_equipment'  => (int)($auth[$id]['auth_equipment'] ?? 0),
                'asgn_personnel'  => (int)($pers[$id] ?? 0),
                'asgn_equipment'  => (int)($equp[$id] ?? 0),
            ];
        }

        return $strength;
    }

    public function rollupStrength(int $unitId, array $children, array $strengthMap): array
    {
        $totals = $strengthMap[$unitId] ?? [
            'auth_personnel' => 0,
            'auth_equipment' => 0,
            'asgn_personnel' => 0,
            'asgn_equipment' => 0,
        ];

        foreach ($children[$unitId] ?? [] as $child) {
            $child = $this->rollupStrength($child['unit_id'], $children, $strengthMap);
            $totals['auth_personnel'] += $child['auth_personnel'];
            $totals['auth_equipment'] += $child['auth_equipment'];
            $totals['asgn_personnel'] += $child['asgn_personnel'];
            $totals['asgn_equipment'] += $child['asgn_equipment'];
        }

        $totals['pct_personnel'] = $totals['auth_personnel'] > 0
            ? round($totals['asgn_personnel'] / $totals['auth_personnel'] * 100, 1) : 0;
        $totals['pct_equipment'] = $totals['auth_equipment'] > 0
            ? round($totals['asgn_equipment'] / $totals['auth_equipment'] * 100, 1) : 0;

        return $totals;
    }

    // Recursive personnel
    public function getAllPersonnelRecursive($unitId, $children)
    {
        $personnel = $this->getPersonnel($unitId);

        if (isset($children[$unitId])) {
            foreach ($children[$unitId] as $child) {
                $personnel = array_merge(
                    $personnel,
                    $this->getAllPersonnelRecursive($child['unit_id'], $children)
                );
            }
        }

        // Global sort: highest grade first
        usort($personnel, function ($a, $b) {
            return $b['rank_grade'] <=> $a['rank_grade'];
        });

        return $personnel;
    }


    // Recursive equipment
    public function getAllEquipmentRecursive($unitId, $children)
    {
        $equipment = $this->getEquipment($unitId);
        if (isset($children[$unitId])) {
            foreach ($children[$unitId] as $child) {
                $equipment = array_merge($equipment, $this->getAllEquipmentRecursive($child['unit_id'], $children));
            }
        }
        return $equipment;
    }

    public function getBreadcrumb($unitId)
    {
        $breadcrumb = [];
        $current = $this->find($unitId);
        while ($current) {
            $breadcrumb[] = $current;
            if ($current['parent_unit_id'] === null) break;
            $current = $this->find($current['parent_unit_id']);
        }
        return array_reverse($breadcrumb);
    }

    public function getAuthorizedPersonnel(int $unitId): int
    {
        return $this->db->table('toe_slots')
            ->join('units', 'units.template_id = toe_slots.template_id')
            ->where('units.unit_id', $unitId)
            ->where('toe_slots.slot_type', 'Personnel')
            ->countAllResults();
    }

    public function getAuthorizedEquipment(int $unitId): int
    {
        return $this->db->table('toe_slots')
            ->join('units', 'units.template_id = toe_slots.template_id')
            ->where('units.unit_id', $unitId)
            ->where('toe_slots.slot_type', 'Equipment')
            ->countAllResults();
    }

    public function getAvailablePersonnel(int $factionId): array
    {
        return $this->db->table('personnel p')
            ->select('
                p.*,
                r.full_name AS rank_full,
                r.abbreviation AS rank_abbr,
                r.grade AS rank_grade
            ')
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->where('p.faction_id', $factionId)
            ->where('p.personnel_id NOT IN (
                SELECT personnel_id
                FROM personnel_assignments
                WHERE date_released IS NULL
            )', null, false) // strict unassigned filter
            ->orderBy('r.grade', 'ASC')
            ->orderBy('p.last_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getAvailableEquipment(int $factionId): array
    {
        return $this->db->table('equipment e')
            ->select('
                e.*,
                c.name AS chassis_name,
                c.variant AS chassis_variant,
                c.weight_class,
                c.type AS chassis_type
            ')
            ->join('chassis c', 'c.chassis_id = e.chassis_id', 'left')
            ->where('e.faction_id', $factionId)
            ->where('e.assigned_unit_id IS NULL', null, false)  // ✅ strict filter
            ->orderBy('c.weight_class', 'ASC')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function managePersonnel(int $unitId, array $assignList, array $unassignList): void
    {
        $personnelAssignments = $this->db->table('personnel_assignments');
        $gameDate = $this->getGameDate();

        // Assign new personnel
        foreach (array_filter($assignList) as $pid) {
            // Ensure not already assigned to prevent duplicates
            $exists = $personnelAssignments
                ->where('personnel_id', $pid)
                ->where('unit_id', $unitId)
                ->where('date_released IS NULL', null, false)
                ->countAllResults();

            if ($exists === 0) {
                $personnelAssignments->insert([
                    'personnel_id'  => $pid,
                    'unit_id'       => $unitId,
                    'date_assigned' => $gameDate,
                ]);
            }
        }

        // Unassign personnel (mark release date)
        foreach (array_filter($unassignList) as $pid) {
            $personnelAssignments
                ->where('personnel_id', $pid)
                ->where('unit_id', $unitId)
                ->where('date_released IS NULL', null, false)
                ->update(['date_released' => $gameDate]);
        }
    }

    public function manageEquipment(int $unitId, array $assignList, array $unassignList): void
    {
        $equipmentTable = $this->db->table('equipment');

        // Assign new equipment
        foreach (array_filter($assignList) as $eid) {
            $equipmentTable
                ->where('equipment_id', $eid)
                ->update(['assigned_unit_id' => $unitId]);
        }

        // Unassign equipment
        foreach (array_filter($unassignList) as $eid) {
            $equipmentTable
                ->where('equipment_id', $eid)
                ->update(['assigned_unit_id' => null]);
        }
    }

    public function getDirectPersonnel(int $unitId): array
    {
        return $this->db->table('personnel p')
            ->select('p.*, r.full_name AS rank_full, r.abbreviation AS rank_abbr, r.grade AS rank_grade')
            ->join('personnel_assignments pa', 'pa.personnel_id = p.personnel_id AND pa.date_released IS NULL', 'inner')
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->where('pa.unit_id', $unitId)
            ->orderBy('r.grade', 'DESC')
            ->get()->getResultArray();
    }

    public function getDirectEquipment(int $unitId): array
    {
        return $this->db->table('equipment e')
            ->select('
                e.*,
                c.name AS chassis_name,
                c.type AS chassis_type,
                c.variant AS chassis_variant,
                c.weight_class
            ')
            ->join('chassis c', 'c.chassis_id = e.chassis_id', 'left')
            ->where('e.assigned_unit_id', $unitId)
            ->orderBy('c.weight_class', 'ASC')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function syncPersonnelAssignments(int $unitId, array $personnelIds, ?string $effectiveDate = null): bool
    {
        // get game date if not provided
        if ($effectiveDate === null) {
            $gs = new GameStateModel();
            $effectiveDate = $gs->getProperty('current_date') ?? '3025-01-01';
        }

        // normalize to ints and uniq
        $newIds = array_values(array_unique(array_map('intval', $personnelIds)));

        $this->db->transStart();

        // fetch current direct assignments
        $current = $this->db->table('personnel_assignments')
            ->select('personnel_id')
            ->where('unit_id', $unitId)
            ->where('date_released', null)
            ->get()->getResultArray();
        $currentIds = array_map(static fn($r) => (int) $r['personnel_id'], $current);

        // compute diffs
        $toUnassign = array_diff($currentIds, $newIds);
        $toAssign   = array_diff($newIds, $currentIds);

        // unassign those no longer present
        if (!empty($toUnassign)) {
            $this->db->table('personnel_assignments')
                ->where('unit_id', $unitId)
                ->whereIn('personnel_id', $toUnassign)
                ->where('date_released', null)
                ->set('date_released', $effectiveDate)
                ->update();
        }

        // Release any active equipment assignments for these personnel
        $this->db->table('personnel_equipment')
            ->whereIn('personnel_id', $toUnassign)
            ->where('is_active', true)
            ->set('date_released', $effectiveDate)
            ->update();

        // assign new ones
        foreach ($toAssign as $pid) {
            $this->db->table('personnel_assignments')->insert([
                'personnel_id'  => $pid,
                'unit_id'       => $unitId,
                'date_assigned' => $effectiveDate,
            ]);
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function syncEquipmentAssignments(int $unitId, array $equipmentIds, ?string $effectiveDate = null): bool
    {
        // Get game date if not provided
        if ($effectiveDate === null) {
            $gs = new \App\Models\GameStateModel();
            $effectiveDate = $gs->getProperty('current_date') ?? '3025-01-01';
        }

        $this->db->transStart();

        // Fetch current assigned equipment for this unit
        $current = $this->db->table('equipment')
            ->select('equipment_id')
            ->where('assigned_unit_id', $unitId)
            ->get()->getResultArray();

        $currentIds = array_map(static fn($r) => (int) $r['equipment_id'], $current);
        $newIds = array_values(array_unique(array_map('intval', $equipmentIds)));

        // Diff
        $toUnassign = array_diff($currentIds, $newIds);
        $toAssign   = array_diff($newIds, $currentIds);

        // Unassign equipment
        if (!empty($toUnassign)) {
            $this->db->table('equipment')
                ->whereIn('equipment_id', $toUnassign)
                ->set('assigned_unit_id', null)
                ->update();
        }

        // Assign new ones
        foreach ($toAssign as $eid) {
            $this->db->table('equipment')
                ->where('equipment_id', $eid)
                ->update([
                    'assigned_unit_id' => $unitId,
                    'last_assigned'    => $effectiveDate, // optional if you track it
                ]);
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }
}
