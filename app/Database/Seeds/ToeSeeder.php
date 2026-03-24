<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ToeSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('toe_slot_roles')->truncate();
        $this->db->table('toe_slots')->truncate();
        $this->db->table('toe_subunits')->truncate();
        $this->db->table('toe_templates')->truncate();
        $this->db->enableForeignKeyChecks();

        $sql = file_get_contents(APPPATH . '../SQL/toe.sql');
        $this->db->query($sql);
    }
}
