<?php namespace App\Libraries;

use DateTime;
use App\Models\GameStateModel;

class PersonnelProfileService
{
    // Keyed by grade (1-13) instead of rank name — works for all factions
    private array $gradeExperienceMap = [
        1  => ['experience' => 'Green',    'age' => [18, 25]], // Private/Heishi
        2  => ['experience' => 'Green',    'age' => [18, 28]], // Corporal/Go-cho
        3  => ['experience' => 'Regular',  'age' => [20, 32]], // Sergeant/Gunso
        4  => ['experience' => 'Regular',  'age' => [22, 35]], // Leftenant/Chu-i
        5  => ['experience' => 'Veteran',  'age' => [26, 40]], // Captain/Tai-i
        6  => ['experience' => 'Veteran',  'age' => [28, 45]], // Major/Sho-sa
        7  => ['experience' => 'Veteran',  'age' => [30, 48]], // Lt Colonel/Chu-sa
        8  => ['experience' => 'Elite',    'age' => [32, 52]], // Colonel/Tai-sa
        9  => ['experience' => 'Elite',    'age' => [35, 55]], // Lt General/Sho-sho
        10 => ['experience' => 'Elite',    'age' => [38, 58]], // Major General/Tai-sho
        11 => ['experience' => 'Elite',    'age' => [40, 60]], // General/Tai-shu
        12 => ['experience' => 'Elite',    'age' => [45, 62]], // Marshal/Gunji-no-Kanrei
        13 => ['experience' => 'Elite',    'age' => [50, 65]], // Field Marshal/Coordinator
    ];

    protected GameStateModel $gameState;

    public function __construct()
    {
        $this->gameState = new GameStateModel();
    }

    public function generateProfileForRank(string $rankName): array
    {
        // Fallback by name for any legacy callers
        $nameMap = [
            'Recruit'   => 1, 'Private'  => 1, 'Heishi'  => 1,
            'Corporal'  => 2, 'Go-cho'   => 2, 'Gochō'   => 2,
            'Sergeant'  => 3, 'Gunso'    => 3, 'Gunsō'   => 3,
            'Leftenant' => 4, 'Chu-i'    => 4,
            'Captain'   => 5, 'Tai-i'    => 5,
            'Major'     => 6, 'Sho-sa'   => 6,
            'Colonel'   => 8, 'Tai-sa'   => 8,
            'Marshal'   => 12,
        ];
        $grade = $nameMap[$rankName] ?? 3;
        return $this->generateProfileForGrade($grade);
    }

    public function generateProfileForGrade(int $grade): array
    {
        $entry = $this->gradeExperienceMap[$grade] ?? [
            'experience' => 'Regular',
            'age'        => [20, 40],
        ];

        [$minAge, $maxAge] = $entry['age'];
        $age = rand($minAge, $maxAge);

        $currentDate = $this->gameState->getProperty('current_date') ?? '3025-01-01';
        $now         = new DateTime($currentDate);
        $birthYear   = (int)$now->format('Y') - $age;

        $month = rand(1, 12);
        $day   = rand(1, cal_days_in_month(CAL_GREGORIAN, $month, $birthYear));

        $dobObj = new DateTime();
        $dobObj->setDate($birthYear, $month, $day);

        return [
            'experience' => $entry['experience'],
            'dob'        => $dobObj->format('Y-m-d'),
            'age'        => $age,
        ];
    }
}