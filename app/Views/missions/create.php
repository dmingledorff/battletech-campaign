<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Plan Mission</h4>
    <a href="/missions" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<form action="/missions/store" method="post">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- Left: Mission Details -->
        <div class="col-md-5">
            <div class="card shadow mb-3">
                <div class="card-header">Mission Details</div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Mission Name <span class="text-muted small">(optional — auto-generated if blank)</span></label>
                        <input type="text" name="name" id="missionName"
                            class="form-control bg-dark text-light border-secondary"
                            placeholder="e.g. Resupply Lifford">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Origin</label>
                        <select id="originPlanet" class="form-select bg-dark text-light border-secondary mb-2">
                            <option value="">Filter by Planet</option>
                            <?php foreach ($planets as $p): ?>
                                <option value="<?= esc($p['planet_id']) ?>"><?= esc($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="origin_location_id" id="originLocation"
                            class="form-select bg-dark text-light border-secondary" required>
                            <option value="">Select Origin Location</option>
                            <?php foreach ($friendlyLocations as $loc): ?>
                                <option value="<?= esc($loc['location_id']) ?>"
                                    data-planet="<?= esc($loc['planet_id']) ?>">
                                    <?= esc($loc['name']) ?> (<?= esc($loc['planet_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Destination</label>
                        <select name="destination_location_id" id="destLocation"
                            class="form-select bg-dark text-light border-secondary" required>
                            <option value="">Select Destination</option>
                            <?php foreach ($allLocations as $loc): ?>
                                <option value="<?= esc($loc['location_id']) ?>"
                                    data-planet="<?= esc($loc['planet_id']) ?>"
                                    data-faction="<?= esc($loc['controlling_faction_id'] ?? 0) ?>"
                                    data-faction-name="<?= esc($loc['faction_name'] ?? 'Uncontrolled') ?>">
                                    <?= esc($loc['name']) ?> (<?= esc($loc['planet_name']) ?>)
                                    <?php if ($loc['faction_name']): ?>
                                        — <?= esc($loc['faction_name']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mission Type</label>
                        <select name="mission_type" id="missionType"
                            class="form-select bg-dark text-light border-secondary" required>
                            <option value="">Select destination first</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3"
                            class="form-control bg-dark text-light border-secondary"
                            placeholder="Optional notes"></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right: Unit Selection -->
        <div class="col-md-7">
            <div class="card shadow mb-3">
                <div class="card-header">Select Units</div>
                <div class="card-body">

                    <p id="unitPrompt" class="text-muted small mb-2">
                        Select an origin location first to see available units.
                    </p>

                    <!-- Dual list picker -->
                    <div class="d-flex gap-2 mb-3 align-items-center">
                        <div style="flex: 1 1 0; min-width: 0;">
                            <label class="form-label small text-muted">Available Units</label>
                            <select id="availableUnits"
                                class="form-select bg-black text-light border-secondary w-100"
                                style="height: 220px;"
                                multiple>
                            </select>
                        </div>
                        <div class="d-flex flex-column gap-2 flex-shrink-0">
                            <button type="button" id="addUnit" class="btn btn-sm btn-outline-success">→</button>
                            <button type="button" id="removeUnit" class="btn btn-sm btn-outline-danger">←</button>
                        </div>
                        <div style="flex: 1 1 0; min-width: 0;">
                            <label class="form-label small text-muted">Selected Units</label>
                            <select id="selectedUnits"
                                class="form-select bg-black text-light border-secondary w-100"
                                style="height: 220px;"
                                multiple>
                            </select>
                        </div>
                    </div>
                    <!-- Hidden inputs -->
                    <div id="unitInputs"></div>

                    <!-- Personnel + Equipment preview -->
                    <div id="unitRoster" class="d-none">
                        <label class="form-label small text-muted">Selected Unit Roster</label>
                        <div id="rosterList"
                            style="max-height: 180px; overflow-y: auto;"
                            class="bg-black border border-secondary rounded p-2 small">
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-outline-info">Save Mission</button>
        <a href="/missions" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<script>
    const playerFactionId = <?= (int)($currentFaction['faction_id'] ?? 0) ?>;

    // Origin planet filter
    document.getElementById('originPlanet').addEventListener('change', function() {
        const planetId = this.value;
        Array.from(document.getElementById('originLocation').options).forEach(opt => {
            if (!opt.value) return;
            opt.style.display = (!planetId || opt.dataset.planet === planetId) ? '' : 'none';
        });
    });

    // Origin location change — filter destination, load units
    document.getElementById('originLocation').addEventListener('change', function() {
        const selectedOpt = this.options[this.selectedIndex];
        const planetId = selectedOpt?.dataset.planet;
        const locationId = this.value;

        // Filter destination to same planet, excluding selected origin
        Array.from(document.getElementById('destLocation').options).forEach(opt => {
            if (!opt.value) return;
            opt.style.display = (opt.dataset.planet === planetId && opt.value !== locationId) ? '' : 'none';
        });

        // Reset destination and mission type
        document.getElementById('destLocation').value = '';
        document.getElementById('missionType').innerHTML = '<option value="">Select destination first</option>';

        // Clear unit lists
        document.getElementById('availableUnits').innerHTML = '';
        document.getElementById('selectedUnits').innerHTML = '';
        document.getElementById('unitInputs').innerHTML = '';
        document.getElementById('unitRoster').classList.add('d-none');

        if (!locationId) return;

        document.getElementById('unitPrompt').classList.add('d-none');

        // Load available units with unit chain
        fetch(`/missions/getUnitsAtLocation/${locationId}`)
            .then(r => r.json())
            .then(units => {
                const list = document.getElementById('availableUnits');
                list.innerHTML = '';
                units.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.unit_id;
                    opt.textContent = u.unit_chain ?? u.name;
                    opt.title = u.unit_chain ?? u.name;
                    list.appendChild(opt);
                });
            });
    });

    // Destination change — set mission types based on controlling faction
    document.getElementById('destLocation').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const factionId = parseInt(opt.dataset.faction || '0');
        const isFriendly = factionId > 0 && factionId === playerFactionId;
        const isUncontrolled = factionId === 0;
        const typeSelect = document.getElementById('missionType');

        let types;
        if (isFriendly) {
            types = ['Transfer', 'Resupply'];
        } else if (isUncontrolled) {
            types = ['Recon', 'Assault'];
        } else {
            types = ['Assault', 'Recon', 'Harass'];
        }
        typeSelect.innerHTML = types.map(t => `<option value="${t}">${t}</option>`).join('');
        updateMissionName();
    });

    // Move units between lists
    function moveSelected(from, to) {
        Array.from(from.selectedOptions).forEach(opt => to.appendChild(opt));
        syncHiddenInputs();
        updateRoster();
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

    function updateRoster() {
        const selected = Array.from(document.getElementById('selectedUnits').options);
        const roster = document.getElementById('rosterList');
        const container = document.getElementById('unitRoster');

        if (!selected.length) {
            container.classList.add('d-none');
            roster.innerHTML = '';
            return;
        }

        container.classList.remove('d-none');
        roster.innerHTML = '<div class="text-muted small">Loading roster...</div>';

        const unitIds = selected.map(o => o.value);
        Promise.all(unitIds.map(id =>
            fetch(`/missions/getUnitRoster/${id}`).then(r => r.json())
        )).then(results => {
            const rows = results.flat();
            roster.innerHTML = rows.length ?
                rows.map(row =>
                    `<div class="d-flex justify-content-between py-1 border-bottom border-secondary">
                    <span>${row.rank_abbr}. ${row.last_name}${row.variant
                        ? ' <span class="text-muted">('+row.variant+')</span>'
                        : ''}</span>
                    <span class="text-muted small">${row.mos}</span>
                </div>`
                ).join('') :
                '<div class="text-muted small">No personnel found.</div>';
        });
    }

    document.getElementById('missionType').addEventListener('change', function() {
        updateMissionName();
    });

    let nameAutoGenerated = false;

    function updateMissionName() {
        const destSelect = document.getElementById('destLocation');
        const typeSelect = document.getElementById('missionType');
        const destOpt = destSelect.options[destSelect.selectedIndex];
        const type = typeSelect.value;
        const destName = destOpt?.text?.split(' (')[0]?.split(' —')[0]?.trim();
        const nameInput = document.getElementById('missionName');

        if (type && destName && destSelect.value) {
            const generated = `${type} ${destName}`;
            // Only auto-fill if blank or previously auto-generated
            if (!nameInput.value || nameAutoGenerated) {
                nameInput.value = generated;
                nameAutoGenerated = true;
            }
        }
    }

    // Track if user manually edited the name
    document.getElementById('missionName').addEventListener('input', function() {
        nameAutoGenerated = false;
    });


    document.querySelector('form').addEventListener('submit', function(e) {
        syncHiddenInputs();
        if (!document.getElementById('missionName').value) {
            e.preventDefault();
            alert('Please select a destination and mission type before saving.');
        }
    });
</script>