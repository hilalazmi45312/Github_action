<?php

namespace BWFCRM\Actions\Autonami;

use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;
use WP_Error;

/**
 * Add tags action class
 */
class Add_Tags extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'add_tags';
		$this->nice_name   = __( 'Add Tags', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
		$this->event_slug  = 'crm_assigned_tag';
	}

	/**
	 * Returns action field schema
	 *
	 * @return array
	 */
	public function get_action_schema() {
		return [
			'type' => 'search',
			'meta' => [
				'autocompleter' => 'tag',
				'addnew'        => true,
			]
		];
	}

	/**
	 *  process action
	 *
	 * @param $contact \BWFCRM_Contact
	 * @param $tags
	 *
	 * @return array|false
	 */
	public function handle_action( $contact, $tags ) {
		$tags = array_column( $tags, 'id' );

		if ( empty( $tags ) ) {
			return 'skip';
		}

		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\Add_Tags' ) ) {
			return false;
		}

		$call_obj = new Calls\Add_Tags;

		/**
		 * Process call
		 */
		$tags_added = $call_obj->process_call( $contact, $tags );

		return $this->get_response( $tags, $tags_added );
	}

	public function get_response( $tags, $tags_added ) {
		$message = array();
		if ( $tags_added instanceof WP_Error ) {
			$message[] = __( 'Tag(s) Add Failed: ', 'wp-marketing-automations-pro' ) . array_reduce( $tags, function ( $carry, $item ) {
					$name = $item['value'];
					if ( empty( $carry ) ) {
						return $name;
					}

					return ! empty( $name ) ? $carry . ', ' . $name : $carry;
				}, '' );
			$message[] = __( 'Tag(s) Add Failed. Error: ', 'wp-marketing-automations-pro' ) . $tags_added->get_error_message();

			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => $message,
			);
		}

		if ( isset( $tags_added['skipped'] ) && count( $tags_added['skipped'] ) > 0 ) {
			$message[] = __( 'Tag(s) Add Skipped: ', 'wp-marketing-automations-pro' ) . array_reduce( array_filter( $tags_added['skipped'] ), function ( $carry, $item ) {
					$name = $item->get_name();
					if ( empty( $carry ) ) {
						return $name;
					}

					return ! empty( $name ) ? $carry . ', ' . $name : $carry;
				}, '' );
		}

		if ( isset( $tags_added['assigned'] ) && count( $tags_added['assigned'] ) > 0 ) {
			$message[] = __( 'Tag(s) Added: ', 'wp-marketing-automations-pro' ) . array_reduce( array_filter( $tags_added['assigned'] ), function ( $carry, $item ) {
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
BWFCRM_Core()->actions->register_action( 'add_tags', 'BWFCRM\Actions\Autonami\Add_Tags', __( 'Add Tags', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
