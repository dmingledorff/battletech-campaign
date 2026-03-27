<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class KuritaNamesSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('name_pool')->where('faction', 'Kurita')->delete();
        $this->db->enableForeignKeyChecks();

        $sql = file_get_contents(APPPATH . '../SQL/kurita_names.sql');
        $this->db->query($sql);
    }
}