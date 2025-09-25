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

    public function getUnit($id) {
        return $this->find($id);
    }

    public function getPersonnel($unitId) {
        return $this->db->table('personnel_assignments')
            ->select('personnel.*')
            ->join('personnel','personnel.personnel_id=personnel_assignments.personnel_id')
            ->where('personnel_assignments.unit_id',$unitId)
            ->get()->getResultArray();
    }

    public function getEquipment($unitId) {
        return $this->db->table('equipment')
            ->select('equipment.*, chassis.name as chassis_name, chassis.type as chassis_type, chassis.weight_class')
            ->join('chassis','chassis.chassis_id=equipment.chassis_id')
            ->where('equipment.assigned_unit_id',$unitId)
            ->get()->getResultArray();
    }


    // Recursive personnel
    public function getAllPersonnelRecursive($unitId, $children) {
        $personnel = $this->getPersonnel($unitId);
        if (isset($children[$unitId])) {
            foreach ($children[$unitId] as $child) {
                $personnel = array_merge($personnel, $this->getAllPersonnelRecursive($child['unit_id'], $children));
            }
        }
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

}
