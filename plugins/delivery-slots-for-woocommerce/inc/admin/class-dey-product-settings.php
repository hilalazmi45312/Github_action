<?php

/**
 * Handles the product Type.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'DEY_Product_Settings' ) ) {

	/**
	 * Class.
	 * */
	class DEY_Product_Settings {

		/**
		 * Class initialization.
		 * */
		public static function init() {

			// Add the custom meta box for delivery slot.
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_custom_meta_boxes' ), 40 );
			// Save the delivery slots data.
			add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_post' ), 10, 2 );
		}

		/**
		 * Add the custom meta boxes for delivery slots.
		 *
		 * @since 1.0.0
		 * @return void
		 * */
		public static function add_custom_meta_boxes() {
			// Return if the product page delivery slots is not enabled.
			if ( ! dey_is_valid_product_scheduler() ) {
				return;
			}

			// Delivery slot metabox.
			add_meta_box( 'dey_delivery_slots', __( 'Delivery and Pickup Scheduler', 'delivery-slots-for-woocommerce' ), array( __CLASS__, 'render_delivery_slots_content' ), 'product', 'normal', 'high' );
		}

		/**
		 * Render the delivery slots settings for product.
		 *
		 * @return void
		 * */
		public static function render_delivery_slots_content() {
			global $product_object, $thepostid;

			include 'menu/views/product/html-product-delivery-data-panels.php';
		}

		/**
		 * Return array of tabs.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		private static function get_delivery_slots_data_tabs() {
			/**
			 * This hook is used to alter the product delivery slots tabs.
			 *
			 * @since 1.0.0
			 */
			$tabs = apply_filters(
				'dey_product_delivery_slots_data_tabs',
				array(
					'delivery'         => array(
						'label'      => __( 'Delivery', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_delivery_slots_data_delivery',
						'class'      => array(),
						'priority'   => 10,
						'icon_class' => 'dashicons-admin-tools',
					),
					'pickup'           => array(
						'label'      => __( 'Pickup', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_delivery_slots_data_local_pickup',
						'class'      => array(),
						'priority'   => 20,
						'icon_class' => 'dashicons-admin-tools',
					),
					'pickup_locations' => array(
						'label'      => __( 'Pickup Locations', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_delivery_slots_data_pickup_locations',
						'class'      => array( '' ),
						'priority'   => 30,
						'icon_class' => 'dashicons-location-alt',
					),
					'time_slots'       => array(
						'label'      => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_delivery_slots_data_time_slots',
						'class'      => array( '' ),
						'priority'   => 40,
						'icon_class' => 'dashicons-calendar',
					),
					'holiday'          => array(
						'label'      => __( 'Holiday', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_delivery_slots_data_holiday',
						'class'      => array(),
						'priority'   => 50,
						'icon_class' => 'dashicons-editor-table',
					),
					'special_day'      => array(
						'label'      => __( 'Specific Day', 'delivery-slots-for-woocommerce' ),
						'target'     => 'dey_delivery_slots_data_special_day',
						'class'      => array( '' ),
						'priority'   => 60,
						'icon_class' => 'dashicons-admin-customizer',
					),
				)
			);

			// Sort tabs based on priority.
			uasort( $tabs, array( __CLASS__, 'delivery_slots_data_tabs_sort' ) );

			if ( ! dey_is_product_delivery() ) {
				unset( $tabs['delivery'] );
			}

			if ( ! dey_is_product_local_pickup() ) {
				unset( $tabs['pickup'] );
				unset( $tabs['pickup_locations'] );
			}

			return $tabs;
		}

		/**
		 * Callback to sort tabs on priority.
		 *
		 * @return bool
		 */
		private static function delivery_slots_data_tabs_sort( $a, $b ) {
			if ( ! isset( $a['priority'], $b['priority'] ) ) {
				return -1;
			}

			if ( $a['priority'] === $b['priority'] ) {
				return 0;
			}

			return $a['priority'] < $b['priority'] ? -1 : 1;
		}

		/**
		 * Product delivery slot data panel content.
		 *
		 * @since 1.0.0
		 * @global object $post Post.
		 * @global int $thepostid Post ID.
		 * @global object $product_object Product object.
		 * @return void
		 */
		public static function delivery_slots_data_panels() {
			global $post, $thepostid, $product_object;

			$tabs = array(
				'delivery',
				'local-pickup',
				'pickup-locations',
				'time-slots',
				'holiday',
				'special-day',
			);

			foreach ( $tabs as $tab ) {
				include 'menu/views/product/html-product-delivery-data-' . $tab . '.php';
			}
		}

		/**
		 * Save the delivery slots data.
		 *
		 * @since 1.0.0
		 * @param int    $post_id Post ID.
		 * @param object $post Post object.
		 * @throws Exception Error message.
		 */
		public static function save_post( $post_id, $post ) {
			// Return if the product page delivery slots is not enabled.
			if ( ! dey_is_valid_product_scheduler() ) {
				return;
			}

			try {
				$meta_data = array_merge(
					self::prepare_delivery_panel_data(),
					self::prepare_pickup_panel_data(),
					self::prepare_pickup_locations_panel_data(),
					self::prepare_time_slots_panel_data( $post_id ),
					self::prepare_holiday_panel_data(),
					self::prepare_special_day_panel_data( $post_id )
				);

				$meta_data['dey_delivery_slot_type']     = isset( $_REQUEST['dey_delivery_slot_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_slot_type'] ) ) : '';
				$meta_data['dey_product_scheduler_type'] = isset( $_REQUEST['dey_product_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_scheduler_type'] ) ) : get_post_meta( $post_id, 'dey_product_scheduler_type', true );

				// Update meta values in product postmeta.
				foreach ( $meta_data as $key => $value ) {
					update_post_meta( $post_id, $key, $value );
				}
			} catch ( Exception $ex ) {
				WC_Admin_Meta_Boxes::add_error( $ex->getMessage() );
			}
		}

		/**
		 * Prepare the delivery panel data.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function prepare_delivery_panel_data() {
			$processing_time = isset( $_REQUEST['dey_delivery_processing_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_processing_time'] ) ) : array(
				'number' => '',
				'unit'   => 'hours',
			);
			$business_days   = isset( $_REQUEST['dey_delivery_business_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_business_days'] ) ) : array();

			return array(
				'dey_delivery_slot_mode'                => isset( $_REQUEST['dey_delivery_slot_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_slot_mode'] ) ) : '',
				'dey_delivery_days_availablity'         => isset( $_REQUEST['dey_delivery_days_availablity'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_days_availablity'] ) ) : '',
				'dey_delivery_max_per_day'              => isset( $_REQUEST['dey_delivery_max_per_day'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_max_per_day'] ) ) : '',
				'dey_delivery_days'                     => isset( $_REQUEST['dey_delivery_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_days'] ) ) : array(),
				'dey_delivery_weekdays_prices'          => self::prepare_weekdays_prices( 'delivery' ),
				'dey_delivery_cutoff_time'              => isset( $_REQUEST['dey_delivery_cutoff_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_cutoff_time'] ) ) : '',
				'dey_delivery_next_day_cutoff_time'     => isset( $_REQUEST['dey_delivery_next_day_cutoff_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_next_day_cutoff_time'] ) ) : '',
				'dey_delivery_same_day_fee'             => isset( $_REQUEST['dey_delivery_same_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_same_day_fee'] ) ) : '',
				'dey_delivery_next_day_fee'             => isset( $_REQUEST['dey_delivery_next_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_next_day_fee'] ) ) : '',
				'dey_delivery_calender_mandatory_field' => isset( $_REQUEST['dey_delivery_calender_mandatory_field'] ) ? 'yes' : 'no',
				'dey_delivery_time_mode'                => isset( $_REQUEST['dey_delivery_time_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_time_mode'] ) ) : '',
				'dey_delivery_available_time_from'      => isset( $_REQUEST['dey_delivery_available_time_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_available_time_from'] ) ) : '',
				'dey_delivery_available_time_to'        => isset( $_REQUEST['dey_delivery_available_time_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_available_time_to'] ) ) : '',
				'dey_delivery_expected_date_from'       => isset( $_REQUEST['dey_delivery_expected_date_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_expected_date_from'] ) ) : '',
				'dey_delivery_expected_date_to'         => isset( $_REQUEST['dey_delivery_expected_date_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_expected_date_to'] ) ) : '',
				'dey_delivery_processing_time'          => $processing_time,
				'dey_delivery_enable_business_day'      => isset( $_REQUEST['dey_delivery_enable_business_day'] ) ? 'yes' : 'no',
				'dey_delivery_business_days'            => self::prepare_business_days( $business_days ),
				'dey_delivery_disable_total_payable'    => isset( $_REQUEST['dey_delivery_disable_total_payable'] ) ? 'yes' : 'no',
				'dey_delivery_fee_display_hide'         => isset( $_REQUEST['dey_delivery_fee_display_hide'] ) ? 'yes' : 'no',
			);
		}

		/**
		 * Prepare the pickup panel data.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public static function prepare_pickup_panel_data() {
			$processing_time = isset( $_REQUEST['dey_pickup_processing_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_processing_time'] ) ) : array(
				'number' => '',
				'unit'   => 'hours',
			);
			$business_days   = isset( $_REQUEST['dey_pickup_business_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_business_days'] ) ) : array();

			return array(
				'dey_pickup_days_availablity'         => isset( $_REQUEST['dey_product_pickup_days_availability'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_pickup_days_availability'] ) ) : '',
				'dey_pickup_weekdays_prices'          => self::prepare_weekdays_prices( 'pickup' ),
				'dey_pickup_max_per_day'              => isset( $_REQUEST['dey_pickup_max_per_day'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_max_per_day'] ) ) : '',
				'dey_pickup_location_mandatory_field' => isset( $_REQUEST['dey_pickup_location_mandatory_field'] ) ? 'yes' : 'no',
				'dey_pickup_calender_mandatory_field' => isset( $_REQUEST['dey_pickup_calender_mandatory_field'] ) ? 'yes' : 'no',
				'dey_pickup_available_time_from'      => isset( $_REQUEST['dey_pickup_available_time_from'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_available_time_from'] ) ) : '',
				'dey_pickup_available_time_to'        => isset( $_REQUEST['dey_pickup_available_time_to'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_available_time_to'] ) ) : '',
				'dey_pickup_time_mode'                => isset( $_REQUEST['dey_pickup_time_mode'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_time_mode'] ) ) : '',
				'dey_pickup_days'                     => isset( $_REQUEST['dey_pickup_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_days'] ) ) : array(),
				'dey_pickup_same_day_cutoff_time'     => isset( $_REQUEST['dey_pickup_same_day_cutoff_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_same_day_cutoff_time'] ) ) : '',
				'dey_pickup_next_day_cutoff_time'     => isset( $_REQUEST['dey_pickup_next_day_cutoff_time'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_next_day_cutoff_time'] ) ) : '',
				'dey_pickup_same_day_fee'             => isset( $_REQUEST['dey_pickup_same_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_same_day_fee'] ) ) : '',
				'dey_pickup_next_day_fee'             => isset( $_REQUEST['dey_pickup_next_day_fee'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_next_day_fee'] ) ) : '',
				'dey_pickup_processing_time'          => $processing_time,
				'dey_pickup_enable_business_day'      => isset( $_REQUEST['dey_pickup_enable_business_day'] ) ? 'yes' : 'no',
				'dey_pickup_business_days'            => self::prepare_business_days( $business_days ),
				'dey_pickup_disable_total_payable'    => isset( $_REQUEST['dey_pickup_disable_total_payable'] ) ? 'yes' : 'no',
				'dey_pickup_fee_display_hide'         => isset( $_REQUEST['dey_pickup_fee_display_hide'] ) ? 'yes' : 'no',
			);
		}

		/**
		 * Prepare the business days.
		 *
		 * @since 3.2.0
		 * @param array $business_days Business days.
		 * @return array
		 */
		public static function prepare_business_days( $business_days ) {
			$post_values = array();
			foreach ( $business_days as $business_day_key => $business_day ) {
				$business_day['enable']           = isset( $business_day['enable'] ) ? 'yes' : 'no';
				$post_values[ $business_day_key ] = $business_day;
			}

			return array_filter( $post_values );
		}

		/**
		 * Prepare the weekdays prices
		 *
		 * @since 1.0.0
		 * @param string $scheduler Product scheduler.
		 * @return array
		 */
		public static function prepare_weekdays_prices( $scheduler ) {
			if ( 'pickup' === $scheduler ) {
				$weekdays_prices = isset( $_REQUEST['dey_pickup_weekdays_prices'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_weekdays_prices'] ) ) : array();
			} else {
				$weekdays_prices = isset( $_REQUEST['dey_delivery_weekdays_prices'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_weekdays_prices'] ) ) : array();
			}

			if ( ! dey_check_is_array( $weekdays_prices ) ) {
				return array();
			}

			$formatted_weekdays_prices = array();
			foreach ( $weekdays_prices as $key => $value ) {
				$formatted_weekdays_prices[ $key ] = wc_format_decimal( $value );
			}

			return $formatted_weekdays_prices;
		}

		/**
		 * Prepare the pickup location panel data.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public static function prepare_pickup_locations_panel_data() {
			$pickup_locations            = isset( $_REQUEST['dey_pickup_locations'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_locations'] ) ) : array();
			$pickup_locations_panel_data = array( 'dey_pickup_location_selection_type' => isset( $_REQUEST['dey_pickup_location_selection_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_location_selection_type'] ) ) : '1' );
			if ( ! dey_check_is_array( $pickup_locations ) ) {
				return $pickup_locations_panel_data;
			}

			foreach ( $pickup_locations as $key => $pickup_location ) {
				if ( ! dey_check_is_array( $pickup_location ) ) {
					continue;
				}

				$pickup_locations_panel_data['dey_pickup_locations'][ $key ] = $pickup_location;
			}

			return $pickup_locations_panel_data;
		}

		/**
		 * Prepare the time slots panel data.
		 *
		 * @since 1.0.0
		 * @param int $product_id Product ID.
		 * @return array
		 */
		public static function prepare_time_slots_panel_data( $product_id ) {
			$time_slot_mandatory_field  = isset( $_REQUEST['dey_delivery_time_slot_mandatory_field'] ) ? 'yes' : 'no';
			$enable_as_soon_as_possible = isset( $_REQUEST['dey_delivery_enable_as_soon_as_possible'] ) ? 'yes' : 'no';
			$time_slot_max              = isset( $_REQUEST['dey_delivery_time_slot_max'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_time_slot_max'] ) ) : '';
			$hide_zero_price            = isset( $_REQUEST['dey_delivery_time_slot_hide_zero_price'] ) ? 'yes' : 'no';
			$first_available_time_slot  = isset( $_REQUEST['dey_delivery_first_available_time_slot'] ) ? 'yes' : 'no';
			$time_slots                 = isset( $_REQUEST['dey_delivery_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_time_slots'] ) ) : array();

			$existing_time_slots = array_filter( (array) get_post_meta( $product_id, 'dey_delivery_time_slots', true ) );

			$formatted_time_slots = array();
			if ( dey_check_is_array( $time_slots ) ) {
				foreach ( $time_slots as $key => $time_slot ) {

					if ( ! dey_check_is_array( $time_slot ) ) {
						continue;
					}

					$time_slot['week_days_enabled'] = ( isset( $time_slot['week_days_enabled'] ) ) ? 'yes' : 'no';
					$time_slot['week_days']         = ( isset( $time_slot['week_days'] ) ) ? $time_slot['week_days'] : array();
					$time_slot['price']             = ( isset( $time_slot['price'] ) ) ? wc_format_decimal( $time_slot['price'] ) : '';
					if ( isset( $existing_time_slots[ $key ] ) && isset( $existing_time_slots[ $key ]['used_order_count'] ) && dey_check_is_array( $existing_time_slots[ $key ]['used_order_count'] ) ) {
						$time_slot['used_order_count'] = $existing_time_slots[ $key ]['used_order_count'];
					} else {
						$time_slot['used_order_count'] = array();
					}

					$formatted_time_slots[ $key ] = $time_slot;
				}
			}

			$delivery_time_slots_data = array(
				'dey_delivery_time_slot_mandatory_field'  => $time_slot_mandatory_field,
				'dey_delivery_enable_as_soon_as_possible' => $enable_as_soon_as_possible,
				'dey_delivery_time_slot_max'              => $time_slot_max,
				'dey_delivery_time_slot_hide_zero_price'  => $hide_zero_price,
				'dey_delivery_first_available_time_slot'  => $first_available_time_slot,
				'dey_delivery_time_slots'                 => $formatted_time_slots,
			);

			$pickup_time_slots_data = array(
				'dey_pickup_time_slot_mandatory_field'  => $time_slot_mandatory_field,
				'dey_pickup_enable_as_soon_as_possible' => $enable_as_soon_as_possible,
				'dey_pickup_time_slot_max'              => $time_slot_max,
				'dey_pickup_time_slot_hide_zero_price'  => $hide_zero_price,
				'dey_pickup_first_available_time_slot'  => $first_available_time_slot,
				'dey_pickup_time_slots'                 => $formatted_time_slots,
			);

			return array_merge( $delivery_time_slots_data, $pickup_time_slots_data );
		}

		/**
		 * Prepare the holiday panel data.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function prepare_holiday_panel_data() {
			$holidays = isset( $_REQUEST['dey_delivery_holidays'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_holidays'] ) ) : array();

			$formatted_holidays = array();
			if ( dey_check_is_array( $holidays ) ) {
				foreach ( $holidays as $key => $holiday ) {
					if ( ! dey_check_is_array( $holiday ) ) {
						continue;
					}

					$holiday['recurring']       = isset( $holiday['recurring'] ) ? 'yes' : 'no';
					$formatted_holidays[ $key ] = $holiday;
				}
			}

			return array( 'dey_delivery_holidays' => $formatted_holidays );
		}

		/**
		 * Prepare the special day panel data.
		 *
		 * @since 1.0.0
		 * @param int $product_id Product ID.
		 * @return array
		 */
		public static function prepare_special_day_panel_data( $product_id ) {
			$special_days           = isset( $_REQUEST['dey_delivery_special_days'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_special_days'] ) ) : array();
			$existing_special_days  = array_filter( (array) get_post_meta( $product_id, 'dey_delivery_special_days', true ) );
			$formatted_special_days = array();
			if ( dey_check_is_array( $special_days ) ) {
				foreach ( $special_days as $key => $special_day ) {
					if ( ! dey_check_is_array( $special_day ) ) {
						continue;
					}

					$special_day['price'] = ( isset( $special_day['price'] ) ) ? wc_format_decimal( $special_day['price'] ) : '';
					if ( isset( $existing_special_days[ $key ] ) && isset( $existing_special_days[ $key ]['used_order_count'] ) && dey_check_is_array( $existing_special_days[ $key ]['used_order_count'] ) ) {
						$special_day['used_order_count'] = $existing_special_days[ $key ]['used_order_count'];
					} else {
						$special_day['used_order_count'] = array();
					}

					$formatted_special_days[ $key ] = $special_day;
				}
			}

			return array(
				'dey_delivery_special_days' => $formatted_special_days,
				'dey_pickup_special_days'   => $formatted_special_days,
			);
		}
	}

	DEY_Product_Settings::init();
}
