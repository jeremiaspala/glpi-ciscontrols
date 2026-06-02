<?php
/**
 * CIS Controls Plugin for GLPI 11
 * inc/execution.class.php - Execution model
 */

class PluginCiscontrolsExecution extends CommonDBTM {

    static $rightname = 'plugin_ciscontrols';

    public static function getTypeName($nb = 0) {
        return 'Ejecución CIS';
    }

    /**
     * Cron: mark overdue any pending executions whose due_date < today
     */
    public static function cronMarkOverdue(CronTask $task): int {
        global $DB;

        $today = date('Y-m-d');
        $res = $DB->update(
            'glpi_plugin_ciscontrols_executions',
            ['status' => 'overdue', 'date_mod' => date('Y-m-d H:i:s')],
            [
                'status'   => 'pending',
                ['due_date' => ['<', $today]],
            ]
        );

        $count = $DB->affectedRows();
        $task->addVolume($count);
        return $count > 0 ? 1 : 0;
    }

    /**
     * Mark an execution as completed, upload evidence file, and create next execution.
     *
     * @param int    $execId    Execution ID
     * @param string $notes     Notes from the user
     * @param array  $fileData  $_FILES entry (can be empty)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function complete(int $execId, string $notes, array $fileData = []): array {
        global $DB;

        $result = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_executions', 'WHERE' => ['id' => $execId]]);
        $exec = $result->current();
        if (!$exec) {
            return ['success' => false, 'message' => 'Ejecución no encontrada'];
        }

        $controlResult = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls', 'WHERE' => ['id' => $exec['plugin_ciscontrols_controls_id']]]);
        $control = $controlResult->current();
        if (!$control) {
            return ['success' => false, 'message' => 'Control no encontrado'];
        }

        // Handle file upload
        $evidenceFile  = null;
        $evidenceName  = null;
        if (!empty($fileData['name']) && $fileData['error'] === UPLOAD_ERR_OK) {
            // Validar tipo de archivo
            $allowed_ext = ['jpg','jpeg','png','gif','webp','bmp','pdf','txt','zip','rar','7z'];
            $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) {
                return ['success' => false, 'message' => 'Tipo de archivo no permitido. Use: ' . implode(', ', $allowed_ext)];
            }
            // Validar tamaño (50 MB)
            if ($fileData['size'] > 50 * 1024 * 1024) {
                return ['success' => false, 'message' => 'Archivo demasiado grande (máx 50 MB)'];
            }
            $uploadDir = GLPI_DOC_DIR . '/_plugins/ciscontrols/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $uuid = self::generateUUID();
            $ext  = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            $dest = $uploadDir . $uuid . ($ext ? '.' . $ext : '');
            if (move_uploaded_file($fileData['tmp_name'], $dest)) {
                $evidenceFile = $uuid . ($ext ? '.' . $ext : '');
                $evidenceName = $fileData['name'];
            } else {
                return ['success' => false, 'message' => 'No se pudo guardar el archivo de evidencia'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'status'          => 'completed',
            'completion_date' => $now,
            'completed_by'    => Session::getLoginUserID(),
            'notes'           => $notes,
            'date_mod'        => $now,
        ];
        if ($evidenceFile !== null) {
            $updateData['evidence_file']          = $evidenceFile;
            $updateData['evidence_original_name'] = $evidenceName;
        }

        $DB->update('glpi_plugin_ciscontrols_executions', $updateData, ['id' => $execId]);

        // Create next execution
        $nextDue = date('Y-m-d', strtotime($now . ' +' . (int)$control['periodicity_days'] . ' days'));
        $DB->insert('glpi_plugin_ciscontrols_executions', [
            'plugin_ciscontrols_controls_id' => $control['id'],
            'due_date'                       => $nextDue,
            'status'                         => 'pending',
            'date_creation'                  => $now,
            'date_mod'                       => $now,
        ]);

        return ['success' => true, 'message' => 'Ejecución completada. Próxima ejecución: ' . $nextDue];
    }

    private static function generateUUID(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Display list of executions with filters
     */
    public static function renderList(): void {
        global $DB, $CFG_GLPI;

        $filterStatus   = $_GET['status']     ?? 'all';
        $filterControl  = (int)($_GET['control'] ?? 0);

        echo "<div class='container-fluid mt-3'>";
        echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
        echo "<h2><i class='ti ti-run me-2'></i>Ejecuciones CIS</h2>";
        echo "</div>";

        // Status filter tabs
        echo "<div class='mb-3'>";
        foreach (['all' => 'Todas', 'pending' => 'Pendientes', 'overdue' => 'Vencidas', 'completed' => 'Completadas'] as $val => $label) {
            $active = ($filterStatus === $val) ? 'btn-primary' : 'btn-outline-secondary';
            echo "<a href='?status={$val}&control={$filterControl}' class='btn btn-sm {$active} me-1'>{$label}</a>";
        }
        echo "</div>";

        $where = [];
        if ($filterStatus !== 'all') {
            $where['status'] = $filterStatus;
        }
        if ($filterControl > 0) {
            $where['plugin_ciscontrols_controls_id'] = $filterControl;
        }

        $executions = $DB->request([
            'FROM'  => 'glpi_plugin_ciscontrols_executions',
            'WHERE' => $where,
            'ORDER' => 'due_date ASC',
        ]);

        // Pre-fetch controls
        $controlsMap = [];
        $allControls = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls']);
        foreach ($allControls as $c) {
            $controlsMap[$c['id']] = $c;
        }

        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-hover align-middle'>";
        echo "<thead class='table-dark'><tr>";
        echo "<th>Control</th>";
        echo "<th>Documento</th>";
        echo "<th>Vencimiento</th>";
        echo "<th>Estado</th>";
        echo "<th>Completada</th>";
        echo "<th>Completado por</th>";
        echo "<th>Evidencia</th>";
        echo "<th>Acciones</th>";
        echo "</tr></thead><tbody>";

        $today = date('Y-m-d');

        foreach ($executions as $row) {
            $ctrl = $controlsMap[$row['plugin_ciscontrols_controls_id']] ?? [];
            $ctrlName = $ctrl['name'] ?? 'N/A';
            $ctrlDoc  = $ctrl['document'] ?? '';

            switch ($row['status']) {
                case 'pending':
                    $badgeClass = ($row['due_date'] < $today) ? 'bg-danger' : 'bg-warning text-dark';
                    $badgeText  = ($row['due_date'] < $today) ? 'Vencida (pendiente)' : 'Pendiente';
                    break;
                case 'overdue':
                    $badgeClass = 'bg-danger';
                    $badgeText  = 'Vencida';
                    break;
                case 'completed':
                    $badgeClass = 'bg-success';
                    $badgeText  = 'Completada';
                    break;
                default:
                    $badgeClass = 'bg-secondary';
                    $badgeText  = htmlspecialchars($row['status']);
            }

            $completedBy = '';
            if ($row['completed_by']) {
                $userRes = $DB->request(['FROM' => 'glpi_users', 'WHERE' => ['id' => $row['completed_by']]]);
                $u = $userRes->current();
                if ($u) {
                    $completedBy = htmlspecialchars($u['firstname'] . ' ' . $u['realname']);
                }
            }

            $evidenceLink = '';
            if (!empty($row['evidence_file'])) {
                $evidenceLink = "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/evidence.php?file=" . urlencode($row['evidence_file']) . "' class='btn btn-sm btn-outline-info' target='_blank'><i class='ti ti-download me-1'></i>" . htmlspecialchars($row['evidence_original_name'] ?? 'Archivo') . "</a>";
            }

            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($ctrlName) . "</strong></td>";
            echo "<td><span class='badge bg-primary'>" . htmlspecialchars($ctrlDoc) . "</span></td>";
            echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
            echo "<td><span class='badge {$badgeClass}'>{$badgeText}</span></td>";
            echo "<td>" . ($row['completion_date'] ? htmlspecialchars(substr($row['completion_date'], 0, 10)) : '-') . "</td>";
            echo "<td>" . ($completedBy ?: '-') . "</td>";
            echo "<td>" . $evidenceLink . "</td>";
            echo "<td>";
            if (in_array($row['status'], ['pending', 'overdue'])) {
                echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/cisexecution.form.php?id=" . $row['id'] . "' class='btn btn-sm btn-success'><i class='ti ti-check me-1'></i>Completar</a>";
            } else {
                echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/cisexecution.form.php?id=" . $row['id'] . "' class='btn btn-sm btn-outline-secondary'><i class='ti ti-eye me-1'></i>Ver</a>";
            }
            echo "</td>";
            echo "</tr>";
        }

        echo "</tbody></table></div></div>";
    }

    /**
     * Display completion form for an execution
     */
    public static function renderForm(int $id): void {
        global $DB, $CFG_GLPI;

        $result = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_executions', 'WHERE' => ['id' => $id]]);
        $exec = $result->current();
        if (!$exec) {
            echo "<div class='alert alert-danger'>Ejecución no encontrada.</div>";
            return;
        }

        $controlResult = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls', 'WHERE' => ['id' => $exec['plugin_ciscontrols_controls_id']]]);
        $control = $controlResult->current();

        $isCompleted = ($exec['status'] === 'completed');

        echo "<div class='container-fluid mt-3'>";
        echo "<h2><i class='ti ti-clipboard-check me-2'></i>" . ($isCompleted ? 'Ver Ejecución' : 'Completar Ejecución') . "</h2>";

        // Control info card
        echo "<div class='card mb-3'><div class='card-header bg-light'><strong>Control: " . htmlspecialchars($control['name'] ?? 'N/A') . "</strong></div>";
        echo "<div class='card-body'>";
        echo "<div class='row'>";
        echo "<div class='col-md-3'><strong>Documento:</strong> " . htmlspecialchars($control['document'] ?? '') . "</div>";
        echo "<div class='col-md-3'><strong>Periodicidad:</strong> " . htmlspecialchars($control['periodicity'] ?? '') . "</div>";
        echo "<div class='col-md-3'><strong>Responsable:</strong> " . htmlspecialchars($control['responsible'] ?? '') . "</div>";
        echo "<div class='col-md-3'><strong>CIS:</strong> " . htmlspecialchars($control['cis_version'] ?? '') . "</div>";
        echo "<div class='col-12 mt-2'><strong>Actividad:</strong> " . htmlspecialchars($control['activity'] ?? '') . "</div>";
        echo "<div class='col-12 mt-1'><strong>Evidencia esperada:</strong> " . htmlspecialchars($control['evidence_type'] ?? '') . "</div>";
        echo "</div></div></div>";

        // Execution info
        $statusBadge = match($exec['status']) {
            'pending'   => "<span class='badge bg-warning text-dark'>Pendiente</span>",
            'overdue'   => "<span class='badge bg-danger'>Vencida</span>",
            'completed' => "<span class='badge bg-success'>Completada</span>",
            default     => "<span class='badge bg-secondary'>" . htmlspecialchars($exec['status']) . "</span>",
        };

        echo "<div class='card mb-3'><div class='card-body'>";
        echo "<div class='row'>";
        echo "<div class='col-md-3'><strong>Vencimiento:</strong> " . htmlspecialchars($exec['due_date']) . "</div>";
        echo "<div class='col-md-3'><strong>Estado:</strong> {$statusBadge}</div>";
        if ($exec['completion_date']) {
            echo "<div class='col-md-3'><strong>Completada:</strong> " . htmlspecialchars(substr($exec['completion_date'], 0, 10)) . "</div>";
        }
        echo "</div>";

        if ($exec['notes']) {
            echo "<div class='mt-2'><strong>Notas:</strong><br>" . nl2br(htmlspecialchars($exec['notes'])) . "</div>";
        }

        if (!empty($exec['evidence_file'])) {
            echo "<div class='mt-2'><strong>Evidencia:</strong> <a href='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/evidence.php?file=" . urlencode($exec['evidence_file']) . "' target='_blank' class='btn btn-sm btn-outline-info'><i class='ti ti-download me-1'></i>" . htmlspecialchars($exec['evidence_original_name'] ?? 'Descargar') . "</a></div>";
        }

        echo "</div></div>";

        if (!$isCompleted) {
            echo "<form method='POST' action='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/cisexecution.form.php' enctype='multipart/form-data'>";
            echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
            echo "<input type='hidden' name='id' value='{$id}'>";
            echo "<div class='card'><div class='card-header'>Registrar Completado</div><div class='card-body'>";
            echo "<div class='mb-3'>";
            echo "<label class='form-label'>Notas / Observaciones</label>";
            echo "<textarea name='notes' class='form-control' rows='4' placeholder='Describa lo realizado...'></textarea>";
            echo "</div>";
            echo "<div class='mb-3'>";
            echo "<label class='form-label'>Archivo de Evidencia (opcional)</label>";
            echo "<input type='file' name='evidence_file' class='form-control' accept='.jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf,.txt,.zip,.rar,.7z'>";
            echo "<div class='form-text'>Formatos: imágenes (JPG, PNG, GIF, BMP, WEBP), PDF, TXT, ZIP, RAR, 7Z. Máx 50 MB.</div>";
            echo "</div>";
            echo "</div></div>";
            echo "<div class='mt-3'>";
            echo "<button type='submit' name='action' value='complete' class='btn btn-success me-2'><i class='ti ti-check me-1'></i>Marcar como Completada</button>";
            echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/cisexecution.php' class='btn btn-secondary'>Cancelar</a>";
            echo "</div>";
            echo "</form>";
        } else {
            echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/cisexecution.php' class='btn btn-secondary'><i class='ti ti-arrow-left me-1'></i>Volver</a>";
        }

        echo "</div>";
    }
}
