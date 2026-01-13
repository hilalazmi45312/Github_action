<?php

namespace GtmEcommerceWooPro\Lib\Extension\Multilingual\Wpml;

use GtmEcommerceWooPro\Lib\Extension\AbstractExtension;
use GtmEcommerceWooPro\Lib\Extension\Multilingual\Wpml\EventStrategy\ChangeLanguageStrategy;
use GtmEcommerceWooPro\Lib\Extension\Multilingual\Wpml\EventStrategy\LanguageStrategy;

class Extension extends AbstractExtension {

	const SUPPORTED_PLUGIN_NAME = 'sitepress-multilingual-cms/sitepress.php';

	const SUPPORTED_PLUGIN_VERSION = '4.6.11';

	public function getEventStrategies() {
		return [
			new LanguageStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new ChangeLanguageStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
		];
	}
}
