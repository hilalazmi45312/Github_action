<?php
/**
 * Test WhatsApp Message API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Test WhatsApp API class
 */
class BWFCRM_API_Send_Test_WhatsApp_Message extends BWFCRM_API_Base {

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
		$this->route  = '/send-test-whatsapp-message';
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'phone'    => '',
			'sms_body' => ''
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
		$data      = [];
		$imagedata = [];
		$first     = false;
		if ( ! empty( $sms_body ) ) {
			if ( isset( $sms_body['body'] ) && ! empty( $sms_body ) ) {

				/** Decode Merge Tags for Preview */
				BWFAN_Merge_Tag_Loader::set_data( array(
					'is_preview' => true,
				) );
				$sms_body['body'] = BWFAN_Common::decode_merge_tags( $sms_body['body'] );

				$data = array(
					'type' => 'text',
					'data' => $sms_body['body'],
				);
			}
			if ( isset( $sms_body['whatsAppImage'] ) && $sms_body['whatsAppImage'] && isset( $sms_body['whatsAppImageSetting'] ) && isset( $sms_body['whatsAppImageSetting']['imageURL'] ) && ! empty( $sms_body['whatsAppImageSetting']['imageURL'] ) ) {
				$imagedata = array(
					'type' => 'image',
					'data' => $sms_body['whatsAppImageSetting']['imageURL'],
				);
				if ( isset( $sms_body['whatsAppImageSetting']['position'] ) && ! empty( $sms_body['whatsAppImageSetting']['position'] ) && $sms_body['whatsAppImageSetting']['position'] != 'after' ) {
					$first = true;
				}
			}
		}
		$finalData = [];
		if ( ! empty( $imagedata ) ) {
			if ( $first ) {
				$finalData = [ $imagedata, $data ];
			} else {
				$finalData = [ $data, $imagedata ];
			}
		} else {
			$finalData[] = $data;
		}

		$response = BWFCRM_Core()->conversation->send_whatsapp_message( $phone, $finalData );

		if ( $response['status'] == true ) {
			return $this->success_response( '', 'Test Message Sent' );
		}

		return $this->error_response( isset( $response['msg'] ) && ! empty( $response['msg'] ) ? $response['msg'] : 'Unable to send test message', null, 500 );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Send_Test_WhatsApp_Message' );
