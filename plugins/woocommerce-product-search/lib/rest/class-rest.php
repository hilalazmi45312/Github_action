<?php
/**
 * class-rest.php
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
 * @since 6.0.0
 */

namespace com\itthinx\woocommerce\search\engine;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST
 */
class REST {

	/**
	 * Register the REST API initialization action.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'rest_api_init' ) );
	}

	/**
	 * Load our REST controllers.
	 */
	public static function rest_api_init() {
		require_once 'class-rest-controller.php';
		require_once 'class-rest-products-controller.php';
		require_once 'class-rest-terms-controller.php';
		require_once 'class-rest-shop-controller.php';

		$controller = new \com\itthinx\woocommerce\search\engine\REST_Products_Controller();
		$controller->register_routes();

		$controller = new \com\itthinx\woocommerce\search\engine\REST_Terms_Controller();
		$controller->register_routes();

		$controller = new \com\itthinx\woocommerce\search\engine\REST_Shop_Controller();
		$controller->register_routes();
	}
}

REST::init();
