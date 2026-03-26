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
            <label class="form-label">Mission Name</label>
            <input type="text" name="name" class="form-control bg-dark text-light border-secondary"
                   placeholder="e.g. Resupply Lifford" required>
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

          <p class="text-muted small mb-3">
            Select an origin location first to see available units.
          </p>

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small text-muted">Available Units</label>
              <select id="availableUnits" class="form-select bg-black text-light border-secondary"
                      size="12" multiple>
              </select>
            </div>
            <div class="col-auto d-flex flex-column justify-content-center gap-2">
              <button type="button" id="addUnit" class="btn btn-sm btn-outline-success">→</button>
              <button type="button" id="removeUnit" class="btn btn-sm btn-outline-danger">←</button>
            </div>
            <div class="col-6">
              <label class="form-label small text-muted">Selected Units</label>
              <select id="selectedUnits" class="form-select bg-black text-light border-secondary"
                      size="12" multiple>
              </select>
            </div>
          </div>

          <!-- Hidden inputs for selected unit IDs -->
          <div id="unitInputs"></div>

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

// Origin planet filter (keep this for filtering the origin list only)
document.getElementById('originPlanet').addEventListener('change', function() {
    const planetId = this.value;
    const opts     = document.getElementById('originLocation').options;
    for (let i = 0; i < opts.length; i++) {
        if (!opts[i].value) continue;
        opts[i].style.display = (!planetId || opts[i].dataset.planet === planetId) ? '' : 'none';
    }
});

// Single origin location change handler
document.getElementById('originLocation').addEventListener('change', function() {
    const selectedOpt = this.options[this.selectedIndex];
    const planetId    = selectedOpt?.dataset.planet;
    const locationId  = this.value;

    // Filter destination to same planet, excluding selected origin
    const destSelect = document.getElementById('destLocation');
    Array.from(destSelect.options).forEach(opt => {
        if (!opt.value) { return; }
        const samePlanet  = opt.dataset.planet === planetId;
        const notOrigin   = opt.value !== locationId;
        opt.style.display = (samePlanet && notOrigin) ? '' : 'none';
    });

    // Reset destination and mission type
    destSelect.value = '';
    document.getElementById('missionType').innerHTML =
        '<option value="">Select destination first</option>';

    // Load available units
    if (!locationId) return;
    fetch(`/missions/getUnitsAtLocation/${locationId}`)
        .then(r => r.json())
        .then(units => {
            const list = document.getElementById('availableUnits');
            list.innerHTML = '';
            units.forEach(u => {
                const opt       = document.createElement('option');
                opt.value       = u.unit_id;
                opt.textContent = `${u.name} (${u.unit_type}${u.role ? ' · ' + u.role : ''})`;
                list.appendChild(opt);
            });
        });
});

// Destination change — set mission types based on faction
document.getElementById('destLocation').addEventListener('change', function() {
    const opt            = this.options[this.selectedIndex];
    const factionId      = parseInt(opt.dataset.faction ?? 0);
    const isFriendly     = factionId === playerFactionId;
    const isUncontrolled = factionId === 0;
    const typeSelect     = document.getElementById('missionType');

    let types;
    if (isFriendly) {
        types = ['Transfer', 'Resupply'];
    } else if (isUncontrolled) {
        types = ['Recon', 'Assault'];
    } else {
        types = ['Assault', 'Recon', 'Harass'];
    }

    typeSelect.innerHTML = types.map(t => `<option value="${t}">${t}</option>`).join('');
});

// Move units between lists
function moveSelected(from, to) {
    Array.from(from.selectedOptions).forEach(opt => to.appendChild(opt));
    syncHiddenInputs();
}

document.getElementById('addUnit').onclick    = () =>
    moveSelected(document.getElementById('availableUnits'), document.getElementById('selectedUnits'));
document.getElementById('removeUnit').onclick = () =>
    moveSelected(document.getElementById('selectedUnits'), document.getElementById('availableUnits'));

function syncHiddenInputs() {
    const container = document.getElementById('unitInputs');
    container.innerHTML = '';
    Array.from(document.getElementById('selectedUnits').options).forEach(opt => {
        const input   = document.createElement('input');
        input.type    = 'hidden';
        input.name    = 'unit_ids[]';
        input.value   = opt.value;
        container.appendChild(input);
    });
}

document.querySelector('form').addEventListener('submit', syncHiddenInputs);
</script>