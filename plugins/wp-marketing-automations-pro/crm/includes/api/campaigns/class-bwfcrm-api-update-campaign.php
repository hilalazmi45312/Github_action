<?php
/**
 * Campaign Update API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Update API class
 */
class BWFCRM_API_Update_Campaign extends BWFCRM_API_Base {
	/**
	 * BWFCRM_Core obj
	 *
	 * @var BWFCRM_Core
	 */
	public static $ins;

	/***
	 * Total count
	 *
	 * @return BWFCRM_API_Update_Campaign
	 * @var int
	 *
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
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/broadcast/(?P<campaign_id>[\\d]+)';
	}

	/**
	 * Default args
	 */
	public function default_args_values() {
		return array(
			'campaign_id' => 0,
			'step'        => 0,
			'content'     => array()
		);
	}

	/**
	 * API callback
	 */
	public function process_api_call() {
		$campaign_id = $this->get_sanitized_arg( 'campaign_id', 'key' );
		$step        = $this->get_sanitized_arg( 'step', 'key' );

		/** Check fo data */
		$data = isset( $this->args['content'] ) ? $this->args['content'] : [];
		$data = BWFAN_Common::is_json( $data ) ? json_decode( $data, true ) : $data;

		/** For old version */
		if ( empty( $data ) && isset( $this->args['data'] ) && ! empty( $this->args['data'] ) ) {
			$data = BWFAN_Common::is_json( $this->args['data'] ) ? json_decode( $this->args['data'], true ) : $this->args['data'];
		}

		if ( empty( $campaign_id ) || ! $step || intval( $step ) < 1 ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Unable to save broadcast, ID is missing', 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $data ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Unable to save broadcast, data is missing', 'wp-marketing-automations-pro' ) );
		}

		$campaign = BWFAN_Model_Broadcast::update_campaign( $campaign_id, $step, $data );
		/** validating the content */
		if ( isset( $data['content'] ) && is_array( $data['content'] ) ) {
			foreach ( $data['content'] as $key => $content ) {
				try {
					BWFCRM_Core()->conversation->emogrify_email( $content['body'] );
					$campaign['data'][ 'validated_email_body_' . $key ] = true;
				} catch ( Error $e ) {
					$campaign['data'][ 'validated_email_body_' . $key ] = false;
				}
			}
		}

		$this->response_code = $campaign['status'];

		if ( 200 === $campaign['status'] ) {
			return $this->success_response( $campaign['data'], $campaign['message'] );
		}

		return $this->error_response( $campaign['message'] );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Update_Campaign' );
