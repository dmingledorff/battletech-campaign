<?php namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('locations')->truncate();
        $this->db->table('planets')->truncate();
        $this->db->table('star_systems')->truncate();
        $this->db->enableForeignKeyChecks();

        $config = config('Database')->default;
        $cmd = sprintf(
            'mysql -u%s -p%s %s < %s',
            $config['username'],
            $config['password'],
            $config['database'],
            APPPATH . '../SQL/locations.sql'
        );
        shell_exec($cmd);
    }
}