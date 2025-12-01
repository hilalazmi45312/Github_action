<?php
/**
 * Order Local Pickup Tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'DEY_Local_Pickup_Tab' ) ) {
	return new DEY_Local_Pickup_Tab();
}

/**
 * Class.
 */
class DEY_Local_Pickup_Tab extends DEY_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'local_pickup';
		$this->label = __( 'Order Local Pickup', 'delivery-slots-for-woocommerce' );

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
		 * This hook is used to alter the pickup location tab sections.
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
			'title'   => __( 'Pickup Location Field Label(Checkout Page)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Location',
			'id'      => $this->get_option_key( 'location_field_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Date Field Label(Checkout Page)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Date',
			'id'      => $this->get_option_key( 'date_field_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Default Pickup Location Label(Checkout Page)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Select a pickup location',
			'id'      => $this->get_option_key( 'default_pickup_location_label' ),
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
			'title'   => __( 'Time Slot Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Time Slot: ',
			'id'      => $this->get_option_key( 'time_slot_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Fee Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Fee',
			'id'      => $this->get_option_key( 'fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Same Day Pickup Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Sameday Fee',
			'id'      => $this->get_option_key( 'sameday_fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Next Day Pickup Fee', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Nextday Fee',
			'id'      => $this->get_option_key( 'nextday_fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Location Label(Thank You and Order Page)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Location: ',
			'id'      => $this->get_option_key( 'location_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Date Label(Thank You and Order Page)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Date: ',
			'id'      => $this->get_option_key( 'date_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Products Label(Thank You and Order Page)', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Pickup Products: ',
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
			'default' => 'Please select a time slot.',
			'id'      => $this->get_option_key( 'time_slot_mandatory_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Pickup Date Incorrect Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please select a valid pickup date.',
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

return new DEY_Local_Pickup_Tab();
