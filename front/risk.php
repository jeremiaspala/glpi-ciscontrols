<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/risk.php - Risk registry list + visual matrix
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

global $DB, $CFG_GLPI;

$canWrite = Session::haveRight('plugin_ciscontrols', UPDATE);
$formUrl  = $CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.form.php';

// Load all risks
$risks = [];
foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_risks', 'ORDER' => 'risk_score DESC, name ASC']) as $r) {
    $risks[$r['id']] = $r;
}

// Count linked controls and docs per risk
$riskControlCount = [];
foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_risk_controls']) as $rc) {
    $riskControlCount[$rc['plugin_ciscontrols_risks_id']] = ($riskControlCount[$rc['plugin_ciscontrols_risks_id']] ?? 0) + 1;
}
$riskDocCount = [];
foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_risk_documents']) as $rd) {
    $riskDocCount[$rd['plugin_ciscontrols_risks_id']] = ($riskDocCount[$rd['plugin_ciscontrols_risks_id']] ?? 0) + 1;
}

$likelihoodLabels = PluginCiscontrolsRisk::likelihoodLabels();
$impactLabels     = PluginCiscontrolsRisk::impactLabels();
$treatmentLabels  = PluginCiscontrolsRisk::treatmentOptions();
$statusLabels     = PluginCiscontrolsRisk::statusOptions();
$categoryLabels   = PluginCiscontrolsRisk::categories();

// Status badge classes
$statusBadge = [
    'abierto'        => 'bg-danger',
    'en_tratamiento' => 'bg-warning text-dark',
    'residual'       => 'bg-info text-dark',
    'cerrado'        => 'bg-success',
];

// Summary counts by level
$levelCounts = ['critico' => 0, 'alto' => 0, 'medio' => 0, 'bajo' => 0];
foreach ($risks as $r) {
    $levelCounts[PluginCiscontrolsRisk::level((int)$r['risk_score'])]++;
}

Html::header('CIS Controls - Matriz de Riesgos', $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');
?>
<div class="container-fluid mt-3">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="ti ti-alert-triangle me-2"></i>Matriz de Riesgo de Ciberseguridad</h2>
    <?php if ($canWrite): ?>
    <a href="<?= $formUrl ?>" class="btn btn-danger">
      <i class="ti ti-plus me-1"></i>Nuevo Riesgo
    </a>
    <?php endif; ?>
  </div>

  <!-- Summary chips -->
  <div class="d-flex flex-wrap gap-2 mb-4">
    <span class="badge bg-danger fs-6 px-3 py-2"><i class="ti ti-flame me-1"></i>Crítico: <?= $levelCounts['critico'] ?></span>
    <span class="badge fs-6 px-3 py-2" style="background:#fd7e14;"><i class="ti ti-alert-triangle me-1"></i>Alto: <?= $levelCounts['alto'] ?></span>
    <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="ti ti-minus me-1"></i>Medio: <?= $levelCounts['medio'] ?></span>
    <span class="badge bg-success fs-6 px-3 py-2"><i class="ti ti-check me-1"></i>Bajo: <?= $levelCounts['bajo'] ?></span>
    <span class="badge bg-secondary fs-6 px-3 py-2">Total: <?= count($risks) ?></span>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3" id="riskTabs">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMatrix">
        <i class="ti ti-grid-dots me-1"></i>Matriz Visual
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabList">
        <i class="ti ti-list me-1"></i>Listado
      </button>
    </li>
  </ul>

  <div class="tab-content">

    <!-- ===== TAB: MATRIX ===== -->
    <div class="tab-pane fade show active" id="tabMatrix">
      <?php if (empty($risks)): ?>
      <div class="alert alert-info">No hay riesgos registrados aún. <a href="<?= $formUrl ?>">Crear el primero</a>.</div>
      <?php else: ?>

      <!-- Index risks by (likelihood, impact) -->
      <?php
      $matrix = [];
      foreach ($risks as $r) {
          $key = $r['likelihood'] . '_' . $r['impact'];
          $matrix[$key][] = $r;
      }
      ?>

      <div class="card shadow-sm mb-4">
        <div class="card-body p-2">
          <div class="d-flex align-items-center mb-2">
            <div style="writing-mode:vertical-rl;transform:rotate(180deg);font-weight:bold;padding:0 8px;white-space:nowrap;">
              Probabilidad →
            </div>
            <div style="flex:1;overflow-x:auto;">
              <table style="width:100%;border-collapse:separate;border-spacing:4px;">
                <thead>
                  <tr>
                    <th style="width:120px;"></th>
                    <?php for ($imp = 1; $imp <= 5; $imp++): ?>
                    <th class="text-center" style="font-size:0.8rem;padding:4px;">
                      <?= $imp ?><br><small class="text-muted fw-normal"><?= $impactLabels[$imp] ?></small>
                    </th>
                    <?php endfor; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php for ($lik = 5; $lik >= 1; $lik--): ?>
                  <tr>
                    <td class="text-end pe-2" style="font-size:0.8rem;white-space:nowrap;">
                      <strong><?= $lik ?></strong> <small class="text-muted"><?= $likelihoodLabels[$lik] ?></small>
                    </td>
                    <?php for ($imp = 1; $imp <= 5; $imp++):
                      $score   = $lik * $imp;
                      $hex     = PluginCiscontrolsRisk::levelHex($score);
                      $label   = PluginCiscontrolsRisk::levelLabel($score);
                      $key     = $lik . '_' . $imp;
                      $cellRisks = $matrix[$key] ?? [];
                      $textColor = $score <= 4 ? '#000' : '#fff';
                    ?>
                    <td style="background:<?= $hex ?>;color:<?= $textColor ?>;border-radius:6px;padding:6px;min-width:100px;min-height:80px;vertical-align:top;cursor:default;"
                        title="Score: <?= $score ?> — <?= $label ?>">
                      <div class="text-center" style="font-size:0.7rem;opacity:0.7;margin-bottom:3px;"><?= $score ?></div>
                      <?php foreach ($cellRisks as $cr): ?>
                      <div style="background:rgba(0,0,0,0.2);border-radius:4px;padding:2px 4px;margin-bottom:2px;font-size:0.7rem;line-height:1.2;">
                        <?php if ($canWrite): ?>
                        <a href="<?= $formUrl ?>?id=<?= $cr['id'] ?>" style="color:<?= $textColor ?>;text-decoration:none;">
                          <?= htmlspecialchars(mb_strimwidth($cr['name'], 0, 28, '…')) ?>
                        </a>
                        <?php else: ?>
                        <?= htmlspecialchars(mb_strimwidth($cr['name'], 0, 28, '…')) ?>
                        <?php endif; ?>
                      </div>
                      <?php endforeach; ?>
                    </td>
                    <?php endfor; ?>
                  </tr>
                  <?php endfor; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="text-center text-muted small">← Impacto →</div>
          <!-- Legend -->
          <div class="d-flex gap-3 justify-content-center mt-2 flex-wrap">
            <span><span style="display:inline-block;width:14px;height:14px;background:#198754;border-radius:3px;"></span> Bajo (1–4)</span>
            <span><span style="display:inline-block;width:14px;height:14px;background:#ffc107;border-radius:3px;"></span> Medio (5–9)</span>
            <span><span style="display:inline-block;width:14px;height:14px;background:#fd7e14;border-radius:3px;"></span> Alto (10–14)</span>
            <span><span style="display:inline-block;width:14px;height:14px;background:#dc3545;border-radius:3px;"></span> Crítico (15–25)</span>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div><!-- /tab matrix -->

    <!-- ===== TAB: LIST ===== -->
    <div class="tab-pane fade" id="tabList">
      <?php if (empty($risks)): ?>
      <div class="alert alert-info">No hay riesgos registrados aún.</div>
      <?php else: ?>

      <!-- Filters -->
      <div class="card mb-3 shadow-sm">
        <div class="card-body py-2">
          <div class="row g-2">
            <div class="col-md-4">
              <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Buscar por nombre, activo, amenaza…" oninput="filterRisks()">
            </div>
            <div class="col-md-3">
              <select id="filterLevel" class="form-select form-select-sm" onchange="filterRisks()">
                <option value="">Todos los niveles</option>
                <option value="critico">Crítico</option>
                <option value="alto">Alto</option>
                <option value="medio">Medio</option>
                <option value="bajo">Bajo</option>
              </select>
            </div>
            <div class="col-md-3">
              <select id="filterStatus" class="form-select form-select-sm" onchange="filterRisks()">
                <option value="">Todos los estados</option>
                <?php foreach ($statusLabels as $val => $lbl): ?>
                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($lbl) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle" id="riskTable">
          <thead class="table-dark">
            <tr>
              <th>Riesgo</th>
              <th>Categoría</th>
              <th style="width:90px;" class="text-center">P × I</th>
              <th style="width:90px;" class="text-center">Nivel</th>
              <th style="width:100px;">Tratamiento</th>
              <th style="width:110px;">Estado</th>
              <th style="width:80px;" class="text-center">Controles</th>
              <th style="width:60px;" class="text-center">Docs</th>
              <?php if ($canWrite): ?><th style="width:80px;">Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($risks as $r):
              $score      = (int)$r['risk_score'];
              $level      = PluginCiscontrolsRisk::level($score);
              $levelLabel = PluginCiscontrolsRisk::levelLabel($score);
              $hex        = PluginCiscontrolsRisk::levelHex($score);
              $ctrlCount  = $riskControlCount[$r['id']] ?? 0;
              $docCount   = $riskDocCount[$r['id']]     ?? 0;
              $sbadge     = $statusBadge[$r['status']] ?? 'bg-secondary';
            ?>
            <tr data-level="<?= $level ?>" data-status="<?= htmlspecialchars($r['status']) ?>"
                data-search="<?= strtolower(htmlspecialchars($r['name'] . ' ' . ($r['asset'] ?? '') . ' ' . ($r['threat'] ?? ''))) ?>">
              <td>
                <strong><?= htmlspecialchars($r['name']) ?></strong>
                <?php if ($r['owner']): ?>
                <br><small class="text-muted"><i class="ti ti-user me-1"></i><?= htmlspecialchars($r['owner']) ?></small>
                <?php endif; ?>
              </td>
              <td><small><?= htmlspecialchars($categoryLabels[$r['category']] ?? $r['category']) ?></small></td>
              <td class="text-center">
                <span class="fw-bold" style="color:<?= $hex ?>;"><?= $score ?></span>
                <br><small class="text-muted"><?= $r['likelihood'] ?>×<?= $r['impact'] ?></small>
              </td>
              <td class="text-center">
                <span class="badge" style="background:<?= $hex ?>;"><?= $levelLabel ?></span>
              </td>
              <td><small><?= htmlspecialchars($treatmentLabels[$r['treatment']] ?? $r['treatment']) ?></small></td>
              <td><span class="badge <?= $sbadge ?>"><?= htmlspecialchars($statusLabels[$r['status']] ?? $r['status']) ?></span></td>
              <td class="text-center">
                <span class="badge <?= $ctrlCount > 0 ? 'bg-success' : 'bg-light text-dark border' ?>"><?= $ctrlCount ?></span>
              </td>
              <td class="text-center">
                <span class="badge <?= $docCount > 0 ? 'bg-info text-dark' : 'bg-light text-dark border' ?>"><?= $docCount ?></span>
              </td>
              <?php if ($canWrite): ?>
              <td>
                <a href="<?= $formUrl ?>?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                  <i class="ti ti-edit"></i>
                </a>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div id="noRisks" class="alert alert-info d-none">Sin resultados para los filtros aplicados.</div>
      <?php endif; ?>
    </div><!-- /tab list -->

  </div><!-- tab-content -->
</div>

<script>
function filterRisks() {
    var search  = document.getElementById('filterSearch').value.toLowerCase();
    var level   = document.getElementById('filterLevel').value;
    var status  = document.getElementById('filterStatus').value;
    var rows    = document.querySelectorAll('#riskTable tbody tr');
    var visible = 0;
    rows.forEach(function(row) {
        var ok = (!search || row.dataset.search.indexOf(search) !== -1)
              && (!level  || row.dataset.level  === level)
              && (!status || row.dataset.status === status);
        row.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.getElementById('noRisks').classList.toggle('d-none', visible > 0);
}
</script>

<?php Html::footer(); ?>
