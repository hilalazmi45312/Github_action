<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_Exporter
 * @package Autonami CRM
 */
#[AllowDynamicProperties]
class BWFCRM_Exporter {
	/** Export type */
	public static $EXPORT = 2;
	/** Export Status */
	public static $EXPORT_IN_PROGRESS = 1;
	public static $EXPORT_FAILED = 2;
	public static $EXPORT_SUCCESS = 3;
	public static $EXPORT_CANCELLED = 4;
	private static $ins = null;
	/**
	 * Export action
	 *
	 * @var string
	 */
	public static $ACTION_HOOK = 'bwfcrm_contact_export';
	private $start_time = 0;
	private $current_pos = 0;

	public $db_export_row = array();
	private $export_meta = array();
	private $export_fields = array();
	private $export_filters = array();
	private $export_id = 0;

	protected $halt = 0;
	protected $count = 0;
	protected $contacts = array();

	private $logs = array();

	public $is_running = null;
	public $end_export = false;
	public $updated_count = null;
	public $saved_count = null;

	public function __construct() {
		add_action( 'wp_loaded', [ $this, 'bwfcrm_exporter_action' ], 9 );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Added row in table and start action
	 *
	 * @param $title
	 * @param $fields
	 * @param $filters
	 * @param $count
	 *
	 * @return array
	 */
	public static function bwfcrm_add_export_action( $title, $fields, $filters, $count ) {
		$fields     = apply_filters( 'bwfcrm_export_fields_headers', $fields );
		$field_data = self::bwfcrm_separate_slug_label( $fields );
		if ( ! file_exists( BWFCRM_EXPORT_DIR . '/' ) ) {
			wp_mkdir_p( BWFCRM_EXPORT_DIR );
		}

		$file_name = 'FKA-Contacts-Export-' . time() . '-';
		if ( class_exists( '\BWFAN_Common' ) && method_exists( '\BWFAN_Common', 'create_token' ) ) {
			$file_name .= \BWFAN_Common::create_token( 5 );
		} else {
			$file_name .= wp_generate_password( 5, false );
		}
		$file_name .= '.csv';

		$file_name = str_replace( [ ' ', ':' ], '-', $file_name );
		$file      = fopen( BWFCRM_EXPORT_DIR . '/' . $file_name, "wb" );
		if ( empty( $file ) ) {
			/** Unable to open file, should return and show a message */
			return array(
				'status' => 404
			);
		}

		fputcsv( $file, $field_data['label'] );
		fclose( $file );

		BWFAN_Model_Import_Export::insert( array(
			'offset'        => 0,
			'type'          => self::$EXPORT,
			'status'        => self::$EXPORT_IN_PROGRESS,
			'count'         => $count,
			'meta'          => wp_json_encode( array(
				'title'   => $title,
				'fields'  => $fields,
				'filters' => $filters,
				'file'    => $file_name,
			) ),
			'created_date'  => current_time( 'mysql', 1 ),
			'last_modified' => current_time( 'mysql', 1 )
		) );

		$export_id = BWFAN_Model_Import_Export::insert_id();
		if ( empty( $export_id ) ) {
			/** Unable to insert row in DB */
			wp_delete_file( BWFCRM_EXPORT_DIR . '/' . $file_name );

			return array(
				'status' => 404
			);
		}

		bwf_schedule_recurring_action( time(), 30, self::$ACTION_HOOK, array( 'export_id' => absint( $export_id ) ), 'bwfcrm' );
		BWFCRM_Common::ping_woofunnels_worker();

		return array(
			'status' => true,
			'id'     => $export_id,
		);
	}

	public static function bwfcrm_separate_slug_label( $data ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return [
				'slug'  => [],
				'label' => []
			];
		}
		$fields_slug = [];
		$field_label = [];
		foreach ( $data as $ds ) {
			if ( ! is_array( $ds ) ) {
				continue;
			}
			foreach ( $ds as $key => $val ) {
				$field_label[] = $val;
				$fields_slug[] = $key;
			}
		}

		return [
			'slug'  => $fields_slug,
			'label' => $field_label
		];
	}

	/**
	 * Gget dynamic string
	 *
	 * @param $count
	 *
	 * @return string
	 */
	public static function get_dynamic_string( $count = 8 ) {
		$chars = apply_filters( 'bwfan_dynamic_string_chars', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' );

		return substr( str_shuffle( $chars ), 0, $count );
	}

	/**
	 * Delete export entry
	 *
	 * @param $export_id
	 *
	 * @return bool
	 */
	public static function delete_export_entry( $export_id ) {
		$response = false;
		$data     = BWFAN_Model_Import_Export::get( $export_id );
		if ( ! empty( $data ) ) {
			$stat = BWFAN_Model_Import_Export::delete( $export_id );
			if ( isset( $data['meta'] ) && isset( $data['meta']['file'] ) && file_exists( BWFCRM_EXPORT_DIR . '/' . $data['meta']['file'] ) ) {
				wp_delete_file( BWFCRM_EXPORT_DIR . '/' . $data['meta']['file'] );
			}
			if ( $stat ) {
				$response = true;
			}
		}

		return $response;
	}

	/**
	 * Cancel export entry
	 *
	 * @param $export_id
	 *
	 * @return bool
	 */
	public static function cancel_export_entry( $export_id ) {
		$data = BWFAN_Model_Import_Export::get( $export_id );
		if ( ! empty( $data ) ) {
			$stat = BWFAN_Model_Import_Export::update( array(
				'status' => self::$EXPORT_CANCELLED
			), array(
				'id' => $export_id
			) );
			if ( isset( $data['meta'] ) && isset( $data['meta']['file'] ) && file_exists( BWFCRM_EXPORT_DIR . '/' . $data['meta']['file'] ) ) {
				wp_delete_file( BWFCRM_EXPORT_DIR . '/' . $data['meta']['file'] );
			}
			if ( bwf_has_action_scheduled( self::$ACTION_HOOK, array( 'export_id' => absint( $export_id ) ), 'bwfcrm' ) ) {
				bwf_unschedule_actions( self::$ACTION_HOOK, array( 'export_id' => absint( $export_id ) ), 'bwfcrm' );
			}
			if ( $stat ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add Exporter Action
	 */
	public function bwfcrm_exporter_action() {
		add_action( self::$ACTION_HOOK, array( $this, 'bwfcrm_export' ) );
	}

	/**
	 * Export Action callback
	 */
	public function bwfcrm_export( $export_id ) {
		if ( ! $this->maybe_get_export( $export_id ) ) {
			$this->end_export( self::$EXPORT_FAILED, __( 'Unable to get Export ID', 'wp-marketing-automations-pro' ) . ': ' . $export_id );

			return;
		}

		if ( $this->is_recently_exported() ) {
			$this->end_export( self::$EXPORT_FAILED, __( 'Contacts Recent Export attempt', 'wp-marketing-automations-pro' ) . ': ' . $this->db_export_row['last_modified'] );

			return;
		}

		if ( ! isset( $this->export_meta['fields'] ) || ! is_array( $this->export_meta['fields'] ) ) {
			$this->end_export( self::$EXPORT_FAILED, __( 'Export Fields Empty', 'wp-marketing-automations-pro' ) );

			return;
		}

		$field_data           = self::bwfcrm_separate_slug_label( $this->export_meta['fields'] );
		$this->export_fields  = $field_data['slug'];
		$this->export_filters = $this->export_meta['filters'] ?? [];

		if ( ! is_array( $this->export_fields ) || empty( $this->export_fields ) ) {
			$this->end_export( self::$EXPORT_FAILED, __( 'Export Fields Empty', 'wp-marketing-automations-pro' ) );

			return;
		}

		if ( ! empty( $this->is_running ) ) {
			return;
		}

		/** Add running status */
		$this->update_status_running();

		$this->current_pos = absint( $this->db_export_row['offset'] );

		$this->start_time = time();
		$this->set_log( __( 'Export started ', 'wp-marketing-automations-pro' ) . ': ' . $export_id );

		while ( ( ( time() - $this->start_time ) < 30 ) && ! BWFCRM_Common::memory_exceeded() && ( 0 === $this->halt ) ) {
			/** Populate contacts when previous contacts are done exporting */
			$this->populate_contacts();
			if ( true === $this->end_export ) {
				break;
			}

			$this->export_contact();
			$this->update_offset();
		}

		if ( true === $this->end_export ) {
			$this->end_export( self::$EXPORT_SUCCESS );

			return;
		}

		/** Remove running status */
		$this->update_status_running( true );
		$this->log();
	}

	public function populate_contacts() {
		$this->set_log( __( 'Populating contacts ', 'wp-marketing-automations-pro' ) );
		$filters  = $this->export_meta['filters'] ?? [];
		$contacts = BWFCRM_Contact::get_contacts( '', $this->current_pos, 100, $filters, [
			'grab_totals'        => true,
			'customer_data'      => true,
			'grab_custom_fields' => true,
			'order_by'           => 'id',
		], OBJECT, true );
		$this->set_log( __( 'Contacts Fetched', 'wp-marketing-automations-pro' ) . ': ' . count( $contacts['contacts'] ) );
		$this->contacts   = $contacts['contacts'] ?? [];
		$this->end_export = empty( $this->contacts );

		/** If saved count is different from total contacts */
		if ( isset( $contacts['total_count'] ) && intval( $contacts['total_count'] ) !== intval( $this->saved_count ) ) {
			$this->updated_count = $contacts['total_count'];
		}
	}

	/**
	 * Check for export id
	 *
	 * @param $export_id
	 *
	 * @return bool
	 */
	public function maybe_get_export( $export_id ) {
		if ( is_array( $this->db_export_row ) && ! empty( $this->db_export_row ) && absint( $this->db_export_row['id'] ) === absint( $export_id ) ) {
			return true;
		}

		$this->export_id     = absint( $export_id );
		$this->db_export_row = BWFAN_Model_Import_Export::get( $this->export_id );
		if ( empty( $this->db_export_row ) ) {
			bwf_unschedule_actions( self::$ACTION_HOOK, array( 'export_id' => absint( $this->export_id ) ), 'bwfcrm' );

			return true;
		}

		$this->export_meta = ! empty( $this->db_export_row['meta'] ) ? json_decode( $this->db_export_row['meta'], true ) : [];
		if ( ! is_array( $this->export_meta ) ) {
			$this->export_meta = [];
		}

		if ( isset( $this->export_meta['log'] ) && is_array( $this->export_meta['log'] ) ) {
			$this->skipped = isset( $this->export_meta['log']['skipped'] ) && empty( $this->skipped ) ? absint( $this->export_meta['log']['skipped'] ) : $this->skipped;
			$this->succeed = isset( $this->export_meta['log']['succeed'] ) && empty( $this->succeed ) ? absint( $this->export_meta['log']['succeed'] ) : $this->succeed;
			$this->failed  = isset( $this->export_meta['log']['failed'] ) && empty( $this->failed ) ? absint( $this->export_meta['log']['failed'] ) : $this->failed;
		}

		$this->is_running  = isset( $this->export_meta['is_running'] ) && ! empty( $this->export_meta['is_running'] );
		$this->saved_count = $this->db_export_row['count'];

		return is_array( $this->db_export_row ) && ! empty( $this->db_export_row );
	}

	/**
	 * Finish exporting to file
	 *
	 * @param int $status
	 * @param string $status_message
	 */
	public function end_export( $status = 3, $status_message = '' ) {
		if ( empty( $this->export_id ) ) {
			return;
		}

		$db_status = absint( $this->db_export_row['status'] );
		if ( bwf_has_action_scheduled( self::$ACTION_HOOK ) && $db_status === self::$EXPORT_IN_PROGRESS ) {
			bwf_unschedule_actions( self::$ACTION_HOOK, array( 'export_id' => absint( $this->export_id ) ), 'bwfcrm' );
		}

		if ( ! empty( $status_message ) ) {
			BWFAN_Core()->logger->log( $status_message, 'export_contacts_crm' );
		} else if ( 3 === $status ) {
			$status_message = __( 'Contacts exported. Export ID', 'wp-marketing-automations-pro' ) . ': #' . $this->export_id;
		}

		$this->db_export_row['status']   = $status;
		$this->export_meta['status_msg'] = $status_message;

		BWFAN_Model_Import_Export::update( array(
			"status" => $status,
			"meta"   => wp_json_encode( $this->export_meta )
		), array(
			'id' => absint( $this->export_id )
		) );

		$this->set_log( __( 'Export ended ', 'wp-marketing-automations-pro' ) . ': ' . $status_message );
		$this->log();
	}

	/**
	 * Check last modified time
	 *
	 * @return bool
	 */
	public function is_recently_exported() {
		$status                = absint( $this->db_export_row['status'] );
		$last_modified_seconds = time() - strtotime( $this->db_export_row['last_modified'] );

		return self::$EXPORT_IN_PROGRESS != $status && $last_modified_seconds <= 5;
	}

	/**
	 * Export contact to CSV function
	 *
	 * @return void
	 */
	public function export_contact() {
		$this->count = 0;

		/** Check if contacts are empty */
		if ( empty( $this->contacts ) ) {
			$this->end_export( self::$EXPORT_SUCCESS, __( 'Contacts not found', 'wp-marketing-automations-pro' ) );

			$this->halt = 1;

			return;
		}

		$file = fopen( BWFCRM_EXPORT_DIR . '/' . $this->export_meta['file'], "a" );
		if ( ! $file ) {
			$this->end_export( self::$EXPORT_FAILED, __( 'Unable to open file for writing', 'wp-marketing-automations-pro' ) );

			return;
		}

		foreach ( $this->contacts as $contact ) {
			$this->set_log( __( 'Exporting contact ', 'wp-marketing-automations-pro' ) . ': ' . $contact->get_id() );
			$csvData = $contact->get_contact_info_by_fields( $this->export_fields );

			$csvData = apply_filters( 'bwfcrm_export_csv_row_before_insert', $csvData, $contact, $this->export_fields );
			if ( ! empty( $file ) ) {
				fputcsv( $file, $csvData );
			}
			$this->count ++;
		}

		if ( ! empty( $file ) ) {
			fclose( $file );
		}
		$this->current_pos = $this->current_pos + $this->count;
	}

	/**
	 * Update DB offset
	 *
	 * @return void
	 */
	public function update_offset() {
		$this->db_export_row['offset'] = $this->current_pos;
		$this->set_log( __( 'Updating offset ', 'wp-marketing-automations-pro' ) . ': ' . $this->current_pos );
		$data = array( "offset" => $this->current_pos );

		/** If count is changed */
		if ( ! empty( $this->updated_count ) ) {
			$data['count'] = $this->updated_count;

			/** Set updated count */
			$this->saved_count = $this->updated_count;
		}

		if ( ! $this->is_running ) {
			$this->is_running                = true;
			$this->export_meta['is_running'] = true;
			$data['meta']                    = wp_json_encode( $this->export_meta );
		}

		BWFAN_Model_Import_Export::update( $data, array( 'id' => absint( $this->export_id ) ) );
	}

	/**
	 * Update running status from export meta
	 *
	 * @param $remove
	 *
	 * @return void
	 */
	public function update_status_running( $remove = false ) {
		if ( true === $remove ) {
			$this->is_running = false;
			unset( $this->export_meta['is_running'] );
		} else {
			$this->is_running                = true;
			$this->export_meta['is_running'] = $this->is_running;
		}
		BWFAN_Model_Import_Export::update( array( "meta" => json_encode( $this->export_meta ) ), array( 'id' => $this->export_id ) );
	}

	/**
	 * Return percent completed
	 *
	 * @return int
	 */
	public function get_percent_completed() {
		$start_pos = isset( $this->db_export_row['offset'] ) && ! empty( absint( $this->db_export_row['offset'] ) ) ? absint( $this->db_export_row['offset'] ) : 1;

		return floatval( ( $start_pos / $this->db_export_row['count'] ) * 100 );
	}

	public function set_log( $log ) {
		if ( empty( $log ) ) {
			return;
		}
		$this->logs[] = array(
			't' => microtime( true ),
			'm' => $log,
		);
	}

	protected function log() {
		if ( ! is_array( $this->logs ) || 0 === count( $this->logs ) ) {
			return;
		}

		if ( false === apply_filters( 'bwfan_allow_contact_export_logging', BWFAN_PRO_Common::is_log_enabled( 'bwfan_contact_export_logging' ) ) ) {
			return;
		}
		add_filter( 'bwfan_before_making_logs', '__return_true' );
		BWFAN_Core()->logger->log( print_r( $this->logs, true ), 'fka-contact-export-' . $this->export_id );
		$this->logs = [];
	}
}

if ( class_exists( 'BWFCRM_Exporter' ) ) {
	BWFCRM_Core::register( 'exporter', 'BWFCRM_Exporter' );
}