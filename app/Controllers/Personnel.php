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

    public function roster()
    {
        $all = [
            'unassigned'  => $this->request->getGet('unassigned'),
            'unit_id'     => $this->request->getGet('unit_id'),
            'status'      => $this->request->getGet('status'),
            'mos'         => $this->request->getGet('mos'),
            'location_id' => $this->request->getGet('location_id'),
            '_regiment'   => $this->request->getGet('_regiment'),
            '_battalion'  => $this->request->getGet('_battalion'),
            '_company'    => $this->request->getGet('_company'),
            '_lance'      => $this->request->getGet('_lance'),
            '_planet'     => $this->request->getGet('_planet'),
        ];

        // Save everything to session including empty cascade keys
        session()->set('roster_filters', $all);

        // Only pass non-empty values to the query
        $filters = array_filter($all, fn($v) => $v !== null && $v !== '');

        $page = (int)($this->request->getGet('page') ?? 1);

        $personnelModel = new PersonnelModel();
        $result = $personnelModel->getRoster($filters, $page);

        return $this->response->setJSON($result);
    }

}
