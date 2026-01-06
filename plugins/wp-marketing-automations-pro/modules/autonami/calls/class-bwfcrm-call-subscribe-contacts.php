<?php

namespace BWFCRM\Calls\Autonami;

use BWFCRM\Calls\Base;

/**
 * Subscribe Contacts call class
 */
class Subscribe_Contacts extends Base {

	/**
	 * @param \BWFCRM_Contact $contact
	 *
	 * @return mixed
	 */
	public function process_call( $contact, $data ) {
		return $contact->resubscribe();
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'subscribe_contacts', 'BWFCRM\Calls\Autonami\Subscribe_Contacts' );
