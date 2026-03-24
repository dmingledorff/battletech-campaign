<div class="row">
  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Equipment Details</div>
      <div class="card-body">
        <p><strong>Chassis:</strong> <?= esc($equipment['chassis_name'])
         . ' ' . esc($equipment['chassis_variant'])?></p>
        <p><strong>Type:</strong> <?= esc($equipment['chassis_type'] ?? 'Unknown') ?></p>
        <p><strong>Weight Class:</strong> <?= esc($equipment['weight_class'] ?? 'Unknown') ?></p>
        <p><strong>Role:</strong> <?= esc($equipment['role'] ?? 'Unknown') ?></p>
        <p><strong>Tonnage:</strong> <?= esc($equipment['tonnage'] . ' tons' ?? 'Unknown') ?></p>
        <p><strong>Speed:</strong> <?= esc($equipment['speed'] . ' km/h' ?? 'Unknown') ?></p>
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
      <?php if (!empty($crewManifest)): ?>
        <table class="table table-dark table-sm mb-0">
          <thead>
            <tr>
              <th>Role</th>
              <th>Assigned</th>
              <th>Rank</th>
              <th>Status</th>
              <th>Morale</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($crewManifest as $slot): ?>
              <tr class="<?= !$slot['is_required'] && !$slot['personnel_id'] ? 'text-muted' : '' ?>">
                <td>
                  <?= esc($slot['crew_role']) ?>
                  <?php if ($slot['is_required']): ?>
                    <span class="badge bg-danger ms-1" title="Required">R</span>
                  <?php else: ?>
                    <span class="badge bg-secondary ms-1" title="Optional">O</span>
                  <?php endif; ?>
                </td>
                <?php if ($slot['personnel_id']): ?>
                  <td>
                    <a class="link-info" href="/personnel/<?= esc($slot['personnel_id']) ?>">
                      <?= esc($slot['last_name'] . ', ' . $slot['first_name']) ?>
                    </a>
                  </td>
                  <td><?= esc($slot['rank_abbr']) ?></td>
                  <td><?= esc($slot['status']) ?></td>
                  <td><?= esc($slot['morale']) ?>%</td>
                  <td>
                    <button class="btn btn-xs btn-outline-danger remove-crew-btn"
                      data-equipment-id="<?= esc($equipment['equipment_id']) ?>"
                      data-personnel-id="<?= esc($slot['personnel_id']) ?>"
                      data-slot-id="<?= esc($slot['slot_id']) ?>">
                      Remove
                    </button>
                  </td>
                <?php else: ?>
                  <td colspan="4" class="text-muted fst-italic">
                    <?= $slot['is_required'] ? '<span class="text-warning">Unfilled</span>' : 'Empty' ?>
                  </td>
                  <td>
                    <?php if (!empty($equipment['assigned_unit_id'])): ?>
                      <button class="btn btn-xs btn-outline-info assign-crew-btn"
                        data-equipment-id="<?= esc($equipment['equipment_id']) ?>"
                        data-slot-id="<?= esc($slot['slot_id']) ?>"
                        data-crew-role="<?= esc($slot['crew_role']) ?>"
                        data-required-mos="<?= esc($slot['required_mos']) ?>">
                        Assign
                      </button>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted p-3 mb-0">No crew requirements defined.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Assign Crew Modal -->
<div class="modal fade" id="assignCrewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-secondary shadow-lg">
      <div class="modal-header border-secondary">
        <h5 class="modal-title">
          <i class="bi bi-person-fill-add me-2"></i>
          Assign <span id="modalRoleLabel"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-2">
          MOS Required: <span class="text-info" id="modalMosLabel"></span>
        </p>
        <div id="crewLoadingSpinner" class="text-center py-3 d-none">
          <div class="spinner-border spinner-border-sm text-info"></div>
          <span class="ms-2">Loading available personnel...</span>
        </div>
        <select id="availableCrewList" class="form-select bg-black text-light border-secondary" size="8">
        </select>
        <div id="noCrewMessage" class="text-muted fst-italic text-center py-2 d-none">
          No available personnel with matching MOS.
        </div>
        <!-- Selected personnel detail panel -->
        <div id="crewDetails" class="mt-3 p-2 bg-black rounded border border-secondary d-none">
          <div class="row text-center small">
            <div class="col-4"><strong>Experience</strong><br><span id="detailExperience"></span></div>
            <div class="col-4"><strong>Status</strong><br><span id="detailStatus"></span></div>
            <div class="col-4"><strong>Morale</strong><br><span id="detailMorale"></span></div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-outline-success" id="confirmAssignCrewBtn" disabled>
          Assign
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let activeSlotId    = null;
  let activeCrewRole  = null;
  let activeEquipId   = null;

  // Open assign modal
  document.querySelectorAll('.assign-crew-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      activeSlotId   = btn.dataset.slotId;
      activeCrewRole = btn.dataset.crewRole;
      activeEquipId  = btn.dataset.equipmentId;

      document.getElementById('modalRoleLabel').textContent = activeCrewRole;
      document.getElementById('modalMosLabel').textContent  = btn.dataset.requiredMos;
      document.getElementById('availableCrewList').innerHTML = '';
      document.getElementById('crewDetails').classList.add('d-none');
      document.getElementById('confirmAssignCrewBtn').disabled = true;
      document.getElementById('noCrewMessage').classList.add('d-none');

      const spinner = document.getElementById('crewLoadingSpinner');
      spinner.classList.remove('d-none');

      const modal = new bootstrap.Modal(document.getElementById('assignCrewModal'));
      modal.show();

      // Fetch available crew
      fetch(`/equipment/getAvailableCrew/${activeEquipId}/${activeSlotId}`)
        .then(r => r.json())
        .then(data => {
          spinner.classList.add('d-none');
          const list = document.getElementById('availableCrewList');

          if (!data.length) {
            document.getElementById('noCrewMessage').classList.remove('d-none');
            return;
          }

          data.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.personnel_id;
            opt.textContent = `${p.last_name}, ${p.first_name} (${p.rank_abbr})`;
            opt.dataset.experience = p.experience;
            opt.dataset.status     = p.status;
            opt.dataset.morale     = p.morale;
            list.appendChild(opt);
          });
        });
    });
  });

  // Show personnel details on selection
  document.getElementById('availableCrewList').addEventListener('change', e => {
    const opt = e.target.selectedOptions[0];
    if (!opt) return;

    document.getElementById('detailExperience').textContent = opt.dataset.experience;
    document.getElementById('detailStatus').textContent     = opt.dataset.status;
    document.getElementById('detailMorale').textContent     = opt.dataset.morale + '%';
    document.getElementById('crewDetails').classList.remove('d-none');
    document.getElementById('confirmAssignCrewBtn').disabled = false;
  });

  // Confirm assign
  document.getElementById('confirmAssignCrewBtn').addEventListener('click', () => {
    const personnelId = document.getElementById('availableCrewList').value;
    if (!personnelId) return;

    fetch(`/equipment/assignCrew/${activeEquipId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        personnel_id: parseInt(personnelId),
        slot_id:      parseInt(activeSlotId),
        crew_role:    activeCrewRole,
      })
    }).then(() => location.reload());
  });

  // Remove crew inline
  document.querySelectorAll('.remove-crew-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!confirm(`Remove this crew member from their slot?`)) return;

      fetch(`/equipment/removeCrew/${btn.dataset.equipmentId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          personnel_id: parseInt(btn.dataset.personnelId),
          slot_id:      parseInt(btn.dataset.slotId),
        })
      }).then(() => location.reload());
    });
  });
});
</script>


  <pre><?= print_r($crewManifest, true) ?></pre>
</div>