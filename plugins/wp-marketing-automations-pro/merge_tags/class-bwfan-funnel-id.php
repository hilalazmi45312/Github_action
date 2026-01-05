<?php

class BWFAN_Funnel_ID extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'funnel_id';
		$this->tag_description = __( 'Funnel ID', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_funnel_id', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_upstroke_funnel_id', array( $this, 'parse_shortcode' ) );
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
	 * @return int|mixed|void
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}

		$funnel_id = BWFAN_Merge_Tag_Loader::get_data( 'funnel_id' );

		return $this->parse_shortcode_output( $funnel_id, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return integer
	 */
	public function get_dummy_preview() {
		return 1235;
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_funnel', 'BWFAN_Funnel_ID', null, __( 'Funnel', 'wp-marketing-automations-pro' ) );
}
