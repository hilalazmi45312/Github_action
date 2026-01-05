<?php

#[AllowDynamicProperties]
class BWFAN_PRO_Common {

	public static $crm_default_fields = [
		'address-1' => [
			'name'  => 'Address 1',
			'type'  => 1,
			'mode'  => 2,
			'vmode' => 1,
			'view'  => 2,
			'meta'  => [],
		],
		'address-2' => [
			'name'  => 'Address 2',
			'type'  => 1,
			'mode'  => 2,
			'vmode' => 1,
			'view'  => 2,
			'meta'  => []
		],
		'city'      => [
			'name'  => 'City',
			'type'  => 1,
			'mode'  => 2,
			'vmode' => 1,
			'view'  => 2,
			'meta'  => []
		],
		'postcode'  => [
			'name'  => 'Pincode',
			'type'  => 1,
			'mode'  => 2,
			'vmode' => 1,
			'view'  => 2,
			'meta'  => []
		],
		'company'   => [
			'name'  => 'Company',
			'type'  => 1,
			'mode'  => 2,
			'vmode' => 1,
			'view'  => 2,
			'meta'  => []
		],
		'gender'    => [
			'name'  => 'Gender',
			'type'  => 4,
			'mode'  => 2,
			'vmode' => 1,
			'view'  => 2,
			'meta'  => [ 'options' => [ 'Male', 'Female', 'Other' ] ]
		],
		'dob'       => [
			'name'  => 'Date of Birth',
			'type'  => 7,
			'mode'  => 2,
			'vmode' => 1,
			'view'  => 2,
			'meta'  => []
		]
	];

	public static $upstroke_funnels = null;
	public static $upstroke_offers = null;

	public static $show_tags_list_by_name = null;

	public static $unsubscribe_page_link = '';

	public static function init() {
		register_deactivation_hook( BWFAN_PRO_PLUGIN_FILE, array( __CLASS__, 'deactivation' ) );

		add_action( 'admin_init', array( __CLASS__, 'schedule_cron' ) );

		add_action( 'bwfan_run_midnight_cron', array( __CLASS__, 'run_midnight_cron' ) );
		add_action( 'bwfan_delete_engagement_meta_records', array( __CLASS__, 'delete_engagement_tracking_meta' ) );
		add_action( 'bwfan_automation_saved', array( __CLASS__, 'schedule_time_async_events' ) );
		add_action( 'bwfan_run_independent_automation', array( __CLASS__, 'make_independent_events_tasks' ) );

		add_filter( 'bwfan_select2_ajax_callable', array( __CLASS__, 'get_callable_object' ), 1, 2 );

		add_filter( 'bwfan_send_async_call_data', array( __CLASS__, 'passing_event_language' ), 10, 1 );

		/** Send test data to zapier zap */
		add_action( 'wp_ajax_bwf_test_zap', array( __CLASS__, 'test_zap' ) );
		/** send test data to pabbly */
		add_action( 'wp_ajax_bwf_test_pabbly', array( __CLASS__, 'test_pabbly' ) );

		/** Send test data to zapier zap */
		add_action( 'wp_ajax_bwf_send_test_http_post', array( __CLASS__, 'send_test_http_post' ) );

		/** Delete automation tasks after paid order */
		add_action( 'bwf_normalize_contact_meta_before_save', array( __CLASS__, 'delete_automation_tasks_by_automation_ids' ), PHP_INT_MAX, 2 );

		/** 5 min action callback */
		add_action( 'bwfan_5_minute_worker', array( __CLASS__, 'five_minute_worker_cb' ) );

		/** populate cart from order **/
		add_action( 'wp', array( __CLASS__, 'bwfan_populate_cart_from_order' ), 999 );

		add_filter( 'bwfan_get_form_submit_events', array( __CLASS__, 'get_form_submit_events' ), 10, 1 );

		add_filter( 'bwfan_automation_tables', array( __CLASS__, 'bwfan_added_pro_tables' ), 10, 1 );

		add_action( 'bwfan_check_conversion_validity', [ __CLASS__, 'bwfan_delete_invalid_conversions' ], 10 );

		/** Ajax call to save email template */
		add_action( 'wp_ajax_bwf_save_email_template', array( __CLASS__, 'bwf_save_email_template' ) );

		/** Display email content in browser */
		add_action( 'wp', [ __CLASS__, 'load_view_in_browser_link_content' ] );
	}

	/** Include Form Submit Events available in Pro
	 *
	 * @param $events
	 *
	 * @return string[]
	 */
	public static function get_form_submit_events( $events ) {
		$pro_form_events = array(
			'BWFAN_Elementor_Form_Submit',
			'BWFAN_Elementor_PopUp_Form_Submit',
			'BWFAN_Caldera_Form_Submit',
			'BWFAN_Fluent_Form_Submit',
			'BWFAN_GF_Form_Submit',
			'BWFAN_Ninja_Form_Submit',
			'BWFAN_Funnel_Optin_Form_Submit',
			'BWFAN_TVE_Lead_Form_Submit',
			'BWFAN_WPFORMS_Form_Submit'
		);

		return is_array( $events ) && ! empty( $events ) ? array_merge( $events, $pro_form_events ) : $pro_form_events;
	}

	public static function send_test_http_post() {
		BWFAN_PRO_Common::nocache_headers();
		BWFAN_Common::check_nonce(); // phpcs:disable WordPress.Security.NonceVerification

		$response = [];
		try {
			$post = $_POST;
			BWFAN_Merge_Tag_Loader::set_data( array(
				'is_preview' => true,
			) );
			$custom_fields = array();
			if ( isset( $post['v'] ) && 2 === absint( $post['v'] ) ) {
				$url = $post['url'];
				if ( isset( $post['custom_fields'] ) && is_array( $post['custom_fields'] ) ) {
					foreach ( $post['custom_fields'] as $field ) {
						$field_value                 = stripslashes( $field['field_value'] );
						$field_key                   = stripslashes( $field['field'] );
						$custom_fields[ $field_key ] = BWFAN_Common::decode_merge_tags( $field_value );
					}
				}

				$headers = array();
				if ( isset( $post['headers'] ) && is_array( $post['headers'] ) ) {
					foreach ( $post['headers'] as $field ) {
						$field_value           = stripslashes( $field['field_value'] );
						$field_key             = stripslashes( $field['field'] );
						$headers[ $field_key ] = BWFAN_Common::decode_merge_tags( $field_value );
					}
				}

				$method = isset( $post['http_method'] ) ? $post['http_method'] : 2;
			} else {

				$url          = $post['data']['url'];
				$fields       = array_map( 'stripslashes', $post['data']['custom_fields']['field'] );
				$fields_value = array_map( 'stripslashes', $post['data']['custom_fields']['field_value'] );
				foreach ( $fields as $key1 => $field_id ) {
					$custom_fields[ $field_id ] = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
				}

				$fields       = array_map( 'stripslashes', $post['data']['headers']['field'] );
				$fields_value = array_map( 'stripslashes', $post['data']['headers']['field_value'] );
				$headers      = array();
				foreach ( $fields as $key1 => $field_id ) {
					$headers[ $field_id ] = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
				}

				$method = isset( $post['data']['http_method'] ) ? $post['data']['http_method'] : 2;
			}


			$action_object             = BWFAN_Core()->integration->get_action( 'wp_http_post' );
			$action_object->is_preview = true;
			$action_object->set_data( array(
				'headers'       => $headers,
				'custom_fields' => $custom_fields,
				'url'           => $url,
				'method'        => $method,
			) );
			$response = $action_object->process();
		} catch ( Exception $e ) {
			wp_send_json( array(
				'status' => false,
				'msg'    => $e->getMessage(),
			) );
		}

		if ( empty( $response ) || 200 !== $response['response'] ) {
			$message = isset( $response['body'] ) && is_array( $response['body'] ) ? $response['body'][0] : __( 'Unable to send data', 'wp-marketing-automations-pro' );
			wp_send_json( array(
				'status' => false,
				'msg'    => $message,
			) );
		}

		wp_send_json( array(
			'status' => true,
			'msg'    => __( 'Test Data posted', 'wp-marketing-automations-pro' ),
		) );
	}

	/**
	 * Capture all the action ids of an automation and delete all its tasks except for completed tasks.
	 *
	 * @param $contact
	 * @param $order_id
	 *
	 * @return void
	 */
	public static function delete_automation_tasks_by_automation_ids( $contact, $order_id ) {
		/** Check if v1 automations active */
		if ( false === BWFAN_Common::is_automation_v1_active() ) {
			return;
		}

		if ( $contact instanceof WooFunnels_Contact && absint( $contact->get_id() ) > 0 ) {
			do_action( 'bwfan_sync_call_delete_tasks', $contact->get_email(), $contact->get_contact_no(), $order_id );

			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		do_action( 'bwfan_sync_call_delete_tasks', $order->get_billing_email(), $order->get_billing_phone(), $order_id );
	}

	/**
	 * Schedule a wp event which will run once a day.
	 * @throws Exception
	 */
	public static function schedule_cron() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$run_midnight_cron_scheduled            = '';
		$five_minute_worker_scheduled           = '';
		$run_midnight_connectors_sync_scheduled = '';

		if ( method_exists( 'BWFAN_Common', 'bwf_has_action_scheduled' ) ) {
			$run_midnight_cron_scheduled            = BWFAN_Common::bwf_has_action_scheduled( 'bwfan_run_midnight_cron' );
			$five_minute_worker_scheduled           = BWFAN_Common::bwf_has_action_scheduled( 'bwfan_5_minute_worker' );
			$run_midnight_connectors_sync_scheduled = BWFAN_Common::bwf_has_action_scheduled( 'bwfan_run_midnight_connectors_sync' );
		} else {
			$run_midnight_cron_scheduled            = bwf_has_action_scheduled( 'bwfan_run_midnight_cron' );
			$five_minute_worker_scheduled           = bwf_has_action_scheduled( 'bwfan_5_minute_worker' );
			$run_midnight_connectors_sync_scheduled = bwf_has_action_scheduled( 'bwfan_run_midnight_connectors_sync' );
		}

		$midnight_time = self::get_midnight_store_time();
		if ( ! $run_midnight_cron_scheduled ) {
			bwf_schedule_recurring_action( $midnight_time, DAY_IN_SECONDS, 'bwfan_run_midnight_cron' );
		}

		/** Schedule connectors sync call */
		if ( ! $run_midnight_connectors_sync_scheduled ) {
			bwf_schedule_recurring_action( $midnight_time, DAY_IN_SECONDS, 'bwfan_run_midnight_connectors_sync' );
		}

		/** Scheduling a 5 min worker */
		if ( ! $five_minute_worker_scheduled ) {
			bwf_schedule_recurring_action( time(), MINUTE_IN_SECONDS * 5, 'bwfan_5_minute_worker' );
		}
	}

	/**
	 * Remove autonami events on plugin deactivation.
	 */
	public static function deactivation() {
		bwf_unschedule_actions( 'bwfan_run_midnight_cron' );
	}

	/**
	 * Run once in a day and check all the active automations for events which are time independent.
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function run_midnight_cron() {
		$global_settings = BWFAN_Common::get_global_settings();

		$delete_time_in_days = intval( $global_settings['bwfan_delete_engagement_tracking_meta'] );
		if ( ! empty( $delete_time_in_days ) && ! bwf_has_action_scheduled( 'bwfan_delete_engagement_meta_records' ) ) {
			bwf_schedule_recurring_action( time(), MINUTE_IN_SECONDS * 5, 'bwfan_delete_engagement_meta_records' );
		}

		$automations = self::get_active_async_automations();
		if ( empty( $automations ) ) {
			return;
		}

		$automations = array_column( $automations, 'ID' );

		do_action( 'bwfan_midnight_cron', $automations );
		foreach ( $automations as $aid ) {
			self::schedule_time_async_events( $aid );
		}
	}

	/**
	 * Delete engagement tracking meta record from DB. This function run once in a day.
	 *
	 * @return void
	 */
	public static function delete_engagement_tracking_meta() {
		global $wpdb;

		$global_settings     = BWFAN_Common::get_global_settings();
		$delete_time_in_days = intval( $global_settings['bwfan_delete_engagement_tracking_meta'] );
		$delete_time_in_days = $delete_time_in_days > 0 ? $delete_time_in_days : 30;

		$current_datetime = new DateTime( 'now' );
		$current_datetime->modify( "-{$delete_time_in_days} days" );

		$cutoff_date = $current_datetime->format( 'Y-m-d' );
		$start_time  = time();

		$last_id_query = $wpdb->prepare( "SELECT MAX(ID) 
        FROM {$wpdb->prefix}bwfan_engagement_tracking 
        WHERE created_at IS NOT NULL
        AND created_at < %s", $cutoff_date );


		$last_id = $wpdb->get_var( $last_id_query );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL

		if ( empty( $last_id ) ) {
			bwf_unschedule_actions( 'bwfan_delete_engagement_meta_records' );

			return;
		}

		do {
			$delete_query = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bwfan_engagement_trackingmeta
	         WHERE eid <= %d
	         AND meta_key = %s
	         LIMIT 500", $last_id, 'merge_tags' );

			$rows_affected = $wpdb->query( $delete_query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL

			if ( empty( $rows_affected ) ) {
				bwf_unschedule_actions( 'bwfan_delete_engagement_meta_records' );
				break;
			}
		} while ( ( time() - $start_time ) < 20 );
	}

	/**
	 * Get all active async automation ids
	 *
	 * @return array|null
	 */
	public static function get_active_async_automations() {
		$ins        = BWFAN_Load_Sources::get_instance();
		$all_events = $ins->get_events();

		/** Fetch time independent only */
		$all_events = array_filter( $all_events, function ( $events ) {
			return $events->is_time_independent();
		} );
		if ( empty( $all_events ) ) {
			return [];
		}

		$all_events = array_map( function ( $events ) {
			return $events->get_slug();
		}, $all_events );
		$all_events = array_keys( $all_events );

		global $wpdb;

		$placeholder = array_fill( 0, count( $all_events ), '%s' );
		$placeholder = implode( ", ", $placeholder );
		$query       = $wpdb->prepare( "SELECT `ID` FROM `{$wpdb->prefix}bwfan_automations` WHERE `event` IN ($placeholder) AND `status` = 1;", $all_events );

		return BWFAN_Model_Automations::get_results( $query );
	}

	/**
	 * Schedule action if the automation is time independent.
	 *
	 * @param $automation_id
	 *
	 * @throws Exception
	 */
	public static function schedule_time_async_events( $automation_id ) {
		if ( class_exists( 'WooFunnels_Cache' ) ) {
			$WooFunnels_Cache_obj = WooFunnels_Cache::get_instance();
			$WooFunnels_Cache_obj->set_cache( 'bwfan_automations_meta_' . $automation_id, false, 'autonami' );
		}

		$automation = BWFAN_Model_Automations::get_automation_with_data( $automation_id );
		/** Check if automation deactivated */
		if ( 1 !== intval( $automation['status'] ) ) {
			if ( bwf_has_action_scheduled( 'bwfan_run_independent_automation', array( $automation_id ) ) ) {
				bwf_unschedule_actions( 'bwfan_run_independent_automation', array( $automation_id ) );
			}

			if ( $automation_id !== intval( $automation_id ) ) {
				if ( bwf_has_action_scheduled( 'bwfan_run_independent_automation', array( intval( $automation_id ) ) ) ) {
					bwf_unschedule_actions( 'bwfan_run_independent_automation', array( intval( $automation_id ) ) );
				}
			}

			return;
		}

		if ( ! isset( $automation['event'] ) || empty( $automation['event'] ) ) {
			return;
		}

		$event_instance = BWFAN_Core()->sources->get_event( $automation['event'] );
		if ( ! $event_instance instanceof BWFAN_Event ) {
			return;
		}
		$event_instance->load_hooks();

		/** v1 */
		$hours   = isset( $automation['meta']['event_meta']['hours'] ) ? $automation['meta']['event_meta']['hours'] : '';
		$minutes = isset( $automation['meta']['event_meta']['minutes'] ) ? $automation['meta']['event_meta']['minutes'] : '';

		if ( 2 === absint( $automation['v'] ) ) {
			$hours   = isset( $automation['meta']['event_meta']['scheduled-everyday-at']['hours'] ) ? $automation['meta']['event_meta']['scheduled-everyday-at']['hours'] : '';
			$minutes = isset( $automation['meta']['event_meta']['scheduled-everyday-at']['minutes'] ) ? $automation['meta']['event_meta']['scheduled-everyday-at']['minutes'] : '';
		}

		$hours   = empty( $hours ) ? 00 : $hours;
		$minutes = empty( $minutes ) ? 00 : $minutes;

		if ( bwf_has_action_scheduled( 'bwfan_run_independent_automation', array( $automation_id ) ) ) {
			bwf_unschedule_actions( 'bwfan_run_independent_automation', array( $automation_id ) );
		}

		if ( $automation_id !== intval( $automation_id ) ) {
			if ( bwf_has_action_scheduled( 'bwfan_run_independent_automation', array( intval( $automation_id ) ) ) ) {
				bwf_unschedule_actions( 'bwfan_run_independent_automation', array( intval( $automation_id ) ) );
			}
		}

		$automation_id = intval( $automation_id );

		bwf_schedule_single_action( BWFAN_Core()->automations->get_automation_execution_time( $hours, $minutes ), 'bwfan_run_independent_automation', array( $automation_id ) );
	}

	/**
	 * Make tasks for those events which are time independent.
	 * Like WC subscription before end or before renewal
	 *
	 * @param $automation_id
	 */
	public static function make_independent_events_tasks( $automation_id ) {
		$automation_details = BWFAN_Model_Automations::get_automation_with_data( $automation_id );

		/** Check if automation is active */
		if ( 1 !== intval( $automation_details['status'] ) ) {
			return;
		}

		$event_instance = BWFAN_Core()->sources->get_event( $automation_details['event'] );

		/** Validate if event exist */
		if ( ! $event_instance instanceof BWFAN_Event ) {
			return;
		}

		/** Halt if not time independent event */
		if ( false === $event_instance->is_time_independent() ) {
			return;
		}

		$event_instance->load_hooks();
		$last_run = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'last_run' );
		if ( empty( $last_run ) ) {
			$event_instance->make_task_data( $automation_id, $automation_details );

			return;
		}

		$date_time   = new DateTime();
		$current_day = $date_time->format( 'Y-m-d' );

		BWFAN_Common::log_test_data( 'async - last run: ' . $last_run . ' | current: ' . $current_day, 'async-checking', true );
		if ( $current_day !== $last_run ) {
			$event_instance->make_task_data( $automation_id, $automation_details );
		}
	}

	/**
	 * Get FunnelKit Upsell funnels
	 *
	 * @param $term
	 *
	 * @return array|null
	 */
	public static function get_upstroke_funnels( $term = '' ) {
		/** Return if already set */
		if ( ! is_null( self::$upstroke_funnels ) ) {
			return self::$upstroke_funnels;
		}

		$args = array(
			'post_type'      => WFOCU_Common::get_funnel_post_type_slug(),
			'post_status'    => array( 'publish' ),
			'posts_per_page' => - 1,
		);
		if ( ! empty( $term ) ) {
			$args['s'] = $term;
		}
		if ( ! empty( $term ) && is_numeric( $term ) ) {
			unset( $args['s'] );
			$args['post__in'] = array( $term );
		}

		$q     = new WP_Query( $args );
		$items = array();
		if ( $q->have_posts() ) {
			foreach ( $q->posts as $post ) {
				$items[] = array(
					'id'         => $post->ID,
					'post_title' => $post->post_title,
				);
			}
		}

		self::$upstroke_funnels = $items;

		return $items;
	}

	/**
	 * Return Funnelkit Upsell offers
	 *
	 * @param $term
	 *
	 * @return array|null
	 */
	public static function get_upstroke_offers( $term = '' ) {
		/** Return if already set */
		if ( ! is_null( self::$upstroke_offers ) ) {
			return self::$upstroke_offers;
		}

		$args = array(
			'post_type'      => WFOCU_Common::get_offer_post_type_slug(),
			'post_status'    => array( 'publish' ),
			'posts_per_page' => - 1,
		);
		if ( ! empty( $term ) ) {
			$args['s'] = $term;
		}
		if ( ! empty( $term ) && is_numeric( $term ) ) {
			unset( $args['s'] );
			$args['post__in'] = array( $term );
		}

		$q     = new WP_Query( $args );
		$items = array();
		if ( $q->have_posts() ) {
			foreach ( $q->posts as $post ) {
				$items[] = array(
					'id'         => $post->ID,
					'post_title' => $post->post_title,
				);
			}
		}

		self::$upstroke_offers = $items;

		return $items;
	}

	public static function get_upstroke_funnel_nice_name( $funnel_ids ) {
		$result = [];
		$args   = array(
			'post_type'   => WFOCU_Common::get_funnel_post_type_slug(),
			'numberposts' => - 1,
			'post__in'    => $funnel_ids,
		);

		$funnels = get_posts( $args );
		if ( false === is_array( $funnels ) || 0 === count( $funnels ) ) {
			return $result;
		}

		foreach ( $funnels as $single_funnel ) {
			$result[ $single_funnel->ID ] = $single_funnel->post_title . ' (#' . $single_funnel->ID . ')';
		}

		return $result;
	}

	public static function get_upstroke_offer_nice_name( $offer_ids ) {
		$result = [];
		$args   = array(
			'post_type'   => WFOCU_Common::get_offer_post_type_slug(),
			'numberposts' => - 1,
			'post__in'    => $offer_ids,
		);

		$offers = get_posts( $args );
		if ( false === is_array( $offers ) || 0 === count( $offers ) ) {
			return $result;
		}

		foreach ( $offers as $single_offer ) {
			$result[ $single_offer->ID ] = $single_offer->post_title;
		}

		return $result;
	}

	public static function get_all_active_affiliates() {
		global $wpdb;

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$affiliates = $wpdb->get_results( $wpdb->prepare( "
										SELECT affiliate_id
										FROM {$wpdb->prefix}affiliate_wp_affiliates
										WHERE status = %s
										", 'active' ), ARRAY_A );

		if ( count( $affiliates ) > 0 ) {
			return array_column( $affiliates, 'affiliate_id' );
		}

		return $affiliates;
	}


	public static function get_referrals_count_from_period( $affiliate_id, $from, $to ) {
		global $wpdb;

		$start_date = $from . ' 00:00:00';
		$end_date   = $to . ' 23:59:59';

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$referrals = $wpdb->get_var( $wpdb->prepare( "
			                                        SELECT COUNT(referral_id)
			                                        FROM {$wpdb->prefix}affiliate_wp_referrals
			                                        WHERE affiliate_id = %d
			                                        AND status = %s
			                                        AND date >= %s
			                                        AND date <= %s
			                                        ", $affiliate_id, 'unpaid', $start_date, $end_date ) );

		return ! empty( $referrals ) ? $referrals : 0;
	}

	public static function get_visits_from_period( $affiliate_id, $from, $to ) {
		global $wpdb;

		$start_date = $from . ' 00:00:00';
		$end_date   = $to . ' 23:59:59';

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$visits = $wpdb->get_var( $wpdb->prepare( "
			                                        SELECT COUNT(visit_id)
			                                        FROM {$wpdb->prefix}affiliate_wp_visits
			                                        WHERE affiliate_id = %d
			                                        AND date >= %s
			                                        AND date <= %s
			                                        ", $affiliate_id, $start_date, $end_date ) );

		return ! empty( $visits ) ? $visits : 0;
	}

	public static function get_commissions_from_period( $affiliate_id, $from, $to ) {
		global $wpdb;

		$start_date = $from . ' 00:00:00';
		$end_date   = $to . ' 23:59:59';

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$earnings = $wpdb->get_var( $wpdb->prepare( "
			                                        SELECT SUM(amount)
			                                        FROM {$wpdb->prefix}affiliate_wp_referrals
			                                        WHERE affiliate_id = %d
			                                        AND status = %s
			                                        AND date >= %s
			                                        AND date <= %s
			                                        ", $affiliate_id, 'unpaid', $start_date, $end_date ) );

		$total_earnings = 0;
		if ( ! empty( $earnings ) ) {
			$decimal        = apply_filters( 'bwfan_get_decimal_values', 2 );
			$total_earnings = round( $earnings, $decimal );
		}

		return $total_earnings;
	}

	public static function get_callable_object( $is_empty, $data ) {
		if ( 'sfwd-courses' === $data['type'] ) {
			return [ __CLASS__, 'get_learndash_courses' ];
		} elseif ( 'tag' === $data['type'] ) {
			return [ __CLASS__, 'get_tags' ];
		} elseif ( 'list' === $data['type'] ) {
			return [ __CLASS__, 'get_lists' ];
		} elseif ( 'link_trigger' === $data['type'] ) {
			return [ __CLASS__, 'get_link_triggers' ];
		} elseif ( 'quiz' === $data['type'] ) {
			return [ __CLASS__, 'get_quizzes' ];
		}

		return $is_empty;
	}

	public static function get_learndash_courses( $searched_term ) {
		$courses      = array();
		$results      = array();
		$query_params = array(
			'post_type'      => 'sfwd-courses',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		);

		if ( '' !== $searched_term ) {
			$query_params['s'] = $searched_term;
		}

		$query = new WP_Query( $query_params );

		if ( $query->found_posts > 0 ) {
			foreach ( $query->posts as $post ) {
				$results[] = array(
					'id'   => $post->ID,
					'text' => $post->post_title,
				);
			}
		}

		$courses['results'] = $results;

		return $courses;
	}

	public static function get_wc_membership_active_statuses() {
		if ( ! function_exists( 'wc_memberships' ) ) {
			return [];
		}

		$statuses = wc_memberships()->get_user_memberships_instance()->get_active_access_membership_statuses();

		return BWFAN_PRO_Common::get_wc_membership_statuses_with_prefix( $statuses );
	}

	public static function get_wc_membership_statuses_with_prefix( $statuses ) {
		if ( ! function_exists( 'wc_memberships' ) ) {
			return [];
		}

		$statuses_to_return = [];
		foreach ( (array) $statuses as $index => $status ) {

			if ( 'any' !== $status ) {
				$statuses_to_return[ $index ] = 'wcm-' . $status;
			}
		}

		return $statuses_to_return;
	}


	public static function test_zap() {
		BWFAN_PRO_Common::nocache_headers();
		BWFAN_Common::check_nonce();
		// phpcs:disable WordPress.Security.NonceVerification
		$result = array(
			'status' => false,
			'msg'    => __( 'Error', 'wp-marketing-automations-pro' ),
		);

		$post        = $_POST;
		$webhook_url = isset( $post['data'] ) || isset( $post['data']['url'] ) ? $post['data']['url'] : '';
		$url         = filter_input( INPUT_POST, 'url' );
		$webhook_url = empty( $url ) ? $webhook_url : $url;
		if ( empty( $webhook_url ) ) {
			$result['msg'] = __( 'Webhook URL cannot be blank', 'wp-marketing-automations-pro' );
			wp_send_json( $result );
		}

		update_option( 'bwfan_show_preview', $post );
		BWFAN_Merge_Tag_Loader::set_data( array(
			'is_preview' => true,
		) );

		$post['event_data']['event_slug'] = $post['event'];
		$custom_fields                    = array();
		/** only checking with v1 automations */
		if ( ! isset( $post['v'] ) || 2 !== absint( $post['v'] ) ) {
			if ( isset( $post['data']['custom_fields'] ) && isset( $post['data']['custom_fields']['field'] ) && isset( $post['data']['custom_fields']['field_value'] ) ) {
				reset( $post['data']['custom_fields']['field'] );
				$first_key = key( $post['data']['custom_fields']['field'] );
				if ( empty( $post['data']['custom_fields']['field'][ $first_key ] ) || empty( $post['data']['custom_fields']['field'][ $first_key ] ) ) {
					$result['msg'] = __( 'Atleast one key / value pair required', 'wp-marketing-automations-pro' );
					wp_send_json( $result );
				}
			} else {
				$result['msg'] = __( 'Atleast one key / value pair required', 'wp-marketing-automations-pro' );
				wp_send_json( $result );
			}
			$fields       = array_map( 'stripslashes', $post['data']['custom_fields']['field'] );
			$fields_value = array_map( 'stripslashes', $post['data']['custom_fields']['field_value'] );

			foreach ( $fields as $key1 => $field_id ) {
				$custom_fields[ $field_id ] = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
			}
		} else {
			$fields = $post['custom_fields'];
			foreach ( $fields as $field ) {
				$custom_fields[ $field['field'] ] = BWFAN_Common::decode_merge_tags( $field['field_value'] );
			}
		}
		$data_to_set                  = array();
		$data_to_set['custom_fields'] = $custom_fields;
		$data_to_set['url']           = BWFAN_Common::decode_merge_tags( $webhook_url );
		$data_to_set['test']          = true;
		$action_object                = BWFAN_Core()->integration->get_action( 'za_send_data' );
		$action_object->is_preview    = true;
		$action_object->set_data( $data_to_set );
		$response = $action_object->process();

		/** adding handling for error responses in test zap */
		if ( empty( $response ) || 200 !== $response['response'] ) {
			$message = isset( $response['body'] ) && is_array( $response['body'] ) ? $response['body'][0] : __( 'Unable to send data', 'wp-marketing-automations-pro' );
			wp_send_json( array(
				'status' => false,
				'msg'    => $message,
			) );
		}

		if ( false !== $response ) {
			$result['msg']    = __( 'Test Data sent to Zap.', 'wp-marketing-automations-pro' );
			$result['status'] = true;
		}


		//phpcs:enable WordPress.Security.NonceVerification
		wp_send_json( $result );
	}

	public static function test_pabbly() {
		BWFAN_PRO_Common::nocache_headers();
		BWFAN_Common::check_nonce();
		// phpcs:disable WordPress.Security.NonceVerification
		$result = array(
			'status' => false,
			'msg'    => __( 'Error', 'wp-marketing-automations-pro' ),
		);

		$post = $_POST;
		if ( empty( $post['data']['url'] ) ) {
			$result['msg'] = __( 'Webhook URL cannot be blank', 'wp-marketing-automations-pro' );
			wp_send_json( $result );

		}

		if ( isset( $post['data']['custom_fields'] ) && isset( $post['data']['custom_fields']['field'] ) && isset( $post['data']['custom_fields']['field_value'] ) ) {
			reset( $post['data']['custom_fields']['field'] );
			$first_key = key( $post['data']['custom_fields']['field'] );
			if ( empty( $post['data']['custom_fields']['field'][ $first_key ] ) || empty( $post['data']['custom_fields']['field'][ $first_key ] ) ) {
				$result['msg'] = __( 'Atleast one key / value pair required', 'wp-marketing-automations-pro' );
				wp_send_json( $result );
			}
		} else {
			$result['msg'] = __( 'Atleast one key / value pair required', 'wp-marketing-automations-pro' );
			wp_send_json( $result );
		}

		update_option( 'bwfan_show_preview', $post );
		BWFAN_Merge_Tag_Loader::set_data( array(
			'is_preview' => true,
		) );

		$post['event_data']['event_slug'] = $post['event'];
		$action_object                    = BWFAN_Core()->integration->get_action( 'pabbly_send_data' );
		$action_object->is_preview        = true;
		$data_to_set                      = array();

		$fields       = array_map( 'stripslashes', $post['data']['custom_fields']['field'] );
		$fields_value = array_map( 'stripslashes', $post['data']['custom_fields']['field_value'] );

		$custom_fields = array();

		foreach ( $fields as $key1 => $field_id ) {
			$custom_fields[ $field_id ] = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
		}
		$data_to_set['custom_fields'] = $custom_fields;
		$data_to_set['url']           = BWFAN_Common::decode_merge_tags( $post['data']['url'] );
		$data_to_set['test']          = true;

		$action_object->set_data( $data_to_set );
		$response = $action_object->process();

		if ( false !== $response ) {
			$result['msg']    = __( 'Test Data sent to Pabbly.', 'wp-marketing-automations-pro' );
			$result['status'] = true;
		}
		//phpcs:enable WordPress.Security.NonceVerification
		wp_send_json( $result );
	}

	public static function five_minute_worker_cb() {
		/** v1 automations */
		$active_automations = BWFAN_Core()->automations->get_active_automations( 1 );
		if ( is_array( $active_automations ) && 0 < count( $active_automations ) ) {
			do_action( 'bwfan_5_min_action', $active_automations );
		}

		/** v2 automations */
		$active_automations = BWFAN_Core()->automations->get_active_automations( 2 );
		if ( is_array( $active_automations ) && 0 < count( $active_automations ) ) {
			do_action( 'bwfan_5_min_action', $active_automations );
		}
	}

	/**
	 * Passing language as a param in sync call for every event
	 * Used in order status pending event - get_event_data() as well
	 *
	 * @param $data
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function passing_event_language( $data ) {
		/** checking for wpml plugin **/
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {

			$lang = ICL_LANGUAGE_CODE;

			/** get order language in case order id set in the data */
			if ( ! empty( $data['order_id'] ) ) {
				$order          = wc_get_order( $data['order_id'] );
				$order_language = $order instanceof WC_Order ? $order->get_meta( 'wpml_language' ) : '';
				if ( ! empty( $order_language ) ) {
					$lang = $order_language;
				}
			}

			$data['language'] = $lang;

			return $data;
		}

		/** checking for polylang plugin **/
		if ( function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post_language' ) ) {
			$lang = pll_current_language();

			/** get order language in case order id set in the data */
			if ( ! empty( $data['order_id'] ) ) {
				$order_language = pll_get_post_language( $data['order_id'] );
				if ( ! empty( $order_language ) ) {
					$lang = $order_language;
				}
			}

			$data['language'] = $lang;

			return $data;
		}

		/** checking for translate press **/
		if ( bwfan_is_translatepress_active() ) {
			$data['language'] = get_locale();

			return $data;
		}

		/** checking for weglot plugin */
		if ( defined( 'BWFAN_VERSION' ) && version_compare( BWFAN_VERSION, '2.0.2', '>' ) && bwfan_is_weglot_active() ) {
			$data['language'] = weglot_get_current_language();
			if ( ! isset( $data['order_id'] ) || empty( $data['order_id'] ) ) {
				return $data;
			}
			$order                  = wc_get_order( $data['order_id'] );
			$order_language         = $order instanceof WC_Order ? $order->get_meta( 'weglot_language' ) : '';
			$weglot_language_option = self::get_weglot_language_option();
			if ( ! array_key_exists( $order_language, $weglot_language_option ) ) {
				return $data;
			}

			$data['language'] = $order_language;

			return $data;
		}

		/** checking for gtranslate plugin */
		if ( function_exists( 'bwfan_is_gtranslate_active' ) && bwfan_is_gtranslate_active() ) {
			$data['language'] = BWFAN_Compatibility_With_GTRANSLATE::get_gtranslate_language();

			return $data;
		}

		return $data;
	}

	/**
	 * Weglot language options
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function get_weglot_language_option() {
		$data            = Context_Weglot::weglot_get_context()->get_service( 'Language_Service_Weglot' )->get_original_and_destination_languages();
		$weglot_language = array();
		foreach ( $data as $lang_key => $lang ) {
			if ( ! $lang instanceof Weglot\Client\Api\LanguageEntry ) {
				continue;
			}
			$weglot_language[ $lang->getInternalCode() ] = $lang->getLocalName();
		}

		return $weglot_language;
	}

	/**
	 * Populate cart from order id
	 *
	 * @throws Exception
	 */
	public static function bwfan_populate_cart_from_order() {
		if ( ! bwfan_is_woocommerce_active() ) {
			return;
		}

		global $woocommerce;

		if ( ! isset( $_GET['bwfan-order-again'] ) || empty( $_GET['bwfan-order-again'] ) ) {
			return;
		}

		/**
		 * Handling for older users
		 * @todo will remove numeric handling in 3.1
		 */
		$order_id = $_GET['bwfan-order-again'];

		/** Check for order */
		if ( ! is_numeric( $order_id ) ) {
			/** Order key */
			$order_id = wc_get_order_id_by_order_key( $order_id );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || ! $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed', 'processing' ) ) ) ) {
			return;
		}
		/** empty cart **/
		$woocommerce->cart->empty_cart();
		$order_items = $order->get_items();

		if ( ! is_array( $order_items ) || 0 === count( $order_items ) ) {
			return;
		}

		foreach ( $order_items as $item ) {

			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product_id     = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $item->get_product_id() );
			$quantity       = $item->get_quantity();
			$variation_id   = (int) $item->get_variation_id();
			$variations     = array();
			$cart_item_data = apply_filters( 'woocommerce_order_again_cart_item_data', array(), $item, $order );
			$product        = $item->get_product();

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			/** Prevent reordering variable products if no selected variation */
			if ( ! $variation_id && $product->is_type( 'variable' ) ) {
				continue;
			}

			/** Prevent reordering items specifically out of stock */
			if ( ! $product->is_in_stock() ) {
				continue;
			}

			foreach ( $item->get_meta_data() as $meta ) {
				if ( taxonomy_is_product_attribute( $meta->key ) ) {
					$term                     = get_term_by( 'slug', $meta->value, $meta->key );
					$variations[ $meta->key ] = $term ? $term->name : $meta->value;
				} elseif ( meta_is_product_attribute( $meta->key, $meta->value, $product_id ) ) {
					$variations[ $meta->key ] = $meta->value;
				}
			}

			if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations, $cart_item_data ) ) {
				continue;
			}

			/** Add to cart directly */
			WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations, $cart_item_data );
		}

		/** Clear show notices for added coupons or products */
		if ( ! is_null( WC()->session ) ) {
			WC()->session->set( 'wc_notices', array() );
		}
	}

	public static function get_bwf_customer_by_email( $email ) {
		$bwf_contact = self::get_bwf_contact_by_email( $email );
		if ( ! $bwf_contact instanceof WooFunnels_Contact ) {
			return false;
		}

		$customer = new WooFunnels_Customer( $bwf_contact );

		if ( ! isset( $customer->id ) || ! $customer->id > 0 ) {
			return false;
		}

		return $customer;
	}

	public static function get_bwf_contact_by_email( $email ) {
		$contact = self::get_contact_by_email( $email );
		if ( ! isset( $contact->wpid ) ) {
			return false;
		}

		$bwf_contact = bwf_get_contact( $contact->wpid, $email );
		if ( $bwf_contact instanceof WooFunnels_Customer ) {
			return $bwf_contact->contact;
		}

		if ( $bwf_contact instanceof WooFunnels_Contact ) {
			return $bwf_contact;
		}

		return false;
	}

	public static function get_contact_by_email( $email ) {
		if ( ! is_email( $email ) || ! class_exists( 'WooFunnels_DB_Operations' ) ) {
			return false;
		}

		$contact_db  = WooFunnels_DB_Operations::get_instance();
		$contact_obj = $contact_db->get_contact_by_email( $email );

		if ( ! isset( $contact_obj->id ) || ! $contact_obj->id > 0 ) {
			return false;
		}

		return $contact_obj;
	}

	public static function get_contact_meta( $contact_id, $key = '' ) {
		$contact_id = absint( $contact_id );
		if ( ! $contact_id > 0 ) {
			return false;
		}

		$contact_db   = WooFunnels_DB_Operations::get_instance();
		$contact_meta = $contact_db->get_contact_metadata( $contact_id );
		if ( ! is_array( $contact_meta ) || empty( $contact_meta ) ) {
			return false;
		}

		$contact_meta_array = array();
		foreach ( $contact_meta as $meta ) {
			$contact_meta_array[ $meta->meta_key ] = maybe_unserialize( $meta->meta_value );
		}

		if ( empty( $key ) ) {
			return $contact_meta_array;
		}

		if ( ! isset( $contact_meta_array[ $key ] ) ) {
			return false;
		}

		return $contact_meta_array[ $key ];
	}

	public static function insert_default_crm_fields() {
		global $wpdb;
		$crm_fields_table = $wpdb->prefix . 'bwfan_fields';
		$default_fields   = self::$crm_default_fields;
		$db_errors        = [];
		foreach ( $default_fields as $field => $field_data ) {
			$already_exist = self::check_field_exist( $field );
			if ( $already_exist ) {
				continue;
			}
			$data = [
				'name'       => $field_data['name'],
				'slug'       => $field,
				'type'       => $field_data['type'],
				'gid'        => 0,
				'meta'       => wp_json_encode( $field_data['meta'] ),
				'mode'       => $field_data['mode'],
				'vmode'      => $field_data['vmode'],
				'view'       => $field_data['view'],
				'search'     => 1,
				'created_at' => current_time( 'mysql', 1 ),
			];
			$wpdb->insert( $crm_fields_table, $data ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$field_id = $wpdb->insert_id;
			if ( ! empty( $wpdb->last_error ) ) {
				$db_errors[] = 'Error in default field creation: ' . $field_data['name'] . ' ' . $wpdb->last_error;
			}
			/** Check field column exist in contact_fields table **/
			$exists = BWF_Model_Contact_Fields::column_already_exists( $field_id );
			if ( empty( $exists ) ) {
				$column_added = BWF_Model_Contact_Fields::add_column_field( $field_id, 1 );
				/** If column not added */
				if ( true !== $column_added ) {
					BWFAN_Model_Fields::delete( $field_id );
					$db_errors[] = 'Error in contact field column creation: ' . $column_added;
				}
			}
		}
		/** Log if any mysql errors */
		if ( ! empty( $db_errors ) ) {
			BWFAN_PRO_Common::log_test_data( array_merge( [ __FUNCTION__ ], $db_errors ), 'field-creation-error' );
		}
	}

	/**
	 * Checking if field with slug exists or not
	 *
	 * @param $slug
	 *
	 * @return bool
	 */
	public static function check_field_exist( $slug ) {
		global $wpdb;
		$query  = $wpdb->prepare( "SELECT `ID` FROM `{$wpdb->prefix}bwfan_fields` WHERE `slug` = %s LIMIT 0, 1", $slug );
		$result = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return ! empty( $result );
	}

	/**
	 *
	 */
	public static function bwfan_added_pro_tables( $tables ) {
		global $wpdb;

		$tables[] = $wpdb->prefix . 'bwfan_engagement_tracking';
		$tables[] = $wpdb->prefix . 'bwfan_engagement_trackingmeta';
		$tables[] = $wpdb->prefix . 'bwfan_conversions';
		$tables[] = $wpdb->prefix . 'bwfan_templates';
		$tables[] = $wpdb->prefix . 'bwfan_terms';
		$tables[] = $wpdb->prefix . 'bwfan_fields';
		$tables[] = $wpdb->prefix . 'bwfan_field_groups';
		$tables[] = $wpdb->prefix . 'bwfan_contact_note';
		$tables[] = $wpdb->prefix . 'bwfan_import_export';
		$tables[] = $wpdb->prefix . 'bwfan_broadcast';
		$tables[] = $wpdb->prefix . 'bwf_contact_fields';
		$tables[] = $wpdb->prefix . 'bwfan_form_feeds';
		$tables[] = $wpdb->prefix . 'bwfan_message';

		return $tables;
	}

	/**
	 * Getting contact id from phone number
	 *
	 * @param $phone
	 *
	 * @return int
	 */
	public static function get_contact_id_by_phone( $phone ) {
		global $wpdb;
		$query = "SELECT `id` FROM `{$wpdb->prefix}bwf_contact` WHERE RIGHT(contact_no,10) = '" . $phone . "' ORDER BY `id` DESC LIMIT 0,1";
		$cid   = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return empty( $cid ) ? 0 : intval( $cid );
	}

	/**
	 * Get paid orders count
	 *
	 * @param $renewal_orders
	 *
	 * @return int
	 */
	public static function get_paid_orders_count( $renewal_orders ) {
		if ( method_exists( 'BWFAN_Common', 'get_paid_orders_count' ) ) {
			return BWFAN_Common::get_paid_orders_count( $renewal_orders );
		}

		if ( empty( $renewal_orders ) ) {
			return 0;
		}
		$paid_statuses = wc_get_is_paid_statuses();
		if ( empty( $paid_statuses ) ) {
			return 0;
		}
		$orders = array_filter( $renewal_orders, function ( $order_id ) use ( $paid_statuses ) {
			$order = wc_get_order( $order_id );

			return ( $order instanceof WC_ORDER && $order->has_status( $paid_statuses ) );
		} );

		return count( $orders );
	}

	public static function bwfan_delete_invalid_conversions() {
		if ( ! function_exists( 'wc_get_order' ) ) {
			delete_option( 'bwfan_db_1_3_3_options' );
			bwf_unschedule_actions( 'bwfan_check_conversion_validity' );

			return;
		}

		$last_conversion_id = get_option( 'bwfan_db_1_3_3_options' );
		if ( ! is_array( $last_conversion_id ) || empty( $last_conversion_id ) ) {
			delete_option( 'bwfan_db_1_3_3_options' );
			bwf_unschedule_actions( 'bwfan_check_conversion_validity' );

			return;
		}

		$last_conversion_id = absint( $last_conversion_id['last_conversion_id'] );
		$conversions        = BWFAN_Model_Conversions::get_conversions_for_check_validity( absint( $last_conversion_id ) );
		if ( empty( $conversions ) ) {
			delete_option( 'bwfan_db_1_3_3_options' );
			bwf_unschedule_actions( 'bwfan_check_conversion_validity' );

			return;
		}

		$start_time = time();
		foreach ( $conversions as $index => $conversion ) {
			$order = wc_get_order( $conversion['wcid'] );
			if ( ! $order instanceof WC_Order || ! in_array( $order->get_status(), wc_get_is_paid_statuses() ) || 'trash' === get_post_status( $order->get_id() ) ) {
				BWFAN_Model_Conversions::delete( $conversion['ID'] );
			}

			if ( ! isset( $conversions[ $index + 1 ] ) ) {
				delete_option( 'bwfan_db_1_3_3_options' );
				bwf_unschedule_actions( 'bwfan_check_conversion_validity' );
				break;
			}

			$next_conversion = $conversions[ $index + 1 ];
			update_option( 'bwfan_db_1_3_3_options', [ 'last_conversion_id' => $next_conversion['ID'] ], false );
			if ( ( time() - $start_time ) >= 30 || BWFCRM_Common::memory_exceeded() ) {
				break;
			}
		}
	}

	public static function remove_actions( $hook, $cls, $function = '' ) {
		global $wp_filter;
		$object = null;
		if ( class_exists( $cls ) && isset( $wp_filter[ $hook ] ) && ( $wp_filter[ $hook ] instanceof WP_Hook ) ) {
			$hooks = $wp_filter[ $hook ]->callbacks;
			foreach ( $hooks as $priority => $reference ) {
				if ( is_array( $reference ) && count( $reference ) > 0 ) {
					foreach ( $reference as $index => $calls ) {
						if ( isset( $calls['function'] ) && is_array( $calls['function'] ) && count( $calls['function'] ) > 0 ) {
							if ( is_object( $calls['function'][0] ) ) {
								$cls_name = get_class( $calls['function'][0] );
								if ( $cls_name == $cls && $calls['function'][1] == $function ) {
									$object = $calls['function'][0];
									unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $index ] );
								}
							} elseif ( $index == $cls . '::' . $function ) {
								// For Static Classess
								$object = $cls;
								unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $cls . '::' . $function ] );
							}
						}
					}
				}
			}
		} elseif ( function_exists( $cls ) && isset( $wp_filter[ $hook ] ) && ( $wp_filter[ $hook ] instanceof WP_Hook ) ) {

			$hooks = $wp_filter[ $hook ]->callbacks;
			foreach ( $hooks as $priority => $reference ) {
				if ( is_array( $reference ) && count( $reference ) > 0 ) {
					foreach ( $reference as $index => $calls ) {
						$remove = false;
						if ( $index == $cls ) {
							$remove = true;
						} elseif ( isset( $calls['function'] ) && $cls == $calls['function'] ) {
							$remove = true;
						}
						if ( true == $remove ) {
							unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $cls ] );
						}
					}
				}
			}
		}

		return $object;

	}

	/**
	 * Get the complete phone number of a contact with a country code
	 *
	 * @param $contact_obj WooFunnels_Contact
	 *
	 * @return mixed|string|void
	 */
	public static function get_contact_full_number( $contact_obj ) {
		if ( ! $contact_obj instanceof WooFunnels_Contact || 0 === absint( $contact_obj->get_id() ) ) {
			return '';
		}
		$phone = $contact_obj->get_contact_no();
		if ( empty( $phone ) ) {
			return '';
		}
		$country = $contact_obj->get_country();
		if ( ! empty( $country ) ) {
			$phone = BWFAN_Phone_Numbers::add_country_code( $phone, $country );
		}

		return $phone;
	}

	public static function get_tags( $searched_term ) {
		if ( ! class_exists( 'BWFCRM_Tag' ) ) {
			return [];
		}

		$tag_data = BWFCRM_Tag::get_tags( [], $searched_term );

		return self::prepare_data( $tag_data, 'tag' );
	}

	public static function prepare_data( $data, $type ) {
		$results   = [];
		$results[] = [ 'id' => 'any', 'text' => 'Any ' . $type ];

		foreach ( $data as $tag ) {
			$results[] = array(
				'id'   => $tag['ID'],
				'text' => $tag['name'],
			);
		}

		return $results;
	}

	public static function get_lists( $searched_term ) {
		if ( ! class_exists( 'BWFCRM_Lists' ) ) {
			return [];
		}

		$tag_data = BWFCRM_Lists::get_lists( [], $searched_term );

		return self::prepare_data( $tag_data, 'list' );
	}

	public static function get_contact_data_counts( $exclude_unsubs = false ) {
		$bulk_actions_count        = BWFAN_Model_Bulk_Action::get_bulk_actions_total_count( false );
		$bulk_actions_all_count    = isset( $bulk_actions_count['all'] ) ? $bulk_actions_count['all'] : 0;
		$bulk_actions_active_count = isset( $bulk_actions_count['1'] ) ? $bulk_actions_count['1'] : 0;

		return [
			'contacts_contacts'            => 0,
			'contacts_manage_audiences'    => 0,
			'contacts_manage_fields'       => 0,
			'contacts_manage_lists'        => 0,
			'contacts_manage_tags'         => 0,
			'contacts_bulk_actions'        => $bulk_actions_all_count,
			'contacts_active_bulk_actions' => $bulk_actions_active_count,
		];
	}

	public static function get_broadcast_data_count( $type = '' ) {
		if ( empty( $type ) ) {
			return [];
		}
		$status_count = BWFAN_Model_Broadcast::get_broadcast_total_count_by_status( $type );

		return [
			'broadcasts_email'    => 0,
			'broadcasts_sms'      => 0,
			'broadcasts_whatsapp' => 0,
			'status_scheduled'    => $status_count['2'],
			'status_ongoing'      => $status_count['3'],
			'status_completed'    => $status_count['4'],
			'status_cancelled'    => $status_count['7'],
			'status_paused'       => intval( $status_count['5'] ) + intval( $status_count['6'] ),
			'status_all'          => isset( $status_count['status_all'] ) ? $status_count['status_all'] : 0,
		];
	}

	/**
	 * Get form count by status
	 *
	 * @return array
	 */
	public static function get_forms_data_count() {
		$count = BWFAN_Model_Form_Feeds::get_forms_total_count();

		return [
			'forms_all'      => $count['all'],
			'forms_draft'    => $count['1'],
			'forms_active'   => $count['2'],
			'forms_inactive' => $count['3'],
		];
	}

	/**
	 * Return link trigger count array with status
	 *
	 * @return array
	 */
	public static function get_link_triggers_data_count() {
		$count = BWFAN_Model_Link_Triggers::get_link_triggers_total_count();

		return [
			'link-triggers_all'      => $count['all'],
			'link-triggers_draft'    => $count['0'],
			'link-triggers_inactive' => $count['1'],
			'link-triggers_active'   => $count['2'],
		];
	}

	/**
	 * Return bulk action count array with status
	 *
	 * @return array
	 */
	public static function get_bulk_actions_data_count() {
		$count = BWFAN_Model_Bulk_Action::get_bulk_actions_total_count();

		return [
			'all'       => $count['all'],
			'draft'     => $count['0'],
			'ongoing'   => $count['1'],
			'completed' => $count['2'],
			'paused'    => $count['3'],
		];
	}

	/**
	 * Returns country iso code
	 *
	 * @param $country
	 *
	 * @return string
	 */
	public static function get_country_iso_code( $country ) {
		$country = strtolower( $country );

		$countries     = self::get_countries_data();
		$countries_iso = array_keys( $countries );
		$countries_iso = array_map( function ( $j ) {
			return strtolower( $j );
		}, $countries_iso );

		if ( in_array( $country, $countries_iso, true ) ) {
			/** Country found */
			return strtoupper( $country );
		}

		/** Country array with 2 digit key and lower names as value */
		$countries_lower = array_map( function ( $j ) {
			return strtolower( $j );
		}, $countries );

		if ( in_array( $country, $countries_lower, true ) ) {
			/** Country found */
			$index = array_search( $country, $countries_lower );

			return strtoupper( $index );
		}

		return '';
	}

	/** countries data
	 * @return mixed|null
	 */
	public static function get_countries_data() {
		$countries_json = file_get_contents( BWFAN_PRO_PLUGIN_DIR . '/includes/countries.json' );
		$countries      = json_decode( $countries_json, true );

		return $countries;
	}

	public static function get_country_name( $country_code ) {
		$country_code = strtoupper( $country_code );
		$countries    = self::get_countries_data();

		if ( isset( $countries[ $country_code ] ) ) {
			return $countries[ $country_code ];
		}

		return $country_code;
	}

	/**
	 * @param $global_data
	 *
	 * @return int
	 */
	public static function maybe_get_user_id_from_task_meta( $task_meta ) {
		/** Get User ID */
		if ( isset( $task_meta['user_id'] ) && ! empty( $task_meta['user_id'] ) ) {
			return $task_meta['user_id'];
		}

		/** Get User ID by Email */
		if ( isset( $task_meta['email'] ) && is_email( $task_meta['email'] ) ) {
			$email = $task_meta['email'];

			/** Check if User exists by this email */
			$user_data = get_user_by( 'email', $email );
			if ( $user_data instanceof WP_User ) {
				return $user_data->ID;
			}

			/** Create user if not exists, and email exists and valid */
			$user_id = wp_create_user( $email, wp_generate_password(), $email );
			if ( ! is_wp_error( $user_id ) ) {
				return $user_id;
			}
		}

		/** get user id from the contact if wpid available */
		if ( isset( $task_meta['contact_id'] ) && ! empty( $task_meta['contact_id'] ) ) {
			$contact = new BWFCRM_Contact( $task_meta['contact_id'] );
			if ( ! $contact->is_contact_exists() ) {
				return false;
			}

			$user_id = $contact->contact->get_wpid();
			if ( ! empty( $user_id ) ) {
				return $user_id;
			}

			/*** if still user id not associate with contact then get using the contact email */
			$contact_email = $contact->contact->get_email();
			$user_data     = get_user_by( 'email', $contact_email );
			if ( $user_data instanceof WP_User && ! empty( $user_data->ID ) ) {
				return $user_data->ID;
			}

			/** if still user not exists with the contact email then create the user and bind with contact */
			$password = wp_generate_password();
			$user_id  = wp_create_user( $contact_email, $password, $contact_email );

			/** if error in user_id then return blank */
			if ( is_wp_error( $user_id ) ) {
				return false;
			}

			/** create display name using first name and appending lastname to it if available*/
			$display_name = trim( $contact->contact->get_f_name() );
			$display_name = empty( $display_name ) ? trim( $contact->contact->get_l_name() ) : $display_name . ' ' . trim( $contact->contact->get_l_name() );;
			if ( ! empty( trim( $display_name ) ) ) {
				wp_update_user( array( 'ID' => $user_id, 'display_name' => trim( $display_name ) ) );
			}

			$user = new WP_User( $user_id );
			$user->set_role( 'subscriber' );

			$user_first_name = trim( $contact->contact->get_f_name() );
			$user_last_name  = trim( $contact->contact->get_l_name() );
			$nickname        = trim( $user_first_name );
			! empty( $user_first_name ) && update_user_meta( $user_id, 'first_name', $user_first_name );
			! empty( $user_last_name ) && update_user_meta( $user_id, 'last_name', $user_last_name );
			! empty( $nickname ) && update_user_meta( $user_id, 'nickname', $nickname );

			$contact->contact->set_wpid( $user_id );
			$contact->contact->save();
			$contact->contact->update_meta( 'bwfan_userpassword', $password );

			return $user_id;
		}

		return false;
	}

	/** return links triggers on searching in automations */
	public static function get_link_triggers( $searched_term ) {
		if ( ! class_exists( 'BWFAN_Model_Link_Triggers' ) ) {
			return [];
		}

		$link_data = BWFAN_Model_Link_Triggers::get_link_triggers( $searched_term, '', 10, 0 );

		if ( ! isset( $link_data['links'] ) || empty( $link_data['links'] ) ) {
			return [];
		}

		$link = array();

		foreach ( $link_data['links'] as $link_index => $links ) {
			$link[ $link_index ]['id']   = $links['ID'];
			$link[ $link_index ]['text'] = $links['title'];
		}

		return $link;
	}

	/**
	 * Add UTM parameters in test email or sms for broadcast
	 *
	 * @param $body
	 * @param $args
	 *
	 * @return array|mixed|string|string[]|null
	 */
	public static function add_test_broadcast_utm_params( $body, $args, $mode = 'email' ) {
		$utm_data = array();

		/** Normalise Data */
		if ( isset( $args['utmContent'] ) ) {
			$utm_data['utm_content'] = $args['utmContent'];
		}

		if ( isset( $args['utmMedium'] ) ) {
			$utm_data['utm_medium'] = $args['utmMedium'];
		}

		if ( isset( $args['utmName'] ) ) {
			$utm_data['utm_name'] = $args['utmName'];
		}

		if ( isset( $args['utmSource'] ) ) {
			$utm_data['utm_source'] = $args['utmSource'];
		}

		if ( isset( $args['utmTerm'] ) ) {
			$utm_data['utm_term'] = $args['utmTerm'];
		}

		if ( empty( $utm_data ) ) {
			return $body;
		}

		/** Add flag to append UTM Params */
		$utm_data['append_utm'] = 1;

		$ins = BWFAN_UTM_Tracking::get_instance();

		return $ins->maybe_add_utm_parameters( $body, $utm_data, $mode );
	}


	/**
	 * Get customer past purchased product by email and time
	 *
	 * @param $current_time
	 * @param $email
	 *
	 * @return array
	 */
	public static function get_customer_past_purchased_product( $current_time, $email ) {
		global $wpdb;

		$email = trim( $email );
		if ( empty( $email ) ) {
			return [];
		}

		$current_time = empty( $current_time ) ? current_time( 'mysql' ) : $current_time;

		$query  = $wpdb->prepare( "SELECT p.product_id FROM `{$wpdb->prefix}wc_order_product_lookup` AS `p` INNER JOIN `{$wpdb->prefix}wc_customer_lookup` AS `c` ON c.customer_id = p.customer_id  WHERE p.`date_created` < %s AND c.email = %s GROUP BY p.product_id", $current_time, $email );
		$result = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $result ) ) {
			return [];
		}

		return array_map( function ( $row ) {
			return $row['product_id'];
		}, $result );
	}

	/**
	 * Return regex pattern
	 *
	 * @param int $type
	 *
	 * @return string
	 */
	public static function get_regex_pattern( $type = 1 ) {
		$type = (int) $type;

		switch ( $type ) {
			case 1:
				/**
				 * [0] => href='https://google.com/?abcd=ss&utm_content=source'
				 * [1] => https://google.com/?abcd=ss&utm_content=source
				 */ return '/href=["\']?([^"\'>]+)["\']?/';
			case 2:
				/**
				 * [0] => <a href='https://...' class='any'>
				 */ return '/<a[^>]+href=[^>]+>/i';
			case 3:
				/**
				 * [0] => https://...
				 */ return '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#';
			default:
				return '';
		}
	}

	/**
	 * v2 Method: Prepared options for field schema
	 *
	 * @param $options
	 *
	 * @return array
	 */
	public static function prepared_field_options( $options ) {
		if ( ! is_array( $options ) || empty( $options ) ) {
			return [];
		}
		$prepared_options = array_map( function ( $label, $value ) {
			return array(
				'label' => $label,
				'value' => $value,
			);
		}, $options, array_keys( $options ) );

		return $prepared_options;
	}

	public static function encrypt_32_bit_string( $str ) {
		if ( empty( $str ) ) {
			return '';
		}

		$r = strrev( $str );
		$n = [];
		for ( $i = 0; $i < strlen( $r ); $i ++ ) {
			$n[] = wp_rand( '0', '9' ) . $r[ $i ];
		}

		return implode( '', $n );
	}

	/**
	 * For AffiliateWP
	 *
	 * @param $user_id
	 *
	 * @return string|null
	 */
	public static function get_aff_id_by_user_id( $user_id ) {
		if ( 0 === absint( $user_id ) ) {
			return 0;
		}

		global $wpdb;

		$sql = $wpdb->prepare( "SELECT `affiliate_id` FROM {$wpdb->prefix}affiliate_wp_affiliates WHERE `user_id` = %d", $user_id );

		return $wpdb->get_var( $sql ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Search string from given array
	 *
	 * @param $data
	 * @param $search
	 *
	 * @return array
	 */
	public static function search_srting_from_data( $data, $search ) {

		if ( empty( $data ) || ! is_array( $data ) ) {
			return [];
		}

		if ( empty( $search ) ) {
			return $data;
		}

		return array_filter( $data, function ( $post ) use ( $search ) {
			return false !== strpos( strtolower( $post ), strtolower( $search ) );
		} );
	}

	/**
	 * Get or create Tag/List if not exists
	 *
	 * @param $terms
	 * @param $type
	 *
	 * @return array
	 */
	public static function get_or_create_terms( $terms, $type ) {

		$terms = array_map( function ( $term ) {
			if ( isset( $term['name'] ) ) {
				$term['value'] = $term['name'];
			}

			return $term;
		}, $terms );

		$terms = BWFCRM_Term::get_or_create_terms( $terms, $type, true );

		/**Prepared Data */
		$terms = array_map( function ( $term ) {
			$term_data         = [];
			$term_data['id']   = $term->get_id();
			$term_data['name'] = $term->get_name();

			return $term_data;
		}, $terms );

		return $terms;
	}

	/**
	 * Get broadcasts id and name
	 *
	 * @param $term
	 *
	 * @return array
	 */
	public static function get_broadcasts_by_term( $term ) {
		$broadcasts = BWFAN_Model_Broadcast::get_broadcasts_titles( $term );
		if ( empty( $broadcasts ) ) {
			return [];
		}

		$arr = array();
		foreach ( $broadcasts as $campaigns ) {
			$arr[ $campaigns['id'] ] = $campaigns['title'];
		}

		return $arr;
	}

	public static function get_contacts_from_broadcast( $bid, $column = '' ) {
		global $wpdb;
		$sql   = "SELECT DISTINCT c.id FROM {$wpdb->prefix}bwf_contact as c LEFT JOIN {$wpdb->prefix}bwf_wc_customers as wc ON c.id = wc.cid  LEFT JOIN {$wpdb->prefix}bwfan_engagement_tracking as et ON c.id=et.cid";
		$where = " WHERE ( c.email != '' AND c.email IS NOT NULL )";
		if ( empty( $column ) ) {
			$where .= " AND ( et.c_status=2 AND et.type = 2  AND et.oid = '$bid')";
		} else {
			$where .= " AND ( et.$column>0 AND et.c_status=2 AND et.type = 2  AND et.oid = '$bid')";
		}
		$sql = $sql . $where;

		return $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function get_engaged_or_unengaged_data_count( $where_query, $contact_id ) {
		global $wpdb;
		$sql   = "SELECT DISTINCT COUNT(c.id) FROM {$wpdb->prefix}bwf_contact as c LEFT JOIN {$wpdb->prefix}bwf_wc_customers as wc ON c.id = wc.cid LEFT JOIN {$wpdb->prefix}bwf_contact_fields as cm ON c.id=cm.cid";
		$where = " WHERE ( c.email != '' AND c.email IS NOT NULL )";
		$where .= $where_query;
		$where .= " AND c.id='$contact_id'";
		$sql   = $sql . $where;

		return $wpdb->get_var( $sql ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function get_skip_conversion_automations() {
		global $wpdb;
		$skip_events = apply_filters( 'bwfan_skip_conversion_automations', [ 'wcs_before_renewal' ] );
		/** Create string placeholder */
		$placeholder = array_fill( 0, count( $skip_events ), '%s' );
		$placeholder = implode( ", ", $placeholder );

		$query = $wpdb->prepare( "SELECT `ID` FROM {$wpdb->prefix}bwfan_automations WHERE 1 = 1 AND `event` IN ($placeholder) ", $skip_events );

		$query_res = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $query_res ) ) {
			return [];
		}

		return array_column( $query_res, 'ID' );
	}


	/**
	 * Get quiz data on search for learndash event
	 *
	 * @param $searched_term
	 *
	 * @return mixed
	 */
	public static function get_quizzes( $searched_term ) {
		$results      = array();
		$query_params = array(
			'post_type'   => 'sfwd-quiz',
			'post_status' => 'publish',
		);

		if ( '' !== $searched_term ) {
			$query_params['s'] = $searched_term;
		}

		$query = new WP_Query( $query_params );
		if ( $query->found_posts > 0 ) {
			foreach ( $query->posts as $post ) {
				$results[] = array(
					'id'   => $post->ID,
					'text' => $post->post_title,
				);
			}
		}

		return $results;
	}

	/**
	 * Excluding the automation which have failed to match the event settings as we have bulk actions as well.
	 *
	 * @param $event_slug
	 * @param $automation_id
	 * @param $v
	 *
	 * @return void
	 */
	public static function exclude_invalid_automation( $event_slug, $automation_id, $v ) {
		// return if any of following data missing
		if ( empty( $v ) || empty( $event_slug ) || empty( $automation_id ) ) {
			return;
		}

		// unset automation from v1 automations
		if ( 'v1' === $v ) {
			if ( isset( BWFAN_Core()->public->active_automations[ $event_slug ] ) && isset( BWFAN_Core()->public->active_automations[ $event_slug ][ $event_slug ][ $automation_id ] ) ) {
				unset( BWFAN_Core()->public->active_automations[ $event_slug ][ $event_slug ][ $automation_id ] );
			}
		}

		// unset automation from v2 automations
		if ( 'v2' === $v ) {
			if ( isset( BWFAN_Core()->public->active_v2_automations[ $event_slug ][ $event_slug ][ $automation_id ] ) ) {
				unset( BWFAN_Core()->public->active_v2_automations[ $event_slug ][ $event_slug ][ $automation_id ] );
			}
		}
	}

	/**
	 * Create or update contact on form submit event
	 *
	 * @param $automation_data
	 */
	public static function maybe_create_update_contact( $automation_data ) {
		if ( empty( $automation_data ) ) {
			return;
		}

		$email = $automation_data['email'];

		if ( ! is_email( trim( $email ) ) ) {
			return;
		}

		$contact = new WooFunnels_Contact( '', $email, '', '', '' );
		$args    = [];
		if ( isset( $automation_data['first_name'] ) && ! empty( $automation_data['first_name'] ) ) {
			$args['f_name'] = $automation_data['first_name'];
		}

		if ( isset( $automation_data['last_name'] ) && ! empty( $automation_data['last_name'] ) ) {
			$args['l_name'] = $automation_data['last_name'];
		}

		if ( isset( $automation_data['contact_phone'] ) && ! empty( $automation_data['contact_phone'] ) ) {
			$args['contact_no'] = $automation_data['contact_phone'];
		}

		if ( isset( $automation_data['mark_contact_subscribed'] ) && 1 === absint( $automation_data['mark_contact_subscribed'] ) ) {
			$args['status'] = 1;
		}

		/** checking if contact not exists than create otherwise update */
		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$bwfcrm_contact = new BWFCRM_Contact( $email, true, $args );
		} else { // update first_name, last_name, phone and status
			$bwfcrm_contact = new BWFCRM_Contact( $contact, false, $args );
			$bwfcrm_contact->update( $args );
			if ( isset( $automation_data['mark_contact_subscribed'] ) && 1 === absint( $automation_data['mark_contact_subscribed'] ) ) {
				$bwfcrm_contact->resubscribe();
			}
		}
	}

	public static function get_emails_by_subject_in_data( $subject, $v ) {
		global $wpdb;
		$sql          = "SELECT `auto_step`.*,`auto_m`.`title` FROM {$wpdb->prefix}bwfan_automation_step AS `auto_step` JOIN {$wpdb->prefix}bwfan_automations as `auto_m` ON `auto_step`.`aid`=`auto_m`.`ID` WHERE `auto_step`.`action` LIKE '%s' && `auto_step`.`data` LIKE '%s' && `auto_step`.`status`=1 && `auto_m`.`v`='%d'";
		$prepared_sql = $wpdb->prepare( $sql, '%wp_sendemail%', '%' . $subject . '%', $v );
		$emails       = $wpdb->get_results( $prepared_sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$final_array = [];
		foreach ( $emails as $email ) {
			$return          = array();
			$data            = json_decode( $email['data'], true );
			$db_subject      = isset( $data['sidebarData']['bwfan_email_data'] ) && isset( $data['sidebarData']['bwfan_email_data']['subject'] ) ? $data['sidebarData']['bwfan_email_data']['subject'] : '';
			$return['id']    = $email['ID'];
			$return['name']  = $db_subject;
			$return['title'] = $email['title'];
			$final_array[]   = $return;
		}

		return $final_array;
	}

	public static function get_contacts_engagement( $cid, $step_ids, $mode, $type ) {
		global $wpdb;

		$step_id         = array_fill( 0, count( $step_ids ), '%d' );
		$format_step_ids = implode( ', ', $step_id );
		$sql             = "SELECT * FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE `cid`='%d' && `mode`='%d' && `type`='%d' && `sid` IN ({$format_step_ids}) && `open` >0";

		$params = array( $cid, $mode, $type );
		$params = array_merge( $params, $step_ids );

		$prepared_sql = $wpdb->prepare( $sql, $params );

		return $wpdb->get_results( $prepared_sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function get_sorted_fields( $fields ) {
		$sort_format = get_option( 'bwf_crm_field_sort', array() );
		if ( empty( $sort_format ) ) {
			return $fields;
		}
		$data = [];
		foreach ( $sort_format as $sort_data ) {
			if ( ! isset( $sort_data['fields'] ) ) {
				continue;
			}

			foreach ( $sort_data['fields'] as $field_id ) {
				if ( ! isset( $fields[ $field_id ] ) ) {
					continue;
				}

				$data[ $field_id ] = $fields[ $field_id ];
			}
		}

		/** Get newly added fields */
		$field_keys = array_keys( $fields );
		$data_keys  = array_keys( $data );
		$new_fields = array_diff( $field_keys, $data_keys );
		if ( 0 === count( $new_fields ) ) {
			return $data;
		}

		foreach ( $new_fields as $field_id ) {
			$data[ $field_id ] = $fields[ $field_id ];
		}

		return $data;
	}

	/**
	 * Get Contact id from contact object
	 *
	 * @param $email
	 * @param $user_id
	 * @param $phone
	 *
	 * @return int
	 */
	public static function get_cid_from_contact( $email = '', $user_id = 0, $phone = '' ) {
		if ( empty( $email ) && empty( $user_id ) && empty( $phone ) ) {
			return 0;
		}
		$contact = new WooFunnels_Contact( $user_id, $email, $phone );

		if ( $contact->get_id() > 0 ) {
			return $contact->get_id();
		}

		return 0;
	}

	/**
	 * Aman testing purpose only
	 *
	 * @param $data
	 * @param $file
	 * @param $force
	 *
	 * @return void
	 */
	public static function log_test_data( $data, $file = 'v2-automations', $force = false ) {
		if ( empty( $data ) ) {
			return;
		}
		if ( true === $force ) {
			add_filter( 'bwfan_before_making_logs', '__return_true' );
		}
		if ( is_array( $data ) ) {
			BWFAN_Core()->logger->log( print_r( $data, true ), $file );

			return;
		}
		BWFAN_Core()->logger->log( $data, $file );
	}

	/** check order contains subscriptions */
	public static function order_contains_subscriptions( $order_id ) {
		if ( empty( $order_id ) ) {
			return false;
		}

		if ( ! bwfan_is_woocommerce_subscriptions_active() || ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return false;
		}

		$order_contain_subscription = wcs_order_contains_subscription( $order_id );
		if ( ! $order_contain_subscription ) {
			return false;
		}

		$order_subscriptions = wcs_get_subscriptions_for_order( $order_id );

		if ( empty( $order_subscriptions ) || ! is_array( $order_subscriptions ) ) {
			return false;
		}

		return $order_subscriptions;
	}

	/**
	 * Save email template in v1 automation
	 * @return void
	 */
	public static function bwf_save_email_template() {
		BWFAN_PRO_Common::nocache_headers();
		BWFAN_Common::check_nonce(); // phpcs:disable WordPress.Security.NonceVerification

		$post_data = $_POST;
		$mode_type = [
			'raw_template' => 1,
			'raw'          => 3,
			'editor'       => 4,
		];
		$template  = '';
		$error     = '';

		$title = isset( $post_data['title'] ) ? $post_data['title'] : '';

		/** Check for template name */
		if ( empty( $title ) ) {
			$error = __( 'Name is required', 'wp-marketing-automations-pro' );
		}

		$data    = isset( $post_data['data'] ) ? $post_data['data'] : [];
		$subject = isset( $data['subject'] ) ? $data['subject'] : '';

		/** Check for template subject */
		if ( empty( $subject ) ) {
			$error = __( 'Subject is required', 'wp-marketing-automations-pro' );
		}

		$mode = isset( $data['template'] ) && isset( $mode_type[ $data['template'] ] ) ? intval( $mode_type[ $data['template'] ] ) : 1;

		switch ( $mode ) {
			case 1:
				$template = isset( $data['body'] ) ? $data['body'] : '';
				break;
			case 3:
				$template = isset( $data['body_raw'] ) ? $data['body_raw'] : '';
				break;
			case 4:
				if ( isset( $data['editor'] ) && isset( $data['editor']['body'] ) ) {
					$template = $data['editor']['body'];
				}
				break;
		}

		/** Check for template body */
		if ( empty( $template ) ) {
			$error = __( 'Email body is required', 'wp-marketing-automations-pro' );
		}

		if ( ! empty( $error ) ) {
			wp_send_json( array(
				'status' => false,
				'msg'    => $error,
			) );
		}

		$m_data = [
			'preheader'  => isset( $data['preheading'] ) ? $data['preheading'] : '',
			'utmEnabled' => ( isset( $data['append_utm'] ) && intval( $data['append_utm'] ) === 1 ) ? true : false,
			'utm'        => [
				'source'  => isset( $data['utm_source'] ) ? $data['utm_source'] : '',
				'medium'  => isset( $data['utm_medium'] ) ? $data['utm_medium'] : '',
				'name'    => '',
				'content' => isset( $data['utm_campaign'] ) ? $data['utm_campaign'] : '',
				'term'    => isset( $data['utm_term'] ) ? $data['utm_term'] : '',
			],
		];

		if ( isset( $data['editor'] ) && isset( $data['editor']['design'] ) ) {
			$m_data['design'] = stripslashes( $data['editor']['design'] );
		}

		$create_time = current_time( 'mysql', 1 );

		$template_data = [
			'title'      => $title,
			'template'   => stripslashes( $template ),
			'subject'    => $subject,
			'type'       => 1,
			'mode'       => $mode,
			'canned'     => 1,
			'created_at' => $create_time,
			'updated_at' => $create_time,
			'data'       => wp_json_encode( $m_data ),
		];

		$result = BWFAN_Model_Templates::bwfan_create_new_template( $template_data );

		if ( ! $result ) {
			wp_send_json( array(
				'status' => false,
				'msg'    => __( 'Unable to saved template.', 'wp-marketing-automations-pro' ),
			) );
		}

		wp_send_json( array(
			'status' => true,
			'msg'    => __( 'Template saved successfully.', 'wp-marketing-automations-pro' ),
		) );
	}

	/**
	 * Get formatted string for mysql query
	 *
	 * @param $value
	 *
	 * @return array|mixed|string|string[]
	 */
	public static function get_formatted_value_for_dbquery( $value ) {
		return ( false !== strpos( $value, "'" ) ) ? str_replace( "'", "\'", $value ) : $value;
	}

	/**
	 * Get languages options if any language plugin is activated
	 *
	 * @param $settings
	 *
	 * @return array|mixed
	 * @throws Exception
	 */
	public static function get_language_settings( $settings = [] ) {
		$language_options = [];

		/** WPML */
		if ( function_exists( 'icl_get_languages' ) ) {
			$languages = icl_get_languages();
			if ( ! empty( $languages ) ) {
				foreach ( $languages as $language ) {
					$language_options[ $language['language_code'] ] = ! empty( $language['translated_name'] ) ? $language['translated_name'] : $language['native_name'];
				}
			}
		}

		/** Polylang */
		if ( function_exists( 'pll_the_languages' ) ) {
			try {
				$languages = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) );
				if ( ! empty( $languages ) ) {
					foreach ( $languages as $language ) {
						$language_options[ $language['slug'] ] = $language['name'];
					}
				}
			} catch ( Error $e ) {
			}
		}

		/** TranslatePress **/
		if ( bwfan_is_translatepress_active() ) {
			$trp                 = TRP_Translate_Press::get_trp_instance();
			$trp_languages       = $trp->get_component( 'languages' );
			$trp_languages_array = $trp_languages->get_languages( 'english_name' );

			$languages = ! empty( get_option( 'trp_settings' ) ) ? get_option( 'trp_settings' ) : array();
			$languages = isset( $languages['translation-languages'] ) ? $languages['translation-languages'] : array();
			if ( ! empty( $languages ) ) {
				foreach ( $languages as $language ) {
					$language_options[ $language ] = $language;
				}
			}

			$language_options = array_intersect_key( $trp_languages_array, $language_options );
		}

		/** Weglot */
		if ( defined( 'BWFAN_VERSION' ) && version_compare( BWFAN_VERSION, '2.0.2', '>' ) && function_exists( 'bwfan_is_weglot_active' ) && bwfan_is_weglot_active() ) {
			$language_options = BWFAN_PRO_Common::get_weglot_language_option();
		}

		if ( count( $language_options ) > 0 ) {
			$settings['enable_lang']  = 1;
			$settings['lang_options'] = $language_options;
		}

		return apply_filters( 'bwfan_enable_language_settings', $settings );
	}

	/**
	 * Get all active automation contact rows by contact id and automation id
	 *
	 * @param $automation_id
	 * @param $contact_id
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_automation_contact_rows( $automation_id, $contact_id ) {
		global $wpdb;

		$table = "{$wpdb->prefix}bwfan_automation_contact";
		$query = $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE `aid` = %d AND `cid` = %d ORDER BY `ID` ', $automation_id, $contact_id );

		return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function get_midnight_store_time() {
		$timezone = new DateTimeZone( wp_timezone_string() );
		$date     = new DateTime();
		$date->modify( '+1 days' );
		$date->setTimezone( $timezone );
		$date->setTime( 0, 0, 0 );

		return $date->getTimestamp();
	}

	/**
	 * Checking if any languages enabled
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function is_language_enabled() {
		$language_data = self::get_language_settings();
		if ( ! is_array( $language_data ) ) {
			return false;
		}

		if ( isset( $language_data['enable_lang'] ) && 1 === intval( $language_data['enable_lang'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get v2 automations (id and meta) based on the given event slug
	 *
	 * @param $event_slug
	 *
	 * @return array
	 */
	public static function get_all_automations_by_slug( $event_slug = '' ) {
		if ( empty( $event_slug ) ) {
			return [];
		}
		global $wpdb;
		$query = $wpdb->prepare( 'SELECT `ID` FROM {table_name} WHERE `v` = %d AND `event` = %s', 2, $event_slug );

		$all_automations = BWFAN_Model_Automations::get_results( $query );
		if ( 0 === count( $all_automations ) ) {
			return [];
		}

		$final_automation_data = [];
		foreach ( $all_automations as $automation ) {
			$id                           = $automation['ID'];
			$final_automation_data[ $id ] = [
				'id'   => $id,
				'meta' => BWFAN_Model_Automationmeta::get_automation_meta( $id ),
			];
		}

		return $final_automation_data;
	}

	/**
	 * Get Birthday min value
	 *
	 * @return false|string
	 */
	public static function get_birthday_min_value( $type = 'date' ) {
		$min_date = apply_filters( 'bwfan_set_birthday_min', false );
		if ( empty( $min_date ) ) {
			return false;
		}
		$date = DateTime::createFromFormat( 'Y-m-d', $min_date );
		if ( $type === 'year' ) {
			return $date->format( 'Y' );
		}

		return $date && $date->format( 'Y-m-d' ) === $min_date ? "min='$min_date'" : '';
	}

	/**
	 * Get Birthday min value
	 *
	 * @return false|string
	 */
	public static function get_birthday_max_value( $type = 'date' ) {
		$min_date = apply_filters( 'bwfan_set_birthday_max', false );
		if ( empty( $min_date ) ) {
			return false;
		}
		$date = DateTime::createFromFormat( 'Y-m-d', $min_date );
		if ( $type === 'year' ) {
			return $date->format( 'Y' );
		}

		return $date && $date->format( 'Y-m-d' ) === $min_date ? "max='$min_date'" : '';
	}

	/**
	 * Show the email content in browser
	 *
	 * @return void
	 */
	public static function load_view_in_browser_link_content() {
		$action = filter_input( INPUT_GET, 'bwfan-action' );
		if ( empty( $action ) || 'view_in_browser' !== $action ) {
			return;
		}

		$hash_code = filter_input( INPUT_GET, 'bwfan-ehash' );
		if ( empty( $hash_code ) ) {
			return;
		}

		global $wpdb;
		$engagement = $wpdb->get_row( $wpdb->prepare( "SELECT `ID`,`tid`,`oid`,`cid` FROM `{$wpdb->prefix}bwfan_engagement_tracking` WHERE `hash_code` = %s", $hash_code ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $engagement ) ) {
			return;
		}

		$e_id        = isset( $engagement['ID'] ) ? $engagement['ID'] : '';
		$oid         = isset( $engagement['oid'] ) ? $engagement['oid'] : '';
		$template_id = isset( $engagement['tid'] ) ? $engagement['tid'] : '';

		$query    = $wpdb->prepare( "SELECT `template`, `mode`,`type` FROM `{$wpdb->prefix}bwfan_templates` WHERE `ID` = %d LIMIT 0,1", $template_id );
		$response = $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $response ) || empty( $response['template'] ) ) {
			return;
		}

		$body = $response['template'];
		$mode = intval( $response['mode'] );
		$type = intval( $response['type'] );
		if ( method_exists( 'BWFAN_Common', 'bwfan_before_send_mail' ) && 1 === $type && 5 === $mode ) {
			BWFAN_Common::bwfan_before_send_mail();
			$body = BWFAN_Common::correct_shortcode_string( $body, $type );
		}

		$merge_tags = BWFAN_Model_Engagement_Trackingmeta::get_merge_tags( $e_id );
		foreach ( $merge_tags as $key => $value ) {
			$body = str_replace( $key, $value, $body );
		}

		/** Append tracking code in body urls */
		$conversation_obj = new BWFAN_Email_Conversations();

		$body = $conversation_obj->add_tracking_code( $body, $engagement, $hash_code, $oid, true );
		$body = BWFAN_Common::bwfan_correct_protocol_url( $body );

		if ( 1 === intval( $mode ) ) {
			$body = BWFCRM_Common::emogrify_rich_text( $body );
		} else {
			$body = BWFCRM_Common::emogrify_html( $body );
		}

		if ( 5 === $mode && class_exists( 'BWFCRM_Block_Editor' ) ) {
			$global_val = BWFCRM_Block_Editor::$global_settings_var;
			if ( ! empty( $global_val ) ) {
				$global_val_k = array_keys( $global_val );
				$global_val_v = array_values( $global_val );
				$body         = str_replace( $global_val_k, $global_val_v, $body );
			}
		}

		echo $body; //phpcs:ignore WordPress.Security.EscapeOutput
		die;
	}

	/**
	 * Get template type
	 *
	 * @param $type_name
	 *
	 * @return int
	 */
	public static function get_email_template_type( $type_name ) {
		$type = 1;
		switch ( $type_name ) {
			case 'html':
				$type = 3;
				break;
			case 'editor':
				if ( bwfan_is_autonami_pro_active() ) {
					$type = 4;
				}
			case 'block':
				if ( bwfan_is_autonami_pro_active() ) {
					$type = 5;
				}
				break;
		}

		return $type;
	}

	/**
	 * Checks if HPOS enabled
	 *
	 * @return bool
	 */
	public static function is_hpos_enabled() {
		return ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() );
	}

	public static function meta_array_flatten( $array ) {
		if ( ! is_array( $array ) ) {
			return [];
		}
		$data = [];
		foreach ( $array as $row ) {
			if ( empty( $row['meta_value'] ) ) {
				continue;
			}
			$modified = maybe_unserialize( $row['meta_value'] );
			$modified = bwf_is_json( $modified ) ? json_decode( $modified, true ) : $modified;

			$data[ $row['meta_key'] ] = $modified;
		}

		return $data;
	}

	/**
	 * Set headers to prevent caching
	 *
	 * @return void
	 */
	public static function nocache_headers() {
		do_action( 'litespeed_control_set_nocache', 'fkautomations' );
		if ( headers_sent() ) {
			return;
		}

		header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
		header( 'Last-Modified: false' );
	}

	/**
	 * Get v2 automation contact run count
	 *
	 * @param $contact_id
	 * @param $automation_id
	 *
	 * @return int
	 */
	public static function get_v2_automation_contact_run_count( $contact_id, $automation_id ) {
		global $wpdb;

		/** Get run count from automation contact table */
		$query = "SELECT COUNT(`ID`) FROM {$wpdb->prefix}bwfan_automation_contact WHERE `cid` = %d AND `aid` = %d";;
		$active_run_count = $wpdb->get_var( $wpdb->prepare( $query, $contact_id, $automation_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		/** Get run count from automation complete contact table */
		$query = "SELECT COUNT(`ID`) FROM {$wpdb->prefix}bwfan_automation_complete_contact WHERE `cid` = %d AND `aid` = %d";

		return intval( $active_run_count ) + intval( $wpdb->get_var( $wpdb->prepare( $query, $contact_id, $automation_id ) ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * If url exists in excluded urls
	 *
	 * @param $url
	 *
	 * @return bool
	 */
	public static function is_exclude_url( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		$excluded_urls = [
			'fonts.googleapis.com',
			'mailto:',
			'tel:',
			'whatsapp:',
			'wa.me:',
			'//wa.me',
			't.me',
			'm.me',
			'x.com',
			'twitter.com',
			'linkedin.com',
			'instagram.com',
			'pinterest.com',
			'youtube.com',
			'snapchat.com',
			'reddit.com',
			'tripadvisor.com',
			'meetup.com',
			'producthunt.com',
			'tinder.com',
			'tumblr.com',
			'music.apple.com',
			'open.spotify.com',
			'soundcloud.com',
			'yelp.com',
			'medium.com',
			'skype.com',
			'flickr.com',
			'github.com',
			'discord.gg',
			'tiktok.com',
			'zoom.us'
		];
		$excluded_urls = apply_filters( 'bwfan_exclude_click_track_urls', $excluded_urls );

		foreach ( $excluded_urls as $excluded_url ) {
			if ( $url === $excluded_url || false !== strpos( $url, $excluded_url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $link
	 *
	 * @return void
	 */
	public static function wp_redirect( $link ) {
		remove_all_filters( 'wp_redirect' );

		add_filter( 'allowed_redirect_hosts', function ( $allowed_hosts, $host ) use ( $link ) {
			/** Add link host in allowed hosts, because already checked link is valid or not */
			if ( $host === wp_parse_url( $link, PHP_URL_HOST ) ) {
				$allowed_hosts[] = $host;
			}

			return $allowed_hosts;
		}, 10, 2 );

		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}

		wp_safe_redirect( $link );
		exit;
	}

	/**
	 * Get unsubscribe link
	 *
	 * @param $data
	 *
	 * @return string
	 */
	public static function get_unsubscribe_link( $data ) {
		if ( method_exists( 'BWFAN_Common', 'get_unsubscribe_link' ) ) {
			return BWFAN_Common::get_unsubscribe_link( $data );
		}

		if ( empty( self::$unsubscribe_page_link ) ) {
			$global_settings = BWFAN_Common::get_global_settings();
			if ( ! isset( $global_settings['bwfan_unsubscribe_page'] ) || empty( $global_settings['bwfan_unsubscribe_page'] ) ) {
				return '';
			}
			$page                        = absint( $global_settings['bwfan_unsubscribe_page'] );
			self::$unsubscribe_page_link = get_permalink( $page );
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return self::$unsubscribe_page_link;
		}

		if ( empty( $data['uid'] ) && isset( $data['contact_id'] ) ) {
			$contact = new WooFunnels_Contact( '', '', '', $data['contact_id'] );
			$uid     = ( $contact->get_id() > 0 ) ? $contact->get_uid() : '';

			if ( ! empty( $uid ) ) {
				$data['uid'] = $uid;
			}
			unset( $data['contact_id'] );
		}

		$data['bwfan-action'] = 'unsubscribe';

		return add_query_arg( $data, self::$unsubscribe_page_link );
	}

	/**
	 * Disable run v2 automation immediately
	 *
	 * @return void
	 */
	public static function disable_run_v2_automation_immediately() {
		$disable = apply_filters( 'bwfan_disable_run_v2_automation_immediately', true );
		add_filter( 'bwfan_run_v2_automation_immediately', function ( $run ) use ( $disable ) {
			return ! ( true === $disable );
		} );
	}

	/**
	 * Set automation ended reason in data
	 *
	 * @param $reason
	 * @param $data
	 *
	 * @return mixed
	 */
	public static function set_automation_ended_reason( $reason, $data ) {
		if ( method_exists( 'BWFAN_Common', 'set_automation_ended_reason' ) ) {
			BWFAN_Common::set_automation_ended_reason( $reason, $data );
		}
		$decoded_data               = ! empty( $data['data'] ) ? json_decode( $data['data'], true ) : [];
		$decoded_data['end_reason'] = $reason;
		$data['data']               = wp_json_encode( $decoded_data );

		return $data;
	}

	/**
	 * Get generic rules
	 *
	 * @return array
	 */
	public static function get_generic_rules() {
		if ( ! class_exists( 'BWFAN_Core' ) ) {
			return [];
		}
		$generic_rule_groups = [
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_field',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast',
			'bwf_automation'
		];
		$rules_groups        = BWFAN_Core()->rules->get_all_groups();
		$rules               = apply_filters( 'bwfan_rule_get_rule_types', array() );

		$final_rules = array();
		foreach ( $generic_rule_groups as $group ) {
			if ( ! isset( $rules_groups[ $group ] ) || ! isset( $rules[ $group ] ) ) {
				continue;
			}

			$final_rules[ $group ] = $rules_groups[ $group ];

			$group_rules = $rules[ $group ];
			$rule_keys   = array_keys( $rules[ $group ] );

			$final_rules[ $group ]['rules'] = array_map( function ( $rule ) use ( $group_rules ) {
				$rules_loader = BWFAN_Rules_Loader::get_instance();
				$rule_array   = $rules_loader->get_rule_schema( $rule, [] );
				if ( empty( $rule_array ) ) {
					return false;
				}
				$rule_array['name'] = $group_rules[ $rule ];

				return $rule_array;
			}, $rule_keys );

			$final_rules[ $group ]['rules'] = array_filter( $final_rules[ $group ]['rules'] );
			if ( empty( $final_rules[ $group ]['rules'] ) ) {
				unset( $final_rules[ $group ] );
			}
		}

		return $final_rules;
	}

	/**
	 * Check lite plugin version is 3.0 or greater
	 *
	 * @return bool
	 */
	public static function is_lite_3_0() {
		return defined( 'BWFAN_VERSION' ) && version_compare( BWFAN_VERSION, '3.0.0', '>=' );
	}

	/**
	 * Optimize phone number by removing spaces, brackets, hyphens and retains the country code and numbers
	 *
	 * @param $phone
	 *
	 * @return array|mixed|string|string[]|null
	 */
	public static function modify_phone( $phone = '' ) {
		if ( empty( $phone ) ) {
			return $phone;
		}
		if ( method_exists( 'BWFAN_Common', 'modify_phone' ) ) {
			return BWFAN_Common::modify_phone( $phone );
		}

		return preg_replace( '/[\s\-()]+/', '', $phone );
	}

	/**
	 * Get wishlist data including product IDs, variation IDs, user ID, and title for a specific wishlist ID.
	 *
	 * @param int $wishlist_id The ID of the wishlist.
	 *
	 * @return array|bool An array of wishlist data with keys 'wishlist_id', 'user_id', 'title', 'product_ids', and 'variation_ids', or false if not found.
	 */
	public static function get_ti_wc_wishlist_data_by_id( $wishlist_id ) {
		if ( empty( $wishlist_id ) || ! is_numeric( $wishlist_id ) ) {
			return false;
		}
		global $wpdb;
		$query  = $wpdb->prepare( "SELECT lists.ID AS wishlist_id, lists.author AS user_id, lists.title, 
                GROUP_CONCAT(items.product_id) AS product_ids, 
                GROUP_CONCAT(items.variation_id) AS variation_ids
         FROM {$wpdb->prefix}tinvwl_lists AS lists 
         JOIN {$wpdb->prefix}tinvwl_items AS items ON lists.ID = items.wishlist_id 
         WHERE lists.ID = %d 
         GROUP BY lists.ID", $wishlist_id );
		$result = $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $result ) || ! is_array( $result ) ) {
			return false;
		}

		return [
			'wishlist_id' => $result['wishlist_id'],
			'user_id'     => $result['user_id'],
			'title'       => $result['title'],
			'product_ids' => explode( ',', $result['product_ids'] )
		];
	}

	/**
	 * @return void
	 */
	public static function validate_scheduled_recurring_actions() {
		if ( method_exists( 'BWFAN_Common', 'validate_scheduled_recurring_actions' ) ) {
			BWFAN_Common::validate_scheduled_recurring_actions();

			return;
		}
		$hooks = [
			[
				'name'       => 'bwfan_run_event_queue',
				'time'       => MINUTE_IN_SECONDS,
				'args'       => [],
				'group_slug' => ''
			],
			[
				'name'       => 'bwfan_run_queue_v2',
				'time'       => MINUTE_IN_SECONDS,
				'args'       => [],
				'group_slug' => ''
			],
			[
				'name'       => 'bwfcrm_broadcast_run_queue',
				'time'       => MINUTE_IN_SECONDS,
				'args'       => [],
				'group_slug' => 'bwfcrm'
			]
		];

		foreach ( $hooks as $hook ) {
			if ( bwf_has_action_scheduled( $hook['name'] ) ) {
				continue;
			}

			bwf_schedule_recurring_action( time(), $hook['time'], $hook['name'], $hook['args'], $hook['group_slug'] );
		}
	}

	public static function forms_fields_rules( $type, $condition_value, $value ) {
		switch ( $type ) {
			case 'is':
				if ( is_array( $condition_value ) && is_array( $value ) ) {
					$result = count( array_intersect( $condition_value, $value ) ) > 0;
				} else {
					$result = in_array( $condition_value, $value );
				}
				break;
			case 'is_not':
				if ( is_array( $condition_value ) && is_array( $value ) ) {
					$result = count( array_intersect( $condition_value, $value ) ) === 0;
				} else {
					$result = ! in_array( $condition_value, $value );
				}
				break;
			case 'contains':
				if ( is_array( $value ) ) {
					$result = ! empty( array_filter( $value, function ( $element ) use ( $condition_value ) {
						return strpos( $element, $condition_value ) !== false;
					} ) );
					break;
				}

				$result = strpos( $value, $condition_value ) !== false;
				break;
			case 'not_contains':
				if ( is_array( $value ) ) {
					$result = ! empty( array_filter( $value, function ( $element ) use ( $condition_value ) {
						return strpos( $element, $condition_value ) === false;
					} ) );
					break;
				}

				$result = strpos( $value, $condition_value ) === false;
				break;
			case 'starts_with':
				$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
				$length = strlen( $condition_value );
				$result = substr( $value, 0, $length ) === $condition_value;
				break;
			case 'ends_with':
				$value  = is_array( $value ) ? end( $value ) : $value;
				$length = strlen( $condition_value );
				if ( 0 === $length || ( $length > strlen( $value ) ) ) {
					$result = false;
					break;
				}
				$result = substr( $value, - $length ) === $condition_value;
				break;
			case 'is_blank':
				$result = empty( $value[0] );
				break;
			case 'is_not_blank':
				$result = ! empty( $value[0] );
				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}

	public static function get_referral_data( $customer_id ) {
		global $wpdb;

		$referral_data = [];
		$customer_data = $wpdb->get_row( $wpdb->prepare( "SELECT email, first_name, last_name FROM {$wpdb->prefix}affiliate_wp_customers WHERE customer_id = %d ", $customer_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $customer_data ) ) {
			return $referral_data;
		}
		$referral_data['email']      = isset( $customer_data->email ) ? $customer_data->email : '';
		$referral_data['first_name'] = isset( $customer_data->first_name ) ? $customer_data->first_name : '';
		$referral_data['last_name']  = isset( $customer_data->last_name ) ? $customer_data->last_name : '';

		return $referral_data;
	}

	/**
	 * Get Offers
	 *
	 * @param $search
	 *
	 * @return array
	 */
	public static function get_offers( $search = '' ) {
		$args = [
			"post_type"   => "wfocu_offer",
			"s"           => $search,
			"numberposts" => 20,
		];

		return self::get_formatted_offer_data( $args, $search );
	}

	/**
	 * Get Bump
	 *
	 * @param $search
	 *
	 * @return array
	 */
	public static function get_bumps( $search = '' ) {
		$args = [
			"post_type"   => "wfob_bump",
			"s"           => $search,
			"numberposts" => 20,
		];

		return self::get_formatted_offer_data( $args, $search );
	}

	/**
	 * Get formatted offer/bump
	 *
	 * @param $args
	 * @param $search
	 *
	 * @return array
	 */
	public static function get_formatted_offer_data( $args, $search ) {
		$posts  = get_posts( $args );
		$offers = [];
		foreach ( $posts as $post ) {
			$offers[ $post->ID ] = $post->post_title . '(#' . $post->ID . ')';
		}

		return array_filter( $offers, function ( $offer ) use ( $search ) {
			return false !== strpos( strtolower( $offer ), strtolower( $search ) );
		} );
	}

	/**
	 * Check advance log enabled
	 *
	 * @param $log
	 *
	 * @return bool
	 */
	public static function is_log_enabled( $log ) {
		if ( method_exists( 'BWFAN_Common', 'is_log_enabled' ) ) {
			return BWFAN_Common::is_log_enabled( $log );
		}
		$global_settings = BWFAN_Common::get_global_settings();

		return isset( $global_settings['bwfan_advance_logs'] ) && ! empty( $global_settings['bwfan_advance_logs'] ) && isset( $global_settings[ $log ] ) && ! empty( $global_settings[ $log ] );
	}

	/**
	 * Get formatted tag & list data if comma exists in value
	 *
	 * @param $data
	 *
	 * @return array|string[]
	 */
	public static function check_for_comma_seperated( $data ) {
		if ( method_exists( 'BWFAN_Common', 'check_for_comma_seperated' ) ) {
			return BWFAN_Common::check_for_comma_seperated( $data );
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return [];
		}
		$formatted_data = [];
		foreach ( $data as $value ) {
			if ( ! empty( ( $value['id'] ) ) || false === strpos( $value['value'], ',' ) ) {
				$formatted_data[] = $value;
				continue;
			}
			$comma_separated_values = explode( ',', $value['value'] );
			$comma_separated_values = array_map( function ( $single_value ) {
				return [
					'id'    => 0,
					'value' => trim( $single_value )
				];
			}, $comma_separated_values );
			$formatted_data         = array_merge( $formatted_data, $comma_separated_values );
		}

		return $formatted_data;
	}

	/**
	 * Get date format
	 *
	 * @return array[]
	 */
	public static function get_date_formats() {
		if ( method_exists( 'BWFAN_Common', 'get_date_formats' ) ) {
			return BWFAN_Common::get_date_formats();
		}

		return array(
			array(
				'format' => 'j M Y',
			),
			array(
				'format' => 'jS M Y',
			),
			array(
				'format' => 'M j Y',
			),
			array(
				'format' => 'M jS Y',
			),
			array(
				'format' => 'd/m/Y',
			),
			array(
				'format' => 'd-m-Y',
			),
			array(
				'format' => 'Y/m/d',
			),
			array(
				'format' => 'Y-m-d',
			),
			array(
				'format' => 'd/m/Y H:i:s',
			),
			array(
				'format' => 'd-m-Y H:i:s',
			),
			array(
				'format' => 'Y/m/d H:i:s',
			),
			array(
				'format' => 'Y-m-d H:i:s',
			),
			array(
				'format' => 'd.m.Y',
			),
		);
	}

	/** Breakdance  */
	public static function get_breakdance_form_fields( $form_post_id ) {
		global $wpdb;

		// Query all Breakdance form metadata
		$query   = "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_breakdance_data'";
		$results = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $results ) {
			return [];
		}

		foreach ( $results as $row ) {
			$meta_data = json_decode( $row->meta_value, true );
			if ( ! isset( $meta_data['tree_json_string'] ) ) {
				continue;
			}

			$tree_data = json_decode( $meta_data['tree_json_string'], true );
			if ( ! isset( $tree_data['root'] ) ) {
				continue;
			}
			$fields = [];

			self::extract_form_fields( $tree_data['root'], $form_post_id, $fields );

			if ( ! empty( $fields ) ) {
				return $fields;
			}
		}

		return [];
	}


	/**
	 * Extract Form Fields from the Breakdance Form Builder
	 */
	private static function extract_form_fields( $node, $form_post_id, &$fields ) {
		if ( isset( $node['data']['type'] ) && $node['data']['type'] === 'EssentialElements\\FormBuilder' ) {
			if ( isset( $node['id'] ) && $node['id'] == $form_post_id ) {
				if ( isset( $node['data']['properties']['content']['form']['fields'] ) ) {
					foreach ( $node['data']['properties']['content']['form']['fields'] as $field ) {
						if ( ! empty( $field['advanced']['id'] ) && ! empty( $field['label'] ) ) {
							$fields[ $field['advanced']['id'] ] = sanitize_text_field( $field['label'] );
						}
					}
				}
			}
		}

		if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
			foreach ( $node['children'] as $child ) {
				self::extract_form_fields( $child, $form_post_id, $fields );
			}
		}
	}

	public static function get_all_breakdance_forms() {
		global $wpdb;

		$post_types   = [ 'page', 'breakdance_popup', 'breakdance_template', 'breakdance_global_block' ];
		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		$query = $wpdb->prepare( "
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_breakdance_data' 
        AND post_id IN (
            SELECT ID FROM {$wpdb->posts} WHERE post_type IN ($placeholders)
        )
    ", ...$post_types );

		$results = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $results ) {
			return [];
		}

		$unique_forms = [];

		foreach ( $results as $row ) {
			$meta_data = json_decode( $row->meta_value, true );
			if ( ! $meta_data || ! isset( $meta_data['tree_json_string'] ) ) {
				continue;
			}

			$tree_data = json_decode( $meta_data['tree_json_string'], true );
			if ( ! $tree_data || ! isset( $tree_data['root'] ) ) {
				continue;
			}
			self::extract_breakdance_forms( $tree_data['root'], $row->post_id, $unique_forms );
		}

		return array_values( $unique_forms );
	}

	public static function extract_breakdance_forms( $node, $post_id, &$unique_forms ) {
		if ( isset( $node['data']['type'] ) && $node['data']['type'] === 'EssentialElements\\FormBuilder' ) {
			$form_id    = $node['id'];
			$form_title = $node['data']['properties']['content']['form']['form_name'] ?? 'Untitled Form';

			// Unique key for preventing duplicate forms
			$unique_key = $form_id . '_' . $post_id;

			if ( ! isset( $unique_forms[ $unique_key ] ) ) {
				$unique_forms[ $unique_key ] = [
					'id'      => $form_id,
					'title'   => $form_title,
					'post_id' => $post_id
				];
			}
		}

		if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
			foreach ( $node['children'] as $child ) {
				self::extract_breakdance_forms( $child, $post_id, $unique_forms );
			}
		}
	}

	/**
	 * @param $link
	 * Validate links domains
	 *
	 * @return mixed|string|null
	 */
	public static function validate_target_link( $link ) {
		if ( method_exists( 'BWFAN_Common', 'validate_target_link' ) ) {
			return BWFAN_Common::validate_target_link( $link );
		}

		$link_host     = parse_url( urldecode( $link ), PHP_URL_HOST );
		$site_url      = home_url();
		$site_url_host = parse_url( $site_url, PHP_URL_HOST );

		/** If site url and redirect url host are same */
		if ( $link_host === $site_url_host ) {
			return $link;
		}

		$allowed_domains = apply_filters( 'bwfan_allowed_redirect_domains', array() );
		if ( empty( $allowed_domains ) ) {
			return $link;
		}

		/** Filter valid domains */
		$allowed_domains = array_filter( array_map( function ( $domain ) {
			return ! empty( $domain ) && is_string( $domain ) ? wp_parse_url( $domain, PHP_URL_HOST ) : false;
		}, $allowed_domains ) );

		return in_array( $link_host, $allowed_domains, true ) ? $link : $site_url;
	}

	/**
	 * If constant define then get contact email by user id
	 *
	 * @param $user_id
	 * @param $contact_id
	 *
	 * @return string
	 */
	public static function get_contact_email( $user_id = 0, $contact_id = 0 ) {
		if ( method_exists( 'BWFAN_Common', 'get_contact_email' ) ) {
			return BWFAN_Common::get_contact_email( $user_id, $contact_id );
		}

		if ( ! defined( 'BWFAN_GET_CONTACT_EMAIL' ) || true !== BWFAN_GET_CONTACT_EMAIL || ( empty( $user_id ) && empty( $contact_id ) ) ) {
			return '';
		}

		$contact = new WooFunnels_Contact( $user_id, '', '', $contact_id );

		return $contact->get_id() > 0 ? $contact->get_email() : '';
	}

}
