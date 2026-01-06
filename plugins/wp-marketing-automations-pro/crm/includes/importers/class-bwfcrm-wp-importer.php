<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFCRM_WP_Importer
 *
 * @package Autonami CRM
 */
#[AllowDynamicProperties]
class BWFCRM_WP_Importer {
	/**
	 * WordPress importer action hook
	 *
	 * @var string
	 */
	private $action_hook = 'bwfcrm_wp_import';

	/**
	 * Working Import ID
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
	 * Array of users fetched
	 *
	 * @var array
	 */
	private $wp_users = array();

	/** Import Contacts Results */
	private $skipped = 0;
	private $failed = 0;
	private $succeed = 0;
	private $processed = 0;
	private $count = 0;
	private $offset = 0;

	private $log_handle = null;

	private $newTags = [];
	private $newLists = [];
	private $log_file = null;

	/**
	 * Returns all user roles for selection in import process
	 *
	 * @return mixed|string|void
	 */
	public static function get_wp_roles() {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		/**
		 * Get all editable roles
		 */
		$roles = get_editable_roles();
		if ( ! is_array( $roles ) || empty( $roles ) ) {
			return __( 'Invalid User Roles / Unknow Error', 'wp-marketing-automations-pro' );
		}

		/**
		 * Formatting role array
		 */
		$roles_for_api = array();
		foreach ( $roles as $slug => $role ) {
			$roles_for_api[ $slug ] = isset( $role['name'] ) ? $role['name'] : $slug;
		}

		return apply_filters( 'bwfcrm_wp_roles_for_import', $roles_for_api );
	}

	/**
	 * Create Import & Start Import
	 *
	 * @param array $roles
	 * @param array $tags
	 * @param array $lists
	 * @param bool $update_existing
	 * @param bool $marketing_status
	 */
	public function create_import( $roles = array(), $tags = array(), $lists = array(), $update_existing = false, $marketing_status = false, $disable_events = true, $imported_contact_status = 1 ) {
		$this->log_file = BWFCRM_Importer::get_log_file_name( 'wp' );
		$import_id      = $this->create_wp_import_record( $roles, $tags, $lists, $update_existing, $marketing_status, $disable_events, $imported_contact_status );

		/** Create Log File */

		$log_file_header = array( 'Email', 'User ID', 'Status', 'Error Message' );
		BWFCRM_Importer::create_importer_log_file( $this->log_file, $log_file_header );

		/** Schedule the Import */
		BWFCRM_Core()->importer->reschedule_background_action( $import_id, $this->action_hook );

		return $import_id;
	}

	/**
	 * Get Import Status or Start Import
	 *
	 * @param int $import_id
	 *
	 * @return array|string
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

	/**
	 * Returns import stat and create import record if not found
	 *
	 * @param $import_id
	 *
	 * @return bool
	 */
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

		if ( isset( $this->import_meta['log_file'] ) ) {
			$this->log_file = $this->import_meta['log_file'];
		}

		return is_array( $this->db_import_row ) && ! empty( $this->db_import_row );
	}

	/**
	 * Returns current processing users id
	 *
	 * @param int $offset
	 * @param array $role__in
	 * @param string $limit
	 *
	 * @return array
	 */
	public function get_wp_users_array( $offset = 0, $role__in = array(), $limit = '' ) {
		global $wpdb;
		/**
		 * Passing roles data with OR relation
		 */
		$role__in_clauses = array( 'relation' => 'OR' );
		/**
		 * Checking role and formatting for WP_Meta_Query
		 */
		if ( ! empty( $role__in ) ) {
			foreach ( $role__in as $role ) {
				$role__in_clauses[] = array(
					'key'     => $wpdb->prefix . 'capabilities',
					'value'   => '"' . $role . '"',
					'compare' => 'LIKE',
				);
			}
		}

		/**
		 * Forming meta sql query for role.
		 */
		$meta_query          = new WP_Meta_Query();
		$meta_query->queries = array(
			'relation' => 'AND',
			array( $role__in_clauses ),
		);

		$offset_query = '';
		if ( intval( $offset ) > 0 ) {
			$offset_query = "AND ID < $offset";
		}

		$metadata = $meta_query->get_sql( 'user', $wpdb->users, 'ID', $role__in_clauses );
		$query    = 'SELECT ID from ' . $wpdb->users . ' ' . $metadata['join'] . " WHERE 1 = 1 $offset_query " . $metadata['where'] . ' ORDER BY ID DESC ';
		if ( intval( $limit > 0 ) ) {
			$query .= "LIMIT 0, $limit";
		}

		return $wpdb->get_col( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Populate the user data
	 *
	 * @param $role
	 */
	public function populate_wp_users( $role ) {
		if ( $this->offset <= 1 ) {
			$this->wp_users = array();

			return;
		}

		/**
		 * Set offset
		 */
		$user_arr = $this->get_wp_users_array( $this->offset, $role, 25 );
		if ( empty( $user_arr ) ) {
			$this->wp_users = array();

			return;
		}
		/**
		 * Fetching uses to import
		 */
		$users = get_users( array(
			'include' => $user_arr,
		) );

		$this->wp_users = array();

		/**
		 * Formatting user data
		 */
		if ( ! empty( $users ) ) {
			/** @var WP_User $user */
			foreach ( $users as $user ) {
				$this->wp_users[ absint( $user->ID ) ] = $user;
			}
			krsort( $this->wp_users, 1 );
		}
	}

	/**
	 * Insert a new row for importer
	 *
	 * @param array $roles
	 * @param array $tags
	 * @param array $lists
	 * @param false $update_existing
	 * @param false $marketing_status
	 * @param bool $disable_events
	 *
	 * @return int
	 */
	public function create_wp_import_record( $roles = array(), $tags = array(), $lists = array(), $update_existing = false, $marketing_status = false, $disable_events = false, $imported_contact_status = 1 ) {

		$user_ids = $this->get_wp_users_array( 0, $roles, '' );
		krsort( $user_ids );

		/**
		 * Adding wp importer entry in DB
		 */
		BWFAN_Model_Import_Export::insert( array(
			'offset'        => $user_ids[0] + 1,
			'processed'     => 0,
			'count'         => count( $user_ids ),
			'type'          => BWFCRM_Importer::$IMPORT,
			'status'        => BWFCRM_Importer::$IMPORT_IN_PROGRESS,
			'meta'          => wp_json_encode( array(
				'import_type'             => 'wp',
				'roles'                   => $roles,
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
	 * Check if import action is run less than 5 seconds ago
	 *
	 * @return bool
	 */
	public function is_recently_imported() {
		$last_modified_seconds = time() - strtotime( $this->db_import_row['last_modified'] );

		return $last_modified_seconds <= 5;
	}

	/**
	 * Returns progress of the importer
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

		if ( $this->is_recently_imported() ) {
			return;
		}

		/** Update last_modified to inform subsequent requests */
		$this->update_last_modified();

		/** Set Import to running */
		$this->update_status_to_running();

		$this->start_import_time = time();

		$this->log_handle = fopen( $this->log_file, 'a' );

		$run_time = BWFCRM_Common::get_contact_export_per_call_time();

		if ( ! empty( $this->get_import_meta( 'tags' ) ) ) {
			$this->newTags = BWFAN_Model_Terms::get_crm_term_ids( $this->get_import_meta( 'tags' ), BWFCRM_Term_Type::$TAG );
		}

		if ( ! empty( $this->get_import_meta( 'lists' ) ) ) {
			$this->newLists = BWFAN_Model_Terms::get_crm_term_ids( $this->get_import_meta( 'lists' ), BWFCRM_Term_Type::$LIST );
		}

		while ( ( ( time() - $this->start_import_time ) < $run_time ) && ! BWFCRM_Common::memory_exceeded() ) {
			/** Populate Next Set of WP Users */
			$this->populate_wp_users( $this->get_import_meta( 'roles' ) );
			if ( empty( $this->wp_users ) ) {
				break;
			}

			foreach ( array_keys( $this->wp_users ) as $user_id ) {
				$this->offset = $user_id;

				/**
				 * Import contact from user array
				 */
				$this->import_contact();

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
			return;
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

		/** Get or Create contact and updated status accordingly */
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

			return;
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
			if ( ! empty( $this->newTags ) ) {
				$contact->set_tags_v2( $this->newTags, $disable_events );
			}

			/** Apply lists */
			if ( ! empty( $this->newLists ) ) {
				$contact->set_lists_v2( $this->newLists, $disable_events );
			}

		} catch ( Exception $e ) {
			$this->processed ++;
			$this->failed ++;

			$error_msg = $e->getMessage();
			$this->prepare_log_data( $contact_data['email'], 'failed', $error_msg, $contact_data['data']['wp_id'] );

			return;
		}

		if ( $fields_updated ) {
			$contact->save_fields();
		}

		$contact->save();

		/** Contact Imported, increase success count and move to next user */
		$this->succeed ++;
		$this->processed ++;
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
		$user = $this->wp_users[ $this->offset ];

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
			'wp_id'  => $user->ID
		);

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

		$data['source'] = 'wp_user';

		return array(
			'email' => $email,
			'data'  => $data
		);
	}

	/**
	 * Update the importer data in table
	 */
	public function update_import_record() {
		/**
		 * Set new stat of importer
		 */
		$this->import_meta['log'] = array( 'succeed' => $this->succeed, 'failed' => $this->failed, 'skipped' => $this->skipped );

		$import_meta = wp_json_encode( $this->import_meta );

		BWFAN_Model_Import_Export::update( array(
			"offset"        => $this->offset,
			'processed'     => $this->processed,
			"meta"          => $import_meta,
			'last_modified' => current_time( 'mysql', 1 )
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
			if ( file_exists( $this->log_file ) ) {
				/** Checking wp log file row count is greater 1 */
				$file_data = file( $this->log_file );
				if ( empty( $file_data ) || ! is_array( $file_data ) ) {
					wp_delete_file( $wp_log_file );
				}
			}
		}

		$this->db_import_row['status']   = $status;
		$this->import_meta['status_msg'] = $status_message;
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
	 * Prepare log data and append data to file
	 *
	 * @param $email
	 * @param $status
	 * @param $err_msg
	 * @param $user_id
	 *
	 * @return void
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

BWFCRM_Core()->importer->register( 'wp', 'BWFCRM_WP_Importer' );
