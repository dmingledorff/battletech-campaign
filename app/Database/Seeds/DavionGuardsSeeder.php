<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DavionGuardsSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Adjust path if you move the SQL file
        $file = APPPATH . '../SQL/clean_regen_inserts_full.sql';

        if (!file_exists($file)) {
            throw new \RuntimeException("SQL file not found: " . $file);
        }

        $sql = file_get_contents($file);

        // Split on semicolons so multiple statements run
        $queries = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($queries as $query) {
            if ($query) {
                $db->query($query);
            }
        }
    }
}
