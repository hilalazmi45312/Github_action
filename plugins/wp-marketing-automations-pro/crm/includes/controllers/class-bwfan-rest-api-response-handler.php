<?php

namespace BWFAN\Rest_API;
/**
 * Class Response_Handler
 * @package BWFAN\Public_API
 */
class Response_Handler {

	private static $ins = null;
	/*
	200 Success               -- Success
	400 Bad Request           -- Your request is invalid.
	401 Unauthorized          -- Invalid API key provided.
	403 Forbidden          	  -- Request rejected by the server.
	404 Not Found    		  -- The requested resource doesn't exist.
	405 Method Not Allowed    -- Server knows the request method, the method has been disabled and can not be used.
	422 Unprocessable Entity  -- The request failed validation or was not allowed for another reason.
	500 Internal Server Error -- Something went wrong.
	*/
	public $response_code = 500;

	public function __construct() {
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * @param string $message
	 * @param int $code
	 * @param null $wp_error
	 *
	 * @return \WP_Error
	 */
	public function error_response( $message = '', $code = 500, $wp_error = null, $err_code = '' ) {
		if ( ! empty( intval( $code ) ) ) {
			$this->response_code = $code;
		}

		$message = empty( $message ) && ! empty( $err_code ) ? $this->get_message( $err_code ) : $message;
		if ( empty( $message ) && $wp_error instanceof \WP_Error ) {
			$message = $wp_error->get_error_message();
		}

		wp_send_json( [
			'code'    => $err_code,
			'message' => $message,
		], $code );
	}

	/**
	 * @param $result_array
	 * @param string $message
	 * @param array $extra_data
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function success_response( $result_array, $message = '', $extra_data = array() ) {
		$response = $this->format_success_response( $result_array, $message, $this->response_code );
		if ( ! empty( $extra_data ) ) {
			$response['data'] = array_merge( $response['data'], $extra_data );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * @param $result_array
	 * @param string $message
	 * @param int $response_code
	 *
	 * @return array
	 */
	public function format_success_response( $result_array, $message = '', $response_code = 200 ) {

		return [
			'code'    => 'success',
			'data'    => $result_array,
			'message' => $message,
		];
	}

	/**
	 * Get message
	 *
	 * @param $code
	 *
	 * @return string
	 */
	public function get_message( $code ) {
		if ( empty( $code ) ) {
			return '';
		}
		$messages = [
			'required_fields_missing' => 'Some arguments are required which are missing.',
			'email_invalid'           => 'Contact Email Address is not valid',
			'already_exists'          => 'Already exists in the system',
			'api_key_missing'         => 'API key is missing.',
			'api_key_invalid'         => 'API key is not valid.',
			'api_key_inactive'        => 'API key is not active.',
			'permission_denied'       => 'API key does not have this permission',
			'contact_not_exists'      => 'Contact not exists',
			'unprocessable_entity'    => 'The request failed validation or was not allowed for another reason.',
			'unknown_error'           => 'Some error occurred',
			'data_not_found'          => 'Data not found',
			'method_not_allowed'      => 'Method not allowed'
		];

		return isset( $messages[ $code ] ) ? $messages[ $code ] : '';
	}
}

/**
 * registering the class
 */
if ( class_exists( 'BWFAN\Rest_API\Response_Handler' ) ) {
	\BWFCRM_Core::register( 'response_handler', 'BWFAN\Rest_API\Response_Handler' );
}