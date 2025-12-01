<?php

namespace BWFCRM\Actions\Autonami;

use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;

/*
 * Remove tags action class
 */

class Remove_Tags extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'remove_tags';
		$this->nice_name   = __( 'Remove Tags', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
		$this->event_slug  = 'crm_unassigned_tag';
	}

	/**
	 * Returns action field schema
	 *
	 * @return array
	 */
	public function get_action_schema() {
		return array(
			'type' => 'search',
			'meta' => array(
				'autocompleter' => 'tag',
				'addnew'        => false,
			),
		);
	}

	/**
	 * @param $contact \BWFCRM_Contact
	 * @param $tags
	 *
	 * @return array|string
	 */
	public function handle_action( $contact, $tags ) {
		$contact_tags = $contact->get_tags();

		// format tag data
		$tags = array_filter( array_map( function ( $tag ) use ( $contact_tags ) {
			return in_array( $tag['id'], $contact_tags ) ? $tag['id'] : false;
		}, $tags ) );

		if ( empty( $tags ) ) {
			return 'skip';
		}

		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\Remove_Tags' ) ) {
			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => __( 'Tag(s) Remove Failed: Call not found', 'wp-marketing-automations-pro' ),
			);
		}

		$call_obj = new Calls\Remove_Tags();

		/**
		 * Process call
		 */
		$removed_tags = $call_obj->process_call( $contact, $tags );
		if ( empty( $removed_tags ) ) {
			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => __( 'Tag(s) Remove Skipped: Tags were not present on the contact', 'wp-marketing-automations-pro' ),
			);
		}

		$removed_tags = \BWFCRM_Tag::get_tags( $removed_tags );
		$removed_tags = array_map( function ( $tag ) {
			return $tag['name'];
		}, $removed_tags );

		return array(
			'status'  => self::$RESPONSE_SUCCESS,
			'message' => __( 'Tag(s) Removed: ', 'wp-marketing-automations-pro' ) . implode( ',', $removed_tags ),
		);
	}
}

/**
 * Register action
 */
BWFCRM_Core()->actions->register_action( 'remove_tags', 'BWFCRM\Actions\Autonami\Remove_Tags', __( 'Remove Tags', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
