<?php

namespace GtmEcommerceWooPro\Lib\Middleware;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class CurrencyMiddleware extends AbstractEventMiddleware {

	protected $supportedEvents = [
		EventType::ABANDON_CART,
		EventType::ABANDON_CHECKOUT,
		EventType::ADD_BILLING_INFO,
		EventType::ADD_PAYMENT_INFO,
		EventType::ADD_SHIPPING_INFO,
		EventType::ADD_TO_CART,
		EventType::ADD_TO_WISHLIST,
		EventType::BEGIN_CHECKOUT,
		EventType::REFUND,
		EventType::REMOVE_FROM_CART,
		EventType::REMOVE_FROM_WISHLIST,
		EventType::SELECT_ITEM,
		EventType::VIEW_CART,
		EventType::VIEW_ITEM,
	];

	protected function apply( Event $event) {
		$currency = get_woocommerce_currency();

		if (false === is_string($currency) || true === empty($currency)) {
			return $event;
		}

		return $event->setCurrency($currency);
	}
}
