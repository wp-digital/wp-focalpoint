<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Admin
 *
 * @wordpress-plugin
 * Plugin Name:       Focal Point for images
 * Plugin URI:        http://example.com/plugin-name-uri/
 * Description:       Allow you to select center point of interest on image
 * Version:           0.1-alpha
 * Author:            Vitaly Nikolev
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       plugin-name
 * Domain Path:       /languages
 */

namespace focalpoint;

use focalpoint\includes\Activator;
use focalpoint\includes\Deactivator;
use focalpoint\includes\Focalpoint;

// If this file is called directly, abort.
if ( !defined('WPINC')) {
	die;
}

function autoload($className)
{
	if (strpos($className, 'focalpoint') !== 0) {
		return;
	}

	$className = str_replace('focalpoint', '', $className);
	$className = ltrim($className, '\\');
	$fileName  = '';
	$namespace = '';
	if ($lastNsPos = strrpos($className, '\\')) {
		$namespace = substr($className, 0, $lastNsPos);
		$className = substr($className, $lastNsPos + 1);
		$fileName  = plugin_dir_path(__FILE__) . str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	}
	$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

	require $fileName;
}

spl_autoload_register('focalpoint\autoload');


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_plugin_name()
{
	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class--deactivator.php
 */
function deactivate_plugin_name()
{
	Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_plugin_name');
register_deactivation_hook(__FILE__, 'deactivate_plugin_name');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_name()
{

	$plugin = new Focalpoint();
	$plugin->run();

}

run_plugin_name();


