<?php namespace App\Controllers;

use App\Models\UnitModel;

class Units extends BaseController
{
// app/Controllers/Units.php

    public function show($id) {
        $unitModel = new \App\Models\UnitModel();
        $children  = $unitModel->getAllChildren();

        $unit = $unitModel->getUnit($id);
        if (!$unit) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Unit not found: {$id}");
        }

        // roll-ups and recursive lists (unchanged)
        $personnel = $unitModel->getAllPersonnelRecursive($id, $children);
        $equipment = $unitModel->getAllEquipmentRecursive($id, $children);
        $unit['avg_morale'] = $unitModel->getUnitMorale($id, $children);
        $breadcrumb = $unitModel->getBreadcrumb($id);
        $personnelStrength = $unitModel->getPersonnelStrengthRecursive($id, $children);
        $equipmentStrength = $unitModel->getEquipmentStrengthRecursive($id, $children);

        // direct personnel/equipment for the modal (IMPORTANT)
        $assignedDirectPersonnel = $unitModel->getDirectPersonnel($id);
        $directEquipment = $unitModel->getDirectEquipment($id);

        // available pools by faction
        $factionId = $this->currentFaction['faction_id'] ?? null;
        $availablePersonnel = $factionId ? $unitModel->getAvailablePersonnel((int)$factionId) : [];
        $availableEquipment = $factionId ? $unitModel->getAvailableEquipment((int)$factionId) : [];

        // required supply calc (unchanged)
        $unit['required_supply'] = 0;
        foreach ($equipment as $e) {
            $unit['required_supply'] += $e['supply_consumption'] ?? 0;
        }

        return $this->render('units/show', [
            'unit'                    => $unit,
            'personnel'               => $personnel,               // recursive for the table
            'equipment'               => $equipment,               // recursive for the table
            'breadcrumb'              => $breadcrumb,
            'personnelStrength'       => $personnelStrength,
            'equipmentStrength'       => $equipmentStrength,
            'children'                => $children,
            'availablePersonnel'      => $availablePersonnel,
            'availableEquipment'      => $availableEquipment,
            'assignedDirectPersonnel' => $assignedDirectPersonnel,
            'directEquipment'         => $directEquipment,
        ]);
    }

    public function managePersonnel($unitId)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $ids     = $payload['personnel_ids'] ?? [];

        // use the current game date
        $gs   = new \App\Models\GameStateModel();
        $date = $gs->getProperty('current_date') ?? '3025-01-01';

        $unitModel = new \App\Models\UnitModel();
        $ok = $unitModel->syncPersonnelAssignments((int)$unitId, $ids, $date);

        return $this->response->setJSON(['success' => $ok]);
    }

    public function manageEquipment($unitId)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $ids     = $payload['equipment_ids'] ?? [];

        $gs   = new \App\Models\GameStateModel();
        $date = $gs->getProperty('current_date') ?? '3025-01-01';

        $unitModel = new \App\Models\UnitModel();
        $ok = $unitModel->syncEquipmentAssignments((int)$unitId, $ids, $date);

        return $this->response->setJSON(['success' => $ok]);
    }

    public function assignPersonnel($unitId)
    {
        $personnelId = $this->request->getPost('personnel_id');
        if ($personnelId) {
            $db = \Config\Database::connect();
            $builder = $db->table('personnel_assignments');
            $builder->insert([
                'personnel_id' => $personnelId,
                'unit_id' => $unitId,
                'date_assigned' => date('Y-m-d'),
            ]);
        }
        return redirect()->to("/units/$unitId");
    }

    public function unassignPersonnel($unitId)
    {
        $personnelId = $this->request->getPost('personnel_id');
        if ($personnelId) {
            $db = \Config\Database::connect();
            $builder = $db->table('personnel_assignments');
            $builder->where('personnel_id', $personnelId)
                    ->where('unit_id', $unitId)
                    ->update(['date_released' => date('Y-m-d')]);
        }
        return redirect()->to("/units/$unitId");
    }

    public function assignEquipment($unitId)
    {
        $equipmentId = $this->request->getPost('equipment_id');
        if ($equipmentId) {
            $db = \Config\Database::connect();
            $builder = $db->table('equipment');
            $builder->where('equipment_id', $equipmentId)
                    ->update(['assigned_unit_id' => $unitId]);
        }
        return redirect()->to("/units/$unitId");
    }

    public function unassignEquipment($unitId)
    {
        $equipmentId = $this->request->getPost('equipment_id');
        if ($equipmentId) {
            $db = \Config\Database::connect();
            $builder = $db->table('equipment');
            $builder->where('equipment_id', $equipmentId)
                    ->update(['assigned_unit_id' => null]);
        }
        return redirect()->to("/units/$unitId");
    }

    public function setCommander($unitId)
    {
        $personnelId = $this->request->getPost('personnel_id');
        if ($personnelId) {
            $unitModel = new \App\Models\UnitModel();
            $unitModel->update($unitId, ['commander_id' => $personnelId]);
        }
        return redirect()->to("/units/$unitId");
    }

}
