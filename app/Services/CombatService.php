<?php

namespace App\Services;

use App\Models\BattleLogModel;
use App\Models\EventLogModel;
use App\Models\GameStateModel;
use App\Models\MissionModel;
use App\Models\UnitModel;
use App\Models\CombatBuildingsModel;
use CodeIgniter\Database\BaseConnection;

class CombatService
{
    protected $db;
    protected string $gameDate;
    protected int    $gameHour;
    protected array  $settings = [];
    protected array $artilleryTypes = [];
    protected CombatBuildingsModel $combatBuildingsModel;
    protected BattleLogModel $battleLog;
    protected GameStateModel $gameState;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db        = $db ?? db_connect();
        $this->battleLog = new BattleLogModel();
        $this->gameState = new GameStateModel();
        $this->combatBuildingsModel = new CombatBuildingsModel();
        $this->gameDate  = $this->gameState->getProperty('current_date') ?? '3025-01-01';
        $this->gameHour  = (int)($this->gameState->getProperty('current_hour') ?? 0);
        $this->loadSettings();
    }

    // ================================================================
    // Load all combat settings from game_state once
    // ================================================================
    protected function loadSettings(): void
    {
        $rows = $this->db->table('game_state')->get()->getResultArray();
        foreach ($rows as $row) {
            $this->settings[$row['property_name']] = $row['property_value'];
        }

        $artRows = $this->db->table('artillery_rules')->get()->getResultArray();
        foreach ($artRows as $row) {
            $this->artilleryTypes[$row['special_code']] = $row;
        }
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    // ================================================================
    // Called by GameTickService every tick
    // ================================================================
    public function processAllCombat(): void
    {
        $missions = $this->db->table('missions')
            ->where('status', 'Combat')
            ->get()->getResultArray();

        foreach ($missions as $mission) {
            $this->processRound($mission);
        }
    }

    // ================================================================
    // Called from GameTickService::completeMission() when assault
    // arrives at enemy location with garrison present
    // ================================================================
    public function initiateCombat(array $mission, array $location): void
    {
        // Guard — don't re-initiate if already in combat
        if ($mission['status'] === 'Combat') {
            return;
        }

        $missionId  = (int)$mission['mission_id'];
        $locationId = (int)$location['location_id'];
        $gameDate   = $this->gameDate;

        // Set mission to Combat phase
        $this->db->table('missions')
            ->where('mission_id', $missionId)
            ->update([
                'status'        => 'Combat',
                'combat_phase'  => 'Skirmish',
                'combat_round'  => 0,
            ]);

        // --- Populate attacker side ---
        $allMissionUnitIds = array_column(
            $this->db->table('mission_units')
                ->select('unit_id')
                ->where('mission_id', $missionId)
                ->get()->getResultArray(),
            'unit_id'
        );

        // Only recurse from top-level units (parent not also in mission)
        $attackerRoots = array_filter($allMissionUnitIds, function ($uid) use ($allMissionUnitIds) {
            $unit = $this->db->table('units')
                ->select('parent_unit_id')
                ->where('unit_id', $uid)
                ->get()->getRowArray();
            return !in_array((int)($unit['parent_unit_id'] ?? 0), array_map('intval', $allMissionUnitIds));
        });

        $attackerLeafIds = [];
        foreach ($attackerRoots as $uid) {
            $this->resolveLeafIds((int)$uid, $attackerLeafIds);
        }
        $attackerLeafIds = array_unique($attackerLeafIds);

        foreach ($attackerLeafIds as $unitId) {
            $this->populatePoolForUnit($unitId, $missionId, 'attacker', $gameDate);
        }

        // --- Populate defender side ---
        $missionFactionId = (int)$mission['faction_id'];

        $defenderTopUnits = $this->db->query("
            SELECT u.unit_id
            FROM units u
            WHERE u.location_id = {$locationId}
            AND u.status IN ('Garrisoned', 'Combat')
            AND u.faction_id != {$missionFactionId}
            AND (
                u.parent_unit_id IS NULL
                OR u.parent_unit_id NOT IN (
                    SELECT unit_id FROM units
                    WHERE location_id = {$locationId}
                    AND faction_id != {$missionFactionId}
                    AND status IN ('Garrisoned', 'Combat')
                )
            )
        ")->getResultArray();

        $defenderLeafIds = [];
        foreach ($defenderTopUnits as $unit) {
            $this->resolveLeafIds((int)$unit['unit_id'], $defenderLeafIds);
        }
        $defenderLeafIds = array_unique($defenderLeafIds);

        foreach ($defenderLeafIds as $unitId) {
            $this->populatePoolForUnit($unitId, $missionId, 'defender', $gameDate);
        }

        // Set all attacker and defender units to Combat status
        $allUnitIds = array_merge($attackerLeafIds, $defenderLeafIds);
        if (!empty($allUnitIds)) {
            $idList = implode(',', $allUnitIds);
            $this->db->query("
            UPDATE units SET status = 'Combat'
            WHERE unit_id IN ({$idList})
        ");
        }

        $this->snapshotBuildings($missionId, $locationId);
        $this->assignInfantryToFortifications($missionId, $locationId);

        // Log BattleStart
        $this->battleLog->record(
            $missionId,
            $gameDate,
            $this->gameHour,
            'Skirmish',
            0,
            'BattleStart',
            "Battle commenced at {$location['name']}. Attacking force engaged defending garrison.",
            null,
            null,
            null
        );
        $eventLog = new EventLogModel();
        $eventLog->log(
            $missionFactionId,
            $gameDate,
            'Combat',
            "Battle Commenced — {$mission['name']}",
            "Assault force engaged defending garrison at {$location['name']}.",
            'Warning',
            null,
            $missionId,
            $locationId
        );
        $defenderFactionId = $this->getDefenderFactionId($locationId, $missionFactionId);
        if ($defenderFactionId) {
            $eventLog->log(
                $defenderFactionId,
                $gameDate,
                'Combat',
                "Under Attack — {$location['name']}",
                "Enemy assault force has engaged our garrison at {$location['name']}.",
                'Critical',
                null,
                null,
                $locationId
            );
        }
    }

    // -------------------------------------------------------------------
    // Populate combat_pool for a single leaf unit
    // -------------------------------------------------------------------
    protected function populatePoolForUnit(int $unitId, int $missionId, string $side, string $gameDate): void
    {
        $unit = $this->db->table('units')
            ->where('unit_id', $unitId)
            ->get()->getRowArray();

        if (!$unit) return;

        $isInfantry = ($unit['unit_type'] === 'Squad');

        if ($isInfantry) {
            // Squad leader = highest grade active personnel in unit
            $leader = $this->db->query("
                SELECT p.personnel_id
                FROM personnel_assignments pa
                JOIN personnel p ON p.personnel_id = pa.personnel_id
                LEFT JOIN ranks r ON r.id = p.rank_id
                WHERE pa.unit_id = {$unitId}
                AND pa.date_released IS NULL
                AND p.status = 'Active'
                ORDER BY r.grade DESC
                LIMIT 1
            ")->getRowArray();

            // Only add if there is at least one active member
            $strength = $this->db->query("
                SELECT COUNT(*) AS cnt
                FROM personnel_assignments pa
                JOIN personnel p ON p.personnel_id = pa.personnel_id
                WHERE pa.unit_id = {$unitId}
                AND pa.date_released IS NULL
                AND p.status = 'Active'
            ")->getRowArray()['cnt'] ?? 0;

            if ($strength > 0) {
                $this->db->table('combat_pool')->insert([
                    'mission_id'       => $missionId,
                    'side'             => $side,
                    'participant_type' => 'infantry',
                    'unit_id'          => $unitId,
                    'equipment_id'     => null,
                    'personnel_id'     => $leader['personnel_id'] ?? null,
                    'pilot_morale'     => $leader['morale'] ?? 100,
                    'pilot_experience' => $leader['experience'] ?? 'Regular',
                    'status'           => 'Active',
                    'joined_at'        => $gameDate,
                ]);
            }
        } else {
            // Equipment-based unit — one pool record per active mech/vehicle
            $equipment = $this->db->query("
                SELECT e.equipment_id
                FROM equipment e
                WHERE e.assigned_unit_id = {$unitId}
                AND e.equipment_status = 'Active'
                AND e.combat_status != 'Destroyed'
            ")->getResultArray();

            foreach ($equipment as $eq) {
                // Must have an active pilot
                $crew = $this->db->query("
                    SELECT p.personnel_id, p.first_name, p.last_name,
                        p.experience, p.morale, p.status,
                        r.abbreviation AS rank_abbr
                    FROM personnel_equipment pe
                    JOIN personnel p ON p.personnel_id = pe.personnel_id
                    LEFT JOIN ranks r ON r.id = p.rank_id
                    WHERE pe.equipment_id = {$eq['equipment_id']}
                    AND pe.date_released IS NULL
                    AND p.status = 'Active'
                    LIMIT 1
                ")->getRowArray();

                if (!$crew) continue;

                $eqData = $this->db->table('equipment')
                    ->select('current_armor, current_structure, max_armor, max_structure')
                    ->where('equipment_id', $eq['equipment_id'])
                    ->get()->getRowArray();


                $this->db->table('combat_pool')->insert([
                    'mission_id'          => $missionId,
                    'side'                => $side,
                    'participant_type'    => 'equipment',
                    'unit_id'             => $unitId,
                    'equipment_id'        => $eq['equipment_id'],
                    'personnel_id'        => $crew['personnel_id'],
                    'pilot_first_name'    => $crew['first_name'],
                    'pilot_last_name'     => $crew['last_name'],
                    'pilot_rank_abbr'     => $crew['rank_abbr'],
                    'pilot_experience'    => $crew['experience'],
                    'pilot_morale'        => $crew['morale'],
                    'pilot_final_status'  => 'Active',
                    'current_armor'       => $eqData['current_armor'],
                    'current_structure'   => $eqData['current_structure'],
                    'max_armor'           => $eqData['max_armor'],
                    'max_structure'       => $eqData['max_structure'],
                    'status'              => 'Active',
                    'joined_at'           => $gameDate,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------
    // Recursively resolve leaf unit IDs (units with no children)
    // -------------------------------------------------------------------
    protected function resolveLeafIds(int $unitId, array &$leafIds): void
    {
        $children = $this->db->table('units')
            ->select('unit_id')
            ->where('parent_unit_id', $unitId)
            ->get()->getResultArray();

        if (empty($children)) {
            $leafIds[] = $unitId;
            return;
        }

        foreach ($children as $child) {
            $this->resolveLeafIds((int)$child['unit_id'], $leafIds);
        }
    }

    // -------------------------------------------------------------------
    // Snapshot all relevant buildings at location into combat_buildings
    // -------------------------------------------------------------------
    protected function snapshotBuildings(int $missionId, int $locationId): void
    {
        $buildings = $this->db->table('buildings')
            ->where('location_id', $locationId)
            ->whereIn('status', ['Operational', 'Damaged'])
            ->get()->getResultArray();

        foreach ($buildings as $b) {
            $this->combatBuildingsModel->insert([
                'mission_id'        => $missionId,
                'building_id'       => $b['building_id'],
                'name'              => $b['name'],
                'type'              => $b['type'],
                'capacity'          => $b['capacity'],
                'current_integrity' => (int)$b['current_integrity'],
                'max_integrity'     => (int)$b['max_integrity'],
                'current_armor'     => $b['current_armor'],
                'max_armor'         => $b['max_armor'],
                'as_dmg_s'          => $b['as_dmg_s'],
                'as_dmg_m'          => $b['as_dmg_m'],
                'as_dmg_l'          => $b['as_dmg_l'],
                'as_specials'       => $b['as_specials'],
                'as_tmm'            => (int)$b['as_tmm'],
                'status'            => $b['status'],
            ]);
        }
    }

    // -------------------------------------------------------------------
    // Auto-assign defending infantry to fortifications up to capacity
    // Priority: highest integrity fortifications first
    // -------------------------------------------------------------------
    protected function assignInfantryToFortifications(int $missionId, int $locationId): void
    {
        $fortifications = $this->combatBuildingsModel->getActiveFortifications($missionId);

        if (empty($fortifications)) return;

        $infantry = $this->db->query("
            SELECT cp.pool_id, cp.unit_id
            FROM combat_pool cp
            WHERE cp.mission_id = {$missionId}
            AND cp.side = 'defender'
            AND cp.participant_type = 'infantry'
            AND cp.status = 'Active'
        ")->getResultArray();

        if (empty($infantry)) return;

        $infantryQueue = $infantry;

        foreach ($fortifications as $fort) {
            $capacity = (int)($fort['capacity'] ?? 0);
            if ($capacity <= 0) continue;

            $assigned = 0;
            $fortId   = (int)$fort['combat_building_id'];

            while ($assigned < $capacity && !empty($infantryQueue)) {
                $unit = array_shift($infantryQueue);

                $this->db->table('fortification_assignments')->insert([
                    'combat_building_id' => $fortId,
                    'unit_id'            => $unit['unit_id'],
                    'mission_id'         => $missionId,
                ]);

                $this->db->table('combat_pool')
                    ->where('pool_id', $unit['pool_id'])
                    ->update(['building_id' => $fortId]);

                $assigned++;
            }

            if (empty($infantryQueue)) break;
        }
    }

    // ================================================================
    // Process one round of combat for a mission
    // ================================================================
    protected function processRound(array $mission): void
    {
        $missionId = $mission['mission_id'];
        $phase     = $mission['combat_phase'];
        $round     = (int)$mission['combat_round'] + 1;
        $locationId = $mission['destination_location_id'];

        // Update round counter
        $this->db->table('missions')
            ->where('mission_id', $missionId)
            ->update(['combat_round' => $round]);

        // Get location for terrain
        $location = $this->db->table('locations')
            ->where('location_id', $locationId)
            ->get()->getRowArray();

        $terrain = $this->getTerrainModifiers($location);

        // Check for fortification buildings
        $hasFortification = $this->db->table('buildings')
            ->where('location_id', $locationId)
            ->where('type', 'Fortification')
            ->where('status', 'Operational')
            ->countAllResults() > 0;

        if ($hasFortification) {
            $terrain['hard_attack_modifier'] *= 0.5;
        }

        // Build combatant lists
        $attackers = $this->buildCombatants($missionId, 'attacker');
        $defenders = $this->buildCombatants($missionId, 'defender');

        // Check battle end conditions before processing
        if ($this->checkBattleEnd($mission, $attackers, $defenders, $location)) {
            return;
        }

        // Collect artillery units (ART special)
        $attackerArtillery = $this->extractArtillery($attackers);
        $defenderArtillery = $this->extractArtillery($defenders);

        // Pair lances and resolve attacks
        $pairings = $this->pairLances($attackers, $defenders);

        foreach ($pairings as $pairing) {
            // Attackers shoot at defenders — pass $defenders master array
            $this->resolveOneSideAttacks(
                $pairing['attackers'],
                $defenders,          // ← master array, not pairing copy
                $mission,
                $phase,
                $round,
                $terrain,
                'attacker'
            );
            // Defenders shoot at attackers — pass $attackers master array
            $this->resolveOneSideAttacks(
                $pairing['defenders'],
                $attackers,          // ← master array, not pairing copy
                $mission,
                $phase,
                $round,
                $terrain,
                'defender'
            );
        }

        // Artillery support
        $this->resolveArtillery($attackerArtillery, $defenders, $mission, $phase, $round, $terrain);
        $this->resolveArtillery($defenderArtillery, $attackers, $mission, $phase, $round, $terrain);

        $this->syncCombatToMainTables($missionId);
        $this->processHeat($missionId);

        // Log round summary
        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'RoundSummary',
            "{$phase} Round {$round} complete."
        );

        // Check phase transition
        $this->checkPhaseTransition($mission, $attackers, $defenders, $round, $phase);
    }

    // ================================================================
    // Build combatant list for one side
    // Returns array of units with their equipment and crew
    // ================================================================
    protected function buildCombatants(int $missionId, string $side): array
    {
        // Read active/crippled participants from pool only
        $pool = $this->db->query("
            SELECT cp.pool_id, cp.unit_id, cp.equipment_id, cp.personnel_id,
                cp.participant_type, cp.status AS pool_status,
                cp.heat_buildup, cp.is_shutdown, cp.used_ov,
                cp.pilot_morale, cp.pilot_experience,
                cp.pilot_first_name, cp.pilot_last_name,
                cp.pilot_rank_abbr, cp.pilot_final_status,
                cp.side
            FROM combat_pool cp
            WHERE cp.mission_id = {$missionId}
            AND cp.side = '{$side}'
            AND cp.status IN ('Active', 'Crippled')
            AND cp.resolved = 0
        ")->getResultArray();
        $combatants = [];

        foreach ($pool as $participant) {
            $unitId = (int)$participant['unit_id'];

            $unit = $this->db->table('units u')
                ->select('u.unit_id, u.name, u.unit_type, u.role, u.faction_id, u.parent_unit_id')
                ->where('u.unit_id', $unitId)
                ->get()->getRowArray();

            if (!$unit) continue;

            // --- Infantry ---
            if ($participant['participant_type'] === 'infantry') {
                $strength = (int)$this->db->query("
                    SELECT COUNT(*) AS cnt
                    FROM personnel_assignments pa
                    JOIN personnel p ON p.personnel_id = pa.personnel_id
                    WHERE pa.unit_id = {$unitId}
                    AND pa.date_released IS NULL
                    AND p.status = 'Active'
                ")->getRowArray()['cnt'] ?? 0;

                // Max strength — total ever assigned (including casualties)
                $maxStrength = (int)$this->db->query("
                    SELECT COUNT(*) AS cnt
                    FROM personnel_assignments pa
                    JOIN personnel p ON p.personnel_id = pa.personnel_id
                    WHERE pa.unit_id = {$unitId}
                ")->getRowArray()['cnt'] ?? $strength;

                // Infantry combatant array
                $combatants[] = [
                    'pool_id'      => (int)$participant['pool_id'],
                    'unit'         => $unit,
                    'equipment'    => null,
                    'crew'         => [
                        'personnel_id' => $participant['personnel_id'],
                        'morale'       => (float)$participant['pilot_morale'],
                        'experience'   => $participant['pilot_experience'] ?? 'Regular',
                    ],
                    'is_infantry'  => true,
                    'is_turret'    => false,
                    'strength'     => $strength,
                    'max_strength' => $maxStrength,
                    'pool_status'  => $participant['pool_status'],
                    'side'         => $side,
                    'retreated'    => false,
                    'out_of_combat' => false,
                ];

                continue;
            }

            // --- Equipment (Mech / Vehicle) ---
            $eqId = (int)$participant['equipment_id'];

            $equipment = $this->db->query("
                SELECT e.equipment_id, e.current_armor, e.current_structure,
                    e.max_armor, e.max_structure, e.combat_status,
                    e.heat_buildup, e.assigned_unit_id,
                    c.name AS chassis_name, c.variant, c.as_type,
                    c.as_mv, c.as_tmm, c.as_size,
                    c.as_dmg_s, c.as_dmg_m, c.as_dmg_l, c.as_dmg_e,
                    c.as_ov, c.as_specials, c.battlefield_role,
                    c.as_armor AS base_armor,
                    c.as_structure AS base_structure,
                    c.weight_class
                FROM equipment e
                JOIN chassis c ON c.chassis_id = e.chassis_id
                WHERE e.equipment_id = {$eqId}
            ")->getRowArray();

            if (!$equipment) continue;

            // Parse specials
            $equipment['specials'] = $this->parseSpecials($equipment['as_specials'] ?? '');

            // Get active pilot via personnel_equipment
            $crew = [
                'personnel_id' => (int)$participant['personnel_id'],
                'morale'       => (float)$participant['pilot_morale'],
                'experience'   => $participant['pilot_experience'] ?? 'Regular',
                'status'       => $participant['pilot_final_status'] ?? 'Active',
                'first_name'   => $participant['pilot_first_name'],
                'last_name'    => $participant['pilot_last_name'],
                'rank_abbr'    => $participant['pilot_rank_abbr'],
                'grade'        => null, // not needed during combat
            ];

            // No active pilot — skip (pilot may have been KIA/Injured mid-battle)
            if (!$participant['personnel_id']) continue;

            $combatants[] = [
                'pool_id'     => (int)$participant['pool_id'],
                'unit'        => $unit,
                'equipment'   => $equipment,
                'crew'        => $crew,
                'is_infantry' => false,
                'is_turret'    => false,
                'pool_status'  => $participant['pool_status'],
                'heat_buildup' => (int)$participant['heat_buildup'],
                'is_shutdown'  => (bool)$participant['is_shutdown'],
                'used_ov'      => (bool)$participant['used_ov'],
                'pool_status' => $participant['pool_status'],
                'side'        => $side,
                'retreated'   => false,
                'out_of_combat'  => false
            ];
        }

        // Load active turrets as defender combatants
        if ($side === 'defender') {
            $turrets = $this->combatBuildingsModel->getActiveTurrets($missionId);

            foreach ($turrets as $turret) {
                $turret['specials'] = $this->parseSpecials($turret['as_specials'] ?? '');

                $combatants[] = [
                    'pool_id'       => null,
                    'unit'          => [
                        'unit_id'        => null,
                        'name'           => $turret['name'],
                        'unit_type'      => 'Turret',
                        'role'           => 'Defender',
                        'faction_id'     => null,
                        'parent_unit_id' => null,
                    ],
                    'equipment'     => [
                        'equipment_id'      => null,
                        'combat_building_id' => $turret['combat_building_id'],
                        'chassis_name'      => $turret['name'],
                        'variant'           => '',
                        'as_type'           => 'Turret',
                        'as_dmg_s'          => $turret['as_dmg_s'],
                        'as_dmg_m'          => $turret['as_dmg_m'],
                        'as_dmg_l'          => $turret['as_dmg_l'],
                        'as_tmm'            => $turret['as_tmm'],
                        'current_armor'     => $turret['current_armor'],
                        'max_armor'         => $turret['max_armor'],
                        'current_structure' => $turret['current_integrity'],
                        'max_structure'     => 100,
                        'combat_status'     => $turret['status'] === 'Damaged' ? 'Crippled' : 'Operational',
                        'specials'          => $turret['specials'],
                    ],
                    'crew'          => null,  // no pilot
                    'is_infantry'   => false,
                    'is_turret'     => true,
                    'pool_status'   => 'Active',
                    'heat_buildup'  => 0,
                    'is_shutdown'   => false,
                    'used_ov'       => false,
                    'side'          => 'defender',
                    'retreated'     => false,
                    'out_of_combat' => false,
                ];
            }
        }

        return $combatants;
    }

    protected function resolveLeafUnits(int $unitId, array &$leafUnits): void
    {
        // Check if this unit has children
        $children = $this->db->table('units')
            ->where('parent_unit_id', $unitId)
            ->whereIn('unit_type', ['Lance', 'Squad', 'Company', 'Platoon'])
            ->get()->getResultArray();

        if (empty($children)) {
            // This is a leaf — check if it has equipment
            $unit = $this->db->table('units u')
                ->select('u.*')
                ->where('u.unit_id', $unitId)
                ->get()->getRowArray();
            if ($unit) {
                $leafUnits[] = $unit;
            }
            return;
        }

        // Recurse into children
        foreach ($children as $child) {
            $this->resolveLeafUnits($child['unit_id'], $leafUnits);
        }
    }

    // ================================================================
    // Pair lances from each side
    // Larger side distributes extras round-robin
    // ================================================================
    protected function pairLances(array $attackers, array $defenders): array
    {
        // Group into lances by parent_unit_id
        $attackerLances = $this->groupByLance($attackers);
        $defenderLances = $this->groupByLance($defenders);

        // Sort each side by average as_mv descending (fastest first)
        $attackerLances = $this->sortLancesBySpeed($attackerLances);
        $defenderLances = $this->sortLancesBySpeed($defenderLances);

        $pairings     = [];
        $aCount       = count($attackerLances);
        $dCount       = count($defenderLances);
        $maxPairings  = max($aCount, $dCount);

        // Build base pairings using smaller side as anchor
        for ($i = 0; $i < min($aCount, $dCount); $i++) {
            $pairings[] = [
                'attackers' => $attackerLances[$i],
                'defenders' => $defenderLances[$i],
            ];
        }

        // Distribute extras round-robin into existing pairings
        if ($aCount > $dCount) {
            for ($i = $dCount; $i < $aCount; $i++) {
                $targetPairing = $i % $dCount;
                foreach ($attackerLances[$i] as $unit) {
                    $pairings[$targetPairing]['attackers'][] = $unit;
                }
            }
        } elseif ($dCount > $aCount) {
            for ($i = $aCount; $i < $dCount; $i++) {
                $targetPairing = $i % $aCount;
                foreach ($defenderLances[$i] as $unit) {
                    $pairings[$targetPairing]['defenders'][] = $unit;
                }
            }
        }

        return $pairings;
    }

    protected function groupByLance(array $combatants): array
    {
        $lances = [];
        foreach ($combatants as $c) {
            $key = $c['unit']['parent_unit_id'] ?? $c['unit']['unit_id'];
            $lances[$key][] = $c;
        }
        return array_values($lances);
    }

    protected function sortLancesBySpeed(array $lances): array
    {
        usort($lances, function ($a, $b) {
            $aSpeed = $this->averageMv($a);
            $bSpeed = $this->averageMv($b);
            return $bSpeed <=> $aSpeed; // descending
        });
        return $lances;
    }

    protected function averageMv(array $combatants): float
    {
        if (empty($combatants)) return 0;
        $total = 0;
        $count = 0;
        foreach ($combatants as $c) {
            if ($c['is_infantry']) {
                $total += 2; // foot infantry base MV
                $count++;
                continue;
            }
            $mv = (float)($c['equipment']['as_mv'] ?? 0);
            if (($c['equipment']['combat_status'] ?? 'Operational') === 'Crippled') {
                $mv /= 2;
            }
            $total += $mv;
            $count++;
        }
        return $count > 0 ? $total / $count : 0;
    }

    protected function extractArtillery(array $combatants): array
    {
        $artillery = [];
        foreach ($combatants as $c) {
            if (!$c['is_infantry'] && isset($c['equipment']['specials']['ART'])) {
                $artillery[] = $c;
            }
        }
        return $artillery;
    }

    protected function resolveOneSideAttacks(
        array  $shooters,
        array  &$targets,   // ← pass by reference
        array  $mission,
        string $phase,
        int    $round,
        array  $terrain,
        string $shooterSide
    ): void {
        if (empty($shooters) || empty($targets)) return;

        foreach ($shooters as $shooter) {
            if ($shooter['retreated']) continue;
            if (
                !$shooter['is_infantry'] &&
                ($shooter['equipment']['combat_status'] ?? 'Operational') === 'Destroyed'
            ) continue;

            // Refresh active targets each shot
            $activeTargets = array_values(array_filter($targets, function ($t) {
                if ($t['out_of_combat']) return false;
                if ($t['retreated']) return false;
                if ($t['is_infantry']) return $t['strength'] > 0;
                $ps = $t['pool_status'] ?? 'Active';
                if (in_array($ps, ['Destroyed', 'Retreated', 'Routed'])) return false;
                return true;
            }));

            if (empty($activeTargets)) return;

            $target    = $this->pickTarget($shooter, $activeTargets, 0);

            $this->resolveAttack($shooter, $target, $mission, $phase, $round, $terrain);

            // Sync updated target state back into $targets array immediately
            foreach ($targets as &$t) {
                if ($t['is_infantry'] && $t['unit']['unit_id'] === $target['unit']['unit_id']) {
                    $t['strength']      = $target['strength'];
                    $t['retreated']     = $target['retreated'];
                    $t['out_of_combat'] = $target['out_of_combat'];
                    $t['pool_status']   = $target['pool_status'] ?? $t['pool_status'];
                } elseif (
                    !$t['is_infantry'] &&
                    isset($t['equipment']['equipment_id']) &&
                    $t['equipment']['equipment_id'] === ($target['equipment']['equipment_id'] ?? null)
                ) {
                    $t['equipment']['combat_status']     = $target['equipment']['combat_status'];
                    $t['equipment']['current_armor']     = $target['equipment']['current_armor'];
                    $t['equipment']['current_structure'] = $target['equipment']['current_structure'];
                    $t['retreated']                      = $target['retreated'];
                    $t['out_of_combat']                  = $target['out_of_combat'];
                    $t['pool_status']                    = $target['pool_status'] ?? $t['pool_status'];
                }
            }
            unset($t);
        }
    }

    // ================================================================
    // Pick target based on role priority
    // ================================================================
    protected function pickTarget(array $shooter, array $targets, int $fallbackIdx): array
    {
        if (empty($targets)) return $targets[0];

        $role = $shooter['unit']['role'] ?? 'Brawler';

        // Score each target
        $scored = [];
        foreach ($targets as $i => $target) {
            $score = 0;
            $targetRole = $target['unit']['role'] ?? '';

            switch ($role) {
                case 'Juggernaut':
                case 'Brawler':
                    // Target highest structure (wants to brawl toughest)
                    $score = $target['is_infantry']
                        ? $target['strength']
                        : (($target['equipment']['current_structure'] ?? 0));
                    break;
                case 'Sniper':
                    // Target highest damage output
                    $score = $target['is_infantry'] ? 5 :
                        max(
                            ($target['equipment']['as_dmg_s'] ?? 0),
                            ($target['equipment']['as_dmg_m'] ?? 0),
                            ($target['equipment']['as_dmg_l'] ?? 0)
                        );
                    break;
                case 'Striker':
                    // Target lowest armor (easy kills)
                    $score = $target['is_infantry']
                        ? -$target['strength']
                        : - (($target['equipment']['current_armor'] ?? 999));
                    break;
                case 'Scout':
                    // Target artillery/support first, then light units
                    $score = isset($target['equipment']['specials']['IF']) ? 100 : 0;
                    $score += $target['is_infantry'] ? 5 : (10 - ($target['equipment']['as_size'] ?? 5));
                    break;
                default:
                    $score = $i === $fallbackIdx ? 1 : 0;
            }

            // Bonus for already-crippled targets (finish them off)
            if (
                !$target['is_infantry'] &&
                ($target['equipment']['combat_status'] ?? '') === 'Crippled'
            ) {
                $score += 20;
            }
            // Small random noise to break ties and distribute focus
            $score += (random_int(0, 100) / 1000);
            $scored[] = ['target' => $target, 'score' => $score];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        return $scored[0]['target'];
    }

    // ================================================================
    // Resolve a single attack
    // ================================================================
    protected function resolveAttack(
        array  $shooter,
        array  &$target,
        array  $mission,
        string $phase,
        int    $round,
        array  $terrain
    ): void {
        $missionId = $mission['mission_id'];

        if ($shooter['is_shutdown'] ?? false) return;

        // Get base damage for this phase/round
        $baseDamage = $this->getBaseDamage($shooter, $phase, $round);
        if ($baseDamage <= 0) return;

        // Apply special ability bonuses to damage
        $specials = $shooter['is_infantry'] ? [] : ($shooter['equipment']['specials'] ?? []);
        $ovBonus = $this->checkAndApplyOV($shooter, $target, $phase, $round);
        $damage   = $this->applySpecialDamageBonus($baseDamage, $specials, $phase, $round);

        if ($ovBonus > 0) {
            $isLRange = ($phase === 'Skirmish' && $round < (int)$this->setting('skirmish_l_to_m_round', 3));
            if (!$isLRange || isset($specials['OVL'])) {
                $damage += $ovBonus;
            }
        }

        // Roll to hit
        $hit = $this->rollToHit($shooter, $target, $terrain);

        $shooterName = $this->getCombatantName($shooter);
        $targetName  = $this->getCombatantName($target);

        if (!$hit) {
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Attack',
                "{$shooterName} fires at {$targetName} — MISS.",
                $shooter['is_infantry'] ? null : ($shooter['equipment']['equipment_id'] ?? null),
                $target['is_infantry']  ? null : ($target['equipment']['equipment_id']  ?? null),
                0
            );
            return;
        }

        // Apply terrain modifiers for infantry targets
        if ($target['is_infantry']) {
            $isSoftAttack = isset($specials['HT']);
            if ($isSoftAttack) {
                $damage *= (float)$this->setting('infantry_ht_multiplier', 1.5);
            } else {
                $damage *= $terrain['hard_attack_modifier'];
                // BM with no HT in urban/fortified
                if (
                    !$shooter['is_infantry'] &&
                    ($shooter['equipment']['as_type'] ?? '') === 'BM' &&
                    !isset($specials['HT']) &&
                    in_array($terrain['type'], ['Urban', 'Dense Urban'])
                ) {
                    $damage *= (float)$this->setting('infantry_bm_urban_multiplier', 0.5);
                }
            }
        }

        //$damage = max(0, round($damage, 1));

        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'Attack',
            "{$shooterName} hits {$targetName} for {$damage} damage."
                . ($ovBonus > 0 ? " [OV+{$ovBonus}]" : ''),
            $shooter['is_infantry'] ? null : ($shooter['equipment']['equipment_id'] ?? null),
            $target['is_infantry']  ? null : ($target['equipment']['equipment_id']  ?? null),
            $damage
        );

        // Apply damage
        if ($target['is_turret'] ?? false) {
            $this->applyTurretDamage($target, $damage, $mission, $phase, $round);
        } elseif ($target['is_infantry']) {
            $this->applyInfantryDamage($target, $damage, $mission, $phase, $round);
        } else {
            $this->applyEquipmentDamage($target, $damage, $mission, $phase, $round);
        }

        // Morale damage — not for turrets
        if (!($target['is_turret'] ?? false)) {
            $this->applyMoraleDamage($target, $damage, $mission, $phase, $round);
        }
    }

    // ================================================================
    // Get base damage for phase and round
    // ================================================================
    protected function getBaseDamage(array $combatant, string $phase, int $round): float
    {
        if ($combatant['is_infantry']) {
            // Infantry only uses S damage equivalent (headcount-based)
            $strength = $combatant['strength'] ?? 0;
            $maxStr   = $combatant['max_strength'] ?? max(1, $strength);
            $ratio    = $maxStr > 0 ? $strength / $maxStr : 0;

            // Strength penalties
            if ($ratio <= 0.25)      return 0; // Broken — no damage
            elseif ($ratio <= 0.50)  $penalty = -2;
            elseif ($ratio <= 0.75)  $penalty = -1;
            else                     $penalty = 0;

            return max(0, 1 + $penalty); // Base infantry damage = 1
        }

        $eq = $combatant['equipment'];
        $lToMRound = (int)$this->setting('skirmish_l_to_m_round', 3);
        $mToSRound = (int)$this->setting('melee_m_to_s_round', 2);

        return match (true) {
            $phase === 'Skirmish' && $round < $lToMRound  => (float)($eq['as_dmg_l'] ?? 0),
            $phase === 'Skirmish' && $round >= $lToMRound => (float)($eq['as_dmg_m'] ?? 0),
            $phase === 'Melee'   && $round < $mToSRound   => (float)($eq['as_dmg_m'] ?? 0),
            $phase === 'Melee'   && $round >= $mToSRound  => (float)($eq['as_dmg_s'] ?? 0),
            $phase === 'Pursuit' && $combatant['side'] === 'attacker' => (float)($eq['as_dmg_s'] ?? 0),
            $phase === 'Pursuit' && $combatant['side'] === 'defender' => (float)($eq['as_dmg_m'] ?? 0),
            default => (float)($eq['as_dmg_s'] ?? 0),
        };
    }

    // ================================================================
    // OV check — pilot decides whether to push heat for extra damage
    // ================================================================
    protected function checkAndApplyOV(array &$shooter, array $target, string $phase, int $round): float
    {
        if ($shooter['is_infantry'] || ($shooter['is_turret'] ?? false)) return 0;
        if ($shooter['used_ov']) return 0;
        if ($shooter['is_shutdown']) return 0;

        $eq    = $shooter['equipment'];
        $ovMax = (int)($eq['as_ov'] ?? 0);
        if ($ovMax <= 0) return 0;

        $crew       = $shooter['crew'];
        $experience = $crew['experience'] ?? 'Regular';
        $heat       = $shooter['heat_buildup'];

        // Base chance to use any OV at all by experience
        $baseOvChance = match ($experience) {
            'Elite'   => 70,
            'Veteran' => 50,
            'Regular' => 30,
            'Green'   => 10,
            default   => 30,
        };

        if (random_int(0, 100) > $baseOvChance) return 0;

        // Determine how many OV points to actually use
        // Pilots prefer not to push into shutdown territory
        $ovToUse = 0;
        for ($points = 1; $points <= $ovMax; $points++) {
            $wouldShutdown = ($heat + $points) >= 4;

            if ($wouldShutdown) {
                // Only push into shutdown if target can be killed
                $targetHealth = $target['is_infantry']
                    ? $target['strength']
                    : (($target['equipment']['current_armor'] ?? 0) + ($target['equipment']['current_structure'] ?? 0));

                $baseDamage        = $this->getBaseDamage($shooter, $phase, $round);
                $potentialDamage   = $baseDamage + $points;

                if ($potentialDamage < $targetHealth) break; // can't kill it, stop here

                $shutdownChance = match ($experience) {
                    'Elite'   => 60,
                    'Veteran' => 40,
                    'Regular' => 15,
                    'Green'   => 5,
                    default   => 15,
                };

                if (random_int(0, 100) <= $shutdownChance) {
                    $ovToUse = $points;
                }
                break; // don't consider higher OV points once shutdown threshold reached
            }

            // Safe to use this point — experienced pilots more willing to push
            $safeChance = match ($experience) {
                'Elite'   => 90,
                'Veteran' => 75,
                'Regular' => 50,
                'Green'   => 20,
                default   => 50,
            };

            if (random_int(0, 100) <= $safeChance) {
                $ovToUse = $points;
            } else {
                break; // pilot decided not to push further
            }
        }

        if ($ovToUse <= 0) return 0;

        // Apply OV
        $shooter['used_ov']      = true;
        $shooter['heat_buildup'] += $ovToUse;

        $this->db->table('combat_pool')
            ->where('pool_id', $shooter['pool_id'])
            ->update([
                'used_ov'      => 1,
                'heat_buildup' => $shooter['heat_buildup'],
            ]);

        return (float)$ovToUse;
    }

    // ================================================================
    // Roll to hit — returns bool
    // ================================================================
    protected function rollToHit(array $shooter, array $target, array $terrain): bool
    {
        $baseToHit = (int)$this->setting('base_to_hit', 7);

        // 2d6 roll
        $roll = random_int(1, 6) + random_int(1, 6);

        // Modifiers that increase required roll (harder to hit)
        $modifier = 0;

        // Target TMM
        if (!$target['is_infantry']) {
            $modifier += (int)($target['equipment']['as_tmm'] ?? 0);
        }

        // Shutdown mech — much easier to hit
        if (!$target['is_infantry'] && ($target['is_shutdown'] ?? false)) {
            $modifier -= 4;
        }

        // Turret TMM
        if ($target['is_turret'] ?? false) {
            $modifier += (int)($target['equipment']['as_tmm'] ?? 0);
        }

        // Attacker experience (better pilots hit more easily)
        $experience = $shooter['crew']['experience'] ?? 'Regular';
        $modifier += match ($experience) {
            'Elite'   => -2,
            'Veteran' => -1,
            'Regular' =>  0,
            'Green'   =>  1,
            default   =>  0,
        };

        // Target crippled = easier to hit
        if (
            !$target['is_infantry'] &&
            ($target['equipment']['combat_status'] ?? 'Operational') === 'Crippled'
        ) {
            $modifier -= 1;
        }

        // Terrain
        $modifier += $terrain['to_hit_modifier'];

        // Infantry in fortification
        if ($target['is_infantry']) {
            $fortBonus = $this->getFortificationBonus($target, $missionId ?? 0);
            $modifier += $fortBonus;
        }

        return $roll >= ($baseToHit + $modifier);
    }

    protected function getFortificationBonus(array $target, int $missionId): int
    {
        $unitId = $target['unit']['unit_id'] ?? null;
        if (!$unitId) return 0;

        $assignment = $this->db->query("
            SELECT cb.current_integrity, cb.max_integrity, cb.status
            FROM fortification_assignments fa
            JOIN combat_buildings cb ON cb.combat_building_id = fa.combat_building_id
            WHERE fa.unit_id = {$unitId}
            AND fa.mission_id = {$missionId}
            AND cb.status != 'Destroyed'
            LIMIT 1
        ")->getRowArray();

        if (!$assignment) return 0;

        $integrityPct = $assignment['max_integrity'] > 0
            ? ($assignment['current_integrity'] / $assignment['max_integrity']) * 100
            : 0;

        return $integrityPct > 50 ? 2 : 1;
    }

    // ================================================================
    // Apply damage to equipment (mech/vehicle)
    // ================================================================
    protected function applyEquipmentDamage(
        array  &$target,
        float  $damage,
        array  $mission,
        string $phase,
        int    $round
    ): void {
        $eq        = $target['equipment'];
        $eqId      = $eq['equipment_id'];
        $missionId = $mission['mission_id'];

        $fresh = $this->db->table('combat_pool')
            ->select('current_armor, current_structure, status AS combat_status')
            ->where('pool_id', $target['pool_id'])
            ->get()->getRowArray();

        if (!$fresh || $fresh['combat_status'] === 'Destroyed') return;

        $currentArmor     = (int)$eq['current_armor'];
        $currentStructure = (int)$eq['current_structure'];
        $maxStructure     = (int)$eq['max_structure'];
        $intDamage        = (int)ceil($damage);

        // Apply to armor first, overflow to structure
        if ($intDamage <= $currentArmor) {
            $currentArmor -= $intDamage;
        } else {
            $overflow         = $intDamage - $currentArmor;
            $currentArmor     = 0;
            $currentStructure = max(0, $currentStructure - $overflow);
        }

        $newStatus = $fresh['combat_status'] ?? 'Operational';

        // Check crippled threshold
        if ($currentStructure <= ($maxStructure / 2) && $newStatus === 'Operational') {
            $newStatus = 'Crippled';

            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Crippled',
                $this->getCombatantName($target) . " is CRIPPLED — movement and effectiveness reduced.",
                null,
                $eqId
            );

            // Update combat pool
            $this->db->table('combat_pool')
                ->where('mission_id', $missionId)
                ->where('equipment_id', $eqId)
                ->update(['status' => 'Crippled']);
            $target['pool_status'] = 'Crippled';

            $this->rollEjection($target, 'crippled', $mission, $phase, $round);
        }

        // Check destroyed
        if ($currentStructure <= 0) {
            $newStatus        = 'Destroyed';
            $currentStructure = 0;

            // Capture structure just before killing blow for salvage calc
            $structureAtDeath = max(1, (int)$fresh['current_structure']);

            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Destroyed',
                $this->getCombatantName($target) . " is DESTROYED.",
                null,
                $eqId
            );

            // Update combat pool — mark destroyed, store pre-kill structure
            $this->db->table('combat_pool')
                ->where('mission_id', $missionId)
                ->where('equipment_id', $eqId)
                ->update([
                    'status'             => 'Destroyed',
                    'structure_at_death' => $structureAtDeath,
                ]);
            $target['pool_status'] = 'Destroyed';
            $target['out_of_combat'] = true;  // exclude from targeting only
            $this->applyFriendlyDestroyedPenalty($target, $missionId, $phase, $round);
            $this->rollEjection($target, 'destroyed', $mission, $phase, $round);
            $this->handleDestroyed($target, $mission);
        }

        $this->db->table('combat_pool')
            ->where('pool_id', $target['pool_id'])
            ->update([
                'current_armor'     => $currentArmor,
                'current_structure' => $currentStructure,
            ]);

        // Update local array for this tick
        $target['equipment']['current_armor']     = $currentArmor;
        $target['equipment']['current_structure'] = $currentStructure;
        $target['equipment']['combat_status']     = $newStatus;
    }

    // ================================================================
    // Apply damage to infantry (casualty-based)
    // ================================================================
    protected function applyInfantryDamage(
        array  &$target,
        float  $damage,
        array  $mission,
        string $phase,
        int    $round
    ): void {
        $missionId  = $mission['mission_id'];
        $unitId     = $target['unit']['unit_id'];
        $casualties = max(1, (int)round($damage));
        $kiaChance  = (float)$this->setting('infantry_kia_chance', 30) / 100;

        // Get active personnel for this unit
        $personnel = $this->db->query("
            SELECT p.personnel_id
            FROM personnel_assignments pa
            JOIN personnel p ON p.personnel_id = pa.personnel_id
            WHERE pa.unit_id = {$unitId}
            AND pa.date_released IS NULL
            AND p.status = 'Active'
            ORDER BY RAND()
            LIMIT {$casualties}
        ")->getResultArray();

        $kiaCount     = 0;
        $injuredCount = 0;

        foreach ($personnel as $p) {
            if ((random_int(0, 100) / 100) < $kiaChance) {
                $this->db->table('personnel')
                    ->where('personnel_id', $p['personnel_id'])
                    ->update(['status' => 'KIA']);
                $kiaCount++;
            } else {
                $this->db->table('personnel')
                    ->where('personnel_id', $p['personnel_id'])
                    ->update(['status' => 'Injured']);
                $injuredCount++;
            }
        }

        $target['strength'] = max(0, $target['strength'] - $casualties);

        $unitName = $target['unit']['name'];
        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'Damage',
            "{$unitName} takes {$casualties} casualties ({$kiaCount} KIA, {$injuredCount} injured). Strength: {$target['strength']}/{$target['max_strength']}.",
            null,
            null,
            $damage
        );

        // Check route threshold — starts at 50% casualties, uses leader morale/experience
        $casualtyRatio = $target['max_strength'] > 0
            ? 1 - ($target['strength'] / $target['max_strength'])
            : 1;

        if ($target['strength'] <= 0 && !$target['retreated']) {
            // Wiped out — auto rout regardless of morale
            $this->routeInfantryUnit($target, $mission, $phase, $round);
            return;
        }
        if ($casualtyRatio >= 0.50 && !$target['retreated']) {
            $this->checkInfantryRout($target, $mission, $phase, $round);
        }
    }

    protected function applyTurretDamage(
        array  &$target,
        float  $damage,
        array  $mission,
        string $phase,
        int    $round
    ): void {
        $combatBuildingId = $target['equipment']['combat_building_id'];
        $missionId        = $mission['mission_id'];
        $intDamage        = (int)ceil($damage);

        $fresh = $this->combatBuildingsModel->find($combatBuildingId);

        if (!$fresh || $fresh['status'] === 'Destroyed') return;

        // Apply to armor first if present, overflow to integrity
        $currentArmor    = (int)($fresh['current_armor'] ?? 0);
        $currentIntegrity = (int)$fresh['current_integrity'];
        $maxIntegrity     = (int)$fresh['max_integrity'];

        if ($currentArmor > 0) {
            if ($intDamage <= $currentArmor) {
                $currentArmor -= $intDamage;
                $intDamage = 0;
            } else {
                $intDamage   -= $currentArmor;
                $currentArmor = 0;
            }
        }

        if ($intDamage > 0) {
            $currentIntegrity = max(0, $currentIntegrity - $intDamage);
        }

        // Determine new status
        $integrityPct = $maxIntegrity > 0 ? ($currentIntegrity / $maxIntegrity) * 100 : 0;
        $newStatus = match (true) {
            $currentIntegrity <= 0  => 'Destroyed',
            $integrityPct <= 50     => 'Damaged',
            default                 => 'Operational',
        };

        $this->combatBuildingsModel->update($combatBuildingId, [
            'current_armor'     => $currentArmor,
            'current_integrity' => $currentIntegrity,
            'status'            => $newStatus,
        ]);

        // Update local array
        $target['equipment']['current_armor']     = $currentArmor;
        $target['equipment']['current_structure'] = $currentIntegrity;
        $target['equipment']['combat_status']     = $newStatus === 'Destroyed' ? 'Destroyed'
            : ($newStatus === 'Damaged' ? 'Crippled' : 'Operational');
        $target['pool_status'] = $newStatus === 'Destroyed' ? 'Destroyed' : $target['pool_status'];

        if ($newStatus === 'Destroyed') {
            $target['out_of_combat'] = true;
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Destroyed',
                "{$target['unit']['name']} has been DESTROYED."
            );
        } elseif ($newStatus === 'Damaged' && $fresh['status'] === 'Operational') {
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Crippled',
                "{$target['unit']['name']} is DAMAGED — effectiveness reduced."
            );
        }
    }

    protected function checkInfantryRout(array &$target, array $mission, string $phase, int $round): void
    {
        $crew = $target['crew'];
        if (!$crew) {
            $this->routeInfantryUnit($target, $mission, $phase, $round);
            return;
        }

        $experience = $crew['experience'] ?? 'Regular';
        $morale     = (float)$crew['morale'];

        $threshold = (float)match ($experience) {
            'Green'   => $this->setting('retreat_threshold_green',   40),
            'Regular' => $this->setting('retreat_threshold_regular', 30),
            'Veteran' => $this->setting('retreat_threshold_veteran', 20),
            'Elite'   => $this->setting('retreat_threshold_elite',   15),
            default   => 30,
        };

        if ($morale < $threshold) {
            $chanceMult = (float)match ($experience) {
                'Green'   => $this->setting('retreat_chance_green',   3.0),
                'Regular' => $this->setting('retreat_chance_regular', 2.0),
                'Veteran' => $this->setting('retreat_chance_veteran', 1.5),
                'Elite'   => $this->setting('retreat_chance_elite',   1.0),
                default   => 2.0,
            };

            $routeChance = ($threshold - $morale) * $chanceMult;
            if (random_int(0, 100) < $routeChance) {
                $this->routeInfantryUnit($target, $mission, $phase, $round);
            }
        }
    }

    protected function routeInfantryUnit(array &$target, array $mission, string $phase, int $round): void
    {
        $target['retreated'] = true;
        $target['out_of_combat'] = true;
        $unitName = $target['unit']['name'];

        $this->battleLog->record(
            $mission['mission_id'],
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'Retreat',
            "{$unitName} has ROUTED — unit broken."
        );

        $this->db->table('combat_pool')
            ->where('mission_id', $mission['mission_id'])
            ->where('unit_id', $target['unit']['unit_id'])
            ->where('participant_type', 'infantry')
            ->update(['status' => 'Routed']);

        // Apply friendly morale penalty if squad is wiped out entirely
        if ($target['strength'] <= 0) {
            $this->applyFriendlyDestroyedPenalty(
                $target,
                $mission['mission_id'],
                $phase,
                $round
            );
        }
    }

    // ================================================================
    // Apply morale damage to crew after taking a hit
    // ================================================================
    protected function applyMoraleDamage(
        array  &$target,
        float  $damage,
        array  $mission,
        string $phase,
        int    $round
    ): void {
        $crew = $target['crew'];
        if (!$crew) return;

        $experience = $crew['experience'] ?? 'Regular';
        $multiplier = (float)match ($experience) {
            'Green'   => $this->setting('morale_loss_green',   2.0),
            'Regular' => $this->setting('morale_loss_regular', 1.5),
            'Veteran' => $this->setting('morale_loss_veteran', 1.0),
            'Elite'   => $this->setting('morale_loss_elite',   0.5),
            default   => 1.5,
        };

        $globalMult  = (float)$this->setting('combat_morale_loss_multiplier', 1.0);
        $moraleLoss  = $damage * $multiplier * $globalMult;
        $newMorale   = max(0, (float)$crew['morale'] - $moraleLoss);

        $this->db->table('personnel')
            ->where('personnel_id', $crew['personnel_id'])
            ->update(['morale' => $newMorale]);
        // Keep pool in sync immediately
        if (!$target['is_infantry'] && isset($target['equipment']['equipment_id'])) {
            $this->db->table('combat_pool')
                ->where('mission_id', $mission['mission_id'])
                ->where('equipment_id', $target['equipment']['equipment_id'])
                ->update(['pilot_morale' => $newMorale]);
        } elseif ($target['is_infantry']) {
            $this->db->table('combat_pool')
                ->where('mission_id', $mission['mission_id'])
                ->where('unit_id', $target['unit']['unit_id'])
                ->where('participant_type', 'infantry')
                ->update(['pilot_morale' => $newMorale]);
            $target['crew']['morale'] = $newMorale;  // ← keep local array in sync
        }

        // Check retreat threshold
        $threshold = (float)match ($experience) {
            'Green'   => $this->setting('retreat_threshold_green',   40),
            'Regular' => $this->setting('retreat_threshold_regular', 30),
            'Veteran' => $this->setting('retreat_threshold_veteran', 20),
            'Elite'   => $this->setting('retreat_threshold_elite',   15),
            default   => 30,
        };

        if ($newMorale < $threshold && !$target['retreated']) {
            $chanceMult = (float)match ($experience) {
                'Green'   => $this->setting('retreat_chance_green',   3.0),
                'Regular' => $this->setting('retreat_chance_regular', 2.0),
                'Veteran' => $this->setting('retreat_chance_veteran', 1.5),
                'Elite'   => $this->setting('retreat_chance_elite',   1.0),
                default   => 2.0,
            };
            $retreatChance = ($threshold - $newMorale) * $chanceMult;
            $roll          = random_int(0, 100);

            if ($roll < $retreatChance) {
                $target['retreated'] = true;
                $target['pool_status'] = 'Retreated';
                $target['out_of_combat'] = true;
                $name = $this->getCombatantName($target);
                $this->battleLog->record(
                    $mission['mission_id'],
                    $this->gameDate,
                    $this->gameHour,
                    $phase,
                    $round,
                    'Retreat',
                    "{$name} RETREATS — morale failure ({$newMorale}%)."
                );

                // Update combat pool — persistent retreat flag
                $this->db->table('combat_pool')
                    ->where('pool_id', $target['pool_id'])
                    ->update([
                        'status'       => 'Retreated',    // ← this was missing
                        'pilot_morale' => $newMorale,
                    ]);
            }
        }
    }

    // ================================================================
    // Artillery support
    // ================================================================
    protected function resolveArtillery(
        array  $artilleryUnits,
        array  &$targets,
        array  $mission,
        string $phase,
        int    $round,
        array  $terrain
    ): void {
        if (empty($artilleryUnits)) return;

        $missionId = $mission['mission_id'];

        foreach ($artilleryUnits as $arty) {
            $specials = $arty['equipment']['specials'] ?? [];
            if (!isset($specials['ART'])) continue;

            $artType    = $specials['ART']['type'] ?? null;
            $artAttacks = (int)($specials['ART']['attacks'] ?? 1);

            if (!$artType || !isset($this->artilleryTypes[$artType])) continue;

            $artData = $this->artilleryTypes[$artType];

            for ($i = 0; $i < $artAttacks; $i++) {
                // Build target list — artillery can hit units OR buildings
                $activeTargets = array_values(array_filter($targets, function ($t) {
                    if ($t['out_of_combat']) return false;
                    if ($t['is_infantry']) return $t['strength'] > 0;
                    $ps = $t['pool_status'] ?? 'Active';
                    if (in_array($ps, ['Destroyed', 'Retreated', 'Routed'])) return false;
                    return true;
                }));

                // Also get targetable buildings (fortifications)
                $targetBuildings = $this->combatBuildingsModel->getActiveFortifications($missionId);

                // Weight infantry and buildings as preferred targets
                // Artillery prioritizes fortifications and infantry
                $buildingTarget = null;
                if (!empty($targetBuildings) && random_int(0, 100) < 60) {
                    $buildingTarget = $targetBuildings[array_rand($targetBuildings)];
                }

                if ($buildingTarget) {
                    $this->resolveArtilleryVsBuilding(
                        $arty,
                        $buildingTarget,
                        $artData,
                        $mission,
                        $phase,
                        $round
                    );
                } elseif (!empty($activeTargets)) {
                    $target = $activeTargets[array_rand($activeTargets)];
                    $this->resolveArtilleryVsUnit(
                        $arty,
                        $target,
                        $artData,
                        $mission,
                        $phase,
                        $round,
                        $terrain
                    );

                    // Sync target state back
                    foreach ($targets as &$t) {
                        if (
                            !$t['is_infantry'] &&
                            isset($t['equipment']['equipment_id']) &&
                            $t['equipment']['equipment_id'] === ($target['equipment']['equipment_id'] ?? null)
                        ) {
                            $t['equipment']['combat_status']     = $target['equipment']['combat_status'];
                            $t['equipment']['current_armor']     = $target['equipment']['current_armor'];
                            $t['equipment']['current_structure'] = $target['equipment']['current_structure'];
                            $t['out_of_combat']                  = $target['out_of_combat'];
                            $t['pool_status']                    = $target['pool_status'];
                        }
                    }
                    unset($t);
                }
            }
        }
    }

    // ================================================================
    // Artillery to-hit roll — separate from standard rollToHit()
    // ================================================================
    protected function rollArtilleryToHit(
        array  $arty,
        mixed  $target,      // combatant array OR building array
        array  $artData,
        array  $terrain,
        bool   $isBuilding = false,
        bool   $isInfantry = false,
        bool   $isFortified = false
    ): bool {
        $baseToHit  = (int)$this->setting('base_to_hit', 7);
        $roll       = random_int(1, 6) + random_int(1, 6);
        $modifier   = 0;

        // Cannon types use their own modifier
        $cannonTypes = ['TC', 'SC', 'LTC'];
        if (in_array($artData['special_code'], $cannonTypes)) {
            $modifier += (int)$this->setting('artillery_cannon_modifier', 2);
        } elseif ($isBuilding) {
            $modifier += (int)$this->setting('artillery_modifier_building', -2);
        } elseif ($isInfantry && $isFortified) {
            $modifier += (int)$this->setting('artillery_modifier_infantry_fort', 2);
            // Artillery ignores fortification to-hit bonus entirely
        } elseif ($isInfantry) {
            $modifier += (int)$this->setting('artillery_modifier_infantry_open', 0);
        } else {
            $modifier += (int)$this->setting('artillery_modifier_mech', 4);
        }

        // IF special — ignore terrain to-hit penalties
        $specials = $arty['equipment']['specials'] ?? [];
        $hasIF    = isset($specials['IF']);
        if (!$hasIF) {
            $modifier += $terrain['to_hit_modifier'];
        }

        // Pilot experience
        $experience = $arty['crew']['experience'] ?? 'Regular';
        $modifier  += match ($experience) {
            'Elite'   => -2,
            'Veteran' => -1,
            'Regular' =>  0,
            'Green'   =>  1,
            default   =>  0,
        };

        // Target TMM for mechs/vehicles
        if (!$isBuilding && !$isInfantry && is_array($target)) {
            $modifier += (int)($target['equipment']['as_tmm'] ?? 0);
        }

        return $roll >= ($baseToHit + $modifier);
    }

    protected function resolveArtilleryVsUnit(
        array  $arty,
        array  &$target,
        array  $artData,
        array  $mission,
        string $phase,
        int    $round,
        array  $terrain
    ): void {
        $missionId  = $mission['mission_id'];
        $artyName   = $this->getCombatantName($arty);
        $targetName = $this->getCombatantName($target);
        $damage     = (int)$artData['primary_damage'];

        if ($damage === 0 && $artData['min_roll']) {
            $roll   = random_int(1, 6);
            $damage = $roll >= $artData['min_roll'] ? 1 : 0;
        }

        // Determine if infantry target is fortified
        $isInfantry = $target['is_infantry'] ?? false;
        $isFortified = false;
        if ($isInfantry) {
            $isFortified = $this->getFortificationBonus($target, $mission['mission_id']) > 0;
        }

        $hit = $this->rollArtilleryToHit(
            $arty,
            $target,
            $artData,
            $terrain,
            false,
            $isInfantry,
            $isFortified
        );

        if (!$hit) {
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Attack',
                "{$artyName} fires artillery at {$targetName} — MISS. [ART-{$artData['special_code']}]",
                $arty['equipment']['equipment_id'] ?? null,
                $isInfantry ? null : ($target['equipment']['equipment_id'] ?? null),
                0
            );
            return;
        }

        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'Attack',
            "{$artyName} fires artillery at {$targetName} for {$damage} damage. [ART-{$artData['special_code']}]",
            $arty['equipment']['equipment_id'] ?? null,
            $isInfantry ? null : ($target['equipment']['equipment_id'] ?? null),
            $damage
        );

        if ($damage > 0) {
            if ($isInfantry) {
                $this->applyInfantryDamage($target, $damage, $mission, $phase, $round);
            } else {
                $this->applyEquipmentDamage($target, $damage, $mission, $phase, $round);
            }
            $this->applyMoraleDamage($target, $damage, $mission, $phase, $round);
        }
    }

    protected function resolveArtilleryVsBuilding(
        array  $arty,
        array  $building,
        array  $artData,
        array  $mission,
        string $phase,
        int    $round
    ): void {
        $missionId        = $mission['mission_id'];
        $combatBuildingId = (int)$building['combat_building_id'];
        $artyName         = $this->getCombatantName($arty);
        $damage           = (int)$artData['primary_damage'];

        if ($damage === 0 && $artData['min_roll']) {
            $roll   = random_int(1, 6);
            $damage = $roll >= $artData['min_roll'] ? 1 : 0;
        }

        // Buildings have no terrain cover — pass empty terrain
        $emptyTerrain = ['to_hit_modifier' => 0, 'type' => 'Plains', 'has_fortification' => false];

        $hit = $this->rollArtilleryToHit(
            $arty,
            $building,
            $artData,
            $emptyTerrain,
            true,
            false,
            false
        );

        if (!$hit) {
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Attack',
                "{$artyName} fires artillery at {$building['name']} — MISS. [ART-{$artData['special_code']}]",
                $arty['equipment']['equipment_id'] ?? null,
                null,
                0
            );
            return;
        }

        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'Attack',
            "{$artyName} fires artillery at {$building['name']} for {$damage} integrity damage. [ART-{$artData['special_code']}]",
            $arty['equipment']['equipment_id'] ?? null,
            null,
            $damage
        );

        if ($damage <= 0) return;

        $currentIntegrity = max(0, (int)$building['current_integrity'] - $damage);
        $maxIntegrity     = (int)$building['max_integrity'];
        $integrityPct     = $maxIntegrity > 0 ? ($currentIntegrity / $maxIntegrity) * 100 : 0;

        $newStatus = match (true) {
            $currentIntegrity <= 0 => 'Destroyed',
            $integrityPct <= 50    => 'Damaged',
            default                => 'Operational',
        };

        $this->combatBuildingsModel->update($combatBuildingId, [
            'current_integrity' => $currentIntegrity,
            'status'            => $newStatus,
        ]);

        if ($newStatus === 'Destroyed') {
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Destroyed',
                "{$building['name']} has been DESTROYED by artillery fire."
            );
        } elseif ($newStatus === 'Damaged' && $building['status'] === 'Operational') {
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Crippled',
                "{$building['name']} is DAMAGED — fortification bonus reduced."
            );
        }
    }

    // ================================================================
    // Ejection rolls
    // ================================================================
    protected function rollEjection(
        array  &$target,
        string $trigger,
        array  $mission,
        string $phase,
        int    $round
    ): void {
        if (($target['equipment']['as_type'] ?? '') !== 'BM') return;
        $crew      = $target['crew'];
        if (!$crew) return;

        $experience = $crew['experience'] ?? 'Regular';
        $missionId  = $mission['mission_id'];

        $chance = (int)match ([$trigger, $experience]) {
            ['crippled', 'Green']    => $this->setting('eject_crippled_green',    60),
            ['crippled', 'Regular']  => $this->setting('eject_crippled_regular',  35),
            ['crippled', 'Veteran']  => $this->setting('eject_crippled_veteran',  20),
            ['crippled', 'Elite']    => $this->setting('eject_crippled_elite',    10),
            ['destroyed', 'Green']   => $this->setting('eject_destroyed_green',   40),
            ['destroyed', 'Regular'] => $this->setting('eject_destroyed_regular', 55),
            ['destroyed', 'Veteran'] => $this->setting('eject_destroyed_veteran', 70),
            ['destroyed', 'Elite']   => $this->setting('eject_destroyed_elite',   80),
            default => 50,
        };

        $roll    = random_int(0, 100);
        $ejected = $roll < $chance;
        $name    = "{$crew['rank_abbr']}. {$crew['last_name']}";
        $mechName = $this->getCombatantName($target);

        if ($ejected) {
            $this->db->table('personnel')
                ->where('personnel_id', $crew['personnel_id'])
                ->update(['status' => 'Injured']);

            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Ejection',
                "{$name} ejects from {$mechName} — pilot survives."
            );

            // Always update pool final status for ejections
            $this->db->table('combat_pool')
                ->where('pool_id', $target['pool_id'])
                ->update([
                    'pilot_final_status' => 'Injured',
                    'pilot_morale'       => $crew['morale'],
                ]);

            if ($trigger === 'crippled') {
                $eqId       = $target['equipment']['equipment_id'];
                $locationId = $mission['destination_location_id'];

                $this->db->table('personnel_equipment')
                    ->where('personnel_id', $crew['personnel_id'])
                    ->where('date_released IS NULL', null, false)
                    ->update(['date_released' => $this->gameDate]);

                $this->db->table('equipment')
                    ->where('equipment_id', $eqId)
                    ->update([
                        'salvage_status' => 'Available',
                        'location_id'    => $locationId,
                    ]);

                $this->db->table('combat_pool')
                    ->where('pool_id', $target['pool_id'])
                    ->update([
                        'status'             => 'Retreated',
                        'pilot_final_status' => 'Injured',
                        'pilot_morale'       => 0,
                    ]);

                $target['retreated']     = true;
                $target['pool_status']   = 'Retreated';
                $target['out_of_combat'] = true;
            }
        } else {
            if ($trigger === 'destroyed') {
                $this->db->table('personnel')
                    ->where('personnel_id', $crew['personnel_id'])
                    ->update(['status' => 'KIA']);

                $this->db->table('personnel_equipment')
                    ->where('personnel_id', $crew['personnel_id'])
                    ->where('date_released IS NULL', null, false)
                    ->update(['date_released' => $this->gameDate]);

                $this->db->table('combat_pool')
                    ->where('pool_id', $target['pool_id'])
                    ->update([
                        'pilot_final_status' => 'KIA',
                        'pilot_morale'       => 0,
                    ]);

                $this->battleLog->record(
                    $missionId,
                    $this->gameDate,
                    $this->gameHour,
                    $phase,
                    $round,
                    'Ejection',
                    "{$name} did not eject from {$mechName} — pilot KIA."
                );
            }
        }
    }

    // ================================================================
    // Handle destroyed equipment — salvage
    // ================================================================
    protected function handleDestroyed(array $target, array $mission): void
    {
        $eqId       = $target['equipment']['equipment_id'];
        $locationId = $mission['destination_location_id'];

        $salvageChance = (int)$this->setting('salvage_base_chance', 60);
        $salvaged      = random_int(0, 100) < $salvageChance;

        $this->db->table('equipment')
            ->where('equipment_id', $eqId)
            ->update([
                'equipment_status' => 'Destroyed',
                'combat_status'    => 'Destroyed',
                'salvage_status'   => $salvaged ? 'Available' : 'None',
                'location_id'      => $salvaged ? $locationId : null,
            ]);
    }

    // ================================================================
    // Check battle end conditions
    // ================================================================
    protected function checkBattleEnd(
        array $mission,
        array $attackers,
        array $defenders,
        array $location
    ): bool {
        $missionId = $mission['mission_id'];

        $attackersAlive = $this->countAlive($attackers);
        $defendersAlive = $this->countAlive($defenders);

        if ($attackersAlive === 0 && $defendersAlive === 0) {
            // Mutual destruction — attacker loses, no control change
            $this->endCombat($mission, 'attacker_defeated', $location);
            return true;
        }

        if ($attackersAlive === 0) {
            // Attacker destroyed/retreated — defender wins
            $this->endCombat($mission, 'attacker_defeated', $location);
            return true;
        }

        if ($defendersAlive === 0) {
            // Defender destroyed/retreated — check for pursuit
            $this->endCombat($mission, 'defender_defeated', $location);
            return true;
        }

        return false;
    }

    protected function countAlive(array $combatants): int
    {
        $alive = 0;
        foreach ($combatants as $c) {
            if ($c['retreated']) continue;  // morale retreat = out of fight
            $ps = $c['pool_status'] ?? 'Active';
            if (in_array($ps, ['Destroyed', 'Retreated', 'Routed'])) continue;
            if ($c['is_infantry']) {
                if ($c['strength'] > 0) $alive++;
            } else {
                if (($c['equipment']['combat_status'] ?? 'Operational') !== 'Destroyed') $alive++;
            }
        }
        return $alive;
    }

    // ================================================================
    // End combat — trigger arrival or return logic
    // ================================================================
    protected function endCombat(array $mission, string $result, array $location): void
    {
        $missionId        = (int)$mission['mission_id'];
        $missionFactionId = (int)$mission['faction_id'];
        $locationId       = (int)$location['location_id'];
        $phase            = $mission['combat_phase'];
        $round            = (int)$mission['combat_round'];
        $eventLog         = new EventLogModel();
        $missionModel     = new MissionModel();
        $unitModel        = new UnitModel();

        // Log battle end
        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'BattleEnd',
            $result === 'defender_defeated'
                ? "Defenders defeated — attacking force victorious."
                : "Attacking force defeated — defenders hold {$location['name']}."
        );

        // ----------------------------------------------------------------
        // Determine winning faction
        // ----------------------------------------------------------------
        $winningFactionId = $result === 'defender_defeated'
            ? $missionFactionId
            : $this->getDefenderFactionId($locationId, $missionFactionId);

        // ----------------------------------------------------------------
        // Salvage — process all destroyed pool entries
        // ----------------------------------------------------------------
        $this->processSalvage($missionId, $locationId, $winningFactionId);

        // ----------------------------------------------------------------
        // KIA cleanup — null out location for all KIA personnel in battle
        // ----------------------------------------------------------------
        $this->processKiaCleanup($missionId);

        // ----------------------------------------------------------------
        // Attacker wins
        // ----------------------------------------------------------------
        if ($result === 'defender_defeated') {

            // Take location
            $this->db->table('locations')
                ->where('location_id', $locationId)
                ->update(['controlled_by' => $missionFactionId]);

            // Garrison all attacker surviving units at location
            $attackerUnitIds = $this->getPoolUnitIds($missionId, 'attacker');
            foreach ($attackerUnitIds as $unitId) {
                $this->db->table('units')
                    ->where('unit_id', $unitId)
                    ->update([
                        'status'      => 'Garrisoned',
                        'location_id' => $locationId,
                        'mission_id'  => null,
                    ]);
            }

            // Update attacker surviving equipment location
            $attackerEqIds = $this->getPoolEquipmentIds($missionId, 'attacker', ['Active', 'Crippled', 'Retreated']);
            if (!empty($attackerEqIds)) {
                $idList = implode(',', $attackerEqIds);
                $this->db->query("
                UPDATE equipment SET location_id = {$locationId}
                WHERE equipment_id IN ({$idList})
            ");
            }

            // Update attacker surviving personnel location
            $this->syncPersonnelLocation($missionId, 'attacker', $locationId);

            // Defender survivors — transfer to nearest friendly
            $defenderUnitIds = $this->getPoolUnitIds($missionId, 'defender');
            $defenderFactionId = $this->getDefenderFactionId($locationId, $missionFactionId);

            foreach ($defenderUnitIds as $unitId) {
                $nearest = $this->findNearestFriendlyLocation($locationId, $defenderFactionId);
                if (!$nearest) continue;

                $from        = $location;
                $distance    = $missionModel->calculateDistance(
                    (float)$from['coord_x'],
                    (float)$from['coord_y'],
                    (float)$nearest['coord_x'],
                    (float)$nearest['coord_y']
                );
                $transitHours = $missionModel->calculateTransitHours($distance, 64.0);
                $etaDate      = (new \DateTime($this->gameDate))
                    ->modify('+' . (int)ceil($transitHours / 24) . ' days');

                $newMissionId = $missionModel->createMission([
                    'name'                    => "Withdrawal",
                    'mission_type'            => 'Withdrawal',
                    'status'                  => 'In Transit',
                    'origin_location_id'      => $locationId,
                    'destination_location_id' => $nearest['location_id'],
                    'faction_id'              => $defenderFactionId,
                    'notes'                   => 'Forced withdrawal — location captured.',
                    'launched_date'           => $this->gameDate,
                    'eta_date'                => $etaDate->format('Y-m-d'),
                    'distance'                => $distance,
                    'transit_hours'           => $transitHours,
                    'hours_elapsed'           => 0,
                    'slowest_speed'           => 64.0,
                    'current_coord_x'         => $from['coord_x'],
                    'current_coord_y'         => $from['coord_y'],
                ], [$unitId]);

                $this->db->table('units')
                    ->where('unit_id', $unitId)
                    ->update([
                        'status'     => 'In Transit',
                        'mission_id' => $newMissionId,
                    ]);
            }

            // Mark original mission arrived
            $this->db->table('missions')
                ->where('mission_id', $missionId)
                ->update([
                    'status'       => 'Arrived',
                    'arrived_date' => $this->gameDate,
                    'combat_phase' => null,
                ]);

            $eventLog->log(
                $missionFactionId,
                $this->gameDate,
                'Combat',
                "Victory — {$mission['name']}",
                "Enemy garrison defeated at {$location['name']}. Location secured.",
                'Warning',
                null,
                $missionId,
                $locationId
            );

            // ----------------------------------------------------------------
            // Defender wins
            // ----------------------------------------------------------------
        } else {

            // Garrison all defender surviving units (they stay)
            $defenderUnitIds = $this->getPoolUnitIds($missionId, 'defender');
            foreach ($defenderUnitIds as $unitId) {
                $this->db->table('units')
                    ->where('unit_id', $unitId)
                    ->update([
                        'status'     => 'Garrisoned',
                        'mission_id' => null,
                    ]);
            }

            // Update defender surviving personnel location (unchanged, but ensure set)
            $this->syncPersonnelLocation($missionId, 'defender', $locationId);

            // Attacker survivors — return to origin
            $attackerUnitIds  = $this->getPoolUnitIds($missionId, 'attacker');
            $originLocationId = (int)$mission['origin_location_id'];
            $originLocation   = $this->db->table('locations')
                ->where('location_id', $originLocationId)
                ->get()->getRowArray();

            foreach ($attackerUnitIds as $unitId) {
                $distance    = $missionModel->calculateDistance(
                    (float)$location['coord_x'],
                    (float)$location['coord_y'],
                    (float)$originLocation['coord_x'],
                    (float)$originLocation['coord_y']
                );
                $transitHours = $missionModel->calculateTransitHours($distance, 64.0);
                $etaDate      = (new \DateTime($this->gameDate))
                    ->modify('+' . (int)ceil($transitHours / 24) . ' days');

                $newMissionId = $missionModel->createMission([
                    'name'                    => "Withdrawal",
                    'mission_type'            => 'Withdrawal',
                    'status'                  => 'In Transit',
                    'origin_location_id'      => $locationId,
                    'destination_location_id' => $originLocationId,
                    'faction_id'              => $missionFactionId,
                    'notes'                   => 'Defeated — returning to origin.',
                    'launched_date'           => $this->gameDate,
                    'eta_date'                => $etaDate->format('Y-m-d'),
                    'distance'                => $distance,
                    'transit_hours'           => $transitHours,
                    'hours_elapsed'           => 0,
                    'slowest_speed'           => 64.0,
                    'current_coord_x'         => $location['coord_x'],
                    'current_coord_y'         => $location['coord_y'],
                ], [$unitId]);

                $this->db->table('units')
                    ->where('unit_id', $unitId)
                    ->update([
                        'status'     => 'In Transit',
                        'mission_id' => $newMissionId,
                    ]);
            }

            // Mark original mission aborted
            $this->db->table('missions')
                ->where('mission_id', $missionId)
                ->update([
                    'status'       => 'Aborted',
                    'combat_phase' => null,
                ]);

            $eventLog->log(
                $missionFactionId,
                $this->gameDate,
                'Combat',
                "Defeated — {$mission['name']}",
                "Assault on {$location['name']} failed. Surviving units returning to origin.",
                'Critical',
                null,
                $missionId,
                $locationId
            );
        }

        // ----------------------------------------------------------------
        // Mark all pool entries resolved
        // ----------------------------------------------------------------
        $this->db->table('combat_pool')
            ->where('mission_id', $missionId)
            ->update(['resolved' => 1]);

        // Sync dispersed status on parent units
        $unitModel->syncDispersedStatus();

        // Resume any held missions at this location
        $this->resumeHeldMissions($locationId);
    }

    // ----------------------------------------------------------------
    // Helper — process salvage for all destroyed pool entries
    // ----------------------------------------------------------------
    protected function processSalvage(int $missionId, int $locationId, int $winningFactionId): void
    {
        $destroyed = $this->db->query("
            SELECT cp.equipment_id, cp.structure_at_death,
                e.max_structure
            FROM combat_pool cp
            JOIN equipment e ON e.equipment_id = cp.equipment_id
            WHERE cp.mission_id = {$missionId}
            AND cp.participant_type = 'equipment'
            AND cp.status = 'Destroyed'
            AND cp.resolved = 0
        ")->getResultArray();

        $salvageModifier = (float)$this->setting('salvage_base_chance', 0.6) / 100;

        foreach ($destroyed as $row) {
            $eqId         = (int)$row['equipment_id'];
            $structAtDeath = (int)($row['structure_at_death'] ?? 1);
            $maxStructure  = (int)($row['max_structure'] ?? 1);

            // Salvage chance = structure remaining % * modifier
            $structurePct   = $maxStructure > 0 ? $structAtDeath / $maxStructure : 0;
            $salvageChance  = $structurePct * $salvageModifier * 100;
            $roll           = random_int(0, 100);

            $salvageStatus = $roll < $salvageChance ? 'Available' : 'Scrap';

            $this->db->table('equipment')
                ->where('equipment_id', $eqId)
                ->update([
                    'equipment_status'  => 'Destroyed',
                    'combat_status'     => 'Destroyed',
                    'salvage_status'    => $salvageStatus,
                    'assigned_unit_id'  => null,
                    'location_id'       => $locationId,
                    'faction_id'        => null, // belongs to location, not faction
                ]);
        }
    }

    // ----------------------------------------------------------------
    // Helper — null out location for all KIA personnel in this battle
    // ----------------------------------------------------------------
    protected function processKiaCleanup(int $missionId): void
    {
        // Find all units in this battle via combat pool
        $unitIds = array_column(
            $this->db->query("
            SELECT DISTINCT unit_id FROM combat_pool
            WHERE mission_id = {$missionId}
            AND resolved = 0
        ")->getResultArray(),
            'unit_id'
        );

        if (empty($unitIds)) return;
        $idList = implode(',', $unitIds);

        // Release KIA from assignments and null location
        $kiaPersonnel = $this->db->query("
            SELECT DISTINCT p.personnel_id
            FROM personnel p
            JOIN personnel_assignments pa ON pa.personnel_id = p.personnel_id
            WHERE pa.unit_id IN ({$idList})
            AND p.status = 'KIA'
            AND p.location_id IS NOT NULL
        ")->getResultArray();

        foreach ($kiaPersonnel as $p) {
            $pid = (int)$p['personnel_id'];

            // Release from unit assignment
            $this->db->table('personnel_assignments')
                ->where('personnel_id', $pid)
                ->where('date_released IS NULL', null, false)
                ->update(['date_released' => $this->gameDate]);

            // Null out location
            $this->db->table('personnel')
                ->where('personnel_id', $pid)
                ->update(['location_id' => null]);
        }
    }

    // ----------------------------------------------------------------
    // Helper — get all unique unit IDs from pool for one side
    // Includes all statuses — survivors, retreated, routed
    // Excludes units whose equipment is all destroyed (nothing to move)
    // ----------------------------------------------------------------
    protected function getPoolUnitIds(int $missionId, string $side): array
    {
        // For equipment participants: include units with at least one
        // non-destroyed entry OR retreated (they travel with unit)
        $rows = $this->db->query("
            SELECT DISTINCT cp.unit_id
            FROM combat_pool cp
            WHERE cp.mission_id = {$missionId}
            AND cp.side = '{$side}'
            AND cp.resolved = 0
            AND cp.status != 'Destroyed'
        ")->getResultArray();

        return array_column($rows, 'unit_id');
    }

    // ----------------------------------------------------------------
    // Helper — get equipment IDs from pool for one side by status list
    // ----------------------------------------------------------------
    protected function getPoolEquipmentIds(int $missionId, string $side, array $statuses): array
    {
        $statusList = "'" . implode("','", $statuses) . "'";
        $rows = $this->db->query("
            SELECT cp.equipment_id
            FROM combat_pool cp
            WHERE cp.mission_id = {$missionId}
            AND cp.side = '{$side}'
            AND cp.participant_type = 'equipment'
            AND cp.status IN ({$statusList})
            AND cp.resolved = 0
            AND cp.equipment_id IS NOT NULL
        ")->getResultArray();

        return array_column($rows, 'equipment_id');
    }

    // ----------------------------------------------------------------
    // Helper — sync personnel location for surviving combatants
    // ----------------------------------------------------------------
    protected function syncPersonnelLocation(int $missionId, string $side, int $locationId): void
    {
        // Get all personnel linked to non-destroyed pool entries
        $eqIds = $this->getPoolEquipmentIds($missionId, $side, ['Active', 'Crippled', 'Retreated']);

        if (!empty($eqIds)) {
            $idList = implode(',', $eqIds);
            // Update pilots
            $this->db->query("
            UPDATE personnel p
            JOIN personnel_equipment pe ON pe.personnel_id = p.personnel_id
            SET p.location_id = {$locationId}
            WHERE pe.equipment_id IN ({$idList})
            AND pe.date_released IS NULL
            AND p.status = 'Active'
        ");
        }

        // Infantry personnel
        $infantryUnitIds = array_column(
            $this->db->query("
            SELECT DISTINCT unit_id FROM combat_pool
            WHERE mission_id = {$missionId}
            AND side = '{$side}'
            AND participant_type = 'infantry'
            AND status NOT IN ('Routed','Destroyed')
            AND resolved = 0
        ")->getResultArray(),
            'unit_id'
        );

        if (!empty($infantryUnitIds)) {
            $idList = implode(',', $infantryUnitIds);
            $this->db->query("
            UPDATE personnel p
            JOIN personnel_assignments pa ON pa.personnel_id = p.personnel_id
            SET p.location_id = {$locationId}
            WHERE pa.unit_id IN ({$idList})
            AND pa.date_released IS NULL
            AND p.status = 'Active'
        ");
        }
    }

    // ----------------------------------------------------------------
    // Helper — get the defending faction ID at a location
    // ----------------------------------------------------------------
    protected function getDefenderFactionId(int $locationId, int $attackerFactionId): int
    {
        $row = $this->db->table('locations')
            ->select('controlled_by')
            ->where('location_id', $locationId)
            ->get()->getRowArray();

        // controlled_by is the defender — if somehow null, find from pool
        if ($row && $row['controlled_by'] && (int)$row['controlled_by'] !== $attackerFactionId) {
            return (int)$row['controlled_by'];
        }

        // Fallback — find faction from defender pool entries
        $row = $this->db->query("
            SELECT u.faction_id
            FROM combat_pool cp
            JOIN units u ON u.unit_id = cp.unit_id
            WHERE cp.mission_id IN (
                SELECT mission_id FROM combat_pool
                WHERE resolved = 0
            )
            AND cp.side = 'defender'
            LIMIT 1
        ")->getRowArray();

        return (int)($row['faction_id'] ?? 0);
    }

    // ================================================================
    // Check phase transition
    // ================================================================
    protected function checkPhaseTransition(
        array  $mission,
        array  $attackers,
        array  $defenders,
        int    $round,
        string $currentPhase
    ): void {
        $missionId = $mission['mission_id'];

        if ($currentPhase === 'Skirmish') {
            $maxSkirmishRounds = (int)$this->setting('skirmish_max_rounds', 4);
            $closeThreshold    = (float)$this->setting('skirmish_close_threshold', 2);

            $attackerMv = $this->averageMvFromAll($attackers);
            $defenderMv = $this->averageMvFromAll($defenders);
            $speedDiff  = abs($attackerMv - $defenderMv);

            if ($round >= $maxSkirmishRounds || $speedDiff <= $closeThreshold) {
                $this->db->table('missions')
                    ->where('mission_id', $missionId)
                    ->update(['combat_phase' => 'Melee', 'combat_round' => 0]);

                $this->battleLog->record(
                    $missionId,
                    $this->gameDate,
                    $this->gameHour,
                    'Skirmish',
                    $round,
                    'PhaseChange',
                    "Forces have closed to melee range — transitioning to Melee phase."
                );
            }
        } elseif ($currentPhase === 'Pursuit') {
            // Pursuit ends after calculated rounds
            // For now end after 2 rounds — will be refined
            if ($round >= 2) {
                $this->endCombat(
                    $mission,
                    'defender_defeated',
                    $this->db->table('locations')
                        ->where('location_id', $mission['destination_location_id'])
                        ->get()->getRowArray()
                );
            }
        }
        // Melee has no automatic transition — ends when one side is defeated
    }

    protected function averageMvFromAll(array $combatants): float
    {
        $lances = $this->groupByLance($combatants);
        if (empty($lances)) return 0;
        $total = array_sum(array_map([$this, 'averageMv'], $lances));
        return $total / count($lances);
    }

    // ================================================================
    // Parse AS specials string into array
    // ================================================================
    protected function parseSpecials(?string $specials): array
    {
        if (!$specials) return [];
        $result = [];
        foreach (explode(',', $specials) as $special) {
            $special = trim($special);
            if (!$special) continue;

            // Handle ART specials first — ARTLT-1, ARTAIS-2, ARTT-1 etc
            if (preg_match('/^ART([A-Z0-9]+)-(\d+)$/', $special, $m)) {
                $result['ART'] = [
                    'type'    => $m[1],
                    'attacks' => (int)$m[2],
                ];
                continue;
            }

            // General specials
            if (preg_match('/^([A-Z]+)(\d+)?(?:\/(\d+))?(?:\/(\d+))?(?:\/([\d-]+))?$/', $special, $m)) {
                $result[$m[1]] = [
                    'value1' => isset($m[2]) && $m[2] !== '' ? (int)$m[2] : null,
                    'value2' => isset($m[3]) && $m[3] !== '' ? (int)$m[3] : null,
                    'value3' => isset($m[4]) && $m[4] !== '' ? (int)$m[4] : null,
                ];
            }
        }
        return $result;
    }

    // ================================================================
    // Apply special ability damage bonuses
    // ================================================================
    protected function applySpecialDamageBonus(
        float  $base,
        array  $specials,
        string $phase,
        int    $round
    ): float {
        $lToMRound = (int)$this->setting('skirmish_l_to_m_round', 3);
        $mToSRound = (int)$this->setting('melee_m_to_s_round', 2);

        // SRM — adds to S damage in melee
        if (isset($specials['SRM']) && $phase === 'Melee' && $round >= $mToSRound) {
            $base += (float)($specials['SRM']['value1'] ?? 0);
        }

        // LRM — adds to L/M damage in skirmish, S in melee
        if (isset($specials['LRM'])) {
            if ($phase === 'Skirmish' && $round < $lToMRound) {
                $base += (float)($specials['LRM']['value3'] ?? 0); // L bracket
            } elseif ($phase === 'Skirmish' && $round >= $lToMRound) {
                $base += (float)($specials['LRM']['value2'] ?? 0); // M bracket
            } elseif ($phase === 'Melee') {
                $base += (float)($specials['LRM']['value1'] ?? 0); // S bracket
            }
        }

        // AC — adds to S/M damage
        if (isset($specials['AC'])) {
            if ($phase === 'Melee' && $round >= $mToSRound) {
                $base += (float)($specials['AC']['value1'] ?? 0); // S
            } elseif ($phase === 'Skirmish' && $round >= $lToMRound) {
                $base += (float)($specials['AC']['value2'] ?? 0); // M
            } elseif ($phase === 'Melee' && $round < $mToSRound) {
                $base += (float)($specials['AC']['value2'] ?? 0); // M
            }
        }

        return $base;
    }

    // ================================================================
    // Terrain modifiers from location
    // ================================================================
    protected function getTerrainModifiers(?array $location): array
    {
        $terrain = $location['terrain'] ?? 'Plains';

        $modifiers = match ($terrain) {
            'Dense Urban' => ['hard_attack_modifier' => 0.4, 'to_hit_modifier' => 1],
            'Urban'       => ['hard_attack_modifier' => 0.6, 'to_hit_modifier' => 1],
            'Woods'       => ['hard_attack_modifier' => 0.8, 'to_hit_modifier' => 1],
            'Hills'       => ['hard_attack_modifier' => 1.0, 'to_hit_modifier' => 1],
            'Mountains'   => ['hard_attack_modifier' => 0.7, 'to_hit_modifier' => 1],
            'Marsh'       => ['hard_attack_modifier' => 0.9, 'to_hit_modifier' => 0],
            'Desert'      => ['hard_attack_modifier' => 1.0, 'to_hit_modifier' => 0],
            default       => ['hard_attack_modifier' => 1.0, 'to_hit_modifier' => 0], // Plains
        };

        $modifiers['type']             = $terrain;
        $modifiers['has_fortification'] = false; // set by caller if building present
        return $modifiers;
    }

    // ================================================================
    // Forced withdrawals after attacker wins
    // (mirrors GameTickService logic)
    // ================================================================
    protected function checkForForcedWithdrawals(int $locationId, int $capturingFactionId): void
    {
        $hqUnits = $this->db->table('units')
            ->whereIn('unit_type', ['Battalion', 'Regiment'])
            ->where('location_id', $locationId)
            ->where('status', 'Garrisoned')
            ->where('faction_id !=', $capturingFactionId)
            ->get()->getResultArray();

        if (empty($hqUnits)) return;

        $missionModel = new MissionModel();
        $from = $this->db->table('locations')
            ->where('location_id', $locationId)
            ->get()->getRowArray();

        foreach ($hqUnits as $hq) {
            // Find nearest friendly — reuse GameTickService approach via direct query
            $nearest = $this->findNearestFriendlyLocation($locationId, $hq['faction_id']);
            if (!$nearest) continue;

            $distance    = $missionModel->calculateDistance(
                (float)$from['coord_x'],
                (float)$from['coord_y'],
                (float)$nearest['coord_x'],
                (float)$nearest['coord_y']
            );
            $speedRow     = $this->db->table('equipment e')
                ->select('MIN(c.speed) AS min_speed')
                ->join('chassis c', 'c.chassis_id = e.chassis_id')
                ->where('e.assigned_unit_id', $hq['unit_id'])
                ->get()->getRowArray();
            $slowestSpeed = (float)($speedRow['min_speed'] ?? 64.0);
            $transitDays  = $missionModel->calculateTransitDays($distance, $slowestSpeed);
            $etaDate      = (new \DateTime($this->gameDate))->modify("+{$transitDays} days");

            $missionId = $missionModel->createMission([
                'name'                    => "Withdrawal — {$hq['name']}",
                'mission_type'            => 'Withdrawal',
                'status'                  => 'In Transit',
                'origin_location_id'      => $locationId,
                'destination_location_id' => $nearest['location_id'],
                'faction_id'              => $hq['faction_id'],
                'notes'                   => 'Forced withdrawal — location captured.',
                'launched_date'           => $this->gameDate,
                'eta_date'                => $etaDate->format('Y-m-d'),
                'distance'                => $distance,
                'transit_days'            => $transitDays,
                'days_elapsed'            => 0,
                'slowest_speed'           => $slowestSpeed,
                'current_coord_x'         => $from['coord_x'],
                'current_coord_y'         => $from['coord_y'],
            ], [$hq['unit_id']]);

            $unitModel = new UnitModel();
            $unitModel->setMissionStatus([$hq['unit_id']], 'In Transit', $missionId);
        }
    }

    protected function findNearestFriendlyLocation(int $fromLocationId, int $factionId): ?array
    {
        $from = $this->db->table('locations')
            ->where('location_id', $fromLocationId)
            ->get()->getRowArray();
        if (!$from) return null;

        $candidates = $this->db->table('locations')
            ->where('controlled_by', $factionId)
            ->where('planet_id', $from['planet_id'])
            ->where('location_id !=', $fromLocationId)
            ->get()->getResultArray();
        if (empty($candidates)) return null;

        usort($candidates, function ($a, $b) use ($from) {
            $dA = sqrt(pow($a['coord_x'] - $from['coord_x'], 2) + pow($a['coord_y'] - $from['coord_y'], 2));
            $dB = sqrt(pow($b['coord_x'] - $from['coord_x'], 2) + pow($b['coord_y'] - $from['coord_y'], 2));
            return $dA <=> $dB;
        });
        return $candidates[0];
    }

    // ================================================================
    // Helper — get display name for a combatant
    // ================================================================
    protected function getCombatantName(array $combatant): string
    {
        if ($combatant['is_infantry']) {
            // Get parent unit name for disambiguation
            $parent = $this->db->table('units')
                ->select('name')
                ->where('unit_id', $combatant['unit']['parent_unit_id'])
                ->get()->getRowArray();
            $parentName = $parent ? " ({$parent['name']})" : '';
            return $combatant['unit']['name'] . $parentName;
        }
        $chassis = $combatant['equipment']['chassis_name'] ?? 'Unknown';
        $variant = $combatant['equipment']['variant'] ?? '';
        $unit    = $combatant['unit']['name'];
        return "{$chassis} {$variant} ({$unit})";
    }

    protected function syncCombatToMainTables(int $missionId): void
    {
        $pool = $this->db->query("
            SELECT cp.pool_id, cp.participant_type,
                cp.equipment_id, cp.personnel_id,
                cp.status AS pool_status,
                cp.pilot_morale, cp.pilot_final_status,
                cp.current_armor, cp.current_structure
            FROM combat_pool cp
            WHERE cp.mission_id = {$missionId}
            AND cp.resolved = 0
        ")->getResultArray();

        foreach ($pool as $participant) {
            if ($participant['participant_type'] !== 'equipment') continue;

            $eqId = (int)$participant['equipment_id'];
            $pid  = (int)$participant['personnel_id'];

            // Sync combat_status to equipment table
            $combatStatus = match ($participant['pool_status']) {
                'Crippled'  => 'Crippled',
                'Destroyed' => 'Destroyed',
                default     => 'Operational',
            };


            $eqUpdate = [
                'combat_status'     => $combatStatus,
                'current_armor'     => $participant['current_armor'],
                'current_structure' => $participant['current_structure'],
            ];

            if ($participant['pool_status'] === 'Destroyed') {
                $eqUpdate['equipment_status'] = 'Destroyed';
            }

            $this->db->table('equipment')
                ->where('equipment_id', $eqId)
                ->update($eqUpdate);

            // Push pool morale → personnel (pool is authoritative)
            if ($pid) {
                $this->db->table('personnel')
                    ->where('personnel_id', $pid)
                    ->update(['morale' => $participant['pilot_morale']]);
            }
        }

        // Infantry sync — pool pilot_morale is authoritative, push to personnel
        foreach ($pool as $participant) {
            if ($participant['participant_type'] !== 'infantry') continue;
            if (!$participant['personnel_id']) continue;

            $this->db->table('personnel')
                ->where('personnel_id', $participant['personnel_id'])
                ->update(['morale' => $participant['pilot_morale']]);
        }

        $this->combatBuildingsModel->syncToBuildings($missionId);
    }

    protected function processHeat(int $missionId): void
    {
        // Step 1 — Reactivate shutdown mechs and clear their heat
        $this->db->table('combat_pool')
            ->where('mission_id', $missionId)
            ->where('is_shutdown', 1)
            ->where('resolved', 0)
            ->update([
                'is_shutdown'  => 0,
                'heat_buildup' => 0,
            ]);

        // Step 2 — Shutdown mechs that hit heat 4+ this round
        $this->db->table('combat_pool')
            ->where('mission_id', $missionId)
            ->where('heat_buildup >=', 4)
            ->where('resolved', 0)
            ->update(['is_shutdown' => 1]);

        // Log any newly shutdown mechs
        $shutdown = $this->db->query("
            SELECT cp.pool_id, cp.pilot_first_name, cp.pilot_last_name,
                cp.pilot_rank_abbr, e.equipment_id,
                c.name AS chassis_name, c.variant,
                u.name AS unit_name
            FROM combat_pool cp
            JOIN equipment e ON e.equipment_id = cp.equipment_id
            JOIN chassis c ON c.chassis_id = e.chassis_id
            JOIN units u ON u.unit_id = cp.unit_id
            WHERE cp.mission_id = {$missionId}
            AND cp.is_shutdown = 1
            AND cp.resolved = 0
        ")->getResultArray();

        foreach ($shutdown as $row) {
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                '',
                0,
                'Shutdown',
                "{$row['pilot_rank_abbr']}. {$row['pilot_last_name']}'s "
                    . "{$row['chassis_name']} {$row['variant']} ({$row['unit_name']}) "
                    . "has SHUT DOWN due to heat."
            );
        }

        // Step 3 — Reduce all mech heat by 1, floor 0
        $this->db->query("
            UPDATE combat_pool
            SET heat_buildup = GREATEST(0, heat_buildup - 1)
            WHERE mission_id = {$missionId}
            AND participant_type = 'equipment'
            AND resolved = 0
        ");

        // Clear used_ov for next round
        $this->db->table('combat_pool')
            ->where('mission_id', $missionId)
            ->where('resolved', 0)
            ->update(['used_ov' => 0]);
    }

    // ================================================================
    // Resume any missions that were holding at this location
    // waiting for battle to resolve
    // ================================================================
    protected function resumeHeldMissions(int $locationId): void
    {
        $held = $this->db->table('missions')
            ->where('destination_location_id', $locationId)
            ->where('status', 'In Transit')
            ->like('notes', 'HOLDING — AWAITING BATTLE RESOLUTION')
            ->get()->getResultArray();

        if (empty($held)) return;

        $missionModel = new MissionModel();

        foreach ($held as $mission) {
            // Strip the holding note
            $cleanNotes = trim(str_replace(
                '[HOLDING — AWAITING BATTLE RESOLUTION]',
                '',
                $mission['notes'] ?? ''
            ));

            $this->db->table('missions')
                ->where('mission_id', $mission['mission_id'])
                ->update(['notes' => $cleanNotes]);

            // Fetch destination location
            $dest = $this->db->table('locations')
                ->where('location_id', $locationId)
                ->get()->getRowArray();

            if (!$dest) continue;

            // Get unit IDs for this mission
            $unitIds = array_column(
                $this->db->table('mission_units')
                    ->select('unit_id')
                    ->where('mission_id', $mission['mission_id'])
                    ->get()->getResultArray(),
                'unit_id'
            );

            // Determine if arriving units are friendly or enemy
            // relative to the location's new controller
            $missionFactionId  = (int)$mission['faction_id'];
            $locationController = (int)$dest['controlled_by'];

            if ($missionFactionId === $locationController) {
                // Friendly arrival — garrison normally
                $unitModel = new UnitModel();
                $unitModel->setMissionStatus($unitIds, 'Garrisoned', null, $locationId);

                // Update equipment and personnel locations
                foreach ($unitIds as $unitId) {
                    $this->db->query("
                    UPDATE equipment SET location_id = {$locationId}
                    WHERE assigned_unit_id = {$unitId}
                    AND equipment_status = 'Active'
                ");
                    $this->db->query("
                    UPDATE personnel p
                    JOIN personnel_assignments pa ON pa.personnel_id = p.personnel_id
                    SET p.location_id = {$locationId}
                    WHERE pa.unit_id = {$unitId}
                    AND pa.date_released IS NULL
                    AND p.status = 'Active'
                ");
                }

                $this->db->table('missions')
                    ->where('mission_id', $mission['mission_id'])
                    ->update([
                        'status'       => 'Arrived',
                        'arrived_date' => $this->gameDate,
                    ]);
            } else {
                // Enemy arriving at now-hostile location — 
                // redirect to nearest friendly location instead
                $nearest = $this->findNearestFriendlyLocation(
                    $locationId,
                    $missionFactionId
                );

                if (!$nearest) {
                    // No friendly location — unit is stranded, hold in place
                    $this->db->table('missions')
                        ->where('mission_id', $mission['mission_id'])
                        ->update([
                            'notes' => trim(($cleanNotes ? $cleanNotes . ' ' : '') .
                                '[STRANDED — NO FRIENDLY LOCATION AVAILABLE]'),
                        ]);
                    continue;
                }

                // Recalculate transit to new destination
                $distance    = $missionModel->calculateDistance(
                    (float)$dest['coord_x'],
                    (float)$dest['coord_y'],
                    (float)$nearest['coord_x'],
                    (float)$nearest['coord_y']
                );
                $transitHours = $missionModel->calculateTransitHours($distance, 64.0);
                $etaDate      = (new \DateTime($this->gameDate))
                    ->modify('+' . (int)ceil($transitHours / 24) . ' days');

                $this->db->table('missions')
                    ->where('mission_id', $mission['mission_id'])
                    ->update([
                        'destination_location_id' => $nearest['location_id'],
                        'origin_location_id'      => $locationId,
                        'transit_hours'           => $transitHours,
                        'hours_elapsed'           => 0,
                        'eta_date'                => $etaDate->format('Y-m-d'),
                        'current_coord_x'         => $dest['coord_x'],
                        'current_coord_y'         => $dest['coord_y'],
                        'notes'                   => trim(($cleanNotes ? $cleanNotes . ' ' : '') .
                            '[REDIRECTED — DESTINATION CAPTURED]'),
                    ]);
            }
        }
    }

    // ================================================================
    // Add arriving units to an active combat as reinforcements
    // ================================================================
    public function joinCombat(array $arrivingMission, array $activeCombatMission, array $location): void
    {
        $activeMissionId   = (int)$activeCombatMission['mission_id'];
        $arrivingFactionId = (int)$arrivingMission['faction_id'];
        $locationId        = (int)$location['location_id'];

        // Determine which side the arriving faction joins
        $attackerFactionId = (int)$activeCombatMission['faction_id'];
        $side = $arrivingFactionId === $attackerFactionId ? 'attacker' : 'defender';

        // Get arriving unit IDs — top level only
        $allArriving = array_column(
            $this->db->table('mission_units')
                ->select('unit_id')
                ->where('mission_id', $arrivingMission['mission_id'])
                ->get()->getResultArray(),
            'unit_id'
        );

        // Filter to top-level roots
        $roots = array_filter($allArriving, function ($uid) use ($allArriving) {
            $unit = $this->db->table('units')
                ->select('parent_unit_id')
                ->where('unit_id', $uid)
                ->get()->getRowArray();
            return !in_array(
                (int)($unit['parent_unit_id'] ?? 0),
                array_map('intval', $allArriving)
            );
        });

        // Resolve to leaf units
        $leafIds = [];
        foreach ($roots as $uid) {
            $this->resolveLeafIds((int)$uid, $leafIds);
        }
        $leafIds = array_unique($leafIds);

        if (empty($leafIds)) return;

        // Add each leaf unit to the active combat pool
        foreach ($leafIds as $unitId) {
            $this->populatePoolForUnit(
                $unitId,
                $activeMissionId,
                $side,
                $this->gameDate
            );
        }

        // Set arriving units to Combat status
        $idList = implode(',', $leafIds);
        $this->db->query("
            UPDATE units SET status = 'Combat'
            WHERE unit_id IN ({$idList})
        ");

        // Add arriving units to the active combat's mission_units
        // so endCombat() can find them when resolving
        foreach ($leafIds as $unitId) {
            // Check not already in mission_units
            $exists = $this->db->table('mission_units')
                ->where('mission_id', $activeMissionId)
                ->where('unit_id', $unitId)
                ->countAllResults();

            if (!$exists) {
                $this->db->table('mission_units')->insert([
                    'mission_id' => $activeMissionId,
                    'unit_id'    => $unitId,
                ]);
            }
        }

        // Mark the arriving mission as absorbed into active combat
        $this->db->table('missions')
            ->where('mission_id', $arrivingMission['mission_id'])
            ->update([
                'status' => 'Arrived',
                'arrived_date' => $this->gameDate,
                'notes' => trim(($arrivingMission['notes'] ?? '') .
                    ' [JOINED ACTIVE COMBAT AS ' . strtoupper($side) . ']'),
            ]);

        // Log reinforcement arrival
        $this->battleLog->record(
            $activeMissionId,
            $this->gameDate,
            $this->gameHour,
            $activeCombatMission['combat_phase'] ?? 'Melee',
            (int)$activeCombatMission['combat_round'],
            'RoundSummary',
            "Reinforcements have arrived and joined the {$side} force."
        );
    }

    protected function applyFriendlyDestroyedPenalty(
        array  $destroyedUnit,
        int    $missionId,
        string $phase,
        int    $round
    ): void {
        $penalty     = (float)$this->setting('morale_loss_friendly_destroyed', 10);
        $destroyedSide = $destroyedUnit['side'];
        $destroyedName = $this->getCombatantName($destroyedUnit);

        // Find all active personnel on the same side in this battle
        // Equipment pilots
        $pilots = $this->db->query("
            SELECT cp.personnel_id, cp.pilot_morale, cp.pool_id,
                cp.pilot_experience
            FROM combat_pool cp
            WHERE cp.mission_id = {$missionId}
            AND cp.side = '{$destroyedSide}'
            AND cp.participant_type = 'equipment'
            AND cp.status IN ('Active','Crippled')
            AND cp.resolved = 0
            AND cp.personnel_id IS NOT NULL
        ")->getResultArray();

        foreach ($pilots as $pilot) {
            $experience = $pilot['pilot_experience'] ?? 'Regular';

            // More experienced pilots are less affected
            $multiplier = match ($experience) {
                'Elite'   => 0.25,
                'Veteran' => 0.5,
                'Regular' => 1.0,
                'Green'   => 1.5,
                default   => 1.0,
            };

            $loss      = $penalty * $multiplier;
            $newMorale = max(0, (float)$pilot['pilot_morale'] - $loss);

            $this->db->table('personnel')
                ->where('personnel_id', $pilot['personnel_id'])
                ->update(['morale' => $newMorale]);

            $this->db->table('combat_pool')
                ->where('pool_id', $pilot['pool_id'])
                ->update(['pilot_morale' => $newMorale]);
        }

        // Infantry leaders
        $infantry = $this->db->query("
            SELECT cp.personnel_id, cp.pilot_morale, cp.pool_id
            FROM combat_pool cp
            WHERE cp.mission_id = {$missionId}
            AND cp.side = '{$destroyedSide}'
            AND cp.participant_type = 'infantry'
            AND cp.status = 'Active'
            AND cp.resolved = 0
            AND cp.personnel_id IS NOT NULL
        ")->getResultArray();

        foreach ($infantry as $inf) {
            $newMorale = max(0, (float)$inf['pilot_morale'] - $penalty);

            $this->db->table('personnel')
                ->where('personnel_id', $inf['personnel_id'])
                ->update(['morale' => $newMorale]);

            $this->db->table('combat_pool')
                ->where('pool_id', $inf['pool_id'])
                ->update(['pilot_morale' => $newMorale]);
        }

        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'Damage',
            "Friendly loss of {$destroyedName} — morale penalty applied to all {$destroyedSide} units."
        );
    }
}
