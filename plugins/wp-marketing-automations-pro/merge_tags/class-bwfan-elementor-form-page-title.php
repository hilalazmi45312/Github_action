<?php

class BWFAN_Elementor_Form_Page_Title extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'elementor_form_page_title';
		$this->tag_description = __( 'Page Title', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_elementor_form_page_title', array( $this, 'parse_shortcode' ) );
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

		$page_id = BWFAN_Merge_Tag_Loader::get_data( 'page_id' );
		if ( empty( $page_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$page_title = get_the_title( $page_id );

		return $this->parse_shortcode_output( $page_title, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Test';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_elementorpro_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'elementor-forms', 'BWFAN_Elementor_Form_Page_Title' );
}
