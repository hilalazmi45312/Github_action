<?php

class BWFCRM_Webhook_Sparkpost extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		$this->_name = "Sparkpost";
	}

	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	private function maybe_bounce_contact_email() {
		foreach ( $this->_data as $datum ) {
			if ( ! is_array( $datum ) || ! isset( $datum['msys']['message_event']['type'] ) || 'bounce' !== $datum['msys']['message_event']['type'] || ! is_email( $datum['msys']['message_event']['rcpt_to'] ) ) {
				continue;
			}

			$this->mark_contact_bounce( $datum['msys']['message_event']['rcpt_to'] );
			break;
		}
	}
}

BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_Sparkpost' );
