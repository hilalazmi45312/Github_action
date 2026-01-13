<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Server;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\EventStrategy\AbstractServerEventStrategy;

class AddToCartStrategy extends AbstractServerEventStrategy {
	protected $eventName = 'add_to_cart';
	protected $eventType = 'server';

	public function defineActions() {
		return [
			'woocommerce_add_to_cart' => [[$this, 'addToCart'], 10, ],
			'woocommerce_update_cart_action_cart_updated' => [$this, 'cartUpdated']
		];
	}

	public function cartUpdated( $cart ) {}

	public function addToCart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
		if (0 !== $variation_id) {
			$item = $this->wcTransformer->getItemFromProductVariation(wc_get_product($variation_id));
		} else {
			$item = $this->wcTransformer->getItemFromProduct(wc_get_product($product_id));
		}
		$item->quantity = $quantity;
		$event = new Event( 'add_to_cart' );
		$event->setItems( [$item] );
		$this->mpClient->sendEvent($event);
	}
}
