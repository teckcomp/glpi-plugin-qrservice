<?php
/**
 * -------------------------------------------------------------------------
 * QR Service plugin for GLPI
 * Abertura Inteligente de Chamados via QR Code
 * -------------------------------------------------------------------------
 */
// Carrega o autoloader do plugin
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (!defined('PLUGIN_QRSERVICE_VERSION')) {
    define('PLUGIN_QRSERVICE_VERSION', '0.1.0-alpha');

    define('PLUGIN_QRSERVICE_MIN_GLPI_VERSION', '11.0.0');
    define('PLUGIN_QRSERVICE_MAX_GLPI_VERSION', '11.99.99');
}

function plugin_version_qrservice()
{
    return [
        'name'         => 'QR Service',
        'version'      => PLUGIN_QRSERVICE_VERSION,
        'author'       => 'Claudio Morett',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/teckcomp/glpi-plugin-qrservice',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_QRSERVICE_MIN_GLPI_VERSION,
                'max' => PLUGIN_QRSERVICE_MAX_GLPI_VERSION,
            ],
            'php' => [
                'min' => '8.1',
            ],
        ],
    ];
}

function plugin_init_qrservice()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['qrservice'] = true;

    \Glpi\Http\SessionManager::registerPluginStatelessPath(
        'qrservice',
        '#^/front/formulario\.php#'
    );
    \Glpi\Http\SessionManager::registerPluginStatelessPath(
        'qrservice',
        '#^/ajax/localizacoes\.php#'
    );
    \Glpi\Http\SessionManager::registerPluginStatelessPath(
        'qrservice',
        '#^/ajax/logo\.php#'
    );

    $PLUGIN_HOOKS['config_page']['qrservice'] = 'front/config.php';

    Plugin::registerClass(\GlpiPlugin\Qrservice\Cliente::class);
    Plugin::registerClass(\GlpiPlugin\Qrservice\QrCode::class, [
        'addtabon' => [\GlpiPlugin\Qrservice\QrCode::class],
    ]);
    Plugin::registerClass(\GlpiPlugin\Qrservice\Campo::class);

    $PLUGIN_HOOKS['menu_toadd']['qrservice'] = [
        'admin' => \GlpiPlugin\Qrservice\QrCode::class,
    ];
}

function plugin_qrservice_check_prerequisites()
{
    return true;
}

function plugin_qrservice_check_config($verbose = false)
{
    return true;
}
