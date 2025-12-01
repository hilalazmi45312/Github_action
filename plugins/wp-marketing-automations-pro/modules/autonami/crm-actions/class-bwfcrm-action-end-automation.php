<?php

namespace BWFCRM\Actions\Autonami;

use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;

/**
 * End Automation action
 */
class End_Automation extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'end_automation';
		$this->nice_name   = __( 'End Automation', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
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
				'autocompleter' => 'automation',
				'addnew'        => false,
			]
		];
	}

	/**
	 * process action
	 *
	 * @param $contact
	 * @param $data
	 *
	 * @return array|mixed
	 */
	public function handle_action( $contact, $data ) {
		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\End_Automation' ) ) {
			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => __( 'End Automation: Call not found', 'wp-marketing-automations-pro' ),
			);
		}

		$call_obj = new Calls\End_Automation;

		/**
		 * Process call
		 */
		return $call_obj->process_call( $contact, $data );
	}
}

/**
 * Register Action
 */
BWFCRM_Core()->actions->register_action( 'end_automation', 'BWFCRM\Actions\Autonami\End_Automation', __( 'End Automation', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
