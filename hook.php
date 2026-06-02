<?php
/**
 * CIS Controls Plugin for GLPI 11
 * hook.php - Install / Uninstall hooks
 */

function plugin_ciscontrols_install() {
    global $DB;

    // Controls table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_controls')) {
        $query = "CREATE TABLE `glpi_plugin_ciscontrols_controls` (
          `id` int NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL DEFAULT '',
          `document` varchar(50) DEFAULT NULL,
          `periodicity` varchar(50) NOT NULL DEFAULT '',
          `periodicity_days` int NOT NULL DEFAULT 30,
          `activity` text,
          `evidence_type` text,
          `responsible` varchar(255) DEFAULT NULL,
          `cis_version` varchar(50) DEFAULT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `reminder_enabled` tinyint(1) NOT NULL DEFAULT 0,
          `reminder_days_before` int NOT NULL DEFAULT 3,
          `reminder_email` varchar(255) DEFAULT NULL,
          `reminder_subject` varchar(500) DEFAULT NULL,
          `reminder_body` longtext,
          `date_creation` timestamp NULL DEFAULT NULL,
          `date_mod` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $DB->queryOrDie($query, "Error creating ciscontrols controls table");
    }

    // Executions table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_executions')) {
        $query = "CREATE TABLE `glpi_plugin_ciscontrols_executions` (
          `id` int NOT NULL AUTO_INCREMENT,
          `plugin_ciscontrols_controls_id` int NOT NULL,
          `due_date` date NOT NULL,
          `status` varchar(20) NOT NULL DEFAULT 'pending',
          `completion_date` timestamp NULL DEFAULT NULL,
          `completed_by` int DEFAULT NULL,
          `notes` text,
          `evidence_file` varchar(500) DEFAULT NULL,
          `evidence_original_name` varchar(500) DEFAULT NULL,
          `date_creation` timestamp NULL DEFAULT NULL,
          `date_mod` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `fk_control` (`plugin_ciscontrols_controls_id`),
          KEY `idx_due_date` (`due_date`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $DB->queryOrDie($query, "Error creating ciscontrols executions table");
    }

    // Config table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_config')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_config` (
              `id` int NOT NULL AUTO_INCREMENT,
              `config_key` varchar(100) NOT NULL,
              `config_value` text,
              PRIMARY KEY (`id`),
              UNIQUE KEY `config_key` (`config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols config table"
        );
        // Default values
        $defaults = [
            ['ig_level', '1'],
            ['company_name', 'Mi Organización'],
            ['compliance_email', 'compliance@example.com'],
        ];
        foreach ($defaults as $d) {
            $DB->insert('glpi_plugin_ciscontrols_config', ['config_key' => $d[0], 'config_value' => $d[1]]);
        }
    }

    // CIS Library table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_cislib')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_cislib` (
              `id` int NOT NULL AUTO_INCREMENT,
              `control_number` varchar(10) NOT NULL,
              `control_group` varchar(150) NOT NULL,
              `safeguard_title` varchar(500) NOT NULL,
              `safeguard_description` text,
              `ig_level` tinyint NOT NULL DEFAULT 1,
              `asset_type` varchar(100) DEFAULT NULL,
              `security_function` varchar(50) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `ig_level` (`ig_level`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols cislib table"
        );
    }

    // Documents table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_documents')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_documents` (
              `id` int NOT NULL AUTO_INCREMENT,
              `parent_id` int DEFAULT NULL,
              `name` varchar(255) NOT NULL,
              `is_folder` tinyint(1) NOT NULL DEFAULT 0,
              `file_path` varchar(500) DEFAULT NULL,
              `original_name` varchar(500) DEFAULT NULL,
              `file_size` int DEFAULT NULL,
              `mime_type` varchar(100) DEFAULT NULL,
              `description` text DEFAULT NULL,
              `uploaded_by` int DEFAULT NULL,
              `date_creation` timestamp NULL DEFAULT NULL,
              `date_mod` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `parent_id` (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols documents table"
        );
        // Default root folders
        $folders = ['Políticas y Procedimientos', 'Registros de Control', 'Evidencias de Auditoría', 'Contratos y Proveedores'];
        foreach ($folders as $fn) {
            $DB->insert('glpi_plugin_ciscontrols_documents', ['name' => $fn, 'is_folder' => 1, 'date_creation' => date('Y-m-d H:i:s')]);
        }
    }

    // Control <-> CIS Library link table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_control_cislib')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_control_cislib` (
              `id` int NOT NULL AUTO_INCREMENT,
              `plugin_ciscontrols_controls_id` int NOT NULL,
              `plugin_ciscontrols_cislib_id` int NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_link` (`plugin_ciscontrols_controls_id`,`plugin_ciscontrols_cislib_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols control_cislib table"
        );
    }

    // Control <-> Documents link table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_control_documents')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_control_documents` (
              `id` int NOT NULL AUTO_INCREMENT,
              `plugin_ciscontrols_controls_id` int NOT NULL,
              `plugin_ciscontrols_documents_id` int NOT NULL,
              `link_type` varchar(50) DEFAULT 'procedimiento',
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_link` (`plugin_ciscontrols_controls_id`,`plugin_ciscontrols_documents_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols control_documents table"
        );
    }

    // CIS Library <-> Documents link table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_cislib_documents')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_cislib_documents` (
              `id` int NOT NULL AUTO_INCREMENT,
              `plugin_ciscontrols_cislib_id` int NOT NULL,
              `plugin_ciscontrols_documents_id` int NOT NULL,
              `link_type` varchar(50) DEFAULT 'referencia',
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_link` (`plugin_ciscontrols_cislib_id`,`plugin_ciscontrols_documents_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols cislib_documents table"
        );
    }

    // Risks table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_risks')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_risks` (
              `id` int NOT NULL AUTO_INCREMENT,
              `name` varchar(500) NOT NULL DEFAULT '',
              `category` varchar(50) DEFAULT 'otro',
              `description` text,
              `asset` varchar(255) DEFAULT NULL,
              `threat` text,
              `vulnerability` text,
              `likelihood` tinyint NOT NULL DEFAULT 1,
              `impact` tinyint NOT NULL DEFAULT 1,
              `risk_score` tinyint NOT NULL DEFAULT 1,
              `treatment` varchar(20) DEFAULT 'mitigar',
              `treatment_notes` text,
              `owner` varchar(255) DEFAULT NULL,
              `status` varchar(20) DEFAULT 'abierto',
              `review_date` date DEFAULT NULL,
              `date_creation` timestamp NULL DEFAULT NULL,
              `date_mod` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_score` (`risk_score`),
              KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols risks table"
        );
    }

    // Risk <-> Controls link table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_risk_controls')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_risk_controls` (
              `id` int NOT NULL AUTO_INCREMENT,
              `plugin_ciscontrols_risks_id` int NOT NULL,
              `plugin_ciscontrols_controls_id` int NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_link` (`plugin_ciscontrols_risks_id`,`plugin_ciscontrols_controls_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols risk_controls table"
        );
    }

    // Risk <-> Documents link table
    if (!$DB->tableExists('glpi_plugin_ciscontrols_risk_documents')) {
        $DB->queryOrDie(
            "CREATE TABLE `glpi_plugin_ciscontrols_risk_documents` (
              `id` int NOT NULL AUTO_INCREMENT,
              `plugin_ciscontrols_risks_id` int NOT NULL,
              `plugin_ciscontrols_documents_id` int NOT NULL,
              `link_type` varchar(50) DEFAULT 'evidencia',
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_link` (`plugin_ciscontrols_risks_id`,`plugin_ciscontrols_documents_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "Error creating ciscontrols risk_documents table"
        );
    }

    // Populate CIS Controls v8.1 library
    if ($DB->tableExists('glpi_plugin_ciscontrols_cislib') &&
        countElementsInTable('glpi_plugin_ciscontrols_cislib') === 0) {
        $cislib_data = [
            ['1.1', 'Inventario y Control de Activos Empresariales', 'Establecer y mantener inventario detallado de activos empresariales', null, 1, 'Devices', 'Identify'],
            ['1.2', 'Inventario y Control de Activos Empresariales', 'Tratar activos no autorizados', null, 1, 'Devices', 'Respond'],
            ['2.1', 'Inventario y Control de Activos de Software', 'Establecer y mantener inventario de software', null, 1, 'Applications', 'Identify'],
            ['2.2', 'Inventario y Control de Activos de Software', 'Asegurar que el software esté en lista de permitidos', null, 1, 'Applications', 'Protect'],
            ['2.3', 'Inventario y Control de Activos de Software', 'Tratar software no autorizado', null, 1, 'Applications', 'Respond'],
            ['3.1', 'Protección de Datos', 'Establecer y mantener proceso de gestión de datos', null, 1, 'Data', 'Identify'],
            ['3.3', 'Protección de Datos', 'Configurar listas de control de acceso a datos', null, 1, 'Data', 'Protect'],
            ['3.5', 'Protección de Datos', 'Eliminar datos de forma segura', null, 1, 'Data', 'Protect'],
            ['3.7', 'Protección de Datos', 'Establecer y mantener proceso de gestión de datos de copia de seguridad', null, 1, 'Data', 'Recover'],
            ['4.1', 'Configuración Segura de Activos y Software', 'Establecer y mantener proceso de configuración segura', null, 1, 'Devices', 'Protect'],
            ['4.2', 'Configuración Segura de Activos y Software', 'Establecer y mantener proceso de configuración segura para software', null, 1, 'Applications', 'Protect'],
            ['4.3', 'Configuración Segura de Activos y Software', 'Configurar el bloqueo automático de sesión en activos empresariales', null, 1, 'Users', 'Protect'],
            ['4.4', 'Configuración Segura de Activos y Software', 'Implementar y gestionar un cortafuegos en servidores', null, 1, 'Network', 'Protect'],
            ['4.5', 'Configuración Segura de Activos y Software', 'Implementar y gestionar un cortafuegos en dispositivos de usuario final', null, 1, 'Devices', 'Protect'],
            ['4.7', 'Configuración Segura de Activos y Software', 'Gestionar cuentas de usuario predeterminadas', null, 1, 'Users', 'Protect'],
            ['5.1', 'Gestión de Cuentas', 'Establecer y mantener inventario de cuentas', null, 1, 'Users', 'Identify'],
            ['5.2', 'Gestión de Cuentas', 'Usar contraseñas únicas', null, 1, 'Users', 'Protect'],
            ['5.3', 'Gestión de Cuentas', 'Deshabilitar cuentas de usuario inactivas', null, 1, 'Users', 'Protect'],
            ['5.4', 'Gestión de Cuentas', 'Restringir privilegios de administrador a cuentas de administrador dedicadas', null, 1, 'Users', 'Protect'],
            ['6.1', 'Gestión de Control de Acceso', 'Establecer una política de control de acceso', null, 1, 'Data', 'Protect'],
            ['7.1', 'Gestión Continua de Vulnerabilidades', 'Establecer y mantener proceso de gestión de vulnerabilidades', null, 1, 'Applications', 'Protect'],
            ['7.2', 'Gestión Continua de Vulnerabilidades', 'Establecer y mantener sistema de remediación', null, 1, 'Applications', 'Respond'],
            ['8.1', 'Gestión de Registros de Auditoría', 'Establecer y mantener proceso de gestión de registros de auditoría', null, 1, 'Devices', 'Protect'],
            ['8.2', 'Gestión de Registros de Auditoría', 'Recopilar registros de auditoría', null, 1, 'Devices', 'Detect'],
            ['9.1', 'Protecciones de Correo Electrónico y Navegadores', 'Uso de Navegadores y Clientes de Correo Completamente Soportados', 'Utilizar únicamente versiones de navegadores y clientes de correo que reciban soporte activo de seguridad del proveedor.', 1, 'Dispositivos del usuario final', 'Protect'],
            ['9.2', 'Protecciones de Correo Electrónico y Navegadores', 'Usar Servicios de Filtrado DNS', 'Usar servicios de filtrado DNS en toda la red empresarial para bloquear acceso a dominios maliciosos conocidos.', 1, 'Redes', 'Protect'],
            ['10.1', 'Defensas Contra Malware', 'Implementar y Mantener Software Anti-Malware', 'Implementar y mantener software anti-malware en todos los activos empresariales.', 1, 'Dispositivos del usuario final', 'Protect'],
            ['10.2', 'Defensas Contra Malware', 'Configurar Actualizaciones Automáticas de Firmas Anti-Malware', 'Configurar las actualizaciones automáticas de firmas de software anti-malware en todos los activos empresariales.', 1, 'Dispositivos del usuario final', 'Protect'],
            ['10.3', 'Defensas Contra Malware', 'Deshabilitar Ejecución Automática y Reproducción Automática para Medios Extraíbles', 'Deshabilitar las funciones de ejecución automática y reproducción automática para medios extraíbles.', 1, 'Dispositivos del usuario final', 'Protect'],
            ['11.1', 'Recuperación de Datos', 'Establecer y mantener proceso de recuperación de datos', null, 1, 'Data', 'Recover'],
            ['11.2', 'Recuperación de Datos', 'Realizar copias de seguridad automatizadas', null, 1, 'Data', 'Recover'],
            ['11.3', 'Recuperación de Datos', 'Proteger los datos de recuperación', null, 1, 'Data', 'Recover'],
            ['12.1', 'Gestión de Infraestructura de Red', 'Asegurar la infraestructura de red', null, 1, 'Network', 'Protect'],
            ['15.1', 'Gestión de Proveedores de Servicios', 'Establecer y mantener inventario de proveedores de servicios', null, 1, 'Data', 'Identify'],
            ['15.2', 'Gestión de Proveedores de Servicios', 'Establecer y mantener política de gestión de proveedores', null, 1, 'Data', 'Protect'],
            ['17.1', 'Gestión de Respuesta a Incidentes', 'Designar personal para gestión de incidentes', null, 1, 'Users', 'Respond'],
            ['17.2', 'Gestión de Respuesta a Incidentes', 'Establecer y mantener proceso de respuesta a incidentes de seguridad', null, 1, 'Users', 'Respond'],
            ['17.3', 'Gestión de Respuesta a Incidentes', 'Establecer y mantener proceso de comunicación de incidentes de seguridad', null, 1, 'Users', 'Respond'],
            ['1.3', 'Inventario y Control de Activos Empresariales', 'Utilizar herramienta de descubrimiento activo de activos', null, 2, 'Devices', 'Detect'],
            ['1.4', 'Inventario y Control de Activos Empresariales', 'Usar DHCP para actualizar inventario de activos', null, 2, 'Devices', 'Identify'],
            ['2.4', 'Inventario y Control de Activos de Software', 'Utilizar herramientas de inventario de software automatizadas', null, 2, 'Applications', 'Identify'],
            ['2.5', 'Inventario y Control de Activos de Software', 'Incluir aplicaciones no admitidas en el inventario', null, 2, 'Applications', 'Identify'],
            ['2.6', 'Inventario y Control de Activos de Software', 'Tratar el software no admitido', null, 2, 'Applications', 'Respond'],
            ['3.2', 'Protección de Datos', 'Establecer y mantener esquema de clasificación de datos', null, 2, 'Data', 'Identify'],
            ['3.4', 'Protección de Datos', 'Hacer cumplir la retención de datos', null, 2, 'Data', 'Protect'],
            ['3.6', 'Protección de Datos', 'Cifrar datos en dispositivos de usuario final', null, 2, 'Data', 'Protect'],
            ['3.9', 'Protección de Datos', 'Cifrar datos en medios extraíbles', null, 2, 'Data', 'Protect'],
            ['3.10', 'Protección de Datos', 'Cifrar datos sensibles en tránsito', null, 2, 'Data', 'Protect'],
            ['4.6', 'Configuración Segura de Activos y Software', 'Gestionar de forma segura los activos empresariales y el software', null, 2, 'Devices', 'Protect'],
            ['4.8', 'Configuración Segura de Activos y Software', 'Desinstalar o deshabilitar servicios innecesarios', null, 2, 'Applications', 'Protect'],
            ['4.9', 'Configuración Segura de Activos y Software', 'Configurar puertos, protocolos y servicios de confianza', null, 2, 'Network', 'Protect'],
            ['4.10', 'Configuración Segura de Activos y Software', 'Cifrar datos en dispositivos de usuario final', null, 2, 'Devices', 'Protect'],
            ['5.5', 'Gestión de Cuentas', 'Establecer y mantener inventario de cuentas de servicio', null, 2, 'Users', 'Identify'],
            ['5.6', 'Gestión de Cuentas', 'Centralizar la gestión de cuentas', null, 2, 'Users', 'Protect'],
            ['6.2', 'Gestión de Control de Acceso', 'Establecer y mantener un inventario de autenticación', null, 2, 'Users', 'Identify'],
            ['6.3', 'Gestión de Control de Acceso', 'Requerir MFA para aplicaciones expuestas externamente', null, 2, 'Users', 'Protect'],
            ['6.4', 'Gestión de Control de Acceso', 'Requerir MFA para acceso remoto', null, 2, 'Users', 'Protect'],
            ['6.5', 'Gestión de Control de Acceso', 'Requerir MFA para acceso administrativo', null, 2, 'Users', 'Protect'],
            ['7.3', 'Gestión Continua de Vulnerabilidades', 'Realizar gestión automatizada de parches para aplicaciones', null, 2, 'Applications', 'Protect'],
            ['7.4', 'Gestión Continua de Vulnerabilidades', 'Realizar gestión automatizada de parches para SO', null, 2, 'Devices', 'Protect'],
            ['7.5', 'Gestión Continua de Vulnerabilidades', 'Realizar escaneos automatizados de vulnerabilidades de activos internos', null, 2, 'Devices', 'Identify'],
            ['7.7', 'Gestión Continua de Vulnerabilidades', 'Remediar vulnerabilidades detectadas', null, 2, 'Applications', 'Respond'],
            ['8.3', 'Gestión de Registros de Auditoría', 'Garantizar el almacenamiento adecuado de registros', null, 2, 'Devices', 'Protect'],
            ['8.4', 'Gestión de Registros de Auditoría', 'Estandarizar la recopilación de registros de tiempo', null, 2, 'Devices', 'Detect'],
            ['8.5', 'Gestión de Registros de Auditoría', 'Recopilar registros de auditoría detallados', null, 2, 'Devices', 'Detect'],
            ['8.6', 'Gestión de Registros de Auditoría', 'Recopilar registros de auditoría DNS', null, 2, 'Network', 'Detect'],
            ['8.7', 'Gestión de Registros de Auditoría', 'Recopilar registros de auditoría de solicitudes URL', null, 2, 'Network', 'Detect'],
            ['8.9', 'Gestión de Registros de Auditoría', 'Centralizar los registros de auditoría', null, 2, 'Devices', 'Detect'],
            ['8.10', 'Gestión de Registros de Auditoría', 'Conservar los registros de auditoría', null, 2, 'Devices', 'Protect'],
            ['8.11', 'Gestión de Registros de Auditoría', 'Realizar revisiones de registros de auditoría', null, 2, 'Devices', 'Detect'],
            ['9.3', 'Protecciones de Correo Electrónico y Navegadores', 'Mantener y Aplicar Filtros de URL Basados en Red', 'Aplicar y actualizar filtros de URL basados en red para limitar el acceso a sitios no autorizados.', 2, 'Redes', 'Protect'],
            ['9.5', 'Protecciones de Correo Electrónico y Navegadores', 'Implementar DMARC', 'Para reducir la probabilidad de correos falsos, implementar DMARC y verificar que los dominios estén configurados.', 2, 'Redes', 'Protect'],
            ['9.6', 'Protecciones de Correo Electrónico y Navegadores', 'Bloquear Tipos de Archivos Innecesarios', 'Bloquear tipos de archivos adjuntos de correo electrónico que representan riesgos de seguridad innecesarios.', 2, 'Redes', 'Protect'],
            ['9.7', 'Protecciones de Correo Electrónico y Navegadores', 'Implementar Protecciones Anti-Malware en el Servidor de Correo', 'Implementar y mantener protecciones anti-malware en el servidor de correo.', 2, 'Redes', 'Protect'],
            ['10.4', 'Defensas Contra Malware', 'Configurar Análisis Anti-Malware Automático de Medios Extraíbles', 'Configurar el análisis automático de medios extraíbles para identificar malware.', 2, 'Dispositivos del usuario final', 'Detect'],
            ['10.5', 'Defensas Contra Malware', 'Activar Características Anti-Explotación', 'Activar las características anti-explotación en los sistemas operativos y aplicaciones.', 2, 'Dispositivos del usuario final', 'Protect'],
            ['10.6', 'Defensas Contra Malware', 'Administrar Centralmente el Software Anti-Malware', 'Administrar centralmente el software anti-malware en todos los activos empresariales.', 2, 'Dispositivos del usuario final', 'Protect'],
            ['11.4', 'Recuperación de Datos', 'Establecer y mantener práctica aislada de recuperación de datos', null, 2, 'Data', 'Recover'],
            ['11.5', 'Recuperación de Datos', 'Probar recuperación de datos', null, 2, 'Data', 'Recover'],
            ['12.2', 'Gestión de Infraestructura de Red', 'Establecer y mantener arquitectura de red segura', null, 2, 'Network', 'Protect'],
            ['12.3', 'Gestión de Infraestructura de Red', 'Gestionar de forma segura la infraestructura de red', null, 2, 'Network', 'Protect'],
            ['12.4', 'Gestión de Infraestructura de Red', 'Establecer y mantener arquitectura de red', null, 2, 'Network', 'Identify'],
            ['15.3', 'Gestión de Proveedores de Servicios', 'Clasificar proveedores de servicios', null, 2, 'Data', 'Identify'],
            ['15.4', 'Gestión de Proveedores de Servicios', 'Asegurar que los contratos con proveedores incluyan requisitos de seguridad', null, 2, 'Data', 'Protect'],
            ['15.5', 'Gestión de Proveedores de Servicios', 'Evaluar a los proveedores de servicios', null, 2, 'Data', 'Identify'],
            ['15.6', 'Gestión de Proveedores de Servicios', 'Monitorear a los proveedores de servicios', null, 2, 'Data', 'Detect'],
            ['17.4', 'Gestión de Respuesta a Incidentes', 'Establecer y mantener registro de incidentes de seguridad', null, 2, 'Users', 'Detect'],
            ['17.5', 'Gestión de Respuesta a Incidentes', 'Asignar clave de gestión de incidentes', null, 2, 'Users', 'Respond'],
            ['17.6', 'Gestión de Respuesta a Incidentes', 'Definir mecanismos para comunicar vulnerabilidades de seguridad', null, 2, 'Users', 'Respond'],
            ['17.9', 'Gestión de Respuesta a Incidentes', 'Establecer y mantener planes de respuesta a incidentes de seguridad', null, 2, 'Users', 'Respond'],
            ['18.1', 'Pruebas de Penetración', 'Establecer y mantener programa de pruebas de penetración', null, 2, 'Devices', 'Identify'],
            ['18.2', 'Pruebas de Penetración', 'Realizar pruebas de penetración externas periódicas', null, 2, 'Devices', 'Identify'],
            ['18.3', 'Pruebas de Penetración', 'Remediar hallazgos de pruebas de penetración', null, 2, 'Devices', 'Respond'],
            ['1.5', 'Inventario y Control de Activos Empresariales', 'Usar herramienta de descubrimiento pasivo de activos', null, 3, 'Devices', 'Identify'],
            ['2.7', 'Inventario y Control de Activos de Software', 'Utilizar el control de aplicaciones', null, 3, 'Applications', 'Protect'],
            ['3.8', 'Protección de Datos', 'Establecer y mantener una arquitectura de segmentación de red adecuada', null, 3, 'Network', 'Protect'],
            ['3.11', 'Protección de Datos', 'Cifrar datos sensibles en reposo', null, 3, 'Data', 'Protect'],
            ['3.12', 'Protección de Datos', 'Separar sistemas de procesamiento de datos sensibles', null, 3, 'Data', 'Protect'],
            ['3.13', 'Protección de Datos', 'Desplegar solución de Prevención de Pérdida de Datos (DLP)', null, 3, 'Data', 'Detect'],
            ['3.14', 'Protección de Datos', 'Registrar acceso a datos sensibles', null, 3, 'Data', 'Detect'],
            ['4.11', 'Configuración Segura de Activos y Software', 'Aplicar listas de control de acceso para sistemas de archivos locales y remotos', null, 3, 'Data', 'Protect'],
            ['4.12', 'Configuración Segura de Activos y Software', 'Separar estaciones de trabajo empresariales mediante VLANs', null, 3, 'Network', 'Protect'],
            ['6.6', 'Gestión de Control de Acceso', 'Establecer y mantener inventario de sistemas de autenticación y autorización', null, 3, 'Users', 'Identify'],
            ['6.7', 'Gestión de Control de Acceso', 'Centralizar el control de acceso', null, 3, 'Users', 'Protect'],
            ['6.8', 'Gestión de Control de Acceso', 'Definir y mantener control de acceso basado en roles', null, 3, 'Users', 'Protect'],
            ['7.6', 'Gestión Continua de Vulnerabilidades', 'Realizar escaneos automatizados de vulnerabilidades de activos externos', null, 3, 'Devices', 'Identify'],
            ['8.8', 'Gestión de Registros de Auditoría', 'Recopilar registros de auditoría de línea de comandos', null, 3, 'Devices', 'Detect'],
            ['8.12', 'Gestión de Registros de Auditoría', 'Recopilar registros de proveedor de servicios', null, 3, 'Data', 'Detect'],
            ['9.4', 'Protecciones de Correo Electrónico y Navegadores', 'Restringir el Uso de Sistemas de Correo Externos', 'Restringir la capacidad de los usuarios para utilizar sistemas de correo externo no autorizados.', 3, 'Usuarios', 'Protect'],
            ['10.7', 'Defensas Contra Malware', 'Usar Software Anti-Malware Basado en Comportamiento', 'Usar software anti-malware basado en comportamiento que detecte amenazas desconocidas.', 3, 'Dispositivos del usuario final', 'Detect'],
            ['12.5', 'Gestión de Infraestructura de Red', 'Centralizar la autenticación de red, autorización y auditoría (AAA)', null, 3, 'Network', 'Protect'],
            ['12.6', 'Gestión de Infraestructura de Red', 'Usar certificados de comunicación seguros', null, 3, 'Network', 'Protect'],
            ['12.7', 'Gestión de Infraestructura de Red', 'Asegurar los dispositivos de infraestructura de red', null, 3, 'Network', 'Protect'],
            ['12.8', 'Gestión de Infraestructura de Red', 'Establecer y mantener red dedicada para gestión', null, 3, 'Network', 'Protect'],
            ['15.7', 'Gestión de Proveedores de Servicios', 'Ejecutar revisiones de seguridad periódicas con los proveedores', null, 3, 'Data', 'Identify'],
            ['17.7', 'Gestión de Respuesta a Incidentes', 'Realizar revisiones posteriores a los incidentes', null, 3, 'Users', 'Recover'],
            ['17.8', 'Gestión de Respuesta a Incidentes', 'Realizar pruebas de simulación', null, 3, 'Users', 'Respond'],
            ['18.4', 'Pruebas de Penetración', 'Validar controles de seguridad', null, 3, 'Devices', 'Identify'],
            ['18.5', 'Pruebas de Penetración', 'Realizar pruebas de penetración internas periódicas', null, 3, 'Devices', 'Identify'],
        ];
        foreach ($cislib_data as $row) {
            $DB->insert('glpi_plugin_ciscontrols_cislib', [
                'control_number'        => $row[0],
                'control_group'         => $row[1],
                'safeguard_title'       => $row[2],
                'safeguard_description' => $row[3],
                'ig_level'              => $row[4],
                'asset_type'            => $row[5],
                'security_function'     => $row[6],
            ]);
        }
    }

    // Register cron tasks
    CronTask::register(
        'PluginCiscontrolsControl',
        'SendReminders',
        DAY_TIMESTAMP,
        ['comment' => 'CIS Controls: send reminder emails', 'mode' => CronTask::MODE_INTERNAL]
    );
    CronTask::register(
        'PluginCiscontrolsExecution',
        'MarkOverdue',
        HOUR_TIMESTAMP,
        ['comment' => 'CIS Controls: mark overdue executions', 'mode' => CronTask::MODE_INTERNAL]
    );

    return true;
}

function plugin_ciscontrols_uninstall() {
    global $DB;

    $tables = [
        'glpi_plugin_ciscontrols_risk_documents',
        'glpi_plugin_ciscontrols_risk_controls',
        'glpi_plugin_ciscontrols_risks',
        'glpi_plugin_ciscontrols_cislib_documents',
        'glpi_plugin_ciscontrols_control_documents',
        'glpi_plugin_ciscontrols_control_cislib',
        'glpi_plugin_ciscontrols_documents',
        'glpi_plugin_ciscontrols_cislib',
        'glpi_plugin_ciscontrols_config',
        'glpi_plugin_ciscontrols_executions',
        'glpi_plugin_ciscontrols_controls',
    ];
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->queryOrDie("DROP TABLE `$table`", "Error dropping $table");
        }
    }

    CronTask::unregister('PluginCiscontrolsControl');
    CronTask::unregister('PluginCiscontrolsExecution');

    return true;
}
