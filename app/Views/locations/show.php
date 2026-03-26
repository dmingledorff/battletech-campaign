<!-- Location Header -->
<div class="row mb-3">
  <div class="col">
    <div class="card shadow">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h4 class="mb-1"><?= esc($location['name']) ?>
              <span class="text-muted fs-6">(<?= esc($location['planet_name']) ?>)</span>
            </h4>
            <p class="mb-1"><strong>Type:</strong> <?= esc($location['type']) ?></p>
            <p class="mb-1"><strong>Terrain:</strong> <?= esc($location['terrain']) ?></p>
            <p class="mb-0"><strong>Controlled By:</strong>
              <?php if (!empty($location['controlled_by_name'])): ?>
                <span style="color: <?= esc($location['controlled_by_color']) ?>">
                  <?= esc($location['controlled_by_name']) ?>
                </span>
              <?php else: ?>
                <span class="text-muted">Uncontrolled</span>
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!$controlled): ?>
  <!-- Enemy / Neutral location — restricted view -->
  <div class="alert alert-secondary">
    <i class="bi bi-lock-fill me-2"></i>
    Detailed intelligence on this location is not available.
    <?php if (!empty($location['controlled_by_name'])): ?>
      This location is controlled by <strong><?= esc($location['controlled_by_name']) ?></strong>.
    <?php endif; ?>
  </div>

<?php else: ?>

  <!-- Buildings + Units row -->
  <div class="row">

    <!-- Buildings -->
    <div class="col-md-4">
      <div class="card shadow mb-3">
        <div class="card-header">Buildings</div>
        <div class="card-body p-0">
          <?php if (!empty($buildings)): ?>
            <table class="table table-dark table-sm mb-0">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Type</th>
                  <th>Capacity</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($buildings as $b): ?>
                  <?php
                    $statusColor = match($b['status']) {
                        'Operational' => 'success',
                        'Damaged'     => 'warning',
                        'Destroyed'   => 'danger',
                        default       => 'secondary',
                    };
                  ?>
                  <tr>
                    <td><?= esc($b['name']) ?></td>
                    <td><?= esc($b['type']) ?></td>
                    <td><?= $b['capacity'] ?? '—' ?></td>
                    <td>
                      <span class="badge bg-<?= $statusColor ?>">
                        <?= esc($b['status']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="text-muted p-3 mb-0">No buildings recorded.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Units -->
    <div class="col-md-8">
      <div class="card shadow mb-3">
        <div class="card-header">Garrison</div>
        <div class="card-body p-0">
          <?php if (!empty($units)): ?>
            <table class="table table-dark table-sm mb-0">
  <thead>
                <tr>
                <th>Unit</th>
                <th title="Average Morale">Mor%</th>
                <th title="Personnel Strength">Per%</th>
                <th title="Equipment Strength">Eqp%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($units as $u): ?>
                <?php
                    $moraleColor = match(true) {
                        $u['avg_morale'] >= 70 => 'success',
                        $u['avg_morale'] >= 40 => 'warning',
                        default                => 'danger',
                    };
                    $persColor = match(true) {
                        $u['pct_personnel'] >= 75 => 'success',
                        $u['pct_personnel'] >= 50 => 'warning',
                        default                   => 'danger',
                    };
                    $eqpColor = match(true) {
                        $u['pct_equipment'] >= 75 => 'success',
                        $u['pct_equipment'] >= 50 => 'warning',
                        default                   => 'danger',
                    };
                ?>
                <tr>
                    <td>
                    <a class="link-info" href="/units/<?= esc($u['unit_id']) ?>">
                        <?= esc($u['unit_chain']) ?>
                    </a>
                    </td>
                    <td>
                    <?php if ($u['avg_morale'] !== null): ?>
                        <span class="text-<?= $moraleColor ?>">
                        <?= number_format($u['avg_morale'], 1) ?>%
                        </span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                    </td>
                    <td>
                    <span class="text-<?= $persColor ?>">
                        <?= number_format($u['pct_personnel'], 1) ?>%
                    </span>
                    </td>
                    <td>
                    <span class="text-<?= $eqpColor ?>">
                        <?= number_format($u['pct_equipment'], 1) ?>%
                    </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
          <?php else: ?>
            <p class="text-muted p-3 mb-0">No units stationed here.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- Personnel + Equipment row -->
  <div class="row">

    <!-- Personnel -->
    <div class="col-md-6">
      <div class="card shadow mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Personnel</span>
          <span class="badge bg-secondary"><?= count($personnel) ?></span>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($personnel)): ?>
            <table class="table table-dark table-sm mb-0">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Unit</th>
                  <th>Status</th>
                  <th>Morale</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($personnel as $p): ?>
                  <?php
                    $moraleColor = match(true) {
                        (float)$p['morale'] >= 70 => 'green',
                        (float)$p['morale'] >= 40 => 'yellow',
                        default                   => 'red',
                    };
                  ?>
                  <tr>
                    <td>
                      <a class="link-info" href="/personnel/<?= esc($p['personnel_id']) ?>">
                        <?= esc($p['rank_abbr'] . '. ' . $p['last_name'] . ', ' . $p['first_name']) ?>
                      </a>
                    </td>
                    <td>
                      <?php if ($p['unit_id']): ?>
                        <a class="link-secondary" href="/units/<?= esc($p['unit_id']) ?>">
                          <?= esc($p['unit_name']) ?>
                        </a>
                      <?php else: ?>
                        <span class="text-muted">Unassigned</span>
                      <?php endif; ?>
                    </td>
                    <td><?= esc($p['status']) ?></td>
                    <td>
                      <span style="color: <?= $moraleColor ?>">
                        <?= number_format((float)$p['morale'], 1) ?>%
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="text-muted p-3 mb-0">No personnel at this location.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Equipment -->
    <div class="col-md-6">
      <div class="card shadow mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Equipment</span>
          <span class="badge bg-secondary"><?= count($equipment) ?></span>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($equipment)): ?>
            <table class="table table-dark table-sm mb-0">
              <thead>
                <tr>
                  <th>Chassis</th>
                  <th>Variant</th>
                  <th>Unit</th>
                  <th>Status</th>
                  <th>Dmg</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($equipment as $e): ?>
                  <?php
                    $dmg = (float)$e['damage_percentage'];
                    $dmgColor = match(true) {
                        $dmg === 0.0  => 'success',
                        $dmg <= 30.0  => 'warning',
                        default       => 'danger',
                    };
                  ?>
                  <tr>
                    <td>
                      <a class="link-info" href="/equipment/<?= esc($e['equipment_id']) ?>">
                        <?= esc($e['chassis_name']) ?>
                      </a>
                    </td>
                    <td><?= esc($e['chassis_variant']) ?></td>
                    <td>
                      <?php if ($e['assigned_unit_id']): ?>
                        <a class="link-secondary" href="/units/<?= esc($e['assigned_unit_id']) ?>">
                          <?= esc($e['unit_name']) ?>
                        </a>
                      <?php else: ?>
                        <span class="text-muted">Unassigned</span>
                      <?php endif; ?>
                    </td>
                    <td><?= esc($e['equipment_status']) ?></td>
                    <td>
                      <span class="text-<?= $dmgColor ?>">
                        <?= number_format($dmg, 1) ?>%
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="text-muted p-3 mb-0">No equipment at this location.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

<?php endif; ?>