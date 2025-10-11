<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FactionsSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('factions')->truncate();
        $this->db->enableForeignKeyChecks();

        $sql = file_get_contents(APPPATH . '../SQL/factions.sql');
        $this->db->query($sql);
    }
}
