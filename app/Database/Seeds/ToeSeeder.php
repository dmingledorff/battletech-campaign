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

        $config = config('Database')->default;
        $cmd = sprintf(
            'mysql -u%s -p%s %s < %s',
            $config['username'],
            $config['password'],
            $config['database'],
            APPPATH . '../SQL/toe.sql'
        );
        shell_exec($cmd);
    }
}
