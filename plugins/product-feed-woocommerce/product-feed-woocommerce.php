<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.woocommerce.com
 * @since             1.0.0
 * @package           Webtoffee_Product_Feed_Sync_Pro
 *
 * @wordpress-plugin
 * Plugin Name:       All in one Product Feed for WooCommerce
 * Plugin URI:        https://woocommerce.com/products/product-feed/
 * Description:       Lets you generate WooCommerce product feeds for Google Merchant Center, Facebook/Instagram shop, TikTok Ads, Pinterest and more.
 * Version:           1.0.4
 * Author:            WebToffee
 * Author URI:        https://www.woocommerce.com/vendor/webtoffee/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       product-feed-woocommerce
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Tested up to: 6.8.3
 * Woo: 18734005025675:3b53e39844c63aaddcef1ee94f68b87f
 * WC requires at least: 3.0
 * WC tested up to: 10.3.5
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION', '1.0.4' );
define( 'WEBTOFFEE_PRODUCT_FEED_PRO_ID', 'webtoffee_product_feed_main_pro_export' );
define( 'WT_PRODUCT_FEED_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WT_PRODUCT_FEED_PRO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WT_PRODUCT_FEED_PRO_PLUGIN_FILENAME', __FILE__ );
if ( ! defined( 'WT_PRODUCT_FEED_PRO_BASE_NAME' ) ) {
	define( 'WT_PRODUCT_FEED_PRO_BASE_NAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'WEBTOFFEE_PRODUCT_FEED_MAIN_PRO_ID' ) ) {
	define( 'WEBTOFFEE_PRODUCT_FEED_MAIN_PRO_ID', 'webtoffee_product_feed_main_pro_export' );
}

if ( ! defined( 'WT_PF_DEBUG_PRO' ) ) {
	define( 'WT_PF_DEBUG_PRO', false );
}



if ( ! defined( 'WT_PF_PLUGIN_NAME' ) ) {
	define(
		'WT_PF_PLUGIN_NAME',
		'product-feed-woocommerce'
	);
	define( 'WT_PF_PLUGIN_ID', 'webtoffee_product_feed_main_pro_export' );
	define( 'WT_PF_SETTINGS_FIELD', WT_PF_PLUGIN_NAME ); /* option name to store settings */
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-webtoffee-product-feed-sync-pro-activator.php
 */
function activate_webtoffee_product_feed_pro() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-webtoffee-product-feed-sync-pro-activator.php';
	Webtoffee_Product_Feed_Sync_Pro_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-webtoffee-product-feed-sync-pro-deactivator.php
 */
function deactivate_webtoffee_product_feed_pro() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-webtoffee-product-feed-sync-pro-deactivator.php';
	Webtoffee_Product_Feed_Sync_Pro_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_webtoffee_product_feed_pro' );
register_deactivation_hook( __FILE__, 'deactivate_webtoffee_product_feed_pro' );



/* Checking WC is actived or not */
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_action( 'plugins_loaded', 'wt_feed_check_for_woocommerce' );

if ( ! function_exists( 'wt_feed_check_for_woocommerce' ) ) {
	/**
	 * WooCommerce active check
	 */
	function wt_feed_check_for_woocommerce() {

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) || ! defined( 'WC_VERSION' ) ) {
			add_action( 'admin_notices', 'wt_wc_missing_warning_for_feed' );
		}
		if ( ! function_exists( 'wt_wc_missing_warning_for_feed' ) ) {
			/**
			 * WooCommerce missing warning
			 */
			function wt_wc_missing_warning_for_feed() {

				$install_url = wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'install-plugin',
							'plugin' => 'woocommerce',
						),
						admin_url( 'update.php' )
					),
					'install-plugin_woocommerce'
				);
				$class       = 'notice notice-error';
				$post_type   = 'product';
				$message     = sprintf(
										/* translators: 1: Post type. 2: Installation URL  */
					__( 'The <b>WooCommerce</b> plugin must be active for <b> WebToffee WooCommerce %1$s Feed & Sync Manager Pro</b> plugin to work.  Please <a href="%2$s" target="_blank">install & activate WooCommerce</a>.', 'product-feed-woocommerce' ),
					ucfirst( $post_type ),
					esc_url( $install_url )
				);
				printf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
			}
		}
	}
}


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-webtoffee-product-feed-sync-pro.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-wt-productfeed-uninstall-feedback.php';


// WooCommerce HPOS compatibility decleration.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_webtoffee_product_feed_pro() {

	$plugin = new Webtoffee_Product_Feed_Sync_Pro();
	$plugin->run();
}

run_webtoffee_product_feed_pro();
