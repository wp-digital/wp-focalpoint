<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Admin
 * @subpackage Admin/admin
 */

namespace focalpoint\admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Admin
 * @subpackage Admin/admin
 * @author     Vitaly Nikolaev <vitaly@pingbull.no>
 */
class Admin {

	const
		META_POSITION_PICKER = 'focalpoint_positionPicker',
		META_POSITION = 'focalpoint_position';

	static $lastPostId;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name
	 * @param      string $version
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	public static function image_resize_dimensions($something, $orig_w, $orig_h, $dest_w, $dest_h, $crop)
	{
		if ( !$crop || self::$lastPostId === null) {
			return null;
		}
		$meta = self::get_position(self::$lastPostId);

		$aspect_ratio = $orig_w / $orig_h;

		if (Settings::get('upscale')) {
			$new_w = $dest_w;
			$new_h = $dest_h;
		} else {
			$new_w = min($dest_w, $orig_w);
			$new_h = min($dest_h, $orig_h);
		}

		if ( !$new_w) {
			$new_w = intval($new_h * $aspect_ratio);
		}

		if ( !$new_h) {
			$new_h = intval($new_w / $aspect_ratio);
		}

		$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

		$crop_w = round($new_w / $size_ratio);
		$crop_h = round($new_h / $size_ratio);

		$s_x = floor(($orig_w * $meta[0]) - $crop_w / 2);
		$s_y = floor(($orig_h * $meta[1]) - $crop_h / 2);

		//check if we are not out of image
		$s_x = $s_x < 0 ? 0 : $s_x;
		$s_x = $s_x > ($orig_w - $crop_w) ? $orig_w - $crop_w : $s_x;
		$s_y = $s_y < 0 ? 0 : $s_y;
		$s_y = $s_y > ($orig_h - $crop_h) ? $orig_h - $crop_h : $s_y;

		// If the resulting image would be the same size or larger we don't want to resize it
		if ( !Settings::get('upscale') && $new_w >= $orig_w && $new_h >= $orig_h) {
			return false;
		}

		return array(0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h);
	}

	public static function wp_get_attachment_metadata($data, $postId)
	{
		self::$lastPostId = $postId;

		return $data;
	}

	public static function get_attached_file($file, $attachment_id)
	{
		self::$lastPostId = $attachment_id;

		return $file;
	}

	public function attachment_fields_to_save($post, $attachment)
	{
		if (isset($attachment[ self::META_POSITION ]) && $attachment[ self::META_POSITION ]) {
			$previousPosition = self::get_position($post['ID']);
			$position         = json_decode($attachment[ self::META_POSITION ]);
			update_post_meta($post['ID'], self::META_POSITION, $position);

			// Update the thumbnail if the position changed
			if ($previousPosition[0] != $position[0] || $previousPosition[1] != $position[1]) {
				$suffix           = '-focalcropped';
				$realOldImagePath = get_attached_file($post['ID']);
				$oldImagePath     = str_ireplace($suffix, '', $realOldImagePath);
				if ( !file_exists($oldImagePath)) {
					return $post;
				}
				// Rename file, while keeping the previous version
				// Generate new filename
				$oldImagePathParts = pathinfo($oldImagePath);

				$filename = $oldImagePathParts['filename'];
				$filename = str_ireplace($suffix, '', $filename);
				$filename .= $suffix;

				$newImageFile = "{$filename}.{$oldImagePathParts['extension']}";
				$newImagePath = "{$oldImagePathParts['dirname']}/$newImageFile";

				if (copy($oldImagePath, $newImagePath) === false) {
					$post['errors']['focal_point'] = sprintf(__('Could not copy file %s to %s.', $this->plugin_name), $oldImagePath, $newImagePath);

					return $post;
				};
				$url = $newImagePath;

				//@set_time_limit(900); // 5 minutes per image should be PLENTY
				update_attached_file($post['ID'], $url);
				$metadata = wp_generate_attachment_metadata($post['ID'], $url);
				if ( !$metadata || is_wp_error($metadata)) {
					$post['errors']['focal_point'] = __('Empty metadata.', $this->plugin_name);

					return $post;
				}
				if (isset($metadata['image_meta'])) {
					$metadata['image_meta']['focalpoint_timestamp'] = time();
				}
				wp_update_attachment_metadata($post['ID'], $metadata);
				clean_post_cache($post['ID']);
			}
		}

		return $post;
	}

	/**
	 * Get position of focal point for image
	 *
	 * @param $image_id integer ID of image
	 *
	 * @return array
	 */
	public static function get_position($image_id)
	{
		$meta = get_post_meta($image_id, self::META_POSITION, true);
		if ( !$meta) {
			$meta = array(0.5, 0.5);
		}

		return $meta;
	}

	/**
	 * Add fields to the Media Upload dialog.
	 *
	 * @param $form_fields
	 * @param $post
	 *
	 * @return array
	 */

	public function attachment_fields_to_edit($form_fields, $post)
	{
		if ( !is_array($form_fields)) {
			$form_fields = array();
		}
		$sizes = [];

		// Get saved values.
		$meta = self::get_position($post->ID);

		// Create HTML.
		$html      = '';
		$image     = wp_get_attachment_image_src($post->ID, 'medium');
		$imageId   = 'focalpoint_picker_image_' . $post->ID;
		$previewId = 'focalpoint_picker_preview_' . $post->ID;
		$html .= '<p><span class="_targetIcon"></span>' . __('Click on the point of interest (the area you want included in the thumbnail).', $this->plugin_name) . '</p>';
		$html .= '<div id="' . $imageId . '" class="_picker"><img src="' . $image[0] . '"></div>';
		$html .= '<br><button class="button button-small smartthumbnailupdate">Update</button>&nbsp;<span class="hidden" style="line-height: 26px;">You can go ahead. Resize will be finished in background.</span>';
		$html .= '<p><span class="_previewIcon"></span><strong>' . __('Preview') . '</strong> (' . __('this is how the thumbnails will look, depending on the size used') . '):</p>';
		$html .= '<div id="' . $previewId . '" class="_preview"></div>';
		$html = '<div class="focalpoint_mediaUpload">' . $html . '</div>';

		// Create JavaScript.
		$size          = 150;
		$sizes_to_show = Settings::get('sizes_to_show');
		global $_wp_additional_image_sizes;
		if ( !empty($_wp_additional_image_sizes)) {
			foreach ($_wp_additional_image_sizes as $name => $image_sizes) {
				if ( !empty($sizes_to_show)) {
					if ( !in_array($name, $sizes_to_show)) {
						continue;
					}
				}
				if (strpos($name, 'lazy') || strpos($name, 'side')) {
					continue;
				}
				$ratio = $size / max($image_sizes['width'], $image_sizes['height']);
				$ratio = $ratio > 1 ? 1 : $ratio;

				$sizes[ str_replace('-', ' ', ucfirst($name)) ] = [
					'width'  => $ratio * $image_sizes['width'],
					'height' => $ratio * $image_sizes['height'],
				];
			}
		}

		// The script will initialize the picker. If the elements do not yet exist, it will wait a while until retrying.
		$script = '
			jQuery(document).ready(function() {
				focalpoint.createPickerDelayed({
					attachmentId: "' . $post->ID . '",
					image: "#' . $imageId . '",
					input: "input[name=\'attachments[' . $post->ID . '][' . self::META_POSITION . ']\']",
					preview: "#' . $previewId . '",
					sizes: ' . json_encode($sizes) . ',
					position: {
						x: ' . $meta[0] . ',
						y: ' . $meta[1] . '
					}
				});
			});
		';
		$html .= '<script type="text/javascript">' . $script . '</script>';

		$form_fields[ self::META_POSITION ]        = array(
			'label' => '',
			'input' => 'text'
		);
		$form_fields[ self::META_POSITION_PICKER ] = array(
			'label' => '',
			'input' => 'html',
			'html'  => $html
		);

		return $form_fields;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/focalpoint-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/focalpoint-admin.js', array('jquery'), $this->version, true);
	}

}
