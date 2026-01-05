<?php

class BWFAN_AFFWP_Affiliate_Data extends BWFAN_Merge_Tag {

	private static $instance = null;


	public function __construct() {
		$this->tag_name        = 'affwp_affiliate_data';
		$this->tag_description = __( 'Affiliate Data', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_affwp_affiliate_data', array( $this, 'parse_shortcode' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Show the html in popup for the merge tag.
	 */
	public function get_view() {
		$this->get_back_button();
		$this->data_key();
		if ( $this->support_fallback ) {
			$this->get_fallback();
		}

		$this->get_preview();
		$this->get_copy_button();
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

		$affiliate_id = BWFAN_Merge_Tag_Loader::get_data( 'affiliate_id' );

		if ( empty( $affiliate_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$item_key     = $attr['key'];
		$return_value = affwp_get_affiliate_meta( $affiliate_id, $item_key, true );

		return $this->parse_shortcode_output( $return_value, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return __( 'Value of the key', 'wp-marketing-automations-pro' );
	}

	/**
	 * Return mergetag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		return [
			[
				'id'          => 'key',
				'label'       => __( 'Meta Key', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => '',
				'placeholder' => '',
				'hint'        => __( 'Input the correct meta key in order to get the data', 'wp-marketing-automations-pro' ),
				'required'    => true,
				'toggler'     => array(),
			],
		];
	}


}

/**
 * Register this merge tag to a group.
 */
if ( function_exists( 'bwfan_is_affiliatewp_active' ) && bwfan_is_affiliatewp_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'aff_affiliate', 'BWFAN_AFFWP_Affiliate_Data' );
}
