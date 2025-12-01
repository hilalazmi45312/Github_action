<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFAN_Pro_DB_Update
 *
 * @package Autonami
 *
 * @since 2.0.5
 */
#[AllowDynamicProperties]
class BWFAN_Pro_DB_Update {
	private static $ins = null;

	public $db_changes = [];

	/**
	 * 0 - Null | nothing to do
	 * 1 - DB update can start
	 * 2 - DB update started
	 * 3 - DB update complete
	 */

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'db_update' ], 11 );
		add_action( 'bwfan_pro_db_update_2_0_4', array( $this, 'db_update_2_0_4_cb' ) );
		add_action( 'bwfan_pro_db_update_2_1_0', array( $this, 'db_update_2_1_0_cb' ) );

		/**
		 * Scheduler to update date contact fields
		 * For 2.1.0
		 */
		add_action( 'bwfan_update_contact_fields', [ $this, 'bwfan_update_contact_fields' ], 10, 1 );

		$this->db_changes = array(
			'2.0.4' => '2_0_4',
			'2.1.0' => '2_1_0'
		);
	}

	/**
	 * Return the object of current class
	 *
	 * @return null|BWFAN_Pro_DB_Update
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Update pro db function for setting value
	 */
	public function db_update() {
		$db_status  = $this->get_saved_data( 'status' );
		$db_version = $this->get_saved_data() ?? false;
		$db_version = ( false === $db_version ) ? '2.0.3' : $db_version;

		/** Status 1 = ready for run, 2 = in progress, 3 = complete */
		if ( in_array( $db_status, [ 1, 2, 3 ] ) ) {
			return;
		}

		foreach ( $this->db_changes as $version => $version_value ) {
			if ( version_compare( $db_version, $version, '<' ) ) {
				$value = [ $version => 1 ];

				/** Should run or not */
				if ( method_exists( $this, 'should_run_' . $version_value ) && false === call_user_func( [ $this, 'should_run_' . $version_value ] ) ) {
					$value = [ $version => 0 ];
				}

				update_option( 'bwfan_pro_db_update', $value, true );

				return;
			}
		}
	}

	/**
	 * Return version or status from the DB saved value
	 *
	 * @param string $type
	 *
	 * @return false|int|mixed|string|null
	 */
	public function get_saved_data( $type = 'version' ) {
		$data = get_option( 'bwfan_pro_db_update', [] );

		if ( ! is_array( $data ) ) {
			return ( 'version' === $type ) ? false : 0;
		}

		/** Return version */
		if ( 'version' === $type ) {
			return key( $data );
		}

		$status = (int) current( $data );

		/** If status is 2 (in processing) then check if action is scheduled  */
		if ( 2 === $status ) {
			$this->is_action_scheduled( $data );
		}

		/** Return status */
		return $status;
	}

	/**
	 * Schedule DB update action
	 *
	 * @return bool
	 */
	public function start_db_update() {
		/** Status */
		$status = $this->get_saved_data( 'status' );
		if ( 0 === $status ) {
			return false;
		}

		/** Check if already scheduled */
		if ( in_array( $status, [ 2, 3 ] ) ) {
			return true;
		}

		/** Version */
		$version = $this->get_saved_data();

		/** Schedule recurring action */
		$this->schedule_action( $version );

		return true;
	}

	/**
	 * Set the DB update current version value to 0
	 *
	 * @return bool
	 */
	public function dismiss_db_update() {
		/** Version */
		$version = $this->get_saved_data();
		if ( false === $version ) {
			return false;
		}

		return update_option( 'bwfan_pro_db_update', [ $version => 0 ], true );
	}

	/**
	 * Mark version complete and check for next DB update.
	 * If available then start it
	 *
	 * @param $version_no
	 */
	protected function mark_complete( $version_no ) {
		$version_name = str_replace( ".", "_", $version_no );
		BWFAN_Core()->logger->log( 'mark complete: ' . $version_no, 'db_update_' . $version_name );

		/** Mark complete */
		update_option( 'bwfan_pro_db_update', [ $version_no => 3 ], true );

		/** Un-schedule action */
		$version_name = str_replace( ".", "_", $version_no );
		bwf_unschedule_actions( 'bwfan_pro_db_update_' . $version_name );

		/** Maybe schedule next version */
		if ( ! is_array( $this->db_changes ) || 0 === count( $this->db_changes ) ) {
			return;
		}
		foreach ( $this->db_changes as $version => $version_value ) {
			if ( version_compare( $version_no, $version, '<' ) ) {
				/** Schedule recurring action */
				$this->schedule_action( $version );

				return;
			}
		}
	}

	/**
	 * Schedule recurring action
	 *
	 * @param $version
	 */
	protected function schedule_action( $version ) {
		if ( empty( $version ) ) {
			return false;
		}

		/** Mark DB update started */
		update_option( 'bwfan_pro_db_update', [ $version => 2 ], true );

		$version_name = str_replace( ".", "_", $version );
		$action       = 'bwfan_pro_db_update_' . $version_name;
		$args         = array( 'datetime' => current_time( 'mysql', 1 ) );

		/** Check if action is already scheduled */
		if ( ! bwf_has_action_scheduled( $action, $args, 'bwf_update' ) ) {
			bwf_schedule_recurring_action( time(), 60, $action, $args, 'bwf_update' );

			BWFAN_Core()->logger->log( 'scheduling action: ' . $version, 'db_update_' . $version_name );
		}

		return true;
	}

	/**
	 * Version 2.0.4 DB update callback
	 *
	 * @param $datetime
	 */
	public function db_update_2_0_4_cb( $datetime ) {
		global $wpdb;
		$time  = time();
		$key   = 'bwfan_db_update_2_0_4_cb';
		$field = BWFAN_Model_Fields::get_field_by_slug( 'last-sent' );
		BWFAN_Core()->logger->log( 'call starts for: 2.0.4', 'db_update_2_0_4' );
		do {
			$db_cid = get_option( $key, 0 );

			$query  = $wpdb->prepare( "SELECT `cid`, DATE(MAX(`created_at`)) as `created_at` FROM `{$wpdb->prefix}bwfan_engagement_tracking` WHERE `cid` > %d AND `created_at` < %s GROUP BY `cid` ORDER BY `cid` ASC LIMIT 0, 20", $db_cid, $datetime );
			$result = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! is_array( $result ) || 0 === count( $result ) ) {
				delete_option( $key );

				$this->mark_complete( '2.0.4' );

				return;
			}

			foreach ( $result as $data ) {
				$cid  = $data['cid'];
				$date = $data['created_at'];

				$wpdb->update( $wpdb->prefix . 'bwf_contact_fields', array( 'f' . $field['ID'] => $date ), array( 'cid' => $cid ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				update_option( $key, $cid, true );
			}

			$ids = empty( $result ) ? [] : array_column( $result, 'cid' );
			BWFAN_Core()->logger->log( 'updated: ' . implode( ', ', $ids ), 'db_update_2_0_4' );
		} while ( $this->should_run( $time ) ); // keep going until we run out of time, or memory
	}

	/**
	 * Check if 2.0.4 version DB update is valid
	 *
	 * @return bool
	 */
	public function should_run_2_0_4() {
		if ( empty( BWFAN_Model_Engagement_Tracking::get_first_engagement_id() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if time limit or memory passed
	 *
	 * @param $time
	 *
	 * @return bool
	 */
	protected function should_run( $time ) {
		/** If time exceeds */
		if ( ( time() - $time ) > $this->get_threshold_time() ) {
			return false;
		}

		/** If memory exceeds */
		$ins = BWF_AS::instance();

		return ! $ins->memory_exceeded();
	}

	/**
	 * Return call duration time in seconds
	 *
	 * @return mixed|void
	 */
	protected function get_threshold_time() {
		return apply_filters( 'bwfan_db_update_call_duration', 20 );
	}

	public function db_update_2_1_0_cb() {
		/** Check if already scheduled */
		$date_fields = [];
		if ( version_compare( BWFAN_VERSION, '2.5.0', '>=' ) ) {
			$date_fields = bwf_options_get( 'contact_date_fields' );
		} else {
			$date_fields = get_option( 'contact_date_fields', [] );
		}
		if ( ! empty( $date_fields ) ) {
			return;
		}

		global $wpdb;
		$db_columns   = $wpdb->get_results( "DESCRIBE {$wpdb->prefix}bwf_contact_fields", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$date_columns = array_column( array_filter( $db_columns, function ( $col ) {
			return $col['Type'] === 'date';
		} ), 'Field' );

		if ( empty( $date_columns ) ) {
			$this->mark_complete( '2.1.0' );

			BWFAN_Common::log_test_data( 'No date fields found', 'db_update_2_1_0' );

			return;
		}

		BWFAN_Common::log_test_data( 'Found date fields: ' . implode( ', ', $date_columns ), 'db_update_2_1_0' );
		if ( version_compare( BWFAN_VERSION, '2.5.0', '>=' ) ) {
			bwf_options_update( 'contact_date_fields', $date_columns );
		} else {
			update_option( 'contact_date_fields', $date_columns, false );
		}

		foreach ( $date_columns as $col ) {
			if ( ! bwf_has_action_scheduled( 'bwfan_update_contact_fields', [ 'field' => $col ] ) ) {
				bwf_schedule_recurring_action( time(), ( 2 * MINUTE_IN_SECONDS ), 'bwfan_update_contact_fields', [ 'field' => $col ] );
			}
		}

	}

	public function bwfan_update_contact_fields( $field ) {
		BWFAN_Common::log_test_data( 'Callback started for field: ' . $field, 'db_update_2_1_0' );

		global $wpdb;

		$contact = $wpdb->get_var( "SELECT `ID` FROM {$wpdb->prefix}bwf_contact_fields WHERE CAST(`{$field}` AS CHAR(10)) = '0000-00-00' LIMIT 0, 1" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $contact ) ) {
			$this->check_and_mark_close_2_1_0( $field );

			return;
		}
		$time = time();
		try {
			do {
				$contacts = $wpdb->get_col( "SELECT `ID` FROM `{$wpdb->prefix}bwf_contact_fields` WHERE CAST(`{$field}` AS CHAR(10)) = '0000-00-00' LIMIT 0, 30" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( empty( $contacts ) ) {
					$this->check_and_mark_close_2_1_0( $field );
					break;
				}
				$placeholder = array_fill( 0, count( $contacts ), '%d' );
				$placeholder = implode( ", ", $placeholder );
				$query       = $wpdb->prepare( "UPDATE `{$wpdb->prefix}bwf_contact_fields` SET `{$field}` = NULL WHERE `ID` IN ($placeholder);", $contacts );
				$wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			} while ( $this->should_run( $time ) ); // keep going until we run out of time, or memory
		} catch ( Error $e ) {
			BWFAN_Common::log_test_data( 'SQL query update error for field: ' . $field, 'db_update_2_1_0' );
			BWFAN_Common::log_test_data( $e->getMessage(), 'db_update_2_1_0' );
		}
	}

	protected function check_and_mark_close_2_1_0( $field ) {
		/** Field column updated, no records left */
		BWFAN_Common::log_test_data( 'No data left to update for Field: ' . $field, 'db_update_2_1_0' );
		if ( version_compare( BWFAN_VERSION, '2.5.0', '>=' ) ) {
			$date_fields = bwf_options_get( 'contact_date_fields' );
		} else {
			$date_fields = get_option( 'contact_date_fields', [] );
		}
		if ( ( $key = array_search( $field, $date_fields ) ) !== false ) {
			unset( $date_fields[ $key ] );
			sort( $date_fields );
		}

		bwf_unschedule_actions( 'bwfan_update_contact_fields', [ 'field' => $field ] );

		if ( empty( $date_fields ) ) {
			BWFAN_Common::log_test_data( 'No date fields left to update', 'db_update_2_1_0' );
			if ( version_compare( BWFAN_VERSION, '2.5.0', '>=' ) ) {
				bwf_options_delete( 'contact_date_fields' );
			} else {
				delete_option( 'contact_date_fields' );
			}
			$this->mark_complete( '2.1.0' );

			return;
		}

		if ( version_compare( BWFAN_VERSION, '2.5.0', '>=' ) ) {
			bwf_options_update( 'contact_date_fields', $date_fields );

			return;
		}
		update_option( 'contact_date_fields', $date_fields, false );
	}

	/**
	 * Check DB upgrade action scheduler is scheduled or not
	 *
	 * @return void
	 */
	public function is_action_scheduled( $versions ) {
		if ( empty( $versions ) ) {
			return;
		}

		foreach ( $versions as $version => $status ) {
			if ( 2 !== intval( $status ) ) {
				continue;
			}

			if ( ! in_array( $version, $this->db_changes, true ) ) {
				delete_option( 'bwfan_pro_db_update' );
				break;
			}

			$version_name = str_replace( ".", "_", $version );
			$action       = 'bwfan_pro_db_update_' . $version_name;

			/** Check if action is already scheduled */
			if ( ! bwf_has_action_scheduled( $action ) ) {
				$args = array( 'datetime' => current_time( 'mysql', 1 ) );
				bwf_schedule_recurring_action( time(), 60, $action, $args, 'bwf_update' );
			}
		}
	}
}

BWFAN_Pro_DB_Update::get_instance();
