<?php

/**
 * Reports Tab.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'EFW_Reports_Tab' ) ) {
	return new EFW_Reports_Tab();
}

/**
 * EFW_Reports_Tab.
 */
class EFW_Reports_Tab extends EFW_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'reports';
		$this->label = esc_html__( 'Reports', 'extra-fees-for-woocommerce' );

		parent::__construct();

		add_action( sanitize_key( 'woocommerce_settings_efw_reports_settings_after' ), array( $this, 'display_report_settings' ) );
		add_action( sanitize_key( 'woocommerce_settings_efw_reports_based_on_order_settings_after' ), array( $this, 'display_report_based_on_order' ) );
	}

	/**
	 * Get Reports Settings section array.
	 */
	public function reports_section_array() {
		$section_fields = array();

		$section_fields[] = array(
			'type'      => 'efw_custom_fields',
			'efw_field' => 'section_start',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Extra Fees Reports', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_reports_settings',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_reports_settings',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Order Reports', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_reports_based_on_order_settings',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_reports_based_on_order_settings',
		);
		$section_fields[] = array(
			'type'      => 'efw_custom_fields',
			'efw_field' => 'section_end',
		);

		return $section_fields;
	}

	/**
	 * Display Rules for Gateway Deal.
	 */
	public function display_report_settings() {

		global $wpdb;
		$order_fee_query    = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as orderfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_order_fee' and p.ID=pm.post_id", EFW_Register_Post_Type::FEES_POSTTYPE ), ARRAY_A );
		$product_fee_query  = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as productfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_product_fee' and p.ID=pm.post_id", EFW_Register_Post_Type::FEES_POSTTYPE ), ARRAY_A );
		$gateway_fee_query  = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as gatewayfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_gateway_fee' and p.ID=pm.post_id", EFW_Register_Post_Type::FEES_POSTTYPE ), ARRAY_A );
		$shipping_fee_query = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as shippingfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_shipping_fee' and p.ID=pm.post_id", EFW_Register_Post_Type::FEES_POSTTYPE ), ARRAY_A );

		$order_fee          = end( $order_fee_query );
		$product_fee        = end( $product_fee_query );
		$gateway_fee        = end( $gateway_fee_query );
		$shipping_fee       = end( $shipping_fee_query );
		$order_fee_value    = efw_check_is_array( $order_fee ) ? $order_fee['orderfee'] : 0;
		$product_fee_value  = efw_check_is_array( $product_fee ) ? $product_fee['productfee'] : 0;
		$gateway_fee_value  = efw_check_is_array( $gateway_fee ) ? $gateway_fee['gatewayfee'] : 0;
		$shipping_fee_value = efw_check_is_array( $shipping_fee ) ? $shipping_fee['shippingfee'] : 0;
		$total_fee          = $order_fee_value + $product_fee_value + $gateway_fee_value + $shipping_fee_value;
		
		include EFW_ABSPATH . 'inc/admin/menu/views/reports/reports-settings.php';
	}

	/**
	 * Display Rules for Gateway Deal.
	 */
	public function display_report_based_on_order() {
		if ( ! class_exists( 'EFW_Fee_Report_Post_Table' ) ) {
			require_once EFW_PLUGIN_PATH . '/inc/admin/menu/wp-list-table/class-efw-fee-report-post-table.php';
		}

		echo '<div class="efw_fee_report_table_wrap">';
		$post_table = new EFW_Fee_Report_Post_Table();
		$post_table->prepare_items();
		$post_table->search_box( esc_html__( 'Search Order(s)', 'extra-fees-for-woocommerce' ), $this->plugin_slug . '_search' );
		$post_table->display();
		echo '</div>';
	}

	/**
	 * Output the settings buttons.
	 */
	public function output_buttons() {
	}
}

return new EFW_Reports_Tab();
