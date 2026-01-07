<?php
/**
 * Plugin Name: Yoast Meta Import Export
 * Description: Export and import Yoast SEO meta fields as CSV. Use at your own risk. Always back up your database before importing.
 * Version: 0.0.1
 * Author: Kavit Trivedi (WPVIP)
 * License: GPL v2 or later
 * Text Domain: yoast-meta-ie
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'YOAST_META_IE_VERSION', '0.0.1' );
define( 'YOAST_META_IE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YOAST_META_IE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload classes
/**
 * Autoloads plugin classes using PSR-4 naming convention.
 *
 * @param string $class The fully qualified class name.
 */
spl_autoload_register( function ( $class ) {
    $prefix = 'Yoast_Meta_IE\\';
    $base_dir = YOAST_META_IE_PLUGIN_DIR . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// Initialize the plugin
/**
 * Initializes the plugin by instantiating admin and AJAX handlers.
 * Only activates if Yoast SEO is installed and active.
 */
function yoast_meta_ie_init() {
    // Check if Yoast SEO is active
    if ( ! defined( 'WPSEO_VERSION' ) && ! class_exists( 'WPSEO_Options' ) ) {
        add_action( 'admin_notices', 'yoast_meta_ie_yoast_missing_notice' );
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
        return;
    }

    if ( is_admin() ) {
        new Yoast_Meta_IE\Admin\Admin();
    }
    new Yoast_Meta_IE\Ajax\Ajax_Handlers();
    new Yoast_Meta_IE\Ajax\CSV_Converter();
}
add_action( 'plugins_loaded', 'yoast_meta_ie_init' );

/**
 * Displays an admin notice when Yoast SEO is not installed.
 */
function yoast_meta_ie_yoast_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e( 'Yoast Meta Import Export requires Yoast SEO to be installed and activated.', 'yoast-meta-ie' ); ?></p>
        <p><?php _e( 'Please install and activate Yoast SEO before using this plugin.', 'yoast-meta-ie' ); ?></p>
    </div>
    <?php
}