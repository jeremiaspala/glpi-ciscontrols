<?php
/**
 * CIS Controls Plugin for GLPI 11
 * front/ciscontrol.php - Controls list page
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

Html::header('CIS Controls - Controles', $_SERVER['PHP_SELF'], 'plugins', 'ciscontrols');

PluginCiscontrolsControl::renderList();

Html::footer();
