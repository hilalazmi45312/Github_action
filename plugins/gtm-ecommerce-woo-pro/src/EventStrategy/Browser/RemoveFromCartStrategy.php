<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

/**
 * When a user sees a list of items/offerings
 * <td class="product-remove">
							<a href="http://docker.local/?page_id=6&amp;remove_item=45c48cce2e2d7fbdea1afc51c7c6ad26&amp;_wpnonce=f2b331db52" class="remove" aria-label="Remove this item" data-product_id="9" data-product_sku="WCCLITESTP">×</a>                      </td>
 */
class RemoveFromCartStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::REMOVE_FROM_CART;

	public function defineActions() {
		add_filter('woocommerce_cart_item_remove_link', [ $this, 'cartItemRemoveLink' ], 10, 2);
		return [
			'wp_head' => [ $this, 'afterCart' ],
		];
	}

	public function cartItemRemoveLink( $link, $cart_item_key ) {
		global $woocommerce;
		$cart = $woocommerce->cart->get_cart();
		$cartItem = isset($cart[$cart_item_key]) ? $cart[$cart_item_key] : null;
		if (null === $cartItem) {
			return $link;
		}
		if (isset($cartItem['variation_id']) && 0 != $cartItem['variation_id']) {
			$newLink = substr($link, 2);
			$newLink = '<a data-variation_id="' . $cartItem['variation_id'] . '" ' . $newLink;
			return $newLink;
		}
		return $link;
	}

	public function afterCart() {
		global $woocommerce;
		$itemsByProductId = [];
		if (isset($woocommerce->cart)) {
			foreach ( $woocommerce->cart->get_cart() as $item ) {
				$product                                = wc_get_product( $item['data']->get_id() );
				$itemsByProductId[ $product->get_id() ] = $this->wcTransformer->getItemFromProduct( $product );
			}
			$this->onRemoveLinkClick( $itemsByProductId );
		}
	}

	public function onRemoveLinkClick( $items ) {
		$this->wcOutput->addItems($items, 'product_id');
		$this->wcOutput->script(
			<<<EOD
if (typeof wp !== 'undefined' && wp.hooks && wp.hooks.addAction) {
	wp.hooks.addAction('experimental__woocommerce_blocks-cart-remove-item', 'gtm-ecommerce-woo', function(details) {
		if (details.product && details.product.id) {
			let item = gtm_ecommerce_pro.getItemByProductId(details.product.id);

			if (0 === Object.keys(item).length) {
				return;
			}
			item.quantity = 1;

			let event = {$this->getStringifiedEvent()};

			dataLayer.push({
				...event,
			  'ecommerce': {
				...event.ecommerce,
				'value': item.price,
				'items': [item]
			  }
			});
		}

	});
}
jQuery(document).on('click', '.remove', function(ev) {
    let product_id = jQuery(ev.currentTarget).attr('data-product_id');
    let variation_id = jQuery(ev.currentTarget).attr('data-variation_id');
    if (undefined === product_id && undefined === variation_id) {
    	return;
	}

	let item = null;
	if (variation_id) {
		item = gtm_ecommerce_pro.getItemByProductId(variation_id);
	} else {
    	item = gtm_ecommerce_pro.getItemByProductId(product_id);
    }

	if (0 === Object.keys(item).length) {
		return;
	}

    item.quantity = 1;

	let event = {$this->getStringifiedEvent()};

	dataLayer.push({
		...event,
	  'ecommerce': {
		...event.ecommerce,
		'value': item.price,
		'items': [item]
	  }
	});
});

jQuery(document).on('submit', '.woocommerce-cart-form', function(ev) {
	jQuery('input[name$="[qty]"]', ev.currentTarget).each(function(i, el) {
		var previousValue = el.defaultValue || 0;
		var currentValue = el.value || 0;
		var diff = currentValue - previousValue;

		if (diff < 0) {
			var quantity = Math.abs(diff);
			var element = jQuery(el).parents('.cart_item').find('[data-product_id]');
			var productId = element.attr('data-variation_id') ?? element.attr('data-product_id');
			var item = gtm_ecommerce_pro.getItemByProductId(productId);
			item.quantity = quantity;

			let event = {$this->getStringifiedEvent()};

			dataLayer.push({
				...event,
			  'ecommerce': {
				...event.ecommerce,
				'value': (item.price * quantity),
				'items': [item]
			  }
			});
		}
	});
});
EOD
		);
	}

	protected function getStringifiedEvent() {
		return json_encode(new Event(EventType::REMOVE_FROM_CART));
	}
}
