<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CallsignPoolSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('callsign_pool')->truncate();
        $this->db->enableForeignKeyChecks();

        $sql = file_get_contents(APPPATH . '../SQL/callsign_pool.sql');
        $this->db->query($sql);
    }
}
