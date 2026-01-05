<?php

/**
 * Campaign Email Preview API class
 */
class BWFCRM_API_Campaign_Email_Preview extends BWFCRM_API_Base {

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
		$this->route  = '/broadcast/email-preview';
	}

	/**
	 * Default arg.
	 */
	public function default_args_values() {
		return array(
			'content' => 0
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$content = '';
		$type    = ! empty( $this->args['type'] ) ? $this->args['type'] : 'rich';
		if ( ! empty( $this->args['content'] ) ) {
			$content = $this->args['content'];
		}
		if ( method_exists( 'BWFAN_Common', 'bwfan_before_send_mail' ) ) {
			BWFAN_Common::bwfan_before_send_mail( $type );
		}
		/** Email Subject */
		BWFAN_Merge_Tag_Loader::set_data( [ 'is_preview' => true ] );
		$body = method_exists( 'BWFAN_Common', 'correct_shortcode_string' ) ? BWFAN_Common::correct_shortcode_string( $content, $type ) : $content;
		$body = BWFAN_Common::decode_merge_tags( $body );
		$body = BWFAN_Common::bwfan_correct_protocol_url( $body );

		$body = BWFCRM_Core()->conversation->apply_template_by_type( $body, $type, '' );

		$this->response_code = 200;

		return $this->success_response( [ 'body' => $body ] );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Campaign_Email_Preview' );