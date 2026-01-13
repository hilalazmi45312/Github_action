<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy\Browser;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWooPro\Lib\Type\EventType;

/**
 * When a user sees a list of items/offerings
 */
class BeginCheckoutStrategy extends AbstractEventStrategy {
	protected $eventName = EventType::BEGIN_CHECKOUT;
	protected $tracked = false;

	public function defineActions() {
		return [
			'woocommerce_before_checkout_form' => [ $this, 'beforeCheckoutForm' ],
			'wp_footer' => [ $this, 'wpFooter' ]
		];
	}

	public function beforeCheckoutForm() {
		global $woocommerce;
		$wcTransformer = $this->wcTransformer;
		$items         = array_map(
			static function( $cartItem ) use ( $wcTransformer ) {
				return $wcTransformer->getItemFromCartItem( $cartItem );
			},
			$woocommerce->cart->get_cart()
		);
		$event         = new Event( EventType::BEGIN_CHECKOUT );
		$event->setItems( array_values( $items ) );
		$this->wcOutput->dataLayerPush( $event );
		$this->tracked = true;
	}

	public function wpFooter() {
		if (false === $this->tracked && is_checkout() && !is_order_received_page()) {
			$this->beforeCheckoutForm();
		}
	}
}
