<?php

namespace GtmEcommerceWooPro\Lib\Service;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Middleware\CartValueMiddleware;
use GtmEcommerceWooPro\Lib\Middleware\CouponMiddleware;
use GtmEcommerceWoo\Lib\Service\EventStrategiesService as FreeEventStrategiesService;
use GtmEcommerceWooPro\Lib\Middleware\CurrencyMiddleware;
use GtmEcommerceWooPro\Lib\Middleware\SplitItemListMiddleware;
use GtmEcommerceWooPro\Lib\Middleware\UserIdMiddleware;

/**
 * General Logic of the plugin
 */
class EventStrategiesService extends FreeEventStrategiesService {
	public function getEventStrategy( $eventStrategyName ) {
		foreach ($this->eventStrategies as $eventStrategy) {
			if ($eventStrategyName === $eventStrategy->getEventName()) {
				return $eventStrategy;
			}
		}
		return null;
	}

	public function initialize() {
		parent::initialize();

		$eventMiddlewares = [
			new CartValueMiddleware(),
			new CouponMiddleware(),
			new CurrencyMiddleware(),
			new SplitItemListMiddleware($this->wcOutputUtil), // must be the called as last (splits events)
		];

		add_filter( 'gtm_ecommerce_woo_event_middleware' , static function ( Event $event) use ( $eventMiddlewares) {
			foreach ($eventMiddlewares as $eventMiddleware) {
				$event = $eventMiddleware($event);
			}

			return $event;
		});
	}
}
