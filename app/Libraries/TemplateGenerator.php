<?php namespace App\Libraries;

use App\Models\ToeTemplateModel;

class TemplateGenerator
{
    protected $generator;
    protected $unitGenerator;
    protected $toeModel;

    public function __construct()
    {
        $this->generator     = new Generator(db_connect());
        $this->unitGenerator = new UnitGenerator(db_connect());
        $this->toeModel      = new ToeTemplateModel();
    }

    public function generateFromTemplate(array $template, $parentUnitId = null, $allegiance = 'Davion')
    {
        // Commander (basic example: rank mapping could be added later)
        $commanderId = $this->generator->generatePersonnel(
            $allegiance,
            'Officer',
            'Captain',
            'Veteran'
        );

        // Create the unit
        $unitId = $this->unitGenerator->createUnit(
            $template['unit_type'],
            $template['name'],
            //$template['description'] ?? null,
            $template['nickname'] ?? null,
            $allegiance,
            $parentUnitId,
            $commanderId,
            //$template['is_core'] ?? true ? 'Core' : 'Detachment'
        );

        // Slots (personnel & equipment)
        foreach ($template['slots'] as $slot) {
            if ($slot['slot_type'] === 'Personnel') {
                $pid = $this->generator->generatePersonnel(
                    $allegiance,
                    $slot['mos'] ?? 'Infantry',
                    $slot['min_rank_id'] ?? 'Private',
                    'Regular'
                );
                $this->generator->assignPersonnelToUnit($pid, $unitId, '3025-01-01');
            }
            elseif ($slot['slot_type'] === 'Equipment') {
                $eid = $this->generator->generateEquipment(
                    $slot['equipment_type'],
                    null,   // name
                    null,   // variant
                    $slot['roles'] ?? null, // battlefieldRole (can be string or array)
                    $slot['weight_class'],
                    $unitId,
                    'Active'
                );

                // Crew assignment
                foreach ($slot['crew'] as $crew) {
                    $crewId = $this->generator->generatePersonnel(
                        $allegiance,
                        $crew['mos'] ?? 'Tanker',
                        'Private',
                        'Regular'
                    );
                    $this->generator->assignPersonnelToUnit($crewId, $unitId, '3025-01-01');
                    $this->generator->assignEquipmentToPersonnel($crewId, $eid, $crew['crew_role'], '3025-01-01');
                }
            }
        }

        // Subunits (recursive)
        foreach ($template['subunits'] as $sub) {
            for ($i = 0; $i < $sub['quantity']; $i++) {
                $this->generateFromTemplate($sub['child_template'], $unitId, $allegiance);
            }
        }

        return $unitId;
    }
}
