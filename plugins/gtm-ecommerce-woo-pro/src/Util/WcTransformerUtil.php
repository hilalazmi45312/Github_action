<?php

namespace GtmEcommerceWooPro\Lib\Util;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Item;
use GtmEcommerceWoo\Lib\Util\WcTransformerUtil as FreeWcTransformerUtil;
use GtmEcommerceWooPro\Lib\Type\EventType;
use WC_Order;
use WC_Product_Variation;

/**
 * Logic to handle embedding Gtm Snippet
 */
class WcTransformerUtil extends FreeWcTransformerUtil {

	const CUSTOMER_RETURNING_DAYS = 540;

	public function getRefundFromOrderId( $orderId, array $refunds ) {
		$order = wc_get_order( $orderId );

		return $this->getRefundFromOrder($order, $refunds);
	}

	public function getRefundFromOrder( WC_Order $order, array $refunds) {
		$event = new Event( EventType::REFUND );
		$event->setTransactionId( $order->get_order_number() );

		foreach ( $refunds as $refund ) {
			foreach ( $refund->get_items() as $key => $orderItem ) {
				$item           = $this->getItemFromOrderItem( $orderItem );
				$item->quantity = -$item->quantity;
				$event->addItem( $item );
			}
		}

		return $event;
	}

	public function getItemFromCartItem( $cartItem ) {
		$product = wc_get_product( $cartItem['data']->get_id() );
		// Review possible usage of getItemFromOrderItem
		$item = $this->getItemFromProduct( $product );
		$item->setQuantity( $cartItem['quantity'] );
		return $item;
	}

	/**
	 * Url: https://woocommerce.github.io/code-reference/classes/WC-Product-Variation.html
	 */
	public function getItemFromProductVariation( WC_Product_Variation $product ) {
		$parentProduct = wc_get_product($product->get_parent_id());

		$regularPrice = wc_get_price_excluding_tax($product, ['price' => $product->get_regular_price(null)]);
		$salePrice = wc_get_price_excluding_tax($product);
		$discount = round($regularPrice - $salePrice, 2);

		$item = new Item( $parentProduct->get_name() );
		$item->setItemId( $product->get_id() );
		$item->setPrice( $salePrice );
		$item->setItemVariant($product->get_name());
		$item->setExtraProperty('content_type',  $product->is_type(['variable', 'variable-subscription']) ? 'product_group' : 'product');

		if (0 < $discount) {
			$item->setDiscount($discount);
		}

		$productCats = get_the_terms( $product->get_id(), 'product_cat' );
		if ( is_array( $productCats ) ) {
			$categories = array_map(
				static function( $category ) {
					return $category->name; },
				$productCats
			);
			$item->setItemCategories( $categories );
		}
		/**
		 * Filter allowing to manipulate the event item objects
		 *
		 * @since 1.6.0
		 */
		return apply_filters('gtm_ecommerce_woo_item', $item, $product);
	}

	public function getPurchaseFromOrder( WC_Order $order): Event {
		$event = parent::getPurchaseFromOrder($order);

		$userId = $order->get_user_id();
		$customerType = 'new';

		if ($userId) {
			$customerOrders = wc_get_orders(
				[
					'customer_id' => $userId,
					'exclude' => [$order->get_id()],
					'status' => ['wc-processing', 'wc-completed'],
					'date_after' => gmdate('Y-m-d', strtotime('-' . self::CUSTOMER_RETURNING_DAYS . ' days')),
					'limit' => 1,
				]
			);

			if (false === empty($customerOrders)) {
				$customerType = 'returning';
			}
		}

		$event->setExtraEcomProperty('customer_type', $customerType);
		$event->setExtraEcomProperty('payment_type', $order->get_payment_method());

		$address = [
			'first_name'  => $order->get_billing_first_name(),
			'last_name'   => $order->get_billing_last_name(),
			'street'      => join( ' ', array( $order->get_billing_address_1(), $order->get_billing_address_2() ) ),
			'postal_code' => $order->get_billing_postcode(),
			'country'     => $order->get_billing_country(),
			'region'      => $order->get_billing_state(),
			'city'        => $order->get_billing_city(),
		];

		$userData = [
			'email' => $order->get_billing_email(),
			'phone' => $order->get_billing_phone(),
			'sha256_email_address' => hash('sha256', $order->get_billing_email()),
			'sha256_phone_number' => hash('sha256', $order->get_billing_phone()),
			'address' => $address,
		];

		$event->setExtraProperty('user_data', array_filter($userData, function($value) {
			return !is_null($value) && '' !== $value;
		}));

		return $event;
	}
}
