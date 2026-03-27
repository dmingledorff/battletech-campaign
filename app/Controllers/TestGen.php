<?php namespace App\Controllers;

use App\Libraries\TemplateGenerator;
use App\Models\ToeTemplateModel;

class TestGen extends BaseController
{
    public function index()
    {
        $toGenerate = [
            ['name' => '1st Davion Guards', 'allegiance' => 'Davion', 'location' => 1],
            ['name' => '1st ALAG',          'allegiance' => 'Kurita', 'location' => 12],
        ];

        $toeModel    = new ToeTemplateModel();
        $templateGen = new TemplateGenerator();
        $gameDate    = $this->gameState['current_date'] ?? '3025-01-01';
        $results     = [];

        foreach ($toGenerate as $entry) {
            $templateId = $toeModel->getTemplateIdByName($entry['name']);

            if (!$templateId) {
                $results[] = "❌ Template '{$entry['name']}' not found in DB.";
                continue;
            }

            $template = $toeModel->getTemplate($templateId);
            $unitId   = $templateGen->generateFromTemplate(
                $template,
                null,
                $entry['allegiance'],
                $gameDate,
                $entry['location']
            );

            $results[] = "✓ {$entry['name']} ({$entry['allegiance']}) created with unit ID: {$unitId}";
        }

        return implode('<br>', $results);
    }
}