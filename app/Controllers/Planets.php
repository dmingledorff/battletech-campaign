<?php namespace App\Controllers;

use App\Models\PlanetModel;
use App\Models\LocationModel;

class Planets extends BaseController
{
    public function index()
    {
        $planetModel = new PlanetModel();
        $planets = $planetModel->getAllWithSystems();

        return $this->render('planets/index', ['planets' => $planets]);
    }

    public function show($id)
    {
        $planetModel = new PlanetModel();
        $locationModel = new LocationModel();

        $planet = $planetModel->getPlanetWithSystem($id);
        if (!$planet) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Planet not found: {$id}");
        }

        $locations = $locationModel->getByPlanet($id);

        return $this->render('planets/show', [
            'planet'    => $planet,
            'locations' => $locations
        ]);
    }
}
