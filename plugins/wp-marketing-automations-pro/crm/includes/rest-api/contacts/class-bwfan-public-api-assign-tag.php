<?php

/**
 * Class BWFAN_Rest_API_Assign_Tag
 */

namespace BWFAN\Rest_API;
class Assign_Tag extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/contact/tag-assign/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'tags' ];
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
	 * @return mixed|void
	 */
	public function process_api_call( $contact = '' ) {
		$tags            = isset( $this->args['tags'] ) ? $this->args['tags'] : array();
		$stop_automation = isset( $this->args['stop_automation'] ) ? $this->args['stop_automation'] : false;
		if ( ! is_array( $tags ) ) {
			$this->error_response( null, 422, 'unprocessable_entity' );
		}

		$tag_data = \BWFCRM_Tag::get_tags( $tags );
		if ( empty( $tag_data ) ) {
			$this->error_response( 'Tag(s) not found', 422, 'unprocessable_entity' );
		}
		$tag_ids     = array_column( $tag_data, 'ID' );
		$old_tags    = $contact->get_tags();
		$assign_tags = $contact->set_tags_v2( $tag_ids, $stop_automation );
		$contact->save();

		if ( ! is_array( $assign_tags ) ) {
			$this->error_response( 'No tag(s) to update', 422, 'unprocessable_entity' );
		}

		if ( is_wp_error( $assign_tags ) ) {
			$this->error_response( $assign_tags->get_error_messages(), 422, 'unknown_error' );
		}
		$newly_assigned_tags = array_diff( $assign_tags, $old_tags );
		$tag_results         = \BWFCRM_Tag::get_tags( $newly_assigned_tags );

		return $this->success_response( [ 'tags' => $tag_results ], __( 'Tag(s) assigned successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Assign_Tag' );
