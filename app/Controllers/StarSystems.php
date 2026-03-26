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

        $systems = $systemModel->findAll();
        foreach ($systems as &$sys) {
            $sys['planets'] = $planetModel->where('system_id', $sys['system_id'])->findAll();
            foreach ($sys['planets'] as &$planet) {
                $planet['locations'] = $locationModel->where('planet_id', $planet['planet_id'])->findAll();
            }
        }

        $selectedSystem = $systemId ? $systemModel->find($systemId) : ($systems[0] ?? null);
        $selectedPlanet = $planetId ? $planetModel->find($planetId) : null;

        // Default to first planet of selected system
        if (!$selectedPlanet && $selectedSystem) {
            $firstPlanet = $planetModel->where('system_id', $selectedSystem['system_id'])
                ->orderBy('position')->first();
            $selectedPlanet = $firstPlanet ?: null;
        }

        if ($selectedPlanet) {
            // Get locations with faction data
            $selectedPlanet['locations'] = $locationModel->getLocationsWithFactions(
                $selectedPlanet['planet_id']
            );
        }

        $playerFactionId = $this->currentFaction['faction_id'] ?? null;

        $units = [];
        if ($selectedPlanet) {
            $units = $unitModel->select('units.unit_id, units.name, units.unit_type,
                                        l.coord_x, l.coord_y, l.location_id')
                ->join('locations l', 'l.location_id = units.location_id', 'left')
                ->where('l.planet_id', $selectedPlanet['planet_id'])
                ->where('units.faction_id', $playerFactionId)
                ->where('units.unit_id NOT IN (
                    SELECT DISTINCT parent_unit_id FROM units
                    WHERE parent_unit_id IS NOT NULL
                )', null, false)
                ->findAll();
            foreach ($units as &$unit) {
                $unit['unit_chain'] = $unitModel->getUnitChain($unit['unit_id']);
            }
            unset($unit);
        }

        return $this->render('starsystems/index', [
            'systems'          => $systems,
            'selectedSystem'   => $selectedSystem,
            'selectedPlanet'   => $selectedPlanet,
            'units'            => $units,
            'playerFactionId'  => $playerFactionId,
        ]);
    }
}
