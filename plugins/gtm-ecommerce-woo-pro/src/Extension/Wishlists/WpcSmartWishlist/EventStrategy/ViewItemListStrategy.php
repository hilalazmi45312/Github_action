<?php

namespace GtmEcommerceWooPro\Lib\Extension\Wishlists\WpcSmartWishlist\EventStrategy;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class ViewItemListStrategy extends AbstractWishlistPageStrategy {
	protected $eventName = EventType::VIEW_ITEM_LIST;

	public function defineActions() {
		return [
			'woosw_wishlist_items_after' => [[$this, 'handleList'], 11, 2],
			'wp_footer' => [[$this, 'addScript'], 11],
		];
	}

	public function handleList( $key, $products) {
		$items = $this->loadWishlistItems(array_keys($products), $key);

		$event = new Event( EventType::VIEW_ITEM_LIST );
		$event->setItems( $items );

		if (true === is_ajax()) {
			$this->handleModal($event);
			return;
		}

		$this->handleWishlistPage($event);
	}

	private function handleModal( $event) {
		echo sprintf(
			'<div id="gtm-ecommerce-woo__wpc-smart-wishlist__data-carrier__view-item-list" data-event="%s" style="display: none;"></div>',
			esc_html(json_encode($event))
		);
	}

	private function handleWishlistPage( $event) {
		$this->wcOutput->dataLayerPush( $event );
	}

	public function addScript() {
		$this->wcOutput->script(
			<<<EOD
jQuery(document.body).on('woosw_wishlist_show', function(e) {
	  const hookElement = jQuery('#gtm-ecommerce-woo__wpc-smart-wishlist__data-carrier__view-item-list');

	  if (0 === hookElement.length) {
		return;
	  }

	  const event = hookElement.data('event');

	  if (undefined === event) {
		return;
	  }

	  dataLayer.push(event);
});
EOD
		);
	}
}
