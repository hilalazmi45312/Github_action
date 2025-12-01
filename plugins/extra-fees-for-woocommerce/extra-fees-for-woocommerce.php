<?php
/**
 * Plugin Name: Extra Fees for WooCommerce
 * Description: You can charge an extra fee for your orders based on Product Price, Payment Gateway & Amount spent in the order on your WooCommerce Shop.
 * Version: 7.0.0
 * Author: Flintop
 * Author URI: https://woocommerce.com/vendor/flintop/
 * Text Domain: extra-fees-for-woocommerce
 * Domain Path: /languages
 * Woo: 6036731:cc3a0e0a5993504d839eee17714e6d57
 * Tested up to: 6.8.1
 * WC tested up to: 9.8.5
 * WC requires at least: 3.7.0
 * Copyright: © 2020 Flintop
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: woocommerce
 * 
 * @package Extra Fees for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* Include once will help to avoid fatal error by load the files when you call init hook */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Define constant.
if ( ! defined( 'EFW_PLUGIN_FILE' ) ) {
	define( 'EFW_PLUGIN_FILE', __FILE__ );
}

// Include main class file.
if ( ! class_exists( 'EFW_Extra_Fees' ) ) {
	include_once 'inc/class-extra-fees.php';
}

if ( ! function_exists( 'efw_validate_before_plugin_activation' ) ) {

	/**
	 * Validate before plugin activation.
	 *
	 * @return bool
	 */
	function efw_validate_before_plugin_activation() {
		if ( efw_validate_wordpress_version() && efw_validate_woocommerce_is_active() ) {
			return true;
		}

		add_action( 'admin_notices', 'efw_display_error_message' );
		return false;
	}
}

if ( ! function_exists( 'efw_validate_wordpress_version' ) ) {

	/**
	 * Validate WordPress version?
	 *
	 * @return bool
	 */
	function efw_validate_wordpress_version() {
		if ( version_compare( get_bloginfo( 'version' ), '4.6', '<' ) ) {
			return false;
		}

		return true;
	}
}


if ( ! function_exists( 'efw_validate_woocommerce_is_active' ) ) {

	/**
	 * Validate WooCommerce active or not.
	 *
	 * @return bool
	 */
	function efw_validate_woocommerce_is_active() {

		if ( is_multisite() ) {
			// This Condition is for Multi Site WooCommerce Installation
			if ( ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) && ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) ) {
				return false;
			}
		} elseif ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {// This Condition is for Single Site WooCommerce Installation
				return false;
		}

		return true;
	}
}

if ( ! function_exists( 'efw_display_error_message' ) ) {

	/**
	 * Display error message.
	 *
	 * @return void
	 */
	function efw_display_error_message() {
		if ( ! efw_validate_wordpress_version() ) {
			echo "<div class='error'><p> This version of Extra Fees for WooCommerce requires WordPress 4.6 or newer.</p></div>";
		} elseif ( ! efw_validate_woocommerce_is_active() ) {
			echo "<div class='error'><p> Extra Fees for WooCommerce Plugin will not work until WooCommerce Plugin is Activated. Please Activate the WooCommerce Plugin.</p></div>";
		}
	}
}

if ( ! efw_validate_before_plugin_activation() ) {
	return;
}

if ( ! function_exists( 'EFW' ) ) {

	/**
	 * Instance.
	 *
	 * @return object
	 */
	function EFW() {
		return EFW_Extra_Fees::instance();
	}
}

EFW();
