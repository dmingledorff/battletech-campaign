<?php namespace App\Controllers;

use App\Models\UnitModel;

class Units extends BaseController
{
    public function show($id)
    {
        $unitModel = new UnitModel();
        $children  = $unitModel->getAllChildren();

        // Base unit
        $unit = $unitModel->getUnit($id);

        // Add required supply roll-up
        $unit['required_supply'] = 0;
        $equipment = $unitModel->getAllEquipmentRecursive($id, $children);
        foreach ($equipment as $e) {
            $unit['required_supply'] += $e['supply_consumption'] ?? 0;
        }

        // Personnel + Equipment (recursive)
        $personnel = $unitModel->getAllPersonnelRecursive($id, $children);

        // Morale roll-up
        $unit['avg_morale'] = $unitModel->getUnitMorale($id, $children);

        // Breadcrumb
        $breadcrumb = $unitModel->getBreadcrumb($id);

        return view('layout/header')
            . view('units/show', [
                'unit'       => $unit,
                'personnel'  => $personnel,
                'equipment'  => $equipment,
                'breadcrumb' => $breadcrumb,
                'children'   => $children
            ])
            . view('layout/footer');
    }
}
