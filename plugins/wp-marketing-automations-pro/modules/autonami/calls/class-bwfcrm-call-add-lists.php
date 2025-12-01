<?php

namespace BWFCRM\Calls\Autonami;

use BWFCRM\Calls\Base;

/**
 * Add lists call class
 */
class Add_To_Lists extends Base {

	/**
	 * Add list to contact
	 *
	 * @param \BWFCRM_Contact $contact
	 * @param $data
	 */
	public function process_call( $contact, $data ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return [
				'skipped'  => [],
				'assigned' => []
			];
		}

		$existing_lists = $contact->get_lists();

		$contact->set_lists_v2( $data );

		$new_lists = $contact->get_lists();

		/** Check if any lists to add */
		$assigned_ids = array_diff( $new_lists, $existing_lists );
		if ( ! empty( $assigned_ids ) ) {
			$contact->save();
		}

		$skipped_ids = array_diff( $data, $assigned_ids );
		$all_ids     = array_unique( array_merge( $assigned_ids, $skipped_ids ) );

		$term_data = \BWFCRM_Term::get_terms( \BWFCRM_Term_Type::$LIST, $all_ids, '', 0, 0, OBJECT );
		$term_map  = [];
		foreach ( $term_data as $term ) {
			$term_map[ $term->get_id() ] = $term;
		}

		return [
			'assigned' => array_map( function ( $id ) use ( $term_map ) {
				return $term_map[ $id ] ?? null;
			}, array_values( $assigned_ids ) ),
			'skipped'  => array_map( function ( $id ) use ( $term_map ) {
				return $term_map[ $id ] ?? null;
			}, array_values( $skipped_ids ) )
		];
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'add_to_lists', 'BWFCRM\Calls\Autonami\Add_To_Lists' );
