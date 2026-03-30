<?php namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\GameTickService;
use App\Models\GameStateModel;

class Tick extends BaseCommand
{
    protected $group       = 'Game';
    protected $name        = 'game:tick';
    protected $description = 'Advance the game state by one tick (3 game hours).';

    public function run(array $params)
    {
        $ticks = (int)($params[0] ?? 1);

        $service = new GameTickService();

        for ($i = 0; $i < $ticks; $i++) {
            $service->processTick();
        }

        $gameState = new GameStateModel();
        $date      = $gameState->getProperty('current_date');
        $hour      = str_pad($gameState->getProperty('current_hour'), 2, '0', STR_PAD_LEFT);

        CLI::write("Tick(s) processed: {$ticks}. Game time: " . date('j F Y', strtotime($date)) . " {$hour}:00");
    }
}