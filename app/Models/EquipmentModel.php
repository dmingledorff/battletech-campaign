<?php namespace App\Models;

use CodeIgniter\Model;

class EquipmentModel extends Model
{
    protected $table = 'equipment';
    protected $primaryKey = 'equipment_id';
    protected $allowedFields = [
        'chassis_id','serial_number','assigned_unit_id',
        'damage_percent','supply_level','status'
    ];

    // Get equipment with chassis and unit info
    public function getEquipment($id) {
        return $this->db->table('equipment')
            ->select('equipment.*, chassis.name as chassis_name, chassis.type as chassis_type, chassis.weight_class,
                    units.name as unit_name, units.unit_type, units.nickname, chassis.tonnage as tonnage, chassis.speed as speed')
            ->join('chassis','chassis.chassis_id=equipment.chassis_id')
            ->join('units','units.unit_id=equipment.assigned_unit_id','left')
            ->where('equipment.equipment_id',$id)
            ->get()->getRowArray();
    }


    // Get crew/pilot assigned to this equipment
    public function getCrew($equipmentId) {
        return $this->db->table('personnel_equipment pe')
            ->select('
                p.personnel_id,
                pe.role,
                p.first_name,
                p.last_name,
                r.full_name AS rank_full,
                r.abbreviation AS rank_abbr,
                r.grade,
                p.status
            ')
            ->join('personnel p', 'p.personnel_id = pe.personnel_id')
            ->join('ranks r', 'p.rank_id = r.id', 'left')
            ->where('pe.equipment_id', $equipmentId)
            ->orderBy('r.grade', 'DESC')
            ->get()
            ->getResultArray();
    }

    // List all equipment for a unit
    public function getByUnit($unitId) {
        return $this->db->table('equipment')
            ->select('equipment.*, chassis.name as chassis_name')
            ->join('chassis','chassis.chassis_id=equipment.chassis_id')
            ->where('equipment.assigned_unit_id',$unitId)
            ->get()->getResultArray();
    }
}
