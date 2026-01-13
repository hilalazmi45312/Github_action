<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

/**
 * When a user sees a list of items/offerings
 * https://developers.google.com/tag-manager/ecommerce-ga4#measure_productitem_list_viewsimpressions
 * https://developers.google.com/gtagjs/reference/ga4-events#view_item_list
 */
class ViewItemListStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::VIEW_ITEM_LIST;
	protected $itemListName = null;
	protected $itemListId = null;
	protected $trackedProductIds = [];
	protected $firstProductId = null;
	protected $index = 0;
	protected $items = [];
	protected $firstProduct;

	public $settings = [
		'single_product_list' => [
			'label' => 'Single Product',
			'description' => 'Enable to trigger `view_item_list` for related products on Single Product Page',
			'value' => true,
		]
	];

	public function defineActions() {
		return [
			'the_post' => [[$this, 'shopLoop'], 11],
			'wp_footer'  => [ $this, 'afterShopLoop' ],
		];
	}

	public function computeList() {

		if (null !== $this->firstProduct) {
			return;
		}

		$this->items = [];
		$this->index = 0;

		// if we are on a product page then we skip first product
		if (is_product()) {
			$this->firstProduct = true;
		} else {
			$this->firstProduct = false;
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

	public function shopLoop() {
		global $product;
		// sometimes it may be called before header, we want to ignore it
		if (is_a($product, 'WC_Product')) {
			$this->computeList();
			if (true !== $this->firstProduct) {
				// ensure we track every product once
				if (in_array($product->get_id(), $this->trackedProductIds)) {
					return false;
				}
				// ignore products discovered as first product
				if ($this->firstProductId == $product->get_id()) {
					return false;
				}
				$item = $this->wcTransformer->getItemFromProduct( $product );
				$item->setIndex( $this->index );
				$item->setItemListName( $this->itemListName );
				$item->setItemListId( $this->itemListId );
				$item->setQuantity( 1 );

				$this->items[] = $item;
				$this->trackedProductIds[] = $product->get_id();
				$this->index++;
			} else {
				$this->firstProductId = $product->get_id();
			}
			$this->firstProduct = false;
		}

	}

	public function afterShopLoop() {
		// if we want to skip the view_item_list on single product page
		if (is_product() && false === $this->settings['single_product_list']['value']) {
			return true;
		}

		if (is_array($this->items) && count($this->items) > 0) {
			$event = new Event( EventType::VIEW_ITEM_LIST );
			$event->setItems( $this->items );
			$this->wcOutput->dataLayerPush( $event );
		}
	}
	// hook to woocommerce_shop_loop to build the list
	// woocommerce_after_shop_loop to drop it in the page

}
