<?php


namespace GtmEcommerceWooPro\Lib\Extension\Multilingual\Polylang;

use GtmEcommerceWooPro\Lib\Extension\AbstractExtension;
use GtmEcommerceWooPro\Lib\Extension\Multilingual\Polylang\EventStrategy\ChangeLanguageStrategy;
use GtmEcommerceWooPro\Lib\Extension\Multilingual\Polylang\EventStrategy\LanguageStrategy;

class Extension extends AbstractExtension {

	public function getEventStrategies() {
		return [
			new LanguageStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
			new ChangeLanguageStrategy($this->wcTransformerUtil, $this->wcOutputUtil),
		];
	}

	public static function supports( $pluginName, $pluginVersion) {
		$supportedPlugins = [
			'polylang/polylang.php' => '3.6.3',
			'polylang-pro/polylang.php' => '3.6.3'
		];

		foreach ($supportedPlugins as $supportedPluginName => $supportedPluginVersion) {
			if ($supportedPluginName !== $pluginName) {
				continue;
			}

			if (0 > version_compare($supportedPluginVersion, $pluginVersion)) {
				continue;
			}

			return true;
		}

		return false;
	}
}
