<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

/**
 * When a user clicks on item
 */
class SelectItemStrategy extends AbstractEventStrategy {
	protected $itemsByProductId = [];
	protected $eventName = EventType::SELECT_ITEM;
	protected $itemListName;
	protected $itemListId;

	public function defineActions() {
		return [
			'the_post'  => [[$this, 'beforeShopLoopItemTitle'], 11 ],
			'wp_footer' => [ $this, 'afterShopLoop' ],
		];
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

	public function beforeShopLoopItemTitle() {
		global $product;
		if (is_a($product, 'WC_Product')) {
			$this->computeList();
			$item = $this->wcTransformer->getItemFromProduct( $product );
			$item->setItemListName( $this->itemListName );
			$item->setItemListId( $this->itemListId );
			$this->itemsByProductId[ $product->get_id() ] = $item;
			// echo '<span style="display: none" class="gtm_ecommerce_woo_product_id">' . esc_html( $product->get_id() ) . '</span>';
		}
	}

	public function afterShopLoop() {
		if (count($this->itemsByProductId) > 0) {
			$this->onLoopProductLinkClick( $this->itemsByProductId );
		}
	}


	public function onLoopProductLinkClick( $items ) {
		$this->wcOutput->addItems($items, 'product_id');
		$this->wcOutput->script(
			<<<EOD
jQuery('.woocommerce-loop-product__link, .product.type-product .add_to_cart_button.product_type_simple, .product.type-product .add_to_cart_button.product_type_variable, .product.type-product .product_type_grouped, .quick-view-button, .wp-block-post.product a').click(function(ev) {
	var matched_product_id = jQuery(ev.currentTarget).parents(".product.type-product").attr('class').match(/post\-[0-9]*/);
    var product_id = ((matched_product_id && matched_product_id[0]) || "").replace('post-', '');
   	if (!product_id) {
   		return;
   	}
    var item = gtm_ecommerce_pro.getItemByProductId(product_id);

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
EOD
		);
	}

	protected function getStringifiedEvent() {
		return json_encode(new Event(EventType::SELECT_ITEM));
	}
}
