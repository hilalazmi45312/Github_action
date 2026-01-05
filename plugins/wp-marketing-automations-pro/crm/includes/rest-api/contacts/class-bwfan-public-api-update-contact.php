<?php

/**
 * Class BWFAN_Rest_API_Update_Contact
 */

namespace BWFAN\Rest_API;
class Update_Contact extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/contact/update/(?P<id>[\\d]+)';
		$this->required = [ 'id' ];
		$this->validate = [ 'contact_not_exists' ];
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
		$id = $this->get_sanitized_arg( 'id', 'text_field' );

		/** creating array of data */
		$params = array(
			'id'         => $id,
			'f_name'     => isset( $this->args['f_name'] ) ? $this->get_sanitized_arg( 'f_name', 'text_field', $this->args['f_name'] ) : '',
			'l_name'     => isset( $this->args['l_name'] ) ? $this->get_sanitized_arg( 'l_name', 'text_field', $this->args['l_name'] ) : '',
			'country'    => isset( $this->args['country'] ) ? $this->get_sanitized_arg( 'country', 'text_field', $this->args['country'] ) : '',
			'state'      => isset( $this->args['state'] ) ? $this->get_sanitized_arg( 'state', 'text_field', $this->args['state'] ) : '',
			'source'     => isset( $this->args['source'] ) ? $this->get_sanitized_arg( 'source', 'text_field', $this->args['source'] ) : '',
			'contact_no' => isset( $this->args['contact_no'] ) ? $this->get_sanitized_arg( 'contact_no', 'text_field', $this->args['contact_no'] ) : '',
		);

		/** Validating email if provided  */
		if ( isset( $this->args['email'] ) ) {
			$email = $this->get_sanitized_arg( 'email', 'email' );

			if ( ! is_email( $email ) ) {
				$this->error_response( null, 422, 'email_invalid' );
			}

			/** Checking if contact already exist */
			if ( ! empty( \BWFAN_Rest_API_Common::is_contact_exists( $email ) ) ) {
				$this->error_response( null, 422, 'already_exists' );
			}

			$params['email'] = $email;
		}

		/** Setting fields if provided */
		$result = array();
		$fields = isset( $this->args['fields'] ) && ! empty( $this->args['fields'] ) ? $this->args['fields'] : array();
		if ( ! empty( $fields ) ) {
			$field_data = \BWFAN_Rest_API_Common::remove_invalid_fields( $fields );
			if ( ! empty( $field_data['field_not_exists'] ) ) {
				$result['field_not_exists'] = $field_data['field_not_exists'];
			}

			if ( ! empty( $field_data['fields'] ) ) {
				$contact->set_fields( $field_data['fields'] );
			}

		}

		/** Removing empty keys */
		$params = array_filter( $params );

		/** Setting tags if provided */
		if ( isset( $this->args['tags'] ) && ! empty( $this->args['tags'] ) ) {
			$tags     = is_array( $this->args['tags'] ) ? $this->args['tags'] : array( $this->args['tags'] );
			$tag_data = \BWFCRM_Tag::get_tags( $tags );
			$tag_ids  = array_column( $tag_data, 'ID' );
			$contact->set_tags_v2( $tag_ids );
		}

		/** Setting lists if provided */
		if ( isset( $this->args['lists'] ) && ! empty( $this->args['tags'] ) ) {
			$lists      = is_array( $this->args['lists'] ) ? $this->args['lists'] : array( $this->args['lists'] );
			$lists_data = \BWFCRM_Lists::get_lists( $lists );
			$list_ids   = array_column( $lists_data, 'ID' );
			$contact->set_lists_v2( $list_ids );
		}

		/** Updating contact by contact id  */
		$update_contact = $contact->update( $params );
		if ( is_wp_error( $update_contact ) || ! $update_contact ) {
			$this->error_response( '', 422, 'unknown_error' );
		}

		/** Validating status if provided and updating it only when update contact is successful */
		if ( isset( $this->args['status'] ) ) {
			\BWFAN_Rest_API_Common::change_contact_status( $contact, $this->args['status'] );
		}

		/** Re-fetching contact obj after updating */
		$contact = new \BWFCRM_Contact( $contact->get_id() );

		return $this->success_response( [ 'contact' => \BWFAN_Rest_API_Common::get_formatted_contact_data( $contact ) ], __( 'Contact updated successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Update_Contact' );
