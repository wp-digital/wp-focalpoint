<?php
/*
Plugin Name: Focal Point for images
Version: 0.1-alpha
Description: Allow you to select center point of interest on image
Author: Vitaly Nikolev
*/

define('FOCALPOINT_VERSION', '0.1');

// Include other files.
include(dirname(__FILE__) . '/FocalPointMisc.php');

// Add hooks.
add_action('attachment_fields_to_edit', 'FocalPointMisc::attachment_fields_to_edit', 20, 2);
add_action('attachment_fields_to_save', 'FocalPointMisc::attachment_fields_to_save', 10, 2);
add_action('image_resize_dimensions', 'FocalPointMisc::image_resize_dimensions', 10, 6);
add_action('wp_get_attachment_metadata', 'FocalPointMisc::wp_get_attachment_metadata', 10, 2);
add_action('get_attached_file', 'FocalPointMisc::get_attached_file', 10, 2);
add_action('admin_enqueue_scripts', 'FocalPointMisc::admin_enqueue_scripts');