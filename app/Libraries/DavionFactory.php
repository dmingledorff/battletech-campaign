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
        $this->createLances($generator, $unitGenerator, 1, $battalionId, 'Davion', $battCommId, null, 'Command Lance');

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

        $captainId = $generator->generatePersonnel('Davion', 'Vehicle', 'Captain', 'Veteran');
        $dogCompanyId = $unitGenerator->createUnit('Company', 'Dog Company', 'Steel Hounds', 'Federated Suns', $battalionId, $captainId);
        $this->createLances($generator, $unitGenerator, 3, $dogCompanyId, 'Davion', $captainId, true);
        
        $captainId = $generator->generatePersonnel('Davion', 'Infantry', 'Captain', 'Veteran');
        $easyCompanyId   = $unitGenerator->createUnit('Company', 'Easy Company', 'Mud Dogs', 'Federated Suns', $battalionId, $captainId);
        $generator->assignPersonnelToUnit($captainId, $easyCompanyId, '3025-01-01');
        $this->createPlatoons($generator, $unitGenerator, 3, $easyCompanyId, 'Davion');

        return $regimentId;
    }

    function createLances($g, $ug, $size, $parentId, $allegiance, $compCmdId, $vehicle = false, $name = null) {
        for ($x = 1; $x <= $size; $x++) {
            // Ordinal suffix
            $abbreviation = $this->ordinal($x);

            // Default lance role
            $lanceRole = 'Line';
            $mos = $vehicle ? 'Tanker' : 'MechWarrior';

            // Weight templates
            if (!$vehicle) {
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
                    $lanceRole = 'Recon';
                } else {
                    $weights = ['Medium', 'Medium', 'Medium', 'Medium']; // fallback
                    $rank    = 'Lieutenant';
                    $exp     = 'Regular';
                }
            } else {
                if ($x == 1) {
                    $weights   = ['Heavy', 'Heavy', 'Assault', 'Medium'];
                    $rank      = 'Captain';
                    $exp       = 'Veteran';
                    $lanceRole = 'Command';
                } elseif ($x == 2) {
                    $weights = ['Heavy', 'Heavy', 'Medium', 'Medium'];
                    $rank    = 'Lieutenant';
                    $exp     = 'Regular';
                } elseif ($x == 3) {
                    $weights = ['Heavy', 'Medium', 'Medium', 'Light'];
                    $rank    = 'Lieutenant';
                    $exp     = 'Regular';
                } elseif ($x == 4) {
                    $weights = ['Light', 'Light', 'Light', 'Light'];
                    $rank    = 'Lieutenant';
                    $exp     = 'Regular';
                    $lanceRole = 'Recon';
                } else {
                    $weights = ['Medium', 'Medium', 'Medium', 'Medium']; // fallback
                    $rank    = 'Lieutenant';
                    $exp     = 'Regular';
                }
            }

            // Unique lance name per iteration
            $lanceName = ($name && $x === 1) ? $name : $abbreviation . ' Lance';

            // Commander
            $coId = ($x === 1) ? $compCmdId : $g->generatePersonnel('Davion', $mos, $rank, $exp);

            $lanceId = $ug->createUnit('Lance', $lanceName, null, $allegiance, $parentId, $coId, $lanceRole);

            // Slots
            foreach ($weights as $i => $weight) {
                if ($i === 0) {
                    $commander = $coId;
                } else {
                    $exp   = (mt_rand(0,1) === 0) ? 'Regular' : 'Green';
                    $commander = $g->generatePersonnel('Davion', $mos, 'Sergeant', $exp);
                }
                $g->assignPersonnelToUnit($commander, $lanceId, '3025-01-01');

                $equipType = $vehicle ? 'Vehicle' : 'BattleMech';
                $eqId = $g->generateEquipment($equipType, null, null, $weight, $lanceId);
                $g->assignEquipmentToPersonnel($commander, $eqId, $vehicle ? 'Commander' : 'Pilot', '3025-01-01');

                if ($vehicle) {
                    // add driver + gunner per tank
                    $driver = $g->generatePersonnel('Davion', 'Tanker', 'Corporal', 'Regular');
                    $gunner = $g->generatePersonnel('Davion', 'Tanker', 'Private', (mt_rand(0,1) ? 'Regular' : 'Green'));
                    foreach ([['Driver',$driver],['Gunner',$gunner]] as [$role,$crew]) {
                        $g->assignPersonnelToUnit($crew, $lanceId, '3025-01-01');
                        $g->assignEquipmentToPersonnel($crew, $eqId, $role, '3025-01-01');
                    }
                }
            }
        }
    }

    private function createPlatoons($g, $ug, $size, $parentId, $allegiance, $mechanized = true, $name = null) {
        for ($x = 1; $x <= $size; $x++) {
            // Ordinal suffix
            $abbreviation = $this->ordinal($x);

            $pltRole = 'Line';

            // Dynamic platoon name
            if ($name === null) {
                $pltName = $abbreviation . ' ' . ($mechanized ? 'Mechanized Platoon' : 'Platoon');
            } else {
                // Use custom name only for the first platoon
                $pltName = ($x === 1) ? $name : $abbreviation . ' ' . ($mechanized ? 'Mechanized Platoon' : 'Platoon');
            }

            // Platoon commander
            $coId = $g->generatePersonnel('Davion', 'Infantry', 'Lieutenant', 'Regular');
            $pltId = $ug->createUnit('InfantryPlatoon', $pltName, null, $allegiance, $parentId, $coId, $pltRole);
            $g->assignPersonnelToUnit($coId, $pltId, '3025-01-01');

            // 4 Squads
            for ($y = 1; $y <= 4; $y++) {
                $sqdName = $this->ordinal($y) . ' Squad';

                // Squad commander
                $sqdCoId = $g->generatePersonnel('Davion', 'Infantry', 'Sergeant', 'Regular');
                $sqdId = $ug->createUnit('Squad', $sqdName, null, $allegiance, $pltId, $sqdCoId);
                $g->assignPersonnelToUnit($sqdCoId, $sqdId, '3025-01-01');

                // Generate APC only if mechanized
                $apcId = null;
                if ($mechanized) {
                    $apcId = $g->generateEquipment('APC', null, 'Wheeled', null, $sqdId);
                }

                // 6 more Squad members (2 corporals, 4 privates)
                for ($z = 0; $z < 6; $z++) {
                    if ($z < 2) {
                        $rank = 'Corporal';
                        $exp  = (mt_rand(0, 1) === 0) ? 'Regular' : 'Green';
                    } else {
                        $rank = 'Private';
                        $exp  = 'Green';
                    }

                    $soldier = $g->generatePersonnel('Davion', 'Infantry', $rank, $exp);
                    $g->assignPersonnelToUnit($soldier, $sqdId, '3025-01-01');

                    // Assign APC crew if mechanized
                    if ($mechanized) {
                        if ($z == 2) {
                            $g->assignEquipmentToPersonnel($soldier, $apcId, 'Driver', '3025-01-01');
                        }
                        if ($z == 3) {
                            $g->assignEquipmentToPersonnel($soldier, $apcId, 'Gunner', '3025-01-01');
                        }
                    }
                }
            }
        }
    }

    private function ordinal($number) {
        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        if (($number % 100) >= 11 && ($number % 100) <= 13) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }




}
