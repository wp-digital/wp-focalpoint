<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://redink.no
 * @since      1.0.0
 *
 * @package    Admin
 * @subpackage Admin/includes
 */
namespace focalpoint\includes;
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Admin
 * @subpackage Admin/includes
 * @author     Vitaly Nikolaev <vitaly@pingbull.no>
 */
class i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain()
	{

		load_plugin_textdomain(
			'wp-focalpoint',
			false,
			dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
		);

	}


}
