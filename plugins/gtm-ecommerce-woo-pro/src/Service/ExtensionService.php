<?php

namespace GtmEcommerceWooPro\Lib\Service;

use GtmEcommerceWooPro\Lib\Extension\AbstractExtension;
use GtmEcommerceWooPro\Lib\Extension\Themesquad as ThemesquadExtensions;
use GtmEcommerceWooPro\Lib\Util\MpClientUtil;
use GtmEcommerceWooPro\Lib\Util\WcOutputUtil;
use GtmEcommerceWooPro\Lib\Util\WcTransformerUtil;
use GtmEcommerceWooPro\Lib\Extension\Wishlists as WishlistsExtension;
use GtmEcommerceWooPro\Lib\Extension\Multilingual as MultilingualExtension;
use GtmEcommerceWooPro\Lib\Extension\Brands as BrandsExtension;
use TagConcierge\Extension as ExternalExtension;

class ExtensionService {

	private $availableExtensions = [
		ThemesquadExtensions\QuickView\QuickViewExtension::class,
		BrandsExtension\Extension::class,
		WishlistsExtension\Extension::class,
		MultilingualExtension\Extension::class,
	];

	private $externalExtensions = [
		ExternalExtension\Zaraz\Extension::class,
	];

	/**
	 * Array of AbstractExtension objects.
	 *
	 * @var AbstractExtension[]
	 */
	private $loadedExtensions = [];

	/**
	 * WcTransformerUtil
	 *
	 * @var WcTransformerUtil
	 */
	private $wcTransformerUtil;

	/**
	 * WcOutputUtil
	 *
	 * @var WcOutputUtil
	 */
	private $wcOutputUtil;

	/**
	 * MpClientUtil
	 *
	 * @var MpClientUtil
	 */
	private $mpClientUtil;

	public function __construct ( WcTransformerUtil $wcTransformerUtil, WcOutputUtil $wcOutputUtil, MpClientUtil $mpClientUtil) {
		$this->wcTransformerUtil = $wcTransformerUtil;
		$this->wcOutputUtil = $wcOutputUtil;
		$this->mpClientUtil = $mpClientUtil;
	}

	public function loadExtensions() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ($this->externalExtensions as $externalExtensionClass) {
			if (true === class_exists($externalExtensionClass)) {
				$this->availableExtensions[] = $externalExtensionClass;
			}
		}

		$allPlugins = get_plugins();
		$extensionClasses = [];

		foreach (get_option('active_plugins') as $activePluginName) {
			if (false === isset($allPlugins[$activePluginName])) {
				continue;
			}

			$plugin = $allPlugins[$activePluginName];

			/**
			 * AbstractExtension
			 *
			 * @var AbstractExtension|ExtensionAggregateInterface $availableExtension
			 */
			foreach ($this->availableExtensions as $availableExtension) {
				$interfaces = class_implements($availableExtension);

				$availableExtensions = true === isset($interfaces[ExtensionAggregateInterface::class]) ?
					$availableExtension::getExtensions()
					: [$availableExtension];

				foreach ($availableExtensions as $ext) {
					if (true === $ext::supports($activePluginName, $plugin['Version'])) {
						$extensionClasses[] = $ext;
					}
				}
			}
		}

		$extensionClasses = array_unique($extensionClasses);

		foreach ($extensionClasses as $extensionClass) {
			$extension = new $extensionClass($this->wcTransformerUtil, $this->wcOutputUtil, $this->mpClientUtil);
			$extension->init();

			$this->loadedExtensions[] = $extension;
		}
	}

	public function getEventStrategies() {
		$eventStrategies = [];

		foreach ($this->loadedExtensions as $loadedExtension) {
			$eventStrategies = array_merge($eventStrategies, $loadedExtension->getEventStrategies());
		}

		return $eventStrategies;
	}
}
