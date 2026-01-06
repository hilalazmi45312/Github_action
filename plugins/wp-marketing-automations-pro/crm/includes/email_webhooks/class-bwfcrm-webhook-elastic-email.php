<?php

/**
 * Elastic Email Bounce Handling Controller
 */
class BWFCRM_Webhook_ElasticEmail extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		$this->_name = "Elastic Email";
	}

	/**
	 * Email Bounce Service Handler
	 *
	 * @return void
	 */
	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	/**
	 * Mark contact bounced
	 *
	 * @return void
	 */
	private function maybe_bounce_contact_email() {
		if ( ! is_array( $this->_data ) || ! isset( $this->_data['status'] ) || ! is_email( $this->_data['to'] ) ) {
			return;
		}
		$valid_statuses = apply_filters( 'bwfan_elasticemail_bounce_statuses', array( 'complaint', 'error', 'soft', 'hard' ) );

		$status = strtolower( $this->_data['status'] );

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return;
		}
		if ( 'complaint' === $status ) {
			$this->mark_contact_complaint( $this->_data['to'] );

			return;
		}
		if ( 'error' === $status ) {
			$bounce_type = isset( $this->_data['bounce_type'] ) ? strtolower( $this->_data['bounce_type'] ) : 'soft';

			if ( 'hard' === $bounce_type ) {
				$this->mark_contact_bounce( $this->_data['to'] );

				return;
			}
			if ( 'soft' === $bounce_type ) {
				$this->mark_contact_soft_bounce( $this->_data['to'] );
			}
		}
	}
}

BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_ElasticEmail' );
