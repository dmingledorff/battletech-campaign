<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DavionNamesSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('name_pool')->where('faction', 'Davion')->delete();
        $this->db->enableForeignKeyChecks();

        $sql = file_get_contents(APPPATH . '../SQL/davion_names.sql');
        $this->db->query($sql);
    }
}