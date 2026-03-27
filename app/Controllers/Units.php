<?php

namespace App\Controllers;

use App\Models\UnitModel;

class Units extends BaseController
{
    // app/Controllers/Units.php

    public function show($id)
    {
        $unitModel = new \App\Models\UnitModel();
        $children  = $unitModel->getAllChildren();

        $unit = $unitModel->getUnit($id);
        if (!$unit) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Unit not found: {$id}");
        }

        // roll-ups and recursive lists (unchanged)
        $personnel = $unitModel->getAllPersonnelRecursive($id, $children);
        $equipment = $unitModel->getAllEquipmentRecursive($id, $children);
        //$unit['avg_morale'] = $unitModel->getUnitMorale($id, $children);
        $moraleValues = array_filter(array_column($personnel, 'morale'), fn($m) => $m !== null);
        $unit['avg_morale'] = count($moraleValues) > 0
            ? array_sum($moraleValues) / count($moraleValues)
            : null;
        $breadcrumb = $unitModel->getBreadcrumb($id);
        $strengthMap = $unitModel->getStrengthAll();
        $strength    = $unitModel->rollupStrength($id, $children, $strengthMap);

        // direct personnel/equipment for the modal (IMPORTANT)
        $assignedDirectPersonnel = $unitModel->getDirectPersonnel($id);
        $directEquipment = $unitModel->getDirectEquipment($id);

        // available pools by faction
        $factionId = $this->currentFaction['faction_id'] ?? null;
        $availablePersonnel = $factionId ? $unitModel->getAvailablePersonnel((int)$factionId, (int)$id) : [];
        $availableEquipment = $factionId ? $unitModel->getAvailableEquipment((int)$factionId) : [];

        // required supply calc (unchanged)
        $unit['required_supply'] = 0;
        foreach ($equipment as $e) {
            $unit['required_supply'] += $e['supply_consumption'] ?? 0;
        }

        $childIds = [];
        if (!empty($children[$unit['unit_id']])) {
            $childIds = array_column($children[$unit['unit_id']], 'unit_id');
        }

        $speedMap        = $unitModel->getMinSpeedBatch($childIds);
        $unit['speed']   = $unitModel->getMinSpeedRecursive($unit['unit_id']);

        $subStrengths = [];
        if (!empty($children[$unit['unit_id']])) {
            foreach ($children[$unit['unit_id']] as $sub) {
                $subStrengths[$sub['unit_id']] = $unitModel->rollupStrength(
                    $sub['unit_id'],
                    $children,
                    $strengthMap
                );
            }
        }

        return $this->render('units/show', [
            'unit'                    => $unit,
            'personnel'               => $personnel,               // recursive for the table
            'equipment'               => $equipment,               // recursive for the table
            'breadcrumb'              => $breadcrumb,
            'strength'                => $strength,
            'children'                => $children,
            'availablePersonnel'      => $availablePersonnel,
            'availableEquipment'      => $availableEquipment,
            'assignedDirectPersonnel' => $assignedDirectPersonnel,
            'directEquipment'         => $directEquipment,
            'speedMap'                => $speedMap,
            'subStrengths' => $subStrengths,
        ]);
    }

    public function index()
    {
        $unitModel  = new UnitModel();
        $factionId  = $this->currentFaction['faction_id'] ?? null;
        $showDeactivated = (bool)$this->request->getGet('deactivated');

        $units = $unitModel->getUnitsByFaction($factionId, $showDeactivated);

        // Build parent map
        $byParent = [];
        foreach ($units as $u) {
            $byParent[$u['parent_unit_id']][] = $u;
        }

        // Batch speed lookup
        $unitIds   = array_column($units, 'unit_id');
        $speedMap  = $unitModel->getMaxSpeedBatch($unitIds);

        return $this->render('units/index', [
            'byParent'        => $byParent,
            'speedMap'        => $speedMap,
            'showDeactivated' => $showDeactivated,
            'factionId'       => $factionId,
            'unitTypes'       => $this->getEnumValues('units', 'unit_type'),
            'roles'           => $this->getEnumValues('units', 'role'),
            'statuses'        => $this->getEnumValues('units', 'status')
        ]);
    }

    public function managePersonnel(int $unitId)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $ids     = $payload['personnel_ids'] ?? [];
        $date    = $this->gameState['current_date'] ?? '3025-01-01';

        $unitModel = new \App\Models\UnitModel();
        $ok        = $unitModel->syncPersonnelAssignments((int)$unitId, $ids, $date);

        return $this->response->setJSON(['success' => $ok]);
    }

    public function manageEquipment(int $unitId)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $ids     = $payload['equipment_ids'] ?? [];
        $date    = $this->gameState['current_date'] ?? '3025-01-01';

        $unitModel = new \App\Models\UnitModel();
        $ok        = $unitModel->syncEquipmentAssignments((int)$unitId, $ids, $date);

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

    public function assignCommander($unitId)
    {
        $data = $this->request->getJSON(true);
        $personnelId = $data['personnel_id'] ?? null;

        if (!$personnelId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No personnel selected']);
        }

        $db = \Config\Database::connect();
        $db->table('units')
            ->where('unit_id', $unitId)
            ->update(['commander_id' => $personnelId]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function dismissCommander($unitId)
    {
        $db = \Config\Database::connect();
        $db->table('units')
            ->where('unit_id', $unitId)
            ->update(['commander_id' => null]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function byParent(int $parentId)
    {
        $unitModel = new UnitModel();
        $units = $unitModel->where('parent_unit_id', $parentId)
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->response->setJSON($units);
    }

    public function updateName(int $unitId)
    {
        $data     = $this->request->getJSON(true);
        $name     = trim($data['name'] ?? '');
        $nickname = trim($data['nickname'] ?? '') ?: null;

        if (!$name) {
            return $this->response->setJSON(['success' => false, 'message' => 'Name is required.']);
        }

        $unitModel = new UnitModel();
        $unitModel->updateNameNickname($unitId, $name, $nickname);

        return $this->response->setJSON(['success' => true, 'name' => $name, 'nickname' => $nickname]);
    }

    public function deactivate(int $unitId)
    {
        $unitModel = new UnitModel();
        $unit      = $unitModel->find($unitId);

        if (!$unit) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit not found.']);
        }

        $date = $this->gameState['current_date'] ?? date('Y-m-d');
        $unitModel->deactivateUnit($unitId, $date);

        return $this->response->setJSON(['success' => true, 'parent_id' => $unit['parent_unit_id']]);
    }

    public function store()
    {
        $unitModel = new UnitModel();
        $factionId = $this->currentFaction['faction_id'] ?? null;

        $parentId  = (int)$this->request->getPost('parent_unit_id') ?: null;
        $name      = trim($this->request->getPost('name'));
        $nickname  = trim($this->request->getPost('nickname')) ?: null;
        $unitType  = $this->request->getPost('unit_type');
        $role      = $this->request->getPost('role') ?: null;

        if (!$name || !$unitType) {
            return redirect()->back()->with('error', 'Name and type are required.');
        }

        $unitId = $unitModel->insert([
            'name'           => $name,
            'nickname'       => $nickname,
            'unit_type'      => $unitType,
            'role'           => $role,
            'faction_id'     => $factionId,
            'parent_unit_id' => $parentId,
            'status'         => 'Garrisoned',
            'location_id'    => 1,
        ]);

        return redirect()->to("/units/{$unitId}");
    }
}
