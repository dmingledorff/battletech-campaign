<div class="card shadow mb-3">
  <div class="card-header">Personnel Details</div>
  <div class="card-body">
    <div class="row">
      <!-- Left column -->
      <div class="col-md-6">
        <p><strong>Name:</strong> <?= esc($person['last_name'].', '.$person['first_name']) ?></p>
        <p><strong>Rank:</strong> <?= esc($person['rank_full']) ?></p>
        <p><strong>Gender:</strong> <?= esc($person['gender']) ?></p>
        <p><strong>Callsign:</strong> <?= esc($person['callsign']) ?></p>
        <p><strong>Date of Birth:</strong> <?= esc($person['date_of_birth']) ?></p>
        <p><strong>Age:</strong> <?= esc($age) ?> years</p>
      </div>

      <!-- Right column -->
      <div class="col-md-6">
        <p><strong>MOS:</strong> <?= esc($person['mos']) ?></p>
        <p><strong>Experience:</strong> <?= esc($person['experience']) ?></p>
        <p><strong>Missions:</strong> <?= esc($person['missions']) ?></p>
        <?php
          $morale = $person['morale'];
          if ($morale >= 70) {
              $color = 'green';
          } elseif ($morale >= 40) {
              $color = 'yellow';
          } else {
              $color = 'red';
          }
        ?>
        <p><strong>Morale:</strong> <span style="color: <?= $color ?>"><?= number_format($morale, 2) ?>%</span></p>
        <p><strong>Status:</strong> <?= esc($person['status']) ?></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Unit Assignments</div>
      <div class="card-body p-0">
        <?php if (!empty($assignments)): ?>
          <table class="table table-dark table-sm mb-0">
            <thead><tr><th>Unit</th><th>Type</th><th>Nickname</th></tr></thead>
            <tbody>
              <?php foreach($assignments as $a): ?>
                <tr>
                  <td><a class="link-info" href="/units/<?= esc($a['unit_id']) ?>"><?= esc($a['name']) ?></a></td>
                  <td><?= esc($a['unit_type']) ?></td>
                  <td><?= esc($a['nickname']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted">No unit assignments found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Equipment Assignments</div>
      <div class="card-body p-0">
        <?php if (!empty($equipment)): ?>
          <table class="table table-dark table-sm mb-0">
            <thead>
              <tr>
                <th>Chassis</th><th>Variant</th><th>Role</th><th>Weight</th><th>Status</th><th>Damage</th>
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
                  <td><?= esc($e['chassis_variant']) ?></td>
                  <td><?= esc($e['role']) ?></td>
                  <td><?= esc($e['weight_class']) ?></td>
                  <td><?= esc($e['equipment_status']) ?></td>
                  <td><?= esc($e['damage_percentage']) ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted">No equipment assignments found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
