<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/doctree.form.php - Handler for document tree actions
 */

include('../../../inc/includes.php');
Session::checkLoginUser();
Session::checkRight('plugin_ciscontrols', UPDATE);

global $DB, $CFG_GLPI;

$action   = $_REQUEST['action'] ?? '';
$id       = (int)($_REQUEST['id'] ?? 0);
$redirect = $_REQUEST['redirect'] ?? '';
$baseUrl  = $CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/doctree.php';
$redirectUrl = $baseUrl . ($redirect ? '?' . ltrim($redirect, '?') : '');

// Sanitize redirect
if (!preg_match('/^[?&=\w%-]+$/', $redirect)) {
    $redirectUrl = $baseUrl;
}

// Ensure docs directory exists
$docsDir = GLPI_DOC_DIR . '/_plugins/ciscontrols/docs/';

// --- DOWNLOAD (no CSRF needed for GET download) ---
if ($action === 'download' && $id > 0) {
    $row = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_documents', 'WHERE' => ['id' => $id]]);
    $row = $row->current();
    if (!$row || $row['is_folder']) {
        Html::redirect($baseUrl);
        exit;
    }
    $filePath = $docsDir . $row['file_path'];
    if (!file_exists($filePath)) {
        Session::addMessageAfterRedirect('Archivo no encontrado en el servidor.', false, ERROR);
        Html::redirect($redirectUrl);
        exit;
    }
    $mime = $row['mime_type'] ?: mime_content_type($filePath) ?: 'application/octet-stream';
    $origName = $row['original_name'] ?: basename($filePath);
    $isInline = (str_contains($mime, 'image') || str_contains($mime, 'pdf'));
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: ' . ($isInline ? 'inline' : 'attachment') . '; filename="' . addslashes($origName) . '"');
    header('Cache-Control: private, max-age=3600');
    readfile($filePath);
    exit;
}

// All mutating actions require POST (CSRF validated by GLPI 11 kernel listener)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $action !== 'download') {
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/doctree.php');
    exit;
}

// --- NEW FOLDER ---
if ($action === 'new_folder') {
    $folderName = trim($_POST['folder_name'] ?? '');
    $parentId   = (int)($_POST['parent_id'] ?? 0);
    if ($folderName !== '') {
        $DB->insert('glpi_plugin_ciscontrols_documents', [
            'parent_id'     => $parentId > 0 ? $parentId : null,
            'name'          => $folderName,
            'is_folder'     => 1,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod'      => date('Y-m-d H:i:s'),
        ]);
        Session::addMessageAfterRedirect('Carpeta creada correctamente.', false, INFO);
    }
    Html::redirect($redirectUrl);
    exit;
}

// --- UPLOAD ---
if ($action === 'upload') {
    $folderId = (int)($_POST['folder_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
        Session::addMessageAfterRedirect('Error al subir el archivo.', false, ERROR);
        Html::redirect($redirectUrl);
        exit;
    }

    $allowed = ['jpg','jpeg','png','pdf','txt','zip','rar','7z','xls','xlsx','doc','docx','csv'];
    $origName = $_FILES['upload_file']['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        Session::addMessageAfterRedirect('Tipo de archivo no permitido: ' . htmlspecialchars($ext), false, ERROR);
        Html::redirect($redirectUrl);
        exit;
    }

    // Create docs dir
    if (!is_dir($docsDir)) {
        @mkdir($docsDir, 0775, true);
    }

    // Generate unique filename
    $safeExt = preg_replace('/[^a-z0-9]/', '', $ext);
    $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
    $destPath = $docsDir . $storedName;

    if (!move_uploaded_file($_FILES['upload_file']['tmp_name'], $destPath)) {
        Session::addMessageAfterRedirect('No se pudo guardar el archivo en el servidor.', false, ERROR);
        Html::redirect($redirectUrl);
        exit;
    }

    $mimeType = mime_content_type($destPath) ?: 'application/octet-stream';
    $fileSize = filesize($destPath);

    $DB->insert('glpi_plugin_ciscontrols_documents', [
        'parent_id'     => $folderId > 0 ? $folderId : null,
        'name'          => pathinfo($origName, PATHINFO_FILENAME),
        'is_folder'     => 0,
        'file_path'     => $storedName,
        'original_name' => $origName,
        'file_size'     => $fileSize,
        'mime_type'     => $mimeType,
        'description'   => $description ?: null,
        'uploaded_by'   => Session::getLoginUserID(),
        'date_creation' => date('Y-m-d H:i:s'),
        'date_mod'      => date('Y-m-d H:i:s'),
    ]);
    Session::addMessageAfterRedirect('Archivo subido correctamente.', false, INFO);
    Html::redirect($redirectUrl);
    exit;
}

// --- DELETE (POST only) ---
if ($action === 'delete' && $id > 0) {
    $row = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_documents', 'WHERE' => ['id' => $id]]);
    $row = $row->current();
    if ($row) {
        if ($row['is_folder']) {
            // Check empty
            $childCount = count($DB->request(['FROM' => 'glpi_plugin_ciscontrols_documents', 'WHERE' => ['parent_id' => $id]]));
            if ($childCount > 0) {
                Session::addMessageAfterRedirect('No se puede eliminar una carpeta que no está vacía.', false, ERROR);
                Html::redirect($redirectUrl);
                exit;
            }
        } else {
            // Delete physical file
            $filePath = $docsDir . $row['file_path'];
            if ($row['file_path'] && file_exists($filePath)) {
                @unlink($filePath);
            }
            // Delete control links
            $DB->delete('glpi_plugin_ciscontrols_control_documents', ['plugin_ciscontrols_documents_id' => $id]);
        }
        $DB->delete('glpi_plugin_ciscontrols_documents', ['id' => $id]);
        Session::addMessageAfterRedirect('Eliminado correctamente.', false, INFO);
    }
    Html::redirect($redirectUrl);
    exit;
}

// --- LINK CONTROL ---
if ($action === 'link_control') {
    $docId     = (int)($_POST['doc_id'] ?? 0);
    $controlId = (int)($_POST['control_id'] ?? 0);
    $linkType  = $_POST['link_type'] ?? 'procedimiento';

    if ($docId > 0 && $controlId > 0) {
        $existing = $DB->request([
            'FROM'  => 'glpi_plugin_ciscontrols_control_documents',
            'WHERE' => [
                'plugin_ciscontrols_controls_id'   => $controlId,
                'plugin_ciscontrols_documents_id'  => $docId,
            ],
        ]);
        if (count($existing) === 0) {
            $DB->insert('glpi_plugin_ciscontrols_control_documents', [
                'plugin_ciscontrols_controls_id'   => $controlId,
                'plugin_ciscontrols_documents_id'  => $docId,
                'link_type'                        => $linkType,
            ]);
            Session::addMessageAfterRedirect('Documento vinculado al control correctamente.', false, INFO);
        } else {
            Session::addMessageAfterRedirect('Este documento ya está vinculado a ese control.', false, WARNING);
        }
    }
    Html::redirect($redirectUrl);
    exit;
}

Html::redirect($baseUrl);
exit;
