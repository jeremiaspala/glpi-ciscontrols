<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/dashboard.php - Dashboard with compliance, CIS coverage and documentation metrics
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

global $DB, $CFG_GLPI;

// --- Config ---
$configData = [];
foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_config']) as $cr) {
    $configData[$cr['config_key']] = $cr['config_value'];
}
$igLevel     = (int)($configData['ig_level'] ?? 1);
$companyName = $configData['company_name'] ?? '';

// --- Execution metrics (365 días) ---
$today      = date('Y-m-d');
$year365ago = date('Y-m-d', strtotime('-365 days'));
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');
$next30     = date('Y-m-d', strtotime('+30 days'));

$completedTotal = 0;
$pendingTotal   = 0;
$overdueTotal   = 0;
$completedMonth = 0;

foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_executions', 'WHERE' => [['due_date' => ['>=', $year365ago]]]]) as $e) {
    if ($e['status'] === 'completed') {
        $completedTotal++;
        if ($e['due_date'] >= $monthStart && $e['due_date'] <= $monthEnd) $completedMonth++;
    } elseif ($e['status'] === 'overdue') {
        $overdueTotal++;
    } elseif ($e['status'] === 'pending') {
        $pendingTotal++;
    }
}
$execTotal      = $completedTotal + $pendingTotal + $overdueTotal;
$compliancePct  = $execTotal > 0 ? round($completedTotal / $execTotal * 100, 1) : 0;

// Controls map
$controlsMap = [];
foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls']) as $c) {
    $controlsMap[$c['id']] = $c;
}

// Progress by periodicity
$periodicityStats = [];
foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_executions', 'WHERE' => [['due_date' => ['>=', $year365ago]]]]) as $e) {
    $ctrl = $controlsMap[$e['plugin_ciscontrols_controls_id']] ?? null;
    if (!$ctrl) continue;
    $p = $ctrl['periodicity'];
    if (!isset($periodicityStats[$p])) $periodicityStats[$p] = ['completed' => 0, 'total' => 0];
    $periodicityStats[$p]['total']++;
    if ($e['status'] === 'completed') $periodicityStats[$p]['completed']++;
}

// --- CIS Coverage ---
// Safeguards by IG level (cumulative)
$safeguardsByIg = [1 => [], 2 => [], 3 => []];  // [ig_level] => [id => row]
foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_cislib']) as $lr) {
    $safeguardsByIg[$lr['ig_level']][$lr['id']] = $lr;
}

// Build cumulative sets: safeguards applicable to each IG organization
$safeForOrg = [];
for ($ig = 1; $ig <= 3; $ig++) {
    $safeForOrg[$ig] = [];
    for ($l = 1; $l <= $ig; $l++) {
        $safeForOrg[$ig] += $safeguardsByIg[$l];
    }
}
$targetSafeguards = $safeForOrg[$igLevel];
$cislibTotal      = count($targetSafeguards);

// IDs covered by at least one active control
$coveredByControl = [];
foreach ($DB->request([
    'SELECT' => ['l.plugin_ciscontrols_cislib_id as cid'],
    'FROM'   => 'glpi_plugin_ciscontrols_control_cislib AS l',
    'JOIN'   => ['glpi_plugin_ciscontrols_controls AS c' => ['ON' => ['c' => 'id', 'l' => 'plugin_ciscontrols_controls_id']]],
    'WHERE'  => ['c.is_active' => 1],
]) as $lnk) {
    $coveredByControl[$lnk['cid']] = true;
}

// IDs covered by documentation (direct link on cislib)
$coveredByDocDirect = [];
foreach ($DB->request(['SELECT' => ['plugin_ciscontrols_cislib_id as cid'], 'FROM' => 'glpi_plugin_ciscontrols_cislib_documents']) as $dl) {
    $coveredByDocDirect[$dl['cid']] = true;
}

// IDs covered by documentation via a linked control (control has a doc)
$controlsWithDoc = [];
foreach ($DB->request(['SELECT' => ['plugin_ciscontrols_controls_id as ctid'], 'FROM' => 'glpi_plugin_ciscontrols_control_documents']) as $cd) {
    $controlsWithDoc[$cd['ctid']] = true;
}
// Which cislib IDs are covered via a control that has a doc?
$coveredByDocViaControl = [];
foreach ($DB->request([
    'SELECT' => ['l.plugin_ciscontrols_cislib_id as cid', 'l.plugin_ciscontrols_controls_id as ctid'],
    'FROM'   => 'glpi_plugin_ciscontrols_control_cislib AS l',
    'JOIN'   => ['glpi_plugin_ciscontrols_controls AS c' => ['ON' => ['c' => 'id', 'l' => 'plugin_ciscontrols_controls_id']]],
    'WHERE'  => ['c.is_active' => 1],
]) as $lnk) {
    if (isset($controlsWithDoc[$lnk['ctid']])) {
        $coveredByDocViaControl[$lnk['cid']] = true;
    }
}

// Compute metrics over target safeguards
$ctrlCount     = 0;  // safeguards with active control
$docCount      = 0;  // safeguards with any documentation
$fullCount     = 0;  // safeguards with both control AND documentation
$cislibGaps    = []; // no control, no doc

foreach ($targetSafeguards as $sid => $entry) {
    $hasCtrl = isset($coveredByControl[$sid]);
    $hasDoc  = isset($coveredByDocDirect[$sid]) || isset($coveredByDocViaControl[$sid]);
    if ($hasCtrl) $ctrlCount++;
    if ($hasDoc)  $docCount++;
    if ($hasCtrl && $hasDoc) $fullCount++;
    if (!$hasCtrl && !$hasDoc) $cislibGaps[] = $entry;
}

$ctrlPct  = $cislibTotal > 0 ? round($ctrlCount  / $cislibTotal * 100, 1) : 0;
$docPct   = $cislibTotal > 0 ? round($docCount   / $cislibTotal * 100, 1) : 0;
$fullPct  = $cislibTotal > 0 ? round($fullCount  / $cislibTotal * 100, 1) : 0;

// Per-IG-level breakdown for the target (each level within objective)
$igBreakdown = [];
for ($ig = 1; $ig <= $igLevel; $ig++) {
    $ids   = array_keys($safeguardsByIg[$ig]);
    $total = count($ids);
    $ctrl  = count(array_filter($ids, fn($id) => isset($coveredByControl[$id])));
    $doc   = count(array_filter($ids, fn($id) => isset($coveredByDocDirect[$id]) || isset($coveredByDocViaControl[$id])));
    $full  = count(array_filter($ids, fn($id) => isset($coveredByControl[$id]) && (isset($coveredByDocDirect[$id]) || isset($coveredByDocViaControl[$id]))));
    $igBreakdown[$ig] = compact('total', 'ctrl', 'doc', 'full');
}

// --- Documentation stats (counted in PHP to avoid QueryExpression complexity) ---
$totalDocs = 0;
foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_documents', 'WHERE' => ['is_folder' => 0]]) as $d) {
    $totalDocs++;
}

$docsLinkedControlSet = [];
foreach ($DB->request(['SELECT' => ['plugin_ciscontrols_documents_id as did'], 'FROM' => 'glpi_plugin_ciscontrols_control_documents']) as $r) {
    $docsLinkedControlSet[$r['did']] = true;
}
$docsLinkedControl = count($docsLinkedControlSet);

$docsLinkedCislibSet = [];
foreach ($DB->request(['SELECT' => ['plugin_ciscontrols_documents_id as did'], 'FROM' => 'glpi_plugin_ciscontrols_cislib_documents']) as $r) {
    $docsLinkedCislibSet[$r['did']] = true;
}
$docsLinkedCislib = count($docsLinkedCislibSet);

$controlsTotal        = count(array_filter($controlsMap, fn($c) => $c['is_active']));
$controlsWithDocCount = count(array_filter(array_keys($controlsMap), fn($id) => $controlsMap[$id]['is_active'] && isset($controlsWithDoc[$id])));

// --- Upcoming / Overdue ---
$upcoming = $DB->request([
    'FROM'  => 'glpi_plugin_ciscontrols_executions',
    'WHERE' => ['status' => 'pending', ['due_date' => ['>=', $today]], ['due_date' => ['<=', $next30]]],
    'ORDER' => 'due_date ASC',
]);
$overdueList = $DB->request([
    'FROM'  => 'glpi_plugin_ciscontrols_executions',
    'WHERE' => ['status' => 'overdue'],
    'ORDER' => 'due_date ASC',
]);

// --- Helpers ---
$igColors = [1 => 'success', 2 => 'warning', 3 => 'danger'];
$igColor  = $igColors[$igLevel] ?? 'primary';

function barColor(float $pct): string {
    return $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
}
function hexColor(float $pct): string {
    return $pct >= 80 ? '#198754' : ($pct >= 50 ? '#ffc107' : '#dc3545');
}

Html::header('CIS Controls - Dashboard', $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');
?>
<div class="container-fluid mt-3">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-0"><i class="ti ti-dashboard me-2"></i>Dashboard CIS Controls</h2>
      <?php if ($companyName): ?>
      <small class="text-muted"><?= htmlspecialchars($companyName) ?></small>
      <?php endif; ?>
    </div>
    <div>
      <span class="badge bg-<?= $igColor ?> fs-6">
        <i class="ti ti-target me-1"></i>Nivel objetivo: IG<?= $igLevel ?>
      </span>
      <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/config.php" class="btn btn-sm btn-outline-secondary ms-2">
        <i class="ti ti-settings me-1"></i>Configurar
      </a>
    </div>
  </div>

  <!-- ===== SUMMARY CARDS ===== -->
  <div class="row g-3 mb-4">

    <!-- Operativo -->
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center h-100" style="border-top:4px solid <?= hexColor($compliancePct) ?> !important;">
        <div class="card-body d-flex flex-column justify-content-center">
          <div style="font-size:2.8rem;font-weight:700;color:<?= hexColor($compliancePct) ?>;"><?= $compliancePct ?>%</div>
          <div class="fw-bold">Cumplimiento Operativo</div>
          <div class="text-muted small">Ejecuciones completadas (365 días)</div>
          <div class="mt-2 d-flex justify-content-center gap-2 small">
            <span class="badge bg-success"><?= $completedTotal ?> ok</span>
            <span class="badge bg-warning text-dark"><?= $pendingTotal ?> pend.</span>
            <span class="badge bg-danger"><?= $overdueTotal ?> venc.</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Cobertura por controles -->
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center h-100" style="border-top:4px solid <?= hexColor($ctrlPct) ?> !important;">
        <div class="card-body d-flex flex-column justify-content-center">
          <div style="font-size:2.8rem;font-weight:700;color:<?= hexColor($ctrlPct) ?>;"><?= $ctrlPct ?>%</div>
          <div class="fw-bold">Cobertura por Controles</div>
          <div class="text-muted small">Safeguards IG<?= $igLevel ?> con control activo</div>
          <div class="text-muted small mt-1"><?= $ctrlCount ?> / <?= $cislibTotal ?> safeguards</div>
        </div>
      </div>
    </div>

    <!-- Cobertura por documentación -->
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center h-100" style="border-top:4px solid <?= hexColor($docPct) ?> !important;">
        <div class="card-body d-flex flex-column justify-content-center">
          <div style="font-size:2.8rem;font-weight:700;color:<?= hexColor($docPct) ?>;"><?= $docPct ?>%</div>
          <div class="fw-bold">Cobertura por Documentación</div>
          <div class="text-muted small">Safeguards IG<?= $igLevel ?> con documentación</div>
          <div class="text-muted small mt-1"><?= $docCount ?> / <?= $cislibTotal ?> safeguards</div>
        </div>
      </div>
    </div>

    <!-- Score global -->
    <div class="col-md-3">
      <div class="card border-0 shadow-sm text-center h-100" style="border-top:4px solid <?= hexColor($fullPct) ?> !important;">
        <div class="card-body d-flex flex-column justify-content-center">
          <div style="font-size:2.8rem;font-weight:700;color:<?= hexColor($fullPct) ?>;"><?= $fullPct ?>%</div>
          <div class="fw-bold">Cumplimiento CIS Global</div>
          <div class="text-muted small">Safeguards con control <strong>y</strong> documentación</div>
          <div class="text-muted small mt-1"><?= $fullCount ?> / <?= $cislibTotal ?> safeguards</div>
        </div>
      </div>
    </div>

  </div><!-- /summary cards -->

  <!-- ===== CIS COVERAGE DESGLOSE ===== -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-<?= $igColor ?> text-white">
      <strong><i class="ti ti-shield-check me-1"></i>Cobertura CIS v8.1 — Desglose por nivel IG</strong>
      <small class="ms-2 opacity-75">Objetivo actual: IG<?= $igLevel ?></small>
    </div>
    <div class="card-body">

      <?php for ($ig = 1; $ig <= 3; $ig++):
        $bd      = $igBreakdown[$ig] ?? null;
        $isTarget = ($ig === $igLevel);
        $inScope  = ($ig <= $igLevel);
        $igc      = $igColors[$ig];
        if ($bd === null) {
          // IG level outside target — show total only
          $bd = ['total' => count($safeguardsByIg[$ig]), 'ctrl' => 0, 'doc' => 0, 'full' => 0];
        }
        $ctrlP = $bd['total'] > 0 ? round($bd['ctrl'] / $bd['total'] * 100) : 0;
        $docP  = $bd['total'] > 0 ? round($bd['doc']  / $bd['total'] * 100) : 0;
        $fullP = $bd['total'] > 0 ? round($bd['full'] / $bd['total'] * 100) : 0;
      ?>
      <div class="mb-4 <?= $isTarget ? 'p-3 rounded border border-'.$igc.' bg-'.$igc.' bg-opacity-10' : '' ?>">
        <div class="d-flex align-items-center mb-2">
          <span class="badge bg-<?= $igc ?> me-2 fs-6">IG<?= $ig ?></span>
          <strong><?= $bd['total'] ?> safeguards</strong>
          <?php if ($isTarget): ?>
          <span class="badge bg-<?= $igc ?> ms-2">Nivel objetivo</span>
          <?php elseif (!$inScope): ?>
          <span class="badge bg-secondary ms-2 opacity-50">Fuera del objetivo actual</span>
          <?php endif; ?>
        </div>

        <?php if ($inScope): ?>
        <div class="row g-2">
          <div class="col-md-4">
            <div class="d-flex justify-content-between small mb-1">
              <span><i class="ti ti-list-check text-primary me-1"></i>Con control activo</span>
              <span><?= $bd['ctrl'] ?>/<?= $bd['total'] ?> <strong>(<?= $ctrlP ?>%)</strong></span>
            </div>
            <div class="progress" style="height:14px;">
              <div class="progress-bar <?= barColor($ctrlP) ?>" style="width:<?= $ctrlP ?>%;"></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="d-flex justify-content-between small mb-1">
              <span><i class="ti ti-file-description text-info me-1"></i>Con documentación</span>
              <span><?= $bd['doc'] ?>/<?= $bd['total'] ?> <strong>(<?= $docP ?>%)</strong></span>
            </div>
            <div class="progress" style="height:14px;">
              <div class="progress-bar <?= barColor($docP) ?>" style="width:<?= $docP ?>%;"></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="d-flex justify-content-between small mb-1">
              <span><i class="ti ti-circle-check text-success me-1"></i>Control + Documentación</span>
              <span><?= $bd['full'] ?>/<?= $bd['total'] ?> <strong>(<?= $fullP ?>%)</strong></span>
            </div>
            <div class="progress" style="height:14px;">
              <div class="progress-bar <?= barColor($fullP) ?>" style="width:<?= $fullP ?>%;"></div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-0">No aplica al nivel objetivo configurado (IG<?= $igLevel ?>).</p>
        <?php endif; ?>
      </div>
      <?php endfor; ?>

      <div class="d-flex gap-2 flex-wrap">
        <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.php" class="btn btn-sm btn-outline-<?= $igColor ?>">
          <i class="ti ti-books me-1"></i>Ver Biblioteca CIS
        </a>
        <?php if (count($cislibGaps) > 0): ?>
        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#gapsPanel">
          <i class="ti ti-alert-triangle me-1"></i><?= count($cislibGaps) ?> safeguards sin control ni documentación
        </button>
        <?php else: ?>
        <span class="badge bg-success align-self-center"><i class="ti ti-check me-1"></i>Sin gaps críticos</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Gaps panel -->
  <?php if (count($cislibGaps) > 0): ?>
  <div class="collapse mb-4" id="gapsPanel">
    <div class="card border-danger shadow-sm">
      <div class="card-header bg-danger text-white">
        <strong><i class="ti ti-alert-triangle me-1"></i>Gaps críticos: sin control ni documentación (<?= count($cislibGaps) ?>)</strong>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:70px;">Nro.</th>
                <th>Grupo</th>
                <th>Salvaguarda</th>
                <th style="width:60px;">IG</th>
                <th style="width:90px;">Activo</th>
                <th style="width:90px;">Función</th>
                <th style="width:80px;">Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($cislibGaps, 0, 30) as $gap):
                $igc = $igColors[$gap['ig_level']] ?? 'secondary';
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($gap['control_number']) ?></strong></td>
                <td><small><?= htmlspecialchars($gap['control_group']) ?></small></td>
                <td><?= htmlspecialchars($gap['safeguard_title']) ?></td>
                <td><span class="badge bg-<?= $igc ?>">IG<?= $gap['ig_level'] ?></span></td>
                <td><small><?= htmlspecialchars($gap['asset_type'] ?? '') ?></small></td>
                <td><small><?= htmlspecialchars($gap['security_function'] ?? '') ?></small></td>
                <td>
                  <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.php" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-link"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (count($cislibGaps) > 30): ?>
              <tr><td colspan="7" class="text-center text-muted py-2">... y <?= count($cislibGaps) - 30 ?> más. <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.php">Ver todos</a></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== DOCUMENTACIÓN ===== -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-info text-white">
      <strong><i class="ti ti-files me-1"></i>Estado de la Documentación</strong>
    </div>
    <div class="card-body">
      <div class="row g-3 text-center">
        <div class="col-6 col-md-3">
          <div class="border rounded p-3 h-100">
            <div style="font-size:2rem;font-weight:700;" class="text-info"><?= $totalDocs ?></div>
            <div class="small text-muted">Documentos totales</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border rounded p-3 h-100">
            <div style="font-size:2rem;font-weight:700;" class="text-primary"><?= $docsLinkedControl ?></div>
            <div class="small text-muted">Vinculados a controles</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border rounded p-3 h-100">
            <div style="font-size:2rem;font-weight:700;" class="text-<?= $igColor ?>"><?= $docsLinkedCislib ?></div>
            <div class="small text-muted">Vinculados a safeguards CIS</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border rounded p-3 h-100">
            <div style="font-size:2rem;font-weight:700;" class="<?= $controlsWithDocCount === 0 ? 'text-danger' : 'text-success' ?>">
              <?= $controlsWithDocCount ?>
            </div>
            <div class="small text-muted">Controles activos con docs</div>
          </div>
        </div>
      </div>

      <?php if ($controlsTotal > 0): ?>
      <div class="mt-3">
        <?php $ctrlDocPct = round($controlsWithDocCount / max(1, $controlsTotal) * 100); ?>
        <div class="d-flex justify-content-between small mb-1">
          <span><i class="ti ti-list-check me-1"></i>Controles activos con al menos un documento</span>
          <span><?= $controlsWithDocCount ?> / <?= $controlsTotal ?> (<?= $ctrlDocPct ?>%)</span>
        </div>
        <div class="progress" style="height:16px;">
          <div class="progress-bar <?= barColor($ctrlDocPct) ?>" style="width:<?= $ctrlDocPct ?>%;"><?= $ctrlDocPct ?>%</div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($totalDocs === 0): ?>
      <div class="alert alert-warning mt-3 mb-0">
        <i class="ti ti-alert-triangle me-1"></i>
        No hay documentos cargados aún.
        <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.php" class="alert-link">Subir documentos</a>
      </div>
      <?php endif; ?>

      <div class="mt-3">
        <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.php" class="btn btn-sm btn-outline-info">
          <i class="ti ti-folder-open me-1"></i>Ir a Documentación
        </a>
      </div>
    </div>
  </div>

  <!-- ===== CUMPLIMIENTO POR PERIODICIDAD ===== -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header"><strong><i class="ti ti-chart-bar me-1"></i>Cumplimiento Operativo por Periodicidad</strong></div>
    <div class="card-body">
      <?php if (empty($periodicityStats)): ?>
      <p class="text-muted mb-0">Sin datos de ejecuciones aún.</p>
      <?php else: ?>
      <?php foreach ($periodicityStats as $period => $stats):
        $pct = $stats['total'] > 0 ? round($stats['completed'] / $stats['total'] * 100) : 0;
      ?>
      <div class="mb-2">
        <div class="d-flex justify-content-between small mb-1">
          <span><?= htmlspecialchars($period) ?></span>
          <span class="text-muted"><?= $stats['completed'] ?>/<?= $stats['total'] ?> (<?= $pct ?>%)</span>
        </div>
        <div class="progress" style="height:16px;">
          <div class="progress-bar <?= barColor($pct) ?>" style="width:<?= $pct ?>%;"><?= $pct ?>%</div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ===== UPCOMING / OVERDUE ===== -->
  <div class="row g-3 mb-4">

    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-warning text-dark"><strong><i class="ti ti-clock me-1"></i>Próximos Vencimientos (30 días)</strong></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Control</th><th>Vencimiento</th></tr></thead>
            <tbody>
            <?php
            $upcomingArr = iterator_to_array($upcoming);
            if (empty($upcomingArr)): ?>
              <tr><td colspan="2" class="text-center text-muted py-3">Sin vencimientos próximos</td></tr>
            <?php else: foreach ($upcomingArr as $e):
              $ctrl = $controlsMap[$e['plugin_ciscontrols_controls_id']] ?? [];
              $daysLeft = (int)ceil((strtotime($e['due_date']) - strtotime($today)) / 86400);
              $urg = $daysLeft <= 3 ? 'text-danger fw-bold' : ($daysLeft <= 7 ? 'text-warning fw-bold' : '');
            ?>
              <tr>
                <td><?= htmlspecialchars($ctrl['name'] ?? 'N/A') ?></td>
                <td class="<?= $urg ?>"><?= htmlspecialchars($e['due_date']) ?> <small>(<?= $daysLeft ?>d)</small></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-danger text-white"><strong><i class="ti ti-alert-circle me-1"></i>Ejecuciones Vencidas</strong></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Control</th><th>Venció</th></tr></thead>
            <tbody>
            <?php
            $overdueArr = iterator_to_array($overdueList);
            if (empty($overdueArr)): ?>
              <tr><td colspan="2" class="text-center text-muted py-3">Sin ejecuciones vencidas</td></tr>
            <?php else: foreach ($overdueArr as $e):
              $ctrl = $controlsMap[$e['plugin_ciscontrols_controls_id']] ?? [];
              $daysLate = (int)ceil((strtotime($today) - strtotime($e['due_date'])) / 86400);
            ?>
              <tr>
                <td><?= htmlspecialchars($ctrl['name'] ?? 'N/A') ?></td>
                <td class="text-danger"><?= htmlspecialchars($e['due_date']) ?> <small>(hace <?= $daysLate ?>d)</small></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <!-- Quick links -->
  <div class="mb-4 d-flex flex-wrap gap-2">
    <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/ciscontrol.php" class="btn btn-outline-primary"><i class="ti ti-list-check me-1"></i>Controles</a>
    <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cisexecution.php" class="btn btn-outline-secondary"><i class="ti ti-run me-1"></i>Ejecuciones</a>
    <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.php" class="btn btn-outline-info"><i class="ti ti-books me-1"></i>Biblioteca CIS</a>
    <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.php" class="btn btn-outline-warning"><i class="ti ti-folder-open me-1"></i>Documentación</a>
    <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/ciscontrol.form.php" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Nuevo Control</a>
  </div>

</div>
<?php Html::footer(); ?>
