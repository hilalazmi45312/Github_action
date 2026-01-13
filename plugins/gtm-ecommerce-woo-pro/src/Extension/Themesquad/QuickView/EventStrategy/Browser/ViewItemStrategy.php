<?php

namespace GtmEcommerceWooPro\Lib\Extension\Themesquad\QuickView\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

class ViewItemStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::VIEW_ITEM;

	public function defineActions() {
		return [
			'wc_quick_view_after_single_product' => [[$this, 'quickViewModal'], 11],
		];
	}

	public function initialize() {
		$this->wcOutput->script(<<<EOT
	jQuery('body').on('quick-view-displayed', function() {
	  const hookElement = jQuery('#gtm-ecommerce-woo__wc-qv__data-carrier__view-item');

	  if (0 === hookElement.length) {
		return;
	  }

	  const event = hookElement.data('event');

	  if (undefined === event) {
		return;
	  }

	  dataLayer.push(event);
	});
EOT
);
	}

	public function quickViewModal() {
		global $product;

		if (is_null($product)) {
			return;
		}

		$item = $this->wcTransformer->getItemFromProduct( $product );
		$event = new Event( EventType::VIEW_ITEM );
		$event->addItem( $item );

		echo sprintf(
			'<div id="gtm-ecommerce-woo__wc-qv__data-carrier__view-item" data-event="%s" style="display: none;"></div>',
			esc_html(json_encode($event))
		);
	}
}
