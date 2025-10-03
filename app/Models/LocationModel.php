<?php namespace App\Models;

use CodeIgniter\Model;

class LocationModel extends Model
{
    protected $table = 'locations';
    protected $primaryKey = 'location_id';
    protected $allowedFields = ['name','type','terrain','planet_id','coord_x','coord_y'];

    public function getByPlanet(int $planetId): array
    {
        return $this->where('planet_id', $planetId)->findAll();
    }
}
