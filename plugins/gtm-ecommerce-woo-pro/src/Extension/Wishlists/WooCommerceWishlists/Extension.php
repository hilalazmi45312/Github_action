<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists;

use GtmEcommerceWooPro\Lib\Extension\AbstractExtension;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy\AddToCartStrategy;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy\AddToWishlistStrategy;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy\RemoveFromWishlistStrategy;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy\ViewItemListStrategy;

class Extension extends AbstractExtension {

	const SUPPORTED_PLUGIN_NAME = 'woocommerce-wishlists/woocommerce-wishlists.php';

	const SUPPORTED_PLUGIN_VERSION = '2.3.2';

	public function getEventStrategies() {
		return [
			new AddToCartStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new AddToWishlistStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new RemoveFromWishlistStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new ViewItemListStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
		];
	}
}
