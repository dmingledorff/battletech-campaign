<?php

namespace App\Controllers;

use App\Models\EquipmentModel;

class Equipment extends BaseController
{
    public function show($id)
    {
        $equipmentModel = new EquipmentModel();

        // Fetch equipment details
        $equipment = $equipmentModel->getEquipment($id);

        if (!$equipment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Equipment with ID $id not found.");
        }

        // Fetch crew assigned to this equipment
        $crew = $equipmentModel->getCrew($id);
        $crewManifest = $equipmentModel->getCrewManifest($id);

        return $this->render('equipment/show', [
            'equipment' => $equipment,
            'crew'      => $crew,
            'crewManifest' => $crewManifest,
        ]);
    }

    public function getCrew($equipmentId)
    {
        $equipmentModel = new EquipmentModel();
        $crew = $equipmentModel->getCrew($equipmentId);
        return $this->response->setJSON($crew);
    }

    public function getAvailableCrew(int $equipmentId, int $slotId)
    {
        $equipmentModel = new EquipmentModel();
        $available = $equipmentModel->getAvailableCrewForSlot($equipmentId, $slotId);
        return $this->response->setJSON($available);
    }

    public function assignCrew(int $equipmentId)
    {
        $data        = $this->request->getJSON(true);
        $personnelId = (int)($data['personnel_id'] ?? 0);
        $slotId      = (int)($data['slot_id'] ?? 0);
        $role        = $data['crew_role'] ?? '';
        $date        = $this->gameState['current_date'] ?? '3025-01-01';

        if (!$personnelId || !$slotId || !$role) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing data']);
        }

        $equipmentModel = new EquipmentModel();
        $ok             = $equipmentModel->assignCrew($equipmentId, $personnelId, $slotId, $role, $date);

        return $this->response->setJSON(['success' => $ok]);
    }

    public function removeCrew(int $equipmentId)
    {
        $data        = $this->request->getJSON(true);
        $personnelId = (int)($data['personnel_id'] ?? 0);
        $slotId      = (int)($data['slot_id'] ?? 0);
        $date        = $this->gameState['current_date'] ?? '3025-01-01';

        if (!$personnelId || !$slotId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing data']);
        }

        $equipmentModel = new EquipmentModel();
        $ok             = $equipmentModel->removeCrew($equipmentId, $personnelId, $slotId, $date);

        return $this->response->setJSON(['success' => $ok]);
    }
}
