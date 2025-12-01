<?php
/**
 * BWFCRM_Email_Webhook_Base Abstract Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Email_Webhook_Base
 *
 */
#[\AllowDynamicProperties]
abstract class BWFCRM_Email_Webhook_Base {
	protected $_data = [];
	protected $_name = "";

	public function set_data( $data = [] ) {
		$this->_data = $data;
	}

	public function get_name() {
		return $this->_name;
	}

	abstract public function handle_webhook();

	protected function mark_contact_bounce( $contact_email ) {
		$contact = new BWFCRM_Contact( $contact_email );
		if ( ! $contact->mark_as_bounced() ) {
			BWFAN_Core()->logger->log( "Unable to change contact status to bounce. Contact not found of email: $contact_email", 'crm_email_webhooks' );
		}
	}

	protected function mark_contact_soft_bounce( $contact_email ) {
		$contact = new BWFCRM_Contact( $contact_email );
		if ( method_exists( $contact, 'mark_as_soft_bounced' ) && ! $contact->mark_as_soft_bounced() ) {
			BWFAN_Core()->logger->log( "Unable to change contact status to soft bounce. Contact not found of email: $contact_email", 'crm_email_webhooks' );
		}

		/** Older FKA lite version do nothing */
	}

	protected function mark_contact_complaint( $contact_email ) {
		$contact = new BWFCRM_Contact( $contact_email );
		if ( method_exists( $contact, 'mark_as_complaint' ) ) {
			if ( ! $contact->mark_as_complaint() ) {
				BWFAN_Core()->logger->log( "Unable to change contact status to complaint. Contact not found of email: $contact_email", 'crm_email_webhooks' );
			}

			return;
		}

		/** Older FKA lite version */
		if ( ! $contact->mark_as_bounced() ) {
			BWFAN_Core()->logger->log( "Unable to change contact status to complaint first then bounce. Contact not found of email: $contact_email", 'crm_email_webhooks' );
		}
	}
}
