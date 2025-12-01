<?php
/**
 * Handles the Order Local Pickup - Scheduler rule.
 *
 * @since 4.0.0
 * */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Scheduler_Rule_Order_Local_Pickup_Handler' ) ) {

	/**
	 * Class.
	 * */
	class DEY_Scheduler_Rule_Order_Local_Pickup_Handler {

		/**
		 * Booked Dates.
		 *
		 * @var array
		 */
		protected static $booked_dates;

		/**
		 * Available Dates.
		 *
		 * @var array
		 */
		protected static $available_dates;

		/**
		 * Special Dates.
		 *
		 * @var array
		 */
		protected static $special_dates;

		/**
		 * Holiday Dates.
		 *
		 * @var array
		 */
		protected static $holidays;

		/**
		 * Available holiday Dates.
		 *
		 * @since 4.2.0
		 * @var array
		 */
		protected static $available_holidays = array();

		/**
		 * Scheduler rule.
		 *
		 * @since 4.0.0
		 * @var object
		 */
		protected static $scheduler_rule;

		/**
		 * Constructor.
		 *
		 * @since 4.0.0
		 * @param object $scheduler_rule Scheduler rule object.
		 */
		public function __construct( $scheduler_rule ) {
			self::$scheduler_rule = is_object( $scheduler_rule ) ? $scheduler_rule : dey_get_scheduler_rule( $scheduler_rule );
		}

		/**
		 * Get the scheduler rule.
		 *
		 * @since 4.0.0
		 * @return object
		 */
		public static function get_scheduler_rule() {
			return self::$scheduler_rule;
		}

		/**
		 * Get the booked dates.
		 *
		 * @return array.
		 */
		public static function get_booked_dates() {
			return self::prepare_booked_dates();
		}

		/**
		 * Get the holidays.
		 *
		 * @return array.
		 */
		public static function get_holidays() {
			return self::prepare_holidays();
		}

		/**
		 * Get the special dates.
		 *
		 * @return array.
		 */
		public static function get_special_dates() {
			return self::prepare_special_dates();
		}

		/**
		 * Get the available dates.
		 *
		 * @return array.
		 */
		public static function get_available_dates() {
			return self::prepare_available_dates();
		}

		/**
		 * Populate the data.
		 *
		 * @return void
		 */
		private static function populate_data() {
			self::prepare_booked_dates();
			self::prepare_holidays();
			self::prepare_special_dates();
		}

		/**
		 * Render the order local pickup fields.
		 *
		 * @return void.
		 */
		public static function render() {
			self::render_fields();
		}

		/**
		 * Render the fields.
		 *
		 * @since 3.0.0
		 * @return void.
		 */
		private static function render_fields() {
			$fields                  = array();
			$pickup_location_options = dey_get_pickup_location_options();
			if ( dey_check_is_array( $pickup_location_options ) ) {
				$fields['dey_pickup_location'] = array(
					'type'        => 'select',
					'label'       => dey_get_order_pickup_location_field_label(),
					'options'     => $pickup_location_options,
					'required'    => 'yes' === self::$scheduler_rule->get_pickup_location_required(),
					'class'       => array( 'form-row-wide', 'dey-form-row' ),
					'input_class' => array( 'dey-order-pickup-locations-field' ),
					'default'     => dey_get_selected_order_scheduler_data_from_session( 'order_pickup_location' ),
				);
			}

			if ( ( 'yes' !== self::$scheduler_rule->get_pickup_location_required() ) || ( 'yes' === self::$scheduler_rule->get_pickup_location_required() && ! empty( dey_get_selected_order_scheduler_data_from_session( 'order_pickup_location' ) ) ) ) {
				$selected_date      = dey_get_selected_order_scheduler_data_from_session( 'order_local_pickup_date' );
				$formatted_datetime = '2' === self::$scheduler_rule->get_pickup_time_mode() ? DEY_Date_Time::get_wp_format_datetime( $selected_date, 'date' ) . ' ' . DEY_Date_Time::get_wp_format_datetime( $selected_date, 'H:i' ) : DEY_Date_Time::get_wp_format_datetime( $selected_date, 'date' );

				$fields['dey_local_pickup_date'] = array(
					'type'        => '2' === get_option( 'dey_advanced_calender_display_mode' ) ? 'show_calender' : 'text',
					'label'       => dey_get_order_local_pickup_date_field_label(),
					'required'    => 'yes' === self::$scheduler_rule->get_pickup_calender_required(),
					'class'       => array( 'form-row-wide', 'dey-form-row' ),
					'input_class' => array( 'dey-order-local-pickup-date-field' ),
					'default'     => ! empty( $selected_date ) ? $formatted_datetime : '',
				);

				switch ( self::$scheduler_rule->get_pickup_time_mode() ) {
					case '3': // Time slots.
						$fields['dey_local_pickup_date']['input_class'][] = 'dey-datepicker';

						$selected_time_slot_data                          = dey_get_selected_order_scheduler_data_from_session( 'order_local_pickup_time_slot' );
						$selected_time_slot                               = dey_check_is_array( $selected_time_slot_data ) && isset( $selected_time_slot_data['id'] ) ? $selected_time_slot_data['id'] : '';
						$fields['dey_order_local_pickup_date_time_slots'] = array(
							'type'        => 'time_slots',
							'label'       => dey_get_order_local_pickup_time_slots_field_label(),
							'required'    => 'yes' === self::$scheduler_rule->get_pickup_time_slot_required(),
							'class'       => array( 'form-row-wide', 'dey-form-row' ),
							'input_class' => array( 'dey-order-pickup-time-slots', 'dey_select2' ),
							'options'     => self::get_time_slot_options(),
							'default'     => $selected_time_slot,
						);
						break;

					case '2': // Time selector.
						$fields['dey_local_pickup_date']['input_class'][] = 'dey-datetimepicker';
						break;

					default:
						$fields['dey_local_pickup_date']['input_class'][] = 'dey-datepicker';
						break;
				}
			}

			dey_get_template( 'order/local-pickup-fields.php', array( 'fields' => $fields ) );
		}

		/**
		 * Enqueue the default scripts and styles.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function enqueue_default_scripts() {
			DEY_Frontend_Assets::enqueue_style( 'dey-jquery-ui' );
			DEY_Frontend_Assets::enqueue_style( 'jquery-ui-datepicker-addon' );

			DEY_Frontend_Assets::enqueue_script( 'jquery-ui-timpicker-addon' );
			DEY_Frontend_Assets::enqueue_script( 'dey-order-local-pickup' );

			DEY_Frontend_Assets::set_additional_localize_scripts(
				'dey-order-local-pickup',
				'dey_order_local_pickup_params',
				array(
					'available_dates'  => array(),
					'min_date'         => '',
					'max_date'         => '',
					'first_time_slot'  => '',
					'available_times'  => array(),
					'ajax_url'         => DEY_ADMIN_AJAX_URL,
					'datepicker_nonce' => wp_create_nonce( 'dey-datepicker' ),
					'holidays'         => array(),
				)
			);
		}

		/**
		 * Get the script data.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public static function get_script_data() {
			return array(
				'available_dates'  => self::get_available_dates(),
				'min_date'         => self::get_calender_first_date(),
				'max_date'         => self::get_calender_last_date(),
				'first_time_slot'  => self::$scheduler_rule->get_pickup_first_available_time_slot(),
				'available_times'  => self::get_available_times(),
				'ajax_url'         => DEY_ADMIN_AJAX_URL,
				'datepicker_nonce' => wp_create_nonce( 'dey-datepicker' ),
				'holidays'         => self::$available_holidays,
			);
		}

		/**
		 * Get the calender first date.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public static function get_calender_first_date() {
			$first_date = '';
			if ( dey_check_is_array( self::$available_dates ) ) {
				reset( self::$available_dates );
				$first_date = key( self::$available_dates );
			}

			return str_replace( '-', '/', $first_date );
		}

		/**
		 * Get the calender last date.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public static function get_calender_last_date() {
			$last_date = '';
			if ( dey_check_is_array( self::$available_dates ) ) {
				end( self::$available_dates );
				$last_date = key( self::$available_dates );
			}

			return str_replace( '-', '/', $last_date );
		}

		/**
		 * Get the available times.
		 *
		 * @since 3.0.0
		 * @return array
		 */
		public static function get_available_times() {
			$available_times = array(
				'hour_min'   => 0,
				'hour_max'   => 23,
				'minute_min' => 0,
				'minute_max' => 59,
				'min_time'   => '00:00',
				'max_time'   => '23:59',
			);

			$time_from = self::$scheduler_rule->get_pickup_available_time_from();
			if ( $time_from ) {
				$exploded_time_from            = explode( ':', $time_from );
				$available_times['hour_min']   = $exploded_time_from[0];
				$available_times['minute_min'] = $exploded_time_from[1];
				$available_times['min_time']   = $time_from;
			}

			$time_to = self::$scheduler_rule->get_pickup_available_time_to();
			if ( $time_to ) {
				$exploded_time_to              = explode( ':', $time_to );
				$available_times['hour_max']   = $exploded_time_to[0];
				$available_times['minute_max'] = $exploded_time_to[1];
				$available_times['max_time']   = $time_to;
			}

			/**
			 * This hook is used to alter the order local pickup available times.
			 *
			 * @since 3.0.0
			 */
			return apply_filters( 'dey_order_local_pickup_available_times', $available_times );
		}

		/**
		 * Get the shipping date object.
		 *
		 * @since 3.0.0
		 * @return object.
		 */
		public static function get_shipping_date_object() {
			// Populate the required data.
			self::populate_data();

			$current_date_object  = DEY_Date_Time::get_date_time_object( 'now' );
			$cutoff_time          = self::$scheduler_rule->get_pickup_same_day_cutoff_time();
			$cutoff_time          = ( empty( $cutoff_time ) ) ? '23:59' : $cutoff_time;
			$next_day_cutoff_time = self::$scheduler_rule->get_pickup_next_day_cutoff_time();
			$present_date_object  = DEY_Date_Time::get_date_time_object( 'now' );

			// Consider the business days.
			$business_days   = array_filter( (array) get_option( 'dey_local_pickup_business_days' ) );
			$business_days   = dey_parse_business_day_option( $business_days, dey_business_day_default_option() );
			$processing_time = self::$scheduler_rule->get_pickup_processing_time();
			$processing_time = dey_parse_processing_hours( $processing_time );
			if ( dey_check_is_array( $business_days ) && dey_processing_hours_exists( $processing_time ) ) {
				$loop             = true;
				$total_time_diff  = 0;
				$processing_hours = dey_get_processing_hours( $processing_time );
				while ( $loop ) {
					// Exit loop once processing hours reached.
					if ( $total_time_diff >= $processing_hours ) {
						$loop = false;
						continue;
					}

					$current_day  = $current_date_object->format( 'w' ) + 1;
					$enabled      = $business_days[ $current_day ]['enable'];
					$opening_time = $business_days[ $current_day ]['opening_time'];
					$closing_time = $business_days[ $current_day ]['closing_time'];

					// Specific days.
					if ( ( 'no' === $enabled ) && ! isset( self::$special_dates[ $current_date_object->format( 'Y-m-d' ) ] ) ) {
						$current_date_object->modify( "tomorrow $opening_time" );
						continue;
					}

					// Holidays.
					if ( ! isset( self::$special_dates[ $current_date_object->format( 'Y-m-d' ) ] ) && ( isset( self::$holidays[ $current_date_object->format( 'Y-m-d' ) ] ) || isset( self::$holidays[ $current_date_object->format( 'm-d' ) ] ) ) ) {
						self::$available_holidays[ $current_date_object->format( 'Y-m-d' ) ] = isset( self::$holidays[ $current_date_object->format( 'Y-m-d' ) ] ) ? self::$holidays[ $current_date_object->format( 'Y-m-d' ) ]['label'] : self::$holidays[ $current_date_object->format( 'm-d' ) ]['label'];
						$current_date_object->modify( "tomorrow $opening_time" );
						continue;
					}

					$total_time_diff      += dey_get_business_hours( $current_date_object, $opening_time, $closing_time );
					$date_after_processing = dey_get_date_after_processing( $current_date_object, $processing_time, $total_time_diff, $opening_time, $closing_time );
					$cutoff_date_object    = DEY_Date_Time::get_date_time_object( $current_date_object->format( 'Y-m-d' ) . ' ' . $cutoff_time );

					// Modify the current date object if the date after processing hour is less than cutoff date or after processing hour.
					if ( ( ! $date_after_processing ) || ( $date_after_processing && $cutoff_date_object < $date_after_processing ) ) {
						$current_date_object->modify( "tomorrow $opening_time" );

						if ( ! empty( $next_day_cutoff_time ) && ( $present_date_object->format( 'Y-m-d' ) === $current_date_object->format( 'Y-m-d' ) ) ) {
							$next_day_date_object = DEY_Date_Time::get_date_time_object( $next_day_cutoff_time );
							if ( $present_date_object > $next_day_date_object ) {
								$current_date_object->modify( "tomorrow $opening_time" );
							}
						}
					}

					continue;
				}
			}

			if ( $present_date_object->format( 'Y-m-d' ) === $current_date_object->format( 'Y-m-d' ) ) {
				$cutoff_date_object = DEY_Date_Time::get_date_time_object( $present_date_object->format( 'Y-m-d' ) . ' ' . $cutoff_time );

				if ( $cutoff_date_object < $present_date_object ) {
					$current_date_object->modify( 'tomorrow' );

					if ( ! empty( $next_day_cutoff_time ) ) {
						$next_day_date_object = DEY_Date_Time::get_date_time_object( $present_date_object->format( 'Y-m-d' ) . ' ' . $next_day_cutoff_time );
						if ( $present_date_object > $next_day_date_object ) {
							$current_date_object->modify( 'tomorrow' );
						}
					}
				}
			}

			// Consider the pickup days.
			$pickup_days = array_filter( (array) self::$scheduler_rule->get_pickup_days() );
			if ( dey_check_is_array( $pickup_days ) ) {
				$loop = true;
				while ( $loop ) {
					// Specific days.
					if ( isset( self::$special_dates[ $current_date_object->format( 'Y-m-d' ) ] ) ) {
						$loop = false;
						continue;
					}

					// Holidays.
					if ( isset( self::$holidays[ $current_date_object->format( 'Y-m-d' ) ] ) || isset( self::$holidays[ $current_date_object->format( 'm-d' ) ] ) ) {
						self::$available_holidays[ $current_date_object->format( 'Y-m-d' ) ] = isset( self::$holidays[ $current_date_object->format( 'Y-m-d' ) ] ) ? self::$holidays[ $current_date_object->format( 'Y-m-d' ) ]['label'] : self::$holidays[ $current_date_object->format( 'm-d' ) ]['label'];
						$current_date_object->modify( '+1days' );
						continue;
					}

					// Business days.
					if ( ! in_array( $current_date_object->format( 'w' ) + 1, $pickup_days ) ) {
						$current_date_object->modify( '+1days' );
						continue;
					}

					$loop = false;
				}
			}

			return $current_date_object;
		}

		/**
		 * Get the weekday price.
		 *
		 * @since 3.0.0
		 * @param object $date_object Date object.
		 * @return string
		 */
		public static function get_weekday_price( $date_object ) {
			$price           = 0;
			$weekdays_prices = array_filter( (array) self::$scheduler_rule->get_pickup_weekdays_prices() );
			if ( ! isset( $weekdays_prices[ $date_object->format( 'w' ) + 1 ] ) ) {
				return $price;
			}

			return floatval( $weekdays_prices[ $date_object->format( 'w' ) + 1 ] );
		}

		/**
		 * Get the same day price.
		 *
		 * @since 3.2.0
		 * @param object $date_object Date Object.
		 * @return float.
		 */
		public static function get_same_day_price( $date_object ) {
			$same_day_fee   = self::$scheduler_rule->get_pickup_same_day_fee();
			$same_day_price = ( $date_object->format( 'Y-m-d' ) === DEY_Date_Time::get_date_time_object( 'now' )->format( 'Y-m-d' ) ) ? $same_day_fee : 0;

			/**
			 * This hook is to alter the same day price for Local Pickup.
			 *
			 * @param float $same_day_price Same Day Price.
			 * @since 3.2.0
			 */
			return apply_filters( 'dey_local_pickup_same_day_price', floatval( $same_day_price ) );
		}

		/**
		 * Get the next day price.
		 *
		 * @since 3.2.0
		 * @param object $date_object Date Object.
		 * @return float.
		 */
		public static function get_next_day_price( $date_object ) {
			$next_day_fee   = self::$scheduler_rule->get_pickup_next_day_fee();
			$next_day_price = ( $date_object->format( 'Y-m-d' ) === DEY_Date_Time::get_date_time_object( 'now' )->modify( '+1 days' )->format( 'Y-m-d' ) ) ? self::$scheduler_rule->get_pickup_next_day_fee() : 0;

			/**
			 * This hook is to alter the next day price for Local Pickup.
			 *
			 * @param float $next_day_price Next Day Price.
			 * @since 3.2.0
			 */
			return apply_filters( 'dey_local_pickup_next_day_price', floatval( $next_day_price ) );
		}

		/**
		 * Get the time slot options for selected date.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		private static function get_time_slot_options() {
			$time_slot_options = array( '' => dey_get_order_local_pickup_default_time_slot_label() );
			$selected_date     = dey_get_selected_order_scheduler_data_from_session( 'order_local_pickup_date' );
			if ( empty( $selected_date ) ) {
				return $time_slot_options;
			}

			$time_slot_option_labels = self::$scheduler_rule->get_order_local_pickup_time_slot_option_labels( $selected_date );

			return $time_slot_options + array_combine( array_column( $time_slot_option_labels, 'id' ), array_column( $time_slot_option_labels, 'label' ) );
		}

		/**
		 * Prepare the available dates.
		 *
		 * @since 3.0.0
		 * @return array
		 */
		private static function prepare_available_dates() {
			if ( isset( self::$available_dates ) ) {
				return self::$available_dates;
			}

			// Populate the required data.
			self::populate_data();

			$i           = 0;
			$max_date    = intval( self::$scheduler_rule->get_pickup_days_availability() );
			$pickup_days = array_filter( (array) self::$scheduler_rule->get_pickup_days() );
			$date_object = self::get_shipping_date_object();

			do {
				$per_day = self::$scheduler_rule->get_pickup_max_per_day();

				// Consider the delivery days.
				if ( ! isset( self::$special_dates[ $date_object->format( 'Y-m-d' ) ] ) && dey_check_is_array( $pickup_days ) && ! in_array( $date_object->format( 'w' ) + 1, $pickup_days ) ) {
					$date_object->modify( '+1days' );
					continue;
				}

				$count = true;
				// Particular holiday.
				if ( ! isset( self::$special_dates[ $date_object->format( 'Y-m-d' ) ] ) && isset( self::$holidays[ $date_object->format( 'Y-m-d' ) ] ) ) {
					$available_date = array(
						'l' => self::$holidays[ $date_object->format( 'Y-m-d' ) ]['label'],
						't' => 'hy',
					);
					$count          = false;
					// Repeat holiday.
				} elseif ( ! isset( self::$special_dates[ $date_object->format( 'Y-m-d' ) ] ) && isset( self::$holidays[ $date_object->format( 'm-d' ) ] ) ) {
					$available_date = array(
						'l' => self::$holidays[ $date_object->format( 'm-d' ) ]['label'],
						't' => 'hy',
					);
					$count          = false;
					// Partial Booked/Booked day.
				} elseif ( isset( self::$booked_dates[ $date_object->format( 'Y-m-d' ) ] ) ) {
					if ( isset( self::$special_dates[ $date_object->format( 'Y-m-d' ) ] ) ) {
						$per_day = self::$special_dates[ $date_object->format( 'Y-m-d' ) ]['order_count'];
					}

					if ( '' == $per_day ) {
						$available_date = array(
							'l' => dey_get_order_local_pickup_partially_booked_label(),
							't' => 'pb',
						);
					} elseif ( intval( $per_day ) > self::$booked_dates[ $date_object->format( 'Y-m-d' ) ] ) {
						$remaining_count = intval( $per_day ) - self::$booked_dates[ $date_object->format( 'Y-m-d' ) ];
						$available_date  = array(
							/* translators: %s remaining count */
							'l' => sprintf( dey_get_order_local_pickup_remaining_count_label(), $remaining_count ),
							't' => 'pb',
						);
					} else {
						$available_date = array(
							'l' => dey_get_order_local_pickup_booked_label(),
							't' => 'fb',
						);
					}
					// Special day.
				} elseif ( isset( self::$special_dates[ $date_object->format( 'Y-m-d' ) ] ) ) {

					$available_date = array(
						'l' => self::$special_dates[ $date_object->format( 'Y-m-d' ) ]['name'],
						't' => 'sy',
					);

					// Normal day.
				} else {

					$available_date = array(
						'l' => '',
						't' => 'ny',
					);
				}

				self::$available_dates[ $date_object->format( 'Y-m-d' ) ] = $available_date;
				$date_object->modify( '+1days' );

				// Increase the count if the date as available date.
				if ( $count ) {
					++$i;
				}
			} while ( $i < $max_date );

			/**
			 * This hook is used to alter the order local pickup available dates.
			 *
			 * @since 3.0.0
			 */
			self::$available_dates = apply_filters( 'dey_order_local_pickup_available_dates', self::$available_dates );

			return self::$available_dates;
		}

		/**
		 * Prepare the booked dates.
		 *
		 * @since 3.0.0
		 * @return array
		 */
		private static function prepare_booked_dates() {
			if ( isset( self::$booked_dates ) ) {
				return self::$booked_dates;
			}

			global $wpdb;

			$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
			if ( ! is_object( $scheduler_rule ) ) {
				return array();
			}
			$post_query = new DEY_Query( $wpdb->prefix . 'posts', 'p' );
			$post_query->select( 'COUNT(p.ID) AS count, pm.meta_value AS date' )
				->leftJoin( $wpdb->prefix . 'postmeta', 'pm', 'p.ID = pm.post_id' )
				->leftJoin( $wpdb->prefix . 'postmeta', 'pm1', 'p.ID = pm1.post_id' )
				->where( 'p.post_type', DEY_Register_Post_Types::ORDER_LOCAL_PICKUP_POSTTYPE )
				->whereIn( 'p.post_status', dey_get_order_local_pickup_statuses() )
				->where( 'pm.meta_key', 'dey_pickup_date' )
				->groupBy( 'pm.meta_value' )
				->where( 'pm1.meta_key', 'dey_scheduler_rule_id' )
				->where( 'pm1.meta_value', $scheduler_rule->get_id() );

			$pickup_ids = $post_query->fetchArray();
			if ( ! dey_check_is_array( $pickup_ids ) ) {
				return self::$booked_dates;
			}

			foreach ( $pickup_ids as $key => $pickup_data ) {
				self::$booked_dates[ DEY_Date_Time::get_date_time_object( $pickup_data['date'] )->format( 'Y-m-d' ) ] = intval( $pickup_data['count'] );
			}

			/**
			 * This hook is used to alter the order local pickup booked dates.
			 *
			 * @since 2.7
			 */
			self::$booked_dates = apply_filters( 'dey_order_local_pickup_booked_dates', self::$booked_dates );

			return self::$booked_dates;
		}

		/**
		 * Prepare the holiday.
		 *
		 * @since 3.0.0
		 * @return array.
		 */
		private static function prepare_holidays() {
			if ( isset( self::$holidays ) ) {
				return self::$holidays;
			}

			if ( ! is_object( self::$scheduler_rule ) ) {
				return array();
			}

			self::$holidays = array();
			if ( '2' === self::$scheduler_rule->get_holidays_mode() ) { // Rule level.
				$holidays = self::$scheduler_rule->get_holidays();
				if ( ! dey_check_is_array( $holidays ) ) {
					return self::$holidays;
				}

				foreach ( $holidays as $key => $holiday ) {
					if ( ! dey_check_is_array( $holiday ) ) {
						continue;
					}

					if ( isset( $holiday['schedule_type'] ) && '1' === $holiday['schedule_type'] ) {
						continue;
					}

					$from_date_object = DEY_Date_Time::get_date_time_object( $holiday['from_date'] );
					$to_date_object   = DEY_Date_Time::get_date_time_object( $holiday['to_date'] );

					do {
						if ( 'yes' === $holiday['recurring'] ) {
							$date = $from_date_object->format( 'm-d' );
						} else {
							$date = $from_date_object->format( 'Y-m-d' );
						}

						self::$holidays[ $date ] = array(
							'label'  => $holiday['name'],
							'y'      => $from_date_object->format( 'Y' ),
							'm'      => $from_date_object->format( 'm' ),
							'd'      => $from_date_object->format( 'd' ),
							'repeat' => $holiday['recurring'],
						);

						$from_date_object->modify( '+1days' );
					} while ( $from_date_object <= $to_date_object );
				}
			} else { // Global level.
				$args = array(
					'post_type'        => DEY_Register_Post_Types::HOLIDAY_POSTTYPE,
					'post_status'      => 'publish',
					'fields'           => 'ids',
					'numberposts'      => '-1',
					'suppress_filters' => false,
					'meta_query'       => array(
						'relation' => 'OR',
						array(
							'key'     => 'dey_holiday_schedule_type',
							'value'   => '2',
							'compare' => '!=',
						),
						array(
							'key'     => 'dey_holiday_schedule_type',
							'compare' => 'NOT EXISTS',
						),
					),
				);

				$post_ids = get_posts( $args );
				if ( ! dey_check_is_array( $post_ids ) ) {
					return self::$holidays;
				}

				foreach ( $post_ids as $post_id ) {
					$holiday          = dey_get_holiday( $post_id );
					$from_date_object = DEY_Date_Time::get_date_time_object( $holiday->get_from_date() );
					$to_date_object   = DEY_Date_Time::get_date_time_object( $holiday->get_to_date() );

					do {
						$date = ( 'yes' === $holiday->get_recurring() ) ? $from_date_object->format( 'm-d' ) : $from_date_object->format( 'Y-m-d' );

						self::$holidays[ $date ] = array(
							'label'  => $holiday->get_name(),
							'y'      => $from_date_object->format( 'Y' ),
							'm'      => $from_date_object->format( 'm' ),
							'd'      => $from_date_object->format( 'd' ),
							'repeat' => $holiday->get_recurring(),
						);

						$from_date_object->modify( '+1days' );
					} while ( $from_date_object <= $to_date_object );
				}
			}

			/**
			 * This hook is used to alter the order local pickup holiday dates.
			 *
			 * @since 3.0.0
			 */
			self::$holidays = apply_filters( 'dey_order_local_pickup_holiday_dates', self::$holidays );

			return self::$holidays;
		}

		/**
		 * Prepare the special dates.
		 *
		 * @since 3.0.0
		 * @return array.
		 */
		private static function prepare_special_dates() {
			if ( isset( self::$special_dates ) ) {
				return self::$special_dates;
			}

			if ( ! is_object( self::$scheduler_rule ) ) {
				return array();
			}

			self::$special_dates = array();
			if ( '2' === self::$scheduler_rule->get_special_days_mode() ) { // Rule level scheduler rule.
				$special_dates = self::$scheduler_rule->get_special_days();
				if ( ! dey_check_is_array( $special_dates ) ) {
					return self::$special_dates;
				}

				foreach ( $special_dates as $key => $special_day ) {
					if ( ! dey_check_is_array( $special_day ) ) {
						continue;
					}

					if ( isset( $special_day['schedule_type'] ) && '1' === $special_day['schedule_type'] ) {
						continue;
					}

					self::$special_dates[ $special_day['date'] ] = array_merge( array( 'id' => $key ), $special_day );
				}
			} else { // Global level.
				$args = array(
					'post_type'        => DEY_Register_Post_Types::SPECIAL_DAYS_POSTTYPE,
					'post_status'      => 'publish',
					'fields'           => 'ids',
					'numberposts'      => '-1',
					'suppress_filters' => false,
					'meta_query'       => array(
						'relation' => 'OR',
						array(
							'key'     => 'dey_special_day_schedule_type',
							'value'   => '2',
							'compare' => '!=',
						),
						array(
							'key'     => 'dey_special_day_schedule_type',
							'compare' => 'NOT EXISTS',
						),
					),
				);

				$post_ids = get_posts( $args );
				if ( ! dey_check_is_array( $post_ids ) ) {
					return self::$special_dates;
				}

				foreach ( $post_ids as $post_id ) {
					$special_day = dey_get_special_day( $post_id );
					if ( ! $special_day->exists() ) {
						continue;
					}

					self::$special_dates[ $special_day->get_date() ] = array(
						'id'            => $special_day->get_id(),
						'name'          => $special_day->get_name(),
						'order_count'   => $special_day->get_order_count(),
						'price'         => $special_day->get_price(),
						'schedule_type' => $special_day->get_special_day_schedule_type(),
					);
				}
			}

			/**
			 * This hook is used to alter the order local pickup special dates.
			 *
			 * @since 3.0.0
			 */
			self::$special_dates = apply_filters( 'dey_order_local_pickup_special_dates', self::$special_dates );

			return self::$special_dates;
		}
	}
}
