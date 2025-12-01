<?php
/**
 * Plugin Name: Jagif - WooCommerce Free Gift
 * Plugin URI: https://villatheme.com/extensions/jagif-woo-free-gift/
 * Description: Offer free gifts with purchases using custom rules. Highlight eligible products with visual gift icons to inform and entice customers
 * Version: 1.1.5
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jagif-woo-free-gift
 * Domain Path: /languages
 * Copyright 2022-2025 VillaTheme.com. All rights reserved.
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 6.8
 * WC requires at least: 7.0
 * WC tested up to: 9.8
 * Requires PHP: 7.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'VIJAGIF_WOO_FREE_GIFT_VERSION', '1.1.5' );
define( 'VIJAGIF_WOO_FREE_GIFT_DIR', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "jagif-woo-free-gift" . DIRECTORY_SEPARATOR );
define( 'VIJAGIF_WOO_FREE_GIFT_INCLUDES', VIJAGIF_WOO_FREE_GIFT_DIR . "includes" . DIRECTORY_SEPARATOR );
//define( 'VIJAGIF_WOO_FREE_GIFT_IMAGES', VIJAGIF_WOO_FREE_GIFT_DIR . "assets/images/" );
$jg_plugin_url = plugins_url( '', __FILE__ );
$jg_plugin_url = str_replace( '/includes', '', $jg_plugin_url );
define( 'VIJAGIF_WOO_FREE_GIFT_IMAGES', $jg_plugin_url . "/assets/images/" );

/**
 * Class VIJAGIF_WOO_FREE_GIFT
 */
class VIJAGIF_WOO_FREE_GIFT {
	public $plugin_name = 'Jagif - WooCommerce Free Gift';

	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );
//		add_action( 'admin_notices', array( $this, 'global_note' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		//Compatible with High-Performance order storage (COT)
		add_action( 'before_woocommerce_init', array( $this, 'before_woocommerce_init' ) );
	}

	public function init() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'jagif-free-gift-for-woocommerce/jagif-free-gift-for-woocommerce.php' ) ) {
			return;
		}

		if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
			require_once WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "jagif-woo-free-gift/includes/support.php";
		}

		$environment = new VillaTheme_Require_Environment( [
				'plugin_name'     => $this->plugin_name,
				'php_version'     => '7.0',
				'wp_version'      => '5.0',
				'require_plugins' => [
					[
						'slug'    => 'woocommerce',
						'name'    => 'WooCommerce',
						'defined_version' => 'WC_VERSION',
						'version' => '7.0'
					],
				]
			]
		);

		if ( $environment->has_error() ) {
			return;
		}

		$init_file = VIJAGIF_WOO_FREE_GIFT_INCLUDES . "define.php";
		require_once $init_file;
	}

	public function before_woocommerce_init() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}

	function global_note() {
		$jagif_errors = array();
		if ( ! version_compare( phpversion(), '7.0', '>=' ) ) {
			$jagif_errors[] = esc_html__( 'Please update PHP version at least 7.0 to use Jagif - WooCommerce Free Gift plugin.', 'jagif-woo-free-gift' );
			if ( is_plugin_active( 'jagif-woo-free-gift/jagif-woo-free-gift.php' ) ) {
				deactivate_plugins( 'jagif-woo-free-gift/jagif-woo-free-gift.php' );
				unset( $_GET['activate'] );
			}
		}
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$jagif_errors[] = esc_html__( 'Please install and activate WooCommerce to use Jagif - WooCommerce Free Gift plugin.', 'jagif-woo-free-gift' );
		}
		if ( count( $jagif_errors ) ) {
			foreach ( $jagif_errors as $error ) {
				echo sprintf( '<div id="message" class="error"><p>%s</p></div>', esc_html( $error ) );
			}
		}
	}

	/**
	 * When active plugin Function will be call
	 */
	public function install() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'jagif-woocommerce-free-gift/jagif-woocommerce-free-gift.php' ) ) {
			return;
		}

		global $wp_version;
		if ( version_compare( $wp_version, "5.0", "<" ) ) {
//			deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
//			wp_die( "This plugin requires WordPress version 5.0 or higher." );
		} else {
			$check_active = get_option( 'jagif_woo_free_gift_params' );
			if ( ! $check_active ) {
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					require_once( VIJAGIF_WOO_FREE_GIFT_INCLUDES . 'data.php' );
					$settings             = VIJAGIF_WOO_FREE_GIFT_DATA::get_instance();
					$params               = $settings->get_params();
					$params['secret_key'] = md5( time() );
					add_option( 'jagif_woo_free_gift_params', $params );
				}

			}
			if ( ! get_term_by( 'slug', 'jagif-gift', 'product_type' ) ) {
				wp_insert_term( 'jagif-gift', 'product_type' );
			}
		}
	}

	public function uninstall() {

	}

}

new VIJAGIF_WOO_FREE_GIFT();