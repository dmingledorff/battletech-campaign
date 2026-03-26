<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Mission Control</h4>
  <a href="/missions/create" class="btn btn-outline-info btn-sm">
    <i class="bi bi-plus-circle me-1"></i> Plan Mission
  </a>
</div>

<!-- In Transit -->
<?php if (!empty($inTransit)): ?>
<h6 class="text-uppercase text-muted small mb-2">In Transit</h6>
<div class="row g-3 mb-4">
  <?php foreach ($inTransit as $m): ?>
    <?php
      $progress = $m['transit_days'] > 0
        ? min(100, round(($m['days_elapsed'] / $m['transit_days']) * 100))
        : 0;
    ?>
    <div class="col-md-4">
      <div class="card bg-dark border-info shadow h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <h6 class="card-title mb-0"><?= esc($m['name']) ?></h6>
            <span class="badge bg-info"><?= esc($m['mission_type']) ?></span>
          </div>
          <p class="text-muted small mb-1">
            <?= esc($m['origin_name']) ?> (<?= esc($m['origin_planet']) ?>)
            → <?= esc($m['destination_name']) ?> (<?= esc($m['destination_planet']) ?>)
          </p>
          <p class="small mb-2">
            <strong>ETA:</strong> <?= esc($m['eta_date']) ?>
            &nbsp;·&nbsp;
            <strong>Day:</strong> <?= esc($m['days_elapsed']) ?>/<?= esc($m['transit_days']) ?>
          </p>
          <div class="progress mb-2" style="height: 6px;">
            <div class="progress-bar bg-info" style="width: <?= $progress ?>%"></div>
          </div>
          <a href="/missions/<?= esc($m['mission_id']) ?>" class="btn btn-sm btn-outline-info w-100">
            View Mission
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Planning -->
<?php if (!empty($planning)): ?>
<h6 class="text-uppercase text-muted small mb-2">Planning</h6>
<div class="row g-3 mb-4">
  <?php foreach ($planning as $m): ?>
    <div class="col-md-4">
      <div class="card bg-dark border-secondary shadow h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <h6 class="card-title mb-0"><?= esc($m['name']) ?></h6>
            <span class="badge bg-secondary"><?= esc($m['mission_type']) ?></span>
          </div>
          <p class="text-muted small mb-2">
            <?= esc($m['origin_name']) ?> (<?= esc($m['origin_planet']) ?>)
            → <?= esc($m['destination_name']) ?> (<?= esc($m['destination_planet']) ?>)
          </p>
          <a href="/missions/<?= esc($m['mission_id']) ?>" class="btn btn-sm btn-outline-secondary w-100">
            View / Edit
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Arrived -->
<?php if (!empty($arrived)): ?>
<h6 class="text-uppercase text-muted small mb-2">Recently Arrived</h6>
<div class="row g-3 mb-4">
  <?php foreach ($arrived as $m): ?>
    <div class="col-md-4">
      <div class="card bg-dark border-success shadow h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <h6 class="card-title mb-0"><?= esc($m['name']) ?></h6>
            <span class="badge bg-success"><?= esc($m['mission_type']) ?></span>
          </div>
          <p class="text-muted small mb-2">
            <?= esc($m['origin_name']) ?> → <?= esc($m['destination_name']) ?>
          </p>
          <p class="small mb-2">
            <strong>Arrived:</strong> <?= esc($m['arrived_date']) ?>
          </p>
          <a href="/missions/<?= esc($m['mission_id']) ?>" class="btn btn-sm btn-outline-success w-100">
            View
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($planning) && empty($inTransit) && empty($arrived)): ?>
  <div class="text-muted text-center py-5">
    No missions yet. <a href="/missions/create" class="link-info">Plan your first mission.</a>
  </div>
<?php endif; ?>