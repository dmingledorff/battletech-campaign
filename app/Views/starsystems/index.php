<div class="row">
  <!-- Sidebar -->
  <div class="col-md-3 border-end" style="max-height: calc(100vh - 60px); overflow-y: auto;">
    <h5 class="mt-2 mb-3">Star Systems</h5>
    <?php foreach ($systems as $sys): ?>
      <div class="mb-3">
        <div class="fw-bold text-light mb-1"><?= esc($sys['name']) ?></div>
        <?php foreach ($sys['planets'] as $planet): ?>
          <div class="ms-2 mb-2">
            <a href="<?= site_url('starsystems/'.$sys['system_id'].'/'.$planet['planet_id']) ?>"
               class="link-info fw-semibold">
              <?= esc($planet['name']) ?>
            </a>
            <?php if (!empty($planet['locations'])): ?>
              <ul class="list-unstyled ms-3 mb-0">
                <?php foreach ($planet['locations'] as $loc): ?>
                  <li class="small">
                    <a href="/location/<?= esc($loc['location_id']) ?>" class="link-secondary">
                      <?= esc($loc['name']) ?>
                    </a>
                    <span class="text-muted">(<?= esc($loc['type']) ?>)</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Map -->
  <div class="col-md-9">
    <?php if ($selectedPlanet): ?>
      <h5 class="mt-2 mb-2">
        <?= esc($selectedPlanet['name']) ?> Map
        <span class="text-muted fs-6">(<?= esc($selectedSystem['name'] ?? '') ?> System)</span>
      </h5>
      <canvas id="planetMap" width="800" height="800"></canvas>
    <?php else: ?>
      <p class="text-muted mt-3">Select a planet to view its map.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Shared Modal (locations + missions) -->
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="mapModalTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="mapModalBody"></div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a id="mapModalLink" href="#" class="btn btn-outline-info btn-sm">View</a>
      </div>
    </div>
  </div>
</div>

<?php if ($selectedPlanet): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const canvas         = document.getElementById('planetMap');
const ctx            = canvas.getContext('2d');
const playerFactionId = <?= (int)($playerFactionId ?? 0) ?>;

// Background image
const backgroundImage = new Image();
backgroundImage.src   = "<?= base_url(esc($selectedPlanet['map_background'])) ?>";

// Location type icons
const locationIcons = {
  'City':            new Image(),
  'Spaceport':       new Image(),
  'Base':            new Image(),
  'Industrial Zone': new Image(),
};
locationIcons['City'].src            = "/images/icons/modern-city.svg";
locationIcons['Spaceport'].src       = "/images/icons/space-shuttle.svg";
locationIcons['Base'].src            = "/images/icons/military-fort.svg";
locationIcons['Industrial Zone'].src = "/images/icons/factory.svg";

// Faction emblems
const factionEmblems = {};
<?php foreach ($selectedPlanet['locations'] as $loc): ?>
  <?php if (!empty($loc['faction_emblem'])): ?>
    <?php $emblem = esc($loc['faction_emblem']) ?>
    if (!factionEmblems['<?= $emblem ?>']) {
      factionEmblems['<?= $emblem ?>'] = new Image(16, 16);
      factionEmblems['<?= $emblem ?>'].src = '<?= $emblem ?>';
    }
  <?php endif; ?>
<?php endforeach; ?>

// Location data
const locations = <?= json_encode(array_map(function($loc) {
  return [
    'location_id'         => (int)$loc['location_id'],
    'x'                   => (float)($loc['coord_x'] ?? 0),
    'y'                   => (float)($loc['coord_y'] ?? 0),
    'label'               => $loc['name'],
    'iconType'            => $loc['type'],
    'terrain'             => $loc['terrain'] ?? '',
    'faction_name'        => $loc['faction_name'] ?? null,
    'faction_color'       => $loc['faction_color'] ?? null,
    'faction_emblem'      => $loc['faction_emblem'] ?? null,
    'controlling_faction' => (int)($loc['controlling_faction_id'] ?? 0),
  ];
}, $selectedPlanet['locations'] ?? [])) ?>;

// Garrisoned unit data grouped by type
const unitsByType = <?= json_encode(array_map(function($u) {
  return [
    'unit_id'    => (int)$u['unit_id'],
    'name'       => $u['name'],
    'unit_type'  => $u['unit_type'],
    'unit_chain' => $u['unit_chain'] ?? $u['name'],
    'coord_x'    => $u['coord_x'],
    'coord_y'    => $u['coord_y'],
  ];
}, $units ?? [])) ?>.reduce((acc, u) => {
  if (!acc[u.unit_type]) acc[u.unit_type] = [];
  acc[u.unit_type].push({
    unit_id:    u.unit_id,
    x:          parseFloat(u.coord_x ?? 0),
    y:          parseFloat(u.coord_y ?? 0),
    label:      u.name,
    unit_chain: u.unit_chain,
    unit_type:  u.unit_type,
  });
  return acc;
}, {});

// In-transit mission data
const inTransitMissions = <?= json_encode(array_map(function($m) {
  return [
    'mission_id'   => (int)$m['mission_id'],
    'mission_name' => $m['mission_name'],
    'mission_type' => $m['mission_type'],
    'x'            => (float)($m['current_coord_x'] ?? 0),
    'y'            => (float)($m['current_coord_y'] ?? 0),
    'dest_x'       => (float)($m['dest_x'] ?? 0),
    'dest_y'       => (float)($m['dest_y'] ?? 0),
    'origin_name'  => $m['origin_name'],
    'dest_name'    => $m['destination_name'],
    'eta_date'     => $m['eta_date'],
    'days_elapsed' => (int)$m['days_elapsed'],
    'transit_days' => (int)$m['transit_days'],
  ];
}, $inTransitMissions ?? [])) ?>;

// ================================
// Plugins
// ================================

const backgroundPlugin = {
  id: 'backgroundPlugin',
  beforeDraw(chart) {
    if (!backgroundImage.complete || !backgroundImage.naturalWidth) return;
    const { ctx, chartArea: { left, top, width, height } } = chart;
    ctx.save();
    ctx.drawImage(backgroundImage, left, top, width, height);
    ctx.restore();
  }
};

const locationLabelPlugin = {
  id: 'locationLabelPlugin',
  afterDatasetsDraw(chart) {
    const ctx = chart.ctx;
    chart.data.datasets.forEach((dataset, i) => {
      if (dataset.label !== 'Locations') return;
      const meta = chart.getDatasetMeta(i);
      meta.data.forEach((element, index) => {
        const loc = locations[index];
        if (!loc) return;
        const px = element.x;
        const py = element.y;

        // Location name label
        ctx.save();
        ctx.fillStyle    = '#ffffff';
        ctx.font         = 'bold 12px sans-serif';
        ctx.textAlign    = 'left';
        ctx.textBaseline = 'middle';
        ctx.shadowColor  = 'rgba(0,0,0,0.9)';
        ctx.shadowBlur   = 3;
        ctx.fillText(loc.label, px + 14, py + 8);
        ctx.restore();

        // Faction emblem or color dot
        if (loc.faction_emblem && factionEmblems[loc.faction_emblem]?.complete
            && factionEmblems[loc.faction_emblem].naturalWidth > 0) {
          ctx.drawImage(factionEmblems[loc.faction_emblem], px + 14, py - 16, 16, 16);
        } else if (loc.faction_color) {
          ctx.beginPath();
          ctx.arc(px + 20, py - 8, 5, 0, Math.PI * 2);
          ctx.fillStyle = loc.faction_color;
          ctx.fill();
        }
      });
    });
  }
};

const transitLinePlugin = {
  id: 'transitLinePlugin',
  afterDatasetsDraw(chart) {
    if (!inTransitMissions.length) return;
    const ctx   = chart.ctx;
    const xAxis = chart.scales.x;
    const yAxis = chart.scales.y;

    inTransitMissions.forEach(m => {
      const fromX = xAxis.getPixelForValue(m.x);
      const fromY = yAxis.getPixelForValue(m.y);
      const toX   = xAxis.getPixelForValue(m.dest_x);
      const toY   = yAxis.getPixelForValue(m.dest_y);

      ctx.save();
      ctx.beginPath();
      ctx.setLineDash([4, 4]);
      ctx.strokeStyle = 'rgba(255, 235, 59, 0.4)';
      ctx.lineWidth   = 1.5;
      ctx.moveTo(fromX, fromY);
      ctx.lineTo(toX, toY);
      ctx.stroke();
      ctx.restore();
    });
  }
};

// ================================
// Datasets
// ================================
const datasets = [];

// Locations
datasets.push({
  label:       'Locations',
  data:        locations.map(loc => ({ x: loc.x, y: loc.y, iconType: loc.iconType })),
  pointStyle:  (ctx) => locationIcons[ctx.raw.iconType] || 'circle',
  pointRadius: 20,
});

// Garrisoned units by type
for (const [type, items] of Object.entries(unitsByType)) {
  datasets.push({
    label:                type,
    data:                 items.map(u => ({ x: u.x, y: u.y, label: u.label, unit_chain: u.unit_chain, unit_id: u.unit_id })),
    pointBackgroundColor: '#4fc3f7',
    pointBorderColor:     '#01579b',
    pointBorderWidth:     2,
    pointRadius:          6,
    pointHoverRadius:     8,
  });
}

// In-transit missions
if (inTransitMissions.length) {
  datasets.push({
    label:                'In Transit',
    data:                 inTransitMissions.map(m => ({ x: m.x, y: m.y })),
    pointBackgroundColor: '#fff176',
    pointBorderColor:     '#f9a825',
    pointBorderWidth:     2,
    pointRadius:          7,
    pointHoverRadius:     9,
    pointStyle:           'triangle',
  });
}

// ================================
// Chart
// ================================
const chart = new Chart(ctx, {
  type: 'scatter',
  data: { datasets },
  options: {
    animation: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (context) => {
            if (context.dataset.label === 'Locations') {
              return locations[context.dataIndex]?.label ?? '';
            }
            if (context.dataset.label === 'In Transit') {
              const m = inTransitMissions[context.dataIndex];
              return m ? `${m.mission_name} → ${m.dest_name}` : '';
            }
            return context.raw.unit_chain ?? context.raw.label ?? '';
          }
        }
      }
    },
    scales: {
      x: { type: 'linear', min: 0, max: 100 },
      y: { type: 'linear', min: 0, max: 100 }
    }
  },
  plugins: [backgroundPlugin, locationLabelPlugin, transitLinePlugin]
});

// ================================
// Image load triggers
// ================================
backgroundImage.onload = () => chart.update('none');

let iconsLoaded = 0;
const totalIcons = Object.keys(locationIcons).length;
Object.values(locationIcons).forEach(img => {
  if (img.complete && img.naturalWidth > 0) {
    if (++iconsLoaded === totalIcons) chart.update('none');
  } else {
    img.onload = () => { if (++iconsLoaded === totalIcons) chart.update('none'); };
  }
});

Object.values(factionEmblems).forEach(img => {
  img.onload = () => chart.update('none');
});

// ================================
// Click handler
// ================================
canvas.addEventListener('click', (e) => {
  const points = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, false);
  if (!points.length) return;

  const { datasetIndex, index } = points[0];
  const dataset = chart.data.datasets[datasetIndex];

  if (dataset.label === 'Locations') {
    showLocationModal(locations[index]);
  } else if (dataset.label === 'In Transit') {
    showMissionModal(inTransitMissions[index]);
  } else {
    // Garrisoned unit — navigate to unit page
    const point = dataset.data[index];
    if (point?.unit_id) window.location.href = '/units/' + point.unit_id;
  }
});

// Cursor pointer on hover
canvas.addEventListener('mousemove', (e) => {
  const points = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, false);
  canvas.style.cursor = points.length ? 'pointer' : 'default';
});

// ================================
// Modal functions
// ================================
function showLocationModal(loc) {
  const isPlayer   = loc.controlling_faction === playerFactionId;
  const controlled = loc.faction_name
    ? `<span style="color:${loc.faction_color ?? '#fff'}">${loc.faction_name}</span>`
    : '<span class="text-muted">Uncontrolled</span>';

  document.getElementById('mapModalTitle').textContent = loc.label;
  document.getElementById('mapModalBody').innerHTML = `
    <p class="mb-1"><strong>Type:</strong> ${loc.iconType}</p>
    <p class="mb-1"><strong>Terrain:</strong> ${loc.terrain || '—'}</p>
    <p class="mb-0"><strong>Controlled By:</strong> ${controlled}</p>
    ${!isPlayer
      ? '<p class="text-muted small mt-2 mb-0"><i class="bi bi-lock-fill me-1"></i>Detailed intelligence unavailable.</p>'
      : ''}
  `;
  document.getElementById('mapModalLink').href        = '/location/' + loc.location_id;
  document.getElementById('mapModalLink').textContent = 'View Location';
  new bootstrap.Modal(document.getElementById('mapModal')).show();
}

function showMissionModal(m) {
  const progress = m.transit_days > 0
    ? Math.min(100, Math.round((m.days_elapsed / m.transit_days) * 100))
    : 0;

  document.getElementById('mapModalTitle').textContent = m.mission_name;
  document.getElementById('mapModalBody').innerHTML = `
    <p class="mb-1"><strong>Type:</strong>
      <span class="badge bg-info">${m.mission_type}</span>
    </p>
    <p class="mb-1"><strong>Route:</strong> ${m.origin_name} → ${m.dest_name}</p>
    <p class="mb-1"><strong>ETA:</strong> ${m.eta_date}</p>
    <p class="mb-1"><strong>Progress:</strong> ${m.days_elapsed}/${m.transit_days} days</p>
    <div class="progress mt-2" style="height:6px;">
      <div class="progress-bar bg-warning" style="width:${progress}%"></div>
    </div>
  `;
  document.getElementById('mapModalLink').href        = '/missions/' + m.mission_id;
  document.getElementById('mapModalLink').textContent = 'View Mission';
  new bootstrap.Modal(document.getElementById('mapModal')).show();
}
</script>
<?php endif; ?>