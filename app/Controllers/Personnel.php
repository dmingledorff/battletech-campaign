<?php namespace App\Controllers;

use App\Models\PersonnelModel;
use App\Models\UnitModel;

class Personnel extends BaseController
{
    public function show($id)
    {
        $personnelModel = new PersonnelModel();
        $person = $personnelModel->find($id);

        if (!$person) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Personnel ID $id not found.");
        }

        $unitModel         = new UnitModel();
        $currentAssignment = $personnelModel->getCurrentAssignment((int)$id);
        $assignmentHistory = $personnelModel->getAssignmentHistory((int)$id);
        $equipment         = $personnelModel->getEquipmentAssignments((int)$id);

        // Unit chain only needed for current assignment
        $unitChain = null;
        if ($currentAssignment) {
            $unitChain = $unitModel->getUnitChain($currentAssignment['unit_id']);
        }

        // Add chain to history rows too
        foreach ($assignmentHistory as &$row) {
            $row['unit_chain'] = $unitModel->getUnitChain($row['unit_id']);
        }

        $currentDate = new \DateTime($this->gameState['current_date']);
        $dob         = new \DateTime((string)$person['date_of_birth']);
        $age         = $dob->diff($currentDate)->y;

        return $this->render('personnel/show', [
            'person'            => $person,
            'currentAssignment' => $currentAssignment,
            'unitChain'         => $unitChain,
            'assignmentHistory' => $assignmentHistory,
            'equipment'         => $equipment,
            'age'               => $age,
        ]);
    }

}
