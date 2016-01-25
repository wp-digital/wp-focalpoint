<?php
/**
 * Created by PhpStorm.
 * User: vitaly
 * Date: 12/18/15
 * Time: 12:43
 */

namespace focalpoint\admin;


class Settings {

	/**
	 * Name for options to store
	 */
	const OPTIONS_NAME = 'focalpoint_options';

	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Settings constructor.
	 *
	 * @param $plugin_name
	 * @param $version
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	public static function get($name)
	{
		$options = get_option(self::OPTIONS_NAME);

		return $options[ $name ];
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		add_options_page(
			'Focalpoint Settings',
			'Focalpoint',
			'manage_options',
			'focalpoint-setting',
			array($this, 'create_admin_page')
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		// Set class property
		$this->options = get_option(self::OPTIONS_NAME);
		?>
		<div class="wrap">
			<h2>Focalpoint Settings</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields('focalpoint_general');
				do_settings_sections('focalpoint-setting');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		register_setting(
			'focalpoint_general',
			'focalpoint_options',
			array($this, 'sanitize')
		);

		add_settings_section(
			'general',
			__('General', $this->plugin_name),
			array($this, 'print_section_info'),
			'focalpoint-setting'
		);


		add_settings_field(
			'upscale',
			__('Upscale images?', $this->plugin_name),
			array($this, 'upscale_callback'),
			'focalpoint-setting',
			'general'
		);

		add_settings_field(
			'unique_url',
			__('Add unique image url for each new focal point position?', $this->plugin_name),
			array($this, 'unique_url_callback'),
			'focalpoint-setting',
			'general'
		);

		add_settings_field(
			'sizes_to_show',
			__('Select image sizes to show in preview', $this->plugin_name),
			array($this, 'sizes_callback'),
			'focalpoint-setting',
			'general'
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param $input array Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function sanitize($input)
	{
		$new_input = array();
		if (isset($input['upscale'])) {
			$new_input['upscale'] = (bool) $input['upscale'];
		}

		if (isset($input['unique_url'])) {
			$new_input['unique_url'] = (bool) $input['unique_url'];
		}

		if (isset($input['sizes_to_show'])) {

			$new_input['sizes_to_show'] = $input['sizes_to_show'];
		}

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info()
	{
		//print '';
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function upscale_callback()
	{
		printf(
			'<input type="checkbox" id="upscale" name="focalpoint_options[upscale]" %s />',
			checked(true, $this->options['upscale'], false)
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function unique_url_callback()
	{
		printf(
			'<input type="checkbox" id="unique_url" name="focalpoint_options[unique_url]" %s /><p class="description">%s</p>',
			checked(true, $this->options['unique_url'], false),
			__('Could be useful when you using CDN or else image caching system', $this->plugin_name)
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function sizes_callback()
	{
		global $_wp_additional_image_sizes;
		foreach ($_wp_additional_image_sizes as $name => $image_sizes) {
			printf(
				'<input type="checkbox" id="sizes-%s" name="focalpoint_options[sizes_to_show][]" value="%s" %s/><label for="sizes-%s">%s [%s]</label><br>',
				$name,
				$name,
				checked(true, in_array($name, $this->options['sizes_to_show']), false),
				$name,
				str_replace(array('-', '_'), ' ', ucfirst($name)),
				$image_sizes['width'] . 'x' . $image_sizes['height']


			);
		}

	}
}