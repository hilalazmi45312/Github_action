<?php
/**
 * Get Fee based on discount.
 *
 * @package Extra Fees for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'EFW_Fee_Based_On_Discount' ) ) {

	/**
	 * Class.
	 */
	class EFW_Fee_Based_On_Discount {

		/**
		 * Order Object.
		 *
		 * @var object
		 * */
		protected $order;

		/**
		 * Cart Object.
		 *
		 * @var object
		 * */
		protected $cart_object;

		/**
		 * Type.
		 *
		 * @var string
		 * */
		protected $type;

		/**
		 * Fee Type.
		 *
		 * @var string
		 * */
		protected $fee_type;

		/**
		 * Class Initialization.
		 *
		 * @param string $order Cart/Order Object.
		 */
		public function __construct( $type, $order, $cart_object, $fee_type ) {
			$this->type = $type;
			$this->fee_type = $fee_type;
			if ( is_object($order) ) {
				$this->order = $order;
			}

			if ( is_object($cart_object) ) {
				$this->cart_object = $cart_object;
			}
		}

		/**
		 * Get Modified Fee
		 *
		 * @param string $type Order/Cart Page.
		 * @param string $order Order Object.
		 * @param string $fee_type Fixed/Percentage Fee Type.
		 * @param string $cart_object Cart Object.
		 *
		 * @return bool
		 */
		public static function get_fee( $type, $order, $fee_type, $cart_object = false ) {
			$construct = new self( $type, $order, $cart_object, $fee_type );

			$fee = ( in_array('1', $fee_type) || 'yes' != get_option('efw_productfee_discount_based_calculation') ) ? $construct->original_fee_for_product() : $construct->modified_fee_for_product();

			return $fee;
		}

		/**
		 * Get Items
		 * 
		 * */
		public function get_items() {
			$items = array();
			switch ($this->type) {
				case 'order':
					$items = $this->order->get_items();
					break;

				case 'cart':
					if (is_object($this->cart_object)) {
						$items = $this->cart_object->cart_contents;
					} else if (is_object(WC()->cart)) {
						$items = WC()->cart->get_cart();
					}

					break;
			}

			return $items;
		}

		/**
		 * Get Applied Coupons
		 * 
		 * */
		public function get_applied_coupons() {
			$items = array();
			switch ($this->type) {
				case 'order':
					$items = $this->order->get_coupon_codes();
					break;

				case 'cart':
					if (is_object(WC()->cart)) {
						$items = WC()->cart->applied_coupons;
					}
					break;
			}

			return $items;
		}

		
		/**
		 * Modified Fee for Products in manual order.
		 * 
		 * @param WP_Post $order Order Object.
		 * */
		public function modified_fee_for_product() {
			$fee_value          = array();
			$original_fee_value = $this->original_fee_for_product();
			if ( ! efw_check_is_array( $original_fee_value ) ) {
				return $fee_value;
			}

			foreach ( $original_fee_value as $product_id => $fee ) {
				$modified_fee_value = $this->coupon_fee_conversion_for_product( $product_id, $fee );
				if ( ! empty( $modified_fee_value ) ) {
					$fee_value[ $product_id ] = $modified_fee_value;
				}
			}

			return $fee_value;
		}

		/**
		 * Original Points for Products
		 * */
		public function original_fee_for_product() {
			$fee_value        = array();

			if ( efw_check_is_array( $this->get_items() ) ) {
				foreach ( $this->get_items() as $value ) {
					$product_id = ! empty( $value['variation_id'] ) ? $value['variation_id'] : $value['product_id'];
					$product      = wc_get_product( $product_id );

					if ( ! is_object( $product ) ) {
						continue;
					}

					if (isset($value['booking']['_cost'])) {
						if ( 'yes' == get_option('efw_productfee_qty_restriction_enabled')) {
							$price = (float) $product->get_price();
						} else {
							$price = $value['booking']['_cost'];
						}
					} else {
						$price = isset( $value['nyp'] ) ? $value['nyp'] : (float) $product->get_price() + efw_get_wc_signup_fee( $product );
					}
					
					if (isset( $value['qty'] )) {
						$qty = $value['qty'];
					} elseif (isset( $value['quantity'] )) {
						$qty = $value['quantity'];
					} else {
						$qty = 1;
					}

					$product_fee = EFW_Fees_Handler::product_fee( $product_id, $price, get_current_user_id(), 'fee' );
					$product_fee = EFW_Fees_Handler::rule_fees( $product_id, $product_fee, $price, $qty );
					
					if ( empty( $product_fee ) ) {
						continue;
					}

					$qty = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $qty;
					$product_fee = $product_fee * $qty;

					if ( isset( $fee_value[ $product_id ] ) ) {
						$fee_value[ $product_id ] = $product_fee + $fee_value[ $product_id ];
					} else {
						$fee_value[ $product_id ] = $product_fee;
					}
				}
			}
			
			return $fee_value;
		}

		public function coupon_fee_conversion_for_product( $product_id, $fee ) {

			if ( empty( $fee ) ) {
				return $fee;
			}

			$applied_coupons = $this->get_applied_coupons();
			if ( ! efw_check_is_array( $applied_coupons ) ) {
				return $fee;
			}

			$discount_total = $this->get_coupon_discount_total();
			if ( empty( $discount_total ) ) {
				return $fee;
			}

			$coupon_amounts   = $this->get_product_price_for_individual_product( $product_id, $fee, $discount_total );
			if ( ! efw_check_is_array( $coupon_amounts ) ) {
				return $fee;
			}

			$conversion_rate  = array();
			$converted_fee = 0;

			$product_price = $this->get_product_price_in_cart();

			foreach ( $applied_coupons as $coupon_code ) {
				$coupon_object = new WC_Coupon( $coupon_code );
				$product_list  = $coupon_object->get_product_ids();
				$coupon_amount = $coupon_amounts[ $coupon_code ][ $product_id ];
				$line_total    = $this->get_product_price_for_included_products( $product_list );

				if ( empty( $product_list ) && $product_price ) {
					$converted_fee = $discount_total / $product_price;
				} elseif ( $line_total ) {
					$converted_fee = $coupon_amount / $line_total;
				}

				$converted_amount = $converted_fee * $fee;
				if ( $fee > $converted_amount ) {
					$conversion_rate[] = $fee - $converted_amount;
				}
			}

			return end( $conversion_rate );
		}

		/**
		 * Get Product Price for individual products.
		 */
		public function get_coupon_discount_total() {
			$discounted_total = WC()->cart->coupon_discount_amounts;
			return efw_check_is_array( $discounted_total ) ? array_sum( array_values( $discounted_total ) ) : 0;
		}

		/**
		 * Get Product Price for individual products.
		 *
		 * @param array $product_id Product ID.
		 * @param float $fee Fee.
		 * @param float $discount_total Discount Total.
		 */
		public function get_product_price_for_individual_product( $product_id, $fee, $discount_total ) {
			$coupon_amount = array();

			foreach ( $this->get_applied_coupons() as $coupon_code ) {
				$coupon_obj   = new WC_Coupon( $coupon_code );
				$product_list = $coupon_obj->get_product_ids();
				if ( ! empty( $product_list ) ) {
					if ( in_array( $product_id, $product_list ) ) {
						$coupon_amount[ $coupon_code ][ $product_id ] = $discount_total;
					}
				} else {
					$coupon_amount[ $coupon_code ][ $product_id ] = $discount_total;
				}
			}

			return $coupon_amount;
		}

		/**
		 * Get Product Price for included products.
		 *
		 * @param array $product_list Product List.
		 */
		public function get_product_price_for_included_products( $product_list ) {
			$line_total = array();

			foreach ( $this->get_items() as $item ) {
				$product_id = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
				if ( in_array( $product_id, $product_list ) ) {
					$line_total[] = $item['line_subtotal'];
				}
			}

			return array_sum( $line_total );
		}

		/**
		 * Get Product Price in Cart.
		 */
		public function get_product_price_in_cart() {
			$price = array();
			foreach ( $this->get_items() as $items ) {
				
				$product_id = ! empty( $items['variation_id'] ) ? $items['variation_id'] : $items['product_id'];
				$product    = wc_get_product( $product_id );

				if (isset($items['booking']['_cost'])) {
					$prices = $items['booking']['_cost'];
				} else {
					$prices = isset( $items['nyp'] ) ? $items['nyp'] : (float) $product->get_price() + efw_get_wc_signup_fee( $product );
				}
				
				$product_fee = EFW_Fees_Handler::product_fee($product_id, $prices, get_current_user_id(), 'fee');

				if ( empty( $product_fee ) ) {
					continue;
				}

				$price[] = $items['line_subtotal'];
			}

			return array_sum( $price );
		}
	}
}
