<?php

namespace App\Libraries;

use App\Models\ToeTemplateModel;
use App\Models\RankModel;
use App\Models\FactionModel;
use App\Models\GameStateModel;

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
    }

    public function generateFromTemplate(
        array $template,
        $parentUnitId = null,
        $allegiance = 'Mercenary',
        ?string $gameDate = null,
        ?int $defaultLocation = null,
        ?string $unitName = null
    ) {
        $gameDate = $gameDate ?? (new GameStateModel())->getProperty('current_date') ?? '3025-01-01';
        $unitType    = $template['unit_type'];
        // Resolve a scoped counter for this parent
        $num      = $this->getNextCounter($parentUnitId ?? 0, $unitType);
        $unitName = $unitName ?? $this->generateUnitName($unitType, $template, $num, $parentUnitId);

        $factionModel = new FactionModel();
        $faction = $factionModel->where('house', $allegiance)->first();

        // Create unit
        $unitId = $this->unitGenerator->createUnit(
            $template['unit_type'],
            $unitName,
            $template['nickname'] ?? null,
            $faction['faction_id'],
            $parentUnitId,
            null,
            $template['role'] ?? null,
            $template['template_id'],
            $defaultLocation
        );

        // Pass A: personnel slots
        $personBySlotId = [];
        foreach ($template['slots'] as $slot) {
            if ($slot['slot_type'] === 'Personnel') {
                // Resolve rank from grade + faction
                $rankModel = new RankModel();
                $minGrade  = (int)($slot['min_grade'] ?? 1);
                $rank      = $rankModel->getRankByGrade($minGrade, $allegiance);
                // Fall back to Mercenary ranks if faction has none
                if (!$rank) {
                    $rank = $rankModel->getRankByGrade($minGrade, 'Mercenary');
                }

                $rankId = $rank['id'] ?? 1;

                $pid = $this->generator->generatePersonnel(
                    $allegiance,
                    $slot['mos'] ?? 'Infantry',
                    $rankId,
                    'Regular'
                );
                $this->generator->assignPersonnelToUnit($pid, $unitId, $gameDate);
                $personBySlotId[(int)$slot['slot_id']] = $pid;
            }
        }

        $this->assignCommanders(
            $unitId,
            $template,
            $personBySlotId,
            $parentUnitId,
            $num,
            $allegiance
        );

        // Pass B: equipment slots + crew from TOE
        foreach ($template['slots'] as $slot) {
            if ($slot['slot_type'] !== 'Equipment') continue;

            $eid = $this->generator->generateEquipment(
                $slot['equipment_type'],
                null,
                null,
                $slot['roles'] ?? null,
                $slot['weight_class'] ?? null,
                $unitId,
                $allegiance,
                'Active'
            );

            // Use TOE crew mapping, not chassis_crew_requirements
            $crews = $this->toeModel->getCrewForSlot($slot['slot_id']);
            foreach ($crews as $crew) {
                $personnelSlotId = (int)($crew['personnel_slot_id'] ?? 0);

                if ($personnelSlotId && isset($personBySlotId[$personnelSlotId])) {
                    // Use already-generated person from Pass A
                    $crewId = $personBySlotId[$personnelSlotId];
                } else {
                    // Generate extra person if not in TOE (shouldn't happen normally)
                    $minGrade = (int)($crew['min_grade'] ?? 1);
                    $rankModel = new RankModel();
                    $rank      = $rankModel->getRankByGrade($minGrade, $allegiance);
                    $rankId    = $rank['id'] ?? 1;
                    $crewId = $this->generator->generatePersonnel(
                        $allegiance,
                        $crew['mos'] ?? 'Infantry',
                        $rankId,
                        'Regular'
                    );
                    $this->generator->assignPersonnelToUnit($crewId, $unitId, '3025-01-01');
                }

                $this->generator->assignEquipmentToPersonnel(
                    $crewId,
                    $eid,
                    $crew['crew_role'],
                    $gameDate
                );
            }
        }

        // Recurse subunits
        foreach ($template['subunits'] as $sub) {
            for ($i = 0; $i < $sub['quantity']; $i++) {
                $this->generateFromTemplate($sub['child_template'], $unitId, $allegiance, $gameDate);
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
    private function assignCommanders($unitId, $template, $personBySlotId, $parentUnitId, $sequenceNum, $allegiance = 'Mercenary')
    {
        $type = $template['unit_type'];
        $role = $template['role'] ?? null;

        // Use grades instead of rank IDs
        $ltGrade  = 4;   // Leftenant/Chu-i
        $sgtGrade = 3;   // Sergeant/Gunso
        $cptGrade = 5;   // Captain/Tai-i
        $majGrade = 6;   // Major/Sho-sa
        $mshGrade = 12;  // Marshal/Gunji-no-Kanrei

        // Resolve actual rank IDs for promotion (still needed for promotePersonnel)
        $rankModel = new RankModel();

        if ($parentUnitId) {
            $parentUnit = $this->unitGenerator->getUnitById($parentUnitId);
            $parentType = $parentUnit['unit_type'] ?? null;
        }

        if ($type === 'Lance' && ($parentType ?? null) === 'Battalion' && $role === 'Command') {
            $ltSlot      = $this->toeModel->findSlotByGrade($template['template_id'], $ltGrade);
            $commanderId = $personBySlotId[$ltSlot] ?? null;
            if ($commanderId) {
                $majRank = $rankModel->getRankByGrade($majGrade, $allegiance)
                    ?? $rankModel->getRankByGrade($majGrade, 'Mercenary');
                $this->generator->promotePersonnel($commanderId, $majRank['id']);
                $this->unitGenerator->assignCommander($unitId, $commanderId);
                $this->unitGenerator->assignCommander($parentUnitId, $commanderId);
            }
        }

        if ($type === 'Lance' && ($parentType ?? null) === 'Company') {
            $ltSlot      = $this->toeModel->findSlotByGrade($template['template_id'], $ltGrade);
            $commanderId = $personBySlotId[$ltSlot] ?? null;
            if ($commanderId) {
                if ($sequenceNum === 1) {
                    $cptRank = $rankModel->getRankByGrade($cptGrade, $allegiance)
                        ?? $rankModel->getRankByGrade($cptGrade, 'Mercenary');
                    $this->generator->promotePersonnel($commanderId, $cptRank['id']);
                    $this->unitGenerator->assignCommander($parentUnitId, $commanderId);
                }
                $this->unitGenerator->assignCommander($unitId, $commanderId);
            }
        }

        if ($type === 'Platoon') {
            $ltSlot = $this->toeModel->findSlotByGrade($template['template_id'], $ltGrade);
            if ($ltSlot && isset($personBySlotId[$ltSlot])) {
                $this->unitGenerator->assignCommander($unitId, $personBySlotId[$ltSlot]);
            }
        }

        if ($type === 'Squad') {
            $sgtSlot = $this->toeModel->findSlotByGrade($template['template_id'], $sgtGrade);
            if ($sgtSlot && isset($personBySlotId[$sgtSlot])) {
                $this->unitGenerator->assignCommander($unitId, $personBySlotId[$sgtSlot]);
            }
        }

        if ($type === 'Company' && $role === 'Infantry') {
            $cptSlot = $this->toeModel->findSlotByGrade($template['template_id'], $cptGrade);
            if ($cptSlot && isset($personBySlotId[$cptSlot])) {
                $this->unitGenerator->assignCommander($unitId, $personBySlotId[$cptSlot]);
            }
        }

        if ($type === 'Regiment') {
            $mshSlot = $this->toeModel->findSlotByGrade($template['template_id'], $mshGrade);
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
    private function getNextCounter($parentId, $unitType)
    {
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
    private function generateUnitName($unitType, $template, $num, $parentUnitId)
    {
        switch ($unitType) {
            case 'Regiment':
                // Always use template name
                return $template['name'];

            case 'Battalion':
                // Use template name only if top-level (no parent)
                if (!$parentUnitId) {
                    return $template['name'];
                }
                return $this->ordinal($num) . " Battalion";

            case 'Company':
                return $this->alphabet[$num - 1] . " Company";

            case 'Lance':
                if (($template['role'] ?? null) === 'Command' && $this->isParentBattalion($parentUnitId)) {
                    return "Command Lance";
                }
                return $this->ordinal($num) . " Lance";

            case 'Platoon':
                if (!empty($template['mobility'])) {
                    return $this->ordinal($num) . ' ' . $template['mobility'] . ' Platoon';
                }
                return $this->ordinal($num) . " Platoon";

            case 'Squad':
                return $this->ordinal($num) . " Squad";

            default:
                return $template['name'];
        }
    }

    private function isParentBattalion($parentUnitId)
    {
        $row = $this->toeModel->find($parentUnitId);
        return $row && $row['unit_type'] === 'Battalion';
    }

    private function ordinal($number)
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if (($number % 100) >= 11 && ($number % 100) <= 13) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }
}
