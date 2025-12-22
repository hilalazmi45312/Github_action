<?php

/**
 * TikTok Events API
 * https://business-api.tiktok.com/portal/docs?rid=oyn7lhbo6ar&id=1771100865818625
 * ttclid: https://ads.tiktok.com/marketing_api/docs?id=1739584860883969
 * _ttp: https://ads.tiktok.com/help/article?aid=10007540
 */

namespace SweetCode\Pixel_Manager\Pixels\TikTok;

use SweetCode\Pixel_Manager\Geolocation;
use SweetCode\Pixel_Manager\Helpers;
use SweetCode\Pixel_Manager\HTTP;
use SweetCode\Pixel_Manager\Logger;
use SweetCode\Pixel_Manager\Options;
use SweetCode\Pixel_Manager\S2S;
use SweetCode\Pixel_Manager\Shop;
use SweetCode\Pixel_Manager\Product;

defined('ABSPATH') || exit; // Exit if accessed directly

class TikTok_EAPI extends S2S {

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

		/**
		 * Initialize options
		 */

		$request_args = [
			'headers' => [
				'Content-Type' => 'application/json',
				'Access-Token' => Options::get_tiktok_eapi_token(),
			],
		];

		$http->set_request_args($request_args);

		self::$http = $http;

		self::hook_into_generic_events();
	}

	/**
	 * Getters for constants
	 */

	public static function is_pixel_s2s_active() {
		return Options::is_tiktok_eapi_active();
	}

	protected static function get_identifiers_key() {
		return 'tiktok_user_identifiers_' . Options::get_tiktok_pixel_id();
	}

	protected static function get_purchase_hit_key() {
		return 'pmw_tiktok_eapi_purchase_hit';
	}

	private static function get_api_version() {
		return 'v1.3';
	}

	// https://ads.tiktok.com/marketing_api/docs?id=1735712062490625
	private static function get_request_url() {

		$request_url = 'https://';
		$request_url .= 'business-api.tiktok.com';
		$request_url .= '/open_api/';
		$request_url .= self::get_api_version();
		$request_url .= '/event/track/';

		return $request_url;
	}

	/**
	 * Handle TikTok purchase hit
	 **/
	public static function send_purchase_hit( $order ) {

		self::get_instance();

		if (self::can_not_process_order($order)) {
			return;
		}

		$identifiers = self::get_identifiers_from_order($order);

		$payload = [
			'event_source'    => 'web',
			'event_source_id' => Options::get_tiktok_pixel_id(),
		];

		if (Options::is_tiktok_eapi_test_event_code_set()) {
			$payload['test_event_code'] = Options::get_tiktok_eapi_test_event_code();
		}

		// Add event data
		$event_data = [
			'event'      => 'CompletePayment',
			'event_time' => time(),
			'event_id'   => (string) $order->get_id(),
		];

		$event_data['user'] = self::get_user_data($order, $identifiers);
		$event_data['page'] = self::get_page_data($order, $identifiers);

		// add order data
		$event_data['properties'] = [
			'value'        => Shop::get_order_value_total_marketing($order, true),
			'currency'     => (string) $order->get_currency(),
			'content_type' => 'product',
			'contents'     => self::get_order_contents($order),
		];

		$payload['data'] = [ $event_data ];

		self::$http->post(self::get_request_url(), $payload);

		// Now we let the server know that the hit has already been successfully sent.
		$order->update_meta_data(self::get_purchase_hit_key(), true);
		$order->save();

		Logger::debug('TikTok EAPI hit on order ' . $order->get_id() . ': end');
	}

	public static function send_event_hit( $event_data ) {

		self::get_instance();

		if (!Options::is_tiktok_eapi_active()) {
			return;
		}

		if (Shop::do_not_track_user(get_current_user_id())) {
			return;
		}

		$payload = [
			'event_source'    => 'web',
			'event_source_id' => Options::get_tiktok_pixel_id(),
		];

		if (Options::is_tiktok_eapi_test_event_code_set()) {
			$payload['test_event_code'] = Options::get_tiktok_eapi_test_event_code();
		}

//		if (!isset($event_data['context']['user']['ttp']) && !isset($event_data['ad']['callback']['ttclid'])) {
//			$event_data['context']['user']['ttp'] = self::generate_random_ttp();
//		}

		$event_data['event_time'] = time();

		if (Options::is_tiktok_advanced_matching_enabled()) {
			if (Geolocation::get_user_ip()) {
				$event_data['user']['ip'] = Geolocation::get_user_ip();
			}
		}

		$payload['data'] = [ $event_data ];

		self::$http->post(self::get_request_url(), $payload);
	}

	private static function get_page_data( $order, $identifiers ) {

		$data['url'] = strtok($order->get_checkout_order_received_url(), '?');

		if (isset($identifiers['referrer'])) {
			$data['referrer'] = Helpers::make_full_url($identifiers['referrer']);
		}

		return $data;
	}

	private static function get_order_contents( $order ) {

		$contents = [];

		foreach ($order->get_items() as $order_item) {

			$product_id = Product::get_variation_or_product_id($order_item->get_data(), Options::is_dynamic_remarketing_variations_output_enabled());
			$product    = wc_get_product($product_id);

			// Only add if WC retrieves a valid product
			if (Product::is_not_wc_product($product)) {
				continue;
			}

			$contents[] = [
				'price'      => (float) Helpers::format_decimal($product->get_price(), 2),
				'quantity'   => (int) $order_item->get_quantity(),
				'content_id' => (string) Product::get_dyn_r_id_for_product_by_pixel_name($product, 'tiktok'),
			];
		}

		return $contents;
	}

	protected static function get_user_data( $order, $identifiers ) {

		$user = [];

		/**
		 * If ttp exists we set all real data
		 * If ttp doesn't exist, we only set required fields with random data
		 * Recommended field
		 */
		if (isset($identifiers['ttp'])) {
			$user['ttp'] = $identifiers['ttp'];
		}

		// Set ttclid
		if (isset($identifiers['ttclid'])) {
			$user['ttclid'] = $identifiers['ttclid'];
		}

		if (!isset($identifiers['ttp']) && !isset($identifiers['ttclid'])) {
			$user['ttp'] = self::generate_random_ttp();
		}

		// https://ads.tiktok.com/marketing_api/docs?id=1727541103358977
		// https://ads.tiktok.com/marketing_api/tools/tools-list/payload-helper/ads-measurement/events-api/events-api-web
		if (Options::is_tiktok_advanced_matching_enabled()) {

			if (isset($identifiers['ip_address'])) {
				$user['ip'] = $identifiers['ip_address'];
			}

			if (isset($identifiers['user_agent'])) {
				$user['user_agent'] = $identifiers['user_agent'];
			}

			$user_data = Helpers::get_user_data($order);

			// Set the user ID
			if (isset($user_data['id']['sha256'])) {
				$user['external_id'] = $user_data['id']['sha256'];
			}

			// Set the user email
			if (isset($user_data['email']['tiktok'])) {
				$user['email'] = $user_data['email']['tiktok'];
			}

			if (isset($user_data['phone']['tiktok'])) {
				$user['phone'] = $user_data['phone']['tiktok'];
			}
		}

		return $user;
	}

	/**
	 * Add identifiers specific to TikTok EAPI
	 *
	 * @param $identifiers
	 * @return mixed
	 */
	protected static function add_pixel_specific_identifiers( $identifiers ) {

		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

		if (isset($_cookie['_ttp']) && self::is_valid_ttp($_cookie['_ttp'])) {
			$identifiers['ttp'] = $_cookie['_ttp'];
		}

		if (isset($_cookie['_ttclid']) && self::is_valid_ttclid($_cookie['_ttclid'])) {
			$identifiers['ttclid'] = $_cookie['_ttclid'];
		}

		// rewrite ip_address to ip
		if (isset($identifiers['ip_address'])) {
			$identifiers['ip'] = $identifiers['ip_address'];
			unset($identifiers['ip_address']);
		}

		/**
		 * Enrich the data with transient session data
		 */

		$transient_identifiers = Shop::get_transient_identifiers_from_session();

		if (isset($transient_identifiers['referrer'])) {
			$identifiers['referrer'] = $transient_identifiers['referrer'];
		}

		if (!isset($_cookie['_ttclid']) && isset($transient_identifiers['ttclid'])) {
			$identifiers['ttclid'] = $transient_identifiers['ttclid'];
		}

		return $identifiers;
	}

	/**
	 * Check if the ttp is valid
	 *
	 * TODO new regex for ttp
	 *
	 * @param $ttp
	 * @return bool
	 */
	protected static function is_valid_ttp( $ttp ) {

		$re = '/^[\da-zA-Z-]{20,50}$/';

		// Check if $ttp matches the regex. If yes, return true, else return false
		return (bool) preg_match($re, $ttp);
	}

	/**
	 * Check if the ttclid is valid
	 *
	 * Source: https://ads.tiktok.com/marketing_api/docs?id=1701890980108353
	 *
	 * TODO new regex for ttclid
	 *
	 * @param $ttclid
	 * @return bool
	 */
	public static function is_valid_ttclid( $ttclid ) {

		$re = '/^[\da-zA-z-]{5,600}$/';

		return (bool) preg_match($re, $ttclid);
	}

	public static function set_additional_identifiers_on_order( $identifiers ) {
		$identifiers['event_time'] = time();
		return $identifiers;
	}

	protected static function get_random_base_identifiers() {
		return [
			'ttp' => self::generate_random_ttp(),
		];
	}

	private static function generate_random_ttp() {
		$random_ttp = [
			self::generate_random_partial_ttp_string(8),
			self::generate_random_partial_ttp_string(4),
			self::generate_random_partial_ttp_string(4),
			self::generate_random_partial_ttp_string(4),
			self::generate_random_partial_ttp_string(12),
		];

		return implode('-', $random_ttp);
	}

	private static function generate_random_partial_ttp_string( $length ) {
		// Generate a random string with the length of $length and contains small letters and numbers
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		return (string) substr(str_shuffle(str_repeat($characters, ceil($length / strlen($characters)))), 1, $length);
	}
}
