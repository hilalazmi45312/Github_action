<?php

/**
 * Handles Exports.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_CSV_Batch_Exporter', false ) ) {
	include_once WC_ABSPATH . 'includes/export/abstract-wc-csv-batch-exporter.php';
}

if (!class_exists('EFW_Export_CSV')) {

	/**
	 * EFW_Export_CSV.
	 */
	class EFW_Export_CSV extends WC_CSV_Batch_Exporter {

		/**
		 * Export method.
		 *
		 * @var string
		 */
		protected $exporter_method;

		/**
		 * Settings option key.
		 *
		 * @var string
		 */
		protected $efw_option_key;

		/**
		 * Settings option value.
		 *
		 * @var string
		 */
		protected $efw_option_value;


		/**
		 * Get filename.
		 * 
		 * @return string
		 */
		public function get_filename() {

			$filename = 'efw-export-settings';

			/**
			 * This hook is used to alter the filename of export CSV.
			 * 
			 * @since 3.9.0
			 */
			return sanitize_file_name(apply_filters('efw_export_settings_option_filename', $filename));
		}

		/**
		 * Set section to export from product.
		 *
		 */
		public function set_section( $section ) {
			$this->section = $section;
		}

		/**
		 * Set option key.
		 * 
		 * @param string $type
		 */
		public function set_option_key( $type ) {
			$this->efw_option_key = $type;
		}

		/**
		 * Set option value.
		 * 
		 * @param string $type
		 */
		public function set_option_value( $type ) {
			$this->efw_option_value = $type;
		}

		/**
		 * Get section to export.
		 *
		 */
		public function get_section() {
			return $this->section;
		}

		/**
		 * Get option key
		 * 
		 * @return string
		 */
		public function get_option_key() {
			return $this->efw_option_key;
		}

		/**
		 * Get option value
		 * 
		 * @return string
		 */
		public function get_option_value() {
			return $this->efw_option_value;
		}

		/**
		 * Return default columns.
		 * 
		 * @return array
		 */
		public function get_default_column_names() {

			$headings = array(
				'efw_option_key' => __('Key Name', 'extra-fees-for-woocommerce'),
				'efw_option_value' => __('Value', 'extra-fees-for-woocommerce'),
			);

			/**
			 * This hook is used to alter the export column heading.
			 * 
			 * @since 3.9.0
			 */
			return apply_filters('efw_export_column_heading', $headings );
		}

		/**
		 * Prepare data that will be exported.
		 * 
		 * @return void.
		 */
		public function prepare_data_to_export() {

			// Prepare column names.
			$this->column_names = $this->get_default_column_names();

			$total_rows = 0;

			if ( ! class_exists( 'EFW_Settings' ) ) {
				include_once EFW_PLUGIN_PATH . '/inc/admin/menu/class-efw-settings.php' ;
			}

			$settings = EFW_Settings::get_settings_pages() ;

			foreach ( $settings as $section_key => $setting ) {
				$settings_array = $setting->get_settings( $section_key ) ;
				if ( ! efw_check_is_array( $settings_array ) ) {
					continue ;
				}

				foreach ( $settings_array as $value ) {
					$this->row_data[] = self::generate_row_data($value);
				}
			}

			$gateway_values = $this->get_gateway_keys();
			foreach ( $gateway_values as $gateway_value ) {
				foreach ( $gateway_value as $gateway_keys ) {
					$this->row_data[] = self::generate_row_data($gateway_keys);
				}
			}

			if ((float) WC()->version > (float) '6.2.0') {
				// Total rows count.
				$this->total_rows = count($this->row_data);
			} else {
				// Per limit rows count.
				$this->total_rows = $total_rows;
			}
		}

		/**
		 * Get lottery ticket data.
		 * 
		 * @return array
		 */
		protected function generate_row_data( $value ) {
			$row = array(
				'efw_option_key' => $value[ 'id' ],
				'efw_option_value' => get_option( $value[ 'id' ], $value[ 'default' ] ),
			);

			/**
			 * This hook is used to alter the export row data.
			 * 
			 * @since 3.9.0
			 */
			return apply_filters('efw_export_row_data', $row);
		}

		/**
		 * Prepare gateway keys that will be exported.
		 * 
		 * @return array.
		 */
		public function get_gateway_keys() {
			$gateway_values = array();
			$available_gateways = efw_get_wc_available_gateways( true );
			foreach ( $available_gateways as $gateway_id => $gateway_title ) {
				$gateway_values[$gateway_id] = array(
					array(
						'id' => 'efw_enable_fee_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_user_filter_type_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_include_user_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_exclude_user_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_include_userrole_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_exclude_userrole_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_product_filter_type_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_include_product_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_exclude_product_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_include_category_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_exclude_category_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_include_countries_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_from_date_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_to_date_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_fee_text_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_tax_class_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_fee_type_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_fixed_value_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_percentage_type_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_min_fee_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_max_fee_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_min_subtotal_for_' . $gateway_id,
						'default' => '',
					),
					array(
						'id' => 'efw_max_subtotal_for_' . $gateway_id,
						'default' => '',
					),
				);
			}

			return $gateway_values;
		}

		/**
		 * Prepare shipping keys that will be exported.
		 * 
		 * @return array.
		 */
		public function get_shipping_keys() {
			$shipping_values = array();
			$shipping_zones = WC_Shipping_Zones::get_zones();
			foreach ( $shipping_zones as $shipping_zone ) {
				foreach ($shipping_zone['shipping_methods'] as $shipping_method) {
					$shipping_method_key = $shipping_method->id . '_' . $shipping_method->instance_id;
					$shipping_values[$shipping_method->id] = array(
						array(
							'id' => 'efw_enable_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_fee_text_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_fee_description_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_user_filter_type_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_include_users_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_exclude_users_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_include_userroles_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_exclude_userroles_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_product_filter_type_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_include_products_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_exclude_products_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_include_categories_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_exclude_categories_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_from_date_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_to_date_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_tax_class_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_fee_type_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_percentage_based_on_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_fixed_value_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_percentage_value_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_minimum_fee_value_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_fee_minimum_restriction_value_' . $shipping_method_key,
							'default' => '',
						),
						array(
							'id' => 'efw_shipping_fee_maximum_restriction_value_' . $shipping_method_key,
							'default' => '',
						),
					);
				}
			}

			return $shipping_values;
		}
	}

}
