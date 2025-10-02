<?php namespace App\Libraries;

use App\Models\ToeTemplateModel;
use App\Models\RankModel;

class TemplateGenerator
{
    protected $generator;
    protected $unitGenerator;
    protected $toeModel;
    protected $alphabet;
    protected $rankCache = [];

    // counters scoped per parentId
    protected $counters = [];

    public function __construct()
    {
        $this->generator     = new Generator(db_connect());
        $this->unitGenerator = new UnitGenerator(db_connect());
        $this->toeModel      = new ToeTemplateModel();
        $this->alphabet      = $this->toeModel->getAlphabet();

        // Preload ranks for all factions
        $rankModel = new RankModel();
        $allRanks = $rankModel->findAll();

        foreach ($allRanks as $rank) {
            $faction = $rank['faction'];
            $this->rankCache[$faction]['byName'][$rank['full_name']] = $rank['id'];
            $this->rankCache[$faction]['byId'][$rank['id']]          = $rank;
        }
    }

    public function getRankIdByName(string $name, string $faction = 'Davion'): ?int {
        return $this->rankCache[$faction]['byName'][$name] ?? null;
    }

    public function getRankById(int $id, string $faction = 'Davion'): ?array {
        return $this->rankCache[$faction]['byId'][$id] ?? null;
    }

    public function generateFromTemplate(array $template, $parentUnitId = null, $allegiance = 'Davion') {
        $commanderId = null;
        $unitType    = $template['unit_type'];
        // Resolve a scoped counter for this parent
        $num = $this->getNextCounter($parentUnitId ?? 0, $unitType);
        if ($unitType == 'Regiment') {
            $unitName = '1st Davion Guards';
        }
        else {
            // Generate name
            $unitName = $this->generateUnitName($unitType, $template, $num, $parentUnitId);
        }

        // Create unit
        $unitId = $this->unitGenerator->createUnit(
            $template['unit_type'],
            $unitName,
            $template['nickname'] ?? null,
            $allegiance,
            $parentUnitId,
            null,
            $template['role'] ?? null,
            $template['template_id']
        );

        // Pass A: personnel slots
        $personBySlotId = [];
        foreach ($template['slots'] as $slot) {
            if ($slot['slot_type'] === 'Personnel') {
                $pid = $this->generator->generatePersonnel(
                    $allegiance,
                    $slot['mos'] ?? 'Infantry',
                    (int)($slot['min_rank_id'] ?? 1),
                    'Regular'
                );
                $this->generator->assignPersonnelToUnit($pid, $unitId, '3025-01-01');
                $personBySlotId[(int)$slot['slot_id']] = $pid;
            }
        }

        $this->assignCommanders(
            $unitId,
            $template,
            $personBySlotId,
            $parentUnitId,
            $num
        );

        // Pass B: equipment slots + crew
        foreach ($template['slots'] as $slot) {
            if ($slot['slot_type'] !== 'Equipment') continue;

            $eid = $this->generator->generateEquipment(
                $slot['equipment_type'],
                null,
                null,
                $slot['roles'] ?? null,
                $slot['weight_class'] ?? null,
                $unitId,
                'Active'
            );

            $crews = $this->toeModel->getCrewForSlot($slot['slot_id']);
            foreach ($crews as $crew) {
                $personnelSlotId = (int)($crew['personnel_slot_id'] ?? 0);

                if ($personnelSlotId && isset($personBySlotId[$personnelSlotId])) {
                    $crewId = $personBySlotId[$personnelSlotId];
                } else {
                    $crewId = $this->generator->generatePersonnel(
                        $allegiance,
                        $crew['mos'] ?? 'Tanker',
                        (int)($crew['min_rank_id'] ?? 1),
                        'Regular'
                    );
                    $this->generator->assignPersonnelToUnit($crewId, $unitId, '3025-01-01');
                }

                $this->generator->assignEquipmentToPersonnel(
                    $crewId,
                    $eid,
                    $crew['crew_role'],
                    '3025-01-01'
                );
            }
        }

        // Recurse subunits
        foreach ($template['subunits'] as $sub) {
            for ($i = 0; $i < $sub['quantity']; $i++) {
                $this->generateFromTemplate($sub['child_template'], $unitId, $allegiance);
            }
        }

        return $unitId;
    }

    /**
     * Assign commanders to units based on type, role, and sequence number.
     *
     * @param int   $unitId          The generated unit ID
     * @param array $template        The template array for this unit
     * @param array $personBySlotId  Map of slot_id → personnel_id
     * @param int   $parentUnitId    Parent unit ID
     * @param int   $sequenceNum     Sequence number (1 = first unit of type under parent)
     */
    private function assignCommanders($unitId, $template, $personBySlotId, $parentUnitId, $sequenceNum) {
        $type = $template['unit_type'];
        $role = $template['role'] ?? null;

        // Rank IDs (from cache)
        $ltRankId  = $this->getRankIdByName('Leftenant');
        $sgtRankId = $this->getRankIdByName('Sergeant');
        $cptRankId = $this->getRankIdByName('Captain');
        $majRankId = $this->getRankIdByName('Major');
        $mshRankId = $this->getRankIdByName('Marshal');

        // Get parent type
        if ($parentUnitId) {
            $parentUnit = $this->unitGenerator->getUnitById($parentUnitId);
            $parentType = $parentUnit['unit_type'] ?? null;
        }

        // --- Command Lance (child of Battalion) ---
        if ($type === 'Lance' && $parentType === 'Battalion' && $role === 'Command') {
            $ltSlot = $this->toeModel->findSlotByRank($template['template_id'], $ltRankId);
            $commanderId = $personBySlotId[$ltSlot] ?? null;

            if ($commanderId) {
                $this->generator->promotePersonnel($commanderId, $majRankId);
                $this->unitGenerator->assignCommander($unitId, $commanderId);
                $this->unitGenerator->assignCommander($parentUnitId, $commanderId);
            }
        }

        // --- Regular Lance (child of Company) ---
        if ($type === 'Lance' && $parentType === 'Company') {
            $ltSlot = $this->toeModel->findSlotByRank($template['template_id'], $ltRankId);
            $commanderId = $personBySlotId[$ltSlot] ?? null;

            if ($commanderId) {
                if ($sequenceNum === 1) {
                    // First lance → promote to Captain and also company commander
                    $this->generator->promotePersonnel($commanderId, $cptRankId);
                    $this->unitGenerator->assignCommander($parentUnitId, $commanderId);
                }
                $this->unitGenerator->assignCommander($unitId, $commanderId);
            }
        }

        // --- Platoon commander ---
        if ($type === 'Platoon') {
            $ltSlot = $this->toeModel->findSlotByRank($template['template_id'], $ltRankId);
            if ($ltSlot && isset($personBySlotId[$ltSlot])) {
                $this->unitGenerator->assignCommander($unitId, $personBySlotId[$ltSlot]);
            }
        }

        // --- Squad leader ---
        if ($type === 'Squad') {
            $sgtSlot = $this->toeModel->findSlotByRank($template['template_id'], $sgtRankId);
            if ($sgtSlot && isset($personBySlotId[$sgtSlot])) {
                $this->unitGenerator->assignCommander($unitId, $personBySlotId[$sgtSlot]);
            }
        }

        // --- Infantry Company commander ---
        if ($type === 'Company' && $role === 'Infantry') {
            $cptSlot = $this->toeModel->findSlotByRank($template['template_id'], $cptRankId);
            if ($cptSlot && isset($personBySlotId[$cptSlot])) {
                $this->unitGenerator->assignCommander($unitId, $personBySlotId[$cptSlot]);
            }
        }

        // --- Regiment commander ---
        if ($type === 'Regiment') {
            $mshSlot = $this->toeModel->findSlotByRank($template['template_id'], $mshRankId);
            if ($mshSlot && isset($personBySlotId[$mshSlot])) {
                $this->unitGenerator->assignCommander($unitId, $personBySlotId[$mshSlot]);
            }
        }
    }

    /**
     * Utility: check if parent unit is a Company
     */
    private function isChildOfCompany(?int $parentUnitId): bool
    {
        if (!$parentUnitId) return false;
        $parent = $this->unitGenerator->getUnitById($parentUnitId);
        return $parent && $parent['unit_type'] === 'Company';
    }

    /**
     * Utility: check if this is the Command Lance under a Battalion
     */
    private function isCommandLance(array $template, ?int $parentUnitId): bool
    {
        if (!$parentUnitId) return false;
        $parent = $this->unitGenerator->getUnitById($parentUnitId);
        return $parent && $parent['unit_type'] === 'Battalion' && ($template['role'] ?? null) === 'Command';
    }

    /**
     * Returns next counter for unitType under a specific parent.
     */
    private function getNextCounter($parentId, $unitType) {
        if (!isset($this->counters[$parentId])) {
            $this->counters[$parentId] = [
                'Regiment'  => 1,
                'Battalion' => 1,
                'Company'   => 1,
                'Lance'     => 1,
                'Platoon'   => 1,
                'Squad'     => 1,
            ];
        }

        $value = $this->counters[$parentId][$unitType];
        $this->counters[$parentId][$unitType]++;

        return $value;
    }

    /**
     * Generate names based on unitType + scoped counter.
     */
    private function generateUnitName($unitType, $template, $num, $parentUnitId) {
        switch ($unitType) {
            case 'Battalion':
                return $this->ordinal($num) . " Battalion";

            case 'Company':
                return $this->alphabet[$num-1] . " Company";

            case 'Lance':
                // Special case: Battalion-level Command Lance
                if (($template['role'] ?? null) === 'Command' &&
                    ($this->isParentBattalion($parentUnitId))) {
                    return "Command Lance";
                }
                return $this->ordinal($num) . " Lance";

            case 'Platoon':
                if (!empty($template['mobility'])) {
                    return $this->ordinal($num) . ' '
                     . $template['mobility'] . ' ' . $unitType;
                } else {
                    $this->ordinal($num) . " Platoon";
                }

            case 'Squad':
                return $this->ordinal($num) . " Squad";

            default:
                return $template['name']; // fallback
        }
    }

    private function isParentBattalion($parentUnitId)
    {
        $row = $this->toeModel->find($parentUnitId);
        return $row && $row['unit_type'] === 'Battalion';
    }

    private function ordinal($number)
    {
        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        if (($number % 100) >= 11 && ($number % 100) <= 13) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }
}
