<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\GameStateModel;
use App\Models\PlanetModel;
use App\Models\LocationModel;

class BaseController extends Controller
{
    protected $helpers = ['url', 'form', 'html'];
    protected $gameDate;
    protected $gameHour;
    protected $gameDateTime;
    protected $gameState;
    protected $allPlanets;
    protected $currentFaction;
    protected $allLocations;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Load GameState model
        $gameStateModel = new GameStateModel();
        $this->gameState = $gameStateModel->getAllProperties();

        $currentDate = $gameStateModel->getProperty('current_date') ?? '3025-01-01';
        $dateObj     = new \DateTime($currentDate);
        $this->gameDate = $dateObj->format('j F Y');

        $this->gameHour  = (int)($this->gameState['current_hour'] ?? 0);
        // Format for views
        $this->gameDateTime = date('j F Y', strtotime($this->gameDate))
            . ' — '
            . str_pad($this->gameHour, 2, '0', STR_PAD_LEFT) . ':00';

        $planetModel = new PlanetModel();
        $this->allPlanets = $planetModel->getAllWithSystems();
        $locationModel = new LocationModel();
        $this->allLocations = $locationModel->getAllLocationsWithFactionInfo();


        $auth         = service('auth');
        $currentUser  = $auth->loggedIn() ? $auth->user() : null;
        $this->currentFaction = null;

        if ($currentUser && ! empty($currentUser->faction_id)) {
            $this->currentFaction = model('\App\Models\FactionModel')->find($currentUser->faction_id); // array
        }
    }

    /**
     * Universal render wrapper
     * Injects game date + wraps header/footer around content
     */
    protected function render(string $view, array $data = []): string
    {

        // Always include game date in every render
        $data['gameState'] = $this->gameState;
        $data['gameDate'] = $this->gameDate;
        $data['gameDateTime'] = $this->gameDateTime;
        $data['gameHour']     = $this->gameHour;
        $data['allPlanets'] = $this->allPlanets;
        $data['currentFaction'] = $this->currentFaction;
        $data['allLocations'] = $this->allLocations;


        return view('layout/header', $data)
            . view($view, $data)
            . view('layout/footer', $data);
    }

    protected function getEnumValues(string $table, string $column): array
    {
        return \App\Libraries\SchemaHelper::getEnumValues($table, $column);
    }
}
