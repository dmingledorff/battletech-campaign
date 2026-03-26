<?php namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\LocationModel;
use App\Models\UnitModel;
use App\Models\PlanetModel;
use App\Models\PersonnelModel;

class Missions extends BaseController
{
    public function index()
    {
        $missionModel = new MissionModel();
        $factionId    = $this->currentFaction['faction_id'] ?? null;

        $planning  = $missionModel->getMissionsByStatus($factionId, 'Planning');
        $inTransit = $missionModel->getMissionsByStatus($factionId, 'In Transit');
        $arrived   = $missionModel->getMissionsByStatus($factionId, 'Arrived');

        return $this->render('missions/index', [
            'planning'  => $planning,
            'inTransit' => $inTransit,
            'arrived'   => $arrived,
        ]);
    }

    public function create()
    {
        $locationModel     = new LocationModel();
        $planetModel       = new PlanetModel();
        $factionId         = $this->currentFaction['faction_id'] ?? null;
        $planets           = $planetModel->findAll();
        $friendlyLocations = $locationModel->getFriendlyLocations($factionId);
        $allLocations      = $locationModel->getAllLocationsWithFactionInfo();

        return $this->render('missions/create', [
            'planets'           => $planets,
            'friendlyLocations' => $friendlyLocations,
            'allLocations'      => $allLocations,
        ]);
    }

    public function store()
    {
        $factionId     = $this->currentFaction['faction_id'] ?? null;
        $originId      = (int)$this->request->getPost('origin_location_id');
        $destinationId = (int)$this->request->getPost('destination_location_id');
        $name          = $this->request->getPost('name');
        $type          = $this->request->getPost('mission_type');
        $notes         = $this->request->getPost('notes');
        $unitIds       = $this->request->getPost('unit_ids') ?? [];

        $missionModel = new MissionModel();
        $missionId    = $missionModel->createMission([
            'name'                    => $name,
            'mission_type'            => $type,
            'status'                  => 'Planning',
            'origin_location_id'      => $originId,
            'destination_location_id' => $destinationId,
            'faction_id'              => $factionId,
            'notes'                   => $notes,
        ], $unitIds);

        return redirect()->to("/missions/{$missionId}");
    }

    public function show(int $id)
    {
        $missionModel = new MissionModel();
        $mission      = $missionModel->getMission($id);

        if (!$mission) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Mission $id not found.");
        }

        $units = $missionModel->getMissionUnits($id);
        $log   = $missionModel->getLog($id);

        // Get strength for each unit
        $unitModel   = new UnitModel();
        $allChildren = $unitModel->getAllChildren();
        $strengthMap = $unitModel->getStrengthAll();

        foreach ($units as &$unit) {
            $unit['unit_chain'] = $unitModel->getUnitChain($unit['unit_id']);
            $strength              = $unitModel->rollupStrength($unit['unit_id'], $allChildren, $strengthMap);
            $unit['pct_personnel'] = $strength['pct_personnel'];
            $unit['pct_equipment'] = $strength['pct_equipment'];

            $personnel = $unitModel->getAllPersonnelRecursive($unit['unit_id'], $allChildren);
            $moraleValues = array_filter(array_column($personnel, 'morale'), fn($m) => $m !== null);
            $unit['avg_morale'] = count($moraleValues) > 0
                ? round(array_sum($moraleValues) / count($moraleValues), 1)
                : null;
        }
        unset($unit);

        $availableUnits    = [];
        $friendlyLocations = [];
        $allLocations      = [];

        if ($mission['status'] === 'Planning') {
            $locationModel     = new LocationModel();
            $factionId         = $this->currentFaction['faction_id'] ?? null;
            $availableUnits    = $missionModel->getAvailableUnits($mission['origin_location_id'], $id);
            $friendlyLocations = $locationModel->getFriendlyLocations($factionId);
            $allLocations      = $locationModel->getAllLocationsWithFactionInfo();
        }

        return $this->render('missions/show', [
            'mission'           => $mission,
            'units'             => $units,
            'log'               => $log,
            'availableUnits'    => $availableUnits,
            'friendlyLocations' => $friendlyLocations,
            'allLocations'      => $allLocations,
        ]);
    }

    public function update(int $id)
    {
        $missionModel = new MissionModel();
        $mission      = $missionModel->find($id);

        if (!$mission || $mission['status'] !== 'Planning') {
            return redirect()->to("/missions/{$id}");
        }

        $missionModel->updateMission($id, [
            'name'                    => $this->request->getPost('name'),
            'mission_type'            => $this->request->getPost('mission_type'),
            'origin_location_id'      => (int)$this->request->getPost('origin_location_id'),
            'destination_location_id' => (int)$this->request->getPost('destination_location_id'),
            'notes'                   => $this->request->getPost('notes'),
        ], $this->request->getPost('unit_ids') ?? []);

        return redirect()->to("/missions/{$id}");
    }

    public function launch(int $id)
    {
        $missionModel = new MissionModel();
        $mission      = $missionModel->getMission($id);

        if (!$mission || $mission['status'] !== 'Planning') {
            return redirect()->to("/missions/{$id}");
        }

        $unitIds = array_column($missionModel->getMissionUnits($id), 'unit_id');

        if (empty($unitIds)) {
            return redirect()->to("/missions/{$id}")->with('error', 'Cannot launch a mission with no units.');
        }

        $gameDate     = $this->gameState['current_date'] ?? '3025-01-01';
        $slowestSpeed = $missionModel->getSlowestSpeed($unitIds);
        $distance     = $missionModel->calculateDistance(
            (float)$mission['origin_x'],
            (float)$mission['origin_y'],
            (float)$mission['dest_x'],
            (float)$mission['dest_y']
        );
        $transitDays = $missionModel->calculateTransitDays($distance, $slowestSpeed);
        $etaDate     = new \DateTime($gameDate);
        $etaDate->modify("+{$transitDays} days");

        $missionModel->update($id, [
            'status'          => 'In Transit',
            'launched_date'   => $gameDate,
            'eta_date'        => $etaDate->format('Y-m-d'),
            'distance'        => $distance,
            'transit_days'    => $transitDays,
            'days_elapsed'    => 0,
            'slowest_speed'   => $slowestSpeed,
            'current_coord_x' => $mission['origin_x'],
            'current_coord_y' => $mission['origin_y'],
        ]);

        $unitModel = new UnitModel();
        $unitModel->setMissionStatus($unitIds, 'In Transit', $id);

        $missionModel->logEvent(
            $id, $gameDate, 'Launched',
            "Mission launched from {$mission['origin_name']} to {$mission['destination_name']}. " .
            "Distance: " . round($distance, 2) . " units. " .
            "Slowest unit: " . round($slowestSpeed, 1) . " kph. " .
            "ETA: {$etaDate->format('Y-m-d')} ({$transitDays} days)."
        );

        return redirect()->to("/missions/{$id}");
    }

    public function abort(int $id)
    {
        $missionModel = new MissionModel();
        $mission      = $missionModel->getMission($id);

        if (!$mission) {
            return redirect()->to('/missions');
        }

        $gameDate = $this->gameState['current_date'] ?? '3025-01-01';

        if ($mission['status'] === 'Planning') {
            $missionModel->update($id, ['status' => 'Aborted']);
            $missionModel->logEvent($id, $gameDate, 'Aborted', 'Mission aborted during planning.');

        } elseif ($mission['status'] === 'In Transit') {
            $remainingDistance = $missionModel->calculateDistance(
                (float)$mission['current_coord_x'],
                (float)$mission['current_coord_y'],
                (float)$mission['origin_x'],
                (float)$mission['origin_y']
            );
            $returnDays = $missionModel->calculateTransitDays(
                $remainingDistance, (float)$mission['slowest_speed']
            );
            $etaDate = new \DateTime($gameDate);
            $etaDate->modify("+{$returnDays} days");

            $missionModel->update($id, [
                'status'                  => 'In Transit',
                'destination_location_id' => $mission['origin_location_id'],
                'origin_location_id'      => $mission['destination_location_id'],
                'transit_days'            => $returnDays,
                'days_elapsed'            => 0,
                'eta_date'                => $etaDate->format('Y-m-d'),
                'notes'                   => ($mission['notes'] ?? '') . ' [RETURNING TO BASE]',
            ]);

            $missionModel->logEvent(
                $id, $gameDate, 'Aborted',
                "Mission aborted in transit. Units reversing course. " .
                "New ETA at origin: {$etaDate->format('Y-m-d')} ({$returnDays} days)."
            );
        }

        return redirect()->to("/missions/{$id}");
    }

    public function getUnitsAtLocation(int $locationId)
    {
        $missionId    = (int)($this->request->getGet('mission_id') ?? 0);
        $missionModel = new MissionModel();
        $unitModel    = new UnitModel();
        $units        = $missionModel->getAvailableUnits($locationId, $missionId);

        foreach ($units as &$unit) {
            $unit['unit_chain'] = $unitModel->getUnitChain($unit['unit_id']);
        }
        unset($unit);

        return $this->response->setJSON($units);
    }

    public function getLocations()
    {
        $locationModel = new LocationModel();
        $locations     = $locationModel->getAllLocationsWithFactionInfo();
        return $this->response->setJSON($locations);
    }

    public function getUnitRoster(int $unitId)
    {
        $personnelModel = new PersonnelModel();
        return $this->response->setJSON(
            $personnelModel->getUnitRosterForMission($unitId)
        );
    }
}