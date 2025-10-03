<?php namespace App\Libraries;

use DateTime;
use App\Models\GameStateModel;

class PersonnelProfileService
{
    private array $rankExperienceMap = [
        'Recruit'     => ['experience' => 'Green',    'age' => [18, 25]],
        'Private'     => ['experience' => 'Green',    'age' => [18, 28]],
        'Corporal'    => ['experience' => 'Regular',  'age' => [20, 30]],
        'Sergeant'    => ['experience' => 'Regular',  'age' => [22, 35]],
        'Leftenant'   => ['experience' => 'Veteran',  'age' => [24, 40]],
        'Captain'     => ['experience' => 'Veteran',  'age' => [28, 45]],
        'Major'       => ['experience' => 'Elite',    'age' => [32, 50]],
        'Colonel'     => ['experience' => 'Elite',    'age' => [35, 55]],
        'Marshal'     => ['experience' => 'Elite',    'age' => [40, 60]],
    ];

    protected GameStateModel $gameState;

    public function __construct()
    {
        $this->gameState = new GameStateModel();
    }

    /**
     * Generate a personnel profile for a given rank.
     *
     * @param string $rankName
     * @return array
     */
    public function generateProfileForRank(string $rankName): array
    {
        $entry = $this->rankExperienceMap[$rankName] ?? [
            'experience' => 'Regular',
            'age'        => [20, 40]
        ];

        [$minAge, $maxAge] = $entry['age'];
        $age = rand($minAge, $maxAge);

        // Pull current date from GameState
        $currentDate = $this->gameState->getProperty('current_date') ?? '3025-01-01';
        $now = new DateTime($currentDate);

        // Set DOB by subtracting age from current game date
        $dob = (clone $now)->modify("-{$age} years")->format('Y-m-d');

        return [
            'experience' => $entry['experience'],
            'dob'        => $dob,
            'age'        => $age
        ];
    }
}
