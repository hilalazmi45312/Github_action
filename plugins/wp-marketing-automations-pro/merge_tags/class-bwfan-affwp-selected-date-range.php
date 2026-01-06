<?php

class BWFAN_AFFWP_Selected_Date_Range extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'affwp_selected_date_range';
		$this->tag_description = __( 'Affiliate Selected Date Range', 'wp-marketing-automations-pro' );
		$this->support_date    = true;
		$this->support_modify  = false;
		add_shortcode( 'bwfan_affwp_selected_date_range', array( $this, 'parse_shortcode' ) );
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

		$format = isset( $attr['format'] ) ? $attr['format'] : 'j M Y';
		$from   = date( $format, strtotime( BWFAN_Merge_Tag_Loader::get_data( 'from' ) ) );
		$to     = date( $format, strtotime( BWFAN_Merge_Tag_Loader::get_data( 'to' ) ) );

		$range = $from . ' - ' . $to;

		return $this->parse_shortcode_output( $range, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		$from = date( 'Y-m-d', strtotime( 'first day of last month' ) );
		$to   = date( 'Y-m-d', strtotime( 'last day of last month' ) );

		$format = get_option( 'date_format' );
		$from   = date( $format, strtotime( $from ) );
		$to     = date( $format, strtotime( $to ) );

		return $from . ' - ' . $to;
	}

	/**
	 * Return mergetag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		$formats      = BWFAN_PRO_Common::get_date_formats();
		$date_formats = array_map( function ( $data ) {
			return [
				'value' => $data['format'],
				'label' => date( $data['format'] ),
			];
		}, $formats );

		return [
			[
				'id'          => 'format',
				'type'        => 'select',
				'options'     => $date_formats,
				'label'       => __( 'Select Date Format', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Select', 'wp-marketing-automations-pro' ),
				"required"    => false,
				"description" => ""
			],
		];
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'aff_report', 'BWFAN_AFFWP_Selected_Date_Range', null, __( 'AffiliateWP', 'wp-marketing-automations-pro' ) );
}
