<h2 class="mb-3">Personnel</h2>
<div class="card shadow">
  <div class="card-body table-responsive">
    <table class="table table-dark table-striped table-hover table-sm">
      <thead><tr><th>Name</th><th>Grade</th><th>Specialty</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($people as $p): ?>
          <tr>
            <td><a class="link-info" href="/personnel/<?= esc($p['personnel_id']) ?>"><?= esc($p['name']) ?></a></td>
            <td><?= esc($p['grade']) ?></td>
            <td><?= esc($p['specialty']) ?></td>
            <td><?= esc($p['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
