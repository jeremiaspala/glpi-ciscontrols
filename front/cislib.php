<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/cislib.php - CIS v8.1 Library with filtering and coverage
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

global $DB, $CFG_GLPI;

// Load ig_level config
$configRow = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_config', 'WHERE' => ['config_key' => 'ig_level']]);
$configRow = $configRow->current();
$igLevel = (int)($configRow['config_value'] ?? 1);

// Load all cislib entries sorted numerically (e.g. 1.1 < 2.1 < 10.1)
$allLib = [];
$rows = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_cislib']);
foreach ($rows as $r) {
    $allLib[$r['id']] = $r;
}
uasort($allLib, function ($a, $b) {
    $aParts = explode('.', $a['control_number']);
    $bParts = explode('.', $b['control_number']);
    $cmp = (int)$aParts[0] - (int)$bParts[0];
    return $cmp !== 0 ? $cmp : (int)($aParts[1] ?? 0) - (int)($bParts[1] ?? 0);
});

// Load all links: cislib_id => [control names]
$coveredBy = [];
$links = $DB->request([
    'SELECT' => ['l.plugin_ciscontrols_cislib_id as cid', 'c.name as cname', 'c.id as ctrl_id', 'c.is_active'],
    'FROM'   => 'glpi_plugin_ciscontrols_control_cislib AS l',
    'JOIN'   => [
        'glpi_plugin_ciscontrols_controls AS c' => [
            'ON' => ['c' => 'id', 'l' => 'plugin_ciscontrols_controls_id'],
        ],
    ],
]);
foreach ($links as $lnk) {
    if (!isset($coveredBy[$lnk['cid']])) $coveredBy[$lnk['cid']] = [];
    $coveredBy[$lnk['cid']][] = ['id' => $lnk['ctrl_id'], 'name' => $lnk['cname'], 'active' => $lnk['is_active']];
}

// Load all active controls for modal
$allControls = [];
$ctrlRows = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls', 'WHERE' => ['is_active' => 1], 'ORDER' => 'name ASC']);
foreach ($ctrlRows as $c) {
    $allControls[] = $c;
}

// Load documents linked to each cislib safeguard
$docsByCislib = [];
$docLinks = $DB->request([
    'SELECT' => ['l.plugin_ciscontrols_cislib_id as cid', 'l.plugin_ciscontrols_documents_id as did',
                 'l.link_type', 'd.name', 'd.original_name'],
    'FROM'   => 'glpi_plugin_ciscontrols_cislib_documents AS l',
    'JOIN'   => [
        'glpi_plugin_ciscontrols_documents AS d' => [
            'ON' => ['d' => 'id', 'l' => 'plugin_ciscontrols_documents_id'],
        ],
    ],
]);
foreach ($docLinks as $dl) {
    $docsByCislib[$dl['cid']][] = $dl;
}

// Load all non-folder documents for link modal
$allDocs = [];
$docRows = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_documents', 'WHERE' => ['is_folder' => 0], 'ORDER' => 'name ASC']);
foreach ($docRows as $dr) {
    $allDocs[] = $dr;
}

// Unique groups for filter
$groups = [];
foreach ($allLib as $entry) {
    $groups[$entry['control_group']] = true;
}
ksort($groups);

$canWrite = Session::haveRight('plugin_ciscontrols', UPDATE);

Html::header('CIS Controls - Biblioteca CIS v8.1', $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');
?>
<div class="container-fluid mt-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="ti ti-books me-2"></i>Biblioteca CIS Controls v8.1</h2>
    <div>
      <span class="badge bg-primary fs-6">Nivel objetivo: IG<?= $igLevel ?></span>
      <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/config.php" class="btn btn-sm btn-outline-secondary ms-2">
        <i class="ti ti-settings me-1"></i>Cambiar nivel
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="card mb-3 shadow-sm">
    <div class="card-body py-2">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small mb-1">Búsqueda libre</label>
          <input type="text" id="search" class="form-control form-control-sm" placeholder="Buscar por número, título, descripción..." onkeyup="filterTable()">
        </div>
        <div class="col-md-3">
          <label class="form-label small mb-1">
            Nivel IG
            <span class="text-muted" style="font-weight:normal;" title="Cada nivel incluye los safeguards de los niveles anteriores">
              <i class="ti ti-info-circle"></i>
            </span>
          </label>
          <select id="ig_filter" class="form-select form-select-sm" onchange="filterTable()">
            <option value="">Todos los niveles</option>
            <option value="1">Solo introducidos en IG1</option>
            <option value="2">Aplican a IG2 (IG1 + IG2)</option>
            <option value="3">Aplican a IG3 (todos)</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small mb-1">Grupo / Control</label>
          <select id="group_filter" class="form-select form-select-sm" onchange="filterTable()">
            <option value="">Todos los grupos</option>
            <?php foreach (array_keys($groups) as $g): ?>
            <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-responsive">
    <table class="table table-striped table-hover table-sm align-middle" id="cislibTable">
      <thead class="table-dark">
        <tr>
          <th style="width:60px;">Nro.</th>
          <th>Grupo</th>
          <th>Salvaguarda</th>
          <th style="width:80px;">Nivel IG</th>
          <th style="width:100px;">Activo</th>
          <th style="width:100px;">Función</th>
          <th>Cobertura interna</th>
          <th>Documentación</th>
          <?php if ($canWrite): ?>
          <th style="width:110px;">Acciones</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $linkTypeLabels = [
            'referencia'    => 'Referencia',
            'procedimiento' => 'Procedimiento',
            'politica'      => 'Política',
            'evidencia'     => 'Evidencia',
            'otro'          => 'Otro',
        ];
        foreach ($allLib as $entry):
          $isCurrentIg = ($entry['ig_level'] <= $igLevel);
          $covered = isset($coveredBy[$entry['id']]) && count($coveredBy[$entry['id']]) > 0;
          $igColors = [1 => 'success', 2 => 'warning', 3 => 'danger'];
          $igColor = $igColors[$entry['ig_level']] ?? 'secondary';
          $rowClass = '';
          if ($entry['ig_level'] == $igLevel) $rowClass = 'table-primary';
          elseif ($entry['ig_level'] < $igLevel) $rowClass = '';
          else $rowClass = 'text-muted';
          $entryDocs = $docsByCislib[$entry['id']] ?? [];
        ?>
        <tr class="<?= $rowClass ?>"
            data-search="<?= strtolower(htmlspecialchars($entry['control_number'].' '.$entry['safeguard_title'].' '.($entry['safeguard_description'] ?? ''))) ?>"
            data-ig="<?= $entry['ig_level'] ?>"
            data-group="<?= htmlspecialchars($entry['control_group']) ?>">
          <td><strong><?= htmlspecialchars($entry['control_number']) ?></strong></td>
          <td><small><?= htmlspecialchars($entry['control_group']) ?></small></td>
          <td>
            <strong><?= htmlspecialchars($entry['safeguard_title']) ?></strong>
            <?php if (!empty($entry['safeguard_description'])): ?>
            <br><small class="text-muted"><?= htmlspecialchars($entry['safeguard_description']) ?></small>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <span class="badge bg-<?= $igColor ?>">IG<?= $entry['ig_level'] ?></span>
            <?php if ($entry['ig_level'] <= $igLevel): ?>
            <br><small class="text-success" title="Dentro del nivel objetivo"><i class="ti ti-target"></i></small>
            <?php endif; ?>
          </td>
          <td class="text-center"><small><?= htmlspecialchars($entry['asset_type'] ?? '') ?></small></td>
          <td class="text-center"><small><?= htmlspecialchars($entry['security_function'] ?? '') ?></small></td>
          <td>
            <?php if ($covered): ?>
              <?php foreach ($coveredBy[$entry['id']] as $ctrl): ?>
              <span class="badge <?= $ctrl['active'] ? 'bg-success' : 'bg-secondary' ?> me-1 mb-1" title="Control <?= $ctrl['active'] ? 'activo' : 'inactivo' ?>">
                <i class="ti ti-check me-1"></i><?= htmlspecialchars($ctrl['name']) ?>
                <?php if ($canWrite): ?>
                <button type="button" class="btn btn-link p-0 ms-1 text-white border-0" style="font-size:0.7rem;line-height:1;"
                  title="Desvincular control"
                  onclick="submitUnlinkControl(<?= $entry['id'] ?>, <?= $ctrl['id'] ?>)">
                  <i class="ti ti-x"></i>
                </button>
                <?php endif; ?>
              </span>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="badge bg-light text-dark border"><i class="ti ti-minus me-1"></i>Sin cobertura</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($entryDocs)): ?>
              <?php foreach ($entryDocs as $edoc): ?>
              <span class="badge bg-info text-dark me-1 mb-1">
                <i class="ti ti-file me-1"></i><?= htmlspecialchars(mb_strimwidth($edoc['original_name'] ?: $edoc['name'], 0, 25, '...')) ?>
                <small class="ms-1 opacity-75">(<?= htmlspecialchars($linkTypeLabels[$edoc['link_type']] ?? $edoc['link_type']) ?>)</small>
                <?php if ($canWrite): ?>
                <button type="button" class="btn btn-link p-0 ms-1 text-dark border-0" style="font-size:0.7rem;line-height:1;"
                  title="Desvincular documento"
                  onclick="submitUnlinkDoc(<?= $entry['id'] ?>, <?= $edoc['did'] ?>)">
                  <i class="ti ti-x"></i>
                </button>
                <?php endif; ?>
              </span>
              <?php endforeach; ?>
            <?php else: ?>
              <small class="text-muted">—</small>
            <?php endif; ?>
          </td>
          <?php if ($canWrite): ?>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary mb-1 w-100"
              onclick="openLinkModal(<?= $entry['id'] ?>, '<?= addslashes(htmlspecialchars($entry['control_number'].' - '.$entry['safeguard_title'])) ?>')">
              <i class="ti ti-link me-1"></i>Control
            </button>
            <button type="button" class="btn btn-sm btn-outline-info w-100"
              onclick="openLinkDocModal(<?= $entry['id'] ?>, '<?= addslashes(htmlspecialchars($entry['control_number'].' - '.$entry['safeguard_title'])) ?>')">
              <i class="ti ti-file-plus me-1"></i>Doc
            </button>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div id="noResults" class="alert alert-info d-none">No se encontraron safeguards con los filtros aplicados.</div>
</div>

<!-- Hidden forms for POST-based unlink actions -->
<?php if ($canWrite): ?>
<form id="unlinkControlForm" method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.form.php" style="display:none">
  <input type="hidden" name="_glpi_csrf_token" id="unlinkCtrlCsrf" value="">
  <input type="hidden" name="action" value="unlink">
  <input type="hidden" name="cislib_id" id="unlinkCtrlCislibId" value="">
  <input type="hidden" name="control_id" id="unlinkCtrlControlId" value="">
</form>
<form id="unlinkDocForm" method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.form.php" style="display:none">
  <input type="hidden" name="_glpi_csrf_token" id="unlinkDocCsrf" value="">
  <input type="hidden" name="action" value="unlink_doc">
  <input type="hidden" name="cislib_id" id="unlinkDocCislibId" value="">
  <input type="hidden" name="doc_id" id="unlinkDocDocId" value="">
</form>
<?php endif; ?>

<!-- Modal: Vincular control interno -->
<?php if ($canWrite): ?>
<div class="modal fade" id="linkModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-link me-2"></i>Vincular Control Interno</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.form.php">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
        <input type="hidden" name="action" value="link">
        <input type="hidden" name="cislib_id" id="modal_cislib_id" value="">
        <div class="modal-body">
          <p class="text-muted small mb-3">Safeguard: <strong id="modal_safeguard_title"></strong></p>
          <div class="mb-3">
            <label class="form-label">Control interno a vincular</label>
            <select name="control_id" class="form-select" required>
              <option value="">-- Seleccionar control --</option>
              <?php foreach ($allControls as $ctrl): ?>
              <option value="<?= $ctrl['id'] ?>"><?= htmlspecialchars($ctrl['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if (empty($allControls)): ?>
          <div class="alert alert-warning">No hay controles activos. <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/ciscontrol.form.php">Crear un control</a>.</div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Vincular</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Vincular documento a safeguard CIS -->
<div class="modal fade" id="linkDocModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-file-plus me-2"></i>Vincular Documento a Safeguard</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/cislib.form.php">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
        <input type="hidden" name="action" value="link_doc">
        <input type="hidden" name="cislib_id" id="modal_doc_cislib_id" value="">
        <div class="modal-body">
          <p class="text-muted small mb-3">Safeguard: <strong id="modal_doc_safeguard_title"></strong></p>
          <?php if (!empty($allDocs)): ?>
          <div class="mb-3">
            <label class="form-label">Documento a vincular</label>
            <select name="doc_id" class="form-select" required>
              <option value="">-- Seleccionar documento --</option>
              <?php foreach ($allDocs as $doc): ?>
              <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['original_name'] ?: $doc['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Tipo de relación</label>
            <select name="link_type" class="form-select">
              <option value="referencia">Referencia</option>
              <option value="procedimiento">Procedimiento</option>
              <option value="politica">Política</option>
              <option value="evidencia">Evidencia</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <?php else: ?>
          <div class="alert alert-warning">No hay documentos disponibles. <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.php">Subir documentos</a>.</div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <?php if (!empty($allDocs)): ?>
          <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Vincular</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
var _csrfTokens = {
    unlinkCtrl: '<?= Session::getNewCSRFToken() ?>',
    unlinkDoc:  '<?= Session::getNewCSRFToken() ?>',
};

function filterTable() {
    var search = document.getElementById('search').value.toLowerCase();
    var ig     = document.getElementById('ig_filter').value;
    var group  = document.getElementById('group_filter').value;
    var rows   = document.querySelectorAll('#cislibTable tbody tr');
    var visible = 0;

    rows.forEach(function(row) {
        var matchSearch = !search || row.getAttribute('data-search').indexOf(search) !== -1;
        var matchIg     = !ig     || parseInt(row.getAttribute('data-ig')) <= parseInt(ig);
        var matchGroup  = !group  || row.getAttribute('data-group') === group;
        if (matchSearch && matchIg && matchGroup) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });
    document.getElementById('noResults').classList.toggle('d-none', visible > 0);
}

function openLinkModal(cislibId, title) {
    document.getElementById('modal_cislib_id').value = cislibId;
    document.getElementById('modal_safeguard_title').textContent = title;
    new bootstrap.Modal(document.getElementById('linkModal')).show();
}

function openLinkDocModal(cislibId, title) {
    document.getElementById('modal_doc_cislib_id').value = cislibId;
    document.getElementById('modal_doc_safeguard_title').textContent = title;
    new bootstrap.Modal(document.getElementById('linkDocModal')).show();
}

function submitUnlinkControl(cislibId, controlId) {
    if (!confirm('¿Desvincular este control?')) return;
    document.getElementById('unlinkCtrlCsrf').value = _csrfTokens.unlinkCtrl;
    document.getElementById('unlinkCtrlCislibId').value = cislibId;
    document.getElementById('unlinkCtrlControlId').value = controlId;
    document.getElementById('unlinkControlForm').submit();
}

function submitUnlinkDoc(cislibId, docId) {
    if (!confirm('¿Desvincular este documento?')) return;
    document.getElementById('unlinkDocCsrf').value = _csrfTokens.unlinkDoc;
    document.getElementById('unlinkDocCislibId').value = cislibId;
    document.getElementById('unlinkDocDocId').value = docId;
    document.getElementById('unlinkDocForm').submit();
}
</script>
<?php Html::footer(); ?>
