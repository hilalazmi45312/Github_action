<?php

class BWFAN_Caldera_Form_Title extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'caldera_form_title';
		$this->tag_description = __( 'Form Title', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_caldera_form_title', array( $this, 'parse_shortcode' ) );
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

		$form_title = BWFAN_Merge_Tag_Loader::get_data( 'form_title' );

		return $this->parse_shortcode_output( $form_title, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return 'Test';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_caldera_forms_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'calderaforms', 'BWFAN_Caldera_Form_Title', null, __( 'Caldera Form', 'wp-marketing-automations-pro' ) );
}
