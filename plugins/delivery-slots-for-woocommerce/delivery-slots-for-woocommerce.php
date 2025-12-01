<?php
/**
 * Plugin Name: Delivery and Pickup Scheduler for WooCommerce
 * Description: Allow users to select a date and time of delivery/pickup for orders and products. You can also display an estimated delivery date for the same.
 * Version: 4.5.0
 * Author: Flintop
 * Author URI: https://woo.com/vendor/flintop/
 * Text Domain: delivery-slots-for-woocommerce
 * Domain Path: /languages
 * Woo: 7498690:77dfc676cce58850331188dc19806d30
 * Requires Plugins: woocommerce
 * Tested up to: 6.8.1
 * WC tested up to: 9.9.3
 * WC requires at least: 3.5.0
 * Copyright: © 2020 Flintop
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* Include once will help to avoid fatal error by load the files when you call init hook */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Include main class file.
if ( ! class_exists( 'FP_Delivery_Slots' ) ) {
	include_once 'inc/class-delivery-slots.php';
}

if ( ! function_exists( 'dey_is_plugin_active' ) ) {

	/**
	 * Is plugin active?
	 *
	 * @return bool
	 */
	function dey_is_plugin_active() {
		if ( dey_is_valid_wordpress_version() && dey_is_woocommerce_active() && dey_is_valid_woocommerce_version() ) {
			return true;
		}

		add_action( 'admin_notices', 'dey_display_warning_message' );

		return false;
	}
}

if ( ! function_exists( 'dey_is_woocommerce_active' ) ) {

	/**
	 * Function to check whether WooCommerce is active or not.
	 *
	 * @return bool
	 */
	function dey_is_woocommerce_active() {
		$return = true;
		// This condition is for multi site installation.
		if ( is_multisite() && ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$return = false;
			// This condition is for single site installation.
		} elseif ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$return = false;
		}

		return $return;
	}
}

if ( ! function_exists( 'dey_is_valid_wordpress_version' ) ) {

	/**
	 * Is valid WordPress version?
	 *
	 * @return bool
	 */
	function dey_is_valid_wordpress_version() {
		if ( version_compare( get_bloginfo( 'version' ), FP_Delivery_Slots::$wp_minimum_version, '<' ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'dey_is_valid_woocommerce_version' ) ) {

	/**
	 * Is valid WooCommerce version?
	 *
	 * @return bool
	 */
	function dey_is_valid_woocommerce_version() {
		if ( version_compare( get_option( 'woocommerce_version' ), FP_Delivery_Slots::$wc_minimum_version, '<' ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'dey_display_warning_message' ) ) {

	/**
	 * Display the WooCommere is not active warning message.
	 */
	function dey_display_warning_message() {
		$notice = '';

		if ( ! dey_is_valid_wordpress_version() ) {
			$notice = sprintf( 'This version of Delivery and Pickup Scheduler for WooCommerce requires WordPress %1s or newer.', FP_Delivery_Slots::$wp_minimum_version );
		} elseif ( ! dey_is_woocommerce_active() ) {
			$notice = 'Delivery and Pickup Scheduler for WooCommerce Plugin will not work until WooCommerce Plugin is Activated. Please Activate the WooCommerce Plugin.';
		} elseif ( ! dey_is_valid_woocommerce_version() ) {
			$notice = sprintf( 'This version of Delivery and Pickup Scheduler for WooCommerce requires WooCommerce %1s or newer.', FP_Delivery_Slots::$wc_minimum_version );
		}

		if ( $notice ) {
			echo '<div class="error">';
			echo '<p>' . wp_kses_post( $notice ) . '</p>';
			echo '</div>';
		}
	}
}

// Return if the plugin is not active.
if ( ! dey_is_plugin_active() ) {
	return;
}

// Define constant.
if ( ! defined( 'DEY_PLUGIN_FILE' ) ) {
	define( 'DEY_PLUGIN_FILE', __FILE__ );
}

// Return Delivery and Pickup Scheduler class object.
if ( ! function_exists( 'DEY' ) ) {

	function DEY() {
		return FP_Delivery_Slots::instance();
	}
}

// Initialize the plugin.
DEY();
