<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 *
 * Plugin Name:       Focal Point for images
 * Description:       Allow you to select center point of interest on image
 * Version:           0.2
 * Author:            Vitaly Nikolev
 * Author URI:        http://redink.no
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       focalpoint
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
 */
function activate_wpfocalpoint()
{
	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wpfocalpoint()
{
	Deactivator::deactivate();
}

//register_activation_hook(__FILE__, 'activate_wpfocalpoint');
//register_deactivation_hook(__FILE__, 'deactivate_wpfocalpoint');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpfocalpoint()
{
	$plugin = new Focalpoint();
	$plugin->run();
}

run_wpfocalpoint();


