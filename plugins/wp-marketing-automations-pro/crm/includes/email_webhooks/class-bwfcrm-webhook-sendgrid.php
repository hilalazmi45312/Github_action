<?php

class BWFCRM_Webhook_Sendgrid extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		$this->_name = "Sendgrid";
	}

	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	private function maybe_bounce_contact_email() {
		// Apply filter to define valid events for bounce, soft bounce, and complaints
		$valid_events = apply_filters( 'bwfan_sendgrid_bounce_events', array( 'bounce', 'deferred', 'dropped' ) );

		// Loop through each data entry in the webhook
		foreach ( $this->_data as $datum ) {
			if ( ! is_array( $datum ) || ! isset( $datum['event'], $datum['email'] ) || ! is_email( $datum['email'] ) ) {
				continue;
			}
			$event = strtolower( $datum['event'] );
			if ( ! in_array( $event, $valid_events, true ) ) {
				continue;
			}

			switch ( $event ) {
				case 'bounce':
					$this->mark_contact_bounce( $datum['email'] );
					break;

				case 'deferred':
					$this->mark_contact_soft_bounce( $datum['email'] );
					break;

				case 'dropped':
				case 'spamreport':
					$this->mark_contact_complaint( $datum['email'] );
					break;
			}
		}
	}
}

BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_Sendgrid' );
