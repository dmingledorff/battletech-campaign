<div class="card shadow mb-3">
  <div class="card-header">Personnel Details</div>
  <div class="card-body">
    <p><strong>Name:</strong> <?= esc($person['first_name'].' '.$person['last_name']) ?></p>
    <p><strong>Grade:</strong> <?= esc($person['grade']) ?></p>
    <p><strong>Status:</strong> <?= esc($person['status']) ?></p>
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
