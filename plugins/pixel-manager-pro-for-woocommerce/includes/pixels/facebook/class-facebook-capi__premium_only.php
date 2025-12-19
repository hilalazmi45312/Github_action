<?php

/**
 * Facebook CAPI Pixel Class
 *
 * Handles server-to-server communication with Facebook CAPI
 *
 * @package Pixel_Manager/Classes/Pixels/Facebook
 */

namespace SweetCode\Pixel_Manager\Pixels\Facebook;

use SweetCode\Pixel_Manager\Geolocation;
use SweetCode\Pixel_Manager\Helpers;
use SweetCode\Pixel_Manager\HTTP;
use SweetCode\Pixel_Manager\Logger;
use SweetCode\Pixel_Manager\Options;
use SweetCode\Pixel_Manager\S2S;
use SweetCode\Pixel_Manager\Shop;
use SweetCode\Pixel_Manager\Product;
use SweetCode\Pixel_Manager\User_Agent;

defined('ABSPATH') || exit; // Exit if accessed directly

class Facebook_CAPI extends S2S {

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
		self::hook_into_subscriptions_events();
	}

	/**
	 * Getters for constants
	 */

	public static function is_pixel_s2s_active() {
		return Options::is_facebook_capi_active();
	}

	protected static function get_identifiers_key() {
		return 'facebook_user_identifiers_' . Options::get_facebook_pixel_id();
	}

	protected static function get_purchase_hit_key() {
		return 'wpm_facebook_capi_purchase_hit';
	}

	// https://developers.facebook.com/docs/graph-api/changelog/versions
	private static function get_api_version() {

		$api_version = 'v24.0';

		$api_version = apply_filters_deprecated('wooptpm_facebook_capi_api_version', [ $api_version ], '1.13.0', 'pmw_facebook_capi_api_version');
		$api_version = apply_filters_deprecated('wpm_facebook_capi_api_version', [ $api_version ], '1.31.2', 'pmw_facebook_capi_api_version');

		// Filter to output the Facebook API version
		return apply_filters('pmw_facebook_capi_api_version', $api_version);
	}

	private static function get_request_url() {

		$request_url = 'https://';
		$request_url .= 'graph.facebook.com/';
		$request_url .= self::get_api_version() . '/';
		$request_url .= Options::get_facebook_pixel_id() . '/';
		$request_url .= 'events';
		$request_url .= '?access_token=' . Options::get_facebook_capi_token();

		return $request_url;
	}

	/**
	 * Send the purchase hit to Facebook CAPI
	 *
	 * Source: https://developers.facebook.com/docs/marketing-api/conversions-api/using-the-api#send
	 * Source: https://developers.facebook.com/docs/marketing-api/conversions-api/parameters
	 * Source: https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/server-event#event-name
	 *
	 * @param $order
	 **/
	public static function send_purchase_hit( $order ) {

		self::get_instance();

		if (self::can_not_process_subscription_renewal_order($order)) {
			return;
		}

		if (self::can_not_process_order($order)) {
			return;
		}

		$identifiers = self::get_identifiers_from_order($order);

		// Add event data
		$event_data = [
			'event_name'       => 'Purchase',
			'action_source'    => 'website',
			'event_id'         => (string) $order->get_id(),
			'event_source_url' => $order->get_checkout_order_received_url(),
			'event_time' => !empty($identifiers['event_time']) ? $identifiers['event_time'] : time(), // try to match browser event_time, fallback to current timestamp
			'opt_out'          => self::is_ads_delivery_opt_out_active(),
		];

		// Add user data
		$event_data['user_data'] = self::get_user_data($identifiers, $order);

		// add order data
		$event_data['custom_data'] = [
			'content_type' => 'product',
			'value'        => Shop::get_order_value_total_marketing($order, true),
			'currency'     => (string) $order->get_currency(),
			'content_ids'  => Product::get_order_item_ids($order, 'facebook'),
		];

		// data processing options
		$event_data = self::add_data_processing_options($event_data);

		if (isset($identifiers['referrer_url'])) {
			$event_data['referrer_url'] = Helpers::make_full_url($identifiers['referrer_url']);
		}

		$payload = [
			'data' => [ $event_data ],
		];

		if (Options::get_facebook_capi_test_event_code()) {
			$payload['test_event_code'] = Options::get_facebook_capi_test_event_code();
		}

		self::$http->post(self::get_request_url(), $payload);

		// Now we let the server know that the hit has already been successfully sent.
		$order->update_meta_data(self::get_purchase_hit_key(), true);
		$order->save();
	}

	public static function send_event_hit( $event_data ) {

		self::get_instance();

		if (!Options::is_facebook_capi_active()) {
			return;
		}

		if (Shop::do_not_track_user(get_current_user_id())) {
			return;
		}

		$event_data['action_source'] = 'website';
		$event_data['event_time']    = time();
		$event_data['opt_out']       = self::is_ads_delivery_opt_out_active();

		// If an ipv6 address has been set in the session we use it
		// If not, we try to get the IP address using the Geolocation class
		if (Shop::is_woocommerce_session_active() && WC()->session->get('client_ipv6')) {
			$event_data['user_data']['client_ip_address'] = WC()->session->get('client_ipv6');
		} elseif (Geolocation::get_user_ip()) {
			$event_data['user_data']['client_ip_address'] = Geolocation::get_user_ip();
		}

		// data processing options
		$event_data = self::add_data_processing_options($event_data);

		$payload = [
			'data' => [ $event_data ],
		];

		if (Options::get_facebook_capi_test_event_code()) {
			$payload['test_event_code'] = Options::get_facebook_capi_test_event_code();
		}

		self::$http->post(self::get_request_url(), $payload);
	}

	// https://developers.facebook.com/docs/marketing-api/conversions-api/subscription-lifecycle-events/
	public static function send_subscription_hit( $life_cycle_event, $subscription, $parent_order, $renewal_order = null, $reactivation = false ) {

		self::get_instance();

		// Abort if the $parent_order is not an object
		if (!is_object($parent_order)) {
			Logger::debug('aborting Meta CAPI Subscribe hit because parent order is not an object');
			return;
		}

		if (Shop::do_not_track_user(Shop::get_order_user_id($parent_order))) {
			Logger::debug('send_subscription_hit | Do not track user for $life_cycle_event ' . $life_cycle_event . ' on subscription ' . $subscription->get_id() . ' and parent order ' . $parent_order->get_id());
			return;
		}

		$identifiers = self::get_identifiers_from_order($parent_order);

		// Add event data
		$event_data = [
			'event_name' => $life_cycle_event,
			//			'event_time' => $facebook_identifiers['event_time'], // try to match browser event_time
			'event_time' => time(), // FB can't process timestamps in the past
			//			'event_id'         => (string) $order->get_id(),
			'opt_out'    => self::is_ads_delivery_opt_out_active(),
			//			'action_source'    => 'website',
			//			'event_source_url' => $order->get_checkout_order_received_url(),
		];

		// Add user data
		$event_data['user_data'] = self::get_user_data($identifiers, $parent_order);

		// Add the subscription ID to the custom data
		$event_data['user_data']['subscription_id'] = $subscription->get_id();

		/**
		 * Get the order data and add it to the subscription hit if it is a new subscription,
		 * or if it is a subscription renewal.
		 *
		 * In all other cases omit adding the order data.
		 */

		$order = null;

		if ('Subscribe' === $life_cycle_event && false === $reactivation) {
			$order = $parent_order;
		} elseif ('RecurringSubscriptionPayment' === $life_cycle_event) {
			$order = $renewal_order;
		}

		// add order data
		if ($order) {
			$event_data['custom_data'] = [
				'value'    => Shop::get_order_value_total_marketing($order),
				'currency' => (string) $order->get_currency(),
				//			'content_ids'  => (array) Product::get_order_item_ids($order, 'facebook'),
				//			'content_type' => 'product'
			];
		}

		// data processing options
		$event_data = self::add_data_processing_options($event_data);

		$payload = [
			'data' => [ $event_data ],
		];

		if (Options::get_facebook_capi_test_event_code()) {
			$payload['test_event_code'] = Options::get_facebook_capi_test_event_code();
		}

		self::$http->post(self::get_request_url(), $payload);

		// Now we let the server know, that the hit has already been successfully sent.
//		update_post_meta($order->get_id(), self::get_purchase_hit_key(), true);
	}

	/**
	 * Process WooCommerce Subscription renewals
	 * https://docs.woocommerce.com/document/subscriptions/develop/action-reference/
	 * https://github.com/wp-premium/woocommerce-subscriptions/blob/master/includes/class-wc-subscription.php
	 * https://developers.facebook.com/docs/marketing-api/conversions-api/subscription-lifecycle-events/
	 * */
	private static function hook_into_subscriptions_events() {

		add_action('woocommerce_subscription_payment_complete', [ __CLASS__, 'facebook_capi_report_subscription_payment_complete' ], 10, 1);
		add_action('woocommerce_subscription_status_cancelled', [ __CLASS__, 'facebook_capi_report_subscription_cancellation' ], 10, 1);
		add_action('woocommerce_subscription_status_updated', [ __CLASS__, 'facebook_capi_report_subscription_update' ], 10, 3);

		if (self::track_facebook_capi_subscription_renewal()) {
			add_action('woocommerce_subscription_renewal_payment_complete', [ __CLASS__, 'facebook_capi_report_subscription_purchase_renewal' ], 10, 2);
		}
	}

	/**
	 * Subscription initial order
	 */
	public static function facebook_capi_report_subscription_payment_complete( $subscription ) {

		// Only process if this is the initial subscription payment
		if ($subscription->get_payment_count() !== 1) {
			Logger::debug('aborting Meta CAPI Subscribe hit because this is not the initial payment for subscription ' . $subscription->get_id());
			return;
		}

		self::send_subscription_hit('Subscribe', $subscription, $subscription->get_parent());
	}

	/**
	 * Subscription renewal
	 */
	public static function facebook_capi_report_subscription_purchase_renewal( $subscription, $renewal_order ) {

		$parent_order = $subscription->get_parent();

		// Abort if the order was created manually, and thus the parent order is missing.
		if (!$parent_order) {
			Logger::debug('aborting Meta CAPI RenewSubscription hit because parent order is missing for subscription ' . $subscription->get_id() . ' and renewal order ' . $renewal_order->get_id());
			return;
		}

		self::send_subscription_hit('RecurringSubscriptionPayment', $subscription, $parent_order, $renewal_order);
	}

	/**
	 * Subscription cancellation
	 */
	public static function facebook_capi_report_subscription_cancellation( $subscription ) {

		$parent_order = $subscription->get_parent();

		// Abort if the order was created manually, and thus no parent order exists.
		if (!$parent_order) {
			Logger::debug('aborting Meta CAPI CancelSubscription hit because parent order is missing for subscription ' . $subscription->get_id());
			return;
		}

		self::send_subscription_hit('CancelSubscription', $subscription, $parent_order);
	}

	/**
	 * Subscription update
	 *
	 * There is one use case that is not ideal: When the customer manually renews a subscription, it will be cancelled and reactivated.
	 */
	public static function facebook_capi_report_subscription_update( $subscription, $new_status, $old_status ) {

//		error_log('old status: ' . $old_status . ' new status: ' . $new_status);

		$parent_order = $subscription->get_parent();

		// Abort if the order was created manually, and thus no parent order exists.
		if (!$parent_order) {
			return;
		}

		/**
		 * Don't process on-hold status because it triggered by essentially every order simply when the payment is pending:
		 * https://woocommerce.com/document/managing-orders/#visual-diagram-to-illustrate-order-statuses
		 */

		// Putting a subscription on hold
//		if ('active' === $old_status && 'on-hold' === $new_status) {
//			self::send_subscription_hit('CancelSubscription', $subscription, $parent_order);
//		}
//
//		// Only run if we are reactivating a subscription which is on hold
//		if ('on-hold' === $old_status && 'active' === $new_status) {
//			self::send_subscription_hit('Subscribe', $subscription, $parent_order, null, true);
//		}
	}

	public static function track_facebook_capi_subscription_renewal() {

		// If Shop::track_subscription_renewal is false, return false.
		if (Shop::do_not_track_subscription_renewal()) {
			return false;
		}

		return (bool) apply_filters('pmw_facebook_subscription_renewal_tracking', true);
	}

	public static function do_not_track_facebook_capi_subscription_renewal() {
		return !self::track_facebook_capi_subscription_renewal();
	}

	/**
	 * Filter to enable the Facebook CAPI ads delivery opt-out
	 *
	 * @return bool
	 */
	private static function is_ads_delivery_opt_out_active() {

		$opt_out = apply_filters_deprecated('wooptpm_facebook_capi_ads_delivery_opt_out', [ false ], '1.13.0', 'pmw_facebook_capi_ads_delivery_opt_out');
		$opt_out = apply_filters_deprecated('wpm_facebook_capi_ads_delivery_opt_out', [ $opt_out ], '1.31.2', 'pmw_facebook_capi_ads_delivery_opt_out');

		return (bool) apply_filters('pmw_facebook_capi_ads_delivery_opt_out', $opt_out);
	}

	/**
	 * If the order is a subscription renewal,
	 * only continue if renewal tracking is enabled.
	 *
	 * @param $order
	 * @return bool
	 */
	private static function can_process_subscription_renewal_order( $order ) {

		// If the order is not a subscription renewal, we can process it.
		if (!Shop::is_wcs_renewal_order($order)) {
			return true;
		}

		// If the order is a subscription renewal,
		// we can process it if the shop owner allows it.
		if (self::track_facebook_capi_subscription_renewal()) {
			return true;
		}

		// If the order is a subscription renewal,
		// and the shop owner doesn't allow it,
		// we can't process it.
		return false;
	}

	private static function can_not_process_subscription_renewal_order( $order ) {
		return !self::can_process_subscription_renewal_order($order);
	}

	// https://developers.facebook.com/docs/marketing-apis/data-processing-options
	// https://developers.facebook.com/docs/marketing-apis/data-processing-options#conversions-api-and-offline-conversions-api
	private static function add_data_processing_options( $capi_event_data ) {

		$processing_options = apply_filters_deprecated('wooptpm_facebook_capi_data_processing_options', [ [] ], '1.13.0', 'pmw_facebook_capi_data_processing_options');
		$processing_options = apply_filters_deprecated('wpm_facebook_capi_data_processing_options', [ $processing_options ], '1.31.2', 'pmw_facebook_capi_data_processing_options');

		return array_merge($capi_event_data, apply_filters('pmw_facebook_capi_data_processing_options', $processing_options));
	}

	private static function get_user_data( $identifiers, $order ) {

		$user_data = [];

		// If fbp exists we set all real data
		// If fbp doesn't exist, we only set required fields with random data
		if (isset($identifiers['fbp'])) {
			$user_data['fbp'] = $identifiers['fbp'];
		}

		// Set fbc
		if (isset($identifiers['fbc'])) {
			$user_data['fbc'] = $identifiers['fbc'];
		}

		if (isset($identifiers['user_agent'])) {
			$user_data['client_user_agent'] = $identifiers['user_agent'];
		}

		// https://developers.facebook.com/docs/marketing-api/conversions-api/parameters
		// https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/customer-information-parameters
		// https://developers.facebook.com/docs/marketing-api/audiences/guides/custom-audiences/#example_sha256
		if (Options::is_facebook_capi_advanced_matching_enabled()) {

			// Set client_ip_address
			if (isset($identifiers['ip_address'])) {
				$user_data['client_ip_address'] = $identifiers['ip_address'];
			}

			$user_details = Helpers::get_user_data($order);

			// https://developers.facebook.com/docs/meta-pixel/advanced/advanced-matching
			if (isset($user_details['id']['sha256'])) {
				$user_data['external_id'] = $user_details['id']['sha256'];
			}

			if (isset($user_details['email']['facebook'])) {
				$user_data['em'] = $user_details['email']['facebook'];
			}

			if (isset($user_details['phone']['facebook'])) {
				$user_data['ph'] = $user_details['phone']['facebook'];
			}

			if (isset($user_details['first_name']['facebook'])) {
				$user_data['fn'] = $user_details['first_name']['facebook'];
			}

			if (isset($user_details['last_name']['facebook'])) {
				$user_data['ln'] = $user_details['last_name']['facebook'];
			}

			if (isset($user_details['city']['facebook'])) {
				$user_data['ct'] = $user_details['city']['facebook'];
			}

			if (isset($user_details['state']['facebook'])) {
				$user_data['st'] = $user_details['state']['facebook'];
			}

			if (isset($user_details['postcode']['facebook'])) {
				$user_data['zp'] = $user_details['postcode']['facebook'];
			}

			if (isset($user_details['country']['facebook'])) {
				$user_data['country'] = $user_details['country']['facebook'];
			}
		}

		return $user_data;
	}

	protected static function add_pixel_specific_identifiers( $identifiers ) {

		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

		if (isset($_cookie['_fbp']) && self::is_valid_fbp($_cookie['_fbp'])) {
			$identifiers['fbp'] = $_cookie['_fbp'];
		}

		if (isset($_cookie['_fbc']) && self::is_valid_fbc($_cookie['_fbc'])) {
			$identifiers['fbc'] = $_cookie['_fbc'];
		}

		// If an ipv6 address has been set in the session, we use it
		// If not, we try to get the IP address using the Geolocation class
		if (Shop::is_woocommerce_session_active() && WC()->session->get('client_ipv6')) {
			$identifiers['client_ip_address'] = WC()->session->get('client_ipv6');
		} elseif (isset($identifiers['ip_address'])) {
			$identifiers['client_ip_address'] = $identifiers['ip_address'];
		}

		if (isset($identifiers['user_agent'])) {
			$identifiers['client_user_agent'] = $identifiers['user_agent'];
		}

		/**
		 * Enrich the data with transient session data
		 */

		$transient_identifiers = Shop::get_transient_identifiers_from_session();

		if (isset($transient_identifiers['referrer'])) {
			$identifiers['referrer_url'] = $transient_identifiers['referrer'];
		}

		// If the _fbc cookie is not set,
		// but the fbclid transient is set,
		// we set the fbc to the fbclid.
		if (!isset($_cookie['_fbc']) && isset($transient_identifiers['fbclid'])) {
			$identifiers['fbc'] = $transient_identifiers['fbclid'];
		}

		return $identifiers;
	}

	public static function set_additional_identifiers_on_order( $identifiers ) {
		$identifiers['event_time'] = time();
		return $identifiers;
	}

	// https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc/
	public static function is_valid_fbp( $fbp ) {
		return (bool) preg_match('/^fb\.[0-2]\.\d{13}\.\d{8,20}$/', $fbp);
	}

	// https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc/
	public static function is_valid_fbc( $fbc ) {
		return (bool) preg_match('/^fb\.[0-2]\.\d{13}\.[\da-zA-Z_-]{8,}/', $fbc);
	}

	/**
	 * Facebook suggests to user their SDK to generate the random fbp
	 * but, we won't do that. If we want true anonymity we need to generate the random
	 * number on our own terms.
	 * https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc/
	 **/

	private static function get_random_fbp() {
		$random_fbp = [
			'version'         => 'fb',
			'subdomain_index' => 1,
			'creation_time'   => time(),
			'random_number'   => random_int(1000000000, 9999999999),
		];

		return implode('.', $random_fbp);
	}
}
