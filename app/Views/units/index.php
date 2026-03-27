<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Units</h4>
    <div class="d-flex gap-2">
        <a href="?deactivated=<?= $showDeactivated ? '0' : '1' ?>"
            class="btn btn-sm btn-outline-secondary">
            <?= $showDeactivated ? 'Hide Deactivated' : 'Show Deactivated' ?>
        </a>
        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
            data-bs-target="#createUnitModal">
            <i class="bi bi-plus-circle me-1"></i>New Unit
        </button>
    </div>
</div>

<div class="card shadow">
    <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0" id="unitTree">
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Type</th>
                    <th>Role</th>
                    <th>Commander</th>
                    <th>Speed</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                function renderUnitRow(array $byParent, array $speedMap, $parentId, int $depth = 0): void
                {
                    if (empty($byParent[$parentId])) return;
                    foreach ($byParent[$parentId] as $u):
                        $indent = $depth * 16;
                        $speed  = $speedMap[$u['unit_id']] ?? null;
                        $statusColor = match ($u['status']) {
                            'Garrisoned'  => 'success',
                            'In Transit'  => 'info',
                            'Combat'      => 'danger',
                            'Deactivated' => 'secondary',
                            default       => 'secondary',
                        };
                ?>
                        <tr class="<?= $u['status'] === 'Deactivated' ? 'opacity-50' : '' ?>">
                            <td style="padding-left:<?= $indent ?>px">
                                <a class="link-info" href="/units/<?= esc($u['unit_id']) ?>">
                                    <?= esc($u['name']) ?>
                                </a>
                                <?php if ($u['nickname']): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis ms-1">
                                        <?= esc($u['nickname']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= esc($u['unit_type']) ?></td>
                            <td class="text-muted small"><?= esc($u['role'] ?? '—') ?></td>
                            <td class="small">
                                <?php if ($u['last_name']): ?>
                                    <?= esc($u['rank_abbr']) ?>. <?= esc($u['last_name']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?= $speed ? number_format($speed, 1) . ' kph' : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $statusColor ?>">
                                    <?= esc($u['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/units/<?= esc($u['unit_id']) ?>"
                                    class="btn btn-xs btn-outline-secondary">Manage</a>
                            </td>
                        </tr>
                        <?php renderUnitRow($byParent, $speedMap, $u['unit_id'], $depth + 1); ?>
                    <?php endforeach; ?>
                <?php
                }
                renderUnitRow($byParent, $speedMap, null, 0);
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Unit Modal -->
<div class="modal fade" id="createUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">New Unit</h5>
                <button type="button" class="btn-close btn-close-white"
                    data-bs-dismiss="modal"></button>
            </div>
            <form action="/units/store" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name"
                            class="form-control bg-dark text-light border-secondary"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nickname <span class="text-muted small">(optional)</span></label>
                        <input type="text" name="nickname"
                            class="form-control bg-dark text-light border-secondary">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select name="unit_type"
                                class="form-select bg-dark text-light border-secondary"
                                required>
                                <?php foreach ($unitTypes as $t): ?>
                                    <option><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Role <span class="text-muted small">(optional)</span></label>
                            <select name="role"
                                class="form-select bg-dark text-light border-secondary">
                                <option value="">None</option>
                                <?php foreach ($roles as $r): ?>
                                    <option><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Parent Unit <span class="text-muted small">(optional)</span></label>
                        <select name="parent_unit_id"
                            class="form-select bg-dark text-light border-secondary">
                            <option value="">No Parent (Top Level)</option>
                            <?php
                            function renderParentOptions(array $byParent, $parentId, int $depth = 0): void
                            {
                                if (empty($byParent[$parentId])) return;
                                foreach ($byParent[$parentId] as $u) {
                                    $indent = str_repeat('— ', $depth);
                                    echo '<option value="' . esc($u['unit_id']) . '">'
                                        . $indent . esc($u['name']) . ' (' . esc($u['unit_type']) . ')'
                                        . '</option>';
                                    renderParentOptions($byParent, $u['unit_id'], $depth + 1);
                                }
                            }
                            renderParentOptions($byParent, null, 0);
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-outline-info btn-sm">Create Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>