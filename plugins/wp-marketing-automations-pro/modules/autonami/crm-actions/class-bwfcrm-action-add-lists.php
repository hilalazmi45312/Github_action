<?php

namespace BWFCRM\Actions\Autonami;

use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;
use WP_Error;

/**
 * Add lists action class
 */
class Add_To_Lists extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'add_to_lists';
		$this->nice_name   = __( 'Add Lists', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
		$this->event_slug  = 'crm_assigned_list';
	}

	/**
	 * Add list field schema
	 *
	 * @return array
	 */
	public function get_action_schema() {
		return [
			'type' => 'search',
			'meta' => [
				'autocompleter' => 'list',
				'addnew'        => true,
			]
		];
	}

	/**
	 * process action
	 *
	 * @param $contact \BWFCRM_Contact
	 * @param $lists
	 *
	 * @return array|false
	 */
	public function handle_action( $contact, $lists ) {
		$lists = array_column( $lists, 'id' );

		if ( empty( $lists ) ) {
			return 'skip';
		}

		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\Add_To_Lists' ) ) {
			return false;
		}

		$call_obj = new Calls\Add_To_Lists();

		/**
		 * Process call
		 */
		$lists_added = $call_obj->process_call( $contact, $lists );

		return $this->get_response( $lists, $lists_added );
	}

	public function get_response( $lists, $lists_added ) {
		$message = array();
		if ( $lists_added instanceof WP_Error ) {
			$message[] = __( 'List(s) Add Failed: ', 'wp-marketing-automations-pro' ) . array_reduce( $lists, function ( $carry, $item ) {
					$name = $item['value'];
					if ( empty( $carry ) ) {
						return $name;
					}

					return ! empty( $name ) ? $carry . ', ' . $name : $carry;
				}, '' );
			$message[] = __( 'List(s) Add Failed. Error: ', 'wp-marketing-automations-pro' ) . $lists_added->get_error_message();

			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => $message,
			);
		}

		if ( isset( $lists_added['skipped'] ) && count( $lists_added['skipped'] ) > 0 ) {
			$message[] = __( 'List(s) Add Skipped: ', 'wp-marketing-automations-pro' ) . array_reduce( array_filter( $lists_added['skipped'] ), function ( $carry, $item ) {
					$name = $item->get_name();
					if ( empty( $carry ) ) {
						return $name;
					}

					return ! empty( $name ) ? $carry . ', ' . $name : $carry;
				}, '' );
		}

		if ( isset( $lists_added['assigned'] ) && count( $lists_added['assigned'] ) > 0 ) {
			$message[] = __( 'List(s) Added: ', 'wp-marketing-automations-pro' ) . array_reduce( array_filter( $lists_added['assigned'] ), function ( $carry, $item ) {
					$name = $item->get_name();
					if ( empty( $carry ) ) {
						return $name;
					}

					return ! empty( $name ) ? $carry . ', ' . $name : $carry;
				}, '' );
		}

		return array(
			'status'  => self::$RESPONSE_SUCCESS,
			'message' => $message,
		);
	}
}

/**
 * Register action
 */
/**
 * Register action
 */
BWFCRM_Core()->actions->register_action( 'add_to_lists', 'BWFCRM\Actions\Autonami\Add_To_Lists', __( 'Add Lists', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
