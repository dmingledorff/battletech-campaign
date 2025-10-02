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

        if (!$unit) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Unit not found: {$id}");
        }

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

        $personnelStrength = $unitModel->getPersonnelStrengthRecursive($id, $children);
        $equipmentStrength = $unitModel->getEquipmentStrengthRecursive($id, $children);

        return view('layout/header')
            . view('units/show', [
                'unit'       => $unit,
                'personnel'  => $personnel,
                'equipment'  => $equipment,
                'breadcrumb' => $breadcrumb,
                'personnelStrength' => $personnelStrength,
                'equipmentStrength' => $equipmentStrength,
                'children'   => $children
            ])
            . view('layout/footer');
    }
}
