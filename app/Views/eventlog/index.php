<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Event Log</h4>
</div>

<!-- Filters -->
<div class="card shadow mb-3">
    <div class="card-body">
        <form method="get" action="/eventlog" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small text-muted">Type</label>
                <select name="log_type" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Types</option>
                    <?php foreach ($logTypes as $t): ?>
                        <option value="<?= esc($t) ?>" <?= ($filters['log_type'] ?? '') === $t ? 'selected' : '' ?>>
                            <?= esc($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Severity</label>
                <select name="severity" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All</option>
                    <?php foreach ($severities as $s): ?>
                        <option value="<?= esc($s) ?>" <?= ($filters['severity'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= esc($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">From</label>
                <input type="date" name="date_from"
                       value="<?= esc($filters['date_from'] ?? '') ?>"
                       class="form-control form-control-sm bg-dark text-light border-secondary">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">To</label>
                <input type="date" name="date_to"
                       value="<?= esc($filters['date_to'] ?? '') ?>"
                       class="form-control form-control-sm bg-dark text-light border-secondary">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-info">Filter</button>
                <a href="/eventlog" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
            <div class="col-md-2 text-end text-muted small pt-3">
                <?= number_format($result['total']) ?> entries
            </div>
        </form>
    </div>
</div>

<!-- Log entries -->
<div class="card shadow">
    <div class="card-body p-0">
        <?php if (empty($result['rows'])): ?>
            <p class="text-muted p-3 mb-0">No log entries found.</p>
        <?php else: ?>
            <?php $currentDate = null; ?>
            <?php foreach ($result['rows'] as $entry): ?>
                <?php if ($entry['game_date'] !== $currentDate): ?>
                    <?php $currentDate = $entry['game_date']; ?>
                    <div class="px-3 py-2 bg-black border-bottom border-secondary">
                        <span class="text-muted small fw-bold">
                            <?= date('j F Y', strtotime($entry['game_date'])) ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="d-flex gap-3 px-3 py-2 border-bottom border-secondary">
                    <div class="flex-shrink-0 pt-1">
                        <?php
                        $icon = match($entry['log_type']) {
                            'Mission'     => 'bi-crosshair',
                            'Combat'      => 'bi-fire',
                            'Supply'      => 'bi-box-seam',
                            'Maintenance' => 'bi-wrench',
                            'Personnel'   => 'bi-person',
                            'World'       => 'bi-globe',
                            'Intel'       => 'bi-eye',
                            default       => 'bi-info-circle',
                        };
                        $color = match($entry['severity']) {
                            'Warning'  => 'text-warning',
                            'Critical' => 'text-danger',
                            default    => 'text-info',
                        };
                        ?>
                        <i class="bi <?= $icon ?> <?= $color ?>"></i>
                    </div>
                    <div class="flex-fill">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="fw-semibold"><?= esc($entry['title']) ?></span>
                            <span class="badge bg-secondary ms-2 flex-shrink-0">
                                <?= esc($entry['log_type']) ?>
                            </span>
                        </div>
                        <?php if ($entry['description']): ?>
                            <div class="text-muted small mt-1"><?= esc($entry['description']) ?></div>
                        <?php endif; ?>
                        <div class="text-muted mt-1" style="font-size:0.7rem;">
                            <?php if ($entry['mission_name']): ?>
                                <a class="link-secondary" href="/missions/<?= $entry['mission_id'] ?>">
                                    <?= esc($entry['mission_name']) ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($entry['unit_name']): ?>
                                · <a class="link-secondary" href="/units/<?= $entry['unit_id'] ?>">
                                    <?= esc($entry['unit_name']) ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($entry['location_name']): ?>
                                · <a class="link-secondary" href="/location/<?= $entry['location_id'] ?>">
                                    <?= esc($entry['location_name']) ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($entry['personnel_name']): ?>
                                · <?= esc($entry['personnel_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($result['last_page'] > 1): ?>
        <div class="card-footer border-secondary d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                Page <?= $result['page'] ?> of <?= $result['last_page'] ?>
            </span>
            <div class="d-flex gap-2">
                <?php if ($result['page'] > 1): ?>
                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $result['page'] - 1])) ?>"
                       class="btn btn-sm btn-outline-secondary">← Prev</a>
                <?php endif; ?>
                <?php if ($result['page'] < $result['last_page']): ?>
                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $result['page'] + 1])) ?>"
                       class="btn btn-sm btn-outline-secondary">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>