<?php

namespace GtmEcommerceWooPro\Lib\Extension\Brands\YithBrands;

use GtmEcommerceWooPro\Lib\Extension\Brands\AbstractBrandsExtension;

class Extension extends AbstractBrandsExtension {

	const SUPPORTED_PLUGIN_NAME = 'yith-woocommerce-brands-add-on/init.php';

	const SUPPORTED_PLUGIN_VERSION = '2.24.0';

	protected $brandTermName = 'yith_product_brand';
}
