<?php

namespace GtmEcommerceWooPro\Lib\Extension\Brands\WooCommerceBrands;

use GtmEcommerceWooPro\Lib\Extension\Brands\AbstractBrandsExtension;

class Extension extends AbstractBrandsExtension {

	const SUPPORTED_PLUGIN_NAME = 'woocommerce-brands/woocommerce-brands.php';

	const SUPPORTED_PLUGIN_VERSION = '1.7.2';

	protected $brandTermName = 'product_brand';
}
