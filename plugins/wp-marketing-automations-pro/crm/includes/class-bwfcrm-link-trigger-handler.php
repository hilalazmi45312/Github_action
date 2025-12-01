<?php

/**
 * BWFCRM_Actions_Handler
 */
#[AllowDynamicProperties]
class BWFCRM_Link_Trigger_Handler {
	/**
	 * Class instance
	 */
	private static $ins = null;

	protected $contact_obj;
	protected $link_id = 0;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->contact_obj = new stdClass();

		/** Action to handle link trigger click */
		add_action( 'wp_loaded', array( $this, 'handle_link_trigger_click' ), 101 );

		/** Check for link trigger URL */
		add_filter( 'bwfan_is_link_trigger_url', array( $this, 'is_link_trigger_url' ), 10, 2 );

		/** Handle schedule action  */
		add_action( 'bwfcrm_run_link_trigger_async', array( $this, 'link_trigger_async_cb' ), 10, 2 );

		/** Returns contacts link trigger data */
		add_action( 'bwfan_get_contact_field_by_slug', array( $this, 'link_trigger_contact_data' ), 10, 3 );
	}

	/**
	 * Returns class instance
	 *
	 * @return BWFCRM_Link_Trigger_Handler|null
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Handling link trigger click
	 */
	public function handle_link_trigger_click() {
		/** If link trigger is not set, return */
		if ( ! isset( $_GET['bwfan-link-trigger'] ) || empty( $_GET['bwfan-link-trigger'] ) ) {
			return;
		}

		/** Link data */
		$link_hash  = filter_input( INPUT_GET, 'bwfan-link-trigger' );
		$link_data  = BWFAN_Model_Link_Triggers::bwfan_get_link_trigger( 0, $link_hash );
		$target_url = isset( $link_data['data']['redirect_url'] ) ? $link_data['data']['redirect_url'] : '';

		/** Record engagement tracking */
		$this->trigger_engagement_click( $target_url );

		/** Link Trigger is inactive or no target URL defined */
		if ( ! isset( $link_data['status'] ) || $link_data['status'] != 2 || empty( $target_url ) ) {
			return;
		}

		/** @var WooFunnels_Contact $db_contact */
		$db_contact = $this->get_contact_obj();

		/** Set object cache */
		if ( $db_contact instanceof WooFunnels_Contact ) {
			$this->contact_obj = $db_contact;
		}

		/** Primary key */
		$link_id = isset( $link_data['ID'] ) ? $link_data['ID'] : 0;

		$run_async = apply_filters( 'bwfan_link_trigger_async', true, $link_id, $link_data );

		/** Check for sync option */
		if ( true === $run_async ) {
			/** Schedule link trigger action */
			$this->schedule_link_trigger_async( $link_id );
		} else {
			/** Direct process link trigger */
			$this->link_trigger_processing( $link_id, $link_data, $db_contact );
		}

		/** Update click count */
		$this->update_link_clicks( $link_id, $link_data );

		/** If URL contains merge tag */
		if ( true === $this->url_contains_merge_tag( $target_url ) ) {

			/** Get the Contact object from object cache */
			$db_contact = $this->contact_obj;
			if ( ! $db_contact instanceof WooFunnels_Contact ) {
				$db_contact = $this->get_contact_obj();

				/** Set object cache */
				if ( $db_contact instanceof WooFunnels_Contact ) {
					$this->contact_obj = $db_contact;
				}
			}

			if ( $db_contact instanceof WooFunnels_Contact && $db_contact->get_id() > 0 ) {
				/** Set contact Id for merge tag */
				BWFAN_Merge_Tag_Loader::set_data( array(
					'contact_id'   => $db_contact->get_id(),
					'contact_mode' => 1
				) );

				/** Decode target URL merge tags */
				$target_url = BWFAN_Common::decode_merge_tags( $target_url );
			}
		}

		/** Link trigger might have auto login enabled */
		$this->maybe_login( $link_data );

		do_action( 'bwfan_before_redirect_link_trigger', $link_data );

		/** Redirect to target url */
		BWFAN_PRO_Common::wp_redirect( $this->may_append_query_arg( $target_url ) );
		exit;
	}

	/**
	 * Record engagement conversation click
	 *
	 * @param $target_url
	 */
	protected function trigger_engagement_click( $target_url ) {
		$track_id = filter_input( INPUT_GET, 'bwfan-track-id' );
		if ( empty( $track_id ) ) {
			return;
		}
		$engagement_ins = BWFAN_Email_Conversations::get_instance();
		$engagement_ins->track_click_skip( $target_url );
	}

	/**
	 * Schedule link trigger action
	 *
	 * @param $link_id
	 */
	protected function schedule_link_trigger_async( $link_id ) {
		$contact_data = $this->get_contact_id();
		if ( empty( $contact_data ) ) {
			return;
		}

		$args = array(
			'contact' => $contact_data,
			'link_id' => $link_id,
		);

		/** Schedule if not scheduled */
		if ( ! bwf_has_action_scheduled( 'bwfcrm_run_link_trigger_async', $args, 'autonami-link-trigger' ) ) {
			bwf_schedule_single_action( time(), 'bwfcrm_run_link_trigger_async', $args, 'autonami-link-trigger' );
		}
	}

	/**
	 * Callback of async event
	 */
	public function link_trigger_async_cb( $contact_data, $link_id ) {
		/** CRM contact object */
		$contact = $this->get_contact_obj( $contact_data );

		/** Get link data */
		$link_data = BWFAN_Model_Link_Triggers::bwfan_get_link_trigger( $link_id );

		/** Process link trigger */
		$this->link_trigger_processing( $link_id, $link_data, $contact );
	}

	/**
	 * Perform link trigger actions
	 *
	 * @param $link_id
	 * @param $link_data
	 * @param $db_contact
	 */
	protected function link_trigger_processing( $link_id, $link_data, $db_contact ) {
		/** Actions to run */
		$actions = ( isset( $link_data['data']['actions'] ) && count( $link_data['data']['actions'] ) > 0 ) ? $link_data['data']['actions'] : array();

		/** No contact found, just redirect. */
		if ( ! $db_contact instanceof WooFunnels_Contact || 0 === $db_contact->get_id() ) {
			return;
		}

		/** CRM contact object */
		$contact = new BWFCRM_Contact( $db_contact );

		/** Set cookie */
		$uid = $db_contact->get_uid();
		if ( ! empty( $uid ) ) {
			BWFAN_Common::set_cookie( '_fk_contact_uid', $uid, time() + 10 * 365 * 24 * 60 * 60 );
		}

		/** Trigger hook - link trigger clicked */
		do_action( 'bwfan_link_triggered', $link_id, $contact );

		/** Get contact link triggered array */
		$contact_link_clicked = $contact->get_field_by_slug( 'link-trigger-click' );
		$contact_clicked_ids  = json_decode( $contact_link_clicked, true );
		$contact_clicked_ids  = ( ! empty( $contact_clicked_ids ) && is_array( $contact_clicked_ids ) ) ? $contact_clicked_ids : array();

		if ( empty( $actions ) ) {
			/** Update Contact link click */
			$this->update_contact_link_click( $contact, $link_id, $contact_clicked_ids );

			return;
		}

		/** Check Action runs times */
		$actions_run_time = isset( $link_data['data']['action_run'] ) ? $link_data['data']['action_run'] : 'once';

		/** Filter to run link trigger action multiple times ( by default false ) */
		if ( apply_filters( 'bwfan_link_trigger_multi_execution', false ) ) {
			$actions_run_time = 'multiple';
		}

		/** Clicked multiple times, just redirect */
		if ( $actions_run_time == 'once' && in_array( $link_id, $contact_clicked_ids ) ) {
			return;
		}
		$this->link_id = $link_id;

		/** Run actions */
		$logs = $this->process_actions( $actions, $contact );

		/** Maybe create logs contact note */
		$this->create_contact_logs_note( $logs, $contact, $link_data );

		/** Update Contact link click */
		$this->update_contact_link_click( $contact, $link_id, $contact_clicked_ids );
	}

	/**
	 * Updated link trigger field for contact
	 *
	 * @param array $logs
	 * @param \BWFCRM_Contact $contact
	 * @param array $link_data
	 */
	protected function create_contact_logs_note( $logs, $contact, $link_data ) {
		if ( ! isset( $link_data['data']['add_contact_note'] ) || 1 !== absint( $link_data['data']['add_contact_note'] ) || empty( $logs ) ) {
			return;
		}

		$title = 'Link Trigger "' . $link_data['title'] . '" logs';
		$body  = is_array( $logs ) ? implode( ' | ', $logs ) : $logs;

		new BWFCRM_Note( 0, true, array(
			'cid'   => $contact->get_id(),
			'title' => $title,
			'body'  => $body,
			'type'  => 'log',
		) );
	}

	/**
	 * Updated link trigger field for contact
	 *
	 * @param $contact
	 * @param $link_id
	 * @param array $contact_clicked_ids
	 */
	protected function update_contact_link_click( $contact, $link_id, $contact_clicked_ids = array() ) {
		/** Check if link trigger id already exists */
		if ( ! in_array( $link_id, $contact_clicked_ids ) ) {

			$contact_clicked_ids[] = $link_id;
			$contact_clicked_ids   = wp_json_encode( array_unique( $contact_clicked_ids ) );

			/** Set updated link-trigger-click value */
			$contact->set_field_by_slug( 'link-trigger-click', $contact_clicked_ids );
			$contact->save_fields();
		}
	}

	/**
	 * Get contact Id
	 *
	 * @return array|false
	 */
	protected function get_contact_id() {
		/** check if URL contains contacts uid  */
		$uid = filter_input( INPUT_GET, 'bwfan-uid' );
		if ( ! empty( $uid ) ) {
			return array( 'uid' => $uid );
		}

		/** Check for logged-in user */
		$user = wp_get_current_user();
		if ( $user instanceof WP_User ) {
			return array( 'wpid' => $user->ID );
		}

		return false;
	}

	/**
	 * Get contact obj
	 *
	 * @return stdClass|WooFunnels_Contact
	 */
	protected function get_contact_obj( $data = false ) {
		/** Ready contact data */
		if ( empty( $data ) ) {
			$data = $this->get_contact_id();
		}

		/** Returns if data is empty */
		if ( false === $data ) {
			return new stdClass();
		}

		/** Initialize CRM Object  */
		$bwf_contacts = BWF_Contacts::get_instance();

		/** Get CRM contact object by CRM uid */
		if ( isset( $data['uid'] ) ) {
			return $bwf_contacts->get_contact_by( 'uid', $data['uid'] );
		}

		/** Get CRM contact object by WordPress user id */
		if ( isset( $data['wpid'] ) ) {
			return $bwf_contacts->get_contact_by( 'wpid', $data['wpid'] );
		}

		return new stdClass();
	}

	/**
	 * Process actions
	 *
	 * @param $actions
	 * @param $contact_crm
	 *
	 * @return array|void
	 */
	protected function process_actions( $actions, $contact_crm ) {
		$logs = array();

		/** Check for empty action */
		if ( empty( $actions ) ) {
			return;
		}
		/** Iterate over action */
		foreach ( $actions as $action => $data ) {
			$data = empty( $data ) ? [] : $data;

			/** Initialize Action Object */
			$action_obj = BWFCRM_Core()->actions->get_action_by_slug( $action );

			if ( ! $action_obj instanceof \BWFCRM\Actions\Base ) {
				continue;
			}

			if ( 'end_automation' === $action ) {
				$data['from']    = 'Link Trigger';
				$data['link_id'] = $this->link_id;
			}

			if ( count( $data ) > 0 ) {
				/** Keys has extra spacings, removing that */
				$data = array_combine( array_map( 'trim', array_keys( $data ) ), $data );

				/** In case of status field, set the status id */
				if ( isset( $data['status'] ) ) {
					$status_key     = $this->get_status_key( trim( $data['status'] ) );
					$data['status'] = $status_key;
				}
			}

			/** Call to action handler function */
			$result = $action_obj->handle_action( $contact_crm, $data );

			/** Collect Logs from actions */
			if ( is_array( $result ) && ! empty( $result['message'] ) ) {
				$message = is_array( $result['message'] ) ? $result['message'] : ( empty( $result['message'] ) ? array() : array( $result['message'] ) );
				$logs    = array_values( array_merge( $logs, $message ) );
			}
		}

		return $logs;
	}

	/**
	 * Get status id by label
	 *
	 * @param $label
	 *
	 * @return int|string
	 */
	protected function get_status_key( $label ) {
		$arr = array(
			'0' => 'Unverified',
			'1' => 'Subscribed',
			'3' => 'Unsubscribed',
			'2' => 'Bounced',
			'4' => 'Soft Bounced',
			'5' => 'Complaint',
		);
		if ( method_exists( 'BWFAN_Common', 'get_contact_status_array_list' ) ) {
			$status = BWFAN_Common::get_contact_status_array_list();
			$arr    = [];
			foreach ( $status as $value ) {
				$arr[ $value['id'] ] = $value['name'];
			}
		}

		$data = array_search( $label, $arr );

		return ( false === $data ) ? '0' : $data;
	}

	/**
	 * Update link clicks count
	 *
	 * @param $link_id
	 * @param $link_data
	 */
	protected function update_link_clicks( $link_id, $link_data ) {
		/** Update click count  */
		$clicked = isset( $link_data['total_clicked'] ) ? absint( $link_data['total_clicked'] ) : 0;
		$clicked ++;

		/** Link trigger data */
		$values = array(
			'total_clicked' => $clicked,
			'updated_at'    => current_time( 'mysql', 1 ),
		);

		/** Set condition */
		$where = array(
			'ID' => $link_id,
		);

		/** Update Link Trigger DB data */
		BWFAN_Model_Link_Triggers::update( $values, $where );
	}

	protected function maybe_login( $link_data ) {
		$key = 'enable_auto_login';

		/** If auto login disabled */
		if ( ! isset( $link_data['data'][ $key ] ) || 1 !== absint( $link_data['data'][ $key ] ) ) {
			return;
		}

		/** Get contact object */
		$db_contact = $this->contact_obj;

		if ( ! $db_contact instanceof WooFunnels_Contact ) {
			$db_contact = $this->get_contact_obj();
		}

		/** No contact found */
		if ( ! $db_contact instanceof WooFunnels_Contact || 0 === $db_contact->get_id() ) {
			return;
		}

		/** Is contact associated with WP User */
		$wp_id = $db_contact->get_wpid();
		if ( 0 === absint( $wp_id ) ) {
			return;
		}

		$user = get_user_by( 'ID', $wp_id );
		/** WP User not present */
		if ( ! $user instanceof WP_User ) {
			return;
		}

		/** If user role is admin or editor then return */
		$roles = apply_filters( 'bwfan_disabled_autologin_roles', array( 'administrator', 'editor' ) );
		if ( array_intersect( $roles, (array) $user->roles ) ) {
			return;
		}

		/** Check if expiry time reached */
		$crm_contact = new BWFCRM_Contact( $db_contact );
		$auth_hash   = filter_input( INPUT_GET, 'bwfan-auth' );
		if ( ! $this->verify_auth_hash( $crm_contact, $link_data, $auth_hash ) ) {
			return;
		}

		/** Auto login user */
		wp_set_current_user( $user->ID, $user->user_login );

		/** if WooCommerce is active */
		if ( function_exists( 'bwfan_is_woocommerce_active' ) && bwfan_is_woocommerce_active() ) {
			wc_set_customer_auth_cookie( $user->ID );
		} else {
			wp_set_auth_cookie( $user->ID );
		}
		/** Add Hook  */
		do_action( 'wp_login', $user->user_login, $user );
	}

	/**
	 * Check if URL contains link trigger string
	 *
	 * @param $status
	 * @param $url
	 *
	 * @return bool|mixed
	 */
	public function is_link_trigger_url( $status, $url ) {
		if ( empty( $url ) ) {
			return $status;
		}

		return ( false !== strpos( $url, 'bwfan-link-trigger' ) );
	}

	/**
	 * Check if URL contains merge tag
	 *
	 * @param $url
	 *
	 * @return bool
	 */
	public function url_contains_merge_tag( $url ) {
		return ( false !== strpos( $url, '{{' ) );
	}

	/**
	 * Appends the additional parameters to redirect link
	 *
	 * @param $url
	 *
	 * @return mixed|string
	 */
	public function may_append_query_arg( $url ) {
		$url        = apply_filters( 'bwfan_modify_link_trigger_target_url', $url, $this->contact_obj );
		$args       = filter_input_array( INPUT_GET );
		$filter_arr = [];
		foreach ( $args as $key => $arg ) {
			if ( 'l_hash' === $key ) {
				continue;
			}
			if ( strpos( $key, 'bwfan-' ) === false ) {
				$val = str_replace( '%20', '+', $arg );
				$val = str_replace( ' ', '+', $val );

				$filter_arr[ $key ] = $val;
			}
		}
		if ( empty( $filter_arr ) ) {
			return $url;
		}

		return add_query_arg( $filter_arr, $url );
	}

	/**
	 * @param BWFCRM_Contact $contact
	 * @param string $url
	 *
	 * @return false|string
	 */
	public function get_auth_hash( $contact, $url ) {
		$parsed_url = parse_url( $url );
		parse_str( $parsed_url['query'], $query );
		$link_hash = $query['bwfan-link-trigger'];

		if ( ! $contact instanceof BWFCRM_Contact || ! $contact->is_contact_exists() || empty( $link_hash ) ) {
			return false;
		}

		/** Get Link and Auto Login Data */
		$link = BWFAN_Model_Link_Triggers::bwfan_get_link_trigger( 0, $link_hash );
		if ( ! isset( $link['data']['enable_auto_login'] ) || empty( $link['data']['enable_auto_login'] ) || ! isset( $link['data']['auto_login'] ) ) {
			return false;
		}

		$auto_login = $link['data']['auto_login'];
		if ( empty( $auto_login ) || empty( $auto_login['text'] ) || empty( $auto_login['unit'] ) ) {
			return false;
		}

		/** Get New Expiry time based on the link data */
		$current_time = new DateTime( 'now' );
		$expiry_time  = ( $current_time )->modify( '+' . $auto_login['text'] . ' ' . $auto_login['unit'] );
		$expiry_time  = $expiry_time->getTimestamp();

		/** Populate Auth Field, if empty */
		$auth = $contact->get_field_by_slug( 'auth' );
		if ( ! empty( $auth ) ) {
			$auth = json_decode( $auth, true );
		}

		/** Populate auth hash if empty */
		$current_time = $current_time->getTimestamp();
		$auth_hash    = '';
		if ( ! is_array( $auth ) || 0 === count( $auth ) ) {
			$contact_id = $contact->get_id();
			$auth_hash  = md5( $contact_id . $current_time );
			$auth       = array( $auth_hash => '' );
		} else {
			$auth_hash = array_keys( $auth )[0];
		}

		/** Populate Expiry if empty or Old Expiry is less than New Expiry */
		$old_expiry_time = array_values( $auth )[0];
		if ( empty( $old_expiry_time ) || absint( $old_expiry_time ) < $expiry_time ) {
			$auth[ $auth_hash ] = $expiry_time;
			$contact->set_field_by_slug( 'auth', wp_json_encode( $auth ) );
			$contact->save_fields();
		}

		return $auth_hash;
	}

	public function verify_auth_hash( $contact, $link_data, $auth_hash ) {
		if ( ! $contact instanceof BWFCRM_Contact || ! $contact->is_contact_exists() || empty( $link_data ) || empty( $auth_hash ) ) {
			return false;
		}

		/** Check if auto login enabled */
		if ( 0 === absint( $link_data['data']['enable_auto_login'] ) ) {
			return false;
		}

		/** Get Auth from Contact Field, Check if it is valid */
		$auth = $contact->get_field_by_slug( 'auth' );
		$auth = ! empty( $auth ) ? json_decode( $auth, true ) : $auth;
		if ( ! is_array( $auth ) || 0 === count( $auth ) ) {
			return false;
		}

		/** Verify contact auth against auth hash */
		return ( isset( $auth[ $auth_hash ] ) && ( absint( $auth[ $auth_hash ] ) > time() ) );
	}

	/**
	 * Return contact link trigger field data
	 *
	 * @param $val
	 * @param $field
	 * @param $contact
	 *
	 * @return mixed
	 */
	public function link_trigger_contact_data( $val, $field, $contact ) {
		if ( $field === 'links' ) {
			$val = $contact->get_field_by_slug( 'link-trigger-click' );
			if ( ! empty( $val ) ) {
				$link_triggers = BWFAN_Model_Link_Triggers::get_link_triggers( '', '', 0, 0, false, json_decode( $val, true ) );
				$links         = [];

				if ( isset( $link_triggers['links'] ) && ! empty( $link_triggers['links'] ) ) {
					foreach ( $link_triggers['links'] as $link ) {
						if ( isset( $link['title'] ) ) {
							$links[] = $link['title'];
						}
					}
					$val = implode( ', ', $links );
				}
			}
		}

		return $val;
	}
}

/**
 * Register action handler to BWFCRM_Core
 */
if ( class_exists( 'BWFCRM_Link_Trigger_Handler' ) ) {
	BWFCRM_Core::register( 'link_trigger_handler', 'BWFCRM_Link_Trigger_Handler' );
}
