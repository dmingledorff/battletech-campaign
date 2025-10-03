<h2><?= esc($planet['name']) ?> (<?= esc($planet['system_name']) ?>)</h2>

<canvas id="planetMap" width="800" height="600"></canvas>

<div>
  <label><input type="checkbox" class="filter-unit" value="Regiment" checked> Regiments</label>
  <label><input type="checkbox" class="filter-unit" value="Battalion" checked> Battalions</label>
  <label><input type="checkbox" class="filter-unit" value="Company" checked> Companies</label>
  <label><input type="checkbox" class="filter-unit" value="Lance" checked> Lances</label>
  <label><input type="checkbox" class="filter-unit" value="Platoon" checked> Platoons</label>
  <label><input type="checkbox" class="filter-unit" value="Squad" checked> Squads</label>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('planetMap').getContext('2d');
const locations = <?= json_encode($locations) ?>;
const backgroundImage = new Image();
backgroundImage.src = "<?= base_url($planet['map_background'] ?? '/images/maps/default.png') ?>";

let chart;
function buildChart() {
    chart = new Chart(ctx, {
        type: 'scatter',
        data: {
            datasets: [
                {
                    label: 'Locations',
                    data: locations.map(loc => ({
                        x: loc.coord_x, y: loc.coord_y,
                        name: loc.name, type: loc.type
                    })),
                    pointBackgroundColor: 'blue',
                    pointRadius: 6
                }
            ]
        },
        options: {
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const d = context.raw;
                            return d.name + ' (' + d.type + ')';
                        }
                    }
                }
            },
            scales: {
                x: { min: 0, max: 100, display: false },
                y: { min: 0, max: 100, display: false }
            }
        },
        plugins: [{
            id: 'background',
            beforeDraw: (chart) => {
                const ctx = chart.ctx;
                const {left, top, width, height} = chart.chartArea;
                ctx.drawImage(backgroundImage, left, top, width, height);
            }
        }]
    });
}

backgroundImage.onload = () => buildChart();
</script>
