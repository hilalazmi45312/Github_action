<?php
/**
 * Order Total Fee Tab.
 *
 * @package Extra Fee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'EFW_Order_Total_Fee_Tab' ) ) {
	return new EFW_Order_Total_Fee_Tab();
}

/**
 * EFW_Order_Total_Fee_Tab.
 */
class EFW_Order_Total_Fee_Tab extends EFW_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'ordertotalfee';
		$this->label = esc_html__( 'Order Fee', 'extra-fees-for-woocommerce' );

		parent::__construct();

		// Validate Settings for Before Save.
		add_filter( 'efw_validate_settings_before_save', array( $this, 'validate_before_save' ) );
		// Display Multiple level fee table.
		add_action( 'woocommerce_admin_field_efw_multiple_level_fee_table', array( $this, 'multiple_level_fee_table' ) );
		// Validate Settings Enabled.
		add_action( 'efw_ordertotalfee_before_settings_display', array( $this, 'validate_settings_enable' ) );
	}

	/**
	 * Validate Settings Enabled.
	 */
	public function validate_settings_enable() {
		if ('yes' != get_option('efw_ordertotalfee_enable')) {
			echo "<div class='error'><p>" . esc_html('Enable the global checkbox to charge the Order Fee.', 'extra-fees-for-woocommerce') . '</p></div>';
		}
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public function save() {
		if ( ! isset( $_REQUEST['efw_save'] ) ) {
			return;
		}

		// Save Order fees.
		$this->save_order_total_fee_table();

		parent::save();
	}

	/**
	 * Reset settings.
	 *
	 * @return void
	 */
	public function reset() {
		if ( ! isset( $_REQUEST['efw_reset'] ) ) {
			return;
		}

		// Reset Order fees.
		$efw_fee_rules = efw_order_fee_table_default_values();
		update_option( 'efw_fee_rule', $efw_fee_rules );

		parent::reset();
	}

	/**
	 * Save Order fees.
	 *
	 * @return void
	 */
	public function save_order_total_fee_table() {
		$efw_min_cart_fee = isset( $_REQUEST['efw_fee_rule'] ) ? wc_clean( wp_unslash( $_REQUEST['efw_fee_rule'] ) ) : array();
		update_option( 'efw_fee_rule', $efw_min_cart_fee );
	}

	/**
	 * Get Order Total Fee Settings section array.
	 */ 
	public function ordertotalfee_section_array() {
		$section_fields = array();

		$section_fields[]         = array(
			'type'  => 'title',
			'title' => esc_html__( 'Order Fee Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_order_total_fee_settings',
		);
		$section_fields[]         = array(
			'title'   => esc_html__( 'Enable Order Fee', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'desc'    => esc_html__( 'When enabled, a fee has to be paid by the user for purchasing within the specified range', 'extra-fees-for-woocommerce' ),
			'id'      => $this->get_option_key( 'enable' ),
		);
		$section_fields[]         = array(
			'title'   => esc_html__( 'Fee Text', 'extra-fees-for-woocommerce' ),
			'type'    => 'text',
			'default' => '',
			'id'      => $this->get_option_key( 'fee_text' ),
			'class'   => 'show-if-order-fee-enable',
		);
		$section_fields[]         = array(
			'title' => esc_html__( 'Fee Description', 'extra-fees-for-woocommerce' ),
			'type'  => 'textarea',
			'id'    => $this->get_option_key( 'fee_description' ),
			'class' => 'show-if-order-fee-enable',
		);
		$section_fields[]         = array(
			'title'    => esc_html__( 'Tax Class', 'extra-fees-for-woocommerce' ),
			'type'     => 'select',
			'default'  => '1',
			'id'       => $this->get_option_key( 'tax_class' ),
			'options'  => efw_get_fee_tax_classes(),
			'desc_tip' => true,
			'desc'     => esc_html__( 'Select the tax which should be used for calculating the fee', 'extra-fees-for-woocommerce' ),
			'class'    => 'show-if-order-fee-enable',
			'value'    => in_array( get_option( 'efw_ordertotalfee_tax_class' ), array_keys( efw_get_fee_tax_classes() ) ) ? get_option( 'efw_ordertotalfee_tax_class' ) : 'standard',
		);
		$section_fields[]         = array(
			'title'    => esc_html__( 'Exclude Shipping based on', 'extra-fees-for-woocommerce' ),
			'type'     => 'select',
			'default'  => '1',
			'id'       => $this->get_option_key( 'shipping_based_on' ),
			'options'  => array(
				'1' => esc_html__( 'Shipping Method(s)', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Shipping Method(s)/Shipping Zone(s)', 'extra-fees-for-woocommerce' ),
			),
			'desc_tip' => true,
			'desc'     => 'Order fee can be restricted based on the shipping zone & shipping method.<br><b>Shipping Method(s):</b> Order fee will not be charged when choosing the shipping method selected here.',
			'class'    => 'show-if-order-fee-enable',
		);
		$section_fields[]         = array(
			'title'    => esc_html__( 'Exclude Shipping Method(s)', 'extra-fees-for-woocommerce' ),
			'type'     => 'multiselect',
			'class'    => 'efw_select2 show-if-order-fee-enable',
			'default'  => '',
			'id'       => $this->get_option_key( 'excluded_shipping' ),
			'options'  => efw_get_wc_shipping_methods(),
			'desc_tip' => true,
			'desc'     => esc_html__( 'Order fee will not be charged for the shipping method(s) selected in this option.', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[]         = array(
			'title'    => esc_html__( 'Shipping Zone(s) & Shipping Method(s)', 'extra-fees-for-woocommerce' ),
			'type'     => 'multiselect',
			'class'    => 'efw_select2 show-if-order-fee-enable',
			'default'  => '',
			'id'       => $this->get_option_key( 'excluded_shipping_zone' ),
			'options'  => efw_get_wc_shipping_zones(),
			'desc_tip' => true,
			'desc'     => esc_html__( 'Order fee will not be charged when choosing the shipping zone & shipping  method selected here.', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[]         = array(
			'title'   => esc_html__( 'Fee should apply for', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'user_filter' ),
			'options' => array(
				'1' => esc_html__( 'All Users', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Include User(s)', 'extra-fees-for-woocommerce' ),
				'3' => esc_html__( 'Exclude User(s)', 'extra-fees-for-woocommerce' ),
				'4' => esc_html__( 'Include User Role(s)', 'extra-fees-for-woocommerce' ),
				'5' => esc_html__( 'Exclude User Role(s)', 'extra-fees-for-woocommerce' ),
			),
			'class'   => 'show-if-order-fee-enable',
		);
		$section_fields[]         = array(
			'title'                   => esc_html__( 'Select User(s) to Include', 'extra-fees-for-woocommerce' ),
			'type'                    => 'efw_custom_fields',
			'efw_field'               => 'ajaxmultiselect',
			'list_type'               => 'customers',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_customers_search',
			'default'                 => array(),
			'allow_clear'             => false,
			'id'                      => $this->get_option_key( 'include_users' ),
			'class'                   => 'show-if-order-fee-enable',
		);
		$section_fields[]         = array(
			'title'                   => esc_html__( 'Select User(s) to Exclude', 'extra-fees-for-woocommerce' ),
			'type'                    => 'efw_custom_fields',
			'efw_field'               => 'ajaxmultiselect',
			'list_type'               => 'customers',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_customers_search',
			'default'                 => array(),
			'allow_clear'             => false,
			'id'                      => $this->get_option_key( 'exclude_users' ),
			'class'                   => 'show-if-order-fee-enable',
		);
		$section_fields[]         = array(
			'title'   => esc_html__( 'Select User Role(s) to Include', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2 show-if-order-fee-enable',
			'default' => '',
			'options' => efw_get_user_roles(),
			'id'      => $this->get_option_key( 'include_userrole' ),
		);
		$section_fields[]         = array(
			'title'   => esc_html__( 'Select User Role(s) to Exclude', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2 show-if-order-fee-enable',
			'default' => '',
			'options' => efw_get_user_roles(),
			'id'      => $this->get_option_key( 'exclude_userrole' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Product/Category Filter', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'product_filter' ),
			'options' => array(
				'1' => esc_html__( 'All Products', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Include Product(s)', 'extra-fees-for-woocommerce' ),
				'3' => esc_html__( 'Exclude Product(s)', 'extra-fees-for-woocommerce' ),
				'4' => esc_html__( 'Include Categories', 'extra-fees-for-woocommerce' ),
				'5' => esc_html__( 'Exclude Categories', 'extra-fees-for-woocommerce' ),
			),
			'class'   => 'show-if-order-fee-enable',
		);
		$section_fields[] = array(
			'title'                   => esc_html__( 'Select Product(s) to Include', 'extra-fees-for-woocommerce' ),
			'type'                    => 'efw_custom_fields',
			'efw_field'               => 'ajaxmultiselect',
			'list_type'               => 'products',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_product_search',
			'default'                 => array(),
			'allow_clear'             => false,
			'id'                      => $this->get_option_key( 'include_products' ),
			'class'                   => 'show-if-order-fee-enable',
		);
		$section_fields[] = array(
			'title'                   => esc_html__( 'Select Product(s) to Exclude', 'extra-fees-for-woocommerce' ),
			'type'                    => 'efw_custom_fields',
			'efw_field'               => 'ajaxmultiselect',
			'list_type'               => 'products',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_product_search',
			'default'                 => array(),
			'allow_clear'             => false,
			'id'                      => $this->get_option_key( 'exclude_products' ),
			'class'                   => 'show-if-order-fee-enable',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Categories to Include', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2 show-if-order-fee-enable',
			'default' => '',
			'options' => efw_get_wc_categories(),
			'id'      => $this->get_option_key( 'include_categories' ),
		);
		$section_fields[] = array(
			'title'                   => esc_html__( 'Select Additional Product(s) to Include', 'extra-fees-for-woocommerce' ),
			'type'                    => 'efw_custom_fields',
			'efw_field'               => 'ajaxmultiselect',
			'list_type'               => 'products',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_product_search',
			'default'                 => array(),
			'allow_clear'             => false,
			'id'                      => $this->get_option_key( 'include_additional_products' ),
			'class'                   => 'show-if-order-fee-enable',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Categories to Exclude', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2 show-if-order-fee-enable',
			'default' => '',
			'options' => efw_get_wc_categories(),
			'id'      => $this->get_option_key( 'exclude_categories' ),
		);
		$section_fields[] = array(
			'title'                   => esc_html__( 'Select Additional Product(s) to Exclude', 'extra-fees-for-woocommerce' ),
			'type'                    => 'efw_custom_fields',
			'efw_field'               => 'ajaxmultiselect',
			'list_type'               => 'products',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_product_search',
			'default'                 => array(),
			'allow_clear'             => false,
			'id'                      => $this->get_option_key( 'exclude_additional_products' ),
			'class'                   => 'show-if-order-fee-enable',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Fee Configuration Based on', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'fee_configuration' ),
			'options' => array(
				'1' => esc_html__( 'Single Level', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Multiple Levels', 'extra-fees-for-woocommerce' ),
			),
			'class'   => 'show-if-order-fee-enable',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Fee Type', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'fee_type' ),
			'options' => efw_get_fee_type_options(),
			'class'   => 'show-if-order-fee-enable efw-single-level-elements',
		);
		$section_fields[] = array(
			'title'             => esc_html__( 'Fixed Fee Value', 'extra-fees-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => 'any',
			),
			'id'                => $this->get_option_key( 'fixed_value' ),
			'class'             => 'show-if-order-fee-enable efw-single-level-elements',
		);
		$section_fields[] = array(
			'title'             => esc_html__( 'Fee Value in %', 'extra-fees-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => 'any',
			),
			'id'                => $this->get_option_key( 'cart_subtotal_percentage' ),
			'class'             => 'show-if-order-fee-enable efw-single-level-elements',
		);
		$section_fields[] = array(
			'title'             => esc_html__( 'Minimum Cart Subtotal/Order Total to Add Fee', 'extra-fees-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => 'any',
			),
			'id'                => $this->get_option_key( 'min_sub_total' ),
			'desc_tip'          => true,
			'desc'              => esc_html__( '<b>Fixed Fee</b> - The Minimum Order Total is required to add a fee.<br><b>Percentage of Cart Subtotal/Order Total</b> - The Minimum Cart Subtotal/Order Total which is required to add a fee.', 'extra-fees-for-woocommerce' ),
			'class'             => 'show-if-order-fee-enable efw-single-level-elements',
		);
		$section_fields[] = array(
			'title'             => esc_html__( 'Maximum Cart Subtotal/Order Total to Add Fee', 'extra-fees-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => 'any',
			),
			'id'                => $this->get_option_key( 'max_sub_total' ),
			'desc_tip'          => true,
			'desc'              => esc_html__( '<b>Fixed Fee</b> - The fee will be added when the Order Total is less than or equal to the value set in this field.<br><b>Percentage of Cart Subtotal/Order Total</b> - The fee will be added when the Cart Subtotal/Order Total is less than or equal to the value set in this field.', 'extra-fees-for-woocommerce' ),
			'class'             => 'show-if-order-fee-enable efw-single-level-elements',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Rule Priority', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'rule_priority' ),
			'options' => array(
				'1' => esc_html__( 'First Matched Rule', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Last Matched Rule', 'extra-fees-for-woocommerce' ),
			),
			'class'   => 'show-if-order-fee-enable efw-multiple-level-elements',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_order_total_fee_settings',
		);
		$section_fields[] = array(
			'type'  => 'efw_multiple_level_fee_table',
			'class' => 'show-if-order-fee-enable',
		);

		/**
		 * Hook:efw_order_fee_settings.
		 *
		 * @since 5.1.2
		 */
		return apply_filters('efw_order_fee_settings', $section_fields);
	}

	/**
	 * Display the Multiple level fee table.
	 */
	public function multiple_level_fee_table() {
		include_once EFW_PLUGIN_PATH . '/inc/admin/menu/views/html-multiple-level-page.php';
	}

	/**
	 * Validate Settings Before Save.
	 *
	 * @since 1.0
	 * @param Boolean $bool Return Type.
	 * @return Boolean
	 */
	public static function validate_before_save( $bool ) {

		if ( isset( $_REQUEST['efw_ordertotalfee_enable'] ) ) {
			if ( isset( $_REQUEST['efw_ordertotalfee_fee_text'] ) && empty( $_REQUEST['efw_ordertotalfee_fee_text'] ) ) {
				EFW_Settings::add_error( esc_html__( 'Fee Text field cannot be empty', 'extra-fees-for-woocommerce' ) );
				$bool = false;
			}

			$fee_configuration_type = ( isset( $_REQUEST['efw_ordertotalfee_fee_configuration'] ) ) ? wc_clean( wp_unslash( $_REQUEST['efw_ordertotalfee_fee_configuration'] ) ) : '1';

			if ( '1' === $fee_configuration_type ) {
				$fee_type = isset( $_REQUEST['efw_ordertotalfee_fee_type'] ) && ! empty( $_REQUEST['efw_ordertotalfee_fee_type'] ) ? wc_clean( wp_unslash( $_REQUEST['efw_ordertotalfee_fee_type'] ) ) : '1';

				if ( '1' !== $fee_type ) {
					if ( isset( $_REQUEST['efw_ordertotalfee_cart_subtotal_percentage'] ) && empty( $_REQUEST['efw_ordertotalfee_cart_subtotal_percentage'] ) ) {
						EFW_Settings::add_error( esc_html__( 'Fee Value in Percent field cannot be empty', 'extra-fees-for-woocommerce' ) );
						$bool = false;
					}
				} elseif ( isset( $_REQUEST['efw_ordertotalfee_fixed_value'] ) && empty( $_REQUEST['efw_ordertotalfee_fixed_value'] ) ) {
						EFW_Settings::add_error( esc_html__( 'Fixed Fee Value field cannot be empty', 'extra-fees-for-woocommerce' ) );
						$bool = false;
				}
			}

			if ( isset( $_REQUEST['efw_ordertotalfee_min_sub_total'] ) && isset( $_REQUEST['efw_ordertotalfee_max_sub_total'] ) ) {
				if ( ! empty( $_REQUEST['efw_ordertotalfee_min_sub_total'] ) && ! empty( $_REQUEST['efw_ordertotalfee_max_sub_total'] ) ) {
					if ( $_REQUEST['efw_ordertotalfee_min_sub_total'] >= $_REQUEST['efw_ordertotalfee_max_sub_total'] ) {
						EFW_Settings::add_error( esc_html__( 'Maximum Cart Subtotal/Order Total should not be less than Minimum Cart Subtotal/Order Total', 'extra-fees-for-woocommerce' ) );
						$bool = false;
					}
				}
			}
		}

		return $bool;
	}
}

return new EFW_Order_Total_Fee_Tab();
