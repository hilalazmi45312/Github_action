<?php

/**
 * Class BWFAN_Rest_API_Delete_Tags
 */

namespace BWFAN\Rest_API;
class Delete_Tags extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::DELETABLE;
		$this->route    = '/tag/(?P<id>[\\d]+)';
		$this->required = [ 'id' ];
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
		$tag_id = $this->get_sanitized_arg( 'id', 'text_field' );

		// storing tag details before deleting to pass to api response
		$deleted_tag = [ 'ID' => $tag_id, 'name' => $tag->get_name() ];

		$delete_tag = \BWFCRM_Tag::delete_tag( absint( $tag_id ) );

		if ( false === $delete_tag ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		return $this->success_response( [ 'tags' => $deleted_tag ], __( 'Tag deleted successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Delete_Tags' );
