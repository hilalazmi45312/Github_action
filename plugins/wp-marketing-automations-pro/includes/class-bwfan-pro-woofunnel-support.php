<?php
#[AllowDynamicProperties]
class BWFAN_Pro_WooFunnels_Support {

	public static $_instance = null;
	public $full_name = '';
	public $is_license_needed = true;
	/**
	 * @var WooFunnels_License_check
	 */
	public $license_instance;
	protected $slug = 'autonami-automations-pro';
	protected $encoded_basename = '';

	private function __construct() {
		$this->full_name        = BWFAN_PRO_FULL_NAME;
		$this->encoded_basename = sha1( BWFAN_PRO_PLUGIN_BASENAME );

		add_filter( 'woofunnels_plugins_license_needed', array( $this, 'add_license_support' ), 10 );
		add_action( 'init', array( $this, 'init_licensing' ), 12 );
		add_action( 'woofunnels_licenses_submitted', array( $this, 'process_licensing_form' ) );
		add_action( 'woofunnels_deactivate_request', array( $this, 'maybe_process_deactivation' ) );

		if ( ! wp_next_scheduled( 'woofunnels_bwfan_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'woofunnels_bwfan_license_check' );
		}

		add_action( 'woofunnels_bwfan_license_check', array( $this, 'license_check' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_license_activation_wizard' ), 1 );
		add_filter( 'woofunnels_default_reason_' . BWFAN_PRO_PLUGIN_BASENAME, function () {
			return 1;
		} );

		add_filter( 'woofunnels_default_reason_default', function () {
			return 1;
		} );
//		add_action( 'admin_menu', array( $this, 'add_menus' ), 80.1 );
	}

	/**
	 * @return BWFAN_Pro_WooFunnels_Support
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	public function woofunnels_page() {
		if ( ! isset( $_GET['tab'] ) ) { // WordPress.CSRF.NonceVerification.NoNonceVerification
			WooFunnels_dashboard::$selected = 'licenses';
		}
		WooFunnels_dashboard::load_page();
	}

	/**
	 * Adding WooCommerce sub-menu for global options
	 */
	public function add_menus() {
		if ( true === WooFunnels_dashboard::$is_core_menu ) {
			return;
		}

		add_menu_page( __( 'WooFunnels', 'woofunnels' ), __( 'WooFunnels', 'woofunnels' ), 'manage_options', 'woofunnels', array( $this, 'woofunnels_page' ), '', 59 );
		add_submenu_page( 'woofunnels', __( 'Licenses', 'woofunnels' ), __( 'License', 'woofunnels' ), 'manage_options', 'woofunnels' );
		WooFunnels_dashboard::$is_core_menu = true;
	}

	/**
	 * License management helper function to create a slug that is friendly with edd
	 *
	 * @param  $name
	 *
	 * @return String
	 */
	public function slugify_module_name( $name ) {
		return preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $name ) ) );
	}

	public function add_license_support( $plugins ) {
		$status  = 'invalid';
		$renew   = 'Please Activate';
		$license = array(
			'key'     => '',
			'email'   => '',
			'expires' => '',
		);

		$plugins_in_database = WooFunnels_License_check::get_plugins();

		if ( is_array( $plugins_in_database ) && isset( $plugins_in_database[ $this->encoded_basename ] ) && count( $plugins_in_database[ $this->encoded_basename ] ) > 0 ) {
			$status  = 'active';
			$renew   = '';
			$license = array(
				'key'     => $plugins_in_database[ $this->encoded_basename ]['data_extra']['api_key'],
				'email'   => $plugins_in_database[ $this->encoded_basename ]['data_extra']['license_email'],
				'expires' => $plugins_in_database[ $this->encoded_basename ]['data_extra']['expires'],
			);
		}

		$plugins[ $this->encoded_basename ] = array(
			'plugin'            => $this->full_name,
			'product_version'   => BWFAN_PRO_VERSION,
			'product_status'    => $status,
			'license_expiry'    => $renew,
			'product_file_path' => $this->encoded_basename,
			'existing_key'      => $license,
		);

		return $plugins;
	}

	public function woofunnels_slugify_module_name( $name ) {
		return preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $name ) ) );
	}

	public function init_licensing() {
		if ( class_exists( 'WooFunnels_License_check' ) && $this->is_license_needed ) {
			$this->license_instance = new WooFunnels_License_check( $this->encoded_basename );

			$plugins = WooFunnels_License_check::get_plugins();
			if ( isset( $plugins[ $this->encoded_basename ] ) && count( $plugins[ $this->encoded_basename ] ) > 0 ) {
				$data = array(
					'plugin_slug' => BWFAN_PRO_PLUGIN_BASENAME,
					'plugin_name' => BWFAN_PRO_FULL_NAME,
					'license_key' => $plugins[ $this->encoded_basename ]['data_extra']['api_key'],
					'product_id'  => $this->full_name,
					'version'     => BWFAN_PRO_VERSION,
				);
				$this->license_instance->setup_data( $data );
				$this->license_instance->start_updater();
			}
		}
	}

	public function process_licensing_form( $posted_data ) {

		if ( isset( $posted_data['license_keys'][ $this->encoded_basename ] ) ) {
			$key  = $posted_data['license_keys'][ $this->encoded_basename ]['key'];
			$data = array(
				'plugin_slug' => BWFAN_PRO_PLUGIN_BASENAME,
				'plugin_name' => BWFAN_PRO_FULL_NAME,
				'license_key' => $key,
				'product_id'  => $this->full_name,
				'version'     => BWFAN_PRO_VERSION,
			);
			$this->license_instance->setup_data( $data );
			$this->license_instance->activate_license();
		}
	}

	/**
	 * Validate is it is for email product deactivation
	 *
	 * @param  $posted_data
	 */
	public function maybe_process_deactivation( $posted_data ) {
		if ( isset( $posted_data['filepath'] ) && $posted_data['filepath'] === $this->encoded_basename ) {
			$plugins = WooFunnels_License_check::get_plugins();

			if ( isset( $plugins[ $this->encoded_basename ] ) && count( $plugins[ $this->encoded_basename ] ) > 0 ) {
				$data = array(
					'plugin_slug' => BWFAN_PRO_PLUGIN_BASENAME,
					'plugin_name' => BWFAN_PRO_FULL_NAME,
					'license_key' => $plugins[ $this->encoded_basename ]['data_extra']['api_key'],
					'product_id'  => $this->full_name,
					'version'     => BWFAN_PRO_VERSION,
				);
				$this->license_instance->setup_data( $data );
				$this->license_instance->deactivate_license();
				wp_safe_redirect( 'admin.php?page=' . $posted_data['page'] . '&tab=' . $posted_data['tab'] );
			}
		}
	}

	public function process_activation_api( $key ) {
		$data = array(
			'plugin_slug' => BWFAN_PRO_PLUGIN_BASENAME,
			'plugin_name' => BWFAN_PRO_FULL_NAME,
			'license_key' => $key,
			'product_id'  => $this->full_name,
			'version'     => BWFAN_PRO_VERSION,
		);
		BWFAN_Common::log_test_data( $data, 'bwfan-license' );
		$this->license_instance->setup_data( $data );

		$resp = $this->license_instance->activate_license();
		BWFAN_Common::log_test_data( $resp, 'bwfan-license' );

		return $resp;
	}

	public function process_deactivation_api() {
		$plugins = WooFunnels_License_check::get_plugins();
		$data    = array(
			'plugin_slug' => BWFAN_PRO_PLUGIN_BASENAME,
			'plugin_name' => BWFAN_PRO_FULL_NAME,
			'license_key' => $plugins[ $this->encoded_basename ]['data_extra']['api_key'],
			'product_id'  => $this->full_name,
			'version'     => BWFAN_PRO_VERSION,
		);
		BWFAN_Common::log_test_data( $data, 'bwfan-license' );
		$this->license_instance->setup_data( $data );

		$resp = $this->license_instance->deactivate_license();
		BWFAN_Common::log_test_data( $resp, 'bwfan-license' );

		return $resp;
	}

	public function license_check() {
		$plugins = WooFunnels_License_check::get_plugins();
		if ( isset( $plugins[ $this->encoded_basename ] ) && count( $plugins[ $this->encoded_basename ] ) > 0 ) {
			$data = array(
				'plugin_slug' => BWFAN_PRO_PLUGIN_BASENAME,
				'license_key' => $plugins[ $this->encoded_basename ]['data_extra']['api_key'],
				'product_id'  => $this->full_name,
				'version'     => BWFAN_PRO_VERSION,
			);
			$this->license_instance->setup_data( $data );
			$this->license_instance->license_status();
		}
	}

	public function is_license_present() {
		$plugins = WooFunnels_License_check::get_plugins();

		if ( ! isset( $plugins[ $this->encoded_basename ] ) ) {
			return false;
		}

		return true;

	}

	public function maybe_handle_license_activation_wizard() {

		if ( filter_input( INPUT_POST, 'bwfan_verify_license' ) !== null ) {
			$data = array(
				'plugin_slug' => BWFAN_PRO_PLUGIN_BASENAME,
				'plugin_name' => BWFAN_PRO_FULL_NAME,
				'license_key' => filter_input( INPUT_POST, 'license_key' ),
				'product_id'  => $this->full_name,
				'version'     => BWFAN_PRO_VERSION,
			);
			$this->license_instance->setup_data( $data );
			$data_response = $this->license_instance->activate_license();

			if ( is_array( $data_response ) && true === $data_response['activated'] ) {
				BWFAN_Wizard::set_license_state( true );
				do_action( 'bwfan_license_activated', 'autonami-automations-pro' );
				if ( filter_input( INPUT_POST, '_redirect_link' ) !== null ) {
					wp_safe_redirect( filter_input( INPUT_POST, '_redirect_link' ) );
				}
			} else {
				BWFAN_Wizard::set_license_state( false );
				BWFAN_Wizard::set_license_key( filter_input( INPUT_POST, 'license_key' ) );
			}
		}
	}

}

BWFAN_Pro_WooFunnels_Support::get_instance();
