<?php

namespace BWFCRM\Calls\Autonami;

use BWFCRM\Calls\Base;

/**
 * Delete Contacts call class
 */
class Delete_Contacts extends Base {

	/**
	 * @param \BWFCRM_Contact $contact
	 * @param $data
	 *
	 * @return bool
	 */
	public function process_call( $contact, $data ) {
		$contact_id = $contact->contact->get_id();

		// rechecking just to make sure if this function get invoked directly using namespace
		if ( empty( $contact_id ) ) {
			return false;
		}

		return \BWFCRM_Model_Contact::delete_contact( $contact_id );
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'delete_contacts', 'BWFCRM\Calls\Autonami\Delete_Contacts' );
