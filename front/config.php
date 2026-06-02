<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/config.php - Plugin configuration page
 */

include('../../../inc/includes.php');
Session::checkLoginUser();
Session::checkRight('config', UPDATE);

global $DB, $CFG_GLPI;

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fields = ['ig_level', 'company_name', 'compliance_email'];
    foreach ($fields as $key) {
        $value = $_POST[$key] ?? '';
        // Check if key exists
        $exists = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_plugin_ciscontrols_config',
            'WHERE'  => ['config_key' => $key],
        ]);
        if (count($exists) > 0) {
            $DB->update('glpi_plugin_ciscontrols_config', ['config_value' => $value], ['config_key' => $key]);
        } else {
            $DB->insert('glpi_plugin_ciscontrols_config', ['config_key' => $key, 'config_value' => $value]);
        }
    }
    $message = 'Configuración guardada correctamente.';
    $messageType = 'success';
}

// Load config
$configData = [];
$rows = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_config']);
foreach ($rows as $row) {
    $configData[$row['config_key']] = $row['config_value'];
}
$igLevel      = (int)($configData['ig_level'] ?? 1);
$companyName  = $configData['company_name'] ?? 'Mi Organización';
$compEmail    = $configData['compliance_email'] ?? '';

// Coverage stats per IG level
function getCoverage($DB, $maxIg) {
    // Count safeguards in cislib up to maxIg
    $total = 0;
    $rows = $DB->request([
        'SELECT' => ['id'],
        'FROM'   => 'glpi_plugin_ciscontrols_cislib',
        'WHERE'  => [['ig_level' => ['<=', $maxIg]]],
    ]);
    $total = count($rows);

    // Count safeguards linked to at least one active control
    if ($total === 0) return ['total' => 0, 'covered' => 0, 'pct' => 0];

    $coveredIds = [];
    $links = $DB->request([
        'SELECT' => ['l.plugin_ciscontrols_cislib_id as cid'],
        'FROM'   => 'glpi_plugin_ciscontrols_control_cislib AS l',
        'JOIN'   => [
            'glpi_plugin_ciscontrols_controls AS c' => [
                'ON' => ['c' => 'id', 'l' => 'plugin_ciscontrols_controls_id'],
            ],
        ],
        'WHERE'  => ['c.is_active' => 1],
    ]);
    foreach ($links as $lnk) {
        $coveredIds[$lnk['cid']] = true;
    }

    // Filter to those within maxIg
    $libRows = $DB->request([
        'SELECT' => ['id'],
        'FROM'   => 'glpi_plugin_ciscontrols_cislib',
        'WHERE'  => [['ig_level' => ['<=', $maxIg]]],
    ]);
    $coveredCount = 0;
    foreach ($libRows as $lr) {
        if (isset($coveredIds[$lr['id']])) $coveredCount++;
    }

    $pct = $total > 0 ? round($coveredCount / $total * 100, 1) : 0;
    return ['total' => $total, 'covered' => $coveredCount, 'pct' => $pct];
}

$cov1 = getCoverage($DB, 1);
$cov2 = getCoverage($DB, 2);
$cov3 = getCoverage($DB, 3);

Html::header('CIS Controls - Configuración', $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');
?>
<div class="container-fluid mt-3">
  <h2><i class="ti ti-settings me-2"></i>Configuración del Plugin</h2>

<?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

  <div class="row g-4">
    <!-- Config form -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <strong><i class="ti ti-adjustments me-1"></i>Parámetros Generales</strong>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">

            <div class="mb-3">
              <label class="form-label fw-bold">Nombre de la Empresa</label>
              <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($companyName) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Email de Compliance</label>
              <input type="email" name="compliance_email" class="form-control" value="<?= htmlspecialchars($compEmail) ?>">
              <small class="text-muted">Se usa para alertas y reportes de cumplimiento.</small>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold">Nivel IG Objetivo</label>
              <div class="row g-2">
                <?php
                $igOptions = [
                    1 => ['label' => 'IG1 - Higiene Básica', 'desc' => 'Controles esenciales para toda organización. Protección mínima recomendada.', 'color' => 'success'],
                    2 => ['label' => 'IG2 - Fundamentos', 'desc' => 'Para organizaciones con mayor exposición al riesgo. Incluye IG1.', 'color' => 'warning'],
                    3 => ['label' => 'IG3 - Avanzado', 'desc' => 'Para organizaciones con alta criticidad. Incluye IG1 e IG2.', 'color' => 'danger'],
                ];
                foreach ($igOptions as $val => $opt):
                    $checked = $igLevel === $val ? ' checked' : '';
                ?>
                <div class="col-12">
                  <div class="card border-<?= $opt['color'] ?> <?= $igLevel === $val ? 'bg-'.$opt['color'].' bg-opacity-10' : '' ?>">
                    <div class="card-body py-2">
                      <div class="form-check">
                        <input type="radio" name="ig_level" value="<?= $val ?>" id="ig<?= $val ?>" class="form-check-input"<?= $checked ?>>
                        <label class="form-check-label fw-bold" for="ig<?= $val ?>"><?= $opt['label'] ?></label>
                        <p class="mb-0 text-muted small"><?= $opt['desc'] ?></p>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <button type="submit" class="btn btn-primary">
              <i class="ti ti-device-floppy me-1"></i>Guardar Configuración
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Coverage summary -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <strong><i class="ti ti-chart-pie me-1"></i>Cobertura de Safeguards CIS v8.1</strong>
        </div>
        <div class="card-body">
          <p class="text-muted small">Porcentaje de safeguards del nivel cubiertos por al menos un control activo.</p>
          <?php foreach ([1 => $cov1, 2 => $cov2, 3 => $cov3] as $lvl => $cov):
            $barColor = $cov['pct'] >= 80 ? 'bg-success' : ($cov['pct'] >= 50 ? 'bg-warning' : 'bg-danger');
            $isCurrent = ($igLevel === $lvl);
          ?>
          <div class="mb-3 <?= $isCurrent ? 'p-2 border rounded border-primary bg-primary bg-opacity-5' : '' ?>">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-bold">
                IG<?= $lvl ?> (acumulativo)
                <?php if ($isCurrent): ?><span class="badge bg-primary ms-1">Objetivo actual</span><?php endif; ?>
              </span>
              <span class="text-muted"><?= $cov['covered'] ?> / <?= $cov['total'] ?> (<?= $cov['pct'] ?>%)</span>
            </div>
            <div class="progress" style="height:22px;">
              <div class="progress-bar <?= $barColor ?> fw-bold" style="width:<?= $cov['pct'] ?>%;">
                <?= $cov['pct'] ?>%
              </div>
            </div>
          </div>
          <?php endforeach; ?>

          <div class="mt-3">
            <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.php" class="btn btn-outline-info btn-sm">
              <i class="ti ti-books me-1"></i>Ver Biblioteca CIS
            </a>
            <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/dashboard.php" class="btn btn-outline-primary btn-sm ms-2">
              <i class="ti ti-chart-bar me-1"></i>Ver Dashboard
            </a>
          </div>
        </div>
      </div>

      <!-- Info card -->
      <div class="card shadow-sm mt-3">
        <div class="card-header">
          <strong><i class="ti ti-info-circle me-1"></i>Acerca de CIS Controls v8.1</strong>
        </div>
        <div class="card-body small text-muted">
          <p>Los CIS Controls son un conjunto de mejores prácticas de seguridad priorizadas y validadas para mitigar los ciberataques más comunes.</p>
          <ul class="mb-0">
            <li><strong>IG1 (56 safeguards):</strong> Higiene básica — toda organización debería cumplirlos.</li>
            <li><strong>IG2 (+74 safeguards):</strong> Para organizaciones con activos de mayor valor o riesgo.</li>
            <li><strong>IG3 (+23 safeguards):</strong> Para organizaciones con alta criticidad o sector regulado.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
<?php Html::footer(); ?>
