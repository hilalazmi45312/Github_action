<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_WLM_Importer
 *
 * @package Autonami CRM
 */
#[AllowDynamicProperties]
class BWFCRM_WLM_Importer extends BWFCRM_Importer_Base {
	private $action_hook = 'bwfcrm_wlm_import';

	private $import_id = 0;
	private $db_import_row = array();
	private $import_meta = array();

	private $start_import_time = 0;
	private $members = array();

	/** Import Contacts Results */
	private $skipped = 0;
	private $failed = 0;
	private $succeed = 0;
	private $processed = 0;
	private $count = 0;
	private $offset = 0;

	private $log_handle = null;

	/**
	 * Create & Start Import
	 *
	 * @param array $tags
	 * @param array $lists
	 * @param false $update_existing
	 * @param false $marketing_status
	 * @param bool $disable_events
	 * @param int $imported_contact_status
	 *
	 * @return int
	 */
	public function create_import( $tags = array(), $lists = array(), $update_existing = false, $marketing_status = false, $disable_events = true, $imported_contact_status = 1 ) {
		$this->log_file = BWFCRM_Importer::get_log_file_name( 'wlm' );
		$import_id      = $this->create_wlm_import( $tags, $lists, $update_existing, $marketing_status, $disable_events, $imported_contact_status );

		$log_file_header = array( 'Email', 'User ID', 'Status', 'Error Message' );
		BWFCRM_Importer::create_importer_log_file( $this->log_file, $log_file_header );

		$this->maybe_create_wlm_table();
		BWFCRM_Core()->importer->reschedule_background_action( $import_id, $this->action_hook );

		return $import_id;
	}

	public function maybe_create_wlm_table() {
		/** @var BWFCRM_Integration_Wishlist_Member $ins */
		$ins = BWFCRM_Core()->integrations->get_integration( 'wishlist_member' );
		$ins->maybe_create_db_table();
		$ins->maybe_drop_unwanted_columns();
		BWF_Model_Contact_WLM_Fields::truncate();
	}

	/**
	 * Insert a new row for importer
	 *
	 * @param array $tags
	 * @param array $lists
	 * @param false $update_existing
	 * @param false $marketing_status
	 * @param bool $disable_events
	 *
	 * @return int
	 */
	public function create_wlm_import( $tags = array(), $lists = array(), $update_existing = false, $marketing_status = false, $disable_events = false, $imported_contact_status = 1 ) {
		$members = $this->get_members( 0, 1 );
		BWFAN_Model_Import_Export::insert( array(
			'offset'        => $members[0] + 1,
			'processed'     => 0,
			'count'         => $this->get_members_count(),
			'type'          => BWFCRM_Importer::$IMPORT,
			'status'        => BWFCRM_Importer::$IMPORT_IN_PROGRESS,
			'meta'          => wp_json_encode( array(
				'import_type'             => 'wlm',
				'update_existing'         => $update_existing,
				'tags'                    => $tags,
				'lists'                   => $lists,
				'marketing_status'        => $marketing_status,
				'disable_events'          => $disable_events,
				'imported_contact_status' => $imported_contact_status,
				'log_file'                => $this->log_file,
			) ),
			'created_date'  => current_time( 'mysql', 1 ),
			'last_modified' => date( 'Y-m-d H:i:s', time() - 6 ),
		) );

		return BWFAN_Model_Import_Export::insert_id();
	}

	/**
	 * Functions: Get Import Status or Start Import
	 *
	 * @param $import_id
	 *
	 * @return array|string|null
	 */
	public function get_import_status( $import_id = 0 ) {
		/**
		 * Check for import record exists
		 */
		if ( ! $this->maybe_get_import( $import_id ) ) {
			return __( 'Import record not found', 'wp-marketing-automations-pro' );
		}

		/**
		 * Get percent completed
		 */
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

	public function maybe_get_import( $import_id = 0 ) {
		/**
		 * check if import data already fetched
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

		$this->processed = isset( $this->db_import_row['processed'] ) && empty( $this->processed ) ? absint( $this->db_import_row['processed'] ) : $this->processed;
		$this->count     = isset( $this->db_import_row['count'] ) && empty( $this->count ) ? absint( $this->db_import_row['count'] ) : $this->count;
		$this->offset    = isset( $this->db_import_row['offset'] ) && empty( $this->offset ) ? absint( $this->db_import_row['offset'] ) : $this->offset;

		return is_array( $this->db_import_row ) && ! empty( $this->db_import_row );
	}

	/**
	 * Populate Wishlist Members
	 *
	 * @param $role
	 */
	public function populate_members() {
		$this->offset = isset( $this->db_import_row['offset'] ) && empty( $this->offset ) ? absint( $this->db_import_row['offset'] ) : $this->offset;
		$user_ids     = $this->get_members( $this->offset, 25 );

		/**
		 * Fetching uses to import
		 */
		$users = get_users( array(
			'include' => $user_ids,
		) );

		$this->members = array();

		/**
		 * Formatting user data
		 */
		if ( ! empty( $users ) ) {
			/** @var WP_User $user */
			foreach ( $users as $user ) {
				$this->members[ absint( $user->ID ) ] = $user;
			}
			krsort( $this->members, 1 );
		}
	}

	public function get_members( $offset, $limit ) {
		/** @var BWFCRM_Integration_Wishlist_Member $ins */
		$ins = BWFCRM_Core()->integrations->get_integration( 'wishlist_member' );

		return $ins->get_member_ids( array(
			'before' => $offset,
			'limit'  => $limit,
			'order'  => 'DESC',
		) );
	}

	public function get_members_count() {
		/** @var BWFCRM_Integration_Wishlist_Member $ins */
		$ins = BWFCRM_Core()->integrations->get_integration( 'wishlist_member' );

		return $ins->get_member_ids( array(
			'count_only' => true,
		) );
	}

	/**
	 * Rerurns progress of the importer
	 *
	 * @return int
	 */
	public function get_percent_completed() {
		$count     = isset( $this->db_import_row['count'] ) && ! empty( intval( $this->db_import_row['count'] ) ) ? intval( $this->db_import_row['count'] ) : 0;
		$processed = isset( $this->db_import_row['processed'] ) && ! empty( intval( $this->db_import_row['processed'] ) ) ? intval( $this->db_import_row['processed'] ) : 0;

		if ( 0 === $count ) {
			return 100;
		}

		if ( 0 === $processed ) {
			return 0;
		}

		return absint( min( ( ( $processed / $count ) * 100 ), 100 ) );
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

		/** Update last_modified to inform subsequent requests */
		$this->update_last_modified();

		/** Set Import to running */
		$this->update_status_to_running();

		$this->start_import_time = time();

		$this->log_handle = fopen( $this->log_file, 'a' );

		$run_time = BWFCRM_Common::get_contact_export_per_call_time();
		while ( ( ( time() - $this->start_import_time ) < $run_time ) && ! BWFCRM_Common::memory_exceeded() ) {
			$this->populate_members();
			if ( $this->get_percent_completed() >= 100 ) {
				$this->end_import();

				return;
			}

			foreach ( array_keys( $this->members ) as $user_id ) {

				$this->offset = $user_id;

				/**
				 * Import contact from user array
				 */
				$contact_id = absint( $this->import_contact() );
				if ( ! empty( $contact_id ) ) {
					$this->import_wlm_member( $contact_id, $user_id );
				}

				/**
				 * Updated import entry record
				 */
				$this->update_import_record();
			}
		}

		if ( ! empty( $this->log_handle ) ) {

			fclose( $this->log_handle );
		}

		/**
		 * End import if completed
		 */
		if ( $this->get_percent_completed() >= 100 ) {
			$this->end_import();

			return;
		}


		/** Remove import running status */
		$this->update_status_to_running( true );

		/** Update Last modified - 7 to run next iteration immediately */
		$this->update_last_modified( 7 );
		BWFCRM_Core()->importer->reschedule_background_action( $import_id, $this->action_hook );
	}

	/**
	 * Returns import meta
	 *
	 * @param string $key
	 *
	 * @return mixed|string
	 */
	public function get_import_meta( $key = '' ) {
		return ! empty( $key ) && isset( $this->import_meta[ $key ] ) ? $this->import_meta[ $key ] : '';
	}

	/**
	 * Import contact
	 */
	public function import_contact() {
		/**
		 * Get formatted contact data from user
		 */
		$contact_data = $this->prepare_contact_data();
		if ( false === $contact_data ) {
			return 0;
		}

		/** Disable Events */
		$disable_events = $this->get_import_meta( 'disable_events' );
		if ( true === $disable_events ) {
			$contact_data['data']['disable_events'] = true;
		}

		/** Setting Unsubscribe data before initializing contact, because if contact is new then need to update the status */
		$do_unsubscribe = false;
		if ( 3 === absint( $contact_data['data']['status'] ) ) {
			$contact_data['data']['status'] = 1;
			$do_unsubscribe                 = true;
		}

		/**
		 * Get or Create contact and updated status accordingly
		 */
		$contact = null;
		try {
			/** Initialising/Creating the Contact */
			$contact = new BWFCRM_Contact( $contact_data['email'], true, $contact_data['data'] );

			/** If Contact does exists, but doesn't have wpid */
			if ( $contact->is_contact_exists() && empty( $contact->contact->get_wpid() ) ) {
				$contact->contact->set_wpid( absint( $contact_data['data']['wp_id'] ) );
				$contact->contact->save();
			}
		} catch ( Exception $e ) {
			/**
			 * If failed add failed count and set next user to process
			 */
			$this->processed ++;
			$this->failed ++;

			$error_msg = $e->getMessage();
			$this->prepare_log_data( $contact_data['email'], 'failed', $error_msg, $contact_data['data']['wp_id'] );

			return $contact instanceof BWFCRM_Contact && $contact->is_contact_exists() ? $contact->get_id() : 0;
		}

		/** Check if contact need to update if existing */
		$update_existing = $this->get_import_meta( 'update_existing' );

		/** If contact already exists (old) and Update Existing flag is off, then unset status */
		if ( $contact->already_exists && ! $update_existing ) {
			unset( $contact_data['data']['status'] );
		}

		/**
		 * Update contact if update_existing = true
		 */
		$fields_updated = false;
		try {
			/** If needed to do unsubscribe */
			if ( $do_unsubscribe && $update_existing ) {
				$contact->unsubscribe( $disable_events );
			}

			/** If contact was unsubscribed, and new status is not unsubscribed, then remove the entries from Unsubscriber Table */
			if ( ! $do_unsubscribe && $update_existing ) {
				/** NOTE: Checking for: "If the contact was unsubscribed" is already in this below function */
				$contact->remove_unsubscribe_status();
			}

			/** Set Data only if contact already exists, as in case of new contact, data is already been set in above code. */
			if ( $contact->already_exists ) {
				$result         = $contact->set_data( $contact_data['data'] );
				$fields_updated = $result['fields_changed'];
			}

			/** Apply tags */
			$contact->set_tags( $this->get_import_meta( 'tags' ), true, $disable_events );

			/** Apply lists */
			$contact->set_lists( $this->get_import_meta( 'lists' ), true, $disable_events );

		} catch ( Exception $e ) {
			$this->processed ++;
			$this->failed ++;

			$error_msg = $e->getMessage();
			$this->prepare_log_data( $contact_data['email'], 'failed', $error_msg, $contact_data['data']['wp_id'] );

			return $contact->get_id();
		}

		if ( $fields_updated ) {
			$contact->save_fields();
		}

		$contact->save();

		/** Contact Imported, increase success count and move to next user */
		$this->succeed ++;
		$this->processed ++;

		return $contact->get_id();
	}

	/** Import Contact to WLM */
	public function import_wlm_member( $contact_id, $user_id ) {
		$levels = wlmapi_get_member_levels( $user_id );
		if ( ! is_array( $levels ) || empty( $levels ) ) {
			return;
		}

		/** @var BWFCRM_Integration_Wishlist_Member $wlm_integration */
		$wlm_integration = BWFCRM_Core()->integrations->get_integration( 'wishlist_member' );

		$status = array();
		$reg    = array();
		$exp    = array();
		foreach ( $levels as $id => $level ) {
			$status[ absint( $id ) ] = strval( $wlm_integration->get_status_code_from_api( $level ) );
			$reg[ absint( $id ) ]    = date( 'Y-m-d H:i:s', absint( $level->Timestamp ) );
			if ( ! empty( $level->ExpiryDate ) ) {
				$exp[ absint( $id ) ] = date( 'Y-m-d H:i:s', absint( $level->ExpiryDate ) );
			}
		}

		BWF_Model_Contact_WLM_Fields::insert( $contact_id, $status, $reg, $exp );
	}

	/**
	 * Returns formatted contact data
	 *
	 * @return array
	 */
	public function prepare_contact_data() {
		/**
		 * Get user data form wp_user based on current position
		 */
		$user = $this->members[ $this->offset ];

		/**
		 * Fallback if WP_User data not found
		 */
		if ( ! $user instanceof WP_User ) {
			$user = get_user_by( 'id', $this->offset );
		}

		/**
		 * Get contact fields
		 */
		$contact_fields = BWFCRM_Fields::get_contact_fields_from_db( 'slug' );

		/**
		 * Set marketing status to set
		 */
		$import_status = isset( $this->import_meta['marketing_status'] ) ? absint( $this->import_meta['marketing_status'] ) : 0;

		if ( isset( $this->import_meta['imported_contact_status'] ) ) {
			$import_status = $this->get_import_meta( 'imported_contact_status' );
		}

		/**
		 * Form contact data form user data
		 */
		$email = $user->user_email;
		$data  = array(
			'f_name' => $user->first_name,
			'l_name' => $user->last_name,
			'status' => $import_status,
			'wp_id'  => $user->ID,
		);

		// /** WLM User Meta */
		// $address  = $this->get_member_address( $user->ID );
		// $city     = ! empty( $address['city'] ) ? $address['city'] : get_user_meta( $user->ID, 'billing_city', true );
		// $state    = ! empty( $address['state'] ) ? $address['state'] : get_user_meta( $user->ID, 'billing_state', true );
		// $country  = ! empty( $address['country'] ) ? $address['country'] : get_user_meta( $user->ID, 'billing_country', true );
		// $postcode = ! empty( $address['zip'] ) ? $address['zip'] : get_user_meta( $user->ID, 'billing_postcode', true );
		// $address1 = ! empty( $address['address1'] ) ? $address['address1'] : get_user_meta( $user->ID, 'billing_address_1', true );
		// $address2 = ! empty( $address['address2'] ) ? $address['address2'] : get_user_meta( $user->ID, 'billing_address_2', true );
		// $company  = ! empty( $address['company'] ) ? $address['company'] : get_user_meta( $user->ID, 'billing_company', true );

		/** WooCommerce User Meta */
		$phone    = get_user_meta( $user->ID, 'billing_phone', true );
		$city     = get_user_meta( $user->ID, 'billing_city', true );
		$state    = get_user_meta( $user->ID, 'billing_state', true );
		$country  = get_user_meta( $user->ID, 'billing_country', true );
		$postcode = get_user_meta( $user->ID, 'billing_postcode', true );
		$address1 = get_user_meta( $user->ID, 'billing_address_1', true );
		$address2 = get_user_meta( $user->ID, 'billing_address_2', true );
		$company  = get_user_meta( $user->ID, 'billing_company', true );

		$data['f_name'] = empty( $data['f_name'] ) ? get_user_meta( $user->ID, 'billing_first_name', true ) : $data['f_name'];
		$data['l_name'] = empty( $data['f_name'] ) ? get_user_meta( $user->ID, 'billing_last_name', true ) : $data['l_name'];
		$email          = empty( $email ) ? get_user_meta( $user->ID, 'billing_email', true ) : $email;

		! empty( $postcode ) ? $data[ $contact_fields['postcode']['ID'] ] = $postcode : null;
		! empty( $address1 ) ? $data[ $contact_fields['address-1']['ID'] ] = $address1 : null;
		! empty( $address2 ) ? $data[ $contact_fields['address-2']['ID'] ] = $address2 : null;
		! empty( $company ) ? $data[ $contact_fields['company']['ID'] ] = $company : null;
		! empty( $city ) ? $data[ $contact_fields['city']['ID'] ] = $city : null;

		! empty( $phone ) ? $data['contact_no'] = $phone : null;
		! empty( $state ) ? $data['state'] = $state : null;
		! empty( $country ) ? $data['country'] = BWFAN_PRO_Common::get_country_iso_code( $country ) : null;

		$data['source'] = 'wlm';

		return array(
			'email' => $email,
			'data'  => $data,
		);
	}

	public function get_member_address( $member_id ) {
		/** @var BWFCRM_Integration_Wishlist_Member $ins */
		$ins = BWFCRM_Core()->integrations->get_integration( 'wishlist_member' );

		return $ins->get_member_address( $member_id );
	}

	/**
	 * Update the importer data in table
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
	 * Stops importer running
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

		/**
		 * Check if import action is scheduled and status is in progress
		 */
		$db_status = absint( $this->db_import_row['status'] );
		if ( bwf_has_action_scheduled( $this->action_hook ) && $db_status === BWFCRM_Importer::$IMPORT_IN_PROGRESS ) {
			bwf_unschedule_actions( $this->action_hook, array( 'import_id' => absint( $this->import_id ) ), 'bwfcrm' );
		}

		/**
		 * Adding log message
		 */
		if ( ! empty( $status_message ) ) {
			BWFAN_Core()->logger->log( $status_message, 'import_contacts_crm' );
		} elseif ( 3 === $status ) {
			$status_message = 'Contacts imported. Import ID: ' . $this->import_id;
		}

		$this->db_import_row['status']   = $status;
		$this->import_meta['status_msg'] = $status_message;

		/** Checking csv file row count is greater 1 */
		$file_data = file( $this->log_file );
		if ( empty( $file_data ) || ! is_array( $file_data ) ) {
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

		$key             = 'bwfan_import_done';
		$imported        = get_option( $key, [] );
		$imported['wlm'] = 1;
		update_option( $key, $imported );
	}

	/**
	 * Prepare log data and append data to file
	 *
	 * @param $email
	 * @param $status
	 * @param $err_msg
	 * @param $contact
	 */
	public function prepare_log_data( $email, $status, $err_msg, $user_id = '' ) {
		$data = array(
			$email,
			$user_id,
			$status,
			$err_msg,
		);

		if ( ! empty( $this->log_handle ) ) {

			fputcsv( $this->log_handle, $data );
		}
	}

}

BWFCRM_Core()->importer->register( 'wlm', 'BWFCRM_WLM_Importer' );
