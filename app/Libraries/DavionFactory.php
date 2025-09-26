<?php namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

class DavionFactory
{
    protected $generator;
    protected $unitGenerator;

    public function createDavionGuards()
    {
        $generator = new Generator(db_connect());
        $unitGenerator = new UnitGenerator(db_connect());

        // 1. Create Regiment
        $regimentCommanderId = $generator->generatePersonnel('Davion', 'Officer', 'Marshal', 'Elite', 'Active', 'Male', 'Stephen', 'Davion');
        $regimentId = $unitGenerator->createUnit('Regiment', '1st Davion Guards', 'The Strength of Alexander', 'Federated Suns', null, $regimentCommanderId);

        // 2. Create 1st Battalion
        $battCommId = $generator->generatePersonnel('Davion', 'Officer', 'Major', 'Elite');
        $battalionId = $unitGenerator->createUnit('Battalion', '1st Battalion', 'Iron Fists', 'Federated Suns', $regimentId, $battCommId);
        $generator->assignPersonnelToUnit($battCommId, $battalionId, '3025-01-01');
        $this->createLances($generator, $unitGenerator, 1, $battalionId, 'Davion', $battCommId, 'Command Lance');

        // 3. Create Able Company 1st Battalion
        $captainId = $generator->generatePersonnel('Davion', 'MechWarrior', 'Captain', 'Veteran');
        $ableCompanyId   = $unitGenerator->createUnit('Company', 'Able Company', 'Iron Lancers', 'Federated Suns', $battalionId, $captainId);
        $this->createLances($generator, $unitGenerator, 3, $ableCompanyId, 'Davion', $captainId);

        $captainId = $generator->generatePersonnel('Davion', 'MechWarrior', 'Captain', 'Veteran');
        $bakerCompanyId  = $unitGenerator->createUnit('Company', 'Baker Company', 'Wardogs', 'Federated Suns', $battalionId, $captainId);
        $this->createLances($generator, $unitGenerator, 3, $bakerCompanyId, 'Davion', $captainId);

        $captainId = $generator->generatePersonnel('Davion', 'MechWarrior', 'Captain', 'Veteran');
        $charlieCompanyId= $unitGenerator->createUnit('Company', 'Charlie Company', 'Ghosts', 'Federated Suns', $battalionId, $captainId);
        $this->createLances($generator, $unitGenerator, 3, $charlieCompanyId, 'Davion', $captainId);

        /* For Later
    
        $dogCompanyId    = $unitGenerator->createUnit('Company', 'Dog Company', 'Steel Hounds', 'Federated Suns', $battalionId);
        $easyCompanyId   = $unitGenerator->createUnit('Company', 'Easy Company', 'Mud Dogs', 'Federated Suns', $battalionId);
        */

        return $regimentId;
    }

private function createLances($g, $ug, $size, $parentId, $allegiance, $compCmdId, $name = null) {
    for ($x = 1; $x <= $size; $x++) {
        // Ordinal suffix
        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        if (($x % 100) >= 11 && ($x % 100) <= 13) {
            $abbreviation = $x . 'th';
        } else {
            $abbreviation = $x . $ends[$x % 10];
        }

        // Default lance role
        $lanceRole = 'Line';

        // Weight templates
        if ($x == 1) {
            $weights   = ['Heavy', 'Heavy', 'Medium', 'Light'];
            $rank      = 'Captain';
            $exp       = 'Veteran';
            $lanceRole = 'Command';
        } elseif ($x == 2) {
            $weights = ['Medium', 'Medium', 'Medium', 'Light'];
            $rank    = 'Lieutenant';
            $exp     = 'Regular';
        } elseif ($x == 3) {
            $weights = ['Light', 'Light', 'Light', 'Light'];
            $rank    = 'Lieutenant';
            $exp     = 'Regular';
        } else {
            $weights = ['Medium', 'Medium', 'Medium', 'Medium']; // fallback
            $rank    = 'Lieutenant';
            $exp     = 'Regular';
        }

        if ($name === null) $name = $abbreviation . ' Lance';

        // Commander
        if ($x ==1)
            $coId = $compCmdId;
        else
            $coId = $g->generatePersonnel('Davion', 'MechWarrior', $rank, $exp);
        $lanceId = $ug->createUnit('Lance', $name, null, $allegiance, $parentId, $coId, $lanceRole);

        // Slots
        for ($y = 0; $y < 4; $y++) {
            if ($y == 0) {
                $pilot = $coId; // commander also pilots
            } else {
                $exp   = (mt_rand(0, 1) === 0) ? 'Regular' : 'Green';
                $pilot = $g->generatePersonnel('Davion', 'MechWarrior', 'Sergeant', $exp);
            }

            $g->assignPersonnelToUnit($pilot, $lanceId, '3025-01-01');
            $mechId = $g->generateBattleMech(null, null, $weights[$y], $lanceId);
            $g->assignEquipmentToPersonnel($pilot, $mechId, 'Pilot', '3025-01-01');
        }
    }
}


}
