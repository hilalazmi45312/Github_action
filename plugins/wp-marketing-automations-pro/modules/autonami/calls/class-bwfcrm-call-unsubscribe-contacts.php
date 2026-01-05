<?php

namespace BWFCRM\Calls\Autonami;

use BWFCRM\Calls\Base;

/**
 * Unsubscribe Contacts call class
 */
class Unsubscribe_Contacts extends Base {

	/**
	 * @param \BWFCRM_Contact $contact
	 * @param $data
	 *
	 * @return mixed
	 */
	public function process_call( $contact, $data ) {
		return $contact->unsubscribe();
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'unsubscribe_contacts', 'BWFCRM\Calls\Autonami\Unsubscribe_Contacts' );
