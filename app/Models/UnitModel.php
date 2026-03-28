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
        'current_supply',
        'faction_id',
        'status',
        'mission_id'
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
            ->select('u.*, 
                    p.first_name, p.last_name, 
                    r.full_name AS rank_full, r.abbreviation AS rank_abbr,
                    loc.name AS location_name, loc.location_id AS location_id,
                    pl.name AS planet_name,
                    m.mission_id, m.name AS mission_name, m.mission_type,
                    m.eta_date, m.days_elapsed, m.transit_days,
                    ol.name AS mission_origin, dl.name AS mission_destination')
            ->join('personnel p', 'u.commander_id = p.personnel_id', 'left')
            ->join('ranks r', 'p.rank_id = r.id', 'left')
            ->join('locations loc', 'loc.location_id = u.location_id', 'left')
            ->join('planets pl', 'pl.planet_id = loc.planet_id', 'left')
            ->join('missions m', 'm.mission_id = u.mission_id', 'left')
            ->join('locations ol', 'ol.location_id = m.origin_location_id', 'left')
            ->join('locations dl', 'dl.location_id = m.destination_location_id', 'left')
            ->where('u.unit_id', $unit_id)
            ->get()->getRowArray();
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
            ->get()->getResultArray();
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

    public function getUnitChain(int $unitId): string
    {
        $breadcrumb = array_reverse($this->getBreadcrumb($unitId));
        return implode(', ', array_map(fn($u) => $u['name'], $breadcrumb));
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

    public function getAvailablePersonnel(int $factionId, int $unitId): array
    {
        // Get the unit's location
        $unit = $this->find($unitId);
        $unitLocationId = $unit['location_id'] ?? null;

        return $this->db->table('personnel p')
            ->select('
                p.*,
                r.full_name AS rank_full,
                r.abbreviation AS rank_abbr,
                r.grade AS rank_grade,
                CASE 
                    WHEN p.location_id IS NULL THEN 1
                    WHEN p.location_id = ' . (int)$unitLocationId . ' THEN 1
                    ELSE 0
                END AS can_assign,
                l.name AS location_name
            ', false)
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->join('locations l', 'l.location_id = p.location_id', 'left')
            ->where('p.faction_id', $factionId)
            ->where('p.personnel_id NOT IN (
                SELECT personnel_id FROM personnel_assignments
                WHERE date_released IS NULL
            )', null, false)
            ->orderBy('can_assign', 'DESC')
            ->orderBy('r.grade', 'ASC')
            ->orderBy('p.last_name', 'ASC')
            ->get()->getResultArray();
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
            ->get()->getResultArray();
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
            ->get()->getResultArray();
    }

    public function syncPersonnelAssignments(int $unitId, array $personnelIds, string $effectiveDate): bool
    {
        $newIds = array_values(array_unique(array_map('intval', $personnelIds)));

        $this->db->transStart();

        $current    = $this->db->table('personnel_assignments')
            ->select('personnel_id')
            ->where('unit_id', $unitId)
            ->where('date_released', null)
            ->get()->getResultArray();
        $currentIds = array_map(static fn($r) => (int)$r['personnel_id'], $current);

        $toUnassign = array_diff($currentIds, $newIds);
        $toAssign   = array_diff($newIds, $currentIds);

        if (!empty($toUnassign)) {
            $this->db->table('personnel_assignments')
                ->where('unit_id', $unitId)
                ->whereIn('personnel_id', $toUnassign)
                ->where('date_released', null)
                ->set('date_released', $effectiveDate)
                ->update();

            $this->db->table('personnel_equipment')
                ->whereIn('personnel_id', $toUnassign)
                ->where('date_released IS NULL', null, false)
                ->set('date_released', $effectiveDate)
                ->update();
        }

        if (!empty($toAssign)) {
            $unit       = $this->find($unitId);
            $locationId = $unit['location_id'] ?? null;

            foreach ($toAssign as $pid) {
                $this->db->table('personnel_assignments')->insert([
                    'personnel_id'  => $pid,
                    'unit_id'       => $unitId,
                    'date_assigned' => $effectiveDate,
                ]);

                if ($locationId) {
                    $this->db->table('personnel')
                        ->where('personnel_id', $pid)
                        ->update(['location_id' => $locationId]);
                }
            }
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function syncEquipmentAssignments(int $unitId, array $equipmentIds, string $effectiveDate): bool
    {
        $this->db->transStart();

        $current    = $this->db->table('equipment')
            ->select('equipment_id')
            ->where('assigned_unit_id', $unitId)
            ->get()->getResultArray();
        $currentIds = array_map(static fn($r) => (int)$r['equipment_id'], $current);
        $newIds     = array_values(array_unique(array_map('intval', $equipmentIds)));

        $toUnassign = array_diff($currentIds, $newIds);
        $toAssign   = array_diff($newIds, $currentIds);

        if (!empty($toUnassign)) {
            $this->db->table('equipment')
                ->whereIn('equipment_id', $toUnassign)
                ->set('assigned_unit_id', null)
                ->update();
        }

        foreach ($toAssign as $eid) {
            $this->db->table('equipment')
                ->where('equipment_id', $eid)
                ->update(['assigned_unit_id' => $unitId]);
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function moveUnit(int $unitId, int $locationId, array $children): void
    {
        $this->db->transStart();

        // Update the unit's location
        $this->db->table('units')
            ->where('unit_id', $unitId)
            ->update(['location_id' => $locationId]);

        // Update all directly assigned personnel
        $assigned = $this->db->table('personnel_assignments')
            ->select('personnel_id')
            ->where('unit_id', $unitId)
            ->where('date_released IS NULL', null, false)
            ->get()->getResultArray();

        if (!empty($assigned)) {
            $ids = array_column($assigned, 'personnel_id');
            $this->db->table('personnel')
                ->whereIn('personnel_id', $ids)
                ->update(['location_id' => $locationId]);
        }

        // Cascade to child units recursively
        foreach ($children[$unitId] ?? [] as $child) {
            $this->moveUnit($child['unit_id'], $locationId, $children);
        }

        $this->db->transComplete();
    }

    public function setMissionStatus(array $unitIds, string $status, ?int $missionId, ?int $locationId = null): void
    {
        if (empty($unitIds)) return;

        $data = [
            'status'     => $status,
            'mission_id' => $missionId,
        ];

        if ($status === 'In Transit') {
            // Clear location — unit is no longer at a physical location
            $data['location_id'] = null;
        } elseif ($locationId !== null) {
            $data['location_id'] = $locationId;
        }

        $this->db->table('units')
            ->whereIn('unit_id', $unitIds)
            ->update($data);
    }

    public function getSummaryByFaction(?int $factionId): array
    {
        return $this->db->table('units u')
            ->select('
                u.unit_id, u.name, u.unit_type, u.role, u.parent_unit_id, u.status,
                COUNT(DISTINCT pa.personnel_id) AS personnel_count,
                COUNT(DISTINCT e.equipment_id)  AS equipment_count,
                COALESCE(SUM(DISTINCT ch.supply_consumption), 0) AS required_supply,
                u.current_supply
            ')
            ->join(
                'personnel_assignments pa',
                'pa.unit_id = u.unit_id AND pa.date_released IS NULL',
                'left'
            )
            ->join('equipment e', 'e.assigned_unit_id = u.unit_id', 'left')
            ->join('chassis ch', 'ch.chassis_id = e.chassis_id', 'left')
            ->where('u.faction_id', $factionId)
            ->groupBy('u.unit_id')
            ->get()->getResultArray();
    }

    public function getAllChildrenByFaction(?int $factionId): array
    {
        $units = $this->where('faction_id', $factionId)->findAll();
        $map   = [];
        foreach ($units as $u) {
            $map[$u['parent_unit_id']][] = $u;
        }
        return $map;
    }

    public function getMaxSpeed(int $unitId): ?float
    {
        // Find slowest equipment directly assigned to this unit
        $row = $this->db->table('equipment e')
            ->select('MIN(ch.speed) AS min_speed')
            ->join('chassis ch', 'ch.chassis_id = e.chassis_id')
            ->where('e.assigned_unit_id', $unitId)
            ->get()->getRowArray();

        return isset($row['min_speed']) ? (float)$row['min_speed'] : null;
    }

    public function getMaxSpeedBatch(array $unitIds): array
    {
        if (empty($unitIds)) return [];

        $rows = $this->db->table('equipment e')
            ->select('e.assigned_unit_id, MIN(ch.speed) AS min_speed')
            ->join('chassis ch', 'ch.chassis_id = e.chassis_id')
            ->whereIn('e.assigned_unit_id', $unitIds)
            ->groupBy('e.assigned_unit_id')
            ->get()->getResultArray();

        return array_column($rows, 'min_speed', 'assigned_unit_id');
    }

    public function getMinSpeedRecursive(int $unitId): ?float
    {
        // Get all descendant unit IDs including self
        $allIds = $this->getAllDescendantIds($unitId);
        $allIds[] = $unitId;

        if (empty($allIds)) return null;

        $row = $this->db->table('equipment e')
            ->select('MIN(ch.speed) AS min_speed')
            ->join('chassis ch', 'ch.chassis_id = e.chassis_id')
            ->whereIn('e.assigned_unit_id', $allIds)
            ->get()->getRowArray();

        return isset($row['min_speed']) && $row['min_speed'] !== null
            ? (float)$row['min_speed']
            : null;
    }

    private function getAllDescendantIds(int $unitId): array
    {
        $ids      = [];
        $children = $this->db->table('units')
            ->select('unit_id')
            ->where('parent_unit_id', $unitId)
            ->get()->getResultArray();

        foreach ($children as $child) {
            $ids[] = (int)$child['unit_id'];
            $ids   = array_merge($ids, $this->getAllDescendantIds($child['unit_id']));
        }

        return $ids;
    }

    public function getMinSpeedBatch(array $unitIds): array
    {
        if (empty($unitIds)) return [];

        $result = [];
        foreach ($unitIds as $unitId) {
            $result[$unitId] = $this->getMinSpeedRecursive($unitId);
        }
        return $result;
    }

    public function deactivateUnit(int $unitId, string $date): void
    {
        $unit = $this->find($unitId);
        if (!$unit) return;

        $parentId = $unit['parent_unit_id'];

        $this->db->transStart();

        // Re-parent all children to this unit's parent
        $this->db->table('units')
            ->where('parent_unit_id', $unitId)
            ->update(['parent_unit_id' => $parentId]);

        // Release all personnel assignments
        $this->db->table('personnel_assignments')
            ->where('unit_id', $unitId)
            ->where('date_released IS NULL', null, false)
            ->update(['date_released' => $date]);

        // Unassign all equipment
        $this->db->table('equipment')
            ->where('assigned_unit_id', $unitId)
            ->update(['assigned_unit_id' => null]);

        // Deactivate the unit
        $this->db->table('units')
            ->where('unit_id', $unitId)
            ->update([
                'status'       => 'Deactivated',
                'commander_id' => null,
            ]);

        $this->db->transComplete();
    }

    public function updateNameNickname(int $unitId, string $name, ?string $nickname): void
    {
        $this->db->table('units')
            ->where('unit_id', $unitId)
            ->update([
                'name'     => $name,
                'nickname' => $nickname ?: null,
            ]);
    }

    public function getUnitsByFaction(int $factionId, bool $includeDeactivated = false): array
    {
        $builder = $this->db->table('units u')
            ->select('u.*, p.last_name, r.abbreviation AS rank_abbr')
            ->join('personnel p', 'p.personnel_id = u.commander_id', 'left')
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->where('u.faction_id', $factionId);

        if (!$includeDeactivated) {
            $builder->where('u.status !=', 'Deactivated');
        }

        return $builder->orderBy('u.parent_unit_id')->get()->getResultArray();
    }

    public function assignCommander(int $unitId, int $personnelId): void
    {
        $this->db->table('units')
            ->where('unit_id', $unitId)
            ->update(['commander_id' => $personnelId]);
    }

    public function dismissCommander(int $unitId): void
    {
        $this->db->table('units')
            ->where('unit_id', $unitId)
            ->update(['commander_id' => null]);
    }

    public function assignPersonnelToUnitDirect(int $unitId, int $personnelId, string $date): void
    {
        $this->db->table('personnel_assignments')->insert([
            'personnel_id' => $personnelId,
            'unit_id'      => $unitId,
            'date_assigned' => $date,
        ]);
    }

    public function unassignPersonnelFromUnit(int $unitId, int $personnelId, string $date): void
    {
        $this->db->table('personnel_assignments')
            ->where('personnel_id', $personnelId)
            ->where('unit_id', $unitId)
            ->update(['date_released' => $date]);
    }

    public function assignEquipmentToUnit(int $unitId, int $equipmentId): void
    {
        $this->db->table('equipment')
            ->where('equipment_id', $equipmentId)
            ->update(['assigned_unit_id' => $unitId]);
    }

    public function unassignEquipmentFromUnit(int $equipmentId): void
    {
        $this->db->table('equipment')
            ->where('equipment_id', $equipmentId)
            ->update(['assigned_unit_id' => null]);
    }

    public function syncDispersedStatus(): void
    {
        $companies = $this->db->table('units')
            ->whereIn('unit_type', ['Company', 'Platoon'])
            ->where('status !=', 'Deactivated')
            ->get()->getResultArray();

        foreach ($companies as $company) {
            $children = $this->db->table('units')
                ->select('unit_id, name, location_id, status')
                ->where('parent_unit_id', $company['unit_id'])
                ->where('status !=', 'Deactivated')
                ->get()->getResultArray();

            log_message('debug', "syncDispersed: {$company['name']} ({$company['unit_id']}) — "
                . count($children) . " children: "
                . implode(', ', array_map(fn($c) => "{$c['name']}={$c['status']}@{$c['location_id']}", $children)));

            if (empty($children)) continue;

            $locations   = array_unique(array_filter(array_column($children, 'location_id')));
            $allStatuses = array_column($children, 'status');
            $anyMoving = !empty(array_filter(
                $allStatuses,
                fn($s) => in_array($s, ['In Transit', 'Combat'])
            ));

            $garrisonedLocations = array_unique(array_filter($locations ?? array_column($children, 'location_id')));

            if (!$anyMoving && count($garrisonedLocations) === 1) {
                // All children garrisoned at same location — consolidate
                $this->db->table('units')
                    ->where('unit_id', $company['unit_id'])
                    ->update([
                        'status'      => 'Garrisoned',
                        'location_id' => reset($garrisonedLocations),
                    ]);
            } else {
                // Any child moving or split locations — disperse
                $this->db->table('units')
                    ->where('unit_id', $company['unit_id'])
                    ->update([
                        'status'      => 'Dispersed',
                        'location_id' => null,
                    ]);
            }
        }
    }
}
