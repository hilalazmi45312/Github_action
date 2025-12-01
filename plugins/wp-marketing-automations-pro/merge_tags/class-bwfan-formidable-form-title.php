<?php
/**
 * Merge tags register for formidable Form Title
 */
if ( ! class_exists( 'BWFAN_Formidable_Form_Title' ) ) {

	class BWFAN_Formidable_Form_Title extends BWFAN_Merge_Tag {

		private static $instance = null;
		protected $support_v2 = true;
		protected $support_v1 = false;

		public function __construct() {
			$this->tag_name        = 'formidable_form_title';
			$this->tag_description = __( 'Form Title', 'wp-marketing-automations-pro' );
			add_shortcode( 'bwfan_formidable_form_title', array( $this, 'parse_shortcode' ) );
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

			$form_title = isset( $get_data['form_title'] ) ? $get_data['form_title'] : '';

			return $this->parse_shortcode_output( $form_title, $attr );
		}

		/**
		 * Show dummy value of the current merge tag.
		 *
		 * @return string
		 */
		public function get_dummy_preview() {
			return '';
		}
	}

	/**
	 * Register this merge tag to a group. if formidable is activated.
	 */
	if ( function_exists( 'bwfan_is_formidable_forms_active' ) && bwfan_is_formidable_forms_active() ) {
		BWFAN_Merge_Tag_Loader::register( 'formidable_forms', 'BWFAN_Formidable_Form_Title', null, __( 'Formidable Forms', 'wp-marketing-automations-pro' ) );
	}
}
