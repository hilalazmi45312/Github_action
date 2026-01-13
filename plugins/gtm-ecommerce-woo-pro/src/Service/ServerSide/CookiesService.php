<?php

namespace GtmEcommerceWooPro\Lib\Service\ServerSide;

use GtmEcommerceWooPro\Lib\EventStrategy\Server\PurchaseStrategy;

/**
 * Takes care of storing and retrieving cookies used for tracking.
 * It should contain a list of cookies that we want to track and store them on purchase so it can be send server-side
 */
class CookiesService {

	protected $mpClient;
	protected $wpSettingsUtil;

	const COOKIES_NAMES = [
		'_ga',
		'FPID',
		'_fbp', '_fbc', '_gtmeec',
		'_pinterest_sess', '_pinterest_ct', '_pinterest_ct_rt', '_epik', '_derived_epik', '_pin_unauth', '_pinterest_ct_ua', '_routing_id',
		'ttclid'
	];

	protected $mpClientUtil;

	public function __construct( $mpClient, $wpSettingsUtil) {
		$this->mpClient = $mpClient;
		$this->wpSettingsUtil = $wpSettingsUtil;
	}

	public function initialize() {
		if ($this->wpSettingsUtil->getOption('cookies_storage') !== '1') {
			return;
		}
		add_action( 'wp_head', [$this, 'sessionData'] );
		add_action( 'woocommerce_new_order', [$this, 'orderData'] );
	}

	public function sessionData() {
		if (isset(WC()->session)) {
			$this->mpClient->getClientId();
			$cookies = WC()->session->get(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_COOKIES);

			if (!is_array($cookies)) {
				$cookies = [];
			}

			foreach (self::COOKIES_NAMES as $cookieName) {
				if (isset($_COOKIE[$cookieName])) {
					$cookies[$cookieName] = filter_var($_COOKIE[$cookieName]);
				}
			}

			WC()->session->set(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_COOKIES, $cookies);
		}
	}

	public function orderData( $orderId ) {
		if (isset(WC()->session)) {
			$order = wc_get_order($orderId);

			$cookiesFromOrder = $order->get_meta(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_COOKIES);
			$cookiesFromSession = WC()->session->get(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_COOKIES);

			if (!is_array($cookiesFromOrder)) {
				$cookiesFromOrder = [];
			}

			if (!is_array($cookiesFromSession)) {
				$cookiesFromSession = [];
			}

			$cookies = array_merge($cookiesFromOrder, $cookiesFromSession);

			foreach (self::COOKIES_NAMES as $cookieName) {
				if (isset($_COOKIE[$cookieName])) {
					$cookies[$cookieName] = filter_var($_COOKIE[$cookieName]);
				}
			}
			$order->update_meta_data(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_COOKIES, $cookies);

			$order->update_meta_data(PurchaseStrategy::ORDER_META_KEY_PURCHASE_SERVER_EVENT_CLIENT_ID, $this->mpClient->getClientId());
			$order->save();
		}
	}
}
