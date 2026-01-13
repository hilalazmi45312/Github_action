<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWooPro\Lib\Type\EventType;
use WC_Order;
use WC_Order_Refund;
use function GtmEcommerceWooPro\Lib\EventStrategy\count;
use function GtmEcommerceWooPro\Lib\EventStrategy\esc_attr;

class RefundStrategy extends AbstractEventStrategy {

	const ORDER_META_KEY_REFUNDED_IDS = 'gtm_wcommerce_woo_refunded_ids';

	protected $eventName = EventType::REFUND;

	public function defineActions() {
		return [
			'woocommerce_admin_order_data_after_order_details' => [ $this, 'editFormAdvanced' ],
		];
	}

	// this logic may be moved to the actual refund hook now
	public function editFormAdvanced( WC_Order $order ) {
		// get ids of refunds that were already tracked
		$refundedIds = $order->get_meta(self::ORDER_META_KEY_REFUNDED_IDS);
		$refundedIds = is_array( $refundedIds ) ? $refundedIds : [];

		/**
		 * Refunds
		 *
		 * @var WC_Order_Refund[] $refunds
		 */
		$refunds = $order->get_refunds();

		$refundsToTrack = array_reduce(
			$refunds,
			static function( $agg, WC_Order_Refund $refund ) use ( $refundedIds ) {
				if ( false === in_array($refund->get_id(), $refundedIds, true)) {
					$agg[] = $refund;
				}
				return $agg;
			},
			[]
		);

		if ( count( $refundsToTrack ) === 0 ) {
			return;
		}
		$event = $this->wcTransformer->getRefundFromOrder( $order, $refundsToTrack );

		$stringifiedEvent = json_encode( $event );

		$newlyTrackedRefundIds = array_map(
			static function( WC_Order_Refund $refund ) {
				return $refund->get_id();
			},
			$refundsToTrack
		);

		$order->update_meta_data(self::ORDER_META_KEY_REFUNDED_IDS, array_merge($refundedIds, $newlyTrackedRefundIds));
		$order->save();

		echo '<iframe height="0" width="0" style="display:none;visibility:hidden" referrerpolicy="no-referrer" src="/?rest_route=/gtm-ecommerce-woo/v1/container/' . esc_attr( base64_encode( $stringifiedEvent ) ) . '"></iframe>';
	}
}
