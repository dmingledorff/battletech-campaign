<?php 

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\GameStateModel;

class BaseController extends Controller
{
    protected $helpers = ['url','form','html'];
    protected $gameDate;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger) {
        parent::initController($request, $response, $logger);

        // Load GameState model
        $gameState = new \App\Models\GameStateModel();

        $currentDate = $gameState->getProperty('current_date') ?? '3025-01-01';
        $dateObj     = new \DateTime($currentDate);

        // Save formatted date for all controllers
        $this->gameDate = $dateObj->format('j F Y');
    }

    /**
     * Universal render wrapper
     * Injects game date + wraps header/footer around content
     */
    protected function render(string $view, array $data = []): string {
        
        // Always include game date in every render
        $data['gameDate'] = $this->gameDate;

        return view('layout/header', $data)
             . view($view, $data)
             . view('layout/footer', $data);
    }
}
