<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\EventStrategy;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;

abstract class AbstractWishlistPageStrategy extends AbstractEventStrategy {

	protected static $wishlistItemsLoaded = false;

	protected static $items = [];

	protected function loadWishlistItems( array $productIds, $wishlistKey) {
		if (true === self::$wishlistItemsLoaded) {
			return self::$items;
		}

		$index = 0;
		$items = [];

		foreach ($productIds as $productId) {
			$product = wc_get_product($productId);
			$item = $this->wcTransformer->getItemFromProduct( $product );
			$item->setIndex( $index );
			$item->setItemListName( 'wishlist' );
			$item->setItemListId( sprintf('wishlist_%s', $wishlistKey) );
			$item->setQuantity(1);
			$index++;
			$items[$productId] = $item;
		}

		$this->wcOutput->addItems($items, 'product_id');

		self::$items = $items;
		self::$wishlistItemsLoaded = true;

		return $items;
	}
}
