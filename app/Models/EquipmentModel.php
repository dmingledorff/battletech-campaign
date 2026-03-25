<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\GameStateModel;

class EquipmentModel extends Model
{
    protected $table = 'equipment';
    protected $primaryKey = 'equipment_id';
    protected $allowedFields = [
        'chassis_id',
        'serial_number',
        'assigned_unit_id',
        'location_id',
        'faction_id',
        'damage_percentage',
        'equipment_status',
    ];

    public function assignCrew(int $equipmentId, int $personnelId, int $slotId, string $role): bool
    {
        $gs   = new GameStateModel();
        $date = $gs->getProperty('current_date') ?? '3025-01-01';

        $this->db->table('personnel_equipment')->insert([
            'personnel_id' => $personnelId,
            'equipment_id' => $equipmentId,
            'slot_id'      => $slotId,
            'role'         => $role,
            'date_assigned'=> $date,
            'date_released'=> null,
        ]);

        return $this->db->affectedRows() > 0;
    }

    public function removeCrew(int $equipmentId, int $personnelId, int $slotId): bool
    {
        $gs   = new GameStateModel();
        $date = $gs->getProperty('current_date') ?? '3025-01-01';

        $this->db->table('personnel_equipment')
            ->where('personnel_id', $personnelId)
            ->where('equipment_id', $equipmentId)
            ->where('slot_id', $slotId)
            ->where('date_released IS NULL', null, false)
            ->update(['date_released' => $date]);

        return $this->db->affectedRows() > 0;
    }

    // Get equipment with chassis and unit info
    public function getEquipment($id) {
        return $this->db->table('equipment')
            ->select('equipment.*, chassis.name as chassis_name,
                    chassis.type as chassis_type, chassis.weight_class,
                    units.name as unit_name, units.unit_type,
                    units.nickname, chassis.tonnage as tonnage,
                    chassis.speed as speed, chassis.variant as chassis_variant,
                    chassis.battlefield_role as role,
                    loc.name AS location_name, loc.location_id AS location_id,
                    pl.name AS planet_name')
            ->join('chassis', 'chassis.chassis_id = equipment.chassis_id')
            ->join('units', 'units.unit_id = equipment.assigned_unit_id', 'left')
            ->join('locations loc', 'loc.location_id = equipment.location_id', 'left')
            ->join('planets pl', 'pl.planet_id = loc.planet_id', 'left')
            ->where('equipment.equipment_id', $id)
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
                p.status,
                p.morale
            ')
            ->join('personnel p', 'p.personnel_id = pe.personnel_id')
            ->join('ranks r', 'p.rank_id = r.id', 'left')
            ->where('pe.equipment_id', $equipmentId)
            ->where('pe.is_active', true)
            ->orderBy('r.grade', 'DESC')
            ->get()->getResultArray();
    }

    public function getCrewManifest(int $equipmentId): array
    {
        $equipment = $this->find($equipmentId);
        if (!$equipment) return [];

        return $this->db->table('chassis_crew_requirements ccr')
            ->select('
                ccr.id AS slot_id,
                ccr.crew_role,
                ccr.is_required,
                ccr.required_mos,
                pe.personnel_id,
                p.first_name,
                p.last_name,
                r.full_name AS rank_full,
                r.abbreviation AS rank_abbr,
                p.status,
                p.morale
            ')
            ->join('equipment e', 'e.chassis_id = ccr.chassis_id')
            ->join('personnel_equipment pe',
                'pe.equipment_id = e.equipment_id
                AND pe.slot_id = ccr.id
                AND pe.is_active = 1', 'left')
            ->join('personnel p', 'p.personnel_id = pe.personnel_id', 'left')
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->where('e.equipment_id', $equipmentId)
            ->orderBy('ccr.is_required', 'DESC')
            ->orderBy('ccr.crew_role')
            ->get()->getResultArray();

    }

    public function getAvailableCrewForSlot(int $equipmentId, int $slotId): array
    {
        // Get the required MOS for this slot
        $slot = $this->db->table('chassis_crew_requirements')
            ->where('id', $slotId)
            ->get()->getRowArray();

        if (!$slot) return [];

        // Get the unit this equipment is assigned to
        $equipment = $this->find($equipmentId);
        if (!$equipment || !$equipment['assigned_unit_id']) return [];

        // Return unit personnel with matching MOS not already crewing something
        return $this->db->table('personnel p')
            ->select('
                p.personnel_id,
                p.first_name,
                p.last_name,
                p.morale,
                p.status,
                p.experience,
                r.full_name AS rank_full,
                r.abbreviation AS rank_abbr,
                r.grade
            ')
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->join('personnel_assignments pa', 'pa.personnel_id = p.personnel_id AND pa.date_released IS NULL')
            ->join('personnel_equipment pe', 'pe.personnel_id = p.personnel_id AND pe.is_active = 1', 'left')
            ->where('pa.unit_id', $equipment['assigned_unit_id'])
            ->where('p.mos', $slot['required_mos'])
            ->where('p.status', 'Active')
            ->where('pe.personnel_id IS NULL', null, false)
            ->orderBy('r.grade', 'DESC')
            ->orderBy('p.last_name', 'ASC')
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
