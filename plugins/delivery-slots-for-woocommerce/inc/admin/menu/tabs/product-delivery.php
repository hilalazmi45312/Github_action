<?php

/**
 * Product Delivery Tab
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'DEY_Product_Delivery_Tab' ) ) {
	return new DEY_Product_Delivery_Tab();
}

/**
 * Class.
 */
class DEY_Product_Delivery_Tab extends DEY_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'product_delivery';
		$this->label = __( 'Product Delivery', 'delivery-slots-for-woocommerce' );

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
		 * This hook is used to alter the product delivery tab sections.
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

		// General section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'General Settings', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_general_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Enable Product Delivery', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'enable_product_delivery' ),
			'desc'    => __( 'When enabled, the delivery date can be displayed to the user or the user can select the delivery date for each Product.', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_general_options',
		);
		// General section end.

		return $section_fields;
	}

	/**
	 * Get the settings for localization section array.
	 *
	 * @return array
	 */
	public function localization_section_array() {
		$section_fields = array();

		// General section start.
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
			'title'   => __( 'Same day Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Same day Fee',
			'id'      => $this->get_option_key( 'same_day_fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Next day Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Next day Fee',
			'id'      => $this->get_option_key( 'next_day_fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Date Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Delivery Date',
			'id'      => $this->get_option_key( 'date_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slot Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Time Slot',
			'id'      => $this->get_option_key( 'time_slot_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Expected Delivery Info Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Expected Delivery Date',
			'id'      => $this->get_option_key( 'expected_info_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Partially Booked Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Partial Booked',
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
		// General section end.

		return $section_fields;
	}

	/**
	 * Get the settings for message section array.
	 *
	 * @return array
	 */
	public function message_section_array() {
		$section_fields = array();

		// General section start.
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
			'default' => 'Please select a time slot.',
			'id'      => $this->get_option_key( 'time_slot_mandatory_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Delivery Date Incorrect Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select the valid delivery date.',
			'id'      => $this->get_option_key( 'date_incorrect_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slot Incorrect Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Time slot is not valid.',
			'id'      => $this->get_option_key( 'time_slot_incorrect_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Selected Date Fully Booked Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Selected date is fully booked.',
			'id'      => $this->get_option_key( 'date_booked_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Holiday Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Selected date is a holiday.',
			'id'      => $this->get_option_key( 'date_holiday_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Product Delivery Disabled Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Product delivery is disabled so the product has been removed from the cart.',
			'id'      => $this->get_option_key( 'disabled_cart_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Product Removed From Cart Error Message - Delivery Date', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Selected delivery date is not valid so the product has been removed from the cart.',
			'id'      => $this->get_option_key( 'date_cart_incorrect_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Product Removed From Cart Error Message - Time Slot', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Selected time slot is not valid so the product has been removed from the cart.',
			'id'      => $this->get_option_key( 'time_slot_cart_incorrect_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Product Removed From Cart Error Message - Fully Booked', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Maximum Order count is reached for the selected date so the product has been removed from the cart.',
			'id'      => $this->get_option_key( 'date_cart_booked_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Product Removed From Cart Error Message - Holiday', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Selected date is a holiday so the product has been removed from the cart.',
			'id'      => $this->get_option_key( 'date_cart_holiday_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Product Removed From Cart Error Message - Virtual Products', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'The product has been removed from the cart as the delivery date option is not applicable.',
			'id'      => $this->get_option_key( 'virtual_product_removed_cart_msg' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_message_options',
		);
		// General section end.

		return $section_fields;
	}
}

return new DEY_Product_Delivery_Tab();
