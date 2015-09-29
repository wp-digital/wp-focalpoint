<?php

class FocalPointMisc {
	const
		META_POSITION_PICKER = 'focalpoint_positionPicker',
		META_POSITION = 'focalpoint_position';

	/**
	 * This variable stores the last post ID used in "get_attached_file" or "wp_get_attachment_metadata".
	 * These functions are always called before "image_resize_dimensions".
	 * Using this, we can get the post's meta data in "image_resize_dimensions".
	 */
	private static $lastPostId = null;

	// Add fields to the Media Upload dialog.
	public static function attachment_fields_to_edit($form_fields, $post)
	{
		if ( !is_array($form_fields)) {
			$form_fields = array();
		}
		$sizes = [];

		// Get saved values.
		$meta = self::getMeta($post->ID);

		// Create HTML.
		$html      = '';
		$image     = wp_get_attachment_image_src($post->ID, 'medium');
		$imageId   = 'focalpoint_picker_image_' . $post->ID;
		$previewId = 'focalpoint_picker_preview_' . $post->ID;
		$html .= '<p><span class="_targetIcon"></span>' . __('Click on the point of interest (the area you want included in the thumbnail).') . '</p>';
		$html .= '<div id="' . $imageId . '" class="_picker"><img src="' . $image[0] . '"></div>';
		$html .= '<br><button class="button button-small smartthumbnailupdate">Update</button>&nbsp;<span class="hidden" style="line-height: 26px;">You can go ahead. Resize will be finished in background.</span>';
		$html .= '<p><span class="_previewIcon"></span><strong>' . __('Preview') . '</strong> (' . __('this is how the thumbnails will look, depending on the size used') . '):</p>';
		$html .= '<div id="' . $previewId . '" class="_preview"></div>';
		$html = '<div class="focalpoint_mediaUpload">' . $html . '</div>';

		// Create JavaScript.
		$size = 150;
		global $_wp_additional_image_sizes;
		foreach ($_wp_additional_image_sizes as $name => $image_sizes) {
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

		// Add form field
		$form_fields[ self::META_POSITION ]        = array(
			'label' => '',
			'input' => 'text'
		);
		$form_fields[ self::META_POSITION_PICKER ] = array(
			'label' => '',
			'input' => 'html',
			'html'  => $html
		);

		// Return fields
		return $form_fields;
	}

	public static function getMeta($postId)
	{
		$meta = get_post_meta($postId, self::META_POSITION, true);
		if ( !$meta) {
			$meta = array(0.5, 0.5);
		}

		return $meta;
	}

	public static function attachment_fields_to_save($post, $attachment)
	{

		if (isset($attachment[ self::META_POSITION ]) && $attachment[ self::META_POSITION ]) {
			$previousPosition = self::getMeta($post['ID']);
			$position         = json_decode($attachment[ self::META_POSITION ]);
			update_post_meta($post['ID'], self::META_POSITION, $position);

			// Update the thumbnail if the position changed
			if ($previousPosition[0] != $position[0] || $previousPosition[1] != $position[1]) {
				$realOldImagePath = get_attached_file($post['ID']);
				$oldImagePath     = str_ireplace('-focalcropped', '', $realOldImagePath);
				// Rename file, while keeping the previous version
				// Generate new filename
				$oldImagePathParts = pathinfo($oldImagePath);
				$filename          = $oldImagePathParts['filename'];
				$suffix            = '-focalcropped';//time() . rand(100, 999);
				$filename          = str_ireplace('-focalcropped', '', $filename);

				$filename .= $suffix;
				$newImageFile = "{$filename}.{$oldImagePathParts['extension']}";
				$newImagePath = "{$oldImagePathParts['dirname']}/$newImageFile";
				$url          = '';
				if (copy($oldImagePath, $newImagePath)) {
					$url = $newImagePath;
				};
				if ( !$url) {
					return false;
				}
				@set_time_limit(900); // 5 minutes per image should be PLENTY
				update_attached_file($post['ID'], $url);
				$metadata = wp_generate_attachment_metadata($post['ID'], $url);
				if ( !$metadata || is_wp_error($metadata)) {
					wp_send_json_error('empty metadata');
				}
				wp_update_attachment_metadata($post['ID'], $metadata);
				//@unlink($file);
				clean_post_cache($post['ID']);
			}
		}

		return $post;
	}

	public static function get_attached_file($file, $attachment_id)
	{
		self::$lastPostId = $attachment_id;

		return $file;
	}

	public static function wp_get_attachment_metadata($data, $postId)
	{
		self::$lastPostId = $postId;

		return $data;
	}

	// Get the saved crop position of an image.

	public static function image_resize_dimensions($something, $orig_w, $orig_h, $dest_w, $dest_h, $crop)
	{
		if ( !$crop || self::$lastPostId === null) {
			return null;
		}
		$meta = self::getMeta(self::$lastPostId);

		$aspect_ratio = $orig_w / $orig_h;
		$new_w        = min($dest_w, $orig_w);
		$new_h        = min($dest_h, $orig_h);

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
		if ($new_w >= $orig_w && $new_h >= $orig_h) {
			return false;
		}

		return array(0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h);
	}

	// Enqueue JavaScript and CSS for the admin interface.

	public static function admin_enqueue_scripts()
	{
		// Admin JS

		wp_register_script('focalpoint-admin.js', plugin_dir_url(__FILE__) . '/js/focalpoint-admin.js', array('jquery'), FOCALPOINT_VERSION, true);
		wp_enqueue_script('focalpoint-admin.js');

		// Admin CSS
		wp_register_style('focalpoint-admin', plugin_dir_url(__FILE__) . '/css/admin.css', FOCALPOINT_VERSION);
		wp_enqueue_style('focalpoint-admin');
	}
}
