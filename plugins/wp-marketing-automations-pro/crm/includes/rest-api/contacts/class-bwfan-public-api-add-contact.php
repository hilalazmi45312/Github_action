<?php

/**
 * Class BWFAN_Rest_API_Add_Contact
 */

namespace BWFAN\Rest_API;
class Add_Contact extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::CREATABLE;
		$this->route    = '/contact/add';
		$this->required = [ 'email' ];
		$this->validate = [ 'contact_already_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * @param \BWFCRM_Contact $contact
	 *
	 * @return mixed
	 */
	public function process_api_call( $contact = '' ) {
		$result = array();
		$email  = $this->get_sanitized_arg( 'email', 'email' );
		if ( ! is_email( $email ) ) {
			$this->error_response( null, 422, 'email_invalid' );
		}
		$fields = isset( $this->args['fields'] ) && ! empty( $this->args['fields'] ) ? $this->args['fields'] : array();
		$stop_automation = isset( $this->args['stop_automation'] ) ? $this->args['stop_automation'] : false;


		// creating array of data
		$params = array(
			'f_name'     => isset( $this->args['f_name'] ) ? $this->get_sanitized_arg( 'f_name', 'text_field', $this->args['f_name'] ) : '',
			'l_name'     => isset( $this->args['l_name'] ) ? $this->get_sanitized_arg( 'l_name', 'text_field', $this->args['l_name'] ) : '',
			'country'    => isset( $this->args['country'] ) ? $this->get_sanitized_arg( 'country', 'text_field', $this->args['country'] ) : '',
			'state'      => isset( $this->args['state'] ) ? $this->get_sanitized_arg( 'state', 'text_field', $this->args['state'] ) : '',
			'source'     => isset( $this->args['source'] ) ? $this->get_sanitized_arg( 'source', 'text_field', $this->args['source'] ) : 'public_api',
			'contact_no' => isset( $this->args['contact_no'] ) ? $this->get_sanitized_arg( 'contact_no', 'text_field', $this->args['contact_no'] ) : '',
		);

		if ( ! empty( $fields ) ) {
			// check and remove the fields which does not exists
			$field_data = \BWFAN_Rest_API_Common::remove_invalid_fields( $fields );

			// store fields which does not exists
			if ( ! empty( $field_data['field_not_exists'] ) ) {
				$result['field_not_exists'] = $field_data['field_not_exists'];
			}
			$params = array_replace( $params, $field_data['fields'] );
		}
		$contact = \BWFAN_Rest_API_Common::create_contact( $email, $params, true );

		// setting tags if provided
		if ( isset( $this->args['tags'] ) ) {
			$tag_ids     = is_array( $this->args['tags'] ) ? $this->args['tags'] : array( $this->args['tags'] );
			$contact->set_tags_v2( $tag_ids, $stop_automation );

		}

		// setting lists if provided
		if ( isset( $this->args['lists'] ) ) {
			$list_ids     = is_array( $this->args['lists'] ) ? $this->args['lists'] : array( $this->args['lists'] );
			$contact->set_lists_v2( $list_ids, $stop_automation );
		}

		// saving contact
		$contact->save();

		// return if not able to create or some error occurred
		if ( ! $contact->is_contact_exists() ) {
			$this->error_response( '', 422, 'unknown_error' );
		}

		// validating status if provided and updating it only when update contact is successful
		if ( isset( $this->args['status'] ) ) {
			\BWFAN_Rest_API_Common::change_contact_status( $contact, $this->args['status'] );
		}

		return $this->success_response( [ 'contact' => \BWFAN_Rest_API_Common::set_contact_status( $contact ) ], 'Contact created successfully' );
	}

}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Add_Contact' );

