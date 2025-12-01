<?php

class BWFAN_CRM_Field_Value extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'crm_field_value';
		$this->tag_description = __( 'Field Value', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_crm_field_value', array( $this, 'parse_shortcode' ) );
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

		$field_value = BWFAN_Merge_Tag_Loader::get_data( 'new_value' );

		if ( empty( $field_value ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		return $this->parse_shortcode_output( $field_value, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 */
	public function get_dummy_preview() {
		return 'List';
	}
}

/**
 * Register this merge tag to a group.
 */
if ( class_exists( 'BWFCRM_Fields' ) ) {
	BWFAN_Merge_Tag_Loader::register( 'bwfan_crm_fields', 'BWFAN_CRM_Field_Value', null, __( 'FunnelKit Automations Fields', 'wp-marketing-automations-pro' ) );
}
