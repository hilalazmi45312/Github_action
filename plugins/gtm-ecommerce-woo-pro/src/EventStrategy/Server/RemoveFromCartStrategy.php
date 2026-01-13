<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Server;

use GtmEcommerceWooPro\Lib\EventStrategy\AbstractServerEventStrategy;

class RemoveFromCartStrategy extends AbstractServerEventStrategy {

	protected $eventName = 'remove_from_cart';
	protected $eventType = 'server';

	public function defineActions() {
		return [
			'woocommerce_remove_cart_item' => [[$this, 'removeCartItem'], 10, 2],
		];
	}

	public function removeCartItem( $cart_item_key, $cart ) {}
}
