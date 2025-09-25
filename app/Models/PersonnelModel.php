<?php namespace App\Models;

use CodeIgniter\Model;

class PersonnelModel extends Model
{
    protected $table = 'personnel';
    protected $primaryKey = 'personnel_id';
    protected $allowedFields = ['first_name','last_name','grade','status'];

    // Units this person is assigned to
    public function getAssignments($personnelId) {
        return $this->db->table('personnel_assignments')
            ->select('units.unit_id, units.name, units.unit_type, units.nickname')
            ->join('units','units.unit_id=personnel_assignments.unit_id')
            ->where('personnel_assignments.personnel_id',$personnelId)
            ->get()->getResultArray();
    }

    // Equipment this person is crewing
    public function getEquipmentAssignments($personnelId) {
        return $this->db->table('personnel_equipment')
            ->select('equipment.equipment_id, equipment.serial_number, equipment.equipment_status, 
                      chassis.name as chassis_name, chassis.type as chassis_type, chassis.weight_class,
                      personnel_equipment.role')
            ->join('equipment','equipment.equipment_id=personnel_equipment.equipment_id')
            ->join('chassis','chassis.chassis_id=equipment.chassis_id')
            ->where('personnel_equipment.personnel_id',$personnelId)
            ->get()->getResultArray();
    }
}
