<?php

/**
 * Abstract Server 2 Server class
 */

namespace SweetCode\Pixel_Manager;

use RuntimeException;
use SweetCode\Pixel_Manager\Admin\Environment;

defined('ABSPATH') || exit; // Exit if accessed directly

abstract class S2S {

	protected static function get_identifiers_key() {
		// Check if the method has been overridden in the subclass
		if (static::class === self::class) {
			throw new RuntimeException('The method get_identifiers_key must be overridden in the subclass.');
		}
	}

	protected static function get_purchase_hit_key() {
		// Check if the method has been overridden in the subclass
		if (static::class === self::class) {
			throw new RuntimeException('The method get_purchase_hit_key must be overridden in the subclass.');
		}
	}

	/**
	 * Save the identifiers into the session.
	 *
	 * @return void
	 */
	public static function set_identifiers_on_session() {

		// Don't run if WC has not initialized a session yet
		if (!Shop::is_woocommerce_session_active()) {
			return;
		}

//		// Don't run if we already have set the user identifiers into the session
//		if (null !== WC()->session->get(static::get_identifiers_key())) {
//			return;
//		}

		WC()->session->set(static::get_identifiers_key(), self::get_current_identifiers());
	}

	public static function set_identifiers_on_order( $order ) {

// 		Don't run if we already have set the user identifiers into the order
//		if ($order->meta_exists(static::get_identifiers_key())) {
//			return;
//		}

		$identifiers = static::get_current_identifiers();
		$identifiers = static::set_additional_identifiers_on_order($identifiers);

		$order->update_meta_data(static::get_identifiers_key(), $identifiers);
		$order->save();
	}

	/**
	 * Get the current identifiers from the session and the browser.
	 *
	 * First get the identifiers from the session, then from the browser.
	 * (If the session is empty, return an empty array.)
	 * Add new browser identifiers to the session identifiers.
	 * Update existing session identifiers with the browser identifiers.
	 *
	 * The idea is that during the client session new identifiers can be added
	 * and existing identifiers can be updated.
	 *
	 * By updating the session identifiers with the browser identifiers,
	 * while the visitor goes down the funnel,
	 * we make sure that the internal session identifiers are complete and up-to-date.
	 *
	 * @return array
	 */
	private static function get_current_identifiers() {

		$identifiers_from_session = [];
		if (Shop::is_woocommerce_session_active()) {
			$identifiers_from_session = WC()->session->get(static::get_identifiers_key(), []);
		}

		$identifiers_from_browser = static::get_identifiers_from_browser();
		return array_merge($identifiers_from_session, $identifiers_from_browser);
	}

	public static function set_additional_identifiers_on_order( $identifiers ) {
		return $identifiers;
	}

	/**
	 * Get the identifiers from the browser
	 *
	 * @return array
	 */
	private static function get_identifiers_from_browser() {

		// Prevent reading out from an iframe
		// This can be an issue on payment gateway iframes that are not on the same domain.
		if (Helpers::is_iframe()) {
			return [];
		}

		$_server = Helpers::get_input_vars(INPUT_SERVER);

		$identifiers = [];

		// Add user IP
		if (Geolocation::get_user_ip()) {
			$identifiers['ip_address'] = Geolocation::get_user_ip();
		}

		// Add user agent
		if (isset($_server['HTTP_USER_AGENT'])) {
			$identifiers['user_agent'] = $_server['HTTP_USER_AGENT'];
		}

		// Add referrer
		if (isset($_server['HTTP_REFERER'])) {
			$identifiers['referrer'] = $_server['HTTP_REFERER'];
		}

		return (array) static::add_pixel_specific_identifiers($identifiers);
	}

	/**
	 * If an admin created an order in the back-end,
	 * for a customer to be paid on the front-end,
	 * we need to update the identifiers when the customer pays.
	 * Else return the identifiers from the order.
	 * If the identifiers are not set on the order, return the identifiers from the browser.
	 *
	 * @param $order
	 * @return array
	 */
	protected static function get_identifiers_from_order( $order ) {

		// If customer wants to pay order that has been created in the back-end
		// we need to update the identifiers when the customer pays.
		if (!is_admin() && Shop::is_backend_manual_order($order)) {
			$identifiers = self::get_current_identifiers();
			self::update_identifiers_on_order($order, $identifiers);
			return $identifiers;
		}

		$identifiers = $order->get_meta(static::get_identifiers_key(), true);
		return is_array($identifiers) ? $identifiers : self::get_current_identifiers();
	}

	protected static function add_pixel_specific_identifiers( $identifiers ) {
		// Check if the method has been overridden in the subclass
		if (static::class === self::class) {
			throw new RuntimeException('The method add_pixel_specific_identifiers must be overridden in the subclass.');
		}
	}

	protected static function update_identifiers_on_order( $order, $identifiers ) {

		$data = $order->get_meta(static::get_identifiers_key(), true);
		$data = array_merge($data, $identifiers);
		$order->update_meta_data(static::get_identifiers_key(), $data);
		$order->save();
	}

	public static function hook_into_generic_events() {

		if (Environment::is_woocommerce_active()) {
			self::hook_into_order_created_events();
			self::hook_into_purchase_events();
		}
	}

	public static function hook_into_purchase_events() {
		foreach (static::get_purchase_trigger_hooks() as $hook) {
			add_action($hook, [ static::class, 'send_purchase_hit_order_id' ]);
		}
	}

	// Save the session identifiers on the order so that we can use them later when the order gets paid or completed
	// https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-checkout.html#source-view.403
	public static function hook_into_order_created_events() {
		add_action('woocommerce_checkout_order_created', [ static::class, 'set_identifiers_on_order' ]);
	}

	public static function get_purchase_trigger_hooks() {

		$hooks = [];

		foreach (Shop::get_order_paid_statuses() as $status) {
			$hooks[] = 'woocommerce_order_status_' . $status;
		}

		// Used for marketing pixels mainly
		$hooks[] = 'woocommerce_payment_complete';
		$hooks[] = 'woocommerce_order_status_on-hold';

		return $hooks;
	}

	public static function send_purchase_hit_order_id( $order_id ) {
		static::send_purchase_hit(wc_get_order($order_id));
	}

	public static function send_s2s_event( $event_data ) {

		if (!static::is_pixel_s2s_active()) {
			return;
		}

		if (!$event_data) {
			wp_send_json_error();
		}

		static::send_event_hit($event_data);
	}

	public static function is_pixel_s2s_active() {
		// Check if the method has been overridden in the subclass
		if (static::class === self::class) {
			throw new RuntimeException('The method is_pixel_s2s_active must be overridden in the subclass.');
		}
	}

	public static function send_purchase_hit( $order ) {
		// Check if the method has been overridden in the subclass
		if (static::class === self::class) {
			throw new RuntimeException('The method send_event_hit must be overridden in the subclass.');
		}
	}

	public static function send_event_hit( $event_data ) {
		// Check if the method has been overridden in the subclass
		if (static::class === self::class) {
			throw new RuntimeException('The method send_event_hit must be overridden in the subclass.');
		}
	}

	protected static function can_process_order( $order ) {

		// Don't continue if it's a user that we don't want to track
		if (Shop::do_not_track_user(Shop::get_order_user_id($order))) {
			return false;
		}

		// Don't continue if the purchase hit has already been sent
		if ($order->meta_exists(static::get_purchase_hit_key())) {
			return false;
		}

		return true;
	}

	protected static function can_not_process_order( $order ) {
		return !self::can_process_order($order);
	}
}
