<?php

class BWFAN_CRM_Tag_ID extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'crm_tag_id';
		$this->tag_description = __( 'Tag ID', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_crm_tag_id', array( $this, 'parse_shortcode' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Parse the merge tag and return its value.
	 *
	 * @param $attr
	 *
	 * @return mixed|string|void
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}

		$tag_id = BWFAN_Merge_Tag_Loader::get_data( 'tag_id' );

		if ( empty( $tag_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		return $this->parse_shortcode_output( $tag_id, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 */
	public function get_dummy_preview() {
		return 154;
	}
}

/**
 * Register this merge tag to a group.
 */
if ( class_exists( 'BWFCRM_Tag' ) ) {
	BWFAN_Merge_Tag_Loader::register( 'bwfan_crm_tags', 'BWFAN_CRM_Tag_ID', null, __( 'FunnelKit Automations Tag', 'wp-marketing-automations-pro' ) );
}
