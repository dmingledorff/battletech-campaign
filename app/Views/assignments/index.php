<h2 class="mb-3">Assignments</h2>
<div class="card shadow">
  <div class="card-body table-responsive">
    <table class="table table-dark table-striped table-hover table-sm">
      <thead><tr><th>Personnel</th><th>Grade</th><th>Serial</th><th>Chassis</th><th>Type</th><th>Weight</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= esc($r['personnel']) ?></td>
            <td><?= esc($r['grade']) ?></td>
            <td><?= esc($r['serial_number']) ?></td>
            <td><?= esc($r['chassis_name']) ?></td>
            <td><?= esc($r['type']) ?></td>
            <td><?= esc($r['weight_class']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
