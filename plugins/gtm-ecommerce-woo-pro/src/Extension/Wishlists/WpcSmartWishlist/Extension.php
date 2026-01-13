<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist;

use GtmEcommerceWooPro\Lib\Extension\AbstractExtension;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\EventStrategy\AddToCartStrategy;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\EventStrategy\RemoveFromWishlistStrategy;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\EventStrategy\AddToWishlistStrategy;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\EventStrategy\ViewItemListStrategy;

class Extension extends AbstractExtension {

	const SUPPORTED_PLUGIN_NAME = 'woo-smart-wishlist/wpc-smart-wishlist.php';

	const SUPPORTED_PLUGIN_VERSION = '4.8.7';

	public function getEventStrategies() {
		return [
			new AddToCartStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new AddToWishlistStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new RemoveFromWishlistStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new ViewItemListStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
		];
	}
}
