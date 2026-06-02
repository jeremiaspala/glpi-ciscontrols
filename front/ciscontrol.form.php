<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/ciscontrol.form.php - Control add/edit/delete/toggle form handler
 */

include('../../../inc/includes.php');
Session::checkLoginUser();
Session::checkRight('plugin_ciscontrols', UPDATE);

global $DB, $CFG_GLPI;

$action = $_REQUEST['action'] ?? ($_POST['action'] ?? '');
$id     = (int)($_REQUEST['id'] ?? 0);

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'add') {
        $periodicity = $_POST['periodicity'] ?? 'Mensual';
        $days = (int)($_POST['periodicity_days'] ?? PluginCiscontrolsControl::periodicityToDays($periodicity));
        $now = date('Y-m-d H:i:s');

        $DB->insert('glpi_plugin_ciscontrols_controls', [
            'name'                 => $_POST['name'] ?? '',
            'document'             => $_POST['document'] ?? null,
            'periodicity'          => $periodicity,
            'periodicity_days'     => $days,
            'activity'             => $_POST['activity'] ?? null,
            'evidence_type'        => $_POST['evidence_type'] ?? null,
            'responsible'          => $_POST['responsible'] ?? null,
            'cis_version'          => $_POST['cis_version'] ?? null,
            'is_active'            => isset($_POST['is_active']) ? 1 : 0,
            'reminder_enabled'     => isset($_POST['reminder_enabled']) ? 1 : 0,
            'reminder_days_before' => (int)($_POST['reminder_days_before'] ?? 3),
            'reminder_email'       => $_POST['reminder_email'] ?? null,
            'reminder_subject'     => $_POST['reminder_subject'] ?? null,
            'reminder_body'        => $_POST['reminder_body'] ?? null,
            'date_creation'        => $now,
            'date_mod'             => $now,
        ]);
        $newId = $DB->insertId();

        if (isset($_POST['is_active']) && $newId > 0) {
            $fdd = !empty($_POST["first_due_date"]) ? $_POST["first_due_date"] : null;
            PluginCiscontrolsControl::ensurePendingExecution($newId, $fdd);
        }

        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.php');
        exit;
    }

    if ($action === 'update' && $id > 0) {
        $periodicity = $_POST['periodicity'] ?? 'Mensual';
        $days = (int)($_POST['periodicity_days'] ?? PluginCiscontrolsControl::periodicityToDays($periodicity));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $now = date('Y-m-d H:i:s');

        $DB->update('glpi_plugin_ciscontrols_controls', [
            'name'                 => $_POST['name'] ?? '',
            'document'             => $_POST['document'] ?? null,
            'periodicity'          => $periodicity,
            'periodicity_days'     => $days,
            'activity'             => $_POST['activity'] ?? null,
            'evidence_type'        => $_POST['evidence_type'] ?? null,
            'responsible'          => $_POST['responsible'] ?? null,
            'cis_version'          => $_POST['cis_version'] ?? null,
            'is_active'            => $isActive,
            'reminder_enabled'     => isset($_POST['reminder_enabled']) ? 1 : 0,
            'reminder_days_before' => (int)($_POST['reminder_days_before'] ?? 3),
            'reminder_email'       => $_POST['reminder_email'] ?? null,
            'reminder_subject'     => $_POST['reminder_subject'] ?? null,
            'reminder_body'        => $_POST['reminder_body'] ?? null,
            'date_mod'             => $now,
        ], ['id' => $id]);

        if ($isActive) {
            PluginCiscontrolsControl::ensurePendingExecution($id);
        }

        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.php');
        exit;
    }

    // Unlink cislib safeguard
    if ($action === 'unlink_cislib' && $id > 0) {
        $cislibId = (int)($_POST['cislib_id'] ?? 0);
        if ($cislibId > 0) {
            $DB->delete('glpi_plugin_ciscontrols_control_cislib', [
                'plugin_ciscontrols_controls_id' => $id,
                'plugin_ciscontrols_cislib_id'   => $cislibId,
            ]);
        }
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.form.php?id=' . $id);
        exit;
    }

    // Link cislib safeguard
    if ($action === 'link_cislib' && $id > 0) {
        $cislibId = (int)($_POST['cislib_id'] ?? 0);
        if ($cislibId > 0) {
            $existing = $DB->request([
                'FROM'  => 'glpi_plugin_ciscontrols_control_cislib',
                'WHERE' => ['plugin_ciscontrols_controls_id' => $id, 'plugin_ciscontrols_cislib_id' => $cislibId],
            ]);
            if (count($existing) === 0) {
                $DB->insert('glpi_plugin_ciscontrols_control_cislib', [
                    'plugin_ciscontrols_controls_id' => $id,
                    'plugin_ciscontrols_cislib_id'   => $cislibId,
                ]);
            }
        }
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.form.php?id=' . $id);
        exit;
    }

    // Unlink document
    if ($action === 'unlink_doc' && $id > 0) {
        $docId = (int)($_POST['doc_id'] ?? 0);
        if ($docId > 0) {
            $DB->delete('glpi_plugin_ciscontrols_control_documents', [
                'plugin_ciscontrols_controls_id'  => $id,
                'plugin_ciscontrols_documents_id' => $docId,
            ]);
        }
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.form.php?id=' . $id);
        exit;
    }

    // Link document
    if ($action === 'link_doc' && $id > 0) {
        $docId    = (int)($_POST['doc_id'] ?? 0);
        $linkType = $_POST['link_type'] ?? 'procedimiento';
        if ($docId > 0) {
            $existing = $DB->request([
                'FROM'  => 'glpi_plugin_ciscontrols_control_documents',
                'WHERE' => ['plugin_ciscontrols_controls_id' => $id, 'plugin_ciscontrols_documents_id' => $docId],
            ]);
            if (count($existing) === 0) {
                $DB->insert('glpi_plugin_ciscontrols_control_documents', [
                    'plugin_ciscontrols_controls_id'  => $id,
                    'plugin_ciscontrols_documents_id' => $docId,
                    'link_type'                       => $linkType,
                ]);
            }
        }
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.form.php?id=' . $id);
        exit;
    }
}

// POST-only state mutations (delete, activate, deactivate)
// GET requests are rejected — CSRF is validated by GLPI 11 kernel for POST only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'delete' && $id > 0) {
        $DB->delete('glpi_plugin_ciscontrols_control_cislib', ['plugin_ciscontrols_controls_id' => $id]);
        $DB->delete('glpi_plugin_ciscontrols_control_documents', ['plugin_ciscontrols_controls_id' => $id]);
        $DB->delete('glpi_plugin_ciscontrols_executions', ['plugin_ciscontrols_controls_id' => $id]);
        $DB->delete('glpi_plugin_ciscontrols_controls', ['id' => $id]);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.php');
        exit;
    }

    if ($action === 'activate' && $id > 0) {
        $DB->update('glpi_plugin_ciscontrols_controls', ['is_active' => 1, 'date_mod' => date('Y-m-d H:i:s')], ['id' => $id]);
        PluginCiscontrolsControl::ensurePendingExecution($id);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.php');
        exit;
    }

    if ($action === 'deactivate' && $id > 0) {
        $DB->update('glpi_plugin_ciscontrols_controls', ['is_active' => 0, 'date_mod' => date('Y-m-d H:i:s')], ['id' => $id]);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.php');
        exit;
    }
}

// Show form (GET)
$pageTitle = $id > 0 ? 'Editar Control CIS' : 'Nuevo Control CIS';
Html::header($pageTitle, $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');

PluginCiscontrolsControl::renderForm($id);

// --- New sections: CIS Library links and Document links (only when editing) ---
if ($id > 0) {
    // Load linked safeguards
    $linkedSafeguards = [];
    $safeguardLinks = $DB->request([
        'SELECT' => ['l.id as link_id', 'l.plugin_ciscontrols_cislib_id as cid', 'lib.control_number', 'lib.safeguard_title', 'lib.ig_level', 'lib.control_group'],
        'FROM'   => 'glpi_plugin_ciscontrols_control_cislib AS l',
        'JOIN'   => [
            'glpi_plugin_ciscontrols_cislib AS lib' => [
                'ON' => ['lib' => 'id', 'l' => 'plugin_ciscontrols_cislib_id'],
            ],
        ],
        'WHERE'  => ['l.plugin_ciscontrols_controls_id' => $id],
        'ORDER'  => 'lib.control_number ASC',
    ]);
    foreach ($safeguardLinks as $sl) {
        $linkedSafeguards[] = $sl;
    }

    // All cislib for selector (exclude already linked)
    $linkedCislibIds = array_column($linkedSafeguards, 'cid');
    $allCislib = [];
    $cislibRows = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_cislib', 'ORDER' => 'control_number ASC']);
    foreach ($cislibRows as $cr) {
        if (!in_array($cr['id'], $linkedCislibIds)) {
            $allCislib[] = $cr;
        }
    }

    // Load linked documents
    $linkedDocs = [];
    $docLinks = $DB->request([
        'SELECT' => ['l.id as link_id', 'l.plugin_ciscontrols_documents_id as did', 'l.link_type', 'd.name', 'd.original_name', 'd.mime_type'],
        'FROM'   => 'glpi_plugin_ciscontrols_control_documents AS l',
        'JOIN'   => [
            'glpi_plugin_ciscontrols_documents AS d' => [
                'ON' => ['d' => 'id', 'l' => 'plugin_ciscontrols_documents_id'],
            ],
        ],
        'WHERE'  => ['l.plugin_ciscontrols_controls_id' => $id],
    ]);
    foreach ($docLinks as $dl) {
        $linkedDocs[] = $dl;
    }

    // All non-folder documents for selector
    $allDocs = [];
    $docRows = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_documents', 'WHERE' => ['is_folder' => 0], 'ORDER' => 'name ASC']);
    foreach ($docRows as $dr) {
        $allDocs[] = $dr;
    }

    $igColors = [1 => 'success', 2 => 'warning', 3 => 'danger'];
    $linkTypeLabels = [
        'procedimiento' => 'Procedimiento',
        'registro'      => 'Registro',
        'evidencia'     => 'Evidencia',
        'politica'      => 'Política',
        'otro'          => 'Otro',
    ];
?>
<div class="container-fluid mb-4">

  <!-- Safeguards CIS vinculados -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong><i class="ti ti-books me-1"></i>Safeguards CIS v8.1 Vinculados</strong>
      <span class="badge bg-secondary"><?= count($linkedSafeguards) ?></span>
    </div>
    <div class="card-body">
      <?php if (!empty($linkedSafeguards)): ?>
      <div class="mb-3">
        <?php foreach ($linkedSafeguards as $sg):
          $igc = $igColors[$sg['ig_level']] ?? 'secondary';
        ?>
        <div class="d-inline-flex align-items-center badge bg-light text-dark border me-2 mb-2 py-2 px-2">
          <span class="badge bg-<?= $igc ?> me-1">IG<?= $sg['ig_level'] ?></span>
          <strong class="me-1"><?= htmlspecialchars($sg['control_number']) ?></strong>
          <?= htmlspecialchars($sg['safeguard_title']) ?>
          <form method="POST" action="" class="d-inline ms-2">
            <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
            <input type="hidden" name="action" value="unlink_cislib">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="cislib_id" value="<?= $sg['cid'] ?>">
            <button type="submit" class="btn btn-sm btn-link p-0 text-danger ms-1" title="Desvincular"
              onclick="return confirm('¿Desvincular este safeguard?');">
              <i class="ti ti-x"></i>
            </button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="text-muted small mb-3">No hay safeguards vinculados todavía.</p>
      <?php endif; ?>

      <?php if (!empty($allCislib)): ?>
      <form method="POST" action="" class="row g-2 align-items-end">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
        <input type="hidden" name="action" value="link_cislib">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="col-md-8">
          <label class="form-label small">Agregar safeguard</label>
          <select name="cislib_id" class="form-select form-select-sm" required>
            <option value="">-- Seleccionar safeguard CIS --</option>
            <?php foreach ($allCislib as $cs): ?>
            <option value="<?= $cs['id'] ?>">[IG<?= $cs['ig_level'] ?>] <?= htmlspecialchars($cs['control_number']) ?> — <?= htmlspecialchars(mb_strimwidth($cs['safeguard_title'], 0, 80, '...')) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-sm btn-outline-primary w-100">
            <i class="ti ti-link me-1"></i>Vincular Safeguard
          </button>
        </div>
      </form>
      <?php else: ?>
      <p class="text-muted small">Todos los safeguards disponibles ya están vinculados.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Documentos vinculados -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong><i class="ti ti-file-description me-1"></i>Documentos Vinculados</strong>
      <span class="badge bg-secondary"><?= count($linkedDocs) ?></span>
    </div>
    <div class="card-body">
      <?php if (!empty($linkedDocs)): ?>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Documento</th>
              <th style="width:120px;">Tipo</th>
              <th style="width:60px;">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($linkedDocs as $ld): ?>
            <tr>
              <td>
                <i class="ti ti-file me-1 text-muted"></i>
                <?= htmlspecialchars($ld['original_name'] ?: $ld['name']) ?>
              </td>
              <td>
                <span class="badge bg-info"><?= htmlspecialchars($linkTypeLabels[$ld['link_type']] ?? $ld['link_type']) ?></span>
              </td>
              <td>
                <form method="POST" action="" class="d-inline">
                  <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                  <input type="hidden" name="action" value="unlink_doc">
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <input type="hidden" name="doc_id" value="<?= $ld['did'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Desvincular"
                    onclick="return confirm('¿Desvincular este documento?');">
                    <i class="ti ti-x"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <p class="text-muted small mb-3">No hay documentos vinculados todavía.</p>
      <?php endif; ?>

      <?php if (!empty($allDocs)): ?>
      <form method="POST" action="" class="row g-2 align-items-end">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
        <input type="hidden" name="action" value="link_doc">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="col-md-5">
          <label class="form-label small">Documento</label>
          <select name="doc_id" class="form-select form-select-sm" required>
            <option value="">-- Seleccionar documento --</option>
            <?php foreach ($allDocs as $doc): ?>
            <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['original_name'] ?: $doc['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Tipo</label>
          <select name="link_type" class="form-select form-select-sm">
            <?php foreach ($linkTypeLabels as $val => $lbl): ?>
            <option value="<?= $val ?>"><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-sm btn-outline-primary w-100">
            <i class="ti ti-link me-1"></i>Vincular Documento
          </button>
        </div>
      </form>
      <?php else: ?>
      <div class="alert alert-light small">
        No hay documentos disponibles. <a href="<?= $CFG_GLPI['root_doc'] ?>/plugins/ciscontrols/front/doctree.php">Subir documentos</a>.
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>
<?php
}

Html::footer();
