<?php

/**
 * Gateway Fee Tab.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'EFW_Gateway_Fee_Tab' ) ) {
	return new EFW_Gateway_Fee_Tab();
}

/**
 * EFW_Gateway_Fee_Tab.
 */
class EFW_Gateway_Fee_Tab extends EFW_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'gatewayfee';
		$this->label = esc_html__( 'Payment Gateway Fee', 'extra-fees-for-woocommerce' );

		parent::__construct();

		// Display Settings for Gateway Fee.
		add_action( sanitize_key( 'woocommerce_settings_efw_gateway_fee_settings_after' ), array( $this, 'display_gateway_settings' ) );
		// Save Settings for Gateway Fee.
		add_action( sanitize_key( $this->plugin_slug . '_' . $this->id . '_settings_after_save' ), array( $this, 'after_save' ) );
		// Reset Settings for Gateway Fee.
		add_action( sanitize_key( $this->plugin_slug . '_' . $this->id . '_settings_after_reset' ), array( $this, 'after_reset' ) );
		// Validate Settings Enabled.
		add_action( 'efw_gatewayfee_before_settings_display', array( $this, 'validate_settings_enable' ) );
	}

	/**
	 * Validate Settings Enabled.
	 */
	public function validate_settings_enable() {
		if ('yes' != get_option('efw_gatewayfee_enable')) {
			echo "<div class='error'><p>" . esc_html('Enable the global checkbox to charge the Payment Gateway Fee.', 'extra-fees-for-woocommerce') . '</p></div>';
		}
	}

	/**
	 * Get Gateway Fee Settings section array.
	 */
	public function gatewayfee_section_array() {
		$section_fields = array();

		$section_fields[] = array(
			'type'      => 'efw_custom_fields',
			'efw_field' => 'section_start',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Payment Gateway Fee Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_gateway_fee_settings',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Enable Payment Gateway Fee', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'desc'    => esc_html__( 'When enabled, a fee has to be paid by the user for choosing to make the payment with a particular payment gateway.', 'extra-fees-for-woocommerce' ),
			'id'      => $this->get_option_key( 'enable' ),
		);

		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_gateway_fee_settings',
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
	public function display_gateway_settings() {
		$available_gateways = efw_get_wc_available_gateways( true );
		foreach ( $available_gateways as $gateway_id => $gateway_title ) {

			$enable_deal              = get_option( 'efw_enable_fee_for_' . $gateway_id );
			$user_filter_type         = get_option( 'efw_user_filter_type_for_' . $gateway_id );
			$include_user             = get_option( 'efw_include_user_for_' . $gateway_id );
			$exclude_user             = get_option( 'efw_exclude_user_for_' . $gateway_id );
			$include_user_role        = get_option( 'efw_include_userrole_for_' . $gateway_id );
			$exclude_user_role        = get_option( 'efw_exclude_userrole_for_' . $gateway_id );
			$product_filter_type      = get_option( 'efw_product_filter_type_for_' . $gateway_id );
			$include_product          = get_option( 'efw_include_product_for_' . $gateway_id );
			$exclude_product          = get_option( 'efw_exclude_product_for_' . $gateway_id );
			$include_category         = get_option( 'efw_include_category_for_' . $gateway_id );
			$include_additional_products         = get_option( 'efw_include_additional_product_for_' . $gateway_id );
			$exclude_additional_products         = get_option( 'efw_exclude_additional_product_for_' . $gateway_id );
			$exclude_category         = get_option( 'efw_exclude_category_for_' . $gateway_id );
			$include_country          = get_option( 'efw_include_countries_for_' . $gateway_id );
			$fee_based_on             = get_option( 'efw_add_fee_based_on_' . $gateway_id );
			$include_states           = get_option( 'efw_include_states_for_' . $gateway_id );
			$from_date                = get_option( 'efw_from_date_for_' . $gateway_id );
			$to_date                  = get_option( 'efw_to_date_for_' . $gateway_id );
			$selected_week_days       = get_option( 'efw_weekdays_for_' . $gateway_id );
			$fee_text                 = get_option( 'efw_fee_text_for_' . $gateway_id );
			$fee_description          = get_option( 'efw_fee_description_for_' . $gateway_id );
			$tax_class                = get_option( 'efw_tax_class_for_' . $gateway_id );
			$fee_type                 = get_option( 'efw_fee_type_for_' . $gateway_id );
			$percentage_fee_type      = get_option( 'efw_percentage_fee_type_for_' . $gateway_id );
			$fixed_value              = get_option( 'efw_fixed_value_for_' . $gateway_id );
			$percent_of_cart_subtotal = get_option( 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id );
			$percentage_type          = get_option( 'efw_percentage_type_for_' . $gateway_id );
			$add_fixed_fee_on         = get_option( 'efw_add_fixed_for_' . $gateway_id );
			$min_fee                  = get_option( 'efw_min_fee_for_' . $gateway_id );
			$max_fee                  = get_option( 'efw_max_fee_for_' . $gateway_id );
			$min_total                = get_option( 'efw_min_subtotal_for_' . $gateway_id );
			$max_total                = get_option( 'efw_max_subtotal_for_' . $gateway_id );

			include EFW_ABSPATH . 'inc/admin/menu/views/gateway/gateway-settings.php';
		}
	}

	/**
	 * Save added rules for Gateway Deal.
	 */
	public function after_save() {
		$available_gateways = efw_get_wc_available_gateways( true );

		$update = true;

		foreach ( $available_gateways as $gateway_id => $gateway_title ) {

			$update = self::validate_fields( $update, $_REQUEST, $gateway_id, $gateway_title );

			if ( ! $update ) {
				continue;
			}

			if ( isset( $_REQUEST[ 'efw_enable_fee_for_' . $gateway_id ] ) ) {
				update_option( 'efw_enable_fee_for_' . $gateway_id, 'yes' );
			} else {
				update_option( 'efw_enable_fee_for_' . $gateway_id, 'no' );
			}

			if ( isset( $_REQUEST[ 'efw_user_filter_type_for_' . $gateway_id ] ) ) {
				$user_filter_type = wc_clean( wp_unslash( $_REQUEST[ 'efw_user_filter_type_for_' . $gateway_id ] ) );
				update_option( 'efw_user_filter_type_for_' . $gateway_id, $user_filter_type );
			}

			if ( isset( $_REQUEST[ 'efw_include_user_for_' . $gateway_id ] ) ) {
				$include_user = wc_clean( wp_unslash( $_REQUEST[ 'efw_include_user_for_' . $gateway_id ] ) );
				update_option( 'efw_include_user_for_' . $gateway_id, $include_user );
			}

			if ( isset( $_REQUEST[ 'efw_exclude_user_for_' . $gateway_id ] ) ) {
				$exclude_user = wc_clean( wp_unslash( $_REQUEST[ 'efw_exclude_user_for_' . $gateway_id ] ) );
				update_option( 'efw_exclude_user_for_' . $gateway_id, $exclude_user );
			}

			if ( isset( $_REQUEST[ 'efw_include_userrole_for_' . $gateway_id ] ) ) {
				$include_user_role = wc_clean( wp_unslash( $_REQUEST[ 'efw_include_userrole_for_' . $gateway_id ] ) );
				update_option( 'efw_include_userrole_for_' . $gateway_id, $include_user_role );
			} else {
				update_option( 'efw_include_userrole_for_' . $gateway_id, array() );
			}

			if ( isset( $_REQUEST[ 'efw_exclude_userrole_for_' . $gateway_id ] ) ) {
				$exclude_user_role = wc_clean( wp_unslash( $_REQUEST[ 'efw_exclude_userrole_for_' . $gateway_id ] ) );
				update_option( 'efw_exclude_userrole_for_' . $gateway_id, $exclude_user_role );
			} else {
				update_option( 'efw_exclude_userrole_for_' . $gateway_id, array() );
			}

			if ( isset( $_REQUEST[ 'efw_product_filter_type_for_' . $gateway_id ] ) ) {
				$product_filter_type = wc_clean( wp_unslash( $_REQUEST[ 'efw_product_filter_type_for_' . $gateway_id ] ) );
				update_option( 'efw_product_filter_type_for_' . $gateway_id, $product_filter_type );
			}

			if ( isset( $_REQUEST[ 'efw_include_product_for_' . $gateway_id ] ) ) {
				$include_product = wc_clean( wp_unslash( $_REQUEST[ 'efw_include_product_for_' . $gateway_id ] ) );
				update_option( 'efw_include_product_for_' . $gateway_id, $include_product );
			}

			if ( isset( $_REQUEST[ 'efw_exclude_product_for_' . $gateway_id ] ) ) {
				$exclude_product = wc_clean( wp_unslash( $_REQUEST[ 'efw_exclude_product_for_' . $gateway_id ] ) );
				update_option( 'efw_exclude_product_for_' . $gateway_id, $exclude_product );
			}

			if ( isset( $_REQUEST[ 'efw_include_category_for_' . $gateway_id ] ) ) {
				$include_category = wc_clean( wp_unslash( $_REQUEST[ 'efw_include_category_for_' . $gateway_id ] ) );
				update_option( 'efw_include_category_for_' . $gateway_id, $include_category );
			} else {
				update_option( 'efw_include_category_for_' . $gateway_id, array() );
			}

			if ( isset( $_REQUEST[ 'efw_include_additional_product_for_' . $gateway_id ] ) ) {
				$include_additional_product = wc_clean( wp_unslash( $_REQUEST[ 'efw_include_additional_product_for_' . $gateway_id ] ) );
				update_option( 'efw_include_additional_product_for_' . $gateway_id, $include_additional_product );
			}

			if ( isset( $_REQUEST[ 'efw_exclude_category_for_' . $gateway_id ] ) ) {
				$exclude_category = wc_clean( wp_unslash( $_REQUEST[ 'efw_exclude_category_for_' . $gateway_id ] ) );
				update_option( 'efw_exclude_category_for_' . $gateway_id, $exclude_category );
			} else {
				update_option( 'efw_exclude_category_for_' . $gateway_id, array() );
			}

			if ( isset( $_REQUEST[ 'efw_exclude_additional_product_for_' . $gateway_id ] ) ) {
				$exclude_additional_product = wc_clean( wp_unslash( $_REQUEST[ 'efw_exclude_additional_product_for_' . $gateway_id ] ) );
				update_option( 'efw_exclude_additional_product_for_' . $gateway_id, $exclude_additional_product );
			}

			if ( isset( $_REQUEST[ 'efw_add_fee_based_on_' . $gateway_id ] ) ) {
				$fee_based_on = wc_clean( wp_unslash( $_REQUEST[ 'efw_add_fee_based_on_' . $gateway_id ] ) );
				update_option( 'efw_add_fee_based_on_' . $gateway_id, $fee_based_on );
			}

			if ( isset( $_REQUEST[ 'efw_include_states_for_' . $gateway_id ] ) ) {
				$include_states = wc_clean( wp_unslash( $_REQUEST[ 'efw_include_states_for_' . $gateway_id ] ) );
				update_option( 'efw_include_states_for_' . $gateway_id, $include_states );
			} else {
				update_option( 'efw_include_states_for_' . $gateway_id, array() );
			}

			if ( isset( $_REQUEST[ 'efw_include_countries_for_' . $gateway_id ] ) ) {
				$include_countries = wc_clean( wp_unslash( $_REQUEST[ 'efw_include_countries_for_' . $gateway_id ] ) );
				update_option( 'efw_include_countries_for_' . $gateway_id, $include_countries );
			} else {
				update_option( 'efw_include_countries_for_' . $gateway_id, array() );
			}

			if ( isset( $_REQUEST[ 'efw_from_date_for_' . $gateway_id ] ) ) {
				$from_date = wc_clean( wp_unslash( $_REQUEST[ 'efw_from_date_for_' . $gateway_id ] ) );
				update_option( 'efw_from_date_for_' . $gateway_id, $from_date );
			}

			if ( isset( $_REQUEST[ 'efw_to_date_for_' . $gateway_id ] ) ) {
				$to_date = wc_clean( wp_unslash( $_REQUEST[ 'efw_to_date_for_' . $gateway_id ] ) );
				update_option( 'efw_to_date_for_' . $gateway_id, $to_date );
			}

			if ( isset( $_REQUEST[ 'efw_weekdays_for_' . $gateway_id ] ) ) {
				$selected_days = wc_clean( wp_unslash( $_REQUEST[ 'efw_weekdays_for_' . $gateway_id ] ) );
				update_option( 'efw_weekdays_for_' . $gateway_id, $selected_days );
			} else {
				update_option( 'efw_weekdays_for_' . $gateway_id, array() );
			}

			if ( isset( $_REQUEST[ 'efw_fee_text_for_' . $gateway_id ] ) ) {
				$fee_text = wc_clean( wp_unslash( $_REQUEST[ 'efw_fee_text_for_' . $gateway_id ] ) );
				update_option( 'efw_fee_text_for_' . $gateway_id, $fee_text );
			}

			if ( isset( $_REQUEST[ 'efw_fee_description_for_' . $gateway_id ] ) ) {
					$request     = $_REQUEST;
				$fee_description = wp_unslash( $request[ 'efw_fee_description_for_' . $gateway_id ] );
				update_option( 'efw_fee_description_for_' . $gateway_id, $fee_description );
			}

			if ( isset( $_REQUEST[ 'efw_fee_type_for_' . $gateway_id ] ) ) {
				$fee_type = wc_clean( wp_unslash( $_REQUEST[ 'efw_fee_type_for_' . $gateway_id ] ) );
				update_option( 'efw_fee_type_for_' . $gateway_id, $fee_type );
			}

			if ( isset( $_REQUEST[ 'efw_percentage_fee_type_for_' . $gateway_id ] ) ) {
				$fee_type = wc_clean( wp_unslash( $_REQUEST[ 'efw_percentage_fee_type_for_' . $gateway_id ] ) );
				update_option( 'efw_percentage_fee_type_for_' . $gateway_id, $fee_type );
			}

			if ( isset( $_REQUEST[ 'efw_fixed_value_for_' . $gateway_id ] ) ) {
				$fixed_value = wc_clean( wp_unslash( $_REQUEST[ 'efw_fixed_value_for_' . $gateway_id ] ) );
				update_option( 'efw_fixed_value_for_' . $gateway_id, $fixed_value );
			}

			if ( isset( $_REQUEST[ 'efw_percentage_type_for_' . $gateway_id ] ) ) {
				$fee_type = wc_clean( wp_unslash( $_REQUEST[ 'efw_percentage_type_for_' . $gateway_id ] ) );
				update_option( 'efw_percentage_type_for_' . $gateway_id, $fee_type );
			}

			if ( isset( $_REQUEST[ 'efw_add_fixed_for_' . $gateway_id ] ) ) {
				$fee_type = wc_clean( wp_unslash( $_REQUEST[ 'efw_add_fixed_for_' . $gateway_id ] ) );
				update_option( 'efw_add_fixed_for_' . $gateway_id, $fee_type );
			}

			if ( isset( $_REQUEST[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) ) {
				$percent_of_cart_subtotal = wc_clean( wp_unslash( $_REQUEST[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) );
				update_option( 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id, $percent_of_cart_subtotal );
			}

			if ( isset( $_REQUEST[ 'efw_min_fee_for_' . $gateway_id ] ) ) {
				$min_fee = wc_clean( wp_unslash( $_REQUEST[ 'efw_min_fee_for_' . $gateway_id ] ) );
				update_option( 'efw_min_fee_for_' . $gateway_id, $min_fee );
			}

			if ( isset( $_REQUEST[ 'efw_max_fee_for_' . $gateway_id ] ) ) {
				$max_fee = wc_clean( wp_unslash( $_REQUEST[ 'efw_max_fee_for_' . $gateway_id ] ) );
				update_option( 'efw_max_fee_for_' . $gateway_id, $max_fee );
			}

			if ( isset( $_REQUEST[ 'efw_tax_class_for_' . $gateway_id ] ) ) {
				$tax_class = wc_clean( wp_unslash( $_REQUEST[ 'efw_tax_class_for_' . $gateway_id ] ) );
				update_option( 'efw_tax_class_for_' . $gateway_id, $tax_class );
			}

			if ( isset( $_REQUEST[ 'efw_min_subtotal_for_' . $gateway_id ] ) ) {
				$min_total = wc_clean( wp_unslash( $_REQUEST[ 'efw_min_subtotal_for_' . $gateway_id ] ) );
				update_option( 'efw_min_subtotal_for_' . $gateway_id, $min_total );
			}

			if ( isset( $_REQUEST[ 'efw_max_subtotal_for_' . $gateway_id ] ) ) {
				$max_total = wc_clean( wp_unslash( $_REQUEST[ 'efw_max_subtotal_for_' . $gateway_id ] ) );
				update_option( 'efw_max_subtotal_for_' . $gateway_id, $max_total );
			}
		}
	}

	/**
	 * Validate the Fields.
	 */
	public function validate_fields( $update, $fields, $gateway_id, $gateway_title ) {

		if ( isset( $fields[ 'efw_enable_fee_for_' . $gateway_id ] ) && isset( $fields[ 'efw_fee_text_for_' . $gateway_id ] ) && isset( $fields[ 'efw_fee_type_for_' . $gateway_id ] ) ) {
			if ( empty( $fields[ 'efw_fee_text_for_' . $gateway_id ] ) ) {
				$update = false;
				/* translators: %s : Gateway Name */
				EFW_Settings::add_error( sprintf( esc_html__( '%s : Fee Text field cannot be empty', 'extra-fees-for-woocommerce' ), $gateway_title ) );
			}

			if ( '1' == $fields[ 'efw_fee_type_for_' . $gateway_id ] ) {
				if ( isset( $fields[ 'efw_fixed_value_for_' . $gateway_id ] ) && empty( $fields[ 'efw_fixed_value_for_' . $gateway_id ] ) ) {
					$update = false;
					/* translators: %s : Gateway Name */
					EFW_Settings::add_error( sprintf( esc_html__( '%s : Fixed Fee Value field cannot be empty', 'extra-fees-for-woocommerce' ), $gateway_title ) );
				}
			}

			if ( '2' == $fields[ 'efw_fee_type_for_' . $gateway_id ] ) {
				if ( isset( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) && empty( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) ) {
					$update = false;
					/* translators: %s : Gateway Name */
					EFW_Settings::add_error( sprintf( esc_html__( '%s : Fee Value in Percent field cannot be empty', 'extra-fees-for-woocommerce' ), $gateway_title ) );
				} else if (isset( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) && ! empty( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) ) {
					if (isset( $fields[ 'efw_percentage_fee_type_for_' . $gateway_id ] ) && ( '2' == $fields[ 'efw_percentage_fee_type_for_' . $gateway_id ] ) ) {
						if ( ( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] > '90' ) ) {
							$update = false;
							/* translators: %s : Gateway Name */
							EFW_Settings::add_error( sprintf( esc_html__( '%s : Fee Value in Percent field cannot be more than 90', 'extra-fees-for-woocommerce' ), $gateway_title ) );
						}
					}
				}
			}

			if ( '3' == $fields[ 'efw_fee_type_for_' . $gateway_id ] ) {
				if ( isset( $fields[ 'efw_fixed_value_for_' . $gateway_id ] ) && empty( $fields[ 'efw_fixed_value_for_' . $gateway_id ] ) ) {
					$update = false;
					/* translators: %s : Gateway Name */
					EFW_Settings::add_error( sprintf( esc_html__( '%s : Fixed Fee Value field cannot be empty', 'extra-fees-for-woocommerce' ), $gateway_title ) );
				}

				if ( isset( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) && empty( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) ) {
					$update = false;
					/* translators: %s : Gateway Name */
					EFW_Settings::add_error( sprintf( esc_html__( '%s : Fee Value in Percent field cannot be empty', 'extra-fees-for-woocommerce' ), $gateway_title ) );
				} else if (isset( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) && ! empty( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] ) ) {
					if (isset( $fields[ 'efw_percentage_fee_type_for_' . $gateway_id ] ) && ( '2' == $fields[ 'efw_percentage_fee_type_for_' . $gateway_id ] ) ) {
						if ( ( $fields[ 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ] > '90' ) ) {
							$update = false;
							/* translators: %s : Gateway Name */
							EFW_Settings::add_error( sprintf( esc_html__( '%s : Fee Value in Percent field cannot be more than 90', 'extra-fees-for-woocommerce' ), $gateway_title ) );
						}
					}
				}
			}

			if ( isset( $fields[ 'efw_min_subtotal_for_' . $gateway_id ] ) && isset( $fields[ 'efw_max_subtotal_for_' . $gateway_id ] ) ) {
				if ( ! empty( $fields[ 'efw_min_subtotal_for_' . $gateway_id ] ) && ! empty( $fields[ 'efw_max_subtotal_for_' . $gateway_id ] ) ) {
					if ( $fields[ 'efw_min_subtotal_for_' . $gateway_id ] >= $fields[ 'efw_max_subtotal_for_' . $gateway_id ] ) {
						$update = false;
						/* translators: %s : Gateway Name */
						EFW_Settings::add_error( sprintf( esc_html__( '%s : Maximum Cart Subtotal/Order Total should not be less than Minimum Cart Subtotal/Order Total', 'extra-fees-for-woocommerce' ), $gateway_title ) );
					}
				}
			}
		}

		return $update;
	}

	/**
	 * Delete the added rules for Gateway Deal.
	 */
	public function after_reset() {
		$available_gateways = efw_get_wc_available_gateways( true );

		foreach ( $available_gateways as $gateway_id => $gateway_title ) {

			delete_option( 'efw_enable_fee_for_' . $gateway_id );

			delete_option( 'efw_user_filter_type_for_' . $gateway_id );

			delete_option( 'efw_include_user_for_' . $gateway_id );

			delete_option( 'efw_exclude_user_for_' . $gateway_id );

			delete_option( 'efw_include_userrole_for_' . $gateway_id );

			delete_option( 'efw_exclude_userrole_for_' . $gateway_id );

			delete_option( 'efw_product_filter_type_for_' . $gateway_id );

			delete_option( 'efw_include_product_for_' . $gateway_id );

			delete_option( 'efw_exclude_product_for_' . $gateway_id );

			delete_option( 'efw_include_category_for_' . $gateway_id );

			delete_option( 'efw_exclude_category_for_' . $gateway_id );

			delete_option( 'efw_include_countries_for_' . $gateway_id );

			delete_option( 'efw_add_fee_based_on_' . $gateway_id );

			delete_option( 'efw_include_states_for_' . $gateway_id );

			delete_option( 'efw_from_date_for_' . $gateway_id );

			delete_option( 'efw_to_date_for_' . $gateway_id );

			delete_option( 'efw_fee_text_for_' . $gateway_id );

			delete_option( 'efw_tax_class_for_' . $gateway_id );

			delete_option( 'efw_fee_type_for_' . $gateway_id );

			delete_option( 'efw_fixed_value_for_' . $gateway_id );

			delete_option( 'efw_percentage_type_for_' . $gateway_id );

			delete_option( 'efw_add_fixed_for_' . $gateway_id );

			delete_option( 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id );

			delete_option( 'efw_min_fee_for_' . $gateway_id );

			delete_option( 'efw_max_fee_for_' . $gateway_id );

			delete_option( 'efw_min_subtotal_for_' . $gateway_id );

			delete_option( 'efw_max_subtotal_for_' . $gateway_id );
		}
	}
}

return new EFW_Gateway_Fee_Tab();
