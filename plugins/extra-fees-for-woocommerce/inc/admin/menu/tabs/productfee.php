<?php

/**
 * Product Fee Tab.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'EFW_Product_Fee_Tab' ) ) {
	return new EFW_Product_Fee_Tab();
}

/**
 * EFW_Product_Fee_Tab.
 */
class EFW_Product_Fee_Tab extends EFW_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'productfee';
		$this->label = esc_html__( 'Product Fee', 'extra-fees-for-woocommerce' );

		parent::__construct();

		// Validate Settings for Before Save.
		add_filter( 'efw_validate_settings_before_save', array( $this, 'validate_before_save' ) );
		// Display Multiple level fee table.
		add_action( 'woocommerce_admin_field_efw_multiple_fee_settings', array( $this, 'multiple_fee_settings' ) );
		// Validate Settings Enabled.
		add_action( 'efw_productfee_before_settings_display', array( $this, 'validate_settings_enable' ) );
	}

	/**
	 * Validate Settings Enabled.
	 */
	public function validate_settings_enable() {
		if ('yes' != get_option('efw_productfee_enable')) {
			echo "<div class='error'><p>" . esc_html('Enable the global checkbox to charge the Product Fee.', 'extra-fees-for-woocommerce') . '</p></div>';
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

		// Save Multiple fees.
		$this->save_multiple_fee_settings();

		parent::save();
	}

	/**
	 * Save Order fees.
	 *
	 * @return void
	 */
	public function save_multiple_fee_settings() {
		if ( isset( $_REQUEST['efw_product_fees'] ) ) {

			$product_fees = wc_clean( wp_unslash( $_REQUEST['efw_product_fees'] ) );

			foreach ( $product_fees as $rule_id => $rules ) {
				if ( 'new' === $rule_id ) {

					foreach ( $rules as $rule ) {
						$error[ $rule_id ][] = efw_get_error_msg_for_product( $rule );

						if ( ! efw_check_is_array( array_filter( $error[ $rule_id ] ) ) ) {
							$rule['efw_settings_level'] = 'global';
							efw_create_new_fee_rule( $rule );
						}
					}
				} else {
					$error[ $rule_id ][] = efw_get_error_msg_for_product( $rules );

					if ( ! efw_check_is_array( array_filter( $error[ $rule_id ] ) ) ) {
						efw_update_fee_rule( $rule_id, $rules );
					}
				}
			}

			set_transient( 'efw_rule_errors', $error, 45 );
		}
	}

	/**
	 * Get Product Fee Settings section array.
	 */
	public function productfee_section_array() {
		$section_fields = array();

		$section_fields[] = array(
			'type'      => 'efw_custom_fields',
			'efw_field' => 'section_start',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'General Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_product_fee_settings',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Enable Product Fee', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'enable' ),
			'desc'    => esc_html__( 'When enabled, a fee will be added to the product\'s price', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'    => esc_html__( 'Fee Text', 'extra-fees-for-woocommerce' ),
			'type'     => 'text',
			'default'  => 'Product Fee',
			'id'       => $this->get_option_key( 'fee_text' ),
			'desc'     => esc_html__( ' This is a common fee text for products that will display on Shop Page, Product Page, Cart Page & Checkout Page.', 'extra-fees-for-woocommerce' ),
			'desc_tip' => true,
		);
		$section_fields[] = array(
			'title' => esc_html__( 'Fee Description', 'extra-fees-for-woocommerce' ),
			'type'  => 'textarea',
			'id'    => $this->get_option_key( 'description' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_product_fee_settings',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Display Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_display_settings',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Product Fee on Shop Page & Category Page', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => 'no',
			'id'      => $this->get_option_key( 'show_product_fee_shop' ),
			'options' => array(
				'no'           => esc_html__( "Don't Display the Product Fee", 'extra-fees-for-woocommerce' ),
				'yes'          => esc_html__( 'Show Product Fee + Total Payable Amount', 'extra-fees-for-woocommerce' ),
				'add-to-price' => esc_html__( 'Add Product Fee with Original Price', 'extra-fees-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Add to Cart Label in Shop Page', 'extra-fees-for-woocommerce' ),
			'type'    => 'text',
			'default' => 'View Final Price',
			'id'      => $this->get_option_key( 'add_to_cart_label' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Product Fee on Product Page', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => 'yes',
			'id'      => $this->get_option_key( 'show_product_fee_in_single_product' ),
			'options' => array(
				'yes'          => esc_html__( 'Show Product Fee + Total Payable Amount', 'extra-fees-for-woocommerce' ),
				'add-to-price' => esc_html__( 'Add Product Fee with Original Price', 'extra-fees-for-woocommerce' ),
				'no'           => esc_html__( 'Hide the Product Fee Info', 'extra-fees-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Product Fee info position in Product Page', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'info_position_in_product_page' ),
			'desc'    => esc_html__( 'If enabled, the product fee details will be displayed after the "Add to Cart" button on the product page.', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Product Fee display on the Cart Page', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'hide_product_fee_in_cart' ),
			'desc'    => esc_html__( 'By enabling this checkbox, the product fee info will be hidden on each cart line item. Note: It is applicable only for the line item, not when displaying the fee as a separate component', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_display_settings',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Restriction Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_product_fee_restriction_settings',
		);
		$section_fields[] = array(
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
		);
		$section_fields[] = array(
			'title'                   => esc_html__( 'Select User(s) to Include', 'extra-fees-for-woocommerce' ),
			'type'                    => 'efw_custom_fields',
			'efw_field'               => 'ajaxmultiselect',
			'list_type'               => 'customers',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_customers_search',
			'default'                 => array(),
			'allow_clear'             => false,
			'id'                      => $this->get_option_key( 'include_users' ),
		);
		$section_fields[] = array(
			'title'                   => esc_html__( 'Select User(s) to Exclude', 'extra-fees-for-woocommerce' ),
			'type'                    => 'efw_custom_fields',
			'efw_field'               => 'ajaxmultiselect',
			'list_type'               => 'customers',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_customers_search',
			'default'                 => array(),
			'allow_clear'             => false,
			'id'                      => $this->get_option_key( 'exclude_users' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select User Role(s) to Include', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_user_roles(),
			'id'      => $this->get_option_key( 'include_userrole' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select User Role(s) to Exclude', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_user_roles(),
			'id'      => $this->get_option_key( 'exclude_userrole' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Tax Setup', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'tax_setup' ),
			'desc'    => esc_html__( 'By enabling this checkbox, you can configure whether tax cost need to be charged/not on product fee.', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'    => esc_html__( 'Tax Class', 'extra-fees-for-woocommerce' ),
			'type'     => 'select',
			'default'  => 'standard',
			'id'       => $this->get_option_key( 'tax_class' ),
			'options'  => efw_get_fee_tax_classes(),
			'desc_tip' => true,
			'desc'     => esc_html__( 'Select the tax which should be used for calculating the fee', 'extra-fees-for-woocommerce' ),
			'class'    => 'show-if-tax-setup-enable',
			'value'    => in_array( get_option( 'efw_productfee_tax_class' ), array_keys( efw_get_fee_tax_classes() ) ) ? get_option( 'efw_productfee_tax_class' ) : 'standard',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Quantity Restriction', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'qty_restriction_enabled' ),
			'desc'    => __( 'By enabling this checkbox, you can charge the product fee only for one quantity of the product.', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Calculate Product Fee based on the discounted value', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'discount_based_calculation' ),
			'desc'    => __( 'If enabled, the product fee will be modified based on the discount value applied from any coupon.<br><b>Note:</b> It applies only when the <b>Fee Type</b> is selected as <b>Percentage of Product Price</b>.', 'extra-fees-for-woocommerce' ),
			'class'   => 'show-if-tax-or-quantity-restriction-enable',
		);
		$section_fields[] = array(
			'title'    => esc_html__( 'Fee Text', 'extra-fees-for-woocommerce' ),
			'type'     => 'text',
			'id'       => $this->get_option_key( 'overall_fee_text' ),
			'desc'     => esc_html__( 'This fee text will display in the Cart Total section by adding all the product fees.', 'extra-fees-for-woocommerce' ),
			'class'    => 'show-if-tax-or-quantity-restriction-enable',
			'default'  => 'Product Fees',
			'desc_tip' => true,
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_product_fee_restriction_settings',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Product Fee Global Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_product_fee_calculation_mode_settings',
		);
		$section_fields[] = array(
			'title'    => esc_html__( 'Fee Calculation Mode', 'extra-fees-for-woocommerce' ),
			'type'     => 'radio',
			'default'  => '1',
			'options'  => array(
				'1' => esc_html__( 'Quick Setup', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Advanced Setup', 'extra-fees-for-woocommerce' ),
			),
			'id'       => $this->get_option_key( 'fee_setup' ),
			'class'    => 'efw_productfee_fee_setup',
			'desc'     => esc_html__( 'Quick Setup: A common fee will be added to all the products sold on the site. Advanced Setup: A separate fee can be set for each product on the product configuration page.', 'extra-fees-for-woocommerce' ),
			'desc_tip' => true,
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Product Fee is Applicable for', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'apply_for' ),
			'options' => array(
				'1' => esc_html__( 'All Products', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Include Product(s)', 'extra-fees-for-woocommerce' ),
				'3' => esc_html__( 'Exclude Product(s)', 'extra-fees-for-woocommerce' ),
				'4' => esc_html__( 'Include Categories', 'extra-fees-for-woocommerce' ),
				'5' => esc_html__( 'Exclude Categories', 'extra-fees-for-woocommerce' ),
				'6' => esc_html__( 'Include Tag(s)', 'extra-fees-for-woocommerce' ),
				'7' => esc_html__( 'Exclude Tag(s)', 'extra-fees-for-woocommerce' ),
			),
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
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Categories to Include', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_wc_categories(),
			'id'      => $this->get_option_key( 'include_category' ),
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
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Categories to Exclude', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_wc_categories(),
			'id'      => $this->get_option_key( 'exclude_category' ),
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
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Tag(s) to Include', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_wc_tags(),
			'id'      => $this->get_option_key( 'include_tag' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Tag(s) to Exclude', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_wc_tags(),
			'id'      => $this->get_option_key( 'exclude_tag' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Fee Type', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'fee_type' ),
			'options' => array(
				'1' => esc_html__( 'Fixed Fee', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Percentage of Product Price', 'extra-fees-for-woocommerce' ),
			),
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
		);
		$section_fields[] = array(
			'title'             => esc_html__( 'Fee Value in %', 'extra-fees-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => 'any',
			),
			'id'                => $this->get_option_key( 'percent_value' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_product_fee_calculation_mode_settings',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Product Fee Bulk Update Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_product_fee_bulk_settings',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Product/Category Selection', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'product_filters' ),
			'options' => array(
				'1' => esc_html__( 'All Product(s)', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Include Product(s)', 'extra-fees-for-woocommerce' ),
				'3' => esc_html__( 'Exclude Product(s)', 'extra-fees-for-woocommerce' ),
				'4' => esc_html__( 'Include Categories', 'extra-fees-for-woocommerce' ),
				'5' => esc_html__( 'Exclude Categories', 'extra-fees-for-woocommerce' ),
				'6' => esc_html__( 'Include Tag(s)', 'extra-fees-for-woocommerce' ),
				'7' => esc_html__( 'Exclude Tag(s)', 'extra-fees-for-woocommerce' ),
			),
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
			'id'                      => $this->get_option_key( 'update_inc_products' ),
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
			'id'                      => $this->get_option_key( 'update_exc_products' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Categories to Include', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_wc_categories(),
			'id'      => $this->get_option_key( 'update_inc_category' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Categories to Exclude', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_wc_categories(),
			'id'      => $this->get_option_key( 'update_exc_category' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Tag(s) to Include', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_wc_tags(),
			'id'      => $this->get_option_key( 'update_inc_tag' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Select Tag(s) to Exclude', 'extra-fees-for-woocommerce' ),
			'type'    => 'multiselect',
			'class'   => 'efw_select2',
			'default' => '',
			'options' => efw_get_wc_tags(),
			'id'      => $this->get_option_key( 'update_exc_tag' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Enable Product Fee', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'bulk_enable' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Fee should apply from', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'bulk_fee_from' ),
			'options' => array(
				'2' => esc_html__( 'Category Level Settings', 'extra-fees-for-woocommerce' ),
				'1' => esc_html__( 'Product Level Settings', 'extra-fees-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Fee Text is Obtained from', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'bulk_text_from' ),
			'options' => array(
				'1' => esc_html__( 'Global Level Settings', 'extra-fees-for-woocommerce' ),
				'3' => esc_html__( 'Category Level Settings', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Product Level Settings', 'extra-fees-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Fee Text', 'extra-fees-for-woocommerce' ),
			'type'    => 'text',
			'default' => '',
			'id'      => $this->get_option_key( 'bulk_fee_text' ),
		);
		$section_fields[] = array(
			'title' => esc_html__( 'Fee Description', 'extra-fees-for-woocommerce' ),
			'type'  => 'textarea',
			'id'    => $this->get_option_key( 'bulk_fee_description' ),
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Fee Type', 'extra-fees-for-woocommerce' ),
			'type'    => 'select',
			'default' => '1',
			'id'      => $this->get_option_key( 'bulk_fee_type' ),
			'options' => array(
				'1' => esc_html__( 'Fixed Fee', 'extra-fees-for-woocommerce' ),
				'2' => esc_html__( 'Percentage of Product Price', 'extra-fees-for-woocommerce' ),
			),
		);
		$section_fields[] = array(
			'title'             => esc_html__( 'Fixed Fee Value', 'extra-fees-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => 'any',
			),
			'id'                => $this->get_option_key( 'bulk_fixed_value' ),
		);
		$section_fields[] = array(
			'title'             => esc_html__( 'Fee Value in %', 'extra-fees-for-woocommerce' ),
			'type'              => 'number',
			'default'           => '',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => 'any',
			),
			'id'                => $this->get_option_key( 'bulk_percent_value' ),
		);
		$section_fields[] = array(
			'title'     => esc_html__( 'Save & Update', 'extra-fees-for-woocommerce' ),
			'type'      => 'efw_custom_fields',
			'efw_field' => 'button',
			'id'        => $this->get_option_key( 'bulk_update' ),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_product_fee_bulk_settings',
		);
		$section_fields[] = array(
			'type'      => 'efw_custom_fields',
			'efw_field' => 'section_end',
		);
		$section_fields[] = array(
			'type'  => 'efw_multiple_fee_settings',
		);

		/**
		 * Hook:efw_product_fee_settings.
		 *
		 * @since 5.1.2
		 */
		return apply_filters('efw_product_fee_settings', $section_fields);
	}

	/**
	 * Display the Multiple fee settings.
	 */
	public function multiple_fee_settings() {
		$args = array(
			'meta_key' => 'efw_settings_level',
			'meta_value' => 'global',
		);

		$rule_ids = efw_get_fee_rule_ids( $args );

		include_once EFW_PLUGIN_PATH . '/inc/admin/menu/views/global/global-product-fee-settings.php';
	}

	/**
	 * Validate the Fields before Save.
	 */
	public static function validate_before_save( $bool ) {
		if ( isset( $_REQUEST['efw_productfee_enable'] ) && isset( $_REQUEST['efw_productfee_fee_setup'] ) ) {

			if ( isset( $_REQUEST['efw_productfee_fee_text'] ) && empty( $_REQUEST['efw_productfee_fee_text'] ) ) {
				EFW_Settings::add_error( esc_html__( 'Fee Text field cannot be empty', 'extra-fees-for-woocommerce' ) );
				$bool = false;
			}

			if ( '1' == $_REQUEST['efw_productfee_fee_setup'] ) {
				if ( isset( $_REQUEST['efw_productfee_fee_type'] ) ) {
					if ( '1' == $_REQUEST['efw_productfee_fee_type'] ) {
						if ( isset( $_REQUEST['efw_productfee_fixed_value'] ) && '' === $_REQUEST['efw_productfee_fixed_value'] ) {
							EFW_Settings::add_error( esc_html__( 'Fixed Fee Value field cannot be empty', 'extra-fees-for-woocommerce' ) );
							$bool = false;
						}
					}

					if ( '2' == $_REQUEST['efw_productfee_fee_type'] ) {
						if ( isset( $_REQUEST['efw_productfee_percent_value'] ) && empty( $_REQUEST['efw_productfee_percent_value'] ) ) {
							EFW_Settings::add_error( esc_html__( 'Fee Value in Percent field cannot be empty', 'extra-fees-for-woocommerce' ) );
							$bool = false;
						}
					}
				}
			}
		}

		return $bool;
	}
}

return new EFW_Product_Fee_Tab();
