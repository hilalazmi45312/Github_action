<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy;

trait EventStrategyTrait {
	private function getCartItems() {
		global $woocommerce;

		$wcTransformer = $this->wcTransformer;
		return array_map(
			static function( $cartItem ) use ( $wcTransformer ) {
				return $wcTransformer->getItemFromCartItem( $cartItem );
			},
			$woocommerce->cart->get_cart()
		);
	}
}
