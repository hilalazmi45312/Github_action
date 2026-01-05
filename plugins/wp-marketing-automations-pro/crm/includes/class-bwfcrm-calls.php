<?php

/**
 * BWFCRM_Calls_Handler
 */
#[AllowDynamicProperties]
class BWFCRM_Calls_Handler {
	/**
	 * Class instance
	 */
	private static $ins = null;

	/**
	 * Registered calls list
	 *
	 * @var array
	 */
	private $_calls = [];

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_calls' ], 8 );
	}

	/**
	 * Returns class instance
	 *
	 * @return BWFCRM_Calls_Handler|null
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	/**
	 * Load all calls
	 */
	public function load_calls() {
		$integration_dir = BWFAN_PRO_PLUGIN_DIR . '/modules';
		foreach ( glob( $integration_dir . '/*/calls/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once( $_field_filename );
		}

		do_action( 'bwfcrm_calls_loaded' );
	}

	/**
	 * Function registers call
	 *
	 * @param $slug
	 * @param $class
	 * @param $nice_name
	 *
	 * @return null
	 */
	public function register_call( $slug, $class ) {
		if ( empty( $slug ) ) {
			return;
		}

		$this->_calls[ $slug ] = $class;
	}

	/**
	 * Retrun call obj if call exists
	 *
	 * @param $slug
	 *
	 * @return array|false
	 */
	public function get_call_by_slug( $slug ) {
		if ( ! isset( $this->_calls[ $slug ] ) ) {
			return false;
		}

		return new $this->_calls[ $slug ];
	}
}

/**
 * Register calls handler to BWFCRM_Core
 */
if ( class_exists( 'BWFCRM_Calls_Handler' ) ) {
	BWFCRM_Core::register( 'calls', 'BWFCRM_Calls_Handler' );
}