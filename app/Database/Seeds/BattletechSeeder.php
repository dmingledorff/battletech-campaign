<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BattletechSeeder extends Seeder
{
    public function run()
    {
        // Run in the correct order
        $this->call('FactionsSeeder');
        $this->call('LocationsSeeder');
        $this->call('CallsignPoolSeeder');
        $this->call('ChassisSeeder');
        $this->call('DavionNamesSeeder');
        $this->call('RankSeeder');
        $this->call('ToeSeeder');
    }
}
