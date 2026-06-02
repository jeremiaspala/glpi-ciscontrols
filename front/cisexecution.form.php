<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/cisexecution.form.php - Execution completion form & handler
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

global $DB, $CFG_GLPI;

$action = $_POST['action'] ?? '';
$id     = (int)($_REQUEST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'complete' && $id > 0) {

    $notes    = $_POST['notes'] ?? '';
    $fileData = $_FILES['evidence_file'] ?? [];

    $result = PluginCiscontrolsExecution::complete($id, $notes, $fileData);

    if ($result['success']) {
        Session::addMessageAfterRedirect($result['message'], true, INFO);
    } else {
        Session::addMessageAfterRedirect('Error: ' . $result['message'], true, ERROR);
    }

    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/cisexecution.php');
    exit;
}

if ($id <= 0) {
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/cisexecution.php');
    exit;
}

Html::header('CIS Controls - Ejecución', $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');

PluginCiscontrolsExecution::renderForm($id);

Html::footer();
