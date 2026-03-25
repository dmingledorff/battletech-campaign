<div class="card shadow mb-3">
  <div class="card-header">Personnel Details</div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <p><strong>Name:</strong> <?= esc($person['last_name'].', '.$person['first_name']) ?></p>
        <p><strong>Rank:</strong> <?= esc($person['rank_full']) ?></p>
        <p><strong>Gender:</strong> <?= esc($person['gender']) ?></p>
        <p><strong>Callsign:</strong> <?= esc($person['callsign']) ?></p>
        <?php $dob = new \DateTime($person['date_of_birth']); $dobFormatted = $dob->format('j F Y') ?>
        <p><strong>Date of Birth:</strong> <?= esc($dobFormatted) ?></p>
        <p><strong>Age:</strong> <?= esc($age) ?> years</p>
      </div>
      <div class="col-md-6">
        <p><strong>MOS:</strong> <?= esc($person['mos']) ?></p>
        <p><strong>Experience:</strong> <?= esc($person['experience']) ?></p>
        <p><strong>Missions:</strong> <?= esc($person['missions']) ?></p>
        <?php
          $morale = $person['morale'];
          $color  = $morale >= 70 ? 'green' : ($morale >= 40 ? 'yellow' : 'red');
        ?>
        <p><strong>Morale:</strong> <span style="color: <?= $color ?>"><?= number_format($morale, 2) ?>%</span></p>
        <p><strong>Status:</strong> <?= esc($person['status']) ?></p>
        <p><strong>Location:</strong>
          <?php if (!empty($person['location_id'])): ?>
            <a class="link-info" href="/location/<?= esc($person['location_id']) ?>">
              <?= esc($person['location_name']) ?>
              <?php if (!empty($person['planet_name'])): ?>
                <span class="text-muted">(<?= esc($person['planet_name']) ?>)</span>
              <?php endif; ?>
            </a>
          <?php else: ?>
            <span class="text-muted">Unknown</span>
          <?php endif; ?>
        </p>
        <p><strong>Unit:</strong>
          <?php if ($unitChain): ?>
            <a class="link-info" href="/units/<?= esc($currentAssignment['unit_id']) ?>">
              <?= esc($unitChain) ?>
            </a>
          <?php else: ?>
            <span class="text-muted">Unassigned</span>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Assignment History</div>
      <div class="card-body p-0">
        <?php if (!empty($assignmentHistory)): ?>
          <table class="table table-dark table-sm mb-0">
            <thead>
              <tr>
                <th>Unit</th>
                <th>Assigned</th>
                <th>Released</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assignmentHistory as $a): ?>
                <tr>
                  <td>
                    <a class="link-info" href="/units/<?= esc($a['unit_id']) ?>">
                      <?= esc($a['unit_chain']) ?>
                    </a>
                  </td>
                  <td><?= esc($a['date_assigned']) ?></td>
                  <td>
                    <?php if ($a['date_released']): ?>
                      <?= esc($a['date_released']) ?>
                    <?php else: ?>
                      <span class="badge bg-success">Active</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted p-3 mb-0">No assignment history.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow mb-3">
      <div class="card-header">Equipment Assignments</div>
      <div class="card-body p-0">
        <?php if (!empty($equipment)): ?>
          <table class="table table-dark table-sm mb-0">
            <thead>
              <tr>
                <th>Chassis</th><th>Variant</th><th>Role</th>
                <th>Weight</th><th>Status</th><th>Damage</th>
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
                  <td><?= esc($e['chassis_variant']) ?></td>
                  <td><?= esc($e['role']) ?></td>
                  <td><?= esc($e['weight_class']) ?></td>
                  <td><?= esc($e['equipment_status']) ?></td>
                  <td><?= esc($e['damage_percentage']) ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted p-3 mb-0">No equipment assignments.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>