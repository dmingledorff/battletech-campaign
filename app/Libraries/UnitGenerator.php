<?php namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

class UnitGenerator
{
    protected $db;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new unit (Regiment, Battalion, Company, Lance, InfantryPlatoon, Squad, etc.)
     *
     * @param string      $unitType     Type of unit (e.g., "Regiment", "Battalion", "Company", "Lance")
     * @param string      $name         Name of the unit
     * @param string|null $nickname     Nickname (optional)
     * @param string      $allegiance   Allegiance / faction (default "Independent")
     * @param int|null    $parentId     Parent unit_id (optional)
     * @param int|null    $commanderId  Personnel ID of the commander (optional)
     * @param string|null $role         Role (optional, e.g., Recon, Fire Support for lances)
     *
     * @return int        The new unit's ID
     */
    public function createUnit(
        string $unitType,
        string $name,
        ?string $nickname = null,
        string $allegiance = 'Independent',
        ?int $parentId = null,
        ?int $commanderId = null,
        ?string $role = null,
        int $templateId = null
    ): int {
        $data = [
            'name'           => $name,
            'nickname'       => $nickname,
            'current_supply' => 0,
            'unit_type'      => $unitType,
            'allegiance'     => $allegiance,
            'parent_unit_id' => $parentId,
            'commander_id'   => $commanderId,
            'role'           => $role,
            'template_id'    => $templateId
        ];

        $this->db->table('units')->insert($data);
        return $this->db->insertID();
    }

    /**
     * Delete a unit and all its children, personnel assignments, and equipment.
     *
     * @param int  $unitId       The unit_id to delete
     * @param bool $deletePersonnel If true, also delete personnel assigned ONLY to this unit
     *                              (default false: keeps personnel in the DB, just unassigns them)
     * @return void
     */
    public function deleteUnit(int $unitId, bool $deletePersonnel = false): void
    {
        // 1. Recursively delete child units first
        $children = $this->db->table('units')->where('parent_unit_id', $unitId)->get()->getResultArray();
        foreach ($children as $child) {
            $this->deleteUnit($child['unit_id'], $deletePersonnel);
        }

        // 2. Unassign personnel from this unit
        $assignments = $this->db->table('personnel_assignments')->where('unit_id', $unitId)->get()->getResultArray();
        foreach ($assignments as $assignment) {
            if ($deletePersonnel) {
                // Delete personnel entirely
                $this->db->table('personnel')->delete(['personnel_id' => $assignment['personnel_id']]);
            }
            // Delete assignment record regardless
            $this->db->table('personnel_assignments')->delete(['assignment_id' => $assignment['assignment_id']]);
        }

        // 3. Unassign equipment from this unit
        $this->db->table('equipment')->where('assigned_unit_id', $unitId)->update(['assigned_unit_id' => null]);

        // 4. Delete the unit itself
        $this->db->table('units')->delete(['unit_id' => $unitId]);
    }

    public function assignCommander(int $unitId, int $personnelId): void {
        $this->db->table('units')
            ->where('unit_id', $unitId)
            ->update(['commander_id' => $personnelId]);
    }

    public function getUnitById(int $unitId): ?array {
        return $this->db->table('units')
            ->where('unit_id', $unitId)
            ->get()
            ->getRowArray();
    }


}
