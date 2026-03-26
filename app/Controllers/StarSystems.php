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

        if (!$selectedPlanet && $selectedSystem) {
            $selectedPlanet = $planetModel->where('system_id', $selectedSystem['system_id'])
                ->orderBy('position')->first() ?: null;
        }

        $playerFactionId = $this->currentFaction['faction_id'] ?? null;

        if ($selectedPlanet) {
            $selectedPlanet['locations'] = $locationModel->getLocationsWithFactions(
                $selectedPlanet['planet_id']
            );
        }

        // Garrisoned units at this planet (lowest level only)
        $garrisonedUnits = [];
        if ($selectedPlanet) {
            $garrisonedUnits = $unitModel->select('
                    units.unit_id, units.name, units.unit_type,
                    l.coord_x, l.coord_y, l.location_id')
                ->join('locations l', 'l.location_id = units.location_id', 'left')
                ->where('l.planet_id', $selectedPlanet['planet_id'])
                ->where('units.faction_id', $playerFactionId)
                ->where('units.status', 'Garrisoned')
                ->where('units.unit_id NOT IN (
                    SELECT DISTINCT parent_unit_id FROM units
                    WHERE parent_unit_id IS NOT NULL
                )', null, false)
                ->findAll();

            foreach ($garrisonedUnits as &$unit) {
                $unit['unit_chain'] = $unitModel->getUnitChain($unit['unit_id']);
            }
            unset($unit);
        }

        // In-transit missions on this planet
        $inTransitMissions = [];
        if ($selectedPlanet) {
            $db = \Config\Database::connect();
            $inTransitMissions = $db->table('missions m')
                ->select('
                    m.mission_id, m.name AS mission_name, m.mission_type,
                    m.current_coord_x, m.current_coord_y,
                    m.days_elapsed, m.transit_days, m.eta_date,
                    ol.name AS origin_name,
                    dl.name AS destination_name,
                    dl.coord_x AS dest_x, dl.coord_y AS dest_y
                ')
                ->join('locations ol', 'ol.location_id = m.origin_location_id')
                ->join('locations dl', 'dl.location_id = m.destination_location_id')
                ->where('m.faction_id', $playerFactionId)
                ->where('m.status', 'In Transit')
                ->groupStart()
                    ->where('ol.planet_id', $selectedPlanet['planet_id'])
                    ->orWhere('dl.planet_id', $selectedPlanet['planet_id'])
                ->groupEnd()
                ->get()
                ->getResultArray();
        }

        return $this->render('starsystems/index', [
            'systems'           => $systems,
            'selectedSystem'    => $selectedSystem,
            'selectedPlanet'    => $selectedPlanet,
            'units'             => $garrisonedUnits,
            'inTransitMissions' => $inTransitMissions,
            'playerFactionId'   => $playerFactionId,
        ]);
    }
}
