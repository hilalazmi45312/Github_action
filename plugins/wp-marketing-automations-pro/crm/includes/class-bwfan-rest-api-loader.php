<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Public Api load class
 */
class BWFAN_Rest_API_Loader {
	private static $ins = null;
	private static $registered_apis = array();

	public function __construct() {
		// add new menu on setting
		add_action( 'bwfan_filter_app_header_data', [ $this, 'bwfan_add_new_menu' ] );

		// initializing or loading all the api classes exist in api folder
		add_action( 'init', array( __CLASS__, 'load_api_classes' ) );

		// registering the routes
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );

		// adding localized data
		add_action( 'bwfan_admin_view_localize_data', array( $this, 'add_admin_view_localize_data' ), 10, 1 );

		add_action( 'bwfan_admin_settings_schema', array( $this, 'add_bwfan_admin_settings_schema' ), 10, 1 );

		/** If rest api addon is activated then remove the action */
		if ( class_exists( 'BWFAN_Addon_Public_API' ) ) {
			$rest_api_addon_ins = BWFAN_Addon_Public_API::get_instance();
			remove_action( 'plugins_loaded', [ $rest_api_addon_ins, 'bwfan_after_plugin_loaded' ] );
			remove_action( 'bwfcrm_api_classes_loaded', [ $rest_api_addon_ins, 'bwfan_load_admin_apis' ] );
		}
	}

	/**
	 * @return BWFAN_Rest_API_Loader
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function bwfan_add_new_menu( $menu ) {
		if ( isset( $menu['settings_nav'] ) ) {
			$menu['settings_nav']['rest-api'] = [
				'name' => __( 'REST API', 'wp-marketing-automations-pro' ),
				'link' => admin_url( 'admin.php?page=autonami&path=/settings/rest-api' )
			];
		}

		return $menu;
	}

	/**
	 * registering public apis
	 *
	 * @param $api_class
	 */
	public static function register( $api_class ) {
		if ( empty( $api_class ) || ! class_exists( $api_class ) || ! method_exists( $api_class, 'get_instance' ) ) {
			return;
		}

		$api_slug = strtolower( $api_class );
		if ( false === strpos( $api_slug, 'bwfan\rest_api' ) ) {
			return;
		}

		/** @var BWFCRM_API_Base $api_obj */
		$api_obj = $api_class::get_instance();

		if ( empty( $api_obj->route ) ) {
			return;
		}

		self::$registered_apis[ $api_obj->route ][ $api_slug ] = $api_obj;
	}

	/**
	 * Include all the Api files
	 */
	public static function load_api_classes() {
		$api_dir = __DIR__ . '/rest-api';
		foreach ( glob( $api_dir . '/**/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}

			require_once $_field_filename;
		}

		do_action( 'bwfan_public_api_classes_loaded' );
	}

	/**
	 * registering the routes dynamically
	 */
	public static function register_routes() {
		foreach ( self::$registered_apis as $route => $registered_api ) {
			if ( empty( $registered_api ) ) {
				continue;
			}

			$api_group = array_map( function ( $api ) {
				/** @var BWFAN_API_Base $api */
				if ( empty( $api->method ) ) {
					return false;
				}

				$route_args = array(
					'methods'             => $api->method,
					'callback'            => array( $api, 'api_call' ),
					'permission_callback' => array( __CLASS__, 'authenticate' ),
				);

				if ( is_array( $api->request_args ) && ! empty( $api->request_args ) ) {
					$route_args['args'] = $api->request_args;
				}

				return $route_args;
			}, $registered_api );

			$api_group = array_filter( $api_group );
			$api_group = array_values( $api_group );

			register_rest_route( BWFAN_REST_API_NAMESPACE, $route, $api_group );
		}

		do_action( 'bwfan_public_api_routes_registered', self::$registered_apis );
	}

	/**
	 * @param WP_REST_Request $request
	 * authenticating the request by api key
	 *
	 * @return bool
	 */
	public static function authenticate( WP_REST_Request $request ) {
		$query_params = $request->get_query_params();
		$method       = $request->get_method();

		if ( empty( $method ) ) {
			BWFCRM_Core()->response_handler->error_response( null, 405, '', 'method_not_allowed' );
		}

		return self::authenticate_by_api_key( $query_params, $method );

	}

	/**
	 * @param $query_params
	 * @param $method
	 * authenticating api request coming from third party by api key
	 *
	 * @return bool
	 */
	public static function authenticate_by_api_key( $query_params, $method ) {
		if ( ! isset( $query_params['api_key'] ) || empty( $query_params['api_key'] ) ) {
			BWFCRM_Core()->response_handler->error_response( null, 401, '', 'api_key_missing' );
		}

		$api_key = $query_params['api_key'];
		// checking if the api key is correct or not
		$user = BWFAN_Rest_API_DB_Model::get_user_details_by_api( $api_key );
		if ( empty( $user ) ) {
			BWFCRM_Core()->response_handler->error_response( null, 401, '', 'api_key_invalid' );
		}

		// checking if the api key is activated for use. 1 - activated | 2 - revoked
		if ( 2 === absint( $user['status'] ) ) {
			BWFCRM_Core()->response_handler->error_response( null, 401, '', 'api_key_inactive' );
		}

		// checking if the api key has the appropriate permission
		if ( ! in_array( $method, self::get_api_request_methods( $user['permission'] ) ) ) {
			BWFCRM_Core()->response_handler->error_response( null, 401, '', 'permission_denied' );
		}

		// updating the last_access of the api key
		BWFAN_Rest_API_DB_Model::update_api_key_last_access( $user['id'] );

		return true;
	}

	/**
	 * adding public api active status
	 *
	 * @param BWFCRM_Base_React_Page $bwfcrm_brp_obj
	 *
	 * @return void
	 */
	public function add_admin_view_localize_data( $bwfcrm_brp_obj ) {
		$bwfcrm_brp_obj->page_data['is_public_api_active'] = true;
		$bwfcrm_brp_obj->page_data['googleFonts']          = $this->get_google_fonts_array();
		$bwfcrm_brp_obj->page_data['customFonts']          = apply_filters( 'bwfan_block_editor_custom_fonts', [] );
		$bwfcrm_brp_obj->page_data['imageSizes']           = $this->get_all_image_sizes();
		$bwfcrm_brp_obj->page_data['blockEditorVersion']   = defined( 'BWFAN_VERSION' ) && version_compare( BWFAN_VERSION, '3.5.3', '>' ) ? '2.0' : '1.0.6';
	}

	/**
	 * Returns available google fonts for block editor
	 *
	 * @return array
	 */
	public function get_google_fonts_array() {
		return apply_filters( 'bwfan_block_editor_google_fonts', [
			'Arvo',
			'Lato',
			'Lora',
			'Merriweather',
			'Merriweather Sans',
			'Noticia Text',
			'Open Sans',
			'Playfair Display',
			'Roboto',
			'Rubik',
			'Source Sans Pro',
		] );
	}

	/**
	 * Get all image sizes
	 *
	 * @return array
	 */
	public function get_all_image_sizes() {
		global $_wp_additional_image_sizes;

		$default_image_sizes = array( 'thumbnail', 'medium', 'medium_large', 'large' );

		foreach ( $default_image_sizes as $size ) {
			$image_sizes[ $size ]['width']  = get_option( $size . '_size_w' );
			$image_sizes[ $size ]['height'] = get_option( $size . '_size_h' );
			$image_sizes[ $size ]['crop']   = (bool) get_option( $size . '_crop' );
		}

		if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
			$image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
		}

		return $image_sizes;
	}

	/**
	 * 1 - only read permission (GET)
	 * 2 - only write permission (POST)
	 * 3 - both read and write permission (GET,POST,DELETE)
	 *
	 * @param $method
	 *
	 * @return string|string[]
	 */
	public static function get_api_request_methods( $method ) {
		if ( empty( $method ) ) {
			return array();
		}

		$request_methods = array(
			1 => array( 'GET' ),
			2 => array( 'POST' ),
			3 => array( 'GET', 'POST', 'DELETE' )
		);

		return isset( $request_methods[ $method ] ) ? $request_methods[ $method ] : '';
	}

	/**
	 * Add public API tab on settings
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function add_bwfan_admin_settings_schema( $settings ) {
		$settings[0]['tabs'][] = array(
			'key'    => 'rest-api',
			'label'  => 'REST API',
			'fields' => [
				array(
					'id'   => 'bwfan_rest_api',
					'type' => 'bwfan_rest_api',
				),
			],
		);

		return $settings;
	}
}

new BWFAN_Rest_API_Loader();
