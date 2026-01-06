<?php

namespace BWFCRM\Actions\Autonami;

use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;

/**
 * Delete contacts action class
 */
class Delete_Contacts extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'delete_contacts';
		$this->nice_name   = __( 'Delete Contacts', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
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
		// skip if contact doesn't exist
		if ( ! $contact->is_contact_exists() ) {
			return 'skip';
		}

		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\Delete_Contacts' ) ) {
			return false;
		}

		$call_obj = new Calls\Delete_Contacts;

		/**
		 * Process call
		 */
		$contact_deleted = $call_obj->process_call( $contact, $data );

		if ( true === $contact_deleted ) {
			return array(
				'status'  => self::$RESPONSE_SUCCESS,
				'message' => __( 'Contact(s) Deleted: ', 'wp-marketing-automations-pro' ),
			);
		}

		return array(
			'status'  => self::$RESPONSE_FAILED,
			'message' => __( 'Contact(s) Deleted Failed: ', 'wp-marketing-automations-pro' ),
		);
	}

}

/**
 * Register action
 */
BWFCRM_Core()->actions->register_action( 'delete_contacts', 'BWFCRM\Actions\Autonami\Delete_Contacts', __( 'Delete Contacts', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
