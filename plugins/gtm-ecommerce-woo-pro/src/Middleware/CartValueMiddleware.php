<?php

namespace GtmEcommerceWooPro\Lib\Middleware;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class CartValueMiddleware extends AbstractEventMiddleware {

	protected $supportedEvents = [
		EventType::ABANDON_CART,
		EventType::ABANDON_CHECKOUT,
		EventType::ADD_BILLING_INFO,
		EventType::ADD_PAYMENT_INFO,
		EventType::ADD_SHIPPING_INFO,
		EventType::BEGIN_CHECKOUT,
		EventType::VIEW_CART,
	];

	protected function apply( Event $event) {
		global $woocommerce;

		$cart = $woocommerce->cart;

		$cartValue = $cart->get_total(null) - $cart->get_total_tax();

		return $event->setValue($cartValue);
	}
}
