<?php

class BWFAN_AFFWP_Affiliate_Rate extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'affwp_affiliate_rate';
		$this->tag_description = __( 'Affiliate Rate', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_affwp_affiliate_rate', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_affwp_rate', array( $this, 'parse_shortcode' ) );
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

		$affiliate = affwp_get_affiliate( $affiliate_id );
		if ( ! $affiliate ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$affiliate_rate = affwp_get_affiliate_rate( $affiliate, true );

		return $this->parse_shortcode_output( $affiliate_rate, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return '8%';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'aff_affiliate', 'BWFAN_AFFWP_Affiliate_Rate', null, __( 'AffiliateWP', 'wp-marketing-automations-pro' ) );
}
