<?php namespace App\Models;

use CodeIgniter\Model;

class ChassisModel extends Model
{
    protected $table      = 'chassis';
    protected $primaryKey = 'chassis_id';
    protected $allowedFields = [
        'name', 'variant', 'type', 'weight_class', 'battlefield_role',
        'hard_attack', 'soft_attack', 'armor_value', 'ammo_reliance',
        'supply_consumption', 'tonnage', 'speed'
    ];
    protected $returnType = 'array';

    public function getCrewRequirements(int $chassisId): array
    {
        return $this->db->table('chassis_crew_requirements')
            ->where('chassis_id', $chassisId)
            ->get()
            ->getResultArray();
    }

    public function getChassisIdForEquipment(int $equipmentId): ?int
    {
        $row = $this->db->table('equipment')
            ->select('chassis_id')
            ->where('equipment_id', $equipmentId)
            ->get()
            ->getRowArray();

        return $row['chassis_id'] ?? null;
    }
}