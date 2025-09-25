<h2 class="mb-3">Units</h2>
<div class="card shadow">
  <div class="card-body table-responsive">
    <table class="table table-dark table-striped table-hover table-sm">
      <thead><tr><th>Unit</th><th>Type</th><th>Personnel</th><th>Equipment</th><th>Req. Supply</th></tr></thead>
      <tbody>
        <?php foreach($summary as $row): ?>
          <tr>
            <td><a class="link-info" href="/units/<?= esc($row['unit_id']) ?>"><?= esc($row['name']) ?></a></td>
            <td><?= esc($row['unit_type']) ?></td>
            <td><?= esc($row['personnel_count']) ?></td>
            <td><?= esc($row['equipment_count']) ?></td>
            <td><?= number_format($row['required_supply'],2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
