<?php

/**
 * Handles the Product Delivery.
 *
 * @since 1.0.0
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('DEY_Product_Delivery_Handler')) {

	/**
	 * Class.
	 * */
	class DEY_Product_Delivery_Handler {

		/**
		 * Product.
		 *
		 * @var object
		 */
		protected $product;

		/**
		 * Product ID.
		 *
		 * @var int
		 */
		protected $product_id;

		/**
		 * Parent ID.
		 *
		 * @var int
		 */
		protected $parent_id;

		/**
		 * Booked Dates.
		 *
		 * @var array
		 */
		protected static $booked_dates = array();

		/**
		 * Available Dates.
		 *
		 * @var array
		 */
		protected static $available_dates = array();

		/**
		 * Special Dates.
		 *
		 * @var array
		 */
		protected static $special_dates = array();

		/**
		 * Holidays.
		 *
		 * @var array
		 */
		protected static $holidays = array();

		/**
		 * Expected first date.
		 *
		 * @var string
		 */
		protected static $expected_first_date;

		/**
		 * Expected last date.
		 *
		 * @var string
		 */
		protected static $expected_last_date;

		/**
		 * Script exists.
		 *
		 * @var bool
		 */
		protected static $script_exists = false;

		/**
		 * Construct the class.
		 *
		 * @return void.
		 */
		public function __construct( $product ) {
			if (is_a('WC_Product', $product)) {
				$this->product = $product;
			} elseif ($product) {
				$this->product = wc_get_product($product);
			}

			if (!is_object($this->product)) {
				return;
			}

			$this->product_id = $this->product->get_id();
			$this->parent_id = ( $this->product->get_parent_id() ) ? $this->product->get_parent_id() : $this->product->get_id();
		}

		/**
		 * Update the meta data from product.
		 *
		 * @since 1.0.0
		 * @param string $key Key of the meta data.
		 * @param mixed  $value Value of the meta data.
		 * @return bool/int
		 */
		public function update_meta( $key, $value ) {
			return dey_update_post_meta($this->parent_id, $key, $value);
		}

		/**
		 * Get the meta data from product.
		 */
		public function get_meta( $key ) {
			return get_post_meta($this->parent_id, $key, true);
		}

		/**
		 * Get the booked dates.
		 */
		public function get_booked_dates() {
			return $this->prepare_booked_dates();
		}

		/**
		 * Get the holidays.
		 */
		public function get_holidays() {
			return $this->prepare_holidays();
		}

		/**
		 * Get the special dates.
		 *
		 * @return array
		 */
		public function get_special_dates() {
			return $this->prepare_special_dates();
		}

		/**
		 * Get the available dates.
		 *
		 * @return array.
		 */
		public function get_available_dates() {
			return $this->prepare_available_dates();
		}

		/**
		 * Get the expected first date.
		 *
		 * @return array.
		 */
		public function get_expected_first_date() {
			return $this->prepare_expected_first_date();
		}

		/**
		 * Get the expected last date.
		 *
		 * @return array.
		 */
		public function get_expected_last_date() {
			return $this->prepare_expected_last_date();
		}

		/**
		 * Populate the data.
		 *
		 * @return void
		 */
		private function populate_data() {
			$this->prepare_booked_dates();
			$this->prepare_holidays();
			$this->prepare_special_dates();
		}

		/**
		 * Initialize the product delivery.
		 *
		 * @param WP_Post $product Product Object.
		 */
		public static function init( $product ) {
			return new self($product);
		}

		/**
		 * Render the product delivery slots.
		 *
		 * @return void.
		 */
		public function render() {
			$mode = $this->get_meta('dey_delivery_slot_mode');

			switch ($mode) {
				case '2':
					$this->render_expected_days();
					break;
				default:
					$this->render_calender();
					break;
			}
		}

		/**
		 * Render the calender view.
		 *
		 * @return void.
		 */
		private function render_calender() {
			$mode = $this->get_meta('dey_delivery_time_mode');

			$fields = array(
				'dey_delivery_date' => array(
					'type' => ( '2' === get_option('dey_advanced_calender_display_mode') ) ? 'show_calender' : 'text',
					'label' => dey_get_product_delivery_date_field_label(),
					'required' => ( 'yes' === $this->get_meta('dey_delivery_calender_mandatory_field') ),
					'class' => array( 'form-row-wide', 'dey-form-row' ),
					'input_class' => array( 'dey-product-delivery-date-field' ),
					'default' => '',
				),
			);

			switch ($mode) {
				case '3':
					$fields['dey_delivery_date']['input_class'][] = 'dey-datepicker';

					$fields['dey_product_delivery_date_time_slots'] = array(
						'type' => 'select',
						'label' => dey_get_product_delivery_time_slots_field_label(),
						'required' => ( 'no' !== $this->get_meta('dey_delivery_time_slot_mandatory_field') ),
						'class' => array( 'form-row-wide', 'dey-form-row' ),
						'input_class' => array( 'dey-delivery-date', 'dey_select2' ),
						'options' => array( '' => dey_get_product_delivery_default_time_slot_label() ),
						'default' => '',
					);
					break;
				case '2':
					$fields['dey_delivery_date']['input_class'][] = 'dey-datetimepicker';
					break;
				default:
					$fields['dey_delivery_date']['input_class'][] = 'dey-datepicker';
					break;
			}

			$available_times = $this->get_available_times();
			$args = array(
				'fields' => $fields,
				'product_id' => $this->product_id,
				'price' => dey_price(dey_get_product_price_to_display($this->product, $this->product->get_price())),
				'available_dates' => $this->get_available_dates(),
				'min_date' => $this->get_calender_first_date(), // For client date object.
				'max_date' => $this->get_calender_last_date(), // For client date object.
				'first_time_slot' => $this->get_meta('dey_delivery_display_first_available_time_slot'),
				'available_times' => $available_times,
			);

			// Enqueue the required assets.
			self::enqueue_assets();

			dey_get_template('product/product-delivery-fields.php', $args);
		}

		/**
		 * Render the expected days view.
		 *
		 * @return void.
		 */
		private function render_expected_days() {
			$msg = dey_get_expected_product_delivery_date_message($this->get_expected_first_date(), $this->get_expected_last_date());

			dey_get_template('product/expected-days.php', array( 'msg' => $msg ));
		}

		/**
		 * Enqueue the assets.
		 *
		 * @return void.
		 */
		public static function enqueue_assets() {
			if ( self::$script_exists ) {
				return;
			}

			DEY_Frontend_Assets::enqueue_style( 'dey-jquery-ui' );
			DEY_Frontend_Assets::enqueue_style( 'jquery-ui-datepicker-addon' );

			DEY_Frontend_Assets::set_additional_scripts( 'jquery-ui-timpicker-addon' );
			DEY_Frontend_Assets::set_additional_scripts( 'dey-product-delivery-datepicker' );

			self::$script_exists = true;
		}

		/**
		 * Prepare the expected first date.
		 *
		 * @return string.
		 */
		private function prepare_expected_first_date() {
			if (isset(self::$expected_first_date[$this->parent_id])) {
				return self::$expected_first_date[$this->parent_id];
			}

			self::prepare_expected_dates();

			return self::$expected_first_date[$this->parent_id];
		}

		/**
		 * Prepare the expected last date.
		 *
		 * @return string.
		 */
		private function prepare_expected_last_date() {
			if (isset(self::$expected_last_date[$this->parent_id])) {
				return self::$expected_last_date[$this->parent_id];
			}

			self::prepare_expected_dates();

			return self::$expected_last_date[$this->parent_id];
		}

		/**
		 * Prepare the expected dates.
		 *
		 * @return void.
		 */
		private function prepare_expected_dates() {
			$this->populate_data();

			// Expected first date.
			$first_date_object = $this->get_shipping_date_object();
			$start_count = 0;
			$from_range = intval($this->get_meta('dey_delivery_expected_date_from'));
			while ($start_count < $from_range) {
				if (isset(self::$holidays[$this->parent_id][$first_date_object->format('Y-m-d')]) || isset(self::$holidays[$this->parent_id][$first_date_object->format('m-d')])) {
					$first_date_object->modify('+1days');
					continue;
				}

				$first_date_object->modify('+1days');
				++$start_count;
			}

			self::$expected_first_date[$this->parent_id] = $first_date_object->format('Y-m-d');

			// Expected last date.
			$last_date_object = $this->get_shipping_date_object();
			$start_count = 0;
			$to_range = intval($this->get_meta('dey_delivery_expected_date_to'));
			while ($start_count < $to_range) {
				if (isset(self::$holidays[$this->parent_id][$last_date_object->format('Y-m-d')]) || isset(self::$holidays[$this->parent_id][$last_date_object->format('m-d')])) {
					$last_date_object->modify('+1days');
					continue;
				}

				$last_date_object->modify('+1days');
				++$start_count;
			}

			self::$expected_last_date[$this->parent_id] = $last_date_object->format('Y-m-d');
		}

		/**
		 * Get the calender first date.
		 *
		 * @return string
		 */
		public function get_calender_first_date() {
			$first_date = '';
			if (dey_check_is_array(self::$available_dates[$this->parent_id])) {
				reset(self::$available_dates[$this->parent_id]);
				$first_date = key(self::$available_dates[$this->parent_id]);
			}

			return str_replace('-', '/', $first_date);
		}

		/**
		 * Get the calender last date.
		 *
		 * @return string
		 */
		public function get_calender_last_date() {
			$last_date = '';
			if (dey_check_is_array(self::$available_dates[$this->parent_id])) {
				end(self::$available_dates[$this->parent_id]);
				$last_date = key(self::$available_dates[$this->parent_id]);
			}

			return str_replace('-', '/', $last_date);
		}

		/**
		 * Get the available times.
		 *
		 * @return array
		 */
		public function get_available_times() {
			$available_times = array(
				'hour_min' => 0,
				'hour_max' => 23,
				'minute_min' => 0,
				'minute_max' => 59,
				'min_time' => '00:00',
				'max_time' => '23:59',
			);

			$time_from = $this->get_meta('dey_delivery_available_time_from');
			if ($time_from) {
				$exploded_time_from = explode(':', $time_from);
				$available_times['hour_min'] = $exploded_time_from[0];
				$available_times['minute_min'] = $exploded_time_from[1];
				$available_times['min_time'] = $time_from;
			}

			$time_to = $this->get_meta('dey_delivery_available_time_to');
			if ($time_to) {
				$exploded_time_to = explode(':', $time_to);
				$available_times['hour_max'] = $exploded_time_to[0];
				$available_times['minute_max'] = $exploded_time_to[1];
				$available_times['max_time'] = $time_to;
			}
			/**
			 * This hook is used to alter the product delivery available times.
			 *
			 * @since 1.0
			 */
			return apply_filters('dey_product_delivery_available_times', $available_times);
		}

		/**
		 * Is valid time slot?.
		 *
		 * @return array.
		 */
		public function is_valid_time_slot( $time_slot_id, $date ) {
			if ('soon' === $time_slot_id) {
				return true;
			}

			$time_slots = array_filter((array) $this->get_meta('dey_delivery_time_slots'));
			if (!isset($time_slots[$time_slot_id])) {
				return false;
			}

			// Return if the time slot is only applicable for pickup.
			if (isset($time_slots[$time_slot_id]['schedule_type']) && '3' === $time_slots[$time_slot_id]['schedule_type']) {
				return false;
			}

			$time_slot = $time_slots[$time_slot_id];
			$date_object = DEY_Date_Time::get_date_time_object($date . ' ' . $time_slot['from_time']);
			$current_date_object = DEY_Date_Time::get_date_time_object('now');

			// Validate if the time slot crossed the current time.
			if ($current_date_object >= $date_object) {
				return false;
			}

			// Validate the time slot week days.
			if ('yes' === $time_slot['week_days_enabled'] && !in_array($date_object->format('w') + 1, $time_slot['week_days'])) {
				return false;
			}

			// Validate the time slot usage count.
			if (isset($time_slot['used_order_count'])) {
				$usage_count = isset($time_slot['used_order_count'][$date_object->format('Y-m-d')]) ? floatval($time_slot['used_order_count'][$date_object->format('Y-m-d')]) : 0;
				$order_count = ( !empty($time_slot['order_count']) ) ? $time_slot['order_count'] : $this->get_meta('dey_delivery_time_slot_max');

				if (floatval($order_count) <= floatval($usage_count)) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Get the shipping date object.
		 *
		 * @return array.
		 */
		public function get_shipping_date_object() {

			$cutoff_time = $this->get_meta('dey_delivery_cutoff_time');
			$cutoff_time = ( empty($cutoff_time) ) ? '23:59' : $cutoff_time;
			$next_day_cutoff_time = $this->get_meta('dey_delivery_next_day_cutoff_time');
			$present_date_object = DEY_Date_Time::get_date_time_object('now');
			$current_date_object = DEY_Date_Time::get_date_time_object('now');

			// Consider the business days.
			$business_days = $this->get_meta('dey_delivery_business_days');
			$business_days = dey_parse_business_day_option($business_days, dey_business_day_default_option());
			$processing_time = $this->get_meta('dey_delivery_processing_time');
			$processing_time = dey_parse_processing_hours($processing_time);

			if (dey_check_is_array($business_days) && dey_processing_hours_exists($processing_time)) {
				$loop = true;
				$total_time_diff = 0;
				$processing_hours = dey_get_processing_hours($processing_time);
				while ($loop) {
					// Exit loop once processing hours reached.
					if ($total_time_diff >= $processing_hours) {
						$loop = false;
						continue;
					}

					$current_day = $current_date_object->format('w') + 1;
					$enabled = $business_days[$current_day]['enable'];
					$opening_time = $business_days[$current_day]['opening_time'];
					$closing_time = $business_days[$current_day]['closing_time'];

					// Specific days.
					if (( 'no' === $enabled ) && !isset(self::$special_dates[$this->parent_id][$current_date_object->format('Y-m-d')])) {
						$current_date_object->modify("tomorrow $opening_time");
						continue;
					}

					// Holidays.
					if (!isset(self::$special_dates[$this->parent_id][$current_date_object->format('Y-m-d')]) && ( isset(self::$holidays[$this->parent_id][$current_date_object->format('Y-m-d')]) || isset(self::$holidays[$this->parent_id][$current_date_object->format('m-d')]) )) {
						$current_date_object->modify("tomorrow $opening_time");
						continue;
					}

					$total_time_diff += dey_get_business_hours($current_date_object, $opening_time, $closing_time);

					$date_after_processing = dey_get_date_after_processing($current_date_object, $processing_time, $total_time_diff, $opening_time, $closing_time);
					$cutoff_date_object = DEY_Date_Time::get_date_time_object($current_date_object->format('Y-m-d') . ' ' . $cutoff_time);

					// Modify the current date object if the date after processing hour is less than cutoff date or after processing hour.
					if (( !$date_after_processing ) || ( $date_after_processing && $cutoff_date_object < $date_after_processing )) {
						$current_date_object->modify("tomorrow $opening_time");

						if (!empty($next_day_cutoff_time) && ( $present_date_object->format('Y-m-d') === $current_date_object->format('Y-m-d') )) {
							$next_day_date_object = DEY_Date_Time::get_date_time_object($next_day_cutoff_time);
							if ($present_date_object > $next_day_date_object) {
								$current_date_object->modify("tomorrow $opening_time");
							}
						}
					}

					continue;
				}
			}

			if ($present_date_object->format('Y-m-d') === $current_date_object->format('Y-m-d')) {
				$cutoff_date_object = DEY_Date_Time::get_date_time_object($present_date_object->format('Y-m-d') . ' ' . $cutoff_time);

				if ($cutoff_date_object < $present_date_object) {
					$current_date_object->modify('tomorrow');

					if (!empty($next_day_cutoff_time)) {
						$next_day_date_object = DEY_Date_Time::get_date_time_object($present_date_object->format('Y-m-d') . ' ' . $next_day_cutoff_time);
						if ($present_date_object > $next_day_date_object) {
							$current_date_object->modify('tomorrow');
						}
					}
				}
			}

			// Consider the delivery days.
			$delivery_days = array_filter((array) $this->get_meta('dey_delivery_days'));
			if (dey_check_is_array($delivery_days)) {
				$loop = true;
				while ($loop) {
					// Specific days.
					if (!isset(self::$special_dates[$this->parent_id][$current_date_object->format('Y-m-d')])) {
						$loop = false;
						continue;
					}

					// Holidays.
					if (isset(self::$holidays[$this->parent_id][$current_date_object->format('Y-m-d')]) || isset(self::$holidays[$this->parent_id][$current_date_object->format('m-d')])) {
						$current_date_object->modify('+1days');
						continue;
					}

					// Business days.
					if (!in_array($current_date_object->format('w') + 1, $delivery_days)) {
						$current_date_object->modify('+1days');
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
		 * @return string.
		 */
		public function get_weekday_price( $date_object ) {
			$price = 0;
			$weekdays_prices = array_filter((array) $this->get_meta('dey_delivery_weekdays_prices'));
			if (!isset($weekdays_prices[$date_object->format('w') + 1])) {
				return $price;
			}

			return floatval($weekdays_prices[$date_object->format('w') + 1]);
		}

		/**
		 * Get the same day price.
		 *
		 * @since 3.2.0
		 * @param object $date_object Date object.
		 * @return float.
		 */
		public function get_same_day_price( $date_object ) {
			$same_day_price = ( $date_object->format('Y-m-d') === DEY_Date_Time::get_date_time_object('now')->format('Y-m-d') ) ? $this->get_meta('dey_delivery_same_day_fee') : 0;

			/**
			 * This hook is to alter the same day price for Product Delivery.
			 *
			 * @param float $same_day_price Same Day Price.
			 * @since 3.2.0
			 */
			return apply_filters('dey_product_delivery_same_day_price', floatval($same_day_price), $date_object, $this);
		}

		/**
		 * Get the next day price.
		 *
		 * @since 3.2.0
		 * @param object $date_object Date object.
		 * @return float.
		 */
		public function get_next_day_price( $date_object ) {
			$next_day_price = ( $date_object->format('Y-m-d') === DEY_Date_Time::get_date_time_object('now')->modify('+1 days')->format('Y-m-d') ) ? $this->get_meta('dey_delivery_next_day_fee') : 0;

			/**
			 * This hook is to alter the next day price for Product Delivery.
			 *
			 * @param float $next_day_price Next Day Price.
			 * @since 3.2.0
			 */
			return apply_filters('dey_product_delivery_next_day_price', floatval($next_day_price), $date_object, $this);
		}

		/**
		 * Prepare the available dates.
		 *
		 * @return array.
		 */
		private function prepare_available_dates() {
			if (isset(self::$available_dates[$this->parent_id])) {
				return self::$available_dates[$this->parent_id];
			}

			// Populate the required data.
			$this->populate_data();

			$i = 0;
			$max_date = intval($this->get_meta('dey_delivery_days_availablity'));
			$delivery_days = array_filter((array) $this->get_meta('dey_delivery_days'));
			$date_object = $this->get_shipping_date_object();

			do {
				$per_day = $this->get_meta('dey_delivery_max_per_day');

				// Consider the delivery days.
				if (!isset(self::$special_dates[$this->parent_id][$date_object->format('Y-m-d')]) && dey_check_is_array($delivery_days) && !in_array($date_object->format('w') + 1, $delivery_days)) {
					$date_object->modify('+1days');
					continue;
				}

				$count = true;
				// Particular holiday.
				if (!isset(self::$special_dates[$this->parent_id][$date_object->format('Y-m-d')]) && isset(self::$holidays[$this->parent_id][$date_object->format('Y-m-d')])) {
					$available_date = array(
						'l' => self::$holidays[$this->parent_id][$date_object->format('Y-m-d')]['label'],
						't' => 'hy',
					);
					$count = false;
					// Repeat holiday.
				} elseif (!isset(self::$special_dates[$this->parent_id][$date_object->format('Y-m-d')]) && isset(self::$holidays[$this->parent_id][$date_object->format('m-d')])) {
					$available_date = array(
						'l' => self::$holidays[$this->parent_id][$date_object->format('m-d')]['label'],
						't' => 'hy',
					);
					$count = false;
					// Partial Booked/Booked day.
				} elseif (isset(self::$booked_dates[$this->parent_id][$date_object->format('Y-m-d')])) {

					if (isset(self::$special_dates[$this->parent_id][$date_object->format('Y-m-d')])) {
						$per_day = self::$special_dates[$this->parent_id][$date_object->format('Y-m-d')]['order_count'];
					}

					if ('' == $per_day) {
						$available_date = array(
							'l' => dey_get_product_delivery_partially_booked_label(),
							't' => 'pb',
						);
					} elseif (intval($per_day) > self::$booked_dates[$this->parent_id][$date_object->format('Y-m-d')]) {
						$remaining_count = intval($per_day) - self::$booked_dates[$this->parent_id][$date_object->format('Y-m-d')];
						$available_date = array(
							/* translators: %s remaining count */
							'l' => sprintf(dey_get_product_delivery_remaining_count_label(), $remaining_count),
							't' => 'pb',
						);
					} else {
						$available_date = array(
							'l' => dey_get_product_delivery_booked_label(),
							't' => 'fb',
						);
					}
					// Special day
				} elseif (isset(self::$special_dates[$this->parent_id][$date_object->format('Y-m-d')])) {

					$available_date = array(
						'l' => self::$special_dates[$this->parent_id][$date_object->format('Y-m-d')]['name'],
						't' => 'sy',
					);

					// Normal day
				} else {

					$available_date = array(
						'l' => '',
						't' => 'ny',
					);
				}

				self::$available_dates[$this->parent_id][$date_object->format('Y-m-d')] = $available_date;
				$date_object->modify('+1days');

				// Increase the count if the date as available date.
				if ($count) {
					++$i;
				}
			} while ($i < $max_date);
			/**
			 * This hook is used to alter the product delivery available dates.
			 *
			 * @since 1.0
			 */
			self::$available_dates[$this->parent_id] = apply_filters('dey_product_delivery_available_dates', self::$available_dates[$this->parent_id], $this->product);

			return self::$available_dates[$this->parent_id];
		}

		/**
		 * Prepare the booked dates.
		 *
		 * @return array.
		 */
		private function prepare_booked_dates() {
			if (isset(self::$booked_dates[$this->parent_id])) {
				return self::$booked_dates[$this->parent_id];
			}

			$args = array(
				'post_type' => DEY_Register_Post_Types::PRODUCT_DELIVERY_POSTTYPE,
				'post_status' => dey_get_product_delivery_statuses(),
				'fields' => 'ids',
				'numberposts' => '-1',
				'suppress_filters' => false,
				'post_parent' => dey_get_product_id($this->parent_id),
				'meta_key' => 'dey_delivery_mode',
				'meta_value' => '2',
				'meta_compare' => '!=',
			);

			$post_ids = get_posts($args);

			self::$booked_dates[$this->parent_id] = array();
			if (!dey_check_is_array($post_ids)) {
				return self::$booked_dates[$this->parent_id];
			}

			foreach ($post_ids as $post_id) {
				$delivery = dey_get_product_delivery($post_id);

				$booked_date = DEY_Date_Time::get_date_time_object($delivery->get_delivery_date())->format('Y-m-d');

				if (isset(self::$booked_dates[$this->parent_id][$booked_date])) {
					self::$booked_dates[$this->parent_id][$booked_date] = self::$booked_dates[$this->parent_id][$booked_date] + 1;
				} else {
					self::$booked_dates[$this->parent_id][$booked_date] = 1;
				}
			}
			/**
			 * This hook is used to alter the product delivery booked dates.
			 *
			 * @since 1.0
			 */
			self::$booked_dates[$this->parent_id] = apply_filters('dey_product_delivery_booked_dates', self::$booked_dates[$this->parent_id], $this->product);

			return self::$booked_dates[$this->parent_id];
		}

		/**
		 * Prepare the holidays.
		 *
		 * @return array.
		 */
		public function prepare_holidays() {
			if (isset(self::$holidays[$this->parent_id])) {
				return self::$holidays[$this->parent_id];
			}

			$holidays = array_filter((array) $this->get_meta('dey_delivery_holidays'));

			self::$holidays[$this->parent_id] = array();
			if (!dey_check_is_array($holidays)) {
				return self::$holidays[$this->parent_id];
			}

			foreach ($holidays as $holiday) {
				if (!dey_check_is_array($holiday) || '3' === $holiday['schedule_type']) {
					continue;
				}

				if (empty($holiday['from_date']) && empty($holiday['to_date'])) {
					continue;
				}

				$from_date_object = DEY_Date_Time::get_date_time_object($holiday['from_date']);
				$to_date_object = DEY_Date_Time::get_date_time_object($holiday['to_date']);

				do {

					if ('yes' === $holiday['recurring']) {
						$date = $from_date_object->format('m-d');
					} else {
						$date = $from_date_object->format('Y-m-d');
					}

					self::$holidays[$this->parent_id][$date] = array(
						'label' => $holiday['name'],
						'y' => $from_date_object->format('Y'),
						'm' => $from_date_object->format('m'),
						'd' => $from_date_object->format('d'),
						'repeat' => $holiday['recurring'],
					);

					$from_date_object->modify('+1days');
				} while ($from_date_object <= $to_date_object);
			}

			/**
			 * This hook is used to alter the product delivery holidays.
			 *
			 * @since 1.0.0
			 */
			self::$holidays[$this->parent_id] = apply_filters('dey_product_delivery_holiday_dates', self::$holidays[$this->parent_id], $this->product);

			return self::$holidays[$this->parent_id];
		}

		/**
		 * Prepare the special dates.
		 *
		 * @return array.
		 */
		public function prepare_special_dates() {
			if (isset(self::$special_dates[$this->parent_id])) {
				return self::$special_dates[$this->parent_id];
			}

			$special_dates = array_filter((array) $this->get_meta('dey_delivery_special_days'));

			self::$special_dates[$this->parent_id] = array();
			if (!dey_check_is_array($special_dates)) {
				return self::$special_dates[$this->parent_id];
			}

			foreach ($special_dates as $key => $special_day) {

				if (!dey_check_is_array($special_day)) {
					continue;
				}

				$special_day['id'] = $key;

				self::$special_dates[$this->parent_id][$special_day['date']] = $special_day;
			}
			/**
			 * This hook is used to alter the product delivery special days.
			 *
			 * @since 1.0
			 */
			self::$special_dates[$this->parent_id] = apply_filters('dey_product_delivery_special_dates', self::$special_dates[$this->parent_id], $this->product);

			return self::$special_dates[$this->parent_id];
		}
	}

}
