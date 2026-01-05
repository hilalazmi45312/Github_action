<?php

class BWFAN_Funnel_Builder_UTM_Data extends BWFAN_Merge_Tag {

	private static $instance = null;
	private static $fetched_data = [];

	public function __construct() {
		$this->tag_name        = 'funnel_builder_conversion_data';
		$this->tag_description = __( 'Funnel Conversion Data', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_funnel_builder_conversion_data', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_ecom_tracking_data', array( $this, 'parse_shortcode' ) );
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
			return $this->get_dummy_preview( $attr );
		}

		$source   = $this->get_order_id();
		$optin_id = BWFAN_Merge_Tag_Loader::get_data( 'optin_entry_id' );
		$source   = empty( $source ) ? $optin_id : $source;
		if ( empty( $source ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$type = ! empty( $optin_id ) ? 1 : 2;

		$utm_data = $this->get_utm_data( $source, $type );
		if ( empty( $utm_data ) || ! is_array( $utm_data ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$key       = $attr['data'] ?? $attr['fb_utm_data'] ?? 'utm_campaign';
		$utm_value = $utm_data[ $key ] ?? '';
		$utm_value = apply_filters( 'bwfan_modify_ecom_tracking_key_val', $utm_value, $source, $key, $utm_data );

		switch ( $key ) {
			case 'first_click':
				$parameters['format'] = apply_filters( 'bwfan_first_click_date_format', $attr['format'] ?? get_option( 'date_format' ) );
				$utm_value            = $this->format_datetime( $utm_value, [ 'format' => $parameters['format'] ] );
				break;
			case 'conversion_time':
				$first_click = isset( $utm_data['first_click'] ) ? $utm_data['first_click'] : '';
				if ( ! empty( $first_click ) ) {
					$timestamp = isset( $utm_data['timestamp'] ) ? $utm_data['timestamp'] : '';
					$d1        = strtotime( $timestamp );
					$d2        = strtotime( $first_click );
					$utm_value = human_time_diff( $d1, $d2 );
				}
				break;
			case 'funnel_name':
				$funnel_id = isset( $utm_data['funnel_id'] ) ? $utm_data['funnel_id'] : 0;
				if ( class_exists( 'WFFN_Funnel' ) && class_exists( 'WFFN_Common' ) && intval( $funnel_id ) > 0 ) {
					$funnel_obj = new WFFN_Funnel( $funnel_id );
					if ( $funnel_obj instanceof WFFN_Funnel && $funnel_obj->get_id() > 0 ) {
						$utm_value = ! empty( $funnel_obj->get_title() ) ? $funnel_obj->get_title() : $funnel_obj->get_id();
					}
				}
				break;
		}

		return $this->parse_shortcode_output( $utm_value, $attr );
	}

	/**
	 * Get the order ID from the mergetag data.
	 *
	 * @return int
	 */
	public function get_order_id() {
		$order_id = absint( BWFAN_Merge_Tag_Loader::get_data( 'wc_order_id' ) );
		$order_id = ( 0 === $order_id ) ? absint( BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) ) : $order_id;

		if ( 0 === $order_id ) {
			$order    = BWFAN_Merge_Tag_Loader::get_data( 'wc_order' );
			$order_id = $order instanceof WC_Order ? absint( BWFAN_Woocommerce_Compatibility::get_order_id( $order ) ) : 0;
		}

		return $order_id;
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview( $attr ) {
		// Example of placeholder data for UTM fields
		$dummy_data = [
			'utm_source'        => 'dummy_source',
			'utm_medium'        => 'dummy_medium',
			'utm_campaign'      => 'dummy_campaign',
			'utm_term'          => 'dummy_term',
			'utm_content'       => 'dummy_content',
			'funnel_id'         => 'dummy_funnel_id',
			'funnel_name'       => 'dummy_funnel_name',
			'click_id'          => 'dummy_id#abc123',
			'first_click'       => date( "Y/m/d" ),
			'first_landing_url' => 'https://example.com/landing-page',
			'referrer'          => 'https://examplegoogle.com',
			'referrer_last'     => 'https://example.com/previous-page',
			'device'            => 'dummy_device',
			'browser'           => 'dummy_browser',
			'conversion_time'   => date( "Y/m/d H:i:s" ),
		];

		$key = $attr['fb_utm_data'] ?? '';
		if ( $key === 'first_click' ) {
			$dummy_data['first_click'] = $this->format_datetime( $dummy_data['first_click'], [ 'format' => $attr['format'] ?? 'Y-m-d H:i:s' ] );
		}

		return $this->parse_shortcode_output( $dummy_data[ $key ], $attr );
	}

	/**
	 * Fetch UTM data from the database based on the source
	 *
	 * @param int $source Source ID
	 * @param int $type 1 for optin, 2 for funnel
	 *
	 * @return array|object|stdClass|null
	 */
	function get_utm_data( $source, $type ) {
		if ( isset( self::$fetched_data[ $source ] ) ) {
			return self::$fetched_data[ $source ];
		}
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}bwf_conversion_tracking WHERE source = %d && type = %d ORDER BY timestamp DESC";

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		$result = $wpdb->get_row( $wpdb->prepare( $query, [
			$source,
			$type
		] ), ARRAY_A );
		if ( empty( $result ) ) {
			return [];
		}
		// add data in caching prop if multiple merge tags are used
		self::$fetched_data[ $source ] = $result;

		return self::$fetched_data[ $source ];
	}

	/**
	 * Returns merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		$utm_data = $this->get_view_data();
		$options  = [];
		foreach ( $utm_data as $utm_key => $data ) {
			$options[] = [
				'value' => $utm_key,
				'label' => $data,
			];
		}
		$date_formats = BWFAN_PRO_Common::get_date_formats();
		$date_formats = array_map( function ( $data ) {
			return [
				'value' => $data['format'],
				'label' => date( $data['format'] ),
			];
		}, $date_formats );

		return [
			[
				'id'          => 'data',
				'type'        => 'select',
				'options'     => $options,
				'label'       => __( 'Select UTM', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => ""
			],
			[
				'id'          => 'format',
				'type'        => 'select',
				'options'     => $date_formats,
				'label'       => __( 'Select Line Type', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => '',
				"required"    => false,
				"description" => "",
				"toggler"     => [
					'fields'   => [
						[
							'id'    => 'fb_utm_data',
							'value' => 'first_click'
						]
					],
					'relation' => 'AND',
				],
			],
		];
	}

	public function get_view_data() {
		$handler_merge_key = apply_filters( 'bwfan_external_ecom_tracking_key', array(
			'utm_source'        => __( 'UTM Source', 'wp-marketing-automations-pro' ),
			'utm_medium'        => __( 'UTM Medium', 'wp-marketing-automations-pro' ),
			'utm_campaign'      => __( 'UTM Campaign', 'wp-marketing-automations-pro' ),
			'utm_term'          => __( 'UTM Term', 'wp-marketing-automations-pro' ),
			'utm_content'       => __( 'UTM Content', 'wp-marketing-automations-pro' ),
			'funnel_id'         => __( 'Funnel ID', 'wp-marketing-automations-pro' ),
			'funnel_name'       => __( 'Funnel Name', 'wp-marketing-automations-pro' ),
			'click_id'          => __( 'Click ID', 'wp-marketing-automations-pro' ),
			'first_click'       => __( 'First Click', 'wp-marketing-automations-pro' ),
			'first_landing_url' => __( 'First Landing URL', 'wp-marketing-automations-pro' ),
			'referrer'          => __( 'Referrer', 'wp-marketing-automations-pro' ),
			'referrer_last'     => __( 'Referrer Last', 'wp-marketing-automations-pro' ),
			'device'            => __( 'Device', 'wp-marketing-automations-pro' ),
			'browser'           => __( 'Browser', 'wp-marketing-automations-pro' ),
			'conversion_time'   => __( 'Conversion Time', 'wp-marketing-automations-pro' ),
		) );

		return apply_filters( 'bwfan_external_fb_utm_key', $handler_merge_key );
	}

	public function get_default_values() {
		return [
			'format' => 'Y-m-d',
		];
	}
}

if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_funnel_builder_pro_active' ) && bwfan_is_funnel_builder_pro_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_order', 'BWFAN_Funnel_Builder_UTM_Data', null, __( 'Funnel', 'wp-marketing-automations-pro' ) );
	BWFAN_Merge_Tag_Loader::register( 'optinforms', 'BWFAN_Funnel_Builder_UTM_Data', null, __( 'Funnel', 'wp-marketing-automations-pro' ) );
}
