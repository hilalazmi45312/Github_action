<?php
/**
 * class-woocommerce-product-search-compat-twentytwentyfive.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package woocommerce-product-search
 * @since 2.9.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Twenty Twenty-Five compatibility.
 */
class WooCommerce_Product_Search_Compat_Twenty_Twentyfive {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wp_enqueue_scripts' ) );
	}

	public static function wp_enqueue_scripts() {
		wp_register_style( 'wps-twentytwentyfive', WOO_PS_PLUGIN_URL . ( WPS_DEBUG_STYLES ? '/css/twentytwentyfive.css' : '/css/twentytwentyfive.min.css' ), array(), WOO_PS_PLUGIN_VERSION );
		wp_enqueue_style( 'wps-twentytwentyfive' );
	}

}
WooCommerce_Product_Search_Compat_Twenty_Twentyfive::init();
