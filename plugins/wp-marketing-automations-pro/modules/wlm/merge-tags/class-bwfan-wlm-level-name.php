<?php

class BWFAN_WLM_Level_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wishlist_member_level_name';
		$this->tag_description = __( 'WishList Member Level Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wishlist_member_level_name', array( $this, 'parse_shortcode' ) );
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

		$level_id = BWFAN_Merge_Tag_Loader::get_data( 'level_id' );

		if ( empty( $level_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$level_name = wlmapi_get_level( $level_id );
		$level_name = isset( $level_name['level'] ) && isset( $level_name['level']['name'] ) ? $level_name['level']['name'] : '';

		return $this->parse_shortcode_output( $level_name, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Platinum';
	}
}

/**
 * Register this merge tag to a group.
 */
if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wlm_wishlist', 'BWFAN_WLM_Level_Name', null, __( 'WooCommerce Membership', 'wp-marketing-automations-pro' ) );
}
