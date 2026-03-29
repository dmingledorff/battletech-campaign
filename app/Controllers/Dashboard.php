<?php

namespace App\Controllers;

use App\Models\UnitModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $unitModel  = new UnitModel();
        $factionId  = $this->currentFaction['faction_id'] ?? null;

        $topUnits = $unitModel->getTopLevelByFaction($factionId);

        // Totals rolled up across all faction units
        $summary          = $unitModel->getSummaryByFaction($factionId);
        $childrenByParent = $unitModel->getAllChildrenByFaction($factionId);

        $summaryById = [];
        foreach ($summary as $s) {
            $summaryById[$s['unit_id']] = $s;
        }

        // Roll up from each top-level unit
        $totalPersonnel = 0;
        $totalEquipment = 0;
        $totalSupply    = 0;
        foreach ($topUnits as $u) {
            $rolled = $this->rollupTotals($u['unit_id'], $childrenByParent, $summaryById);
            $totalPersonnel += $rolled['personnel'];
            $totalEquipment += $rolled['equipment'];
            $totalSupply    += $rolled['supply'];
        }

        $totals = [
            'units'      => count($summary),
            'personnel'  => $totalPersonnel,
            'equipment'  => $totalEquipment,
            'supply_req' => $totalSupply,
        ];

        $db        = \Config\Database::connect();
        $planets   = $db->table('planets')->orderBy('name')->get()->getResultArray();
        $locations = $db->table('locations')->orderBy('name')->get()->getResultArray();

        $eventLogModel = new \App\Models\EventLogModel();
        $eventLog      = $eventLogModel->getForDashboard(
            $factionId,
            $this->gameState['current_date'] ?? '3025-01-01',
            7
        );

        return $this->render('dashboard/index', [
            'summary'      => $summaryById,
            'children'     => $childrenByParent,
            'totals'       => $totals,
            'topUnits'     => $topUnits,
            'planets'      => $planets,
            'locations'    => $locations,
            'mosTypes'     => $this->getEnumValues('personnel', 'mos'),
            'savedFilters' => session()->get('roster_filters') ?? [],
            'eventLog'     => $eventLog
        ]);
    }

    public function unitChildren(int $unitId)
    {
        $unitModel = new UnitModel();
        $factionId = $this->currentFaction['faction_id'] ?? null;

        $children  = $unitModel
            ->select('units.*, locations.name AS location_name')
            ->join('locations', 'locations.location_id = units.location_id', 'left')
            ->where('parent_unit_id', $unitId)
            ->where('faction_id', $factionId)
            ->findAll();

        $summary = $unitModel->getSummaryByFaction($factionId);
        $summaryById = array_column($summary, null, 'unit_id');

        $childrenByParent = $unitModel->getAllChildrenByFaction($factionId);

        $rows = [];
        foreach ($children as $c) {
            $rolled = $this->rollupTotals($c['unit_id'], $childrenByParent, $summaryById);
            $hasChildren = isset($childrenByParent[$c['unit_id']]);
            $rows[] = [
                'unit_id'    => $c['unit_id'],
                'name'       => $c['name'],
                'unit_type'  => $c['unit_type'],
                'role'       => $c['role'],
                'status'     => $c['status'] ?? 'Garrisoned',
                'personnel'  => $rolled['personnel'],
                'equipment'  => $rolled['equipment'],
                'supply'     => round($rolled['supply'], 2),
                'hasChildren' => $hasChildren,
                'location_name' => $c['location_name'] ?? null
            ];
        }

        return $this->response->setJSON($rows);
    }

    private function rollupTotals($unitId, $children, &$summaryById)
    {
        $totals = [
            'personnel' => $summaryById[$unitId]['personnel_count'] ?? 0,
            'equipment' => $summaryById[$unitId]['equipment_count'] ?? 0,
            'supply'    => $summaryById[$unitId]['required_supply'] ?? 0,
        ];

        if (isset($children[$unitId])) {
            foreach ($children[$unitId] as $child) {
                $childTotals = $this->rollupTotals($child['unit_id'], $children, $summaryById);
                $totals['personnel'] += $childTotals['personnel'];
                $totals['equipment'] += $childTotals['equipment'];
                $totals['supply']    += $childTotals['supply'];
            }
        }

        $summaryById[$unitId]['rolled_personnel'] = $totals['personnel'];
        $summaryById[$unitId]['rolled_equipment'] = $totals['equipment'];
        $summaryById[$unitId]['rolled_supply']    = $totals['supply'];

        return $totals;
    }
}
