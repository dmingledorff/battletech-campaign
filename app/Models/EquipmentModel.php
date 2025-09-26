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
        return $this->db->table('personnel_equipment')
            ->select('personnel.personnel_id, personnel_equipment.role, 
                    personnel.first_name, personnel.last_name, 
                    personnel.grade, personnel.status')
            ->join('personnel','personnel.personnel_id=personnel_equipment.personnel_id')
            ->where('personnel_equipment.equipment_id',$equipmentId)
            ->get()->getResultArray();
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
