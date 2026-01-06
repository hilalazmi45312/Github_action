<?php

class BWFCRM_Webhook_Postmark extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		$this->_name = "Postmark";
	}

	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	private function maybe_bounce_contact_email() {
		if ( ! is_array( $this->_data ) || ! isset( $this->_data['TypeCode'] ) || ! is_email( $this->_data['Email'] ) ) {
			return;
		}

		/**
		 * checking for hard bounce
		 * https://postmarkapp.com/developer/api/bounce-api#bounce-types
		 *
		 * 1 - Hard bounce
		 * 100000 - Invalid email address
		 * 100006 - ISP block
		 * 100009 - DMARC Policy
		 */
		if ( in_array( intval( $this->_data['TypeCode'] ), [ 1, 100000, 100006, 100009 ], true ) ) {
			$this->mark_contact_bounce( $this->_data['Email'] );

			return;
		}

		/**
		 * checking for soft bounce
		 * 2 - Transient - Message delayed/Undeliverable
		 * 256 - DNS error
		 * 4096 - Soft bounce
		 */
		if ( in_array( intval( $this->_data['TypeCode'] ), [ 2, 256, 4096 ], true ) ) {
			$this->mark_contact_soft_bounce( $this->_data['Email'] );

			return;
		}

		/**
		 * checking for spam complaint
		 * 100001 - Spam complaint
		 */
		if ( in_array( intval( $this->_data['TypeCode'] ), [ 100001 ], true ) ) {
			$this->mark_contact_complaint( $this->_data['Email'] );
		}
	}
}

BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_Postmark' );
