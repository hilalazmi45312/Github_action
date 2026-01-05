<?php

namespace BWFCRM\Calls\Autonami;

use BWFCRM\Calls\Base;

/**
 * Remove tags call class
 */
class Remove_Tags extends Base {

	/**
	 * Remove tags to contact
	 *
	 * @param \BWFCRM_Contact $contact
	 * @param $data
	 *
	 * @return mixed
	 */
	public function process_call( $contact, $data ) {
		$response = $contact->remove_tags( $data );
		$contact->save();

		return $response;
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'remove_tags', 'BWFCRM\Calls\Autonami\Remove_Tags' );
