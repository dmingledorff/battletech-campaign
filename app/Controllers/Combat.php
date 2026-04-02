<?php

namespace App\Controllers;

use App\Models\BattleLogModel;
use App\Models\CombatModel;
use App\Models\FactionModel;
use App\Models\LocationModel;

class Combat extends BaseController
{
    public function index()
    {
        $factionId   = $this->currentFaction['faction_id'] ?? null;
        $combatModel = new CombatModel();
        $battleLog   = new BattleLogModel();

        $active    = $combatModel->getActiveCombatMissions($factionId);
        $concluded = $combatModel->getConcludedCombatMissions($factionId);

        foreach ($active as &$m) {
            $m['unit_count'] = $combatModel->getMissionUnitCount($m['mission_id']);
            $m['summary']    = $battleLog->getSummary($m['mission_id']);
        }
        foreach ($concluded as &$m) {
            $m['unit_count'] = $combatModel->getMissionUnitCount($m['mission_id']);
            $m['summary']    = $battleLog->getSummary($m['mission_id']);
        }
        unset($m);

        return $this->render('combat/index', [
            'active'    => $active,
            'concluded' => $concluded,
        ]);
    }

    public function show(int $missionId)
    {
        $combatModel = new CombatModel();
        $battleLog   = new BattleLogModel();
        $locationModel = new LocationModel();

        $mission = $combatModel->getCombatMission($missionId);

        if (!$mission) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $hasFortification = $combatModel->hasFortification(
            $mission['destination_location_id']
        );

        $attackers = $combatModel->getAttackerCombatants($missionId);
        $defenders = $combatModel->getDefenderCombatants($missionId);

        $attackerFaction = $combatModel->getAttackerFaction($missionId);
        $defenderFaction = $combatModel->getDefenderFaction($missionId);

        $summary = $battleLog->getSummary($missionId);
        $log = $battleLog->getForMission($missionId);

        $logByRound   = [];
        $groupFirstId = [];

        foreach ($log as $entry) {
            $key = $entry['combat_phase'] . '|' . $entry['combat_round'];
            $logByRound[$key][]  = $entry;
            $groupFirstId[$key]  = $entry['log_id']; // last seen = highest id for this group
        }

        // Sort groups by highest log_id descending = newest round first
        arsort($groupFirstId);

        $sorted = [];
        foreach (array_keys($groupFirstId) as $key) {
            $sorted[$key] = $logByRound[$key]; // entries within round stay chronological
        }
        $logByRound = $sorted;

        $location = $locationModel->find('location_id', $mission['destination_location_id']);

        $balance = $combatModel->getBattleBalance($missionId, $location);

        return $this->render('combat/show', [
            'mission'          => $mission,
            'hasFortification' => $hasFortification,
            'attackers'        => $attackers,
            'defenders'        => $defenders,
            'log'              => $log,
            'logByRound'       => $logByRound,
            'summary'          => $summary,
            'attackerFaction'  => $attackerFaction,
            'defenderFaction'  => $defenderFaction,
            'balance'          => $balance
        ]);
    }
}
