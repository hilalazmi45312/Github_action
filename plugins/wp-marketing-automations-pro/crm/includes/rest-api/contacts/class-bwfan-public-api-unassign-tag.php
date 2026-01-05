<?php

/**
 * Class BWFAN_Rest_API_Unassign_Tag
 */

namespace BWFAN\Rest_API;
class Unassign_Tag extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/contact/tag-unassign/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'tagId' ];
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
		$tags = isset( $this->args['tagId'] ) ? $this->args['tagId'] : array();

		if ( ! is_array( $tags ) ) {
			$this->error_response( '', 422, 'unprocessable_entity' );
		}

		$tags_data = $contact->remove_tags( $tags );
		$contact->save();

		if ( empty( $tags_data ) ) {
			$this->error_response( 'No tag to unassign', 422, 'unprocessable_entity' );
		}

		if ( is_wp_error( $tags_data ) ) {
			$this->error_response( $tags_data->get_error_messages(), 422, 'unknown_error' );
		}
		$unassign_tags = \BWFCRM_Tag::get_tags( $tags_data );

		return $this->success_response( [ 'tags' => $unassign_tags ], __( 'Tag(s) unassigned successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Unassign_Tag' );
