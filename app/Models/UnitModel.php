<?php namespace App\Models;

use CodeIgniter\Model;

class UnitModel extends Model
{
    protected $table = 'units';
    protected $primaryKey = 'unit_id';
    protected $allowedFields = ['name','unit_type','parent_unit_id','nickname','commander_id','current_supply'];

    public function getSummary() {
        return $this->db->table('units')
            ->select('units.unit_id, units.name, units.unit_type, units.parent_unit_id, units.nickname,
                      units.current_supply,
                      COUNT(DISTINCT personnel.personnel_id) AS personnel_count,
                      COUNT(DISTINCT equipment.equipment_id) AS equipment_count,
                      COALESCE(SUM(chassis.supply_consumption),0) AS required_supply')
            ->join('personnel_assignments','personnel_assignments.unit_id=units.unit_id','left')
            ->join('personnel','personnel.personnel_id=personnel_assignments.personnel_id','left')
            ->join('equipment','equipment.assigned_unit_id=units.unit_id','left')
            ->join('chassis','chassis.chassis_id=equipment.chassis_id','left')
            ->groupBy('units.unit_id')
            ->get()->getResultArray();
    }

    public function getAllChildren() {
        $units = $this->orderBy('parent_unit_id')->findAll();
        $children = [];

        foreach ($units as $u) {
            $children[$u['parent_unit_id']][] = $u;
        }

        // Sort each child group by name (natural order: 1st, 2nd, 10th…)
        foreach ($children as &$group) {
            usort($group, function($a, $b) {
                return strnatcmp($a['name'], $b['name']);
            });
        }

        return $children;
    }

    public function getUnit($unit_id) {
        return $this->db->table('units u')
            ->select('u.*, p.first_name, p.last_name, r.full_name AS rank_full, r.abbreviation AS rank_abbr')
            ->join('personnel p', 'u.commander_id = p.personnel_id', 'left')
            ->join('ranks r', 'p.rank_id = r.id', 'left')
            ->where('u.unit_id', $unit_id)
            ->get()
            ->getRowArray();
    }

    public function getPersonnel($unitId) {
    return $this->db->table('personnel p')
        ->select('
            p.personnel_id,
            p.first_name,
            p.last_name,
            r.full_name AS rank_full,
            r.abbreviation AS rank_abbr,
            r.grade AS rank_grade,
            p.status,
            p.morale,
            p.callsign,
            p.morale,
            ANY_VALUE(pa.unit_id) as unit_id,
            MAX(pa.date_assigned) as date_assigned
        ')
        ->join('personnel_assignments pa', 'pa.personnel_id = p.personnel_id')
        ->join('ranks r', 'p.rank_id = r.id', 'left')
        ->where('pa.unit_id', $unitId)
        ->where('pa.date_released IS NULL')
        ->groupBy('p.personnel_id, r.full_name, r.abbreviation, r.grade, p.first_name, p.last_name, p.status')
        ->orderBy('r.grade', 'DESC') // optional if you add grade column in ranks
        ->get()
        ->getResultArray();
    }

    public function getEquipment($unitId) {
        return $this->db->table('equipment')
            ->select('equipment.*, 
            chassis.name as chassis_name, 
            chassis.type as chassis_type, 
            chassis.weight_class,
            chassis.variant as chassis_variant')
            ->join('chassis','chassis.chassis_id=equipment.chassis_id')
            ->where('equipment.assigned_unit_id',$unitId)
            ->get()->getResultArray();
    }

    public function getUnitMorale($unitId, $children) {
        // Gather all personnel recursively (this includes children units!)
        $personnel = $this->getAllPersonnelRecursive($unitId, $children);

        if (empty($personnel)) {
            return null; // or 0 if you prefer a default
        }

        $totalMorale = 0;
        $count       = 0;
        log_message('info', 'BTECH: ' . json_encode($personnel));
        foreach ($personnel as $p) {
            if (isset($p['morale']) && $p['morale'] !== null) {
                $totalMorale += (float)$p['morale'];
                $count++;
            }
        }

        return $count > 0 ? ($totalMorale / $count) : null;
    }

    // Recursive personnel
    public function getAllPersonnelRecursive($unitId, $children) {
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
    public function getAllEquipmentRecursive($unitId, $children) {
        $equipment = $this->getEquipment($unitId);
        if (isset($children[$unitId])) {
            foreach ($children[$unitId] as $child) {
                $equipment = array_merge($equipment, $this->getAllEquipmentRecursive($child['unit_id'], $children));
            }
        }
        return $equipment;
    }

    public function getBreadcrumb($unitId) {
        $breadcrumb = [];
        $current = $this->find($unitId);
        while ($current) {
            $breadcrumb[] = $current;
            if ($current['parent_unit_id'] === null) break;
            $current = $this->find($current['parent_unit_id']);
        }
        return array_reverse($breadcrumb);
    }

    public function getPersonnelStrengthRecursive(int $unitId, array $children): array {
        // 1. Authorized slots for this unit
        $authorized = $this->db->table('toe_slots s')
            ->select('COUNT(*) as cnt')
            ->join('toe_templates t', 's.template_id = t.template_id')
            ->join('units u', 'u.template_id = t.template_id')
            ->where('u.unit_id', $unitId)
            ->where('s.slot_type', 'Personnel')
            ->get()
            ->getRow('cnt') ?? 0;

        // 2. Assigned personnel for this unit
        $assigned = $this->db->table('personnel_assignments pa')
            ->select('COUNT(*) as cnt')
            ->where('pa.unit_id', $unitId)
            ->where('pa.date_released IS NULL')
            ->get()
            ->getRow('cnt') ?? 0;

        // 3. Recurse into children
        if (isset($children[$unitId])) {
            foreach ($children[$unitId] as $child) {
                $childStrength = $this->getPersonnelStrengthRecursive($child['unit_id'], $children);
                $authorized += $childStrength['authorized'];
                $assigned   += $childStrength['assigned'];
            }
        }

        $percent = $authorized > 0 ? round(($assigned / $authorized) * 100, 1) : 0;

        return [
            'assigned'   => (int) $assigned,
            'authorized' => (int) $authorized,
            'percent'    => $percent,
        ];
    }

    public function getEquipmentStrengthRecursive(int $unitId, array $children): array {
        // 1. Authorized slots for this unit
        $authorized = $this->db->table('toe_slots s')
            ->select('COUNT(*) as cnt')
            ->join('toe_templates t', 's.template_id = t.template_id')
            ->join('units u', 'u.template_id = t.template_id')
            ->where('u.unit_id', $unitId)
            ->where('s.slot_type', 'Equipment')
            ->get()
            ->getRow('cnt') ?? 0;

        // 2. Operational equipment in this unit
        $operational = $this->db->table('equipment e')
            ->select('COUNT(*) as cnt')
            ->where('e.assigned_unit_id', $unitId)
            ->where('e.equipment_status', 'Active')
            ->get()
            ->getRow('cnt') ?? 0;

        // 3. Recurse into children
        if (isset($children[$unitId])) {
            foreach ($children[$unitId] as $child) {
                $childStrength = $this->getEquipmentStrengthRecursive($child['unit_id'], $children);
                $authorized  += $childStrength['authorized'];
                $operational += $childStrength['operational'];
            }
        }

        $percent = $authorized > 0 ? round(($operational / $authorized) * 100, 1) : 0;

        return [
            'operational' => (int) $operational,
            'authorized'  => (int) $authorized,
            'percent'     => $percent,
        ];
    }

    public function getAuthorizedPersonnel(int $unitId): int {
        return $this->db->table('toe_slots')
            ->join('units', 'units.template_id = toe_slots.template_id')
            ->where('units.unit_id', $unitId)
            ->where('toe_slots.slot_type', 'Personnel')
            ->countAllResults();
    }

    public function getAuthorizedEquipment(int $unitId): int {
        return $this->db->table('toe_slots')
            ->join('units', 'units.template_id = toe_slots.template_id')
            ->where('units.unit_id', $unitId)
            ->where('toe_slots.slot_type', 'Equipment')
            ->countAllResults();
    }

}
