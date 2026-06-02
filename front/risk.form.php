<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/risk.form.php - Risk CRUD + control/document link handler
 */

include('../../../inc/includes.php');
Session::checkLoginUser();
Session::checkRight('plugin_ciscontrols', UPDATE);

global $DB, $CFG_GLPI;

$listUrl = $CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.php';
$action  = $_POST['action'] ?? '';
$id      = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Only show form on GET
    $pageTitle = $id > 0 ? 'Editar Riesgo' : 'Nuevo Riesgo';
    Html::header($pageTitle, $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');
    renderRiskForm($id, $DB, $CFG_GLPI);
    Html::footer();
    exit;
}

// ---- POST handlers (CSRF validated by GLPI 11 kernel) ----

$now = date('Y-m-d H:i:s');

if ($action === 'add') {
    $likelihood = max(1, min(5, (int)($_POST['likelihood'] ?? 1)));
    $impact     = max(1, min(5, (int)($_POST['impact']     ?? 1)));
    $DB->insert('glpi_plugin_ciscontrols_risks', [
        'name'            => $_POST['name']            ?? '',
        'category'        => $_POST['category']        ?? 'otro',
        'description'     => $_POST['description']     ?? null,
        'asset'           => $_POST['asset']           ?? null,
        'threat'          => $_POST['threat']          ?? null,
        'vulnerability'   => $_POST['vulnerability']   ?? null,
        'likelihood'      => $likelihood,
        'impact'          => $impact,
        'risk_score'      => $likelihood * $impact,
        'treatment'       => $_POST['treatment']       ?? 'mitigar',
        'treatment_notes' => $_POST['treatment_notes'] ?? null,
        'owner'           => $_POST['owner']           ?? null,
        'status'          => $_POST['status']          ?? 'abierto',
        'review_date'     => $_POST['review_date']     ?: null,
        'date_creation'   => $now,
        'date_mod'        => $now,
    ]);
    Html::redirect($listUrl);
    exit;
}

if ($action === 'update' && $id > 0) {
    $likelihood = max(1, min(5, (int)($_POST['likelihood'] ?? 1)));
    $impact     = max(1, min(5, (int)($_POST['impact']     ?? 1)));
    $DB->update('glpi_plugin_ciscontrols_risks', [
        'name'            => $_POST['name']            ?? '',
        'category'        => $_POST['category']        ?? 'otro',
        'description'     => $_POST['description']     ?? null,
        'asset'           => $_POST['asset']           ?? null,
        'threat'          => $_POST['threat']          ?? null,
        'vulnerability'   => $_POST['vulnerability']   ?? null,
        'likelihood'      => $likelihood,
        'impact'          => $impact,
        'risk_score'      => $likelihood * $impact,
        'treatment'       => $_POST['treatment']       ?? 'mitigar',
        'treatment_notes' => $_POST['treatment_notes'] ?? null,
        'owner'           => $_POST['owner']           ?? null,
        'status'          => $_POST['status']          ?? 'abierto',
        'review_date'     => $_POST['review_date']     ?: null,
        'date_mod'        => $now,
    ], ['id' => $id]);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.form.php?id=' . $id);
    exit;
}

if ($action === 'delete' && $id > 0) {
    $DB->delete('glpi_plugin_ciscontrols_risk_controls',  ['plugin_ciscontrols_risks_id' => $id]);
    $DB->delete('glpi_plugin_ciscontrols_risk_documents', ['plugin_ciscontrols_risks_id' => $id]);
    $DB->delete('glpi_plugin_ciscontrols_risks',          ['id' => $id]);
    Html::redirect($listUrl);
    exit;
}

if ($action === 'link_control' && $id > 0) {
    $ctrlId = (int)($_POST['control_id'] ?? 0);
    if ($ctrlId > 0) {
        $exists = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_risk_controls',
            'WHERE' => ['plugin_ciscontrols_risks_id' => $id, 'plugin_ciscontrols_controls_id' => $ctrlId]]);
        if (count($exists) === 0) {
            $DB->insert('glpi_plugin_ciscontrols_risk_controls', [
                'plugin_ciscontrols_risks_id'    => $id,
                'plugin_ciscontrols_controls_id' => $ctrlId,
            ]);
        }
    }
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.form.php?id=' . $id);
    exit;
}

if ($action === 'unlink_control' && $id > 0) {
    $ctrlId = (int)($_POST['control_id'] ?? 0);
    if ($ctrlId > 0) {
        $DB->delete('glpi_plugin_ciscontrols_risk_controls', [
            'plugin_ciscontrols_risks_id'    => $id,
            'plugin_ciscontrols_controls_id' => $ctrlId,
        ]);
    }
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.form.php?id=' . $id);
    exit;
}

if ($action === 'link_doc' && $id > 0) {
    $docId    = (int)($_POST['doc_id']    ?? 0);
    $linkType = $_POST['link_type'] ?? 'evidencia';
    if ($docId > 0) {
        $exists = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_risk_documents',
            'WHERE' => ['plugin_ciscontrols_risks_id' => $id, 'plugin_ciscontrols_documents_id' => $docId]]);
        if (count($exists) === 0) {
            $DB->insert('glpi_plugin_ciscontrols_risk_documents', [
                'plugin_ciscontrols_risks_id'     => $id,
                'plugin_ciscontrols_documents_id' => $docId,
                'link_type'                       => $linkType,
            ]);
        }
    }
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.form.php?id=' . $id);
    exit;
}

if ($action === 'unlink_doc' && $id > 0) {
    $docId = (int)($_POST['doc_id'] ?? 0);
    if ($docId > 0) {
        $DB->delete('glpi_plugin_ciscontrols_risk_documents', [
            'plugin_ciscontrols_risks_id'     => $id,
            'plugin_ciscontrols_documents_id' => $docId,
        ]);
    }
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.form.php?id=' . $id);
    exit;
}

Html::redirect($listUrl);
exit;

// ---- Form renderer ----

function renderRiskForm(int $id, $DB, array $CFG_GLPI): void {
    $item = [];
    if ($id > 0) {
        $res = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_risks', 'WHERE' => ['id' => $id]]);
        $item = $res->current() ?: [];
    }
    $isEdit  = !empty($item);
    $formUrl = $CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.form.php';
    $listUrl = $CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/risk.php';

    $likelihood = (int)($item['likelihood'] ?? 1);
    $impact     = (int)($item['impact']     ?? 1);
    $score      = $likelihood * $impact;

    echo "<div class='container-fluid mt-3'>";
    echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
    echo "<h2><i class='ti ti-alert-triangle me-2'></i>" . ($isEdit ? 'Editar Riesgo' : 'Nuevo Riesgo') . "</h2>";
    echo "<a href='{$listUrl}' class='btn btn-outline-secondary'><i class='ti ti-arrow-left me-1'></i>Volver</a>";
    echo "</div>";

    echo "<form method='POST' action='{$formUrl}'>";
    echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
    if ($isEdit) echo "<input type='hidden' name='id' value='{$id}'>";

    echo "<div class='row g-3'>";

    // Left column: main fields
    echo "<div class='col-lg-8'>";
    echo "<div class='card shadow-sm mb-3'>";
    echo "<div class='card-header bg-danger text-white'><strong><i class='ti ti-info-circle me-1'></i>Identificación del Riesgo</strong></div>";
    echo "<div class='card-body'>";
    echo "<div class='row g-3'>";

    // Name
    echo "<div class='col-12'>";
    echo "<label class='form-label fw-bold'>Nombre del Riesgo <span class='text-danger'>*</span></label>";
    echo "<input type='text' name='name' class='form-control' required value='" . htmlspecialchars($item['name'] ?? '') . "' placeholder='Ej: Acceso no autorizado a sistemas críticos'>";
    echo "</div>";

    // Category + Asset
    echo "<div class='col-md-6'>";
    echo "<label class='form-label fw-bold'>Categoría</label>";
    echo "<select name='category' class='form-select'>";
    foreach (PluginCiscontrolsRisk::categories() as $val => $lbl) {
        $sel = (($item['category'] ?? '') === $val) ? ' selected' : '';
        echo "<option value='" . htmlspecialchars($val) . "'{$sel}>" . htmlspecialchars($lbl) . "</option>";
    }
    echo "</select></div>";

    echo "<div class='col-md-6'>";
    echo "<label class='form-label fw-bold'>Activo Afectado</label>";
    echo "<input type='text' name='asset' class='form-control' value='" . htmlspecialchars($item['asset'] ?? '') . "' placeholder='Ej: Servidor de base de datos'>";
    echo "</div>";

    // Threat + Vulnerability
    echo "<div class='col-md-6'>";
    echo "<label class='form-label fw-bold'>Amenaza</label>";
    echo "<textarea name='threat' class='form-control' rows='2' placeholder='¿Qué podría ocurrir?'>" . htmlspecialchars($item['threat'] ?? '') . "</textarea>";
    echo "</div>";

    echo "<div class='col-md-6'>";
    echo "<label class='form-label fw-bold'>Vulnerabilidad</label>";
    echo "<textarea name='vulnerability' class='form-control' rows='2' placeholder='¿Por qué es posible?'>" . htmlspecialchars($item['vulnerability'] ?? '') . "</textarea>";
    echo "</div>";

    // Description
    echo "<div class='col-12'>";
    echo "<label class='form-label fw-bold'>Descripción</label>";
    echo "<textarea name='description' class='form-control' rows='3'>" . htmlspecialchars($item['description'] ?? '') . "</textarea>";
    echo "</div>";

    echo "</div></div></div>"; // row / card-body / card

    // Treatment card
    echo "<div class='card shadow-sm mb-3'>";
    echo "<div class='card-header'><strong><i class='ti ti-shield me-1'></i>Tratamiento y Seguimiento</strong></div>";
    echo "<div class='card-body'><div class='row g-3'>";

    echo "<div class='col-md-4'>";
    echo "<label class='form-label fw-bold'>Tratamiento</label>";
    echo "<select name='treatment' class='form-select'>";
    foreach (PluginCiscontrolsRisk::treatmentOptions() as $val => $lbl) {
        $sel = (($item['treatment'] ?? 'mitigar') === $val) ? ' selected' : '';
        echo "<option value='" . htmlspecialchars($val) . "'{$sel}>" . htmlspecialchars($lbl) . "</option>";
    }
    echo "</select></div>";

    echo "<div class='col-md-4'>";
    echo "<label class='form-label fw-bold'>Estado</label>";
    echo "<select name='status' class='form-select'>";
    foreach (PluginCiscontrolsRisk::statusOptions() as $val => $lbl) {
        $sel = (($item['status'] ?? 'abierto') === $val) ? ' selected' : '';
        echo "<option value='" . htmlspecialchars($val) . "'{$sel}>" . htmlspecialchars($lbl) . "</option>";
    }
    echo "</select></div>";

    echo "<div class='col-md-4'>";
    echo "<label class='form-label fw-bold'>Responsable</label>";
    echo "<input type='text' name='owner' class='form-control' value='" . htmlspecialchars($item['owner'] ?? '') . "'>";
    echo "</div>";

    echo "<div class='col-md-4'>";
    echo "<label class='form-label fw-bold'>Fecha de revisión</label>";
    echo "<input type='date' name='review_date' class='form-control' value='" . htmlspecialchars($item['review_date'] ?? '') . "'>";
    echo "</div>";

    echo "<div class='col-md-8'>";
    echo "<label class='form-label fw-bold'>Notas de tratamiento</label>";
    echo "<textarea name='treatment_notes' class='form-control' rows='2'>" . htmlspecialchars($item['treatment_notes'] ?? '') . "</textarea>";
    echo "</div>";

    echo "</div></div></div>"; // row / card-body / card
    echo "</div>"; // col-lg-8

    // Right column: scoring
    echo "<div class='col-lg-4'>";
    echo "<div class='card shadow-sm mb-3'>";
    echo "<div class='card-header bg-dark text-white'><strong><i class='ti ti-calculator me-1'></i>Valoración del Riesgo</strong></div>";
    echo "<div class='card-body'>";

    // Likelihood
    echo "<label class='form-label fw-bold'>Probabilidad</label>";
    echo "<select name='likelihood' id='likelihood' class='form-select mb-3' onchange='updateScore()'>";
    foreach (PluginCiscontrolsRisk::likelihoodLabels() as $val => $lbl) {
        $sel = ($likelihood === $val) ? ' selected' : '';
        echo "<option value='{$val}'{$sel}>{$val} — {$lbl}</option>";
    }
    echo "</select>";

    // Impact
    echo "<label class='form-label fw-bold'>Impacto</label>";
    echo "<select name='impact' id='impact' class='form-select mb-3' onchange='updateScore()'>";
    foreach (PluginCiscontrolsRisk::impactLabels() as $val => $lbl) {
        $sel = ($impact === $val) ? ' selected' : '';
        echo "<option value='{$val}'{$sel}>{$val} — {$lbl}</option>";
    }
    echo "</select>";

    // Score display
    $hex   = PluginCiscontrolsRisk::levelHex($score);
    $label = PluginCiscontrolsRisk::levelLabel($score);
    echo "<div class='text-center p-3 rounded' id='scoreCard' style='background:{$hex};color:#fff;'>";
    echo "<div style='font-size:3rem;font-weight:700;' id='scoreValue'>{$score}</div>";
    echo "<div style='font-size:1.1rem;' id='scoreLabel'>{$label}</div>";
    echo "<small>Probabilidad × Impacto</small>";
    echo "</div>";

    echo "</div></div>"; // card-body / card

    // Submit
    echo "<div class='d-grid gap-2'>";
    echo "<button type='submit' name='action' value='" . ($isEdit ? 'update' : 'add') . "' class='btn btn-danger btn-lg'>";
    echo "<i class='ti ti-device-floppy me-1'></i>Guardar Riesgo</button>";
    if ($isEdit) {
        echo "<form method='POST' action='{$formUrl}' onsubmit=\"return confirm('¿Eliminar este riesgo?')\">";
        echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
        echo "<input type='hidden' name='action' value='delete'>";
        echo "<input type='hidden' name='id' value='{$id}'>";
        echo "<button type='submit' class='btn btn-outline-danger w-100'><i class='ti ti-trash me-1'></i>Eliminar</button>";
        echo "</form>";
    }
    echo "</div>";
    echo "</div>"; // col-lg-4

    echo "</div>"; // row
    echo "</form>";

    // ---- Linked sections (only when editing) ----
    if ($isEdit) {
        // Load linked controls
        $linkedControls = [];
        foreach ($DB->request([
            'SELECT' => ['rc.plugin_ciscontrols_controls_id as cid', 'c.name', 'c.is_active'],
            'FROM'   => 'glpi_plugin_ciscontrols_risk_controls AS rc',
            'JOIN'   => ['glpi_plugin_ciscontrols_controls AS c' => ['ON' => ['c' => 'id', 'rc' => 'plugin_ciscontrols_controls_id']]],
            'WHERE'  => ['rc.plugin_ciscontrols_risks_id' => $id],
            'ORDER'  => 'c.name ASC',
        ]) as $r) {
            $linkedControls[] = $r;
        }
        $linkedControlIds = array_column($linkedControls, 'cid');

        // All active controls not yet linked
        $availableControls = [];
        foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls', 'WHERE' => ['is_active' => 1], 'ORDER' => 'name ASC']) as $c) {
            if (!in_array($c['id'], $linkedControlIds)) $availableControls[] = $c;
        }

        // Load linked documents
        $linkedDocs = [];
        foreach ($DB->request([
            'SELECT' => ['rd.plugin_ciscontrols_documents_id as did', 'rd.link_type', 'd.name', 'd.original_name'],
            'FROM'   => 'glpi_plugin_ciscontrols_risk_documents AS rd',
            'JOIN'   => ['glpi_plugin_ciscontrols_documents AS d' => ['ON' => ['d' => 'id', 'rd' => 'plugin_ciscontrols_documents_id']]],
            'WHERE'  => ['rd.plugin_ciscontrols_risks_id' => $id],
        ]) as $r) {
            $linkedDocs[] = $r;
        }
        $linkedDocIds = array_column($linkedDocs, 'did');

        // All non-folder docs not yet linked
        $availableDocs = [];
        foreach ($DB->request(['FROM' => 'glpi_plugin_ciscontrols_documents', 'WHERE' => ['is_folder' => 0], 'ORDER' => 'name ASC']) as $d) {
            if (!in_array($d['id'], $linkedDocIds)) $availableDocs[] = $d;
        }

        $linkTypeLabels = ['evidencia' => 'Evidencia', 'politica' => 'Política', 'procedimiento' => 'Procedimiento', 'registro' => 'Registro', 'otro' => 'Otro'];

        echo "<div class='row g-3 mt-1'>";

        // Controls card
        echo "<div class='col-md-6'>";
        echo "<div class='card shadow-sm'>";
        echo "<div class='card-header d-flex justify-content-between align-items-center'>";
        echo "<strong><i class='ti ti-list-check me-1'></i>Controles Mitigantes</strong>";
        echo "<span class='badge bg-secondary'>" . count($linkedControls) . "</span>";
        echo "</div><div class='card-body'>";

        if (!empty($linkedControls)) {
            foreach ($linkedControls as $lc) {
                echo "<div class='d-flex align-items-center justify-content-between mb-2'>";
                echo "<span class='badge " . ($lc['is_active'] ? 'bg-success' : 'bg-secondary') . " me-2'><i class='ti ti-list-check me-1'></i>" . htmlspecialchars($lc['name']) . "</span>";
                echo "<form method='POST' action='{$formUrl}' class='d-inline'>";
                echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
                echo "<input type='hidden' name='action' value='unlink_control'>";
                echo "<input type='hidden' name='id' value='{$id}'>";
                echo "<input type='hidden' name='control_id' value='{$lc['cid']}'>";
                echo "<button type='submit' class='btn btn-sm btn-outline-danger' onclick='return confirm(\"¿Desvincular?\")'><i class='ti ti-x'></i></button>";
                echo "</form></div>";
            }
        } else {
            echo "<p class='text-muted small'>Sin controles vinculados.</p>";
        }

        if (!empty($availableControls)) {
            echo "<form method='POST' action='{$formUrl}' class='row g-2 mt-1'>";
            echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
            echo "<input type='hidden' name='action' value='link_control'>";
            echo "<input type='hidden' name='id' value='{$id}'>";
            echo "<div class='col-9'><select name='control_id' class='form-select form-select-sm' required>";
            echo "<option value=''>-- Seleccionar control --</option>";
            foreach ($availableControls as $c) {
                echo "<option value='{$c['id']}'>" . htmlspecialchars($c['name']) . "</option>";
            }
            echo "</select></div>";
            echo "<div class='col-3'><button type='submit' class='btn btn-sm btn-outline-primary w-100'><i class='ti ti-link'></i></button></div>";
            echo "</form>";
        }

        echo "</div></div></div>"; // card-body / card / col

        // Documents card
        echo "<div class='col-md-6'>";
        echo "<div class='card shadow-sm'>";
        echo "<div class='card-header d-flex justify-content-between align-items-center'>";
        echo "<strong><i class='ti ti-files me-1'></i>Documentación Asociada</strong>";
        echo "<span class='badge bg-secondary'>" . count($linkedDocs) . "</span>";
        echo "</div><div class='card-body'>";

        if (!empty($linkedDocs)) {
            foreach ($linkedDocs as $ld) {
                $docName = $ld['original_name'] ?: $ld['name'];
                echo "<div class='d-flex align-items-center justify-content-between mb-2'>";
                echo "<span><i class='ti ti-file me-1 text-muted'></i>" . htmlspecialchars(mb_strimwidth($docName, 0, 35, '...'));
                echo " <span class='badge bg-info text-dark'>" . htmlspecialchars($linkTypeLabels[$ld['link_type']] ?? $ld['link_type']) . "</span></span>";
                echo "<form method='POST' action='{$formUrl}' class='d-inline'>";
                echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
                echo "<input type='hidden' name='action' value='unlink_doc'>";
                echo "<input type='hidden' name='id' value='{$id}'>";
                echo "<input type='hidden' name='doc_id' value='{$ld['did']}'>";
                echo "<button type='submit' class='btn btn-sm btn-outline-danger' onclick='return confirm(\"¿Desvincular?\")'><i class='ti ti-x'></i></button>";
                echo "</form></div>";
            }
        } else {
            echo "<p class='text-muted small'>Sin documentos vinculados.</p>";
        }

        if (!empty($availableDocs)) {
            echo "<form method='POST' action='{$formUrl}' class='row g-2 mt-1'>";
            echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
            echo "<input type='hidden' name='action' value='link_doc'>";
            echo "<input type='hidden' name='id' value='{$id}'>";
            echo "<div class='col-5'><select name='doc_id' class='form-select form-select-sm' required>";
            echo "<option value=''>-- Documento --</option>";
            foreach ($availableDocs as $d) {
                echo "<option value='{$d['id']}'>" . htmlspecialchars(mb_strimwidth($d['original_name'] ?: $d['name'], 0, 40, '...')) . "</option>";
            }
            echo "</select></div>";
            echo "<div class='col-4'><select name='link_type' class='form-select form-select-sm'>";
            foreach ($linkTypeLabels as $val => $lbl) {
                echo "<option value='" . htmlspecialchars($val) . "'>" . htmlspecialchars($lbl) . "</option>";
            }
            echo "</select></div>";
            echo "<div class='col-3'><button type='submit' class='btn btn-sm btn-outline-primary w-100'><i class='ti ti-link'></i></button></div>";
            echo "</form>";
        } else {
            echo "<div class='alert alert-light small mt-2'>No hay documentos disponibles. <a href='{$CFG_GLPI['root_doc']}/plugins/ciscontrols/front/doctree.php'>Subir</a></div>";
        }

        echo "</div></div></div>"; // card-body / card / col
        echo "</div>"; // row
    }

    echo "</div>"; // container

    // JS for live score update
    $levelData = json_encode([
        'colors' => [1 => '#198754', 2 => '#198754', 3 => '#198754', 4 => '#198754', 5 => '#ffc107',
                     6 => '#ffc107', 8 => '#ffc107', 9 => '#ffc107', 10 => '#fd7e14', 12 => '#fd7e14',
                     15 => '#dc3545', 16 => '#dc3545', 20 => '#dc3545', 25 => '#dc3545'],
        'labels' => ['1' => 'Bajo', '2' => 'Bajo', '3' => 'Bajo', '4' => 'Bajo',
                     '5' => 'Medio', '6' => 'Medio', '8' => 'Medio', '9' => 'Medio',
                     '10' => 'Alto', '12' => 'Alto', '15' => 'Crítico',
                     '16' => 'Crítico', '20' => 'Crítico', '25' => 'Crítico'],
    ]);

    echo "<script>
function updateScore() {
    var L = parseInt(document.getElementById('likelihood').value);
    var I = parseInt(document.getElementById('impact').value);
    var score = L * I;
    var level = score >= 15 ? 'Crítico' : (score >= 10 ? 'Alto' : (score >= 5 ? 'Medio' : 'Bajo'));
    var color = score >= 15 ? '#dc3545' : (score >= 10 ? '#fd7e14' : (score >= 5 ? '#ffc107' : '#198754'));
    var card = document.getElementById('scoreCard');
    card.style.background = color;
    document.getElementById('scoreValue').textContent = score;
    document.getElementById('scoreLabel').textContent = level;
}
</script>";
}
