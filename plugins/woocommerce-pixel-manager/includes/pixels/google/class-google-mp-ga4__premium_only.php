<?php

namespace SweetCode\Pixel_Manager\Pixels\Google;

use DateTime;
use SweetCode\Pixel_Manager\Helpers;
use SweetCode\Pixel_Manager\HTTP;
use SweetCode\Pixel_Manager\Logger;
use SweetCode\Pixel_Manager\Options;
use SweetCode\Pixel_Manager\Product;
use SweetCode\Pixel_Manager\S2S;
use SweetCode\Pixel_Manager\Shop;
use WC_Order_Refund;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * On initial order completion
 * woocommerce_order_status_completed
 * woocommerce_payment_complete
 * https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-order.html#source-view.121
 *
 * Subscriptions
 * https://stackoverflow.com/a/55912713/4688612
 * https://stackoverflow.com/a/42798968/4688612
 * https://developer.wordpress.org/plugins/http-api/
 * https://stackoverflow.com/a/42868240/4688612
 * https://stackoverflow.com/a/31861577/4688612
 * WC session storage: https://stackoverflow.com/a/52422613/4688612¿
 * https://developers.google.com/gtagjs/reference/api#get
 */
class Google_MP_GA4 extends S2S {

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
		self::hook_into_order_status_change_event();
		self::hook_into_refund_events();
		self::hook_into_subscriptions_events();
	}

	/*****************************************************************
	 * Getters for constants
	 *****************************************************************/

	public static function is_pixel_s2s_active() {
		return Options::is_ga4_mp_active();
	}

	protected static function get_identifiers_key() {
		return 'google_cid_' . Options::get_ga4_measurement_id();
	}

	protected static function get_purchase_hit_key() {
		return 'wpm_google_analytics_4_mp_purchase_hit';
	}

	private static function get_full_refund_hit_key() {
		return 'wpm_google_analytics_4_mp_full_refund_hit';
	}

	private static function get_partial_refund_hit_key() {
		return 'wpm_google_analytics_4_mp_partial_refund_hit';
	}

	private static function get_server_base_path( $debug = false ) {

		$server_base_path  = 'https://';
		$server_base_path .= 'www.google-analytics.com';
		$server_base_path .= $debug ? '/debug' : '';
		$server_base_path .= '/mp/collect';
		$server_base_path .= '?measurement_id=';
		$server_base_path .= Options::get_ga4_measurement_id();
		$server_base_path .= '&api_secret=';
		$server_base_path .= Options::get_ga4_mp_api_secret();

		return $server_base_path;
	}

	/*****************************************************************
	 * Hooks
	 *****************************************************************/

	/**
	 * If admin sends a payment link to a client,
	 * we want to set the clients cid
	 */
	private static function hook_into_order_status_change_event() {
		add_action('woocommerce_order_status_changed', array( __CLASS__, 'pmw_woocommerce_order_status_changed' ), 10, 4);
	}

	/**
	 * Process the purchase through the GA Measurement Protocol when they are paid, when they change to processing,
	 * or when they are manually set to completed.
	 * https://docs.woocommerce.com/document/managing-orders/
	 *
	 * Maybe also use woocommerce_pre_payment_complete
	 * https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-order.html#source-view.105
	 * */
	public static function get_purchase_trigger_hooks() {

		$hooks = array();

		foreach (Shop::get_order_paid_statuses() as $status) {
			$hooks[] = 'woocommerce_order_status_' . $status;
		}

		$hooks[] = 'woocommerce_payment_complete';

		return $hooks;
	}

	/**
	 * Refund hooks
	 * woocommerce_order_status_refunded
	 * woocommerce_order_refunded
	 * woocommerce_order_partially_refunded
	 * https://github.com/woocommerce/woocommerce/blob/b19500728b4b292562afb65eb3a0c0f50d5859de/includes/wc-order-functions.php#L614
	 *
	 * More refund hooks
	 * woocommerce_order_fully_refunded
	 * https://github.com/woocommerce/woocommerce/blob/b19500728b4b292562afb65eb3a0c0f50d5859de/includes/wc-order-functions.php#L616
	 *
	 * how to tell if order is fully refunded
	 * https://github.com/woocommerce/woocommerce/blob/b19500728b4b292562afb65eb3a0c0f50d5859de/includes/wc-order-functions.php#L774
	 */
	private static function hook_into_refund_events() {
		add_action('woocommerce_order_fully_refunded', array( __CLASS__, 'google_analytics_mp_send_full_refund' ), 10, 2);
		add_action('woocommerce_order_partially_refunded', array( __CLASS__, 'google_analytics_mp_send_partial_refund' ), 10, 2);
	}

	/**
	 * Process WooCommerce Subscription renewals
	 * https://docs.woocommerce.com/document/subscriptions/develop/action-reference/
	 * */
	private static function hook_into_subscriptions_events() {
		add_action('woocommerce_subscription_renewal_payment_complete', array( __CLASS__, 'google_analytics_mp_report_subscription_purchase_renewal' ), 10, 2);
	}

	public static function google_analytics_mp_send_partial_refund( $order_id, $refund_id ) {

		$order = wc_get_order($order_id);

		// Abort if no order is found
		if (!$order) {
			return;
		}

		self::send_partial_refund_hit($order, $refund_id);
	}

	public static function google_analytics_mp_send_full_refund( $order_id, $refund_id ) {

		$order = wc_get_order($order_id);

		// Abort if no order is found
		if (!$order) {
			return;
		}

		self::send_full_refund_hit($order);
	}

	public static function google_analytics_mp_report_subscription_purchase_renewal( $subscription, $renewal_order ) {

		if (!self::track_google_analytics_subscription_renewal()) {
			return;
		}

		$parent_order = $subscription->get_parent();

		// Abort if the order was created manually, and thus no parent order exists.
		if (!$parent_order) {
			return;
		}

		$identifiers = self::get_identifiers_from_order($parent_order);

		self::send_purchase_hit($renewal_order, $identifiers);
	}

	/*****************************************************************
	 * HTTP requests
	 *****************************************************************/

	// We pass the $order and the $cid
	// The $cid is only necessary if it is a subscription renewal order
	// https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#purchase
	public static function send_purchase_hit( $order, $identifiers = null ) {

		self::get_instance();

		if (self::can_not_process_order($order)) {
			return;
		}

		if (!$identifiers) {
			$identifiers = self::get_identifiers_from_order($order);
		}

		/**
		 * Only run, if the hit has not been sent already (check in db)
		 * Also run it on subscription renewals,
		 * but not on orders before premium activation (orders missing a cid)
		 */
		if (!self::purchase_hit_can_be_processed($order)) {
			return;
		}

		// The client_id is required
		$payload = array(
			'client_id' => isset($identifiers['client_id']) ? $identifiers['client_id'] : self::get_random_client_id(),
			'events'    => array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id'     => (string) $order->get_order_number(),
					'value'              => Shop::get_order_value_total_statistics($order),
					'currency'           => (string) $order->get_currency(),
					'tax'                => (float) $order->get_total_tax(),
					'shipping'           => (float) $order->get_shipping_total(),
					'affiliation'        => (string) get_bloginfo('name'),
					'coupon'             => implode(',', $order->get_coupon_codes()),
					'items'              => self::get_all_order_products($order),
					'page_location'      => (string) $order->get_checkout_order_received_url(),
					'payment_type'       => (string) $order->get_payment_method(),
					'payment_type_title' => (string) $order->get_payment_method_title(),
				),
			),
		);

		$custom_parameters = Shop::get_custom_order_parameters($order);

		// If $custom_parameters is not an empty array, merge it with $payload['events']['params']
		if (!empty($custom_parameters)) {
			$payload['events']['params'] = array_merge($payload['events']['params'], $custom_parameters);
		}

		// https://developers.google.com/analytics/devguides/collection/protocol/ga4/sending-events?client_type=gtag#recommended_parameters_for_reports
		// If we have a session id, add it to the payload
		if (isset($identifiers['session_id'])) {

			$payload['events']['params']['session_id'] = $identifiers['session_id'];

			// TODO test sending engagement_time_msec as additional parameter to display the purchase in the standard reports
			// https://developers.google.com/analytics/devguides/collection/protocol/ga4/sending-events?client_type=gtag#recommended_parameters_for_reports
			$payload['events']['params']['engagement_time_msec'] = 100;
		}

		/**
		 * Add user_id if it is set in the options and the user is logged in
		 */

		if (Options::is_google_user_id_active()) {

			$user_id = Shop::get_user_id();

			if ($user_id) {
				$payload['user_id'] = $user_id;
			}
		}

		/**
		 * Add user_data if enhanced_conversions is enabled.
		 * The user ID is not mandatory anymore: https://support.google.com/analytics/answer/9164320?hl=en#032024 and https://cln.sh/Wyy1FxKR
		 *
		 * Source: https://developers.google.com/analytics/devguides/collection/ga4/uid-data
		 */
		if (Options::is_google_enhanced_conversions_active()) {
			$payload['user_data'] = Google_Helpers::get_google_enhanced_conversion_data($order);
		}

		/**
		 * Add timestamp_micros in microseconds.
		 * https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference?client_type=gtag#payload_post_body
		 *
		 * TODO: GA4 specifies that the timestamp_micros should only be set if the order happened in the past. Check what impact it has sending it right away compared to sending it only if the order is older than seconds. We can use self::is_order_older_than_seconds($order, 120) to check if the order is older than a certain amount of seconds.
		 **/

		// If the current time is older than a certain amount of seconds than the order creation time, we can assume that the order happened in the past.
		// In that case add the timestamp_micros to the payload
//      if (self::is_order_older_than_seconds($order)) {
//          $payload['timestamp_micros'] = (int) $order->get_date_created()->getTimestamp() * 1000000;
//      }

		$payload['timestamp_micros'] = (int) $order->get_date_created()->getTimestamp() * 1000000;

		if (Google_Helpers::is_ga4_debug_mode_active()) {
			Logger::debug('GA4 event debug mode enabled');
			$payload['events']['params']['debug_mode'] = true;
		}

		// Apply unified filter pipeline
		$payload = self::apply_filters_to_payload($payload, 'purchase', $order);

		// If filters blocked the event, stop processing
		if (empty($payload)) {
			return;
		}

		self::$http->post(self::get_server_base_path(), $payload);

		/**
		 * The GA4 Measurement Protocol production endpoint doesn't send error responses,
		 * even if the hit is malformed. So we can't check if the hit was successful.
		 * But we can use the debug endpoint to check if the payload is correct.
		 * https://developers.google.com/analytics/devguides/collection/protocol/ga4/validating-events
		 */
//      if (Options::is_http_request_logging_enabled()) {
//          Logger::debug('Sending GA4 purchase hit through the debug endpoint.');
//          self::$http->post(self::get_server_base_path(true), $payload);
//      }

		// Now we let the server know, that the hit has already been successfully sent.
		$order->update_meta_data(self::get_purchase_hit_key(), true);
		$order->save();
	}

	/**
	 * Apply unified filter pipeline to GA4 MP payload
	 *
	 * @param array $payload GA4 MP payload
	 * @param string $event_name Event name (e.g., 'purchase')
	 * @param object $order WC_Order object
	 * @return array|null Filtered payload or null to block event
	 */
	private static function apply_filters_to_payload( $payload, $event_name, $order ) {

		// Stage 1: Pixel-specific filter (all events)
		$payload = apply_filters('pmw_server_event_payload_google_analytics', $payload, 'google_analytics');

		if (empty($payload)) {
			return null;
		}

		// Stage 2: Pixel + Event-specific filter
		$payload = apply_filters("pmw_server_event_payload_google_analytics_{$event_name}", $payload, 'google_analytics', $event_name);

		if (empty($payload)) {
			return null;
		}

		// Stage 3: Post-processing filter
		$payload = apply_filters('pmw_server_event_payload_post', $payload, 'google_analytics');

		return $payload;
	}

	// Check if the order is older than two minutes, return true if it is
	private static function is_order_older_than_seconds( $order, $seconds = 10 ) {

		$now        = new DateTime();
		$order_date = $order->get_date_created();

		$diff = $now->getTimestamp() - $order_date->getTimestamp();

		// If the order is older than two minutes, return true
		if ($diff >= $seconds) {
			return true;
		}

		return false;
	}

	/**
	 * Send a full refund hit to GA4.
	 * For full refunds, we only need to send the transaction_id.
	 *
	 * Source: https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#refund
	 *
	 * @param $order
	 * @return void
	 */
	public static function send_full_refund_hit( $order ) {

		self::get_instance();

		// TODO: Refunds use different identifiers than purchases. We should check if the identifiers are set on the order.
//      if (self::can_not_process_order($order)) {
//          return;
//      }

		// Only run if the hit has not been sent already (check in db)
		if ($order->meta_exists(self::get_full_refund_hit_key())) {
			return;
		}

		$identifiers = self::get_identifiers_from_order($order);

		$payload = array(
			'client_id' => isset($identifiers['client_id']) ? $identifiers['client_id'] : self::get_random_client_id(),
			'events'    => array(
				'name'   => 'refund',
				'params' => array(
					'transaction_id' => $order->get_order_number(),
				),
			),
		);

		if (Google_Helpers::is_ga4_debug_mode_active()) {
			Logger::debug('GA4 event debug mode enabled');
			$payload['events']['params']['debug_mode'] = true;
		}

		self::$http->post(self::get_server_base_path(), $payload);

		// Now we let the server know, that the hit has already been successfully sent.
		$order->update_meta_data(self::get_full_refund_hit_key(), true);
		$order->save();
	}

	/**
	 * Send a partial refund hit to GA4
	 * https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#refund
	 *
	 * @param $order
	 * @param $refund_id
	 * @return void
	 */
	public static function send_partial_refund_hit( $order, $refund_id ) {

		self::get_instance();

		// TODO: Refunds use different identifiers than purchases. We should check if the identifiers are set on the order.
//      if (self::can_not_process_order($order)) {
//          return;
//      }

		$refund = new WC_Order_Refund($refund_id);

		// Only run if the hit has not been sent already (check in db)
		if (self::has_partial_refund_hit_already_been_sent($order, $refund_id, self::get_partial_refund_hit_key())) {
			return;
		}

		$identifiers = self::get_identifiers_from_order($order);

		$payload = array(
			'client_id' => isset($identifiers['client_id']) ? $identifiers['client_id'] : self::get_random_client_id(),
			'events'    => array(
				'name'   => 'refund',
				'params' => array(
					'transaction_id' => $order->get_order_number(),
					'currency'       => $order->get_currency(),
					'items'          => self::get_all_order_products($refund),
				),
			),
		);

		if (Google_Helpers::is_ga4_debug_mode_active()) {
			$payload['events']['params']['debug_mode'] = true;
		}

		self::$http->post(self::get_server_base_path(), $payload);

		// Now we let the server know that the hit has already been successfully sent.
		self::save_partial_refund_hit_to_db($order, $refund_id, self::get_partial_refund_hit_key());
	}

	private static function get_all_order_products( $order ) {

		$items = array();

		foreach (Product::pmw_get_order_items($order) as $item_id => $item) {

			$order_item_data = Google_Helpers::get_order_item_data($item);

			$item_details = array(
				'item_id'   => $order_item_data['id'],
				'item_name' => $order_item_data['name'],
				//                'coupon'        => '',
				//                'discount'      => '',
				//                'affiliation'   => '',
				'price'     => $order_item_data['price'],
				//                'currency'      => '',
				'quantity'  => $order_item_data['quantity'],
			);

			if (isset($order_item_data['brand'])) {
				$item_details['item_brand'] = $order_item_data['brand'];
			}

			if (isset($order_item_data['variant'])) {
				$item_details['item_variant'] = $order_item_data['variant'];
			}

			$custom_parameters = Shop::get_custom_order_item_parameters($item, $order);

			// If $custom_parameters is not an empty array, merge it with $item_details
			if (!empty($custom_parameters)) {
				$item_details = array_merge($item_details, $custom_parameters);
			}

			$item_details = Google_Helpers::add_categories_to_ga4_product_items($item_details, $order_item_data['category_array']);

			// limit each item to 100 characters
			// This is a GA4 MP limitation
			// https://developers.google.com/analytics/devguides/collection/protocol/ga4/sending-events?client_type=gtag#limitations
			$item_details = array_map(function ( $item ) {
				return substr($item, 0, 100);
			}, $item_details);

			$items[] = $item_details;
		}

		return $items;
	}

	/**
	 * Check if the partial refund hit has already been sent
	 *
	 * @param $order
	 * @param $refund_id
	 * @param $mp_partial_refund_hit_key
	 * @return bool
	 */
	private static function has_partial_refund_hit_already_been_sent( $order, $refund_id, $mp_partial_refund_hit_key ) {

		$meta_value = $order->get_meta($mp_partial_refund_hit_key, true);

		if ($meta_value) {
			return in_array($refund_id, $meta_value);
		}

		return false;
	}

	/**
	 * Save the partial refund hit to the database
	 *
	 * @param $order
	 * @param $refund_id
	 * @param $mp_partial_refund_hit_key
	 */
	private static function save_partial_refund_hit_to_db( $order, $refund_id, $mp_partial_refund_hit_key ) {

		$meta_value = $order->get_meta($mp_partial_refund_hit_key, true);

		if (!is_array($meta_value)) {
			$meta_value = array();
		}

		$meta_value[] = $refund_id;

		$order->update_meta_data($mp_partial_refund_hit_key, $meta_value);
		$order->save();
	}

	/**
	 * Getting GA4 session ID from browser
	 * gtag('get', 'GA4_MEASUREMENT_ID', 'session_id', (session_id) => {
	 *    console.log(session_id)
	 * })
	 *
	 * Old format _ga:                  GS1.1.1081496294.1.1.1747830394.0.0.0
	 * New format _ga_MEASUREMENT_ID: GS2.1.s1747821527$o1$g1$t1747821573$j0$l0$h0
	 *
	 * The session ID must be an integer
	 *
	 * @param $target_id
	 * @param $_cookie
	 * @return int|null
	 **/
	private static function get_ga4_session_id_from_cookie( $target_id, $_cookie ) {

		// If the cookie is not set in the browser, return null
		if (!isset($_cookie['_ga_' . self::get_ga4_target_id_suffix($target_id)])) {
			return null;
		}

		$cookie = $_cookie['_ga_' . self::get_ga4_target_id_suffix($target_id)];

		if (strpos($cookie, 'GS2') === 0) {
			$cookie_parts = self::parse_ga4_gs2_cookie($cookie);
			return isset($cookie_parts['session_id']) ? $cookie_parts['session_id'] : null;
		}

		if (strpos($cookie, 'GS1') === 0) {
			$cookie_parts = self::parse_ga4_gs1_cookie($cookie);
			return isset($cookie_parts['session_id']) ? $cookie_parts['session_id'] : null;
		}

		return null;
	}

	/**
	 * Extracts and parses the GS1 cookie into an associative array.
	 *
	 * Example: GA1.1.1081496294.1.1.1747830394.0.0.0
	 *
	 * @param string $cookie The GS1 cookie string.
	 * @return array|null An associative array of parsed fields or null if the cookie is invalid.
	 */
	private static function parse_ga4_gs1_cookie( $cookie ) {

		// Check if the cookie starts with "GS1"
		if (strpos($cookie, 'GS1') !== 0) {
			return null;
		}

		$parts = explode('.', $cookie);
		if (count($parts) < 5) {
			Logger::debug('Invalid GS1 cookie format: ' . $cookie);
			return null;
		}

		return array(
			'type'               => 'Google Stream',
			'analysis_version'   => (int) substr($parts[0], 2), // Extract the number from GS1
			'domain_level'       => (int) $parts[1],
			'session_id'         => (int) $parts[2],
			'session_count'      => (int) $parts[3],
			'engagement_session' => (int) $parts[4],
			'timestamp'          => (int) $parts[5],
			'countdown'          => (int) $parts[6],
			'login_set_user_id'  => (int) $parts[7],
			'enhanced_client_id' => (int) $parts[8],
		);
	}

	/**
	 * Extracts and parses the GS2 cookie into an associative array.
	 *
	 * Example: GS2.1.s1747821527$o1$g1$t1747821573$j0$l0$h0
	 *
	 * @param string $cookie The GS2 cookie string.
	 * @return array|null An associative array of parsed fields or null if the cookie is invalid.
	 */
	private static function parse_ga4_gs2_cookie( $cookie ) {
		// Check if the cookie starts with "GS2"
		if (strpos($cookie, 'GS2') !== 0) {
			return null;
		}

		$parts = explode('.', $cookie);
		if (count($parts) < 3) {
			Logger::debug('Invalid GS2 cookie format: ' . $cookie);
			return null;
		}

		$data = array(
			'type'             => 'Google Stream',
			'analysis_version' => (int) substr($parts[0], 2), // Extract the number from GS2
			'domain_level'     => (int) $parts[1],
		);

		$data = array_merge($data, array_reduce(explode('$', $parts[2]), function ( $carry, $part ) {
			$keyMap = array(
				's' => 'session_id',
				'o' => 'session_count',
				'g' => 'engagement_session',
				't' => 'timestamp',
				'j' => 'countdown',
				'l' => 'login_set_user_id',
				'h' => 'enhanced_client_id',
			);
			$key    = substr($part, 0, 1);
			$carry[isset($keyMap[$key]) ? $keyMap[$key] : $key] = substr($part, 1); // Use the mapped key or fallback to the original key.
			return $carry;
		}, array()));

		// If anything went wrong, then use the logger to log it and return null.
		if (count($data) < 2) {
			Logger::debug('Invalid GS2 cookie format: ' . $cookie);
			return null;
		}

		return $data;
	}

	private static function get_ga4_target_id_suffix( $target_id ) {

		$re = '/[\dA-Z]{4,}/';

		preg_match($re, $target_id, $matches);

		if (isset($matches[0])) {
			return $matches[0];
		}

		return null;
	}

	protected static function add_pixel_specific_identifiers( $identifiers ) {

		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);
		$_get    = Helpers::get_input_vars(INPUT_GET);

		// Add the GA4 client_id
		if (isset($_cookie['_ga'])) {

			$client_id = self::get_ga_client_id_from_ga_cookie($_cookie['_ga']);

			if ($client_id) {
				$identifiers['client_id'] = $client_id;
			}
		}

		// Add the GA4 session_id
		if (isset($_cookie['_ga_' . self::get_ga4_target_id_suffix(Options::get_ga4_measurement_id())])) {

			$session_id = self::get_ga4_session_id_from_cookie(Options::get_ga4_measurement_id(), $_cookie);

			if ($session_id) {
				$identifiers['session_id'] = $session_id;
			}
		}

		// Get Google Ads click ID
		if (isset($_cookie['_gcl_aw'])) {
			$identifiers['gclid'] = self::get_gclid_from_gcl_aw_cookie($_cookie['_gcl_aw']);
		} elseif (isset($_get['gclid'])) {
			$identifiers['gclid'] = $_get['gclid'];
		}

		// Google Ads Double-Click ID
		if (isset($_cookie['_gcl_dc'])) {
			$identifiers['dclid'] = self::get_gclid_from_gcl_aw_cookie($_cookie['_gcl_dc']);
		}

		return $identifiers;
	}

	/**
	 * Get the cid from the $cookie string
	 *
	 * Cookie name: _ga
	 * Format: GA1.1.171933599.1747821527
	 *
	 * The cid is 639182791.1685507935
	 *
	 * @param $cookie
	 * @return string|null
	 */
	private static function get_ga_client_id_from_ga_cookie( $cookie ) {

		$cookie_parts = explode('.', $cookie);

		if (isset($cookie_parts[2]) && isset($cookie_parts[3])) {
			return $cookie_parts[2] . '.' . $cookie_parts[3];
		}

		return null;
	}

	private static function get_gclid_from_gcl_aw_cookie( $cookie ) {

		preg_match('/(GCL.[\d]*.)(.*)/', $cookie, $matches);

		if (isset($matches[2])) {
			return $matches[2];
		}

		return null;
	}

	private static function purchase_hit_can_be_processed( $order ) {

		/**
		 * Only approve, if the hit has not been sent already (check in db)
		 *
		 * Also approve subscription renewals (cid is missing on order but available as argument),
		 * but don't approve normal orders before premium activation where the cid is missing on the order.
		 */

		// Don't approve if the purchase hit has already been processed
		if (self::check_if_purchase_hit_post_meta_exists_for_ga4($order)) {
			Logger::debug('Purchase hit has already been processed for order ID: ' . $order->get_id());
			return false;
		}

		// Process order if it is a backend order, otherwise it will fail in the next test
		if (Shop::is_backend_manual_order($order)) {
			Logger::debug('Order ID: ' . $order->get_id() . ' is a backend manual order. Processing purchase hit.');
			return true;
		}

//      if (Shop::was_order_created_while_wpm_was_active($order->get_id())) {
//          return true;
//      }

//      if (Shop::is_backend_subscription_renewal_order($order->get_id())) {
//          return true;
//      }

		if (Shop::was_order_created_while_pmw_premium_was_active($order)) {
			Logger::debug('Order ID: ' . $order->get_id() . ' was created while PMW was active. Processing purchase hit.');
			return true;
		}

		/**
		 * Don't approve if the order was placed before PMW was active
		 */
		if (!Shop::was_order_created_while_pmw_was_active($order)) {
			Logger::debug('Purchase hit not approved for processing. Order ID: ' . $order->get_id() . ' was placed before PMW was active.');
			return false;
		}

		return true;
	}

	/**
	 * Needed to create this because I changed the key and now some order confirmations get re-sent to GA
	 *
	 * @param $order
	 * @return bool
	 */
	private static function check_if_purchase_hit_post_meta_exists_for_ga4( $order ) {

		/**
		 * List of possible db meta keys:
		 * wpm_google_analytics_4_mp_purchase_hit
		 * wooptpm_google_analytics_4_mp_purchase_hit
		 */

		$meta_keys = array(
			'wpm_google_analytics_4_mp_purchase_hit',
			'wooptpm_google_analytics_4_mp_purchase_hit',
		);

		foreach ($meta_keys as $key) {
			if ($order->get_meta($key)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate a random client ID
	 *
	 * @return string
	 */
	private static function get_random_client_id() {
		$client_id = mt_rand(1000000000, 9999999999) . '.' . time();
		$prefix    = 'anon_';

		return (string) substr_replace($client_id, $prefix, 0, strlen($prefix));
	}

	/**
	 * If admin sends a payment link to a client
	 * we want to set the clients cid
	 */
	public static function pmw_woocommerce_order_status_changed( $order_id, $old_status, $new_status, $order ) {

		if ('on-hold' === $new_status && !is_admin()) {
			self::set_identifiers_on_order($order);
		}
	}

	/*******************************************************************
	 * Filters
	 *******************************************************************/

	/**
	 * Check if the cid logger is active
	 *
	 * @return bool
	 */
	private static function is_ga_cid_logger_active() {

		$logger_active = apply_filters_deprecated('wooptpm_get_ga_cid_logger', array( false ), '1.13.0', 'pmw_ga_cid_log_activation');
		$logger_active = apply_filters_deprecated('wpm_get_ga_cid_logger', array( $logger_active ), '1.31.2', 'pmw_ga_cid_log_activation');

		return (bool) apply_filters('pmw_ga_cid_log_activation', $logger_active);
	}

	/**
	 * Disable tracking of subscription renewals in Google Analytics.
	 *
	 * @return bool
	 */
	public static function track_google_analytics_subscription_renewal() {

		// Abort if general subscription renewal tracking is disabled
		if (Shop::do_not_track_subscription_renewal()) {
			return false;
		}

		return (bool) apply_filters('pmw_google_analytics_subscription_renewal_tracking', true);
	}

	/**
	 * Check if the GA cid logger is active
	 *
	 * @return bool
	 */
	private static function is_filter_pmw_ga_cid_logger_active() {

		$logger_active = apply_filters_deprecated('wooptpm_get_ga_cid_logger', array( false ), '1.13.0', 'pmw_ga_cid_logger');
		$logger_active = apply_filters_deprecated('wpm_get_ga_cid_logger', array( $logger_active ), '1.31.2', 'pmw_ga_cid_logger');

		return (bool) apply_filters('pmw_ga_cid_logger', $logger_active);
	}
}
