<?php
/**
 * Main class for Cart Notices
 *
 * @author      StoreApps
 * @since       9.60.0
 * @version     1.0.0
 * @package     WooCommerce Smart Coupons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Cart_Notices' ) ) {

	/**
	 *  Cart Notices Class.
	 *
	 * @return object of WC_SC_Cart_Notices having all functionality of Cart Notices
	 */
	class WC_SC_Cart_Notices {

		/**
		 * Variable to hold instance of Cart Notices
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Session key for auto apply coupon
		 *
		 * @var $session_key_auto_apply_coupons
		 */
		public $session_key_auto_apply_coupons = 'wc_sc_auto_apply_coupons';

		/**
		 * Get single instance of Cart Notices.
		 *
		 * @return WC_SC_Cart_Notices Singleton object of WC_SC_Cart_Notices
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0.0
		 */
		private function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-smart-coupons' ), '1.0.0' );
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			if ( 'yes' !== get_option( 'smart_coupons_enable_cart_threshold_promotion', 'no' ) ) {
				return;
			}
			// add the notices to the top of the cart/checkout pages.
			add_action( 'woocommerce_before_cart_contents', array( $this, 'cart_notice' ) );
			add_action( 'woocommerce_before_checkout_form', array( $this, 'cart_notice' ) );
		}

		/**
		 * Function to handle WC compatibility related function call from appropriate class
		 *
		 * @param string $function_name Function to call.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return mixed Result of function call.
		 */
		public function __call( $function_name, $arguments = array() ) {

			global $woocommerce_smart_coupon;

			if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
			} else {
				return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
			}
		}

		/**
		 * Cart notice
		 *
		 * @since 1.0.0
		 */
		public function cart_notice() {
			$is_cart = ( is_callable( 'WC' ) && isset( WC()->cart ) && ! WC()->cart->is_empty() ) ? true : false;
			if ( ! $is_cart ) {
				return false;
			}

			$message          = '';
			$tax_display_cart = get_option( 'woocommerce_tax_display_cart' );
			if ( 'incl' !== $tax_display_cart ) {
				$cart_contents_total = ( isset( WC()->cart->cart_contents_total ) ) ? WC()->cart->cart_contents_total : 0;
			} else {
				$cart_contents_total = ( isset( WC()->cart->cart_contents_total ) && isset( WC()->cart->tax_total ) ) ? WC()->cart->cart_contents_total + WC()->cart->tax_total : 0;
			}

			$filtered_ids = $this->get_auto_apply_threshold_coupons();
			if ( ! empty( $filtered_ids ) && is_array( $filtered_ids ) ) {
				foreach ( $filtered_ids as $id ) {
					$coupon = new WC_Coupon( $id );
					if ( ! $coupon instanceof WC_Coupon || ! $this->is_callable( $coupon, 'get_id' ) ) {
						continue;
					}
					$threshold_terms_message = $this->get_cart_threshold_terms( $coupon );
					$threshold_terms_message = apply_filters( 'wc_sc_extend_cart_threshold_terms', $threshold_terms_message, $coupon, WC()->cart );

					$threshold_offers_message = $this->get_cart_threshold_offers( $coupon );
					$threshold_offers_message = apply_filters( 'wc_sc_extend_cart_threshold_offers', $threshold_offers_message, $coupon, WC()->cart );

					/**
					 * Merge messages
					 */
					if ( ! empty( $threshold_terms_message ) && is_array( $threshold_terms_message ) && ! empty( $threshold_offers_message ) && is_array( $threshold_offers_message ) ) {
						$separator = apply_filters( 'wc_sc_cart_threshold_separator', __( 'and', 'woocommerce-smart-coupons' ) );
						// translators: 1: Threshold terms message. 2: Threshold offers message.
						$message = sprintf( __( '%1$s %2$s!', 'woocommerce-smart-coupons' ), implode( $separator, $threshold_terms_message ), implode( $separator, $threshold_offers_message ) );
						break;
					}
				}
			} else {

				if ( false === $this->is_show_free_shipping() ) {
					return false;
				}
				$cart_free_shipping_minimum = $this->get_cart_free_shipping_minimum();
				if ( empty( $cart_free_shipping_minimum ) || $cart_free_shipping_minimum < 1 || is_numeric( $cart_free_shipping_minimum ) && $cart_contents_total >= $cart_free_shipping_minimum ) {
					return false;
				}
				$min_amount_required = wc_price( $cart_free_shipping_minimum - $cart_contents_total );
				/* translators: minimum ammount for shipping */
				$message = sprintf( __( 'Add %s to your cart in order to receive free shipping!', 'woocommerce-smart-coupons' ), '<strong>' . $min_amount_required . '</strong>' );

			}
			$message = apply_filters( 'wc_sc_cart_threshold_message', $message );
			if ( empty( $message ) ) {
				return;
			}
			$this->dispay_promotional_message( $message );
		}

		/**
		 * Helper method to display promotional message.
		 *
		 * @param string $message the promotional text message.
		 */
		public function dispay_promotional_message( $message = '' ) {
			$message       = apply_filters( 'sc_cn_message', $message );
			$shop_now_text = apply_filters( 'sc_cn_cta_text', __( 'Shop Now', 'woocommerce-smart-coupons' ) );
			$page_id       = apply_filters( 'sc_cn_cta_page_id', wc_get_page_id( 'shop' ) );
			$shop_page_url = ( ! empty( $page_id ) ) ? get_permalink( $page_id ) : home_url();
			$message_html  = "<div class='woocommerce-info'>" . $message . "<a class='button' href=" . $shop_page_url . '>' . $shop_now_text . '</a></div>';
			$message_html  = apply_filters( 'wc_sc_cart_threshold_message_html', $message_html );
			echo wp_kses_post( $message_html );
		}

		/**
		 * List all cart threshold terms message of a single coupon.
		 *
		 * @param WC_Coupon $coupon woocommerce coupon object.
		 * @return array $messages get list of terms message.
		 */
		public function get_cart_threshold_terms( $coupon = null ) {
			$messages                      = array();
			$min_amount                    = (float) $coupon->get_minimum_amount();
			$product_ids                   = (array) $coupon->get_product_ids();
			$product_quantity_restrictions = $coupon->get_meta( 'wc_sc_product_quantity_restrictions' );
			$tax_display_cart              = get_option( 'woocommerce_tax_display_cart' );
			$cart_subtotal                 = ( 'incl' !== $tax_display_cart ) ? WC()->cart->get_subtotal() : WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();
			// Check minimum spend difference.
			if ( $min_amount > 0 && $cart_subtotal < $min_amount ) {
				$remaining = wc_price( $min_amount - $cart_subtotal );
				// translators: 1: Remaining amount.
				$messages[] = sprintf( __( 'Spend %s more', 'woocommerce-smart-coupons' ), '<strong>' . $remaining . '</strong>' );
			}
			// Product Quantity restriction message.
			if ( ! empty( $product_quantity_restrictions ) && is_array( $product_quantity_restrictions ) && ! empty( $product_quantity_restrictions['values']['product'] ) ) {
				$type      = ! empty( $product_quantity_restrictions['type'] ) ? $product_quantity_restrictions['type'] : '';
				$values    = ! empty( $product_quantity_restrictions['values'] ) ? $product_quantity_restrictions['values'] : '';
				$condition = ! empty( $product_quantity_restrictions['condition'] ) ? $product_quantity_restrictions['condition'] : 'any';

				if ( 'product' === $type && ! empty( $values['product'] ) ) {

					foreach ( $values['product'] as $pid => $qty_data ) {
						$product = wc_get_product( $pid );
						if ( $product && ! empty( $qty_data['min'] ) ) {
							$required_qty = $qty_data['min'];
							foreach ( WC()->cart->get_cart() as $cart_item ) {
								if ( in_array( (int) $pid, array( (int) $cart_item['product_id'], (int) $cart_item['variation_id'] ), true ) ) {
									$required_qty -= $cart_item['quantity'];
									if ( $required_qty > 0 ) {
										$messages[] = sprintf(
											// translators: 1: Threshold required quantity. 2: Threshold required product name.
											__( 'Add %1$s more %2$s to your cart', 'woocommerce-smart-coupons' ),
											'<strong>' . intval( $required_qty ) . '</strong>',
											'<strong>' . $product->get_name() . '</strong>'
										);
									}
								}
							}
						}
					}
				}
			}
			return $messages;
		}

		/**
		 * List all cart threshold offers message of a single coupon.
		 *
		 * @param WC_Coupon $coupon woocommerce coupon object.
		 * @return array $messages get list of offer promotion message.
		 */
		public function get_cart_threshold_offers( $coupon = null ) {
			$messages       = array();
			$discount_type  = $coupon->get_discount_type();
			$discount_value = $coupon->get_amount();
			if ( $discount_value > 0 ) {

				$discount_text = ( 'percent' === $discount_type )
					// translators: %s: Discount value as a percentage.
					? sprintf( __( 'to get %s%% off ', 'woocommerce-smart-coupons' ), $discount_value )
					// translators: %s: Discount value formatted as a price.
					: sprintf( __( 'to get %s off ', 'woocommerce-smart-coupons' ), wc_price( $discount_value ) );
				$messages[] = $discount_text;
			}
			if ( $coupon->get_free_shipping() && $this->is_show_free_shipping() ) {
				$messages[] = __( ' get a free shipping ', 'woocommerce-smart-coupons' );
			}
			return $messages;
		}

		/**
		 * Check whether free shipping cost is applicable or not.
		 *
		 * @return bool $is_show_free_shipping_minimum_notice
		 */
		public function is_show_free_shipping() {
			$shipping_total = ( is_callable( array( WC()->cart, 'get_shipping_total' ) ) ) ? floatval( WC()->cart->get_shipping_total() ) : 0;

			$is_show_free_shipping_minimum_notice = apply_filters(
				'sc_cn_is_show_free_shipping_minimum_notice',
				$shipping_total > 0,
				array(
					'source'         => $this,
					'shipping_total' => $shipping_total,
				)
			);
			return boolval( $is_show_free_shipping_minimum_notice );
		}

		/**
		 * Gets the free shipping minimum for the current cart and available zone.
		 *
		 * @credit: WooCommerce Cart Notices
		 *
		 * @since 1.0.0
		 *
		 * @return int|bool $minimum_amount free shipping minimum based on available zones
		 */
		private function get_cart_free_shipping_minimum() {
			$is_cart = ( is_callable( 'WC' ) && isset( WC()->cart ) && ! WC()->cart->is_empty() ) ? true : false;
			$minimum = false;
			// we only need this check if zones are available and the cart is shipped.
			// as a configured notice amount would have been check already when this is called.
			if ( ! $is_cart || ! WC()->cart->needs_shipping() ) {
				return $minimum;
			}

			$packages      = WC()->cart->get_shipping_packages();
			$free_minimums = array();

			// check all packages for available methods on each.
			foreach ( $packages as $i => $package ) {

				if ( empty( $package['contents'] ) ) {
					continue;
				}

				// hold the lowest free shipping minimum for this package.
				$free_minimums[ $i ] = '';

				$shipping = WC()->shipping()->load_shipping_methods( $package );

				// loop all package methods to get the min amount for any free shipping rate.
				// there could be more than one free shipping rate available.
				foreach ( $shipping as $method ) {

					// ensure we're looking at a free shipping rate assigned to a zone (instance ID can't be 0).
					if ( 'yes' === $method->enabled && 'free_shipping' === $method->id && $method->instance_id > 0 ) {

						// sanity check -- ensure that the min amount actually is in effect.
						if ( ! in_array( $method->requires, array( 'min_amount', 'either', 'both' ), true ) ) {
							continue;
						}

						// set the value for our first loop.
						// if we've already pushed a value, only push our new minimum if it's lower.
						if ( empty( $free_minimums[ $i ] ) ) {
							$free_minimums[ $i ] = $method->min_amount;
						} elseif ( $method->min_amount < $free_minimums[ $i ] ) {
							$free_minimums[ $i ] = $method->min_amount;
						}
					}
				}
			}

			// now we have the lowest min_amount for each package, pull the absolute lowest for the notice.
			$minimum = ! empty( $free_minimums ) ? (float) min( $free_minimums ) : false;

			return $minimum;
		}

		/**
		 * Gets & filter auto apply coupons which could be apply with minimal restriction.
		 *
		 * @return array $results auto apply coupons without sny restrictions
		 */
		public function get_auto_apply_threshold_coupons() {
			global $wpdb;
			$cart = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
			if ( ! $cart ) {
				return array();
			}
			$wc_session            = ! empty( WC()->session ) ? WC()->session : null;
			$auto_apply_coupon_ids = ( ! empty( $wc_session ) && is_a( $wc_session, 'WC_Session' ) && is_callable( array( $wc_session, 'get' ) ) ) ? $wc_session->get( $this->session_key_auto_apply_coupons ) : array();
			if ( empty( $auto_apply_coupon_ids ) ) {
				return array();
			}
			$placeholders = implode( ',', array_map( 'absint', $auto_apply_coupon_ids ) );
			$query        = $wpdb->prepare(
				"SELECT id
				FROM {$wpdb->prefix}wc_smart_coupons
				WHERE id IN ( %s )
					AND ( individual_use IS NULL OR individual_use = 0 )
					AND ( usage_limit IS NULL OR usage_limit = 0 )
					AND ( usage_limit_per_user IS NULL OR usage_limit_per_user = 0 )
					AND (wc_sc_payment_method_ids IS NULL OR wc_sc_payment_method_ids IN( '', %s ) )
					AND (wc_sc_shipping_method_ids IS NULL OR wc_sc_shipping_method_ids IN( '', %s ) )
					AND (exclude_product_ids IS NULL OR exclude_product_ids = '')
					AND (wc_sc_product_attribute_ids IS NULL OR wc_sc_product_attribute_ids IN( '', %s ) )
					AND (wc_sc_exclude_product_attribute_ids IS NULL OR wc_sc_exclude_product_attribute_ids IN( '', %s ) )
					AND (product_categories IS NULL OR product_categories IN( '', %s ) )
					AND (exclude_product_categories IS NULL OR exclude_product_categories IN( '', %s ) )
					AND (wc_sc_taxonomy_restrictions IS NULL OR wc_sc_taxonomy_restrictions IN( '', %s ) )
					AND (customer_email IS NULL OR customer_email = %s)
					AND (wc_sc_user_role_ids IS NULL OR wc_sc_user_role_ids IN( '', %s ) )
					AND (wc_sc_excluded_customer_email IS NULL OR wc_sc_excluded_customer_email IN( '', %s ) )",
				$placeholders,
				'a:0:{}',
				'a:0:{}',
				'a:0:{}',
				'a:0:{}',
				'a:0:{}',
				'a:0:{}',
				'a:0:{}',
				'a:0:{}',
				'a:0:{}',
				'a:0:{}'
			);
			$results      = $wpdb->get_col( $query ); // phpcs:ignore
			return apply_filters( 'wc_sc_get_auto_apply_threshold_coupons', ! empty( $results ) ? $results : array(), $auto_apply_coupon_ids );
		}


	}
}

WC_SC_Cart_Notices::get_instance();
