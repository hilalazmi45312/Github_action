<?php

class BWFAN_CRM_List_ID extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'crm_list_id';
		$this->tag_description = __( 'List ID', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_crm_list_id', array( $this, 'parse_shortcode' ) );
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

		$list_id = BWFAN_Merge_Tag_Loader::get_data( 'list_id' );

		if ( empty( $list_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		return $this->parse_shortcode_output( $list_id, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 */
	public function get_dummy_preview() {
		return 12;
	}
}

/**
 * Register this merge tag to a group.
 */
if ( class_exists( 'BWFCRM_Lists' ) ) {
	BWFAN_Merge_Tag_Loader::register( 'bwfan_crm_lists', 'BWFAN_CRM_List_ID', null, __( 'FunnelKit Automations List', 'wp-marketing-automations-pro' ) );
}
