<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser\Purchase;

use GtmEcommerceWoo\Lib\EventStrategy\PurchaseStrategy as FreePurchaseStrategy;
use WC_Order;

class ThankYouPageStrategy extends FreePurchaseStrategy {
	use PurchaseStrategyTrait;
	/**
	 * Tracked order.
	 *
	 * @var WC_Order
	 */
	private $trackedOrder;

	public function defineActions() {
		return [
			'woocommerce_thankyou' => [$this, 'thankyou'],
			'wp_footer' => [$this, 'wpFooter'],
		];
	}

	public function thankyou( $orderId ) {
		$order = wc_get_order( $orderId );

		if (false === $order instanceof WC_Order) {
			return;
		}

		if (false === $this->shouldTrack($order)) {
			return;
		}

		$this->trackOrder($order);
	}

	public function wpFooter() {
		if (null === $this->trackedOrder) {
			return;
		}

		$this->trackedOrder->update_meta_data(self::ORDER_META_KEY_PURCHASE_EVENT_TRACKED, '1');
		$this->trackedOrder->save();
	}
}
