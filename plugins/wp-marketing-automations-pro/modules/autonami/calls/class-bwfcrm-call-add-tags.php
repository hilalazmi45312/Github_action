<?php

namespace BWFCRM\Calls\Autonami;

use BWFCRM\Calls\Base;

/**
 * Add tags call class
 */
class Add_Tags extends Base {

	/**
	 * Add tags to contact
	 *
	 * @param \BWFCRM_Contact $contact
	 * @param $data
	 *
	 * @return mixed
	 */
	public function process_call( $contact, $data ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return [
				'skipped'  => [],
				'assigned' => []
			];
		}

		$existing_tags = $contact->get_tags();

		$contact->set_tags_v2( $data );

		$new_tags = $contact->get_tags();

		/** Check if any tags to add */
		$assigned_ids = array_diff( $new_tags, $existing_tags );
		if ( ! empty( $assigned_ids ) ) {
			$contact->save();
		}

		$skipped_ids = array_diff( $data, $assigned_ids );
		$all_ids     = array_unique( array_merge( $assigned_ids, $skipped_ids ) );

		$term_data = \BWFCRM_Term::get_terms( \BWFCRM_Term_Type::$TAG, $all_ids, '', 0, 0, OBJECT );
		$term_map  = [];
		foreach ( $term_data as $term ) {
			$term_map[ $term->get_id() ] = $term;
		}

		return [
			'assigned' => array_filter( array_map( function ( $id ) use ( $term_map ) {
				return $term_map[ $id ] ?? null;
			}, array_values( $assigned_ids ) ) ),
			'skipped'  => array_filter( array_map( function ( $id ) use ( $term_map ) {
				return $term_map[ $id ] ?? null;
			}, array_values( $skipped_ids ) ) )
		];
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'add_tags', 'BWFCRM\Calls\Autonami\Add_Tags' );
