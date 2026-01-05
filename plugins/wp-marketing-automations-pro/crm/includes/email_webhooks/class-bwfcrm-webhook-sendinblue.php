<?php

/**
 * Class BWFCRM_Webhook_Sendinblue
 * This class tracks the bounced mail from sendinblue
 */
class BWFCRM_Webhook_Sendinblue extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	/*
	 * the name in the constructor is the name which will be shown in the dropdown in setting
	 */
	public function __construct() {
		$this->_name = "Brevo (Formerly Sendinblue)";
	}

	/**
	 * this is the abstract function from the base class
	 * it needs to be define in child class
	 * it receives the data received from webhook set in sendinblue
	 */
	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	/**
	 * this function mark the email as bounce
	 */
	private function maybe_bounce_contact_email() {
		if ( ! isset( $this->_data['event'], $this->_data['email'] ) || ! is_email( $this->_data['email'] ) ) {
			return;
		}
		$valid_record_types = apply_filters( 'bwfan_sendinblue_bounce_recordtypes', array( 'hard_bounce', 'soft_bounce', 'complaint' ) );

		$event = strtolower( $this->_data['event'] );
		if ( ! in_array( $event, $valid_record_types, true ) ) {
			return;
		}
		$handle_softbounce = apply_filters( 'bwfan_sendinblue_soft_bounce', 'yes' );
		if ( 'soft_bounce' === $event && 'no' === $handle_softbounce ) {
			return;
		}
		switch ( $event ) {
			case 'hard_bounce':
				$this->mark_contact_bounce( $this->_data['email'] );
				break;

			case 'soft_bounce':
				$this->mark_contact_soft_bounce( $this->_data['email'] );
				break;

			case 'complaint':
			case 'block':
				$this->mark_contact_complaint( $this->_data['email'] );
				break;
		}
	}
}

/**
 * Registering the BWFCRM_Webhook_Sendinblue
 * it will add the option of sendinblue in the dropdown in setting
 */
BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_Sendinblue' );
