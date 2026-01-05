<?php

class BWFCRM_API_SendMessage extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = '/contacts/(?P<contact_id>[\\d]+)/sendmessage';
		$this->request_args = array(
			'contact_id' => array(
				'description' => __( 'Contact ID to send message', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			)
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => '',
			'title'      => '',
			'message'    => '',
			'type'       => 'email'
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$title      = $this->get_sanitized_arg( 'title', 'text_field' );
		$message    = $this->args['message'];
		$type       = $this->get_sanitized_arg( 'type', 'text_field' );

		$contact = new BWFCRM_Contact( absint( $contact_id ) );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 400;

			return $this->error_response( __( 'No contact found with given ID #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

		/** Check if contacts status is bounced */
		if ( 4 === $contact->get_display_status() ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Contact status is bounced', 'wp-marketing-automations-pro' ) );
		}

		if ( 'email' === $type && empty( $title ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Title is mandatory', 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $message ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Message is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$author_id = get_current_user_id();

		$mode = BWFAN_Email_Conversations::$MODE_EMAIL;
		switch ( $type ) {
			case 'sms':
				$mode = BWFAN_Email_Conversations::$MODE_SMS;
				break;
			case 'whatsapp':
				$mode = BWFAN_Email_Conversations::$MODE_WHATSAPP;
				break;
		}

		BWFAN_Merge_Tag_Loader::set_data( array(
			'contact_id'   => $contact->get_id(),
			'contact_mode' => $mode
		) );
		$message      = BWFAN_Common::decode_merge_tags( ( 1 === $mode ? wpautop( $message ) : $message ) );
		$template     = [
			'subject'  => ( empty( $title ) ? '' : $title ),
			'template' => ( empty( $message ) ? '' : $message )
		];
		$conversation = BWFCRM_Core()->conversation->create_campaign_conversation( $contact, 0, 0, $author_id, $mode, true, $template );

		if ( empty( $conversation['conversation_id'] ) ) {
			return $this->error_response( $conversation );
		}

		if ( class_exists( 'BWFAN_Message' ) ) {
			$message_obj = new BWFAN_Message();
			$message_obj->set_message( 0, $conversation['conversation_id'], $title, $message );
			$message_obj->save();
		}

		$conversation['template'] = $message;
		$conversation['subject']  = $title;

		if ( 'sms' === $type ) {
			$sent = $this->send_single_sms( $conversation, $contact );
		} else if ( 'whatsapp' === $type ) {
			$sent = $this->send_single_whatsapp_message( $conversation, $contact );
		} else {
			$sent = $this->send_single_email( $title, $conversation, $contact );
		}

		if ( true === $sent ) {
			return $this->success_response( [], __( 'Message sent', 'wp-marketing-automations-pro' ) );
		}

		return $this->error_response( $sent, null, 500 );
	}

	/**
	 * @param $title
	 * @param $conversation
	 * @param $contact BWFCRM_Contact
	 *
	 * @return string|true|null
	 */
	public function send_single_email( $title, $conversation, $contact ) {
		$conversation_id = $conversation['conversation_id'];
		$contact_id      = $contact->contact->get_id();

		$email_subject = BWFCRM_Core()->conversation->prepare_email_subject( $title, $contact_id );
		try {
			$email_body = BWFCRM_Core()->conversation->prepare_email_body( $conversation['conversation_id'], $contact_id, $conversation['hash_code'], 'rich', $conversation['template'] );
		} catch ( Error $e ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $e->getMessage() );

			return $e->getMessage();
		}
		if ( is_wp_error( $email_body ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $email_body->get_error_message() );

			return $email_body->get_error_message();
		}

		$to = $contact->contact->get_email();
		if ( ! is_email( $to ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, __( 'No email found for this contact: ' . $contact_id, 'wp-marketing-automations-pro' ) );

			return __( 'No email found for this contact: ' . $contact_id, 'wp-marketing-automations-pro' );
		}

		$global_email_settings = BWFAN_Common::get_global_settings();

		$headers = array(
			'MIME-Version: 1.0',
			'Content-type: text/html;charset=UTF-8'
		);

		$from = '';
		if ( isset( $global_email_settings['bwfan_email_from_name'] ) && ! empty( $global_email_settings['bwfan_email_from_name'] ) ) {
			$from = 'From: ' . $global_email_settings['bwfan_email_from_name'];
		}

		if ( isset( $global_email_settings['bwfan_email_from'] ) && ! empty( $global_email_settings['bwfan_email_from'] ) ) {

			$headers[] = $from . ' <' . $global_email_settings['bwfan_email_from'] . '>';
		}

		if ( isset( $global_email_settings['bwfan_email_reply_to'] ) && ! empty( $global_email_settings['bwfan_email_reply_to'] ) ) {
			$headers[] = 'Reply-To:  ' . $global_email_settings['bwfan_email_reply_to'];
		}

		/** Set unsubscribe link in header */
		$unsubscribe_link = BWFAN_PRO_Common::get_unsubscribe_link( [ 'uid' => $contact->contact->get_uid() ] );
		if ( ! empty( $unsubscribe_link ) ) {
			$headers[] = "List-Unsubscribe: <$unsubscribe_link>";
			$headers[] = "List-Unsubscribe-Post: List-Unsubscribe=One-Click";
		}

		BWFCRM_Common::bwf_remove_filter_before_wp_mail();
		$headers = apply_filters( 'bwfan_email_headers', $headers );

		BWFCRM_Common::$captured_email_failed_message = null;

		$result = wp_mail( $to, $email_subject, $email_body, $headers );
		if ( true === $result ) {
			/** Save the time of last sent engagement **/
			$data = array( 'cid' => $contact_id );
			BWFCRM_Conversation::save_last_sent_engagement( $data );

			BWFCRM_Core()->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );
		} else {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, __( 'Email not sent', 'wp-marketing-automations-pro' ) );

			return __( 'Email not sent', 'wp-marketing-automations-pro' );
		}

		return $result;
	}

	public function send_single_sms( $conversation, $contact ) {
		$conversation_id = $conversation['conversation_id'];
		$contact_id      = $contact->contact->get_id();
		$sms_body        = BWFCRM_Core()->conversation->prepare_sms_body( $conversation['conversation_id'], $contact_id, $conversation['hash_code'], $conversation['template'] );

		if ( is_wp_error( $sms_body ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $sms_body->get_error_message() );

			return $sms_body->get_error_message();
		}

		$to = BWFAN_PRO_Common::get_contact_full_number( $contact->contact );

		if ( empty( $to ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, __( 'No phone number found for this contact: ' . $contact_id, 'wp-marketing-automations-pro' ) );

			return __( 'No phone number found for this contact: ' . $contact_id, 'wp-marketing-automations-pro' );
		}

		$send_sms_result = BWFCRM_Common::send_sms( array(
			'to'   => $to,
			'body' => $sms_body,
		) );

		if ( $send_sms_result instanceof WP_Error ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $send_sms_result->get_error_message() );

			return $send_sms_result->get_error_message();
		}

		/** Save the date of last sent engagement **/
		$data = array( 'cid' => $contact_id );
		BWFCRM_Conversation::save_last_sent_engagement( $data );

		BWFCRM_Core()->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );

		return true;
	}

	public function send_single_whatsapp_message( $conversation, $contact ) {
		$conversation_id = $conversation['conversation_id'];
		$contact_id      = $contact->contact->get_id();
		$message_body    = BWFCRM_Core()->conversation->prepare_sms_body( $conversation['conversation_id'], $contact_id, $conversation['hash_code'], $conversation['template'] );

		if ( is_wp_error( $message_body ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $message_body->get_error_message() );

			return $message_body->get_error_message();
		}

		$to = BWFAN_PRO_Common::get_contact_full_number( $contact->contact );

		if ( empty( $to ) ) {
			BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, __( 'No phone number found for this contact: ' . $contact_id, 'wp-marketing-automations-pro' ) );

			return __( 'No phone number found for this contact: ' . $contact_id, 'wp-marketing-automations-pro' );
		}

		$response = BWFCRM_Core()->conversation->send_whatsapp_message( $to, array(
			array(
				'type' => 'text',
				'data' => $message_body,
			)
		) );

		if ( $response['status'] == true ) {
			/** Save the time of last sent engagement **/
			$data = array( 'cid' => $contact_id );
			BWFCRM_Conversation::save_last_sent_engagement( $data );
			BWFCRM_Core()->conversation->update_conversation_status( $conversation_id, BWFAN_Email_Conversations::$STATUS_SEND );

			return true;
		}
		$error_message = isset( $response['msg'] ) && ! empty( $response['msg'] ) ? $response['msg'] : 'Unable to send message';
		BWFCRM_Core()->conversation->fail_the_conversation( $conversation_id, $error_message );

		return $error_message;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_SendMessage' );
