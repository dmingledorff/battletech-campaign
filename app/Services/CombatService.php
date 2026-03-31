<?php

namespace App\Services;

use App\Models\BattleLogModel;
use App\Models\EventLogModel;
use App\Models\GameStateModel;
use App\Models\MissionModel;
use App\Models\UnitModel;
use CodeIgniter\Database\BaseConnection;

class CombatService
{
    protected $db;
    protected string $gameDate;
    protected int    $gameHour;
    protected array  $settings = [];
    protected BattleLogModel $battleLog;
    protected GameStateModel $gameState;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db        = $db ?? db_connect();
        $this->battleLog = new BattleLogModel();
        $this->gameState = new GameStateModel();
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
        $missionId        = $mission['mission_id'];
        $missionFactionId = (int)$mission['faction_id'];

        // Set mission to Combat status with Skirmish phase
        $this->db->table('missions')
            ->where('mission_id', $missionId)
            ->update([
                'status'          => 'Combat',
                'combat_phase'    => 'Skirmish',
                'combat_round'    => 0,
                'arrived_date'    => $this->gameDate,
                'current_coord_x' => $location['coord_x'],
                'current_coord_y' => $location['coord_y'],
            ]);

        // Set attacker units to Combat status at the location
        $unitIds = array_column(
            $this->db->table('mission_units')
                ->where('mission_id', $missionId)
                ->get()->getResultArray(),
            'unit_id'
        );
        $unitModel = new UnitModel();
        $unitModel->setMissionStatus($unitIds, 'Combat', $missionId, $location['location_id']);

        // Log to battle log
        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            'Skirmish',
            0,
            'BattleStart',
            "Battle commenced at {$location['name']}. Attacking force engaged defending garrison."
        );

        // Log single event_log entry for the battle start
        $eventLog = new EventLogModel();
        $eventLog->log(
            $missionFactionId,
            $this->gameDate,
            'Combat',
            "Combat initiated — {$mission['name']}",
            "Assault force engaged enemy garrison at {$location['name']}.",
            'Warning',
            null,
            $missionId,
            $location['location_id']
        );
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

        // Pair lances and resolve attacks
        $pairings = $this->pairLances($attackers, $defenders);

        // Collect artillery units (IF special) — not paired
        $attackerArtillery = $this->extractArtillery($attackers);
        $defenderArtillery = $this->extractArtillery($defenders);

        foreach ($pairings as $pairing) {
            $this->resolvePairing(
                $pairing['attackers'],
                $pairing['defenders'],
                $mission,
                $phase,
                $round,
                $terrain
            );
        }

        // Artillery support
        $this->resolveArtillery($attackerArtillery, $defenders, $mission, $phase, $round, $terrain);
        $this->resolveArtillery($defenderArtillery, $attackers, $mission, $phase, $round, $terrain);

        $this->syncCombatantStatuses($missionId, $mission['destination_location_id'], $mission['faction_id']);

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
        $mission    = $this->db->table('missions')->where('mission_id', $missionId)->get()->getRowArray();
        $locationId = $mission['destination_location_id'];

        if ($side === 'attacker') {
            // Get top-level mission units
            $topUnits = $this->db->query("
            SELECT u.unit_id, u.name, u.unit_type, u.role,
                   u.parent_unit_id, u.faction_id, u.status
            FROM mission_units mu
            JOIN units u ON u.unit_id = mu.unit_id
            WHERE mu.mission_id = {$missionId}
        ")->getResultArray();
        } else {
            $missionFactionId = (int)$mission['faction_id'];
            $topUnits = $this->db->query("
            SELECT u.unit_id, u.name, u.unit_type, u.role,
                   u.parent_unit_id, u.faction_id, u.status
            FROM units u
            WHERE u.location_id = {$locationId}
            AND u.status IN ('Garrisoned', 'Combat')
            AND u.faction_id != {$missionFactionId}
            AND u.unit_type IN ('Lance','Squad','Company','Platoon','Battalion','Regiment')
        ")->getResultArray();
        }

        // Resolve all units to leaf nodes (units with actual equipment)
        $leafUnits = [];
        foreach ($topUnits as $unit) {
            $this->resolveLeafUnits($unit['unit_id'], $leafUnits);
        }

        // Remove duplicates
        $seen      = [];
        $leafUnits = array_filter($leafUnits, function ($u) use (&$seen) {
            if (isset($seen[$u['unit_id']])) return false;
            $seen[$u['unit_id']] = true;
            return true;
        });

        // Build combatants from leaf units
        $combatants = [];
        foreach ($leafUnits as $unit) {
            $unitId = $unit['unit_id'];

            $equipment = $this->db->query("
                SELECT e.equipment_id, e.current_armor, e.current_structure,
                    e.max_armor, e.max_structure, e.combat_status,
                    e.heat_buildup, e.assigned_unit_id,
                    c.name AS chassis_name, c.variant, c.as_type,
                    c.as_mv, c.as_tmm, c.as_size,
                    c.as_dmg_s, c.as_dmg_m, c.as_dmg_l, c.as_dmg_e,
                    c.as_ov, c.as_specials, c.as_armor AS base_armor,
                    c.as_structure AS base_structure
                FROM equipment e
                JOIN chassis c ON c.chassis_id = e.chassis_id
                WHERE e.assigned_unit_id = {$unitId}
                AND e.equipment_status = 'Active'
                AND e.combat_status != 'Destroyed'
            ")->getResultArray();

            // Skip units with no equipment — they're command/HQ only
            if (empty($equipment)) continue;

            foreach ($equipment as &$eq) {
                $eq['specials'] = $this->parseSpecials($eq['as_specials'] ?? '');
            }
            unset($eq);

            $crew = $this->db->query("
                SELECT p.personnel_id, p.morale, p.experience, p.status,
                    p.first_name, p.last_name,
                    r.abbreviation AS rank_abbr, r.grade
                FROM personnel_equipment pe
                JOIN personnel p ON p.personnel_id = pe.personnel_id
                LEFT JOIN ranks r ON r.id = p.rank_id
                WHERE pe.equipment_id = {$equipment[0]['equipment_id']}
                AND pe.date_released IS NULL
                AND p.status = 'Active'
                LIMIT 1
            ")->getRowArray();

            $isInfantry = ($unit['unit_type'] === 'Squad') ||
                (($equipment[0]['as_type'] ?? '') === 'CI');

            if ($isInfantry) {
                $strength = $this->db->query("
                SELECT COUNT(*) AS cnt
                FROM personnel_assignments pa
                JOIN personnel p ON p.personnel_id = pa.personnel_id
                WHERE pa.unit_id = {$unitId}
                AND pa.date_released IS NULL
                AND p.status = 'Active'
            ")->getRowArray()['cnt'] ?? 0;

                $combatants[] = [
                    'unit'         => $unit,
                    'equipment'    => $equipment,
                    'crew'         => $crew,
                    'is_infantry'  => true,
                    'strength'     => (int)$strength,
                    'max_strength' => (int)$strength,
                    'side'         => $side,
                    'retreated'    => false,
                ];
            } else {
                foreach ($equipment as $eq) {
                    $eqCrew = $this->db->query("
                        SELECT p.personnel_id, p.morale, p.experience, p.status,
                            p.first_name, p.last_name,
                            r.abbreviation AS rank_abbr, r.grade
                        FROM personnel_equipment pe
                        JOIN personnel p ON p.personnel_id = pe.personnel_id
                        LEFT JOIN ranks r ON r.id = p.rank_id
                        WHERE pe.equipment_id = {$eq['equipment_id']}
                        AND pe.date_released IS NULL
                        AND p.status = 'Active'   -- only Active pilots can fight
                        LIMIT 1
                    ")->getRowArray();

                    // Skip this equipment if no active pilot assigned
                    if (!$eqCrew) continue;

                    $combatants[] = [
                        'unit'        => $unit,
                        'equipment'   => $eq,
                        'crew'        => $eqCrew,
                        'is_infantry' => false,
                        'side'        => $side,
                        'retreated'   => false,
                    ];
                }
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

    protected function extractArtillery(array &$combatants): array
    {
        $artillery = [];
        $remaining = [];
        foreach ($combatants as $c) {
            if (!$c['is_infantry'] && isset($c['equipment']['specials']['IF'])) {
                $artillery[] = $c;
            } else {
                $remaining[] = $c;
            }
        }
        $combatants = $remaining;
        return $artillery;
    }

    // ================================================================
    // Resolve all attacks within a paired engagement
    // Both sides attack independently
    // ================================================================
    protected function resolvePairing(
        array $attackers,
        array $defenders,
        array $mission,
        string $phase,
        int $round,
        array $terrain
    ): void {
        // Attackers target defenders
        $this->resolveOneSideAttacks($attackers, $defenders, $mission, $phase, $round, $terrain, 'attacker');
        // Defenders target attackers (independently)
        $this->resolveOneSideAttacks($defenders, $attackers, $mission, $phase, $round, $terrain, 'defender');
    }

    protected function resolveOneSideAttacks(
        array $shooters,
        array $targets,
        array $mission,
        string $phase,
        int $round,
        array $terrain,
        string $shooterSide
    ): void {
        if (empty($shooters) || empty($targets)) return;

        // Filter to active targets only
        $activeTargets = array_values(array_filter($targets, function ($t) {
            if ($t['retreated']) return false;
            if ($t['is_infantry']) return $t['strength'] > 0;
            // Check both combat_status AND equipment_status
            if (($t['equipment']['equipment_status'] ?? 'Active') === 'Destroyed') return false;
            return ($t['equipment']['combat_status'] ?? 'Operational') !== 'Destroyed';
        }));

        if (empty($activeTargets)) return;

        $targetIdx = 0;
        foreach ($shooters as $shooter) {
            if ($shooter['retreated']) continue;
            if (
                !$shooter['is_infantry'] &&
                ($shooter['equipment']['combat_status'] ?? 'Operational') === 'Destroyed'
            ) continue;

            // Pick target by role priority
            $target = $this->pickTarget($shooter, $activeTargets, $targetIdx);
            $targetIdx = ($targetIdx + 1) % count($activeTargets);

            $this->resolveAttack($shooter, $target, $mission, $phase, $round, $terrain);
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
        array  $target,
        array  $mission,
        string $phase,
        int    $round,
        array  $terrain
    ): void {
        $missionId = $mission['mission_id'];

        // Get base damage for this phase/round
        $baseDamage = $this->getBaseDamage($shooter, $phase, $round);
        if ($baseDamage <= 0) return;

        // Apply special ability bonuses to damage
        $specials = $shooter['is_infantry'] ? [] : ($shooter['equipment']['specials'] ?? []);
        $damage   = $this->applySpecialDamageBonus($baseDamage, $specials, $phase, $round);

        // Roll to hit
        $hit = $this->rollToHit($shooter, $target, $terrain);

        if (!$hit) {
            $shooterName = $this->getCombatantName($shooter);
            $targetName  = $this->getCombatantName($target);
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

        $damage = max(0, round($damage, 1));

        // Apply damage
        if ($target['is_infantry']) {
            $this->applyInfantryDamage($target, $damage, $mission, $phase, $round);
        } else {
            $this->applyEquipmentDamage($target, $damage, $mission, $phase, $round);
        }

        $shooterName = $this->getCombatantName($shooter);
        $targetName  = $this->getCombatantName($target);
        $this->battleLog->record(
            $missionId,
            $this->gameDate,
            $this->gameHour,
            $phase,
            $round,
            'Attack',
            "{$shooterName} hits {$targetName} for {$damage} damage.",
            $shooter['is_infantry'] ? null : ($shooter['equipment']['equipment_id'] ?? null),
            $target['is_infantry']  ? null : ($target['equipment']['equipment_id']  ?? null),
            $damage
        );

        // Apply morale damage to crew
        $this->applyMoraleDamage($target, $damage, $mission, $phase, $round);
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

        // Infantry in fortification — harder to target
        if ($target['is_infantry'] && $terrain['has_fortification']) {
            $modifier += 2;
        }

        return $roll >= ($baseToHit + $modifier);
    }

    // ================================================================
    // Apply damage to equipment (mech/vehicle)
    // ================================================================
    protected function applyEquipmentDamage(
        array  $target,
        float  $damage,
        array  $mission,
        string $phase,
        int    $round
    ): void {
        $eq        = $target['equipment'];
        $eqId      = $eq['equipment_id'];
        $missionId = $mission['mission_id'];

        $fresh = $this->db->table('equipment')
            ->select('current_armor, current_structure, combat_status')
            ->where('equipment_id', $eqId)
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

        $newStatus = $eq['combat_status'] ?? 'Operational';

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
            $this->rollEjection($target, 'crippled', $mission, $phase, $round);
        }

        // Check destroyed
        if ($currentStructure <= 0) {
            $newStatus        = 'Destroyed';
            $currentStructure = 0;
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
            $this->rollEjection($target, 'destroyed', $mission, $phase, $round);
            $this->handleDestroyed($target, $mission);
        }

        // Persist to DB
        $this->db->table('equipment')
            ->where('equipment_id', $eqId)
            ->update([
                'current_armor'     => $currentArmor,
                'current_structure' => $currentStructure,
                'combat_status'     => $newStatus,
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

        // Check broken threshold
        $ratio = $target['max_strength'] > 0
            ? $target['strength'] / $target['max_strength']
            : 0;

        if ($ratio < 0.25 && !$target['retreated']) {
            $target['retreated'] = true;
            $this->battleLog->record(
                $missionId,
                $this->gameDate,
                $this->gameHour,
                $phase,
                $round,
                'Retreat',
                "{$unitName} is BROKEN — below 25% strength. Unit retreats.",
            );
            $this->db->table('units')
                ->where('unit_id', $unitId)
                ->update(['status' => 'Garrisoned']);
        }
    }

    // ================================================================
    // Apply morale damage to crew after taking a hit
    // ================================================================
    protected function applyMoraleDamage(
        array  $target,
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
                if (!$target['is_infantry']) {
                    $this->db->table('units')
                        ->where('unit_id', $target['unit']['unit_id'])
                        ->update(['status' => 'Garrisoned']);
                }
            }
        }
    }

    // ================================================================
    // Artillery support
    // ================================================================
    protected function resolveArtillery(
        array  $artilleryUnits,
        array  $targets,
        array  $mission,
        string $phase,
        int    $round,
        array  $terrain
    ): void {
        if (empty($artilleryUnits) || empty($targets)) return;

        foreach ($artilleryUnits as $arty) {
            $ifValue = (float)($arty['equipment']['specials']['IF']['value1'] ?? 0);
            if ($ifValue <= 0) continue;

            // Reduced effectiveness in melee
            if ($phase === 'Melee') $ifValue *= 0.5;

            // Pick a random active target
            $activeTargets = array_values(array_filter($targets, function ($t) {
                if ($t['retreated']) return false;
                if ($t['is_infantry']) return $t['strength'] > 0;
                return ($t['equipment']['combat_status'] ?? 'Operational') !== 'Destroyed';
            }));

            if (empty($activeTargets)) continue;

            $target = $activeTargets[array_rand($activeTargets)];
            $this->resolveAttack($arty, $target, $mission, $phase, $round, $terrain);
        }
    }

    // ================================================================
    // Ejection rolls
    // ================================================================
    protected function rollEjection(
        array  $target,
        string $trigger,
        array  $mission,
        string $phase,
        int    $round
    ): void {
        // Only BattleMechs eject
        if (($target['equipment']['as_type'] ?? '') !== 'BM') return;

        $crew       = $target['crew'];
        if (!$crew) return;

        $experience = $crew['experience'] ?? 'Regular';
        $missionId  = $mission['mission_id'];

        $chance = (int)match ([$trigger, $experience]) {
            ['crippled', 'Green']   => $this->setting('eject_crippled_green',   60),
            ['crippled', 'Regular'] => $this->setting('eject_crippled_regular', 35),
            ['crippled', 'Veteran'] => $this->setting('eject_crippled_veteran', 20),
            ['crippled', 'Elite']   => $this->setting('eject_crippled_elite',   10),
            ['destroyed', 'Green']  => $this->setting('eject_destroyed_green',  40),
            ['destroyed', 'Regular'] => $this->setting('eject_destroyed_regular', 55),
            ['destroyed', 'Veteran'] => $this->setting('eject_destroyed_veteran', 70),
            ['destroyed', 'Elite']  => $this->setting('eject_destroyed_elite',  80),
            default => 50,
        };

        $roll     = random_int(0, 100);
        $ejected  = $roll < $chance;
        $name     = "{$crew['rank_abbr']}. {$crew['last_name']}";
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
        } else {
            if ($trigger === 'destroyed') {
                $this->db->table('personnel')
                    ->where('personnel_id', $crew['personnel_id'])
                    ->update(['status' => 'KIA']);

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
            if ($c['retreated']) continue;
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
        $missionId        = $mission['mission_id'];
        $missionFactionId = (int)$mission['faction_id'];
        $phase            = $mission['combat_phase'];
        $round            = (int)$mission['combat_round'];

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

        $eventLog = new EventLogModel();

        if ($result === 'defender_defeated') {
            // Attacker wins — take control, garrison
            $this->db->table('locations')
                ->where('location_id', $mission['destination_location_id'])
                ->update(['controlled_by' => $missionFactionId]);

            // Get unit IDs and garrison them
            $unitIds = array_column(
                $this->db->table('mission_units')
                    ->where('mission_id', $missionId)
                    ->get()->getResultArray(),
                'unit_id'
            );

            $unitModel = new UnitModel();
            $unitModel->setMissionStatus($unitIds, 'Garrisoned', null, $mission['destination_location_id']);

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
                $location['location_id']
            );

            // Check for forced withdrawals of enemy HQ
            // (reuse GameTickService logic via direct DB calls)
            $this->checkForForcedWithdrawals($mission['destination_location_id'], $missionFactionId);
        } else {
            // Attacker defeated — return to origin
            $unitIds = array_column(
                $this->db->table('mission_units')
                    ->where('mission_id', $missionId)
                    ->get()->getResultArray(),
                'unit_id'
            );

            $missionModel = new MissionModel();
            $unitModel    = new UnitModel();

            // Calculate return trip
            $currentX = (float)$mission['current_coord_x'];
            $currentY = (float)$mission['current_coord_y'];
            $origin   = $this->db->table('locations')
                ->where('location_id', $mission['origin_location_id'])
                ->get()->getRowArray();

            $distance   = $missionModel->calculateDistance($currentX, $currentY, (float)$origin['coord_x'], (float)$origin['coord_y']);
            $speed      = (float)($mission['slowest_speed'] ?? 64.0);
            $returnDays = $missionModel->calculateTransitDays($distance, $speed);
            $etaDate    = new \DateTime($this->gameDate);
            $etaDate->modify("+{$returnDays} days");

            $this->db->table('missions')
                ->where('mission_id', $missionId)
                ->update([
                    'status'                  => 'In Transit',
                    'combat_phase'            => null,
                    'origin_location_id'      => $mission['destination_location_id'],
                    'destination_location_id' => $mission['origin_location_id'],
                    'transit_days'            => $returnDays,
                    'days_elapsed'            => 0,
                    'eta_date'                => $etaDate->format('Y-m-d'),
                    'notes'                   => ($mission['notes'] ?? '') . ' [DEFEATED — RETURNING]',
                ]);

            $unitModel->setMissionStatus($unitIds, 'In Transit', $missionId);

            $eventLog->log(
                $missionFactionId,
                $this->gameDate,
                'Combat',
                "Defeated — {$mission['name']}",
                "Assault on {$location['name']} failed. Surviving units returning to origin.",
                'Critical',
                null,
                $missionId,
                $location['location_id']
            );
        }

        $unitModel = new UnitModel();
        $unitModel->syncDispersedStatus();
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
            return $combatant['unit']['name'];
        }
        $chassis = $combatant['equipment']['chassis_name'] ?? 'Unknown';
        $variant = $combatant['equipment']['variant'] ?? '';
        $unit    = $combatant['unit']['name'];
        return "{$chassis} {$variant} ({$unit})";
    }

    protected function syncCombatantStatuses(int $missionId, int $locationId, int $missionFactionId): void
    {
        // Mark retreated attacker units back to Garrisoned at origin
        // (already done in applyMoraleDamage, this just ensures consistency)

        // Any equipment with combat_status = Destroyed should have its unit
        // checked — if ALL equipment in a unit is destroyed/retreated, 
        // ensure unit status reflects that
        $this->db->query("
            UPDATE units u
            SET u.status = 'Garrisoned'
            WHERE u.unit_id IN (
                SELECT DISTINCT e.assigned_unit_id
                FROM equipment e
                WHERE e.assigned_unit_id IS NOT NULL
                AND e.combat_status = 'Destroyed'
            )
            AND u.status = 'Combat'
            AND NOT EXISTS (
                SELECT 1 FROM equipment e2
                WHERE e2.assigned_unit_id = u.unit_id
                AND e2.combat_status = 'Operational'
            )
        ");
    }
}
