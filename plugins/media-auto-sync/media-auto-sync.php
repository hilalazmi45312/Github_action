<?php

/**
 * Plugin Name: Media Auto Sync
 * Description: Automatically sync uploaded media to another WordPress site via REST API.
 * Version: 1.0.0
 * Author: Cloone Corportation
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Add Settings Page to the Admin Menu
 */
add_action('admin_menu', 'mas_add_settings_page');

function mas_add_settings_page()
{
    add_options_page(
        'Media Auto Sync Settings',  // Page title
        'Media Auto Sync',           // Menu title
        'manage_options',            // Capability required
        'media-auto-sync',           // Menu slug
        'mas_render_settings_page'   // Callback function
    );
}

/**
 * Render the Settings Page
 */
function mas_render_settings_page()
{
?>
    <div class="wrap">
        <h1>Media Auto Sync Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('mas_settings_group');
            do_settings_sections('media-auto-sync');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Username</th>
                    <td><input type="text" name="mas_api_user" value="<?php echo esc_attr(get_option('mas_api_user')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API Password</th>
                    <td><input type="password" name="mas_api_pass" value="<?php echo esc_attr(get_option('mas_api_pass')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Target Endpoint URL</th>
                    <td><input type="url" name="mas_target_endpoint" value="<?php echo esc_attr(get_option('mas_target_endpoint')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

/**
 * Register and initialize the settings
 */
add_action('admin_init', 'mas_register_settings');

function mas_register_settings()
{
    register_setting('mas_settings_group', 'mas_api_user');
    register_setting('mas_settings_group', 'mas_api_pass');
    register_setting('mas_settings_group', 'mas_target_endpoint'); // Register target endpoint setting
}

/**
 * Trigger when attachment is created
 */
add_action('add_attachment', 'mas_schedule_media_sync');

function mas_schedule_media_sync($attachment_id)
{

    // Only sync images
    if (! wp_attachment_is_image($attachment_id)) {
        return;
    }

    // Prevent infinite loop
    if (get_post_meta($attachment_id, '_mas_synced', true)) {
        return;
    }

    // Schedule async job
    wp_schedule_single_event(
        time() + 10,
        'mas_sync_media_event',
        [$attachment_id]
    );
}

/**
 * Background job handler
 */
add_action('mas_sync_media_event', 'mas_sync_media_to_remote_site');

function mas_sync_media_to_remote_site($attachment_id)
{

    $file_path = get_attached_file($attachment_id);

    if (! file_exists($file_path)) {
        return;
    }

    $file_name = basename($file_path);
    $mime_type = get_post_mime_type($attachment_id);

    // Get the API credentials and target endpoint from the options table
    $api_user = get_option('mas_api_user');
    $api_pass = get_option('mas_api_pass');
    $target_endpoint = get_option('mas_target_endpoint');

    // Ensure credentials and endpoint are set
    if (empty($api_user) || empty($api_pass) || empty($target_endpoint)) {
        return; // You can log an error here if needed
    }

    // Construct the Authorization header
    $auth_header = 'Basic ' . base64_encode($api_user . ':' . $api_pass);

    // Send the media to the target site via the REST API
    $response = wp_remote_post(
        $target_endpoint, // Use the target endpoint from settings
        [
            'timeout' => 20,
            'headers' => [
                'Authorization'       => $auth_header,
                'Content-Disposition' => 'attachment; filename="' . $file_name . '"',
                'Content-Type'        => $mime_type,
            ],
            'body' => file_get_contents($file_path),
        ]
    );

    if (is_wp_error($response)) {
        return;
    }

    $code = wp_remote_retrieve_response_code($response);

    if ($code >= 200 && $code < 300) {
        update_post_meta($attachment_id, '_mas_synced', true);
    }
}