<?php
/**
 * REST API Product Tags controller
 *
 * Handles requests to the products/tags endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Tags controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Tags_V2_Controller
 */

if ( ! class_exists( 'WC_REST_Product_Tags_Controller' ) ) {
	return;
}

class BWFCRM_API_Get_Product_Tags extends WC_REST_Product_Tags_Controller {
	public static $ins;
	public $route = '/products/tags';

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = BWFAN_API_NAMESPACE;
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Product_Tags' );
