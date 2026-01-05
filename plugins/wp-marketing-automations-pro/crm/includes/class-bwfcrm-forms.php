<?php

#[AllowDynamicProperties]
class BWFCRM_Form_Controller {

	private $_forms = [];
	/** Initialising names for fallback, in case form plugin not activated */
	private $_nice_names = [
		'elementor'    => 'Elementor',
		'gforms'       => 'Gravity Forms',
		'wpforms'      => 'WP Forms',
		'fluent'       => 'Fluent Forms',
		'funnel_optin' => 'FunnelKit Optin',
		'divi_form'    => 'Divi Forms',
		'formidable'   => 'Formidable',
		'thrive'       => 'Thrive Forms',
		'ninja'        => 'Ninja Forms',
		'breakdance'   => 'Breakdance Forms',
	];
	private $_autonami_events = [];

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_forms' ], 8 );
		add_action( 'bwfan_capture_async_form_submission', array( $this, 'capture_async_form_submission' ) );
		add_action( 'wp', array( $this, 'handle_incentive_email' ), 999 );
	}

	public function load_forms() {
		$integration_dir = __DIR__ . '/forms';
		foreach ( glob( $integration_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once( $_field_filename );
		}

		do_action( 'bwfcrm_forms_loaded' );
	}

	public function register( $slug, $class, $nice_name, $autonami_events ) {
		if ( empty( $slug ) ) {
			return;
		}

		$this->_forms[ $slug ]      = $class;
		$this->_nice_names[ $slug ] = ! empty( $nice_name ) ? $nice_name : ( ! empty( $this->_nice_names[ $slug ] ) ? $this->_nice_names[ $slug ] : $slug );
		if ( is_array( $autonami_events ) && ! empty( $autonami_events ) ) {
			foreach ( $autonami_events as $event ) {
				$this->_autonami_events[ $event ] = $slug;
			}
		}
	}

	public function capture_async_form_submission() {
		$form_data = BWFAN_Common::$events_async_data;
		if ( ! isset( $form_data['event'] ) ) {
			return;
		}

		$form = $this->get_form_slug_from_event( $form_data['event'] );
		if ( false === $form ) {
			return;
		}

		/** @var BWFCRM_Form_Base $form */
		$form = $this->get_form( $form );
		if ( is_null( $form ) ) {
			return;
		}

		$form->capture_async_submission();
	}

	public function get_active_feeds_by_form( $slug ) {
		$feeds = BWFAN_Model_Form_Feeds::get_feeds( array(
			'source' => $slug,
			'status' => BWFCRM_Form_Feed::$STATUS_ACTIVE
		) );

		if ( empty( $feeds ) ) {
			return false;
		}

		return array_filter( array_map( function ( $feed ) {
			if ( ! is_array( $feed ) || ! isset( $feed['ID'] ) || empty( $feed['ID'] ) ) {
				return false;
			}

			$feed = new BWFCRM_Form_Feed( $feed['ID'] );

			return $feed->is_feed_exists() ? $feed : false;
		}, $feeds['feeds'] ) );
	}

	public function maybe_delete_contact_from_feed( $contact_id ) {
		$contact = new BWFCRM_Contact( absint( $contact_id ) );
		if ( ! $contact->is_contact_exists() ) {
			return false;
		}

		$feed_id = $contact->get_field_by_slug( 'form-feed-id' );
		$feed    = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() || 0 === $feed->get_contacts() ) {
			return false;
		}

		$feed->decrement_contacts();

		return ! ! $feed->save();
	}

	public function maybe_delete_contacts_from_feed( $contact_ids, $form_feed_field_id ) {
		foreach ( $contact_ids as $id ) {
			$contact = new BWFCRM_Contact( absint( $id ) );
			if ( ! $contact->is_contact_exists() ) {
				continue;
			}

			if ( ! isset( $contact->fields[ $form_feed_field_id ] ) || empty( $contact->fields[ $form_feed_field_id ] ) ) {
				continue;
			}

			$feed_id = $contact->fields[ $form_feed_field_id ];
			$feed    = new BWFCRM_Form_Feed( $feed_id );
			if ( ! $feed->is_feed_exists() || 0 === $feed->get_contacts() ) {
				continue;
			}

			$feed->decrement_contacts();
			$feed->save();
		}
	}

	/**
	 * @param $slug
	 *
	 * @return |BWFCRM_Form_Base|null
	 */
	public function get_form( $slug ) {
		if ( empty( $slug ) ) {
			return null;
		}

		$form_class = isset( $this->_forms[ $slug ] ) ? $this->_forms[ $slug ] : '';
		if ( empty( $form_class ) || ! class_exists( $form_class ) ) {
			return null;
		}

		$form = new $form_class;

		return $form instanceof BWFCRM_Form_Base ? $form : null;
	}

	public function get_form_slug_from_event( $event ) {
		if ( ! is_array( $this->_autonami_events ) || empty( $this->_autonami_events ) || ! isset( $this->_autonami_events[ $event ] ) ) {
			return false;
		}


		return $this->_autonami_events[ $event ];
	}

	public function get_forms_nice_names() {
		return $this->_nice_names;
	}

	public function get_available_forms() {
		return array_keys( $this->_forms );
	}

	public function get_feeds( $args = array() ) {
		$feeds = BWFAN_Model_Form_Feeds::get_feeds( $args );

		return array(
			'feeds' => $this->get_feeds_array( $feeds['feeds'] ),
			'total' => $feeds['total']
		);
	}

	public function get_feeds_array( $feeds, $slim_data = true ) {
		return array_filter( array_map( function ( $feed ) use ( $slim_data ) {
			if ( ! is_array( $feed ) || ! isset( $feed['ID'] ) ) {
				return false;
			}

			$feed_obj = new BWFCRM_Form_Feed();
			$feed_obj->set_form_feed( $feed['ID'], $feed['title'], $feed['contacts'], $feed['source'], $feed['status'], $feed['data'] );
			$feed_obj->set_db_row( $feed );

			return $feed_obj->get_array( $slim_data );
		}, $feeds ) );
	}

	public function sync_feed_selection( $feed_id, $form = '', $selection = array(), $return_all_options = false ) {
		$feed = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'Feed doesn\'t exists', 'wp-marketing-automations-pro' ) );
		}

		$source = empty( $form ) ? $feed->get_source() : $form;
		$form   = $this->get_form( $source );
		if ( is_null( $form ) ) {
			return BWFCRM_Common::crm_error( __( 'Form source invalid', 'wp-marketing-automations-pro' ) );
		}

		if ( ! is_array( $selection ) ) {
			$selection = [];
		}

		$new_selection = $form->get_form_selection( $selection, $return_all_options );
		$update        = $form->update_form_selection( $selection, $feed->get_id() );
		if ( is_wp_error( $update ) ) {
			return $update;
		}

		return array( 'selection' => $new_selection, 'total' => $form->get_total_selection_steps() );
	}

	public function is_valid_status( $status ) {
		$status = absint( $status );

		return in_array( $status, array(
			BWFCRM_Form_Feed::$STATUS_DRAFT,
			BWFCRM_Form_Feed::$STATUS_ACTIVE,
			BWFCRM_Form_Feed::$STATUS_INACTIVE,
		) );
	}

	public function send_incentive_email( $contact, $feed ) {
		if ( ! $contact instanceof BWFCRM_Contact || ! $contact->is_contact_exists() || ! is_email( $contact->contact->get_email() ) || ! $feed instanceof BWFCRM_Form_Feed ) {
			return false;
		}

		$do_email = $feed->get_data( 'incentivize_email' );
		if ( empty( $do_email ) ) {
			return true;
		}

		/** Check if contacts status is bounced */
		if ( BWFCRM_Contact::$DISPLAY_STATUS_BOUNCED === $contact->get_display_status() ) {
			return false;
		}

		$email = $feed->get_data( 'incentive_email' );
		if ( ! is_array( $email ) || ! isset( $email['content'] ) || ! is_array( $email['content'] ) || ! isset( $email['content'][0] ) || ! is_array( $email['content'][0] ) ) {
			return false;
		}
		$email_content    = $email['content'][0];
		$is_transactional = isset( $email_content['isTransactional'] ) ? $email_content['isTransactional'] : 0;

		/** Check if email is not transactional and contacts status is unsubscribed */
		if ( empty( $is_transactional ) && BWFCRM_Contact::$DISPLAY_STATUS_UNSUBSCRIBED === $contact->get_display_status() ) {
			return false;
		}

		if ( method_exists( 'BWFAN_Common', 'bwfan_before_send_mail' ) ) {
			BWFAN_Common::bwfan_before_send_mail( isset( $email_content['type'] ) ? $email_content['type'] : '' );
		}
		/** Set Merge Tags Data */
		BWFAN_Merge_Tag_Loader::set_data( array(
			'contact_id'   => $contact->contact->get_id(),
			'form_feed_id' => $feed->get_id()
		) );

		$conversation = $this->create_incentive_email_conversation( $contact, $feed, $email['content'][0] );
		if ( ! $conversation instanceof BWFAN_Engagement_Tracking ) {
			return false;
		}

		$email_body    = 'editor' === $email_content['type'] ? $email_content['editor']['body'] : $email_content['body'];
		$email_subject = $email_content['subject'];

		/** @var  $global_email_settings BWFAN_Common settings */
		$global_email_settings = BWFAN_Common::get_global_settings();


		/** Email Subject */
		$email_body      = method_exists( 'BWFAN_Common', 'correct_shortcode_string' ) ? BWFAN_Common::correct_shortcode_string( $email_body, $email['content'][0]['type'] ) : $email_body;
		$subject         = BWFAN_Common::decode_merge_tags( $email_subject );
		$merge_tags_data = [ 'contact_id' => $contact->contact->get_id(), 'form_feed_id' => $feed->get_id() ];
		$merge_tags      = BWFCRM_Core()->merge_tags->fetch_tags_from_string( $email_body, $merge_tags_data );
		$email_body      = BWFAN_Common::replace_merge_tags( $email_body, $merge_tags, $contact->contact->get_id() );
		$email_body      = BWFAN_Common::decode_merge_tags( $email_body );
		/** Email Body */
		$body = BWFAN_Common::bwfan_correct_protocol_url( $email_body );
		$body = BWFCRM_Core()->conversation->apply_template_by_type( $body, $email_content['type'], $subject );

		/** Apply Click tracking and UTM Params */
		$utm_enabled = isset( $email_content['utmEnabled'] ) && 1 === absint( $email_content['utmEnabled'] );
		$utm_data    = $utm_enabled && isset( $email_content['utm'] ) && is_array( $email_content['utm'] ) ? $email_content['utm'] : array();

		$uid = $contact->contact->get_uid();

		/** Set contact object */
		BWFCRM_Core()->conversation->contact         = $contact;
		BWFCRM_Core()->conversation->engagement_type = BWFAN_Email_Conversations::$TYPE_INCENTIVE;
		BWFAN_Core()->conversation->broadcast_id     = $feed->get_id();
		$body                                        = BWFCRM_Core()->conversation->append_to_email_body_links( $body, $utm_data, $conversation->get_hash(), $uid );

		/** Apply Pre-Header and Hash ID (open pixel id) */
		$pre_header = isset( $email_content['preheader'] ) && ! empty( $email_content['preheader'] ) ? $email_content['preheader'] : '';
		$pre_header = BWFAN_Common::decode_merge_tags( $pre_header );

		$body = BWFCRM_Core()->conversation->append_to_email_body( $body, $pre_header, $conversation->get_hash() );

		/** Email Headers */
		$reply_to_email = isset( $email['reply_to'] ) ? $email['reply_to'] : $global_email_settings['bwfan_email_reply_to'];
		$from_email     = isset( $email['from_email'] ) ? $email['from_email'] : $global_email_settings['bwfan_email_from'];
		$from_name      = isset( $email['from_name'] ) ? $email['from_name'] : $global_email_settings['bwfan_email_from_name'];

		/** Setup Headers */
		$header   = array();
		$header[] = 'MIME-Version: 1.0';
		if ( ! empty( $from_email ) && ! empty( $from_name ) ) {
			$header[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		}
		if ( ! empty( $reply_to_email ) ) {
			$header[] = 'Reply-To:  ' . $reply_to_email;
		}
		$header[] = 'Content-type:text/html;charset=UTF-8';

		/** Set unsubscribe link in header */
		$unsubscribe_link = BWFAN_PRO_Common::get_unsubscribe_link( [ 'uid' => $contact->contact->get_uid(), 'form_feed_id' => $feed->get_id() ] );
		if ( ! empty( $unsubscribe_link ) ) {
			$header[] = "List-Unsubscribe: <$unsubscribe_link>";
			$header[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
		}

		/** Removed wp mail filters */
		BWFCRM_Common::bwf_remove_filter_before_wp_mail();
		$header = apply_filters( 'bwfan_email_headers', $header );

		BWFCRM_Common::$captured_email_failed_message = null;

		/** Send the Email */
		$email_status = wp_mail( $contact->contact->get_email(), $subject, $body, $header );

		/** Set the status of Email */
		if ( true === $email_status ) {
			$conversation->set_status( BWFAN_Email_Conversations::$STATUS_SEND );
			$conversation->save();
		} else {
			$conversation->fail_the_conversation();
		}

		return $email_status;
	}

	public function create_incentive_email_conversation( $contact, $feed, $email_content ) {
		if ( ! $contact instanceof BWFCRM_Contact || ! $contact->is_contact_exists() || ! is_email( $contact->contact->get_email() ) || ! $feed instanceof BWFCRM_Form_Feed ) {
			return false;
		}

		if ( ! isset( $email_content['type'] ) ) {
			return false;
		}

		$email_body      = 'editor' === $email_content['type'] ? $email_content['editor']['body'] : $email_content['body'];
		$email_body      = method_exists( 'BWFAN_Common', 'correct_shortcode_string' ) ? BWFAN_Common::correct_shortcode_string( $email_body, $email_content['type'] ) : $email_body;
		$template_type   = BWFAN_PRO_Common::get_email_template_type( $email_content['type'] );
		$email_subject   = $email_content['subject'];
		$template_id     = BWFCRM_Core()->conversation->get_or_create_template( BWFAN_Email_Conversations::$MODE_EMAIL, $email_subject, $email_body, 'id', [ 'template' => $template_type ] );
		$merge_tags_data = [ 'contact_id' => $contact->contact->get_id(), 'form_feed_id' => $feed->get_id() ];

		if ( property_exists( BWFAN_Core()->conversation, 'template_id' ) ) {
			BWFAN_Core()->conversation->template_id = $template_id;
		}

		$conversation = new BWFAN_Engagement_Tracking();
		$conversation->set_oid( $feed->get_id() );
		$conversation->set_mode( BWFAN_Email_Conversations::$MODE_EMAIL );
		$conversation->set_contact( $contact );
		$conversation->set_send_to( $contact->contact->get_email() );
		$conversation->enable_tracking();
		$conversation->set_type( BWFAN_Email_Conversations::$TYPE_INCENTIVE );
		$conversation->set_template_id( $template_id );
		$conversation->set_status( BWFAN_Email_Conversations::$STATUS_DRAFT );
		$conversation->add_merge_tags_from_string( $email_body, $merge_tags_data );
		$conversation->add_merge_tags_from_string( $email_subject, $merge_tags_data );

		if ( ! $conversation->save() ) {
			return false;
		}

		return $conversation;
	}

	/**
	 * handling email incentive
	 */
	public function handle_incentive_email() {
		if ( ! isset( $_GET['bwfan-uid'] ) || empty( $_GET['bwfan-uid'] ) ) {
			return;
		}

		if ( ! isset( $_GET['bwfan-action'] ) || empty( $_GET['bwfan-action'] ) || 'incentive' !== sanitize_text_field( $_GET['bwfan-action'] ) ) {
			return;
		}

		$uid          = $_GET['bwfan-uid'];
		$bwf_contacts = BWF_Contacts::get_instance();
		$dbcontact    = $bwf_contacts->get_contact_by( 'uid', $uid );

		if ( empty( $dbcontact->db_contact ) ) {
			$link = filter_input( INPUT_GET, 'bwfan-link' );
			if ( ! empty( $link ) ) {
				$url = BWFAN_Common::bwfan_correct_protocol_url( $link );
				$url = BWFAN_PRO_Common::validate_target_link( $url );
				if ( false !== wp_http_validate_url( $url ) ) {
					BWFAN_PRO_Common::wp_redirect( $url );
					exit;
				}
			}

			// when no contact found with the uid then redirect to the home url
			wp_safe_redirect( home_url() );
			exit();
		}

		$contact = new BWFCRM_Contact( $dbcontact );

		if ( isset( $_GET['feed-id'] ) && absint( $_GET['feed-id'] ) > 0 ) {
			$feed = new BWFCRM_Form_Feed( absint( $_GET['feed-id'] ) );
			if ( $feed->is_feed_exists() ) {
				$tag_enabled = $feed->get_data( 'add_tag_enable' );
				if ( $tag_enabled ) {
					$tag_to_add = $feed->get_data( 'tag_to_add' );
					if ( ! empty( $tag_to_add ) ) {
						$contact->set_tags( $tag_to_add );
					}
				}
			}
		}

		/** To mark the contact subscribe and remove the unsubscribe record */
		$contact->resubscribe( false );
		$contact->save();

		/** Hook after confirmation link clicked */
		do_action( 'bwfcrm_confirmation_link_clicked', $contact );
		$link = filter_input( INPUT_GET, 'bwfan-link' );
		if ( ! empty( $link ) ) {
			$url = BWFAN_Common::bwfan_correct_protocol_url( $link );
			$url = BWFAN_PRO_Common::validate_target_link( $url );
			if ( false !== wp_http_validate_url( $url ) ) {
				BWFAN_PRO_Common::wp_redirect( $url );
				exit;
			}
		}
	}
}

if ( class_exists( 'BWFCRM_Form_Controller' ) ) {
	BWFCRM_Core::register( 'forms', 'BWFCRM_Form_Controller' );
}
