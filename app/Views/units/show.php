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
        <div class="d-flex align-items-center gap-2 mb-1" id="unitNameDisplay">
          <h4 class="mb-0" id="unitNameText"><?= esc($unit['name']) ?></h4>
          <?php if ($unit['nickname']): ?>
            <span class="badge bg-info-subtle text-info-emphasis" id="unitNicknameText">
              <?= esc($unit['nickname']) ?>
            </span>
          <?php endif; ?>
          <?php if (!$onMission): ?>
            <button class="btn btn-xs btn-outline-secondary" onclick="editNameToggle(true)">
              <i class="bi bi-pencil"></i>
            </button>
          <?php endif; ?>
        </div>

        <!-- Inline edit form (hidden by default) -->
        <div id="unitNameEdit" class="d-none mb-2">
          <div class="d-flex gap-2 align-items-center">
            <input type="text" id="editName" value="<?= esc($unit['name']) ?>"
              class="form-control form-control-sm bg-dark text-light border-secondary"
              style="max-width:250px;">
            <input type="text" id="editNickname" value="<?= esc($unit['nickname'] ?? '') ?>"
              placeholder="Nickname (optional)"
              class="form-control form-control-sm bg-dark text-light border-secondary"
              style="max-width:200px;">
            <button class="btn btn-sm btn-outline-success" onclick="saveName()">Save</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="editNameToggle(false)">Cancel</button>
          </div>
        </div>
        <p><strong>Type:</strong> <?= esc($unit['unit_type']) ?></p>
        <p><strong>Role:</strong> <?= esc($unit['role']) ?></p>
        <p><strong>Status:</strong>
          <?php
          $statusColor = match ($unit['status'] ?? 'Garrisoned') {
            'Garrisoned' => 'success',
            'In Transit' => 'info',
            'Combat'     => 'danger',
            default      => 'secondary',
          };
          ?>
          <span class="badge bg-<?= $statusColor ?>"><?= esc($unit['status'] ?? 'Garrisoned') ?></span>
        </p>

        <p>
          <strong>Location:</strong>
          <?php if (($unit['status'] === 'In Transit' || $unit['status'] === 'Combat') && $unit['mission_id']): ?>
            <a class="link-info" href="/missions/<?= esc($unit['mission_id']) ?>">
              <?= esc($unit['mission_name']) ?>
            </a>
            <?php if ($unit['status'] === 'In Transit'): ?>
              <span class="text-muted small">
                — <?= esc($unit['mission_origin']) ?> → <?= esc($unit['mission_destination']) ?>
                · ETA: <?= esc($unit['eta_date']) ?>
                (<?= esc($unit['days_elapsed']) ?>/<?= esc($unit['transit_days']) ?> days)
              </span>
            <?php endif; ?>
          <?php elseif (!empty($unit['location_id'])): ?>
            <a class="link-info" href="/location/<?= esc($unit['location_id']) ?>">
              <?= esc($unit['location_name']) ?>
              <?php if (!empty($unit['planet_name'])): ?>
                <span class="text-muted">(<?= esc($unit['planet_name']) ?>)</span>
              <?php endif; ?>
            </a>
          <?php else: ?>
            <span class="text-muted">Unknown</span>
          <?php endif; ?>
        </p>
        <p>
          <strong>Commander:</strong>
          <?php if (!empty($unit['commander_id'])): ?>
            <a class="link-info" href="/personnel/<?= esc($unit['commander_id']) ?>">
              <?= esc($unit['rank_abbr']) ?>. <?= esc($unit['last_name'] . ', ' . $unit['first_name']) ?>
            </a>
            <?php if (!$onMission): ?>
              <button class="btn btn-sm btn-outline-danger ms-2" data-bs-toggle="modal" data-bs-target="#assignCommanderModal">
                Dismiss
              </button>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-muted">Unassigned</span>
            <?php if (!$onMission): ?>
              <button class="btn btn-sm btn-outline-info ms-2" data-bs-toggle="modal" data-bs-target="#assignCommanderModal">
                Assign
              </button>
            <?php endif; ?>
          <?php endif; ?>
        </p>
        <?php if (!$onMission && $unit['status'] !== 'Deactivated'): ?>
          <button class="btn btn-sm btn-outline-danger mt-2"
            onclick="deactivateUnit(<?= $unit['unit_id'] ?>)">
            <i class="bi bi-slash-circle me-1"></i>Deactivate Unit
          </button>
        <?php endif; ?>
        <?php if ($unit['status'] === 'Deactivated'): ?>
          <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span><i class="bi bi-exclamation-triangle me-2"></i>This unit is deactivated.</span>
            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#reactivateModal">
              Reactivate
            </button>
          </div>

          <!-- Reactivate Modal -->
          <div class="modal fade" id="reactivateModal" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content bg-dark text-light">
                <div class="modal-header border-secondary">
                  <h5 class="modal-title">Reactivate <?= esc($unit['name']) ?></h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <label class="form-label">Starting Location</label>
                  <select id="reactivateLocation"
                    class="form-select bg-dark text-light border-secondary">
                    <?php foreach ($allLocations as $loc): ?>
                      <option value="<?= $loc['location_id'] ?>">
                        <?= esc($loc['name']) ?> (<?= esc($loc['planet_name']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="modal-footer border-secondary">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-outline-success" onclick="reactivateUnit()">Reactivate</button>
                </div>
              </div>
            </div>
          </div>

          <script>
            function reactivateUnit() {
              const locationId = document.getElementById('reactivateLocation').value;
              fetch(`/units/<?= $unit['unit_id'] ?>/reactivate`, {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                    location_id: locationId
                  })
                })
                .then(r => r.json())
                .then(d => {
                  if (d.success) location.reload();
                  else alert(d.message);
                });
            }
          </script>
        <?php endif; ?>
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
          <?= esc($strength['asgn_personnel']) ?>/<?= esc($strength['auth_personnel']) ?>
          (<?= number_format($strength['pct_personnel'], 2) ?>%)
        </p>
        <p><strong>Equipment Strength:</strong>
          <?= esc($strength['asgn_equipment']) ?>/<?= esc($strength['auth_equipment']) ?>
          (<?= number_format($strength['pct_equipment'], 2) ?>%)
        </p>
        <p><strong>Current Supply:</strong> <?= esc($unit['current_supply']) ?></p>
        <p><strong>Daily Supply Use:</strong> </p>
        <p><strong>Combat Supply Use:</strong> </p>
        <p><strong>Max Speed:</strong>
          <?php if ($unit['speed']): ?>
            <?= number_format($unit['speed'], 1) ?> kph
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</div>

<div class="card shadow mb-3">
  <div class="card-header">Sub-Units</div>
  <div class="card-body p-0">
    <table class="table table-dark table-sm mb-0">
      <thead>
        <tr>
          <th>Unit</th>
          <th>Type</th>
          <th>Role</th>
          <th>Speed</th>
          <th>Per%</th>
          <th>Eqp%</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="subunitTableBody">
        <?php if (!empty($children[$unit['unit_id']])): ?>
          <?php foreach ($children[$unit['unit_id']] as $sub):
            $speed    = $speedMap[$sub['unit_id']] ?? null;
            $subStr   = $subStrengths[$sub['unit_id']] ?? ['pct_personnel' => 0, 'pct_equipment' => 0];
            $persColor = $subStr['pct_personnel'] >= 75 ? 'success' : ($subStr['pct_personnel'] >= 50 ? 'warning' : 'danger');
            $eqpColor  = $subStr['pct_equipment'] >= 75 ? 'success' : ($subStr['pct_equipment'] >= 50 ? 'warning' : 'danger');
          ?>
            <tr id="subunit-<?= $sub['unit_id'] ?>">
              <td>
                <a class="link-info" href="/units/<?= esc($sub['unit_id']) ?>">
                  <?= esc($sub['name']) ?>
                </a>
                <?php if (!empty($sub['nickname'])): ?>
                  <span class="badge bg-info-subtle text-info-emphasis ms-1">
                    <?= esc($sub['nickname']) ?>
                  </span>
                <?php endif; ?>
              </td>
              <td class="small"><?= esc($sub['unit_type']) ?></td>
              <td class="small"><?= esc($sub['role'] ?? '—') ?></td>
              <td class="small">
                <?= $speed !== null ? number_format($speed, 1) . ' kph' : '<span class="text-muted">—</span>' ?>
              </td>
              <td><span class="text-<?= $persColor ?>"><?= number_format($subStr['pct_personnel'], 1) ?>%</span></td>
              <td><span class="text-<?= $eqpColor ?>"><?= number_format($subStr['pct_equipment'], 1) ?>%</span></td>
              <td>
                <?php if (!$onMission): ?>
                  <button class="btn btn-xs btn-outline-danger"
                    onclick="removeSubunit(<?= $unit['unit_id'] ?>, <?= $sub['unit_id'] ?>, this)">
                    Remove
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr id="subunitEmpty">
            <td colspan="7" class="text-muted p-3">No sub-units assigned.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$onMission && !empty($availableSubunits)): ?>
    <div class="card-footer border-secondary">
      <div class="d-flex gap-2 align-items-center">
        <select id="addSubunitSelect"
          class="form-select form-select-sm bg-dark text-light border-secondary">
          <option value="">Select unit to add...</option>
          <?php
          $byType = [];
          foreach ($availableSubunits as $u) {
            $byType[$u['unit_type']][] = $u;
          }
          foreach (['Battalion', 'Company', 'Platoon', 'Lance', 'Squad'] as $type):
            if (empty($byType[$type])) continue;
          ?>
            <optgroup label="<?= $type ?>">
              <?php foreach ($byType[$type] as $u): ?>
                <option value="<?= $u['unit_id'] ?>">
                  <?= esc($u['unit_chain']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-outline-success flex-shrink-0"
          onclick="addSubunit(<?= $unit['unit_id'] ?>)">
          <i class="bi bi-plus me-1"></i>Add
        </button>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="row">
  <div class="col-md-6">
    <!-- PERSONNEL CARD -->
    <div class="card shadow mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Personnel — <?= esc($unit['name']) ?></span>
        <?php if (!$onMission && !$isDispersed): ?>
          <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#managePersonnelModal">
            Manage
          </button>
        <?php elseif ($isDispersed): ?>
          <p class="text-muted small fst-italic">
            <i class="bi bi-diagram-3 me-1"></i>
            Unit is dispersed — manage individual subunits.
          </p>
        <?php else: ?>
          <span class="text-muted small fst-italic">Unit on mission</span>
        <?php endif; ?>
      </div>

      <div class="card-body p-0">
        <h6 class="bg-secondary text-light px-3 py-1 mb-0">Directly Assigned</h6>
        <table class="table table-dark table-sm mb-3">
          <thead>
            <tr>
              <th>Name</th>
              <th>Callsign</th>
              <th>Rank</th>
              <th>Status</th>
              <th>Morale</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assignedDirectPersonnel as $p): ?>
              <tr>
                <td><a class="link-info" href="/personnel/<?= esc($p['personnel_id']) ?>"><?= esc($p['last_name'] . ', ' . $p['first_name']) ?></a></td>
                <td><?= esc($p['callsign']) ?></td>
                <td><?= esc($p['rank_full']) ?></td>
                <td><?= esc($p['status']) ?></td>
                <td><?= esc($p['morale']) ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <button class="btn btn-outline-secondary btn-sm ms-3 mb-2" type="button"
          data-bs-toggle="collapse" data-bs-target="#personnelRollup"
          aria-expanded="false" aria-controls="personnelRollup">
          <i class="bi bi-chevron-down"></i> Show All Including Sub-Units
        </button>

        <div class="collapse" id="personnelRollup">
          <h6 class="bg-secondary text-light px-3 py-1 mb-0">Including Sub-Units</h6>
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
                  <td><a class="link-info" href="/personnel/<?= esc($p['personnel_id']) ?>"><?= esc($p['last_name'] . ', ' . $p['first_name']) ?></a></td>
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
  </div>

  <div class="col-md-6">
    <!-- EQUIPMENT CARD -->
    <div class="card shadow mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Equipment — <?= esc($unit['name']) ?></span>
        <?php if (!$onMission && !$isDispersed): ?>
          <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#manageEquipmentModal">
            Manage
          </button>
        <?php elseif ($isDispersed): ?>
          <p class="text-muted small fst-italic">
            <i class="bi bi-diagram-3 me-1"></i>
            Unit is dispersed — manage individual subunits.
          </p>
        <?php else: ?>
          <span class="text-muted small fst-italic">Unit on mission</span>
        <?php endif; ?>
      </div>

      <div class="card-body p-0">
        <h6 class="bg-secondary text-light px-3 py-1 mb-0">Directly Assigned</h6>
        <table class="table table-dark table-sm mb-3">
          <thead>
            <tr>
              <th>Chassis</th>
              <th>Variant</th>
              <th>Type</th>
              <th>Weight Class</th>
              <th>Status</th>
              <th>Damage</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($directEquipment as $e): ?>
              <tr>
                <td><a class="link-info" href="/equipment/<?= esc($e['equipment_id']) ?>"><?= esc($e['chassis_name']) ?></a></td>
                <td><?= esc($e['chassis_variant']) ?></td>
                <td><?= esc($e['chassis_type']) ?></td>
                <td><?= esc($e['weight_class']) ?></td>
                <td><?= esc($e['equipment_status']) ?></td>
                <td><?= esc($e['damage_percentage']) ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <button class="btn btn-outline-secondary btn-sm ms-3 mb-2" type="button"
          data-bs-toggle="collapse" data-bs-target="#equipmentRollup"
          aria-expanded="false" aria-controls="equipmentRollup">
          <i class="bi bi-chevron-down"></i> Show All Including Sub-Units
        </button>

        <div class="collapse" id="equipmentRollup">
          <h6 class="bg-secondary text-light px-3 py-1 mb-0">Including Sub-Units</h6>
          <table class="table table-dark table-sm mb-0">
            <thead>
              <tr>
                <th>Chassis</th>
                <th>Variant</th>
                <th>Type</th>
                <th>Unit</th>
                <th>Status</th>
                <th>Damage</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($equipment as $e): ?>
                <tr>
                  <td><a class="link-info" href="/equipment/<?= esc($e['equipment_id']) ?>"><?= esc($e['chassis_name']) ?></a></td>
                  <td><?= esc($e['chassis_variant']) ?></td>
                  <td><?= esc($e['chassis_type']) ?></td>
                  <td><?= esc($e['unit_name'] ?? '-') ?></td>
                  <td><?= esc($e['equipment_status']) ?></td>
                  <td><?= esc($e['damage_percentage']) ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
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
                  <?= !$p['can_assign'] ? 'disabled class="text-muted"' : '' ?>
                  data-name="<?= esc($p['first_name'] . ' ' . $p['last_name']) ?>"
                  data-mos="<?= esc($p['mos']) ?>"
                  data-status="<?= esc($p['status']) ?>"
                  data-dob="<?= esc($p['date_of_birth']) ?>"
                  data-experience="<?= esc($p['experience']) ?>"
                  data-morale="<?= esc($p['morale']) ?>">
                  <?= esc($p['last_name'] . ', ' . $p['first_name'] . ' (' . $p['rank_abbr'] . ')') ?>
                  <?= !$p['can_assign'] ? ' — ' . esc($p['location_name'] ?? 'Unknown Location') : '' ?>
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
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-secondary shadow-lg">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="manageEquipmentLabel">
          <i class="bi bi-gear-wide-connected me-2"></i>Manage Unit Equipment
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row">
          <!-- Unassigned Equipment -->
          <div class="col-md-5">
            <h6 class="text-info mb-2">Available Equipment</h6>
            <select multiple id="availableEquipment" class="form-select bg-black text-light border-secondary" size="15">
              <?php foreach ($availableEquipment as $e): ?>
                <option value="<?= $e['equipment_id'] ?>"
                  data-chassis="<?= esc($e['chassis_name']) ?>"
                  data-variant="<?= esc($e['chassis_variant'] ?? 'Unknown') ?>"
                  data-type="<?= esc($e['chassis_type'] ?? 'Unknown') ?>"
                  data-weight="<?= esc($e['weight_class'] ?? 'Unknown') ?>"
                  data-status="<?= esc($e['equipment_status'] ?? 'Unknown') ?>">
                  <?= esc($e['chassis_name'] . ' ' . $e['chassis_variant'] . ' (' . $e['weight_class'] . ')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Control Buttons -->
          <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
            <button id="assignEquipmentBtn" class="btn btn-outline-success btn-sm my-2" title="Assign Selected">
              <i class="bi bi-arrow-right-circle-fill fs-4"></i>
            </button>
            <button id="unassignEquipmentBtn" class="btn btn-outline-danger btn-sm my-2" title="Unassign Selected">
              <i class="bi bi-arrow-left-circle-fill fs-4"></i>
            </button>
          </div>

          <!-- Assigned Equipment -->
          <div class="col-md-5">
            <h6 class="text-warning mb-2">Assigned Equipment</h6>
            <select multiple id="assignedEquipment" class="form-select bg-black text-light border-secondary" size="15">
              <?php foreach ($directEquipment as $e): ?>
                <option value="<?= $e['equipment_id'] ?>"
                  data-chassis="<?= esc($e['chassis_name']) ?>"
                  data-variant="<?= esc($e['chassis_variant'] ?? 'Unknown') ?>"
                  data-type="<?= esc($e['chassis_type'] ?? 'Unknown') ?>"
                  data-weight="<?= esc($e['weight_class'] ?? 'Unknown') ?>"
                  data-status="<?= esc($e['equipment_status'] ?? 'Unknown') ?>">
                  <?= esc($e['chassis_name'] . ' ' . $e['chassis_variant'] . ' (' . $e['weight_class'] . ')') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Equipment Details Panel -->
        <div class="mt-4">
          <h6 class="text-center text-info">Selected Equipment Details</h6>
          <div id="equipmentDetails" class="p-3 bg-black border border-secondary rounded">
            <p class="text-muted mb-0">Select an equipment item to view its details and crew.</p>
          </div>
        </div>
      </div>

      <div class="modal-footer justify-content-between border-secondary">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" id="saveEquipmentChanges" class="btn border-secondary text-light">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Assign Commander Modal -->
<div class="modal fade" id="assignCommanderModal" tabindex="-1" aria-labelledby="assignCommanderLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-secondary shadow-lg">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="assignCommanderLabel">
          <i class="bi bi-person-badge-fill me-2"></i>Assign or Dismiss Commander
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p>Select a personnel member from this unit or its subunits to assign as commander.</p>

        <select id="commanderSelect" class="form-select bg-black text-light border-secondary" size="10">
          <?php foreach ($personnel as $p): ?>
            <option value="<?= $p['personnel_id'] ?>"
              <?= $unit['commander_id'] == $p['personnel_id'] ? 'selected' : '' ?>>
              <?= esc($p['last_name'] . ', ' . $p['first_name'] . ' — ' . $p['rank_full'] . ' (' . $p['unit_name'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-outline-danger" id="dismissCommanderBtn">Dismiss</button>
        <button type="button" class="btn btn-outline-success" id="assignCommanderBtn">Assign</button>
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

  // Equipment details panel
  const eqDetails = document.getElementById('equipmentDetails');

  // Helper to populate details
  const showEquipmentDetails = async (option) => {
    const eqId = option.value;
    const chassis = option.dataset.chassis;
    const variant = option.dataset.variant;
    const type = option.dataset.type;
    const weight = option.dataset.weight;
    const status = option.dataset.status;

    // Fetch crew data for this equipment
    const response = await fetch(`/equipment/getCrew/${eqId}`);
    let crewList = [];
    if (response.ok) crewList = await response.json();

    // Build HTML
    eqDetails.innerHTML = `
    <div class="row text-center">
      <div class="col-md-3"><strong>Chassis:</strong><br>${chassis}</div>
      <div class="col-md-3"><strong>Variant:</strong><br>${variant}</div>
      <div class="col-md-3"><strong>Type:</strong><br>${type}</div>
      <div class="col-md-3"><strong>Weight:</strong><br>${weight}</div>
    </div>
    <div class="row text-center mt-2">
      <div class="col-md-6"><strong>Status:</strong><br>${status}</div>
      <div class="col-md-6"><strong>Crew Count:</strong><br>${crewList.length}</div>
    </div>
    <hr>
    <h6 class="text-info text-center">Crew Members</h6>
    ${
      crewList.length > 0
        ? `<ul class="list-unstyled mb-0">
            ${crewList.map(c => `
              <li>
              ${c.rank_abbr}. ${c.last_name}, ${c.first_name} — <span class="text-muted">${c.mos}</span>
              ${c.role ? `(${c.role})` : ''}
              </li>
            `).join('')}
          </ul>`
        : `<p class="text-muted text-center mb-0">No crew assigned.</p>`
    }
  `;
  };

  // Listen for equipment selection
  ['availableEquipment', 'assignedEquipment'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', (e) => {
      const selected = e.target.selectedOptions[0];
      if (selected) showEquipmentDetails(selected);
    });
  });

  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const icon = btn.querySelector('i');
      if (icon) icon.classList.toggle('bi-chevron-up');
      if (icon) icon.classList.toggle('bi-chevron-down');
    });
  });

  // Commander Assign / Dismiss
  document.getElementById("assignCommanderBtn")?.addEventListener("click", () => {
    const commanderId = document.getElementById("commanderSelect").value;
    if (!commanderId) return;

    fetch(`/units/assignCommander/<?= $unit['unit_id'] ?>`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        personnel_id: commanderId
      })
    }).then(() => location.reload());
  });

  document.getElementById("dismissCommanderBtn")?.addEventListener("click", () => {
    fetch(`/units/dismissCommander/<?= $unit['unit_id'] ?>`, {
        method: "POST"
      })
      .then(() => location.reload());
  });

  document.getElementById('assignCommanderModal')?.addEventListener('shown.bs.modal', () => {
    const select = document.getElementById('commanderSelect');
    const selected = select.selectedOptions[0];
    if (selected) {
      selected.scrollIntoView({
        block: 'center'
      });
    }
  });

  function editNameToggle(editing) {
    document.getElementById('unitNameDisplay').classList.toggle('d-none', editing);
    document.getElementById('unitNameEdit').classList.toggle('d-none', !editing);
  }

  function saveName() {
    const name = document.getElementById('editName').value.trim();
    const nickname = document.getElementById('editNickname').value.trim();

    if (!name) {
      alert('Name is required.');
      return;
    }

    fetch(`/units/<?= $unit['unit_id'] ?>/updateName`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          name,
          nickname
        })
      })
      .then(r => r.json())
      .then(d => {
        if (!d.success) {
          alert(d.message ?? 'Error saving.');
          return;
        }
        document.getElementById('unitNameText').textContent = d.name;
        const badge = document.getElementById('unitNicknameText');
        if (d.nickname) {
          if (badge) badge.textContent = d.nickname;
          else {
            const newBadge = document.createElement('span');
            newBadge.id = 'unitNicknameText';
            newBadge.className = 'badge bg-info-subtle text-info-emphasis';
            newBadge.textContent = d.nickname;
            document.getElementById('unitNameDisplay').insertBefore(
              newBadge,
              document.getElementById('unitNameDisplay').children[1]
            );
          }
        } else if (badge) badge.remove();
        editNameToggle(false);
      });
  }

  function deactivateUnit(unitId) {
    if (!confirm(
        'Deactivate this unit? All personnel and equipment will be unassigned, ' +
        'and subunits will be moved to the parent unit. This cannot be undone easily.'
      )) return;

    fetch(`/units/${unitId}/deactivate`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
      })
      .then(r => r.json())
      .then(d => {
        if (!d.success) {
          alert(d.message ?? 'Error deactivating unit.');
          return;
        }
        // Navigate to parent or units index
        window.location.href = d.parent_id ? `/units/${d.parent_id}` : '/units';
      });
  }

  function addSubunit(unitId) {
    const select = document.getElementById('addSubunitSelect');
    const subunitId = select.value;
    if (!subunitId) return;

    fetch(`/units/${unitId}/subunit/add`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          subunit_id: subunitId
        })
      })
      .then(r => r.json())
      .then(d => {
        if (!d.success) {
          alert(d.message);
          return;
        }

        // Remove from dropdown
        const opt = select.options[select.selectedIndex];
        const name = opt.text;
        const type = opt.closest('optgroup')?.label ?? '';
        opt.remove();

        // Remove empty placeholder
        document.getElementById('subunitEmpty')?.remove();

        // Add row to table
        const tbody = document.getElementById('subunitTableBody');
        const tr = document.createElement('tr');
        tr.id = `subunit-${subunitId}`;
        tr.innerHTML = `
            <td><a class="link-info" href="/units/${subunitId}">${name}</a></td>
            <td class="small">${type}</td>
            <td class="small">—</td>
            <td class="small">—</td>
            <td><span class="text-danger">0.0%</span></td>
            <td><span class="text-danger">0.0%</span></td>
            <td>
                <button class="btn btn-xs btn-outline-danger"
                    onclick="removeSubunit(${unitId}, ${subunitId}, this)">
                    Remove
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        select.value = '';
      });
  }

  function removeSubunit(unitId, subunitId, btn) {
    if (!confirm('Remove this subunit from the unit?')) return;

    fetch(`/units/${unitId}/subunit/${subunitId}/remove`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        }
      })
      .then(r => r.json())
      .then(d => {
        if (!d.success) {
          alert(d.message);
          return;
        }

        // Remove row from table
        document.getElementById(`subunit-${subunitId}`)?.remove();

        // Add back to dropdown if it exists
        const select = document.getElementById('addSubunitSelect');
        if (select) {
          const name = btn.closest('tr').querySelector('a').textContent.trim();
          const type = btn.closest('tr').querySelector('td:nth-child(2)').textContent.trim();

          // Find or create optgroup
          let group = Array.from(select.querySelectorAll('optgroup'))
            .find(g => g.label === type);
          if (!group) {
            group = document.createElement('optgroup');
            group.label = type;
            select.appendChild(group);
          }

          const opt = document.createElement('option');
          opt.value = subunitId;
          opt.textContent = name;
          group.appendChild(opt);
        }
      });
  }
</script>