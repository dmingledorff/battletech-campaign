<?php

namespace App\Controllers;

use App\Models\ToeTemplateModel;
use App\Models\UnitModel;
use App\Models\LocationModel;
use App\Models\FactionModel;
use App\Models\EventLogModel;
use App\Models\GameStateModel;
use App\Libraries\TemplateGenerator;

class Admin extends BaseController
{
    public function index()
    {
        $toeModel      = new ToeTemplateModel();
        $unitModel     = new UnitModel();
        $locationModel = new LocationModel();
        $factionModel  = new FactionModel();

        $templates  = $toeModel->orderBy('unit_type')->orderBy('name')->findAll();
        $factions   = $factionModel->findAll();
        $locations  = $locationModel->findAll();
        $units  = $unitModel->where('status !=', 'Deactivated')
            ->orderBy('unit_type')
            ->orderBy('name')
            ->findAll();
        foreach ($units as &$unit) {
            $unit['unit_chain'] = $unitModel->getUnitChain($unit['unit_id']);
        }
        unset($unit);
        $unitTypes  = $this->getEnumValues('units', 'unit_type');
        $logTypes   = $this->getEnumValues('event_log', 'log_type');
        $severities = $this->getEnumValues('event_log', 'severity');

        return $this->render('admin/index', [
            'templates'   => $templates,
            'factions'    => $factions,
            'locations'   => $locations,
            'units'       => $units,
            'unitTypes'   => $unitTypes,
            'logTypes'    => $logTypes,
            'severities'  => $severities,
            'gameState'   => $this->gameState,
            'gameDate'    => $this->gameDate,
        ]);
    }

    public function setDate()
    {
        $date  = $this->request->getPost('game_date');
        $model = new GameStateModel();
        $model->setProperty('current_date', $date);

        $eventLog = new EventLogModel();
        $eventLog->log(
            null,
            $date,
            'System',
            'Game date set to ' . date('j F Y', strtotime($date)),
            'Date manually set by administrator.',
            'Warning'
        );

        return redirect()->to('/admin')->with('success', 'Date updated to ' . $date);
    }

    public function tick()
    {
        $tickService = new \App\Services\GameTickService();
        $tickService->processTick();

        $gameStateModel = new GameStateModel();
        $newDate = $gameStateModel->getProperty('current_date');
        $newHour = $gameStateModel->getProperty('current_hour');
        $timeStr = date('j F Y', strtotime($newDate))
            . ' ' . str_pad($newHour, 2, '0', STR_PAD_LEFT) . ':00';

        return redirect()->to('/admin')
            ->with('success', "Tick processed. Game time: {$timeStr}");
    }

    public function generateUnit()
    {
        $templateId = (int)$this->request->getPost('template_id');
        $allegiance = $this->request->getPost('allegiance');
        $locationId = (int)$this->request->getPost('location_id');
        $unitName   = trim($this->request->getPost('unit_name')) ?: null;

        $toeModel = new ToeTemplateModel();
        $template = $toeModel->getTemplate($templateId);

        if (!$template) {
            return redirect()->to('/admin')->with('error', 'Template not found.');
        }

        $factionModel = new FactionModel();
        $faction      = $factionModel->where('house', $allegiance)->first();

        $gameDate    = $this->gameState['current_date'] ?? '3025-01-01';
        $templateGen = new TemplateGenerator();
        $unitId      = $templateGen->generateFromTemplate(
            $template,
            null,
            $allegiance,
            $gameDate,
            $locationId,
            $unitName
        );

        if ($locationId && $unitId) {
            $unitModel = new UnitModel();
            $children  = $unitModel->getAllChildren();
            $unitModel->moveUnit($unitId, $locationId, $children);
        }

        $eventLog = new EventLogModel();
        $eventLog->log(
            $faction['faction_id'] ?? null,
            $gameDate,
            'System',
            "Unit generated: " . ($unitName ?? $template['name']),
            "Generated from TOE template '{$template['name']}' as {$allegiance} at location #{$locationId}.",
            'Info',
            $unitId
        );

        return redirect()->to('/admin')
            ->with('success', "Generated " . ($unitName ?? $template['name']) . " (Unit ID: {$unitId})");
    }

    public function moveUnit()
    {
        $unitId       = (int)$this->request->getPost('unit_id');
        $locationId   = (int)$this->request->getPost('location_id');
        $moveSubunits = (bool)$this->request->getPost('move_subunits');

        $unitModel = new UnitModel();
        $unit      = $unitModel->find($unitId);

        if (!$unit) {
            return redirect()->to('/admin')->with('error', 'Unit not found.');
        }

        if ($moveSubunits) {
            $children = $unitModel->getAllChildren();
            $unitModel->moveUnit($unitId, $locationId, $children);
        } else {
            $unitModel->db->table('units')
                ->where('unit_id', $unitId)
                ->update(['location_id' => $locationId]);
        }

        $location = $unitModel->db->table('locations')
            ->where('location_id', $locationId)
            ->get()->getRowArray();

        $gameDate = $this->gameState['current_date'] ?? '3025-01-01';
        $eventLog = new EventLogModel();
        $eventLog->log(
            $unit['faction_id'],
            $gameDate,
            'System',
            "{$unit['name']} relocated to {$location['name']}",
            'Unit moved by administrator' . ($moveSubunits ? ' (including subunits).' : '.'),
            'Info',
            $unitId,
            null,
            $locationId
        );

        return redirect()->to('/admin')->with('success', "Moved {$unit['name']} to {$location['name']}");
    }

    public function sendLog()
    {
        $factionId   = $this->request->getPost('faction_id') ?: null;
        $logType     = $this->request->getPost('log_type');
        $severity    = $this->request->getPost('severity');
        $title       = $this->request->getPost('title');
        $description = $this->request->getPost('description');
        $gameDate    = $this->gameState['current_date'] ?? '3025-01-01';

        $eventLog = new EventLogModel();

        if ($factionId === 'all') {
            $factions = (new FactionModel())->findAll();
            foreach ($factions as $faction) {
                $eventLog->log(
                    (int)$faction['faction_id'],
                    $gameDate,
                    $logType,
                    $title,
                    $description,
                    $severity
                );
            }
            $msg = 'Log sent to all factions.';
        } else {
            $eventLog->log(
                $factionId ? (int)$factionId : null,
                $gameDate,
                $logType,
                $title,
                $description,
                $severity
            );
            $msg = $factionId ? 'Log sent to faction.' : 'Global log sent.';
        }

        return redirect()->to('/admin')->with('success', $msg);
    }
}
