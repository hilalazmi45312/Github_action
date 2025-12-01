<?php

/**
 * Advance Tab.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'EFW_Advance_Tab' ) ) {
	return new EFW_Advance_Tab();
}

/**
 * EFW_Advance_Tab.
 */
class EFW_Advance_Tab extends EFW_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'advance';
		$this->label = esc_html__( 'Advanced', 'extra-fees-for-woocommerce' );

		parent::__construct();

		// Validate Settings for Before Save.
		add_filter( 'efw_validate_settings_before_save', array( $this, 'validate_before_save' ) );
		add_action( sanitize_key( 'woocommerce_settings_efw_import_export_settings_after' ), array( $this, 'get_imported_settings' ) );
		// Display Multiple level fee table.
		add_action( 'woocommerce_admin_field_efw_additional_fee_table', array( $this, 'additional_fee_table' ) );
		add_action( 'efw_' . $this->id . '_settings_after_save', array( $this, 'save_additional_fee_rules' ) );
	}

	/**
	 * Get Advance Settings section array.
	 */
	public function advance_section_array() {
		$section_fields = array();

		$section_fields[] = array(
			'type'      => 'efw_custom_fields',
			'efw_field' => 'section_start',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Combine Multiple Fees Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_combine_fee_settings',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Combine Multiple Fees', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'combine_fee' ),
			'desc'    => esc_html__( 'If enabled, the fee displayed in a separate component on the cart & checkout will be calculated & displayed in a single component.', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'    => esc_html__( 'Fee Text', 'extra-fees-for-woocommerce' ),
			'type'     => 'text',
			'default'  => 'Extra Charges',
			'id'       => $this->get_option_key( 'combine_fee_text' ),
			'class'    => 'show-if-combine-fee-enable',
			'desc'     => esc_html__( 'This is a common fee text displayed on the cart & checkout page.', 'extra-fees-for-woocommerce' ),
			'desc_tip' => true,
		);
		$section_fields[] = array(
			'title' => esc_html__( 'Fee Description', 'extra-fees-for-woocommerce' ),
			'type'  => 'textarea',
			'default'  => '',
			'id'    => $this->get_option_key( 'combine_fee_description' ),
			'class' => 'show-if-combine-fee-enable',
		);
		$section_fields[]         = array(
			'title'    => esc_html__( 'Tax Class', 'extra-fees-for-woocommerce' ),
			'type'     => 'select',
			'default'  => 'not-required',
			'id'       => $this->get_option_key( 'tax_class_for_combined_fee' ),
			'options'  => efw_get_fee_tax_classes(),
			'desc_tip' => true,
			'desc'     => esc_html__( 'Select the tax class for which the tax cost should be used to calculate the fee', 'extra-fees-for-woocommerce' ),
			'class'    => 'show-if-combine-fee-enable',
			'value'    => in_array( get_option( 'efw_advance_tax_class_for_combined_fee' ), array_keys( efw_get_fee_tax_classes() ) ) ? get_option( 'efw_advance_tax_class_for_combined_fee' ) : 'standard',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_combine_fee_settings',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Additional Fee Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_additional_fee_settings',
		);
		$section_fields[] = array(
			'title'   => esc_html__( 'Additional Fee', 'extra-fees-for-woocommerce' ),
			'type'    => 'checkbox',
			'default' => 'no',
			'id'      => $this->get_option_key( 'additional_fee' ),
			'desc'    => esc_html__( 'If enabled, you can charge an additional fee based on cart quantity.', 'extra-fees-for-woocommerce' ),
		);
		$section_fields[] = array(
			'title'    => esc_html__( 'Fee Text', 'extra-fees-for-woocommerce' ),
			'type'     => 'text',
			'default'  => 'Quantity Fee',
			'id'       => $this->get_option_key( 'additional_fee_text' ),
			'class'    => 'show-if-additional-fee-enable',
			'desc'     => esc_html__( 'This is a common fee text displayed on the cart & checkout page.', 'extra-fees-for-woocommerce' ),
			'desc_tip' => true,
		);
		$section_fields[]         = array(
			'title'    => esc_html__( 'Tax Class', 'extra-fees-for-woocommerce' ),
			'type'     => 'select',
			'default'  => 'not-required',
			'id'       => $this->get_option_key( 'tax_class_for_additional_fee' ),
			'options'  => efw_get_fee_tax_classes(),
			'desc_tip' => true,
			'desc'     => esc_html__( 'Select the tax class for which the tax cost should be used to calculate the fee.', 'extra-fees-for-woocommerce' ),
			'class'    => 'show-if-additional-fee-enable',
			'value'    => in_array( get_option( 'efw_advance_tax_class_for_additional_fee' ), array_keys( efw_get_fee_tax_classes() ) ) ? get_option( 'efw_advance_tax_class_for_additional_fee' ) : 'standard',
		);
		$section_fields[] = array(
			'title'    => esc_html__( 'Rule Priority', 'extra-fees-for-woocommerce' ),
			'type'     => 'select',
			'default'  => '1',
			'id'       => $this->get_option_key( 'rule_priority_for_additional_fee' ),
			'class'    => 'show-if-additional-fee-enable',
			'options'   => array(
				'1' => esc_html('First Matched Rule', 'extra-fees-for-woocommerce'),
				'2' => esc_html('Last Matched Rule', 'extra-fees-for-woocommerce'),
				'3' => esc_html('Minimum Fee Value', 'extra-fees-for-woocommerce'),
				'4' => esc_html('Maximum Fee Value', 'extra-fees-for-woocommerce'),
			),
		);
		$section_fields[] = array(
			'type'  => 'efw_additional_fee_table',
			'class' => 'show-if-additional-fee-enable',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_additional_fee_settings',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Import/Export Plugin Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_import_export_settings',
		);
		$section_fields[] = array(
			'title'     => esc_html__( 'Export Plugin Settings', 'extra-fees-for-woocommerce' ),
			'id'        => $this->get_option_key( 'export_plugin_settings' ),
			'type'      => 'efw_custom_fields',
			'class'     => 'efw-export-csv',
			'efw_field' => 'button',
			'default'   => 'Export',
		);
		$section_fields[] = array(
			'title'     => esc_html__( 'Import Plugin Settings', 'extra-fees-for-woocommerce' ),
			'id'        => $this->get_option_key( 'import_plugin_settings' ),
			'type'      => 'efw_custom_fields',
			'class'     => 'efw-import-settings',
			'button_title' => esc_html__( 'Import', 'extra-fees-for-woocommerce' ),
			'efw_field' => 'file',
			'desc'  => 'It does not work for Shipping Fee',
			'desc_tip' => true,
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_import_export_settings',
		);
		$section_fields[] = array(
			'type'  => 'title',
			'title' => esc_html__( 'Custom CSS Settings', 'extra-fees-for-woocommerce' ),
			'id'    => 'efw_custom_css_settings',
		);
		$section_fields[] = array(
			'title'    => esc_html__( 'Custom CSS', 'extra-fees-for-woocommerce' ),
			'type'     => 'textarea',
			'id'       => $this->get_option_key( 'custom_css' ),
			'default'  => '',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'efw_custom_css_settings',
		);
		$section_fields[] = array(
			'type'      => 'efw_custom_fields',
			'efw_field' => 'section_end',
		);

		/**
		 * Hook:efw_advance_settings.
		 *
		 * @since 5.9.1
		 */
		return apply_filters('efw_advance_settings', $section_fields);
	}

	/**
	 * Display the Additional fee table.
	 */
	public function additional_fee_table() {
		$additional_fee_rule_ids = efw_get_additional_fee_ids();

		ob_start();

		include_once EFW_PLUGIN_PATH . '/inc/admin/menu/views/additional-fee/additional-fee-new-rule.php';

		$new_rule = ob_get_clean();

		include_once EFW_PLUGIN_PATH . '/inc/admin/menu/views/additional-fee/html-additional-fee-table.php';
	}

	/**
	 * Save Global MLM Rules.
	 */
	public function save_additional_fee_rules() {
		if ( isset( $_REQUEST['efw_additional_fee_rule'] ) ) {

			$additional_fee_rules = wc_clean( $_REQUEST['efw_additional_fee_rule'] );

			$additional_fee_rule_ids = efw_get_additional_fee_ids();

			if ( isset( $additional_fee_rules['new'] ) && efw_check_is_array( $additional_fee_rules['new'] ) ) {
				foreach ( $additional_fee_rules['new'] as $additional_fee_new_rule ) {
					efw_create_new_additional_fee_rule( $additional_fee_new_rule );
				}
			}

			foreach ( $additional_fee_rule_ids as $additional_fee_rule_id ) {
				if ( isset( $additional_fee_rules[ $additional_fee_rule_id ] ) ) {
					efw_update_additional_fee_rule( $additional_fee_rule_id, $additional_fee_rules[ $additional_fee_rule_id ] );
				} else {
					efw_delete_post( $additional_fee_rule_id );
				}
			}
		}
	}

	public static function get_imported_settings() {
		delete_option( 'efw_imported_data', true );
		if ( isset( $_REQUEST['efw_advance_import_plugin_settings'] )) {
			check_admin_referer( 'efw_import_settings' , '_efw_import_nonce' ) ;
			$files = $_FILES;
			$file_error = isset( $files[ 'efw_advance_import_plugin_settings' ][ 'error' ] ) ? ( $files[ 'efw_advance_import_plugin_settings' ][ 'error' ] ) : 0 ;
			if ( $file_error > 0 ) {
				echo esc_html( 'Error: ' . $file_error . '<br>' ) ;
			} else {
				$mimes = array(
				'text/csv',
					'text/plain',
					'application/csv',
					'text/comma-separated-values',
					'application/excel',
					'application/vnd.ms-excel',
					'application/vnd.msexcel',
					'text/anytext',
					'application/octet-stream',
					'application/txt',
				) ;
				if ( isset( $files[ 'efw_advance_import_plugin_settings' ][ 'type' ] ) && in_array( $files[ 'efw_advance_import_plugin_settings' ][ 'type' ] , $mimes ) ) {
					$file_name = isset( $files[ 'efw_advance_import_plugin_settings' ][ 'tmp_name' ] ) ? $files[ 'efw_advance_import_plugin_settings' ][ 'tmp_name' ] : '' ;
					if ( $file_name ) {
						$handle = file_exists( $file_name ) ? fopen( $file_name , 'r' ) : '' ;
						if ( ! ( $handle ) ) {
							return ;
						}

						$imported_data = array();
						while ( ( $data = fgetcsv( $handle , 1000 , ',' ) ) !== false ) {
							$imported_data[] = array_filter( array( $data[ 0 ], $data[ 1 ] ) ) ;
						}
						update_option( 'efw_imported_data' , array_merge( array_filter( $imported_data ) ) ) ;
						fclose( $handle ) ;
					}
				}
			}
		}
	}

	/**
	 * Validate Settings Before Save.
	 *
	 * @since 1.0
	 * @param Boolean $bool Return Type.
	 * @return Boolean
	 */
	public static function validate_before_save( $bool ) {

		if ( isset( $_REQUEST['efw_advance_combine_fee'] ) ) {
			if ( isset( $_REQUEST['efw_advance_combine_fee_text'] ) && empty( $_REQUEST['efw_advance_combine_fee_text'] ) ) {
				EFW_Settings::add_error( esc_html__( 'Combined Fees : Fee Text for field cannot be empty', 'extra-fees-for-woocommerce' ) );
				$bool = false;
			}
		}

		if ( isset( $_REQUEST['efw_advance_additional_fee'] ) ) {
			if ( isset( $_REQUEST['efw_advance_additional_fee_text'] ) && empty( $_REQUEST['efw_advance_additional_fee_text'] ) ) {
				EFW_Settings::add_error( esc_html__( 'Additional Fee : Fee Text for field cannot be empty', 'extra-fees-for-woocommerce' ) );
				$bool = false;
			}
		}

		return $bool;
	}
}

return new EFW_Advance_Tab();
