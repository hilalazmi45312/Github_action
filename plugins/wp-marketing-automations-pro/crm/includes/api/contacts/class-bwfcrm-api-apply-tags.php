<?php

class BWFCRM_API_Apply_Tags extends BWFCRM_API_Base {
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
		$this->route  = '/contacts/(?P<contact_id>[\\d]+)/tags';
	}

	public function default_args_values() {
		return array(
			'contact_id' => 0,
			'tags'       => array(),
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

			return $this->error_response( __( 'No contact found related with contact id :' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

		$tags = $this->args['tags'];
		$tags = array_filter( array_values( $tags ) );
		if ( empty( $tags ) ) {
			$response            = __( 'No Tags provided', 'wp-marketing-automations-pro' );
			$this->response_code = 400;

			return $this->error_response( $response );
		}

		$added_tags = $contact->add_tags( $tags );
		if ( is_wp_error( $added_tags ) ) {
			$this->response_code = 500;

			return $this->error_response( '', $added_tags );
		}

		if ( empty( $added_tags ) ) {
			$this->response_code = 200;

			return $this->success_response( '', __( 'Provided tags are applied already.', 'wp-marketing-automations-pro' ) );
		}
		$tags_added = array_map( function ( $tag ) {
			return $tag->get_array();
		}, $added_tags );
		$result     = [];
		$message    = __( 'Tag(s) added', 'wp-marketing-automations-pro' );
		if ( count( $tags ) !== count( $added_tags ) ) {
			$applied_tags_names  = array_map( function ( $tag ) {
				return $tag->get_name();
			}, $added_tags );
			$applied_tags_names  = implode( ', ', $applied_tags_names );
			$this->response_code = 200;
			$message             = __( 'Some Tags are applied already. Applied Tags are: ' . $applied_tags_names, 'wp-marketing-automations-pro' );
		}
		$result['tags_added']    = is_array( $tags_added ) ? array_values( $tags_added ) : $tags_added;
		$result['last_modified'] = $contact->contact->get_last_modified();

		return $this->success_response( $result, $message );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Apply_Tags' );
