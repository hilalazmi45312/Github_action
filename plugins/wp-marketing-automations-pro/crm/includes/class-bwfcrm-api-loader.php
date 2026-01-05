<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_Api_Loader
 *
 * @package Autonami
 */
#[AllowDynamicProperties]
class BWFCRM_API_Loader {
	private static $ins = null;

	/**
	 * @var array $registered_apis
	 */
	private static $registered_apis = array();

	/**
	 * Supported HTTP Method Constants
	 */
	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';
	const PATCH = 'PATCH';
	const DELETE = 'DELETE';

	/**
	 * BWFCRM_Api_Loader constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Return the object of current class
	 *
	 * @return null|BWFCRM_Api_Loader
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Include all the Api files
	 */
	public static function load_api_classes() {
		$api_dir = __DIR__ . '/api';
		foreach ( glob( $api_dir . '/**/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once $_field_filename;
		}

		do_action( 'bwfcrm_api_classes_loaded' );
	}

	public static function register( $api_class ) {
		if ( ! class_exists( $api_class ) || ! method_exists( $api_class, 'get_instance' ) ) {
			return;
		}

		if ( empty( $api_class ) ) {
			return;
		}

		$api_slug = strtolower( $api_class );
		if ( false === strpos( $api_slug, 'bwfcrm_api_' ) && false === strpos( $api_slug, 'bwfan\rest_api' ) ) {
			return;
		}

		if ( false !== strpos( $api_slug, 'bwfcrm_api_' ) ) {
			$api_slug = explode( 'bwfcrm_api_', $api_slug )[1];
		}

		/** @var BWFCRM_API_Base $api_obj */
		$api_obj = $api_class::get_instance();

		if ( empty( $api_obj->route ) ) {
			return;
		}

		self::$registered_apis[ $api_obj->route ][ $api_slug ] = $api_obj;
	}

	public static function register_routes() {
		self::load_api_classes();
		foreach ( self::$registered_apis as $route => $registered_api ) {
			if ( empty( $registered_api ) ) {
				continue;
			}

			$api_group = array_map( function ( $api ) {
				/** Added support for WC_REST_Controllers */
				if ( class_exists( 'WC_REST_Controller' ) && $api instanceof WC_REST_Controller ) {
					$api->register_routes();

					return false;
				}

				/** @var BWFCRM_API_Base $api */
				if ( empty( $api->method ) ) {
					return false;
				}

				$route_args = array(
					'methods'             => $api->method,
					'callback'            => array( $api, 'api_call' ),
					'permission_callback' => '__return_true'
				);

				if ( false === $api->public_api ) {
					$route_args['permission_callback'] = [ $api, 'rest_permission_callback' ];
				}

				if ( is_array( $api->request_args ) && ! empty( $api->request_args ) ) {
					$route_args['args'] = $api->request_args;
				}

				return $route_args;
			}, $registered_api );

			$api_group = array_filter( $api_group );
			$api_group = array_values( $api_group );

			register_rest_route( BWFAN_API_NAMESPACE, $route, $api_group );
		}

		do_action( 'bwfcrm_api_routes_registered', self::$registered_apis );
	}

}

if ( class_exists( 'BWFCRM_Core' ) ) {
	BWFCRM_Core::register( 'ap', 'BWFCRM_API_Loader' );
}
