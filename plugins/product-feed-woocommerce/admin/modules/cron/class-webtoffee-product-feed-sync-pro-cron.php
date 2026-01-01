<?php
/**
 * Handles the scheduled actions.
 *
 * @package   Webtoffee_Product_Feed_Sync_Pro\Admin\Modules\Cron
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webtoffee_Product_Feed_Sync_Pro_Cron Class.
 */
class Webtoffee_Product_Feed_Sync_Pro_Cron {

	/**
	 * Module ID
	 *
	 * @var string
	 */
	public $module_id = '';
	/**
	 * Module ID
	 *
	 * @var string
	 */
	public static $module_id_static = '';
	/**
	 *  Module
	 *
	 * @var string
	 */
	public $module_base = 'cron';
	/**
	 * Actions modules
	 *
	 * @var array
	 */
	public $action_modules = array( 'export' => 'export' );

	/**
	 * Status
	 *
	 * @var array
	 */
	public static $status_arr = array();
	/**
	 * Status label
	 *
	 * @var array
	 */
	public static $status_label_arr = array();
	/**
	 * Status color
	 *
	 * @var array
	 */
	public static $status_color_arr = array();
	/**
	 * Import or Export
	 *
	 * @var string
	 */
	public $to_cron = '';
	/**
	 * Cron salt
	 *
	 * @var string
	 */
	private $cron_url_salt = 'WTeb(DjCr<}P2c#s';
	/**
	 * Export object
	 *
	 * @var object
	 */
	protected $export_obj = null;
	/**
	 * Status label
	 *
	 * @var array
	 */
	public $step_description = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->module_id = Webtoffee_Product_Feed_Sync_Pro::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;
		self::$status_arr = array(
			'not_started' => 0, // Not started yet.
			'finished' => 1, // At least one completed.
			'disabled' => 2, // Disabled.
			'running' => 3, // Cron on running, eg: at least one batch completed.
			'uploading' => 4, // Uploading exported file.
			'downloading' => 5, // Downloading the file to import.
		);

		self::$status_color_arr = array(
			0 => '#337ab7', // Dark blue.
			1 => '#5cb85c', // Green.
			2 => '#f0ad4e', // Orange.
			3 => '#5bc0de', // Light blue.
			4 => '#5bc0de', // Light blue.
			5 => '#5bc0de', // Light blue.
		);

		/* Register late translation */
		add_action('init', array($this, 'translate_labels'));

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 10, 1 );

		/* altering footer buttons */
		add_filter( 'wt_productfeed_exporter_alter_footer_btns', array( $this, 'exporter_alter_footer_btns' ), 10, 3 );

		/* toggling the Export, Export/Schedule button based on `Export to` option */
		add_action( 'wt_productfeed_toggle_schedule_btn', array( $this, 'toggle_schedule_btn' ), 10, 1 );

		/* hook for `schedule now` JS action */
		add_action( 'wt_productfeed_custom_action', array( $this, 'schedule_now' ) );

		/* advanced plugin settings */
		add_filter( 'wt_productfeed_advanced_setting_fields', array( $this, 'advanced_setting_fields' ), 11 );

		/* schedule main ajax hook */
		add_action( 'wp_ajax_pf_schedule_ajax', array( $this, 'ajax_main' ) );

		add_action( 'wp_ajax_pf_schedule_refresh', array( $this, 'refresh_catalog' ) );

		/* add interval time for cron */
		add_filter( 'cron_schedules', array( $this, 'set_cron_interval' ) );

		/* Hook cron based on action types */
		$this->prepare_cron();

		/* hook for scheduling cron */
		add_action( 'init', array( $this, 'schedule_cron' ) );

		/**
		* Hook for URL cron (Server cron)
		*/
		add_action( 'init', array( $this, 'do_url_cron' ) );

		/* Admin menu for cron listing */
		add_filter( 'wt_pf_admin_menu_pro', array( $this, 'add_admin_pages' ), 10, 1 );

		add_action( 'init', array( $this, 'test_cron' ) );
	}

	/**
	 * Translate labels.
	 */
	public function translate_labels() {
		self::$status_label_arr = array(
			0 => __('Not started', 'product-feed-woocommerce'),
			1 => __('Finished', 'product-feed-woocommerce'),
			2 => __('Disabled', 'product-feed-woocommerce'),
			3 => __('Running', 'product-feed-woocommerce'),
			4 => __('Uploading', 'product-feed-woocommerce'),
			5 => __('Downloading', 'product-feed-woocommerce'),
		);
	}

	/**
	 * Refresh catalog feed
	 */
	public function refresh_catalog() {
		// Nonce already verified on main function - sanitization thorugh helper functions.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				return $out;
		}
		// The process is based on history_id.
		$cron_id = ( isset( $_POST['cron_id'] ) ? absint( $_POST['cron_id'] ) : 0 );
		if ( $cron_id > 0 ) {
			// phpcs:ignore Nonce and user role check handled by check_write_access method.
			if(Wt_Pf_Sh::check_write_access(WEBTOFFEE_PRODUCT_FEED_PRO_ID)){
				$this->do_cron( 'export', $cron_id, 1 );

				$out = array(
					'status' => 1,
					'msg' => __(
						'Catalog refresh has been initiated and processing in the background',
						'product-feed-woocommerce'
					),
				);
				echo wp_json_encode( $out );
				exit();
			}
		}
	}
	/**
	 *     Test cron
	 */
	public function test_cron() {
		if ( defined( 'WT_PF_DEBUG_PRO' ) && WT_PF_DEBUG_PRO ) {
			$action_type = ( isset( $_GET['action_type'] ) ? sanitize_text_field( wp_unslash( $_GET['action_type'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
			$trigger_cron = ( isset( $_GET['wt_productfeed_test_cron'] ) ? absint( $_GET['wt_productfeed_test_cron'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( 'export' == $action_type && 1 == $trigger_cron ) {   // echo time();.
				$this->do_cron( $action_type );
				exit();
			}
		}
	}


	/**
	 * Fields for advanced settings
	 *
	 * @param array $fields Settings fields.
	 * @return string
	 */
	public function advanced_setting_fields( $fields ) {
		$fields['default_time_zone'] = array(
			'label' => __(
				'Switch to website timezone',
				'product-feed-woocommerce'
			),
			'type' => 'checkbox',
			'checkbox_fields' => array( 1 => '' ),
			'value' => 0,
			'field_name' => 'default_time_zone',
			'css_class' => 'wt_productfeed_checkbox_toggler wt_ier_toggler_blue',
			'help_text' => __(
				"Turn on to switch to your wesbite\'s timezone (local timezone). By default the timezone will be in UTC.",
				'product-feed-woocommerce'
			),
			'validation_rule' => array( 'type' => 'absint' ),
		);

		return $fields;
	}



	/**
	 *   Main ajax hook for all ajax actions
	 */
	public function ajax_main() {
		$out = array(
			'response' => false,
			'out' => array(),
			'msg' => __(
				'Error',
				'product-feed-woocommerce'
			),
		);
		$schedule_action = ( isset( $_REQUEST['pf_schedule_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pf_schedule_action'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification

		// phpcs:ignore Nonce and user role check handled by check_write_access method.
		if ( Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
			$json_actions = array( 'save_schedule', 'update_schedule', 'edit_schedule' );
			$allowed_actions = array( 'save_schedule', 'list_cron', 'update_schedule', 'edit_schedule' );
			if ( method_exists( $this, $schedule_action ) && in_array( $schedule_action, $allowed_actions ) ) {
				$out = $this->{$schedule_action}( $out );
			}
		}
		if ( in_array( $schedule_action, $json_actions ) ) {
			echo wp_json_encode( $out );
		}
		exit();
	}

	/**
	 * Adding admin menus
	 *
	 * @param array $menus Menus.
	 */
	public function add_admin_pages( $menus ) {
		$menus[ $this->module_base ] = array(
			'submenu',
			WEBTOFFEE_PRODUCT_FEED_PRO_ID,
			__(
				'Scheduled Feeds',
				'product-feed-woocommerce'
			),
			__(
				'Scheduled Feeds',
				'product-feed-woocommerce'
			),
			/**
			 * Capability for menus.
			 *
			 * Enables adding extra arguments or setting defaults for the request.
			 *
			 * @since 1.0.0
			 *
			 * @param string $capability    Capability.
			 */
			apply_filters( 'wt_import_export_allowed_capability', 'import' ),
			$this->module_id,
			array( $this, 'admin_settings_page' ),
		);

		return $menus;
	}

	/**
	 * List cron schedules
	 */
	public function list_cron() {
		global $wpdb;
		$cron_list = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wt_pf_cron ORDER BY id DESC", ARRAY_A ); // @codingStandardsIgnoreLine.
		$cron_list = ( $cron_list ? $cron_list : array() );
		include plugin_dir_path( __FILE__ ) . 'views/schedule-list.php';
	}

	/**
	 *  Schedule list page
	 */
	public function admin_settings_page() {
		if ( isset( $_GET['wt_productfeed_change_schedule_status'] ) || isset( $_GET['wt_productfeed_delete_schedule'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification

			// phpcs:ignore Nonce and user role check handled by check_write_access method.
			if ( Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
				$cron_id = isset( $_GET['wt_productfeed_cron_id'] ) ? absint( $_GET['wt_productfeed_cron_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

				if ( $cron_id > 0 ) {
					$cron_data = self::get_cron_by_id( $cron_id );
					if ( $cron_data ) {
						if ( isset( $_GET['wt_productfeed_delete_schedule'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
							/* deleting history entries */

							if ( $cron_data['history_id'] > 0 ) {
								$history_module_obj = Webtoffee_Product_Feed_Sync_Pro::load_modules( 'history' );
								if ( ! is_null( $history_module_obj ) ) {
									$history_module_obj->delete_history_by_id( $cron_data['history_id'] );
								}
							}
							self::delete_cron_by_id( $cron_id );
						} else {
							$action = sanitize_text_field( wp_unslash( $_GET['wt_productfeed_change_schedule_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
							if ( 'enable' == $action ) {
								// Checking its disabled.
								if ( $cron_data['status'] == self::$status_arr['disabled'] ) {
									$update_data = array(
										'status' => absint( $cron_data['old_status'] ),
									);
									$update_data_type = array( '%d' );
									self::update_cron( $cron_id, $update_data, $update_data_type );
								}
							} elseif ( $cron_data['status'] != self::$status_arr['disabled'] ) {
								// Checking it is already not disabled.
									$update_data = array(
										'status' => self::$status_arr['disabled'],
										'old_status' => $cron_data['status'],
									);
									$update_data_type = array( '%d', '%d' );
									self::update_cron( $cron_id, $update_data, $update_data_type );

							}
						}
					}
				}
			}
		}
		include plugin_dir_path( __FILE__ ) . 'views/settings.php';
	}

	/**
	 *  Delete cron entry from DB.
	 *
	 * @param integer $id Cron ID.
	 */
	public static function delete_cron_by_id( $id ) {

		global $wpdb;
		if ( is_array( $id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wt_pf_cron WHERE id IN(" . implode( ',', array_fill( 0, count( $id ), '%d' ) ) . ')', $id ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wt_pf_cron WHERE id=%d", $id ) );
		}
	}//end delete_cron_by_id()

	/**
	 * Schedule cron on action types.
	 */
	public function schedule_cron() {
		foreach ( $this->action_modules as $key => $value ) {
			if ( $this->is_cron_scheduled( $key ) ) {
				if ( ! wp_next_scheduled( 'wt_productfeed_do_cron_' . $key ) ) {
					$start_time = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::wt_strtotimetz( 'now +1 minutes' );
					wp_schedule_event( $start_time, 'wt_productfeed_cron_interval', 'wt_productfeed_do_cron_' . $key );
				}
			} elseif ( wp_next_scheduled( 'wt_productfeed_do_cron_' . $key ) ) {
					wp_clear_scheduled_hook( 'wt_productfeed_do_cron_' . $key );
			}
		}
	}


	/**
	 * Hook cron on action types. Declare action for cron
	 */
	public function prepare_cron() {
		foreach ( $this->action_modules as $key => $value ) {
			if ( $this->is_cron_scheduled( $key ) ) {
				$method_name = 'do_cron_' . $key;
				if ( method_exists( $this, $method_name ) ) {
					add_action( 'wt_productfeed_do_cron_' . $key, array( $this, $method_name ) );
				}
			}
		}
	}

	/**
	 *   Initiate export cron
	 */
	public function do_cron_export() {
		$this->do_cron( 'export' );
	}

	/**
	 *  Registering new time interval for cron
	 *
	 * @param array $schedules Cron intervals.
	 */
	public function set_cron_interval( $schedules ) {
		if ( $this->is_cron_scheduled() ) {
			$schedules['wt_productfeed_cron_interval'] = array(
				'interval' => ( 5 ), // 5 second
				'display'  => __(
					'Every 5 second',
					'product-feed-woocommerce'
				),
			);
		}
		/**
		 * Cron intervals.
		 *
		 * Enables adding extra arguments or setting defaults for the request.
		 *
		 * @since 1.0.0
		 *
		 * @param string  $schedules    Cron schedule intervals.
		 */
		return apply_filters( 'wt_productfeed_cron_interval_details', $schedules );
	}


	/**
	 * Checks any cron is available in the database
	 *
	 * @param string $action_type Cron action type.
	 */
	private function is_cron_scheduled( $action_type = '' ) {
		global $wpdb;
		$status_check_arr = self::$status_arr;
		unset( $status_check_arr['disabled'] );

		$db_data_arr = array_values( $status_check_arr );
		$db_data_arr_int = $db_data_arr;
		$db_data_arr[]  = 'wordpress_cron';
				/* Check fb sync table exist */
		$tb = 'wt_pf_cron';
		$like = '%' . $wpdb->prefix . $tb . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ), ARRAY_N ) ) {
			return false;
		}

		if ( '' != $action_type ) {
			$db_data_arr[] = $action_type;
			// taking count of available crons.
			$cron_count_arr = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS ttl FROM {$wpdb->prefix}wt_pf_cron WHERE status IN(" . implode( ', ', array_fill( 0, count( $db_data_arr_int ), '%d' ) ) . ') AND schedule_type=%s  AND action_type=%s', $db_data_arr ), ARRAY_A );// @codingStandardsIgnoreLine.

		} else {
			// taking count of available crons.
			$cron_count_arr = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS ttl FROM {$wpdb->prefix}wt_pf_cron WHERE status IN(" . implode( ', ', array_fill( 0, count( $db_data_arr_int ), '%d' ) ) . ') AND schedule_type=%s', $db_data_arr ), ARRAY_A );// @codingStandardsIgnoreLine.
		}

		$cron_count     = 0;
		if ( ! is_wp_error( $cron_count_arr ) ) {
			$cron_count = intval( isset( $cron_count_arr['ttl'] ) ? $cron_count_arr['ttl'] : 0 );
		}

		return $cron_count;
	}//end is_cron_scheduled()

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( 'webtoffee_product_feed_main_pro_export' == $_GET['page'] || 'cron' == $current_tab ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_enqueue_script( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/js/cron.js', array( 'jquery' ), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, false );

				$wt_time_zone = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'default_time_zone' );

				$params = array(
					'msgs' => array(
						'invalid_date' => __(
							'Chosen date is invalid',
							'product-feed-woocommerce'
						),
						'date_selected_info'      => __( 'You have selected 30 as the date but this date is not available in all months. In that case, last date of the month will be taken. Proceed?', 'product-feed-woocommerce' ),
						'specify_file_name' => __(
							'Please specify a file name.',
							'product-feed-woocommerce'
						),
						'saving' => __(
							'Saving',
							'product-feed-woocommerce'
						),
						'sure' => __(
							'Are you sure?',
							'product-feed-woocommerce'
						),
						'invalid_custom_interval' => __(
							'Please enter a valid interval.',
							'product-feed-woocommerce'
						),
						'invalid_time_hr' => __(
							'Please enter a valid time in hours(1-12).',
							'product-feed-woocommerce'
						),
						'invalid_time_mnt' => __(
							'Please enter a valid time in minutes(0-60).',
							'product-feed-woocommerce'
						),
						'use_url' => __(
							'Use the generated URL to run cron.',
							'product-feed-woocommerce'
						),
					),
					'timestamp' => ( $wt_time_zone ) ? date_i18n( 'Y M d h:i:s A' ) : gmdate( 'Y M d h:i:s A' ),
					'action_types' => array_keys( $this->action_modules ),
				);
				wp_localize_script( $this->module_id, 'wt_productfeed_cron_params', $params );
			}

			if ( 'webtoffee_product_feed_main_pro_export' == $_GET['page'] || 'cron' == $current_tab ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_enqueue_script( $this->module_id . '_js', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery' ), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, false );
			}
		}
	}
	/**
	 * Filter callback for schedule now/Export now button toggle
	 *
	 * @param array  $step_btns Step buttons.
	 * @param string $step Step.
	 * @param array  $steps Steps.
	 * @return array
	 */
	public function exporter_alter_footer_btns( $step_btns, $step, $steps ) {
		if ( 'advanced' !== $step ) {
			return $step_btns;
		}

		$out = array();
		foreach ( $step_btns as $step_btnk => $step_btnv ) {
			$out[ $step_btnk ] = $step_btnv;
			if ( 'export' == $step_btnk ) {
				$out['export_schedule'] = array(
					'key' => 'export_schedule',
					'icon' => '',
					'type' => 'dropdown_button',
					'class' => 'iew_export_schedule_drp_btn',
					'text' => __(
						'Export/Schedule',
						'product-feed-woocommerce'
					),
					'items' => array(
						$step_btnk => $step_btnv,
						'schedule' => array(
							'key' => 'schedule_export',
							'text' => __(
								'Schedule',
								'product-feed-woocommerce'
							),
						),
					),
				);
			}
		}
		return $out;
	}

	/**
	 *   Javascript callback for schedule now/Export now button toggle
	 */
	public function toggle_schedule_btn() {
		?>
		wt_productfeed_cron.toggle_schedule_btn(state);
		<?php
	}

	/**
	 *   Javascript callback for schedule now
	 */
	public function schedule_now() {
		?>
		wt_productfeed_cron.schedule_now(ajx_dta, action, id);
		<?php
	}

	/**
	 *  Do the cron
	 *
	 * @param string $action_type Cron action type.
	 * @param string $cron_id Cron action type.
	 * @param int    $force_refresh Force refresh.
	 */
	public function do_cron( $action_type, $cron_id = 0, $force_refresh = 0 ) {

		global $wpdb;
		if ( '' == $action_type ) {
			return '';
		}

		/* modules associated with action types */
		$action_modules = $this->action_modules;

		/* checking corresponding module exists */
		if ( ! isset( $action_modules[ $action_type ] ) ) {
			return '';
		}

		/* checking corresponding module available/active */
		if ( ! Webtoffee_Product_Feed_Sync_Pro_Admin::module_exists( $action_modules[ $action_type ] ) ) {
			return;
		}

		/**
		 * Cron type and ID
		 *
		 * @since 1.1.2
		 * To control the cron run
		 */
		$args = array(
			'action_type' => $action_type,
			'cron_id' => $cron_id,
		);
		/**
		 * Cron enabled check.
		 *
		 * Enables adding extra arguments or setting defaults for the request.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean   $run_cron    Cron enabled flag.
		 * @param array   $args    Cron arguments.
		 */
		if ( ! apply_filters( 'wt_productfeed_run_cron', true, $args ) ) {
			return;
		}

		$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$cron_tb;

		$tme = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::wt_strtotimetz( 'now' );
		/**
		 * Paralell cron check.
		 *
		 * Enables adding extra arguments or setting defaults for the request.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean   $run_cron    Cron enabled flag.
		 * @param array   $args    Cron arguments.
		 */
		$is_parallel = apply_filters( 'wt_pf_allow_parallel_cron', 1 ); // allow parallel cron on single request.
		$limit_sql = ( 0 == $is_parallel ? ' LIMIT 1' : '' );

		/*
		Taking cron details from db.
		*   Takes all data that have status running
		*   Takes data that have status not started/finshed will take based on the startime
		*   If id given then take that record only with above condition
		*/
		if ( 0 == $cron_id ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$cron_list = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wt_pf_cron WHERE ( ( (status= %d OR  status= %d) AND start_time <= %d ) OR status IN(%d, %d, %d) ) AND action_type=%s AND schedule_type=%s ORDER BY start_time ASC LIMIT 1", // @codingStandardsIgnoreLine.
					array(
						self::$status_arr['not_started'],
						self::$status_arr['finished'],
						$tme,
						self::$status_arr['running'],
						self::$status_arr['uploading'],
						self::$status_arr['downloading'],
						$action_type,
						'wordpress_cron',
					)
				),
				ARRAY_A
			);
		} elseif ( $force_refresh ) {
			/* cron id exists */
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$cron_list = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}wt_pf_cron WHERE ( ( status= %d OR  status= %d ) OR status IN(%d, %d, %d) ) AND action_type=%s AND history_id=%d",
						array(
							self::$status_arr['not_started'],
							self::$status_arr['finished'],
							self::$status_arr['running'],
							self::$status_arr['uploading'],
							self::$status_arr['downloading'],
							$action_type,
							$cron_id,
						)
					),
					ARRAY_A
				);
		} else {
			/* cron id exists */
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$cron_list = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wt_pf_cron WHERE ( ( (status= %d OR  status= %d) AND start_time <= %d ) OR status IN(%d, %d, %d) ) AND action_type=%s AND history_id=%d",
					array(
						self::$status_arr['not_started'],
						self::$status_arr['finished'],
						$tme,
						self::$status_arr['running'],
						self::$status_arr['uploading'],
						self::$status_arr['downloading'],
						$action_type,
						$cron_id,
					)
				),
				ARRAY_A
			);
		}

		// taking list of available crons.
		// if cron found.
		if ( $cron_list ) {
			$action_module = Webtoffee_Product_Feed_Sync_Pro::load_modules( $action_modules[ $action_type ] );

			if ( ! defined( 'WT_PRODUCT_FEED_CRON' ) ) {
				define( 'WT_PRODUCT_FEED_CRON', true );
			}

			foreach ( $cron_list as $cron_listv ) {
				if ( defined( 'WT_PF_DEBUG_PRO' ) && WT_PF_DEBUG_PRO ) {
					echo '<pre>';
					print_r( $cron_listv ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					echo '</pre><br />';
				}

				if ( $cron_listv['history_id'] > 0 ) {
					/* no need to send formdata. It will take from history table by `process_action` method */
					$form_data = array();
				} else {
					$form_data = maybe_unserialize( $cron_listv['data'] );
				}

				$cron_data = maybe_unserialize( $cron_listv['cron_data'] );
				$file_name = ( isset( $cron_data['file_name'] ) ? $cron_data['file_name'] : '' );

				if ( $cron_listv['status'] == self::$status_arr['finished'] || $cron_listv['status'] == self::$status_arr['not_started'] ) {

					$out = $action_module->process_action( $form_data, $action_type, $cron_listv['item_type'], $file_name, $cron_listv['history_id'], $cron_listv['next_offset'] );

				} elseif ( $cron_listv['status'] == self::$status_arr['running'] ) {
					$out = $action_module->process_action( $form_data, $action_type, $cron_listv['item_type'], $file_name, $cron_listv['history_id'], $cron_listv['next_offset'] );

				} elseif ( $cron_listv['status'] == self::$status_arr['uploading'] ) {
					$out = $action_module->process_upload( 'upload', $cron_listv['history_id'], $cron_listv['item_type'] );

				} elseif ( $cron_listv['status'] == self::$status_arr['downloading'] ) {
					$out = $action_module->process_download( $form_data, $action_type, $cron_listv['item_type'], $cron_listv['history_id'], $cron_listv['next_offset'] );
				}

				/**
				 *   Prepare for next run
				 */
				$update_data = array(
					'last_run' => Webtoffee_Product_Feed_Sync_Pro_Common_Helper::wt_strtotimetz( 'now' ),
					'history_id' => $out['history_id'],
				);
				$update_data_type = array( '%d', '%d', '%d', '%d' );

				if ( false === $out['response'] ) {
					$this->prepare_for_next_run( $update_data, $update_data_type, $cron_listv, $out );
				} else {
					if ( isset( $out['finished'] ) && 1 == $out['finished'] ) {
						/**
						 * Finished the export batching.
						 *
						 * @since 1.0.0
						 *
						 * @param array   $out    Cron output.
						 */
						do_action( 'wt_pf_scheduled_action_finished', $out );
						$this->prepare_for_next_run( $update_data, $update_data_type, $cron_listv, $out );
					} elseif ( isset( $out['finished'] ) && 2 == $out['finished'] ) {
						// Udate the status and reset the offset.
						$update_data['status'] = self::$status_arr['uploading']; // upload the exported data.
						$update_data['next_offset'] = 0; // Reset the offset.
					} elseif ( isset( $out['finished'] ) && 3 === $out['finished'] ) {
						// Update the status and reset the offset.
						$update_data['status'] = self::$status_arr['running']; // Do import.
						$update_data['next_offset'] = 0; // Reset the offset.
					} else // Not finished, more batches are pending.
					{
						if ( 'export' === $cron_listv['action_type'] ) {
								$new_status = self::$status_arr['running'];
						} elseif ( $cron_listv['status'] == self::$status_arr['running'] ) {
								$new_status = self::$status_arr['running']; // Continue import.
						} else {
							$new_status = self::$status_arr['downloading']; // Continue download.

						}

						// Update the status and reset the offset.
						$update_data['status'] = $new_status; // Waiting for next batch.
						$update_data['next_offset'] = $out['new_offset']; // Save the next offset.
					}

					/* first execution, then update the ID in history id list */
					if ( 0 == $cron_listv['history_id'] ) {
						$history_id_list = ( '' !== $cron_listv['history_id_list'] ? maybe_unserialize( $cron_listv['history_id_list'] ) : array() );
						$history_id_list = ( ! is_array( $history_id_list ) ? array() : $history_id_list );
						$history_id_list[] = $out['history_id']; // History id from import/export module.

						$update_data['history_id_list'] = maybe_serialize( $history_id_list );
						$update_data_type[] = '%s';
					}
				}

				if ( defined( 'WT_PF_DEBUG_PRO' ) && WT_PF_DEBUG_PRO ) {
					echo '<pre>';
					print_r( $out ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					echo '</pre><br />';

				}

				/**
				 *   Update cron DB entry
				 */
				$this->update_cron( $cron_listv['id'], $update_data, $update_data_type );
			}
		}
	}

	/**
	 *   Prepare for next cron (Not batch)
	 *
	 *   @param array $update_data data to be updated in cron table.
	 *   @param array $update_data_type for data be updated in cron table.
	 *   @param array $cron_listv cron DB record.
	 *   @param array $action_module_out output from action module Eg: export response from export module.
	 */
	private function prepare_for_next_run( &$update_data, &$update_data_type, $cron_listv, $action_module_out ) {
		// update the status and reset the offset.
		$update_data['status'] = self::$status_arr['finished']; // waiting for next run.
		$update_data['next_offset'] = 0; // reset the offset.

		// add next start time based on interval type.
		$cron_data = maybe_unserialize( $cron_listv['cron_data'] );
		$prev_start_time = $cron_listv['start_time'];
		$update_data['start_time'] = self::prepare_start_time( $cron_data, $prev_start_time );
		$update_data_type[] = '%d';
	}
	/**
	 * Get cron by id
	 *
	 * @param array $cron_id id of cron.
	 *
	 * @since 1.0.0
	 * @return integer
	 */
	public static function get_cron_by_id( $cron_id ) {
		global $wpdb;

		// taking cron data.
		$cron_arr = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_cron WHERE id=%d", array( $cron_id ) ), ARRAY_A );// @codingStandardsIgnoreLine.
		if ( ! is_wp_error( $cron_arr ) ) {
			return $cron_arr;
		} else {
			return false;
		}
	}//end get_cron_by_id()
	/**
	 * Get cron by history entry
	 *
	 * @global type $wpdb
	 * @param type $history_id History id.
	 * @return bool
	 */
	public static function get_cron_by_history_id( $history_id ) {
		global $wpdb;

		// taking cron data.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cron_arr = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_cron WHERE history_id=%d", array( $history_id ) ), ARRAY_A );
		if ( ! is_wp_error( $cron_arr ) ) {
			return $cron_arr;
		} else {
			return false;
		}
	}

	/**
	 *    Update the cron data when running.
	 *
	 * @param array $cron_id cron id.
	 * @param array $update_data form data.
	 * @param array $update_data_type update type.
	 * @since 1.0.0
	 * @return bool
	 */
	public static function update_cron( $cron_id, $update_data, $update_data_type ) {

		global $wpdb;
		$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$cron_tb;
		$update_where = array(
			'id' => $cron_id,
		);
		$update_where_type = array(
			'%d',
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->update( $tb, $update_data, $update_where, $update_data_type, $update_where_type ) !== false ) {
			return true;
		}
		return false;
	}

	/**
	 *     Prepare start time timestamp.
	 *
	 * @param array $cron_data process result.
	 * @param array $last_start_time process result.
	 * @since 1.0.0
	 * @return array
	 */
	public static function prepare_start_time( $cron_data, $last_start_time = 0 ) {
		$time_vl = $cron_data['start_time'];
		$tme = time();

		$month = gmdate( 'M' );
		$y = gmdate( 'Y' );
		$day = gmdate( 'd' );

		$out = 0;
		if ( 'monthly' == $cron_data['interval'] ) {
			if ( 'last_day' == $cron_data['date_vl'] ) {
				$time_stamp = strtotime( "$time_vl Last day of +0 Month" );
				if ( $time_stamp < $tme ) {
					$out = strtotime( "$time_vl Last day of +1 Month" );
				} else {
					$out = $time_stamp;
				}
			} else {
				$date_vl = $cron_data['date_vl'];
				$time_stamp = strtotime( "$time_vl $y-$month-$date_vl" );
				if ( $time_stamp < $tme ) {
					$out = strtotime( '+1 Month', $time_stamp );
				} else {
					$out = $time_stamp;
				}
			}
		} elseif ( 'weekly' == $cron_data['interval'] ) {
			$day_vl = $cron_data['day_vl'];
			$time_stamp = strtotime( "This week $day_vl $time_vl" );
			if ( $time_stamp < $tme ) {
				$out = strtotime( "Next week $day_vl $time_vl" );
			} else {
				$out = $time_stamp;
			}
		} elseif ( 'daily' == $cron_data['interval'] ) {

			$time_stamp = strtotime( $time_vl );
			if ( $time_stamp < $tme ) {
				$out = strtotime( "+1 day $time_vl" );
			} else {
				$out = $time_stamp;
			}
		} elseif ( 'hourly' == $cron_data['interval'] ) {
			$time_stamp = strtotime( $time_vl );
			if ( $time_stamp < $tme ) {
				$out = strtotime( "+1 hour $time_vl" );
			} else {
				$out = $time_stamp;
			}
		} elseif ( '6hour' == $cron_data['interval'] ) {
			$time_stamp = strtotime( $time_vl );
			if ( $time_stamp < $tme ) {
				$out = strtotime( "+6 hour $time_vl" );
			} else {
				$out = $time_stamp;
			}
		} elseif ( '12hour' == $cron_data['interval'] ) {
			$time_stamp = strtotime( $time_vl );
			if ( $time_stamp < $tme ) {
				$out = strtotime( "+12 hour $time_vl" );
			} else {
				$out = $time_stamp;
			}
		} elseif ( '30minute' == $cron_data['interval'] ) {
			$time_stamp = strtotime( $time_vl );
			if ( $time_stamp < $tme ) {
				$out = strtotime( "+30 minutes $time_vl" );
			} else {
					   $out = $time_stamp;
			}
		} else {
			$custom_interval = $cron_data['custom_interval']; // In minutes.
			$custom_interval_sec = ( $custom_interval * 60 ); // In seconds.
			if ( 0 == $last_start_time ) {
				$time_stamp = strtotime( $time_vl );
				if ( $time_stamp < $tme ) {
							$out = strtotime( "+1 day $time_vl" );

				} else {
					$out = $time_stamp;
				}
			} else {
						$next_start_time = ( $last_start_time + $custom_interval_sec );
				if ( $next_start_time < $tme ) {
					$interval_diff = ( $tme - $next_start_time );
					$out = $next_start_time + ( ( ceil( $interval_diff / $custom_interval_sec ) - 1 ) * $custom_interval_sec );
				} else {
					$out = $next_start_time;
				}
			}
		}

		return $out;
	}

	/**
	 *  Save the cron data
	 *
	 * @param array $out process result.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function save_schedule( $out ) {
		global $wpdb;
		// Nonce already verified on main function - sanitization thorugh helper functions.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				return $out;
		}
		$cron_data = ( isset( $_POST['schedule_data'] ) ? map_deep( wp_unslash( $_POST['schedule_data'] ), 'sanitize_text_field' ) : null ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! $cron_data ) {
			return $out;
		}

		/* sanitize the file name */
		$cron_data['file_name'] = ( isset( $cron_data['file_name'] ) ? sanitize_file_name( $cron_data['file_name'] ) : '' );

		$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$cron_tb;
		$start_time = self::prepare_start_time( $cron_data );
		if ( 0 == $start_time ) {
			return $out;
		}

		$item_type   = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already handled in main function
		$action_type = isset( $_POST['schedule_action'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_action'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already handled in main function

		if ( ! isset( $this->action_modules[ $action_type ] ) ) {
			return $out;
		}

		/* process form data */
		$form_data = ( isset( $_POST['form_data'] ) ? Wt_Import_Export_For_Woo_Common_Helper::process_formdata( maybe_unserialize( map_deep( wp_unslash( $_POST['form_data'] ), 'sanitize_text_field' ) ) ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		/* loading export module class object */
		$this->module_obj = Webtoffee_Product_Feed_Sync_Pro::load_modules( $action_type );

		if ( ! is_null( $this->module_obj ) ) {
			// sanitize form data.
			$form_data = Wt_Iew_IE_Helper::sanitize_formdata( $form_data, $this->module_obj );
		}

		$insert_data = array(
			'action_type' => $action_type,
			'item_type' => $item_type,
			'schedule_type' => $cron_data['schedule_type'],
			'data' => maybe_serialize( $form_data ),
			'start_time' => $start_time,  // next cron start time.
			'cron_data' => maybe_serialize( $cron_data ),  // cron settings data Eg: Cron interval type.
			'last_run' => 0, // first time, not started yet.
			'history_id' => 0, // first time, not started yet, it will added on first run.
			'status' => self::$status_arr['not_started'], // not started yet status.
			'next_offset' => 0,
			'history_id_list' => maybe_serialize( array() ),
		);
		$insert_data_type = array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $wpdb->insert( $tb, $insert_data, $insert_data_type ) ) {
			$cron_id = $wpdb->insert_id;
			$out = array(
				'response' => true,
				'out' => array(),
				'msg' => __( 'Success', 'product-feed-woocommerce' ),
			);
			if ( 'server_cron' == $cron_data['schedule_type'] ) {
				$out['cron_url'] = $this->generate_cron_url( $cron_id, $action_type, $item_type );
			}
		}
		return $out;
	}



	/**
	 *  Add the cron data
	 *
	 * @param array $out Process result.
	 * @param array $form_data Form data.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function add_schedule( $out, $form_data ) {

		$cron_data = array();
		$cron_data['file_name'] = sanitize_file_name( $form_data['post_type_form_data']['wt_pf_export_catalog_name'] );

		$start_time_hr = ! empty( $form_data['post_type_form_data']['wt_pf_cron_start_val'] ) ? $form_data['post_type_form_data']['wt_pf_cron_start_val'] : '01';
		$start_time_mnt = ! empty( $form_data['post_type_form_data']['wt_pf_cron_start_val_min'] ) ? $form_data['post_type_form_data']['wt_pf_cron_start_val_min'] : '01';
		$start_time_hr = substr( str_pad( $start_time_hr, 2, '0', STR_PAD_LEFT ), -2 );
		$start_time_mnt = substr( str_pad( $start_time_mnt, 2, '0', STR_PAD_LEFT ), -2 );

		$start_time_ampm = ! empty( $form_data['post_type_form_data']['wt_pf_cron_start_ampm_val'] ) ? $form_data['post_type_form_data']['wt_pf_cron_start_ampm_val'] : 'am';
		$start_time = $start_time_hr . '.' . $start_time_mnt . ' ' . $start_time_ampm;

		$cron_data['start_time'] = $start_time;
		$cron_data['interval'] = $form_data['post_type_form_data']['wt_pf_export_catalog_interval'];
		$cron_data['schedule_type'] = isset( $form_data['post_type_form_data']['wt_pf_catalog_cron_type'] ) ? $form_data['post_type_form_data']['wt_pf_catalog_cron_type'] : 'wordpress_cron';
		$cron_data['date_vl'] = isset( $form_data['post_type_form_data']['wt_pf_cron_interval_date'] ) ? $form_data['post_type_form_data']['wt_pf_cron_interval_date'] : gmdate( 'j' );
		$cron_data['day_vl'] = isset( $form_data['post_type_form_data']['wt_pf_schedule_cron_day'] ) ? $form_data['post_type_form_data']['wt_pf_schedule_cron_day'] : strtolower( gmdate( 'D' ) );

		if ( in_array( $cron_data['interval'], array( 'hourly', '12hour', '6hour', '30minute' ) ) ) {
			$start_time = gmdate( 'h' ) . '.' . gmdate( 'i' ) . ' ' . gmdate( 'a' );
			$cron_data['start_time'] = $start_time;
		}

		global $wpdb;

		$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$cron_tb;
		$start_time = self::prepare_start_time( $cron_data );
		if ( 0 == $start_time ) {
			return $out;
		}

		$item_type = $form_data['post_type_form_data']['wt_pf_export_post_type'];
		$action_type = 'export';

		if ( ! isset( $this->action_modules[ $action_type ] ) ) {
			return $out;
		}

		/* process form data */

		$insert_data = array(
			'action_type' => 'export',
			'item_type' => $form_data['post_type_form_data']['wt_pf_export_post_type'],
			'schedule_type' => $cron_data['schedule_type'],
			'data' => maybe_serialize( $form_data ),
			'start_time' => $start_time,  // Next cron start time.
			'cron_data' => maybe_serialize( $cron_data ),  // Cron settings data Eg: Cron interval type.
			'last_run' => 0, // First time, not started yet.
			'history_id' => isset( $out['history_id'] ) ? $out['history_id'] : 0, // First time, not started yet, it will added on first run.
			'status' => self::$status_arr['not_started'], // Not started yet status.
			'next_offset' => 0,
			'history_id_list' => maybe_serialize( array() ),
		);
		$insert_data_type = array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%s' );

		if ( isset( $out['history_id'] ) && $out['history_id'] > 0 ) {
			$cron_details = self::get_cron_by_history_id( $out['history_id'] );
			if ( $cron_details ) {
				$insert_data['id'] = $cron_details['id'];
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $tb, $insert_data, array( 'id' => $cron_details['id'] ) );
				return $out;
			}
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $wpdb->insert( $tb, $insert_data, $insert_data_type ) ) {
			$cron_id = $wpdb->insert_id;
			$out = array(
				'response' => true,
				'out' => array(),
				'msg' => __( 'Success', 'product-feed-woocommerce' ),
			);
			if ( 'server_cron' == $cron_data['schedule_type'] ) {
				$out['cron_url'] = $this->generate_cron_url( $cron_id, $action_type, $item_type );
			}
		}

		return $out;
	}


	/**
	 *  Edit the cron data.
	 *
	 * @param array $out process result.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function edit_schedule( $out ) {

		global $wpdb;
		// Nonce already verified on main function - sanitization thorugh helper functions.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				return $out;
		}
		$cron_id      = isset( $_POST['cron_id'] ) ? absint( $_POST['cron_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $cron_id ) {
			return $out;
		}
		$cron_details = self::get_cron_by_id( $cron_id );
		if ( $cron_details ) {

			$cron_form_data      = maybe_unserialize( $cron_details['data'] ); // cron settings data Eg: Cron interval type.
			$advanced_form_data = $cron_form_data['advanced_form_data'];
			$action_type = $cron_details['action_type'];
			$method_action_type_form_data_holder = "method_{$action_type}_form_data";
			$method_action_type_form_data = $cron_form_data[ $method_action_type_form_data_holder ];
			$update_data = array(
				'id'             => $cron_details['id'],
				'action_type'    => $action_type,
				'item_type'      => $cron_details['item_type'],
				'schedule_type'  => $cron_details['schedule_type'],
				'cron_data'  => maybe_unserialize( $cron_details['cron_data'] ),
				"method_{$action_type}_form_data"        => $method_action_type_form_data,
				'advanced_form_data'         => $advanced_form_data,
			);

			$step_info = array(
				'title' => ' ',
				'description' => ' ',
			);

			$action_type_base_holder = ucfirst( $action_type );
			$action_type_base_class = "Webtoffee_Product_Feed_Sync_Pro_{$action_type_base_holder}";
			$action_type_base_object = new $action_type_base_class();

			if ( is_object( $action_type_base_object ) ) {
				if ( 'export' == $action_type ) {
					$action_type_base_object->to_export = $cron_details['item_type'];
				} else {
					$action_type_base_object->to_import = $cron_details['item_type'];
				}

				$advanced_screen_fields = $action_type_base_object->get_advanced_screen_fields( $advanced_form_data );

				ob_start();
				include_once dirname( plugin_dir_path( __FILE__ ) ) . "/{$action_type}/views/_{$action_type}_advanced_page.php";
				$advanced_form_edit = ob_get_clean();
				$out['advanced_form_edit_html'] = $advanced_form_edit;
				$out['data'] = $update_data;
			}
		}
		return $out;
	}


	/**
	 *  Update the cron data.
	 *
	 * @param array $out process result.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function update_schedule( $out ) {

		global $wpdb;
		// Nonce already verified on main function - sanitization thorugh helper functions.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
				return $out;
		}
		$cron_data = ( isset( $_POST['schedule_data'] ) ? map_deep( wp_unslash( $_POST['schedule_data'] ), 'sanitize_text_field' ) : null ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! $cron_data ) {
			return $out;
		}
		$cron_id      = isset( $_POST['cron_id'] ) ? absint( $_POST['cron_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$cron_details = self::get_cron_by_id( $cron_id );
		if ( ! $cron_details ) {

			$out = array(
				'msg' => __( 'Couldn\'t find selected schedule.', 'product-feed-woocommerce' ),
			);
			return $out;
		}

		/* sanitize the file name */
		$cron_data['file_name'] = ( isset( $cron_data['file_name'] ) ? sanitize_file_name( $cron_data['file_name'] ) : '' );

		$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$cron_tb;
		$start_time = self::prepare_start_time( $cron_data );

		if ( 0 == $start_time ) {
			return $out;
		}

		$item_type = sanitize_text_field( $cron_details['item_type'] );
		$action_type = sanitize_text_field( $cron_details['action_type'] );

		if ( ! isset( $this->action_modules[ $action_type ] ) ) { // not in the allowed action list.
			return $out;
		}
		$cron_form_details = maybe_unserialize( $cron_details['data'] );

		/* process form data */
		$form_data = ( isset( $_POST['form_data'] ) ? Wt_Import_Export_For_Woo_Common_Helper::process_formdata( maybe_unserialize( map_deep( wp_unslash( $_POST['form_data'] ), 'sanitize_text_field' ) ) ) : array() );

		/* loading export module class object */
		$this->module_obj = Webtoffee_Product_Feed_Sync_Pro::load_modules( $action_type );

		if ( ! is_null( $this->module_obj ) ) {
			// sanitize form data.
			$form_data = Wt_Iew_IE_Helper::sanitize_formdata( $form_data, $this->module_obj );
		}

		if ( 'export' == $action_type ) {
			$method_from_data = $cron_form_details['method_export_form_data'];
			$form_data['method_export_form_data'] = $method_from_data;
		} else {
			$method_from_data = $cron_form_details['method_import_form_data'];
			$form_data['method_import_form_data'] = $method_from_data;
		}

		$update_data = array(
			'id' => $cron_id,
			'schedule_type' => $cron_data['schedule_type'],
			'data' => maybe_serialize( $form_data ),
			'start_time' => $start_time, // next cron start time.
			'cron_data' => maybe_serialize( $cron_data ),
		);

		$out = array(
			'response' => true,
			'out' => array(),
			'msg' => __( 'Schedule updated successfully', 'product-feed-woocommerce' ),
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->update( $tb, $update_data, array( 'id' => $cron_id ) ) ) { // success.
			if ( 'server_cron' == $cron_data['schedule_type'] ) {

				$out['cron_url'] = $this->generate_cron_url( $cron_id, $action_type, $item_type );
			}
		}

		return $out;
	}
	/**
	 *    Do URL cron.
	 *
	 * @since 1.0.0
	 */
	public function do_url_cron() {
		// Note: This is a public-facing cron endpoint with a custom hash check.
		// Nonce verification is intentionally skipped for URL-based external triggering.
		if ( isset( $_GET['wt_productfeed_url_cron'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$cron_id = absint( $_GET['wt_productfeed_url_cron'] ); // phpcs:ignore WordPress.Security.NonceVerification			
			$action_type = ( isset( $_GET['a'] ) ? sanitize_text_field( wp_unslash( $_GET['a'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
			$item_type   = ( isset( $_GET['i'] ) ? sanitize_text_field( wp_unslash( $_GET['i'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
			$hash        = ( isset( $_GET['h'] ) ? sanitize_text_field( wp_unslash( $_GET['h'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
			$tme         = ( isset( $_GET['t'] ) ? absint( $_GET['t'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( $cron_id > 0 && '' != $action_type && '' != $item_type && '' != $hash && $tme > 0 ) {
				/* check the hash is matching */
				$expected_hash = $this->generate_hash_for_url( $cron_id, $tme, $action_type );
				if ( $expected_hash == $hash ) {
					global $wpdb;
					$db_data_arr = array( self::$status_arr['disabled'], $cron_id, $action_type, $item_type );
					// checking cron exists.
					$cron_count_arr = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM  {$wpdb->prefix}wt_pf_cron  WHERE status!=%d AND id=%d AND action_type=%s AND item_type=%s", $db_data_arr ), ARRAY_A );// @codingStandardsIgnoreLine.
					if ( ! is_wp_error( $cron_count_arr ) ) {
						$history_id = $cron_count_arr['history_id'];
						$this->do_cron( $action_type, $history_id );
					}
				}
			}
			exit();
		}
	}

	/**
	 * Generate hash for URL cron.
	 *
	 * @param integer $id   cron id.
	 * @param string  $tme  time .
	 * @param string  $action_type  action type.
	 *
	 * @since 1.0.0
	 * @return string md5hash
	 */
	private function generate_hash_for_url( $id, $tme, $action_type ) {
		return md5( $tme . '_' . $this->cron_url_salt . '-' . $id . $action_type );
	}

	/**
	 *    Generate URL for URL cron.
	 *
	 * @param integer $id   cron id.
	 * @param string  $action_type  action type.
	 * @param sring   $item_type  item type.
	 *
	 * @since 1.0.0
	 * @return string URL
	 */
	private function generate_cron_url( $id, $action_type, $item_type ) {

		$tme = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::wt_strtotimetz( 'now' );
		$hash = $this->generate_hash_for_url( $id, $tme, $action_type );
		return site_url( '?wt_productfeed_url_cron=' . $id . '&a=' . $action_type . '&i=' . $item_type . '&h=' . $hash . '&t=' . $tme );
	}
}
Webtoffee_Product_Feed_Sync_Pro::$loaded_modules['cron'] = new Webtoffee_Product_Feed_Sync_Pro_Cron();
