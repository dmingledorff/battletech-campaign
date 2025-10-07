<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-dark text-light p-2">
    <?php foreach ($breadcrumb as $b): ?>
      <li class="breadcrumb-item">
        <a class="link-info" href="/units/<?= esc($b['unit_id']) ?>">
          <?= esc($b['name']) ?>
        </a>
        <?php if (!empty($b['nickname'])): ?>
          <span class="badge bg-info-subtle text-info-emphasis"><?= esc($b['nickname']) ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>

<div class="row">
  <!-- Unit Information -->
  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Unit Information</div>
      <div class="card-body">
        <p><strong>Name:</strong> <?= esc($unit['name']) ?></p>
        <p><strong>Nickname:</strong> <?= esc($unit['nickname']) ?></p>
        <p><strong>Type:</strong> <?= esc($unit['unit_type']) ?></p>
        <p><strong>Role:</strong> <?= esc($unit['role']) ?></p>
        <p><strong>Commander:</strong>
          <?php if (!empty($unit['commander_id'])): ?>
            <a class="link-info" href="/personnel/<?= esc($unit['commander_id']) ?>">
              <?= esc($unit['rank_abbr']) ?>. <?= esc($unit['last_name'] . ', ' . $unit['first_name']) ?>
            </a>
          <?php else: ?>
            <span class="text-muted">Unassigned</span>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>

  <!-- Combat Readiness -->
  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Combat Readiness</div>
      <div class="card-body">
        <?php
        // morale color coding
        $morale = $unit['avg_morale'];
        if ($morale >= 70) {
          $moraleColor = 'green';
        } elseif ($morale >= 40) {
          $moraleColor = 'yellow';
        } else {
          $moraleColor = 'red';
        }
        ?>
        <p><strong>Average Morale:</strong> <span style="color: <?= $moraleColor ?>">
            <?= number_format($morale, 2) ?>%
          </span></p>
        <p><strong>Personnel Strength:</strong>
          <?= esc($personnelStrength['assigned']) ?>/<?= esc($personnelStrength['authorized']) ?>
          (<?= number_format($personnelStrength['percent'], 2) ?>%)
        </p>
        <p><strong>Equipment Strength:</strong>
          <?= esc($equipmentStrength['operational']) ?>/<?= esc($equipmentStrength['authorized']) ?>
          (<?= number_format($equipmentStrength['percent'], 2) ?>%)
        </p>
        <p><strong>Current Supply:</strong> <?= esc($unit['current_supply']) ?></p>
        <p><strong>Daily Supply Use:</strong> </p>
        <p><strong>Combat Supply Use:</strong> </p>
      </div>
    </div>
  </div>
</div>

<div class="card shadow mb-3">
  <div class="card-header">Sub-Units</div>
  <div class="card-body p-0">
    <?php if (!empty($children[$unit['unit_id']])): ?>
      <table class="table table-dark table-sm mb-0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Nickname</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($children[$unit['unit_id']] as $child): ?>
            <tr>
              <td><a class="link-info" href="/units/<?= esc($child['unit_id']) ?>"><?= esc($child['name']) ?></a></td>
              <td><?= esc($child['unit_type']) ?></td>
              <td><?= esc($child['nickname'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted mb-0">No sub-units assigned.</p>
    <?php endif; ?>
  </div>
</div>


<div class="row">
  <div class="col-md-6">

    <div class="card shadow mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Personnel (incl. sub-units)</span>
        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#managePersonnelModal">
          Manage
        </button>
      </div>
      <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Callsign</th>
              <th>Rank</th>
              <th>Unit</th>
              <th>Status</th>
              <th>Morale</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($personnel as $p): ?>
              <tr>
                <td>
                  <a class="link-info" href="/personnel/<?= esc($p['personnel_id']) ?>">
                    <?= esc($p['last_name'] . ', ' . $p['first_name']) ?>
                  </a>
                </td>
                <td><?= esc($p['callsign']) ?></td>
                <td><?= esc($p['rank_full']) ?></td>
                <td><?= esc($p['unit_name'] ?? '-') ?></td>
                <td><?= esc($p['status']) ?></td>
                <td><?= esc($p['morale']) ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Equipment (incl. sub-units)</span>
        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#manageEquipmentModal">
          Manage
        </button>
      </div>
      <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0">
          <thead>
            <tr>
              <th>Chassis</th>
              <th>Variant</th>
              <th>Type</th>
              <th>Weight Class</th>
              <th>Damage</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($equipment as $e): ?>
              <tr>
                <td>
                  <a class="link-info" href="/equipment/<?= esc($e['equipment_id']) ?>">
                    <?= esc($e['chassis_name']) ?>
                  </a>
                </td>
                <td><?= esc($e['chassis_variant'] ?? 'Unknown') ?></td>
                <td><?= esc($e['chassis_type'] ?? 'Unknown') ?></td>
                <td><?= esc($e['weight_class'] ?? 'Unknown') ?></td>
                <td><?= esc($e['damage_percentage']) . '%' ?></td>
                <td><?= esc($e['equipment_status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Manage Personnel Modal -->
<div class="modal fade" id="managePersonnelModal" tabindex="-1" aria-labelledby="managePersonnelLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-secondary shadow-lg">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="managePersonnelLabel">
          <i class="bi bi-people-fill me-2"></i>Manage Unit Personnel
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row">
          <!-- Unassigned (Available) -->
          <div class="col-md-5">
            <h6 class="text-info mb-2">Available Personnel</h6>
            <select multiple id="availablePersonnel" class="form-select bg-black text-light border-secondary" size="15">
              <?php foreach ($availablePersonnel as $p): ?>
                <option value="<?= $p['personnel_id'] ?>"
                  data-name="<?= esc($p['first_name'] . ' ' . $p['last_name']) ?>"
                  data-mos="<?= esc($p['mos']) ?>"
                  data-status="<?= esc($p['status']) ?>"
                  data-dob="<?= esc($p['date_of_birth'] ?? 'N/A') ?>"
                  data-experience="<?= esc($p['experience']) ?>"
                  data-morale="<?= esc($p['morale']) ?>">
                  <?= esc($p['last_name'] . ', ' . $p['first_name'] . ' (' . $p['rank_full'] . ')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Control Buttons -->
          <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
            <button id="assignBtn" class="btn btn-outline-success btn-sm my-2" title="Assign Selected">
              <i class="bi bi-arrow-right-circle-fill fs-4"></i>
            </button>
            <button id="unassignBtn" class="btn btn-outline-danger btn-sm my-2" title="Unassign Selected">
              <i class="bi bi-arrow-left-circle-fill fs-4"></i>
            </button>
          </div>

          <!-- Assigned (Direct) -->
          <div class="col-md-5">
            <h6 class="text-warning mb-2">Assigned to Unit</h6>
            <select multiple id="assignedPersonnel" class="form-select bg-black text-light border-secondary" size="15">
              <?php foreach ($assignedDirectPersonnel as $p): ?>
                <option value="<?= $p['personnel_id'] ?>"
                  data-name="<?= esc($p['first_name'] . ' ' . $p['last_name']) ?>"
                  data-mos="<?= esc($p['mos']) ?>"
                  data-status="<?= esc($p['status']) ?>"
                  data-dob="<?= esc($p['date_of_birth'] ?? 'N/A') ?>"
                  data-experience="<?= esc($p['experience']) ?>"
                  data-morale="<?= esc($p['morale']) ?>">
                  <?= esc($p['last_name'] . ', ' . $p['first_name'] . ' (' . $p['rank_full'] . ')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Personnel Detail Panel -->
          <div class="mt-4">
            <h6 class="text-center text-info">Selected Personnel Details</h6>
            <div id="personnelDetails" class="p-3 bg-black border border-secondary rounded">
              <p class="text-muted mb-0">Select a personnel member to view their details.</p>
            </div>
          </div>
        </div>
        <div class="modal-footer justify-content-between border-secondary">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" id="savePersonnelChanges"
            class="btn border-secondary text-light">
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Manage Equipment Modal -->
<div class="modal fade" id="manageEquipmentModal" tabindex="-1" aria-labelledby="manageEquipmentLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-secondary">
      <div class="modal-header">
        <h5 class="modal-title" id="manageEquipmentLabel">
          Manage Equipment — <?= esc($unit['name']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-5">
            <h6 class="text-center">Available Equipment</h6>
            <select multiple class="form-select bg-black text-light" id="availableEquipment" size="10">
              <?php foreach ($availableEquipment as $e): ?>
                <option value="<?= $e['equipment_id'] ?>">
                  <?= esc($e['chassis_name'] . ' ' . $e['chassis_variant'] . ' (' . $e['weight_class'] . ')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
            <button class="btn btn-outline-success mb-2 border-secondary text-light" id="assignEquipmentBtn">
              <i class="bi bi-arrow-right"></i> Assign
            </button>
            <button class="btn btn-outline-danger" id="unassignEquipmentBtn">
              <i class="bi bi-arrow-left"></i> Remove
            </button>
          </div>

          <div class="col-md-5">
            <h6 class="text-center">Assigned Equipment</h6>
            <select multiple class="form-select bg-black text-light border-secondary" id="assignedEquipment" size="10">
              <?php foreach ($directEquipment as $e): ?>
                <option value="<?= $e['equipment_id'] ?>">
                  <?= esc($e['chassis_name'] . ' ' . $e['chassis_variant'] . ' (' . $e['weight_class'] . ')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" id="saveEquipmentChanges"
          class="btn border-secondary text-light">
          Save Changes
        </button>
      </div>
    </div>
  </div>
</div>


<script>
  document.addEventListener("DOMContentLoaded", () => {
    // Move selected options between lists
    function moveSelected(from, to) {
      const selected = Array.from(from.selectedOptions);
      selected.forEach(opt => to.appendChild(opt));
    }

    const availablePersonnel = document.getElementById("availablePersonnel");
    const assignedPersonnel = document.getElementById("assignedPersonnel");
    const assignBtn = document.getElementById("assignBtn");
    const unassignBtn = document.getElementById("unassignBtn");

    // Assign/unassign actions
    assignBtn.onclick = () => moveSelected(availablePersonnel, assignedPersonnel);
    unassignBtn.onclick = () => moveSelected(assignedPersonnel, availablePersonnel);

    // Save Personnel Changes
    document.getElementById("savePersonnelChanges")?.addEventListener("click", () => {
      const assignedIds = Array.from(assignedPersonnel.options).map(o => o.value);
      fetch(`/units/managePersonnel/<?= $unit['unit_id'] ?>`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          personnel_ids: assignedIds
        })
      }).then(() => location.reload());
    });

    // Equipment buttons (still matched properly)
    const availableEquipment = document.getElementById("availableEquipment");
    const assignedEquipment = document.getElementById("assignedEquipment");
    document.getElementById("assignEquipmentBtn").onclick = () => moveSelected(availableEquipment, assignedEquipment);
    document.getElementById("unassignEquipmentBtn").onclick = () => moveSelected(assignedEquipment, availableEquipment);

    document.getElementById("saveEquipmentChanges").onclick = () => {
      const ids = Array.from(assignedEquipment.options).map(o => o.value);
      fetch(`/units/manageEquipment/<?= $unit['unit_id'] ?>`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          equipment_ids: ids
        })
      }).then(() => location.reload());
    };

    // Show selected personnel details
    const details = document.getElementById('personnelDetails');
    const populateDetails = (option) => {
      details.innerHTML = `
      <div class="row text-center">
        <div class="col-md-4"><strong>Name:</strong><br>${option.dataset.name}</div>
        <div class="col-md-4"><strong>MOS:</strong><br>${option.dataset.mos}</div>
        <div class="col-md-4"><strong>Status:</strong><br>${option.dataset.status}</div>
      </div>
      <div class="row text-center mt-2">
        <div class="col-md-4"><strong>DOB:</strong><br>${option.dataset.dob}</div>
        <div class="col-md-4"><strong>Experience:</strong><br>${option.dataset.experience}</div>
        <div class="col-md-4"><strong>Morale:</strong><br>${option.dataset.morale}%</div>
      </div>
    `;
    };

    document.querySelectorAll('#availablePersonnel, #assignedPersonnel').forEach(select => {
      select.addEventListener('change', e => {
        const selected = e.target.selectedOptions[0];
        if (selected) populateDetails(selected);
      });
    });
  });
</script>