<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/evidence.php - Serves evidence files securely
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

$file = $_GET['file'] ?? '';

// Security: only allow safe filename characters (UUID + extension)
if (!preg_match('/^[a-f0-9\-]{36}(\.[a-zA-Z0-9]{1,10})?$/', $file)) {
    http_response_code(400);
    die('Nombre de archivo inválido.');
}

$uploadDir = GLPI_DOC_DIR . '/_plugins/ciscontrols/';
$fullPath  = realpath($uploadDir . $file);

// Ensure the resolved path is still within uploadDir
if ($fullPath === false || strpos($fullPath, realpath($uploadDir)) !== 0) {
    http_response_code(404);
    die('Archivo no encontrado.');
}

if (!file_exists($fullPath)) {
    http_response_code(404);
    die('Archivo no encontrado.');
}

// Look up original filename from DB
global $DB;
$execResult = $DB->request([
    'FROM'  => 'glpi_plugin_ciscontrols_executions',
    'WHERE' => ['evidence_file' => $file],
    'LIMIT' => 1,
]);
$exec = $execResult->current();
$originalName = $exec['evidence_original_name'] ?? $file;

// Determine MIME type
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($fullPath);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, no-cache');
readfile($fullPath);
exit;
