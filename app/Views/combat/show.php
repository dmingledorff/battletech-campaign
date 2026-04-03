<?php
$isResolved = ($mission['status'] !== 'Combat');
$isActive  = $mission['status'] === 'Combat';
$won       = $mission['status'] === 'Arrived';
$summary   = $summary ?? [];
$attackerWon = ($mission['status'] === 'Arrived');
$winningFaction = $attackerWon ? $attackerFaction : $defenderFaction;
$losingFaction  = $attackerWon ? $defenderFaction : $attackerFaction;
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-dark p-2">
        <li class="breadcrumb-item"><a class="link-info" href="/combat">Combat</a></li>
        <li class="breadcrumb-item active"><?= esc($mission['name']) ?></li>
    </ol>
</nav>

<!-- Resolved Banner -->
<?php if ($isResolved): ?>
    <div class="alert alert-secondary border border-secondary mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= esc($winningFaction['emblem_path']) ?>"
                    alt="<?= esc($winningFaction['name']) ?>"
                    style="height:40px;">
                <div>
                    <h5 class="mb-1">
                        <?php if ($attackerWon): ?>
                            <span style="color:<?= esc($winningFaction['color']) ?>">
                                <?= esc($winningFaction['name']) ?>
                            </span>
                            victorious — <?= esc($mission['destination_name']) ?> captured
                        <?php else: ?>
                            <span style="color:<?= esc($winningFaction['color']) ?>">
                                <?= esc($winningFaction['name']) ?>
                            </span>
                            holds <?= esc($mission['destination_name']) ?>
                        <?php endif; ?>
                    </h5>
                    <div class="text-muted small">
                        <?= esc($mission['combat_phase']) ?> ·
                        <?= $mission['combat_round'] ?> Rounds ·
                        Concluded <?= esc($mission['arrived_date'] ?? $gameDate) ?>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <img src="<?= esc($losingFaction['emblem_path']) ?>"
                    alt="<?= esc($losingFaction['name']) ?>"
                    style="height:30px; opacity:0.4;">
                <a href="/missions/<?= $mission['mission_id'] ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Mission Record
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-crosshair me-2 <?= $isActive ? 'text-danger' : ($won ? 'text-success' : 'text-secondary') ?>"></i>
                <?= esc($mission['name']) ?>
            </h4>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($isActive): ?>
                    <span class="badge bg-danger"><i class="bi bi-fire me-1"></i>Active Combat</span>
                    <span class="badge bg-secondary"><?= esc($mission['combat_phase']) ?> — Round <?= esc($mission['combat_round']) ?></span>
                <?php elseif ($won): ?>
                    <span class="badge bg-success">Victory</span>
                <?php else: ?>
                    <span class="badge bg-danger">Defeated</span>
                <?php endif; ?>
                <span class="text-muted small">
                    <a class="link-secondary" href="/missions/<?= $mission['mission_id'] ?>">
                        View Mission
                    </a>
                </span>
            </div>
        </div>
        <?php if ($isActive): ?>
            <div class="d-flex gap-2">
                <form action="/missions/<?= $mission['mission_id'] ?>/withdraw" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-warning btn-sm"
                        onclick="return confirm('Order withdrawal? Units will attempt to break contact.')">
                        <i class="bi bi-arrow-left me-1"></i>Order Withdrawal
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<!-- Summary stats bar -->
<?php if (!empty($summary)): ?>
    <div class="row g-2 mb-3">
        <?php
        $stats = [
            ['label' => 'Rounds',    'value' => $summary['total_rounds']    ?? 0, 'color' => 'secondary'],
            ['label' => 'Attacks',   'value' => $summary['total_attacks']   ?? 0, 'color' => 'info'],
            ['label' => 'Crippled',  'value' => $summary['units_crippled']  ?? 0, 'color' => 'warning'],
            ['label' => 'Destroyed', 'value' => $summary['units_destroyed'] ?? 0, 'color' => 'danger'],
            ['label' => 'Ejections', 'value' => $summary['pilots_ejected']  ?? 0, 'color' => 'success'],
            ['label' => 'Retreated', 'value' => $summary['units_retreated'] ?? 0, 'color' => 'secondary'],
        ];
        ?>
        <?php foreach ($stats as $s): ?>
            <div class="col">
                <div class="card bg-secondary-subtle text-center py-2">
                    <div class="fs-5 fw-bold text-<?= $s['color'] ?>"><?= $s['value'] ?></div>
                    <div class="text-muted" style="font-size:0.7rem; text-transform:uppercase;"><?= $s['label'] ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-3">

    <!-- Left: Attackers -->
    <div class="col-md-3">

        <div class="card shadow mb-3">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <img src="<?= esc($attackerFaction['emblem_path']) ?>"
                        alt="<?= esc($attackerFaction['name']) ?>"
                        style="height:18px;">
                    <span class="fw-semibold" style="color:<?= esc($attackerFaction['color']) ?>">
                        <?= esc($attackerFaction['name']) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        <i class="bi bi-arrow-right-circle me-1 text-info"></i>Attackers
                    </span>
                    <span class="text-muted small"><?= count($attackers) ?> units</span>
                </div>
            </div>
            <?php
            $activeAttackers = array_filter(
                $attackers,
                fn($c) => in_array($c['pool_status'] ?? 'Active', ['Active', 'Crippled'])
                    && ($c['is_infantry'] ?? false
                        ? true  // infantry — only check pool_status
                        : ($c['pilot_status'] ?? 'Active') === 'Active')
            );

            $oooAttackers = array_filter(
                $attackers,
                fn($c) => in_array($c['pool_status'] ?? 'Active', ['Retreated', 'Routed', 'Destroyed'])
                    || ($c['is_infantry'] ?? false
                        ? false  // infantry — pool_status already handled above
                        : ($c['pilot_status'] ?? 'Active') !== 'Active')
            );
            ?>
            <div class="card-body p-0">
                <?php foreach ($activeAttackers as $c): ?>
                    <?php include('_combatant_row.php') ?>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($oooAttackers)): ?>
                <div class="card-header border-top border-secondary bg-dark">
                    <span class="text-muted small text-uppercase fw-bold">
                        <i class="bi bi-slash-circle me-1"></i>Out of Action
                    </span>
                </div>
                <div class="card-body p-0 opacity-50">
                    <?php foreach ($oooAttackers as $c): ?>
                        <?php include('_combatant_row.php') ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Center: Combat Details + Battle Log -->
    <div class="col-md-6">
        <!-- Combat Details -->
        <div class="card shadow mb-3">
            <div class="card-header">Battle Details</div>
            <div class="card-body">
                <p class="mb-1">
                    <strong>Location:</strong>
                    <a class="link-info" href="/location/<?= $mission['destination_location_id'] ?>">
                        <?= esc($mission['destination_name']) ?>
                    </a>
                    <span class="text-muted small">(<?= esc($mission['destination_planet']) ?>)</span>
                </p>
                <p class="mb-1">
                    <strong>Terrain:</strong> <?= esc($mission['terrain'] ?? '—') ?>
                    <?php if ($mission['location_type'] ?? null): ?>
                        · <?= esc($mission['location_type']) ?>
                    <?php endif; ?>
                </p>
                <?php
                $hasActiveFortification = !empty(array_filter(
                    $combatBuildings,
                    fn($b) => $b['type'] === 'Fortification' && $b['status'] !== 'Destroyed'
                ));
                ?>

                <?php if ($hasActiveFortification): ?>
                    <p class="mb-1">
                        <strong>Fortifications:</strong>
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-shield-fill me-1"></i>Active
                        </span>
                        <span class="text-muted small">+1/+2 to-hit vs fortified infantry</span>
                    </p>
                <?php endif; ?>
                <p class="mb-1">
                    <strong>Started:</strong> <?= esc($mission['arrived_date'] ?? '—') ?>
                </p>
                <?php if ($mission['controlling_faction_name']): ?>
                    <p class="mb-1">
                        <strong>Currently Controlled By:</strong>
                        <span style="color:<?= esc($mission['controlling_faction_color'] ?? '#fff') ?>">
                            <?= esc($mission['controlling_faction_name']) ?>
                        </span>
                    </p>
                <?php endif; ?>
                <!-- Battle Balance -->
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.7rem;">
                        <span style="color:<?= esc($attackerFaction['color']) ?>">
                            <?= esc($attackerFaction['name']) ?>
                            <span class="text-muted ms-1"><?= $balance['attacker_pct'] ?>%</span>
                        </span>
                        <span style="color:<?= esc($defenderFaction['color']) ?>">
                            <span class="text-muted me-1"><?= $balance['defender_pct'] ?>%</span>
                            <?= esc($defenderFaction['name']) ?>
                        </span>
                    </div>
                    <div class="progress" style="height:8px; background:<?= esc($defenderFaction['color']) ?>;">
                        <div class="progress-bar"
                            style="width:<?= $balance['attacker_pct'] ?>%;
                                background-color:<?= esc($attackerFaction['color']) ?>;
                                transition\: width \0\.5s ease\;\">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-text me-1"></i>Battle Log</span>
                <div class="d-flex gap-1">
                    <button class="btn btn-xs btn-outline-secondary active" id="filterAll" onclick="filterLog('all', this)">All</button>
                    <button class="btn btn-xs btn-outline-danger" id="filterDestroyed" onclick="filterLog('Destroyed', this)">Destroyed</button>
                    <button class="btn btn-xs btn-outline-warning" id="filterCrippled" onclick="filterLog('Crippled', this)">Crippled</button>
                    <button class="btn btn-xs btn-outline-info" id="filterAttacks" onclick="filterLog('Attack', this)">Attacks</button>
                </div>
            </div>
            <div id="battleLogContainer" style="max-height: 70vh; overflow-y: auto;">
                <?php if (empty($logByRound)): ?>
                    <p class="text-muted small p-3 mb-0">No battle log entries yet.</p>
                <?php else: ?>
                    <?php foreach ($logByRound as $key => $entries):
                        [$phase, $round] = explode('|', $key);
                    ?>
                        <div class="log-phase-header px-3 py-1 bg-black border-bottom border-secondary sticky-top">
                            <span class="text-muted fw-bold" style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em;">
                                <?php if ($round == 0): ?>
                                    Battle Start
                                <?php else: ?>
                                    <?= esc($phase) ?> — Round <?= esc($round) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php foreach ($entries as $entry):
                            $icon = match ($entry['log_type']) {
                                'Attack'      => ['bi-crosshair',           'text-info'],
                                'Damage'      => ['bi-shield-x',            'text-warning'],
                                'Crippled'    => ['bi-exclamation-triangle', 'text-warning'],
                                'Destroyed'   => ['bi-x-circle',            'text-danger'],
                                'Ejection'    => ['bi-person-check',        'text-success'],
                                'Retreat'     => ['bi-arrow-left-circle',   'text-secondary'],
                                'PhaseChange' => ['bi-arrow-right-circle',  'text-info'],
                                'BattleStart' => ['bi-play-circle',         'text-success'],
                                'BattleEnd'   => ['bi-stop-circle',         'text-danger'],
                                'RoundSummary' => ['bi-dash-circle',         'text-muted'],
                                default       => ['bi-dot',                 'text-muted'],
                            };
                        ?>
                            <div class="d-flex gap-2 px-3 py-1 border-bottom border-secondary log-entry"
                                data-type="<?= esc($entry['log_type']) ?>"
                                style="font-size:0.8rem;">
                                <i class="bi <?= $icon[0] ?> <?= $icon[1] ?> flex-shrink-0 mt-1" style="font-size:0.75rem;"></i>
                                <span class="<?= $entry['log_type'] === 'RoundSummary' ? 'text-muted fst-italic' : '' ?>">
                                    <?= esc($entry['description']) ?>
                                    <?php if ($entry['damage_dealt'] > 0 && $entry['log_type'] === 'Attack'): ?>
                                        <span class="text-warning ms-1">[<?= number_format($entry['damage_dealt'], 1) ?> dmg]</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">

        <!-- Right: Defenders -->
        <div class="card shadow mb-3">

            <div class="card-header">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <img src="<?= esc($defenderFaction['emblem_path']) ?>"
                        alt="<?= esc($defenderFaction['name']) ?>"
                        style="height:18px;">
                    <span class="fw-semibold" style="color:<?= esc($defenderFaction['color']) ?>">
                        <?= esc($defenderFaction['name']) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        <i class="bi bi-shield-shaded me-1 text-warning"></i>Defenders
                    </span>
                    <span class="text-muted small"><?= count($defenders) ?> units</span>
                </div>
            </div>

            <?php
            $activeDefenders = array_filter(
                $defenders,
                fn($c) => in_array($c['pool_status'] ?? 'Active', ['Active', 'Crippled'])
                    && ($c['is_infantry'] ?? false
                        ? true  // infantry — only check pool_status
                        : ($c['pilot_status'] ?? 'Active') === 'Active')
            );

            $oooDefenders = array_filter(
                $defenders,
                fn($c) => in_array($c['pool_status'] ?? 'Active', ['Retreated', 'Routed', 'Destroyed'])
                    || ($c['is_infantry'] ?? false
                        ? false  // infantry — pool_status already handled above
                        : ($c['pilot_status'] ?? 'Active') !== 'Active')
            );
            ?>

            <?php if (!empty($combatBuildings)): ?>
                <?php foreach ($combatBuildings as $cb):
                    $integrityPct = $cb['max_integrity'] > 0
                        ? round(($cb['current_integrity'] / $cb['max_integrity']) * 100)
                        : 0;
                    $intColor = $cb['status'] === 'Destroyed' ? 'danger'
                        : ($cb['status'] === 'Damaged' ? 'warning' : 'success');
                    $assignedUnits = $fortificationAssignments[$cb['combat_building_id']] ?? [];
                ?>

                    <div class="card-body p-0">

                        <div class="px-3 py-2 border-bottom border-secondary <?= $cb['status'] === 'Destroyed' ? 'opacity-50' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="small fw-semibold">
                                    <i class="bi bi-shield-fill me-1 text-<?= $intColor ?>"></i>
                                    <?= esc($cb['name']) ?>
                                </div>
                                <span class="badge bg-<?= $intColor ?>" style="font-size:0.65rem;">
                                    <?= esc($cb['status']) ?>
                                </span>
                            </div>

                            <!-- Integrity bar -->
                            <div class="mt-1" style="font-size:0.65rem;">
                                <div class="d-flex justify-content-between text-muted mb-1">
                                    <span>Integrity</span>
                                    <span><?= $cb['current_integrity'] ?>/<?= $cb['max_integrity'] ?> (<?= $integrityPct ?>%)</span>
                                </div>
                                <div class="progress" style="height:4px; background:#333;">
                                    <div class="progress-bar bg-<?= $intColor ?>"
                                        style="width:<?= $integrityPct ?>%"></div>
                                </div>
                            </div>

                            <!-- Fortification bonus indicator -->
                            <?php if ($cb['status'] !== 'Destroyed'): ?>
                                <div class="mt-1 text-muted" style="font-size:0.65rem;">
                                    <i class="bi bi-shield-check me-1"></i>
                                    +<?= $integrityPct > 50 ? 2 : 1 ?> to-hit bonus
                                    · <?= $cb['capacity'] ?? 0 ?> unit capacity
                                </div>
                            <?php endif; ?>

                            <!-- Assigned units -->
                            <?php if (!empty($assignedUnits)): ?>
                                <div class="mt-1" style="font-size:0.65rem;">
                                    <?php foreach ($assignedUnits as $au): ?>
                                        <span class="badge bg-dark border border-secondary me-1">
                                            <?= esc($au['unit_name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php foreach ($activeDefenders as $c): ?>
                    <?php include('_combatant_row.php') ?>
                <?php endforeach; ?>
                    </div>
                    <?php if (!empty($oooDefenders)): ?>
                        <div class="card-header border-top border-secondary bg-dark">
                            <span class="text-muted small text-uppercase fw-bold">
                                <i class="bi bi-slash-circle me-1"></i>Out of Action
                            </span>
                        </div>
                        <div class="card-body p-0 opacity-50">
                            <?php foreach ($oooDefenders as $c): ?>
                                <?php include('_combatant_row.php') ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
        </div>
    </div>

</div>

<script>
    function filterLog(type, btn) {
        // Update active button
        document.querySelectorAll('[id^="filter"]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Show/hide entries
        document.querySelectorAll('.log-entry').forEach(el => {
            if (type === 'all') {
                el.style.display = '';
            } else {
                el.style.display = el.dataset.type === type ? '' : 'none';
            }
        });

        // Hide phase headers if all their entries are hidden
        document.querySelectorAll('.log-phase-header').forEach(header => {
            const section = [];
            let next = header.nextElementSibling;
            while (next && !next.classList.contains('log-phase-header')) {
                if (next.classList.contains('log-entry')) section.push(next);
                next = next.nextElementSibling;
            }
            const anyVisible = section.some(el => el.style.display !== 'none');
            header.style.display = anyVisible ? '' : 'none';
        });
    }
</script>