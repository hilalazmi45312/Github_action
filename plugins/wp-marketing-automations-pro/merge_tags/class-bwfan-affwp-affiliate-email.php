<?php

class BWFAN_AFFWP_Affiliate_Email extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'affwp_affiliate_email';
		$this->tag_description = __( 'Affiliate Email', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_affwp_affiliate_email', array( $this, 'parse_shortcode' ) );
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

		$affiliate_id    = BWFAN_Merge_Tag_Loader::get_data( 'affiliate_id' );
		$affiliate_email = affwp_get_affiliate_email( $affiliate_id );

		if ( empty( $affiliate_email ) ) {
			return;
		}

		return $this->parse_shortcode_output( $affiliate_email, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 *
	 */
	public function get_dummy_preview() {
		return get_bloginfo( 'admin_email' );
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'aff_affiliate', 'BWFAN_AFFWP_Affiliate_Email', null, __( 'AffiliateWP', 'wp-marketing-automations-pro' ) );
}
