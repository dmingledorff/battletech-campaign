<?php

namespace App\Models;

use CodeIgniter\Model;

class ToeTemplateModel extends Model
{
    protected $table      = 'toe_templates';
    protected $primaryKey = 'template_id';
    protected $allowedFields = ['name', 'description', 'unit_type', 'faction', 'era', 'role', 'mobility', 'chassis_id'];
    protected $returnType = 'array';

    public function getTemplateIdByName(string $name): ?int
    {
        $row = $this->select('template_id')
            ->where('name', $name)
            ->get()->getRowArray();

        return $row['template_id'] ?? null;
    }

    public function getTemplate(int $templateId): array
    {
        $template = $this->db->table('toe_templates')
            ->where('template_id', $templateId)
            ->get()->getRowArray();

        if (!$template) {
            throw new \RuntimeException("Template ID {$templateId} not found");
        }

        $template['slots']    = $this->getSlots($templateId);
        $template['subunits'] = $this->getSubunits($templateId);

        return $template;
    }

    public function getSlots(int $templateId): array
    {
        $slots = $this->db->table('toe_slots s')
            ->select('s.*')
            ->where('s.template_id', $templateId)
            ->get()->getResultArray();

        foreach ($slots as &$slot) {
            // Battlefield roles
            $roles = $this->db->table('toe_slot_roles')
                ->select('battlefield_role')
                ->where('slot_id', $slot['slot_id'])
                ->get()->getResultArray();
            $slot['roles'] = array_map(fn($r) => $r['battlefield_role'], $roles);

            // Do NOT preload crew here → handled per equipment slot in TemplateGenerator
            $slot['crew'] = [];
        }

        return $slots;
    }

    public function getSubunits(int $templateId): array
    {
        $subs = $this->db->table('toe_subunits')
            ->where('parent_template_id', $templateId)
            ->get()->getResultArray();

        foreach ($subs as &$sub) {
            $sub['child_template'] = $this->getTemplate($sub['child_template_id']);
        }

        return $subs;
    }

    public function getCrewForSlot(int $slotId): array
    {
        return $this->db->table('toe_slot_crews c')
            ->select('c.crew_role, c.personnel_slot_id, s.mos, s.min_grade')
            ->join('toe_slots s', 's.slot_id = c.personnel_slot_id')
            ->where('c.equipment_slot_id', $slotId)
            ->get()
            ->getResultArray();
    }

    public function getAlphabet(): array
    {
        return [
            'Able',
            'Baker',
            'Charlie',
            'Dog',
            'Easy',
            'Fox',
            'George',
            'How',
            'Item',
            'Jig',
            'King',
            'Love',
            'Mike',
            'Nan',
            'Oboe',
            'Peter',
            'Queen',
            'Roger',
            'Sugar',
            'Tare',
            'Uncle',
            'Victor',
            'William',
            'Xray',
            'Yankee',
            'Zulu'
        ];
    }

    public function findSlotByGrade(int $templateId, int $grade): ?int
    {
        $row = $this->db->table('toe_slots')
            ->where('template_id', $templateId)
            ->where('min_grade', $grade)
            ->where('slot_type', 'Personnel')
            ->get()
            ->getRowArray();

        return $row['slot_id'] ?? null;
    }

    /**
     * Generic helper: get rank_id by full_name
     */
    public function getRankId(string $rankName, string $faction = 'Davion'): ?int
    {
        $row = $this->db->table('ranks')
            ->select('id')
            ->where('faction', $faction)
            ->where('full_name', $rankName)
            ->get(1)->getRowArray();

        return $row['id'] ?? null;
    }

    public function getAllTemplates(): array
    {
        return $this->db->table('toe_templates t')
            ->select('t.*,
                COUNT(DISTINCT s.slot_id) AS slot_count,
                COUNT(DISTINCT su.subunit_id) AS subunit_count,
                f.emblem_path AS faction_emblem,
                f.name AS faction_name')
            ->join('toe_slots s', 's.template_id = t.template_id', 'left')
            ->join('toe_subunits su', 'su.parent_template_id = t.template_id', 'left')
            ->join('factions f', 'f.house = t.faction', 'left')
            ->groupBy('t.template_id, t.name, t.description, t.unit_type, t.role, 
                    t.mobility, t.faction, t.era, f.emblem_path, f.name')
            ->orderBy('t.unit_type')
            ->orderBy('t.name')
            ->get()
            ->getResultArray();
    }

    public function getFullTemplate(int $templateId): ?array
    {
        $template = $this->db->table('toe_templates t')
            ->select('t.*, f.emblem_path AS faction_emblem, f.name AS faction_full_name')
            ->join('factions f', 'f.house = t.faction', 'left')
            ->where('t.template_id', $templateId)
            ->get()->getRowArray();

        if (!$template) return null;

        // Personnel slots
        $template['personnel_slots'] = $this->db->table('toe_slots s')
            ->select('s.*')
            ->where('s.template_id', $templateId)
            ->where('s.slot_type', 'Personnel')
            ->get()->getResultArray();

        // Equipment slots with their crew assignments
        $equipSlots = $this->db->table('toe_slots s')
            ->select('s.*, GROUP_CONCAT(r.battlefield_role) AS roles, c.name AS chassis_name, c.variant AS chassis_variant')
            ->join('toe_slot_roles r', 'r.slot_id = s.slot_id', 'left')
            ->join('chassis c', 'c.chassis_id = s.chassis_id', 'left')
            ->where('s.template_id', $templateId)
            ->where('s.slot_type', 'Equipment')
            ->groupBy('s.slot_id')
            ->get()->getResultArray();

        foreach ($equipSlots as &$slot) {
            $slot['crew'] = $this->db->table('toe_slot_crews c')
                ->select('c.*, s.mos, s.min_grade')
                ->join('toe_slots s', 's.slot_id = c.personnel_slot_id')
                ->where('c.equipment_slot_id', $slot['slot_id'])
                ->get()->getResultArray();
        }
        unset($slot);
        $template['equipment_slots'] = $equipSlots;

        // Subunits
        $template['subunits'] = $this->db->table('toe_subunits su')
            ->select('su.*, t.name AS child_name, t.unit_type AS child_unit_type, t.role AS child_role')
            ->join('toe_templates t', 't.template_id = su.child_template_id')
            ->where('su.parent_template_id', $templateId)
            ->get()->getResultArray();

        return $template;
    }

    public function addSlot(int $templateId, array $data, array $roles = []): int
    {
        $data['template_id'] = $templateId;
        $this->db->table('toe_slots')->insert($data);
        $slotId = $this->db->insertID();

        foreach ($roles as $role) {
            if ($role) {
                $this->db->table('toe_slot_roles')->insert([
                    'slot_id'          => $slotId,
                    'battlefield_role' => $role,
                ]);
            }
        }

        return $slotId;
    }

    public function deleteSlot(int $slotId): void
    {
        $this->db->table('toe_slot_crews')
            ->where('equipment_slot_id', $slotId)
            ->orWhere('personnel_slot_id', $slotId)
            ->delete();
        $this->db->table('toe_slot_roles')
            ->where('slot_id', $slotId)
            ->delete();
        $this->db->table('toe_slots')
            ->where('slot_id', $slotId)
            ->delete();
    }

    public function addCrew(int $equipSlotId, int $personnelSlotId, string $role): int
    {
        $this->db->table('toe_slot_crews')->insert([
            'equipment_slot_id'  => $equipSlotId,
            'personnel_slot_id'  => $personnelSlotId,
            'crew_role'          => $role,
        ]);
        return $this->db->insertID();
    }

    public function deleteCrew(int $crewId): void
    {
        $this->db->table('toe_slot_crews')->where('crew_id', $crewId)->delete();
    }

    public function addSubunit(int $parentId, int $childId, int $quantity, bool $isCore, bool $isCommand): int
    {
        $this->db->table('toe_subunits')->insert([
            'parent_template_id' => $parentId,
            'child_template_id'  => $childId,
            'quantity'           => $quantity,
            'is_core'            => $isCore ? 1 : 0,
            'is_command'         => $isCommand ? 1 : 0,
        ]);
        return $this->db->insertID();
    }

    public function deleteSubunit(int $subunitId): void
    {
        $this->db->table('toe_subunits')->where('subunit_id', $subunitId)->delete();
    }

    public function deleteTemplate(int $templateId): void
    {
        $this->db->table('toe_subunits')
            ->where('parent_template_id', $templateId)
            ->orWhere('child_template_id', $templateId)
            ->delete();
        $this->delete($templateId);
    }

    public function getChassisCrewRequirements(
        ?string $equipmentType = null,
        ?string $weightClass   = null,
        ?int    $chassisId     = null
    ): array {
        $builder = $this->db->table('chassis_crew_requirements ccr')
            ->select('ccr.crew_role, ccr.is_required, ccr.required_mos')
            ->join('chassis c', 'c.chassis_id = ccr.chassis_id')
            ->orderBy('ccr.is_required', 'DESC')
            ->orderBy('ccr.crew_role');

        if ($chassisId) {
            $builder->where('ccr.chassis_id', $chassisId);
        } else {
            if ($equipmentType) $builder->where('c.type', $equipmentType);
            if ($weightClass)   $builder->where('c.weight_class', $weightClass);
        }

        return $builder->get()->getResultArray();
    }
}
