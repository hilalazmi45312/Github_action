<?php
/**
 * Product local pickup tab.
 *
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( class_exists( 'DEY_Product_Pickup_Tab' ) ) {
	return new DEY_Product_Pickup_Tab();
}

/**
 * Class.
 *
 * @since 3.5.0
 */
class DEY_Product_Pickup_Tab extends DEY_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		$this->id    = 'product_local_pickup';
		$this->label = __( 'Product Local Pickup', 'delivery-slots-for-woocommerce' );

		parent::__construct();
	}

	/**
	 * Get the sections.
	 *
	 * @since 3.5.0
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
		 * @since 3.5.0
		 */
		return apply_filters( $this->get_plugin_slug() . '_get_sections_' . $this->get_id(), $sections );
	}

	/**
	 * Get the settings for general section array.
	 *
	 * @since 3.5.0
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
			'title'   => __( 'Enable Product Local Pickup', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'enable_product_pickup' ),
			'desc'    => __( 'When enabled, the pickup date can be displayed to the user or the user can select the pickup date for each Product.', 'delivery-slots-for-woocommerce' ),
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
	 * @since 3.5.0
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
			'title'   => __( 'Pickup Date Field Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Date',
			'id'      => $this->get_option_key( 'date_field_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Location Field Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Location',
			'id'      => $this->get_option_key( 'location_field_label' ),
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
			'title'   => __( 'Pickup Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Fee',
			'id'      => $this->get_option_key( 'fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Same Day Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Same Day Fee',
			'id'      => $this->get_option_key( 'same_day_fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Next Day Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Next Day Fee',
			'id'      => $this->get_option_key( 'next_day_fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Date Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Date',
			'id'      => $this->get_option_key( 'date_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slot Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Time Slot',
			'id'      => $this->get_option_key( 'time_slot_label' ),
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
	 * @since 3.5.0
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
			'title'   => __( 'Pickup Location Mandatory Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select a pickup location.',
			'id'      => $this->get_option_key( 'location_mandatory_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Date Mandatory Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select a pickup date.',
			'id'      => $this->get_option_key( 'date_mandatory_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Time Slot Mandatory Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select a time slot',
			'id'      => $this->get_option_key( 'time_slot_mandatory_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Date Incorrect Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select the valid pickup date.',
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
			'title'   => __( 'Product Pickup Disabled Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Product pickup is disabled so the product has been removed from the cart.',
			'id'      => $this->get_option_key( 'disabled_cart_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Product Removed From Cart Error Message - Pickup Date', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Selected pickup date is not valid so the product has been removed from the cart.',
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
			'default' => 'The product has been removed from the cart as the pickup date option is not applicable.',
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

return new DEY_Product_Pickup_Tab();
