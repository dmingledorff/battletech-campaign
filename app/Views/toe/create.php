<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">New TOE Template</h4>
  <a href="/toe" class="btn btn-outline-secondary btn-sm">← Back</a>
</div>

<div class="card shadow">
  <div class="card-body">
    <form action="/toe/store" method="post">
      <?= csrf_field() ?>
      <?php include('_form.php') ?>
      <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-outline-info">Save Template</button>
        <a href="/toe" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>