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

        $template['slots']    = $this->getSlots($templateId);
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
            // Battlefield roles
            $roles = $this->db->table('toe_slot_roles')
                ->select('battlefield_role')
                ->where('slot_id', $slot['slot_id'])
                ->get()
                ->getResultArray();
            $slot['roles'] = array_map(fn($r) => $r['battlefield_role'], $roles);

            // Do NOT preload crew here → handled per equipment slot in TemplateGenerator
            $slot['crew'] = [];
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

    public function getCrewForSlot($slotId): array {
        return $this->db->table('toe_slot_crews c')
            ->select('c.equipment_slot_id, c.personnel_slot_id, c.crew_role, ps.mos, ps.min_rank_id, ps.max_rank_id')
            ->join('toe_slots ps', 'ps.slot_id = c.personnel_slot_id', 'left')
            ->where('c.equipment_slot_id', $slotId)
            ->get()
            ->getResultArray();
    }

    public function getAlphabet(): array {
        return [
            'Able', 'Baker', 'Charlie', 'Dog',
            'Easy', 'Fox', 'George', 'How',
            'Item', 'Jig', 'King', 'Love',
            'Mike', 'Nan', 'Oboe', 'Peter',
            'Queen', 'Roger', 'Sugar', 'Tare',
            'Uncle', 'Victor', 'William', 'Xray',
            'Yoke', 'Zebra'
        ];
    }

    public function findSlotByRank(int $templateId, int $rankId): ?int {
        $row = $this->db->table('toe_slots')
            ->where('template_id', $templateId)
            ->where('min_rank_id', $rankId)
            ->get()
            ->getRowArray();

        return $row['slot_id'] ?? null;
    }

    /**
     * Generic helper: get rank_id by full_name
     */
    public function getRankId(string $rankName, string $faction = 'Davion'): ?int {
        $row = $this->db->table('ranks')
            ->select('id')
            ->where('faction', $faction)
            ->where('full_name', $rankName)
            ->get(1)
            ->getRowArray();

        return $row['id'] ?? null;
    }



}
