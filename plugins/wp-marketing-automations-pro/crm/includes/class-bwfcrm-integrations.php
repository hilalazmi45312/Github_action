<?php
/**
 * Integrations Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Integrations
 */
#[AllowDynamicProperties]
class BWFCRM_Integrations {
	private $_integrations = array();

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_integrations' ) );
	}

	public function load_integrations() {
		$current_dir = __DIR__ . '/integrations';
		foreach ( glob( $current_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once $_field_filename;
		}

		do_action( 'bwfcrm_integrations_loaded' );
	}

	public function register( $class ) {
		if ( ! class_exists( $class ) || ! method_exists( $class, 'get_instance' ) ) {
			return;
		}

		$slug = strtolower( $class );
		if ( false === strpos( $slug, 'bwfcrm_integration_' ) ) {
			return;
		}

		$slug = explode( 'bwfcrm_integration_', $slug )[1];

		$this->_integrations[ $slug ] = $class::get_instance();
	}

	public function get_integration( $slug ) {
		if ( empty( $slug ) || ! isset( $this->_integrations[ $slug ] ) ) {
			return false;
		}

		return $this->_integrations[ $slug ];
	}
}

if ( class_exists( 'BWFCRM_Integrations' ) ) {
	BWFCRM_Core::register( 'integrations', 'BWFCRM_Integrations' );
}
