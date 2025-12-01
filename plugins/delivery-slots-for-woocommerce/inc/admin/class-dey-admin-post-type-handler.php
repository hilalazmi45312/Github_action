<?php

/**
 * Admin Post Type Handler.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Admin_Post_Type_Handler' ) ) {

	/**
	 * Class.
	 */
	class DEY_Admin_Post_Type_Handler {

		/**
		 * Class initialization.
		 */
		public static function init() {
			// Load correct list table classes for current screen.
			add_action( 'current_screen', array( __CLASS__, 'setup_screen' ) );
			add_action( 'check_ajax_referer', array( __CLASS__, 'setup_screen' ) );

			// Display the order delivery details.
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_order_delivery_details' ) );
			// Display the order preview delivery details.
			add_filter( 'woocommerce_admin_order_preview_get_order_details', array( __CLASS__, 'display_order_preview_delivery_details' ), 10, 2 );

			// Export the CSV.
			add_action( 'admin_init', array( __CLASS__, 'export_csv' ) );
		}

		/**
		 * Looks at the current screen and loads the correct list table handler.
		 *
		 * @return void
		 */
		public static function setup_screen() {
			global $dey_list_table;

			$screen_id = false;

			if ( ! empty( $_REQUEST['screen'] ) ) {
				$screen_id = wc_clean( wp_unslash( $_REQUEST['screen'] ) );
			} elseif ( function_exists( 'get_current_screen' ) ) {
				$screen    = get_current_screen();
				$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
			}

			$screen_id = str_replace( 'edit-', '', $screen_id );

			switch ( $screen_id ) {
				case 'dey_order_delivery':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-order-delivery-list-table.php';

					$dey_list_table = new DEY_Order_Delivery_List_Table();
					break;

				case 'dey_order_pickup':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-order-local-pickup-list-table.php';

					$dey_list_table = new DEY_Order_Local_Pickup_List_Table();
					break;

				case 'dey_product_delivery':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-product-delivery-list-table.php';

					$dey_list_table = new DEY_Product_Delivery_List_Table();
					break;

				case 'dey_product_pickup':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-product-local-pickup-list-table.php';

					$dey_list_table = new DEY_Product_Local_Pickup_List_Table();
					break;

				case 'dey_order_tip':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-order-tip-list-table.php';

					$dey_list_table = new DEY_Order_Tip_List_Table();
					break;

				case 'dey_time_slots':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-time-slots-list-table.php';

					$dey_list_table = new DEY_Time_Slots_List_Table();
					break;

				case 'dey_holiday':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-holiday-list-table.php';

					$dey_list_table = new DEY_Holiday_List_Table();
					break;

				case 'dey_special_days':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-special-day-list-table.php';

					$dey_list_table = new DEY_Special_Day_List_Table();
					break;

				case 'dey_pickup_locations':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-pickup-locations-list-table.php';

					$dey_list_table = new DEY_Pickup_Locations_List_Table();
					break;

				case 'dey_scheduler_rule':
					include_once DEY_PLUGIN_PATH . '/inc/admin/menu/post-tables/class-dey-scheduler-rules-list-table.php';

					$dey_list_table = new DEY_Scheduler_Rule_List_Table();
					break;
			}

			// Ensure the table handler is only loaded once. Prevents multiple loads if a plugin calls check_ajax_referer many times.
			remove_action( 'current_screen', array( __CLASS__, 'setup_screen' ) );
			remove_action( 'check_ajax_referer', array( __CLASS__, 'setup_screen' ) );
		}

		/**
		 * Display the order delivery details.
		 *
		 * @return void
		 */
		public static function display_order_delivery_details( $order ) {
			// Return if the order is not object.
			if ( ! is_object( $order ) ) {
				return;
			}

			// Return if the order delivery details not exists in order.
			$order_delivery_details = dey_get_order_delivery_details( $order );
			if ( ! dey_check_is_array( $order_delivery_details ) ) {
				return;
			}

			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/html-order-delivery-details.php';
		}

		/**
		 * Display the order preview delivery details.
		 *
		 * @return array
		 */
		public static function display_order_preview_delivery_details( $data, $order ) {
			// Return if the order is not object.
			if ( ! is_object( $order ) ) {
				return $data;
			}

			// Return if the data not having the payment via key.
			if ( ! isset( $data['payment_via'] ) ) {
				return $data;
			}

			// Return if the order delivery details not exists in order.
			$order_delivery_details = dey_get_order_delivery_details( $order );
			if ( ! dey_check_is_array( $order_delivery_details ) ) {
				return $data;
			}

			foreach ( $order_delivery_details as $order_delivery_detail ) {
				$data['payment_via'] .= '<strong>' . esc_html( $order_delivery_detail['label'] ) . '</strong>' . wp_kses_post( $order_delivery_detail['value'] );
			}

			return $data;
		}

		/**
		 * Export the CSV.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function export_csv() {

			if ( ! isset( $_REQUEST['dey_export_csv'] ) ) {
				return;
			}

			$action     = wc_clean( wp_unslash( $_REQUEST['dey_export_csv'] ) );
			$day_filter = isset( $_REQUEST['dey_delivery_day_filter'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_day_filter'] ) ) : '';
			$from_date  = isset( $_REQUEST['dey_from_datetime_picker_value'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_from_datetime_picker_value'] ) ) : '';
			$to_date    = isset( $_REQUEST['dey_to_datetime_picker_value'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_to_datetime_picker_value'] ) ) : '';

			switch ( $action ) {
				case 'order_delivery':
					include_once DEY_ABSPATH . 'inc/exports/class-dey-order-delivery-export-csv.php';

					$exporter = new DEY_Order_Delivery_Export_CSV();
					$exporter->set_day_filter( $day_filter );
					$exporter->set_from_date( $from_date );
					$exporter->set_to_date( $to_date );
					$exporter->export();
					break;

				case 'product_delivery':
					include_once DEY_ABSPATH . 'inc/exports/class-dey-product-delivery-export-csv.php';

					$exporter = new DEY_Product_Delivery_Export_CSV();
					$exporter->set_day_filter( $day_filter );
					$exporter->set_from_date( $from_date );
					$exporter->set_to_date( $to_date );
					$exporter->export();
					break;

				case 'order_local_pickup':
					include_once DEY_ABSPATH . 'inc/exports/class-dey-order-local-pickup-export-csv.php';

					$exporter = new DEY_Order_Local_Pickup_Export_CSV();
					$exporter->set_day_filter( $day_filter );
					$exporter->set_from_date( $from_date );
					$exporter->set_to_date( $to_date );
					$exporter->export();
					break;

				case 'product_pickup':
					include_once DEY_ABSPATH . 'inc/exports/class-dey-product-local-pickup-export-csv.php';

					$exporter = new DEY_Product_Local_Pickup_Export_CSV();
					$exporter->set_day_filter( $day_filter );
					$exporter->set_from_date( $from_date );
					$exporter->set_to_date( $to_date );
					$exporter->export();
					break;

				case 'order_tip':
					include_once DEY_ABSPATH . 'inc/exports/class-dey-order-tip-export-csv.php';

					$exporter = new DEY_Order_Tip_Export_CSV();
					$exporter->set_day_filter( $day_filter );
					$exporter->set_from_date( $from_date );
					$exporter->set_to_date( $to_date );
					$exporter->export();
					break;
			}
		}
	}

	DEY_Admin_Post_Type_Handler::init();
}
