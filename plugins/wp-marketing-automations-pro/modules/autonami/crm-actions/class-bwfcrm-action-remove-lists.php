<?php

namespace BWFCRM\Actions\Autonami;

use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;

/**
 * Remove lists action
 */
class Remove_Lists extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'remove_lists';
		$this->nice_name   = __( 'Remove Lists', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
		$this->event_slug  = 'crm_unassigned_list';
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
				'autocompleter' => 'list',
				'addnew'        => false,
			),
		);
	}

	/**
	 * process action
	 *
	 * @param $contact
	 * @param $lists
	 *
	 * @return array|string
	 */
	public function handle_action( $contact, $lists ) {

		$contact_lists = $contact->get_lists();

		// format list data
		$lists = array_filter( array_map( function ( $list ) use ( $contact_lists ) {
			return in_array( $list['id'], $contact_lists ) ? $list['id'] : false;
		}, $lists ) );

		if ( empty( $lists ) ) {
			return 'skip';
		}

		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\Remove_Lists' ) ) {
			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => __( 'List(s) Remove Failed: Call not found', 'wp-marketing-automations-pro' ),
			);
		}

		$call_obj = new Calls\Remove_Lists();

		/**
		 * Process call
		 */
		$removed_lists = $call_obj->process_call( $contact, $lists );
		if ( empty( $removed_lists ) ) {
			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => __( 'List(s) Remove Skipped: Lists were not assigned on the contact', 'wp-marketing-automations-pro' ),
			);
		}

		$removed_lists = \BWFCRM_Lists::get_lists( $removed_lists );
		$removed_lists = array_map( function ( $list ) {
			return $list['name'];
		}, $removed_lists );

		return array(
			'status'  => self::$RESPONSE_SUCCESS,
			'message' => __( 'List(s) Removed: ', 'wp-marketing-automations-pro' ) . implode( ',', $removed_lists ),
		);
	}
}

/**
 * Register Action
 */
BWFCRM_Core()->actions->register_action( 'remove_lists', 'BWFCRM\Actions\Autonami\Remove_Lists', __( 'Remove Lists', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
