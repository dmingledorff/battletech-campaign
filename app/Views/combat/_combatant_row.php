<?php
// _combatant_row.php
$armorPct  = ($c['max_armor']     ?? 0) > 0 ? round(($c['current_armor']     ?? 0) / $c['max_armor']     * 100) : 0;
$structPct = ($c['max_structure'] ?? 0) > 0 ? round(($c['current_structure'] ?? 0) / $c['max_structure'] * 100) : 0;

$poolStatus  = $c['pool_status']  ?? 'Active';
$equipStatus = $c['combat_status'] ?? 'Operational';

$isCrippledSalvage = $poolStatus === 'Retreated'
    && ($c['salvage_status'] ?? 'None') === 'Available'
    && ($c['equipment_status'] ?? 'Active') !== 'Destroyed';

$isDestroyedSalvage = $poolStatus === 'Destroyed'
    && ($c['salvage_status'] ?? 'None') === 'Available';

$status = match(true) {
    $isCrippledSalvage       => 'Abandoned',
    $poolStatus === 'Retreated' => 'Retreated',
    $poolStatus === 'Routed'    => 'Routed',
    $poolStatus === 'Destroyed' => 'Destroyed',
    default => match($equipStatus) {
        'Crippled'  => 'Crippled',
        'Destroyed' => 'Destroyed',
        default     => 'Operational',
    },
};

$statusColor = match($status) {
    'Abandoned'   => 'info',
    'Retreated'   => 'secondary',
    'Routed'      => 'secondary',
    'Destroyed'   => 'danger',
    'Crippled'    => 'warning',
    default       => 'success',
};

$pilotStatus = $c['pilot_status'] ?? 'Active';
$pilotColor  = match($pilotStatus) {
    'Active'  => 'success',
    'Injured' => 'warning',
    'KIA'     => 'danger',
    'MIA'     => 'secondary',
    default   => 'secondary',
};

$heat       = (int)($c['heat_buildup'] ?? 0);
$isShutdown = (bool)($c['is_shutdown'] ?? false);

$heatBadgeColor = match(true) {
    $heat >= 4 => 'danger',
    $heat >= 3 => 'warning',
    $heat > 0  => 'warning',
    default    => 'secondary',
};

$showArmor = !($c['is_infantry'] ?? false);
?>

<div class="px-3 py-2 border-bottom border-secondary <?= in_array($status, ['Destroyed']) || $isShutdown ? 'opacity-50' : '' ?>">
    <div class="d-flex justify-content-between align-items-start">
        <div class="small">
            <span class="fw-semibold">
                <?= esc($c['chassis_name'] ?? $c['unit_name']) ?>
                <?php if ($c['variant'] ?? null): ?>
                    <span class="text-muted"><?= esc($c['variant']) ?></span>
                <?php endif; ?>
            </span>
            <span class="text-muted ms-1" style="font-size:0.7rem;"><?= esc($c['unit_name']) ?></span>
        </div>
        <div class="d-flex gap-1 align-items-center flex-wrap justify-content-end">
            <span class="badge bg-<?= $statusColor ?>" style="font-size:0.65rem;"><?= $status ?></span>

            <?php if ($isCrippledSalvage || $isDestroyedSalvage): ?>
                <span class="badge bg-info text-dark" style="font-size:0.65rem;">Salvage</span>
            <?php endif; ?>

            <?php if ($isShutdown): ?>
                <span class="badge bg-danger" style="font-size:0.65rem;">
                    <i class="bi bi-power me-1"></i>Shutdown
                </span>
            <?php elseif ($heat > 0): ?>
                <span class="badge bg-<?= $heatBadgeColor ?>" style="font-size:0.65rem;">
                    <i class="bi bi-thermometer-half me-1"></i><?= $heat ?> Heat
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($showArmor && ($c['chassis_name'] ?? null)): ?>
    <div class="row g-1 mt-1" style="font-size:0.65rem;">
        <div class="col-6">
            <div class="d-flex justify-content-between text-muted mb-1">
                <span>Armor</span>
                <span><?= $c['current_armor'] ?? 0 ?>/<?= $c['max_armor'] ?? 0 ?></span>
            </div>
            <div class="progress" style="height:4px; background:#333;">
                <div class="progress-bar bg-info" style="width:<?= $armorPct ?>%"></div>
            </div>
        </div>
        <div class="col-6">
            <div class="d-flex justify-content-between text-muted mb-1">
                <span>Structure</span>
                <span><?= $c['current_structure'] ?? 0 ?>/<?= $c['max_structure'] ?? 0 ?></span>
            </div>
            <div class="progress" style="height:4px; background:#333;">
                <div class="progress-bar <?= $structPct < 50 ? 'bg-danger' : 'bg-warning' ?>"
                     style="width:<?= $structPct ?>%"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($c['is_infantry'] ?? false): ?>
    <div class="text-muted mt-1" style="font-size:0.65rem;">
        <i class="bi bi-people me-1"></i>
        Strength: <?= (int)($c['current_strength'] ?? 0) ?>/<?= (int)($c['max_strength'] ?? 0) ?>
    </div>
    <?php endif; ?>

    <?php if ($c['last_name'] ?? null): ?>
    <div class="d-flex justify-content-between mt-1" style="font-size:0.7rem;">
        <span class="text-muted">
            <?= esc($c['rank_abbr'] ?? '') ?>. <?= esc($c['last_name']) ?>
            <span class="ms-1 badge bg-dark border border-secondary" style="font-size:0.6rem;">
                <?= esc($c['experience'] ?? 'Regular') ?>
            </span>
        </span>
        <div class="d-flex align-items-center gap-1">
            <span class="badge bg-<?= $pilotColor ?>" style="font-size:0.6rem;"><?= $pilotStatus ?></span>
            <?php if (isset($c['morale'])): ?>
                <span class="text-muted"><?= number_format((float)$c['morale'], 0) ?>% morale</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>