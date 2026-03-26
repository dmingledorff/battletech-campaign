<?php namespace App\Controllers;

use App\Models\LocationModel;
use App\Models\UnitModel;

class Locations extends BaseController
{
    public function show(int $id)
    {
        $locationModel = new LocationModel();
        $location = $locationModel->getLocation($id);

        if (!$location) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                "Location ID $id not found."
            );
        }

        $playerFactionId = $this->currentFaction['faction_id'] ?? null;
        $controlled      = (int)$location['controlled_by_faction_id'] === (int)$playerFactionId;

        $data = [
            'location'  => $location,
            'controlled' => $controlled,
        ];

        if ($controlled) {
            $unitModel = new UnitModel();
            $units     = $locationModel->getUnitsAtLocation($id);

            // Add unit chain and strength to each unit
            $allChildren = $unitModel->getAllChildren();
            $strengthMap = $unitModel->getStrengthAll();

            foreach ($units as &$unit) {
                $unit['unit_chain'] = $unitModel->getUnitChain($unit['unit_id']);
                $strength = $unitModel->rollupStrength(
                    $unit['unit_id'], $allChildren, $strengthMap
                );
                $unit['pct_personnel'] = $strength['pct_personnel'];
                $unit['pct_equipment'] = $strength['pct_equipment'];
                // Morale from personnel at this unit
                $personnel = $unitModel->getAllPersonnelRecursive(
                    $unit['unit_id'], $allChildren
                );
                $moraleValues = array_filter(
                    array_column($personnel, 'morale'), fn($m) => $m !== null
                );
                $unit['avg_morale'] = count($moraleValues) > 0
                    ? round(array_sum($moraleValues) / count($moraleValues), 1)
                    : null;
            }
            unset($unit);

            $data['buildings'] = $locationModel->getBuildings($id);
            $data['units']     = $units;
            $data['personnel'] = $locationModel->getPersonnelAtLocation($id);
            $data['equipment'] = $locationModel->getEquipmentAtLocation($id);
        }

        return $this->render('locations/show', $data);
    }
}