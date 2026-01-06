<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class BWFAN_Pro_DB
 *
 * @package Autonami
 */
#[AllowDynamicProperties]
class BWFAN_Pro_DB {
	private static $ins = null;
	protected $method_run = [];

	/**
	 * BWFAN_Pro_DB constructor.
	 */
	public function __construct() {
		global $wpdb;
		$wpdb->bwfan_broadcast               = $wpdb->prefix . 'bwfan_broadcast';
		$wpdb->bwfan_bulk_action             = $wpdb->prefix . 'bwfan_bulk_action';
		$wpdb->bwfan_contact_note            = $wpdb->prefix . 'bwfan_contact_note';
		$wpdb->bwfan_conversions             = $wpdb->prefix . 'bwfan_conversions';
		$wpdb->bwfan_engagement_tracking     = $wpdb->prefix . 'bwfan_engagement_tracking';
		$wpdb->bwfan_engagement_trackingmeta = $wpdb->prefix . 'bwfan_engagement_trackingmeta';
		$wpdb->bwfan_fields                  = $wpdb->prefix . 'bwfan_fields';
		$wpdb->bwfan_field_groups            = $wpdb->prefix . 'bwfan_field_groups';
		$wpdb->bwfan_form_feeds              = $wpdb->prefix . 'bwfan_form_feeds';
		$wpdb->bwfan_import_export           = $wpdb->prefix . 'bwfan_import_export';
		$wpdb->bwfan_link_triggers           = $wpdb->prefix . 'bwfan_link_triggers';
		$wpdb->bwfan_message                 = $wpdb->prefix . 'bwfan_message';
		$wpdb->bwfan_templates               = $wpdb->prefix . 'bwfan_templates';
		$wpdb->bwfan_terms                   = $wpdb->prefix . 'bwfan_terms';
		$wpdb->bwf_contact_fields            = $wpdb->prefix . 'bwf_contact_fields';
		$wpdb->bwf_contact_lms_fields        = $wpdb->prefix . 'bwf_contact_lms_fields';
		$wpdb->bwf_contact_wlm_fields        = $wpdb->prefix . 'bwf_contact_wlm_fields';
		$wpdb->bwf_contact_wlm_fields        = $wpdb->prefix . 'bwfan_api_keys';

		add_action( 'plugins_loaded', array( $this, 'load_db_classes' ), 8 );
		add_action( 'admin_init', array( $this, 'db_update' ), 12 );

		/** Save time when db version updated */
		add_filter( 'pre_update_option_bwfan_pro_db', [ $this, 'db_updated' ] );
	}

	/**
	 * Return the object of current class
	 *
	 * @return null|BWFAN_Pro_DB
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Include all the DB Table files
	 */
	public static function load_db_classes() {
		$integration_dir = __DIR__ . '/db';
		foreach ( glob( $integration_dir . '/class-*.php' ) as $_field_filename ) {
			require_once $_field_filename;
		}
	}

	public function load_pro_table_classes() {
		/** loading base and all lite table classes */
		BWFAN_DB::load_table_classes();

		/** loading all the pro table classes after base and lite table class has been loaded */
		BWFAN_DB::load_class_files( __DIR__ . '/db/tables' );
	}

	/**
	 * Creating tables for v 1.0
	 */
	public function db_update() {
		if ( ! class_exists( 'BWFAN_DB' ) || ! method_exists( 'BWFAN_DB', 'load_table_classes' ) ) {
			return;
		}

		$db_changes = array(
			'1.9.4'  => '1_9_4',
			'1.9.5'  => '1_9_5',
			'1.9.6'  => '1_9_6',
			'2.0.2'  => '2_0_2',
			'2.0.4'  => '2_0_4',
			'2.0.5'  => '2_0_5',
			'2.0.6'  => '2_0_6',
			'2.0.7'  => '2_0_7',
			'2.0.8'  => '2_0_8',
			'2.0.9'  => '2_0_9',
			'2.0.11' => '2_0_11',
			'2.1.2'  => '2_1_2',
			'2.4.0'  => '2_4_0',
			'2.4.3'  => '2_4_3',
			'2.6.1'  => '2_6_1',
			'2.6.2'  => '2_6_2',
			'3.0.0'  => '3_0_0',
			'3.2.2'  => '3_2_2',
			'3.2.3'  => '3_2_3',
			'3.2.4'  => '3_2_4',
			'3.2.5'  => '3_2_5',
		);

		$saved_db_version = get_option( 'bwfan_pro_db', '1.2.2' );

		foreach ( $db_changes as $version_key => $version_value ) {
			if ( version_compare( $saved_db_version, $version_key, '<' ) ) {
				self::load_pro_table_classes();
				$function_name = 'db_update_' . $version_value;
				$this->$function_name( $version_key );
			}
		}
	}

	/**
	 * Save time when DB version updated
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	public function db_updated( $value ) {
		$versions           = bwf_options_get( 'bwfan_pro_db_updated', 'value', [] );
		$versions[ $value ] = current_time( 'mysql', 1 );
		bwf_options_update( 'bwfan_pro_db_updated', $versions );

		return $value;
	}

	/**
	 * Create tables
	 *
	 * @param $version_key
	 *
	 * @throws Exception
	 */
	public function db_update_1_9_4( $version_key ) {
		$db_errors = [];

		if ( ! BWFAN_PRO_Common::is_lite_3_0() ) {
			$table_instance = new BWFAN_DB_Table_Engagement_Tracking();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}

			$table_instance = new BWFAN_DB_Table_Engagement_Trackingmeta();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}

			$table_instance = new BWFAN_DB_Table_Templates();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}


			$table_instance = new BWFAN_DB_Table_Conversions();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}

			$table_instance = new BWFAN_DB_Table_Terms();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}

			$table_instance = new BWFAN_DB_Table_Fields();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}

			$table_instance = new BWFAN_DB_Table_Field_Groups();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}

			$table_instance = new BWFAN_DB_Table_Contact_Note();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}

			$table_instance = new BWFAN_DB_Table_Contact_Fields();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}
		}

		if ( ! class_exists( 'BWFAN_DB_Table_Lite_Import_Export' ) ) { // This class exists in FKA Lite, so we check if it exists to avoid conflicts.
			$table_instance = new BWFAN_DB_Table_Import_Export();
			$table_instance->create_table();
			if ( ! empty( $table_instance->db_errors ) ) {
				$db_errors[] = $table_instance->db_errors;
			}
		}

		$table_instance = new BWFAN_DB_Table_Broadcast();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$table_instance = new BWFAN_DB_Table_Form_Feeds();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$table_instance = new BWFAN_DB_Table_Message();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$table_instance = new BWFAN_DB_Table_Link_Triggers();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$table_instance = new BWFAN_DB_Table_Bulk_Action();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$table_instance = new BWFAN_DB_Table_API_Keys();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$table_instance = new BWFAN_DB_Table_Stripe_Offer();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$this->method_run[] = $version_key;

		/** Save pro start date */
		$this->save_pro_start_date();

		/** Updating version key */
		update_option( 'bwfan_pro_db', $version_key, true );

		/** Allow optin for FKA */
		WooFunnels_optIn_Manager::Allow_optin( true, 'FKA' );

		$db_update    = new BWFAN_Pro_DB_Update();
		$db_changes   = array_keys( $db_update->db_changes );
		$last_version = end( $db_changes );
		update_option( 'bwfan_pro_db_update', [ $last_version => 0 ], true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Create fields
	 *
	 * @param $version_key
	 */
	public function db_update_1_9_5( $version_key ) {
		// If FKA lite older than 3.0
		if ( ! BWFAN_PRO_Common::is_lite_3_0() ) {
			global $wpdb;
			/** Set View = 2 (Hide) for System Fields for existing fields */
			$query   = "SELECT `ID` FROM `{$wpdb->prefix}bwfan_fields` WHERE `slug` IN ('form-feed-id', 'last-click', 'last-open', 'last-login', 'address-1', 'address-2', 'city', 'postcode', 'company', 'gender', 'dob')"; // WPCS: unprepared SQL OK
			$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( is_array( $results ) && count( $results ) > 0 ) {
				$ids   = array_column( $results, 'ID' );
				$query = "UPDATE `{$wpdb->prefix}bwfan_fields` SET `view`= 2 WHERE `ID` IN (" . implode( ',', $ids ) . ")"; // WPCS: unprepared SQL OK

				$wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			/** Creating default contact fields */
			BWFAN_PRO_Common::insert_default_crm_fields();

			BWFCRM_Fields::add_field( 'Last Login', BWFCRM_Fields::$TYPE_DATE, array(), '', 2, 2, 1, 0, 2 );
			BWFCRM_Fields::add_field( 'Last Open', BWFCRM_Fields::$TYPE_DATE, array(), '', 2, 2, 1, 0, 2 );
			BWFCRM_Fields::add_field( 'Last Click', BWFCRM_Fields::$TYPE_DATE, array(), '', 2, 2, 1, 0, 2 );
		}

		BWFCRM_Fields::add_field( 'Form Feed ID', 2, array(), 'System Field', 2, 2, 1, 0, 2 );

		/** Type Checkbox will enable the JSON Array searching capabilities */
		BWFCRM_Fields::add_field( 'Unsubscribed Lists', BWFCRM_Fields::$TYPE_CHECKBOX, array(), 'System Field', 2, 2, 1, 0, 2 );

		$this->method_run[] = $version_key;

		/** Updating version key */
		update_option( 'bwfan_pro_db', $version_key, true );
	}

	/**
	 * Schedule cron' and extra savings
	 *
	 * @param $version_key
	 *
	 * @throws Exception
	 */
	public function db_update_1_9_6( $version_key ) {
		global $wpdb;

		/** Scheduling Broadcast action */
		if ( ! bwf_has_action_scheduled( 'bwfcrm_broadcast_run_queue' ) ) {
			bwf_schedule_recurring_action( time(), 10, 'bwfcrm_broadcast_run_queue', array(), 'bwfcrm' );
		}

		/** Schedule midnight cron and 5-min worker */
		BWFAN_PRO_Common::schedule_cron();

		/** Save last created contact id */
		$saved_val = get_option( 'bwfan_show_contacts_from', 0 );
		if ( empty( $saved_val ) ) {
			$last_contact_id = BWFCRM_Model_Contact::get_last_contact_id();
			if ( absint( $last_contact_id ) > 0 ) {
				update_option( 'bwfan_show_contacts_from', $last_contact_id, true );
			}
		}

		/** Delete unpaid or invalid conversions */
		$last_conversion_id = BWFAN_Model_Conversions::get_last_conversion_id();
		if ( absint( $last_conversion_id ) > 0 ) {
			$hook  = 'bwfan_check_conversion_validity';
			$group = 'autonami';
			$args  = array();

			if ( ! bwf_has_action_scheduled( $hook, $args, $group ) ) {
				update_option( 'bwfan_db_1_3_3_options', array( 'last_conversion_id' => $last_conversion_id ), false );
				bwf_schedule_recurring_action( time(), MINUTE_IN_SECONDS * 10, $hook, $args, $group );
			}
		}
		if ( ! is_array( $this->method_run ) || ! in_array( '1.9.4', $this->method_run, true ) ) {
			/** Delete Unwanted last login field */
			$wpdb->query( "DELETE FROM {$wpdb->prefix}bwfan_fields WHERE slug = 'lastlogin'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		$this->method_run[] = $version_key;

		/** Updating version key */
		update_option( 'bwfan_pro_db', $version_key, true );
	}

	/**
	 * Correct the default values and make the missing tables
	 *
	 * @param $version_key
	 *
	 * @throws Exception
	 */
	public function db_update_2_0_2( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		$db_errors = [];

		$table_instance = new BWFAN_DB_Table_Engagement_Tracking();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$table_instance = new BWFAN_DB_Table_Templates();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$table_instance = new BWFAN_DB_Table_Conversions();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Make new table for link triggers
	 * Add Indexes for Hash Code and CStatus columns
	 *
	 * @param $version_key
	 *
	 * @throws Exception
	 */
	public function db_update_2_0_4( $version_key ) {
		/** Create fields */
		BWFCRM_Fields::add_field( 'Link Trigger Click', BWFCRM_Fields::$TYPE_TEXTAREA, array(), '', 2, 2, 1, 0, 2 );
		BWFCRM_Fields::add_field( 'Auth', BWFCRM_Fields::$TYPE_TEXT, array(), '', 2, 2, 1, 0, 2 );
		BWFCRM_Fields::add_field( 'Last Sent', BWFCRM_Fields::$TYPE_DATE, array(), '', 2, 2, 1, 0, 2 );

		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		global $wpdb;
		$db_errors = [];

		$table_instance = new BWFAN_DB_Table_Link_Triggers();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		/** Add Indexes for Hash Code and C_Status keys */
		$wpdb->query( "ALTER TABLE `{$wpdb->prefix}bwfan_engagement_tracking` MODIFY COLUMN `hash_code` varchar(60), ADD KEY `hash_code`(`hash_code`), ADD KEY `c_status`(`c_status`)" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		if ( ! empty( $wpdb->last_error ) ) {
			$db_errors[] = 'bwfan_engagement_tracking indexes - ' . $wpdb->last_error;
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Add new column in table
	 *
	 * @param $version_key
	 *
	 * @throws Exception
	 */
	public function db_update_2_0_5( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}
		global $wpdb;
		$db_errors = [];

		/** Add Step Id (sid) column */
		$wpdb->query( "ALTER TABLE `{$wpdb->prefix}bwfan_engagement_tracking` ADD `sid` bigint(20) unsigned NOT NULL default 0 COMMENT 'Step ID', ADD KEY `sid` (`sid`)" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		if ( ! empty( $wpdb->last_error ) ) {
			$db_errors[] = 'bwfan_engagement_tracking new column - ' . $wpdb->last_error;
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Add indexes
	 *
	 * @param $version_key
	 *
	 * @throws Exception
	 */
	public function db_update_2_0_6( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}
		global $wpdb;
		$db_errors = [];

		/** Add Indexes for Hash Code and C_Status keys */
		$wpdb->query( "ALTER TABLE `{$wpdb->prefix}bwfan_engagement_tracking` ADD KEY `created_at`(`created_at`)" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		if ( ! empty( $wpdb->last_error ) ) {
			$db_errors[] = 'bwfan_engagement_tracking indexes - ' . $wpdb->last_error;
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	public function db_update_2_0_7( $version_key ) {
		/** Create automation fields */
		BWFCRM_Fields::add_field( 'Automation Entered', BWFCRM_Fields::$TYPE_TEXTAREA, array(), '', 2, 2, 1, 0, 2 );
		BWFCRM_Fields::add_field( 'Automation Active', BWFCRM_Fields::$TYPE_TEXTAREA, array(), '', 2, 2, 1, 0, 2 );
		BWFCRM_Fields::add_field( 'Automation Completed', BWFCRM_Fields::$TYPE_TEXTAREA, array(), '', 2, 2, 1, 0, 2 );

		$this->method_run[] = $version_key;

		/** Updating version key */
		update_option( 'bwfan_pro_db', $version_key, true );

	}

	public function db_update_2_0_8( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		$db_errors = [];

		$table_instance = new BWFAN_DB_Table_Bulk_Action();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Temporary done for beta users
	 *
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_2_0_9( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		global $wpdb;
		$events_slugs = [
			'elementor_form_submit',
			'elementor_popup_form_submit',
			'caldera_form_submit',
			'divi_form_Submit',
			'fluent_form_submit',
			'gf_form_submit',
			'formidable_form_Submit',
			'ninja_form_submit',
			'funnel_optin_form_submit',
			'tve_lead_form_submit',
			'wpforms_form_submit'
		];

		$automations = $this->get_automations_by_slugs( $events_slugs );
		if ( empty( $automations ) ) {
			$this->method_run[] = $version_key;
			update_option( 'bwfan_pro_db', $version_key, true );
		}

		$aids       = empty( $automations ) ? [] : array_column( $automations, 'ID' );
		$event_meta = BWFAN_Model_Automationmeta::get_automations_meta( $aids, 'event_meta' );
		foreach ( $event_meta as $aid => $data ) {
			if ( ! isset( $data['event_meta'] ) || isset( $data['event_meta']['bwfan-form-field-map'] ) ) {
				continue;
			}

			$map_fields = $this->format_form_submit_data( $data['event_meta'] );
			$table_name = "{$wpdb->prefix}bwfan_automationmeta";
			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table_name, [
				'meta_value' => maybe_serialize( $map_fields )
			], [
				'bwfan_automation_id' => intval( $aid ),
				'meta_key'            => 'event_meta'
			] );
		}

		$this->method_run[] = $version_key;

		/** Updating version key */
		update_option( 'bwfan_pro_db', $version_key, true );
	}

	/**
	 * Get v2 automations ids by event slug
	 *
	 * @param $event_slugs
	 *
	 * @return array|object|stdClass[]|null
	 */
	protected function get_automations_by_slugs( $event_slugs ) {
		global $wpdb;
		$args        = $event_slugs;
		$args[]      = 2;
		$placeholder = array_fill( 0, count( $event_slugs ), '%s' );
		$placeholder = implode( ', ', $placeholder );

		return $wpdb->get_results( $wpdb->prepare( "SELECT `ID` FROM {$wpdb->prefix}bwfan_automations WHERE `event` IN ($placeholder) AND `v` = %d", $args ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	protected function format_form_submit_data( $event_meta ) {
		if ( ! is_array( $event_meta ) || empty( $event_meta ) ) {
			return [];
		}
		$data = [];
		foreach ( $event_meta as $key => $value ) {
			if ( false === strpos( $key, 'email' ) && false === strpos( $key, 'first_name' ) && false === strpos( $key, 'last_name' ) && false === strpos( $key, 'phone' ) ) {
				continue;
			}

			switch ( $key ) {
				case( false !== strpos( $key, 'email' ) ) :
					$data['bwfan_email_field_map'] = $value;
					break;
				case( false !== strpos( $key, 'first_name' ) ) :
					$data['bwfan_first_name_field_map'] = $value;
					break;
				case( false !== strpos( $key, 'last_name' ) ) :
					$data['bwfan_last_name_field_map'] = $value;
					break;
				case( false !== strpos( $key, 'phone' ) ) :
					$data['bwfan_phone_field_map'] = $value;
					break;
			}

			unset( $event_meta[ $key ] );
		}
		$event_meta['bwfan-form-field-map'] = $data;

		return $event_meta;

	}

	/**
	 * Temporary done for beta users
	 *
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_2_0_11( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		global $wpdb;
		$table_name      = "{$wpdb->prefix}bwfan_automation_step";
		$query           = "SELECT `ID`,`data` FROM $table_name WHERE `type` = 4 AND `data` LIKE '%customer_custom_field%'"; // WPCS: unprepared SQL OK
		$condition_steps = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		/** If empty then don't proceed */
		if ( empty( $condition_steps ) ) {
			$this->method_run[] = $version_key;
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		foreach ( $condition_steps as $step ) {
			if ( ! isset( $step['data'] ) && empty( $step['data'] ) ) {
				continue;
			}

			$db_data = json_decode( $step['data'], true );
			foreach ( $db_data['sidebarData'] as $key => $data ) {
				if ( ! is_array( $data ) || 0 === count( $data ) ) {
					continue;
				}
				/** $key holds a group of fields with AND operator */
				$db_data['sidebarData'][ $key ] = $this->modify_custom_field_filters( $data );
			}

			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table_name, [
				'data'       => wp_json_encode( $db_data ),
				'updated_at' => current_time( 'mysql', 1 )
			], [
				'ID' => intval( $step['ID'] )
			] );
		}
		$this->method_run[] = $version_key;

		/** Updating version key */
		update_option( 'bwfan_pro_db', $version_key, true );
	}

	protected function modify_custom_field_filters( $sidebar_data ) {
		$sidebar_data = array_map( function ( $data ) {
			/** If not "customer_custom_field" filter */
			if ( ! isset( $data['filter'] ) || 'customer_custom_field' !== $data['filter'] ) {
				return $data;
			}

			$field_slug      = $data['data'][0];
			$contact_columns = [ 'f_name', 'l_name', 'email', 'contact_no', 'status', 'country', 'state' ];
			if ( in_array( $field_slug, $contact_columns, false ) ) {
				/** These are contact columns so filtered under contact details and geography group */
				return $this->modify_contact_table_filters( $data );
			}

			/** Below all are contact fields */

			$field = BWFAN_Model_Fields::get_field_by_slug( $field_slug );
			if ( ! isset( $field['ID'] ) ) {
				return $data;
			}

			/** Field type is date (6) */
			if ( 6 === absint( $field['type'] ) ) {
				$data['data'] = [
					[
						'key'   => $data['data'][1],
						'label' => $data['data'][1]
					]
				];

				$rule         = 'is' === $data['rule'] ? 'has' : '';
				$rule         = ( 'is_not' === $data['rule'] || 'isnot' === $data['rule'] ) ? 'hasnot' : $rule;
				$data['rule'] = empty( $rule ) ? 'any' : $rule;
			} else {
				$data['data'] = $data['data'][1];
			}

			$data['filter'] = 'bwf_contact_field_f' . $field['ID'];

			/** Check for Geography fields */
			if ( in_array( $field_slug, [ 'address-1', 'address-2', 'city', 'postcode' ] ) ) {
				switch ( $field_slug ) {
					case 'address-1':
						$data['filter'] = 'contact_address_1';
						break;
					case 'address-2':
						$data['filter'] = 'contact_address_2';
						break;
					case 'city':
						$data['filter'] = 'contact_city';
						break;
					case 'postcode':
						$data['filter'] = 'contact_post_code';
						break;
				}
			}

			/** If company, gender & dob */
			if ( in_array( $field_slug, [ 'company', 'gender', 'dob' ] ) ) {
				switch ( $field_slug ) {
					case 'company':
						$data['filter'] = 'contact_company';
						break;
					case 'gender':
						$data['filter'] = 'contact_gender';
						break;
					case 'dob':
						$data['filter'] = 'contact_dob';
						break;
				}
			}

			return $data;
		}, $sidebar_data );

		return $sidebar_data;
	}

	protected function modify_contact_table_filters( $data ) {
		$key   = $data['data'][0];
		$value = $data['data'][1];
		$rule  = $data['rule'];

		if ( 'f_name' === $key ) {
			return [
				'filter' => 'contact_first_name',
				'rule'   => $rule,
				'data'   => $value,
			];
		}
		if ( 'l_name' === $key ) {
			return [
				'filter' => 'contact_last_name',
				'rule'   => $rule,
				'data'   => $value,
			];
		}
		if ( 'email' === $key ) {
			return [
				'filter' => 'contact_email',
				'rule'   => $rule,
				'data'   => $value,
			];
		}
		if ( 'contact_no' === $key ) {
			return [
				'filter' => 'contact_phone',
				'rule'   => $rule,
				'data'   => $value,
			];
		}
		if ( 'state' === $key ) {
			return [
				'filter' => 'contact_state',
				'rule'   => $rule,
				'data'   => $value,
			];
		}
		if ( 'status' === $key ) {
			return [
				'filter' => 'customer_marketing_status',
				'rule'   => '',
				'data'   => $value,
			];
		}
		if ( 'country' === $key ) {
			$arr           = [
				'filter' => 'contact_country',
				'rule'   => ( 'is_not' === $rule ) ? 'none' : 'any',
				'data'   => [
					[
						'key'   => '',
						'label' => ''
					]
				],
			];
			$all_countries = bwf_get_countries_data();
			if ( isset( $all_countries[ $value ] ) || isset( $all_countries[ strtoupper( $value ) ] ) ) {
				$arr['data'][0]['key']   = strtoupper( $value );
				$arr['data'][0]['label'] = $all_countries[ strtoupper( $value ) ];
			}

			return $arr;
		}

		return $data;
	}

	/**
	 * Add sid column in engagement table if not available
	 *
	 * @param $version_key
	 *
	 * @throws Exception
	 */
	public function db_update_2_1_2( $version_key ) {
		if ( is_array( $this->method_run ) && ( in_array( '1.9.4', $this->method_run, true ) || in_array( '2.0.5', $this->method_run, true ) ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );
			$this->method_run[] = $version_key;

			return;
		}

		global $wpdb;

		/** Check `sid` column if exists */
		$query      = "SHOW COLUMNS FROM `{$wpdb->prefix}bwfan_engagement_tracking` LIKE 'sid'";
		$sid_exists = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $sid_exists ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		$db_errors = [];

		/** Add Step Id (sid) column */
		$wpdb->query( "ALTER TABLE `{$wpdb->prefix}bwfan_engagement_tracking` ADD `sid` bigint(20) unsigned NOT NULL default 0 COMMENT 'Step ID', ADD KEY `sid` (`sid`)" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		if ( ! empty( $wpdb->last_error ) ) {
			$db_errors[] = 'bwfan_engagement_tracking new column in 2.1.2- ' . $wpdb->last_error;
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Increase text input, drop down, and radio field's max length
	 *
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_2_4_0( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		global $wpdb;
		$query = "SELECT `ID` FROM {$wpdb->prefix}bwfan_fields WHERE `type` IN (1, 4, 5)";
		$ids   = $wpdb->get_col( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$db_errors = [];
		foreach ( $ids as $id ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}bwf_contact_fields` MODIFY COLUMN `f{$id}` varchar(99)" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			if ( ! empty( $wpdb->last_error ) ) {
				$db_errors[] = 'bwf_contact_fields change column f' . $id . ' length. Error: ' . $wpdb->last_error;
			}
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Update tags and list events settings
	 *
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_2_4_3( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		$event_slugs = array( 'crm_assigned_tag', 'crm_assigned_list', 'crm_unassigned_list', 'crm_unassigned_tag' );
		foreach ( $event_slugs as $event_slug ) {
			$automations = BWFAN_PRO_Common::get_all_automations_by_slug( $event_slug );
			if ( empty( $automations ) ) {
				continue;
			}

			$meta_key     = ( 'crm_assigned_tag' === $event_slug || 'crm_unassigned_tag' === $event_slug ) ? 'tags' : 'list';
			$contains_key = ( 'tags' === $meta_key ) ? 'tag-contains' : 'list-contains';
			$selected_key = ( 'tags' === $meta_key ) ? 'selected_tag' : 'selected_list';

			foreach ( $automations as $id => $value ) {
				if ( ! isset( $value['meta'] ) || empty( $value['meta'] ) ) {
					continue;
				}

				if ( ! isset( $value['meta']['event_meta'] ) || empty( $value['meta']['event_meta'] ) ) {
					continue;
				}

				$new_event_meta = $value['meta']['event_meta'];

				$new_event_meta[ $contains_key ] = $selected_key;
				if ( ! isset( $value['meta']['event_meta'][ $meta_key ] ) || empty( $value['meta']['event_meta'][ $meta_key ] ) ) {
					$new_event_meta[ $contains_key ] = 'any';
				}

				BWFAN_Model_Automationmeta::update( [
					'meta_value' => maybe_serialize( $new_event_meta )
				], [
					'bwfan_automation_id' => $id,
					'meta_key'            => 'event_meta'
				] );
			}
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	public function db_update_2_6_1( $version_key ) {
		if ( ! class_exists( 'BWFAN_Divi_Form_Submit' ) || ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		$divi_form_ins = BWFAN_Divi_Form_Submit::get_instance();
		$forms         = $divi_form_ins->get_view_data();
		if ( empty( $forms ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}
		try {
			$this->update_divi_forms_automation_data( $forms );
			$this->update_divi_form_feeds_data( $forms );
		} catch ( Error $e ) {
			BWFAN_PRO_Common::log_test_data( 'Divi form data update: ' . $e->getMessage(), 'divi-form-data-update' );
		}

		update_option( 'bwfan_pro_db', $version_key, true );
	}

	/**
	 * Update old form id with new form id for automations
	 *
	 * @param $forms
	 *
	 * @return void
	 */
	public function update_divi_forms_automation_data( $forms ) {
		global $wpdb;
		$table1  = $wpdb->prefix . 'bwfan_automationmeta';
		$table2  = $wpdb->prefix . 'bwfan_automations';
		$sql     = "SELECT $table1.meta_value , $table1.bwfan_automation_id   FROM $table1 JOIN $table2 ON $table1.bwfan_automation_id = $table2.ID
				WHERE $table2.event = 'divi_form_submit'
				AND $table1.meta_key = 'event_meta'";
		$results = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $results ) ) {
			return;
		}

		foreach ( $results as $result ) {
			$form_meta_values = maybe_unserialize( $result['meta_value'] );
			if ( ! isset( $form_meta_values['bwfan-divi_form_submit_form_id'] ) || empty( $form_meta_values['bwfan-divi_form_submit_form_id'] ) ) {
				continue;
			}
			$selected_form_id = $form_meta_values['bwfan-divi_form_submit_form_id'];
			if ( in_array( $selected_form_id, array_keys( $forms ), true ) ) {
				continue;
			}

			$matched_from_ids = array_filter( array_keys( $forms ), function ( $item ) use ( $selected_form_id ) {
				return ( false !== strpos( $item, $selected_form_id ) ) ? $item : false;
			} );

			if ( empty( $matched_from_ids ) ) {
				continue;
			}

			$form_meta_values['bwfan-divi_form_submit_form_id'] = end( $matched_from_ids );
			BWFAN_Model_Automationmeta::update( [
				'meta_value' => maybe_serialize( $form_meta_values )
			], [
				'bwfan_automation_id' => $result['bwfan_automation_id'],
				'meta_key'            => 'event_meta'
			] );
		}
	}

	/**
	 * Update old form id with new form id for form feeds
	 *
	 * @param $forms
	 *
	 * @return void
	 */
	public function update_divi_form_feeds_data( $forms ) {
		global $wpdb;
		$sql     = "SELECT `ID`, `data` FROM {$wpdb->prefix}bwfan_form_feeds  WHERE `source` = 'divi_form'";
		$results = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $results ) ) {
			return;
		}
		foreach ( $results as $result ) {
			$form_meta_values = json_decode( $result['data'], true );
			if ( ! isset( $form_meta_values['form_id'] ) || empty( $form_meta_values['form_id'] ) ) {
				continue;
			}
			$selected_form_id = $form_meta_values['form_id'];
			if ( in_array( $selected_form_id, array_keys( $forms ), true ) ) {
				continue;
			}

			$matched_from_ids = array_filter( array_keys( $forms ), function ( $item ) use ( $selected_form_id ) {
				return ( false !== strpos( $item, $selected_form_id ) ) ? $item : false;

			} );

			if ( empty( $matched_from_ids ) ) {
				continue;
			}

			$form_meta_values['form_id'] = end( $matched_from_ids );
			$data                        = array(
				'data' => wp_json_encode( $form_meta_values ),
			);
			$where                       = array(
				'ID'     => $result['ID'],
				'source' => 'divi_form',
			);
			$wpdb->update( $wpdb->prefix . 'bwfan_form_feeds', $data, $where ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}

	public function db_update_2_6_2( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		/** Save pro start date */
		$this->save_pro_start_date();

		update_option( 'bwfan_pro_db', $version_key, true );
	}

	/**
	 * Save pro start date
	 *
	 * @return void
	 */
	public function save_pro_start_date() {
		$pro_start_date = bwf_options_get( 'fka_psd' );
		if ( empty( $pro_start_date ) ) {
			bwf_options_update( 'fka_psd', date( 'Y-m-d' ) );
		}
	}

	/**
	 * Add api keys table
	 *
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_3_0_0( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		$db_errors = [];

		$table_instance = new BWFAN_DB_Table_API_Keys();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Add Stripe upsell offer table
	 *
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_3_2_2( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		$db_errors = [];

		$table_instance = new BWFAN_DB_Table_Stripe_Offer();
		$table_instance->create_table();
		if ( ! empty( $table_instance->db_errors ) ) {
			$db_errors[] = $table_instance->db_errors;
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );

		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'db-creation-errors' );
		}
	}

	/**
	 * Update enabled transactional mails data from bwf_options to wp_options
	 *
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_3_2_3( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		$enabled_mails = bwf_options_get( 'bwfan_enabled_transactional_mails', 'value', [] );
		if ( ! empty( $enabled_mails ) ) {
			update_option( 'bwfan_enabled_transactional_mails', $enabled_mails, true );
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );
	}

	/**
	 * Update download key
	 *
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_3_2_4( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );

			return;
		}

		if ( ! get_option( 'bwfan_unique_secret', false ) ) {
			/** Adding a new unique key for file download */
			update_option( 'bwfan_unique_secret', wp_generate_password( 32, false ), true );
		}

		$this->method_run[] = $version_key;

		update_option( 'bwfan_pro_db', $version_key, true );
	}

	/**
	 * @param $version_key
	 *
	 * @return void
	 */
	public function db_update_3_2_5( $version_key ) {
		if ( is_array( $this->method_run ) && in_array( '1.9.4', $this->method_run, true ) ) {
			update_option( 'bwfan_pro_db', $version_key, true );
			$this->method_run[] = $version_key;

			return;
		}

		if ( ! bwf_has_action_scheduled( 'bwfcrm_change_log_file_name' ) ) {
			bwf_schedule_recurring_action( time(), 60, 'bwfcrm_change_log_file_name', [], 'bwfcrm' );
		}

		$this->method_run[] = $version_key;

		/** Updating version key */
		update_option( 'bwfan_pro_db', $version_key, true );
	}
}

BWFAN_Pro_DB::get_instance();
