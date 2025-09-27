<div class="card shadow mb-3">
  <div class="card-header">Personnel Details</div>
  <div class="card-body">
      <p><strong>Name:</strong> <?= esc($person['first_name'].' '.$person['last_name']) ?></p>
      <p><strong>Gender:</strong> <?= esc($person['gender']) ?></p>
      <p><strong>Callsign:</strong> <?= esc($person['callsign']) ?></p>
      <p><strong>Status:</strong> <?= esc($person['status']) ?></p>
      <p><strong>Rank:</strong> <?= esc($person['rank_full']) ?></p>
      <p><strong>MOS:</strong> <?= esc($person['mos']) ?></p>
      <p><strong>Experience:</strong> <?= esc($person['experience']) ?></p>
      <p><strong>Missions:</strong> <?= esc($person['missions']) ?></p>
  </div>
</div>

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

<div class="card shadow mb-3">
  <div class="card-header">Equipment Assignments</div>
  <div class="card-body p-0">
    <?php if (!empty($equipment)): ?>
      <table class="table table-dark table-sm mb-0">
        <thead><tr><th>Chassis</th><th>Role</th><th>Weight</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach($equipment as $e): ?>
            <tr>
              <td>
                <a class="link-info" href="/equipment/<?= esc($e['equipment_id']) ?>">
                  <?= esc($e['chassis_name']) ?>
                </a>
              </td>
              <td><?= esc($e['role']) ?></td>
              <td><?= esc($e['weight_class']) ?></td>
              <td><?= esc($e['equipment_status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted">No equipment assignments found.</p>
    <?php endif; ?>
  </div>
</div>
