<?php
$statusColor = match ($mission['status']) {
    'Planning'   => 'secondary',
    'In Transit' => 'info',
    'Arrived'    => 'success',
    'Aborted'    => 'danger',
    default      => 'secondary',
};
$progress = $mission['transit_days'] > 0
    ? min(100, round(($mission['days_elapsed'] / $mission['transit_days']) * 100))
    : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><?= esc($mission['name']) ?></h4>
        <span class="badge bg-<?= $statusColor ?> me-1"><?= esc($mission['status']) ?></span>
        <span class="badge bg-dark border border-secondary"><?= esc($mission['mission_type']) ?></span>
    </div>
    <a href="/missions" class="btn btn-outline-secondary btn-sm">← Mission Control</a>
</div>

<div class="row g-3 mb-3">

    <!-- Mission Info -->
    <div class="col-md-5">
        <div class="card shadow mb-3">
            <div class="card-header">Mission Details</div>
            <div class="card-body">
                <p class="mb-1"><strong>Origin:</strong>
                    <a class="link-info" href="/location/<?= esc($mission['origin_location_id']) ?>">
                        <?= esc($mission['origin_name']) ?>
                    </a>
                </p>
                <p class="mb-1"><strong>Destination:</strong>
                    <a class="link-info" href="/location/<?= esc($mission['destination_location_id']) ?>">
                        <?= esc($mission['destination_name']) ?>
                    </a>
                </p>

                <?php if ($mission['status'] === 'Planning'): ?>
                    <!-- Planning estimates -->
                    <?php if ($estimatedDistanceKm !== null): ?>
                        <p class="mb-1"><strong>Est. Distance:</strong>
                            <?= number_format($estimatedDistanceKm, 1) ?> km
                            <span class="text-muted small">(<?= number_format($estimatedDistanceKm / $kmPerCoord, 2) ?> map units)</span>
                        </p>
                    <?php endif; ?>
                    <?php if ($estimatedTransitDays !== null): ?>
                        <p class="mb-1"><strong>Est. Transit:</strong> ~<?= esc($estimatedTransitDays) ?> days</p>
                        <p class="mb-1"><strong>Slowest Unit:</strong>
                            <?= number_format((float)$slowestSpeed, 1) ?> kph
                            <span class="text-muted small">
                                (<?= number_format((float)$slowestSpeed * $speedEfficiency, 1) ?> kph effective)
                            </span>
                        </p>
                    <?php else: ?>
                        <p class="text-muted small fst-italic mb-1">Assign units to see ETA estimate.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Launched mission actuals -->
                    <p class="mb-1"><strong>Launched:</strong> <?= esc($mission['launched_date']) ?></p>
                    <p class="mb-1"><strong>ETA:</strong> <?= esc($mission['eta_date']) ?></p>
                    <p class="mb-1"><strong>Distance:</strong>
                        <?= number_format((float)$distanceKm, 1) ?> km
                        <span class="text-muted small">(<?= number_format((float)$mission['distance'], 2) ?> map units)</span>
                    </p>
                    <p class="mb-1"><strong>Slowest Unit:</strong>
                        <?= number_format((float)$mission['slowest_speed'], 1) ?> kph
                        <span class="text-muted small">(<?= number_format((float)$mission['slowest_speed'] * $speedEfficiency, 1) ?> kph effective)</span>
                    </p>
                    <p class="mb-1"><strong>Transit:</strong>
                        <?= esc($mission['days_elapsed']) ?>/<?= esc($mission['transit_days']) ?> days
                    </p>
                    <?php if ($mission['status'] === 'In Transit'): ?>
                        <div class="progress mt-2" style="height:8px;">
                            <div class="progress-bar bg-info" style="width:<?= $progress ?>%"></div>
                        </div>
                        <p class="text-muted small mt-1 mb-0"><?= $progress ?>% complete</p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($mission['notes']): ?>
                    <p class="mt-2 mb-0 text-muted small"><?= esc($mission['notes']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($mission['status'] === 'Planning'): ?>
            <div class="d-flex gap-2 mb-3">
                <form action="/missions/launch/<?= esc($mission['mission_id']) ?>" method="post" class="flex-grow-1">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-success w-100"
                        onclick="return confirm('Launch this mission?')">
                        <i class="bi bi-rocket-takeoff me-1"></i> Launch
                    </button>
                </form>
                <form action="/missions/abort/<?= esc($mission['mission_id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger"
                        onclick="return confirm('Abort this mission?')">
                        Abort
                    </button>
                </form>
            </div>
        <?php elseif ($mission['status'] === 'In Transit'): ?>
            <form action="/missions/abort/<?= esc($mission['mission_id']) ?>" method="post" class="mb-3">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-warning w-100"
                    onclick="return confirm('Abort mission and return units to origin?')">
                    <i class="bi bi-arrow-return-left me-1"></i> Abort &amp; Return to Origin
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Units -->
    <div class="col-md-7">
        <div class="card shadow mb-3">
            <div class="card-header">Mission Units</div>
            <div class="card-body p-0">
                <?php if (!empty($units)): ?>
                    <table class="table table-dark table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th title="Average Morale">Mor%</th>
                                <th title="Personnel Strength">Per%</th>
                                <th title="Equipment Strength">Eqp%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($units as $u): ?>
                                <?php
                                $persColor = $u['pct_personnel'] >= 75 ? 'success' : ($u['pct_personnel'] >= 50 ? 'warning' : 'danger');
                                $eqpColor  = $u['pct_equipment'] >= 75 ? 'success' : ($u['pct_equipment'] >= 50 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td>
                                        <a class="link-info" href="/units/<?= esc($u['unit_id']) ?>">
                                            <?= esc($u['unit_chain'] ?? $u['name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($u['avg_morale'] !== null):
                                            $moraleColor = $u['avg_morale'] >= 70 ? 'success' : ($u['avg_morale'] >= 40 ? 'warning' : 'danger');
                                        ?>
                                            <span class="text-<?= $moraleColor ?>"><?= number_format($u['avg_morale'], 1) ?>%</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="text-<?= $persColor ?>"><?= number_format($u['pct_personnel'], 1) ?>%</span></td>
                                    <td><span class="text-<?= $eqpColor ?>"><?= number_format($u['pct_equipment'], 1) ?>%</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted p-3 mb-0">No units assigned.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mission Log -->
        <div class="card shadow">
            <div class="card-header">Mission Log</div>
            <div class="card-body p-0">
                <?php if (!empty($log)): ?>
                    <table class="table table-dark table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Event</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($log as $entry): ?>
                                <?php
                                $entryColor = match ($entry['event_type']) {
                                    'Launched'   => 'info',
                                    'Arrived'    => 'success',
                                    'Aborted'    => 'warning',
                                    'Combat'     => 'danger',
                                    default      => 'secondary',
                                };
                                ?>
                                <tr>
                                    <td class="text-muted small"><?= esc($entry['game_date']) ?></td>
                                    <td><span class="badge bg-<?= $entryColor ?>"><?= esc($entry['event_type']) ?></span></td>
                                    <td class="small"><?= esc($entry['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted p-3 mb-0">No log entries yet.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Edit form for Planning missions -->
<?php if ($mission['status'] === 'Planning'): ?>
    <div class="card shadow mt-3">
        <div class="card-header">Edit Mission</div>
        <div class="card-body">
            <form action="/missions/update/<?= esc($mission['mission_id']) ?>" method="post">
                <?= csrf_field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="<?= esc($mission['name']) ?>"
                            class="form-control bg-dark text-light border-secondary" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mission Type</label>
                        <select name="mission_type" class="form-select bg-dark text-light border-secondary">
                            <?php foreach (['Transfer', 'Resupply', 'Assault', 'Recon', 'Harass'] as $type): ?>
                                <option value="<?= $type ?>" <?= $mission['mission_type'] === $type ? 'selected' : '' ?>>
                                    <?= $type ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Destination</label>
                        <select name="destination_location_id" class="form-select bg-dark text-light border-secondary">
                            <?php foreach ($allLocations as $loc): ?>
                                <option value="<?= esc($loc['location_id']) ?>"
                                    <?= $mission['destination_location_id'] == $loc['location_id'] ? 'selected' : '' ?>>
                                    <?= esc($loc['name']) ?> (<?= esc($loc['planet_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Unit picker -->
                <label class="form-label">Units</label>
                <div class="row g-2 mb-3">
                    <div class="col-5">
                        <select id="availableUnits" class="form-select bg-black text-light border-secondary"
                            size="8" multiple>
                            <?php foreach ($availableUnits as $u): ?>
                                <option value="<?= esc($u['unit_id']) ?>">
                                    <?= esc($u['name']) ?> (<?= esc($u['unit_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto d-flex flex-column justify-content-center gap-2">
                        <button type="button" id="addUnit" class="btn btn-sm btn-outline-success">→</button>
                        <button type="button" id="removeUnit" class="btn btn-sm btn-outline-danger">←</button>
                    </div>
                    <div class="col-5">
                        <select id="selectedUnits" class="form-select bg-black text-light border-secondary"
                            size="8" multiple>
                            <?php foreach ($units as $u): ?>
                                <option value="<?= esc($u['unit_id']) ?>">
                                    <?= esc($u['name']) ?> (<?= esc($u['unit_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div id="unitInputs">
                    <?php foreach ($units as $u): ?>
                        <input type="hidden" name="unit_ids[]" value="<?= esc($u['unit_id']) ?>">
                    <?php endforeach; ?>
                </div>

                <input type="hidden" name="origin_location_id" value="<?= esc($mission['origin_location_id']) ?>">

                <button type="submit" class="btn btn-outline-info"
                    onclick="syncHiddenInputs()">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function moveSelected(from, to) {
            Array.from(from.selectedOptions).forEach(opt => to.appendChild(opt));
            syncHiddenInputs();
        }

        document.getElementById('addUnit').onclick = () =>
            moveSelected(document.getElementById('availableUnits'), document.getElementById('selectedUnits'));
        document.getElementById('removeUnit').onclick = () =>
            moveSelected(document.getElementById('selectedUnits'), document.getElementById('availableUnits'));

        function syncHiddenInputs() {
            const container = document.getElementById('unitInputs');
            container.innerHTML = '';
            Array.from(document.getElementById('selectedUnits').options).forEach(opt => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'unit_ids[]';
                input.value = opt.value;
                container.appendChild(input);
            });
        }
    </script>
<?php endif; ?>