<?php
/**
 * CIS Controls Plugin - REST API endpoint
 * POST /glpi/plugins/ciscontrols/front/api.php
 *
 * Autenticación: Session-Token header obtenido via /apirest.php/initSession
 *
 * Acciones disponibles (POST param: action):
 *   - complete : marca una ejecución como completada, sube evidencia opcional
 *   - get      : devuelve datos de una ejecución o busca por control
 */

// Restaurar sesión GLPI antes de que includes.php llame session_start()
$sessionToken = $_SERVER['HTTP_SESSION_TOKEN'] ?? '';
if (!empty($sessionToken)) {
    session_id($sessionToken);
}

define('GLPI_ROOT', realpath('../../../'));
include(GLPI_ROOT . '/inc/includes.php');

header('Content-Type: application/json');

// Verificar sesión válida
if (!isset($_SESSION['glpiID']) || (int)$_SESSION['glpiID'] <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Session-Token inválido o expirado']);
    exit;
}

global $DB;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── GET: obtener ejecución por ID o por control_id ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $execId    = (int)($_GET['execution_id'] ?? 0);
    $controlId = (int)($_GET['control_id'] ?? 0);

    if ($execId > 0) {
        $row = $DB->request([
            'FROM'  => 'glpi_plugin_ciscontrols_executions',
            'WHERE' => ['id' => $execId],
        ])->current();
    } elseif ($controlId > 0) {
        $row = $DB->request([
            'FROM'   => 'glpi_plugin_ciscontrols_executions',
            'WHERE'  => [
                'plugin_ciscontrols_controls_id' => $controlId,
                'status'                          => 'pending',
            ],
            'ORDER'  => 'due_date ASC',
            'LIMIT'  => 1,
        ])->current();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Se requiere execution_id o control_id']);
        exit;
    }

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ejecución no encontrada']);
        exit;
    }

    echo json_encode(['success' => true, 'execution' => $row]);
    exit;
}

// ── POST: completar ejecución ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'complete') {
    $execId    = (int)($_POST['execution_id'] ?? 0);
    $controlId = (int)($_POST['control_id'] ?? 0);
    $notes     = trim($_POST['notes'] ?? '');

    // Resolver execution_id desde control_id si no se pasó directamente
    if ($execId <= 0 && $controlId > 0) {
        $row = $DB->request([
            'FROM'   => 'glpi_plugin_ciscontrols_executions',
            'WHERE'  => [
                'plugin_ciscontrols_controls_id' => $controlId,
                'status'                          => 'pending',
            ],
            'ORDER'  => 'due_date ASC',
            'LIMIT'  => 1,
        ])->current();
        if ($row) {
            $execId = (int)$row['id'];
        }
    }

    if ($execId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Se requiere execution_id o control_id con ejecución pendiente']);
        exit;
    }

    $fileData = $_FILES['evidence_file'] ?? [];
    $result   = PluginCiscontrolsExecution::complete($execId, $notes, $fileData);

    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Acción inválida. Use action=complete (POST) o action=get (GET)']);
