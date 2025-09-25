<?php
namespace App\Models;
use CodeIgniter\Model;
class PersonnelEquipmentModel extends Model {
  protected $table='personnel_equipment'; protected $primaryKey=null; protected $useAutoIncrement=false;
  protected $allowedFields=['personnel_id','equipment_id'];
  public function getAll(): array {
    return $this->db->table('personnel_equipment pe')
      ->select('p.name as personnel, p.grade, e.serial_number, c.name as chassis_name, c.type, c.weight_class')
      ->join('personnel p','p.personnel_id = pe.personnel_id')
      ->join('equipment e','e.equipment_id = pe.equipment_id')
      ->join('chassis c','c.chassis_id = e.chassis_id')
      ->orderBy('p.name')->get()->getResultArray();
  }
}
