<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists;

use GtmEcommerceWooPro\Lib\Service\ExtensionAggregateInterface;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\Extension as WooCommerceWishlistsExtension;
use GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\Extension as WpcSmartWishlistExtension;

class Extension implements ExtensionAggregateInterface {

	public static function getExtensions() {
		return [
			WooCommerceWishlistsExtension::class,
			WpcSmartWishlistExtension::class
		];
	}
}
