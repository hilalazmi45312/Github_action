<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\EventStrategy;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class AddToWishlistStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::ADD_TO_WISHLIST;

	public function defineActions() {
		return [
			'wp_head' => [[$this, 'handleProductPage'], 11],
			'wp_footer' => [[$this, 'addScript'], 11],
		];
	}

	public function handleProductPage() {
		global $product;

		if (null === $product || false === is_product()) {
			return;
		}

		$items = [];

		$items[$product->get_id()] = $this->wcTransformer->getItemFromProduct($product);
;
		$this->wcOutput->addItems($items, 'product_id');
	}

	public function addScript() {
		$stringifiedEvent = json_encode(new Event(EventType::ADD_TO_WISHLIST));

		$this->wcOutput->script(
			<<<EOD
jQuery(document.body).on('woosw_add', function(e, productId) {
    const item = gtm_ecommerce_pro.getItemByProductId(productId);

    if (0 === Object.keys(item).length) {
        return;
    }

    item.quantity = 1;

    let event = {$stringifiedEvent};

    dataLayer.push({
        ...event,
        'ecommerce': {
            ...event.ecommerce,
            'value': (item.price * item.quantity),
            'items': [item]
        }
    });
});
EOD
		);
	}
}
