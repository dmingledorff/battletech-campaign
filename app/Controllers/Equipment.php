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

        return $this->render('equipment/show', [
            'equipment' => $equipment,
            'crew'      => $crew
        ]);
    }
    public function getCrew($equipmentId)
    {
        $db = \Config\Database::connect();

        // Join personnel through personnel_equipment (your actual link table)
        $crew = $db->table('personnel p')
            ->select('p.personnel_id, p.first_name, p.last_name, p.mos, p.status, r.abbreviation AS rank_abbr, pe.role')
            ->join('ranks r', 'p.rank_id = r.id', 'left')
            ->join('personnel_equipment pe', 'pe.personnel_id = p.personnel_id', 'inner')
            ->where('pe.equipment_id', $equipmentId)
            ->where('pe.date_released IS NULL') // only active crew members
            ->get()
            ->getResultArray();

        return $this->response->setJSON($crew);
    }

}
