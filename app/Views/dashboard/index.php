<div class="row mb-3">
  <div class="col">
    <div class="card bg-secondary-subtle text-light">
      <div class="card-body d-flex gap-4">
        <div><span class="fs-4 fw-bold"><?= esc(number_format($totals['units'])) ?></span>
          <div class="text-secondary">Units</div>
        </div>
        <div><span class="fs-4 fw-bold"><?= esc(number_format($totals['personnel'])) ?></span>
          <div class="text-secondary">Personnel</div>
        </div>
        <div><span class="fs-4 fw-bold"><?= esc(number_format($totals['equipment'])) ?></span>
          <div class="text-secondary">Equipment</div>
        </div>
        <div><span class="fs-4 fw-bold"><?= esc(number_format($totals['supply_req'], 2)) ?></span>
          <div class="text-secondary">Total Required Supply</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">

    <div class="card shadow-sm">
      <div class="card-header">Unit Summary</div>
      <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0">
          <thead>
            <tr>
              <th>Unit</th>
              <th>Type</th>
              <th>Pers</th>
              <th>Eqp</th>
              <th>Supply (Current / Required)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Renders regiment (parent_unit_id = NULL) first, then all descendants with subtle indentation.
            function renderSummaryTable($children, $summary, $parentId = null, $depth = 0)
            {
              if (!isset($children[$parentId])) return;

              foreach ($children[$parentId] as $u) {
                $row = $summary[$u['unit_id']] ?? null;

                // Subtle indentation: 8px per level so the tree stays compact
                $pad = 8 * $depth;

                // Safely choose rolled totals if present; fall back to direct counts
                $pers = $row['rolled_personnel'] ?? $row['personnel_count'] ?? 0;
                $eqp  = $row['rolled_equipment'] ?? $row['equipment_count'] ?? 0;
                $req  = $row['rolled_supply'] ?? $row['required_supply'] ?? 0.0;
                $curr = $row['current_supply'] ?? 0.0;

                echo '<tr>';
                echo '<td style="padding-left:' . $pad . 'px">';
                echo '<a class="link-info" href="/units/' . esc($u['unit_id']) . '">' . esc($u['name']) . '</a>';
                if (!empty($u['nickname'])) {
                  echo ' <span class="badge bg-info-subtle text-info-emphasis">' . esc($u['nickname']) . '</span>';
                }
                echo '</td>';
                echo '<td>' . esc($u['unit_type']) . '</td>';
                echo '<td>' . esc($pers) . '</td>';
                echo '<td>' . esc($eqp) . '</td>';
                echo '<td>' . esc(number_format($curr, 2)) . ' / ' . esc(number_format($req, 2)) . '</td>';
                echo '</tr>';

                // Recurse into children
                renderSummaryTable($children, $summary, $u['unit_id'], $depth + 1);
              }
            }

            // ✅ Start from top-level (NULL parent) so the regiment appears first
            renderSummaryTable($children, $summary, null, 0);
            ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Personnel Roster</span>
        <div class="d-flex gap-2 align-items-center">
          <button id="clearFilters" class="btn btn-xs btn-outline-secondary">Clear Filters</button>
          <span id="rosterTotal" class="badge bg-secondary"></span>
        </div>
      </div>

      <div class="card-body border-bottom border-secondary pb-3">

        <!-- Group 1: Unit Hierarchy -->
        <p class="text-muted small mb-1 fw-bold text-uppercase">Unit</p>
        <div class="row g-2 mb-1">
          <div class="col-6">
            <select id="filterRegiment" class="form-select form-select-sm bg-dark text-light border-secondary">
              <option value="">All Regiments</option>
              <?php foreach ($topUnits as $u): ?>
                <option value="<?= esc($u['unit_id']) ?>"
                  <?= isset($savedFilters['_regiment']) && $savedFilters['_regiment'] == $u['unit_id'] ? 'selected' : '' ?>>
                  <?= esc($u['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <select id="filterBattalion" class="form-select form-select-sm bg-dark text-light border-secondary" disabled>
              <option value="">— Battalion</option>
            </select>
          </div>
          <div class="col-6">
            <select id="filterCompany" class="form-select form-select-sm bg-dark text-light border-secondary" disabled>
              <option value="">— Company</option>
            </select>
          </div>
          <div class="col-6">
            <select id="filterLance" class="form-select form-select-sm bg-dark text-light border-secondary" disabled>
              <option value="">— Lance / Platoon</option>
            </select>
          </div>
        </div>
        <div id="unitBreadcrumb" class="text-info small mb-3" style="min-height:1.2em;"></div>

        <!-- Group 2: Location -->
        <p class="text-muted small mb-1 fw-bold text-uppercase">Location</p>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <select id="filterPlanet" class="form-select form-select-sm bg-dark text-light border-secondary">
              <option value="">All Planets</option>
              <?php foreach ($planets as $pl): ?>
                <option value="<?= esc($pl['planet_id']) ?>"
                  <?= isset($savedFilters['_planet']) && $savedFilters['_planet'] == $pl['planet_id'] ? 'selected' : '' ?>>
                  <?= esc($pl['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <select id="filterLocation" class="form-select form-select-sm bg-dark text-light border-secondary" disabled>
              <option value="">— Location</option>
            </select>
          </div>
        </div>

        <!-- Group 3: Personnel Filters -->
        <p class="text-muted small mb-1 fw-bold text-uppercase">Personnel</p>
        <div class="row g-2">
          <div class="col-4">
            <select id="filterStatus" class="form-select form-select-sm bg-dark text-light border-secondary">
              <option value="">All Statuses</option>
              <option value="Active" <?= ($savedFilters['status'] ?? '') === 'Active'   ? 'selected' : '' ?>>Active</option>
              <option value="Injured" <?= ($savedFilters['status'] ?? '') === 'Injured'  ? 'selected' : '' ?>>Injured</option>
              <option value="KIA" <?= ($savedFilters['status'] ?? '') === 'KIA'      ? 'selected' : '' ?>>KIA</option>
              <option value="Retired" <?= ($savedFilters['status'] ?? '') === 'Retired'  ? 'selected' : '' ?>>Retired</option>
              <option value="MIA" <?= ($savedFilters['status'] ?? '') === 'MIA'      ? 'selected' : '' ?>>MIA</option>
            </select>
          </div>
          <div class="col-4">
            <select id="filterMos" class="form-select form-select-sm bg-dark text-light border-secondary">
              <option value="">All MOS</option>
              <option value="MechWarrior" <?= ($savedFilters['mos'] ?? '') === 'MechWarrior' ? 'selected' : '' ?>>MechWarrior</option>
              <option value="Tanker" <?= ($savedFilters['mos'] ?? '') === 'Tanker'      ? 'selected' : '' ?>>Tanker</option>
              <option value="Infantry" <?= ($savedFilters['mos'] ?? '') === 'Infantry'    ? 'selected' : '' ?>>Infantry</option>
            </select>
          </div>
          <div class="col-4 d-flex align-items-center">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" id="filterUnassigned"
                <?= !empty($savedFilters['unassigned']) ? 'checked' : '' ?>>
              <label class="form-check-label text-light small" for="filterUnassigned">Unassigned</label>
            </div>
          </div>
        </div>

      </div>

      <!-- Table -->
      <div class="card-body p-0">
        <table class="table table-dark table-sm table-hover mb-0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Unit</th>
              <th>Status</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody id="rosterBody">
            <tr>
              <td colspan="4" class="text-center text-muted py-3">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="card-footer border-secondary d-flex justify-content-between align-items-center">
        <button id="rosterPrev" class="btn btn-sm btn-outline-secondary" disabled>← Prev</button>
        <span id="rosterPageInfo" class="text-muted small"></span>
        <button id="rosterNext" class="btn btn-sm btn-outline-secondary" disabled>Next →</button>
      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let currentPage    = 1;
  const allLocations = <?= json_encode($locations) ?>;
  const savedFilters = <?= json_encode($savedFilters) ?>;

  const statusColor = {
    'Active':  'success',
    'Injured': 'warning',
    'KIA':     'danger',
    'Retired': 'secondary',
    'MIA':     'info',
  };

  function updateUnitBreadcrumb() {
    const parts = [];
    ['filterRegiment','filterBattalion','filterCompany','filterLance'].forEach(id => {
      const sel = document.getElementById(id);
      if (sel.value) parts.push(sel.options[sel.selectedIndex].text);
    });
    document.getElementById('unitBreadcrumb').textContent =
      parts.length ? '▸ ' + parts.join(' → ') : '';
  }

  function getSelectedUnitId() {
    const selects = ['filterLance','filterCompany','filterBattalion','filterRegiment'];
    for (const id of selects) {
      const val = document.getElementById(id).value;
      if (val) return val;
    }
    return '';
  }

  function getFilters() {
    return {
      unit_id:     getSelectedUnitId(),
      status:      document.getElementById('filterStatus').value,
      mos:         document.getElementById('filterMos').value,
      location_id: document.getElementById('filterLocation').value,
      unassigned:  document.getElementById('filterUnassigned').checked ? 1 : '',
      page:        currentPage,
      _regiment:   document.getElementById('filterRegiment').value,
      _battalion:  document.getElementById('filterBattalion').value,
      _company:    document.getElementById('filterCompany').value,
      _lance:      document.getElementById('filterLance').value,
      _planet:     document.getElementById('filterPlanet').value,
    };
  }

  function loadRoster() {
    const filters = getFilters();
    const params  = new URLSearchParams(
      Object.fromEntries(Object.entries(filters).filter(([,v]) => v !== ''))
    );

    document.getElementById('rosterBody').innerHTML =
      '<tr><td colspan="4" class="text-center text-muted py-3">Loading...</td></tr>';

    fetch(`/personnel/roster?${params}`)
      .then(r => r.json())
      .then(data => {
        const tbody = document.getElementById('rosterBody');

        document.getElementById('rosterTotal').textContent    = data.total + ' personnel';
        document.getElementById('rosterPageInfo').textContent = `Page ${data.page} of ${data.last_page}`;
        document.getElementById('rosterPrev').disabled        = data.page <= 1;
        document.getElementById('rosterNext').disabled        = data.page >= data.last_page;

        if (!data.rows.length) {
          tbody.innerHTML =
            '<tr><td colspan="4" class="text-center text-muted py-3">No personnel found.</td></tr>';
          return;
        }

        tbody.innerHTML = data.rows.map(p => {
          const name    = `<a class="link-info" href="/personnel/${p.personnel_id}">${p.rank_abbr}. ${p.last_name}</a>`;
          const unit    = p.unit_name
            ? `<a class="link-secondary" href="/units/${p.unit_id}">${p.unit_name}</a>`
            : '<span class="text-muted fst-italic">Unassigned</span>';
          const badge   = `<span class="badge bg-${statusColor[p.status] ?? 'secondary'}">${p.status}</span>`;
          const locText = p.location_name
            ? `${p.location_name}` + (p.planet_name ? ' <span class="text-muted small">(' + p.planet_name + ')</span>' : '')
            : '—';
          const location = p.location_id
            ? `<a class="link-secondary" href="/location/${p.location_id}">${locText}</a>`
            : `<span class="text-muted">${locText}</span>`;

          return `<tr>
            <td>${name}</td>
            <td>${unit}</td>
            <td>${badge}</td>
            <td>${location}</td>
          </tr>`;
        }).join('');
      });
  }

  async function populateChildren(parentId, targetId, placeholder) {
    const select     = document.getElementById(targetId);
    select.innerHTML = `<option value="">— ${placeholder}</option>`;
    select.disabled  = true;
    if (!parentId) return;

    const data = await fetch(`/units/byParent/${parentId}`).then(r => r.json());
    if (!data.length) return;

    data.forEach(u => {
      const opt       = document.createElement('option');
      opt.value       = u.unit_id;
      opt.textContent = u.name;
      select.appendChild(opt);
    });
    select.disabled = false;
  }

  async function restoreUnitFilters() {
    const regiment  = savedFilters['_regiment'];
    const battalion = savedFilters['_battalion'];
    const company   = savedFilters['_company'];
    const lance     = savedFilters['_lance'];

    if (regiment) {
      document.getElementById('filterRegiment').value = regiment;
      await populateChildren(regiment, 'filterBattalion', 'Battalion');
    }
    if (battalion) {
      document.getElementById('filterBattalion').value = battalion;
      await populateChildren(battalion, 'filterCompany', 'Company');
    }
    if (company) {
      document.getElementById('filterCompany').value = company;
      await populateChildren(company, 'filterLance', 'Lance / Platoon');
    }
    if (lance) {
      document.getElementById('filterLance').value = lance;
    }

    updateUnitBreadcrumb();
  }

  function restoreLocationFilters() {
    const planet   = savedFilters['_planet'];
    const location = savedFilters['location_id'];

    if (planet) {
      document.getElementById('filterPlanet').value = planet;
      const locSelect = document.getElementById('filterLocation');
      locSelect.innerHTML = '<option value="">— Location</option>';
      const filtered = allLocations.filter(l => l.planet_id == planet);
      filtered.forEach(l => {
        const opt       = document.createElement('option');
        opt.value       = l.location_id;
        opt.textContent = l.name;
        locSelect.appendChild(opt);
      });
      locSelect.disabled = false;
      if (location) locSelect.value = location;
    }
  }

  // Unit cascade events
  document.getElementById('filterRegiment').addEventListener('change', async function() {
    await populateChildren(this.value, 'filterBattalion', 'Battalion');
    document.getElementById('filterCompany').innerHTML = '<option value="">— Company</option>';
    document.getElementById('filterCompany').disabled  = true;
    document.getElementById('filterLance').innerHTML   = '<option value="">— Lance / Platoon</option>';
    document.getElementById('filterLance').disabled    = true;
    updateUnitBreadcrumb();
    currentPage = 1; loadRoster();
  });

  document.getElementById('filterBattalion').addEventListener('change', async function() {
    await populateChildren(this.value, 'filterCompany', 'Company');
    document.getElementById('filterLance').innerHTML = '<option value="">— Lance / Platoon</option>';
    document.getElementById('filterLance').disabled  = true;
    updateUnitBreadcrumb();
    currentPage = 1; loadRoster();
  });

  document.getElementById('filterCompany').addEventListener('change', async function() {
    await populateChildren(this.value, 'filterLance', 'Lance / Platoon');
    updateUnitBreadcrumb();
    currentPage = 1; loadRoster();
  });

  document.getElementById('filterLance').addEventListener('change', function() {
    updateUnitBreadcrumb();
    currentPage = 1; loadRoster();
  });

  // Planet → Location cascade
  document.getElementById('filterPlanet').addEventListener('change', function() {
    const planetId  = this.value;
    const locSelect = document.getElementById('filterLocation');
    locSelect.innerHTML = '<option value="">— Location</option>';
    locSelect.disabled  = true;

    if (planetId) {
      const filtered = allLocations.filter(l => l.planet_id == planetId);
      filtered.forEach(l => {
        const opt       = document.createElement('option');
        opt.value       = l.location_id;
        opt.textContent = l.name;
        locSelect.appendChild(opt);
      });
      locSelect.disabled = false;
    }
    currentPage = 1; loadRoster();
  });

  // Simple filter events
  ['filterStatus','filterMos','filterLocation','filterUnassigned'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
      currentPage = 1; loadRoster();
    });
  });

  // Pagination
  document.getElementById('rosterPrev').addEventListener('click', () => {
    if (currentPage > 1) { currentPage--; loadRoster(); }
  });
  document.getElementById('rosterNext').addEventListener('click', () => {
    currentPage++; loadRoster();
  });

  async function init() {
    restoreLocationFilters();
    await restoreUnitFilters();
    loadRoster();
  }

  document.getElementById('clearFilters').addEventListener('click', () => {
    // Reset unit cascade
    document.getElementById('filterRegiment').value  = '';
    document.getElementById('filterBattalion').innerHTML = '<option value="">— Battalion</option>';
    document.getElementById('filterBattalion').disabled  = true;
    document.getElementById('filterCompany').innerHTML   = '<option value="">— Company</option>';
    document.getElementById('filterCompany').disabled    = true;
    document.getElementById('filterLance').innerHTML     = '<option value="">— Lance / Platoon</option>';
    document.getElementById('filterLance').disabled      = true;

    // Reset location cascade
    document.getElementById('filterPlanet').value        = '';
    document.getElementById('filterLocation').innerHTML  = '<option value="">— Location</option>';
    document.getElementById('filterLocation').disabled   = true;

    // Reset personnel filters
    document.getElementById('filterStatus').value        = '';
    document.getElementById('filterMos').value           = '';
    document.getElementById('filterUnassigned').checked  = false;

    updateUnitBreadcrumb();
    currentPage = 1;
    loadRoster();
  });

  init();
});
</script>