<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

/**
 * When user sees item details
 * https://developers.google.com/tag-manager/ecommerce-ga4#measure_viewsimpressions_of_productitem_details
 * https://developers.google.com/gtagjs/reference/ga4-events#view_item
 */
class ViewItemStrategy extends AbstractEventStrategy {

	protected $eventName = EventType::VIEW_ITEM;

	protected $firstPost = false;

	public function defineActions() {
		return [
			'the_post' => [[$this, 'beforeSingleProduct'], 11 ],
		];
	}

	public function beforeSingleProduct() {
		global $product;
		// if product is null then this must be other WP post
		if (is_null($product)) {
			return false;
		}
		if (false === $this->firstPost && is_product() && is_single()) {
			$this->firstPost = true;
			$item  = $this->wcTransformer->getItemFromProduct( $product );
			$item->setQuantity( 1 );
			$event = new Event( EventType::VIEW_ITEM );
			$event->addItem( $item );
			$this->wcOutput->dataLayerPush( $event );
		}
	}

}
