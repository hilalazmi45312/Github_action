<?php

namespace GtmEcommerceWooPro\Lib\Extension\Multilingual;

use GtmEcommerceWooPro\Lib\Service\ExtensionAggregateInterface;
use GtmEcommerceWooPro\Lib\Extension\Multilingual\Polylang\Extension as PolylangExtension;
use GtmEcommerceWooPro\Lib\Extension\Multilingual\WooCommerceMultilingual\Extension as WooCommerceMultilingualExtension;
use GtmEcommerceWooPro\Lib\Extension\Multilingual\Wpml\Extension as WpmlExtension;

class Extension implements ExtensionAggregateInterface {

	public static function getExtensions() {
		return [
			PolylangExtension::class,
			WooCommerceMultilingualExtension::class,
			WpmlExtension::class,
		];
	}
}
