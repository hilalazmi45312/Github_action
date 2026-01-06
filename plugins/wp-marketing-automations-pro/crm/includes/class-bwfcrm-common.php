<?php

/**
 * Class BWFCRM_Common
 * Handles Common Functions For Admin as well as front end interface
 */
class BWFCRM_Common {

	private static $_user_address_meta_updated = array();
	public static $contact_wp_user_address_fields = array(
		'address-1' => 'billing_address_1',
		'address-2' => 'billing_address_2',
		'city'      => 'billing_city',
		'state'     => 'billing_state',
		'postcode'  => 'billing_postcode',
		'country'   => 'billing_country',
	);

	public static $captured_email_failed_message = null;

	public static function init() {
		add_action( 'bwf_before_migrate_contact_save', array( __CLASS__, 'migrate_bwf_contact' ), 10, 2 );
		add_action( 'bwf_normalize_contact_meta_before_save', array( __CLASS__, 'update_contact_crm_fields' ), 10, 3 );
		add_action( 'wp_login', array( __CLASS__, 'save_last_login' ), 9, 2 );

		add_action( 'profile_update', array( __CLASS__, 'update_contact_fields' ), 10, 2 );
		add_action( 'updated_user_meta', array( __CLASS__, 'mark_updated_address_fields' ), 10, 4 );

		add_filter( 'bwf_before_profile_update_contact_sync', [ __CLASS__, 'profile_update_contact_sync' ], 10, 1 );

		add_action( 'bwfan_delete_unsubscriber', array( __CLASS__, 'maybe_subscribe_contact' ), 10, 1 );

		add_filter( 'bwfan_modify_engagement_body_preview', array( __CLASS__, 'add_style_in_engagement_body_preview' ), 10, 2 );

		add_filter( 'bwfan_weekly_mail_status_section', [ __CLASS__, 'bwfan_weekly_mail_status_section' ] );

		/** Save contact's last sent field */
		add_action( 'bwfan_broadcast_last_sent', array( __CLASS__, 'update_last_sent' ), 10, 2 );

		/** Change the old Export\Import log files name */
		add_action( 'bwfcrm_change_log_file_name', array( __CLASS__, 'bwfcrm_change_log_file_name' ) );

		add_action( 'wp_mail_failed', array( __CLASS__, 'email_failed' ) );
	}

	/**
	 * Checks the status
	 *
	 * @return array
	 * @throws DateMalformedStringException
	 */
	public static function bwfan_weekly_mail_status_section( $arr = [] ) {
		$data = BWFAN_Common::get_lk_data();
		$s    = isset( $data['s'] ) ? $data['s'] : 0;
		$e    = isset( $data['e'] ) ? $data['e'] : '';
		$ad   = isset( $data['ad'] ) ? $data['ad'] : '';

		$upgrade_link = BWFAN_Common::get_fk_site_links();
		$upgrade_link = isset( $upgrade_link['upgrade'] ) ? $upgrade_link['upgrade'] : '';
		$upgrade_link = add_query_arg( [
			'utm_campaign' => 'FKA+Pro+Notification'
		], $upgrade_link );
		if ( $s === 2 ) {
			if ( $e === '' ) {
				return [];
			}
			$n  = new DateTime();
			$ed = new DateTime( $e );
			if ( $n > $ed ) {
				$diff = $n->diff( $ed )->days;
				if ( $diff < 1 ) {
					$ed->modify( '+1 days' );

					$link = add_query_arg( [
						'utm_medium' => 'Renew+Now+Grace'
					], $upgrade_link );
					$date = date_i18n( 'd F, Y', $ed->getTimestamp() );

					return [
						'content'           => sprintf( __( "Your FunnelKit Automations license grace period will expire on %s. Please renew to get uninterrupted service.", 'wp-marketing-automations-pro' ), $date ),
						'link'              => $link,
						'link_text'         => __( 'Renew Now', 'wp-marketing-automations-pro' ),
						'background_color'  => '#FEF7E8',
						'button_color'      => '#FFC65C',
						'button_text_color' => '#000000',
					];
				}

				$link = add_query_arg( [
					'utm_medium' => 'Renew+Now+Expired'
				], $upgrade_link );

				return [
					'content'           => sprintf( __( "Your FunnelKit Automations %s. Please renew to get uninterrupted service.", 'wp-marketing-automations-pro' ), '<strong>' . __( 'license has been expired', 'wp-marketing-automations-pro' ) . '</strong>' ),
					'link'              => $link,
					'link_text'         => __( 'Renew Now', 'wp-marketing-automations-pro' ),
					'background_color'  => '#FFE9E9',
					'button_color'      => '#E15334',
					'button_text_color' => '#ffffff',
				];
			}

			return [];
		}
		if ( $s === 1 ) {
			if ( $ad !== '' ) {
				$n      = new DateTime();
				$adDate = new DateTime( $ad );
				$diff   = $n->diff( $adDate )->days;
				if ( $diff < 1 ) {
					$adDate->modify( '+1 days' );

					$link = add_query_arg( [
						'utm_medium' => 'Get+License+Grace'
					], $upgrade_link );
					$date = date_i18n( 'd F, Y', $adDate->getTimestamp() );

					return [
						'content'           => sprintf( __( "Your FunnelKit Automations license grace period will expire on %s. Activate license to get uninterrupted service.", 'wp-marketing-automations-pro' ), $date ),
						'link'              => $link,
						'link_text'         => __( 'Get License', 'wp-marketing-automations-pro' ),
						'background_color'  => '#FEF7E8',
						'button_color'      => '#FFC65C',
						'button_text_color' => '#000000',
					];
				}
			}

			$link = add_query_arg( [
				'utm_medium' => 'Get+License+Inactive'
			], $upgrade_link );

			return [
				'content'           => __( "Your FunnelKit Automations license is not active. Please renew to get uninterrupted service.", 'wp-marketing-automations-pro' ),
				'link'              => $link,
				'link_text'         => __( 'Get License Now', 'wp-marketing-automations-pro' ),
				'background_color'  => '#FEF7E8',
				'button_color'      => '#FFC65C',
				'button_text_color' => '#000000',
			];
		}

		return [];
	}

	public static function profile_update_contact_sync( $contact ) {
		$contact = new BWFCRM_Contact( $contact );

		if ( $contact->is_contact_exists() ) {
			add_filter( 'bwf_profile_update_contact_sync_field', function ( $bwf_contact, $key, $value ) use ( $contact ) {
				$contact->contact = $bwf_contact;
				$contact->set_field_by_slug( $key, $value );

				return $contact->contact;
			}, 10, 3 );

			add_filter( 'bwf_after_profile_update_contact_sync', function ( $bwf_contact ) use ( $contact ) {
				$contact->contact = $bwf_contact;
				$contact->save_fields();

				return $contact->contact;
			}, 10, 1 );
		}

		return $contact->contact;
	}

	/** Update Address fields on WP User update
	 *
	 * @param int $user_id
	 * @param WP_User $user_data
	 */
	public static function update_contact_fields( $user_id, $user_data ) {

		/** Check if Old User Data valid */
		if ( ! $user_data instanceof WP_User || ! is_email( $user_data->user_email ) ) {
			self::$_user_address_meta_updated = array();

			return;
		}

		/** Check if there is a contact */
		$contact = new BWFCRM_Contact( $user_data->user_email );
		if ( ! $contact->is_contact_exists() ) {
			self::$_user_address_meta_updated = array();

			return;
		}

		/** Check whether new email update required or address needed update */
		$new_email = self::maybe_get_new_user_email( $user_id, $user_data->user_email );
		if ( empty( self::$_user_address_meta_updated ) && empty( $new_email ) ) {
			self::$_user_address_meta_updated = array();

			return;
		}

		$fields_updated = false;
		foreach ( self::$_user_address_meta_updated as $meta_key => $meta_value ) {
			$crm_key = array_search( $meta_key, self::$contact_wp_user_address_fields, true );
			if ( empty( $crm_key ) ) {
				continue;
			}

			if ( 'state' === $crm_key ) {
				$contact->contact->set_state( $meta_value );
				continue;
			}

			if ( 'country' === $crm_key ) {
				$contact->contact->set_country( $meta_value );
				continue;
			}

			$contact->set_field_by_slug( $crm_key, $meta_value );
			$fields_updated = true;
		}

		/** Update the email, if Profile email got updated */
		if ( ! empty( $new_email ) ) {
			$contact->contact->set_email( $new_email );
		}

		if ( $fields_updated ) {
			$contact->save_fields();
		}

		$contact->contact->set_last_modified( current_time( 'mysql', 1 ) );
		$contact->save();

	}

	public static function mark_updated_address_fields( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$address_meta_keys = array_values( self::$contact_wp_user_address_fields );
		if ( in_array( $meta_key, $address_meta_keys, true ) ) {
			self::$_user_address_meta_updated[ $meta_key ] = $_meta_value;
		}
	}

	/**
	 * Contact Indexing
	 *
	 * @param $bwf_contact
	 * @param $order
	 */
	public static function migrate_bwf_contact( $bwf_contact, $order ) {
		$crm_contact = new BWFCRM_Contact( $bwf_contact );
		if ( ! $crm_contact->is_contact_exists() ) {
			return;
		}

		$address_1 = $bwf_contact->get_meta( 'address_1', false );
		$address_2 = $bwf_contact->get_meta( 'address_2', false );
		$postcode  = $bwf_contact->get_meta( 'postcode', false );
		$company   = $bwf_contact->get_meta( 'company', false );
		$city      = $bwf_contact->get_meta( 'city', false );

		! empty( $address_1 ) && $crm_contact->set_address_1( $address_1 );
		! empty( $address_2 ) && $crm_contact->set_address_2( $address_2 );
		! empty( $postcode ) && $crm_contact->set_postcode( $postcode );
		! empty( $company ) && $crm_contact->set_company( $company );
		! empty( $city ) && $crm_contact->set_city( $city );
		if ( $order instanceof WC_Order ) {
			self::get_set_customer_fields( $crm_contact, $order->get_id(), $order );
		}
	}

	/**
	 * Update Contact Fields
	 *
	 * @param $bwf_contact
	 * @param $order_id
	 * @param $order WC_Order
	 */
	public static function update_contact_crm_fields( $bwf_contact, $order_id, $order ) {
		$crm_contact = new BWFCRM_Contact( $bwf_contact );
		if ( ! $crm_contact->is_contact_exists() ) {
			return;
		}

		self::get_set_customer_fields( $crm_contact, $order_id, $order );
	}

	/**
	 * Get customer billing details and save in Contact Fields
	 * Helper function
	 *
	 * @param $crm_contact BWFCRM_Contact
	 * @param $order_id
	 * @param $order WC_Order
	 */
	public static function get_set_customer_fields( $crm_contact, $order_id, $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return;
			}
		}

		$address_1 = $order->get_billing_address_1();
		$address_2 = $order->get_billing_address_2();
		$postcode  = $order->get_billing_postcode();
		$company   = $order->get_billing_company();
		$city      = $order->get_billing_city();

		if ( true === WooFunnels_DB_Updater::$indexing ) {
			( empty( $crm_contact->get_address_1() ) && ! empty( $address_1 ) ) && $crm_contact->set_address_1( $address_1 );
			( empty( $crm_contact->get_address_2() ) && ! empty( $address_2 ) ) && $crm_contact->set_address_2( $address_2 );
			( empty( $crm_contact->get_postcode() ) && ! empty( $postcode ) ) && $crm_contact->set_postcode( $postcode );
			( empty( $crm_contact->get_company() ) && ! empty( $company ) ) && $crm_contact->set_company( $company );
			( empty( $crm_contact->get_city() ) && ! empty( $city ) ) && $crm_contact->set_city( $city );

			$crm_contact->save_fields();

			return;
		}

		if ( apply_filters( 'bwfan_maybe_save_blank_value', false ) ) {
			$crm_contact->set_address_1( $address_1 );
			$crm_contact->set_address_2( $address_2 );
			$crm_contact->set_postcode( $postcode );
			$crm_contact->set_company( $company );
			$crm_contact->set_city( $city );

			$crm_contact->save_fields();

			return;
		}

		! empty( $address_1 ) && $crm_contact->set_address_1( $address_1 );
		! empty( $address_2 ) && $crm_contact->set_address_2( $address_2 );
		! empty( $postcode ) && $crm_contact->set_postcode( $postcode );
		! empty( $company ) && $crm_contact->set_company( $company );
		! empty( $city ) && $crm_contact->set_city( $city );

		$crm_contact->save_fields();
	}

	/**
	 * Get Format for Error Response
	 *
	 * @param $error_array
	 * @param string $message
	 * @param int $response_code
	 *
	 * @return array
	 */
	public static function format_error_response( $error_array, $message = '', $response_code = 500 ) {
		return array(
			'code'    => $response_code,
			'message' => $message,
			'error'   => $error_array,
		);
	}

	/**
	 * Get Format for Success Response
	 *
	 * @param $result_array
	 * @param string $message
	 * @param int $response_code
	 *
	 * @return array
	 */
	public static function format_success_response( $result_array, $message = '', $response_code = 200 ) {
		return array(
			'code'    => $response_code,
			'message' => $message,
			'result'  => $result_array,
		);
	}

	/**
	 * @param string $message
	 * @param array $data
	 * @param int $code
	 *
	 * @return WP_Error
	 */
	public static function crm_error( $message = '', $data = array(), $code = 500 ) {
		$wp_error = new WP_Error( $code, $message );
		if ( ! empty( $data ) ) {
			$wp_error->add_data( $data );
		}

		return $wp_error;
	}

	public static function get_store_aov() {
		global $wpdb;
		$sql    = "SELECT count(`order_id`) as `orders`, SUM(`total_sales`) as `total` FROM {$wpdb->prefix}wc_order_stats WHERE `status` NOT IN('wc-failed', 'wc-pending', 'wc-cancelled', 'wc-refunded') ";
		$result = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$aov    = 0;
		if ( isset( $result[0]['orders'] ) && ! empty( $result[0]['orders'] ) && isset( $result[0]['total'] ) && ! empty( $result[0]['total'] ) ) {
			$aov = ( floatval( $result[0]['total'] ) / absint( $result[0]['orders'] ) );
		}

		return $aov;
	}

	/**
	 * * function to get term by type tag=0 and list=1
	 **/
	public static function get_term_by_type( $type ) {
		global $wpdb;
		$query     = "Select * from {table_name} where type='" . $type . "'";
		$all_terms = BWFAN_Model_Terms::get_results( $query );
		$all_term  = array();

		foreach ( $all_terms as $term_key => $term ) {
			$all_term[ $term['ID'] ] = $term['name'];
		}

		return $all_term;
	}

	/**
	 * function to get all the lists
	 **/
	public static function get_all_lists() {
		$query     = "Select ID,name from {table_name} where type='2'";
		$lists     = BWFAN_Model_Terms::get_results( $query );
		$list_data = array();
		if ( empty( $lists ) ) {
			return $list_data;
		}

		foreach ( $lists as $key => $list ) {
			$list_data[ $list['ID'] ] = $list['name'];
		}

		return $list_data;
	}

	public static function is_crm_page() {
		return isset( $_GET['page'] ) && ( 'bwf-broadcasts' === $_GET['page'] || 'bwfcrm-contacts' === $_GET['page'] );
	}

	public static function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = '128M'; // Sensible default, and minimum required by WooCommerce
		}

		if ( ! $memory_limit || - 1 === $memory_limit || '-1' === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32G';
		}

		return self::convert_hr_to_bytes( $memory_limit );
	}

	public static function convert_hr_to_bytes( $value ) {
		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {
			return wp_convert_hr_to_bytes( $value );
		}

		$value = strtolower( trim( $value ) );
		$bytes = (int) $value;

		if ( false !== strpos( $value, 'g' ) ) {
			$bytes *= GB_IN_BYTES;
		} elseif ( false !== strpos( $value, 'm' ) ) {
			$bytes *= MB_IN_BYTES;
		} elseif ( false !== strpos( $value, 'k' ) ) {
			$bytes *= KB_IN_BYTES;
		}

		// Deal with large (float) values which run into the maximum integer size.
		return min( $bytes, PHP_INT_MAX );
	}

	public static function memory_exceeded() {
		$memory_limit   = self::get_memory_limit() * 0.9;
		$current_memory = memory_get_usage( true );

		return $current_memory >= $memory_limit;
	}

	public static function reschedule_broadcast_action() {
		if ( bwf_has_action_scheduled( 'bwfcrm_broadcast_run_queue' ) ) {
			bwf_unschedule_actions( 'bwfcrm_broadcast_run_queue' );
		}

		bwf_schedule_recurring_action( time(), 60, 'bwfcrm_broadcast_run_queue', array(), 'bwfcrm' );
		self::ping_woofunnels_worker();
	}

	public static function ping_woofunnels_worker() {
		$url  = rest_url( '/woofunnels/v1/worker' ) . '?' . time();
		$args = array(
			'method'    => 'GET',
			'body'      => array(),
			'timeout'   => 0.01,
			'sslverify' => false,
		);

		wp_remote_post( $url, $args );
	}

	/**
	 * @param $user_login
	 * @param $user WP_USER
	 *
	 * @return void
	 */
	public static function save_last_login( $user_login = false, $user = false ) {
		$user = self::get_user( $user_login, $user );
		if ( empty( $user ) || ! $user instanceof WP_User ) {
			return;
		}

		$contact = new WooFunnels_Contact( '', $user->user_email );
		if ( empty( $contact->get_id() ) ) {
			return;
		}

		/** Set contact uid in cookie */
		if ( ! empty( $contact->get_uid() ) ) {
			BWFAN_Common::set_cookie( '_fk_contact_uid', $contact->get_uid(), time() + 10 * 365 * 24 * 60 * 60 );
		}

		/** Save last login */
		add_action( 'shutdown', function () use ( $contact ) {
			$field_id = BWFCRM_Fields::get_field_id_by_slug( 'last-login' );
			if ( empty( $field_id ) ) {
				return;
			}
			BWF_Model_Contact_Fields::update( [ 'f' . $field_id => current_time( 'mysql' ) ], array( 'cid' => $contact->get_id() ) );
		} );
	}

	public static function get_user( $user_login = false, $user = false ) {
		if ( method_exists( 'BWFAN_Common', 'get_user' ) ) {
			/** Checks if function exists in lite */
			return BWFAN_Common::get_user( $user_login, $user );
		}

		if ( ! empty( $user ) && $user instanceof WP_User ) {
			return $user;
		}

		if ( ! empty( $user_login ) ) {
			$user = get_user_by( 'login', $user_login );
			if ( false === $user ) {
				$user = get_user_by( 'email', $user_login );
			}
			if ( ! empty( $user ) && $user instanceof WP_User ) {
				return $user;
			}
		}
		if ( is_user_logged_in() ) {
			return wp_get_current_user();
		}

		return false;
	}

	public static function get_contact_by_email_or_phone( $email_or_phone ) {
		global $wpdb;
		$sql     = $wpdb->prepare( "SELECT * from {$wpdb->prefix}bwf_contact WHERE email=%s OR contact_no=%s LIMIT 0,1", $email_or_phone, $email_or_phone );
		$contact = $wpdb->get_row( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $contact ) ) {
			return false;
		}

		$contact = new BWFCRM_Contact( $contact );
		if ( ! $contact->is_contact_exists() ) {
			return false;
		}

		return $contact;
	}

	public static function send_sms( $args ) {
		/** Make sure there is atleast 1 active connector */
		$active_services = BWFAN_Common::get_sms_services();
		if ( empty( $active_services ) ) {
			return new WP_Error( 'connector_not_found', __( 'No active SMS service (connector) available.', 'wp-marketing-automations-pro' ) );
		}

		// fetching provider for test sms
		$provider = isset( $args['sms_provider'] ) ? $args['sms_provider'] : '';

		if ( empty( $provider ) ) {
			$global_settings = BWFAN_Common::get_global_settings();
			$provider        = isset( $global_settings['bwfan_sms_service'] ) && ! empty( $global_settings['bwfan_sms_service'] ) ? $global_settings['bwfan_sms_service'] : BWFAN_Common::get_default_sms_provider();
		}

		/** If no provider selected OR selected provider is not within active providers, then select default provider */
		if ( empty( $provider ) || ! isset( $active_services[ $provider ] ) ) {
			$provider = BWFAN_Common::get_default_sms_provider();
		}

		$provider = explode( 'bwfco_', $provider );
		$provider = isset( $provider[1] ) ? $provider[1] : '';
		$provider = ! empty( $provider ) ? BWFAN_Core()->integration->get_integration( $provider ) : '';
		if ( ! $provider instanceof BWFAN_Integration || ! method_exists( $provider, 'send_message' ) ) {
			return new WP_Error( 'connector_not_found', __( 'Connector Integration not found', 'wp-marketing-automations-pro' ) );
		}

		return $provider->send_message( $args );
	}

	public static function get_sms_provider_integration() {
		/** Make sure there is atleast 1 active connector */
		$active_services = BWFAN_Common::get_sms_services();
		if ( empty( $active_services ) ) {
			return new WP_Error( 'connector_not_found', __( 'No active SMS service (connector) available.', 'wp-marketing-automations-pro' ) );
		}

		$global_settings = BWFAN_Common::get_global_settings();
		$provider        = isset( $global_settings['bwfan_sms_service'] ) && ! empty( $global_settings['bwfan_sms_service'] ) ? $global_settings['bwfan_sms_service'] : BWFAN_Common::get_default_sms_provider();

		/** If no provider selected OR selected provider is not within active providers, then select default provider */
		if ( empty( $provider ) || ! isset( $active_services[ $provider ] ) ) {
			$provider = BWFAN_Common::get_default_sms_provider();
		}

		$provider = explode( 'bwfco_', $provider );
		$provider = isset( $provider[1] ) ? $provider[1] : '';
		$provider = ! empty( $provider ) ? BWFAN_Core()->integration->get_integration( $provider ) : '';
		if ( ! $provider instanceof BWFAN_Integration || ! method_exists( $provider, 'send_message' ) ) {
			return new WP_Error( 'connector_not_found', __( 'Connector Integration not found', 'wp-marketing-automations-pro' ) );
		}

		return $provider;
	}


	public static function get_sms_provider_slug() {
		$provider = BWFCRM_Common::get_sms_provider_integration();
		if ( is_wp_error( $provider ) ) {
			return false;
		}

		return $provider->get_connector_slug();
	}

	/**
	 * Remove filter before sending mail
	 */
	public static function bwf_remove_filter_before_wp_mail() {
		remove_all_filters( 'wp_mail_from' );
		remove_all_filters( 'pre_wp_mail' );
		remove_all_filters( 'wp_mail_from_name' );
		remove_all_filters( 'wp_mail_content_type' );
		remove_all_filters( 'wp_mail_charset' );
	}

	/** update contact email on update of user email
	 *
	 * @param $user_id
	 * @param $old_user_data
	 */
	public static function maybe_get_new_user_email( $user_id, $old_user_email ) {
		$user           = get_userdata( $user_id );
		$new_user_email = $user->user_email;

		if ( $new_user_email === $old_user_email ) {
			return false;
		}

		$new_email_contact = new BWFCRM_contact( $new_user_email );
		if ( $new_email_contact->is_contact_exists() ) {
			return false;
		}

		return $new_user_email;
	}

	/**
	 * Trigger contact subscribed event when deleting unsubscribes from list
	 *
	 * @param $data
	 */
	public static function maybe_subscribe_contact( $data ) {
		global $wpdb;

		/** @var BWFCRM_Contact $contact */
		$contact = self::get_contact_by_email_or_phone( $data['recipient'] );
		if ( ! $contact->is_contact_exists() ) {
			return;
		}

		/** If contact status 0(unverified) and 2(bounced)  */
		if ( 1 !== absint( $contact->contact->get_status() ) ) {
			return;
		}

		$search = $contact->contact->get_email();
		if ( is_email( $data['recipient'] ) ) {
			$search = $contact->contact->get_contact_no();
		}

		/** Get unsubscribe ids if contact's email or phone available in unsubscribe table*/
		$query        = $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}bwfan_message_unsubscribe WHERE recipient=%s", $search );
		$unsubscribed = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( count( $unsubscribed ) > 0 ) {
			return;
		}

		if ( ! did_action( 'bwfcrm_after_contact_subscribed' ) ) {
			do_action( 'bwfcrm_after_contact_subscribed', $contact->contact );
		}
	}

	public static function emogrify_rich_text( $email_body ) {
		$email_body = self::add_body_attrs( $email_body );

		BWFCRM_Core()->conversation->include_email_merge_tags_templates();

		ob_start();
		include BWFAN_PLUGIN_DIR . '/templates/email-styles.php';
		$css = ob_get_clean();

		$email_body = self::emogrifier_parsed_output( $css, $email_body );

		return $email_body;
	}

	public static function emogrify_html( $email_body ) {
		$email_body = self::add_body_attrs( $email_body );

		BWFCRM_Core()->conversation->include_email_merge_tags_templates();

		ob_start();
		include BWFAN_PLUGIN_DIR . '/templates/email-editor-styles.php';
		$css = ob_get_clean();

		$email_body = self::emogrifier_parsed_output( $css, $email_body );

		return $email_body;
	}

	public static function emogrifier_parsed_output( $css, $email_body ) {
		if ( empty( $email_body ) || empty( $css ) ) {
			return $email_body;
		}

		if ( ! BWFAN_Common::supports_emogrifier() ) {
			$email_body = '<style type="text/css">' . $css . '</style>' . $email_body;

			return $email_body;
		}

		$emogrifier_class = '\\BWF_Pelago\\Emogrifier';
		if ( ! class_exists( $emogrifier_class ) ) {
			include_once BWFAN_PLUGIN_DIR . '/libraries/class-emogrifier.php';
		}
		try {
			/** @var \BWF_Pelago\Emogrifier $emogrifier */
			$emogrifier = new $emogrifier_class( $email_body, $css );
			$email_body = $emogrifier->emogrify();
		} catch ( Exception $e ) {
			BWFAN_Core()->logger->log( $e->getMessage(), 'send_email_emogrifier' );
		}

		return $email_body;
	}

	public static function add_body_attrs( $content ) {
		$has_body = stripos( $content, '<body' ) !== false;

		/** Check if body tag exists */
		if ( ! $has_body ) {
			return '<html><head></head><body><div id="body_content">' . $content . '</div></body></html>';
		}

		$pattern     = '/<body(.*?)>(.*?)<\/body>/is';
		$replacement = '<body$1><div id="body_content">$2</div></body>';

		return preg_replace( $pattern, $replacement, $content );
	}

	public static function get_or_create_contact_from_user( $user_id ) {
		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user instanceof WP_User ) {
			return false;
		}

		$email   = $user->user_email;
		$contact = new WooFunnels_Contact( absint( $user_id ), $email );
		if ( $contact->get_id() > 0 ) {
			return new BWFCRM_Contact( $contact );
		}

		/**
		 * Form contact data from user data
		 */
		$data = array(
			'f_name' => $user->first_name,
			'l_name' => $user->last_name,
			'status' => 1,
			'wp_id'  => $user->ID,
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
		$email          = ! is_email( $email ) ? get_user_meta( $user->ID, 'billing_email', true ) : $email;

		$contact_fields = BWFCRM_Fields::get_contact_fields_from_db( 'slug' );

		! empty( $postcode ) ? $data[ $contact_fields['postcode']['ID'] ] = $postcode : null;
		! empty( $address1 ) ? $data[ $contact_fields['address-1']['ID'] ] = $address1 : null;
		! empty( $address2 ) ? $data[ $contact_fields['address-2']['ID'] ] = $address2 : null;
		! empty( $company ) ? $data[ $contact_fields['company']['ID'] ] = $company : null;
		! empty( $city ) ? $data[ $contact_fields['city']['ID'] ] = $city : null;

		! empty( $phone ) ? $data['contact_no'] = $phone : null;
		! empty( $state ) ? $data['state'] = $state : null;
		! empty( $country ) ? $data['country'] = BWFAN_PRO_Common::get_country_iso_code( $country ) : null;

		return new BWFCRM_Contact( $email, true, $data );
	}

	public static function is_wlm_integration_active() {
		if ( ! function_exists( 'bwfan_is_wlm_active' ) || ! bwfan_is_wlm_active() ) {
			return false;
		}

		$imported = get_option( 'bwfan_import_done', [] );

		return ( isset( $imported['wlm'] ) && 1 === absint( $imported['wlm'] ) );
	}

	public static function is_affwp_integration_active() {
		if ( ! function_exists( 'bwfan_is_affiliatewp_active' ) || ! bwfan_is_affiliatewp_active() ) {
			return false;
		}

		$imported = get_option( 'bwfan_import_done', [] );

		return ( isset( $imported['affwp'] ) && 1 === absint( $imported['affwp'] ) );
	}

	public static function get_current_user_crm_object() {
		$user = wp_get_current_user();
		if ( ! $user instanceof WP_USER || 0 === $user->ID ) {
			return false;
		}

		/** get user email */
		$user_email = $user->user_email;

		$contact = new WooFunnels_Contact( $user->ID, $user_email );
		if ( $contact instanceof WooFunnels_Contact && $contact->get_id() > 0 ) {
			return $contact;
		}

		return false;
	}

	public static function get_contact_export_per_call_time() {
		if ( defined( 'BWFCRM_CONTACTS_EXPORT_AS_CALL_SECONDS' ) ) {
			return absint( BWFCRM_CONTACTS_EXPORT_AS_CALL_SECONDS );
		}

		return apply_filters( 'bwfan_as_per_call_time', 30 );
	}

	/**
	 * Get the latest customer note for an order.
	 *
	 * @param $order_id
	 *
	 * @return string|null
	 */
	public static function get_latest_customer_note( $order_id = 0 ) {
		if ( empty( $order_id ) ) {
			return null;
		}
		// Arguments to fetch the latest customer note
		$args = array(
			'post_id'    => $order_id,
			'orderby'    => 'comment_ID',
			'order'      => 'DESC',
			'approve'    => 'approve',
			'type'       => 'order_note',
			'meta_query' => array(
				array(
					'key'     => 'is_customer_note',
					'value'   => '1',
					'compare' => '='
				)
			),
			'number'     => 1
		);

		// Temporarily remove the filter that excludes order comments
		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		// Fetch the comments based on the arguments
		$notes = get_comments( $args );

		// Restore the filter
		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		// Check if a note exists and return its content
		if ( empty( $notes ) ) {
			// Return null if no customer note is found
			return null;
		}

		return $notes[0]->comment_content;
	}


	/**
	 * Update contacts last sent field
	 *
	 * @param $date
	 * @param $contact_ids
	 * @param $column
	 *
	 * @return bool|int|mysqli_result|null
	 */
	public static function update_contact_field( $date, $contact_ids, $column ) {
		if ( empty( $contact_ids ) || ! is_array( $contact_ids ) ) {
			return false;
		}
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $contact_ids ), '%d' ) );
		$args         = array_merge( [ $date ], $contact_ids );

		/** Add date in args for checking last sent value (it should be less than from given date) */
		$args[] = $date;
		$sql    = $wpdb->prepare( "UPDATE {$wpdb->prefix}bwf_contact_fields SET {$column} = %s WHERE cid IN ($placeholders) AND {$column} < %s", $args );

		return $wpdb->query( $sql );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
	}

	/**
	 * Insert contact field rows
	 *
	 * @param $cids
	 * @param $column
	 * @param $value
	 *
	 * @return void
	 */
	public static function insert_contact_field( $cids, $column, $value ) {
		if ( empty( $cids ) || ! is_array( $cids ) ) {
			return;
		}

		$data         = [];
		$placeholders = [];
		foreach ( $cids as $cid ) {
			$data[]         = $cid;
			$data[]         = $value;
			$placeholders[] = "(%d, %s)";
		}

		global $wpdb;

		$query = "INSERT INTO {$wpdb->prefix}bwf_contact_fields (`cid`, `{$column}`) VALUES " . implode( ', ', $placeholders );
		$wpdb->query( $wpdb->prepare( $query, $data ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
	}

	/**
	 * Get contact field table rows by contact ids
	 *
	 * @param $contact_ids
	 *
	 * @return array|object|stdClass|null
	 */
	public static function get_contact_field_rows( $contact_ids ) {
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $contact_ids ), '%d' ) );
		$query        = $wpdb->prepare( "SELECT `cid` FROM {$wpdb->prefix}bwf_contact_fields WHERE `cid` IN ($placeholders)", $contact_ids );

		return $wpdb->get_col( $query );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
	}

	/**
	 * Update last sent of contact
	 *
	 * @param $broadcast_id
	 * @param $delete_key
	 *
	 * @return void
	 */
	public static function update_last_sent( $broadcast_id, $delete_key = true ) {
		$last_sent_dates = bwf_options_get( 'broadcast_last_sent_offset_' . $broadcast_id, '', 0 );
		$batch_size      = 100;
		$start_time      = time();

		/** Get field id */
		$field  = BWFAN_Model_Fields::get_field_by_slug( 'last-sent' );
		$column = 'f' . $field['ID'];
		while ( ( time() - $start_time ) < 10 && ! BWFCRM_Common::memory_exceeded() ) {
			$engagements = BWFCRM_Campaigns::get_broadcast_engagements( $broadcast_id, $batch_size, $last_sent_dates );
			if ( empty( $engagements ) ) {
				if ( true === $delete_key ) {
					bwf_options_delete( 'broadcast_last_sent_offset_' . $broadcast_id );
				}
				bwf_unschedule_actions( 'bwfan_broadcast_last_sent', array( $broadcast_id, $delete_key ), 'bwfcrm' );

				return;
			}

			foreach ( $engagements as $engagement ) {
				$created_at = $engagement['created_at'];
				try {
					$dateTime = new DateTime( $created_at );
					$date     = $dateTime->format( 'Y-m-d' );
				} catch ( Exception $e ) {
					$date = $created_at;
				}

				$contact_ids = $engagement['cids'];
				if ( ! empty( $contact_ids ) ) {
					$contact_ids    = explode( ',', $contact_ids );
					$rows_to_update = BWFCRM_Common::get_contact_field_rows( $contact_ids );
					self::update_contact_field( $date, $rows_to_update, $column );
					$rows_to_insert = array_diff( $contact_ids, $rows_to_update );
					if ( count( $rows_to_insert ) > 0 ) {
						sort( $rows_to_insert );
						self::insert_contact_field( $rows_to_insert, $column, $date );
					}
				}
				$last_sent_dates = $created_at;
			}
		}
		bwf_options_update( 'broadcast_last_sent_offset_' . $broadcast_id, $last_sent_dates );
	}

	/**
	 * @param $contact_data
	 *
	 * @return array
	 */
	public static function get_unsubscribed_recipients( $contact_data ) {
		global $wpdb;

		if ( empty( $contact_data ) ) {
			return [];
		}

		$recipients = isset( $contact_data['contacts'] ) ? array_merge( array_column( $contact_data['contacts'], 'email' ), array_column( $contact_data['contacts'], 'contact_no' ) ) : [
			$contact_data['contact']['db_contact']['email'] ?? '',
			$contact_data['contact']['db_contact']['contact_no'] ?? ''
		];

		$recipients = array_unique( array_filter( $recipients ) );

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		return empty( $recipients ) ? [] : $wpdb->get_col( $wpdb->prepare( "SELECT recipient FROM {$wpdb->prefix}bwfan_message_unsubscribe WHERE recipient IN (" . implode( ',', array_fill( 0, count( $recipients ), '%s' ) ) . ")", $recipients ) );
	}

	/**
	 * @param $contact_data
	 * @param $unsubscribed
	 *
	 * @return void
	 */
	public static function update_contact_status( &$contact_data, $unsubscribed ) {
		if ( empty( $unsubscribed ) ) {
			return;
		}
		if ( isset( $contact_data['contacts'] ) ) {
			$contact_data['contacts'] = array_map( function ( $contact ) use ( $unsubscribed ) {
				$email      = $contact['email'] ?? '';
				$contact_no = $contact['contact_no'] ?? '';

				if ( in_array( $email, $unsubscribed, true ) || in_array( $contact_no, $unsubscribed, true ) ) {
					$contact['status'] = 3;
				}

				return $contact;
			}, $contact_data['contacts'] );
		}
		$email      = $contact_data['contact']['db_contact']['email'] ?? '';
		$contact_no = $contact_data['contact']['db_contact']['contact_no'] ?? '';
		if ( in_array( $email, $unsubscribed, true ) || in_array( $contact_no, $unsubscribed, true ) ) {
			$contact_data['contact']['db_contact']['status'] = 3;
			$contact_data['contact']['status']               = 3;
		}
	}

	/**
	 * @param $data
	 * @param array $format
	 *
	 * @return bool|int|mysqli_result|null
	 */
	public static function insert_engagement( $data, $format = null ) {
		if ( method_exists( 'BWFAN_Model_Engagement_Tracking', 'insert_ignore' ) ) {
			return BWFAN_Model_Engagement_Tracking::insert_ignore( $data, $format );
		}

		return self::insert_ignore( 'bwfan_engagement_tracking', $data, $format );
	}

	/**
	 * @param $table
	 * @param $data
	 * @param $format
	 *
	 * @return bool|int|mysqli_result|null
	 */
	public static function insert_ignore( $table, $data, $format = null ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return false;
		}

		// Validate format if provided
		if ( ! is_null( $format ) && count( $format ) !== count( $data ) ) {
			$format = null; // Reset format if it doesn't match data count
		}
		$placeholders = is_null( $format ) ? array_fill( 0, count( $data ), '%s' ) : $format;
		$columns      = array_keys( $data );
		global $wpdb;
		$table = $wpdb->prefix . $table;

		$sql = "INSERT IGNORE INTO `$table` (`" . implode( '`,`', $columns ) . "`) VALUES (" . implode( ',', $placeholders ) . ")";

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $wpdb->prepare( $sql, array_values( $data ) ) );
		if ( ! empty( $result ) ) {
			return $result;
		}

		/** If duplicate entry DB error come */
		if ( 0 === $result ) {
			$warnings = $wpdb->get_results( "SHOW WARNINGS", ARRAY_A );//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! empty( $warnings ) ) {
				foreach ( $warnings as $warning ) {
					if ( empty( $warning['Message'] ) || false === strpos( $warning['Message'], 'Duplicate entry' ) ) {
						continue;
					}
					BWFAN_Common::log_test_data( 'WP db error in ' . $table . ' : ' . $warning['Message'], 'fka-db-duplicate-error', true );
					BWFAN_Common::log_test_data( $data, 'fka-db-duplicate-error', true );
				}
			}
		}

		return false;
	}

	/**
	 * Change the old log file name
	 *
	 * @return void
	 */
	public static function bwfcrm_change_log_file_name() {
		$offset     = intval( get_option( 'bwfan_log_file_offset', 0 ) );
		$type       = intval( get_option( 'bwfan_log_file_type', 1 ) );
		$start_time = time();
		$batch_size = 20;
		global $wpdb;
		while ( ( time() - $start_time ) <= 10 && ! BWFCRM_Common::memory_exceeded() ) {
			$table = ( 2 === $type ) ? "{$wpdb->prefix}bwfan_bulk_action" : "{$wpdb->prefix}bwfan_import_export";
			$args  = [ $offset, '%"log_file"%' ];

			$type_query = '';
			$type_col   = ', `created_at`';
			if ( 1 === $type ) {
				$type_query = "OR (`meta` LIKE %s AND `type` = 2)";
				$type_col   = ",`type`, `created_date` AS `created_at`";
				$args[]     = '%"file"%';
			}
			$args[] = $batch_size;
			$query  = "SELECT `id`, `meta` {$type_col} FROM {$table} WHERE `id` > %d  AND (`meta` LIKE %s $type_query) LIMIT %d";
			$query  = $wpdb->prepare( $query, $args );

			$data = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
			if ( empty( $data ) ) {
				if ( 1 === $type ) {
					update_option( 'bwfan_log_file_type', 2 );
					update_option( 'bwfan_log_file_offset', 0 );
					$type   = 2;
					$offset = 0;
					continue;
				}

				bwf_unschedule_actions( 'bwfcrm_change_log_file_name', array(), 'bwfcrm' );
				delete_option( 'bwfan_log_file_offset' );
				delete_option( 'bwfan_log_file_type' );

				return;
			}

			foreach ( $data as $row ) {
				if ( ( time() - $start_time ) > 10 ) {
					return;
				}
				$id                = $row['id'];
				$created_at        = $row['created_at'] ?? '';
				$meta              = json_decode( $row['meta'], true );
				$is_contact_export = isset( $row['type'] ) && 2 === intval( $row['type'] );
				$old_name          = $meta['log_file'] ?? '';
				$file_path         = ( 2 === $type ) ? BWFCRM_BULK_ACTION_LOG_DIR . '/' : '';
				if ( $is_contact_export ) {
					$old_name  = $meta['file'] ?? '';
					$file_path = BWFCRM_EXPORT_DIR . '/';
				}
				$old_path = $file_path . $old_name;

				if ( empty( $old_path ) || ! file_exists( $old_path ) ) {
					$offset = $id;
					continue;
				}

				try {
					$created_at = ! empty( $created_at ) ? strtotime( $created_at ) : time();
				} catch ( Exception|Error $e ) {
					$created_at = time();
				}

				switch ( true ) {
					case 2 === $type:
						/** Bulk Action */ $file_name = 'FKA-Bulk-Action-' . $created_at . '-' . wp_generate_password( 5, false ) . '-log.csv';
						$meta['log_file']             = $file_name;
						break;
					case $is_contact_export:
						/** Contact export */ $file_name = 'FKA-Contacts-Export-' . $created_at . '-' . wp_generate_password( 5, false ) . '.csv';
						$meta['file']                    = $file_name;
						break;
					default:
						/** Contact import */ $file_name = isset( $meta['import_type'] ) ? $meta['import_type'] . '-import-log-' . $created_at . '-' . wp_generate_password( 5, false ) . '.csv' : '';
						$file_path                       = empty( $file_path ) ? BWFCRM_IMPORT_DIR . '/' : $file_path;
						$meta['log_file']                = $file_name;
				}

				if ( empty( $file_name ) ) {
					continue;
				}
				$new_path = $file_path . $file_name;
				rename( $old_path, $new_path );

				$wpdb->update( $table, [ 'meta' => json_encode( $meta ) ], [ 'id' => $id ] ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
				$offset = $id;
			}
			update_option( 'bwfan_log_file_offset', $offset );
		}
	}

	/**
	 * Get the failed email log
	 *
	 * @param $wp_error WP_Error
	 *
	 * @return void
	 */
	public static function email_failed( $wp_error ) {
		if ( ! is_wp_error( $wp_error ) ) {
			return;
		}

		$log = $wp_error->get_error_message( 'wp_mail_failed' );
		if ( ! empty( $log ) ) {
			self::$captured_email_failed_message = $log;
		}
	}
}

BWFCRM_Common::init();
