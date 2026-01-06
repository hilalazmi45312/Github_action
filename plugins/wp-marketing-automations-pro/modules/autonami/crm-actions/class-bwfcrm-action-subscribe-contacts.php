<?php

namespace BWFCRM\Actions\Autonami;

use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;

/**
 * Subscribe contacts action class
 */
class Subscribe_Contacts extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'subscribe_contacts';
		$this->nice_name   = __( 'Subscribe Contacts', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
		$this->event_slug  = 'crm_contact_subscribed';
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
		// skip if already subscribed
		if ( $contact->contact->is_subscribed ) {
			return 'skip';
		}

		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\Subscribe_Contacts' ) ) {
			return false;
		}

		$call_obj = new Calls\Subscribe_Contacts;

		/**
		 * Process call
		 */
		$constact_subsribed = $call_obj->process_call( $contact, $data );

		if ( true === $constact_subsribed ) {
			return array(
				'status'  => self::$RESPONSE_SUCCESS,
				'message' => __( 'Contact(s) Subscribed: ', 'wp-marketing-automations-pro' ),
			);
		}

		return array(
			'status'  => self::$RESPONSE_FAILED,
			'message' => __( 'Contact(s) Subscribed Failed: ', 'wp-marketing-automations-pro' ),
		);
	}

}

/**
 * Register action
 */
BWFCRM_Core()->actions->register_action( 'subscribe_contacts', 'BWFCRM\Actions\Autonami\Subscribe_Contacts', __( 'Subscribe Contacts', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
