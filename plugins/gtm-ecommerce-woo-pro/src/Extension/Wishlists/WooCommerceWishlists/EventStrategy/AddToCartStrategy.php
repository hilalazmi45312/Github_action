<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class AddToCartStrategy extends AbstractWishlistPageStrategy {
	protected $eventName = EventType::ADD_TO_CART;

	public function defineActions() {
		return [
			'woocommerce_wishlists_before_wrapper' => [[$this, 'addScript'], 11],
		];
	}

	public function addScript() {
		if (false === $this->isWishlist()) {
			return;
		}

		$this->loadWishlistItems();

		$stringifiedEvent = json_encode(new Event(EventType::ADD_TO_CART));

		$this->wcOutput->script(
			<<<EOD
jQuery('.wishlist-add-to-cart-button').click(function(e) {
    const wishlistItemId = new URLSearchParams(jQuery(this).attr('href')).get('wishlist-item-key');
    const quantity = jQuery(this).closest('tr').find('input.qty').val();
    const item = window.gtm_ecommerce_pro.extensions.wishlists.getItemByWishlistId(wishlistItemId);

    if (null === item || undefined === quantity) {
        return;
    }

    item.quantity = parseInt(quantity);

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

		$this->wcOutput->script(
			<<<EOD
jQuery(document).on('click', '.product-purchase a.wl-add-all', function(e) {
    e.preventDefault();

    const targetUrl = jQuery(this).attr('href');
    const wishlistItems = gtm_ecommerce_pro.extensions.wishlists.getAllItems();

    let items = [];
    let value = 0;

    for (let key in wishlistItems) {
        let i = wishlistItems[key];
        items.push(i);
        value += i.price * i.quantity;
    }

    if (0 < items.length) {
        let event = {$stringifiedEvent};

        dataLayer.push({
            ...event,
          'ecommerce': {
            ...event.ecommerce,
            'value': value,
            'items': items
          }
        });
    }

    document.location = targetUrl;
});
EOD
		);

		$this->wcOutput->script(
			<<<EOD
jQuery('.wl-actions-table .btn-apply').click(function(e) {
    const action = jQuery('select[name=wlupdateaction]').val();

    if (false === ['add-to-cart', 'quantity-add-to-cart'].includes(action)) {
        return;
    }

    let value = 0;

    const items = jQuery('input[type="checkbox"][name="wlitem\\[\\]"]:checked').map(function() {
        let i = window.gtm_ecommerce_pro.extensions.wishlists.getItemByWishlistId(this.value);
        i.quantity = 'quantity-add-to-cart' === action ? jQuery(this).closest('tr').find('input.qty').val() : i.quantity;

        value += i.price * i.quantity;

        return i;
    }).get();

    if (0 === items.length) {
        return;
    }

    let event = {$stringifiedEvent};

    dataLayer.push({
    	...event,
      'ecommerce': {
      	...event.ecommerce,
      	'value': value,
        'items': items
      }
    });
});
EOD
		);
	}
}
