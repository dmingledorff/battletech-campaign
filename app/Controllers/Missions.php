<?php namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\LocationModel;
use App\Models\UnitModel;
use App\Models\GameStateModel;
use App\Models\PlanetModel;

class Missions extends BaseController
{
    public function index()
    {
        $missionModel = new MissionModel();
        $factionId    = $this->currentFaction['faction_id'] ?? null;

        $planning   = $missionModel->getMissionsByStatus($factionId, 'Planning');
        $inTransit  = $missionModel->getMissionsByStatus($factionId, 'In Transit');
        $arrived    = $missionModel->getMissionsByStatus($factionId, 'Arrived');

        return $this->render('missions/index', [
            'planning'  => $planning,
            'inTransit' => $inTransit,
            'arrived'   => $arrived,
        ]);
    }

    public function create()
    {
        $locationModel = new LocationModel();
        $planetModel   = new PlanetModel();
        $factionId     = $this->currentFaction['faction_id'] ?? null;

        $planets          = $planetModel->findAll();
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
        $factionId = $this->currentFaction['faction_id'] ?? null;
        $gameState = new GameStateModel();
        $gameDate  = $gameState->getProperty('current_date');

        $originId      = (int)$this->request->getPost('origin_location_id');
        $destinationId = (int)$this->request->getPost('destination_location_id');
        $name          = $this->request->getPost('name');
        $type          = $this->request->getPost('mission_type');
        $notes         = $this->request->getPost('notes');
        $unitIds       = $this->request->getPost('unit_ids') ?? [];

        $missionModel = new MissionModel();

        $missionId = $missionModel->createMission([
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

        $units    = $missionModel->getMissionUnits($id);
        $log      = $missionModel->getLog($id);

        // Get strength for each unit
        $unitModel   = new UnitModel();
        $allChildren = $unitModel->getAllChildren();
        $strengthMap = $unitModel->getStrengthAll();

        foreach ($units as &$unit) {
            $strength              = $unitModel->rollupStrength($unit['unit_id'], $allChildren, $strengthMap);
            $unit['pct_personnel'] = $strength['pct_personnel'];
            $unit['pct_equipment'] = $strength['pct_equipment'];
        }
        unset($unit);

        // For planning missions — get available units and locations for editing
        $availableUnits    = [];
        $friendlyLocations = [];
        $allLocations      = [];

        if ($mission['status'] === 'Planning') {
            $locationModel     = new LocationModel();
            $factionId         = $this->currentFaction['faction_id'] ?? null;
            $availableUnits    = $missionModel->getAvailableUnits(
                $mission['origin_location_id'], $id
            );
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

        $originId      = (int)$this->request->getPost('origin_location_id');
        $destinationId = (int)$this->request->getPost('destination_location_id');
        $unitIds       = $this->request->getPost('unit_ids') ?? [];

        $missionModel->updateMission($id, [
            'name'                    => $this->request->getPost('name'),
            'mission_type'            => $this->request->getPost('mission_type'),
            'origin_location_id'      => $originId,
            'destination_location_id' => $destinationId,
            'notes'                   => $this->request->getPost('notes'),
        ], $unitIds);

        return redirect()->to("/missions/{$id}");
    }

    public function launch(int $id)
    {
        $missionModel = new MissionModel();
        $mission      = $missionModel->getMission($id);

        if (!$mission || $mission['status'] !== 'Planning') {
            return redirect()->to("/missions/{$id}");
        }

        $gameState = new GameStateModel();
        $gameDate  = $gameState->getProperty('current_date');

        // Get unit IDs
        $unitIds = array_column($missionModel->getMissionUnits($id), 'unit_id');

        if (empty($unitIds)) {
            // Can't launch without units
            return redirect()->to("/missions/{$id}")->with('error', 'Cannot launch a mission with no units.');
        }

        // Calculate slowest speed
        $slowestSpeed = $missionModel->getSlowestSpeed($unitIds);

        // Calculate distance
        $distance = $missionModel->calculateDistance(
            (float)$mission['origin_x'],
            (float)$mission['origin_y'],
            (float)$mission['dest_x'],
            (float)$mission['dest_y']
        );

        // Calculate transit days
        $transitDays = $missionModel->calculateTransitDays($distance, $slowestSpeed);

        // Calculate ETA
        $etaDate = new \DateTime($gameDate);
        $etaDate->modify("+{$transitDays} days");

        // Update mission to In Transit
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

        // Log launch
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

        $gameState = new GameStateModel();
        $gameDate  = $gameState->getProperty('current_date');

        if ($mission['status'] === 'Planning') {
            $missionModel->update($id, ['status' => 'Aborted']);
            $missionModel->logEvent($id, $gameDate, 'Aborted', 'Mission aborted during planning.');

        } elseif ($mission['status'] === 'In Transit') {
            // Reverse course — current position becomes new origin
            // Original origin becomes new destination
            $currentX    = (float)$mission['current_coord_x'];
            $currentY    = (float)$mission['current_coord_y'];
            $returnDestX = (float)$mission['origin_x'];
            $returnDestY = (float)$mission['origin_y'];

            $remainingDistance = $missionModel->calculateDistance(
                $currentX, $currentY, $returnDestX, $returnDestY
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
        $units        = $missionModel->getAvailableUnits($locationId, $missionId);
        return $this->response->setJSON($units);
    }

    public function getLocations()
    {
        $locationModel = new LocationModel();
        $factionId     = $this->currentFaction['faction_id'] ?? null;
        $locations     = $locationModel->getAllLocationsWithFactionInfo();
        return $this->response->setJSON($locations);
    }
}