<?php

class BWFAN_AFFWP_Affiliate_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'affwp_affiliate_name';
		$this->tag_description = __( 'Affiliate Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_affwp_affiliate_name', array( $this, 'parse_shortcode' ) );
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

		$affiliate_id = BWFAN_Merge_Tag_Loader::get_data( 'affiliate_id' );
		$affiliate    = affwp_get_affiliate( $affiliate_id );

		if ( false === $affiliate ) {
			return;
		}

		$affiliate_user_id = $affiliate->user_id;
		if ( empty( $affiliate_user_id ) ) {
			return;
		}

		$affiliate_user_data = get_userdata( $affiliate_user_id );
		if ( ! $affiliate_user_data instanceof WP_User ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		if ( ! $affiliate_user_data->data ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		if ( ! $affiliate_user_data->data->display_name ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$affiliate_display_name = trim( ucwords( $affiliate_user_data->data->display_name ) );

		return $this->parse_shortcode_output( $affiliate_display_name, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'John Doe';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'aff_affiliate', 'BWFAN_AFFWP_Affiliate_Name', null, __( 'AffiliateWP', 'wp-marketing-automations-pro' ) );
}
