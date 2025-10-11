<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RanksSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('ranks')->truncate();
        $this->db->enableForeignKeyChecks();

        $sql = file_get_contents(APPPATH . '../SQL/ranks.sql');
        $this->db->query($sql);
    }
}
