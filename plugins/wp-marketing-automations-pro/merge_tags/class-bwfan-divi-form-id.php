<?php

class BWFAN_Divi_Form_ID extends BWFAN_Merge_Tag {

	private static $instance = null;
	protected $support_v2 = true;
	protected $support_v1 = false;

	public function __construct() {
		$this->tag_name        = 'divi_form_id';
		$this->tag_description = __( 'Form ID', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_divi_form_id', array( $this, 'parse_shortcode' ) );
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data();

		if ( true === $get_data['is_preview'] ) {
			return $this->parse_shortcode_output( $this->get_dummy_preview(), $attr );
		}

		$form_id = $get_data['form_id'];

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
		return '11';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( function_exists( 'bwfan_is_divi_forms_active' ) && bwfan_is_divi_forms_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'divi_forms', 'BWFAN_Divi_Form_ID', null, __( 'Divi Forms', 'wp-marketing-automations-pro' ) );
}
