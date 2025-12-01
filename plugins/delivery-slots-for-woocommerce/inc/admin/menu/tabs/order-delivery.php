<?php
/**
 * Order Delivery Tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'DEY_Order_Delivery_Tab' ) ) {
	return new DEY_Order_Delivery_Tab();
}

/**
 * Class.
 */
class DEY_Order_Delivery_Tab extends DEY_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'order_delivery';
		$this->label = __( 'Order Delivery', 'delivery-slots-for-woocommerce' );

		parent::__construct();
	}

	/**
	 * Get the sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'general'      => __( 'General', 'delivery-slots-for-woocommerce' ),
			'localization' => __( 'Localization', 'delivery-slots-for-woocommerce' ),
			'message'      => __( 'Messages', 'delivery-slots-for-woocommerce' ),
		);
		/**
		 * This hook is used to alter the order delivery tab sections.
		 *
		 * @since 1.0
		 */
		return apply_filters( $this->get_plugin_slug() . '_get_sections_' . $this->get_id(), $sections );
	}

	/**
	 * Get the settings for general section array.
	 *
	 * @return array
	 */
	public function general_section_array() {
		$section_fields = array();

		// Business day section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'Business Days', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_business_day_options',
		);
		$section_fields[] = array(
			'title'     => __( 'Business Days of the Week', 'delivery-slots-for-woocommerce' ),
			'type'      => 'dey_custom_fields',
			'default'   => dey_business_day_default_option(),
			'dey_field' => 'business_day_prices',
			'id'        => $this->get_option_key( 'business_days' ),
			'desc_tip'  => true,
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_business_day_options',
		);
		// Business day section end.

		return $section_fields;
	}

	/**
	 * Get the settings for localization section array.
	 *
	 * @return array
	 */
	public function localization_section_array() {
		$section_fields = array();

		// Localization section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'General Settings', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_localization_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Date Field Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Delivery Date',
			'id'      => $this->get_option_key( 'date_field_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slots Field Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Time Slots',
			'id'      => $this->get_option_key( 'time_slots_field_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Default Time Slot Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Select a Time Slot',
			'id'      => $this->get_option_key( 'default_time_slot_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slot Unavailable Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Time Slots Unavailable',
			'id'      => $this->get_option_key( 'unavailable_time_slot_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'As Soon As Possible Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'As Soon As Possible',
			'id'      => $this->get_option_key( 'as_soon_as_possible_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Delivery Fee',
			'id'      => $this->get_option_key( 'fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Same Day Delivery Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Sameday Fee',
			'id'      => $this->get_option_key( 'sameday_fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Next Day Delivery Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Nextday Fee',
			'id'      => $this->get_option_key( 'nextday_fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Date Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Delivery Date: ',
			'id'      => $this->get_option_key( 'date_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slot Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Time Slot: ',
			'id'      => $this->get_option_key( 'time_slot_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Expected Delivery Info Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Expected Delivery Date: ',
			'id'      => $this->get_option_key( 'expected_info_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Products Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Delivery Products: ',
			'id'      => $this->get_option_key( 'products_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Partially Booked Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Partially Booked',
			'id'      => $this->get_option_key( 'partially_booked_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Remaining Orders Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Remaining Orders: %s',
			/* translators: %s: remaining orders count */
			'desc'    => __( 'Use <b>%s</b> for Remaining orders count', 'delivery-slots-for-woocommerce' ),
			'id'      => $this->get_option_key( 'remaining_count_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Booked Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Booked',
			'id'      => $this->get_option_key( 'booked_label' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_localization_options',
		);
		// Localization section end.
		// MyAccount Localization section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'MyAccount Settings', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_myaccount_localization_options',
		);
		$section_fields[] = array(
			'title'   => __( 'My Orders Table - Delivery/Pickup Date and Time Column Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Delivery/Pickup Date and Time',
			'id'      => $this->get_option_key( 'myaccount_order_delivery_date_label' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_myaccount_localization_options',
		);
		// MyAccount Localization section end.

		return $section_fields;
	}

	/**
	 * Get the settings for message section array.
	 *
	 * @return array
	 */
	public function message_section_array() {
		$section_fields = array();

		// Message section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'General Settings', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_message_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Info Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'You can receive the items between {min_duration} and {max_duration}',
			'desc'    => __( '<b>Supported Shortcodes:<br/>{min_duration}</b> - Minimum Duration<br/><b>{max_duration}</b> - Maximum Duration', 'delivery-slots-for-woocommerce' ),
			'id'      => $this->get_option_key( 'expected_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Date Mandatory Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select a delivery date.',
			'id'      => $this->get_option_key( 'date_mandatory_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slot Mandatory Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select a time slots.',
			'id'      => $this->get_option_key( 'time_slot_mandatory_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Date Incorrect Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select a valid delivery date.',
			'id'      => $this->get_option_key( 'date_incorrect_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slot Incorrect Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Time Slot is not valid.',
			'id'      => $this->get_option_key( 'time_slot_incorrect_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Selected Date Fully Booked Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Selected Date is fully booked.',
			'id'      => $this->get_option_key( 'date_booked_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Holiday Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Selected date is a holiday.',
			'id'      => $this->get_option_key( 'date_holiday_msg' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_message_options',
		);
		// Message section end.

		return $section_fields;
	}
}

return new DEY_Order_Delivery_Tab();
