<?php
/**
 * Plugin Name: URL Media Uploader
 * Description: Easily upload media files to your WordPress media library by pasting URLs. Import images, videos, and other media directly from external links without manual download.
 * Version: 1.0.1
 * Author: apprhyme
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: url-media-uploader
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function url_media_uploader_enqueue_scripts($hook) {
    if ('post.php' !== $hook && 
        'post-new.php' !== $hook && 
        'upload.php' !== $hook && 
        'skip' !== $hook
    ) {
        return;
    }

    wp_enqueue_script('url-media-uploader-js', plugin_dir_url(__FILE__) . 'js/url-media-uploader.js', array('jquery'), '1.0.0', true);
    wp_enqueue_style('url-media-uploader-css', plugin_dir_url(__FILE__) . 'css/url-media-uploader.css', array(), '1.0.0');
    
    wp_localize_script('url-media-uploader-js', 'urlMediaUploader', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('url_media_uploader_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'url_media_uploader_enqueue_scripts');


// Handle Elementor
if (has_action('elementor/editor/before_enqueue_scripts')) {
	add_action( 'elementor/editor/before_enqueue_scripts', function() {
		url_media_uploader_enqueue_scripts('skip');
	} );
}

// Handle Divi
add_action('wp_enqueue_scripts', function() {
	if (function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled()) {
		url_media_uploader_enqueue_scripts('skip');
	}
});

function url_media_uploader_url_upload_ajax_handler() {
    if (!check_ajax_referer('url_media_uploader_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Nonce verification failed'));
        return;
    }

    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

    if (strpos($url, site_url()) !== false) {
        $attachment_id = attachment_url_to_postid($url);;
        if ($attachment_id) {
            wp_send_json_success(array('message' => 'Media already exists!', 'attachment_id' => $attachment_id));
            return;
        }
    }

    $result = url_media_uploader_download_and_attach_media_from_url($url);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    } else {
        wp_send_json_success(array('message' => 'Media uploaded successfully!', 'attachment_id' => $result));
    }
}
add_action('wp_ajax_url_media_uploader_url_upload', 'url_media_uploader_url_upload_ajax_handler');

function url_media_uploader_download_and_attach_media_from_url($url) {
    if (empty($url)) {
        return new WP_Error('no_url_provided', 'No URL provided.');
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return new WP_Error('invalid_url', 'Invalid URL.');
    }

    $parsed_url = parse_url($url);
    if (!$parsed_url || !isset($parsed_url['host'])) {
        return new WP_Error('invalid_url', 'Invalid URL format.');
    }

    $ip = gethostbyname($parsed_url['host']);

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return new WP_Error('blocked_ip', 'URL points to an internal or reserved IP.');
    }

    $tmp = download_url($url);
    if (is_wp_error($tmp)) {
        return $tmp;
    }

    $file_array = array(
        'name' => basename($url),
        'tmp_name' => $tmp
    );

    $file_type = wp_check_filetype_and_ext($tmp, $file_array['name']);
    if ($file_type['type'] === false) {
        wp_delete_file($tmp);
        return new WP_Error('invalid_file_type', 'Invalid file type.');
    }

    $id = media_handle_sideload($file_array, 0, preg_replace('/\.\w+$/', '', basename($url)), array('test_form' => false));
    if (is_wp_error($id)) {
        wp_delete_file($file_array['tmp_name']);
        return $id;
    }

    return $id;
}

?>