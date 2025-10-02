<?php namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\GameTickService;

class Tick extends BaseCommand
{
    protected $group       = 'Game';
    protected $name        = 'game:tick';
    protected $description = 'Advance the game state by one or more days.';

    public function run(array $params)
    {
        $days = (int) ($params[0] ?? 1);

        $service = new GameTickService(db_connect());
        $service->processTick($days);

        CLI::write("Game advanced by {$days} day(s).");
    }
}
