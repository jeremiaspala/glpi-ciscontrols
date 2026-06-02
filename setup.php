<?php
/**
 * CIS Controls Plugin for GLPI 11
 * setup.php - Plugin registration and bootstrap
 */

define('PLUGIN_CISCONTROLS_VERSION', '2.2.0');
define('PLUGIN_CISCONTROLS_MIN_GLPI', '11.0.0');
define('PLUGIN_CISCONTROLS_MAX_GLPI', '12.0.0');

function plugin_version_ciscontrols() {
    return [
        'name'           => 'CIS Controls',
        'version'        => PLUGIN_CISCONTROLS_VERSION,
        'author'         => 'Jeremías Palazzesi',
        'license'        => 'GPL v2+',
        'homepage'       => 'https://www.nerdadas.com',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_CISCONTROLS_MIN_GLPI,
                'max' => PLUGIN_CISCONTROLS_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_ciscontrols_check_prerequisites() {
    if (version_compare(GLPI_VERSION, PLUGIN_CISCONTROLS_MIN_GLPI, 'lt')
        || version_compare(GLPI_VERSION, PLUGIN_CISCONTROLS_MAX_GLPI, 'ge')) {
        echo "This plugin requires GLPI >= " . PLUGIN_CISCONTROLS_MIN_GLPI . " and < " . PLUGIN_CISCONTROLS_MAX_GLPI;
        return false;
    }
    return true;
}

function plugin_ciscontrols_check_config() {
    return true;
}

function plugin_ciscontrols_init() {
    global $PLUGIN_HOOKS;

    Plugin::registerClass('PluginCiscontrolsControl');
    Plugin::registerClass('PluginCiscontrolsExecution');
    Plugin::registerClass('PluginCiscontrolsRisk');

    $PLUGIN_HOOKS['menu_toadd']['ciscontrols'] = [
        'ciscontrols' => ['PluginCiscontrolsControl'],
    ];

    $PLUGIN_HOOKS['redefine_menus']['ciscontrols'] = function($menu) {
        // Siempre aplicar estructura completa; preservar 'types' de menu_toadd
        $existing_types = $menu['ciscontrols']['types'] ?? [];
        {
            $menu['ciscontrols'] = [
                'title'   => 'CIS Controls',
                'icon'    => 'ti ti-shield-check',
                'default' => '/plugins/ciscontrols/front/ciscontrol.php',
                'content' => [
                    'dashboard'   => ['title' => 'Dashboard',       'page' => '/plugins/ciscontrols/front/dashboard.php',    'icon' => 'ti ti-chart-bar'],
                    'controls'    => ['title' => 'Controles',        'page' => '/plugins/ciscontrols/front/ciscontrol.php',   'icon' => 'ti ti-list-check'],
                    'executions'  => ['title' => 'Ejecuciones',      'page' => '/plugins/ciscontrols/front/cisexecution.php', 'icon' => 'ti ti-clipboard-check'],
                    'cislib'      => ['title' => 'Biblioteca CIS',   'page' => '/plugins/ciscontrols/front/cislib.php',       'icon' => 'ti ti-books'],
                    'doctree'     => ['title' => 'Documentación',    'page' => '/plugins/ciscontrols/front/doctree.php',      'icon' => 'ti ti-folder-open'],
                    'risks'       => ['title' => 'Matriz de Riesgos', 'page' => '/plugins/ciscontrols/front/risk.php',         'icon' => 'ti ti-alert-triangle'],
                    'config'      => ['title' => 'Configuración',    'page' => '/plugins/ciscontrols/front/config.php',       'icon' => 'ti ti-settings'],
                ],
                'types' => $existing_types,
            ];
        }
        return $menu;
    };
}

function plugin_ciscontrols_getMenuContent() {
    $menu = [];
    $menu['title'] = 'CIS Controls';
    $menu['page']  = '/plugins/ciscontrols/front/dashboard.php';
    $menu['icon']  = 'ti ti-shield-check';

    $menu['links']['title']    = '/plugins/ciscontrols/front/dashboard.php';
    $menu['links']['Dashboard'] = '/plugins/ciscontrols/front/dashboard.php';
    $menu['links']['add']       = '/plugins/ciscontrols/front/ciscontrol.form.php';
    $menu['links']['search']    = '/plugins/ciscontrols/front/ciscontrol.php';

    $menu['options']['ciscontrol']['title']           = 'Controles';
    $menu['options']['ciscontrol']['page']            = '/plugins/ciscontrols/front/ciscontrol.php';
    $menu['options']['ciscontrol']['icon']            = 'ti ti-list-check';
    $menu['options']['ciscontrol']['links']['search'] = '/plugins/ciscontrols/front/ciscontrol.php';
    $menu['options']['ciscontrol']['links']['add']    = '/plugins/ciscontrols/front/ciscontrol.form.php';

    $menu['options']['cisexecution']['title']           = 'Ejecuciones';
    $menu['options']['cisexecution']['page']            = '/plugins/ciscontrols/front/cisexecution.php';
    $menu['options']['cisexecution']['icon']            = 'ti ti-run';
    $menu['options']['cisexecution']['links']['search'] = '/plugins/ciscontrols/front/cisexecution.php';
    $menu['options']['cisexecution']['links']['add']    = '/plugins/ciscontrols/front/cisexecution.form.php';

    $menu['options']['dashboard']['title'] = 'Dashboard';
    $menu['options']['dashboard']['page']  = '/plugins/ciscontrols/front/dashboard.php';
    $menu['options']['dashboard']['icon']  = 'ti ti-dashboard';
    $menu['options']['dashboard']['links']['search'] = '/plugins/ciscontrols/front/dashboard.php';

    $menu['options']['cislib']['title'] = 'Biblioteca CIS';
    $menu['options']['cislib']['page']  = '/plugins/ciscontrols/front/cislib.php';
    $menu['options']['cislib']['icon']  = 'ti ti-books';
    $menu['options']['cislib']['links']['search'] = '/plugins/ciscontrols/front/cislib.php';

    $menu['options']['doctree']['title'] = 'Documentación';
    $menu['options']['doctree']['page']  = '/plugins/ciscontrols/front/doctree.php';
    $menu['options']['doctree']['icon']  = 'ti ti-folder-open';
    $menu['options']['doctree']['links']['search'] = '/plugins/ciscontrols/front/doctree.php';

    $menu['options']['config']['title'] = 'Configuración';
    $menu['options']['config']['page']  = '/plugins/ciscontrols/front/config.php';
    $menu['options']['config']['icon']  = 'ti ti-settings';
    $menu['options']['config']['links']['search'] = '/plugins/ciscontrols/front/config.php';

    $menu['options']['risks']['title']           = 'Matriz de Riesgos';
    $menu['options']['risks']['page']            = '/plugins/ciscontrols/front/risk.php';
    $menu['options']['risks']['icon']            = 'ti ti-alert-triangle';
    $menu['options']['risks']['links']['search'] = '/plugins/ciscontrols/front/risk.php';
    $menu['options']['risks']['links']['add']    = '/plugins/ciscontrols/front/risk.form.php';

    return $menu;
}

/**
 * GLPI 11 init function (plugin_init_{key})
 */
function plugin_init_ciscontrols() {
    /* CIS_DEBUG */     global $PLUGIN_HOOKS;

    Plugin::registerClass('PluginCiscontrolsControl');
    Plugin::registerClass('PluginCiscontrolsExecution');
    Plugin::registerClass('PluginCiscontrolsRisk');

    $PLUGIN_HOOKS['menu_toadd']['ciscontrols'] = [
        'ciscontrols' => ['PluginCiscontrolsControl'],
    ];

    $PLUGIN_HOOKS['redefine_menus']['ciscontrols'] = function($menu) {
        // Siempre aplicar estructura completa; preservar 'types' de menu_toadd
        $existing_types = $menu['ciscontrols']['types'] ?? [];
        {
            $menu['ciscontrols'] = [
                'title'   => 'CIS Controls',
                'icon'    => 'ti ti-shield-check',
                'default' => '/plugins/ciscontrols/front/ciscontrol.php',
                'content' => [
                    'dashboard'   => ['title' => 'Dashboard',       'page' => '/plugins/ciscontrols/front/dashboard.php',    'icon' => 'ti ti-chart-bar'],
                    'controls'    => ['title' => 'Controles',        'page' => '/plugins/ciscontrols/front/ciscontrol.php',   'icon' => 'ti ti-list-check'],
                    'executions'  => ['title' => 'Ejecuciones',      'page' => '/plugins/ciscontrols/front/cisexecution.php', 'icon' => 'ti ti-clipboard-check'],
                    'cislib'      => ['title' => 'Biblioteca CIS',   'page' => '/plugins/ciscontrols/front/cislib.php',       'icon' => 'ti ti-books'],
                    'doctree'     => ['title' => 'Documentación',    'page' => '/plugins/ciscontrols/front/doctree.php',      'icon' => 'ti ti-folder-open'],
                    'risks'       => ['title' => 'Matriz de Riesgos', 'page' => '/plugins/ciscontrols/front/risk.php',         'icon' => 'ti ti-alert-triangle'],
                    'config'      => ['title' => 'Configuración',    'page' => '/plugins/ciscontrols/front/config.php',       'icon' => 'ti ti-settings'],
                ],
            ];
        }
        return $menu;
    };
}
