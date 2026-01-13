<?php

namespace GtmEcommerceWooPro\Lib\Extension\Brands;

use GtmEcommerceWooPro\Lib\Service\ExtensionAggregateInterface;
use GtmEcommerceWooPro\Lib\Extension\Brands\WooCommerceBrands\Extension as WooCommerceBrandsExtension;
use GtmEcommerceWooPro\Lib\Extension\Brands\YithBrands\Extension as YithBrandsExtension;
use GtmEcommerceWooPro\Lib\Extension\Brands\YithBrandsPremium\Extension as YithBrandsPremiumExtension;

class Extension implements ExtensionAggregateInterface {

	public static function getExtensions() {
		return [
			WooCommerceBrandsExtension::class,
			YithBrandsExtension::class,
			YithBrandsPremiumExtension::class,
		];
	}
}
