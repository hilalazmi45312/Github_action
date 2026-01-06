<?php

namespace BWFCRM\Calls\Autonami;

use BWFCRM\Calls\Base;

/**
 * Remove lists call class
 */
class Remove_Lists extends Base {

	/**
	 * Remove lists to contact
	 *
	 * @param \BWFCRM_Contact $contact
	 * @param $data
	 *
	 * @return mixed
	 */
	public function process_call( $contact, $data ) {
		return $contact->remove_lists( $data );
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'remove_lists', 'BWFCRM\Calls\Autonami\Remove_Lists' );
