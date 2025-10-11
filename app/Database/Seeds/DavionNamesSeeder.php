<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DavionNamesSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('davion_names')->truncate();
        $this->db->enableForeignKeyChecks();

        $sql = file_get_contents(APPPATH . '../SQL/davion_names.sql');
        $this->db->query($sql);
    }
}
