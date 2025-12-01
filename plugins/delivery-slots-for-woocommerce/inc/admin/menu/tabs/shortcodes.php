<?php

/**
 * Shortcodes tab.
 *
 * @since 3.1.0
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'DEY_Shortcode_Tab' ) ) {
	return new DEY_Shortcode_Tab();
}

/**
 * Class.
 *
 * @since 3.1.0
 * */
class DEY_Shortcode_Tab extends DEY_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 * */
	public function __construct() {
		$this->id           = 'shortcodes';
		$this->label        = __( 'Shortcodes', 'delivery-slots-for-woocommerce' );
		$this->show_buttons = false;

		// Display shortcode information.
		add_action( 'woocommerce_admin_field_dey_display_shortcodes_information', array( $this, 'display_shortcodes_information' ) );
		parent::__construct();
	}

	/**
	 * Get settings for shortcodes section array.
	 *
	 * @since 3.1.0
	 * @return array
	 * */
	public function shortcodes_section_array() {
		$section_fields = array();

		// Shortcodes section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'Shortcodes', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_shortcodes_options',
		);
		$section_fields[] = array(
			'type' => 'dey_display_shortcodes_information',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_shortcodes_options',
		);
		// Shortcodes section end.

		return $section_fields;
	}

	/**
	 * Display shortcode information.
	 *
	 * @since 3.1.0
	 * @return void
	 * */
	public function display_shortcodes_information() {
		$shortcodes_info = array(
			'[dey_order_scheduler_field]'  => array(
				'supported_parameters' => '-',
				'usage'                => __( 'Displays the order scheduler fields', 'delivery-slots-for-woocommerce' ),
			),
			'[dey_product_delivery_field]' => array(
				'supported_parameters' => 'product_id',
				'usage'                => __( 'Displays the product delivery field', 'delivery-slots-for-woocommerce' ),
			),
			'[dey_tip_field]'              => array(
				'supported_parameters' => '-',
				'usage'                => __( 'Displays the tip field', 'delivery-slots-for-woocommerce' ),
			),
		);

		include_once DEY_ABSPATH . 'inc/admin/menu/views/html-shortcodes-info.php';
	}
}

return new DEY_Shortcode_Tab();
