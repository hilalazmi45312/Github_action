<?php

/**
 * Shipping Fee Tab.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'EFW_Shipping_Fee_Tab' ) ) {
	return new EFW_Shipping_Fee_Tab();
}

/**
 * EFW_Shipping_Fee_Tab.
 */
class EFW_Shipping_Fee_Tab extends EFW_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'shippingfee';
		$this->label = esc_html__( 'Shipping Fee', 'extra-fees-for-woocommerce' );

		// Display Shipping methods.
		add_action( 'woocommerce_settings_efw_shipping_fee_settings_after', array( $this, 'display_shipping_methods' ) );
		// Save Settings for Shipping Fee.
		add_action( sanitize_key( $this->plugin_slug . '_' . $this->id . '_settings_after_save' ), array( $this, 'settings_after_save' ) );
		// Reset Settings for Shipping Fee.
		add_action( sanitize_key( $this->plugin_slug . '_' . $this->id . '_settings_after_reset' ), array( $this, 'settings_after_reset' ) );
		// Validate Settings Enabled.
		add_action( 'efw_shippingfee_before_settings_display', array( $this, 'validate_settings_enable' ) );

		parent::__construct();
	}

	/**
	 * Validate Settings Enabled.
	 */
	public function validate_settings_enable() {
		if ('yes' != get_option('efw_shippingfee_enable')) {
			echo "<div class='error'><p>" . esc_html('Enable the global checkbox to charge the Shipping Fee.', 'extra-fees-for-woocommerce') . '</p></div>';
		}
	}

	/**
	 * Get Shipping Fee Settings section array.
	 */
	public function shippingfee_section_array() {

		$section_fields = array();

		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Shipping Fee Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_shipping_fee_settings',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Enable Shipping Fee', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'desc'    => esc_html__( 'When enabled, a fee has to be paid by the user for choosing the shipping.', 'extra-fees-for-woocommerce' ),
			'id'      => $this->get_option_key( 'enable' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_shipping_fee_settings',
		);

		return $section_fields;
	}

	/**
	 * Display shipping methods.
	 */
	public static function display_shipping_methods() {
		$shipping_zones = WC_Shipping_Zones::get_zones();
		foreach ($shipping_zones as $shipping_zone) {
			include EFW_PLUGIN_PATH . '/inc/admin/menu/views/shipping/html-shipping-region-settings.php';
		}       
	}

	/**
	 * Settings after save.
	 */
	public static function settings_after_save() {

		$shipping_zones = WC_Shipping_Zones::get_zones();

		foreach ( $shipping_zones as $shipping_zone ) {
			foreach ($shipping_zone['shipping_methods'] as $shipping_method) {
				$shipping_method_key = $shipping_method->id . '_' . $shipping_method->instance_id;
						$settings_args = array(
							'efw_enable'                       => isset( $_REQUEST[ "efw_enable_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_enable_$shipping_method_key" ] ) ) : '',
							'efw_shipping_fee_text'            => isset( $_REQUEST[ "efw_shipping_fee_text_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_fee_text_$shipping_method_key" ] ) ) : '',
							'efw_shipping_fee_description'     => isset( $_REQUEST[ "efw_shipping_fee_description_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_fee_description_$shipping_method_key" ] ) ) : '',
							'efw_shipping_user_filter_type'    => isset( $_REQUEST[ "efw_shipping_user_filter_type_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_user_filter_type_$shipping_method_key" ] ) ) : '',
							'efw_shipping_include_users'       => isset( $_REQUEST[ "efw_shipping_include_users_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_include_users_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_exclude_users'       => isset( $_REQUEST[ "efw_shipping_exclude_users_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_exclude_users_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_include_userroles'   => isset( $_REQUEST[ "efw_shipping_include_userroles_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_include_userroles_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_exclude_userroles'   => isset( $_REQUEST[ "efw_shipping_exclude_userroles_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_exclude_userroles_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_product_filter_type' => isset( $_REQUEST[ "efw_shipping_product_filter_type_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_product_filter_type_$shipping_method_key" ] ) ) : '',
							'efw_shipping_include_products'    => isset( $_REQUEST[ "efw_shipping_include_products_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_include_products_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_exclude_products'    => isset( $_REQUEST[ "efw_shipping_exclude_products_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_exclude_products_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_include_categories'  => isset( $_REQUEST[ "efw_shipping_include_categories_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_include_categories_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_exclude_categories'  => isset( $_REQUEST[ "efw_shipping_exclude_categories_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_exclude_categories_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_fee_based_on'  => isset( $_REQUEST[ "efw_shipping_fee_based_on_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_fee_based_on_$shipping_method_key" ] ) ) : array(),
							'efw_shipping_from_date'           => isset( $_REQUEST[ "efw_shipping_from_date_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_from_date_$shipping_method_key" ] ) ) : '',
							'efw_shipping_to_date'             => isset( $_REQUEST[ "efw_shipping_to_date_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_to_date_$shipping_method_key" ] ) ) : '',
							'efw_shipping_weekdays_for' => isset( $_REQUEST[ "efw_shipping_weekdays_for_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_weekdays_for_$shipping_method_key" ] ) ) : '',
							'efw_shipping_tax_class'           => isset( $_REQUEST[ "efw_shipping_tax_class_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_tax_class_$shipping_method_key" ] ) ) : '',
							'efw_shipping_fee_type'            => isset( $_REQUEST[ "efw_shipping_fee_type_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_fee_type_$shipping_method_key" ] ) ) : '',
							'efw_percentage_based_on'          => isset( $_REQUEST[ "efw_percentage_based_on_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_percentage_based_on_$shipping_method_key" ] ) ) : '',
							'efw_percentage_fee_type_for'          => isset( $_REQUEST[ "efw_percentage_fee_type_for_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_percentage_fee_type_for_$shipping_method_key" ] ) ) : '',
							'efw_shipping_fixed_value'         => isset( $_REQUEST[ "efw_shipping_fixed_value_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_fixed_value_$shipping_method_key" ] ) ) : '',
							'efw_add_fixed_for'         => isset( $_REQUEST[ "efw_add_fixed_for_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_add_fixed_for_$shipping_method_key" ] ) ) : '',
							'efw_shipping_percentage_value'    => isset( $_REQUEST[ "efw_shipping_percentage_value_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_percentage_value_$shipping_method_key" ] ) ) : '',
							'efw_shipping_minimum_fee_value'   => isset( $_REQUEST[ "efw_shipping_minimum_fee_value_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_minimum_fee_value_$shipping_method_key" ] ) ) : '',
							'efw_shipping_maximum_fee_value'   => isset( $_REQUEST[ "efw_shipping_maximum_fee_value_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_maximum_fee_value_$shipping_method_key" ] ) ) : '',
							'efw_shipping_fee_minimum_restriction_value' => isset( $_REQUEST[ "efw_shipping_fee_minimum_restriction_value_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_fee_minimum_restriction_value_$shipping_method_key" ] ) ) : '',
							'efw_shipping_fee_maximum_restriction_value' => isset( $_REQUEST[ "efw_shipping_fee_maximum_restriction_value_$shipping_method_key" ] ) ? wc_clean( wp_unslash( $_REQUEST[ "efw_shipping_fee_maximum_restriction_value_$shipping_method_key" ] ) ) : '',
						);

						if ( ! self::validate_settings_before_save( $settings_args ) ) {
							continue;
						}

						foreach ( $settings_args as $meta_key => $meta_value ) {
							update_option( sanitize_key( $meta_key . '_' . $shipping_method_key ), $meta_value );
						}

			}
		}
	}

	/**
	 * Validate settings before save.
	 */
	public static function validate_settings_before_save( $settings_args ) {

		if ( empty( $settings_args['efw_enable'] ) ) {
			return true;
		}

		if ( empty( $settings_args['efw_shipping_fee_text'] ) ) {
			EFW_Settings::add_error( esc_html__( 'Fee Text field cannot be empty', 'extra-fees-for-woocommerce' ) );
			return false;
		}

		if ( ! empty( $settings_args['efw_shipping_fee_type'] ) && '1' === $settings_args['efw_shipping_fee_type'] && ! $settings_args['efw_shipping_fixed_value'] ) {
			EFW_Settings::add_error( esc_html__( 'Fixed Fee Value field cannot be empty', 'extra-fees-for-woocommerce' ) );
			return false;
		}

		if ( ! empty( $settings_args['efw_shipping_fee_type'] ) && '2' == $settings_args['efw_shipping_fee_type'] && ! $settings_args['efw_shipping_percentage_value'] ) {
			EFW_Settings::add_error( esc_html__( 'Fee Value in Percent field cannot be empty', 'extra-fees-for-woocommerce' ) );
			return false;
		}

		if ( isset( $settings_args['efw_shipping_fee_minimum_restriction_value'] ) && isset( $settings_args['efw_shipping_fee_maximum_restriction_value'] ) ) {
			if ( ! empty( $settings_args['efw_shipping_fee_minimum_restriction_value'] ) && ! empty( $settings_args['efw_shipping_fee_maximum_restriction_value'] ) ) {
				if ( $settings_args['efw_shipping_fee_minimum_restriction_value'] >= $settings_args['efw_shipping_fee_maximum_restriction_value'] ) {
					EFW_Settings::add_error( esc_html__( 'Maximum Cart Subtotal/Order Total should not be less than Minimum Cart Subtotal/Order Total', 'extra-fees-for-woocommerce' ) );
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Settings after reset.
	 */
	public function settings_after_reset() {

		$shipping_methods = efw_get_wc_available_shippings(true);
		if ( ! efw_check_is_array( $shipping_methods ) ) :
			return;
		endif;

		foreach ( $shipping_methods as $shipping_id => $shipping ) :
			$settings_args = array(
				'efw_enable',
				'efw_shipping_fee_text',
				'efw_shipping_user_filter_type',
				'efw_shipping_include_users',
				'efw_shipping_exclude_users',
				'efw_shipping_include_userroles',
				'efw_shipping_exclude_userroles',
				'efw_shipping_product_filter_type',
				'efw_shipping_include_products',
				'efw_shipping_exclude_products',
				'efw_shipping_include_additional_products',
				'efw_shipping_exclude_additional_products',
				'efw_shipping_include_categories',
				'efw_shipping_exclude_categories',
				'efw_shipping_from_date',
				'efw_shipping_to_date',
				'efw_shipping_tax_class',
				'efw_shipping_fee_type',
				'efw_shipping_fixed_value',
				'efw_shipping_percentage_value',
				'efw_shipping_fee_minimum_restriction_value',
				'efw_shipping_fee_maximum_restriction_value',
			);

			foreach ( $settings_args as $option_name ) {
				delete_option( sanitize_key( $option_name . '_' . $shipping_id ) );
			}

		endforeach;
	}
}

return new EFW_Shipping_Fee_Tab();
