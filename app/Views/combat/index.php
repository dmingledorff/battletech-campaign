<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-crosshair me-2 text-danger"></i>Combat Operations</h4>
</div>

<?php if (!empty($active)): ?>
<div class="mb-4">
    <h6 class="text-danger text-uppercase small fw-bold mb-2">
        <i class="bi bi-fire me-1"></i>Active Engagements
    </h6>
    <div class="row g-3">
        <?php foreach ($active as $m): ?>
        <div class="col-md-6">
            <div class="card shadow border-danger border-opacity-50">
                <div class="card-header d-flex justify-content-between align-items-center bg-danger bg-opacity-10">
                    <span class="d-flex align-items-center gap-2">
                        <img src="/<?= esc($m['faction_emblem']) ?>" style="height:18px;">
                        <span class="fw-semibold"><?= esc($m['name']) ?></span>
                    </span>
                    <span class="badge bg-danger">
                        <i class="bi bi-fire me-1"></i><?= esc($m['combat_phase'] ?? 'Combat') ?>
                        — Round <?= esc($m['combat_round'] ?? 0) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold">
                                <?= esc($m['destination_name']) ?>
                                <span class="text-muted small">(<?= esc($m['destination_planet']) ?>)</span>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="text-muted small">Terrain</div>
                            <div><?= esc($m['terrain'] ?? '—') ?></div>
                        </div>
                        <div class="col-3">
                            <div class="text-muted small">Units</div>
                            <div><?= esc($m['unit_count']) ?></div>
                        </div>
                    </div>
                    <?php if (!empty($m['summary'])): ?>
                    <div class="d-flex gap-3" style="font-size:0.75rem;">
                        <span class="text-muted">
                            <i class="bi bi-crosshair me-1"></i><?= $m['summary']['total_attacks'] ?? 0 ?> attacks
                        </span>
                        <span class="text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i><?= $m['summary']['units_crippled'] ?? 0 ?> crippled
                        </span>
                        <span class="text-danger">
                            <i class="bi bi-x-circle me-1"></i><?= $m['summary']['units_destroyed'] ?? 0 ?> destroyed
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer border-secondary d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Started: <?= esc($m['launched_date'] ?? '—') ?></span>
                    <a href="/combat/<?= $m['mission_id'] ?>" class="btn btn-sm btn-outline-danger">
                        View Battle →
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($concluded)): ?>
<div>
    <h6 class="text-muted text-uppercase small fw-bold mb-2">
        <i class="bi bi-archive me-1"></i>Recent Battles
    </h6>
    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-dark table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Battle</th>
                        <th>Location</th>
                        <th>Terrain</th>
                        <th>Rounds</th>
                        <th>Destroyed</th>
                        <th>Outcome</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($concluded as $m):
                        $attackerWon       = ($m['status'] === 'Arrived');
                        $currentIsAttacker = ((int)$m['faction_id'] === (int)$currentFaction['faction_id']);
                        $weWon             = ($attackerWon && $currentIsAttacker)
                                          || (!$attackerWon && !$currentIsAttacker);
                        $summary           = $m['summary'] ?? [];
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= esc($m['faction_emblem']) ?>" style="height:16px; opacity:0.7;">
                                <a class="link-info" href="/combat/<?= $m['mission_id'] ?>">
                                    <?= esc($m['name']) ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <?= esc($m['destination_name']) ?>
                            <span class="text-muted small">(<?= esc($m['destination_planet']) ?>)</span>
                        </td>
                        <td class="text-muted small"><?= esc($m['terrain'] ?? '—') ?></td>
                        <td class="text-muted"><?= esc($summary['total_rounds'] ?? 0) ?></td>
                        <td>
                            <span class="<?= ($summary['units_destroyed'] ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
                                <?= $summary['units_destroyed'] ?? 0 ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $weWon ? 'bg-success' : 'bg-danger' ?>">
                                <?= $weWon ? 'Victory' : 'Defeated' ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= esc($m['arrived_date'] ?? '—') ?></td>
                        <td>
                            <a href="/combat/<?= $m['mission_id'] ?>" class="btn btn-xs btn-outline-secondary">
                                Report
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($active) && empty($concluded)): ?>
<p class="text-muted">No combat engagements on record.</p>
<?php endif; ?>