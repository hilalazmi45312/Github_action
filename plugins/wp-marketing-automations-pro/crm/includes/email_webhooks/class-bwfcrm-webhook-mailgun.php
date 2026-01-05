<?php

class BWFCRM_Webhook_Mailgun extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		$this->_name = "Mailgun";
	}

	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	private function maybe_bounce_contact_email() {
		foreach ( $this->_data as $key => $datum ) {
			if ( 'event-data' !== $key ) {
				continue;
			}
			$valid_bounce_statuses = apply_filters( 'bwfan_mailgun_bounce_status', array( 'bounced', 'failed', 'complained', 'unsubscribed' ) );
			if ( ! is_array( $datum ) || ! isset( $datum['event'] ) || ! in_array( $datum['event'], $valid_bounce_statuses, true ) || ! is_email( $datum['recipient'] ) ) {
				continue;
			}

			$event_type = strtolower( $datum['event'] );

			switch ( $event_type ) {
				case 'bounced':
				case 'failed':
					$this->mark_contact_bounce( $datum['recipient'] );
					break;

				case 'complained':
					$this->mark_contact_complaint( $datum['recipient'] );
					break;
			}
		}
	}
}

BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_Mailgun' );
