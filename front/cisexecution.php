<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/cisexecution.php - Executions list page
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

Html::header('CIS Controls - Ejecuciones', $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');

PluginCiscontrolsExecution::renderList();

Html::footer();
