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
        $regimentId = $unitGenerator->createUnit('Regiment', '1st Davion Guards', 'The Strength of Alexander', 'Federated Suns', null);

        // 2. Create 1st Battalion
        $battalionId = $unitGenerator->createUnit('Battalion', '1st Battalion', 'Iron Fists', 'Federated Suns', $regimentId);

        // 3. Create Companies under 1st Battalion
        $ableCompanyId   = $unitGenerator->createUnit('Company', 'Able Company', 'Iron Lancers', 'Federated Suns', $battalionId);
        $bakerCompanyId  = $unitGenerator->createUnit('Company', 'Baker Company', 'Wardogs', 'Federated Suns', $battalionId);
        $charlieCompanyId= $unitGenerator->createUnit('Company', 'Charlie Company', 'Ghosts', 'Federated Suns', $battalionId);
        $dogCompanyId    = $unitGenerator->createUnit('Company', 'Dog Company', 'Steel Hounds', 'Federated Suns', $battalionId);
        $easyCompanyId   = $unitGenerator->createUnit('Company', 'Easy Company', 'Mud Dogs', 'Federated Suns', $battalionId);

        // 4. Example: Add a lance to Able Company
        $able1stLanceId = $unitGenerator->createUnit('Lance', '1st Lance', null, 'Federated Suns', $ableCompanyId, null, 'Command');

        // 5. Example Personnel
        $captainId = $generator->generatePersonnel('Davion', 'MechWarrior', 'Captain', 'Veteran');
        $generator->assignPersonnelToUnit($captainId, $able1stLanceId, '3025-01-01');

        // 6. Example Equipment
        $centurionId = $generator->generateBattleMech('Centurion', 'CN9-A', 'Active');
        $generator->assignEquipmentToPersonnel($captainId, $centurionId, 'Pilot', '3025-01-01');

        return $regimentId;
    }

}
