<?php namespace App\Controllers;

use App\Models\StarSystemModel;
use App\Models\PlanetModel;
use App\Models\LocationModel;
use App\Models\UnitModel;

class StarSystems extends BaseController
{
    public function index($systemId = null, $planetId = null)
    {
        $systemModel   = new StarSystemModel();
        $planetModel   = new PlanetModel();
        $locationModel = new LocationModel();
        $unitModel     = new UnitModel();

        // Load all star systems with planets + locations
        $systems = $systemModel->findAll();
        foreach ($systems as &$sys) {
            $sys['planets'] = $planetModel->where('system_id', $sys['system_id'])->findAll();
            foreach ($sys['planets'] as &$planet) {
                $planet['locations'] = $locationModel->where('planet_id', $planet['planet_id'])->findAll();
            }
        }

        // Selected system/planet
        $selectedSystem = $systemId ? $systemModel->find($systemId) : $systems[0];
        $selectedPlanet = $planetId ? $planetModel->find($planetId) : null;

        // Add locations to selected planet (needed for the map)
        if ($selectedPlanet) {
            $selectedPlanet['locations'] = $locationModel
                ->where('planet_id', $selectedPlanet['planet_id'])
                ->findAll();
        }

        // Units joined with their location coords
        $units = [];
        if ($selectedPlanet) {
            $units = $unitModel->select('units.unit_id, units.name, units.unit_type, l.coord_x, l.coord_y')
                ->join('locations l', 'l.location_id = units.location_id', 'left')
                ->where('l.planet_id', $selectedPlanet['planet_id'])
                ->findAll();
        }

        return $this->render('starsystems/index', [
            'systems'         => $systems,
            'selectedSystem'  => $selectedSystem,
            'selectedPlanet'  => $selectedPlanet,
            'units'           => $units,
        ]);
    }
}
