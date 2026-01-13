<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class AddToWishlistStrategy extends AbstractWishlistPageStrategy {

	protected $eventName = EventType::ADD_TO_WISHLIST;

	public function defineActions() {
		return [
			'wp_head' => [[$this, 'handleProductPage'], 11],
			'woocommerce_wishlists_before_wrapper' => [[$this, 'handleWishlist'], 11],
		];
	}

	public function handleProductPage() {
		global $product;

		if (null === $product || false === is_product()) {
			return;
		}

		$items = [];
		if (\WC_Product_Variable::class === get_class($product)) {
			foreach ( $product->get_available_variations( 'object' ) as $variation ) {
				$items[ $variation->get_id() ] = $this->wcTransformer->getItemFromProductVariation( $variation );
				$this->wcOutput->addItems($items, 'id');
			}
		} else {
			$items[$product->get_id()] = $this->wcTransformer->getItemFromProduct($product);
			$this->wcOutput->addItems($items, 'product_id');
		}

		$stringifiedEvent = json_encode(new Event(EventType::ADD_TO_WISHLIST));

		$this->wcOutput->script(
			<<<EOD
jQuery(document).on('submit', '.cart', function(ev) {
    const form = jQuery(this);
    const formData = new FormData(this);
    const itemId = (new URL(form.attr('action'), document.baseURI)).searchParams.get('add-to-wishlist-itemid');
    const quantity = formData.get('quantity');
    const variationId = formData.get('variation_id');

    if (null === itemId || null === quantity) {
        return;
    }

    let item = null === variationId ? gtm_ecommerce_pro.getItemByProductId(itemId) : gtm_ecommerce_pro.getItemByItemId(variationId);

    if (0 === Object.keys(item).length) {
        return;
    }

    item.quantity = parseInt(quantity);

    let event = {$stringifiedEvent};

    dataLayer.push({
    	...event,
      'ecommerce': {
      	...event.ecommerce,
      	'value': (item.price * item.quantity),
        'items': [item].map((i) => {
            delete i.item_list_name;
            delete i.item_list_id;

            return i;
        })
      }
    });
});
EOD
		);
	}

	public function handleWishlist() {
		if (false === $this->isWishlist()) {
			return;
		}

		$this->loadWishlistItems();

		$stringifiedEvent = json_encode(new Event(EventType::ADD_TO_WISHLIST));

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

        if (newQuantity > i.quantity) {
            i.quantity = newQuantity - i.quantity;
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
	}
}
