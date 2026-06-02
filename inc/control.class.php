<?php
/**
 * CIS Controls Plugin for GLPI 11
 * inc/control.class.php - Control model
 */

class PluginCiscontrolsControl extends CommonDBTM {

    static $rightname = 'plugin_ciscontrols';

    public static function getTypeName($nb = 0) {
        return 'CIS Control';
    }

    public static function getIcon() {
        return 'ti ti-shield-check';
    }

    public static function getMenuName() {
        return 'CIS Controls';
    }

    public static function getMenuContent() {
        return plugin_ciscontrols_getMenuContent();
    }

    /**
     * Map periodicity string to number of days
     */
    public static function periodicityToDays(string $periodicity): int {
        $map = [
            'Diaria'        => 1,
            'Semanal'       => 7,
            'Mensual'       => 30,
            'Bimestral'     => 60,
            'Trimestral'    => 90,
            'Cada 60 días'  => 60,
            'Cada 60 dias'  => 60,
            'Cada 120 días' => 120,
            'Cada 120 dias' => 120,
            'Semestral'     => 180,
            'Cada 180 días' => 180,
            'Cada 180 dias' => 180,
            'Anual'         => 365,
        ];
        return $map[$periodicity] ?? 30;
    }

    /**
     * Ensure a pending execution exists for an active control.
     * If no pending/overdue execution exists, create one with due_date = today.
     */
    public static function ensurePendingExecution(int $controlId, ?string $dueDate = null): void {
        global $DB;

        $result = $DB->request([
            'FROM'  => 'glpi_plugin_ciscontrols_executions',
            'WHERE' => [
                'plugin_ciscontrols_controls_id' => $controlId,
                'status'                         => ['pending', 'overdue'],
            ],
            'LIMIT' => 1,
        ]);

        if (count($result) === 0) {
            $DB->insert('glpi_plugin_ciscontrols_executions', [
                'plugin_ciscontrols_controls_id' => $controlId,
                'due_date'                       => date('Y-m-d'),
                'status'                         => 'pending',
                'date_creation'                  => date('Y-m-d H:i:s'),
                'date_mod'                       => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Cron task: send reminder emails for executions due within reminder_days_before days
     */
    public static function cronSendReminders(CronTask $task): int {
        global $DB;

        $count = 0;

        // Get all active controls with reminders enabled
        $controls = $DB->request([
            'FROM'  => 'glpi_plugin_ciscontrols_controls',
            'WHERE' => [
                'is_active'        => 1,
                'reminder_enabled' => 1,
            ],
        ]);

        foreach ($controls as $control) {
            if (empty($control['reminder_email'])) {
                continue;
            }

            $daysAhead = (int) $control['reminder_days_before'];
            $targetDate = date('Y-m-d', strtotime("+{$daysAhead} days"));

            // Find pending executions due on target date
            $executions = $DB->request([
                'FROM'  => 'glpi_plugin_ciscontrols_executions',
                'WHERE' => [
                    'plugin_ciscontrols_controls_id' => $control['id'],
                    'status'                         => 'pending',
                    'due_date'                       => $targetDate,
                ],
            ]);

            foreach ($executions as $exec) {
                $subject = $control['reminder_subject']
                    ?: 'Recordatorio CIS: ' . $control['name'];
                $body = $control['reminder_body']
                    ?: '<p>Se acerca el vencimiento del control <strong>' . htmlspecialchars($control['name']) . '</strong>.<br>Fecha de vencimiento: ' . $exec['due_date'] . '</p>';

                try {
                    $mailer = new GLPIMailer();
                    $mailer->Subject = $subject;
                    $mailer->isHTML(true);
                    $mailer->Body = $body;
                    $mailer->addAddress($control['reminder_email']);
                    if ($mailer->send()) {
                        $count++;
                    }
                } catch (\Exception $e) {
                    // Log error but continue
                    Toolbox::logError('CIS Controls reminder error: ' . $e->getMessage());
                }
            }
        }

        $task->addVolume($count);
        return $count > 0 ? 1 : 0;
    }

    /**
     * Display the list of controls
     */
    public static function renderList(): void {
        global $DB, $CFG_GLPI;

        $canWrite = Session::haveRight(self::$rightname, UPDATE);

        echo "<div class='container-fluid mt-3'>";
        echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
        echo "<h2><i class='ti ti-list-check me-2'></i>Controles CIS</h2>";
        if ($canWrite) {
            echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/ciscontrol.form.php' class='btn btn-primary'><i class='ti ti-plus me-1'></i>Nuevo Control</a>";
        }
        echo "</div>";

        // Filter
        $filterStatus = $_GET['filter'] ?? 'all';
        echo "<div class='mb-3'>";
        echo "<a href='?filter=all' class='btn btn-sm " . ($filterStatus === 'all' ? 'btn-primary' : 'btn-outline-secondary') . " me-1'>Todos</a>";
        echo "<a href='?filter=active' class='btn btn-sm " . ($filterStatus === 'active' ? 'btn-success' : 'btn-outline-success') . " me-1'>Activos</a>";
        echo "<a href='?filter=inactive' class='btn btn-sm " . ($filterStatus === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary') . "'>Inactivos</a>";
        echo "</div>";

        $where = [];
        if ($filterStatus === 'active') {
            $where['is_active'] = 1;
        } elseif ($filterStatus === 'inactive') {
            $where['is_active'] = 0;
        }

        $controls = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls', 'WHERE' => $where, 'ORDER' => 'name ASC']);

        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-hover align-middle'>";
        echo "<thead class='table-dark'><tr>";
        echo "<th>Nombre / Actividad</th>";
        echo "<th>Documento</th>";
        echo "<th>Periodicidad</th>";
        echo "<th>Responsable</th>";
        echo "<th>CIS Ver.</th>";
        echo "<th>Estado</th>";
        echo "<th>Recordatorio</th>";
        echo "<th>Acciones</th>";
        echo "</tr></thead><tbody>";

        foreach ($controls as $row) {
            $activeClass = $row['is_active'] ? '' : 'table-secondary text-muted';
            $badgeClass  = $row['is_active'] ? 'bg-success' : 'bg-secondary';
            $badgeText   = $row['is_active'] ? 'Activo' : 'Inactivo';
            $toggleAction = $row['is_active'] ? 'deactivate' : 'activate';
            $toggleLabel  = $row['is_active'] ? 'Desactivar' : 'Activar';
            $toggleClass  = $row['is_active'] ? 'btn-outline-warning' : 'btn-outline-success';

            $reminderBadge = $row['reminder_enabled']
                ? "<span class='badge bg-info'>Email: " . htmlspecialchars($row['reminder_email'] ?? '') . "</span>"
                : "<span class='badge bg-light text-dark'>No</span>";

            echo "<tr class='{$activeClass}'>";
            echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong><br><small class='text-muted'>" . htmlspecialchars($row['activity'] ?? '') . "</small></td>";
            echo "<td><span class='badge bg-primary'>" . htmlspecialchars($row['document'] ?? '') . "</span></td>";
            echo "<td>" . htmlspecialchars($row['periodicity']) . "<br><small class='text-muted'>" . $row['periodicity_days'] . " días</small></td>";
            echo "<td>" . htmlspecialchars($row['responsible'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['cis_version'] ?? '') . "</td>";
            echo "<td><span class='badge {$badgeClass}'>{$badgeText}</span></td>";
            echo "<td>{$reminderBadge}</td>";
            echo "<td>";
            if ($canWrite) {
                $formUrl = $CFG_GLPI['root_doc'] . '/plugins/ciscontrols/front/ciscontrol.form.php';
                $csrfToken = Session::getNewCSRFToken();
                echo "<a href='" . $formUrl . "?id=" . $row['id'] . "' class='btn btn-sm btn-outline-primary me-1' title='Editar'><i class='ti ti-edit'></i></a>";
                echo "<form method='POST' action='" . $formUrl . "' class='d-inline me-1' onsubmit='return confirm(\"¿Confirmar?\")'>";
                echo "<input type='hidden' name='_glpi_csrf_token' value='" . $csrfToken . "'>";
                echo "<input type='hidden' name='action' value='" . $toggleAction . "'>";
                echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                echo "<button type='submit' class='btn btn-sm {$toggleClass}' title='{$toggleLabel}'><i class='ti ti-power'></i></button>";
                echo "</form>";
                echo "<form method='POST' action='" . $formUrl . "' class='d-inline' onsubmit='return confirm(\"¿Eliminar este control y todas sus ejecuciones?\")'>";
                echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
                echo "<input type='hidden' name='action' value='delete'>";
                echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                echo "<button type='submit' class='btn btn-sm btn-outline-danger' title='Eliminar'><i class='ti ti-trash'></i></button>";
                echo "</form>";
            }
            echo "</td>";
            echo "</tr>";
        }

        echo "</tbody></table></div></div>";
    }

    /**
     * Display add/edit form
     */
    public static function renderForm(int $id = 0): void {
        global $DB, $CFG_GLPI;

        $item = [];
        if ($id > 0) {
            $result = $DB->request(['FROM' => 'glpi_plugin_ciscontrols_controls', 'WHERE' => ['id' => $id]]);
            $item = $result->current() ?: [];
        }

        $isEdit = !empty($item);
        $title  = $isEdit ? 'Editar Control CIS' : 'Nuevo Control CIS';

        $periodicities = [
            'Diaria', 'Semanal', 'Mensual', 'Bimestral', 'Trimestral',
            'Cada 60 días', 'Cada 120 días', 'Semestral', 'Cada 180 días', 'Anual',
        ];

        echo "<div class='container-fluid mt-3'>";
        echo "<h2><i class='ti ti-shield-check me-2'></i>{$title}</h2>";
        echo "<form method='POST' action='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/ciscontrol.form.php'>";
        echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
        if ($isEdit) {
            echo "<input type='hidden' name='id' value='{$id}'>";
        }

        echo "<div class='card'><div class='card-body'>";
        echo "<div class='row g-3'>";

        // Name
        echo "<div class='col-md-6'>";
        echo "<label class='form-label'>Nombre *</label>";
        echo "<input type='text' name='name' class='form-control' required value='" . htmlspecialchars($item['name'] ?? '') . "'>";
        echo "</div>";

        // Document
        echo "<div class='col-md-3'>";
        echo "<label class='form-label'>Documento</label>";
        echo "<input type='text' name='document' class='form-control' value='" . htmlspecialchars($item['document'] ?? '') . "'>";
        echo "</div>";

        // CIS Version
        echo "<div class='col-md-3'>";
        echo "<label class='form-label'>Versión CIS</label>";
        echo "<input type='text' name='cis_version' class='form-control' value='" . htmlspecialchars($item['cis_version'] ?? '') . "'>";
        echo "</div>";

        // Periodicity
        echo "<div class='col-md-4'>";
        echo "<label class='form-label'>Periodicidad *</label>";
        echo "<select name='periodicity' id='periodicity_select' class='form-select' required onchange='updatePeriodDays()'>";
        foreach ($periodicities as $p) {
            $sel = (isset($item['periodicity']) && $item['periodicity'] === $p) ? " selected" : "";
            echo "<option value='" . htmlspecialchars($p) . "'{$sel}>" . htmlspecialchars($p) . "</option>";
        }
        echo "</select></div>";

        // Periodicity days
        echo "<div class='col-md-2'>";
        echo "<label class='form-label'>Días</label>";
        echo "<input type='number' name='periodicity_days' id='periodicity_days' class='form-control' min='1' value='" . (int)($item['periodicity_days'] ?? 30) . "'>";
        echo "</div>";

        // First due date
        echo "<div class='col-md-3'>";
        echo "<label class='form-label'>Fecha 1er vencimiento</label>";
        $fdd = !empty($item['first_due_date']) ? $item['first_due_date'] : date('Y-m-d');
        echo "<input type='date' name='first_due_date' class='form-control' value='" . htmlspecialchars($fdd) . "'>";
        echo "<small class='text-muted'>Primera vez que vence este control</small>";
        echo "</div>";

        // Responsible
        echo "<div class='col-md-6'>";
        echo "<label class='form-label'>Responsable</label>";
        echo "<input type='text' name='responsible' class='form-control' value='" . htmlspecialchars($item['responsible'] ?? '') . "'>";
        echo "</div>";

        // Activity
        echo "<div class='col-md-12'>";
        echo "<label class='form-label'>Actividad</label>";
        echo "<textarea name='activity' class='form-control' rows='3'>" . htmlspecialchars($item['activity'] ?? '') . "</textarea>";
        echo "</div>";

        // Evidence type
        echo "<div class='col-md-12'>";
        echo "<label class='form-label'>Tipo de Evidencia</label>";
        echo "<textarea name='evidence_type' class='form-control' rows='2'>" . htmlspecialchars($item['evidence_type'] ?? '') . "</textarea>";
        echo "</div>";

        // Is active
        echo "<div class='col-md-3'>";
        echo "<div class='form-check mt-4'>";
        $checked = (!$isEdit || $item['is_active']) ? ' checked' : '';
        echo "<input type='checkbox' name='is_active' id='is_active' class='form-check-input' value='1'{$checked}>";
        echo "<label class='form-check-label' for='is_active'>Activo</label>";
        echo "</div></div>";

        // Reminder section
        echo "<div class='col-12'><hr><h5>Recordatorio por Email</h5></div>";

        echo "<div class='col-md-3'>";
        echo "<div class='form-check'>";
        $remChecked = (!empty($item['reminder_enabled'])) ? ' checked' : '';
        echo "<input type='checkbox' name='reminder_enabled' id='reminder_enabled' class='form-check-input' value='1'{$remChecked} onchange='toggleReminder()'>";
        echo "<label class='form-check-label' for='reminder_enabled'>Habilitar recordatorio</label>";
        echo "</div></div>";

        echo "<div class='col-md-3'>";
        echo "<label class='form-label'>Días antes del vencimiento</label>";
        echo "<input type='number' name='reminder_days_before' class='form-control' min='1' value='" . (int)($item['reminder_days_before'] ?? 3) . "'>";
        echo "</div>";

        echo "<div class='col-md-6'>";
        echo "<label class='form-label'>Email destinatario</label>";
        echo "<input type='email' name='reminder_email' class='form-control' value='" . htmlspecialchars($item['reminder_email'] ?? '') . "'>";
        echo "</div>";

        echo "<div class='col-md-12'>";
        echo "<label class='form-label'>Asunto del email</label>";
        echo "<input type='text' name='reminder_subject' class='form-control' value='" . htmlspecialchars($item['reminder_subject'] ?? '') . "'>";
        echo "</div>";

        echo "<div class='col-md-12'>";
        echo "<label class='form-label'>Cuerpo del email (HTML permitido)</label>";
        echo "<textarea name='reminder_body' class='form-control' rows='5'>" . htmlspecialchars($item['reminder_body'] ?? '') . "</textarea>";
        echo "</div>";

        echo "</div>"; // row
        echo "</div></div>"; // card

        echo "<div class='mt-3'>";
        echo "<button type='submit' name='action' value='" . ($isEdit ? 'update' : 'add') . "' class='btn btn-primary me-2'><i class='ti ti-device-floppy me-1'></i>Guardar</button>";
        echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ciscontrols/front/ciscontrol.php' class='btn btn-secondary'>Cancelar</a>";
        echo "</div>";
        echo "</form>";
        echo "</div>";

        // JS for periodicity days auto-fill and reminder toggle
        $periodicityMap = json_encode([
            'Diaria'        => 1,
            'Semanal'       => 7,
            'Mensual'       => 30,
            'Bimestral'     => 60,
            'Trimestral'    => 90,
            'Cada 60 días'  => 60,
            'Cada 120 días' => 120,
            'Semestral'     => 180,
            'Cada 180 días' => 180,
            'Anual'         => 365,
        ]);
        echo "<script>
var periodicityMap = {$periodicityMap};
function updatePeriodDays() {
    var sel = document.getElementById('periodicity_select').value;
    if (periodicityMap[sel]) {
        document.getElementById('periodicity_days').value = periodicityMap[sel];
    }
}
function toggleReminder() {
    // visual only, fields are always submitted
}
</script>";
    }
}
