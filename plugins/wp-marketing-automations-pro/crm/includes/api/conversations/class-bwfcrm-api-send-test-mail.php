<?php
/**
 * Test Mail API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Test Mail API class
 */
class BWFCRM_API_Send_Test_Mail extends BWFCRM_API_Base {

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
		$this->route  = '/send-test-email';
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'email'   => '',
			'content' => 0
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$content = isset( $this->args['content'] ) ? $this->args['content'] : [];
		$content = BWFAN_Common::is_json( $content ) ? json_decode( $content, true ) : $content;
		if ( empty( $content ) ) {
			return $this->error_response( __( 'No content data found', 'wp-marketing-automations-pro' ) );
		}

		if ( isset( $content['mail_data'] ) && is_array( $content['mail_data'] ) ) {
			$mail_data = $content['mail_data'];
			unset( $content['mail_data'] );
			$content = array_replace( $content, $mail_data );
		}

		$content['email'] = $this->get_sanitized_arg( 'email', 'text_field' );

		if ( BWFAN_Common::send_test_email( $content ) ) {
			return $this->success_response( '', 'Test Email Sent' );
		}

		return $this->error_response( 'Unable to send test email', null, 500 );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Send_Test_Mail' );
