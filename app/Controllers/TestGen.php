<?php namespace App\Controllers;

use App\Libraries\DavionFactory;
use App\Libraries\TemplateGenerator;
use App\Models\ToeTemplateModel;

class TestGen extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $factory = new DavionFactory($db);
        //$regimentId = $factory->createDavionGuards();

        // Now generate a Combined Arms Battalion from template
        $toeModel = new ToeTemplateModel();
        $templateId = $toeModel->getTemplateIdByName('Combined Arms Battalion');

        if (!$templateId) {
            return "Template 'Combined Arms Battalion' not found in DB.";
        }
        $template = $toeModel->getTemplate($templateId);
        $templateGen = new TemplateGenerator();
        $battalionId = $templateGen->generateFromTemplate($template, null, 'Davion');
        return "Combined Arms Battalion created with ID: {$battalionId}";
        //return "1st Davion Guards Regiment created with ID: " . $regimentId;
    }
}
