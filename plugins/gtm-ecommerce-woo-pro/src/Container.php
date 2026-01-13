<?php

namespace GtmEcommerceWooPro\Lib;

use GtmEcommerceWoo\Lib\Container as FreeContainer;
use GtmEcommerceWoo\Lib\Service\EventInspectorService;
use GtmEcommerceWoo\Lib\Service\OrderDiagnosticsService;
use GtmEcommerceWoo\Lib\Service\OrderMonitorService;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use GtmEcommerceWooPro\Lib\Service\EventStrategiesService;
use GtmEcommerceWooPro\Lib\Service\ExtensionService;
use GtmEcommerceWooPro\Lib\Service\GtmSnippetService;
use GtmEcommerceWooPro\Lib\Service\PluginService;
use GtmEcommerceWooPro\Lib\Service\ServerSide\CookiesService;
use GtmEcommerceWooPro\Lib\Service\ServerSide\QueueService;
use GtmEcommerceWooPro\Lib\Service\SettingsService;
use GtmEcommerceWooPro\Lib\Service\ProductFeedService;
use GtmEcommerceWooPro\Lib\Service\ToolsService;
use GtmEcommerceWooPro\Lib\Util\MpClientUtil;
use GtmEcommerceWooPro\Lib\Util\WcOutputUtil;
use GtmEcommerceWooPro\Lib\Util\WcTransformerUtil;
use GtmEcommerceWooPro\Lib\Factory\UserDataFactory;

use GtmEcommerceWooPro\Lib\EventStrategy\Browser as BrowserEventStrategy;
use GtmEcommerceWooPro\Lib\EventStrategy\Server as ServerEventStrategy;

class Container extends FreeContainer {
	/**
	 * ExtensionService
	 *
	 * @var ExtensionService
	 */
	private $extensionService;

	/**
	 * MpClientUtil
	 *
	 * @var MpClientUtil
	 */
	private $mpClientUtil;

	/**
	 * WpSettingsUtil
	 *
	 * @var WpSettingsUtil
	 */
	private $wpSettingsUtil;

	private $queueService;

	private $cookiesService;

	private $toolsService;

	public function __construct( $pluginVersion ) {
		$snakeCaseNamespace = 'gtm_ecommerce_woo';
		$spineCaseNamespace = 'gtm-ecommerce-woo';
		$tagConciergeApiUrl = getenv('TAG_CONCIERGE_API_URL') ? getenv('TAG_CONCIERGE_API_URL') : 'https://api.tagconcierge.com';

		$this->wpSettingsUtil = new WpSettingsUtil( $snakeCaseNamespace, $spineCaseNamespace );
		$this->wcTransformerUtil = new WcTransformerUtil();
		$wcOutputUtil = new WcOutputUtil($pluginVersion);
		$this->mpClientUtil = new MpClientUtil( $snakeCaseNamespace, $spineCaseNamespace );

		$this->extensionService = new ExtensionService($this->wcTransformerUtil, $wcOutputUtil, $this->mpClientUtil);
		$this->extensionService->loadExtensions();

		$userDataFactory = new UserDataFactory();

		$baseEventStrategies = [
			new BrowserEventStrategy\ViewItemListStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\SelectItemStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\ViewItemStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\AddToCartStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\ViewCartStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\RemoveFromCartStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\BeginCheckoutStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\AddBillingInfoStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\AddPaymentInfoStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\AddShippingInfoStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\Purchase\ThankYouPageStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\AbandonCartStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\AbandonCheckoutStrategy( $this->wcTransformerUtil, $wcOutputUtil ),
			new BrowserEventStrategy\UserDataStrategy( $this->wcTransformerUtil, $wcOutputUtil, $userDataFactory ),
			new ServerEventStrategy\PurchaseStrategy( $this->wcTransformerUtil, $wcOutputUtil, $this->mpClientUtil ),
		];

		if ('1' !== $this->wpSettingsUtil->getOption(SettingsService::SETTING_NAME_EVENT_BROWSER_PURCHASE_ONLY_ON_THANK_YOU_PAGE)) {
			$baseEventStrategies[] = new BrowserEventStrategy\Purchase\FormPayStrategy($this->wcTransformerUtil, $wcOutputUtil);
			$baseEventStrategies[] = new BrowserEventStrategy\Purchase\BeforePaymentRedirectStrategy($this->wcTransformerUtil, $wcOutputUtil);
		}

		$eventStrategies = array_merge($baseEventStrategies, $this->extensionService->getEventStrategies());

		$events = array_reduce($eventStrategies, static function( $agg, $eventStrategy ) {
			if ('server' === $eventStrategy->getEventType()) {
				$agg['server'][] = $eventStrategy->getEventName();
			} else {
				$agg['browser'][] = $eventStrategy->getEventName();
			}
			return $agg;
		}, ['browser' => [], 'server' => []]);

		$this->eventStrategiesService = new EventStrategiesService( $this->wpSettingsUtil, $wcOutputUtil, $eventStrategies );
		$this->gtmSnippetService = new GtmSnippetService( $this->wpSettingsUtil );
		$this->orderMonitorService = new OrderMonitorService($this->wpSettingsUtil, $wcOutputUtil);
		$this->settingsService = new SettingsService( $this->wpSettingsUtil, $this->orderMonitorService, $events['browser'], [], $events['server'], $tagConciergeApiUrl, $pluginVersion);
		$this->pluginService = new PluginService($spineCaseNamespace, $this->wpSettingsUtil, $wcOutputUtil, $pluginVersion);
		$this->eventInspectorService = new EventInspectorService( $this->wpSettingsUtil, $wcOutputUtil );
		$this->queueService = new QueueService( $snakeCaseNamespace, $this->wpSettingsUtil, $this->mpClientUtil, $this->wcTransformerUtil);
		$this->cookiesService = new CookiesService( $this->mpClientUtil, $this->wpSettingsUtil );
		$this->productFeedService = new ProductFeedService( $snakeCaseNamespace, $this->wpSettingsUtil );
		$this->toolsService = new ToolsService( $this->wpSettingsUtil );

		add_action( 'init', function () {
			/**
			 * Hook that allows to get access to all objects within plugin's dependency injection container
			 *
			 * @since 1.6.0
			 */
			do_action('gtm_ecommerce_woo_container', $this);
		} );
	}


	public function getWpSettingsUtil() {
		return $this->wpSettingsUtil;
	}

	public function getMpClientUtil() {
		return $this->mpClientUtil;
	}

	public function getQueueService() {
		return $this->queueService;
	}

	public function getCookiesService() {
		return $this->cookiesService;
	}

	public function getToolsService() {
		return $this->toolsService;
	}
}
