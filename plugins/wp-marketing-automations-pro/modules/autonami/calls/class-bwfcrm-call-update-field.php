<?php

namespace BWFCRM\Calls\Autonami;

use BWFCRM\Calls\Base;

/**
 * Remove lists call class
 */
class Update_Fields extends Base {

	/**
	 * Remove lists to contact
	 *
	 * @param \BWFCRM_Contact $contact
	 * @param $data
	 *
	 * @return mixed
	 */
	public function process_call( $contact, $data ) {
		/** Handle status change */
		if ( isset( $data['status'] ) ) {
			if ( intval( $data['status'] ) === 3 ) {
				/** unsubscribe contact */
				$contact->unsubscribe( false );
				unset( $data['status'] );
			} else {
				/** remove unsubscribe entry - maybe exists */
				$contact->remove_unsubscribe_status();
			}
		}

		return $contact->update_custom_fields( $data );
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'update_fields', 'BWFCRM\Calls\Autonami\Update_Fields' );
