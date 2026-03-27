<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">TOE Templates</h4>
  <a href="/toe/create" class="btn btn-outline-info btn-sm">
    <i class="bi bi-plus-circle me-1"></i> New Template
  </a>
</div>

<?php
$grouped = [];
foreach ($templates as $t) {
    $grouped[$t['unit_type']][] = $t;
}
$order = ['Regiment','Battalion','Company','Lance','Platoon','Squad'];
?>

<?php foreach ($order as $type): ?>
  <?php if (!empty($grouped[$type])): ?>
    <h6 class="text-uppercase text-muted small mb-2"><?= $type ?></h6>
    <div class="row g-2 mb-4">
      <?php foreach ($grouped[$type] as $t): ?>
        <div class="col-md-4">
          <div class="card bg-dark border-secondary shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <h6 class="mb-1"><?= esc($t['name']) ?></h6>
                <?php if ($t['faction']): ?>
                <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                    <?php if (!empty($t['faction_emblem'])): ?>
                    <img src="<?= esc($t['faction_emblem']) ?>"
                        style="height:12px; width:auto;">
                    <?php endif; ?>
                    <?= esc($t['faction']) ?>
                </span>
                <?php endif; ?>
              </div>
              <?php if ($t['description']): ?>
                <p class="text-muted small mb-2"><?= esc($t['description']) ?></p>
              <?php endif; ?>
              <div class="text-muted small mb-2">
                <?= esc($t['role'] ?? 'No Role') ?>
                <?php if ($t['mobility']): ?>
                  · <?= esc($t['mobility']) ?>
                <?php endif; ?>
                · <?= esc($t['slot_count']) ?> slots
                · <?= esc($t['subunit_count']) ?> subunits
              </div>
              <a href="/toe/<?= esc($t['template_id']) ?>"
                 class="btn btn-sm btn-outline-info w-100">
                View / Edit
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<?php if (empty($templates)): ?>
  <p class="text-muted text-center py-5">
    No templates yet. <a href="/toe/create" class="link-info">Create your first template.</a>
  </p>
<?php endif; ?>