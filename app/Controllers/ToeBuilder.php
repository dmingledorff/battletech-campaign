<?php

namespace App\Controllers;

use App\Models\ToeTemplateModel;
use App\Models\RankModel;
use App\Models\FactionModel;

class ToeBuilder extends BaseController
{
    public function index()
    {
        $toeModel   = new ToeTemplateModel();
        $templates  = $toeModel->getAllTemplates();

        return $this->render('toe/index', [
            'templates' => $templates,
        ]);
    }

    public function create()
    {
        $rankModel    = new RankModel();
        $factionModel = new FactionModel();

        return $this->render('toe/create', [
            'ranks'    => $rankModel->orderBy('faction')->orderBy('grade')->findAll(),
            'factions' => $factionModel->findAll(),
        ]);
    }

    public function store()
    {
        $toeModel = new ToeTemplateModel();
        $id = $toeModel->insert([
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'unit_type'   => $this->request->getPost('unit_type'),
            'role'        => $this->request->getPost('role') ?: null,
            'mobility'    => $this->request->getPost('mobility') ?: null,
            'faction'     => $this->request->getPost('faction') ?: null,
            'era'         => $this->request->getPost('era') ?: null,
        ]);
        return redirect()->to("/toe/{$id}");
    }

    public function show(int $id)
    {
        $toeModel     = new ToeTemplateModel();
        $rankModel    = new RankModel();
        $factionModel = new FactionModel();
        $template     = $toeModel->getFullTemplate($id);

        if (!$template) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Template $id not found.");
        }

        $allTemplates = $toeModel->where('template_id !=', $id)
            ->orderBy('unit_type')->orderBy('name')->findAll();

        // Get ranks for the template's faction, or player's faction, or Davion as fallback
        $rankFaction = $template['faction']
            ?? $this->currentFaction['house']
            ?? 'Davion';

        $ranks = $rankModel->where('faction', $rankFaction)
            ->orderBy('grade', 'ASC')
            ->findAll();

        return $this->render('toe/show', [
            'template'     => $template,
            'allTemplates' => $allTemplates,
            'ranks'        => $ranks,
            'factions'     => $factionModel->findAll(),
            'rankFaction'  => $rankFaction,
        ]);
    }

    public function update(int $id)
    {
        $toeModel = new ToeTemplateModel();
        $toeModel->update($id, [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'unit_type'   => $this->request->getPost('unit_type'),
            'role'        => $this->request->getPost('role') ?: null,
            'mobility'    => $this->request->getPost('mobility') ?: null,
            'faction'     => $this->request->getPost('faction') ?: null,
            'era'         => $this->request->getPost('era') ?: null,
        ]);
        return redirect()->to("/toe/{$id}");
    }

    public function delete(int $id)
    {
        $toeModel = new ToeTemplateModel();
        $toeModel->deleteTemplate($id);
        return redirect()->to('/toe');
    }

    // ================================
    // Slot AJAX endpoints
    // ================================

    public function addSlot(int $templateId)
    {
        $data     = $this->request->getJSON(true);
        $toeModel = new ToeTemplateModel();

        $slotData = [
            'slot_type' => $data['slot_type'],
            'is_core'   => 1,
        ];

        $roles = [];

        if ($data['slot_type'] === 'Personnel') {
            $slotData['mos']       = $data['mos'];
            $slotData['min_grade'] = (int)$data['min_grade'];
            $slotData['max_grade'] = (int)$data['max_grade'];
        } else {
            $slotData['equipment_type'] = $data['equipment_type'];
            $slotData['weight_class']   = $data['weight_class'] ?: null;
            $roles = $data['roles'] ?? [];
        }

        $slotId = $toeModel->addSlot($templateId, $slotData, $roles);

        return $this->response->setJSON(['success' => true, 'slot_id' => $slotId]);
    }

    public function deleteSlot(int $slotId)
    {
        $toeModel = new ToeTemplateModel();
        $toeModel->deleteSlot($slotId);
        return $this->response->setJSON(['success' => true]);
    }

    public function addCrew(int $equipSlotId)
    {
        $data     = $this->request->getJSON(true);
        $toeModel = new ToeTemplateModel();
        $crewId   = $toeModel->addCrew(
            $equipSlotId,
            (int)$data['personnel_slot_id'],
            $data['crew_role']
        );
        return $this->response->setJSON(['success' => true, 'crew_id' => $crewId]);
    }

    public function deleteCrew(int $crewId)
    {
        $toeModel = new ToeTemplateModel();
        $toeModel->deleteCrew($crewId);
        return $this->response->setJSON(['success' => true]);
    }

    // ================================
    // Subunit AJAX endpoints
    // ================================

    public function addSubunit(int $parentId)
    {
        $data     = $this->request->getJSON(true);
        $toeModel = new ToeTemplateModel();
        $subunitId = $toeModel->addSubunit(
            $parentId,
            (int)$data['child_template_id'],
            (int)($data['quantity'] ?? 1),
            (bool)($data['is_core'] ?? true),
            (bool)($data['is_command'] ?? false)
        );
        return $this->response->setJSON(['success' => true, 'subunit_id' => $subunitId]);
    }

    public function deleteSubunit(int $subunitId)
    {
        $toeModel = new ToeTemplateModel();
        $toeModel->deleteSubunit($subunitId);
        return $this->response->setJSON(['success' => true]);
    }
}
