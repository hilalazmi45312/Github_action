<?php

class BWFCRM_Webhook_MailJet extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		$this->_name = "Mailjet";
	}

	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	private function maybe_bounce_contact_email() {
		// Loop through the data if it's an array
		foreach ( $this->_data as $datum ) {
			// Ensure the necessary data is set and it's a valid email event
			if ( ! is_array( $datum ) || ! isset( $datum[0] ) || ! isset( $datum[0]['event'] ) || ! isset( $datum[0]['email'] ) || ! is_email( $datum[0]['email'] ) ) {
				continue;
			}

			$event         = $datum[0]['event'];
			$email         = $datum[0]['email'];
			$bounce_status = apply_filters( 'bwfan_mailjet_bounce_status', array( 'bounce', 'soft_bounce', 'complaint' ) );
			if ( ! in_array( $event, $bounce_status, true ) ) {
				continue;
			}
			switch ( strtolower( $event ) ) {
				case 'bounce':
					$this->mark_contact_bounce( $email );
					break;

				case 'soft_bounce':
					$this->mark_contact_soft_bounce( $email );
					break;

				case 'complaint':
					$this->mark_contact_complaint( $email );
					break;
			}
		}
	}
}

BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_MailJet' );
