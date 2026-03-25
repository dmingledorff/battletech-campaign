<?php namespace App\Controllers;

use App\Models\UnitModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $unitModel = new UnitModel();

        $summary          = $unitModel->getSummary();
        $childrenByParent = $unitModel->getAllChildren();

        $summaryById = [];
        foreach ($summary as $s) {
            $summaryById[$s['unit_id']] = $s;
        }

        $this->rollupTotals(1, $childrenByParent, $summaryById);

        $totals = [
            'units'      => count($summary),
            'personnel'  => $summaryById[1]['rolled_personnel'] ?? 0,
            'equipment'  => $summaryById[1]['rolled_equipment'] ?? 0,
            'supply_req' => $summaryById[1]['rolled_supply'] ?? 0,
        ];

        $topUnits  = $unitModel->where('parent_unit_id IS NULL', null, false)->findAll();
        $db        = \Config\Database::connect();
        $planets   = $db->table('planets')->orderBy('name')->get()->getResultArray();
        $locations = $db->table('locations')->orderBy('name')->get()->getResultArray();

        return $this->render('dashboard/index', [
            'summary'      => $summaryById,
            'children'     => $childrenByParent,
            'totals'       => $totals,
            'rootId'       => 1,
            'topUnits'     => $topUnits,
            'planets'      => $planets,
            'locations'    => $locations,
            'savedFilters' => session()->get('roster_filters') ?? [],
        ]);
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
