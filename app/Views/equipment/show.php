<div class="row">
  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Equipment Details</div>
      <div class="card-body">
        <p><strong>Chassis:</strong> <?= esc($equipment['chassis_name']) ?></p>
        <p><strong>Type:</strong> <?= esc($equipment['chassis_type'] ?? 'Unknown') ?></p>
        <p><strong>Weight Class:</strong> <?= esc($equipment['weight_class'] ?? 'Unknown') ?></p>
        <p><strong>Serial Number:</strong> <?= esc($equipment['serial_number']) ?></p>
        <p><strong>Damage:</strong> <?= esc($equipment['damage_percentage']).'%' ?></p>
        <p><strong>Status:</strong> <?= esc($equipment['equipment_status']) ?></p>
        <p><strong>Assigned Unit:</strong>
          <?php if (!empty($equipment['unit_name'])): ?>
            <a class="link-info" href="/units/<?= esc($equipment['assigned_unit_id']) ?>">
              <?= esc($equipment['unit_name']) ?>
            </a>
          <?php else: ?>
            <span class="text-muted">Unassigned</span>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Crew / Pilot</div>
      <div class="card-body p-0">
        <?php if (!empty($crew)): ?>
          <table class="table table-dark table-sm mb-0">
            <thead>
              <tr><th>Name</th><th>Grade</th><th>Role</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach($crew as $c): ?>
                <tr>
                  <td>
                    <a class="link-info" href="/personnel/<?= esc($c['personnel_id']) ?>">
                      <?= esc($c['first_name'].' '.$c['last_name']) ?>
                    </a>
                  </td>
                  <td><?= esc($c['grade']) ?></td>
                  <td><?= esc($c['role']) ?></td>
                  <td><?= esc($c['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted">No crew assigned.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>