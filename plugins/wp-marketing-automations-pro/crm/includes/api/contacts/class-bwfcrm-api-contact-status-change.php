<?php

class BWFCRM_API_Contact_Status_Change extends BWFCRM_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = '/contacts/(?P<contact_id>[\\d]+)/execute_status_action/(?P<status>[a-zA-Z0-9-]+)';
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$status     = $this->get_sanitized_arg( 'status', 'text_field' );

		$contact = new BWFCRM_Contact( absint( $contact_id ) );
		if ( ! $contact->is_contact_exists() ) {
			return $this->error_response( __( 'Contact does not exists', 'wp-marketing-automations-pro' ) );
		}

		$result  = false;
		$message = __( 'Unable to perform requested action', 'wp-marketing-automations-pro' );
		switch ( $status ) {
			case 'resubscribe':
				$result = $contact->resubscribe();
				if ( true === $result ) {
					$message = __( 'Contact resubscribed', 'wp-marketing-automations-pro' );
				}
				break;
			case 'unsubscribe':
				$result = $contact->unsubscribe();
				if ( true === $result ) {
					$message = __( 'Contact unsubscribed', 'wp-marketing-automations-pro' );
				}
				break;
			case 'verify':
				$result = $contact->verify();
				if ( true === $result ) {
					$message = __( 'Contact subscribed', 'wp-marketing-automations-pro' );
				}
				break;
			case 'unverify':
				$result = $contact->unverify();
				if ( true === $result ) {
					$message = __( 'Contact unverfied', 'wp-marketing-automations-pro' );
				}
				break;
			case 'bounced':
				$result = $contact->mark_as_bounced();
				if ( true === $result ) {
					$message = __( 'Contact bounced', 'wp-marketing-automations-pro' );
				}
				break;
			case 'softbounced':
				if ( method_exists( $contact, 'mark_as_soft_bounced' ) ) {
					$result = $contact->mark_as_soft_bounced();
					if ( true === $result ) {
						$message = __( 'Contact soft bounced', 'wp-marketing-automations-pro' );
					}
				} else {
					$result = $contact->mark_as_bounced();
					if ( true === $result ) {
						$message = __( 'Contact bounced', 'wp-marketing-automations-pro' );
					}
				}
				break;
			case 'complaint':
				if ( method_exists( $contact, 'mark_as_complaint' ) ) {
					$result = $contact->mark_as_complaint();
					if ( true === $result ) {
						$message = __( 'Contact complaint', 'wp-marketing-automations-pro' );
					}
				} else {
					$result = $contact->mark_as_bounced();
					if ( true === $result ) {
						$message = __( 'Contact bounced', 'wp-marketing-automations-pro' );
					}
				}
				break;
		}

		if ( ! $result ) {
			return $this->error_response( $message, null, 500 );
		}

		return $this->success_response( [
			'status' => $contact->get_display_status(),
			'data'   => $contact->get_array( false, true, true, true, true ),
		], $message );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Contact_Status_Change' );
