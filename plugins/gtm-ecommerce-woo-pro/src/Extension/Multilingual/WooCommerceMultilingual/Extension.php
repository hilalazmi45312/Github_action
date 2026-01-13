<?php

namespace GtmEcommerceWooPro\Lib\Extension\Multilingual\WooCommerceMultilingual;

use GtmEcommerceWooPro\Lib\Extension\AbstractExtension;
use GtmEcommerceWooPro\Lib\Extension\Multilingual\WooCommerceMultilingual\EventStrategy\ChangeCurrencyStrategy;

class Extension extends AbstractExtension {

	const SUPPORTED_PLUGIN_NAME = 'woocommerce-multilingual/wpml-woocommerce.php';

	const SUPPORTED_PLUGIN_VERSION = '5.3.6';

	public function getEventStrategies() {
		return [
			new ChangeCurrencyStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
		];
	}
}
