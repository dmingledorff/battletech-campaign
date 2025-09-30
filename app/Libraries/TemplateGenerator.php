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
        // NOTE: Commander creation omitted here; you can add later with rank awareness
        $commanderId = null;

        // Create unit
        $unitId = $this->unitGenerator->createUnit(
            $template['unit_type'],
            $template['name'],
            $template['nickname'] ?? null,
            $allegiance,
            $parentUnitId,
            $commanderId
        );

        // -------- Pass A: create personnel for Personnel slots --------
        // Map: toe_slots.slot_id  -> generated personnel_id
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

        // -------- Pass B: create equipment and assign crews --------
        foreach ($template['slots'] as $slot) {
            if ($slot['slot_type'] !== 'Equipment') {
                continue;
            }

            // Create the equipment
            $eid = $this->generator->generateEquipment(
                $slot['equipment_type'],
                null,                      // name
                null,                      // variant
                $slot['roles'] ?? null,    // allowed battlefield_role(s)
                $slot['weight_class'] ?? null,
                $unitId,
                'Active'
            );

            // Fetch THIS equipment slot’s crew definitions
            $crews = $this->toeModel->getCrewForSlot($slot['slot_id']);

            foreach ($crews as $crew) {
                $personnelSlotId = (int)$crew['personnel_slot_id'];

                // If the crew is linked to a Personnel slot, reuse that person.
                if ($personnelSlotId && isset($personBySlotId[$personnelSlotId])) {
                    $crewId = $personBySlotId[$personnelSlotId];
                } else {
                    // Fallback: generate a new person (for templates that intentionally omit a dedicated Personnel slot)
                    $crewId = $this->generator->generatePersonnel(
                        $allegiance,
                        $crew['mos'] ?? 'Tanker',
                        (int)($crew['min_rank_id'] ?? 1),
                        'Regular'
                    );
                    $this->generator->assignPersonnelToUnit($crewId, $unitId, '3025-01-01');
                }

                // Assign that person to this equipment with the crew role
                $this->generator->assignEquipmentToPersonnel(
                    $crewId,
                    $eid,
                    $crew['crew_role'],
                    '3025-01-01'
                );
            }
        }

        // -------- Recurse sub-units --------
        foreach ($template['subunits'] as $sub) {
            for ($i = 0; $i < $sub['quantity']; $i++) {
                $this->generateFromTemplate($sub['child_template'], $unitId, $allegiance);
            }
        }

        return $unitId;
    }
}
