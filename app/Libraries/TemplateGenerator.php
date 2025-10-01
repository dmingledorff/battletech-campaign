<?php namespace App\Libraries;

use App\Models\ToeTemplateModel;

class TemplateGenerator
{
    protected $generator;
    protected $unitGenerator;
    protected $toeModel;
    protected $alphabet;

    // counters scoped per parentId
    protected $counters = [];

    public function __construct()
    {
        $this->generator     = new Generator(db_connect());
        $this->unitGenerator = new UnitGenerator(db_connect());
        $this->toeModel      = new ToeTemplateModel();
        $this->alphabet      = $this->toeModel->getAlphabet();
    }

    public function generateFromTemplate(array $template, $parentUnitId = null, $allegiance = 'Davion')
    {
        $commanderId = null;
        $unitType    = $template['unit_type'];

        if ($unitType == 'Regiment') {
            $unitName = '1st Davion Guards';
        }
        else {
            // Resolve a scoped counter for this parent
            $num = $this->getNextCounter($parentUnitId ?? 0, $unitType);
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
            $commanderId
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
     * Returns next counter for unitType under a specific parent.
     */
    private function getNextCounter($parentId, $unitType)
    {
        if (!isset($this->counters[$parentId])) {
            $this->counters[$parentId] = [
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
                return $this->ordinal($num) . " Platoon";

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
