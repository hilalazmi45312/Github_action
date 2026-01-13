<?php

namespace GtmEcommerceWooPro\Lib\Extension\Themesquad\QuickView\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class AddToCartStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::ADD_TO_CART;

	public function defineActions() {
		return [
			'wp_head' => [$this, 'wpHead'],
			'wc_quick_view_after_single_product' => [[$this, 'quickViewModal'], 11],
		];
	}

	public function wpHead() {
		$stringifiedEvent = json_encode(new Event(EventType::ADD_TO_CART));

		$this->wcOutput->script(<<<EOT
	jQuery('body').on('quick-view-displayed', function() {
	  const hookElement = jQuery('#gtm-ecommerce-woo__wc-qv__data-carrier__add-to-cart');

	  if (0 === hookElement.length) {
	    return;
	  }

	  const data = {
	  	item: hookElement.data('item'),
	  	itemsById: hookElement.data('items-by-id'),
	  	itemsByAttributes: hookElement.data('items-by-attributes'),
	  };

	  if (0 < data.itemsByAttributes.length) {
	  	window.gtm_ecommerce_pro.items.byAttributeId = data.itemsByAttributes;
	  	window.gtm_ecommerce_pro.items.byId = {
	  		...window.gtm_ecommerce_pro.items.byId,
	  		...data.itemsById
	  	};

	  	jQuery(document).on('click', '.woocommerce.quick-view.single-product .single_add_to_cart_button', function(ev) {
			var form = jQuery(ev.currentTarget).parents('form.cart');
			var quantity = jQuery('[name="quantity"]', form).val();
			var product_id = jQuery('[name="add-to-cart"]', form).val();
			var attributes = jQuery('.variations select', form).get().reduce(function(agg, el) {agg[el.name] = jQuery(el).val(); return agg;}, {});
			var attribute = (gtm_ecommerce_pro.getItemsByAttributes() || []).filter(function(el) {

				for (var key in attributes) {
				  if (!el[key] || el[key] !== attributes[key]) {
					return false;
				  }
				}
				return true;
			});
			if (!attribute.length || attribute.length === 0) {
				return true;
			}

			var item = gtm_ecommerce_pro.getItemByItemId([attribute[0].item_id]);
			item.quantity = parseInt(quantity);

			let event = {$stringifiedEvent};

			dataLayer.push({
				...event,
			  'ecommerce': {
				...event.ecommerce,
				'value': (item.price * quantity),
				'items': [item]
			  }
			});
		});

		return;
	  }
	  let item = data.item;

	  jQuery(document).on('click', '.woocommerce.quick-view.single-product .single_add_to_cart_button', function(ev) {
	    var form = jQuery(ev.currentTarget).parents('form.cart');
		var quantity = jQuery('[name="quantity"]', form).val();
		var product_id = jQuery('[name="add-to-cart"]', form).val();

		item.quantity = parseInt(quantity);

		let event = {$stringifiedEvent};

		dataLayer.push({
			...event,
		  'ecommerce': {
			...event.ecommerce,
			'value': (item.price * quantity),
			'items': [item]
		  }
		});
	  });
	});
EOT
		);
	}

	public function quickViewModal() {
		global $product;

		if (is_null($product)) {
			return;
		}

		$itemsById = [];
		$itemsByAttributes = [];
		$singleItem = [];

		if ( 'WC_Product_Variable' === get_class( $product ) ) {
			foreach ( $product->get_available_variations( 'object' ) as $variation ) {
				$item = $this->wcTransformer->getItemFromProductVariation( $variation );
				$itemsById[ $variation->get_id() ] = $item;
				$attributes = $variation->get_variation_attributes();
				$attributes['item_id'] = $variation->get_id();
				$itemsByAttributes[]= $attributes;
			}
		} else {
			$singleItem = $this->wcTransformer->getItemFromProduct($product);
		}

		echo sprintf(
			'<div id="gtm-ecommerce-woo__wc-qv__data-carrier__add-to-cart" data-item="%s" data-items-by-id="%s" data-items-by-attributes="%s"  style="display: none;"></div>',
			esc_html(json_encode($singleItem)),
			esc_html(json_encode($itemsById)),
			esc_html(json_encode($itemsByAttributes))
		);
	}
}
