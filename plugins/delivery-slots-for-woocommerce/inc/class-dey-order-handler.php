<?php
/**
 * Handles the Order.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Order_Handler' ) ) {

	/**
	 * Class.
	 */
	class DEY_Order_Handler {

		/**
		 * Class Initialization.
		 */
		public static function init() {
			// Create the product delivery order line item.
			add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'create_product_order_line_item' ), 10, 4 );
			// May be create the delivery slots meta based on short code checkout.
			add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'maybe_update_delivery_slots_meta' ), 10 );
			// May be create the delivery slots meta based on block checkout.
			add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'maybe_update_delivery_slots_meta' ) );
			// Hide the order item meta keys.
			add_action( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hide_order_item_meta_key' ), 10, 2 );
			// Maybe hide the cancel order button.
			add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'maybe_hide_cancel_order_button' ), 10, 2 );
			add_filter( 'woocommerce_valid_order_statuses_for_cancel', array( __CLASS__, 'maybe_restrict_cancel_order' ), 10, 2 );

			/**
			 * This hook is used to alter the product scheduler order statuses.
			 *
			 * @since 1.0.0
			 */
			$product_scheduler_order_statuses = apply_filters(
				'dey_product_scheduler_order_statuses',
				array(
					'woocommerce_order_status_processing',
					'woocommerce_order_status_completed',
				)
			);

			if ( dey_check_is_array( $product_scheduler_order_statuses ) ) {
				foreach ( $product_scheduler_order_statuses as $order_status ) {
					// Add the hooks for product delivery order status.
					add_action( $order_status, array( __CLASS__, 'processes_product_schedulers' ), 1 );
				}
			}

			/**
			 * This hook is used to alter the order delivery order statuses.
			 *
			 * @since 1.0
			 */
			$order_delivery_order_statuses = apply_filters(
				'dey_order_delivery_order_statuses',
				array(
					'woocommerce_order_status_processing',
					'woocommerce_order_status_completed',
				)
			);

			if ( dey_check_is_array( $order_delivery_order_statuses ) ) {
				foreach ( $order_delivery_order_statuses as $order_status ) {
					// Add the hooks for order delivery order status.
					add_action( $order_status, array( __CLASS__, 'processes_order_delivery' ), 1 );
				}
			}

			/**
			 * This hook is used to alter the order tip order statuses.
			 *
			 * @since 1.0
			 */
			$order_tip_order_statuses = apply_filters(
				'dey_order_tip_order_statuses',
				array(
					'woocommerce_order_status_processing',
					'woocommerce_order_status_completed',
				)
			);

			if ( dey_check_is_array( $order_tip_order_statuses ) ) {
				foreach ( $order_tip_order_statuses as $order_status ) {
					// Add the hooks for order tip order status.
					add_action( $order_status, array( __CLASS__, 'processes_order_tip' ), 1 );
				}
			}

			/**
			 * This hook is used to alter the product delivery refund order statuses.
			 *
			 * @since 1.0
			 */
			$product_refund_order_statuses = apply_filters(
				'dey_product_delivery_refund_order_statuses',
				array(
					'woocommerce_order_status_refunded',
					'woocommerce_order_status_cancelled',
					'woocommerce_order_status_failed',
				)
			);

			if ( dey_check_is_array( $product_refund_order_statuses ) ) {
				foreach ( $product_refund_order_statuses as $order_status ) {
					// Add the hooks for product delivery slots refund.
					add_action( $order_status, array( __CLASS__, 'maybe_failed_product_delivery' ), 1 );
				}
			}

			/**
			 * This hook is used to alter the product local pickup refund order statuses.
			 *
			 * @since 3.9.1
			 */
			$product_local_pickup_refund_order_statuses = apply_filters(
				'dey_product_local_pickup_refund_order_statuses',
				array(
					'woocommerce_order_status_refunded',
					'woocommerce_order_status_cancelled',
					'woocommerce_order_status_failed',
				)
			);

			if ( dey_check_is_array( $product_local_pickup_refund_order_statuses ) ) {
				foreach ( $product_local_pickup_refund_order_statuses as $order_status ) {
					// Add the hooks for product local pickup slots refund.
					add_action( $order_status, array( __CLASS__, 'maybe_failed_product_local_pickup' ), 1 );
				}
			}

			/**
			 * This hook is used to alter the order delivery refund order statuses.
			 *
			 * @since 1.0
			 */
			$order_delivery_refund_statuses = apply_filters(
				'dey_order_delivery_refund_order_statuses',
				array(
					'woocommerce_order_status_refunded',
					'woocommerce_order_status_cancelled',
					'woocommerce_order_status_failed',
				)
			);

			if ( dey_check_is_array( $order_delivery_refund_statuses ) ) {
				foreach ( $order_delivery_refund_statuses as $order_status ) {
					// Add the hooks for order delivery slots refund.
					add_action( $order_status, array( __CLASS__, 'maybe_failed_order_delivery' ), 1 );
				}
			}

			/**
			 * This hook is used to alter the order local pickup refund order statuses.
			 *
			 * @since 3.9.1
			 */
			$order_local_pickup_refund_statuses = apply_filters(
				'dey_order_local_pickup_refund_order_statuses',
				array(
					'woocommerce_order_status_refunded',
					'woocommerce_order_status_cancelled',
					'woocommerce_order_status_failed',
				)
			);

			if ( dey_check_is_array( $order_local_pickup_refund_statuses ) ) {
				foreach ( $order_local_pickup_refund_statuses as $order_status ) {
					// Add the hooks for order local pickup slots refund.
					add_action( $order_status, array( __CLASS__, 'maybe_failed_order_local_pickup' ), 1 );
				}
			}

			/**
			 * This hook is used to alter the order tip refund order statuses.
			 *
			 * @since 1.0
			 */
			$order_tip_refund_statuses = apply_filters(
				'dey_order_tip_refund_order_statuses',
				array(
					'woocommerce_order_status_refunded',
					'woocommerce_order_status_cancelled',
					'woocommerce_order_status_failed',
				)
			);

			if ( dey_check_is_array( $order_tip_refund_statuses ) ) {
				foreach ( $order_tip_refund_statuses as $order_status ) {
					// Add the hooks for order tip refund.
					add_action( $order_status, array( __CLASS__, 'maybe_failed_order_tip' ), 1 );
				}
			}

			// May be custom order meta fields in email.
			add_filter( 'woocommerce_email_order_meta_fields', array( __CLASS__, 'maybe_email_custom_order_meta_fields' ), 10, 3 );

			// Maybe change the status of delivery slots to fail before deleting the order.
			add_action( 'before_delete_post', array( __CLASS__, 'before_delete_order' ), 10, 2 );

			/**
			 * This hook is used to alter the order tip refund order statuses.
			 *
			 * @since 1.0
			 */
			$order_local_pickup_location_emails = apply_filters(
				'dey_order_local_pickup_location_emails',
				array(
					'new_order',
					'cancelled_order',
					'failed_order',
				)
			);

			if ( dey_check_is_array( $order_local_pickup_location_emails ) ) {
				foreach ( $order_local_pickup_location_emails as $email ) {
					// May be send the email to the local pickup admins.
					add_action( 'woocommerce_email_recipient_' . $email, array( __CLASS__, 'maybe_send_email_local_pickup_admins' ), 10, 2 );
				}
			}
		}

		/**
		 * Maybe change the status of delivery slots to fail before deleting the order.
		 *
		 * @since 1.0.0
		 * @param int    $order_id Order ID.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function before_delete_order( $order_id, $order ) {
			// Return if the order is not an object or post type is not a shop order.
			if ( ! is_object( $order ) || 'shop_order' !== $order->post_type ) {
				return;
			}

			// Product delivery.
			self::maybe_failed_product_delivery( $order_id );
			// Product local pickup.
			self::maybe_failed_product_local_pickup( $order_id );
			// Order delivery.
			self::maybe_failed_order_delivery( $order_id );
			// Order local pickup.
			self::maybe_failed_order_local_pickup( $order_id );
			// Order tip.
			self::maybe_failed_order_tip( $order_id );
		}

		/**
		 * Create the product delivery order line item.
		 *
		 * @since 1.0.0
		 * @param array  $item Cart item.
		 * @param string $cart_item_key Cart item key.
		 * @param array  $values Values.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function create_product_order_line_item( $item, $cart_item_key, $values, $order ) {
			// Product delivery.
			self::create_product_delivery_order_line_item( $item, $cart_item_key, $values, $order );
			// Product local pickup.
			self::create_product_pickup_order_line_item( $item, $cart_item_key, $values, $order );
		}

		/**
		 * Create the product delivery order line item.
		 *
		 * @since 1.0.0
		 * @param array  $item Cart item.
		 * @param string $cart_item_key Cart item key.
		 * @param array  $values Values.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function create_product_delivery_order_line_item( $item, $cart_item_key, $values, $order ) {
			if ( ! isset( $values['dey_delivery_slots'] ) ) {
				return;
			}

			$delivery_slots = $values['dey_delivery_slots'];
			if ( ! dey_check_is_array( $delivery_slots ) ) {
				return;
			}

			$order_item_data = array(
				'_dey_delivery_date'           => isset( $delivery_slots['date'] ) ? $delivery_slots['date'] : '',
				'_dey_delivery_last_date'      => isset( $delivery_slots['last_date'] ) ? $delivery_slots['last_date'] : '',
				'_dey_delivery_price'          => isset( $delivery_slots['price'] ) ? $delivery_slots['price'] : '',
				'_dey_delivery_mode'           => isset( $delivery_slots['mode'] ) ? $delivery_slots['mode'] : '',
				'_dey_delivery_time_mode'      => isset( $delivery_slots['time_mode'] ) ? $delivery_slots['time_mode'] : '',
				'_dey_delivery_time_slot_id'   => isset( $delivery_slots['time_slot_id'] ) ? $delivery_slots['time_slot_id'] : '',
				'_dey_delivery_time_slot_from' => isset( $delivery_slots['time_slot_from'] ) ? $delivery_slots['time_slot_from'] : '',
				'_dey_delivery_time_slot_to'   => isset( $delivery_slots['time_slot_to'] ) ? $delivery_slots['time_slot_to'] : '',
				'_dey_delivery_special_day_id' => isset( $delivery_slots['special_day_id'] ) ? $delivery_slots['special_day_id'] : '',
				'_dey_delivery_charge_details' => isset( $delivery_slots['price_details'] ) ? $delivery_slots['price_details'] : array(),
			);

			switch ( $delivery_slots['mode'] ) {
				// Expected delivery date.
				case '2':
					// Delivery slots date.
					$order_item_data[ dey_get_product_delivery_expected_info_label() ] = DEY_Date_Time::get_wp_format_datetime( $delivery_slots['date'], 'date' );
					break;

				// Calender.
				default:
					// Delivery slots date.
					$order_item_data[ dey_get_product_delivery_date_label() ] = dey_format_delivery_date( $delivery_slots['date'], $delivery_slots['time_mode'] );

					if ( isset( $delivery_slots['time_slot_id'] ) && ! empty( $delivery_slots['time_slot_id'] ) ) {
						// Delivery time slots.
						$order_item_data[ dey_get_product_delivery_time_slot_label() ] = dey_format_product_delivery_time_slots( $delivery_slots['time_slot_from'], $delivery_slots['time_slot_to'], $delivery_slots['time_slot_id'] );
					}

					if ( ! empty( $delivery_slots['price'] ) || 'yes' !== get_post_meta( $item['product_id'], 'dey_delivery_fee_display_hide', true ) ) {
						$order_item_data[ dey_get_product_delivery_fee_label() ] = dey_price( $delivery_slots['price'] );
					}

					// Same day fee.
					if ( isset( $delivery_slots['price_details']['same_day'] ) && ! empty( $delivery_slots['price_details']['same_day'] ) ) {
						$order_item_data[ dey_get_product_delivery_same_day_fee_label() ] = dey_price( $delivery_slots['price_details']['same_day'] );
					}

					// Next day fee.
					if ( isset( $delivery_slots['price_details']['next_day'] ) && ! empty( $delivery_slots['price_details']['next_day'] ) ) {
						$order_item_data[ dey_get_product_delivery_next_day_fee_label() ] = dey_price( $delivery_slots['price_details']['next_day'] );
					}

					break;
			}

			/**
			 * This hook is used to alter the product delivery order item data.
			 *
			 * @since 1.0
			 */
			$order_item_data = apply_filters( 'dey_product_delivery_order_item_data', $order_item_data, $item, $cart_item_key, $values, $order );

			if ( dey_check_is_array( $order_item_data ) ) {
				foreach ( $order_item_data as $key => $value ) {
					// Update the order item meta.
					$item->add_meta_data( $key, $value );
				}
			}
		}

		/**
		 * Create the product pickup order line item.
		 *
		 * @since 3.5.0
		 * @param object $item Order item object.
		 * @param string $cart_item_key Cart item key.
		 * @param array  $values Values.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function create_product_pickup_order_line_item( $item, $cart_item_key, $values, $order ) {
			if ( ! isset( $values['dey_product_pickup_slots'] ) ) {
				return;
			}

			$pickup_slots = $values['dey_product_pickup_slots'];
			if ( ! dey_check_is_array( $pickup_slots ) ) {
				return;
			}

			$order_item_data = array(
				'_dey_product_pickup_location_id'    => isset( $pickup_slots['location_id'] ) ? $pickup_slots['location_id'] : '',
				'_dey_product_pickup_date'           => isset( $pickup_slots['date'] ) ? $pickup_slots['date'] : '',
				'_dey_product_pickup_price'          => isset( $pickup_slots['price'] ) ? $pickup_slots['price'] : '',
				'_dey_product_pickup_same_day_price' => isset( $pickup_slots['same_day_price'] ) ? $pickup_slots['same_day_price'] : '',
				'_dey_product_pickup_next_day_price' => isset( $pickup_slots['next_day_price'] ) ? $pickup_slots['next_day_price'] : '',
				'_dey_product_pickup_time_mode'      => isset( $pickup_slots['time_mode'] ) ? $pickup_slots['time_mode'] : '',
				'_dey_product_pickup_time_slot_id'   => isset( $pickup_slots['time_slot_id'] ) ? $pickup_slots['time_slot_id'] : '',
				'_dey_product_pickup_time_slot_from' => isset( $pickup_slots['time_slot_from'] ) ? $pickup_slots['time_slot_from'] : '',
				'_dey_product_pickup_time_slot_to'   => isset( $pickup_slots['time_slot_to'] ) ? $pickup_slots['time_slot_to'] : '',
				'_dey_product_pickup_special_day_id' => isset( $pickup_slots['special_day_id'] ) ? $pickup_slots['special_day_id'] : '',
				'_dey_product_pickup_charge_details' => isset( $pickup_slots['price_details'] ) ? $pickup_slots['price_details'] : array(),
			);

			// Pickup slots date.
			$order_item_data[ dey_get_product_pickup_date_label() ] = dey_format_pickup_date( $pickup_slots['date'], $pickup_slots['time_mode'] );

			// Pickup time slots.
			if ( isset( $pickup_slots['time_slot_id'] ) && ! empty( $pickup_slots['time_slot_id'] ) ) {
				$order_item_data[ dey_get_product_pickup_time_slot_label() ] = dey_format_product_pickup_time_slots( $pickup_slots['time_slot_from'], $pickup_slots['time_slot_to'], $pickup_slots['time_slot_id'] );
			}

			// Pickup Fee.
			if ( ! empty( $pickup_slots['price'] ) || 'yes' !== get_post_meta( $item['product_id'], 'dey_pickup_fee_display_hide', true ) ) {
				$order_item_data[ dey_get_product_pickup_fee_label() ] = dey_price( $pickup_slots['price'] );
			}

			// Same day fee.
			if ( isset( $pickup_slots['price_details']['same_day'] ) && ! empty( $pickup_slots['price_details']['same_day'] ) ) {
				$order_item_data[ dey_get_product_pickup_same_day_fee_label() ] = dey_price( $pickup_slots['price_details']['same_day'] );
			}

			// Next day fee.
			if ( isset( $pickup_slots['price_details']['next_day'] ) && ! empty( $pickup_slots['price_details']['next_day'] ) ) {
				$order_item_data[ dey_get_product_pickup_next_day_fee_label() ] = dey_price( $pickup_slots['price_details']['next_day'] );
			}

			// Pickup Location.
			if ( isset( $pickup_slots['location_id'] ) && $pickup_slots['location_id'] ) {
				$order_item_data[ dey_get_product_pickup_location_field_label() ] = dey_get_product_pickup_location_to_display( $item['product_id'], $pickup_slots['location_id'] );
			}

			/**
			 * This hook is used to alter the product pickup order item data.
			 *
			 * @since 3.5.0
			 */
			$order_item_data = apply_filters( 'dey_product_pickup_order_item_data', $order_item_data, $item, $cart_item_key, $values, $order );
			if ( dey_check_is_array( $order_item_data ) ) {
				foreach ( $order_item_data as $key => $value ) {
					// Update the order item meta.
					$item->add_meta_data( $key, $value );
				}
			}
		}

		/**
		 * May be create the delivery slots meta based on short code/ block checkout.
		 *
		 * @since 1.0.0
		 * @param int/object $order_id Order ID.
		 * @return void
		 * */
		public static function maybe_update_delivery_slots_meta( $order_id ) {
			$order_id = is_object( $order_id ) ? $order_id->get_id() : $order_id;
			$order    = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			self::create_product_delivery_slots( $order ); // Product delivery.
			self::create_product_local_pickup_slots( $order ); // Product local pickup.
			self::create_order_delivery_slots( $order ); // Order delivery.
			self::create_order_local_pickup_slots( $order ); // Order local pickup.
			self::create_order_tip( $order ); // Order tip.

			$order->save();
		}

		/**
		 * Create the product delivery slots.
		 *
		 * @since 1.0.0
		 * @param object $order Order object.
		 * @return void
		 * */
		public static function create_product_delivery_slots( &$order ) {
			$total_price          = 0;
			$update_label         = false;
			$product_delivery_ids = array();

			foreach ( $order->get_items() as $key => $value ) {
				// Check the product is having delivery date.
				if ( ! isset( $value['dey_delivery_date'] ) ) {
					continue;
				}

				// Return if the product delivery slot is disabled.
				if ( '2' != get_post_meta( $value['product_id'], 'dey_delivery_slot_type', true ) ) {
					continue;
				}

				$product_id = $value['variation_id'] ? $value['variation_id'] : $value['product_id'];
				$product    = wc_get_product( $product_id );

				$same_day_price = isset( $value['dey_delivery_charge_details']['same_day'] ) ? $value['dey_delivery_charge_details']['same_day'] : 0;
				$next_day_price = isset( $value['dey_delivery_charge_details']['next_day'] ) ? $value['dey_delivery_charge_details']['next_day'] : 0;

				/**
				 * This hook is used to alter the product delivery data.
				 *
				 * @since 3.8.0
				 * @param array Product delivery data.
				 * @param array $value Order item.
				 */
				$meta_data = apply_filters(
					'dey_get_product_delivery_data',
					array(
						'dey_order_id'                => $order->get_id(),
						'dey_quantity'                => $value['quantity'],
						'dey_delivery_date'           => $value['dey_delivery_date'],
						'dey_delivery_last_date'      => $value['dey_delivery_last_date'],
						'dey_delivery_date_gmt'       => $value['dey_delivery_last_date'] ? DEY_Date_Time::get_gmt_format_datetime_from_wp( $value['dey_delivery_last_date'], 'Y-m-d' ) : '',
						'dey_delivery_mode'           => $value['dey_delivery_mode'],
						'dey_delivery_time_mode'      => $value['dey_delivery_time_mode'],
						'dey_delivery_charge'         => $value['dey_delivery_price'] + $same_day_price + $next_day_price,
						'dey_time_slot_from'          => $value['dey_delivery_time_slot_from'],
						'dey_time_slot_to'            => $value['dey_delivery_time_slot_to'],
						'dey_time_slot_id'            => $value['dey_delivery_time_slot_id'],
						'dey_special_day_id'          => $value['dey_delivery_special_day_id'],
						'dey_user_name'               => $order->get_formatted_billing_full_name(),
						'dey_user_email'              => $order->get_billing_email(),
						'dey_user_id'                 => $order->get_customer_id(),
						'dey_timezone'                => DEY_Date_Time::get_wp_timezone(),
						'dey_currency'                => $order->get_currency(),
						'dey_delivery_charge_details' => $value['dey_delivery_charge_details'],
					),
					$value
				);

				$post_data = array(
					'post_parent' => dey_get_product_id( $product_id ),
				);
				// Create a product delivery slot.
				$product_delivery_id    = dey_create_new_product_delivery( $meta_data, $post_data );
				$product_delivery_ids[] = $product_delivery_id;

				// Update the product delivery ID in order item meta.
				wc_add_order_item_meta( $key, '_dey_product_delivery_id', $product_delivery_id );

				// Update the order usage count.
				dey_update_product_delivery_time_slot_order_usage_count( $product, $value['dey_delivery_time_slot_id'], $value['dey_delivery_date'] );
				dey_update_product_delivery_special_day_order_usage_count( $product, $value['dey_delivery_special_day_id'] );

				$update_label = true;
			}

			if ( 'yes' === get_option( 'dey_advanced_order_notes_enabled', 'no' ) ) {
				self::set_product_delivery_order_notes( $product_delivery_ids, $order );
			}

			if ( $update_label ) {
				$labels = array(
					'delivery_date'     => dey_get_product_delivery_date_label(),
					'time_slot'         => dey_get_product_delivery_time_slot_label(),
					'delivery_fee'      => dey_get_product_delivery_fee_label(),
					'expected_delivery' => dey_get_product_delivery_expected_info_label(),
				);
				// Update the order item meta.
				$order->update_meta_data( 'dey_product_delivery_labels', $labels );
			}
		}

		/**
		 * Create the product local pickup slots.
		 *
		 * @since 3.5.0
		 * @param object $order Order object.
		 * @return void
		 * */
		public static function create_product_local_pickup_slots( &$order ) {
			$total_price        = 0;
			$update_label       = false;
			$product_pickup_ids = array();

			foreach ( $order->get_items() as $key => $value ) {
				// Check the product is having pickup date.
				if ( ! isset( $value['dey_product_pickup_date'] ) ) {
					continue;
				}

				$product_id         = $value['variation_id'] ? $value['variation_id'] : $value['product_id'];
				$product            = wc_get_product( $product_id );
				$pickup_location_id = isset( $value['dey_product_pickup_location_id'] ) ? wc_clean( wp_unslash( $value['dey_product_pickup_location_id'] ) ) : '';

				$same_day_price = isset( $value['dey_product_pickup_charge_details']['same_day'] ) ? $value['dey_product_pickup_charge_details']['same_day'] : 0;
				$next_day_price = isset( $value['dey_product_pickup_charge_details']['next_day'] ) ? $value['dey_product_pickup_charge_details']['next_day'] : 0;

				/**
				 * This hook is used to alter the product pickup data.
				 *
				 * @since 3.8.0
				 * @param array Product pickup data.
				 * @param array $value Order item.
				 */
				$meta_data = apply_filters(
					'dey_get_product_pickup_data',
					array(
						'dey_order_id'              => $order->get_id(),
						'dey_quantity'              => $value['quantity'],
						'dey_pickup_date'           => $value['dey_product_pickup_date'],
						'dey_pickup_last_date'      => $value['dey_pickup_last_date'],
						'dey_pickup_date_gmt'       => $value['dey_pickup_last_date'] ? DEY_Date_Time::get_gmt_format_datetime_from_wp( $value['dey_pickup_last_date'], 'Y-m-d' ) : '',
						'dey_pickup_time_mode'      => $value['dey_product_pickup_time_mode'],
						'dey_pickup_charge'         => $value['dey_product_pickup_price'] + $same_day_price + $next_day_price,
						'dey_time_slot_from'        => $value['dey_product_pickup_time_slot_from'],
						'dey_time_slot_to'          => $value['dey_product_pickup_time_slot_to'],
						'dey_time_slot_id'          => $value['dey_product_pickup_time_slot_id'],
						'dey_special_day_id'        => $value['dey_product_pickup_special_day_id'],
						'dey_user_name'             => $order->get_formatted_billing_full_name(),
						'dey_user_email'            => $order->get_billing_email(),
						'dey_user_id'               => $order->get_customer_id(),
						'dey_timezone'              => DEY_Date_Time::get_wp_timezone(),
						'dey_currency'              => $order->get_currency(),
						'dey_pickup_location'       => self::prepare_product_pickup_location_data( $value['product_id'], $pickup_location_id, $value['dey_product_pickup_date'] ),
						'dey_pickup_charge_details' => $value['dey_product_pickup_charge_details'],
					),
					$value
				);

				$post_data = array(
					'post_parent' => dey_get_product_id( $product_id ),
				);
				// Create a product pickup slot.
				$product_pickup_id    = dey_create_new_product_local_pickup( $meta_data, $post_data );
				$product_pickup_ids[] = $product_pickup_id;

				// Update the product pickup ID in order item meta.
				wc_add_order_item_meta( $key, '_dey_product_pickup_id', $product_pickup_id );

				// Update the order usage count.
				dey_update_product_pickup_time_slot_order_usage_count( $product, $value['dey_pickup_time_slot_id'], $value['dey_product_pickup_date'] );
				dey_update_product_pickup_special_day_order_usage_count( $product, $value['dey_pickup_special_day_id'] );

				$update_label = true;
			}

			if ( 'yes' === get_option( 'dey_advanced_order_notes_enabled', 'no' ) ) {
				self::set_product_pickup_order_notes( $product_pickup_ids, $order );
			}

			if ( $update_label ) {
				$labels = array(
					'pickup_date' => dey_get_product_pickup_date_label(),
					'time_slot'   => dey_get_product_pickup_time_slot_label(),
					'pickup_fee'  => dey_get_product_pickup_fee_label(),
				);
				// Update the order item meta.
				$order->update_meta_data( 'dey_product_pickup_labels', $labels );
			}
		}

		/**
		 * Set product delivery order notes.
		 *
		 * @since 2.6.0
		 * @param int    $product_delivery_ids Product delivery IDs.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function set_product_delivery_order_notes( $product_delivery_ids, $order ) {
			$product_delivery_order_notes = array();
			foreach ( $product_delivery_ids as $product_delivery_id ) {
				$product_delivery = dey_get_product_delivery( $product_delivery_id );

				$product_note  = '<p><b>' . __( 'Product Name', 'delivery-slots-for-woocommerce' ) . ':</b> ' . $product_delivery->get_product()->get_name() . '</p>';
				$product_note .= '<p><b>' . __( 'Product Quantity', 'delivery-slots-for-woocommerce' ) . ':</b>  ' . $product_delivery->get_quantity() . '</p>';
				$product_note .= '<p><b>' . dey_get_product_delivery_time_slot_label() . ':</b> ' . $product_delivery->get_formatted_time_slots() . '</p>';
				$product_note .= '<p><b>' . dey_get_product_delivery_fee_label() . ':</b> ' . $product_delivery->get_formatted_delivery_charge() . '</p>';
				$product_note .= '<p><b>' . dey_get_product_delivery_date_label() . ':</b> ' . $product_delivery->get_delivery_date() . '</p>';
				$product_note .= '</br>';

				$product_delivery_order_notes[] = $product_note;
			}

			// Add notes if product delivery details in the order.
			if ( dey_check_is_array( $product_delivery_order_notes ) ) {
				$product_delivery_note  = '<p><b>' . __( 'Product Delivery Details', 'delivery-slots-for-woocommerce' ) . '</b></p></br>';
				$product_delivery_note .= implode( '', $product_delivery_order_notes );
				$order->add_order_note( $product_delivery_note );
			}
		}

		/**
		 * Set product pickup order notes.
		 *
		 * @since 3.5.0
		 * @param array  $product_pickup_ids Product pickup ID's.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function set_product_pickup_order_notes( $product_pickup_ids, $order ) {
			$product_pickup_order_notes = array();
			foreach ( $product_pickup_ids as $product_pickup_id ) {
				$product_pickup = dey_get_product_local_pickup( $product_pickup_id );

				$product_note  = '<p><b>' . __( 'Product Name', 'delivery-slots-for-woocommerce' ) . ':</b> ' . $product_pickup->get_product()->get_name() . '</p>';
				$product_note .= '<p><b>' . __( 'Product Quantity', 'delivery-slots-for-woocommerce' ) . ':</b>  ' . $product_pickup->get_quantity() . '</p>';
				$product_note .= '<p><b>' . dey_get_product_pickup_time_slot_label() . ':</b> ' . $product_pickup->get_formatted_time_slots() . '</p>';
				$product_note .= '<p><b>' . dey_get_product_pickup_fee_label() . ':</b> ' . $product_pickup->get_formatted_pickup_charge() . '</p>';
				$product_note .= '<p><b>' . dey_get_product_pickup_date_label() . ':</b> ' . $product_pickup->get_pickup_date() . '</p>';
				$product_note .= '</br>';

				$product_pickup_order_notes[] = $product_note;
			}

			// Add notes if product pickup details in the order.
			if ( dey_check_is_array( $product_pickup_order_notes ) ) {
				$product_pickup_note  = '<p><b>' . __( 'Product Pickup Details', 'delivery-slots-for-woocommerce' ) . '</b></p></br>';
				$product_pickup_note .= implode( '', $product_pickup_order_notes );
				$order->add_order_note( $product_pickup_note );
			}
		}

		/**
		 * Create the order delivery slots.
		 *
		 * @since 1.0.0
		 * @param object $order Order object.
		 * @return void
		 */
		public static function create_order_delivery_slots( &$order ) {
			if ( ! dey_is_order_delivery() ) {
				return;
			}

			$order_scheduler_type = isset( $_REQUEST['dey_order_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_scheduler_type'] ) ) : '';
			if ( dey_is_order_scheduler_type() && 'order-delivery' !== $order_scheduler_type ) {
				return;
			}

			$delivery_dates = self::prepare_order_delivery_dates();
			if ( ! isset( $delivery_dates['first_date'] ) || empty( $delivery_dates['first_date'] ) ) {
				return;
			}

			$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
			$post_meta      = array(
				'dey_scheduler_rule_id'       => $scheduler_rule->get_id(),
				'dey_product_ids'             => self::prepare_order_product_ids( $order ),
				'dey_delivery_date'           => $delivery_dates['first_date'],
				'dey_delivery_date_gmt'       => $delivery_dates['first_date_gmt'],
				'dey_delivery_last_date'      => $delivery_dates['last_date'],
				'dey_delivery_last_date_gmt'  => $delivery_dates['last_date_gmt'],
				'dey_delivery_charge'         => self::prepare_order_delivery_charge( $delivery_dates['first_date'] ),
				'dey_delivery_mode'           => $scheduler_rule->get_delivery_slot_mode(),
				'dey_delivery_time_mode'      => $scheduler_rule->get_delivery_time_mode(),
				'dey_user_id'                 => $order->get_customer_id(),
				'dey_user_name'               => $order->get_formatted_billing_full_name(),
				'dey_user_email'              => $order->get_billing_email(),
				'dey_timezone'                => DEY_Date_Time::get_wp_timezone(),
				'dey_currency'                => $order->get_currency(),
				'dey_delivery_charge_details' => array( 'weekday' => self::prepare_order_delivery_charge( $delivery_dates['first_date'] ) ),
			);

			$post_meta = self::prepare_order_delivery_time_slots( $post_meta );
			$post_meta = self::prepare_order_delivery_special_day( $post_meta, $delivery_dates['first_date'] );
			$post_meta = self::prepare_order_delivery_same_day( $post_meta, $delivery_dates['first_date'] );
			$post_meta = self::prepare_order_delivery_next_day( $post_meta, $delivery_dates['first_date'] );

			/**
			 * This hook is used to alter the order delivery data.
			 *
			 * @since 3.8.0
			 * @param array $post_meta Order delivery data
			 * @param array $delivery_dates Delivery dates.
			 */
			$post_meta   = apply_filters( 'dey_order_delivery_data', $post_meta, $delivery_dates );
			$delivery_id = dey_create_new_order_delivery( $post_meta, array( 'post_parent' => $order->get_id() ) );

			$order_delivery = dey_get_order_delivery( $delivery_id );
			if ( 'yes' === get_option( 'dey_advanced_order_notes_enabled', 'no' ) ) {
				self::set_order_delivery_order_notes( $order_delivery, $order );
			}

			if ( $order_delivery->exists() ) {
				$delivery_data = array(
					'dey_order_delivery_id'  => $delivery_id,
					'dey_delivery_date'      => $order_delivery->get_delivery_date(),
					'dey_delivery_last_date' => $order_delivery->get_delivery_last_date(),
					'dey_delivery_charge'    => $order_delivery->get_delivery_charge(),
					'dey_delivery_mode'      => $order_delivery->get_delivery_mode(),
					'dey_delivery_time_mode' => $order_delivery->get_delivery_time_mode(),
					'dey_time_slot_from'     => $order_delivery->get_time_slot_from(),
					'dey_time_slot_to'       => $order_delivery->get_time_slot_to(),                    
					'dey_time_slot_id'       => $order_delivery->get_time_slot_id(),
					'dey_time_slot_mode'     => $order_delivery->get_time_slot_mode(),
					'dey_special_day_id'     => $order_delivery->get_special_day_id(),
					'dey_special_day_mode'   => $order_delivery->get_special_day_mode(),
				);

				foreach ( $delivery_data as $delivery_key => $delivery_value ) {
					// Update the order item meta.
					$order->update_meta_data( $delivery_key, $delivery_value );
				}

				// Update the order time slot usage count.
				dey_update_order_time_slot_usage_count(
					array(
						'mode'              => $order_delivery->get_time_slot_mode(),
						'date'              => $order_delivery->get_delivery_date(),
						'time_slot_id'      => $order_delivery->get_time_slot_id(),
						'scheduler_rule_id' => $order_delivery->get_scheduler_rule_id(),
					)
				);
				// Update the order special day usage count.
				dey_update_order_special_day_usage_count(
					array(
						'mode'              => $order_delivery->get_special_day_mode(),
						'special_day_id'    => $order_delivery->get_special_day_id(),
						'scheduler_rule_id' => $order_delivery->get_scheduler_rule_id(),
					)
				);
			}
		}

		/**
		 * Set order delivery order notes.
		 *
		 * @since 2.6
		 * @param object $order_delivery Order delivery object.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function set_order_delivery_order_notes( $order_delivery, $order ) {
			$product_names = self::prepare_order_product_ids( $order, 'product_name' );
			$product_names = dey_check_is_array( $product_names ) ? $product_names : '-';

			$order_note  = '<p><b>' . __( 'Order Delivery Details', 'delivery-slots-for-woocommerce' ) . '</b></p></br>';
			$order_note .= '<p><b>' . dey_get_order_delivery_products_label() . ':</b> ' . implode( ',', $product_names ) . '</p>';
			$order_note .= '<p><b>' . dey_get_order_delivery_date_label() . ':</b> ' . $order_delivery->get_delivery_date() . '</p>';
			$order_note .= '<p><b>' . dey_get_order_delivery_time_slot_label() . ':</b> ' . $order_delivery->get_formatted_time_slots() . '</p>';
			$order_note .= '<p><b>' . dey_get_order_delivery_fee_label() . ':</b> ' . $order_delivery->get_formatted_delivery_charge() . '</p>';

			// Add notes if order delivery details in the order.
			$order->add_order_note( $order_note );
		}

		/**
		 * Create the order local pickup slots.
		 *
		 * @param object $order Order object.
		 */
		public static function create_order_local_pickup_slots( &$order ) {
			if ( ! dey_is_order_local_pickup() ) {
				return;
			}

			$order_scheduler_type = isset( $_REQUEST['dey_order_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_scheduler_type'] ) ) : '';
			if ( dey_is_order_scheduler_type() && 'order-local-pickup' !== $order_scheduler_type ) {
				return;
			}

			$order_local_pickup_handler = dey_get_order_local_pickup_handler();
			if ( ! is_object( $order_local_pickup_handler ) ) {
				return;
			}

			$local_pickup_dates = self::prepare_order_pickup_dates();
			if ( ! isset( $local_pickup_dates['first_date'] ) || empty( $local_pickup_dates['first_date'] ) ) {
				return;
			}

			$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
			if ( ! is_object( $scheduler_rule ) ) {
				return;
			}

			$pickup_location_data = self::prepare_pickup_location_data();

			$pickup_location_id = isset( $_REQUEST['dey_pickup_location'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_location'] ) ) : false;
			$pickup_location    = dey_get_pickup_location( $pickup_location_id );
			$consideration_mode = is_object( $pickup_location ) && '2' === $pickup_location->get_pickup_mode() ? 'pickup_location' : 'scheduler_rule';

			$post_meta = array(
				'dey_scheduler_rule_id'     => $scheduler_rule->get_id(),
				'dey_pickup_location_id'    => $pickup_location_data['id'],
				'dey_pickup_address'        => $pickup_location_data['address'],
				'dey_product_ids'           => self::prepare_order_product_ids( $order ),
				'dey_pickup_date'           => $local_pickup_dates['first_date'],
				'dey_pickup_date_gmt'       => $local_pickup_dates['first_date_gmt'],
				'dey_pickup_charge'         => self::prepare_order_pickup_charge( $local_pickup_dates['first_date'], $order_local_pickup_handler ),
				'dey_pickup_time_mode'      => 'pickup_location' === $consideration_mode ? $pickup_location->get_pickup_time_mode() : $scheduler_rule->get_pickup_time_mode(),
				'dey_user_id'               => $order->get_customer_id(),
				'dey_user_name'             => $order->get_formatted_billing_full_name(),
				'dey_user_email'            => $order->get_billing_email(),
				'dey_timezone'              => DEY_Date_Time::get_wp_timezone(),
				'dey_currency'              => $order->get_currency(),
				'dey_pickup_charge_details' => array( 'weekday' => self::prepare_order_pickup_charge( $local_pickup_dates['first_date'], $order_local_pickup_handler ) ),
				'dey_consideration_mode'    => $consideration_mode,
			);

			$post_meta = self::prepare_order_local_pickup_time_slots( $post_meta, $pickup_location );
			$post_meta = self::prepare_order_local_pickup_special_day( $post_meta, $local_pickup_dates['first_date'], $order_local_pickup_handler );
			$post_meta = self::prepare_order_local_pickup_same_day( $post_meta, $local_pickup_dates['first_date'], $order_local_pickup_handler );
			$post_meta = self::prepare_order_local_pickup_next_day( $post_meta, $local_pickup_dates['first_date'], $order_local_pickup_handler );

			/**
			 * This hook is used to alter the order local pickup data.
			 *
			 * @since 3.8.0
			 * @param array $post_meta Order local pickup data
			 * @param array $local_pickup_dates Local pickup dates.
			 */
			$post_meta       = apply_filters( 'dey_order_local_pickup_data', $post_meta, $local_pickup_dates );
			$local_pickup_id = dey_create_new_order_local_pickup( $post_meta, array( 'post_parent' => $order->get_id() ) );

			$order_local_pickup = dey_get_order_local_pickup( $local_pickup_id );
			if ( 'yes' === get_option( 'dey_advanced_order_notes_enabled', 'no' ) ) {
				self::set_order_local_pickup_order_notes( $order_local_pickup, $order );
			}

			if ( $order_local_pickup->exists() ) {
				$local_pickup_data = array(
					'dey_order_local_pickup_id' => $local_pickup_id,
					'dey_pickup_location_id'    => $order_local_pickup->get_pickup_location_id(),
					'dey_pickup_address'        => $order_local_pickup->get_formatted_address(),
					'dey_pickup_date'           => $order_local_pickup->get_pickup_date(),
					'dey_pickup_charge'         => $order_local_pickup->get_pickup_charge(),
					'dey_pickup_time_mode'      => $order_local_pickup->get_pickup_time_mode(),
					'dey_time_slot_from'        => $order_local_pickup->get_time_slot_from(),
					'dey_time_slot_to'          => $order_local_pickup->get_time_slot_to(),
					'dey_time_slot_id'          => $order_local_pickup->get_time_slot_id(),
					'dey_time_slot_mode'        => $order_local_pickup->get_time_slot_mode(),
					'dey_special_day_id'        => $order_local_pickup->get_special_day_id(),
					'dey_special_day_mode'      => $order_local_pickup->get_special_day_mode(),
				);

				foreach ( $local_pickup_data as $local_pickup_key => $local_pickup_value ) {
					// Update the order item meta.
					$order->update_meta_data( $local_pickup_key, $local_pickup_value );
				}

				// Update the order local pickup time slot usage count.
				dey_update_order_time_slot_usage_count(
					array(
						'mode'               => $order_local_pickup->get_time_slot_mode(),
						'date'               => $order_local_pickup->get_pickup_date(),
						'time_slot_id'       => $order_local_pickup->get_time_slot_id(),
						'scheduler_rule_id'  => $order_local_pickup->get_scheduler_rule_id(),
						'pickup_location_id' => $order_local_pickup->get_pickup_location_id(),
					)
				);
				// Update the order special day usage count.
				dey_update_order_special_day_usage_count(
					array(
						'mode'               => $order_local_pickup->get_special_day_mode(),
						'special_day_id'     => $order_local_pickup->get_special_day_id(),
						'scheduler_rule_id'  => $order_local_pickup->get_scheduler_rule_id(),
						'pickup_location_id' => $order_local_pickup->get_pickup_location_id(),
					)
				);
			}
		}

		/**
		 * Set order local pickup order notes.
		 *
		 * @since 2.6.0
		 * @param object $order_local_pickup Order local pickup object.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function set_order_local_pickup_order_notes( $order_local_pickup, $order ) {
			$product_names = self::prepare_order_product_ids( $order, 'product_name' );
			$product_names = dey_check_is_array( $product_names ) ? $product_names : '-';

			$order_note  = '<p><b>' . __( 'Local Pickup Details', 'delivery-slots-for-woocommerce' ) . '</b></p></br>';
			$order_note .= '<p><b>' . dey_get_order_pickup_products_label() . ':</b> ' . implode( ',', $product_names ) . '</p>';
			$order_note .= '<p><b>' . dey_get_order_pickup_location_label() . ':</b> ' . $order_local_pickup->get_formatted_address() . '</p>';
			$order_note .= '<p><b>' . dey_get_order_pickup_date_label() . ':</b> ' . $order_local_pickup->get_pickup_date() . '</p>';
			$order_note .= '<p><b>' . dey_get_order_local_pickup_time_slot_label() . ':</b> ' . $order_local_pickup->get_formatted_time_slots() . '</p>';
			$order_note .= '<p><b>' . dey_get_order_pickup_fee_label() . ':</b> ' . $order_local_pickup->get_formatted_pickup_charge() . '</p>';

			// Add notes if local pickup details in the order.
			$order->add_order_note( $order_note );
		}

		/**
		 * Prepare the order delivery charge.
		 *
		 * @since 1.0.0
		 * @param string $date Selected date.
		 * @return float|int
		 * */
		public static function prepare_order_delivery_charge( $date ) {
			$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
			if ( '2' == $scheduler_rule->get_delivery_slot_mode() ) {
				return 0;
			}

			$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$date_object            = DEY_Date_Time::get_date_time_object( $date );

			return $order_delivery_handler->get_weekday_price( $date_object );
		}

		/**
		 * Prepare the order pickup charge.
		 *
		 * @since 3.0.0
		 * @param string $date Date.
		 * @param object $order_local_pickup_handler Order local pickup handler object.
		 * @return float|int
		 * */
		public static function prepare_order_pickup_charge( $date, $order_local_pickup_handler ) {
			$date_object = DEY_Date_Time::get_date_time_object( $date );

			return $order_local_pickup_handler->get_weekday_price( $date_object );
		}

		/**
		 * Prepare the order product IDs.
		 *
		 * @since 1.0.0
		 * @param object $order Order object.
		 * @param string $return Whether to return product Ids or product name.
		 * @return array
		 */
		public static function prepare_order_product_ids( &$order, $return = 'product_id' ) {
			$product_ids = array();

			foreach ( $order->get_items() as $key => $value ) {
				if ( isset( $value['dey_delivery_mode'] ) || isset( $value['dey_product_pickup_date'] ) ) {
					continue;
				}

				if ( 'product_name' === $return ) {
					$product_ids[] = $value->get_product()->get_name();
					continue;
				}

				$product_id                 = ! empty( $value['variation_id'] ) ? $value['variation_id'] : $value['product_id'];
				$product_ids[ $product_id ] = isset( $product_ids[ $product_id ] ) ? $value['quantity'] + $product_ids[ $product_id ] : $value['quantity'];
			}

			return $product_ids;
		}

		/**
		 * Prepare the order delivery date.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function prepare_order_delivery_dates() {
			$first_date     = '';
			$first_date_gmt = '';
			$last_date      = '';
			$last_date_gmt  = '';
			$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
			switch ( $scheduler_rule->get_delivery_slot_mode() ) {
				// Expected delivery date.
				case '2':
					$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
					$first_date             = $order_delivery_handler->get_expected_first_date();
					$last_date              = $order_delivery_handler->get_expected_last_date();
					$first_date_gmt         = DEY_Date_Time::get_gmt_format_datetime_from_wp( $first_date, 'Y-m-d' );
					$last_date_gmt          = DEY_Date_Time::get_gmt_format_datetime_from_wp( $last_date, 'Y-m-d' );

					break;

				default:
					// Calender.
					if ( isset( $_REQUEST['dey_delivery_date'] ) && '' !== wc_clean( wp_unslash( $_REQUEST['dey_delivery_date'] ) ) ) {
						$first_date     = wc_clean( wp_unslash( $_REQUEST['dey_delivery_date'] ) );
						$first_date_gmt = DEY_Date_Time::get_gmt_format_datetime_from_wp( $first_date, 'Y-m-d' );
					}
					break;
			}

			$delivery_dates = array(
				'first_date'     => $first_date,
				'last_date'      => $last_date,
				'first_date_gmt' => $first_date_gmt,
				'last_date_gmt'  => $last_date_gmt,
			);

			return $delivery_dates;
		}

		/**
		 * Prepare the order pickup date.
		 *
		 * @since 3.0.0
		 * @return array
		 * */
		public static function prepare_order_pickup_dates() {
			$first_date     = '';
			$first_date_gmt = '';
			$last_date      = '';
			$last_date_gmt  = '';

			// Calender.
			if ( isset( $_REQUEST['dey_local_pickup_date'] ) && '' !== wc_clean( wp_unslash( $_REQUEST['dey_local_pickup_date'] ) ) ) {
				$first_date     = wc_clean( wp_unslash( $_REQUEST['dey_local_pickup_date'] ) );
				$first_date_gmt = DEY_Date_Time::get_gmt_format_datetime_from_wp( $first_date, 'Y-m-d' );
			}

			$pickup_dates = array(
				'first_date'     => $first_date,
				'last_date'      => $last_date,
				'first_date_gmt' => $first_date_gmt,
				'last_date_gmt'  => $last_date_gmt,
			);

			return $pickup_dates;
		}

		/**
		 * Prepare the pickup location data.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function prepare_pickup_location_data() {
			$pickup_location_data = array(
				'id'      => '',
				'date'    => '',
				'address' => array(),
			);

			$pickup_location_id = isset( $_REQUEST['dey_pickup_location'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_pickup_location'] ) ) : '';
			$pickup_date        = isset( $_REQUEST['dey_local_pickup_date'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_local_pickup_date'] ) ) : '';

			if ( ! $pickup_location_id || ! $pickup_date ) {
				return $pickup_location_data;
			}

			$pickup_location = dey_get_pickup_location( $pickup_location_id );
			if ( ! $pickup_location->exists() ) {
				return $pickup_location_data;
			}

			$pickup_location_data = array(
				'id'      => $pickup_location_id,
				'date'    => $pickup_date,
				'address' => array(
					'address1'     => $pickup_location->get_address1(),
					'address2'     => $pickup_location->get_address2(),
					'city'         => $pickup_location->get_city(),
					'country'      => $pickup_location->get_country(),
					'pincode'      => $pickup_location->get_pincode(),
					'phone_number' => $pickup_location->get_phone_number(),
				),
			);

			return $pickup_location_data;
		}

		/**
		 * Prepare the product pickup location data.
		 *
		 * @since 3.5.0
		 * @param int        $product_id Product ID.
		 * @param int|string $pickup_location_id Pickup location ID.
		 * @return array
		 */
		public static function prepare_product_pickup_location_data( $product_id, $pickup_location_id, $pickup_date ) {
			$pickup_location_data = array(
				'id'      => '',
				'date'    => '',
				'address' => array(),
			);

			if ( ! $product_id || ! $pickup_location_id ) {
				return $pickup_location_data;
			}

			if ( '1' === dey_get_product_pickup_location_selection_type( $product_id ) ) {
				$pickup_location = dey_get_pickup_location( $pickup_location_id );
				if ( ! $pickup_location->exists() ) {
					return $pickup_location_data;
				}

				$pickup_location_data = array(
					'id'      => $pickup_location_id,
					'name'    => $pickup_location->get_name(),
					'date'    => $pickup_date,
					'address' => array(
						'address1'     => $pickup_location->get_address1(),
						'address2'     => $pickup_location->get_address2(),
						'city'         => $pickup_location->get_city(),
						'country'      => $pickup_location->get_country(),
						'pincode'      => $pickup_location->get_pincode(),
						'phone_number' => $pickup_location->get_phone_number(),
					),
				);
			} else {
				$pickup_locations = dey_get_product_pickup_locations( $product_id );
				if ( ! dey_check_is_array( $pickup_locations ) || ! isset( $pickup_locations[ $pickup_location_id ] ) || ! dey_check_is_array( $pickup_locations[ $pickup_location_id ] ) ) {
					return $pickup_location_data;
				}

				$location_data        = $pickup_locations[ $pickup_location_id ];
				$pickup_location_data = array(
					'id'           => $pickup_location_id,
					'name'         => $location_data['name'],
					'date'         => $pickup_date,
					'admin_emails' => $location_data['email_lists'],
					'address'      => array(
						'address1'     => $location_data['address1'],
						'address2'     => $location_data['address2'],
						'city'         => $location_data['city'],
						'country'      => $location_data['country'],
						'pincode'      => $location_data['pincode'],
						'phone_number' => $location_data['phone_number'],
					),
				);
			}

			return $pickup_location_data;
		}

		/**
		 * Prepare the order delivery time slots.
		 *
		 * @since 1.0.0
		 * @param array  $post_meta Post meta.
		 * @return array
		 * */
		public static function prepare_order_delivery_time_slots( $post_meta ) {
			$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
			$time_slot_id   = isset( $_REQUEST['dey_order_delivery_date_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_delivery_date_time_slots'] ) ) : '';
			if ( empty( $time_slot_id ) ) {
				return $post_meta;
			}

			$post_meta['dey_time_slot_id'] = $time_slot_id;

			if ( '1' == $scheduler_rule->get_time_slots_mode() ) { // Global level.
				$time_slot = dey_get_time_slot( $time_slot_id );
				if ( ! $time_slot->exists() ) {
					return $post_meta;
				}

				$time_slot_from = $time_slot->get_time_slot_from();
				$time_slot_to   = $time_slot->get_time_slot_to();
				$price          = floatval( $time_slot->get_price() );
				$selected_mode  = 'global';
			} else { // Rule level.
				$time_slots = $scheduler_rule->get_time_slots();
				if ( ! isset( $time_slots[ $time_slot_id ] ) ) {
					return $post_meta;
				}

				$time_slot_from = $time_slots[ $time_slot_id ]['from_time'];
				$time_slot_to   = $time_slots[ $time_slot_id ]['to_time'];
				$price          = floatval( $time_slots[ $time_slot_id ]['price'] );
				$selected_mode  = 'scheduler_rule_order_delivery';
			}

			$post_meta['dey_time_slot_from']                       = $time_slot_from;
			$post_meta['dey_time_slot_to']                         = $time_slot_to;
			$post_meta['dey_delivery_charge']                     += floatval( $price );
			$post_meta['dey_delivery_charge_details']['time_slot'] = floatval( $price );
			$post_meta['dey_time_slot_mode']                       = $selected_mode;

			return $post_meta;
		}

		/**
		 * Prepare the order local pickup time slots.
		 *
		 * @since 3.0.0
		 * @param array $post_meta Post meta.
		 * @return array
		 * */
		public static function prepare_order_local_pickup_time_slots( $post_meta, $pickup_location ) {
			$time_slot_id = isset( $_REQUEST['dey_order_local_pickup_date_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_order_local_pickup_date_time_slots'] ) ) : '';
			if ( empty( $time_slot_id ) ) {
				return $post_meta;
			}

			$scheduler_rule                = dey_get_scheduler_rule_by_shipping_method();
			$post_meta['dey_time_slot_id'] = $time_slot_id;
			if ( is_object( $pickup_location ) && 'pickup_location' === $post_meta['dey_consideration_mode'] ) {
				if ( '2' === $pickup_location->get_time_slots_mode() ) {  // Pickup location.
					$time_slots = $pickup_location->get_time_slots();
					if ( ! isset( $time_slots[ $time_slot_id ] ) ) {
						return $post_meta;
					}

					$time_slot_from = $time_slots[ $time_slot_id ]['from_time'];
					$time_slot_to   = $time_slots[ $time_slot_id ]['to_time'];
					$price          = floatval( $time_slots[ $time_slot_id ]['price'] );
					$selected_mode  = 'pickup_location_order_local_pickup';
				} else { // Global level.
					$time_slot = dey_get_time_slot( $time_slot_id );
					if ( ! $time_slot->exists() ) {
						return $post_meta;
					}

					$time_slot_from = $time_slot->get_time_slot_from();
					$time_slot_to   = $time_slot->get_time_slot_to();
					$price          = floatval( $time_slot->get_price() );
					$selected_mode  = 'global';
				}
			} elseif ( is_object( $scheduler_rule ) && '2' === $scheduler_rule->get_time_slots_mode() ) { // Scheduler rule.
				$time_slots = $scheduler_rule->get_time_slots();
				if ( ! isset( $time_slots[ $time_slot_id ] ) ) {
					return $post_meta;
				}

				$time_slot_from = $time_slots[ $time_slot_id ]['from_time'];
				$time_slot_to   = $time_slots[ $time_slot_id ]['to_time'];
				$price          = floatval( $time_slots[ $time_slot_id ]['price'] );
				$selected_mode  = 'scheduler_rule_order_local_pickup';
			} else { // Global level.
				$time_slot = dey_get_time_slot( $time_slot_id );
				if ( ! $time_slot->exists() ) {
					return $post_meta;
				}

				$time_slot_from = $time_slot->get_time_slot_from();
				$time_slot_to   = $time_slot->get_time_slot_to();
				$price          = floatval( $time_slot->get_price() );
				$selected_mode  = 'global';
			}

			$post_meta['dey_time_slot_from']                     = $time_slot_from;
			$post_meta['dey_time_slot_to']                       = $time_slot_to;
			$post_meta['dey_pickup_charge']                     += floatval( $price );
			$post_meta['dey_pickup_charge_details']['time_slot'] = floatval( $price );
			$post_meta['dey_time_slot_mode']                     = $selected_mode;

			return $post_meta;
		}

		/**
		 * Prepare the order delivery special day.
		 *
		 * @param array  $post_meta Post meta.
		 * @param string $delivery_date Delivery date.
		 * @return array
		 * */
		public static function prepare_order_delivery_special_day( $post_meta, $delivery_date ) {
			$scheduler_rule         = dey_get_scheduler_rule_by_shipping_method();
			$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$special_days           = $order_delivery_handler->get_special_dates();
			if ( ! dey_check_is_array( $special_days ) || ! isset( $special_days[ $delivery_date ] ) ) {
				return $post_meta;
			}

			$special_day = $special_days[ $delivery_date ];
			if ( ! dey_check_is_array( $special_day ) ) {
				return $post_meta;
			}

			$post_meta['dey_delivery_charge']                       += floatval( $special_day['price'] );
			$post_meta['dey_special_day_id']                         = $special_day['id'];
			$post_meta['dey_delivery_charge_details']['special_day'] = floatval( $special_day['price'] );
			$post_meta['dey_special_day_mode']                       = ( '2' === $scheduler_rule->get_special_days_mode() ) ? 'scheduler_rule_order_delivery' : 'global';

			return $post_meta;
		}

		/**
		 * Prepare the order delivery same day.
		 *
		 * @since 3.5.0
		 * @param array  $post_meta Post meta.
		 * @param string $delivery_date Delivery date.
		 * @return array
		 * */
		public static function prepare_order_delivery_same_day( $post_meta, $delivery_date ) {
			$scheduler_rule         = dey_get_scheduler_rule_by_shipping_method();
			$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$same_day_fee           = $order_delivery_handler->get_same_day_price( DEY_Date_Time::get_date_time_object( $delivery_date ) );

			$post_meta['dey_delivery_charge']                    += floatval( $same_day_fee );
			$post_meta['dey_delivery_charge_details']['same_day'] = floatval( $same_day_fee );

			return $post_meta;
		}

		/**
		 * Prepare the order delivery next day.
		 *
		 * @since 3.5.0
		 * @param array  $post_meta Post meta.
		 * @param string $delivery_date Delivery date.
		 * @return array
		 * */
		public static function prepare_order_delivery_next_day( $post_meta, $delivery_date ) {
			$scheduler_rule                    = dey_get_scheduler_rule_by_shipping_method();
			$order_delivery_handler            = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$next_day_fee                      = $order_delivery_handler->get_next_day_price( DEY_Date_Time::get_date_time_object( $delivery_date ) );
			$post_meta['dey_delivery_charge'] += floatval( $next_day_fee );
			$post_meta['dey_delivery_charge_details']['next_day'] = floatval( $next_day_fee );

			return $post_meta;
		}

		/**
		 * Prepare the order local pickup special day.
		 *
		 * @since 3.0.0
		 * @param array  $post_meta Post meta.
		 * @param string $pickup_date Pickup date.
		 * @param object $order_local_pickup_handler Order local pickup handler.
		 * @return array
		 * */
		public static function prepare_order_local_pickup_special_day( $post_meta, $pickup_date, $order_local_pickup_handler ) {
			$special_days = $order_local_pickup_handler->get_special_dates();
			if ( ! dey_check_is_array( $special_days ) || ! isset( $special_days[ $pickup_date ] ) ) {
				return $post_meta;
			}

			$special_day = $special_days[ $pickup_date ];
			if ( ! dey_check_is_array( $special_day ) ) {
				return $post_meta;
			}

			if ( is_a( $order_local_pickup_handler, 'DEY_Pickup_Location_Order_Local_Pickup_Handler' ) ) {
				$selected_mode = is_object( $order_local_pickup_handler->get_pickup_location() ) && '2' === $order_local_pickup_handler->get_pickup_location()->get_special_days_mode() ? 'pickup_location_order_local_pickup' : 'global';
			} elseif ( is_a( $order_local_pickup_handler, 'DEY_Scheduler_Rule_Order_Local_Pickup_Handler' ) ) {
				$selected_mode = is_object( $order_local_pickup_handler->get_scheduler_rule() ) && '2' === $order_local_pickup_handler->get_scheduler_rule()->get_special_days_mode() ? 'scheduler_rule_order_local_pickup' : 'global';
			} else {
				$selected_mode = 'global';
			}

			$post_meta['dey_pickup_charge']                       += floatval( $special_day['price'] );
			$post_meta['dey_special_day_id']                       = $special_day['id'];
			$post_meta['dey_pickup_charge_details']['special_day'] = floatval( $special_day['price'] );
			$post_meta['dey_special_day_mode']                     = $selected_mode;

			return $post_meta;
		}

		/**
		 * Prepare the order local pickup same day.
		 *
		 * @since 3.5.0
		 * @param array  $post_meta Post meta.
		 * @param string $pickup_date Pickup date.
		 * @param object $order_local_pickup_handler Order local pickup handler object.
		 * @return array
		 * */
		public static function prepare_order_local_pickup_same_day( $post_meta, $pickup_date, $order_local_pickup_handler ) {
			$same_day_fee                                       = $order_local_pickup_handler->get_same_day_price( DEY_Date_Time::get_date_time_object( $pickup_date ) );
			$post_meta['dey_pickup_charge']                    += floatval( $same_day_fee );
			$post_meta['dey_pickup_charge_details']['same_day'] = floatval( $same_day_fee );

			return $post_meta;
		}

		/**
		 * Prepare the order local pickup next day.
		 *
		 * @since 3.5.0
		 * @param array  $post_meta Post meta.
		 * @param string $pickup_date Pickup date.
		 * @param object $order_local_pickup_handler Order local pickup handler object.
		 * @return array
		 * */
		public static function prepare_order_local_pickup_next_day( $post_meta, $pickup_date, $order_local_pickup_handler ) {
			$next_day_fee                                       = $order_local_pickup_handler->get_next_day_price( DEY_Date_Time::get_date_time_object( $pickup_date ) );
			$post_meta['dey_pickup_charge']                    += floatval( $next_day_fee );
			$post_meta['dey_pickup_charge_details']['next_day'] = floatval( $next_day_fee );

			return $post_meta;
		}

		/**
		 * Create the order tip.
		 *
		 * @since 1.0.0
		 * @param object $order Order object.
		 * @return void
		 * */
		public static function create_order_tip( &$order ) {
			$session_data = dey_get_order_tip_session_data();
			if ( ! dey_check_is_array( $session_data ) ) {
				return;
			}

			$fees = WC()->cart->get_fees();
			// Return if the fees is not exists in the cart.
			if ( ! dey_check_is_array( $fees ) ) {
				return;
			}

			// Return if the order tip fee not exists.
			if ( ! array_key_exists( DEY()->order_tip_fee_name(), $fees ) ) {
				return;
			}

			$amount    = abs( $fees[ DEY()->order_tip_fee_name() ]->total );
			$post_meta = array(
				'dey_amount'     => $amount,
				'dey_mode'       => $session_data['mode'],
				'dey_type'       => $session_data['type'],
				'dey_user_id'    => $order->get_customer_id(),
				'dey_user_name'  => $order->get_formatted_billing_full_name(),
				'dey_user_email' => $order->get_billing_email(),
				'dey_currency'   => $order->get_currency(),
			);

			$order_tip_id = dey_create_new_order_tip(
				$post_meta,
				array(
					'post_status' => 'dey_pending_payment',
					'post_parent' => $order->get_id(),
				)
			);
			if ( $order_tip_id ) {
				// Update the order item meta.
				$order->update_meta_data( 'dey_order_tip_amount', $amount );
				$order->update_meta_data( 'dey_order_tip_mode', $session_data['mode'] );
				$order->update_meta_data( 'dey_order_tip_type', $session_data['type'] );
				$order->update_meta_data( 'dey_order_tip_id', $order_tip_id );
			}
		}

		/**
		 * Hidden the custom order item meta.
		 *
		 * @since 1.0.0
		 * @param array $hidden_order_item_meta Hidden order item meta.
		 * @return array
		 */
		public static function hide_order_item_meta_key( $hidden_order_item_meta ) {
			$product_delivery_order_item_meta = array(
				'_dey_delivery_date',
				'_dey_delivery_last_date',
				'_dey_product_delivery_id',
				'_dey_delivery_price',
				'_dey_delivery_mode',
				'_dey_delivery_time_mode',
				'_dey_delivery_time_slot_id',
				'_dey_delivery_time_slot_from',
				'_dey_delivery_time_slot_to',
				'_dey_delivery_special_day_id',
				'_dey_delivery_charge_details',
			);

			$product_pickup_order_item_meta = array(
				'_dey_product_pickup_location_id',
				'_dey_product_pickup_date',
				'_dey_product_pickup_price',
				'_dey_product_pickup_time_mode',
				'_dey_product_pickup_time_slot_id',
				'_dey_product_pickup_time_slot_from',
				'_dey_product_pickup_time_slot_to',
				'_dey_product_pickup_special_day_id',
				'_dey_product_pickup_id',
				'_dey_product_pickup_charge_details',
			);

			return array_merge( $hidden_order_item_meta, $product_delivery_order_item_meta, $product_pickup_order_item_meta );
		}

		/**
		 * Maybe hide the cancel order button.
		 *
		 * @since 3.8.0
		 * @param array  $actions Orders actions.
		 * @param object $order Order object.
		 * @return array
		 */
		public static function maybe_hide_cancel_order_button( $actions, $order ) {
			if ( ! dey_check_is_array( $actions ) || ! is_object( $order ) || ! isset( $actions['cancel'] ) ) {
				return $actions;
			}

			if ( ! dey_is_valid_to_display_cancel_order( $order ) ) {
				unset( $actions['cancel'] );
			}

			return $actions;
		}

		/**
		 * Maybe restrict cancel order.
		 *
		 * @since 3.8.0
		 * @param array  $statuses Order statuses.
		 * @param object $order Order object.
		 * @return array
		 */
		public static function maybe_restrict_cancel_order( $statuses, $order ) {
			if ( ! dey_check_is_array( $statuses ) || ! is_object( $order ) ) {
				return $statuses;
			}

			if ( ! dey_is_valid_to_display_cancel_order( $order ) ) {
				return array();
			}

			return $statuses;
		}

		/**
		 * Process the product schedulers.
		 *
		 * @since 3.5.0
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public static function processes_product_schedulers( $order_id ) {
			// Product delivery.
			self::maybe_processes_product_delivery( $order_id );
			// Product local pickup.
			self::maybe_processes_product_pickup( $order_id );
		}

		/**
		 * Maybe processes the product delivery slots based on order status.
		 *
		 * @since 1.0.0
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public static function maybe_processes_product_delivery( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			foreach ( $order->get_items() as $key => $value ) {
				if ( ! isset( $value['dey_product_delivery_id'] ) ) {
					continue;
				}

				$product_delivery = dey_get_product_delivery( $value['dey_product_delivery_id'] );
				if ( ! $product_delivery->exists() || ! $product_delivery->has_status( array( 'dey_pending_payment', 'dey_failed' ) ) ) {
					continue;
				}

				if ( $product_delivery->has_status( 'dey_failed' ) ) {
					// Update the order usage count.
					dey_update_product_delivery_time_slot_order_usage_count( $product_delivery->get_product(), $product_delivery->get_time_slot_id(), $product_delivery->get_delivery_date() );
					dey_update_product_delivery_special_day_order_usage_count( $product_delivery->get_product(), $product_delivery->get_special_day_id() );
				}

				// Update status.
				$product_delivery->update_status( 'dey_upcoming' );

				/**
				 * This hook is used to do extra action after product delivery processed.
				 *
				 * @since 1.0.0
				 */
				do_action( 'dey_product_delivery_processed', $value['dey_product_delivery_id'], $product_delivery, $order );
			}
		}

		/**
		 * Maybe processes the product pickup slots based on order status.
		 *
		 * @since 3.5.0
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public static function maybe_processes_product_pickup( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			foreach ( $order->get_items() as $key => $value ) {
				if ( ! isset( $value['dey_product_pickup_id'] ) ) {
					continue;
				}

				$product_pickup = dey_get_product_local_pickup( $value['dey_product_pickup_id'] );
				if ( ! $product_pickup->exists() || ! $product_pickup->has_status( array( 'dey_pending_payment', 'dey_failed' ) ) ) {
					continue;
				}

				if ( $product_pickup->has_status( 'dey_failed' ) ) {
					// Update the product pickup order usage count.
					dey_update_product_pickup_time_slot_order_usage_count( $product_pickup->get_product(), $product_pickup->get_time_slot_id(), $product_pickup->get_pickup_date() );
					dey_update_product_pickup_special_day_order_usage_count( $product_pickup->get_product(), $product_pickup->get_special_day_id() );
				}

				// Update status.
				$product_pickup->update_status( 'dey_upcoming' );

				/**
				 * This hook is used to do extra action after product pickup processed.
				 *
				 * @since 3.5.0
				 */
				do_action( 'dey_product_pickup_processed', $value['dey_product_pickup_id'], $product_pickup, $order );
			}
		}

		/**
		 * May be processes the order delivery slots based on order status.
		 *
		 * @since 1.0.0
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public static function processes_order_delivery( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			self::maybe_change_order_delivery_status_to_upcoming( $order );
			self::maybe_change_order_pickup_status_to_upcoming( $order );
		}

		/**
		 * May be change the order delivery status to upcoming.
		 *
		 * @since 1.0.0
		 * @param object $order Order object.
		 * @return void
		 */
		public static function maybe_change_order_delivery_status_to_upcoming( $order ) {
			$order_delivery_id = $order->get_meta( 'dey_order_delivery_id' );
			if ( empty( $order_delivery_id ) ) {
				return;
			}

			$order_delivery = dey_get_order_delivery( $order_delivery_id );
			if ( ! $order_delivery->exists() || ! $order_delivery->has_status( array( 'dey_pending_payment', 'dey_failed' ) ) ) {
				return;
			}

			if ( $order_delivery->has_status( 'dey_failed' ) ) {
				// Update the order delivery time slot usage count.
				dey_update_order_time_slot_usage_count(
					array(
						'mode'              => $order_delivery->get_time_slot_mode(),
						'date'              => $order_delivery->get_delivery_date(),
						'time_slot_id'      => $order_delivery->get_time_slot_id(),
						'scheduler_rule_id' => $order_delivery->get_scheduler_rule_id(),
					)
				);
				// Update the order special day usage count.
				dey_update_order_special_day_usage_count(
					array(
						'mode'              => $order_delivery->get_special_day_mode(),
						'special_day_id'    => $order_delivery->get_special_day_id(),
						'scheduler_rule_id' => $order_delivery->get_scheduler_rule_id(),
					)
				);
			}

			// Update status.
			$order_delivery->update_status( 'dey_upcoming' );
			/**
			 * This hook is used to do extra action after order delivery processed.
			 *
			 * @since 1.0
			 */
			do_action( 'dey_order_delivery_processed', $order_delivery_id, $order_delivery );
		}

		/**
		 * May be change the order local pickup status to upcoming.
		 *
		 * @since 1.0.0
		 * @param object $order Order object.
		 * @return void
		 */
		public static function maybe_change_order_pickup_status_to_upcoming( $order ) {
			$order_local_pickup_id = $order->get_meta( 'dey_order_local_pickup_id' );
			if ( empty( $order_local_pickup_id ) ) {
				return;
			}

			$order_local_pickup = dey_get_order_local_pickup( $order_local_pickup_id );
			if ( ! $order_local_pickup->exists() || ! $order_local_pickup->has_status( array( 'dey_pending_payment', 'dey_failed' ) ) ) {
				return;
			}

			if ( $order_local_pickup->has_status( 'dey_failed' ) ) {
				// Update the order local pickup time slot usage count.
				dey_update_order_time_slot_usage_count(
					array(
						'mode'               => $order_local_pickup->get_time_slot_mode(),
						'date'               => $order_local_pickup->get_pickup_date(),
						'time_slot_id'       => $order_local_pickup->get_time_slot_id(),
						'scheduler_rule_id'  => $order_local_pickup->get_scheduler_rule_id(),
						'pickup_location_id' => $order_local_pickup->get_pickup_location_id(),
					)
				);
				// Update the order special day usage count.
				dey_update_order_special_day_usage_count(
					array(
						'mode'               => $order_local_pickup->get_special_day_mode(),
						'special_day_id'     => $order_local_pickup->get_special_day_id(),
						'scheduler_rule_id'  => $order_local_pickup->get_scheduler_rule_id(),
						'pickup_location_id' => $order_local_pickup->get_pickup_location_id(),
					)
				);
			}

			// Update status.
			$order_local_pickup->update_status( 'dey_upcoming' );
			/**
			 * This hook is used to do extra action after order local pickup processed.
			 *
			 * @since 2.2
			 */
			do_action( 'dey_order_local_pickup_processed', $order_local_pickup_id, $order_local_pickup );
		}

		/**
		 * May be processes the order tip based on order status.
		 * */
		public static function processes_order_tip( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			$order_tip_id = $order->get_meta( 'dey_order_tip_id' );
			if ( empty( $order_tip_id ) ) {
				return;
			}

			$order_tip = dey_get_order_tip( $order_tip_id );
			if ( ! $order_tip->exists() || ! $order_tip->has_status( array( 'dey_pending_payment', 'dey_failed' ) ) ) {
				return;
			}

			// Update status.
			$order_tip->update_status( 'dey_paid' );
			/**
			 * This hook is used to do extra action after order tip processed.
			 *
			 * @since 1.0
			 */
			do_action( 'dey_order_tip_processed', $order_tip_id, $order_tip, $order );
		}

		/**
		 * May be failed the product delivery based on order status.
		 * */
		public static function maybe_failed_product_delivery( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			foreach ( $order->get_items() as $key => $value ) {
				if ( ! isset( $value['dey_product_delivery_id'] ) ) {
					continue;
				}

				$product_delivery = dey_get_product_delivery( $value['dey_product_delivery_id'] );
				if ( ! $product_delivery->exists() || $product_delivery->has_status( 'dey_failed' ) ) {
					continue;
				}

				// Update the order usage count.
				dey_update_product_delivery_time_slot_order_usage_count( $product_delivery->get_product(), $product_delivery->get_time_slot_id(), $product_delivery->get_delivery_date(), 1, 'decrease' );
				dey_update_product_delivery_special_day_order_usage_count( $product_delivery->get_product(), $product_delivery->get_special_day_id(), 1, 'decrease' );

				// Update status.
				$product_delivery->update_status( 'dey_failed' );
				/**
				 * This hook is used to do extra action after product delivery failed.
				 *
				 * @since 1.0.0
				 */
				do_action( 'dey_product_delivery_failed', $value['dey_product_delivery_id'], $product_delivery, $order );
			}
		}

		/**
		 * Maybe failed the product local pickup based on order status.
		 *
		 * @since 3.5.0
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public static function maybe_failed_product_local_pickup( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			foreach ( $order->get_items() as $key => $value ) {
				if ( ! isset( $value['dey_product_pickup_id'] ) ) {
					continue;
				}

				$product_pickup = dey_get_product_local_pickup( $value['dey_product_pickup_id'] );
				if ( ! $product_pickup->exists() || $product_pickup->has_status( 'dey_failed' ) ) {
					continue;
				}

				// Update the product local pickup order usage count.
				dey_update_product_pickup_time_slot_order_usage_count( $product_pickup->get_product(), $product_pickup->get_time_slot_id(), $product_pickup->get_pickup_date(), 1, 'decrease' );
				dey_update_product_pickup_special_day_order_usage_count( $product_pickup->get_product(), $product_pickup->get_special_day_id(), 1, 'decrease' );
				// Update status.
				$product_pickup->update_status( 'dey_failed' );

				/**
				 * This hook is used to do extra action after product pickup failed.
				 *
				 * @since 3.5.0
				 */
				do_action( 'dey_product_pickup_failed', $value['dey_product_pickup_id'], $product_pickup, $order );
			}
		}

		/**
		 * May be failed the order delivery based on order status.
		 * */
		public static function maybe_failed_order_delivery( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			$order_delivery_id = $order->get_meta( 'dey_order_delivery_id' );
			if ( empty( $order_delivery_id ) ) {
				return;
			}

			$order_delivery = dey_get_order_delivery( $order_delivery_id );
			if ( ! $order_delivery->exists() || $order_delivery->has_status( 'dey_failed' ) ) {
				return;
			}

			// Update the order delivery time slot usage count.
			dey_update_order_time_slot_usage_count(
				array(
					'mode'              => $order_delivery->get_time_slot_mode(),
					'date'              => $order_delivery->get_delivery_date(),
					'time_slot_id'      => $order_delivery->get_time_slot_id(),
					'scheduler_rule_id' => $order_delivery->get_scheduler_rule_id(),
					'action'            => 'decrease',
				)
			);
			// Update the order special day usage count.
			dey_update_order_special_day_usage_count(
				array(
					'mode'              => $order_delivery->get_special_day_mode(),
					'special_day_id'    => $order_delivery->get_special_day_id(),
					'scheduler_rule_id' => $order_delivery->get_scheduler_rule_id(),
					'action'            => 'decrease',
				)
			);

			// Update status.
			$order_delivery->update_status( 'dey_failed' );
			/**
			 * This hook is used to do extra action after order delivery failed.
			 *
			 * @since 1.0
			 */
			do_action( 'dey_order_delivery_failed', $order_delivery_id, $order_delivery, $order );
		}

		/**
		 * May be failed the order local pickup based on order status.
		 *
		 * @param string $order_id
		 * @since 2.2
		 * */
		public static function maybe_failed_order_local_pickup( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			$order_local_pickup_id = $order->get_meta( 'dey_order_local_pickup_id' );
			if ( empty( $order_local_pickup_id ) ) {
				return;
			}

			$order_local_pickup = dey_get_order_local_pickup( $order_local_pickup_id );
			if ( ! $order_local_pickup->exists() || $order_local_pickup->has_status( 'dey_failed' ) ) {
				return;
			}

			// Update the order local pickup time slot usage count.
			dey_update_order_time_slot_usage_count(
				array(
					'mode'               => $order_local_pickup->get_time_slot_mode(),
					'date'               => $order_local_pickup->get_pickup_date(),
					'time_slot_id'       => $order_local_pickup->get_time_slot_id(),
					'scheduler_rule_id'  => $order_local_pickup->get_scheduler_rule_id(),
					'pickup_location_id' => $order_local_pickup->get_pickup_location_id(),
					'action'             => 'decrease',
				)
			);
			// Update the order special day usage count.
			dey_update_order_special_day_usage_count(
				array(
					'mode'               => $order_local_pickup->get_special_day_mode(),
					'special_day_id'     => $order_local_pickup->get_special_day_id(),
					'scheduler_rule_id'  => $order_local_pickup->get_scheduler_rule_id(),
					'pickup_location_id' => $order_local_pickup->get_pickup_location_id(),
					'action'             => 'decrease',
				)
			);

			// Update status.
			$order_local_pickup->update_status( 'dey_failed' );
			/**
			 * This hook is used to do extra action after order delivery failed.
			 *
			 * @since 2.2.0
			 */
			do_action( 'dey_order_local_pickup_failed', $order_local_pickup_id, $order_local_pickup, $order );
		}

		/**
		 * May be failed the order tip based on order status.
		 * */
		public static function maybe_failed_order_tip( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! is_object( $order ) ) {
				return;
			}

			$order_tip_id = $order->get_meta( 'dey_order_tip_id' );
			if ( empty( $order_tip_id ) ) {
				return;
			}

			$order_tip = dey_get_order_tip( $order_tip_id );
			if ( ! $order_tip->exists() || $order_tip->has_status( 'dey_failed' ) ) {
				return;
			}

			// Update status.
			$order_tip->update_status( 'dey_failed' );
			/**
			 * This hook is used to do extra action after order tip failed.
			 *
			 * @since 1.0
			 */
			do_action( 'dey_order_tip_failed', $order_tip_id, $order_tip, $order );
		}

		/**
		 * Add the custom order meta fields in email.
		 *
		 * @return array
		 * */
		public static function maybe_email_custom_order_meta_fields( $fields, $sent_to_admin, $order ) {
			if ( ! is_object( $order ) ) {
				return $fields;
			}

			// Return if the order delivery details not exists in order .
			$order_delivery_details = dey_get_order_delivery_details( $order );
			if ( ! dey_check_is_array( $order_delivery_details ) ) {
				return $fields;
			}

			foreach ( $order_delivery_details as $key => $order_delivery_detail ) {
				$fields[ $key ] = $order_delivery_detail;
			}

			/**
			 * This hook is used to alter the order scheduler details on email.
			 *
			 * @since 3.9.0
			 */
			return apply_filters( 'dey_email_order_scheduler_details', $fields, $order );
		}

		/**
		 * May be send the email to local pickup admins.
		 *
		 * @param string $recipients
		 * @param object $order
		 * @since 2.2
		 *
		 * @return string
		 * */
		public static function maybe_send_email_local_pickup_admins( $recipients, $order ) {
			if ( ! is_object( $order ) ) {
				return $recipients;
			}

			$order_local_pickup_id = $order->get_meta( 'dey_order_local_pickup_id' );
			if ( empty( $order_local_pickup_id ) ) {
				return $recipients;
			}

			$order_local_pickup = dey_get_order_local_pickup( $order_local_pickup_id );
			if ( ! $order_local_pickup->exists() || $order_local_pickup->has_status( 'dey_failed' ) || empty( $order_local_pickup->get_pickup_location()->get_email_lists() ) ) {
				return $recipients;
			}

			$recipients                 = explode( ',', $recipients );
			$pickup_location_recipients = explode( ',', $order_local_pickup->get_pickup_location()->get_email_lists() );

			$recipients = array_merge( $recipients, $pickup_location_recipients );

			return implode( ',', $recipients );
		}
	}

	DEY_Order_Handler::init();
}
