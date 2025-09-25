<h2 class="mb-3">Equipment</h2>
<div class="card shadow">
  <div class="card-body table-responsive">
    <table class="table table-dark table-striped table-hover table-sm">
      <thead><tr><th>Serial</th><th>Chassis</th><th>Type</th><th>Weight</th><th>Status</th><th>Supply/day</th></tr></thead>
      <tbody>
        <?php foreach($items as $i): ?>
          <tr>
            <td><a class="link-info" href="/equipment/<?= esc($i['equipment_id']) ?>"><?= esc($i['serial_number']) ?></a></td>
            <td><?= esc($i['chassis_name']) ?></td>
            <td><?= esc($i['type']) ?></td>
            <td><?= esc($i['weight_class']) ?></td>
            <td><?= esc($i['status']) ?></td>
            <td><?= number_format($i['supply_consumption_rate'],2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
