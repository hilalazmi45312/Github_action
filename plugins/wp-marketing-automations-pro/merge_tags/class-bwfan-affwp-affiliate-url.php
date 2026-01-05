<?php

class BWFAN_AFFWP_Affiliate_URL extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'affwp_affiliate_url';
		$this->tag_description = __( 'Affiliate Url', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_affwp_affiliate_url', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_affwp_url', array( $this, 'parse_shortcode' ) );
		$this->is_crm_broadcast = true;
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

		/** If affiliate id is empty and contact id is available */
		if ( empty( $affiliate_id ) ) {
			$affiliate_id = $this->get_affiliate_id();
		}

		if ( ! $affiliate_id ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$args = array(
			'affiliate_id' => $affiliate_id,
		);

		$affiliate_referral_url = affwp_get_affiliate_referral_url( $args );

		return $this->parse_shortcode_output( $affiliate_referral_url, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return '';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'aff_affiliate', 'BWFAN_AFFWP_Affiliate_URL', null, __( 'AffiliateWP', 'wp-marketing-automations-pro' ) );
}
