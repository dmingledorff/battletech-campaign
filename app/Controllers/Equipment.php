<?php namespace App\Controllers;

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
}
