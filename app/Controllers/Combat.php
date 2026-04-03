<?php

namespace App\Controllers;

use App\Models\BattleLogModel;
use App\Models\CombatBuildingsModel;
use App\Models\CombatModel;
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
        $combatModel          = new CombatModel();
        $combatBuildingsModel = new CombatBuildingsModel();
        $battleLog            = new BattleLogModel();
        $locationModel        = new LocationModel();

        $mission = $combatModel->getCombatMission($missionId);
        if (!$mission) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $location = $locationModel->find($mission['destination_location_id']);
        $balance  = $combatModel->getBattleBalance($missionId, $location);

        $log          = $battleLog->getForMission($missionId);
        $logByRound   = [];
        $groupFirstId = [];

        foreach ($log as $entry) {
            $key = $entry['combat_phase'] . '|' . $entry['combat_round'];
            $logByRound[$key][]  = $entry;
            $groupFirstId[$key]  = $entry['log_id'];
        }

        arsort($groupFirstId);
        $sorted = [];
        foreach (array_keys($groupFirstId) as $key) {
            $sorted[$key] = $logByRound[$key];
        }
        $logByRound = $sorted;

        $attackers = array_merge(
            $combatModel->getAttackerCombatants($missionId),
            $combatModel->getAttackerInfantry($missionId)
        );
        $defenders = array_merge(
            $combatModel->getDefenderCombatants($missionId),
            $combatModel->getDefenderInfantry($missionId)
        );

        return $this->render('combat/show', [
            'mission'                  => $mission,
            'attackers'                => $attackers,
            'defenders'                => $defenders,
            'attackerFaction'          => $combatModel->getAttackerFaction($missionId),
            'defenderFaction'          => $combatModel->getDefenderFaction($missionId),
            'summary'                  => $battleLog->getSummary($missionId),
            'log'                      => $log,
            'logByRound'               => $logByRound,
            'balance'                  => $balance,
            'combatBuildings'          => $combatBuildingsModel->getForMission($missionId),
            'fortificationAssignments' => $combatBuildingsModel->getFortificationAssignments($missionId),
            'location'                 => $location,
        ]);
    }
}
