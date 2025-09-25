<div class="row mb-3">
  <div class="col">
    <div class="card bg-secondary-subtle text-light">
      <div class="card-body d-flex gap-4">
        <div><span class="fs-4 fw-bold"><?= esc(number_format($totals['units'])) ?></span><div class="text-secondary">Units</div></div>
        <div><span class="fs-4 fw-bold"><?= esc(number_format($totals['personnel'])) ?></span><div class="text-secondary">Personnel</div></div>
        <div><span class="fs-4 fw-bold"><?= esc(number_format($totals['equipment'])) ?></span><div class="text-secondary">Equipment</div></div>
        <div><span class="fs-4 fw-bold"><?= esc(number_format($totals['supply_req'],2)) ?></span><div class="text-secondary">Total Required Supply</div></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    
    <div class="card shadow-sm">
      <div class="card-header">Unit Summary</div>
      <div class="card-body p-0">
        <table class="table table-dark table-sm mb-0">
          <thead>
            <tr>
              <th>Unit</th>
              <th>Type</th>
              <th>Pers</th>
              <th>Eqp</th>
              <th>Supply (Current / Required)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Renders regiment (parent_unit_id = NULL) first, then all descendants with subtle indentation.
            function renderSummaryTable($children, $summary, $parentId = null, $depth = 0) {
              if (!isset($children[$parentId])) return;

              foreach ($children[$parentId] as $u) {
                $row = $summary[$u['unit_id']] ?? null;

                // Subtle indentation: 8px per level so the tree stays compact
                $pad = 8 * $depth;

                // Safely choose rolled totals if present; fall back to direct counts
                $pers = $row['rolled_personnel'] ?? $row['personnel_count'] ?? 0;
                $eqp  = $row['rolled_equipment'] ?? $row['equipment_count'] ?? 0;
                $req  = $row['rolled_supply'] ?? $row['required_supply'] ?? 0.0;
                $curr = $row['current_supply'] ?? 0.0;

                echo '<tr>';
                echo '<td style="padding-left:'.$pad.'px">';
                echo '<a class="link-info" href="/units/'.esc($u['unit_id']).'">'.esc($u['name']).'</a>';
                if (!empty($u['nickname'])) {
                  echo ' <span class="badge bg-info-subtle text-info-emphasis">'.esc($u['nickname']).'</span>';
                }
                echo '</td>';
                echo '<td>'.esc($u['unit_type']).'</td>';
                echo '<td>'.esc($pers).'</td>';
                echo '<td>'.esc($eqp).'</td>';
                echo '<td>'.esc(number_format($curr,2)).' / '.esc(number_format($req,2)).'</td>';
                echo '</tr>';

                // Recurse into children
                renderSummaryTable($children, $summary, $u['unit_id'], $depth + 1);
              }
            }

            // ✅ Start from top-level (NULL parent) so the regiment appears first
            renderSummaryTable($children, $summary, null, 0);
            ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <div class="col-md-6">

    <div class="card shadow-sm">
      <div class="card-header">Hierarchy</div>
      <div class="card-body">
        <?php
          function renderTree($children, $parentId = null, $depth = 0) {
              if (!isset($children[$parentId])) return;

              // Use Bootstrap ms-* classes (max 5)
              $ms = min($depth, 5);

              echo '<ul class="list-unstyled ms-'.$ms.'">';
              foreach ($children[$parentId] as $u) {
                  echo '<li class="mb-1">';

                  // Regiment (root) styling
                  if ($u['parent_unit_id'] === null) {
                      echo '<strong><a class="text-decoration-none text-light" href="/units/'.esc($u['unit_id']).'">'.esc($u['name']).'</a></strong>';
                      if (!empty($u['nickname'])) {
                          echo ' <span class="badge bg-primary">'.esc($u['nickname']).'</span>';
                      }
                  } else {
                      // Normal styling for other units
                      echo '<a class="text-decoration-none" href="/units/'.esc($u['unit_id']).'">'.esc($u['name']).'</a>';
                      if (!empty($u['nickname'])) {
                          echo ' <span class="badge bg-info-subtle text-info-emphasis">'.esc($u['nickname']).'</span>';
                      }
                  }

                  // Recurse into children
                  renderTree($children, $u['unit_id'], $depth + 1);
                  echo '</li>';
              }
              echo '</ul>';
          }

          // Start from top-level (parent_unit_id is NULL) so the regiment shows as the root
          renderTree($children, null, 0);
        ?>
      </div>
    </div>

  </div>
</div>
