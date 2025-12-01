<?php

/**
 * Order Tip Tab
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'DEY_Order_Tip_Tab' ) ) {
	return new DEY_Order_Tip_Tab();
}

/**
 * Class.
 */
class DEY_Order_Tip_Tab extends DEY_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'order_tip';
		$this->label = __( 'Tip', 'delivery-slots-for-woocommerce' );

		parent::__construct();
	}

	/**
	 * Get the sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'general'             => __( 'General', 'delivery-slots-for-woocommerce' ),
			'color_customization' => __( 'Color Customization', 'delivery-slots-for-woocommerce' ),
			'localization'        => __( 'Localization', 'delivery-slots-for-woocommerce' ),
			'message'             => __( 'Messages', 'delivery-slots-for-woocommerce' ),
		);
		/**
		 * This hook is used to alter the order tip tab sections.
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
			'title'   => __( 'Allow User to Add a Tip on the Checkout Page', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'checkout_enabled' ),
			'desc'    => __( 'When enabled, an option to select a tip will be displayed to your users on the checkout page.', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Display Position', 'delivery-slots-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'checkout_display_position' ),
			'class'   => 'dey-order-tip-checkout-fields',
			'options' => dey_order_tip_checkout_display_positions(),
			'desc'    => __( 'Positioning will work only for shortcode based checkout page. For positioning in block based checkout page, edit the checkout page and move the "Order Delivery Tip" block as per your needs.', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'             => __( 'Display Position Priority in case of a Conflict', 'delivery-slots-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '10',
			'custom_attributes' => array(
				'min'  => 1,
				'step' => 1,
			),
			'id'                => $this->get_option_key( 'checkout_display_position_priority' ),
			'class'             => 'dey-order-tip-checkout-fields',
			'desc_tip'          => true,
			'desc'              => __( 'Input a different number if you are facing issues in displaying the Tip field in the selected position. Lower numbers have higher priority', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Enable Adding Tip Feature on the Cart Page', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'cart_enabled' ),
			'desc'    => __( 'When enabled, adding tip field can be displayed to the user or the user can select the tip amount on the cart page.', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Display Position', 'delivery-slots-for-woocommerce' ),
			'type'    => 'select',
			'default' => '2',
			'id'      => $this->get_option_key( 'cart_display_position' ),
			'class'   => 'dey-order-tip-cart-fields',
			'options' => dey_order_tip_cart_display_positions(),
			'desc'    => __( 'Positioning will work only for shortcode based cart page. For positioning in block based cart page, edit the cart page and move the "Order Delivery Tip" block as per your needs.', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'             => __( 'Display Position Priority in case of a Conflict', 'delivery-slots-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '10',
			'custom_attributes' => array(
				'min'  => 1,
				'step' => 1,
			),
			'id'                => $this->get_option_key( 'cart_display_position_priority' ),
			'class'             => 'dey-order-tip-cart-fields',
			'desc_tip'          => true,
			'desc'              => __( 'Input a different number if you are facing issues in displaying the Tip field in the selected position. Lower numbers have higher priority', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Tips Description Display', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'description_enabled' ),
			'desc'    => __( 'When enabled, adding tip field can be displayed to the user or the user can select the tip amount on the cart page.', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Charge Tax for Tip Amount', 'delivery-slots-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'tax_enabled' ),
			'desc'    => __( 'When enabled, Tax will be charged for the tip amount', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_general_options',
		);
		// General section end.

		// Display settings start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => __( 'Display Settings', 'delivery-slots-for-woocommerce' ),
			'id'    => 'dey_display_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Tip Type', 'delivery-slots-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'display_type' ),
			'options' => array(
				'1' => __( 'Predefined with Custom Tip', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Predefined Tip', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Custom Tip', 'delivery-slots-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => __( 'Predefined Tip Type', 'delivery-slots-for-woocommerce' ),
			'type'    => 'select',
			'default' => '2',
			'id'      => $this->get_option_key( 'predefined_value_type' ),
			'class'   => 'dey-order-tip-predefined-fields dey-order-tip-type-fields',
			'options' => array(
				'1' => __( 'Fixed', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Percentage', 'delivery-slots-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => __( 'Calculate Percentage Based On', 'delivery-slots-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'percentage_type' ),
			'class'   => 'dey-order-tip-predefined-fields dey-order-tip-type-fields',
			'options' => array(
				'1' => __( 'Cart Subtotal', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Order Total', 'delivery-slots-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'             => __( 'Order Tip Values', 'delivery-slots-for-woocommerce' ),
			'type'              => 'textarea',
			'default'           => '',
			'id'                => $this->get_option_key( 'predefined_values' ),
			'class'             => 'dey-order-tip-predefined-fields dey-order-tip-type-fields',
			'placeholder'       => '10|30|50',
			'custom_attributes' => array( 'data-error' => __( 'Please enter the values', 'delivery-slots-for-woocommerce' ) ),
		);
		$section_fields[] = array(
			'title'             => __( 'Minimum Custom Tip', 'delivery-slots-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => 1,
				'step' => 1,
			),
			'id'                => $this->get_option_key( 'custom_tip_min_value' ),
			'class'             => 'dey-order-tip-custom-fields dey-order-tip-type-fields',
		);
		$section_fields[] = array(
			'title'             => __( 'Maximum Custom Tip', 'delivery-slots-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => 1,
				'step' => 1,
			),
			'id'                => $this->get_option_key( 'custom_tip_max_value' ),
			'class'             => 'dey-order-tip-custom-fields dey-order-tip-type-fields',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_display_options',
		);
		// Display settings end.

		return $section_fields;
	}

	/**
	 * Get the settings for color customization section array.
	 *
	 * @return array
	 */
	public function color_customization_section_array() {
		$section_fields = array();

		// Color customization section start.
		$section_fields[] = array(
			'type'  => 'title',
			'title' => '',
			'id'    => 'dey_color_customization_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Background Color of Predefined Button', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#eeeeee',
			'id'      => $this->get_option_key( 'predefined_button_bg_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Predefined Button Border Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#eeeeee',
			'id'      => $this->get_option_key( 'predefined_button_border_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Predefined Button Font Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#333333',
			'id'      => $this->get_option_key( 'predefined_button_font_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Background Color of Selected Predefined Button', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#33a8ce',
			'id'      => $this->get_option_key( 'active_predefined_button_bg_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Selected Predefined Button Border Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#33a8ce',
			'id'      => $this->get_option_key( 'active_predefined_button_border_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Selected Predefined Button Font Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#ffffff',
			'id'      => $this->get_option_key( 'active_predefined_button_font_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Background Color of Custom Add Tip Button', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#52ce33',
			'id'      => $this->get_option_key( 'custom_add_tip_button_bg_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Add Tip Button Border Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#52ce33',
			'id'      => $this->get_option_key( 'custom_add_tip_button_border_color' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Add Tip Button Font Color', 'delivery-slots-for-woocommerce' ),
			'type'    => 'color',
			'css'     => 'width:6em;',
			'default' => '#ffffff',
			'id'      => $this->get_option_key( 'custom_add_tip_button_font_color' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_color_customization_options',
		);
		// Color customization section end.

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
			'title' => '',
			'id'    => 'dey_localization_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Tip Title Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Tip',
			'id'      => $this->get_option_key( 'title_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Tip Button Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Custom Tip',
			'id'      => $this->get_option_key( 'custom_button_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Tip Field Placeholder Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Please enter your tip',
			'id'      => $this->get_option_key( 'custom_field_placeholder_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Add Tip Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Add Tip',
			'id'      => $this->get_option_key( 'custom_add_tip_button_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Tip Fee Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'Tip',
			'id'      => $this->get_option_key( 'fee_label' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Tip Fee Remove Label', 'delivery-slots-for-woocommerce' ),
			'type'    => 'text',
			'default' => '[Remove]',
			'id'      => $this->get_option_key( 'fee_remove_label' ),
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
			'title' => '',
			'id'    => 'dey_message_options',
		);
		$section_fields[] = array(
			'title'   => __( 'Tip Description Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Delivery Partners will deliver your ordered product(s) by the Date and Time selected from your side. The tip amount will be transferred to them for your happiness.',
			'id'      => $this->get_option_key( 'description_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Tip Added Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Tip added successfully',
			'id'      => $this->get_option_key( 'added_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Tip Removed Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Tip removed successfully',
			'id'      => $this->get_option_key( 'removed_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Tip - Empty Tip Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please enter the tip amount',
			'id'      => $this->get_option_key( 'custom_empty_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Tip - Invalid Tip Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please enter a valid tip amount.',
			'id'      => $this->get_option_key( 'custom_valid_msg' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Tip - Minimum Tip Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please enter a tip amount which is equal or more than {min_amount}',
			'id'      => $this->get_option_key( 'custom_minimum_msg' ),
			'desc'    => __( '<b>Supported Shortcodes:<br/>{min_amount}</b> - Minimum Amount', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => __( 'Custom Tip - Maximum Tip Error Message', 'delivery-slots-for-woocommerce' ),
			'type'    => 'textarea',
			'default' => 'Please enter a tip amount which is equal or less than {max_amount}',
			'id'      => $this->get_option_key( 'custom_maximum_msg' ),
			'desc'    => __( '<b>Supported Shortcodes:<br/>{max_amount}</b> - Maximum Amount', 'delivery-slots-for-woocommerce' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'dey_message_options',
		);
		// Message section end.

		return $section_fields;
	}
}

return new DEY_Order_Tip_Tab();
