<?php

/**
 * Handles the product delivery slots data store.
 *
 * @since 3.8.0
 * */
defined('ABSPATH') || exit; // Exit if accessed directly.

if (!class_exists('DEY_Product_Data_Store')) {

	/**
	 * Class.
	 *
	 * @since 3.8.0
	 * */
	class DEY_Product_Data_Store {

		/**
		 * Product ID.
		 *
		 * @since 3.8.0
		 * @var int
		 */
		protected $product_id;

		/**
		 * Product.
		 *
		 * @since 3.8.0
		 * @var object
		 */
		protected $product;

		/**
		 * Data.
		 *
		 * @since 3.8.0
		 * @var array
		 */
		protected $data;

		/**
		 * Meta Keys
		 * 
		 * @since 3.8.0
		 * @var array
		 */
		protected static $meta_keys = array(
			'dey_delivery_slot_type',
			'dey_product_scheduler_type',
			'dey_delivery_slot_mode',
			'dey_delivery_days_availablity',
			'dey_delivery_weekdays_prices',
			'dey_delivery_same_day_fee',
			'dey_delivery_next_day_fee',
			'dey_delivery_max_per_day',
			'dey_delivery_calender_mandatory_field',
			'dey_delivery_time_mode',
			'dey_delivery_available_time_from',
			'dey_delivery_available_time_to',
			'dey_delivery_expected_date_from',
			'dey_delivery_expected_date_to',
			'dey_delivery_days',
			'dey_delivery_cutoff_time',
			'dey_delivery_next_day_cutoff_time',
			'dey_delivery_processing_time',
			'dey_delivery_business_days',
			'dey_delivery_disable_total_payable',
			'dey_delivery_fee_display_hide',
			'dey_pickup_days_availablity',
			'dey_pickup_weekdays_prices',
			'dey_pickup_same_day_fee',
			'dey_pickup_next_day_fee',
			'dey_pickup_max_per_day',
			'dey_pickup_location_mandatory_field',
			'dey_pickup_calender_mandatory_field',
			'dey_pickup_time_mode',
			'dey_pickup_available_time_from',
			'dey_pickup_available_time_to',
			'dey_pickup_days',
			'dey_pickup_same_day_cutoff_time',
			'dey_pickup_next_day_cutoff_time',
			'dey_pickup_processing_time',
			'dey_pickup_business_days',
			'dey_pickup_disable_total_payable',
			'dey_pickup_fee_display_hide',
			'dey_delivery_time_slot_mandatory_field',
			'dey_delivery_enable_as_soon_as_possible',
			'dey_delivery_first_available_time_slot',
			'dey_delivery_time_slot_hide_zero_price',
			'dey_delivery_time_slot_max',
			'dey_delivery_time_slots',
			'dey_pickup_time_slots',
			'dey_delivery_holidays',
			'dey_delivery_special_days',
			'dey_pickup_location_selection_type',
			'dey_pickup_locations',
		);

		/**
		 * Extra Meta Keys
		 * 
		 * @since 3.8.0
		 * @var array
		 */
		protected static $extra_meta_keys = array();

		/**
		 * Construct the class.
		 *
		 * @since 3.5.0
		 * @param object/int $product instance of WC_Product/Product ID.
		 * @return void.
		 */
		public function __construct( $product ) {
			if (!$product) {
				return;
			}

			$product = is_numeric($product) ? wc_get_product($product) : $product;
			if (!is_object($product) || !( $product instanceof WC_Product )) {
				return;
			}

			$this->product = $product;
			$this->product_id = $this->product->get_parent_id() ? $this->product->get_parent_id() : $this->product->get_id();
		}

		/**
		 * Initialize the product.
		 * 
		 * @since 3.8.0
		 * @return object
		 */
		public static function init( $product ) {
			return new self($product);
		}

		/**
		 * Get meta keys.
		 * 
		 * @since 3.8.0
		 * @return array
		 */
		public static function get_meta_keys() {
			return self::$meta_keys + self::$extra_meta_keys;
		}

		/**
		 * Save the product delivery slots data.
		 * 
		 * @since 3.8.0 
		 * @param array $data
		 */
		public function save( $data ) {
			//Return if the data does not contains any values or not an array.
			if (!dey_check_is_array($data)) {
				return;
			}

			$meta_keys = self::get_meta_keys();
			foreach ($data as $meta_key => $meta_value) {
				if (!in_array($meta_key, $meta_keys)) {
					continue;
				}

				update_post_meta($this->product_id, $meta_key, $meta_value);
			}

			/**
			 * This hook is used to do extra action after delivery slots data saved.
			 *
			 * @since 3.8.0
			 * @param int $post Post ID
			 */
			do_action('dey_after_product_delivery_slots_data_saved', $this->product_id, $data);
		}

		/**
		 * Get the product delivery slots data.
		 * 
		 * @since 3.8.0 
		 * @return array $data
		 */
		public function get_data() {
			if (isset($this->data)) {
				return $this->data;
			}

			$this->data = array();
			$product_meta_data = get_post_meta($this->product_id);
			if (!dey_check_is_array($product_meta_data)) {
				return $this->data;
			}

			$meta_keys = self::get_meta_keys();
			foreach ($meta_keys as $meta_key) {
				if (!isset($product_meta_data[$meta_key])) {
					continue;
				}

				$this->data[$meta_key] = $product_meta_data[$meta_key][0];
			}

			return $this->data;
		}
	}

}
