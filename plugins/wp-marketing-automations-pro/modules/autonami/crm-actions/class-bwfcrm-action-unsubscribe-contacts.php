<?php

namespace BWFCRM\Actions\Autonami;

use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;


/**
 * Unsubscribe contacts action class
 */
class Unsubscribe_Contacts extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'unsubscribe_contacts';
		$this->nice_name   = __( 'Unsubscribe Contacts', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
		$this->event_slug  = 'crm_contact_unsubscribed';
	}

	/**
	 * Returns action field schema
	 *
	 * @return array
	 */
	public function get_action_schema() {
		return [];
	}

	/**
	 * process action
	 *
	 * @param $contact \BWFCRM_Contact
	 * @param $data
	 *
	 * @return array|false|string
	 */
	public function handle_action( $contact, $data ) {
		// skip if already unsubscribed
		if ( ! empty( $contact->check_contact_unsubscribed() ) ) {
			return 'skip';
		}

		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\Unsubscribe_Contacts' ) ) {
			return false;
		}

		$call_obj = new Calls\Unsubscribe_Contacts;

		/**
		 * Process call
		 */
		$contact_unsubscribed = $call_obj->process_call( $contact, $data );
		if ( true === $contact_unsubscribed ) {
			return array(
				'status'  => self::$RESPONSE_SUCCESS,
				'message' => __( 'Contact(s) Unubscribed: ', 'wp-marketing-automations-pro' ),
			);
		}

		return array(
			'status'  => self::$RESPONSE_FAILED,
			'message' => __( 'Contact(s) Unsubscribed Failed: ', 'wp-marketing-automations-pro' ),
		);
	}

}

/**
 * Register action
 */
BWFCRM_Core()->actions->register_action( 'unsubscribe_contacts', 'BWFCRM\Actions\Autonami\Unsubscribe_Contacts', __( 'Unsubscribe Contacts', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
