<?php

namespace App\Controllers;

use App\Models\MissionModel;
use App\Models\LocationModel;
use App\Models\UnitModel;
use App\Models\PlanetModel;
use App\Models\PersonnelModel;
use App\Models\EventLogModel;

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
            'missionTypes'      => $this->getEnumValues('missions', 'mission_type')
        ]);
    }

    public function store()
    {
        $locationModel = new LocationModel();
        $factionId     = $this->currentFaction['faction_id'] ?? null;
        $originId      = (int)$this->request->getPost('origin_location_id');
        $destinationId = (int)$this->request->getPost('destination_location_id');
        $type          = $this->request->getPost('mission_type');
        $notes         = $this->request->getPost('notes');
        $unitIds       = $this->request->getPost('unit_ids') ?? [];

        // Validate units
        $unitModel = new UnitModel();
        foreach ($unitIds as $uid) {
            $u = $unitModel->find($uid);
            if (in_array($u['unit_type'], ['Company', 'Platoon']) && $u['status'] === 'Dispersed') {
                return redirect()->back()
                    ->with('error', "Cannot assign dispersed unit {$u['name']} to a mission — assign individual lances instead.");
            }
        }

        $destLocation = $locationModel->find($destinationId);
        $name = $this->request->getPost('name') ?: $type . ' ' . ($destLocation['name'] ?? 'Unknown');

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

        $units         = $missionModel->getMissionUnits($id);
        $unitIds       = array_column($units, 'unit_id');
        $eventLogModel = new EventLogModel();
        $log           = $eventLogModel->getForMission($id);

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

        $kmPerCoord      = (float)($this->gameState['km_per_coord_unit'] ?? 100);
        $speedEfficiency = (float)($this->gameState['speed_efficiency'] ?? 0.7);

        // For launched missions, convert stored distance
        $distanceKm = isset($mission['distance'])
            ? round((float)$mission['distance'] * $kmPerCoord, 1)
            : null;

        // For planning missions, calculate estimated distance and ETA
        $estimatedDistance    = null;
        $estimatedDistanceKm  = null;
        $estimatedTransitDays = null;
        $slowestSpeed         = null;

        if (
            $mission['status'] === 'Planning'
            && $mission['origin_location_id']
            && $mission['destination_location_id']
        ) {
            $estimatedDistance    = $missionModel->calculateDistance(
                (float)$mission['origin_x'],
                (float)$mission['origin_y'],
                (float)$mission['dest_x'],
                (float)$mission['dest_y']
            );
            $estimatedDistanceKm  = round($estimatedDistance * $kmPerCoord, 1);
            // Only calculate ETA if we have units assigned
            if (!empty($unitIds)) {
                $slowestSpeed         = $missionModel->getSlowestSpeed($unitIds);
                $estimatedTransitHours = $missionModel->calculateTransitHours($estimatedDistance, $slowestSpeed);
                $estimatedTransitDays  = (int)ceil($estimatedTransitHours / 24);
            }
        }

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
            'distanceKm'           => $distanceKm,
            'estimatedDistanceKm'  => $estimatedDistanceKm,
            'estimatedTransitDays' => $estimatedTransitDays,
            'estimatedTransitHours' => $estimatedTransitHours ?? null,
            'slowestSpeed'         => $slowestSpeed,
            'kmPerCoord'           => $kmPerCoord,
            'speedEfficiency'      => $speedEfficiency
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
        $gameHour     = (int)($this->gameState['current_hour'] ?? 0);
        $slowestSpeed = $missionModel->getSlowestSpeed($unitIds);
        $distance     = $missionModel->calculateDistance(
            (float)$mission['origin_x'],
            (float)$mission['origin_y'],
            (float)$mission['dest_x'],
            (float)$mission['dest_y']
        );

        $transitHours = $missionModel->calculateTransitHours($distance, $slowestSpeed);
        $transitDays  = (int)ceil($transitHours / 24);

        $etaDate = new \DateTime($gameDate);
        $etaDate->modify("+{$transitDays} days");

        $missionModel->update($id, [
            'status'          => 'In Transit',
            'launched_date'   => $gameDate,
            'eta_date'        => $etaDate->format('Y-m-d'),
            'distance'        => $distance,
            'transit_days'    => $transitDays,
            'transit_hours'   => $transitHours,
            'days_elapsed'    => 0,
            'hours_elapsed'   => 0,
            'slowest_speed'   => $slowestSpeed,
            'current_coord_x' => $mission['origin_x'],
            'current_coord_y' => $mission['origin_y'],
        ]);

        $unitModel = new UnitModel();
        $unitModel->setMissionStatus($unitIds, 'In Transit', $id);
        $unitModel->syncDispersedStatus();

        $kmPerCoord      = (float)($this->gameState['km_per_coord_unit'] ?? 100);
        $speedEfficiency = (float)($this->gameState['speed_efficiency'] ?? 0.7);

        $missionModel->logEvent(
            $id,
            $gameDate,
            'Launched',
            "Mission launched from {$mission['origin_name']} to {$mission['destination_name']}. " .
                "Distance: " . round($distance * $kmPerCoord, 1) . " km. " .
                "Slowest unit: " . round($slowestSpeed, 1) . " kph " .
                "(" . round($slowestSpeed * $speedEfficiency, 1) . " kph effective). " .
                "ETA: {$etaDate->format('Y-m-d')} ({$transitDays}d / {$transitHours}h)."
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

            $returnHours = $missionModel->calculateTransitHours(
                $remainingDistance,
                (float)$mission['slowest_speed']
            );
            $returnDays = (int)ceil($returnHours / 24);

            $etaDate = new \DateTime($gameDate);
            $etaDate->modify("+{$returnDays} days");

            $missionModel->update($id, [
                'status'                  => 'In Transit',
                'destination_location_id' => $mission['origin_location_id'],
                'origin_location_id'      => $mission['destination_location_id'],
                'transit_hours'           => $returnHours,
                'transit_days'            => $returnDays,
                'hours_elapsed'           => 0,
                'days_elapsed'            => 0,
                'eta_date'                => $etaDate->format('Y-m-d'),
                'notes'                   => ($mission['notes'] ?? '') . ' [RETURNING TO BASE]',
            ]);

            (new UnitModel())->syncDispersedStatus();

            $missionModel->logEvent(
                $id,
                $gameDate,
                'Aborted',
                "Mission aborted in transit. Units reversing course. " .
                    "New ETA at origin: {$etaDate->format('Y-m-d')} ({$returnDays}d / {$returnHours}h)."
            );
        }

        return redirect()->to("/missions/{$id}");
    }

    public function getUnitsAtLocation(int $locationId)
    {
        $missionId = (int)($this->request->getGet('mission_id') ?? 0);
        $includeHQ = (bool)($this->request->getGet('include_hq') ?? false);
        $factionId = $this->currentFaction['faction_id'] ?? null;

        $missionModel = new MissionModel();
        $unitModel    = new UnitModel();
        $units        = $missionModel->getAvailableUnits($locationId, $missionId, $includeHQ, $factionId);

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

    public function getUnitsForMap(int $missionId)
    {
        $missionModel = new MissionModel();
        $unitModel    = new UnitModel();
        $units        = $missionModel->getMissionUnits($missionId);

        foreach ($units as &$unit) {
            $unit['unit_chain'] = $unitModel->getUnitChain($unit['unit_id']);
        }
        unset($unit);

        return $this->response->setJSON($units);
    }
}
