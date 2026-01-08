<?php

/**
 * Pinterest API for Conversions
 *
 * Sources:
 * https://developers.pinterest.com/docs/conversions/updated/
 * https://help.pinterest.com/en/business/article/the-pinterest-api-for-conversions
 * https://developers.pinterest.com/docs/api/v5/#operation/events/create
 */

namespace SweetCode\Pixel_Manager\Pixels\Pinterest;

use SweetCode\Pixel_Manager\Geolocation;
use SweetCode\Pixel_Manager\Helpers;
use SweetCode\Pixel_Manager\HTTP;
use SweetCode\Pixel_Manager\Options;
use SweetCode\Pixel_Manager\S2S;
use SweetCode\Pixel_Manager\Shop;
use SweetCode\Pixel_Manager\Product;

defined('ABSPATH') || exit; // Exit if accessed directly

class Pinterest_APIC extends S2S {

	private static $http;

	private static $instance;

	public static function get_instance() {
		if (is_null(self::$instance)) {
			$http           = new HTTP();      // Create a new HTTP object
			self::$instance = new self($http); // Pass the HTTP object to the constructor
		}
		return self::$instance;
	}

	public function __construct( HTTP $http ) {

		$request_args = array(
			// Add an authorization bearer token to the header
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . Options::get_pinterest_apic_token(),
			),
		);

		$http->set_request_args($request_args);

		self::$http = $http;

		self::hook_into_generic_events();
	}

	/**
	 * Getters for constants
	 */

	public static function is_pixel_s2s_active() {
		return Options::is_pinterest_apic_active();
	}

	/**
	 * Get the key for the identifiers
	 *
	 * @return string
	 */
	protected static function get_identifiers_key() {
		return 'pinterest_user_identifiers_' . Options::get_pinterest_ad_account_id();
	}

	/**
	 * Get the key for the purchase hit
	 *
	 * @return string
	 */
	protected static function get_purchase_hit_key() {
		return 'pmw_pinterest_apic_purchase_hit';
	}

	/**
	 * Get the API version
	 *
	 * @return string
	 */
	private static function get_api_version() {
		return 'v5';
	}

	/**
	 * Get the request URL
	 *
	 * Source: https://developers.pinterest.com/docs/api/v5/#operation/events/create
	 *
	 * @param bool $debug
	 * @return string
	 */
	private static function get_request_url( $debug = false ) {

		$request_url  = 'https://';
		$request_url .= 'api.pinterest.com/';
		$request_url .= self::get_api_version() . '/';
		$request_url .= 'ad_accounts/';
		$request_url .= Options::get_pinterest_ad_account_id() . '/';
		$request_url .= 'events';

		if ($debug) {
			$request_url .= '?test=true'; // Add this query parameter to test the request
		}

		return $request_url;
	}

	/**
	 * Handle Pinterest purchase hit
	 *
	 * TODO: Use the payload helper to add more data https://developers.pinterest.com/payload-helper/
	 *
	 * @param $order
	 * @return void
	 **/
	public static function send_purchase_hit( $order ) {

		self::get_instance();

		if (self::can_not_process_order($order)) {
			return;
		}

		$identifiers = self::get_identifiers_from_order($order);

		// Add event data
		$apic_event_data = array(
			'event_name'       => 'checkout',
			'action_source'    => 'web',
			'event_time'       => time(),
			'event_id'         => (string) $order->get_id(),
			'event_source_url' => (string) $order->get_checkout_order_received_url(),
			'opt_out'          => false,

		);

		// Add user data
		$apic_event_data['user_data'] = self::get_user_data_for_order($identifiers, $order);

		// add order data
		$apic_event_data['custom_data'] = array(
			'currency'    => (string) $order->get_currency(),
			'value'       => (string) Shop::get_order_value_total_marketing($order, true),
			// Save the amount of products in num_items
			'num_items'   => (int) $order->get_item_count(),
			// order_id
			'order_id'    => (string) $order->get_id(),
			// Save an array of the product IDs into the product_ids array
			'content_ids' => self::get_product_ids($order),
			// Save into contents an array of the products with their quantity and item_price
			'contents'    => self::get_order_contents_with_quantity_and_item_price($order),
		);

		$payload = array(
			'data' => array( $apic_event_data ),
		);

		self::$http->post(self::get_request_url(), $payload);

		// TODO: This should be behind some UX checkbox that the user can enable/disable (because the test endpoint is not same as validation endpoints)
//      if (Options::is_http_request_logging_enabled()) {
//          self::$http->post(self::get_request_url(true), $payload);
//      }

		// Now we let the server know, that the hit has already been successfully sent.
		$order->update_meta_data(self::get_purchase_hit_key(), true);
		$order->save();
	}

	private static function is_one_pinterest_cookie_set( $identifiers ) {
		return (
			isset($identifiers['epik'])
			|| isset($identifiers['derived_epik'])
			|| isset($identifiers['pin_unauth'])
			|| isset($identifiers['pinterest_ct_rt'])
			|| isset($identifiers['pinterest_ct'])
			|| isset($identifiers['pinterest_ct_ua'])
			|| isset($identifiers['pinterest_sess'])
		);
	}

	private static function get_order_contents_with_quantity_and_item_price( $order ) {
		$data = array();
		foreach ($order->get_items() as $order_item) {
			$product_id = Product::get_variation_or_product_id($order_item->get_data(), Options::is_shop_variations_output_active());
			$product    = wc_get_product($product_id);

			// Only add if WC retrieves a valid product
			if (Product::is_not_wc_product($product)) {
				continue;
			}

			$data[] = array(
				'id'         => (string) Product::get_dyn_r_id_for_product_by_pixel_name($product, 'pinterest'),
				'item_price' => (string) Helpers::format_decimal($product->get_price(), 2),
				'quantity'   => (int) $order_item->get_quantity(),
				'item_name'  => $product->get_name(),
			);
		}
		return $data;
	}

	private static function get_product_ids( $order ) {

		$data = array();

		foreach ($order->get_items() as $order_item) {

			$product_id = Product::get_variation_or_product_id($order_item->get_data(), Options::is_shop_variations_output_active());
			$product    = wc_get_product($product_id);

			// Only add if WC retrieves a valid product
			if (Product::is_not_wc_product($product)) {
				continue;
			}

			$product_id_compiled = Product::get_dyn_r_id_for_product_by_pixel_name($product, 'pinterest');

			$data[] = (string) $product_id_compiled;
		}

		return $data;
	}

	// TODO: Use the payload helper to add more data https://developers.pinterest.com/payload-helper/
	public static function send_event_hit( $event_data ) {

		if (!Options::is_pinterest_apic_active()) {
			return;
		}

		if (Shop::do_not_track_user(get_current_user_id())) {
			return;
		}

		$user_ip = Geolocation::get_user_ip();

		if ($user_ip) {
			$event_data['user_data']['client_ip_address'] = $user_ip;
		}

		$payload = array(
			'data' => array( $event_data ),
		);

		self::$http->post(self::get_request_url(), $payload);

		// TODO: This should be behind some UX checkbox that the user can enable/disable (because the test endpoint is not same as validation endpoints)
//      if (Options::is_http_request_logging_enabled()) {
//          self::$http->post(self::get_request_url(true), $payload);
//      }
	}

	// https://developers.pinterest.com/docs/conversions/event/#Event%20level%20requirements
	protected static function get_user_data_for_order( $identifiers, $order ) {

		$user_data_output = array();

		// Required parameter
		if (isset($identifiers['ip_address'])) {
			$user_data_output['client_ip_address'] = $identifiers['ip_address'];
		}

		// Required parameter
		if (isset($identifiers['user_agent'])) {
			$user_data_output['client_user_agent'] = $identifiers['user_agent'];
		}

		if (isset($identifiers['derived_epik'])) {
			$user_data_output['click_id'] = $identifiers['derived_epik'];
		}

		// Optional parameters
		if (isset($identifiers['epik'])) {
			$user_data_output['click_id'] = $identifiers['epik'];
		}

		if (Options::is_pinterest_advanced_matching_active()) {

			$user_data_input = Helpers::get_user_data_object($order);

			if (isset($user_data_input->email->pinterest)) {
				$user_data_output['em'] = array( $user_data_input->email->pinterest );
			}

			if (isset($user_data_input->phone->pinterest)) {
				$user_data_output['ph'] = array( $user_data_input->phone->pinterest );
			}

			if (isset($user_data_input->first_name->pinterest)) {
				$user_data_output['fn'] = array( $user_data_input->first_name->pinterest );
			}

			if (isset($user_data_input->last_name->pinterest)) {
				$user_data_output['ln'] = array( $user_data_input->last_name->pinterest );
			}

			if (isset($user_data_input->city->pinterest)) {
				$user_data_output['ct'] = array( $user_data_input->city->pinterest );
			}

			if (isset($user_data_input->state->pinterest)) {
				$user_data_output['st'] = array( $user_data_input->state->pinterest );
			}

			if (isset($user_data_input->zip->pinterest)) {
				$user_data_output['zp'] = array( $user_data_input->zip->pinterest );
			}

			if (isset($user_data_input->country->pinterest)) {
				$user_data_output['country'] = array( $user_data_input->country->pinterest );
			}

			if (isset($user_data_input->id->sha256)) {
				$user_data_output['external_id'] = array( $user_data_input->id->sha256 );
			}
		}

		return $user_data_output;
	}

	/**
	 * Add identifiers specific to Pinterest APIC
	 *
	 * @param $identifiers
	 * @return mixed
	 */
	protected static function add_pixel_specific_identifiers( $identifiers ) {

		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

		if (isset($_cookie['_epik']) && self::is_valid_epik($_cookie['_epik'])) {
			$identifiers['epik'] = $_cookie['_epik'];
		}

		if (isset($_cookie['_derived_epik']) && self::is_valid_derived_epik($_cookie['_derived_epik'])) {
			$identifiers['derived_epik'] = $_cookie['_derived_epik'];
		}

		// Set the _pin_unauth cookie if it exists
		if (isset($_cookie['_pin_unauth']) && self::is_valid_generic_pinterest_cookie($_cookie['_pin_unauth'])) {
			$identifiers['pin_unauth'] = $_cookie['_pin_unauth'];
		}

		// Set the _pinterest_ct_rt cookie if it exists
		if (isset($_cookie['_pinterest_ct_rt']) && self::is_valid_generic_pinterest_cookie($_cookie['_pinterest_ct_rt'])) {
			$identifiers['pinterest_ct_rt'] = $_cookie['_pinterest_ct_rt'];
		}

		// Set the _pinterest_ct cookie if it exists
		if (isset($_cookie['_pinterest_ct']) && self::is_valid_generic_pinterest_cookie($_cookie['_pinterest_ct'])) {
			$identifiers['pinterest_ct'] = $_cookie['_pinterest_ct'];
		}

		// Set the _pinterest_ct_ua cookie if it exists
		if (isset($_cookie['_pinterest_ct_ua']) && self::is_valid_generic_pinterest_cookie($_cookie['_pinterest_ct_ua'])) {
			$identifiers['pinterest_ct_ua'] = $_cookie['_pinterest_ct_ua'];
		}

		// Set the _pinterest_sess cookie if it exists
		if (isset($_cookie['_pinterest_sess']) && self::is_valid_generic_pinterest_cookie($_cookie['_pinterest_sess'])) {
			$identifiers['pinterest_sess'] = $_cookie['_pinterest_sess'];
		}

		/**
		 * Enrich the data with transient session data
		 */

		$transient_identifiers = Shop::get_transient_identifiers_from_session();

		if (!isset($_cookie['_epik']) && isset($transient_identifiers['epik'])) {
			$identifiers['epik'] = $transient_identifiers['epik'];
		}

		return $identifiers;
	}

	/**
	 * Check if the _epik cookie is valid
	 *
	 * @param $cookie
	 * @return bool
	 */
	public static function is_valid_epik( $cookie ) {
		return (bool) preg_match('/^dj0yJnU9[a-zA-Z0-9\-\_]{100}/', $cookie);
	}

	/**
	 * Check if the _derived_epik cookie is valid
	 *
	 * @param $cookie
	 * @return bool
	 */
	private static function is_valid_derived_epik( $cookie ) {
		return (bool) preg_match('/^dj0yJnU9[a-zA-Z0-9\-\_]{134}/', $cookie);
	}

	/**
	 * Check if the generic Pinterest cookie is valid
	 *
	 * @param $cookie
	 * @return bool
	 */
	private static function is_valid_generic_pinterest_cookie( $cookie ) {
		return (bool) preg_match('/^["a-zA-Z0-9=]*$/', $cookie);
	}

	/**
	 * Generate random identifiers
	 */

	/**
	 * Get random base identifiers
	 *
	 * @return string[]
	 */
	protected static function get_random_base_identifiers() {
		return array(
			'epik' => self::generate_random_epik(),
		);
	}

	/**
	 * Generate a random epik string
	 *
	 * Random string with the length of 100 and contains small letters and numbers
	 *
	 * @return string
	 */
	private static function generate_random_epik() {
		return 'dj0yJnU9' . self::generate_random_partial_epik_string(100);
	}

	/**
	 * Generate a random partial epik string
	 *
	 * It can contain small letters, capital letters and numbers
	 *
	 * @return string
	 */
	private static function generate_random_partial_epik_string() {
		return (string) substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 100);
	}
}
