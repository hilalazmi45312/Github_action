<?php
/**
 * Test SMS API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Test Mail API class
 */
class BWFCRM_API_Send_Test_SMS extends BWFCRM_API_Base {

	/**
	 * BWFCRM_Core obj
	 *
	 * @var BWFCRM_Core
	 */
	public static $ins;

	/**
	 * Return class instance
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = '/send-test-sms';
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'phone'    => '',
			'sms_body' => '',
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		if ( ! isset( $this->args ) || empty( $this->args ) || ! is_array( $this->args ) ) {
			return $this->error_response( __( 'No data found', 'wp-marketing-automations-pro' ) );
		}

		$phone = $this->args['phone'];
		if ( empty( $phone ) ) {
			return $this->error_response( __( 'Phone no is required.', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$sms_body = $this->args['sms_body'];
		if ( empty( $sms_body ) ) {
			return $this->error_response( __( 'SMS body is required.', 'wp-marketing-automations-pro' ), null, 400 );
		}

		BWFAN_Merge_Tag_Loader::set_data( array(
			'is_preview' => true,
		) );
		$sms_body = BWFAN_Common::decode_merge_tags( $sms_body );
		/** Append UTM parameters */
		$sms_body = BWFAN_PRO_Common::add_test_broadcast_utm_params( $sms_body, $this->args['sms_data'], 'sms' );

		$send_sms_result = BWFCRM_Common::send_sms( array(
			'to'      => $phone,
			'body'    => $sms_body,
			'is_test' => true
		) );

		if ( $send_sms_result instanceof WP_Error ) {
			return $this->error_response( 'Unable to send test sms. Error: ' . $send_sms_result->get_error_message(), null, 500 );
		}

		return $this->success_response( '', 'Test SMS Sent' );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Send_Test_SMS' );
