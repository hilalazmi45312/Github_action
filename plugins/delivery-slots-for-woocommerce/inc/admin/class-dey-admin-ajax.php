<?php
/**
 * Admin Ajax.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'DEY_Admin_Ajax' ) ) {

	/**
	 * Class.
	 */
	class DEY_Admin_Ajax {

		/**
		 *  Class initialization.
		 */
		public static function init() {

			$actions = array(
				'json_search_products_and_variations'      => false,
				'json_search_products'                     => false,
				'json_search_customers'                    => false,
				'reset_order_time_slot_usage_count'        => false,
				'reset_order_special_day_usage_count'      => false,
				'add_product_delivery_holiday'             => false,
				'add_product_pickup_location'              => false,
				'add_product_delivery_time_slot'           => false,
				'add_product_delivery_special_day'         => false,
				'reset_product_time_slot_usage_count'      => false,
				'reset_product_special_day_usage_count'    => false,
				'delivery_printing_data'                   => false,
				'calender_events'                          => false,
				'calender_event_data'                      => false,
				'get_order_scheduler_fields'               => true,
				'get_product_scheduler_fields'             => true,
				'handle_order_delivery_selected_date_data' => true,
				'handle_order_delivery_time_slots'         => true,
				'handle_order_pickup_selected_date_data'   => true,
				'handle_order_local_pickup_time_slots'     => true,
				'get_product_selected_delivery_date_data'  => true,
				'get_product_selected_pickup_date_data'    => true,
				'handle_product_delivery_charge'           => true,
				'handle_product_pickup_charge'             => true,
				'add_order_tip'                            => true,
				'add_custom_order_tip'                     => true,
				'remove_order_tip'                         => true,
				'set_order_scheduler_type_session'         => true,
				'create_scheduler_rule'                    => false,
				'create_scheduler_rule_time_slot'          => false,
				'reset_scheduler_rule_time_slot_usage_count' => false,
				'create_scheduler_rule_holiday'            => false,
				'create_scheduler_rule_special_day'        => false,
				'reset_scheduler_rule_special_day_usage_count' => false,
				'create_order_pickup_location_time_slot'   => false,
				'reset_pickup_location_time_slot_usage_count' => false,
				'create_order_pickup_location_holiday'     => false,
				'create_order_pickup_location_special_day' => false,
				'reset_pickup_location_special_day_usage_count' => false,
				'get_order_delivery_script_data'           => true,
				'get_order_local_pickup_script_data'       => true,
			);

			foreach ( $actions as $action => $nopriv ) {
				add_action( 'wp_ajax_dey_' . $action, array( __CLASS__, $action ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_dey_' . $action, array( __CLASS__, $action ) );
				}
			}
		}

		/**
		 * Search for products.
		 *
		 * @return void
		 */
		public static function json_search_products( $term = '', $include_variations = false ) {
			check_ajax_referer( 'search-products', 'dey_security' );

			try {

				if ( empty( $term ) && isset( $_GET['term'] ) ) {
					$term = isset( $_GET['term'] ) ? wc_clean( wp_unslash( $_GET['term'] ) ) : '';
				}

				if ( empty( $term ) ) {
					throw new exception( __( 'No Products found', 'delivery-slots-for-woocommerce' ) );
				}

				if ( ! empty( $_GET['limit'] ) ) {
					$limit = absint( $_GET['limit'] );
				} else {
					/**
					 * This hook is used to alter the WooCommerce JSON search limit.
					 *
					 * @since 1.0
					 */
					$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
				}

				$data_store = WC_Data_Store::load( 'product' );
				$ids        = $data_store->search_products( $term, '', (bool) $include_variations, false, $limit );

				$product_objects = dey_filter_readable_products( $ids );
				$products        = array();

				$exclude_global_variable = isset($_GET['exclude_global_variable']) ? wc_clean(wp_unslash($_GET['exclude_global_variable'])) : 'no'; // @codingStandardsIgnoreLine.
				foreach ( $product_objects as $product_object ) {
					if ( 'yes' === $exclude_global_variable && $product_object->is_type( 'variable' ) ) {
						continue;
					}

					$products[ $product_object->get_id() ] = rawurldecode( $product_object->get_formatted_name() );
				}
				/**
				 * This hook is used to alter the WooCommerce JSON search founded products.
				 *
				 * @since 1.0
				 */
				wp_send_json( apply_filters( 'woocommerce_json_search_found_products', $products ) );
			} catch ( Exception $ex ) {
				wp_die();
			}
		}

		/**
		 * Search for product variations.
		 *
		 * @return void
		 */
		public static function json_search_products_and_variations( $term = '', $include_variations = false ) {
			self::json_search_products( '', true );
		}

		/**
		 * Customers search.
		 *
		 * @return void
		 */
		public static function json_search_customers() {
			check_ajax_referer( 'dey-search-nonce', 'dey_security' );

			try {
				$term = isset($_GET['term']) ? wc_clean(wp_unslash($_GET['term'])) : ''; // @codingStandardsIgnoreLine.

				if ( empty( $term ) ) {
					throw new exception( __( 'No Customer found', 'delivery-slots-for-woocommerce' ) );
				}

				$exclude = isset($_GET['exclude']) ? wc_clean(wp_unslash($_GET['exclude'])) : ''; // @codingStandardsIgnoreLine.
				$exclude = ! empty( $exclude ) ? array_map( 'intval', explode( ',', $exclude ) ) : array();

				if ( ! empty( $_GET['limit'] ) ) {
					$limit = absint( $_GET['limit'] );
				} else {
					/**
					 * This hook is used to alter the WooCommerce JSON search limit.
					 *
					 * @since 1.0
					 */
					$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
				}

				$found_customers = array();
				$customers_query = new WP_User_Query(
					array(
						'fields'         => 'all',
						'orderby'        => 'display_name',
						'exclude'        => $exclude,
						'search'         => '*' . $term . '*',
						'number'         => $limit,
						'search_columns' => array( 'ID', 'user_login', 'user_email', 'user_nicename' ),
					)
				);
				$customers       = $customers_query->get_results();

				if ( dey_check_is_array( $customers ) ) {
					foreach ( $customers as $customer ) {
						$found_customers[ $customer->ID ] = $customer->display_name . ' (#' . $customer->ID . ' &ndash; ' . sanitize_email( $customer->user_email ) . ')';
					}
				}

				wp_send_json( $found_customers );
			} catch ( Exception $ex ) {
				wp_die();
			}
		}

		/**
		 * Reset the order time slot usage count.
		 *
		 * @return void
		 */
		public static function reset_order_time_slot_usage_count() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {

				$time_slot_id = isset( $_REQUEST['time_slot_id'] ) ? wc_clean( wp_unslash( $_REQUEST['time_slot_id'] ) ) : '';
				if ( empty( $time_slot_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slot = dey_get_time_slot( $time_slot_id );
				if ( ! $time_slot->exists() ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				// Reset the usage count.
				$time_slot->update_meta( 'dey_order_usage_count', array() );
				$time_slot->update_meta( 'dey_order_local_pickup_usage_count', array() );

				wp_send_json_success( array( 'msg' => __( 'Order usage count reset successfully', 'delivery-slots-for-woocommerce' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Reset the order special day usage count.
		 *
		 * @return void
		 */
		public static function reset_order_special_day_usage_count() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$special_day_id = isset( $_REQUEST['special_day_id'] ) ? wc_clean( wp_unslash( $_REQUEST['special_day_id'] ) ) : '';
				if ( empty( $special_day_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$special_day = dey_get_special_day( $special_day_id );
				if ( ! $special_day->exists() ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				// Reset the usage count.
				$special_day->update_meta( 'dey_order_usage_count', 0 );

				wp_send_json_success( array( 'msg' => __( 'Order usage count reset successfully', 'delivery-slots-for-woocommerce' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Add new product pickup location.
		 *
		 * @since 3.5.0
		 * @throws exception If the current user does not have permission to edit posts.
		 */
		public static function add_product_pickup_location() {
			check_ajax_referer( 'dey-product-nonce', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$key             = uniqid();
				$pickup_location = dey_get_product_pickup_location_default_data();

				ob_start();
				// Include product time slot file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/product/html-product-delivery-pickup-location.php';

				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Add th product delivery time slot.
		 *
		 * @return void
		 */
		public static function add_product_delivery_time_slot() {
			check_ajax_referer( 'dey-product-nonce', 'dey_security' );

			try {

				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$key       = uniqid();
				$time_slot = dey_get_product_time_slot_default_data();

				ob_start();
				// Include product time slot file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/product/html-product-delivery-time-slot.php';

				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Add th product delivery special day.
		 *
		 * @return void
		 */
		public static function add_product_delivery_special_day() {
			check_ajax_referer( 'dey-product-nonce', 'dey_security' );

			try {

				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$key         = uniqid();
				$special_day = dey_get_product_special_day_default_data();

				ob_start();
				// Include product special day file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/product/html-product-delivery-special-day.php';

				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Add th product delivery holiday.
		 *
		 * @return void
		 */
		public static function add_product_delivery_holiday() {
			check_ajax_referer( 'dey-product-nonce', 'dey_security' );

			try {

				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$key     = uniqid();
				$holiday = dey_get_product_holiday_default_data();

				ob_start();
				// Include product holiday file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/product/html-product-delivery-holiday.php';

				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Reset the product time slot usage count.
		 *
		 * @return void
		 */
		public static function reset_product_time_slot_usage_count() {
			check_ajax_referer( 'dey-product-nonce', 'dey_security' );

			try {

				$time_slot_key = isset( $_REQUEST['time_slot_key'] ) ? wc_clean( wp_unslash( $_REQUEST['time_slot_key'] ) ) : '';
				$product_id    = isset( $_REQUEST['product_id'] ) ? wc_clean( wp_unslash( $_REQUEST['product_id'] ) ) : '';
				if ( empty( $time_slot_key ) || empty( $product_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slots = array_filter( (array) get_post_meta( $product_id, 'dey_delivery_time_slots', true ) );
				if ( ! isset( $time_slots[ $time_slot_key ] ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$time_slots[ $time_slot_key ]['used_order_count'] = array();
				// Reset the usage count.
				dey_update_post_meta( $product_id, 'dey_delivery_time_slots', $time_slots );

				wp_send_json_success( array( 'msg' => __( 'Order usage count reset successfully', 'delivery-slots-for-woocommerce' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Reset the product special day usage count.
		 *
		 * @return void
		 */
		public static function reset_product_special_day_usage_count() {
			check_ajax_referer( 'dey-product-nonce', 'dey_security' );

			try {
				$special_day_key = isset( $_REQUEST['special_day_key'] ) ? wc_clean( wp_unslash( $_REQUEST['special_day_key'] ) ) : '';
				$product_id      = isset( $_REQUEST['product_id'] ) ? wc_clean( wp_unslash( $_REQUEST['product_id'] ) ) : '';
				if ( empty( $special_day_key ) || empty( $product_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$special_days = array_filter( (array) get_post_meta( $product_id, 'dey_delivery_special_days', true ) );
				if ( ! isset( $special_days[ $special_day_key ] ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$special_days[ $special_day_key ]['used_order_count'] = array();
				// Reset the usage count.
				dey_update_post_meta( $product_id, 'dey_delivery_time_slots', $special_days );

				wp_send_json_success( array( 'msg' => __( 'Order usage count reset successfully', 'delivery-slots-for-woocommerce' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the delivery printing data.
		 *
		 * @return void
		 */
		public static function delivery_printing_data() {
			check_ajax_referer( 'dey-print-nonce', 'dey_security' );

			try {
				$type = isset($_REQUEST['type']) ? wc_clean(wp_unslash($_REQUEST['type'])) : ''; // @codingStandardsIgnoreLine.
				if ( empty( $type ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$day_filter = isset( $_REQUEST['day_filter'] ) ? wc_clean( wp_unslash( $_REQUEST['day_filter'] ) ) : '';
				$from_date  = isset( $_REQUEST['from_date'] ) ? wc_clean( wp_unslash( $_REQUEST['from_date'] ) ) : '';
				$to_date    = isset( $_REQUEST['to_date'] ) ? wc_clean( wp_unslash( $_REQUEST['to_date'] ) ) : '';

				ob_start();
				switch ( $type ) {
					case 'order_delivery':
						include_once DEY_ABSPATH . 'inc/print/class-dey-order-delivery-print.php';

						$exporter = new DEY_Order_Delivery_Print();
						$exporter->set_from_date( $from_date );
						$exporter->set_to_date( $to_date );
						$exporter->set_day_filter( $day_filter );
						$exporter->print_data();
						break;

					case 'product_delivery':
						include_once DEY_ABSPATH . 'inc/print/class-dey-product-delivery-print.php';

						$exporter = new DEY_Product_Delivery_Print();
						$exporter->set_from_date( $from_date );
						$exporter->set_to_date( $to_date );
						$exporter->set_day_filter( $day_filter );
						$exporter->print_data();
						break;

					case 'order_local_pickup':
						include_once DEY_ABSPATH . 'inc/print/class-dey-order-local-pickup-print.php';

						$exporter = new DEY_Order_Local_Pickup_Print();
						$exporter->set_from_date( $from_date );
						$exporter->set_to_date( $to_date );
						$exporter->set_day_filter( $day_filter );
						$exporter->print_data();
						break;

					case 'product_pickup':
						include_once DEY_ABSPATH . 'inc/print/class-dey-product-local-pickup-print.php';

						$exporter = new DEY_Product_Local_Pickup_Print();
						$exporter->set_from_date( $from_date );
						$exporter->set_to_date( $to_date );
						$exporter->set_day_filter( $day_filter );
						$exporter->print_data();
						break;

					case 'order_tip':
						include_once DEY_ABSPATH . 'inc/print/class-dey-order-tip-print.php';

						$exporter = new DEY_Order_Tip_Print();
						$exporter->set_from_date( $from_date );
						$exporter->set_to_date( $to_date );
						$exporter->set_day_filter( $day_filter );
						$exporter->print_data();
						break;
				}

				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the calender events.
		 *
		 * @return void
		 */
		public static function calender_events() {
			check_ajax_referer( 'dey-calender-events-nonce', 'dey_security' );

			try {
				$start = isset($_REQUEST['start']) ? wc_clean(wp_unslash($_REQUEST['start'])) : ''; // @codingStandardsIgnoreLine.
				$end = isset($_REQUEST['end']) ? wc_clean(wp_unslash($_REQUEST['end'])) : ''; // @codingStandardsIgnoreLine.

				if ( empty( $start ) || empty( $end ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$start_date = DEY_Date_Time::get_mysql_date_time_format( $start );
				$end_date   = DEY_Date_Time::get_mysql_date_time_format( $end );
				$type       = isset( $_REQUEST['type'] ) ? wc_clean( wp_unslash( $_REQUEST['type'] ) ) : '';

				wp_send_json_success( array( 'events' => DEY_Calender::prepare_calender_events( $start_date, $end_date, $type ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the calender event data.
		 *
		 * @return void
		 */
		public static function calender_event_data() {
			check_ajax_referer( 'dey-calender-events-nonce', 'dey_security' );

			try {
				$event_id = isset($_REQUEST['event_id']) ? wc_clean(wp_unslash($_REQUEST['event_id'])) : ''; // @codingStandardsIgnoreLine.
				if ( empty( $event_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$type = isset( $_REQUEST['type'] ) ? wc_clean( wp_unslash( $_REQUEST['type'] ) ) : '';

				wp_send_json_success( array( 'event' => DEY_Calender::prepare_calender_event_data( $event_id, $type ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the order delivery fields.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public static function get_order_scheduler_fields() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				wp_send_json_success( array( 'content' => dey_get_template_html( 'order/order-scheduler.php' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Handles the order delivery selected date data.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function handle_order_delivery_selected_date_data() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
				if ( ! is_object( $scheduler_rule ) && 'order-delivery' === dey_get_selected_order_scheduler_data_from_session( 'order_scheduler_type' ) ) {
					DEY_Cart_Session_Handler::destroy_order_scheduler_data();
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$date         = isset( $_POST['date']) ? wc_clean( wp_unslash( $_POST['date'] ) ) : ''; // @codingStandardsIgnoreLine.
				$time_slot_id = isset( $_POST['time_slot_id'] ) ? wc_clean( wp_unslash( $_POST['time_slot_id'] ) ) : ''; // @codingStandardsIgnoreLine.
				if ( ! empty( $date ) ) {
					DEY_Cart_Session_Handler::set( 'order_delivery_date', $date );
					DEY_Cart_Session_Handler::set( 'order_scheduler_type', 'order-delivery' );

					$date_object = DEY_Date_Time::get_date_time_object( $date );

					// Handle the order delivery price.
					self::maybe_handle_order_delivery_price( $date_object, $scheduler_rule );

					// Handle the order same day delivery price.
					self::maybe_handle_order_same_day_delivery_price( $date_object, $scheduler_rule );

					// Handle the order next day delivery price.
					self::maybe_handle_order_next_day_delivery_price( $date_object, $scheduler_rule );

					// Handle the order delivery time slots price.
					self::maybe_handle_order_delivery_time_slots_price( $scheduler_rule, $time_slot_id );

					// Handle the order delivery special day.
					self::maybe_handle_order_delivery_special_day( $date_object, $scheduler_rule );
				} else {
					DEY_Cart_Session_Handler::destroy_order_scheduler_data();
				}

				/**
				 * This hook is used to handle order charges.
				 *
				 * @since 1.0.0
				 * @param string $date Selected date.
				 */
				do_action( 'dey_handle_order_delivery_charges', $date );

				wp_send_json_success( array( 'time_slots' => $scheduler_rule->get_order_delivery_time_slot_option_labels( $date ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Handle the order delivery time slots.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function handle_order_delivery_time_slots() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
				if ( ! is_object( $scheduler_rule ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slot_id = isset( $_POST['time_slot_id'] ) ? wc_clean( wp_unslash( $_POST['time_slot_id'] ) ) : ''; // @codingStandardsIgnoreLine.
				// Handle the order delivery time slots price.
				self::maybe_handle_order_delivery_time_slots_price( $scheduler_rule, $time_slot_id );

				wp_send_json_success();
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the order local pickup selected date data.
		 *
		 * @since 3.0.0
		 * @throws exception
		 */
		public static function handle_order_pickup_selected_date_data() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
				if ( ! is_object( $scheduler_rule ) && 'order-local-pickup' === dey_get_selected_order_scheduler_data_from_session( 'order_scheduler_type' ) ) {
					DEY_Cart_Session_Handler::destroy_order_scheduler_data();
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$date               = isset( $_POST['date'] ) ? wc_clean( wp_unslash( $_POST['date'])) : ''; // @codingStandardsIgnoreLine.
				$pickup_location_id = isset( $_POST['pickup_location_id'] ) ? wc_clean( wp_unslash( $_POST['pickup_location_id'] ) ) : false;
				$time_slot_id       = isset( $_POST['time_slot'] ) ? wc_clean( wp_unslash( $_POST['time_slot'] ) ) : 0; // @codingStandardsIgnoreLine.
				if ( ! empty( $date ) ) {
					DEY_Cart_Session_Handler::set( 'order_local_pickup_date', $date );
					DEY_Cart_Session_Handler::set( 'order_scheduler_type', 'order-local-pickup' );

					if ( $pickup_location_id ) {
						DEY_Cart_Session_Handler::set( 'order_pickup_location', $pickup_location_id );
					}

					$date_object                = DEY_Date_Time::get_date_time_object( $date );
					$order_local_pickup_handler = dey_get_order_local_pickup_handler();

					// Handle the order pickup price.
					self::maybe_handle_order_pickup_price( $date_object, $order_local_pickup_handler );

					// Handle the order same day pickup price.
					self::maybe_handle_order_same_day_pickup_price( $date_object, $order_local_pickup_handler );

					// Handle the order next day delivery price.
					self::maybe_handle_order_next_day_pickup_price( $date_object, $order_local_pickup_handler );

					// Handle the order local pickup time slots price.
					self::maybe_handle_order_pickup_time_slots_price( $scheduler_rule, $time_slot_id );

					// Handle the order local pickup special day.
					self::maybe_handle_order_local_pickup_special_day( $date_object, $order_local_pickup_handler );
				} else {
					DEY_Cart_Session_Handler::destroy_order_scheduler_data();
				}

				/**
				 * This hook is used to handle order local pickup charges.
				 *
				 * @since 3.0.0
				 * @param string $date Selected date.
				 */
				do_action( 'dey_handle_order_pickup_charges', $date );

				wp_send_json_success( array( 'time_slots' => self::get_order_pickup_time_slots( $date, $scheduler_rule, $pickup_location_id ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Handle the order local pickup time slots.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function handle_order_local_pickup_time_slots() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
				if ( ! is_object( $scheduler_rule ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slot_id = isset( $_POST['time_slot_id'] ) ? wc_clean( wp_unslash( $_POST['time_slot_id'] ) ) : ''; // @codingStandardsIgnoreLine.
				// Handle the order local pickup time slots price.
				self::maybe_handle_order_pickup_time_slots_price( $scheduler_rule, $time_slot_id );

				wp_send_json_success();
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the order local pickup time slots.
		 *
		 * @since 3.0.0
		 * @param string $date selected date.
		 * @param object $scheduler_rule Scheduler rule object.
		 * @return array
		 */
		public static function get_order_pickup_time_slots( $date, $scheduler_rule, $pickup_location_id ) {
			if ( ! $date ) {
				return array();
			}

			$pickup_location = dey_get_pickup_location( $pickup_location_id );
			if ( is_object( $pickup_location ) && '3' === $pickup_location->get_pickup_time_mode() ) {
				return $pickup_location->get_order_local_pickup_time_slot_option_labels( $date );
			} elseif ( is_object( $scheduler_rule ) && '3' === $scheduler_rule->get_pickup_time_mode() ) {
				return $scheduler_rule->get_order_local_pickup_time_slot_option_labels( $date );
			}

			return false;
		}

		/**
		 * May be handle the order delivery time slots price.
		 *
		 * @since 4.0.0
		 * @param object     $scheduler_rule Scheduler rule object.
		 * @param int|string $time_slot_id Time slot ID or key.
		 * @return void
		 */
		public static function maybe_handle_order_delivery_time_slots_price( $scheduler_rule, $time_slot_id ) {
			// handle the order time slots charges.
			if ( empty( $time_slot_id ) || ( 'none' === $time_slot_id ) || ( 'soon' === $time_slot_id ) ) {
				DEY_Cart_Session_Handler::delete( 'order_delivery_time_slot' );
			} else {
				$time_slot_data = array();
				// Rule level.
				if ( '2' === $scheduler_rule->get_time_slots_mode() ) {
					$time_slots = $scheduler_rule->get_time_slots();
					if ( isset( $time_slots[ $time_slot_id ] ) ) {
						$time_slot_data = array(
							'label' => $time_slots[ $time_slot_id ]['name'],
							'value' => $time_slots[ $time_slot_id ]['price'],
							'id'    => $time_slot_id,
						);
					}
				} else { // Global level.
					$time_slot = dey_get_time_slot( $time_slot_id );
					if ( $time_slot->exists() ) {
						$time_slot_data = array(
							'label' => $time_slot->get_name(),
							'value' => $time_slot->get_price(),
							'id'    => $time_slot->get_id(),
						);
					}
				}

				if ( dey_check_is_array( $time_slot_data ) ) {
					DEY_Cart_Session_Handler::delete( 'order_local_pickup_time_slot' );
					DEY_Cart_Session_Handler::set( 'order_delivery_time_slot', $time_slot_data );
				} else {
					DEY_Cart_Session_Handler::delete( 'order_delivery_time_slot' );
				}
			}
		}

		/**
		 * Maybe handle the order pickup time slots price.
		 *
		 * @since 4.0.0
		 * @param object     $scheduler_rule Scheduler rule object.
		 * @param int|string $time_slot_id Time slot ID or key.
		 * @return void
		 */
		public static function maybe_handle_order_pickup_time_slots_price( $scheduler_rule, $time_slot_id ) {
			// handle the order time slots charges.
			if ( empty( $time_slot_id ) || ( 'none' === $time_slot_id ) || ( 'soon' === $time_slot_id ) ) {
				DEY_Cart_Session_Handler::delete( 'order_local_pickup_time_slot' );
			} else {
				$time_slot_data     = array();
				$pickup_location_id = dey_get_selected_order_scheduler_data_from_session( 'order_pickup_location' );
				$pickup_location    = dey_get_pickup_location( $pickup_location_id );

				if ( is_object( $pickup_location ) && '2' === $pickup_location->get_pickup_mode() ) {
					if ( '2' === $pickup_location->get_time_slots_mode() ) { // Pickup location.
						$time_slots = $pickup_location->get_time_slots();
						if ( isset( $time_slots[ $time_slot_id ] ) ) {
							$time_slot_data = array(
								'label' => $time_slots[ $time_slot_id ]['name'],
								'value' => $time_slots[ $time_slot_id ]['price'],
								'id'    => $time_slot_id,
							);
						}
					} else { // Global level.
						$time_slot = dey_get_time_slot( $time_slot_id );
						if ( $time_slot->exists() ) {
							$time_slot_data = array(
								'label' => $time_slot->get_name(),
								'value' => $time_slot->get_price(),
								'id'    => $time_slot->get_id(),
							);
						}
					}
				} elseif ( '2' === $scheduler_rule->get_time_slots_mode() ) { // Scheduler rule.
					$time_slots = $scheduler_rule->get_time_slots();
					if ( isset( $time_slots[ $time_slot_id ] ) ) {
						$time_slot_data = array(
							'label' => $time_slots[ $time_slot_id ]['name'],
							'value' => $time_slots[ $time_slot_id ]['price'],
							'id'    => $time_slot_id,
						);
					}
				} else { // Global level.
					$time_slot = dey_get_time_slot( $time_slot_id );
					if ( $time_slot->exists() ) {
						$time_slot_data = array(
							'label' => $time_slot->get_name(),
							'value' => $time_slot->get_price(),
							'id'    => $time_slot->get_id(),
						);
					}
				}

				if ( dey_check_is_array( $time_slot_data ) ) {
					DEY_Cart_Session_Handler::delete( 'order_delivery_time_slot' );
					DEY_Cart_Session_Handler::set( 'order_local_pickup_time_slot', $time_slot_data );
				} else {
					DEY_Cart_Session_Handler::delete( 'order_local_pickup_time_slot' );
				}
			}
		}

		/**
		 * May be handle the order delivery price.
		 *
		 * @return void
		 */
		public static function maybe_handle_order_delivery_price( $date_object, $scheduler_rule ) {
			$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$price                  = $order_delivery_handler->get_weekday_price( $date_object );
			if ( ! empty( $price ) ) {
				$data = array(
					'label' => dey_get_order_delivery_fee_label(),
					'value' => $price,
				);

				DEY_Cart_Session_Handler::set( 'weekday', $data );
			} else {
				DEY_Cart_Session_Handler::delete( 'weekday' );
			}
		}

		/**
		 * May be handle the order same day delivery price.
		 *
		 * @since 3.2.0
		 * @param object $date_object Date object.
		 * @param object $scheduler_rule Scheduler rule object.
		 */
		public static function maybe_handle_order_same_day_delivery_price( $date_object, $scheduler_rule ) {
			$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$price                  = $order_delivery_handler->get_same_day_price( $date_object );
			if ( ! empty( $price ) ) {
				$data = array(
					'label' => dey_get_order_same_day_delivery_fee_label(),
					'value' => $price,
				);

				DEY_Cart_Session_Handler::set( 'same_day_price', $data );
			} else {
				DEY_Cart_Session_Handler::delete( 'same_day_price' );
			}
		}

		/**
		 * May be handle the order next day delivery price.
		 *
		 * @since 3.2.0
		 * @param object $date_object Date object.
		 * @param object $scheduler_rule Scheduler rule object.
		 */
		public static function maybe_handle_order_next_day_delivery_price( $date_object, $scheduler_rule ) {
			$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$price                  = $order_delivery_handler->get_next_day_price( $date_object );
			if ( ! empty( $price ) ) {
				$data = array(
					'label' => dey_get_order_next_day_delivery_fee_label(),
					'value' => $price,
				);

				DEY_Cart_Session_Handler::set( 'next_day_price', $data );
			} else {
				DEY_Cart_Session_Handler::delete( 'next_day_price' );
			}
		}

		/**
		 * May be handle the order local pickup price.
		 *
		 * @param object $date_object Date object.
		 */
		public static function maybe_handle_order_pickup_price( $date_object, $order_local_pickup_handler ) {
			if ( ! is_object( $order_local_pickup_handler ) ) {
				return;
			}

			$price = $order_local_pickup_handler->get_weekday_price( $date_object );
			if ( ! empty( $price ) ) {
				DEY_Cart_Session_Handler::set( 'weekday', array( 'label' => dey_get_order_pickup_fee_label(), 'value' => $price ) );
			} else {
				DEY_Cart_Session_Handler::delete( 'weekday' );
			}
		}

		/**
		 * May be handle the order same day pickup price.
		 *
		 * @since 3.2.0
		 * @param object $date_object Date object.
		 * @param object $order_local_pickup_handler Order local pickup handler object.
		 */
		public static function maybe_handle_order_same_day_pickup_price( $date_object, $order_local_pickup_handler ) {
			$price = $order_local_pickup_handler->get_same_day_price( $date_object );
			if (  ! empty( $price )  ) {
				DEY_Cart_Session_Handler::set( 'same_day_price', array( 'label' => dey_get_order_same_day_pickup_fee_label(), 'value' => $price ) );
			} else {
				DEY_Cart_Session_Handler::delete( 'same_day_price' );
			}
		}

		/**
		 * May be handle the order next day pickup price.
		 *
		 * @since 3.2.0
		 * @param object $date_object Date object.
		 * @param object $order_local_pickup_handler Order local pickup handler object.
		 */
		public static function maybe_handle_order_next_day_pickup_price( $date_object, $order_local_pickup_handler ) {
			$price = $order_local_pickup_handler->get_next_day_price( $date_object );
			if ( ! empty( $price ) ) {
				DEY_Cart_Session_Handler::set( 'next_day_price', array( 'label' => dey_get_order_next_day_pickup_fee_label(), 'value' => $price ) );
			} else {
				DEY_Cart_Session_Handler::delete( 'next_day_price' );
			}
		}

		/**
		 * May be handle the order delivery special day.
		 *
		 * @since 1.0.0
		 * @param object $date_object Date object.
		 * @param object $scheduler_rule Scheduler rule object.
		 * @return void
		 */
		public static function maybe_handle_order_delivery_special_day( $date_object, $scheduler_rule ) {
			$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$special_days           = $order_delivery_handler->get_special_dates();

			if ( dey_check_is_array( $special_days ) && isset( $special_days[ $date_object->format( 'Y-m-d' ) ] ) ) {
				$special_day = $special_days[ $date_object->format( 'Y-m-d' ) ];
				$data        = array(
					'label' => $special_day['name'],
					'value' => $special_day['price'],
					'id'    => $special_day['id'],
				);

				DEY_Cart_Session_Handler::set( 'special_day', $data );
			} else {
				DEY_Cart_Session_Handler::delete( 'special_day' );
			}
		}

		/**
		 * May be handle the order local pickup special day.
		 *
		 * @since 3.0.0
		 * @param object $date_object date object.
		 * @param object $order_local_pickup_handler Order local pickup handler object.
		 * @return void
		 */
		public static function maybe_handle_order_local_pickup_special_day( $date_object, $order_local_pickup_handler ) {
			$order_local_pickup_handler = dey_get_order_local_pickup_handler();
			$special_days               = $order_local_pickup_handler->get_special_dates();

			if ( dey_check_is_array( $special_days ) && isset( $special_days[ $date_object->format( 'Y-m-d' ) ] ) ) {
				$special_day = $special_days[ $date_object->format( 'Y-m-d' ) ];

				DEY_Cart_Session_Handler::set(
					'special_day',
					array(
						'label' => $special_day['name'],
						'value' => $special_day['price'],
						'id'    => $special_day['id'],
					)
				);
			} else {
				DEY_Cart_Session_Handler::delete( 'special_day' );
			}
		}

		/**
		 * Get the product delivery selected date data.
		 *
		 * @since 1.0.0
		 * @throws exception IF date|product is invalid.
		 */
		public static function get_product_selected_delivery_date_data() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$date = isset($_REQUEST['date']) ? wc_clean(wp_unslash($_REQUEST['date'])) : ''; // @codingStandardsIgnoreLine.
				$product_id = isset( $_REQUEST['product_id'] ) ? wc_clean( wp_unslash( $_REQUEST['product_id'] ) ) : '';
				if ( empty( $date ) ) {
					throw new exception( __( 'Please select a date', 'delivery-slots-for-woocommerce' ) );
				}

				if ( empty( $product_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$product = wc_get_product( $product_id );
				if ( ! is_object( $product ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				wp_send_json_success( array( 'time_slots' => self::get_product_delivery_time_slots( $date, $product ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the product pickup selected date data.
		 *
		 * @since 3.5.0
		 * @throws exception IF date|product is invalid.
		 */
		public static function get_product_selected_pickup_date_data() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$date = isset($_REQUEST['date']) ? wc_clean(wp_unslash($_REQUEST['date'])) : ''; // @codingStandardsIgnoreLine.
				$product_id = isset( $_REQUEST['product_id'] ) ? wc_clean( wp_unslash( $_REQUEST['product_id'] ) ) : '';
				if ( ! $date ) {
					throw new exception( __( 'Please select a date', 'delivery-slots-for-woocommerce' ) );
				}

				if ( ! $product_id ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$product = wc_get_product( $product_id );
				if ( ! is_object( $product ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				wp_send_json_success( array( 'time_slots' => self::get_product_pickup_time_slots( $date, $product ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the product time slots.
		 *
		 * @since 1.0.0
		 * @param string $date selected date.
		 * @param object $product instanceof WC_Product.
		 * @return array
		 */
		public static function get_product_delivery_time_slots( $date, $product ) {
			$time_slot_options = array();
			$product_delivery  = DEY_Product_Delivery_Handler::init( $product );
			if ( '2' == $product_delivery->get_meta( 'dey_delivery_slot_mode' ) || '3' != $product_delivery->get_meta( 'dey_delivery_time_mode' ) ) {
				return $time_slot_options;
			}

			$time_slots = array_filter( (array) $product_delivery->get_meta( 'dey_delivery_time_slots' ) );

			if ( 'yes' === $product_delivery->get_meta( 'dey_delivery_enable_as_soon_as_possible' ) ) {
				$time_slot_options[] = array(
					'id'    => 'soon',
					'label' => dey_get_product_delivery_as_soon_as_possible_label(),
				);
			}

			if ( dey_check_is_array( $time_slots ) ) {
				foreach ( $time_slots as $key => $time_slot ) {
					if ( ! $product_delivery->is_valid_time_slot( $key, $date ) ) {
						continue;
					}

					$time_slot_options[] = array(
						'id'    => $key,
						'label' => dey_format_product_delivery_time_slot_label( $time_slot['from_time'], $time_slot['to_time'], $time_slot['price'], $product ),
					);
				}
			}

			if ( ! dey_check_is_array( $time_slot_options ) ) {
				$time_slot_options[] = array(
					'id'    => 'none',
					'label' => dey_get_product_delivery_unavailable_time_slot_label(),
				);
			}

			return $time_slot_options;
		}

		/**
		 * Get the product pickup time slots.
		 *
		 * @since 3.5.0
		 * @param string $date selected date.
		 * @param object $product instanceof WC_Product.
		 * @return array
		 */
		public static function get_product_pickup_time_slots( $date, $product ) {
			$time_slot_options = array();
			$product_pickup    = DEY_Product_Local_Pickup_Handler::init( $product );
			if ( '2' === $product_pickup->get_meta( 'dey_pickup_slot_mode' ) || '3' !== $product_pickup->get_meta( 'dey_pickup_time_mode' ) ) {
				return $time_slot_options;
			}

			$time_slots = array_filter( (array) $product_pickup->get_meta( 'dey_pickup_time_slots' ) );
			if ( 'yes' === $product_pickup->get_meta( 'dey_pickup_enable_as_soon_as_possible' ) ) {
				$time_slot_options[] = array(
					'id'    => 'soon',
					'label' => dey_get_product_pickup_as_soon_as_possible_label(),
				);
			}

			if ( dey_check_is_array( $time_slots ) ) {
				foreach ( $time_slots as $key => $time_slot ) {
					if ( ! $product_pickup->is_valid_time_slot( $key, $date ) ) {
						continue;
					}

					$time_slot_options[] = array(
						'id'    => $key,
						'label' => dey_format_product_pickup_time_slot_label( $time_slot['from_time'], $time_slot['to_time'], $time_slot['price'], $product ),
					);
				}
			}

			if ( ! dey_check_is_array( $time_slot_options ) ) {
				$time_slot_options[] = array(
					'id'    => 'none',
					'label' => dey_get_product_pickup_unavailable_time_slot_label(),
				);
			}

			return $time_slot_options;
		}

		/**
		 * Handle the product delivery charge.
		 *
		 * @since 1.0.0
		 * @throws exception IF the product_id is invalid.
		 */
		public static function handle_product_delivery_charge() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$date = isset($_REQUEST['date']) ? wc_clean(wp_unslash($_REQUEST['date'])) : ''; // @codingStandardsIgnoreLine.              
				$product_id = isset( $_REQUEST['product_id'] ) ? wc_clean( wp_unslash( $_REQUEST['product_id'] ) ) : '';
				if ( empty( $product_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$product = wc_get_product( $product_id );
				if ( ! is_object( $product ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$price            = $product->get_price();
				$product_delivery = DEY_Product_Delivery_Handler::init( $product );
				if ( ! empty( $date ) ) {
					$date_object = DEY_Date_Time::get_date_time_object( $date );

					// Handle the product delivery price.
					$price += $product_delivery->get_weekday_price( $date_object );

					// Handle the product delivery price for same day.
					$price += $product_delivery->get_same_day_price( $date_object );

					// Handle the product delivery price for next day.
					$price += $product_delivery->get_next_day_price( $date_object );

					// Handle the product delivery  time slots price.
					$price += self::maybe_handle_product_delivery_time_slots_price( $product_delivery );

					// Handle the product delivery special day.
					$price += self::maybe_handle_product_delivery_special_day( $date_object, $product_delivery );
				}

				/**
				 * This hook is used to handle product delivery charges.
				 *
				 * @since 1.0
				 */
				do_action( 'dey_handle_product_delivery_charges' );

				/**
				 * This hook is used to alter the product delivery charges.
				 *
				 * @since 3.8.0
				 * @param float $price Delivery charges.
				 * @param object $product Product object.
				 * @param string $date Selected date.
				 */
				$price = apply_filters( 'dey_product_delivery_charge', $price, $product, $date );

				wp_send_json_success( array( 'price' => dey_price( dey_get_product_price_to_display( $product, $price ) ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Handle the product pickup charge.
		 *
		 * @since 3.5.0
		 * @throws exception IF the product_id is invalid.
		 */
		public static function handle_product_pickup_charge() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$date = isset($_REQUEST['date']) ? wc_clean(wp_unslash($_REQUEST['date'])) : ''; // @codingStandardsIgnoreLine.              
				$product_id = isset( $_REQUEST['product_id'] ) ? wc_clean( wp_unslash( $_REQUEST['product_id'] ) ) : '';
				if ( empty( $product_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$product = wc_get_product( $product_id );
				if ( ! is_object( $product ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$price          = $product->get_price();
				$product_pickup = DEY_Product_Local_Pickup_Handler::init( $product );
				if ( ! empty( $date ) ) {
					$date_object = DEY_Date_Time::get_date_time_object( $date );
					// Handle the product pickup price.
					$price += $product_pickup->get_weekday_price( $date_object );

					// Handle the product pickup price for same day.
					$price += $product_pickup->get_same_day_price( $date_object );

					// Handle the product pickup price for next day.
					$price += $product_pickup->get_next_day_price( $date_object );

					// Handle the product pickup time slots price.
					$price += self::maybe_handle_product_pickup_time_slots_price( $product_pickup );

					// Handle the product pickup special day.
					$price += self::maybe_handle_product_pickup_special_day( $date_object, $product_pickup );
				}

				/**
				 * This hook is used to handle product pickup charges.
				 *
				 * @since 3.5.0
				 */
				do_action( 'dey_handle_product_pickup_charges' );

				/**
				 * This hook is used to alter the product pickup charges.
				 *
				 * @since 3.8.0
				 * @param float $price Pickup charges.
				 * @param object $product Product object.
				 * @param string $date Selected date.
				 */
				$price = apply_filters( 'dey_product_pickup_charge', $price, $product, $date );

				wp_send_json_success( array( 'price' => dey_price( dey_get_product_price_to_display( $product, $price ) ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Maybe handle the product delivery time slots price.
		 *
		 * @since 1.0.0
		 * @param object $product_delivery instanceof DEY_Product_Delivery_Handler.
		 * @return float
		 */
		public static function maybe_handle_product_delivery_time_slots_price( $product_delivery ) {
			$time_slot_id = isset($_REQUEST['time_slot']) ? wc_clean(wp_unslash($_REQUEST['time_slot'])) : 0; // @codingStandardsIgnoreLine.
			// handle the order time slots charges.
			if ( empty( $time_slot_id ) || ( 'none' === $time_slot_id ) || ( 'soon' === $time_slot_id ) ) {
				return 0;
			}

			$time_slots = array_filter( (array) $product_delivery->get_meta( 'dey_delivery_time_slots' ) );
			if ( ! isset( $time_slots[ $time_slot_id ] ) ) {
				return 0;
			}

			return floatval( $time_slots[ $time_slot_id ]['price'] );
		}

		/**
		 * Maybe handle the product pickup time slots price.
		 *
		 * @since 3.5.0
		 * @param object $product_pickup instanceof DEY_Product_Local_Pickup_Handler.
		 * @return float
		 */
		public static function maybe_handle_product_pickup_time_slots_price( $product_pickup ) {
			$time_slot_id = isset($_REQUEST['time_slot']) ? wc_clean(wp_unslash($_REQUEST['time_slot'])) : 0; // @codingStandardsIgnoreLine.
			// Handle the order time slots charges.
			if ( ! $time_slot_id || ! is_object( $product_pickup ) || ( 'none' === $time_slot_id ) || ( 'soon' === $time_slot_id ) ) {
				return 0;
			}

			$time_slots = array_filter( (array) $product_pickup->get_meta( 'dey_pickup_time_slots' ) );
			if ( ! isset( $time_slots[ $time_slot_id ] ) ) {
				return 0;
			}

			return floatval( $time_slots[ $time_slot_id ]['price'] );
		}

		/**
		 * Maybe handle the product delivery special day.
		 *
		 * @since 1.0.0
		 * @param object $date_object Date object.
		 * @param object $product_delivery instanceof DEY_Product_Delivery_Handler.
		 * @return float
		 */
		public static function maybe_handle_product_delivery_special_day( $date_object, $product_delivery ) {
			$special_days = $product_delivery->get_special_dates();
			if ( ! dey_check_is_array( $special_days ) || ! isset( $special_days[ $date_object->format( 'Y-m-d' ) ] ) ) {
				return 0;
			}

			return floatval( $special_days[ $date_object->format( 'Y-m-d' ) ] );
		}

		/**
		 * Maybe handle the product pickup special day.
		 *
		 * @since 3.5.0
		 * @param object $date_object Date object.
		 * @param object $product_pickup instanceof DEY_Product_Local_Pickup_Handler.
		 * @return float
		 */
		public static function maybe_handle_product_pickup_special_day( $date_object, $product_pickup ) {
			$special_days = $product_pickup->get_special_dates();
			if ( ! dey_check_is_array( $special_days ) || ! isset( $special_days[ $date_object->format( 'Y-m-d' ) ] ) ) {
				return 0;
			}

			return floatval( $special_days[ $date_object->format( 'Y-m-d' ) ] );
		}

		/**
		 * Add the order tip by using WC session.
		 *
		 * @return void
		 */
		public static function add_order_tip() {
			check_ajax_referer( 'dey-order-tip', 'dey_security' );

			try {

				$amount = isset( $_REQUEST['amount'] ) ? wc_clean( wp_unslash( $_REQUEST['amount'] ) ) : '';
				if ( empty( $amount ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$buttons = dey_get_order_tip_buttons();
				if ( ! array_key_exists( $amount, $buttons ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$data = array(
					'value' => $amount,
					'mode'  => 'online',
					'type'  => 'predefined',
				);

				// Set order tip session.
				DEY_Cart_Session_Handler::set_order_tip_session_data( $data );

				dey_add_wc_notice( dey_get_order_tip_fee_added_message() );

				wp_send_json_success( array( 'message' => dey_get_order_tip_fee_added_message() ) );
			} catch ( Exception $ex ) {
				DEY_Cart_Session_Handler::delete_order_tip_session_data();
				// Add error.
				dey_add_wc_notice( $ex->getMessage(), 'error' );
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Add the custom order tip by using WC session.
		 *
		 * @return void
		 */
		public static function add_custom_order_tip() {
			check_ajax_referer( 'dey-order-tip', 'dey_security' );

			try {
				// Validate if the amount is empty.
				$amount = isset( $_REQUEST['amount'] ) ? wc_clean( wp_unslash( $_REQUEST['amount'] ) ) : '';
				if ( empty( $amount ) ) {
					throw new Exception( dey_get_custom_tip_empty_message() );
				}

				// Return if the fund is not numeric and equal or less than 0.
				if ( 0 >= $amount ) {
					throw new Exception( dey_get_valid_custom_tip_message() );
				}

				// Validate if the amount less than minimum amount.
				$min_value = get_option( 'dey_order_tip_custom_tip_min_value' );
				if ( $min_value && $min_value > $amount ) {
					throw new Exception( dey_get_custom_tip_minimum_message( $min_value ) );
				}

				// Validate if the amount greater than maximum amount.
				$max_value = get_option( 'dey_order_tip_custom_tip_max_value' );
				if ( $max_value && $max_value < $amount ) {
					throw new Exception( dey_get_custom_tip_maximum_message( $max_value ) );
				}

				$data = array(
					'value' => $amount,
					'mode'  => 'online',
					'type'  => 'custom',
				);

				// Set order tip session.
				DEY_Cart_Session_Handler::set_order_tip_session_data( $data );

				dey_add_wc_notice( dey_get_order_tip_fee_added_message() );

				wp_send_json_success( array( 'message' => dey_get_order_tip_fee_added_message() ) );
			} catch ( Exception $ex ) {
				// Delete the session data.
				DEY_Cart_Session_Handler::delete_order_tip_session_data();

				// Add error.
				dey_add_wc_notice( $ex->getMessage(), 'error' );

				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Remove the order tip by using WC session.
		 *
		 * @return void
		 */
		public static function remove_order_tip() {
			check_ajax_referer( 'dey-order-tip', 'dey_security' );

			try {
				// Delete the order tip session.
				DEY_Cart_Session_Handler::delete_order_tip_session_data();

				dey_add_wc_notice( dey_get_order_tip_fee_removed_message() );

				wp_send_json_success( array( 'message' => dey_get_order_tip_fee_removed_message() ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Set the order scheduler type in the WC_Session.
		 *
		 * @since 3.6.0
		 * @throws exception If the order scheduler type is not valid.
		 */
		public static function set_order_scheduler_type_session() {
			check_ajax_referer( 'dey-order-scheduler', 'dey_security' );

			try {
				if ( ! isset( $_REQUEST['order_scheduler_type'] ) || empty( $_REQUEST['order_scheduler_type'] ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				// Set the selected order scheduler type in WC_Session.
				DEY_Cart_Session_Handler::set( 'order_scheduler_type', wc_clean( wp_unslash( $_REQUEST['order_scheduler_type'] ) ) );

				wp_send_json_success();
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Create scheduler rule.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function create_scheduler_rule() {
			check_ajax_referer( 'dey-scheduler-rule', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$scheduler_rule_id = dey_create_new_scheduler_rule(
					wp_parse_args(
						array(
							'dey_shipping_methods' => isset( $_POST['shipping_methods'] ) ? wc_clean( wp_unslash( $_POST['shipping_methods'] ) ) : '',
							'dey_scheduler_type'   => isset( $_POST['scheduler_type'] ) ? wc_clean( wp_unslash( $_POST['scheduler_type'] ) ) : '3',
						),
						dey_get_scheduler_rule_default_data()
					),
					array(
						'post_title'  => isset( $_POST['title'] ) && ! empty( $_POST['title'] ) ? wc_clean( wp_unslash( $_POST['title'] ) ) : 'Untitled',
						'post_status' => 'dey_inactive',
					)
				);

				$url = add_query_arg( array( 'post' => $scheduler_rule_id, 'action' => 'edit' ), admin_url( 'post.php' ) );

				wp_send_json_success( array( 'url' => $url ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Create new scheduler rule time slot.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function create_scheduler_rule_time_slot() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( esc_html__( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['time_slot_data'] ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slot_data = array();
				$_post          = $_POST;
				parse_str( $_post['time_slot_data'], $time_slot_data ); // @codingStandardsIgnoreLine.

				if ( ! isset( $time_slot_data['dey_time_slot'] ) ) {
					throw new exception( esc_html__( 'Invalid Data', 'delivery-slots-for-woocommerce' ) );
				}

				$key       = uniqid();
				$time_slot = wp_parse_args( array_filter( $time_slot_data['dey_time_slot'] ), dey_get_scheduler_rule_time_slot_default_data() );

				// Include scheduler rule time slot file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/scheduler-rule/html-time-slot.php';
				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Create new scheduler rule holiday.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function create_scheduler_rule_holiday() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( esc_html__( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['holiday_data'] ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				$holiday_data = array();
				$_post        = $_POST;
				parse_str( $_post['holiday_data'], $holiday_data );

				if ( ! isset( $holiday_data['dey_holidays'] ) ) {
					throw new exception( esc_html__( 'Invalid Data', 'delivery-slots-for-woocommerce' ) );
				}

				$key     = uniqid();
				$holiday = wp_parse_args( array_filter( $holiday_data['dey_holidays'] ), dey_get_scheduler_rule_holiday_default_data() );

				// Include order time slot file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/scheduler-rule/html-holiday.php';
				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Create new scheduler rule special day.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function create_scheduler_rule_special_day() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( esc_html__( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['special_day_data'] ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				$special_day_data = array();
				$_post            = $_POST;
				parse_str( $_post['special_day_data'], $special_day_data );

				if ( ! isset( $special_day_data['dey_special_days'] ) ) {
					throw new exception( esc_html__( 'Invalid Data', 'delivery-slots-for-woocommerce' ) );
				}

				$key         = uniqid();
				$special_day = wp_parse_args( array_filter( $special_day_data['dey_special_days'] ), dey_get_scheduler_rule_special_day_default_data() );

				// Include scheduler rule special day file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/scheduler-rule/html-special-day.php';
				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Reset the scheduler rule time slot order usage count.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function reset_scheduler_rule_time_slot_usage_count() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$scheduler_rule_id = isset( $_POST['scheduler_rule_id'] ) ? wc_clean( wp_unslash( $_POST['scheduler_rule_id'] ) ) : '';
				if ( empty( $scheduler_rule_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slot_key = isset( $_POST['time_slot_key'] ) ? wc_clean( wp_unslash( $_POST['time_slot_key'] ) ) : '';
				if ( empty( $time_slot_key ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slots = array_filter( (array) get_post_meta( $scheduler_rule_id, 'dey_time_slots', true ) );
				if ( ! dey_check_is_array( $time_slots ) || ! isset( $time_slots[ $time_slot_key ] ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slots[ $time_slot_key ]['order_delivery_usage_count']     = array();
				$time_slots[ $time_slot_key ]['order_local_pickup_usage_count'] = array();
				dey_update_scheduler_rule( $scheduler_rule_id, array( 'dey_time_slots' => $time_slots ) );

				wp_send_json_success( array( 'msg' => __( 'Order usage count reset successfully', 'delivery-slots-for-woocommerce' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Reset the scheduler rule special day order usage count.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function reset_scheduler_rule_special_day_usage_count() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$scheduler_rule_id = isset( $_POST['scheduler_rule_id'] ) ? wc_clean( wp_unslash( $_POST['scheduler_rule_id'] ) ) : '';
				if ( empty( $scheduler_rule_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$special_day_key = isset( $_POST['special_day_key'] ) ? wc_clean( wp_unslash( $_POST['special_day_key'] ) ) : '';
				if ( empty( $special_day_key ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$special_days = array_filter( (array) get_post_meta( $scheduler_rule_id, 'dey_special_days', true ) );
				if ( ! dey_check_is_array( $special_days ) || ! isset( $special_days[ $special_day_key ] ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$special_days[ $special_day_key ]['used_order_count'] = 0;

				dey_update_scheduler_rule( $scheduler_rule_id, array( 'dey_special_days' => $special_days ) );

				wp_send_json_success( array( 'msg' => __( 'Order usage count reset successfully', 'delivery-slots-for-woocommerce' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Create new order pickup location time slot.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function create_order_pickup_location_time_slot() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( esc_html__( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['time_slot_data'] ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slot_data = array();
				$_post          = $_POST;
				parse_str( $_post['time_slot_data'], $time_slot_data );

				if ( ! isset( $time_slot_data['dey_time_slot'] ) ) {
					throw new exception( esc_html__( 'Invalid Data', 'delivery-slots-for-woocommerce' ) );
				}

				$key       = uniqid();
				$time_slot = wp_parse_args( array_filter( $time_slot_data['dey_time_slot'] ), dey_get_order_pickup_location_time_slot_default_data() );

				// Include order pickup location time slot file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/pickup-location/html-time-slot.php';
				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Reset the pickup location time slot order usage count.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function reset_pickup_location_time_slot_usage_count() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$pickup_location_id = isset( $_POST['pickup_location_id'] ) ? wc_clean( wp_unslash( $_POST['pickup_location_id'] ) ) : '';
				if ( empty( $pickup_location_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slot_key = isset( $_POST['time_slot_key'] ) ? wc_clean( wp_unslash( $_POST['time_slot_key'] ) ) : '';
				if ( empty( $time_slot_key ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slots = array_filter( (array) get_post_meta( $pickup_location_id, 'dey_time_slots', true ) );
				if ( ! dey_check_is_array( $time_slots ) || ! isset( $time_slots[ $time_slot_key ] ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$time_slots[ $time_slot_key ]['used_order_count'] = array();
				dey_update_pickup_location( $pickup_location_id, array( 'dey_time_slots' => $time_slots ) );

				wp_send_json_success( array( 'msg' => __( 'Order usage count reset successfully', 'delivery-slots-for-woocommerce' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Create new order pickup location holiday.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function create_order_pickup_location_holiday() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( esc_html__( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['holiday_data'] ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				$holiday_data = array();
				$_post        = $_POST;
				parse_str( $_post['holiday_data'], $holiday_data );

				if ( ! isset( $holiday_data['dey_holidays'] ) ) {
					throw new exception( esc_html__( 'Invalid Data', 'delivery-slots-for-woocommerce' ) );
				}

				$key     = uniqid();
				$holiday = wp_parse_args( array_filter( $holiday_data['dey_holidays'] ), dey_get_order_pickup_location_holiday_default_data() );

				// Include order pickup location time slot file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/pickup-location/html-holiday.php';
				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Create new order pickup location special day.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function create_order_pickup_location_special_day() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( esc_html__( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['special_day_data'] ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				$special_day_data = array();
				$_post            = $_POST;
				parse_str( $_post['special_day_data'], $special_day_data );

				if ( ! isset( $special_day_data['dey_special_days'] ) ) {
					throw new exception( esc_html__( 'Invalid Data', 'delivery-slots-for-woocommerce' ) );
				}

				$key         = uniqid();
				$special_day = wp_parse_args( array_filter( $special_day_data['dey_special_days'] ), dey_get_order_pickup_location_special_day_default_data() );

				// Include order pickup location special day file.
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/meta-boxes/pickup-location/html-special-day.php';
				$contents = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $contents ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Reset the order pickup location special day order usage count.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function reset_pickup_location_special_day_usage_count() {
			check_ajax_referer( 'dey-metabox', 'dey_security' );

			try {
				// Return if the current user does not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'delivery-slots-for-woocommerce' ) );
				}

				$pickup_location_id = isset( $_POST['pickup_location_id'] ) ? wc_clean( wp_unslash( $_POST['pickup_location_id'] ) ) : '';
				if ( empty( $pickup_location_id ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$special_day_key = isset( $_POST['special_day_key'] ) ? wc_clean( wp_unslash( $_POST['special_day_key'] ) ) : '';
				if ( empty( $special_day_key ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$special_days = array_filter( (array) get_post_meta( $pickup_location_id, 'dey_special_days', true ) );
				if ( ! dey_check_is_array( $special_days ) || ! isset( $special_days[ $special_day_key ] ) ) {
					throw new exception( __( 'Cannot proceed the action', 'delivery-slots-for-woocommerce' ) );
				}

				$special_days[ $special_day_key ]['used_order_count'] = 0;
				dey_update_pickup_location( $pickup_location_id, array( 'dey_special_days' => $special_days ) );

				wp_send_json_success( array( 'msg' => __( 'Order usage count reset successfully', 'delivery-slots-for-woocommerce' ) ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the order delivery datepicker script data.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function get_order_delivery_script_data() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
				if ( ! is_object( $scheduler_rule ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );

				wp_send_json_success( array( 'script_data' => $order_delivery_handler::get_script_data() ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get the order local pickup datepicker script data based on selected pickup location.
		 *
		 * @since 4.0.0
		 * @throws exception
		 */
		public static function get_order_local_pickup_script_data() {
			check_ajax_referer( 'dey-datepicker', 'dey_security' );

			try {
				$pickup_location_id = isset( $_REQUEST['pickup_location_id'] ) && ! empty( $_REQUEST['pickup_location_id'] ) ? wc_clean( wp_unslash( $_REQUEST['pickup_location_id'] ) ) : '';
				// Set the chosen order pickup location in WC_Session.
				DEY_Cart_Session_Handler::set( 'order_pickup_location', $pickup_location_id );

				$order_local_pickup_handler = dey_get_order_local_pickup_handler();
				if ( ! is_object( $order_local_pickup_handler ) ) {
					throw new exception( esc_html__( 'Invalid Request', 'delivery-slots-for-woocommerce' ) );
				}

				$content        = '';
				$script_data    = array();
				$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
				if ( is_object( $scheduler_rule ) ) {
					ob_start();
					DEY_Frontend::render_order_local_pickup_fields( $scheduler_rule );
					$content = ob_get_clean();
				}

				wp_send_json_success( array( 'script_data' => $order_local_pickup_handler->get_script_data(), 'html' => $content ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}
	}

	DEY_Admin_Ajax::init();
}
