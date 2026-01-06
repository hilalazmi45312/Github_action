<?php

class BWFAN_AFFWP_Selected_Range_Visits extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'affwp_selected_range_visits';
		$this->tag_description = __( 'Affiliate Selected Range Visits', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_affwp_selected_range_visits', array( $this, 'parse_shortcode' ) );
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

		$visits = BWFAN_Merge_Tag_Loader::get_data( 'visits' );

		return $this->parse_shortcode_output( $visits, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return '11';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'aff_report', 'BWFAN_AFFWP_Selected_Range_Visits', null, __( 'AffiliateWP', 'wp-marketing-automations-pro' ) );
}
