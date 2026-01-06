<?php
/**
 * Merge tags register for breakdance Form id
 */
if ( ! class_exists( 'BWFAN_Breakdance_Form_ID' ) ) {

	class BWFAN_Breakdance_Form_ID extends BWFAN_Merge_Tag {

		private static $instance = null;
		protected $support_v2 = true;
		protected $support_v1 = false;

		public function __construct() {
			$this->tag_name        = 'breakdance_form_id';
			$this->tag_description = __( 'Form ID', 'wp-marketing-automations-pro' );
			add_shortcode( 'bwfan_breakdance_form_id', array( $this, 'parse_shortcode' ) );
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
			$from_post_id = isset( $get_data['form_post_id'] ) ? $get_data['form_post_id'] : '';
			if ( empty( $from_post_id ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}
			$form_id = get_post_meta( $from_post_id, '_breakdance_form_id', true ) ?? '';
			if ( empty( $form_id ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			return $this->parse_shortcode_output( $form_id, $attr );
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
	 * Register this merge tag to a group. if breakdance addon is activated.
	 */
	if ( function_exists( 'bwfan_is_breakdance_active' ) && bwfan_is_breakdance_active() ) {
		BWFAN_Merge_Tag_Loader::register( 'breakdance_forms', 'BWFAN_Breakdance_Form_ID', null, __( 'Breakdance Form', 'wp-marketing-automations-pro' ) );
	}
}
