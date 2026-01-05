<?php

class BWFAN_Integrately_Integration extends BWFAN_Integration {

	private static $ins = null;

	public function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'Integrately', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Send Data', 'wp-marketing-automations-pro' );
		$this->group_slug = 'send_data';
		$this->priority   = 85;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Handle the responses for all the actions of this connector.
	 * Return 0 for try again, 2 for action halt, 3 for successful execution , 4 for permanent failure.
	 *
	 * @param $result
	 * @param $connector_slug
	 * @param $action_call_class_slug
	 *
	 * @return array
	 */
	public function handle_response( $result, $connector_slug, $action_call_class_slug, $action_data = null ) {
		// Required field missing error
		if ( isset( $result['bwfan_response'] ) ) {
			return array(
				'status'  => 4,
				'message' => $result['bwfan_response'],
			);
		}

		// Curl error
		if ( isset( $result['response'] ) && 500 === $result['response'] ) {
			return array(
				'status'  => 0,
				'message' => $result['body'],
			);
		}

		// Rate limit exceeded error
		if ( false === $result ) {
			return array(
				'status'  => 2,
				'message' => __( 'Time Out', 'wp-marketing-automations-pro' ),
			);
		}

		if ( ( isset( $result['response'] ) && 200 === $result['response'] ) && ( isset( $result['body'] ) && ! empty( $result['body'] ) ) ) {
			return array(
				'status' => 3,
			);
		}

		return array(
			'status'  => 4,
			'message' => __( 'Webhook not active in integrately.', 'wp-marketing-automations-pro' ),
		);
	}

}

/**
 * Register this class as an integration.
 */
BWFAN_Load_Integrations::register( 'BWFAN_Integrately_Integration' );
