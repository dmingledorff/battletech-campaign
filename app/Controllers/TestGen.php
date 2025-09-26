<?php namespace App\Controllers;

use App\Libraries\DavionFactory;

class TestGen extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $factory = new DavionFactory($db);
        $regimentId = $factory->createDavionGuards();

        return "1st Davion Guards Regiment created with ID: " . $regimentId;
    }
}
