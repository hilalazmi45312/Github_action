<?php

/**
 * Reddit Conversions API
 * https://business.reddithelp.com/s/article/Conversions-API
 * https://ads-api.reddit.com/docs/v3/capi-direct-integration
 *
 * @since 1.52.0
 */

namespace SweetCode\Pixel_Manager\Pixels\Reddit;

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
 * Class Reddit_CAPI
 */
class Reddit_CAPI extends S2S {

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

		$request_args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . Options::get_reddit_capi_token(),
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
		return Options::is_reddit_capi_active();
	}

	protected static function get_identifiers_key() {
		return 'reddit_user_identifiers_' . Options::get_reddit_advertiser_id();
	}

	protected static function get_purchase_hit_key() {
		return 'pmw_reddit_capi_purchase_hit';
	}

	// https://ads-api.reddit.com/docs/v3/capi-direct-integration
	// https://ads-api.reddit.com/docs/v3/operations/Post%20Conversion%20Events
	private static function get_request_url( $test_mode = false ) {

		// Reddit CAPI v3 endpoint - test_mode is handled in the payload, not the URL
		$url = 'https://ads-api.reddit.com/api/v3/pixels/' . Options::get_reddit_advertiser_id() . '/conversion_events';

		return $url;
	}

	/**
	 * Add identifiers specific to Reddit CAPI
	 *
	 * @param $identifiers
	 * @return mixed
	 */
	protected static function add_pixel_specific_identifiers( $identifiers ) {

		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

		// Reddit UUID
		if (isset($_cookie['_rdt_uuid'])) {
			$identifiers['rdt_uuid'] = $_cookie['_rdt_uuid'];
		}

		// Reddit Click ID (rdtCid) - passed as query parameter (rdt_cid) and stored in cookie
		if (isset($_cookie['rdtCid'])) {
			$identifiers['rdt_cid'] = sanitize_text_field(wp_unslash($_cookie['rdtCid']));
		}

		/**
		 * Enrich the data with transient session data
		 */

		$transient_identifiers = Shop::get_transient_identifiers_from_session();

		if (!isset($_cookie['_rdt_uuid']) && isset($transient_identifiers['rdt_uuid'])) {
			$identifiers['rdt_uuid'] = $transient_identifiers['rdt_uuid'];
		}

		return $identifiers;
	}

	public static function send_purchase_hit( $order ) {

		self::get_instance();

		if (self::can_not_process_order($order)) {
			return;
		}

		$identifiers = self::get_identifiers_from_order($order);

		// Get user data
		$user_data = self::get_user_data($order, $identifiers);

		// Add client IP address
		if (Geolocation::get_user_ip()) {
			$user_data['ip_address'] = Geolocation::get_user_ip();
		}

		// Get products and fix categories (Reddit expects string, not array)
		$products = self::get_products($order);
		foreach ($products as &$product) {
			if (isset($product['category']) && is_array($product['category'])) {
				$product['category'] = implode(', ', $product['category']);
			}
		}
		unset($product); // Break reference

		// Build metadata
		$metadata = array(
			'item_count'    => $order->get_item_count(),
			'currency'      => $order->get_currency(),
			'value'         => (float) Shop::get_order_value_total_marketing($order, true),
			'products'      => $products,
			'conversion_id' => (string) $order->get_id(),
		);

		// Build v3 API event structure
		$event = array(
			'event_at'      => strtotime($order->get_date_created()) * 1000, // Convert to milliseconds
			'action_source' => 'WEBSITE',
			'user'          => $user_data,
			'type'          => array(
				'tracking_type' => 'Purchase',
			),
			'metadata'      => $metadata,
		);

		// Add click_id if available from identifiers
		if (isset($identifiers['rdt_cid'])) {
			$event['click_id'] = $identifiers['rdt_cid'];
		}

		// Build v3 payload with data wrapper
		$payload = array(
			'data' => array(
				'events' => array( $event ),
			),
		);

		// Add test_id if test event code is configured
		if (Options::is_reddit_capi_test_event_code_set()) {
			$payload['data']['test_id'] = Options::get_reddit_capi_test_event_code();
		}

		$test_mode = Options::is_reddit_capi_test_event_code_set();
		self::$http->post(self::get_request_url($test_mode), $payload);

		// Mark the hit as sent
		$order->update_meta_data(self::get_purchase_hit_key(), true);
		$order->save();

		Logger::debug('Reddit CAPI purchase hit sent for order ' . $order->get_id());
	}

	public static function send_event_hit( $event_data ) {

		self::get_instance();

		if (!Options::is_reddit_capi_active()) {
			return;
		}

		if (Shop::do_not_track_user(get_current_user_id())) {
			return;
		}

		// event_at is already in Unix milliseconds from JavaScript
		// No conversion needed on PHP side

		// Add client IP address
		if (Geolocation::get_user_ip()) {
			$event_data['user']['ip_address'] = Geolocation::get_user_ip();
		}

		// Store click_id from cookie (if available) and conversion_id before restructuring
		$click_id      = isset($event_data['click_id']) ? $event_data['click_id'] : null;
		$conversion_id = isset($event_data['event_id']) ? $event_data['event_id'] : null;

		// Fix product categories - Reddit expects string, not array
		if (isset($event_data['event_metadata']['products']) && is_array($event_data['event_metadata']['products'])) {
			foreach ($event_data['event_metadata']['products'] as &$product) {
				if (isset($product['category']) && is_array($product['category'])) {
					$product['category'] = implode(', ', $product['category']);
				}
			}
			unset($product); // Break reference
		}

		// Add conversion_id to event_metadata
		if ($conversion_id && isset($event_data['event_metadata'])) {
			$event_data['event_metadata']['conversion_id'] = $conversion_id;
		}

		// Build v3 API event structure
		$event = array(
			'event_at'      => $event_data['event_at'],
			'action_source' => 'WEBSITE',
			'user'          => $event_data['user'],
		);

		// Add click_id if available
		if ($click_id) {
			$event['click_id'] = $click_id;
		}

		// Rename event_type to type
		if (isset($event_data['event_type'])) {
			$event['type'] = $event_data['event_type'];
		}

		// Rename event_metadata to metadata
		if (isset($event_data['event_metadata'])) {
			$event['metadata'] = $event_data['event_metadata'];
		}

		// Build v3 payload with data wrapper
		$payload = array(
			'data' => array(
				'events' => array( $event ),
			),
		);

		// Add test_id if test event code is configured
		if (Options::is_reddit_capi_test_event_code_set()) {
			$payload['data']['test_id'] = Options::get_reddit_capi_test_event_code();
		}

		$test_mode = Options::is_reddit_capi_test_event_code_set();
		$endpoint  = self::get_request_url($test_mode);

		self::$http->post($endpoint, $payload);
	}

	/**
	 * Send S2S event from browser data
	 *
	 * Called by the adapter when browser sends S2S data to server endpoint.
	 * Processes the browser event data and sends to Reddit CAPI.
	 *
	 * @param array $pixel_data Event data from browser (adapted by JS)
	 * @return void
	 */
	public static function send_s2s_event( $pixel_data ) {

		self::get_instance();

		if (!Options::is_reddit_capi_active()) {
			return;
		}

		if (Shop::do_not_track_user(get_current_user_id())) {
			return;
		}

		// The pixel_data comes from the browser adapter with this structure:
		// - event_at
		// - user (with hashed user data from browser)
		// - event_id (for deduplication)
		// - event_type
		// - event_metadata (with click_id, products, value, etc.)

		// Send the event using the standard send method
		self::send_event_hit($pixel_data);
	}

	/**
	 * Get user data for Reddit CAPI
	 * https://ads-api.reddit.com/docs/v3/capi-direct-integration#user-data
	 *
	 * @param $order
	 * @param $identifiers
	 * @return array
	 */
	private static function get_user_data( $order, $identifiers ) {

		$user_data = array();

		// Add IP address
		if (isset($identifiers['ip_address'])) {
			$user_data['ip_address'] = $identifiers['ip_address'];
		}

		// Add user agent
		if (isset($identifiers['user_agent'])) {
			$user_data['user_agent'] = $identifiers['user_agent'];
		}

		// Add Reddit UUID (click ID)
		if (isset($identifiers['rdt_uuid'])) {
			$user_data['uuid'] = $identifiers['rdt_uuid'];
		}

		// Add advanced matching data if enabled
		if (Options::is_reddit_advanced_matching_enabled()) {

			$order_user_data = Helpers::get_user_data($order);

			// Email (hashed with SHA-256)
			if (!empty($order_user_data['email']['sha256'])) {
				$user_data['email'] = $order_user_data['email']['sha256'];
			}

			// External ID (user ID)
			if (!empty($order_user_data['id']['sha256'])) {
				$user_data['external_id'] = $order_user_data['id']['sha256'];
			}
		}

		return $user_data;
	}

	/**
	 * Get products array for Reddit CAPI
	 *
	 * @param $order
	 * @return array
	 */
	private static function get_products( $order ) {

		$products = array();

		foreach ($order->get_items() as $order_item) {

			$product_id = Product::get_variation_or_product_id($order_item->get_data(), Options::is_dynamic_remarketing_variations_output_enabled());
			$product    = wc_get_product($product_id);

			// Only add if WC retrieves a valid product
			if (Product::is_not_wc_product($product)) {
				continue;
			}

			$products[] = array(
				'id'       => Product::get_dyn_r_id_for_product_by_pixel_name($product, 'reddit'),
				'name'     => $product->get_name(),
				'category' => self::get_product_category($product),
			);
		}

		return $products;
	}

	/**
	 * Get the primary category for a product
	 *
	 * @param $product
	 * @return string
	 */
	private static function get_product_category( $product ) {

		$category_ids = $product->get_category_ids();

		if (empty($category_ids)) {
			return '';
		}

		$term = get_term($category_ids[0]);

		if (is_wp_error($term)) {
			return '';
		}

		return $term->name;
	}
}
