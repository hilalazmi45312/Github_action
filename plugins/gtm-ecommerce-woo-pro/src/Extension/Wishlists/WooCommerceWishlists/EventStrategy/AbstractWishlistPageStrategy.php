<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use WC_Wishlists_Wishlist;
use WC_Wishlists_Wishlist_Item_Collection;

abstract class AbstractWishlistPageStrategy extends AbstractEventStrategy {

	protected static $wishlistItemsLoaded = false;

	protected static $items = [];

	protected function loadWishlistItems() {
		if (true === self::$wishlistItemsLoaded) {
			return;
		}

		self::$items = isset($_GET['wlid']) ? $this->getWishlistItems((int) $_GET['wlid']) : [];
		$serializedItems = json_encode(self::$items);

		$this->wcOutput->script(
			<<<EOD
window.gtm_ecommerce_pro = {
    ...window.gtm_ecommerce_pro,
    extensions: {
        ...window.gtm_ecommerce_pro.extensions,
        wishlists: {
            wishlistItems: {$serializedItems},
            getItemByWishlistId: (wishlistItemId) => {
                if ('undefined' === typeof window.gtm_ecommerce_pro.extensions.wishlists.wishlistItems[wishlistItemId]) {
                    return null;
                }

                return {...window.gtm_ecommerce_pro.extensions.wishlists.wishlistItems[wishlistItemId]};
            },
            getAllItems: () => window.gtm_ecommerce_pro.extensions.wishlists.wishlistItems
        }
    }
};
EOD
		);

		self::$wishlistItemsLoaded = true;
	}

	protected function isWishlist() {
		return true === isset($_GET['wlid']);
	}

	private function getWishlistItems( $wishlistId) {
		$wishlist = new WC_Wishlists_Wishlist( $wishlistId );
		$wishlistItems = WC_Wishlists_Wishlist_Item_Collection::get_items( $wishlist->id );

		$index = 0;

		return array_map(function ( $i) use ( $wishlist, &$index) {
			$product = $i['data'];

			if (\WC_Product_Variation::class === get_class($product)) {
				$item = $this->wcTransformer->getItemFromProductVariation( $product );
			} else {
				$item = $this->wcTransformer->getItemFromProduct($product);
			}

			$item->setItemListId(sprintf('wishlist_%d', $wishlist->id));
			$item->setItemListName(get_the_title($wishlist->id));
			$item->setIndex($index);
			$item->setQuantity($i['quantity']);

			$index++;

			return $item;
		}, $wishlistItems);
	}
}
