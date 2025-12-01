<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_WC_Importer
 *
 * @package Autonami CRM
 */
#[AllowDynamicProperties]
class BWFCRM_WC_Importer extends BWFCRM_Importer_Base {
	/**
	 * WooCommerce importer action hook
	 *
	 * @var string
	 */
	private $action_hook = 'bwfcrm_wc_import';

	/**
	 * Working import ID
	 *
	 * @var int
	 */
	private $import_id = 0;

	/**
	 * Working import DB data
	 *
	 * @var array
	 */
	private $db_import_row = array();

	/**
	 * Working import meta data
	 *
	 * @var array
	 */
	private $import_meta = array();

	/**
	 * Import start time
	 *
	 * @var int
	 */
	private $start_import_time = 0;

	/**
	 * Flag for the iterated users from populated data
	 *
	 * @var int
	 */
	private $current_pos = 0;

	/**
	 * Array of orders fetched
	 *
	 * @var array
	 */
	private $wc_orders = array();

	/** Import Contacts Results */
	private $skipped = 0;
	private $failed = 0;
	private $succeed = 0;
	private $processed = 0;
	private $count = 0;
	private $offset = 0;
	private $log_handle = null;

	/**
	 * BWFCRM_WC_Importer constructor.
	 */
	public function __construct() {
		add_action( 'bwf_normalize_contact_meta_before_save', array( $this, 'bwfcrm_update_contact_data_before_import' ), 19, 3 );
		add_action( 'bwf_normalize_contact_meta_after_save', array( $this, 'bwfcrm_update_contact_data_after_import' ), 20, 3 );
	}

	public function reset_bwf_wc_customers() {
		global $wpdb;

		$bwf_table = $wpdb->prefix . 'bwf_wc_customers';
		$c         = $wpdb->get_row( "SHOW TABLES LIKE '$bwf_table'", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $c ) ) {
			$wpdb->query( "TRUNCATE TABLE $bwf_table" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$table = $wpdb->prefix . 'postmeta';
		$wpdb->delete( $table, array( 'meta_key' => '_woofunnel_cid' ) );  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$wpdb->delete( $table, array( 'meta_key' => '_woofunnel_custid' ) );  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		if ( method_exists( 'BWF_WC_Compatibility', 'is_hpos_enabled' ) && BWF_WC_Compatibility::is_hpos_enabled() ) {
			$table = $wpdb->prefix . 'wc_orders_meta';
			$wpdb->delete( $table, array( 'meta_key' => '_woofunnel_cid' ) );  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$wpdb->delete( $table, array( 'meta_key' => '_woofunnel_custid' ) );  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		BWFAN_Core()->logger->log( 'Customers data reset done, as the start of WC Importer', 'import_wc_contacts' );
	}

	public function create_import( $tags = array(), $lists = array(), $update_existing = false, $marketing_status = false, $disable_events = false, $imported_contact_status = 1 ) {
		/** Create Log File */
		$this->log_file = BWFCRM_Importer::get_log_file_name( 'wc' );
		$import_id      = $this->import_id = $this->create_wc_import_record( $tags, $lists, $marketing_status, $disable_events, $imported_contact_status, $update_existing );

		$log_file_header = array( 'Email', 'Order ID', 'Status', 'Error Message' );
		BWFCRM_Importer::create_importer_log_file( $this->log_file, $log_file_header );

		BWFCRM_Core()->importer->reschedule_background_action( $import_id, $this->action_hook );

		return $import_id;
	}

	/**
	 * Update contact data before contact normalization
	 *
	 * @param $bwf_contact
	 * @param $order_id
	 * @param $order
	 */
	public function bwfcrm_update_contact_data_before_import( $bwf_contact, $order_id, $order ) {
		if ( empty( $this->import_id ) ) {
			return;
		}

		$contact    = new BWFCRM_Contact( $bwf_contact );
		$created_on = strtotime( $contact->contact->get_creation_date() );
		if ( false === $created_on ) {
			return;
		}

		$already_exists = $created_on < ( time() - 5 );

		/** Check if contact need to update status if existing */
		$update_existing = $this->get_import_meta( 'update_existing' );
		if ( $already_exists && ! $update_existing ) {
			return;
		}

		/**
		 * Update contact status
		 */
		$disable_events = $this->get_import_meta( 'disable_events' );
		$import_status  = $this->get_import_meta( 'imported_contact_status' );
		switch ( absint( $import_status ) ) {
			case 0:
				$contact->unverify();
				break;
			case 1:
				$contact->resubscribe( $disable_events );
				break;
			case 2:
				$contact->mark_as_bounced( $disable_events );
				break;
			case 3:
				if ( $order instanceof WC_Order ) {
					$order->delete_meta_data( 'marketing_status' );
					$order->save();
				}
				$contact->contact->is_subscribed = false;
				$contact->unsubscribe( $disable_events );
				break;
			case 4:
				if ( method_exists( $contact, 'mark_as_soft_bounced' ) ) {
					$contact->mark_as_soft_bounced( $disable_events );
				} else {
					$contact->mark_as_bounced( $disable_events );
				}
				break;
			case 5:
				if ( method_exists( $contact, 'mark_as_complaint' ) ) {
					$contact->mark_as_complaint( $disable_events );
				} else {
					$contact->mark_as_bounced( $disable_events );
				}
				break;
		}
	}

	/**
	 * Update contact data after contact normalization
	 *
	 * @param $bwf_contact
	 * @param $order_id
	 * @param $order
	 */
	public function bwfcrm_update_contact_data_after_import( $bwf_contact, $order_id, $order ) {
		if ( ! empty( $this->import_id ) ) {
			/**
			 * Get contact data by id
			 */
			$contact = new BWFCRM_Contact( $bwf_contact->get_id() );
			if ( 0 === $contact->get_id() ) {
				BWFAN_Core()->logger->log( 'order id #' . $order_id . ' contact not found', 'import_wc_failed_contacts' );

				return;
			}

			/**
			 * Checks if add list and tag event needed to run
			 */
			$disable_events = $this->get_import_meta( 'disable_events' );

			$contact_changed = false;

			/**
			 * Apply tags if contains tag data in importer
			 */
			if ( ! empty( $this->get_import_meta( 'tags' ) ) ) {
				$tags = BWFAN_Model_Terms::get_crm_term_ids( $this->get_import_meta( 'tags' ), BWFCRM_Term_Type::$TAG );
				$contact->set_tags_v2( $tags, $disable_events );
				$contact_changed = true;
			}

			/**
			 * Apply lists if contains list data in importer
			 */
			if ( ! empty( $this->get_import_meta( 'lists' ) ) ) {
				$lists = BWFAN_Model_Terms::get_crm_term_ids( $this->get_import_meta( 'lists' ), BWFCRM_Term_Type::$LIST );
				$contact->set_lists_v2( $lists, $disable_events );
				$contact_changed = true;
			}

			/** Only run when contact has changes */
			if ( $contact_changed ) {
				$contact->save();
			}
		}
	}

	/**
	 *  Get Import Status or Start Import
	 *
	 * @param int $import_id
	 *
	 * @return array|string|void
	 * @throws Exception
	 */
	public function get_import_status( $import_id = 0 ) {
		/**
		 * Check for import record exists
		 */
		if ( ! $this->maybe_get_import( $import_id ) ) {
			return __( 'Import record not found', 'wp-marketing-automations-pro' );
		}

		$percent = $this->get_percent_completed();

		/** End import if completed */
		if ( $percent >= 100 ) {
			$this->end_import();
		}

		$status = absint( $this->db_import_row['status'] );

		if ( BWFCRM_Importer::$IMPORT_IN_PROGRESS === $status && bwf_has_action_scheduled( $this->action_hook, array( 'import_id' => absint( $import_id ) ) && empty( $this->db_import_row['meta']['is_running'] ) ) ) {
			bwf_unschedule_actions( $this->action_hook, array( 'import_id' => absint( $import_id ) ) );

			add_filter( 'bwfan_as_per_call_time', function () {
				return 10;
			} );

			$this->import( $import_id );

			/** If still in progress, return the status */
			$this->db_import_row = null;
			$this->maybe_get_import( $import_id );

			/** Get percent completed */
			$percent = $this->get_percent_completed();

			$status = absint( $this->db_import_row['status'] );

			/** End import if completed */
			if ( $percent >= 100 ) {
				$this->end_import();
			}
		}

		$data = array(
			'import_id' => $this->import_id,
			'percent'   => $percent,
			'status'    => BWFCRM_Importer::get_status_text( $status ),
			'log'       => $this->get_import_meta( 'log' ),
		);

		/** If import completed 100% and import has log file */
		if ( 100 === $percent && ! empty( $this->import_meta['log_file'] ) ) {
			$data['has_log_file'] = true;
		}

		return $data;
	}

	/**
	 * Check for importer to start
	 *
	 * @param $import_id
	 *
	 * @return bool
	 */
	public function maybe_get_import( $import_id = 0 ) {
		/**
		 * Check if import data already fetched
		 */
		if ( is_array( $this->db_import_row ) && ! empty( $this->db_import_row ) && absint( $this->db_import_row['id'] ) === absint( $import_id ) ) {
			return true;
		}

		/**
		 * Create import entry in table if not exists
		 */
		$this->import_id = absint( $import_id );

		/**
		 * Get import data from DB
		 */
		$this->db_import_row = BWFAN_Model_Import_Export::get( $this->import_id );
		$this->import_meta   = ! empty( $this->db_import_row['meta'] ) ? json_decode( $this->db_import_row['meta'], true ) : array();

		/**
		 * Set log data
		 */
		if ( isset( $this->import_meta['log'] ) ) {
			$this->skipped = isset( $this->import_meta['log']['skipped'] ) && empty( $this->skipped ) ? absint( $this->import_meta['log']['skipped'] ) : $this->skipped;
			$this->succeed = isset( $this->import_meta['log']['succeed'] ) && empty( $this->succeed ) ? absint( $this->import_meta['log']['succeed'] ) : $this->succeed;
			$this->failed  = isset( $this->import_meta['log']['failed'] ) && empty( $this->failed ) ? absint( $this->import_meta['log']['failed'] ) : $this->failed;
		}

		if ( isset( $this->import_meta['log_file'] ) ) {
			$this->log_file = $this->import_meta['log_file'];
		}

		$this->processed = isset( $this->db_import_row['processed'] ) && empty( $this->processed ) ? absint( $this->db_import_row['processed'] ) : $this->processed;
		$this->count     = isset( $this->db_import_row['count'] ) && empty( $this->count ) ? absint( $this->db_import_row['count'] ) : $this->count;
		$this->offset    = isset( $this->db_import_row['offset'] ) && empty( $this->offset ) ? absint( $this->db_import_row['offset'] ) : $this->offset;

		return is_array( $this->db_import_row ) && ! empty( $this->db_import_row );
	}

	/**
	 * Get total orders to import
	 *
	 * @return int
	 */
	public function get_orders_count() {
		/**
		 * Get total order id
		 */
		$all_order_ids = wc_get_orders( array(
			'return'       => 'ids',
			'numberposts'  => '-1',
			'type'         => 'shop_order',
			'parent'       => 0,
			'date_created' => '<' . time(),
			'status'       => wc_get_is_paid_statuses(),
		) );

		/**
		 * Count orders
		 */
		$get_threshold_order = count( $all_order_ids );

		return $get_threshold_order;
	}

	/**
	 * Create a new row in import_export table for importer
	 *
	 * @param array $tags
	 * @param array $lists
	 * @param false $marketing_status
	 * @param bool $disable_events
	 *
	 * @return int
	 */
	public function create_wc_import_record( $tags = array(), $lists = array(), $marketing_status = false, $disable_events = false, $imported_contact_status = 1, $update_existing = false ) {
		/**
		 * Adding wp importer entry in DB
		 */
		BWFAN_Model_Import_Export::insert( array(
			'offset'        => 0,
			'processed'     => 0,
			'count'         => $this->get_orders_count(),
			'type'          => BWFCRM_Importer::$IMPORT,
			'status'        => BWFCRM_Importer::$IMPORT_IN_PROGRESS,
			'meta'          => wp_json_encode( array(
				'import_type'             => 'wc',
				'tags'                    => $tags,
				'lists'                   => $lists,
				'marketing_status'        => $marketing_status,
				'disable_events'          => $disable_events,
				'imported_contact_status' => $imported_contact_status,
				'update_existing'         => $update_existing,
				'log_file'                => $this->log_file,
			) ),
			'created_date'  => current_time( 'mysql', 1 ),
			'last_modified' => date( 'Y-m-d H:i:s', time() - 6 ),
		) );

		return BWFAN_Model_Import_Export::insert_id();
	}

	/**
	 * Returns meta data by key provided
	 *
	 * @param string $key
	 *
	 * @return mixed|string
	 */
	public function get_import_meta( $key = '' ) {
		return ! empty( $key ) && isset( $this->import_meta[ $key ] ) ? $this->import_meta[ $key ] : '';
	}

	/**
	 * Populate order to import
	 *
	 * @throws Exception
	 */
	private function populate_wc_orders() {
		$this->current_pos = 0;
		$this->offset      = isset( $this->db_import_row['offset'] ) && empty( $this->offset ) ? absint( $this->db_import_row['offset'] ) : $this->offset;

		/**
		 * Fetching orders to import from
		 */
		$orders = wc_get_orders( array(
			'return'       => 'ids',
			'type'         => 'shop_order',
			'parent'       => 0,
			'limit'        => 25,
			'offset'       => $this->offset,
			'date_created' => '<' . strtotime( $this->db_import_row['created_date'] ),
			'status'       => wc_get_is_paid_statuses(),
		) );

		// $orders_str = is_array( $orders ) ? implode( ',', $orders ) : '';
		// BWFAN_Core()->logger->log( 'order IDs populated for import: ' . $orders_str , 'import_wc_contacts' );

		$this->wc_orders = $orders;
	}

	/**
	 * Returns the progress of importer
	 *
	 * @return int
	 */
	public function get_percent_completed() {
		$processed = isset( $this->db_import_row['processed'] ) && ! empty( absint( $this->db_import_row['processed'] ) ) ? absint( $this->db_import_row['processed'] ) : 0;
		$count     = isset( $this->db_import_row['count'] ) && ! empty( absint( $this->db_import_row['count'] ) ) ? absint( $this->db_import_row['count'] ) : 0;

		if ( 0 === $count ) {
			return 100;
		}

		if ( 0 === $processed ) {
			return 0;
		}

		return absint( min( ( ( $processed / $count ) * 100 ), 100 ) );
	}

	/**
	 * Update last_modified to inform subsequent requests
	 */
	public function update_last_modified( $minus = 0 ) {
		$last_modified_time = time() - $minus;
		$last_modified_time = date( 'Y-m-d H:i:s', $last_modified_time );
		BWFAN_Model_Import_Export::update( array(
			'last_modified' => $last_modified_time,
		), array( 'id' => absint( $this->import_id ) ) );

		$this->db_import_row['last_modified'] = $last_modified_time;
	}

	/**
	 * Action Scheduler Contact Import
	 *
	 * @param $import_id
	 *
	 * @throws Exception
	 */
	public function import( $import_id ) {
		/**
		 * End import when import data is not found
		 */
		if ( ! $this->maybe_get_import( $import_id ) ) {
			$this->end_import( 2, 'Unable to get Import ID: ' . $import_id );

			return;
		}

		if ( $this->is_recently_imported() || ! empty( $this->db_import_row['meta']['is_running'] ) ) {
			return;
		}

		/** Update Last Modified time to inform subsequent requests */
		$this->update_last_modified();

		/** Set Import to running */
		$this->update_status_to_running();

		/** Populate the Woocommerce Orders */
		$this->populate_wc_orders();

		$this->start_import_time = time();

		/** Create log file for failed and skipped contacts */
		$this->log_handle = fopen( $this->log_file, 'a' );

		/** Set */
		$run_time = BWFCRM_Common::get_contact_export_per_call_time();

		while ( ( ( time() - $this->start_import_time ) < $run_time ) && ! BWFCRM_Common::memory_exceeded() ) {
			/** End Import on 100% */
			if ( $this->get_percent_completed() >= 100 ) {
				$this->end_import();

				return;
			}

			/** Populate Orders when previous orders are done importing */
			if ( $this->current_pos >= count( $this->wc_orders ) ) {
				/**
				 * Fetch next set of orders to import
				 */
				$this->populate_wc_orders();
			}

			/** if no orders found */
			if ( empty( $this->wc_orders ) ) {
				$this->processed ++;
				$this->offset ++;
				$this->current_pos ++;
			}

			/**
			 * Import contact from order data
			 */
			$this->import_order();

			/**
			 * Updated import entry record
			 */
			$this->update_import_record();
		}

		if ( ! empty( $this->log_handle ) ) {

			fclose( $this->log_handle );
		}

		if ( $this->get_percent_completed() >= 100 ) {
			$this->end_import();

			return;
		}

		/** Remove the import running status */
		$this->update_status_to_running( true );

		$this->update_last_modified( 7 );
		BWFCRM_Core()->importer->reschedule_background_action( $import_id, $this->action_hook );
	}

	/**
	 * Check if import action is run less than 5 seconds ago
	 *
	 * @return bool
	 */
	public function is_recently_imported() {
		$last_modified_seconds = time() - strtotime( $this->db_import_row['last_modified'] );

		return $last_modified_seconds <= 5;
	}

	/**
	 * Import Order
	 */
	public function import_order() {
		$order_id = $this->wc_orders[ $this->current_pos ];

		/**
		 * Check for order id
		 */
		if ( empty( absint( $order_id ) ) ) {
			return;
		}

		WooFunnels_DB_Updater::$indexing = true;
		try {
			/**
			 * Get or create contact.
			 */
			$contact_data = bwf_create_update_contact( $order_id, array(), 0, true );
		} catch ( Error $e ) {
			/**
			 * Adding error log if import failed
			 */
			$error_msg = $e->getMessage();
			BWFAN_Core()->logger->log( 'order id #' . $order_id . ' parsing broke with error message: ' . $error_msg, 'import_wc_failed_contacts' );
		}
		WooFunnels_DB_Updater::$indexing = null;

		if ( empty( $contact_data ) ) {
			/**
			 * If failed add failed count
			 */
			$this->failed ++;

			$error_msg = empty( $error_msg ) ? __( 'Order data is not valid.', 'wp-marketing-automations-pro' ) : $error_msg;
			$order     = wc_get_order( $order_id );
			$email     = $order instanceof WC_Order ? $order->get_billing_email() : '';
			$this->prepare_log_data( $email, 'failed', $error_msg, $order_id );
		} else {
			/**
			 * Contact Imported , increase success count
			 */
			$this->succeed ++;
		}

		/**
		 * Updating importer count and move to next order
		 */
		$this->processed ++;
		$this->offset ++;
		$this->current_pos ++;
	}

	/**
	 * Update table data after importing contact
	 */
	public function update_import_record() {
		/**
		 * Set new stat of importer
		 */
		$this->import_meta['log'] = array(
			'succeed' => $this->succeed,
			'failed'  => $this->failed,
			'skipped' => $this->skipped,
		);

		$import_meta = wp_json_encode( $this->import_meta );

		BWFAN_Model_Import_Export::update( array(
			'offset'        => $this->offset,
			'processed'     => $this->processed,
			'meta'          => $import_meta,
			'last_modified' => current_time( 'mysql', 1 ),
		), array( 'id' => absint( $this->import_id ) ) );

		$this->db_import_row['offset']    = $this->offset;
		$this->db_import_row['processed'] = $this->processed;
		$this->db_import_row['meta']      = $import_meta;
	}

	public function update_status_to_running( $remove = false ) {
		if ( $remove && isset( $this->import_meta['is_running'] ) ) {
			unset( $this->import_meta['is_running'] );
		} else {
			$this->import_meta['is_running'] = true;
		}
		$import_meta = wp_json_encode( $this->import_meta );

		/** Update status to running */
		BWFAN_Model_Import_Export::update( array( 'meta' => $import_meta ), array( 'id' => absint( $this->import_id ) ) );
		$this->db_import_row['meta'] = $import_meta;
	}

	/**
	 * Stop the importer running
	 *
	 * @param int $status
	 * @param string $status_message
	 */
	public function end_import( $status = 3, $status_message = '' ) {
		/**
		 * Checks importer entry exists
		 */
		if ( empty( $this->import_id ) ) {
			return;
		}

		$db_status = absint( $this->db_import_row['status'] );

		/**
		 * Check if import action is scheduled and status is in progress
		 */
		if ( bwf_has_action_scheduled( $this->action_hook ) && $db_status === BWFCRM_Importer::$IMPORT_IN_PROGRESS ) {
			bwf_unschedule_actions( $this->action_hook, array( 'import_id' => absint( $this->import_id ) ), 'bwfcrm' );
		}

		/**
		 * Adding log message
		 */
		if ( ! empty( $status_message ) ) {
			BWFAN_Core()->logger->log( $status_message, 'import_wc_contacts' );
		} elseif ( 3 === $status ) {
			$status_message = 'Contacts imported. Import ID: ' . $this->import_id;
			if ( ! empty( get_option( 'bwfan_show_contacts_from' ) ) ) {
				delete_option( 'bwfan_show_contacts_from' );
			}
		}

		$this->db_import_row['status']   = $status;
		$this->import_meta['status_msg'] = $status_message;

		/** Checking csv log file row count is greater 1 */
		$file_data = file( $this->log_file );
		if ( ! is_array( $file_data ) || empty( $file_data ) ) {
			unset( $this->import_meta['log_file'] );
			wp_delete_file( $this->log_file );
		}
		if ( isset( $this->import_meta['is_running'] ) ) {
			unset( $this->import_meta['is_running'] );
		}


		/**
		 * Updating importer data in DB
		 */
		BWFAN_Model_Import_Export::update( array(
			'status' => $status,
			'meta'   => wp_json_encode( $this->import_meta ),
		), array( 'id' => absint( $this->import_id ) ) );
	}

	/**
	 * Prepare log data & append data to file
	 *
	 * @param $email
	 * @param $status
	 * @param $err_msg
	 * @param $order_id
	 */
	public function prepare_log_data( $email, $status, $err_msg, $order_id = '' ) {
		$data = array(
			$email,
			$order_id,
			$status,
			$err_msg,
		);

		if ( ! empty( $this->log_handle ) ) {

			fputcsv( $this->log_handle, $data );
		}

	}
}

BWFCRM_Core()->importer->register( 'wc', 'BWFCRM_WC_Importer' );
