<?php 

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\GameStateModel;
use App\Models\PlanetModel;

class BaseController extends Controller
{
    protected $helpers = ['url','form','html'];
    protected $gameDate;
    protected $gameState;
    protected $allPlanets;
    protected $currentFaction;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger) {
        parent::initController($request, $response, $logger);

        // Load GameState model
        $gameStateModel = new GameStateModel();
        $this->gameState = $gameStateModel->getAllProperties();

        $currentDate = $gameStateModel->getProperty('current_date') ?? '3025-01-01';
        $dateObj     = new \DateTime($currentDate);

        // Save formatted date for all controllers
        $this->gameDate = $dateObj->format('j F Y');

        $planetModel = new PlanetModel();
        $this->allPlanets = $planetModel->getAllWithSystems();

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
    protected function render(string $view, array $data = []): string {
        
        // Always include game date in every render
        $data['gameState'] = $this->gameState;
        $data['gameDate'] = $this->gameDate;
        $data['allPlanets'] = $this->allPlanets;
        $data['currentFaction'] = $this->currentFaction;


        return view('layout/header', $data)
             . view($view, $data)
             . view('layout/footer', $data);
    }
}
