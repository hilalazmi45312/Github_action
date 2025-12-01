<?php
/**
 * Updates.
 *
 * @since 3.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Updates' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.0.0
	 */
	class DEY_Updates {

		/**
		 * DB updates and callbacks that need to be run per version.
		 *
		 * @since 3.0.0
		 * @var array
		 */
		private static $updates = array(
			'update_300' => '3.0.0',
			'update_320' => '3.2.0',
			'update_350' => '3.5.0',
			'update_400' => '4.0.0',
		);

		/**
		 * Maybe run updates if the versions do not match.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public static function maybe_run() {
			// Return if it will not run admin.
			if ( ! is_admin() || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
				return;
			}

			if ( dey_check_is_array( self::$updates ) && version_compare( get_option( 'dey_updated_version' ), max( array_values( self::$updates ) ), '<' ) ) {
				self::maybe_update_version();
			}
		}

		/**
		 * Update DEY DB version to current if unavailable
		 *
		 * @since 3.0.0
		 * @param int|string $version Version number.
		 * @return void
		 */
		public static function update_version( $version = null ) {
			update_option( 'dey_updated_version', ! is_numeric( $version ) ? DEY_VERSION : $version );
		}

		/**
		 * Check whether we need to show or run db updates during install.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		private static function maybe_update_version() {
			if ( ! dey_check_is_array( self::$updates ) ) {
				self::update_version();
				return;
			}

			$needs_db_update = version_compare( get_option( 'dey_updated_version' ), max( array_values( self::$updates ) ), '<' );
			if ( ! $needs_db_update ) {
				self::update_version();
				return;
			}

			// Update DEY database.
			foreach ( self::$updates as $update => $updating_version ) {
				if ( is_callable( array( 'DEY_Updates', $update ) ) ) {
					call_user_func_array( array( 'DEY_Updates', $update ), array( $updating_version ) );
				}
			}

			self::update_version();
		}

		/**
		 * Update Version 3.0.0 data
		 *
		 * @since 3.0.0
		 * @param string $updating_version Version
		 * @return void
		 */
		public static function update_300( $updating_version ) {
			// Return if order local pickup data updated.
			if ( 'yes' === get_option( 'dey_order_local_pickup_settings_upgraded', 'no' ) ) {
				return;
			}

			$local_pickup_upgrade_keys = array(
				'dey_order_delivery_enable'           => 'dey_local_pickup_enable',
				'dey_order_delivery_days_availablity' => 'dey_local_pickup_days_availablity',
				'dey_order_delivery_calender_mandatory_field' => 'dey_local_pickup_calender_mandatory_field',
				'dey_order_delivery_days'             => 'dey_local_pickup_days',
				'dey_order_delivery_max_per_day'      => 'dey_local_pickup_max_per_day',
				'dey_order_delivery_time_mode'        => 'dey_local_pickup_time_mode',
				'dey_order_delivery_time_slot_mandatory_field' => 'dey_local_pickup_time_slot_mandatory_field',
				'dey_order_delivery_enable_as_soon_as_possible' => 'dey_local_pickup_enable_as_soon_as_possible',
				'dey_order_delivery_display_first_available_time_slot' => 'dey_local_pickup_display_first_available_time_slot',
				'dey_order_delivery_time_slot_hide_zero_price' => 'dey_local_pickup_time_slot_hide_zero_price',
				'dey_order_delivery_time_slot_max'    => 'dey_local_pickup_time_slot_max',
				'dey_order_delivery_processing_time'  => 'dey_local_pickup_processing_time',
				'dey_order_delivery_cutoff_time'      => 'dey_local_pickup_cutoff_time',
				'dey_order_delivery_business_days'    => 'dey_local_pickup_business_days',
				'dey_order_delivery_weekdays_prices'  => 'dey_local_pickup_weekdays_prices',
				'dey_order_delivery_calculate_tax'    => 'dey_local_pickup_calculate_tax',
				'dey_order_delivery_display_position' => 'dey_advanced_display_position',
				'dey_order_delivery_display_position_priority' => 'dey_advanced_display_position_priority',
			);

			foreach ( $local_pickup_upgrade_keys as $order_delivery_key => $local_pickup_key ) {
				update_option( $local_pickup_key, get_option( $order_delivery_key ) );
			}

			update_option( 'dey_order_local_pickup_settings_upgraded', 'yes' );
		}

		/**
		 * Update Version 3.1.3 business day
		 *
		 * @since 3.2.0
		 * @param string $updating_version Version.
		 */
		public static function update_320( $updating_version ) {
			// Return if order local pickup data updated.
			if ( 'yes' === get_option( 'dey_business_day_settings_upgraded', 'no' ) ) {
				return;
			}

			// Update Business day for Order Delivery.
			self::update_business_day_settings( 'order_delivery' );
			// Update Business day for Local Pickup.
			self::update_business_day_settings( 'local_pickup' );
			// Update Business day for Product Delivery.
			self::update_business_day_settings_for_product_delivery();

			update_option( 'dey_business_day_settings_upgraded', 'yes' );
		}

		/**
		 * Update Order Delivery/Local Pickup business day settings for Version 3.2.0
		 *
		 * @since 3.2.0
		 * @param string $type Type.
		 */
		public static function update_business_day_settings( $type ) {
			if ( false === get_option( 'dey_' . $type . '_enable_business_day' ) ) {
				return;
			}

			$selected_business_days = array();
			$value_to_update        = array(
				'enable'       => 'no',
				'opening_time' => '00:00',
				'closing_time' => '23:59',
			);

			if ( 'yes' === get_option( 'dey_' . $type . '_enable_business_day' ) ) {
				$business_days = get_option( 'dey_' . $type . '_business_days' );
				if ( dey_check_is_array( $business_days ) ) {
					foreach ( dey_weekdays() as $weekday_key => $weekday ) {
						$value_to_update['enable']              = in_array( $weekday_key, $business_days ) ? 'yes' : 'no';
						$selected_business_days[ $weekday_key ] = $value_to_update;
					}

					update_option( 'dey_' . $type . '_business_days', $selected_business_days );
				} else {
					update_option( 'dey_' . $type . '_business_days', dey_business_day_default_option() );
				}
			} else {
				foreach ( dey_weekdays() as $weekday_key => $weekday ) {
					$value_to_update['enable']              = 'yes';
					$selected_business_days[ $weekday_key ] = $value_to_update;
				}

				update_option( 'dey_' . $type . '_business_days', $selected_business_days );
			}
		}

		/**
		 * Update product delivery business day settings for Version 3.2.0
		 *
		 * @since 3.2.0
		 */
		public static function update_business_day_settings_for_product_delivery() {
			$product_ids = self::get_product_ids();
			if ( ! dey_check_is_array( $product_ids ) ) {
				return;
			}

			$value_to_update = array(
				'enable'       => 'no',
				'opening_time' => '00:00',
				'closing_time' => '23:59',
			);

			$selected_business_days = array();
			foreach ( $product_ids as $product_id ) {
				if ( 'yes' === get_post_meta( $product_id, 'dey_delivery_enable_business_day', true ) ) {
					$business_days = get_post_meta( $product_id, 'dey_delivery_business_days', true );
					if ( dey_check_is_array( $business_days ) ) {
						foreach ( dey_weekdays() as $weekday_key => $weekday ) {
							$value_to_update['enable']              = in_array( $weekday_key, $business_days ) ? 'yes' : 'no';
							$selected_business_days[ $weekday_key ] = $value_to_update;
						}
						update_post_meta( $product_id, 'dey_delivery_business_days', $selected_business_days );
					} else {
						update_post_meta( $product_id, 'dey_delivery_business_days', dey_business_day_default_option() );
					}
				} else {
					foreach ( dey_weekdays() as $weekday_key => $weekday ) {
						$value_to_update['enable']              = 'yes';
						$selected_business_days[ $weekday_key ] = $value_to_update;
					}

					update_post_meta( $product_id, 'dey_delivery_business_days', $selected_business_days );
				}
			}
		}

		/**
		 * Get Product Ids for Product Delivery.
		 *
		 * @since 3.2.0
		 */
		public static function get_product_ids() {
			return get_posts(
				array(
					'post_type'   => 'product',
					'post_status' => 'publish',
					'fields'      => 'ids',
					'numberposts' => '-1',
					'meta_query'  => array(
						'relation' => 'AND',
						array(
							'key'     => 'dey_delivery_slot_type',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => 'dey_delivery_slot_type',
							'value'   => '2',
							'compare' => '==',
						),
						array(
							'key'     => 'dey_delivery_enable_business_day',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => 'dey_delivery_business_days',
							'compare' => 'EXISTS',
						),
					),
				)
			);
		}

		/**
		 * Update version 3.5.0 product local pickup.
		 *
		 * @since 3.5.0
		 * @param string $updating_version Version.
		 */
		public static function update_350( $updating_version ) {
			$query_args = array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'fields'      => 'ids',
				'numberposts' => '-1',
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => 'dey_delivery_slot_type',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'dey_delivery_slot_type',
						'value'   => '2',
						'compare' => '==',
					),
				),
			);

			$product_ids = get_posts( $query_args );
			if ( ! dey_check_is_array( $product_ids ) ) {
				return;
			}

			foreach ( $product_ids as $product_id ) {
				// Check the product scheduler type is empty.
				if ( empty( get_post_meta( $product_id, 'dey_product_scheduler_type', true ) ) ) {
					update_post_meta( $product_id, 'dey_product_scheduler_type', '1' );
				}
			}
		}

		/**
		 * Update version 4.0.0 Scheduler rule.
		 *
		 * @since 4.0.0
		 * @param string $updating_version Version.
		 */
		public static function update_400( $updating_version ) {
			// Return if order delivery rule data updated.
			if ( 'yes' === get_option( 'dey_default_scheduler_rule_updated', 'no' ) ) {
				return;
			}

			// Return if the order schedulers data is empty.
			if ( false === get_option( 'dey_order_delivery_enable' ) && false === get_option( 'dey_local_pickup_enable' ) ) {
				return;
			}

			$scheduler_rule_type = 3;
			if ( 'yes' === get_option( 'dey_order_delivery_enable' ) && 'yes' !== get_option( 'dey_local_pickup_enable' ) ) {
				$scheduler_rule_type = 1;
			} elseif ( 'yes' !== get_option( 'dey_order_delivery_enable' ) && 'yes' === get_option( 'dey_local_pickup_enable' ) ) {
				$scheduler_rule_type = 2;
			}

			$scheduler_rule_data = array( 'dey_scheduler_type' => $scheduler_rule_type );

			foreach ( dey_get_scheduler_rule_update_data_keys() as $scheduler_rule_key => $option_key ) {
				$scheduler_rule_data[ $scheduler_rule_key ] = get_option( $option_key );
			}

			dey_create_new_scheduler_rule(
				wp_parse_args(
					$scheduler_rule_data,
					dey_get_scheduler_rule_default_data()
				),
				array(
					'post_title'  => __( 'Order Scheduler', 'delivery-slots-for-woocommerce' ),
					'post_status' => ( 'yes' === get_option( 'dey_order_delivery_enable' ) || 'yes' === get_option( 'dey_local_pickup_enable' ) ) ? 'dey_active' : 'dey_inactive',
				)
			);

			update_option( 'dey_default_scheduler_rule_updated', 'yes' );
		}
	}
}
