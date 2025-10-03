<?php namespace App\Models;

use CodeIgniter\Model;

class PlanetModel extends Model
{
    protected $table = 'planets';
    protected $primaryKey = 'planet_id';
    protected $allowedFields = [
        'name','system_id','position','time_to_jump_point','allegiance','map_background'
    ];

    public function getPlanetWithSystem(int $planetId): ?array
    {
        return $this->db->table('planets p')
            ->select('p.*, s.name as system_name')
            ->join('star_systems s', 's.system_id = p.system_id')
            ->where('p.planet_id', $planetId)
            ->get()
            ->getRowArray();
    }

    public function getAllWithSystems(): array
    {
        return $this->db->table('planets p')
            ->select('p.*, s.name as system_name')
            ->join('star_systems s', 's.system_id = p.system_id')
            ->get()
            ->getResultArray();
    }
}
