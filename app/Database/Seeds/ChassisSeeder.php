<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ChassisSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('chassis')->truncate();
        $this->db->enableForeignKeyChecks();

        $sql = file_get_contents(APPPATH . '../SQL/chassis.sql');
        $this->db->query($sql);
    }
}
