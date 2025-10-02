<?php namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use App\Models\GameStateModel;

class GameTickService
{
    protected $db;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Main entry point to process a tick.
     */
    public function processTick(int $days = 1): void
    {
        // Step 1: Advance game date
        $this->advanceDate($days);

        // Step 2: Run subsystems
        //$this->processSupplies();
        $this->processMorale();
        //$this->processMaintenance();
        //$this->processMissions();
        //$this->processEvents();

        // Add more subsystems here as needed
        $this->updateTick();
    }

    protected function updateTick() {
        $gameState = new GameStateModel();
        // Increment tick count
        $tickCount = (int)$gameState->getProperty('tick_count', 0);
        $gameState->setProperty('tick_count', $tickCount + 1);
    }

    protected function advanceDate(int $days): void
    {
        $gameState = new GameStateModel();

        $currentDate = $gameState->getProperty('current_date');
        $dateObj = new \DateTime($currentDate);

        $dateObj->modify("+{$days} days");

        $gameState->setProperty('current_date', $dateObj->format('Y-m-d'));
    }

    protected function processSupplies(): void
    {
        // Example: Deduct daily supply usage for each unit
        $units = $this->db->table('units')->get()->getResultArray();
        foreach ($units as $unit) {
            $dailyUse = $unit['daily_supply_use'] ?? 0;
            if ($dailyUse > 0) {
                $newSupply = max(0, $unit['current_supply'] - $dailyUse);
                $this->db->table('units')
                    ->where('unit_id', $unit['unit_id'])
                    ->update(['current_supply' => $newSupply]);
            }
        }
    }

    protected function processMorale(): void
    {
        // Example: Slowly increase morale if supplies are good
        $personnel = $this->db->table('personnel')->get()->getResultArray();
        foreach ($personnel as $p) {
            $morale = $p['morale'] ?? 100;
            if ($morale < 100) {
                $morale = min(100, $morale + 0.1); // tiny recovery
                $this->db->table('personnel')
                    ->where('personnel_id', $p['personnel_id'])
                    ->update(['morale' => $morale]);
            }
        }
    }

    protected function processMaintenance(): void
    {
        // Example: track equipment wear or repair jobs
    }

    protected function processMissions(): void
    {
        // Example: progress any active missions
    }

    protected function processEvents(): void
    {
        // Example: trigger random events based on date
    }
}
