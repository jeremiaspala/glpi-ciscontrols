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
-- Table structure for table `glpi_plugin_ciscontrols_cislib`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_cislib`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_cislib` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `control_number` varchar(10) NOT NULL,
  `control_group` varchar(150) NOT NULL,
  `safeguard_title` varchar(500) NOT NULL,
  `safeguard_description` text DEFAULT NULL,
  `ig_level` tinyint(4) NOT NULL DEFAULT 1,
  `asset_type` varchar(100) DEFAULT NULL,
  `security_function` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ig_level` (`ig_level`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_cislib_documents`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_cislib_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_cislib_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_ciscontrols_cislib_id` int(11) NOT NULL,
  `plugin_ciscontrols_documents_id` int(11) NOT NULL,
  `link_type` varchar(50) DEFAULT 'referencia',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_link` (`plugin_ciscontrols_cislib_id`,`plugin_ciscontrols_documents_id`)
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_config`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_control_cislib`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_control_cislib`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_control_cislib` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_ciscontrols_controls_id` int(11) NOT NULL,
  `plugin_ciscontrols_cislib_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_link` (`plugin_ciscontrols_controls_id`,`plugin_ciscontrols_cislib_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_control_documents`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_control_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_control_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_ciscontrols_controls_id` int(11) NOT NULL,
  `plugin_ciscontrols_documents_id` int(11) NOT NULL,
  `link_type` varchar(50) DEFAULT 'procedimiento',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_link` (`plugin_ciscontrols_controls_id`,`plugin_ciscontrols_documents_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_controls`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_controls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_controls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `document` varchar(50) DEFAULT NULL,
  `periodicity` varchar(50) NOT NULL DEFAULT '',
  `periodicity_days` int(11) NOT NULL DEFAULT 30,
  `activity` text DEFAULT NULL,
  `evidence_type` text DEFAULT NULL,
  `responsible` varchar(255) DEFAULT NULL,
  `cis_version` varchar(50) DEFAULT NULL,
  `first_due_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `reminder_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `reminder_days_before` int(11) NOT NULL DEFAULT 3,
  `reminder_email` varchar(255) DEFAULT NULL,
  `reminder_subject` varchar(500) DEFAULT NULL,
  `reminder_body` longtext DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_documents`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `is_folder` tinyint(1) NOT NULL DEFAULT 0,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_executions`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_executions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_ciscontrols_controls_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `completion_date` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `evidence_file` varchar(500) DEFAULT NULL,
  `evidence_original_name` varchar(500) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_control` (`plugin_ciscontrols_controls_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_risk_controls`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_risk_controls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_risk_controls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_ciscontrols_risks_id` int(11) NOT NULL,
  `plugin_ciscontrols_controls_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_link` (`plugin_ciscontrols_risks_id`,`plugin_ciscontrols_controls_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_risk_documents`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_risk_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_risk_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_ciscontrols_risks_id` int(11) NOT NULL,
  `plugin_ciscontrols_documents_id` int(11) NOT NULL,
  `link_type` varchar(50) DEFAULT 'evidencia',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_link` (`plugin_ciscontrols_risks_id`,`plugin_ciscontrols_documents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glpi_plugin_ciscontrols_risks`
--

DROP TABLE IF EXISTS `glpi_plugin_ciscontrols_risks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_ciscontrols_risks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(500) NOT NULL DEFAULT '',
  `category` varchar(50) DEFAULT 'otro',
  `description` text DEFAULT NULL,
  `asset` varchar(255) DEFAULT NULL,
  `threat` text DEFAULT NULL,
  `vulnerability` text DEFAULT NULL,
  `likelihood` tinyint(4) NOT NULL DEFAULT 1,
  `impact` tinyint(4) NOT NULL DEFAULT 1,
  `risk_score` tinyint(4) NOT NULL DEFAULT 1,
  `treatment` varchar(20) DEFAULT 'mitigar',
  `treatment_notes` text DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'abierto',
  `review_date` date DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_score` (`risk_score`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-02 21:58:54
