<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AddToCartStrategy as FreeAddToCartStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class AddToCartStrategy extends FreeAddToCartStrategy {

	protected $eventType = 'web';

	protected $itemListName;
	protected $itemListId;
	public $settings = [
		'product_redirect' => [
			'label' => 'Redirect on Single Product',
			'description' => 'Enable if adding to cart on Single Product reloads page.',
			'value' => false,
		],
		'product_ajax' => [
			'label' => 'AJAX on Single Product',
			'description' => 'Enable if adding to cart on Single Product happens dynamically without page reload.',
			'value' => true,
		]
	];

	public function defineActions() {
		return [
			'the_post' => [[$this, 'thePost'], 11],
			'wp_footer' => [$this, 'afterShopLoop'],
			'woocommerce_add_to_cart' => [[$this, 'addToCart'], 10, 6],
			'wp_head' => [$this, 'wpHead']
		];
	}

	public function addToCart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
		if (wp_doing_ajax()) {
			return true;
		}
		if (false === $this->settings['product_redirect']['value']) {
			return true;
		}
		if (0 === $variation_id) {
			$item = $this->wcTransformer->getItemFromProduct(wc_get_product($product_id));
		} else {
			$item = $this->wcTransformer->getItemFromProductVariation(wc_get_product($variation_id));
		}
		$item->quantity = $quantity;
		$event = new Event( EventType::ADD_TO_CART );
		$event->setItems( [$item] );
		$this->wcOutput->dataLayerPush( $event );
	}

	public function computeList() {
		if (null !== $this->itemListName) {
			return;
		}

		$this->itemListName = 'default';
		$this->itemListId   = '0';

		if ( is_product_category() ) {
			$cat                = get_queried_object();
			$this->itemListName = 'category_' . $cat->name;
			$this->itemListId   = 'category_' . $cat->term_id;
		}

		if ( is_product_tag() ) {
			$cat                = get_queried_object();
			$this->itemListName = 'tag_' . $cat->name;
			$this->itemListId   = 'tag_' . $cat->term_id;
		}
	}

	public function productLoop() {
		global $product;
		if (is_a($product, 'WC_Product')) {
			$this->computeList();
			$item = $this->wcTransformer->getItemFromProduct($product);
			$item->setItemListName( $this->itemListName );
			$item->setItemListId( $this->itemListId );

			$this->itemsByProductId[$product->get_id()] = $item;
		}
	}

	public function singleProduct() {
		global $product;
		// if product is null then this must be other WP post
		if (is_null($product)) {
			return false;
		}
		if (is_product() && false === $this->firstPost) {
			$this->firstPost = true;

			if (false === $this->settings['product_ajax']['value']) {
				return true;
			}

			if ( get_class( $product ) === 'WC_Product_Variable' ) {
				$itemsById = [];
				$itemsByAttributes = [];
				foreach ( $product->get_available_variations( 'object' ) as $variation ) {
					$item                          = $this->wcTransformer->getItemFromProductVariation( $variation );
					$itemsById[ $variation->get_id() ] = $item;
					$attributes                    = $variation->get_variation_attributes();
					$attributes['item_id']         = $variation->get_id();
					$itemsByAttributes[]           = $attributes;
				}
				return $this->onCartSubmitScriptVariable( $itemsByAttributes, $itemsById );
			} else {
				$item = $this->wcTransformer->getItemFromProduct( $product );
				$this->onCartSubmitScript( $item );
			}
		}
	}

	public function onCartSubmitScriptVariable( $attributes, $items ) {
		$this->wcOutput->addItems($items, 'id');
		$this->wcOutput->setItemsByAttributes($attributes);

		$this->wcOutput->script(
			<<<EOD
function handleAddToCart(form) {
	var quantity = jQuery('[name="quantity"]', form).val();
    var product_id = jQuery('[name="add-to-cart"]', form).val();
    var attributes = jQuery('.variations select', form).get().reduce(function(agg, el) {agg[el.name] = jQuery(el).val(); return agg;}, {});
    var attribute = (gtm_ecommerce_pro.getItemsByAttributes() || []).filter(function(el) {

        for (var key in attributes) {
          if (undefined !== el[key] && 0 === el[key].length) {
            continue;
          }

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

    let event = {$this->getStringifiedEvent()};

    dataLayer.push({
    	...event,
      'ecommerce': {
      	...event.ecommerce,
      	'value': (item.price * item.quantity),
        'items': [item]
      }
    });
}

var trackOnClick = false;
var trackOnAddToCart = false;
jQuery( document.body ).on( 'added_to_cart', function( event, fragments, cartHash, button ) {
	if (trackOnClick === true) {
		return;
	}
	trackOnAddToCart = true;
	var form = jQuery(button).parents('form.cart');
	handleAddToCart(form);
});
jQuery(document).on('click', '.cart .single_add_to_cart_button', function(ev) {
	if (trackOnAddToCart === true) {
		return;
	}
	trackOnClick = true;
	trackOnAddToCart = false; // disable fallback
	var form = jQuery(ev.currentTarget).parents('form.cart');
	handleAddToCart(form);
});

EOD
		);

	}

	public function wpHead() {
		global $woocommerce;
		if (is_cart()) {
			$itemsByProductId = [];
			foreach ( $woocommerce->cart->get_cart() as $item ) {
				$product                                = wc_get_product( $item['data']->get_id() );
				$itemsByProductId[ $product->get_id() ] = $this->wcTransformer->getItemFromProduct( $product );
			}
			$this->wcOutput->addItems($itemsByProductId, 'product_id');
			$this->wcOutput->script(
<<<EOD
jQuery(document).on('submit', '.woocommerce-cart-form', function(ev) {
	jQuery('input[name$="[qty]"]', ev.currentTarget).each(function(i, el) {
		var previousValue = el.defaultValue || 0;
		var currentValue = el.value || 0;
		var diff = currentValue - previousValue;

		if (diff > 0) {
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
				'value': (item.price * item.quantity),
				'items': [item]
			  }
			});
		}
	});
});
EOD
			);
		}
	}

	protected function getStringifiedEvent() {
		return json_encode(new Event(EventType::ADD_TO_CART));
	}
}
