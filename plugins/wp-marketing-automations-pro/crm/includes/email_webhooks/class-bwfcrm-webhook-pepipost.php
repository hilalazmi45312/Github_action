<?php

class BWFCRM_Webhook_Pepipost extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		$this->_name = "Pepipost";
	}

	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	private function maybe_bounce_contact_email() {
		foreach ( $this->_data as $datum ) {
			if ( ! is_array( $datum ) || ! isset( $datum['EVENT'], $datum['EMAIL'] ) || ! is_email( $datum['EMAIL'] ) ) {
				continue;
			}

			$valid_events = apply_filters( 'bwfan_pepipost_bounce_events', array( 'bounced', 'soft_bounced', 'complaint' ) );
			$event        = strtolower( $datum['EVENT'] );

			if ( ! in_array( $event, $valid_events, true ) ) {
				continue;
			}

			switch ( $event ) {
				case 'bounced':
					$this->mark_contact_bounce( $datum['EMAIL'] );
					break;

				case 'soft_bounced':
					$this->mark_contact_soft_bounce( $datum['EMAIL'] );
					break;

				case 'complaint':
					$this->mark_contact_complaint( $datum['EMAIL'] );
					break;
			}
		}
	}
}

BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_Pepipost' );
