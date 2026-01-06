<?php

class BWFCRM_API_Apply_Lists extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/contacts/(?P<contact_id>[\\d]+)/lists';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'lists'      => array(),
		);
	}

	public function process_api_call() {
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );
		if ( empty( $contact_id ) ) {

			$this->response_code = 404;

			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No contact found with given id #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

		$lists = $this->args['lists'];
		$lists = array_filter( array_values( $lists ) );
		if ( empty( $lists ) ) {
			$response            = __( 'Required Lists missing', 'wp-marketing-automations-pro' );
			$this->response_code = 404;

			return $this->error_response( $response );
		}

		$added_lists = $contact->add_lists( $lists );

		if ( is_wp_error( $added_lists ) ) {
			$this->response_code = 500;

			return $this->error_response( '', $added_lists );
		}

		if ( empty( $added_lists ) ) {
			$this->response_code = 200;

			return $this->success_response( '', __( 'Provided lists are applied already.', 'wp-marketing-automations-pro' ) );
		}

		$result      = [];
		$lists_added = array_map( function ( $list ) {
			return $list->get_array();
		}, $added_lists );

		$message = __( 'List(s) assigned', 'wp-marketing-automations-pro' );
		if ( count( $lists ) !== count( $added_lists ) ) {
			$added_lists_names = array_map( function ( $list ) {
				return $list->get_name();
			}, $added_lists );

			$added_lists_names   = implode( ', ', $added_lists_names );
			$this->response_code = 200;
			$message             = __( 'Some Lists are applied already. Applied Lists are: ' . $added_lists_names, 'wp-marketing-automations-pro' );
		}
		$result['list_added']    = is_array( $lists_added ) ? array_values( $lists_added ) : $lists_added;
		$result['last_modified'] = $contact->contact->get_last_modified();

		return $this->success_response( $result, $message );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Apply_Lists' );
