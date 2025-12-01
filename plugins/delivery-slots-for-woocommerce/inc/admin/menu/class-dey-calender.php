<?php
/**
 * Calender.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Calender' ) ) {

	/**
	 * Class.
	 *
	 * @since 1.0.0
	 */
	class DEY_Calender {

		/**
		 * Output calender.
		 *
		 * @since 1.0.0
		 * @global string $current_tab Current tab.
		 * */
		public static function output() {
			global $current_tab;

			$tabs     = self::get_tabs();
			$statuses = self::get_calender_statuses();

			// Html for calender page.
			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/html-calender.php';
		}

		/**
		 * Get the tabs.
		 *
		 * @since 1.0.0
		 * @return array
		 * */
		public static function get_tabs() {

			/**
			 * This hook is used to alter the calender tabs.
			 *
			 * @since 1.0.0
			 */
			return apply_filters(
				'dey_calender_tabs',
				array(
					'order_calender'       => __( 'Order Delivery', 'delivery-slots-for-woocommerce' ),
					'order_local_pickup'   => __( 'Order Local Pickup', 'delivery-slots-for-woocommerce' ),
					'product_calender'     => __( 'Product Delivery', 'delivery-slots-for-woocommerce' ),
					'product_local_pickup' => __( 'Product Local Pickup', 'delivery-slots-for-woocommerce' ),
					'order_tip_calender'   => __( 'Tip Calender', 'delivery-slots-for-woocommerce' ),
				)
			);
		}

		/**
		 * Get the calender statuses.
		 *
		 * @since 1.0.0
		 * @global string $current_tab Current tab.
		 * @return array
		 * */
		public static function get_calender_statuses() {
			global $current_tab;

			switch ( $current_tab ) {
				case 'order_tip_calender':
					$statuses = array(
						'pending' => __( 'Pending Payment', 'delivery-slots-for-woocommerce' ),
						'paid'    => __( 'Paid', 'delivery-slots-for-woocommerce' ),
						'failed'  => __( 'Failed', 'delivery-slots-for-woocommerce' ),
					);
					break;
				case 'order_local_pickup':
				case 'product_local_pickup':
					$statuses = array(
						'holiday'   => __( 'Holiday', 'delivery-slots-for-woocommerce' ),
						'upcoming'  => __( 'Upcoming', 'delivery-slots-for-woocommerce' ),
						'picked-up' => __( 'Picked up', 'delivery-slots-for-woocommerce' ),
					);
					break;
				case 'order_calender':
				case 'product_calender':
				default:
					$statuses = array(
						'holiday'   => __( 'Holiday', 'delivery-slots-for-woocommerce' ),
						'upcoming'  => __( 'Upcoming', 'delivery-slots-for-woocommerce' ),
						'delivered' => __( 'Delivered', 'delivery-slots-for-woocommerce' ),
					);
					break;
			}

			/**
			 * This hook is used to alter the calender status details.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'dey_calender_status_details', $statuses );
		}

		/**
		 * Prepare the calender events.
		 *
		 * @since 1.0.0
		 * @param string $start_date Start date.
		 * @param string $end_date End date.
		 * @param string $type Type of event.
		 * @return array
		 */
		public static function prepare_calender_events( $start_date, $end_date, $type ) {
			$post_ids = self::get_date_range_delivery( $start_date, $end_date, $type );
			$events   = array();
			switch ( $type ) {
				case 'order_calender':
					$events = self::prepare_order_calender_events( $post_ids );
					break;
				case 'order_local_pickup':
					$events = self::prepare_order_local_pickup_events( $post_ids );
					break;
				case 'product_calender':
					$events = self::prepare_product_calender_events( $post_ids );
					break;
				case 'product_local_pickup':
					$events = self::prepare_product_local_pickup_events( $post_ids );
					break;
				case 'order_tip_calender':
					$events = self::prepare_order_tip_calender_events( $post_ids );
					break;
			}

			return $events;
		}

		/**
		 * Prepare the calender event data.
		 *
		 * @since 1.0.0
		 * @param  int    $id ID of the event.
		 * @param string $type Type of event.
		 * @return string
		 */
		public static function prepare_calender_event_data( $id, $type ) {
			$event_data = __( 'No data found', 'delivery-slots-for-woocommerce' );
			switch ( $type ) {
				case 'order_tip_calender':
					$order_tip = dey_get_order_tip( $id );
					if ( $order_tip->exists() ) {
						ob_start();
						// Include order tip event file.
						include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/html-order-tip-event-data.php';

						$event_data = ob_get_contents();
						ob_end_clean();
					}
					break;

				case 'order_calender':
					$order_calender = dey_get_order_delivery( $id );
					if ( $order_calender->exists() ) {
						ob_start();
						// Include order delivery event file.
						include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/html-order-delivery-event-data.php';

						$event_data = ob_get_contents();
						ob_end_clean();
					}
					break;

				case 'order_local_pickup':
					$order_local_pickup = dey_get_order_local_pickup( $id );
					if ( $order_local_pickup->exists() ) {
						ob_start();
						// Include order delivery event file.
						include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/html-order-local-pickup-event-data.php';

						$event_data = ob_get_contents();
						ob_end_clean();
					}
					break;

				case 'product_calender':
					$product_calender = dey_get_product_delivery( $id );
					if ( $product_calender->exists() ) {
						ob_start();
						// Include product delivery event file.
						include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/html-product-delivery-event-data.php';

						$event_data = ob_get_contents();
						ob_end_clean();
					}
					break;

				case 'product_local_pickup':
					$product_pickup = dey_get_product_local_pickup( $id );
					if ( $product_pickup->exists() ) {
						ob_start();
						// Include product local pickup event file.
						include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/html-product-local-pickup-event-data.php';

						$event_data = ob_get_contents();
						ob_end_clean();
					}
					break;
			}

			return $event_data;
		}

		/**
		 * Prepare the order delivery calender events.
		 *
		 * @since 1.0.0
		 * @param array $post_ids Post IDs.
		 * @return array
		 */
		public static function prepare_order_calender_events( $post_ids ) {
			if ( ! dey_check_is_array( $post_ids ) ) {
				return array();
			}

			$events = array();
			foreach ( $post_ids as $post_id ) {
				$order_delivery = dey_get_order_delivery( $post_id );
				if ( ! $order_delivery->exists() ) {
					continue;
				}

				$event = array(
					'id'          => $order_delivery->get_id(),
					'title'       => '#' . $order_delivery->get_order_id(),
					'description' => '',
					'color'       => ( 'dey_delivered' === $order_delivery->get_status() ) ? '#00b53c' : '#ead41b',
				);

				if ( '2' == $order_delivery->get_delivery_mode() ) {
					$event['start'] = $order_delivery->get_delivery_date();
					$event['end']   = $order_delivery->get_delivery_last_date();
				} else {
					switch ( $order_delivery->get_delivery_time_mode() ) {
						case '2':
							$event['start']  = $order_delivery->get_delivery_date();
							$event['allDay'] = false;
							break;

						case '3':
							$event['start']  = ( $order_delivery->get_time_slot_from() ) ? $order_delivery->get_delivery_date() . ' ' . $order_delivery->get_time_slot_from() : $order_delivery->get_delivery_date();
							$event['end']    = ( $order_delivery->get_time_slot_to() ) ? $order_delivery->get_delivery_date() . ' ' . $order_delivery->get_time_slot_to() : $order_delivery->get_delivery_date();
							$event['allDay'] = $order_delivery->is_as_soon_as_possible_time_slot();
							break;

						default:
							$event['start'] = $order_delivery->get_delivery_date();
							break;
					}
				}

				$events[] = $event;
			}

			return $events;
		}

		/**
		 * Prepare the product delivery calender events.
		 *
		 * @since 1.0.0
		 * @param array $post_ids Post IDs.
		 * @return array
		 */
		public static function prepare_product_calender_events( $post_ids ) {
			if ( ! dey_check_is_array( $post_ids ) ) {
				return array();
			}

			$events = array();
			foreach ( $post_ids as $post_id ) {
				$product_delivery = dey_get_product_delivery( $post_id );
				if ( ! $product_delivery->exists() ) {
					continue;
				}

				$event = array(
					'id'          => $product_delivery->get_id(),
					'title'       => '#' . $product_delivery->get_product_id(),
					'description' => '',
					'color'       => ( 'dey_delivered' === $product_delivery->get_status() ) ? '#00b53c' : '#ead41b',
				);

				if ( '2' == $product_delivery->get_delivery_mode() ) {
					$event['start'] = $product_delivery->get_delivery_date();
					$event['end']   = $product_delivery->get_delivery_last_date();
				} else {
					switch ( $product_delivery->get_delivery_time_mode() ) {
						case '2':
							$event['start']  = $product_delivery->get_delivery_date();
							$event['allDay'] = false;
							break;

						case '3':
							$event['start']  = ( $product_delivery->get_time_slot_from() ) ? $product_delivery->get_delivery_date() . ' ' . $product_delivery->get_time_slot_from() : $product_delivery->get_delivery_date();
							$event['end']    = ( $product_delivery->get_time_slot_to() ) ? $product_delivery->get_delivery_date() . ' ' . $product_delivery->get_time_slot_to() : $product_delivery->get_delivery_date();
							$event['allDay'] = $product_delivery->is_as_soon_as_possible_time_slot();
							break;

						default:
							$event['start'] = $product_delivery->get_delivery_date();
							break;
					}
				}

				$events[] = $event;
			}

			return $events;
		}

		/**
		 * Prepare the product local pickup events.
		 *
		 * @since 3.5.0
		 * @param array $post_ids Post IDs.
		 * @return array
		 * */
		public static function prepare_product_local_pickup_events( $post_ids ) {
			if ( ! dey_check_is_array( $post_ids ) ) {
				return array();
			}

			$events = array();
			foreach ( $post_ids as $post_id ) {
				$product_pickup = dey_get_product_local_pickup( $post_id );
				if ( ! $product_pickup->exists() ) {
					continue;
				}

				$event = array(
					'id'          => $product_pickup->get_id(),
					'title'       => '#' . $product_pickup->get_product_id(),
					'description' => '',
					'color'       => ( 'dey_picked_up' === $product_pickup->get_status() ) ? '#00b53c' : '#ead41b',
				);

				switch ( $product_pickup->get_pickup_time_mode() ) {
					case '2':
						$event['start']  = $product_pickup->get_pickup_date();
						$event['allDay'] = false;
						break;

					case '3':
						$event['start']  = ( $product_pickup->get_time_slot_from() ) ? $product_pickup->get_pickup_date() . ' ' . $product_pickup->get_time_slot_from() : $product_pickup->get_pickup_date();
						$event['end']    = ( $product_pickup->get_time_slot_to() ) ? $product_pickup->get_pickup_date() . ' ' . $product_pickup->get_time_slot_to() : $product_pickup->get_pickup_date();
						$event['allDay'] = $product_pickup->is_as_soon_as_possible_time_slot();
						break;

					default:
						$event['start'] = $product_pickup->get_pickup_date();
						break;
				}

				$events[] = $event;
			}

			return $events;
		}

		/**
		 * Prepare the order local pickup events.
		 *
		 * @since 2.2.0
		 * @param array $post_ids Post IDs.
		 * @return array
		 * */
		public static function prepare_order_local_pickup_events( $post_ids ) {
			if ( ! dey_check_is_array( $post_ids ) ) {
				return array();
			}

			$events = array();
			foreach ( $post_ids as $post_id ) {
				$order_local_pickup = dey_get_order_local_pickup( $post_id );
				if ( ! $order_local_pickup->exists() ) {
					continue;
				}

				$event = array(
					'id'          => $order_local_pickup->get_id(),
					'title'       => '#' . $order_local_pickup->get_order_id(),
					'description' => '',
					'color'       => ( 'dey_picked_up' === $order_local_pickup->get_status() ) ? '#00b53c' : '#ead41b',
				);

				switch ( $order_local_pickup->get_pickup_time_mode() ) {
					case '2':
						$event['start']  = $order_local_pickup->get_pickup_date();
						$event['allDay'] = false;
						break;

					case '3':
						$event['start']  = ( $order_local_pickup->get_time_slot_from() ) ? $order_local_pickup->get_pickup_date() . ' ' . $order_local_pickup->get_time_slot_from() : $order_local_pickup->get_pickup_date();
						$event['end']    = ( $order_local_pickup->get_time_slot_to() ) ? $order_local_pickup->get_pickup_date() . ' ' . $order_local_pickup->get_time_slot_to() : $order_local_pickup->get_pickup_date();
						$event['allDay'] = $order_local_pickup->is_as_soon_as_possible_time_slot();
						break;

					default:
						$event['start'] = $order_local_pickup->get_pickup_date();
						break;
				}

				$events[] = $event;
			}

			return $events;
		}

		/**
		 * Prepare the order tip calender events.
		 *
		 * @since 1.0.0
		 * @param array $post_ids Post IDs.
		 * @return array
		 */
		public static function prepare_order_tip_calender_events( $post_ids ) {
			if ( ! dey_check_is_array( $post_ids ) ) {
				return array();
			}

			$events = array();
			foreach ( $post_ids as $post_id ) {
				$order_tip = dey_get_order_tip( $post_id );
				if ( ! $order_tip->exists() ) {
					continue;
				}

				switch ( $order_tip->get_status() ) {
					case 'dey_failed':
						$color = '#ff0000';
						break;
					case 'dey_paid':
						$color = '#6000b5';
						break;
					default:
						$color = '#ff7800';
						break;
				}

				$event = array(
					'id'          => $order_tip->get_id(),
					'title'       => '#' . $order_tip->get_order_id(),
					'description' => '',
					'color'       => $color,
					'start'       => DEY_Date_Time::get_mysql_date_time_format( $order_tip->get_created_date(), true, true ),
					'allDay'      => false,
				);

				$events[] = $event;
			}

			return $events;
		}

		/**
		 * Get the date range delivery.
		 *
		 * @since 1.0.0
		 * @param string $start_date Start date.
		 * @param string $end_date End date.
		 * @param string $type Type.
		 * @return array
		 */
		public static function get_date_range_delivery( $start_date, $end_date, $type ) {
			switch ( $type ) {
				// Order Tip Calender.
				case 'order_tip_calender':
					$args = array(
						'post_type'   => DEY_Register_Post_Types::ORDER_TIP_POSTTYPE,
						'post_status' => dey_get_order_tip_statuses(),
						'fields'      => 'ids',
						'numberposts' => '-1',
						'date_query'  => array(
							array(
								'column' => 'post_date',
								'after'  => $start_date,
							),
							array(
								'column' => 'post_date',
								'before' => $end_date,
							),
						),
					);
					break;

				// Order local pickup.
				case 'order_local_pickup':
					$args = array(
						'post_type'   => DEY_Register_Post_Types::ORDER_LOCAL_PICKUP_POSTTYPE,
						'post_status' => dey_get_order_local_pickup_statuses(),
						'fields'      => 'ids',
						'numberposts' => '-1',
						'meta_query'  => array(
							array(
								'key'     => 'dey_pickup_date',
								'value'   => $start_date,
								'compare' => '>=',
								'type'    => 'DATETIME',
							),
							array(
								'key'     => 'dey_pickup_date',
								'value'   => $end_date,
								'compare' => '<=',
								'type'    => 'DATETIME',
							),
						),
					);
					break;

				// Product Calender.
				case 'product_calender':
					$args = array(
						'post_type'   => DEY_Register_Post_Types::PRODUCT_DELIVERY_POSTTYPE,
						'post_status' => dey_get_product_delivery_statuses(),
						'fields'      => 'ids',
						'numberposts' => '-1',
						'meta_query'  => array(
							array(
								'key'     => 'dey_delivery_date',
								'value'   => $start_date,
								'compare' => '>=',
								'type'    => 'DATETIME',
							),
							array(
								'key'     => 'dey_delivery_date',
								'value'   => $end_date,
								'compare' => '<=',
								'type'    => 'DATETIME',
							),
						),
					);
					break;

				// Product local pickup.
				case 'product_local_pickup':
					$args = array(
						'post_type'   => DEY_Register_Post_Types::PRODUCT_LOCAL_PICKUP_POSTTYPE,
						'post_status' => dey_get_product_local_pickup_statuses(),
						'fields'      => 'ids',
						'numberposts' => '-1',
						'meta_query'  => array(
							array(
								'key'     => 'dey_pickup_date',
								'value'   => $start_date,
								'compare' => '>=',
								'type'    => 'DATETIME',
							),
							array(
								'key'     => 'dey_pickup_date',
								'value'   => $end_date,
								'compare' => '<=',
								'type'    => 'DATETIME',
							),
						),
					);
					break;

				// Order Calender.
				default:
					$args = array(
						'post_type'   => DEY_Register_Post_Types::ORDER_DELIVERY_POSTTYPE,
						'post_status' => dey_get_order_delivery_statuses(),
						'fields'      => 'ids',
						'numberposts' => '-1',
						'meta_query'  => array(
							array(
								'key'     => 'dey_delivery_date',
								'value'   => $start_date,
								'compare' => '>=',
								'type'    => 'DATETIME',
							),
							array(
								'key'     => 'dey_delivery_date',
								'value'   => $end_date,
								'compare' => '<=',
								'type'    => 'DATETIME',
							),
						),
					);
					break;
			}

			return get_posts( $args );
		}
	}

}
