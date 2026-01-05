<?php

/**
 * Class BWFAN_Rest_API_Update_Tags
 */

namespace BWFAN\Rest_API;
class Update_Tags extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/tag/update/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'tag' ];
		$this->validate = [ 'tag_not_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * @param \BWFCRM_Tag $tag
	 *
	 * @return mixed
	 */
	public function process_api_call( $tag = '' ) {
		$tag_id   = $this->get_sanitized_arg( 'id', 'text_field' );
		$tag_name = $this->get_sanitized_arg( 'tag', 'text_field' );

		$current_name = $tag->get_name();
		if ( $tag_name === $current_name ) {
			$updated_tag = [ 'ID' => $tag_id, 'name' => $tag_name ];

			return $this->success_response( [ 'tags' => $updated_tag ], __( 'Tag updated successfully', 'wp-marketing-automations-pro' ) );
		}

		/** Check if list is already exists */
		$already_exists = \BWFCRM_Tag::get_terms( \BWFCRM_Term_Type::$TAG, [], $tag_name, 0, 0, ARRAY_A, 'exact' );
		if ( ! empty( $already_exists ) ) {
			$response = __( "Tag already exists with name: " . $tag_name, 'wp-marketing-automations-pro' );

			$this->error_response( $response, 422, 'unknown_error' );
		}

		$tag->set_name( $tag_name );
		$update_tag = $tag->save();

		if ( empty( $update_tag ) ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		$updated_tag = [ 'ID' => $tag_id, 'name' => $tag->get_name() ];

		return $this->success_response( [ 'tags' => $updated_tag ], __( 'Tag updated successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Update_Tags' );
