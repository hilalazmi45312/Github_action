<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_Importer
 * @package Autonami CRM
 */
#[AllowDynamicProperties]
class BWFCRM_Importer {
	private static $ins = null;

	/** Import type */
	public static $IMPORT = 1;

	/** Import Status */
	public static $IMPORT_IN_PROGRESS = 1;
	public static $IMPORT_FAILED = 2;
	public static $IMPORT_SUCCESS = 3;

	/** Import Status Text */
	public static $IMPORT_IN_PROGRESS_TEXT = 'in_progress';
	public static $IMPORT_FAILED_TEXT = 'failed';
	public static $IMPORT_SUCCESS_TEXT = 'success';

	private $_importers = array();

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		/** Load classes */
		add_action( 'bwf_as_data_store_set', [ $this, 'load_importer_classes' ], 8 );
		add_action( 'bwfan_rest_call', [ $this, 'load_importer_classes' ], 8 );

		/** Core Action Scheduler set */
		add_action( 'bwf_as_data_store_set', [ $this, 'bwfcrm_importer_action' ], 9 );
	}

	public function bwfcrm_importer_action() {
		$list = $this->get_importers();
		$list = array_keys( $list );

		foreach ( $list as $importer ) {
			$ins = $this->get_importer( $importer );
			if ( ! is_null( $ins ) ) {
				add_action( 'bwfcrm_' . $importer . '_import', array( $ins, 'import' ) );
			}
		}
	}

	public function load_importer_classes() {
		$integration_dir = __DIR__ . '/importers';
		foreach ( glob( $integration_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			require_once( $_field_filename );
		}
	}

	public function register( $slug, $class ) {
		if ( empty( $slug ) ) {
			return;
		}

		$this->_importers[ $slug ] = $class;
	}

	public function get_importers() {
		return $this->_importers;
	}

	public function get_importer( $slug ) {
		if ( empty( $slug ) ) {
			return null;
		}

		$importer_class = isset( $this->_importers[ $slug ] ) ? $this->_importers[ $slug ] : '';
		$importer       = class_exists( $importer_class ) ? ( new $importer_class ) : null;

		return $importer;
	}

	public static function get_status_text( $status ) {
		switch ( $status ) {
			case BWFCRM_Importer::$IMPORT_IN_PROGRESS:
				return BWFCRM_Importer::$IMPORT_IN_PROGRESS_TEXT;
			case BWFCRM_Importer::$IMPORT_FAILED:
				return BWFCRM_Importer::$IMPORT_FAILED_TEXT;
			case BWFCRM_Importer::$IMPORT_SUCCESS:
				return BWFCRM_Importer::$IMPORT_SUCCESS_TEXT;
			default:
				return '';
		}
	}

	public function get_existing_processes() {
		global $wpdb;
		$scheduled_imports = array();

		$list = $this->get_importers();
		$list = array_keys( $list );

		foreach ( $list as $importer ) {
			if ( bwf_has_action_scheduled( 'bwfcrm_' . $importer . '_import' ) ) {
				$args = $wpdb->get_var( "SELECT `args` FROM {$wpdb->prefix}bwf_actions WHERE `hook` = 'bwfcrm_{$importer}_import' LIMIT 0,1" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				try {
					$args = json_decode( $args, true );
					if ( isset( $args['import_id'] ) ) {
						$scheduled_imports[ $importer ] = absint( $args['import_id'] );
					}
				} catch ( Exception $e ) {
				}
			}
		}

		return $scheduled_imports;
	}

	/**
	 * Remove the already running action and Re-schedule new one,
	 * And ping WooFunnels worker to run immediately
	 *
	 * @param $import_id
	 * @param $action_hook
	 */
	public function reschedule_background_action( $import_id, $action_hook ) {
		if ( bwf_has_action_scheduled( $action_hook ) ) {
			bwf_unschedule_actions( $action_hook, array( 'import_id' => absint( $import_id ) ), 'bwfcrm' );
		}

		bwf_schedule_recurring_action( time() - 7, 60, $action_hook, array( 'import_id' => absint( $import_id ) ), 'bwfcrm' );
		BWFCRM_Common::ping_woofunnels_worker();
	}

	/**
	 * Create new log file
	 *
	 * @param $file
	 * @param $file_header
	 *
	 * @return false|resource
	 */
	public static function create_importer_log_file( $file, $file_header ) {
		$file_source = fopen( $file, 'a' );
		if ( ! empty( $file_source ) ) {
			fputcsv( $file_source, $file_header );
			fclose( $file_source );
		}

		return $file_source;
	}

	/**
	 * Append data to log file
	 *
	 * @param $data
	 * @param $file_name
	 */
	public static function append_data_to_log_file( $data, $file_name ) {

		$file = fopen( $file_name, "a" );
		if ( ! empty( $file ) ) {
			fputcsv( $file, $data );
		}
	}

	/**
	 * Create log file
	 *
	 * @param $log_name
	 *
	 * @return string
	 */
	public static function get_log_file_name( $log_name ) {
		/**
		 * Check for import directory and create if not exists
		 */
		if ( ! file_exists( BWFCRM_IMPORT_DIR . '/' ) ) {
			wp_mkdir_p( BWFCRM_IMPORT_DIR );
		}

		$file_name = BWFCRM_IMPORT_DIR . '/' . $log_name . '-import-log-' . time() . '-';
		if ( class_exists( '\BWFAN_Common' ) && method_exists( '\BWFAN_Common', 'create_token' ) ) {
			$file_name .= \BWFAN_Common::create_token( 5 );
		} else {
			$file_name .= wp_generate_password( 5, false );
		}
		$file_name .= '.csv';

		return $file_name;
	}
}

if ( class_exists( 'BWFCRM_Importer' ) ) {
	BWFCRM_Core::register( 'importer', 'BWFCRM_Importer' );
}
