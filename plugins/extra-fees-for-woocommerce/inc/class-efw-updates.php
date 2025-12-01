<?php
/**
 * Updates.
 *
 * @since 5.9.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Updates' ) ) {

	/**
	 * Class.
	 *
	 * @since 5.9.0
	 */
	class EFW_Updates {

		/**
		 * DB updates and callbacks that need to be run per version.
		 *
		 * @since 5.9.0
		 * @var array
		 */
		private static $updates = array(
			'update_590' => '5.9.0',
			'update_690' => '6.9.0',
		);

		/**
		 * Maybe run updates if the versions do not match.
		 *
		 * @since 5.9.0
		 * @return void
		 */
		public static function maybe_run() {
			// Return if it will not run admin.
			if ( ! is_admin() || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
				return;
			}

			delete_option( 'efw_updated_version' );

			if ( version_compare( get_option( 'efw_updated_version' ), EFW_VERSION, '<' ) ) {
				self::maybe_update_version();
			}
		}

		/**
		 * Update EFW DB version to current if unavailable
		 *
		 * @since 5.9.0
		 * @param int|string $version Version number.
		 * @return void
		 */
		public static function update_version( $version = null ) {
			update_option( 'efw_updated_version', ! is_numeric( $version ) ? EFW_VERSION : $version );
		}

		/**
		 * Check whether we need to show or run db updates during install.
		 *
		 * @since 5.9.0
		 * @return void
		 */
		private static function maybe_update_version() {
			if ( ! efw_check_is_array( self::$updates ) ) {
				self::update_version();
				return;
			}

			$needs_db_update = version_compare( get_option( 'efw_updated_version' ), max( array_values( self::$updates ) ), '<' );

			if ( ! $needs_db_update ) {
				self::update_version();
				return;
			}

			// Update EFW database.
			foreach ( self::$updates as $update => $updating_version ) {
				if ( is_callable( array( 'EFW_Updates', $update ) ) ) {
					call_user_func_array( array( 'EFW_Updates', $update ), array( $updating_version ) );
				}
			}
		}

		/**
		 * Update Version 5.9.0 data
		 *
		 * @since 5.9.0
		 * @param string $updating_version Version
		 * @return void
		 */
		public static function update_590( $updating_version ) {
			// Return if shipping settings data updated.
			if ( 'yes' === get_option( 'efw_shipping_settings_upgraded', 'no' ) ) {
				return;
			}

			$shipping_methods = efw_get_wc_available_shippings(true);
			if ( ! efw_check_is_array( $shipping_methods ) ) {
				return;
			}

			$shipping_zones = WC_Shipping_Zones::get_zones();
			foreach ( $shipping_methods as $shipping_id => $shipping ) {
				foreach ( $shipping_zones as $shipping_zone ) {
					foreach ($shipping_zone['shipping_methods'] as $shipping_method) {
	
						if ('no' == $shipping_method->enabled) {
							continue;
						}
					
						$method_title = $shipping_method->method_title;
						$shipping_method_key = $shipping_method->id . '_' . $shipping_method->instance_id;

						if ( $shipping_method->id != $shipping_id ) {
							continue;
						}
	
						update_option( "efw_enable_$shipping_method_key", get_option( "efw_enable_$shipping_id" ) );
						update_option( "efw_shipping_fee_text_$shipping_method_key", get_option( "efw_shipping_fee_text_$shipping_id" ) );
						update_option( "efw_shipping_fee_description_$shipping_method_key", get_option( "efw_shipping_fee_description_$shipping_id" ) );
						update_option( "efw_shipping_user_filter_type_$shipping_method_key", get_option( "efw_shipping_user_filter_type_$shipping_id" ) );
						update_option( "efw_shipping_include_users_$shipping_method_key", get_option( "efw_shipping_include_users_$shipping_id" ) );
						update_option( "efw_shipping_exclude_users_$shipping_method_key", get_option( "efw_shipping_exclude_users_$shipping_id" ) );
						update_option( "efw_shipping_include_userroles_$shipping_method_key", get_option( "efw_shipping_include_userroles_$shipping_id" ) );
						update_option( "efw_shipping_exclude_userroles_$shipping_method_key", get_option( "efw_shipping_exclude_userroles_$shipping_id" ) );
						update_option( "efw_shipping_product_filter_type_$shipping_method_key", get_option( "efw_shipping_product_filter_type_$shipping_id" ) );
						update_option( "efw_shipping_include_products_$shipping_method_key", get_option( "efw_shipping_include_products_$shipping_id" ) );
						update_option( "efw_shipping_exclude_products_$shipping_method_key", get_option( "efw_shipping_exclude_products_$shipping_id" ) );
						update_option( "efw_shipping_include_categories_$shipping_method_key", get_option( "efw_shipping_include_categories_$shipping_id" ) );
						update_option( "efw_shipping_exclude_categories_$shipping_method_key", get_option( "efw_shipping_exclude_categories_$shipping_id" ) );
						update_option( "efw_shipping_include_additional_products_$shipping_method_key", get_option( "efw_shipping_include_additional_products_$shipping_id" ) );
						update_option( "efw_shipping_exclude_additional_products_$shipping_method_key", get_option( "efw_shipping_exclude_additional_products_$shipping_id" ) );
						update_option( "efw_shipping_from_date_$shipping_method_key", get_option( "efw_shipping_from_date_$shipping_id" ) );
						update_option( "efw_shipping_to_date_$shipping_method_key", get_option( "efw_shipping_to_date_$shipping_id" ) );
						update_option( "efw_shipping_tax_class_$shipping_method_key", get_option( "efw_shipping_tax_class_$shipping_id" ) );
						update_option( "efw_shipping_fee_type_$shipping_method_key", get_option( "efw_shipping_fee_type_$shipping_id" ) );
						update_option( "efw_percentage_based_on_$shipping_method_key", get_option( "efw_percentage_based_on_$shipping_id" ) );
						update_option( "efw_shipping_fixed_value_$shipping_method_key", get_option( "efw_shipping_fixed_value_$shipping_id" ) );
						update_option( "efw_shipping_percentage_value_$shipping_method_key", get_option( "efw_shipping_percentage_value_$shipping_id" ) );
						update_option( "efw_shipping_minimum_fee_value_$shipping_method_key", get_option( "efw_shipping_minimum_fee_value_$shipping_id" ) );
						update_option( "efw_shipping_fee_minimum_restriction_value_$shipping_method_key", get_option( "efw_shipping_fee_minimum_restriction_value_$shipping_id" ) );
						update_option( "efw_shipping_fee_maximum_restriction_value_$shipping_method_key", get_option( "efw_shipping_fee_maximum_restriction_value_$shipping_id" ) );                    
					}
				}   
			}

			update_option( 'efw_shipping_settings_upgraded', 'yes' );

			self::update_version();
		}

		/**
		 * Update Version 6.9.0 data
		 *
		 * @since 6.9.0
		 * @return void
		 */
		public static function update_690() {
			// Return if reports data updated.
			if ( 'yes' === get_option( 'efw_report_updated' ) ) {
				return;
			}

			$reports = new EFW_Report_Update_Action();
			$reports->efw_update_report();

			update_option( 'efw_report_updated', 'yes' );

			self::update_version();
		}
	}
}
