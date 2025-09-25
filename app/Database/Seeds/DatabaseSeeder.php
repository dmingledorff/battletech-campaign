<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Call your regiment seeder
        $this->call('DavionGuardsSeeder');

        // 🔹 Add more later as you expand
        // $this->call('SomeOtherSeeder');
    }
}
