<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><?= esc($template['name']) ?></h4>
        <span class="text-muted small">
            <?= esc($template['unit_type']) ?>
            <?= $template['role'] ? ' · ' . esc($template['role']) : '' ?>
            <?php if ($template['faction']): ?>
                <span class="d-inline-flex align-items-center gap-1 ms-1">
                    <?php if (!empty($template['faction_emblem'])): ?>
                        <img src="<?= esc($template['faction_emblem']) ?>" style="height:14px; width:auto;">
                    <?php endif; ?>
                    <?= esc($template['faction']) ?>
                </span>
            <?php endif; ?>
        </span>
    </div>
    <a href="/toe" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<!-- Edit template details -->
<div class="card shadow mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Template Details</span>
        <button class="btn btn-sm btn-outline-secondary" type="button"
            data-bs-toggle="collapse" data-bs-target="#editDetails">Edit</button>
    </div>
    <div class="collapse" id="editDetails">
        <div class="card-body">
            <form action="/toe/<?= esc($template['template_id']) ?>/update" method="post">
                <?= csrf_field() ?>
                <?php include('_form.php') ?>
                <button type="submit" class="btn btn-outline-info btn-sm mt-3">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php $gradeLookup = array_column($ranks, 'full_name', 'grade'); ?>

<!-- Row 1: Personnel + Crew Assignments -->
<div class="row g-3 mb-3">

    <!-- Personnel Slots -->
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Personnel Slots</span>
                <span class="text-muted small">Ranks: <?= esc($rankFaction) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($template['personnel_slots'])): ?>
                    <table class="table table-dark table-sm mb-0">
                        <thead>
                            <tr><th>MOS</th><th>Min Rank</th><th>Max Rank</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($template['personnel_slots'] as $slot): ?>
                                <tr id="pslot-<?= $slot['slot_id'] ?>">
                                    <td><?= esc($slot['mos']) ?></td>
                                    <td><?= esc($gradeLookup[$slot['min_grade']] ?? 'Grade '.$slot['min_grade']) ?></td>
                                    <td><?= esc($gradeLookup[$slot['max_grade']] ?? 'Grade '.$slot['max_grade']) ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-danger"
                                            onclick="deleteSlot(<?= $slot['slot_id'] ?>, 'pslot-<?= $slot['slot_id'] ?>')">×</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted small p-3 mb-0">No personnel slots.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer border-secondary">
                <div class="row g-1 align-items-center">
                    <div class="col-4">
                        <select id="newMos" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <?php foreach (['MechWarrior','Tanker','Infantry','Officer'] as $mos): ?>
                                <option><?= $mos ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <select id="newMinGrade" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <?php foreach ($ranks as $r): ?>
                                <option value="<?= esc($r['grade']) ?>">
                                    <?= esc($r['grade']) ?> — <?= esc($r['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <select id="newMaxGrade" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <?php foreach ($ranks as $r): ?>
                                <option value="<?= esc($r['grade']) ?>"
                                    <?= $r['grade'] == 13 ? 'selected' : '' ?>>
                                    <?= esc($r['grade']) ?> — <?= esc($r['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-2">
                        <button class="btn btn-sm btn-outline-success w-100" onclick="addPersonnelSlot()">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Crew Assignments -->
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header">Crew Assignments</div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Link a personnel slot to an equipment slot with a crew role.
                </p>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small text-muted">Equipment Slot</label>
                        <select id="crewEqSlot" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <option value="">Select...</option>
                            <?php foreach ($template['equipment_slots'] as $slot): ?>
                                <option value="<?= $slot['slot_id'] ?>">
                                    Slot <?= $slot['slot_id'] ?> —
                                    <?= esc($slot['equipment_type']) ?>
                                    <?= $slot['weight_class'] ? '('.esc($slot['weight_class']).')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Personnel Slot</label>
                        <select id="crewPersSlot" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <option value="">Select...</option>
                            <?php foreach ($template['personnel_slots'] as $slot): ?>
                                <option value="<?= $slot['slot_id'] ?>">
                                    Slot <?= $slot['slot_id'] ?> — <?= esc($slot['mos']) ?>
                                    (<?= esc($gradeLookup[$slot['min_grade']] ?? 'Grade '.$slot['min_grade']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="form-label small text-muted">Role</label>
                        <select id="crewRole" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <?php foreach (['Pilot','Commander','Driver','Gunner','Loader','Dismount'] as $role): ?>
                                <option><?= $role ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4 d-flex align-items-end">
                        <button class="btn btn-sm btn-outline-success w-100" onclick="addCrew()">Link</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Row 2: Equipment Slots (full width) -->
<div class="card shadow mb-3">
    <div class="card-header">Equipment Slots</div>
    <div class="card-body p-0">
        <?php if (!empty($template['equipment_slots'])): ?>
            <table class="table table-dark table-sm mb-0">
                <thead>
                    <tr>
                        <th>Slot</th>
                        <th>Type</th>
                        <th>Weight</th>
                        <th>Roles</th>
                        <th>Crew Assignments</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($template['equipment_slots'] as $slot): ?>
                        <tr id="eslot-<?= $slot['slot_id'] ?>">
                            <td class="text-muted small">#<?= $slot['slot_id'] ?></td>
                            <td><?= esc($slot['equipment_type']) ?></td>
                            <td><?= esc($slot['weight_class'] ?? '—') ?></td>
                            <td>
                                <?php if ($slot['roles']): ?>
                                    <?php foreach (explode(',', $slot['roles']) as $role): ?>
                                        <span class="badge bg-dark border border-secondary me-1">
                                            <?= esc(trim($role)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Any</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php foreach ($slot['crew'] as $c): ?>
                                    <span class="badge bg-secondary me-1 mb-1">
                                        <?= esc($c['crew_role']).': (#'.$c['personnel_slot_id'].')' ?>
                                        <?= esc($gradeLookup[$c['min_grade']] ?? 'Grade '.$c['min_grade']) ?>
                                        <?= esc($c['mos']) ?>
                                        <span class="ms-1" style="cursor:pointer; color:#ff6b6b;"
                                            onclick="deleteCrew(<?= $c['crew_id'] ?>, this)">×</span>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (empty($slot['crew'])): ?>
                                    <span class="text-muted small">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-outline-danger"
                                    onclick="deleteSlot(<?= $slot['slot_id'] ?>, 'eslot-<?= $slot['slot_id'] ?>')">×</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted small p-3 mb-0">No equipment slots.</p>
        <?php endif; ?>
    </div>
    <div class="card-footer border-secondary">
        <div class="row g-2 align-items-start">
            <div class="col-3">
                <label class="form-label small text-muted mb-1">Type</label>
                <select id="newEqType" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <?php foreach (['BattleMech','Vehicle','APC','Aerospace','Infantry'] as $t): ?>
                        <option><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2">
                <label class="form-label small text-muted mb-1">Weight</label>
                <select id="newEqWeight" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">Any</option>
                    <?php foreach (['Light','Medium','Heavy','Assault'] as $w): ?>
                        <option><?= $w ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-5">
                <label class="form-label small text-muted mb-1">
                    Battlefield Roles <span class="text-muted" style="font-size:0.7rem;">(Ctrl/Cmd for multiple, optional)</span>
                </label>
                <select id="newEqRoles" class="form-select form-select-sm bg-dark text-light border-secondary" multiple size="4">
                    <?php foreach (['Ambusher','Brawler','Missile Boat','Juggernaut','Scout','Sniper','Skirmisher','Striker'] as $role): ?>
                        <option><?= $role ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2 d-flex align-items-end">
                <button class="btn btn-sm btn-outline-success w-100" onclick="addEquipmentSlot()">+ Add Slot</button>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Subunits (full width) -->
<div class="card shadow mb-3">
    <div class="card-header">Subunits</div>
    <div class="card-body p-0">
        <?php if (!empty($template['subunits'])): ?>
            <table class="table table-dark table-sm mb-0">
                <thead>
                    <tr>
                        <th>Template</th>
                        <th>Type</th>
                        <th>Role</th>
                        <th>Qty</th>
                        <th>Core</th>
                        <th>Command</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="subunitList">
                    <?php foreach ($template['subunits'] as $sub): ?>
                        <tr id="sub-<?= $sub['subunit_id'] ?>">
                            <td>
                                <a class="link-info" href="/toe/<?= esc($sub['child_template_id']) ?>">
                                    <?= esc($sub['child_name']) ?>
                                </a>
                            </td>
                            <td><?= esc($sub['child_unit_type']) ?></td>
                            <td><?= esc($sub['child_role'] ?? '—') ?></td>
                            <td><?= esc($sub['quantity']) ?></td>
                            <td><?= $sub['is_core'] ? '✓' : '—' ?></td>
                            <td><?= $sub['is_command'] ? '✓' : '—' ?></td>
                            <td>
                                <button class="btn btn-xs btn-outline-danger"
                                    onclick="deleteSubunit(<?= $sub['subunit_id'] ?>, 'sub-<?= $sub['subunit_id'] ?>')">×</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted small p-3 mb-0">No subunits attached.</p>
        <?php endif; ?>
    </div>
    <div class="card-footer border-secondary">
        <div class="row g-1 align-items-center">
            <div class="col-5">
                <select id="newSubunitTemplate" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">Select Template</option>
                    <?php foreach ($allTemplates as $t): ?>
                        <option value="<?= $t['template_id'] ?>">
                            <?= esc($t['name']) ?> (<?= esc($t['unit_type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2">
                <input type="number" id="newSubunitQty" value="1" min="1" max="99"
                    class="form-control form-control-sm bg-dark text-light border-secondary">
            </div>
            <div class="col-2 d-flex align-items-center gap-1">
                <input type="checkbox" id="newSubunitCore" class="form-check-input" checked>
                <label class="form-check-label small" for="newSubunitCore">Core</label>
            </div>
            <div class="col-2 d-flex align-items-center gap-1">
                <input type="checkbox" id="newSubunitCommand" class="form-check-input">
                <label class="form-check-label small" for="newSubunitCommand">Command</label>
            </div>
            <div class="col-1">
                <button class="btn btn-sm btn-outline-success w-100" onclick="addSubunit()">+</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete template -->
<div class="mt-3 text-end">
    <form action="/toe/<?= esc($template['template_id']) ?>/delete" method="post"
        onsubmit="return confirm('Delete this template? This cannot be undone.')">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">Delete Template</button>
    </form>
</div>

<script>
const templateId = <?= (int)$template['template_id'] ?>;

function deleteSlot(slotId, rowId) {
    if (!confirm('Delete this slot and its crew assignments?')) return;
    fetch(`/toe/slots/${slotId}/delete`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(d => {
        if (d.success) document.getElementById(rowId)?.remove();
    });
}

function addPersonnelSlot() {
    const mos      = document.getElementById('newMos').value;
    const minGrade = document.getElementById('newMinGrade').value;
    const maxGrade = document.getElementById('newMaxGrade').value;

    fetch(`/toe/${templateId}/slots/add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slot_type: 'Personnel', mos, min_grade: minGrade, max_grade: maxGrade })
    }).then(() => location.reload());
}

function addEquipmentSlot() {
    const equipment_type = document.getElementById('newEqType').value;
    const weight_class   = document.getElementById('newEqWeight').value;
    const roles          = Array.from(document.getElementById('newEqRoles').selectedOptions)
                               .map(o => o.value);

    fetch(`/toe/${templateId}/slots/add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slot_type: 'Equipment', equipment_type, weight_class, roles })
    }).then(() => location.reload());
}

function addCrew() {
    const equipSlotId     = document.getElementById('crewEqSlot').value;
    const personnelSlotId = document.getElementById('crewPersSlot').value;
    const crewRole        = document.getElementById('crewRole').value;

    if (!equipSlotId || !personnelSlotId) {
        alert('Select both an equipment slot and a personnel slot.');
        return;
    }

    fetch(`/toe/slots/${equipSlotId}/crew/add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ personnel_slot_id: personnelSlotId, crew_role: crewRole })
    }).then(() => location.reload());
}

function deleteCrew(crewId, el) {
    fetch(`/toe/crews/${crewId}/delete`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(d => {
        if (d.success) el.closest('.badge')?.remove();
    });
}

function addSubunit() {
    const childId   = document.getElementById('newSubunitTemplate').value;
    const quantity  = document.getElementById('newSubunitQty').value;
    const isCore    = document.getElementById('newSubunitCore').checked;
    const isCommand = document.getElementById('newSubunitCommand').checked;

    if (!childId) { alert('Select a template.'); return; }

    fetch(`/toe/${templateId}/subunits/add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ child_template_id: childId, quantity, is_core: isCore, is_command: isCommand })
    }).then(() => location.reload());
}

function deleteSubunit(subunitId, rowId) {
    if (!confirm('Remove this subunit?')) return;
    fetch(`/toe/subunits/${subunitId}/delete`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(d => {
        if (d.success) document.getElementById(rowId)?.remove();
    });
}
</script>