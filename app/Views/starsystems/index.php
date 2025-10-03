<div class="row">
  <!-- Sidebar -->
  <div class="col-md-3 border-end">
    <h4>Star Systems</h4>
    <ul class="list-group">
      <?php foreach ($systems as $sys): ?>
        <li class="list-group-item bg-dark text-light">
          <strong><?= esc($sys['name']) ?></strong>
          <ul class="list-unstyled ms-3">
            <?php foreach ($sys['planets'] as $planet): ?>
              <li>
                <a href="<?= site_url('starsystems/index/'.$sys['system_id'].'/'.$planet['planet_id']) ?>"
                   class="link-info">
                  <?= esc($planet['name']) ?>
                </a>
                <ul class="list-unstyled ms-3">
                  <?php if (!empty($planet['locations'])): ?>
                    <?php foreach ($planet['locations'] as $loc): ?>
                      <li><small><?= esc($loc['name']) ?> (<?= esc($loc['type']) ?>)</small></li>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </ul>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Map -->
  <div class="col-md-9">
    <?php if ($selectedPlanet): ?>
      <h3><?= esc($selectedPlanet['name']) ?> Map</h3>
      <canvas id="planetMap" width="800" height="600"></canvas>

      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <script>
        const ctx = document.getElementById('planetMap').getContext('2d');

        // Pull map background path from planet record
        const backgroundImage = new Image();
        backgroundImage.src = "<?= base_url(esc($selectedPlanet['map_background'])) ?>";

        // Chart.js plugin to draw background image
        const backgroundPlugin = {
          id: 'backgroundImagePlugin',
          beforeDraw: (chart) => {
            if (!backgroundImage.complete) return; // wait for image to load
            const ctx = chart.ctx;
            const { left, top, width, height } = chart.chartArea;
            ctx.save();
            ctx.globalAlpha = 0.7; // make it slightly transparent if desired
            ctx.drawImage(backgroundImage, left, top, width, height);
            ctx.restore();
          }
        };

        // Colors per type
        const colors = {
          Location: 'yellow',
          Company: 'blue',
          Lance: 'green',
          Battalion: 'orange',
          Regiment: 'red',
          Platoon: 'purple',
          Squad: 'cyan'
        };

        const datasets = [];

        // Add locations
        datasets.push({
          label: 'Locations',
          data: <?= json_encode(array_map(function($loc) {
            return [
              'x' => (float) ($loc['coord_x'] ?? 0),
              'y' => (float) ($loc['coord_y'] ?? 0),
              'label' => $loc['name']
            ];
          }, $selectedPlanet['locations'] ?? [])) ?>,
            pointBackgroundColor: 'rgba(255, 215, 0, 0.5)', // gold with transparency
            pointBorderColor: 'black',
            pointRadius: 18
        });

        // Group units by type
        const unitsByType = <?= json_encode($units ?? []) ?>.reduce((acc, u) => {
          if (!acc[u.unit_type]) acc[u.unit_type] = [];
          acc[u.unit_type].push({
            x: parseFloat(u.coord_x ?? 0),
            y: parseFloat(u.coord_y ?? 0),
            label: u.name
          });
          return acc;
        }, {});

        for (const [type, items] of Object.entries(unitsByType)) {
          datasets.push({
            label: type,
            data: items,
            pointBackgroundColor: colors[type] || 'white',
            pointRadius: 6
          });
        }

        new Chart(ctx, {
          type: 'scatter',
          data: { datasets },
          options: {
            plugins: {
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return context.raw.label;
                  }
                }
              }
            },
            scales: {
              x: { type: 'linear', min: 0, max: 100 },
              y: { type: 'linear', min: 0, max: 100 }
            }
          },
          plugins: [backgroundPlugin]
        });
      </script>
    <?php else: ?>
      <p class="text-muted">Select a planet to view its map.</p>
    <?php endif; ?>
  </div>
</div>
