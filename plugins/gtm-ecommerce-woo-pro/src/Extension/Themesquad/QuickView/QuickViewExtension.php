<?php

namespace GtmEcommerceWooPro\Lib\Extension\Themesquad\QuickView;

use GtmEcommerceWooPro\Lib\Extension\AbstractExtension;
use GtmEcommerceWooPro\Lib\Extension\Themesquad\QuickView\EventStrategy\Browser\AddToCartStrategy;
use GtmEcommerceWooPro\Lib\Extension\Themesquad\QuickView\EventStrategy\Browser\ViewItemStrategy;

class QuickViewExtension extends AbstractExtension {

	const SUPPORTED_PLUGIN_NAME = 'woocommerce-quick-view/woocommerce-quick-view.php';

	const SUPPORTED_PLUGIN_VERSION = '1.8.1';

	public function getEventStrategies() {
		return [
			new AddToCartStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new ViewItemStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
		];
	}
}
