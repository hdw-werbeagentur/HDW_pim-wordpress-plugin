<?php

/**
 * Plugin Name:     [Project] DMS Importer
 * Plugin URI:      https://www.hdw1.de
 * Description:     Import [Project] Products from HDW DMS
 * Author:          Anthony Spross
 * Author URI:      https://www.hdw1.de
 * Text Domain:     hdw-dms-importer
 * Domain Path:     /languages
 * Version:         1.0.12
 *
 * @package         HDW/[...]
 */

namespace HDW\ProjectDmsImporter;

use HDW\ProjectDmsImporter\Admin;
use HDW\ProjectDmsImporter\Cron;
use Horttcore\Plugin\PluginFactory;

// ------------------------------------------------------------------------------
// Prevent direct file access
// ------------------------------------------------------------------------------
if (!defined('WPINC')) :
    die;
endif;

// ------------------------------------------------------------------------------
// Autoloader
// ------------------------------------------------------------------------------
$autoloader = dirname(__FILE__) . '/vendor/autoload.php';

if (is_readable($autoloader)) :
    require_once $autoloader;
endif;

// ------------------------------------------------------------------------------
// Bootstrap
// ------------------------------------------------------------------------------
PluginFactory::create()
    ->addTranslation('hdw-dms-importer', dirname(plugin_basename(__FILE__)) . '/languages/')
    ->addService(Admin::class)
    ->addService(Import::class)
    ->addService(Cron::class)
    ->boot();