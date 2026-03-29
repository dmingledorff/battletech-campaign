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
                <table class="table table-dark table-sm mb-0">
                    <thead>
                        <tr>
                            <th>MOS</th>
                            <th>Min Rank</th>
                            <th>Max Rank</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="personnelSlots">
                        <?php if (empty($template['personnel_slots'])): ?>
                            <tr id="personnelEmpty">
                                <td colspan="4" class="text-muted small p-3">No personnel slots.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($template['personnel_slots'] as $slot): ?>
                                <tr id="pslot-<?= $slot['slot_id'] ?>">
                                    <td><?= esc($slot['mos']) ?></td>
                                    <td><?= esc($gradeLookup[$slot['min_grade']] ?? 'Grade ' . $slot['min_grade']) ?></td>
                                    <td><?= esc($gradeLookup[$slot['max_grade']] ?? 'Grade ' . $slot['max_grade']) ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-outline-danger"
                                            onclick="deleteSlot(<?= $slot['slot_id'] ?>, 'pslot-<?= $slot['slot_id'] ?>')">×</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer border-secondary">
                <div class="row g-1 align-items-center">
                    <div class="col-4">
                        <select id="newMos" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <?php foreach ($mosTypes as $mos): ?>
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
                                <option value="<?= $slot['slot_id'] ?>"
                                    data-type="<?= esc($slot['equipment_type']) ?>"
                                    data-weight="<?= esc($slot['weight_class'] ?? '') ?>"
                                    data-chassis="<?= esc($slot['chassis_id'] ?? '') ?>">
                                    Slot <?= $slot['slot_id'] ?> —
                                    <?= esc($slot['equipment_type']) ?>
                                    <?= $slot['weight_class'] ? ' (' . esc($slot['weight_class']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="crewHint"></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Personnel Slot</label>
                        <select id="crewPersSlot" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <option value="">Select...</option>
                            <?php foreach ($template['personnel_slots'] as $slot): ?>
                                <option value="<?= $slot['slot_id'] ?>">
                                    Slot <?= $slot['slot_id'] ?> — <?= esc($slot['mos']) ?>
                                    (<?= esc($gradeLookup[$slot['min_grade']] ?? 'Grade ' . $slot['min_grade']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="form-label small text-muted">Role</label>
                        <select id="crewRole" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <?php foreach ($crewRoles as $role): ?>
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
<!-- Equipment Slots (full width) -->
<div class="card shadow mb-3">
    <div class="card-header">Equipment Slots</div>
    <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0">
            <thead>
                <tr>
                    <th>Slot</th>
                    <th>Type</th>
                    <th>Weight</th>
                    <th>Chassis</th>
                    <th>Roles</th>
                    <th>Crew Assignments</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="equipmentSlots">
                <?php if (empty($template['equipment_slots'])): ?>
                    <tr id="equipmentEmpty">
                        <td colspan="6" class="text-muted small p-3">No equipment slots.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($template['equipment_slots'] as $slot): ?>
                        <tr id="eslot-<?= $slot['slot_id'] ?>">
                            <td class="text-muted small">#<?= esc($slot['slot_id']) ?></td>
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
                                <?php if (!empty($slot['chassis_name'])): ?>
                                    <span class="text-muted small">
                                        <?= esc($slot['chassis_name']) ?>
                                        <?= esc($slot['chassis_variant'] ?? '') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td id="crew-<?= $slot['slot_id'] ?>">
                                <?php if (empty($slot['crew'])): ?>
                                    <span class="text-muted small">None</span>
                                <?php else: ?>
                                    <?php foreach ($slot['crew'] as $c): ?>
                                        <span class="badge bg-secondary me-1 mb-1">
                                            <?= esc($c['crew_role']) ?>:
                                            <?= esc($gradeLookup[$c['min_grade']] ?? 'Grade ' . $c['min_grade']) ?>
                                            <?= esc($c['mos']) ?>
                                            <span class="ms-1" style="cursor:pointer; color:#ff6b6b;"
                                                onclick="deleteCrew(<?= $c['crew_id'] ?>, this)">×</span>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-outline-danger"
                                    onclick="deleteSlot(<?= $slot['slot_id'] ?>, 'eslot-<?= $slot['slot_id'] ?>')">×</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer border-secondary">
        <div class="row g-2 align-items-start">
            <div class="col-3">
                <label class="form-label small text-muted">Type</label>
                <select id="newEqType" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <?php foreach ($eqTypes as $t): ?>
                        <option><?= esc($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2">
                <label class="form-label small text-muted">Weight</label>
                <select id="newEqWeight" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">Any</option>
                    <?php foreach ($weights as $w): ?>
                        <option><?= esc($w) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-5">
                <label class="form-label small text-muted">
                    Chassis
                    <span class="text-muted" style="font-size:0.7rem;">(optional — locks to specific chassis)</span>
                </label>
                <select id="newEqChassis" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">Any (random by type/weight)</option>
                </select>
                <div id="newSlotCrewHint" class="mt-1"></div>
            </div>
            <div class="col-2 d-flex align-items-end">
                <button class="btn btn-sm btn-outline-success w-100"
                    onclick="addEquipmentSlot()">+ Add Slot</button>
            </div>
        </div>
        <!-- Roles on its own row since it needs height -->
        <div class="row g-2 mt-1">
            <div class="col-10">
                <label class="form-label small text-muted">
                    Battlefield Roles
                    <span class="text-muted" style="font-size:0.7rem;">(Ctrl/Cmd for multiple, optional)</span>
                </label>
                <select id="newEqRoles"
                    class="form-select form-select-sm bg-dark text-light border-secondary"
                    multiple size="4">
                    <?php foreach ($slotRoles as $role): ?>
                        <option><?= esc($role) ?></option>
                    <?php endforeach; ?>
                </select>
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
    const allChassis = <?= json_encode(array_map(fn($c) => [
                            'chassis_id'       => $c['chassis_id'],
                            'name'             => $c['name'],
                            'variant'          => $c['variant'] ?? '',
                            'type'             => $c['type'],
                            'weight_class'     => $c['weight_class'],
                            'battlefield_role' => $c['battlefield_role'],
                        ], $chassis)) ?>;
    const templateId = <?= (int)$template['template_id'] ?>;

    function deleteSlot(slotId, rowId) {
        if (!confirm('Delete this slot and its crew assignments?')) return;
        fetch(`/toe/slots/${slotId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(r => r.json()).then(d => {
            if (d.success) document.getElementById(rowId)?.remove();
        });
    }

    function addPersonnelSlot() {
        const mosSelect = document.getElementById('newMos');
        const minGradeSelect = document.getElementById('newMinGrade');
        const maxGradeSelect = document.getElementById('newMaxGrade');

        const mos = mosSelect.value;
        const minGrade = minGradeSelect.value;
        const maxGrade = maxGradeSelect.value;
        const minName = minGradeSelect.options[minGradeSelect.selectedIndex].text.split(' — ')[1] ?? minGradeSelect.options[minGradeSelect.selectedIndex].text;
        const maxName = maxGradeSelect.options[maxGradeSelect.selectedIndex].text.split(' — ')[1] ?? maxGradeSelect.options[maxGradeSelect.selectedIndex].text;

        fetch(`/toe/${templateId}/slots/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    slot_type: 'Personnel',
                    mos,
                    min_grade: minGrade,
                    max_grade: maxGrade
                })
            })
            .then(r => r.json())
            .then(d => {
                if (!d.success && !d.slot_id) {
                    alert('Error adding slot.');
                    return;
                }

                // Remove empty placeholder if present
                document.getElementById('personnelEmpty')?.remove();

                // Append new row directly to tbody by id
                const tbody = document.getElementById('personnelSlots');
                const tr = document.createElement('tr');
                tr.id = `pslot-${d.slot_id}`;
                tr.innerHTML = `
            <td>${mos}</td>
            <td>${minName}</td>
            <td>${maxName}</td>
            <td>
                <button class="btn btn-xs btn-outline-danger"
                    onclick="deleteSlot(${d.slot_id}, 'pslot-${d.slot_id}')">×</button>
            </td>
        `;
                tbody.appendChild(tr);

                // Add to crew personnel dropdown
                const persSelect = document.getElementById('crewPersSlot');
                const opt = document.createElement('option');
                opt.value = d.slot_id;
                opt.textContent = `Slot ${d.slot_id} — ${mos} (${minName})`;
                persSelect.appendChild(opt);
            });
    }

    function addEquipmentSlot() {
        const typeSelect = document.getElementById('newEqType');
        const weightSelect = document.getElementById('newEqWeight');
        const rolesSelect = document.getElementById('newEqRoles');
        const chassisSelect = document.getElementById('newEqChassis');

        const equipment_type = typeSelect.value;
        const weight_class = weightSelect.value;
        const roles = Array.from(rolesSelect.selectedOptions).map(o => o.value);
        const chassis_id = chassisSelect.value || null;

        fetch(`/toe/${templateId}/slots/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    slot_type: 'Equipment',
                    equipment_type,
                    weight_class,
                    roles,
                    chassis_id: chassis_id || null
                })
            })
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    alert('Error adding slot.');
                    return;
                }

                const tbody = document.getElementById('equipmentSlots');
                const tr = document.createElement('tr');
                tr.id = `eslot-${d.slot_id}`;
                const roleBadges = roles.length ?
                    roles.map(r => `<span class="badge bg-dark border border-secondary me-1">${r}</span>`).join('') :
                    '<span class="text-muted small">Any</span>';
                const chassisLabel = chassisSelect.value ?
                    `<span class="text-muted small">${chassisSelect.options[chassisSelect.selectedIndex].text}</span>` :
                    '—';

                tr.innerHTML = `
                    <td class="text-muted small">#${d.slot_id}</td>
                    <td>${equipment_type}</td>
                    <td>${weight_class || '—'}</td>
                    <td>${chassisLabel}</td>
                    <td>${roleBadges}</td>
                    <td id="crew-${d.slot_id}">
                        <span class="text-muted small">None</span>
                    </td>
                    <td>
                        <button class="btn btn-xs btn-outline-danger"
                            onclick="deleteSlot(${d.slot_id}, 'eslot-${d.slot_id}')">×</button>
                    </td>
                `;
                tbody.appendChild(tr);

                const eqSelect = document.getElementById('crewEqSlot');
                const opt = document.createElement('option');
                opt.value = d.slot_id;
                opt.textContent = `Slot ${d.slot_id} — ${equipment_type}${weight_class ? ' (' + weight_class + ')' : ''}`;
                opt.dataset.type = equipment_type;
                opt.dataset.weight = weight_class;
                eqSelect.appendChild(opt);

                document.getElementById('equipmentEmpty')?.remove();
            });
    }

    function addCrew() {
        const eqSelect = document.getElementById('crewEqSlot');
        const persSelect = document.getElementById('crewPersSlot');
        const roleSelect = document.getElementById('crewRole');

        const equipSlotId = eqSelect.value;
        const personnelSlotId = persSelect.value;
        const crewRole = roleSelect.value;

        if (!equipSlotId || !personnelSlotId) {
            alert('Select both an equipment slot and a personnel slot.');
            return;
        }

        const persOpt = persSelect.options[persSelect.selectedIndex];
        const persText = persOpt.text; // "Slot 139 — Tech (1 — Private)"
        // Extract just the rank name from inside parentheses, strip grade number
        const rankMatch = persText.match(/\(([^)]+)\)/);
        const rankRaw = rankMatch ? rankMatch[1] : '';
        const rankName = rankRaw.includes(' — ') ? rankRaw.split(' — ')[1] : rankRaw;
        const mosText = persText.split(' — ')[1]?.split(' (')[0] ?? '';
        const badgeLabel = `${crewRole}: ${rankName} ${mosText}`;

        fetch(`/toe/slots/${equipSlotId}/crew/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    personnel_slot_id: personnelSlotId,
                    crew_role: crewRole
                })
            })
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    alert('Error linking crew.');
                    return;
                }

                // Find the crew cell for this equipment slot and append badge
                const crewCell = document.getElementById(`crew-${equipSlotId}`);
                if (crewCell) {
                    // Remove "None" placeholder if present
                    const none = crewCell.querySelector('.text-muted');
                    if (none) none.remove();

                    const badge = document.createElement('span');
                    badge.className = 'badge bg-secondary me-1 mb-1';
                    badge.innerHTML = `
                        ${badgeLabel}
                        <span class="ms-1" style="cursor:pointer; color:#ff6b6b;"
                            onclick="deleteCrew(${d.crew_id}, this)">×</span>
                    `;
                    crewCell.appendChild(badge);
                }
                // Keep all selections — user may want to link same personnel to another slot
            });
    }

    function deleteCrew(crewId, el) {
        fetch(`/toe/crews/${crewId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(r => r.json()).then(d => {
            if (d.success) el.closest('.badge')?.remove();
        });
    }

    function addSubunit() {
        const childId = document.getElementById('newSubunitTemplate').value;
        const quantity = document.getElementById('newSubunitQty').value;
        const isCore = document.getElementById('newSubunitCore').checked;
        const isCommand = document.getElementById('newSubunitCommand').checked;

        if (!childId) {
            alert('Select a template.');
            return;
        }

        fetch(`/toe/${templateId}/subunits/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                child_template_id: childId,
                quantity,
                is_core: isCore,
                is_command: isCommand
            })
        }).then(() => location.reload());
    }

    function deleteSubunit(subunitId, rowId) {
        if (!confirm('Remove this subunit?')) return;
        fetch(`/toe/subunits/${subunitId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(r => r.json()).then(d => {
            if (d.success) document.getElementById(rowId)?.remove();
        });
    }

    document.getElementById('crewEqSlot').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const chassisId = opt.dataset.chassis;

        document.getElementById('crewHint').innerHTML = '';

        if (!this.value || !chassisId) return; // no hint without specific chassis

        fetch(`/toe/crewRequirements?chassis_id=${chassisId}`)
            .then(r => r.json())
            .then(roles => renderCrewHint(roles, 'crewHint', 'Crew'));
    });

    document.getElementById('newEqChassis').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!this.value) {
            document.getElementById('newSlotCrewHint').innerHTML = '';
            return;
        }

        // Auto-fill type and weight from chassis
        const typeSelect = document.getElementById('newEqType');
        const weightSelect = document.getElementById('newEqWeight');
        Array.from(typeSelect.options).forEach(o => {
            if (o.value === opt.dataset.type) o.selected = true;
        });
        Array.from(weightSelect.options).forEach(o => {
            if (o.value === opt.dataset.weight) o.selected = true;
        });
    });

    function filterChassis() {
        const type = document.getElementById('newEqType').value;
        const weight = document.getElementById('newEqWeight').value;
        const roles = Array.from(document.getElementById('newEqRoles').selectedOptions).map(o => o.value);
        const select = document.getElementById('newEqChassis');

        // Reset
        select.innerHTML = '<option value="">Any (random by type/weight)</option>';
        document.getElementById('newSlotCrewHint').innerHTML = '';

        const filtered = allChassis.filter(c => {
            if (c.type !== type) return false;
            if (weight && c.weight_class !== weight) return false;
            if (roles.length && !roles.includes(c.battlefield_role)) return false;
            return true;
        });

        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.chassis_id;
            opt.dataset.type = c.type;
            opt.dataset.weight = c.weight_class;
            opt.textContent = `${c.name} ${c.variant} (${c.weight_class})`.trim();
            select.appendChild(opt);
        });

        // Disable if no matches
        select.disabled = filtered.length === 0;
    }

    document.getElementById('newEqType').addEventListener('change', function() {
        // Reset weight to Any when type changes
        document.getElementById('newEqWeight').value = '';
        filterChassis();
    });

    document.getElementById('newEqWeight').addEventListener('change', filterChassis);
    document.getElementById('newEqRoles').addEventListener('change', filterChassis);

    document.getElementById('newEqChassis').addEventListener('change', function() {
        if (!this.value) {
            document.getElementById('newSlotCrewHint').innerHTML = '';
            return;
        }

        const opt = this.options[this.selectedIndex];

        // Auto-fill weight if not already set
        const weightSelect = document.getElementById('newEqWeight');
        if (!weightSelect.value) {
            Array.from(weightSelect.options).forEach(o => {
                if (o.value === opt.dataset.weight) o.selected = true;
            });
        }

        // Show crew hint
        fetch(`/toe/crewRequirements?chassis_id=${this.value}`)
            .then(r => r.json())
            .then(roles => renderCrewHint(roles, 'newSlotCrewHint', 'Required crew'));
    });

    function groupCrewRoles(roles) {
        // Group by crew_role + required_mos + is_required
        const groups = {};
        roles.forEach(r => {
            const key = `${r.crew_role}|${r.required_mos ?? ''}|${r.is_required}`;
            if (!groups[key]) {
                groups[key] = {
                    ...r,
                    count: 0
                };
            }
            groups[key].count++;
        });
        return Object.values(groups);
    }

    function renderCrewHint(roles, targetId, label = 'Crew') {
        if (!roles.length) {
            document.getElementById(targetId).innerHTML = '';
            return;
        }

        const grouped = groupCrewRoles(roles);
        const badges = grouped.map(r => {
            const mos = r.required_mos ? ` (${r.required_mos})` : ' (Any)';
            const count = r.count > 1 ? ` ×${r.count}` : '';
            const optional = !r.is_required ?
                '<span class="opacity-50 ms-1" style="font-size:0.7rem;">opt</span>' :
                '';
            return `<span class="badge ${r.is_required ? 'bg-warning text-dark' : 'bg-dark border border-secondary'} me-1 mb-1">
            ${r.crew_role}${mos}${count}${optional}
        </span>`;
        }).join('');

        document.getElementById(targetId).innerHTML =
            `<div class="mt-1 small text-muted">${label}: ${badges}</div>`;
    }

    // Init on page load
    filterChassis();
</script>