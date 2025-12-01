<?php
/**
 * Custom Filters Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Custom_Filters_Controller
 */
class BWFCRM_Custom_Filters_Controller {
	private static $ins = null;
	private $_webhooks = [];

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'load_custom_filters' ] );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function load_custom_filters( $wp_rest_server ) {
		$rest_route = isset( $_GET['rest_route'] ) ? $_GET['rest_route'] : '';
		if ( empty( $rest_route ) ) {
			$rest_route = $_SERVER['REQUEST_URI'];
		}
		if ( empty( $rest_route ) ) {
			return;
		}
		if ( strpos( $rest_route, 'autonami/' ) === false && strpos( $rest_route, 'woofunnel_customer/' ) === false && strpos( $rest_route, 'funnelkit-app' ) === false && strpos( $rest_route, 'autonami-app' ) === false && strpos( $rest_route, 'funnelkit-automations' ) === false && strpos( $rest_route, 'autonami-webhook' ) === false && strpos( $rest_route, 'woofunnels/' ) === false ) {
			return;
		}

		/** Load custom filter class files */
		$this->load_classes( 'custom_filters' );
		do_action( 'bwfcrm_custom_filters_loaded' );

		/** Load column preferences class files */
		$this->load_classes( 'column-preferences' );
		do_action( 'bwfcrm_column_preferences_loaded' );
	}

	public function load_classes( $folder ) {
		$dir = __DIR__ . '/' . $folder;
		foreach ( glob( $dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once( $_field_filename );
		}
	}
}

if ( class_exists( 'BWFCRM_Custom_Filters_Controller' ) ) {
	BWFCRM_Core::register( 'custom_filters', 'BWFCRM_Custom_Filters_Controller' );
}
