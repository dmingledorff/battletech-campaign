<?php namespace App\Models;

use CodeIgniter\Model;

class ToeTemplateModel extends Model
{
    protected $table      = 'toe_templates';
    protected $primaryKey = 'template_id';
    protected $allowedFields = ['name','description','unit_type','faction','era'];
    protected $returnType = 'array';

    public function getTemplateIdByName(string $name): ?int {
        $row = $this->select('template_id')
            ->where('name', $name)
            ->get()
            ->getRowArray();

        return $row['template_id'] ?? null;
    }

    public function getTemplate(int $templateId): array {
        $template = $this->db->table('toe_templates')
            ->where('template_id', $templateId)
            ->get()
            ->getRowArray();

        if (!$template) {
            throw new \RuntimeException("Template ID {$templateId} not found");
        }

        $template['slots'] = $this->getSlots($templateId);
        $template['subunits'] = $this->getSubunits($templateId);

        return $template;
    }

    public function getSlots(int $templateId): array {
        $slots = $this->db->table('toe_slots s')
            ->select('s.*')
            ->where('s.template_id', $templateId)
            ->get()
            ->getResultArray();

        foreach ($slots as &$slot) {
            // Roles
            $roles = $this->db->table('toe_slot_roles')
                ->select('battlefield_role')
                ->where('slot_id', $slot['slot_id'])
                ->get()
                ->getResultArray();
            $slot['roles'] = array_map(fn($r) => $r['battlefield_role'], $roles);

            // Crew
            $crew = $this->db->table('toe_slot_crews c')
                ->select('c.crew_role, ps.mos')
                ->join('toe_slots ps', 'ps.slot_id = c.personnel_slot_id')
                ->where('c.equipment_slot_id', $slot['slot_id'])
                ->get()
                ->getResultArray();
            $slot['crew'] = $crew;
        }

        return $slots;
    }

    public function getSubunits(int $templateId): array {
        $subs = $this->db->table('toe_subunits')
            ->where('parent_template_id', $templateId)
            ->get()
            ->getResultArray();

        foreach ($subs as &$sub) {
            $sub['child_template'] = $this->getTemplate($sub['child_template_id']);
        }

        return $subs;
    }

    public function getRolesForSlot($slotId) {
        return $this->db->table('toe_slot_roles')
            ->select('battlefield_role')
            ->where('slot_id', $slotId)
            ->get()
            ->getResultArray();
    }

    public function getCrewForSlot($slotId) {
        return $this->db->table('toe_slot_crews c')
            ->select('c.*, s.mos')
            ->join('toe_slots s', 's.slot_id = c.personnel_slot_id')
            ->where('c.equipment_slot_id', $slotId)
            ->get()->getResultArray();
    }
}
