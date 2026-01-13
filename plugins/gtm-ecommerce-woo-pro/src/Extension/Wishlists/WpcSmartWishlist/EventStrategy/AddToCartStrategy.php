<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\EventStrategy;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class AddToCartStrategy extends AbstractWishlistPageStrategy {
	protected $eventName = EventType::ADD_TO_CART;

	public function defineActions() {
		return [
			'woosw_wishlist_items_after' => [[$this, 'handleList'], 11, 2],
		];
	}

	public function handleList( $key, $products) {
		if (true === is_ajax()) {
			return;
		}

		$this->handleWishlistPage($key, $products);
	}

	private function handleWishlistPage( $key, $products) {
		$this->loadWishlistItems(array_keys($products), $key);

		$stringifiedEvent = json_encode(new Event(EventType::ADD_TO_CART));

		$this->wcOutput->script(
			<<<EOD
jQuery('.add_to_cart_button').click(function(e) {
    const productId = jQuery(this).data('product_id');
    const item = gtm_ecommerce_pro.getItemByProductId(productId);

    if (0 === Object.keys(item).length) {
        return;
    }

    let event = {$stringifiedEvent};

    dataLayer.push({
        ...event,
        'ecommerce': {
            ...event.ecommerce,
            'value': (item.price * 1),
            'items': [item]
        }
    });
});
EOD
		);
	}
}
