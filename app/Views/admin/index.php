<div class="d-flex align-items-center mb-4">
    <i class="bi bi-shield-lock me-2 fs-4 text-warning"></i>
    <h4 class="mb-0">Admin Panel</h4>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">

    <!-- Game State -->
    <div class="col-md-6">
        <div class="card shadow border-warning">
            <div class="card-header text-warning">
                <i class="bi bi-calendar-event me-1"></i>Game State
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Current Date:</strong> <?= esc($gameDate) ?>
                    &nbsp;·&nbsp;
                    <strong>Tick:</strong> <?= esc($gameState['tick_count'] ?? 0) ?>
                </p>
                <div class="row g-2">
                    <div class="col-8">
                        <form action="/admin/setDate" method="post" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="date" name="game_date"
                                value="<?= esc($gameState['current_date'] ?? '') ?>"
                                class="form-control form-control-sm bg-dark text-light border-secondary">
                            <button type="submit" class="btn btn-sm btn-outline-warning flex-shrink-0">
                                Set Date
                            </button>
                        </form>
                    </div>
                    <div class="col-4">
                        <form action="/admin/tick" method="post">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-info w-100"
                                onclick="return confirm('Process game tick?')">
                                <i class="bi bi-skip-forward me-1"></i>Tick Game
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Units -->
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <i class="bi bi-gear me-1"></i>Generate Unit from TOE
            </div>
            <div class="card-body">
                <form action="/admin/generateUnit" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small text-muted">TOE Template</label>
                            <select name="template_id"
                                class="form-select form-select-sm bg-dark text-light border-secondary"
                                required>
                                <option value="">Select template...</option>
                                <?php
                                $grouped = [];
                                foreach ($templates as $t) {
                                    $grouped[$t['unit_type']][] = $t;
                                }
                                foreach (['Regiment', 'Battalion', 'Company', 'Lance', 'Platoon', 'Squad'] as $type):
                                    if (empty($grouped[$type])) continue;
                                ?>
                                    <optgroup label="<?= $type ?>">
                                        <?php foreach ($grouped[$type] as $t): ?>
                                            <option value="<?= $t['template_id'] ?>">
                                                <?= esc($t['name']) ?>
                                                <?= $t['faction'] ? ' (' . esc($t['faction']) . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">
                                Unit Name <span class="text-muted">(optional — uses template name if blank)</span>
                            </label>
                            <input type="text" name="unit_name"
                                class="form-control form-control-sm bg-dark text-light border-secondary"
                                placeholder="e.g. Hansen's Roughriders">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">Allegiance</label>
                            <select name="allegiance"
                                class="form-select form-select-sm bg-dark text-light border-secondary"
                                required>
                                <?php foreach ($factions as $f): ?>
                                    <option value="<?= esc($f['house']) ?>">
                                        <?= esc($f['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">Starting Location</label>
                            <select name="location_id"
                                class="form-select form-select-sm bg-dark text-light border-secondary"
                                required>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= esc($loc['location_id']) ?>">
                                        <?= esc($loc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-outline-info w-100">
                                <i class="bi bi-plus-circle me-1"></i>Generate
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Move Unit -->
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <i class="bi bi-arrows-move me-1"></i>Move Unit
            </div>
            <div class="card-body">
                <form action="/admin/moveUnit" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small text-muted">Unit</label>
                            <select name="unit_id"
                                class="form-select form-select-sm bg-dark text-light border-secondary"
                                required>
                                <option value="">Select unit...</option>
                                <?php
                                $byType = [];
                                foreach ($units as $u) {
                                    $byType[$u['unit_type']][] = $u;
                                }
                                foreach ($unitTypes as $type):
                                    if (empty($byType[$type])) continue;
                                ?>
                                    <optgroup label="<?= $type ?>">
                                        <?php foreach ($byType[$type] as $u): ?>
                                            <option value="<?= $u['unit_id'] ?>"
                                                title="<?= esc($u['unit_chain']) ?>">
                                                <?= esc($u['unit_chain']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Destination</label>
                            <select name="location_id"
                                class="form-select form-select-sm bg-dark text-light border-secondary"
                                required>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= esc($loc['location_id']) ?>">
                                        <?= esc($loc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 d-flex align-items-center gap-2">
                            <input type="checkbox" name="move_subunits" value="1"
                                class="form-check-input" id="moveSubunits">
                            <label class="form-check-label small" for="moveSubunits">
                                Move all subunits too
                            </label>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-outline-info w-100">
                                <i class="bi bi-arrows-move me-1"></i>Move Unit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Send Log Message -->
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <i class="bi bi-megaphone me-1"></i>Send Log Message
            </div>
            <div class="card-body">
                <form action="/admin/sendLog" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small text-muted">Recipient</label>
                            <select name="faction_id"
                                class="form-select form-select-sm bg-dark text-light border-secondary">
                                <option value="">Global (all)</option>
                                <option value="all">All factions (individual)</option>
                                <?php foreach ($factions as $f): ?>
                                    <option value="<?= esc($f['faction_id']) ?>">
                                        <?= esc($f['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label small text-muted">Type</label>
                            <select name="log_type"
                                class="form-select form-select-sm bg-dark text-light border-secondary">
                                <?php foreach ($logTypes as $t): ?>
                                    <option><?= esc($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label small text-muted">Severity</label>
                            <select name="severity"
                                class="form-select form-select-sm bg-dark text-light border-secondary">
                                <?php foreach ($severities as $s): ?>
                                    <option><?= esc($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Title</label>
                            <input type="text" name="title"
                                class="form-control form-control-sm bg-dark text-light border-secondary"
                                required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">
                                Description <span class="text-muted">(optional)</span>
                            </label>
                            <textarea name="description" rows="2"
                                class="form-control form-control-sm bg-dark text-light border-secondary"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-outline-info w-100">
                                <i class="bi bi-send me-1"></i>Send Message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>