<?php

/**
 * Class BWFCRM_API_Get_Transactional_Preview
 */
class BWFCRM_API_Get_Transactional_Preview extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * BWFCRM_API_Get_Transactional_Preview constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->method            = WP_REST_Server::READABLE;
		$this->route             = '/transactional-mail/(?P<type>[a-zA-Z_-]+)/preview';
	}

	/**
	 * Process API call
	 *
	 * @return array|WP_Error
	 */
	public function process_api_call() {
		$type      = $this->get_sanitized_arg( 'type' );
		if ( empty( $type ) ) {
			return $this->error_response( __( 'Empty transactional mail type', 'wp-marketing-automations-pro' ), null, 400 );
		}
		$lang = $this->args['lang'] ?? '';
		$this->response_code = 200;

		// Check if transactional mails are loaded
		if ( ! isset( BWFCRM_Core()->transactional_mails ) ) {
			return $this->success_response( [] );
		}
		// Initialize transactional mails class
		$mail_class = BWFCRM_Core()->transactional_mails;

		// Get transactional mail data by slug
		$template_data =  $mail_class->get_transactional_mail_by_slug( $type, $lang );

		// Check if transactional mail not found
		if ( empty( $template_data ) ) {
			return $this->error_response( __( 'Transactional mail not found', 'wp-marketing-automations-pro' ), null, 404 );
		}

		// Get template content
		$content = $template_data['template'];

		if( method_exists( 'BWFAN_Common', 'bwfan_before_send_mail' ) ){
			BWFAN_Common::bwfan_before_send_mail( 'block' );
		}

		// Set data for merge tags
		BWFAN_Merge_Tag_Loader::set_data( [ 'is_preview' => true ] );

		// Correct shortcode string for email body
		$body = method_exists( 'BWFAN_Common', 'correct_shortcode_string' ) ? BWFAN_Common::correct_shortcode_string( $content, 'block' ) : $content;

		// Decode merge tags
		$body = BWFAN_Common::decode_merge_tags( $body );

		// Update links in email body
		$body = BWFAN_Common::bwfan_correct_protocol_url( $body );

		// Get final email body
		$body = BWFCRM_Core()->conversation->apply_template_by_type( $body, 'block', '' );

		$this->response_code = 200;

		// Return success response
		return $this->success_response( [
			'body' => $body,
			'data' => [
				'subject'   => ! empty( $template_data['subject'] ) ? $template_data['subject'] : __( 'Test Subject', 'wp-marketing-automations-pro' ),
				'preheader' => ! empty( $template_data['data']['preheader'] ) ? $template_data['data']['preheader']: '',
				'utmEnabled' => ! empty( $template_data['data']['utmEnabled'] ) && boolval( $template_data['data']['utmEnabled'] ),
				'utmDetails' => ! empty( $template_data['data']['utm'] ) ? $template_data['data']['utm'] : [],
				'overRideSenderInfo' => true,
				'overRideInfo' => [
					'from_name' => ! empty( $template_data['data']['from_name'] ) ? $template_data['data']['from_name'] : '',
					'from_email' => ! empty( $template_data['data']['from_email'] ) ? $template_data['data']['from_email'] : '',
					'reply_to' => ! empty( $template_data['data']['reply_to_email'] ) ? $template_data['data']['reply_to_email'] : '',
				]
			],

		] );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Transactional_Preview' );