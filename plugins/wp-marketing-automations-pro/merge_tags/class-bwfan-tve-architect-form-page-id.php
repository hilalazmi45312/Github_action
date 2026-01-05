<?php

class BWFAN_TVE_Architect_Form_Page_ID extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'thrive_architect_form_page_id';
		$this->tag_description = __( 'Form Page ID', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_thrive_architect_form_page_id', array( $this, 'parse_shortcode' ) );
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
		$form_id = BWFAN_Merge_Tag_Loader::get_data( 'form_page_id' );

		return $this->parse_shortcode_output( $form_id, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Dummy: 001';

	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_tve_architect_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'thrive_architect_forms', 'BWFAN_TVE_Architect_Form_Page_ID', null, __( 'Thrive Architect', 'wp-marketing-automations-pro' ) );
}
