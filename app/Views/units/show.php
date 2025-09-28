<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-dark text-light p-2">
    <?php foreach($breadcrumb as $b): ?>
      <li class="breadcrumb-item">
        <a class="link-info" href="/units/<?= esc($b['unit_id']) ?>">
          <?= esc($b['name']) ?>
        </a>
        <?php if (!empty($b['nickname'])): ?>
          <span class="badge bg-info-subtle text-info-emphasis"><?= esc($b['nickname']) ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>

<div class="card shadow mb-3">
  <div class="card-header">Unit Information</div>
  <div class="card-body">
    <p><strong>Name:</strong> <?= esc($unit['name']) ?></p>
    <p><strong>Nickname:</strong> <?= esc($unit['nickname'] ?? '-') ?></p>
    <p>
      <strong>Type:</strong> <?= esc($unit['unit_type']) ?>
      <?php if (!empty($unit['role'])): ?>
          <span class="text-info fst-italic">– <?= esc($unit['role']) ?></span>
      <?php endif; ?>
    </p>
    <p><strong>Commander:</strong>
      <?php if (!empty($unit['commander_id'])): ?>
        <a class="link-info" href="/personnel/<?= esc($unit['commander_id']) ?>">
          <?= esc($unit['rank_abbr']) ?>. <?= esc($unit['first_name'].' '.$unit['last_name']) ?>
        </a>
      <?php else: ?>
        <span class="text-muted">Unassigned</span>
      <?php endif; ?>
    </p>
    <p><strong>Current Supply:</strong> <?= esc(number_format($unit['current_supply'],2)) ?></p>
    <p><strong>Required Supply:</strong> <?= esc(number_format($unit['required_supply'],2)) ?></p>
    <?php
      $morale = $unit['avg_morale'];
      $color = $morale >= 80 ? 'green' : ($morale >= 50 ? 'orange' : 'red');
    ?>
    <p><strong>Average Morale:</strong> <span style="color: <?= $color ?>"><?= number_format($morale, 2) ?>%</span></p>
  </div>
</div>

<div class="card shadow mb-3">
  <div class="card-header">Sub-Units</div>
  <div class="card-body p-0">
    <?php if (!empty($children[$unit['unit_id']])): ?>
      <table class="table table-dark table-sm mb-0">
        <thead>
          <tr><th>Name</th><th>Type</th><th>Nickname</th></tr>
        </thead>
        <tbody>
          <?php foreach($children[$unit['unit_id']] as $child): ?>
            <tr>
              <td><a class="link-info" href="/units/<?= esc($child['unit_id']) ?>"><?= esc($child['name']) ?></a></td>
              <td><?= esc($child['unit_type']) ?></td>
              <td><?= esc($child['nickname'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted mb-0">No sub-units assigned.</p>
    <?php endif; ?>
  </div>
</div>


<div class="row">
  <div class="col-md-6">

    <div class="card shadow mb-3">
      <div class="card-header">Personnel (incl. sub-units)</div>
      <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0">
          <thead><tr><th>Name</th><th>Rank</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach($personnel as $p): ?>
              <tr>
                <td>
                  <a class="link-info" href="/personnel/<?= esc($p['personnel_id']) ?>">
                    <?= esc($p['first_name'].' '.$p['last_name']) ?>
                  </a>
                </td>
                <td><?= esc($p['rank_full']) ?></td>
                <td><?= esc($p['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Equipment (incl. sub-units)</div>
      <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0">
          <thead>
            <tr>
              <th>Chassis</th>
              <th>Type</th>
              <th>Weight Class</th>
              <th>Damage</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($equipment as $e): ?>
              <tr>
                <td>
                  <a class="link-info" href="/equipment/<?= esc($e['equipment_id']) ?>">
                    <?= esc($e['chassis_name']) ?>
                  </a>
                </td>
                <td><?= esc($e['chassis_type'] ?? 'Unknown') ?></td>
                <td><?= esc($e['weight_class'] ?? 'Unknown') ?></td>
                <td><?= esc($e['damage_percentage']).'%' ?></td>
                <td><?= esc($e['equipment_status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
