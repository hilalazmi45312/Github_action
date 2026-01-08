<?php

/**
 * Snapchat Conversions API
 * https://businesshelp.snapchat.com/s/article/conversions-api
 * https://docs.snap.com/api/marketing-api/Conversions-API/UsingTheAPI
 */

namespace SweetCode\Pixel_Manager\Pixels\Snapchat;

use SweetCode\Pixel_Manager\Geolocation;
use SweetCode\Pixel_Manager\Helpers;
use SweetCode\Pixel_Manager\HTTP;
use SweetCode\Pixel_Manager\Logger;
use SweetCode\Pixel_Manager\Options;
use SweetCode\Pixel_Manager\Product;
use SweetCode\Pixel_Manager\S2S;
use SweetCode\Pixel_Manager\Shop;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Class Snapchat_CAPI
 * https://snap.com/en-US/privacy/cookie-information
 */
class Snapchat_CAPI extends S2S {

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

		self::$http = $http;

		self::hook_into_generic_events();
	}

	/**
	 * Getters for constants
	 */

	public static function is_pixel_s2s_active() {
		return Options::is_snapchat_capi_active();
	}

	protected static function get_identifiers_key() {
		return 'snapchat_user_identifiers_' . Options::get_snapchat_pixel_id();
	}

	protected static function get_purchase_hit_key() {
		return 'pmw_snapchat_capi_purchase_hit';
	}

	private static function get_api_version() {
		return 'v3';
	}

	// https://docs.snap.com/api/marketing-api/Conversions-API/UsingTheAPI
	private static function get_request_url( $debug = false ) {

		$url  = 'https://';
		$url .= 'tr.snapchat.com/';
		$url .= self::get_api_version() . '/';
		$url .= Options::get_snapchat_pixel_id();
		$url .= '/events';

		if ($debug) {
			$url .= '/validate';
		}

		$url .= '?access_token=' . Options::get_snapchat_capi_token();

		return $url;
	}

	/**
	 * Add identifiers specific to Snapchat CAPI
	 *
	 * @param $identifiers
	 * @return mixed
	 */
	protected static function add_pixel_specific_identifiers( $identifiers ) {

		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

		if (isset($_cookie['_scid'])) {
			$identifiers['sc_click_id'] = $_cookie['_scid'];
		}

		/**
		 * Enrich the data with transient session data
		 */

		$transient_identifiers = Shop::get_transient_identifiers_from_session();

		if (!isset($_cookie['_scid']) && isset($transient_identifiers['scid'])) {
			$identifiers['sc_click_id'] = $transient_identifiers['scid'];
		}

		return $identifiers;
	}

	public static function send_purchase_hit( $order ) {

		self::get_instance();

		if (self::can_not_process_order($order)) {
			return;
		}

		$identifiers = self::get_identifiers_from_order($order);

		$event_data = array(
			'event_name'       => 'PURCHASE',
			'event_time'       => strtotime($order->get_date_created()),
			'event_source_url' => (string) $order->get_checkout_order_received_url(),
			'event_id'         => $order->get_id(),
			'action_source'    => 'WEB',
			'integration'      => 'pmw',
			'user_data'        => self::get_user_data($order, $identifiers),
			'custom_data'      => array(
				'content_category' => self::get_content_category($order),
				'content_ids'      => self::get_content_ids($order),
				'content_name'     => $order->get_checkout_order_received_url(),
				'content_type'     => 'product',
				'contents'         => self::get_contents($order),
				'currency'         => $order->get_currency(),
				'num_items'        => $order->get_item_count(),
				'order_id'         => $order->get_id(),
				'value'            => Shop::get_order_value_total_marketing($order, true),
				'brands'           => self::get_brands($order),
			),
		);

		$payload = array(
			'data' => array( $event_data ),
		);

		self::$http->post(self::get_request_url(), $payload);

		if (Options::is_http_request_logging_enabled()) {
			Logger::debug('Sending Snapchat purchase hit through the debug endpoint.');
			self::$http->post(self::get_request_url(true), $payload);
		}

		$order->update_meta_data(self::get_purchase_hit_key(), true);
		$order->save();
	}

	public static function send_event_hit( $event_data ) {

		self::get_instance();

		if (!Options::is_snapchat_capi_active()) {
			return;
		}

		if (Shop::do_not_track_user(get_current_user_id())) {
			return;
		}

		$event_data['user_data']['client_ip_address'] = Geolocation::get_user_ip();

		$payload = array(
			'data' => array( $event_data ),
		);

		self::$http->post(self::get_request_url(), $payload);

		if (Options::is_http_request_logging_enabled()) {
			Logger::debug('Sending Snapchat purchase hit through the debug endpoint.');
			self::$http->post(self::get_request_url(true), $payload);
		}
	}

	/**
	 * Returns a unique array of all the category names of the products in the order
	 */
	private static function get_content_category( $order ) {
		$category_names = array();

		foreach ($order->get_items() as $item) {
			$product = $item->get_product();

			if ($product) {

				$category_ids = $product->get_category_ids();

				foreach ($category_ids as $category_id) {

					$term = get_term($category_id);

					if (is_wp_error($term)) {
						Logger::error('Snapchat CAPI: get_content_category: ' . $term->get_error_message());
						continue;
					}

					$category_names[] = $term->name;
				}
			}
		}

		return array_unique($category_names);
	}

	/**
	 * Returns an array of all the product IDs in the order
	 *
	 * @param $order
	 * @return array|void[]
	 */
	private static function get_content_ids( $order ) {
		$product_ids = array_map(function ( $item ) {
			$product = $item->get_product();
			if ($product) {
				return Product::get_dyn_r_id_for_product_by_pixel_name($product, 'snapchat');
			}
		}, $order->get_items());

		// Re-index the array
		return array_values(array_filter($product_ids));
	}

	/**
	 * This returns an array of arrays. Each array contains the following fields: id quantity item_price
	 *
	 * @param $order
	 * @return array|array[]|void[]
	 */
	private static function get_contents( $order ) {
		$product_details = array_map(function ( $item ) {
			$product = $item->get_product();
			if ($product) {
				return array(
					'id'         => Product::get_dyn_r_id_for_product_by_pixel_name($product, 'snapchat'),
					'quantity'   => $item->get_quantity(),
					'item_price' => Helpers::format_decimal($item->get_total(), 2),
				);
			}
		}, $order->get_items());

		// Re-index the array
		return array_values(array_filter($product_details));
	}

	/**
	 * Returns a unique array of all the brand names in the order
	 *
	 * @param $order
	 * @return array|void[]
	 */
	private static function get_brands( $order ) {
		$brands = array();

		foreach ($order->get_items() as $item) {
			$product = $item->get_product();

			if ($product) {
				$brands[] = Product::get_brand_name($product->get_id());
			}
		}

		return array_unique($brands);
	}

	// https://docs.snap.com/api/marketing-api/Conversions-API/UsingTheAPI
	private static function get_user_data( $order, $identifiers ) {

		$user_data = array();

		if (isset($identifiers['ip_address'])) {
			$user_data['client_ip_address'] = $identifiers['ip_address'];
		}

		if (isset($identifiers['user_agent'])) {
			$user_data['client_user_agent'] = $identifiers['user_agent'];
		}

		// $identifiers['uuid_c1']
		if (isset($identifiers['sc_click_id']) && self::is_valid_scid($identifiers['sc_click_id'])) {
			$user_data['sc_click_id'] = $identifiers['sc_click_id'];
		}

		// If advanced matching is enabled, add the email address to the user_data
		if (Options::is_snapchat_advanced_matching_enabled()) {

			$order_user_data = Helpers::get_user_data($order);

			if (!empty($order_user_data['email'])) {
				$user_data['em'] = $order_user_data['email']['sha256'];
			}

			if (!empty($order_user_data['phone'])) {
				$user_data['ph'] = $order_user_data['phone']['snapchat'];
			}

			if (!empty($order_user_data['first_name'])) {
				$user_data['fn'] = $order_user_data['first_name']['snapchat'];
			}

			if (!empty($order_user_data['last_name'])) {
				$user_data['ln'] = $order_user_data['last_name']['snapchat'];
			}

			if (!empty($order_user_data['city'])) {
				$user_data['ct'] = $order_user_data['city']['snapchat'];
			}

			if (!empty($order_user_data['state'])) {
				$user_data['st'] = $order_user_data['state']['snapchat'];
			}

			if (!empty($order_user_data['postcode'])) {
				$user_data['zp'] = $order_user_data['postcode']['sha256'];
			}

			if (!empty($order_user_data['country'])) {
				$user_data['country'] = $order_user_data['country']['snapchat'];
			}

			if (!empty($order_user_data['id'])) {
				$user_data['external_id'] = $order_user_data['id']['sha256'];
			}
		}

		return $user_data;
	}

	/**
	 * Check if the scid is valid
	 *
	 * Example scid: 51c50086-bdee-47aa-8d5a-dfd1d0487305
	 *
	 * @param $scid
	 * @return bool
	 */
	public static function is_valid_scid( $scid ) {
		return (bool) preg_match('/^[0-9a-f-]{20,60}$/', $scid);
	}
}
