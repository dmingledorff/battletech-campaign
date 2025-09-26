<?php namespace App\Libraries;

use CodeIgniter\Database\ConnectionInterface;

class Generator
{
    protected $db;

    public function __construct(ConnectionInterface $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Generate a single personnel record
     *
     * @param string $faction     Faction name (e.g., "Davion")
     * @param string $role        MOS/role (e.g., "MechWarrior", "Infantry", "Tanker")
     * @param string $grade       Rank (e.g., "Sergeant", "Lieutenant")
     * @param string $experience  Experience level (e.g., "Green", "Regular", "Veteran", "Elite")
     * @param string $status      Current status (e.g., "Active", "KIA", "Retired")
     * @return int                Inserted personnel_id
     */
    public function generatePersonnel(
        string $faction = 'Davion',
        string $role = 'MechWarrior',
        string $grade = 'Private',
        string $experience = 'Green',
        string $status = 'Active'
    ) {
        // Random name
        $name = $this->db->table('name_pool')
            ->where('faction', $faction)
            ->orderBy('RAND()')
            ->get(1)
            ->getRow();

        if (!$name) {
            throw new \RuntimeException("No names available for faction: {$faction}");
        }

        // Random callsign only for MechWarriors
        $callsign = null;
        if ($role === 'MechWarrior') {
            $callsign = $this->db->table('callsign_pool')
                ->where('used', false)
                ->orderBy('RAND()')
                ->get(1)
                ->getRow();
        }

        $personnel = [
            'first_name' => $name->first_name,
            'last_name'  => $name->last_name,
            'grade'      => $grade,
            'gender'     => $name->gender,
            'callsign'   => $callsign->value ?? null,
            'mos'        => $role,
            'experience' => $experience,
            'status'     => $status
        ];

        $this->db->table('personnel')->insert($personnel);
        $id = $this->db->insertID();

        // Mark callsign as used
        if ($callsign) {
            $this->db->table('callsign_pool')
                ->where('id', $callsign->id)
                ->update(['used' => true]);
        }

        return $id;
    }

    public function generateAPC(
        string $variant = 'Wheeled',
        int $unitId = null,
        string $status = 'Active'   // <-- parameterized
    ) {
        // Find APC chassis for this variant
        $apc = $this->db->table('chassis')
            ->where('type', 'APC')
            ->where('variant', $variant)
            ->get(1)
            ->getRow();

        if (!$apc) {
            throw new \RuntimeException("No APC chassis found for variant: {$variant}");
        }

        // Create unique serial
        $serial = strtoupper($apc->variant) . '_' . str_pad(rand(1, 9999999999), 10, '0', STR_PAD_LEFT);

        // Insert equipment record
        $data = [
            'chassis_id'       => $apc->chassis_id,
            'serial_number'    => $serial,
            'assigned_unit_id' => $unitId,
            'damage_percentage'=> 0.0,
            'equipment_status' => $status,   // <-- use param
            'mobility'         => $variant
        ];

        $this->db->table('equipment')->insert($data);

        return $this->db->insertID();
    }

    public function generateBattleMech(
        string $name = null,
        string $variant = null,
        string $weightClass = null,
        int $unitId = null,
        string $status = 'Active'
    ) {
        $builder = $this->db->table('chassis')->where('type', 'BattleMech');

        // Case 4: Variant only
        if ($variant && !$name && !$weightClass) {
            $builder->where('variant', $variant);
        }

        // Case 1 + 2: Name (with or without variant)
        if ($name) {
            $builder->where('name', $name);
            if ($variant) {
                $builder->where('variant', $variant);
            }
        }

        // Case 3: Weight Class only
        if ($weightClass && !$name && !$variant) {
            $builder->where('weight_class', $weightClass);
        }

        $rows = $builder->get()->getResult();

        if (!$rows) {
            throw new \RuntimeException("No chassis found with provided filters (name: {$name}, variant: {$variant}, weight: {$weightClass}).");
        }

        // Random pick if multiple results
        $mech = $rows[array_rand($rows)];

        // Serial number
        $serial = strtoupper($mech->variant) . '_' . str_pad(rand(1, 9999999999), 10, '0', STR_PAD_LEFT);

        // Insert into equipment
        $data = [
            'chassis_id'       => $mech->chassis_id,
            'serial_number'    => $serial,
            'assigned_unit_id' => $unitId,
            'damage_percentage'=> 0.0,
            'equipment_status' => $status
        ];

        $this->db->table('equipment')->insert($data);

        return $this->db->insertID();
    }

    public function assignEquipmentToPersonnel(
        int $personnelId,
        int $equipmentId,
        string $role = 'Pilot',
        string $dateAssigned = null
    ) {
        $dateAssigned = $dateAssigned ?? date('Y-m-d');

        // Step 1: Find the personnel's current unit
        $unitRow = $this->db->table('personnel_assignments')
            ->select('unit_id')
            ->where('personnel_id', $personnelId)
            ->where('date_released IS NULL')
            ->orderBy('date_assigned', 'DESC')
            ->get()
            ->getRow();

        if (!$unitRow) {
            throw new \RuntimeException("Personnel {$personnelId} has no active unit assignment.");
        }

        $unitId = $unitRow->unit_id;

        // Step 2: Insert into personnel_equipment
        $this->db->table('personnel_equipment')->insert([
            'personnel_id' => $personnelId,
            'equipment_id' => $equipmentId,
            'role'         => $role,
            'date_assigned'=> $dateAssigned,
            'date_released'=> null
        ]);

        // Step 3: Update the equipment’s assigned_unit_id
        $this->db->table('equipment')
            ->where('equipment_id', $equipmentId)
            ->update(['assigned_unit_id' => $unitId]);

        return true;
    }

    public function unassignEquipmentFromPersonnel(
        int $personnelId,
        int $equipmentId,
        string $dateReleased = null
    ) {
        $dateReleased = $dateReleased ?? date('Y-m-d');

        // Step 1: Update personnel_equipment (release record)
        $this->db->table('personnel_equipment')
            ->where('personnel_id', $personnelId)
            ->where('equipment_id', $equipmentId)
            ->where('date_released IS NULL')
            ->update(['date_released' => $dateReleased]);

        // Step 2: Check if this equipment is still assigned to anyone else
        $activeAssignment = $this->db->table('personnel_equipment')
            ->where('equipment_id', $equipmentId)
            ->where('date_released IS NULL')
            ->countAllResults();

        if ($activeAssignment == 0) {
            // No active assignment left, unassign from unit
            $this->db->table('equipment')
                ->where('equipment_id', $equipmentId)
                ->update(['assigned_unit_id' => null]);
        }

        return true;
    }

    public function assignPersonnelToUnit(
        int $personnelId,
        int $unitId,
        string $dateAssigned = null
    ) {
        $dateAssigned = $dateAssigned ?? date('Y-m-d');

        // End any active assignment for this person (avoid duplicates)
        $this->db->table('personnel_assignments')
            ->where('personnel_id', $personnelId)
            ->where('date_released IS NULL')
            ->update(['date_released' => $dateAssigned]);

        // Insert new assignment
        $this->db->table('personnel_assignments')->insert([
            'personnel_id' => $personnelId,
            'unit_id'      => $unitId,
            'date_assigned'=> $dateAssigned,
            'date_released'=> null
        ]);

        return true;
    }


   
}
