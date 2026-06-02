/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: glpi
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Dumping data for table `glpi_plugin_ciscontrols_cislib`
--

LOCK TABLES `glpi_plugin_ciscontrols_cislib` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_ciscontrols_cislib` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `glpi_plugin_ciscontrols_cislib` (`id`, `control_number`, `control_group`, `safeguard_title`, `safeguard_description`, `ig_level`, `asset_type`, `security_function`) VALUES (1,'1.1','Inventario y Control de Activos Empresariales','Establecer y mantener inventario detallado de activos empresariales',NULL,1,'Devices','Identify'),
(2,'1.2','Inventario y Control de Activos Empresariales','Tratar activos no autorizados',NULL,1,'Devices','Respond'),
(3,'1.3','Inventario y Control de Activos Empresariales','Utilizar herramienta de descubrimiento activo de activos',NULL,2,'Devices','Detect'),
(4,'1.4','Inventario y Control de Activos Empresariales','Usar DHCP para actualizar inventario de activos',NULL,2,'Devices','Identify'),
(5,'1.5','Inventario y Control de Activos Empresariales','Usar herramienta de descubrimiento pasivo de activos',NULL,3,'Devices','Identify'),
(6,'2.1','Inventario y Control de Activos de Software','Establecer y mantener inventario de software',NULL,1,'Applications','Identify'),
(7,'2.2','Inventario y Control de Activos de Software','Asegurar que el software esté en lista de permitidos',NULL,1,'Applications','Protect'),
(8,'2.3','Inventario y Control de Activos de Software','Tratar software no autorizado',NULL,1,'Applications','Respond'),
(9,'2.4','Inventario y Control de Activos de Software','Utilizar herramientas de inventario de software automatizadas',NULL,2,'Applications','Identify'),
(10,'2.5','Inventario y Control de Activos de Software','Incluir aplicaciones no admitidas en el inventario',NULL,2,'Applications','Identify'),
(11,'2.6','Inventario y Control de Activos de Software','Tratar el software no admitido',NULL,2,'Applications','Respond'),
(12,'2.7','Inventario y Control de Activos de Software','Utilizar el control de aplicaciones',NULL,3,'Applications','Protect'),
(13,'3.1','Protección de Datos','Establecer y mantener proceso de gestión de datos',NULL,1,'Data','Identify'),
(14,'3.2','Protección de Datos','Establecer y mantener esquema de clasificación de datos',NULL,2,'Data','Identify'),
(15,'3.3','Protección de Datos','Configurar listas de control de acceso a datos',NULL,1,'Data','Protect'),
(16,'3.4','Protección de Datos','Hacer cumplir la retención de datos',NULL,2,'Data','Protect'),
(17,'3.5','Protección de Datos','Eliminar datos de forma segura',NULL,1,'Data','Protect'),
(18,'3.6','Protección de Datos','Cifrar datos en dispositivos de usuario final',NULL,2,'Data','Protect'),
(19,'3.7','Protección de Datos','Establecer y mantener proceso de gestión de datos de copia de seguridad',NULL,1,'Data','Recover'),
(20,'3.8','Protección de Datos','Establecer y mantener una arquitectura de segmentación de red adecuada',NULL,3,'Network','Protect'),
(21,'3.9','Protección de Datos','Cifrar datos en medios extraíbles',NULL,2,'Data','Protect'),
(22,'3.10','Protección de Datos','Cifrar datos sensibles en tránsito',NULL,2,'Data','Protect'),
(23,'3.11','Protección de Datos','Cifrar datos sensibles en reposo',NULL,3,'Data','Protect'),
(24,'3.12','Protección de Datos','Separar sistemas de procesamiento de datos sensibles',NULL,3,'Data','Protect'),
(25,'3.13','Protección de Datos','Desplegar solución de Prevención de Pérdida de Datos (DLP)',NULL,3,'Data','Detect'),
(26,'3.14','Protección de Datos','Registrar acceso a datos sensibles',NULL,3,'Data','Detect'),
(27,'4.1','Configuración Segura de Activos y Software','Establecer y mantener proceso de configuración segura',NULL,1,'Devices','Protect'),
(28,'4.2','Configuración Segura de Activos y Software','Establecer y mantener proceso de configuración segura para software',NULL,1,'Applications','Protect'),
(29,'4.3','Configuración Segura de Activos y Software','Configurar el bloqueo automático de sesión en activos empresariales',NULL,1,'Users','Protect'),
(30,'4.4','Configuración Segura de Activos y Software','Implementar y gestionar un cortafuegos en servidores',NULL,1,'Network','Protect'),
(31,'4.5','Configuración Segura de Activos y Software','Implementar y gestionar un cortafuegos en dispositivos de usuario final',NULL,1,'Devices','Protect'),
(32,'4.6','Configuración Segura de Activos y Software','Gestionar de forma segura los activos empresariales y el software',NULL,2,'Devices','Protect'),
(33,'4.7','Configuración Segura de Activos y Software','Gestionar cuentas de usuario predeterminadas',NULL,1,'Users','Protect'),
(34,'4.8','Configuración Segura de Activos y Software','Desinstalar o deshabilitar servicios innecesarios',NULL,2,'Applications','Protect'),
(35,'4.9','Configuración Segura de Activos y Software','Configurar puertos, protocolos y servicios de confianza',NULL,2,'Network','Protect'),
(36,'4.10','Configuración Segura de Activos y Software','Cifrar datos en dispositivos de usuario final',NULL,2,'Devices','Protect'),
(37,'4.11','Configuración Segura de Activos y Software','Aplicar listas de control de acceso para sistemas de archivos locales y remotos',NULL,3,'Data','Protect'),
(38,'4.12','Configuración Segura de Activos y Software','Separar estaciones de trabajo empresariales mediante VLANs',NULL,3,'Network','Protect'),
(39,'5.1','Gestión de Cuentas','Establecer y mantener inventario de cuentas',NULL,1,'Users','Identify'),
(40,'5.2','Gestión de Cuentas','Usar contraseñas únicas',NULL,1,'Users','Protect'),
(41,'5.3','Gestión de Cuentas','Deshabilitar cuentas de usuario inactivas',NULL,1,'Users','Protect'),
(42,'5.4','Gestión de Cuentas','Restringir privilegios de administrador a cuentas de administrador dedicadas',NULL,1,'Users','Protect'),
(43,'5.5','Gestión de Cuentas','Establecer y mantener inventario de cuentas de servicio',NULL,2,'Users','Identify'),
(44,'5.6','Gestión de Cuentas','Centralizar la gestión de cuentas',NULL,2,'Users','Protect'),
(45,'6.1','Gestión de Control de Acceso','Establecer una política de control de acceso',NULL,1,'Data','Protect'),
(46,'6.2','Gestión de Control de Acceso','Establecer y mantener un inventario de autenticación',NULL,2,'Users','Identify'),
(47,'6.3','Gestión de Control de Acceso','Requerir MFA para aplicaciones expuestas externamente',NULL,2,'Users','Protect'),
(48,'6.4','Gestión de Control de Acceso','Requerir MFA para acceso remoto',NULL,2,'Users','Protect'),
(49,'6.5','Gestión de Control de Acceso','Requerir MFA para acceso administrativo',NULL,2,'Users','Protect'),
(50,'6.6','Gestión de Control de Acceso','Establecer y mantener inventario de sistemas de autenticación y autorización',NULL,3,'Users','Identify'),
(51,'6.7','Gestión de Control de Acceso','Centralizar el control de acceso',NULL,3,'Users','Protect'),
(52,'6.8','Gestión de Control de Acceso','Definir y mantener control de acceso basado en roles',NULL,3,'Users','Protect'),
(53,'7.1','Gestión Continua de Vulnerabilidades','Establecer y mantener proceso de gestión de vulnerabilidades',NULL,1,'Applications','Protect'),
(54,'7.2','Gestión Continua de Vulnerabilidades','Establecer y mantener sistema de remediación',NULL,1,'Applications','Respond'),
(55,'7.3','Gestión Continua de Vulnerabilidades','Realizar gestión automatizada de parches para aplicaciones',NULL,2,'Applications','Protect'),
(56,'7.4','Gestión Continua de Vulnerabilidades','Realizar gestión automatizada de parches para SO',NULL,2,'Devices','Protect'),
(57,'7.5','Gestión Continua de Vulnerabilidades','Realizar escaneos automatizados de vulnerabilidades de activos internos',NULL,2,'Devices','Identify'),
(58,'7.6','Gestión Continua de Vulnerabilidades','Realizar escaneos automatizados de vulnerabilidades de activos externos',NULL,3,'Devices','Identify'),
(59,'7.7','Gestión Continua de Vulnerabilidades','Remediar vulnerabilidades detectadas',NULL,2,'Applications','Respond'),
(60,'8.1','Gestión de Registros de Auditoría','Establecer y mantener proceso de gestión de registros de auditoría',NULL,1,'Devices','Protect'),
(61,'8.2','Gestión de Registros de Auditoría','Recopilar registros de auditoría',NULL,1,'Devices','Detect'),
(62,'8.3','Gestión de Registros de Auditoría','Garantizar el almacenamiento adecuado de registros',NULL,2,'Devices','Protect'),
(63,'8.4','Gestión de Registros de Auditoría','Estandarizar la recopilación de registros de tiempo',NULL,2,'Devices','Detect'),
(64,'8.5','Gestión de Registros de Auditoría','Recopilar registros de auditoría detallados',NULL,2,'Devices','Detect'),
(65,'8.6','Gestión de Registros de Auditoría','Recopilar registros de auditoría DNS',NULL,2,'Network','Detect'),
(66,'8.7','Gestión de Registros de Auditoría','Recopilar registros de auditoría de solicitudes URL',NULL,2,'Network','Detect'),
(67,'8.8','Gestión de Registros de Auditoría','Recopilar registros de auditoría de línea de comandos',NULL,3,'Devices','Detect'),
(68,'8.9','Gestión de Registros de Auditoría','Centralizar los registros de auditoría',NULL,2,'Devices','Detect'),
(69,'8.10','Gestión de Registros de Auditoría','Conservar los registros de auditoría',NULL,2,'Devices','Protect'),
(70,'8.11','Gestión de Registros de Auditoría','Realizar revisiones de registros de auditoría',NULL,2,'Devices','Detect'),
(71,'8.12','Gestión de Registros de Auditoría','Recopilar registros de proveedor de servicios',NULL,3,'Data','Detect'),
(72,'11.1','Recuperación de Datos','Establecer y mantener proceso de recuperación de datos',NULL,1,'Data','Recover'),
(73,'11.2','Recuperación de Datos','Realizar copias de seguridad automatizadas',NULL,1,'Data','Recover'),
(74,'11.3','Recuperación de Datos','Proteger los datos de recuperación',NULL,1,'Data','Recover'),
(75,'11.4','Recuperación de Datos','Establecer y mantener práctica aislada de recuperación de datos',NULL,2,'Data','Recover'),
(76,'11.5','Recuperación de Datos','Probar recuperación de datos',NULL,2,'Data','Recover'),
(77,'12.1','Gestión de Infraestructura de Red','Asegurar la infraestructura de red',NULL,1,'Network','Protect'),
(78,'12.2','Gestión de Infraestructura de Red','Establecer y mantener arquitectura de red segura',NULL,2,'Network','Protect'),
(79,'12.3','Gestión de Infraestructura de Red','Gestionar de forma segura la infraestructura de red',NULL,2,'Network','Protect'),
(80,'12.4','Gestión de Infraestructura de Red','Establecer y mantener arquitectura de red',NULL,2,'Network','Identify'),
(81,'12.5','Gestión de Infraestructura de Red','Centralizar la autenticación de red, autorización y auditoría (AAA)',NULL,3,'Network','Protect'),
(82,'12.6','Gestión de Infraestructura de Red','Usar certificados de comunicación seguros',NULL,3,'Network','Protect'),
(83,'12.7','Gestión de Infraestructura de Red','Asegurar los dispositivos de infraestructura de red',NULL,3,'Network','Protect'),
(84,'12.8','Gestión de Infraestructura de Red','Establecer y mantener red dedicada para gestión',NULL,3,'Network','Protect'),
(85,'15.1','Gestión de Proveedores de Servicios','Establecer y mantener inventario de proveedores de servicios',NULL,1,'Data','Identify'),
(86,'15.2','Gestión de Proveedores de Servicios','Establecer y mantener política de gestión de proveedores',NULL,1,'Data','Protect'),
(87,'15.3','Gestión de Proveedores de Servicios','Clasificar proveedores de servicios',NULL,2,'Data','Identify'),
(88,'15.4','Gestión de Proveedores de Servicios','Asegurar que los contratos con proveedores incluyan requisitos de seguridad',NULL,2,'Data','Protect'),
(89,'15.5','Gestión de Proveedores de Servicios','Evaluar a los proveedores de servicios',NULL,2,'Data','Identify'),
(90,'15.6','Gestión de Proveedores de Servicios','Monitorear a los proveedores de servicios',NULL,2,'Data','Detect'),
(91,'15.7','Gestión de Proveedores de Servicios','Ejecutar revisiones de seguridad periódicas con los proveedores',NULL,3,'Data','Identify'),
(92,'17.1','Gestión de Respuesta a Incidentes','Designar personal para gestión de incidentes',NULL,1,'Users','Respond'),
(93,'17.2','Gestión de Respuesta a Incidentes','Establecer y mantener proceso de respuesta a incidentes de seguridad',NULL,1,'Users','Respond'),
(94,'17.3','Gestión de Respuesta a Incidentes','Establecer y mantener proceso de comunicación de incidentes de seguridad',NULL,1,'Users','Respond'),
(95,'17.4','Gestión de Respuesta a Incidentes','Establecer y mantener registro de incidentes de seguridad',NULL,2,'Users','Detect'),
(96,'17.5','Gestión de Respuesta a Incidentes','Asignar clave de gestión de incidentes',NULL,2,'Users','Respond'),
(97,'17.6','Gestión de Respuesta a Incidentes','Definir mecanismos para comunicar vulnerabilidades de seguridad',NULL,2,'Users','Respond'),
(98,'17.7','Gestión de Respuesta a Incidentes','Realizar revisiones posteriores a los incidentes',NULL,3,'Users','Recover'),
(99,'17.8','Gestión de Respuesta a Incidentes','Realizar pruebas de simulación',NULL,3,'Users','Respond'),
(100,'17.9','Gestión de Respuesta a Incidentes','Establecer y mantener planes de respuesta a incidentes de seguridad',NULL,2,'Users','Respond'),
(101,'18.1','Pruebas de Penetración','Establecer y mantener programa de pruebas de penetración',NULL,2,'Devices','Identify'),
(102,'18.2','Pruebas de Penetración','Realizar pruebas de penetración externas periódicas',NULL,2,'Devices','Identify'),
(103,'18.3','Pruebas de Penetración','Remediar hallazgos de pruebas de penetración',NULL,2,'Devices','Respond'),
(104,'18.4','Pruebas de Penetración','Validar controles de seguridad',NULL,3,'Devices','Identify'),
(105,'18.5','Pruebas de Penetración','Realizar pruebas de penetración internas periódicas',NULL,3,'Devices','Identify'),
(106,'9.1','Protecciones de Correo Electrónico y Navegadores','Uso de Navegadores y Clientes de Correo Completamente Soportados','Utilizar únicamente versiones de navegadores y clientes de correo que reciban soporte activo de seguridad del proveedor.',1,'Dispositivos del usuario final','Protect'),
(107,'9.2','Protecciones de Correo Electrónico y Navegadores','Usar Servicios de Filtrado DNS','Usar servicios de filtrado DNS en toda la red empresarial para bloquear acceso a dominios maliciosos conocidos.',1,'Redes','Protect'),
(108,'9.3','Protecciones de Correo Electrónico y Navegadores','Mantener y Aplicar Filtros de URL Basados en Red','Aplicar y actualizar filtros de URL basados en red para limitar el acceso a sitios no autorizados.',2,'Redes','Protect'),
(109,'9.4','Protecciones de Correo Electrónico y Navegadores','Restringir el Uso de Sistemas de Correo Externos','Restringir la capacidad de los usuarios para utilizar sistemas de correo externo no autorizados.',3,'Usuarios','Protect'),
(110,'9.5','Protecciones de Correo Electrónico y Navegadores','Implementar DMARC','Para reducir la probabilidad de correos falsos, implementar DMARC y verificar que los dominios estén configurados.',2,'Redes','Protect'),
(111,'9.6','Protecciones de Correo Electrónico y Navegadores','Bloquear Tipos de Archivos Innecesarios','Bloquear tipos de archivos adjuntos de correo electrónico que representan riesgos de seguridad innecesarios.',2,'Redes','Protect'),
(112,'9.7','Protecciones de Correo Electrónico y Navegadores','Implementar Protecciones Anti-Malware en el Servidor de Correo','Implementar y mantener protecciones anti-malware en el servidor de correo.',2,'Redes','Protect'),
(113,'10.1','Defensas Contra Malware','Implementar y Mantener Software Anti-Malware','Implementar y mantener software anti-malware en todos los activos empresariales.',1,'Dispositivos del usuario final','Protect'),
(114,'10.2','Defensas Contra Malware','Configurar Actualizaciones Automáticas de Firmas Anti-Malware','Configurar las actualizaciones automáticas de firmas de software anti-malware en todos los activos empresariales.',1,'Dispositivos del usuario final','Protect'),
(115,'10.3','Defensas Contra Malware','Deshabilitar Ejecución Automática y Reproducción Automática para Medios Extraíbles','Deshabilitar las funciones de ejecución automática y reproducción automática para medios extraíbles.',1,'Dispositivos del usuario final','Protect'),
(116,'10.4','Defensas Contra Malware','Configurar Análisis Anti-Malware Automático de Medios Extraíbles','Configurar el análisis automático de medios extraíbles para identificar malware.',2,'Dispositivos del usuario final','Detect'),
(117,'10.5','Defensas Contra Malware','Activar Características Anti-Explotación','Activar las características anti-explotación en los sistemas operativos y aplicaciones.',2,'Dispositivos del usuario final','Protect'),
(118,'10.6','Defensas Contra Malware','Administrar Centralmente el Software Anti-Malware','Administrar centralmente el software anti-malware en todos los activos empresariales.',2,'Dispositivos del usuario final','Protect'),
(119,'10.7','Defensas Contra Malware','Usar Software Anti-Malware Basado en Comportamiento','Usar software anti-malware basado en comportamiento que detecte amenazas desconocidas.',3,'Dispositivos del usuario final','Detect');
/*!40000 ALTER TABLE `glpi_plugin_ciscontrols_cislib` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-02 21:58:54
/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: glpi
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Dumping data for table `glpi_plugin_ciscontrols_cislib_documents`
--

LOCK TABLES `glpi_plugin_ciscontrols_cislib_documents` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_ciscontrols_cislib_documents` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `glpi_plugin_ciscontrols_cislib_documents` (`id`, `plugin_ciscontrols_cislib_id`, `plugin_ciscontrols_documents_id`, `link_type`) VALUES (1,53,5,'procedimiento'),
(2,101,5,'procedimiento'),
(3,102,5,'procedimiento'),
(4,1,6,'procedimiento'),
(5,45,6,'procedimiento'),
(6,51,6,'procedimiento'),
(7,1,7,'procedimiento'),
(8,6,7,'procedimiento'),
(9,27,7,'procedimiento'),
(10,39,8,'procedimiento'),
(11,40,8,'procedimiento'),
(12,41,8,'procedimiento'),
(13,45,8,'procedimiento'),
(14,39,9,'procedimiento'),
(15,40,9,'procedimiento'),
(16,41,9,'procedimiento'),
(17,45,9,'procedimiento'),
(18,92,10,'procedimiento'),
(19,93,10,'procedimiento'),
(20,94,10,'procedimiento'),
(21,42,11,'procedimiento'),
(22,45,11,'procedimiento'),
(23,27,12,'procedimiento'),
(24,28,12,'procedimiento'),
(25,72,13,'procedimiento'),
(26,92,13,'procedimiento'),
(27,95,13,'procedimiento'),
(28,7,14,'procedimiento'),
(29,1,15,'procedimiento'),
(30,51,15,'procedimiento'),
(31,39,18,'procedimiento'),
(32,41,18,'procedimiento'),
(33,42,18,'procedimiento'),
(34,92,19,'procedimiento'),
(35,93,19,'procedimiento'),
(36,95,19,'procedimiento'),
(37,96,19,'procedimiento'),
(38,1,20,'procedimiento'),
(39,2,20,'procedimiento'),
(40,3,20,'procedimiento'),
(41,4,20,'procedimiento'),
(42,5,20,'procedimiento'),
(43,6,20,'procedimiento'),
(44,28,21,'procedimiento'),
(45,60,21,'procedimiento'),
(46,64,21,'procedimiento'),
(47,1,22,'procedimiento'),
(48,27,22,'procedimiento'),
(49,45,22,'procedimiento'),
(50,53,23,'procedimiento'),
(51,54,23,'procedimiento'),
(52,85,24,'procedimiento'),
(53,86,24,'procedimiento'),
(54,13,25,'procedimiento'),
(55,14,25,'procedimiento'),
(56,1,26,'procedimiento'),
(57,27,26,'procedimiento'),
(58,28,26,'procedimiento'),
(59,72,27,'procedimiento'),
(60,73,27,'procedimiento'),
(61,74,27,'procedimiento'),
(62,72,28,'procedimiento'),
(63,75,28,'procedimiento'),
(64,72,29,'procedimiento'),
(65,74,29,'procedimiento'),
(66,72,30,'procedimiento'),
(67,74,30,'procedimiento'),
(68,75,31,'procedimiento'),
(69,76,31,'procedimiento'),
(70,60,32,'procedimiento'),
(71,64,32,'procedimiento'),
(72,75,33,'procedimiento'),
(73,76,33,'procedimiento'),
(74,27,35,'procedimiento'),
(75,28,35,'procedimiento'),
(76,75,36,'procedimiento'),
(77,76,36,'procedimiento'),
(78,72,37,'procedimiento'),
(79,73,37,'procedimiento'),
(80,74,37,'procedimiento'),
(81,75,37,'procedimiento'),
(82,77,38,'procedimiento'),
(83,78,38,'procedimiento'),
(84,79,38,'procedimiento'),
(85,77,39,'referencia'),
(86,78,39,'referencia'),
(87,79,39,'referencia'),
(88,27,40,'procedimiento'),
(89,28,40,'procedimiento'),
(90,33,40,'procedimiento'),
(91,60,41,'procedimiento'),
(92,61,41,'procedimiento'),
(93,62,41,'procedimiento'),
(94,64,41,'procedimiento'),
(95,53,42,'procedimiento'),
(96,54,42,'procedimiento'),
(97,57,42,'procedimiento'),
(98,92,43,'procedimiento'),
(99,93,43,'procedimiento'),
(100,95,43,'procedimiento'),
(101,96,43,'procedimiento'),
(102,92,44,'procedimiento'),
(103,93,44,'procedimiento'),
(104,95,44,'procedimiento'),
(105,96,44,'procedimiento'),
(106,72,45,'procedimiento'),
(107,92,45,'procedimiento'),
(108,95,45,'procedimiento'),
(109,1,46,'evidencia'),
(110,60,46,'evidencia'),
(111,60,47,'evidencia'),
(112,64,47,'evidencia'),
(113,40,48,'evidencia'),
(114,41,48,'evidencia'),
(115,39,49,'evidencia'),
(116,40,49,'evidencia'),
(117,6,50,'evidencia'),
(118,7,50,'evidencia'),
(119,27,51,'evidencia'),
(120,28,51,'evidencia'),
(121,42,52,'evidencia'),
(122,45,52,'evidencia'),
(123,53,53,'evidencia'),
(124,7,54,'evidencia'),
(125,7,55,'evidencia'),
(126,45,56,'evidencia'),
(127,51,56,'evidencia'),
(128,72,57,'evidencia'),
(129,42,58,'evidencia'),
(130,1,59,'evidencia'),
(131,4,59,'evidencia'),
(132,1,60,'evidencia'),
(133,72,61,'evidencia'),
(134,73,61,'evidencia'),
(135,45,62,'evidencia'),
(136,51,62,'evidencia'),
(137,75,63,'evidencia'),
(138,76,63,'evidencia'),
(139,75,64,'evidencia'),
(140,76,64,'evidencia'),
(141,42,65,'evidencia'),
(142,42,66,'evidencia'),
(143,64,66,'evidencia'),
(144,39,67,'evidencia'),
(145,41,67,'evidencia'),
(146,92,68,'evidencia'),
(147,60,68,'evidencia'),
(148,53,69,'evidencia'),
(149,54,69,'evidencia'),
(150,72,70,'evidencia'),
(151,73,70,'evidencia'),
(152,53,71,'evidencia'),
(153,54,71,'evidencia'),
(154,85,72,'evidencia'),
(155,86,72,'evidencia'),
(156,13,73,'evidencia'),
(157,14,73,'evidencia'),
(158,27,74,'evidencia'),
(159,39,74,'evidencia'),
(160,27,75,'evidencia'),
(161,33,75,'evidencia'),
(162,60,76,'evidencia'),
(163,61,76,'evidencia'),
(164,85,77,'evidencia'),
(165,86,77,'evidencia'),
(166,87,77,'evidencia'),
(167,28,78,'evidencia'),
(168,53,78,'evidencia');
/*!40000 ALTER TABLE `glpi_plugin_ciscontrols_cislib_documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-02 21:58:54
