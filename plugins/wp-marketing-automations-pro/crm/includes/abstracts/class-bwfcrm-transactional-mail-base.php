<?php

#[AllowDynamicProperties]
abstract class BWFCRM_Transactional_Mail_Base {
	/**
	 * Slug of mail should be same as class name BWFCRM_Transactional_Mail_Base{ slug }
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Name of mail
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Description of mail
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Is enabled by user
	 *
	 * @var bool
	 */
	protected $enabled = false;

	/**
	 * Supported block
	 *
	 * @var array
	 */
	protected $supported_block = [];

	/**
	 * Priority of mail to change position of mail in mail list
	 *
	 * @var int
	 */
	protected $priority = 10;

	/**
	 * Supported merge tag groups
	 *
	 * @var array
	 */
	protected $merge_tag_group = [];

	/**
	 * Recipient of mail Admin | Customer
	 *
	 * @var string
	 */
	protected $recipient = '';

	/**
	 * Data for template data to make mail
	 *
	 * @var array
	 */
	protected $template_data = [];

	/**
	 * Subject of mail
	 *
	 * @var string
	 */
	protected $subject = '';

	/**
	 * WC mail id
	 *
	 * @var string
	 */
	protected $wc_mail_id = '';

	/**
	 * Template url
	 *
	 * @var string
	 */
	protected $template_url = 'https://app.getautonami.com/transactional-mail/';

	/**
	 * @function get_data
	 */
	public function get_data( $key ) {
		if ( property_exists( $this, $key ) ) {
			return $this->{$key};
		}

		return null;
	}

	/**
	 * Check if mail is valid with dependency
	 *
	 * @return bool
	 */
	public function is_valid() {
		return true;
	}

	/**
	 * Set status based on template data
	 *
	 * @return void
	 */
	public function set_data_by_template_data() {
		if ( empty( $this->template_data ) ) {
			return;
		}
		if ( isset( $this->template_data['status'] ) && $this->template_data['status'] === 'enabled' ) {
			$this->enabled = true;
		}
	}

	/**
	 * Returns API data
	 *
	 * @return array
	 */
	public function get_api_data() {
		return [
			'slug'            => $this->slug,
			'name'            => $this->name,
			'description'     => $this->description,
			'enabled'         => $this->enabled,
			'priority'        => $this->priority,
			'recipient'       => $this->recipient,
			'template_data'   => $this->template_data,
			'merge_tag_group' => $this->merge_tag_group,
			'supported_block' => $this->supported_block,
		];
	}

	/**
	 * Create engagement tracking based on passed template id and merge tags data
	 *
	 * @param $template_id
	 * @param $transactional_data
	 * @param $merge_tags_data
	 *
	 * @return bool|mixed
	 */
	public function create_engagement_tracking( $template_id, $transactional_data, $merge_tags_data = [] ) {
		$template_data = BWFCRM_Templates::get_transactional_mail_by_template_id( $template_id );
		if ( empty( $template_data ) ) {
			return false;
		}

		// Set flag for transactional mail
		$merge_tags_data['bwfan_transactional_mail'] = true;

		$email_content       = isset( $template_data['data'] ) && BWFAN_Common::is_json( $template_data['data'] ) ? json_decode( $template_data['data'], true ) : [];
		$template_email_body = ! empty( $template_data['template'] ) ? $template_data['template'] : '';
		$email_subject       = ! empty( $template_data['subject'] ) ? $template_data['subject'] : '';
		$other_recipient     = ! empty( $email_content['other_recipients'] ) ? $email_content['other_recipients'] : '';
		if ( empty( $template_email_body ) || empty( $email_subject ) ) {
			return false;
		}
		$recipients = [];
		if ( ! empty( $other_recipient ) ) {
			$data = explode( ',', $other_recipient );
			if ( ! empty( $data ) ) {
				foreach ( $data as $recipient ) {
					$recipient = BWFAN_Common::decode_merge_tags( trim( $recipient ) );
					if ( is_email( $recipient ) ) {
						$recipients[] = $recipient;
					}
				}
			}
		}

		// Set wc trancasactional recipient data
		$merge_tags_data['bwfan_wc_transactional_recipient'] = $transactional_data['recipient'];

		if ( $transactional_data['recipient'] === 'admin' && empty( $recipients ) ) {
			$recipients[] = BWFAN_Common::$admin_email;
		}

		if ( empty( $recipients ) || $transactional_data['recipient'] === 'customer' ) {
			// if customer is recipient then first priority is mail not contact id
			$data = ! empty( $merge_tags_data['email'] ) ? $merge_tags_data['email'] : ( ! empty( $merge_tags_data['contact_id'] ) ? $merge_tags_data['contact_id'] : '' );
			if ( ! empty( $data ) ) {
				$recipients[] = $data;
			}
		}

		/** @var  $global_email_settings BWFAN_Common settings */
		$global_email_settings = BWFAN_Common::get_global_settings();

		/** Apply Click tracking and UTM Params */
		$utm_enabled = isset( $email_content['utmEnabled'] ) && 1 === absint( $email_content['utmEnabled'] );
		$utm_data    = $utm_enabled && isset( $email_content['utm'] ) && is_array( $email_content['utm'] ) ? $email_content['utm'] : array();
		/** load block files */
		BWFAN_Common::bwfan_before_send_mail( 'block' );

		$email_status = false;
		foreach ( $recipients as $recipient ) {
			$contact = new BWFCRM_Contact( $recipient, true );
			if ( ! $contact instanceof BWFCRM_Contact || false === $contact->is_contact_exists() ) {
				continue;
			}

			/** Set email to contact */
			$merge_tags_data['email'] = $contact->contact->get_email();

			$conversation = new BWFAN_Engagement_Tracking();
			$conversation->set_template_id( $template_id );
			$conversation->set_oid( isset( $merge_tags_data['oid'] ) ? $merge_tags_data['oid'] : '' );
			$conversation->set_mode( BWFAN_Email_Conversations::$MODE_EMAIL );
			$conversation->set_contact( $contact );
			$conversation->set_send_to( $contact->contact->get_email() );
			$conversation->enable_tracking();
			$conversation->set_type( BWFAN_Email_Conversations::$TYPE_TRANSACTIONAL );
			$conversation->set_template_id( $template_id );
			$conversation->set_status( BWFAN_Email_Conversations::$STATUS_DRAFT );
			$conversation->add_merge_tags_from_string( $template_email_body, $merge_tags_data );
			$conversation->add_merge_tags_from_string( $email_subject, $merge_tags_data );

			/** Email Subject */
			$email_body = method_exists( 'BWFAN_Common', 'correct_shortcode_string' ) ? BWFAN_Common::correct_shortcode_string( $template_email_body, 5 ) : $template_email_body;
			$subject    = BWFAN_Common::decode_merge_tags( $email_subject );
			$merge_tags = $conversation->get_merge_tags();
			$email_body = BWFAN_Common::replace_merge_tags( $email_body, $merge_tags, $contact->get_id() );
			$email_body = BWFAN_Common::decode_merge_tags( $email_body );
			/** Email Body */
			$body = BWFAN_Common::bwfan_correct_protocol_url( $email_body );
			$body = BWFCRM_Core()->conversation->apply_template_by_type( $body, 'block', $subject );

			$uid = $contact->contact->get_uid();

			/** Set contact object */
			BWFCRM_Core()->conversation->contact         = $contact;
			BWFCRM_Core()->conversation->engagement_type = BWFAN_Email_Conversations::$TYPE_TRANSACTIONAL;
			if ( property_exists( BWFAN_Core()->conversation, 'template_id' ) ) {
				BWFAN_Core()->conversation->template_id = $template_id;
			}
			$body = BWFCRM_Core()->conversation->append_to_email_body_links( $body, $utm_data, $conversation->get_hash(), $uid );

			/** Apply Pre-Header and Hash ID (open pixel id) */
			$pre_header = isset( $email_content['preheader'] ) && ! empty( $email_content['preheader'] ) ? $email_content['preheader'] : '';
			$pre_header = BWFAN_Common::decode_merge_tags( $pre_header );

			$body = BWFCRM_Core()->conversation->append_to_email_body( $body, $pre_header, $conversation->get_hash() );

			/** Removed wp mail filters */
			BWFCRM_Common::bwf_remove_filter_before_wp_mail();

			/** Email Headers */
			$reply_to_email = isset( $email_content['reply_to_email'] ) ? BWFAN_Common::decode_merge_tags( $email_content['reply_to_email'] ) : $global_email_settings['bwfan_email_reply_to'];
			$from_email     = isset( $email_content['from_email'] ) ? BWFAN_Common::decode_merge_tags( $email_content['from_email'] ) : $global_email_settings['bwfan_email_from'];
			$from_name      = isset( $email_content['from_name'] ) ? BWFAN_Common::decode_merge_tags( $email_content['from_name'] ) : $global_email_settings['bwfan_email_from_name'];

			/** Setup Headers */
			$header = [ 'MIME-Version: 1.0' ];
			if ( ! empty( $from_email ) && ! empty( $from_name ) ) {
				$header[] = 'From: ' . $from_name . ' <' . $from_email . '>';
			}
			if ( ! empty( $reply_to_email ) ) {
				$header[] = 'Reply-To:  ' . $reply_to_email;
			}
			$header[] = 'Content-type:text/html;charset=UTF-8';

			$header = apply_filters( 'bwfan_email_headers', $header );

			BWFCRM_Common::$captured_email_failed_message = null;
			/** Send the Email */
			$email_status = wp_mail( $conversation->get_send_to(), $subject, $body, $header, $this->get_attachments() );

			/** Set the status of Email */
			if ( true === $email_status ) {
				$conversation->set_status( BWFAN_Email_Conversations::$STATUS_SEND );
			} else {
				$conversation->set_status( BWFAN_Email_Conversations::$STATUS_ERROR );
			}
			$conversation->save();
		}

		return $email_status;
	}


	/**
	 * Get email attachments.
	 *
	 * @return array
	 */
	public function get_attachments() {
		$merge_tags_data = BWFAN_Merge_Tag_Loader::get_data();

		return apply_filters( 'woocommerce_email_attachments', [], $this->wc_mail_id, ! empty( $merge_tags_data['order_id'] ) ? wc_get_order( $merge_tags_data['order_id'] ) : '', null );
	}

	/**
	 * Get default template data from api
	 *
	 * @param string $lang
	 *
	 * @return array
	 */
	public function get_default_template_data( $lang = '' ) {
		$template_data = [
			'title'   => $this->slug,
			'subject' => $this->subject,
			'type'    => 1,
			'mode'    => 7,
			'canned'  => 0,
		];

		$api_url = $this->template_url . $this->slug . ( ! empty( $lang ) ? '?lang=' . $lang : '' );

		// Fetch data from api
		$response = wp_remote_get( $api_url );

		if ( is_wp_error( $response ) ) {
			$fetched_data = [];
		} else {
			$body         = wp_remote_retrieve_body( $response );
			$fetched_data = json_decode( $body, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$fetched_data = [];
			}
		}

		// Set template data
		$template_data['template'] = isset( $fetched_data['html'] ) ? $fetched_data['html'] : '';

		$from_name = get_option( 'woocommerce_email_from_name' );
		$from_mail = get_option( 'woocommerce_email_from_address' );
		// Set mail data
		$template_data['data'] = [
			'block'          => isset( $fetched_data['block'] ) ? $fetched_data['block'] : '',
			'preheader'      => '',
			'utmEnabled'     => false,
			'utm'            => [],
			"from_name"      => ! empty( $from_name ) ? $from_name : '',
			"from_email"     => ! empty( $from_mail ) ? $from_mail : '',
			"reply_to_email" => ! empty( $from_mail ) ? $from_mail : '',
		];

		$template_data['data'] = wp_json_encode( $template_data['data'] );

		return $template_data;
	}
}