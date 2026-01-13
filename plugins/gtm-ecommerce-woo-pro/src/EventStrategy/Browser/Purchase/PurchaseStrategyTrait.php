<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser\Purchase;

use GtmEcommerceWoo\Lib\EventStrategy\PurchaseStrategy as FreePurchaseStrategy;
use GtmEcommerceWooPro\Lib\Util\WcOutputUtil;
use GtmEcommerceWooPro\Lib\Util\WcTransformerUtil;
use WC_Order;

trait PurchaseStrategyTrait {

	/**
	 * WcTransformerUtil
	 *
	 * @var WcTransformerUtil
	 */
	protected $wcTransformer;

	/**
	 * WcOutputUtil
	 *
	 * @var WcOutputUtil
	 */
	protected $wcOutput;

	protected function shouldTrack( WC_Order $order) {
		return $this->isTrackingForced() || '1' !== $order->get_meta(FreePurchaseStrategy::ORDER_META_KEY_PURCHASE_EVENT_TRACKED);
	}

	protected function trackOrder( WC_Order $order ) {
		$event = $this->wcTransformer->getPurchaseFromOrder($order);

		$this->wcOutput->dataLayerPush($event);

		$this->trackedOrder = $order;
	}

	protected function isTrackingForced() {
		return ( isset($_GET['gtm-ecommerce-woo-force-purchase'] ) && '1' === $_GET['gtm-ecommerce-woo-force-purchase'] );
	}
}
