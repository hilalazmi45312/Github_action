<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WooCommerceWishlists\EventStrategy;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class ViewItemListStrategy extends AbstractWishlistPageStrategy {
	protected $eventName = EventType::VIEW_ITEM_LIST;

	public function defineActions() {
		return [
			'woocommerce_wishlists_before_wrapper' => [[$this, 'pushEvent'], 11],
		];
	}

	public function pushEvent() {
		if (false === $this->isWishlist()) {
			return;
		}

		$this->loadWishlistItems();

		if (true === empty(self::$items)) {
			return;
		}

		$event = new Event( EventType::VIEW_ITEM_LIST );
		$event->setItems( self::$items );
		$this->wcOutput->dataLayerPush( $event );
	}
}
