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
        $name = '1st Davion Guards';
        $toeModel = new ToeTemplateModel();
        $templateId = $toeModel->getTemplateIdByName($name);

        if (!$templateId) {
            return "Template '{$name}' not found in DB.";
        }
        $template = $toeModel->getTemplate($templateId);
        $templateGen = new TemplateGenerator();
        $unitId = $templateGen->generateFromTemplate($template);
        return "{$name} created with ID: {$unitId}";
        //return "1st Davion Guards Regiment created with ID: " . $regimentId;
    }
}
