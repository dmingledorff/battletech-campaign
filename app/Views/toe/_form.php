<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Template Name</label>
    <input type="text" name="name"
           value="<?= esc($template['name'] ?? '') ?>"
           class="form-control bg-dark text-light border-secondary" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">Unit Type</label>
    <select name="unit_type" class="form-select bg-dark text-light border-secondary" required>
      <?php foreach (['Regiment','Battalion','Company','Lance','Platoon','Squad'] as $type): ?>
        <option value="<?= $type ?>"
          <?= ($template['unit_type'] ?? '') === $type ? 'selected' : '' ?>>
          <?= $type ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Role <span class="text-muted small">(optional)</span></label>
    <select name="role" class="form-select bg-dark text-light border-secondary">
      <option value="">None</option>
      <?php foreach (['Command','Battle','Striker','Pursuit','Fire','Security','Support','Assault','Recon','Urban Combat','Infantry'] as $role): ?>
        <option value="<?= $role ?>"
          <?= ($template['role'] ?? '') === $role ? 'selected' : '' ?>>
          <?= $role ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Mobility <span class="text-muted small">(optional)</span></label>
    <select name="mobility" class="form-select bg-dark text-light border-secondary">
      <option value="">None</option>
      <?php foreach (['Foot','Mechanized','Motorized','Airborne','Jump','Hover'] as $mob): ?>
        <option value="<?= $mob ?>"
          <?= ($template['mobility'] ?? '') === $mob ? 'selected' : '' ?>>
          <?= $mob ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Faction <span class="text-muted small">(optional)</span></label>
    <select name="faction" class="form-select bg-dark text-light border-secondary">
      <option value="">Any Faction</option>
      <?php foreach ($factions as $f): ?>
        <option value="<?= esc($f['house']) ?>"
          <?= ($template['faction'] ?? '') === $f['house'] ? 'selected' : '' ?>>
          <?= esc($f['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Era <span class="text-muted small">(optional)</span></label>
    <input type="text" name="era"
           value="<?= esc($template['era'] ?? '') ?>"
           placeholder="e.g. 3025"
           class="form-control bg-dark text-light border-secondary">
  </div>
  <div class="col-12">
    <label class="form-label">Description <span class="text-muted small">(optional)</span></label>
    <textarea name="description" rows="2"
              class="form-control bg-dark text-light border-secondary"><?= esc($template['description'] ?? '') ?></textarea>
  </div>
</div>