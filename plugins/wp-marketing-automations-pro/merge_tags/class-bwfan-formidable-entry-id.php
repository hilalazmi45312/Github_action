<?php
/**
 * Merge tags register for formidable Entry id
 */
if ( ! class_exists( 'BWFAN_Formidable_Entry_ID' ) ) {

	class BWFAN_Formidable_Entry_ID extends BWFAN_Merge_Tag {

		private static $instance = null;
		protected $support_v2 = true;
		protected $support_v1 = false;

		public function __construct() {
			$this->tag_name        = 'formidable_entry_id';
			$this->tag_description = __( 'Entry ID', 'wp-marketing-automations-pro' );
			add_shortcode( 'bwfan_formidable_entry_id', array( $this, 'parse_shortcode' ) );
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

			$entry_id = isset( $get_data['entry_id'] ) ? $get_data['entry_id'] : '';

			return $this->parse_shortcode_output( $entry_id, $attr );
		}

		/**
		 * Show dummy value of the current merge tag.
		 *
		 * @return string
		 */
		public function get_dummy_preview() {
			return '10';
		}
	}

	/**
	 * Register this merge tag to a group. if formidable addon is activated.
	 */
	if ( function_exists( 'bwfan_is_formidable_forms_active' ) && bwfan_is_formidable_forms_active() ) {
		BWFAN_Merge_Tag_Loader::register( 'formidable_forms', 'BWFAN_Formidable_Entry_ID', null, __( 'Formidable Forms', 'wp-marketing-automations-pro' ) );
	}
}
