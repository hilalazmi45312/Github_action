<?php

class BWFCRM_Webhook_AmazonSES extends BWFCRM_Email_Webhook_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		$this->_name = "Amazon SES";
	}

	public function handle_webhook() {
		if ( ! is_array( $this->_data ) || empty( $this->_data ) ) {
			return;
		}

		$this->maybe_bounce_contact_email();
	}

	private function maybe_bounce_contact_email() {
		$notificationType = isset( $this->_data['notificationType'] ) && ! empty( $this->_data['notificationType'] ) ? $this->_data['notificationType'] : '';

		if ( empty( $notificationType ) ) {
			$notificationType = isset( $this->_data['Type'] ) && ! empty( $this->_data['Type'] ) ? $this->_data['Type'] : '';
		}

		if ( empty( $notificationType ) ) {
			BWFAN_Core()->logger->log( "Amazon SES no notification type found", 'crm_email_webhooks' );

			return;
		}

		if ( $notificationType === 'SubscriptionConfirmation' ) {
			wp_remote_get( $this->_data['SubscribeURL'] );
			wp_send_json( [
				'status'  => 200,
				'message' => 'success'
			], 200 );
		}

		if ( $notificationType === 'Complaint' ) {
			$complaint = isset( $this->_data['complaint'] ) && is_array( $this->_data['complaint'] ) ? $this->_data['complaint'] : [];
			if ( empty( $complaint ) || ! isset( $complaint['complainedRecipients'] ) || ! is_array( $complaint['complainedRecipients'] ) ) {
				return;
			}

			/** In case of complaint marking contact bounced */
			foreach ( $complaint['complainedRecipients'] as $complainedRecipient ) {
				$email = $this->extractEmail( $complainedRecipient['emailAddress'] );
				$this->mark_contact_complaint( $email );
			}

			return;
		}

		if ( $notificationType === 'Bounce' ) {
			$bounce = isset( $this->_data['bounce'] ) && is_array( $this->_data['bounce'] ) ? $this->_data['bounce'] : [];
			if ( ! isset( $bounce['bounceType'] ) ) {
				return;
			}

			$bounce_type = [ 'Undetermined', 'Permanent' ];
			if ( in_array( $bounce['bounceType'], apply_filters( 'bwfan_amazonses_bounce_type', $bounce_type ), true ) ) {
				foreach ( $bounce['bouncedRecipients'] as $bouncedRecipient ) {
					$email = $this->extractEmail( $bouncedRecipient['emailAddress'] );
					$this->mark_contact_bounce( $email );
				}

				return;
			}

			/** Soft bounce case */
			foreach ( $bounce['bouncedRecipients'] as $bouncedRecipient ) {
				$email = $this->extractEmail( $bouncedRecipient['emailAddress'] );
				$this->mark_contact_soft_bounce( $email );
			}
		}
	}

	private function extractEmail( $from_email ) {
		$bracket_pos = strpos( $from_email, '<' );
		if ( false !== $bracket_pos ) {
			$from_email = substr( $from_email, $bracket_pos + 1 );
			$from_email = str_replace( '>', '', $from_email );
			$from_email = trim( $from_email );
		}

		if ( is_email( $from_email ) ) {
			return $from_email;
		}

		return false;
	}
}

BWFCRM_Core()->email_webhooks->register( 'BWFCRM_Webhook_AmazonSES' );
