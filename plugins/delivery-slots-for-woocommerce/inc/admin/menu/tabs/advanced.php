<?php
/**
 * Advanced Tab.
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'DEY_Advanced_Tab' ) ) {
	return new DEY_Advanced_Tab();
}

/**
 * Class.
 * */
class DEY_Advanced_Tab extends DEY_Settings_Page {

	/**
	 * Constructor.
	 * */
	public function __construct() {
		$this->id    = 'advanced';
		$this->label = __( 'Advanced', 'delivery-slots-for-woocommerce' );

		// Display the server cron information.
		add_action( 'woocommerce_admin_field_dey_cron_information', array( $this, 'cron_information' ) );

		parent::__construct();
	}

	/**
	 * Get the settings for advanced section array.
	 *
	 * @return array
	 * */
	public function advanced_section_array() {
		$section_fields = array();

		// General section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'General Settings', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_general_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Display Position - Applicable for Delivery & Pickup', 'delivery-slots-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'display_position' ),
			'options' => dey_order_scheduler_display_positions(),
			'desc'    => __( 'Positioning will work only for shortcode based checkout page. For positioning in block based checkout page, edit the checkout page and move the "Order Delivery/Order Pickup" block as per your needs.', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'             => __( 'Display Position Priority in Case of a Conflict', 'delivery-slots-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '10',
			'custom_attributes' => array(
				'min'  => 1,
				'step' => 1,
			),
			'id'                => $this->get_option_key( 'display_position_priority' ),
			'desc_tip'          => true,
			'desc'              => __( 'Input a different number if you are facing issues in displaying the Delivery Info/Calendar in the selected position. Lower numbers have higher priority', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Default Order Scheduler Method', 'delivery-slots-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'default_order_scheduler_type' ),
			'options' => array(
				'1' => __( 'Order Delivery', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Order Local Pickup', 'delivery-slots-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => __( 'Exclude Delivery Date Selection for Virtual Products', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'virtual_products_disabled' ),
			'desc'    => __( 'When enabled, the delivery date selection will not be displayed for virtual product(s).', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Display Delivery Slots Details in Order Notes', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'order_notes_enabled' ),
			'desc'    => __( 'Enable this to display the Delivery Slots details in Order Notes section in the Edit Order page', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'       => __( 'Order delivery Cancellation Cutoff Time', 'delivery-slots-for-woocommerce' ),
			'type'        => 'dey_custom_fields',
			'dey_field'   => 'relative_date_selector',
			'option_type' => '2',
			'default'     => array(),
			'id'          => $this->get_option_key( 'order_delivery_cancel_cutoff_time' ),
			'desc_tip'    => true,
		);
		$section_fields[] = array(
			'title'       => __( 'Order Local Pickup Cancellation Cutoff Time', 'delivery-slots-for-woocommerce' ),
			'type'        => 'dey_custom_fields',
			'dey_field'   => 'relative_date_selector',
			'option_type' => '2',
			'default'     => array(),
			'id'          => $this->get_option_key( 'order_pickup_cancel_cutoff_time' ),
			'desc_tip'    => true,
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_general_options',
		);
		// General section end.
		// Troubleshoot section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'Troubleshoot', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_troubleshoot_options',
		);
		$section_fields[] = array(
			'title'    => __( 'Frontend Scripts Enqueued on', 'delivery-slots-for-woocommerce' ),
			'id'       => $this->get_option_key( 'frontend_enqueue_scripts_type' ),
			'type'     => 'select',
			'default'  => '1',
			'options'  => array(
				'1' => __( 'Header', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Footer', 'delivery-slots-for-woocommerce' ),
			),
			'desc_tip' => true,
			'desc'     => __( 'Choose whether the frontend scripts has to be loaded on Header/Footer', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'    => __( 'Delivery Date Display Priority Product', 'delivery-slots-for-woocommerce' ),
			'id'       => $this->get_option_key( 'product_delivery_date_display_priority' ),
			'type'     => 'number',
			'default'  => '10',
			'desc_tip' => true,
			'desc'     => __( 'Please change a priority when facing the other plugins/themes issue for display position', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Display Delivery Date and Time in the Orders Table - My Account Page', 'delivery-slots-for-woocommerce' ),
			'id'      => $this->get_option_key( 'order_table_delivery_details_enabled' ),
			'type'    => 'select',
			'default' => '2',
			'options' => array(
				'1' => __( 'Show', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Hide', 'delivery-slots-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => __( 'Shipping Method(s) to be Displayed for Local Pickups', 'delivery-slots-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'shipping_mode', 'local_pickup' ),
			'options' => array(
				'1' => __( 'Display All the Available Shipping Methods on the Site', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Display Only the Matching Shipping Methods from the Applicable Rule', 'delivery-slots-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_troubleshoot_options',
		);
		// Troubleshoot section end.
		// Appearance section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'Appearance', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_appearance_options',
		);
		$section_fields[] = array(
			'title'    => __( 'Frontend Calender Display Mode', 'delivery-slots-for-woocommerce' ),
			'id'       => $this->get_option_key( 'calender_display_mode' ),
			'type'     => 'select',
			'default'  => '1',
			'options'  => array(
				'1' => __( 'Display after Clicking the Date Picker Field', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Always Display under the Date Picker', 'delivery-slots-for-woocommerce' ),
			),
			'desc_tip' => true,
			'desc'     => __( 'Display Mode for the delivery date calendar in frontend', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'    => __( 'Frontend Calender Theme', 'delivery-slots-for-woocommerce' ),
			'id'       => $this->get_option_key( 'calender_theme' ),
			'type'     => 'select',
			'default'  => 'base',
			'options'  => dey_get_datepicker_themes(),
			'desc_tip' => true,
			'desc'     => __( 'Theme for the delivery date calendar displayed to the user in frontend', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Display the Calendar date Color Information', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'order_calendar_date_color_info' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Background Color of Available Date(s)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#00cc66',
			'id'      => $this->get_option_key( 'calender_day_bg_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Available Date(s) Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#000000',
			'id'      => $this->get_option_key( 'calender_day_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Background Color of Holiday Date(s)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#dd9944',
			'id'      => $this->get_option_key( 'holiday_bg_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Holiday Date Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#000000',
			'id'      => $this->get_option_key( 'holiday_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Background Color of Partially Booked Date(s)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#ffcc00',
			'id'      => $this->get_option_key( 'partial_booked_bg_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Partially Booked Date(s) Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#000000',
			'id'      => $this->get_option_key( 'partial_booked_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Background Color of Completely Booked Date(s)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#ff3300',
			'id'      => $this->get_option_key( 'booked_bg_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Completely Booked Date(s) Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#000000',
			'id'      => $this->get_option_key( 'booked_color' ),
		);
		$section_fields[] = array(
			'title'   => __( "Expected Delivery Info Text's Color", 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#6d6d6d',
			'id'      => $this->get_option_key( 'expected_delivery_info_color' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_appearance_options',
		);
		// Appearance section end.
		// Custom CSS section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'Custom CSS', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_custom_css_options',
		);
		$section_fields[] = array(
			'title'             => __( 'Custom CSS', 'delivery-slots-for-woocommerce' ),
			'type'              => 'textarea',
			'default'           => '',
			'custom_attributes' => array( 'rows' => 10 ),
			'id'                => $this->get_option_key( 'custom_css' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_custom_css_options',
		);
		// Custom CSS section end.
		// Cron section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'Cron Information', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_cron_options',
		);
		$section_fields[] = array(
			'type' => 'dey_cron_information',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_server_cron_options',
		);
		// Cron section end.

		return $section_fields;
	}

	/**
	 * Display the Cron information.
	 * */
	public function cron_information() {
		$cron_info = array(
			'email_reminder' => array(
				'cron'              => __( 'Today Email Reminder Cron', 'delivery-slots-for-woocommerce' ),
				'last_updated_date' => self::format_last_updated_date( get_option( 'dey_update_wp_cron_last_updated_date' ) ),
			),
		);

		include_once DEY_ABSPATH . 'inc/admin/menu/views/html-cron-info.php';
	}

	/**
	 * Format the last update date.
	 *
	 * @return string.
	 * */
	public function format_last_updated_date( $date ) {
		if ( empty( $date ) ) {
			return __( 'Cron not Triggered', 'delivery-slots-for-woocommerce' );
		}

		return DEY_Date_Time::get_wp_format_datetime_from_gmt( $date, false, ' ', true );
	}
}

return new DEY_Advanced_Tab();
