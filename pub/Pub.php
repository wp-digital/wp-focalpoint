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

namespace focalpoint\pub;

/**
 * The public-specific functionality of the plugin.
 *
 * @package    Focalpoint
 * @subpackage Focalpoint/public
 * @author     Vitaly Nikolaev <vitaly@pingbull.no>
 */
class Pub {

	public static function add_timestamp_to_url($image, $id)
	{
		if ( !is_admin()) {
			if ($image) {
				$metadata = wp_get_attachment_metadata($id);
				if ( !empty($metadata['image_meta']['focalpoint_timestamp'])) {
					$timestamp = $metadata['image_meta']['focalpoint_timestamp'];
					$image[0]  = add_query_arg(['fpv' => $timestamp], $image[0]);
				};
			}
		}

		return $image;
	}
}
