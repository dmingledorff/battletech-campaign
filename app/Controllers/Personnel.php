<?php namespace App\Controllers;

use App\Models\PersonnelModel;

class Personnel extends BaseController
{
    public function show($id)
    {
        $personnelModel = new PersonnelModel();
        $person = $personnelModel->find($id);

        if (!$person) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Personnel ID $id not found.");
        }

        $assignments = $personnelModel->getAssignments($id);
        $equipment   = $personnelModel->getEquipmentAssignments($id);

        return $this->render('personnel/show', [
                'person'      => $person,
                'assignments' => $assignments,
                'equipment'   => $equipment
            ]);
    }

}
