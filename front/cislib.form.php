<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/cislib.form.php - Handler for cislib <-> control and cislib <-> document links
 */

include('../../../inc/includes.php');
Session::checkLoginUser();
Session::checkRight('plugin_ciscontrols', UPDATE);

global $DB, $CFG_GLPI;

$action    = $_POST['action']     ?? '';
$cislibId  = (int)($_POST['cislib_id']  ?? 0);
$controlId = (int)($_POST['control_id'] ?? 0);

$redirect = $CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/cislib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Html::redirect($redirect);
    exit;
}

// Link control to cislib safeguard
if ($action === 'link' && $cislibId > 0 && $controlId > 0) {
    $existing = $DB->request([
        'FROM'  => 'glpi_plugin_ciscontrols_control_cislib',
        'WHERE' => [
            'plugin_ciscontrols_controls_id' => $controlId,
            'plugin_ciscontrols_cislib_id'   => $cislibId,
        ],
    ]);
    if (count($existing) === 0) {
        $DB->insert('glpi_plugin_ciscontrols_control_cislib', [
            'plugin_ciscontrols_controls_id' => $controlId,
            'plugin_ciscontrols_cislib_id'   => $cislibId,
        ]);
    }
    Html::redirect($redirect);
    exit;
}

// Unlink control from cislib safeguard
if ($action === 'unlink' && $cislibId > 0 && $controlId > 0) {
    $DB->delete('glpi_plugin_ciscontrols_control_cislib', [
        'plugin_ciscontrols_controls_id' => $controlId,
        'plugin_ciscontrols_cislib_id'   => $cislibId,
    ]);
    Html::redirect($redirect);
    exit;
}

// Link document to cislib safeguard
if ($action === 'link_doc' && $cislibId > 0) {
    $docId    = (int)($_POST['doc_id']    ?? 0);
    $linkType = $_POST['link_type'] ?? 'referencia';
    if ($docId > 0) {
        $existing = $DB->request([
            'FROM'  => 'glpi_plugin_ciscontrols_cislib_documents',
            'WHERE' => [
                'plugin_ciscontrols_cislib_id'    => $cislibId,
                'plugin_ciscontrols_documents_id' => $docId,
            ],
        ]);
        if (count($existing) === 0) {
            $DB->insert('glpi_plugin_ciscontrols_cislib_documents', [
                'plugin_ciscontrols_cislib_id'    => $cislibId,
                'plugin_ciscontrols_documents_id' => $docId,
                'link_type'                       => $linkType,
            ]);
        }
    }
    Html::redirect($redirect);
    exit;
}

// Unlink document from cislib safeguard
if ($action === 'unlink_doc' && $cislibId > 0) {
    $docId = (int)($_POST['doc_id'] ?? 0);
    if ($docId > 0) {
        $DB->delete('glpi_plugin_ciscontrols_cislib_documents', [
            'plugin_ciscontrols_cislib_id'    => $cislibId,
            'plugin_ciscontrols_documents_id' => $docId,
        ]);
    }
    Html::redirect($redirect);
    exit;
}

Html::redirect($redirect);
exit;
