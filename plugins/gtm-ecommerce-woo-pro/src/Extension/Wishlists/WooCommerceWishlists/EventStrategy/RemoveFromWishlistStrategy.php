<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class RemoveFromWishlistStrategy extends AbstractWishlistPageStrategy {

	protected $eventName = EventType::REMOVE_FROM_WISHLIST;

	public function defineActions() {
		return [
			'woocommerce_wishlists_before_wrapper' => [[$this, 'handleWishlist'], 11],
		];
	}

	public function handleWishlist() {
		if (false === $this->isWishlist()) {
			return;
		}

		$this->loadWishlistItems();

		$stringifiedEvent = json_encode(new Event(EventType::REMOVE_FROM_WISHLIST));

		$this->wcOutput->script(
			<<<EOD
jQuery('.wl-actions-table .btn-apply').click(function(e) {
    const action = jQuery('select[name=wlupdateaction]').val();

    if (false === ['quantity', 'quantity-add-to-cart'].includes(action)) {
        return;
    }

    let value = 0;

    const items = jQuery('input[type="checkbox"][name="wlitem\\[\\]"]:checked').map(function() {
        let i = window.gtm_ecommerce_pro.extensions.wishlists.getItemByWishlistId(this.value);
        const newQuantity = jQuery(this).closest('tr').find('input.qty').val();

        if (newQuantity < i.quantity) {
            i.quantity = i.quantity - newQuantity;
            value += i.price * i.quantity;
            return i;
        }
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

		$this->wcOutput->script(
			<<<EOD
jQuery('.wl-actions-table .btn-apply').click(function(e) {
    const action = jQuery('select[name=wlupdateaction]').val();

    if ('remove' !== action) {
        return;
    }

    let value = 0;

    const items = jQuery('input[type="checkbox"][name="wlitem\\[\\]"]:checked').map(function() {
        let i = window.gtm_ecommerce_pro.extensions.wishlists.getItemByWishlistId(this.value);

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

		$this->wcOutput->script(
			<<<EOD
jQuery(document).on('click', '.wlconfirm', function(e) {
    const wishlistItemId = new URLSearchParams(jQuery(this).attr('href')).get('wishlist-item-key');
    const item = window.gtm_ecommerce_pro.extensions.wishlists.getItemByWishlistId(wishlistItemId);

    if (null === item) {
        return;
    }

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
