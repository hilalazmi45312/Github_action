<?php
/**
 * Handle coupon actions
 *
 * @author      StoreApps
 * @since       3.5.0
 * @version     1.18.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Coupon_Actions' ) ) {

	/**
	 * Class for handling processes of coupons
	 */
	class WC_SC_Coupon_Actions {

		/**
		 * Variable to hold instance of WC_SC_Coupon_Actions
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {

			add_filter( 'woocommerce_add_cart_item', array( $this, 'modify_cart_item_data_in_add_to_cart' ), 15, 2 );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'modify_cart_item_in_session' ), 15, 3 );
			add_filter( 'woocommerce_cart_item_quantity', array( $this, 'modify_cart_item_quantity' ), 5, 3 );
			add_filter( 'woocommerce_cart_item_price', array( $this, 'modify_cart_item_price' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'modify_cart_item_price' ), 10, 3 );
			add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'modify_cart_item_price' ), 10, 3 );
			add_filter( 'woocommerce_coupon_get_items_to_validate', array( $this, 'remove_products_from_validation' ), 10, 2 );

			add_action( 'woocommerce_applied_coupon', array( $this, 'coupon_action' ) );
			add_action( 'woocommerce_removed_coupon', array( $this, 'remove_product_from_cart' ) );
			add_action( 'woocommerce_check_cart_items', array( $this, 'review_cart_items' ) );
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_product_source_in_order_item_meta' ), 10, 4 );

			add_filter( 'wc_smart_coupons_export_headers', array( $this, 'export_headers' ) );
			add_filter( 'wc_sc_export_coupon_meta_data', array( $this, 'export_coupon_meta_data' ), 10, 2 );
			add_filter( 'smart_coupons_parser_postmeta_defaults', array( $this, 'postmeta_defaults' ) );
			add_filter( 'sc_generate_coupon_meta', array( $this, 'generate_coupon_meta' ), 10, 2 );
			add_filter( 'is_protected_meta', array( $this, 'make_action_meta_protected' ), 10, 3 );

			add_action( 'wc_sc_new_coupon_generated', array( $this, 'copy_coupon_action_meta' ) );

			add_filter( 'show_zero_amount_coupon', array( $this, 'show_coupon_with_actions' ), 10, 2 );
			add_filter( 'wc_sc_is_auto_generate', array( $this, 'auto_generate_coupon_with_actions' ), 10, 2 );
			add_filter( 'wc_sc_validate_coupon_amount', array( $this, 'validate_coupon_amount' ), 10, 2 );

			add_filter( 'wc_sc_hold_applied_coupons', array( $this, 'maybe_run_coupon_actions' ), 10, 2 );
			add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'stop_cart_item_quantity_update' ), 99, 4 );

			add_action( 'woocommerce_order_applied_coupon', array( $this, 'coupon_action' ), 10, 2 );

			add_action( 'admin_init', array( $this, 'remove_product_from_order' ), 11 );

			add_filter( 'woocommerce_store_api_product_quantity_editable', array( $this, 'store_api_restrict_product_quantity_in_cart_item' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'remove_action_product_link_for_classic_cart' ), 10, 2 );

			// Hooks related to coupon actions in the Coupon.
			add_action( 'wp_footer', array( $this, 'sc_load_coupon_action_modal_js_css' ) );
			add_action( 'woocommerce_before_cart', array( $this, 'display_gift_selection_message' ), 9999 );
			add_action( 'woocommerce_checkout_update_order_review', array( $this, 'display_gift_selection_message' ), 9999 );
			add_action( 'wp_ajax_get_coupon_product_selection_html', array( $this, 'get_coupon_product_selection_html' ) );
			add_action( 'wp_ajax_nopriv_get_coupon_product_selection_html', array( $this, 'get_coupon_product_selection_html' ) );
			add_action( 'wp_ajax_handle_coupon_product_addition', array( $this, 'handle_coupon_product_addition' ) );
			add_action( 'wp_ajax_nopriv_handle_coupon_product_addition', array( $this, 'handle_coupon_product_addition' ) );
		}

		/**
		 * Get single instance of WC_SC_Coupon_Actions
		 *
		 * @return WC_SC_Coupon_Actions Singleton object of WC_SC_Coupon_Actions
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return result of function call
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
		 * Get coupon actions
		 *
		 * @param  string $coupon_code The coupon code.
		 * @return array  Coupon actions
		 */
		public function get_coupon_actions( $coupon_code = '' ) {

			if ( empty( $coupon_code ) ) {
				return array();
			}

			$coupon_code = wc_format_coupon_code( $coupon_code );
			$coupons     = get_posts(
				array(
					'post_type'   => 'shop_coupon',
					'title'       => $coupon_code,
					'post_status' => 'publish',
					'numberposts' => 1,
				)
			);
			$coupon      = current( $coupons );
			$coupon_id   = ( ! empty( $coupon->ID ) ) ? $coupon->ID : 0;
			$coupon      = new WC_Coupon( $coupon_id );

			if ( ! is_wp_error( $coupon ) ) {

				if ( $this->is_wc_gte_30() ) {
					$actions = $coupon->get_meta( 'wc_sc_add_product_details' );
				} else {
					$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
					$actions   = get_post_meta( $coupon_id, 'wc_sc_add_product_details', true );
				}

				return apply_filters( 'wc_sc_coupon_actions', $actions, array( 'coupon_code' => $coupon_code ) );

			}

			return array();

		}

		/**
		 * Modify cart item data
		 *
		 * @param  array   $cart_item_data The cart item data.
		 * @param  integer $product_id     The product id.
		 * @param  integer $variation_id   The variation id.
		 * @param  integer $quantity       The quantity of product.
		 * @return array   $cart_item_data
		 */
		public function modify_cart_item_data( $cart_item_data = array(), $product_id = 0, $variation_id = 0, $quantity = 0 ) {

			if ( empty( $cart_item_data ) || empty( $product_id ) || empty( $quantity ) ) {
				return $cart_item_data;
			}

			if ( ! empty( $cart_item_data['wc_sc_product_source'] ) ) {
				$coupon_code    = $cart_item_data['wc_sc_product_source'];
				$coupon_actions = $this->get_coupon_actions( $coupon_code );
				if ( ! empty( $coupon_actions ) && ! is_scalar( $coupon_actions ) ) {
					foreach ( $coupon_actions as $product_data ) {
						if ( ! empty( $product_data['product_id'] ) && in_array( absint( $product_data['product_id'] ), array_map( 'absint', array( $product_id, $variation_id ) ), true ) ) {
							$discount_amount = ( '' !== $product_data['discount_amount'] ) ? $product_data['discount_amount'] : '';
							if ( '' !== $discount_amount ) {
								if ( ! empty( $variation_id ) ) {
									$product = wc_get_product( $variation_id );
								} else {
									$product = wc_get_product( $product_id );
								}
								$product_price = $product->get_price();
								$regular_price = $product->get_regular_price();
								$discount_type = ( ! empty( $product_data['discount_type'] ) ) ? $product_data['discount_type'] : 'percent';
								switch ( $discount_type ) {
									case 'flat':
										$discount = $this->convert_price( $discount_amount );
										break;

									case 'percent':
										$discount = ( $product_price * $discount_amount ) / 100;
										break;
								}
								$discount         = wc_cart_round_discount( min( $product_price, $discount ), wc_get_price_decimals() );
								$discounted_price = $product_price - $discount;
								$cart_item_data['data']->set_price( $discounted_price );
								$cart_item_data['data']->set_regular_price( $regular_price );
								$cart_item_data['data']->set_sale_price( $discounted_price );
							}
							break;
						}
					}
				}
			}

			return $cart_item_data;
		}

		/**
		 * Modify cart item in WC_Cart::add_to_cart()
		 *
		 * @param array  $cart_item_data The cart item data as passed by filter 'woocommerce_add_cart_item'.
		 * @param string $cart_item_key The cart item key.
		 * @return array $cart_item_data
		 */
		public function modify_cart_item_data_in_add_to_cart( $cart_item_data = array(), $cart_item_key = '' ) {
			if ( ! empty( $cart_item_data['wc_sc_product_source'] ) ) {
				$cart_item_data = $this->modify_cart_item_data( $cart_item_data, $cart_item_data['product_id'], $cart_item_data['variation_id'], $cart_item_data['quantity'] );
			}

			return $cart_item_data;
		}

		/**
		 * Modify cart item in session
		 *
		 * @param  array  $session_data The session data.
		 * @param  array  $values       The cart item.
		 * @param  string $key          The cart item key.
		 * @return array  $session_data
		 */
		public function modify_cart_item_in_session( $session_data = array(), $values = array(), $key = '' ) {

			if ( ! empty( $values['wc_sc_product_source'] ) ) {
				$session_data['wc_sc_product_source'] = $values['wc_sc_product_source'];
				$qty                                  = ( ! empty( $session_data['quantity'] ) ) ? absint( $session_data['quantity'] ) : ( ( ! empty( $values['quantity'] ) ) ? absint( $values['quantity'] ) : 1 );
				$session_data                         = $this->modify_cart_item_data( $session_data, $session_data['product_id'], $session_data['variation_id'], $qty );
			}

			return $session_data;
		}

		/**
		 * Modify cart item quantity
		 *
		 * @param  string $product_quantity The product quantity.
		 * @param  string $cart_item_key    The cart item key.
		 * @param  array  $cart_item        The cart item.
		 * @return string $product_quantity
		 */
		public function modify_cart_item_quantity( $product_quantity = '', $cart_item_key = '', $cart_item = array() ) {

			if ( ! empty( $cart_item['wc_sc_product_source'] ) ) {
				$product_quantity = sprintf( '%s <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item['quantity'], $cart_item_key, $cart_item['quantity'] );
			}

			return $product_quantity;
		}

		/**
		 * Modify cart item price
		 *
		 * @param  string $product_price The product price.
		 * @param  array  $cart_item     The cart item.
		 * @param  string $cart_item_key The cart item key.
		 * @return string $product_price
		 */
		public function modify_cart_item_price( $product_price = '', $cart_item = array(), $cart_item_key = '' ) {

			if ( ( is_array( $cart_item ) && isset( $cart_item['wc_sc_product_source'] ) ) || ( is_object( $cart_item ) && is_callable( array( $cart_item, 'get_meta' ) ) && $cart_item->get_meta( '_wc_sc_product_source' ) ) ) {
				if ( wc_price( 0 ) === $product_price ) {
					$product_price = apply_filters(
						'wc_sc_price_zero_text',
						$product_price,
						array(
							'cart_item'     => $cart_item,
							'cart_item_key' => $cart_item_key,
						)
					);
				}
			}

			return $product_price;
		}

		/**
		 * Remove products added by the coupon from validation
		 *
		 * Since the filter 'woocommerce_coupon_get_items_to_validate' is added in WooCommerce 3.4.0, this function will work only in WC 3.4.0+
		 * Otherwise, the products added by coupon might get double discounts applied
		 *
		 * @param  array        $items     The cart/order items.
		 * @param  WC_Discounts $discounts The discounts object.
		 * @return mixed        $items
		 */
		public function remove_products_from_validation( $items = array(), $discounts = null ) {

			if ( ! empty( $items ) && ! is_scalar( $items ) ) {
				foreach ( $items as $index => $item ) {
					$coupon_code = '';
					if ( is_array( $item->object ) && isset( $item->object['wc_sc_product_source'] ) ) {
						$coupon_code = $item->object['wc_sc_product_source'];
					} elseif ( is_a( $item->object, 'WC_Order_Item' ) && is_callable( array( $item->object, 'get_meta' ) ) && $item->object->get_meta( '_wc_sc_product_source' ) ) {
						$coupon_code = $item->object->get_meta( '_wc_sc_product_source' );
					}
					if ( ! empty( $coupon_code ) ) {
						$item_product_id = ( is_a( $item->product, 'WC_Product' ) && is_callable( array( $item->product, 'get_id' ) ) ) ? $item->product->get_id() : 0;
						$coupon_actions  = $this->get_coupon_actions( $coupon_code );
						if ( ! empty( $coupon_actions ) && ! is_scalar( $coupon_actions ) ) {
							foreach ( $coupon_actions as $product_data ) {
								if ( ! empty( $product_data['product_id'] ) && absint( $product_data['product_id'] ) === absint( $item_product_id ) ) {
									$discount_amount = ( '' !== $product_data['discount_amount'] ) ? $product_data['discount_amount'] : '';
									if ( '' !== $discount_amount ) {
										unset( $items[ $index ] );
									}
								}
							}
						}
					}
				}
			}

			return $items;

		}

		/**
		 * Added selected product to cart
		 *
		 * @param  int    $product_id product id of the selectable product.
		 * @param  string $coupon_code coupon code what applied.
		 * @param int    $quantity no of quantity will add to cart.
		 */
		public function add_action_tab_selected_product_to_cart( $product_id = null, $coupon_code = null, $quantity = 1 ) {

			$product = wc_get_product( $product_id );

			$product_data = $this->get_product_data( $product );

			$product_id   = ( ! empty( $product_data['product_id'] ) ) ? absint( $product_data['product_id'] ) : 0;
			$variation_id = ( ! empty( $product_data['variation_id'] ) ) ? absint( $product_data['variation_id'] ) : 0;
			$variation    = array();

			if ( ! empty( $variation_id ) ) {
				$variation = $product->get_variation_attributes();
			}

			$quantity = absint( $quantity );

			$cart_item_data = array(
				'wc_sc_product_source' => $coupon_code,
			);

			$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
			if ( ! empty( $cart_item_key ) ) {
				if ( $this->is_wc_gte_30() ) {
					$product_names[] = ( is_object( $product ) && is_callable( array( $product, 'get_name' ) ) ) ? $product->get_name() : '';
				} else {
					$product_names[] = ( is_object( $product ) && is_callable( array( $product, 'get_title' ) ) ) ? $product->get_title() : '';
				}
			}

			if ( ! empty( $product_names ) ) {
				/* translators: 1. Product title */
				wc_add_notice( sprintf( __( '%s has been added to your cart!', 'woocommerce-smart-coupons' ), implode( ', ', $product_names ) ) );
			}
		}

		/**
		 * Apply coupons actions
		 *
		 * @param string   $coupon_code The coupon code.
		 * @param WC_Order $order WooCommerce order instance.
		 */
		public function coupon_action( $coupon_code = '', $order = null ) {

			if ( empty( $coupon_code ) ) {
				return;
			}

			if ( $coupon_code instanceof WC_Coupon && is_callable( array( $coupon_code, 'get_code' ) ) ) {
				$coupon_code = $coupon_code->get_code();
			}

			$coupon_actions = $this->get_coupon_actions( $coupon_code );

			if ( ! empty( $coupon_actions ) && ! is_scalar( $coupon_actions ) ) {
				$product_names = array();
				foreach ( $coupon_actions as $coupon_action ) {
					if ( empty( $coupon_action['product_id'] ) ) {
						continue;
					}

					$id = absint( $coupon_action['product_id'] );

					$product = wc_get_product( $id );

					$product_data = $this->get_product_data( $product );

					$product_id   = ( ! empty( $product_data['product_id'] ) ) ? absint( $product_data['product_id'] ) : 0;
					$variation_id = ( ! empty( $product_data['variation_id'] ) ) ? absint( $product_data['variation_id'] ) : 0;
					$variation    = array();

					if ( ! empty( $variation_id ) ) {
						$variation = $product->get_variation_attributes();
					}

					$quantity = absint( $coupon_action['quantity'] );

					$cart_item_data = array(
						'wc_sc_product_source' => $coupon_code,
					);

					if ( $order instanceof WC_Abstract_Order ) {
						$product_exists = false;
						$order_items    = $order->get_items();
						foreach ( $order_items as $order_item ) {
							if ( absint( $order_item->get_product_id() ) === $product_id ) {
								$product_exists = true; // Exit if the product is already in the order.
								break;
							}
						}
						if ( ! $product_exists ) {
							// Add a line item to the order.
							$item            = new WC_Order_Item_Product();
							$discount_amount = (float) ( '' !== $product_data['discount_amount'] ) ? $coupon_action['discount_amount'] : '';
							$discount_type   = ( ! empty( $coupon_action['discount_type'] ) ) ? $coupon_action['discount_type'] : 'percent';
							$price           = floatval( $product->get_price() );
							switch ( $discount_type ) {
								case 'flat':
									$discount = $this->convert_price( $discount_amount );
									break;

								case 'percent':
									$discount = ( $price * $discount_amount ) / 100;
									break;
							}
							// Calculated the discounted amount.
							$discounted_price = ( $price - $discount ) * $quantity;

							$item->set_props(
								array(
									'name'         => $product->get_name(),
									'product_id'   => $product_id, // ID of the product to add.
									'quantity'     => $quantity,   // Quantity of the product.
									'variation_id' => $variation_id, // ID of the product variation (if applicable).
								)
							);
							$item->set_subtotal( $discounted_price );
							$item->add_meta_data( '_wc_sc_product_source', $coupon_code );
							$item->set_order_id( $order->get_id() );

							// Add the item to the order.
							$order->add_item( $item );
						}
					} else {

						$no_of_selectable_product = 'no';
						$coupon                   = new WC_Coupon( $coupon_code );
						if ( $this->is_wc_gte_30() ) {
							$coupon_id = ( ! empty( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
						} else {
							$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
						}
						if ( $coupon instanceof WC_Coupon && is_callable( array( $coupon, 'get_meta' ) ) ) {
							$no_of_selectable_product = $coupon->get_meta( 'wc_sc_no_of_selectable_product' );
						}
						$is_auto_apply = is_callable( array( $coupon, 'get_meta' ) ) ? $coupon->get_meta( 'wc_sc_auto_apply_coupon' ) : get_post_meta( $coupon_id, 'wc_sc_auto_apply_coupon', true );
						if ( ! class_exists( 'WC_SC_URL_Coupon' ) ) {
							include_once 'class-wc-sc-url-coupon.php';
						}
						$wc_sc_url_coupon = WC_SC_URL_Coupon::get_instance();
						if ( ( ! is_admin() || wp_doing_ajax() ) &&
							'yes' !== $no_of_selectable_product &&
							(
								wp_doing_ajax() ||
								WC()->is_rest_api_request() ||
								'yes' === $is_auto_apply ||
								$wc_sc_url_coupon->is_coupon_applied_via_url( $coupon_code )
							)
						) {

							$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
							if ( ! empty( $cart_item_key ) ) {
								if ( $this->is_wc_gte_30() ) {
									$product_names[] = ( is_object( $product ) && is_callable( array( $product, 'get_name' ) ) ) ? $product->get_name() : '';
								} else {
									$product_names[] = ( is_object( $product ) && is_callable( array( $product, 'get_title' ) ) ) ? $product->get_title() : '';
								}
							}
						}
					}
				}

				if ( ! ( $order instanceof WC_Abstract_Order ) && ! empty( $product_names ) ) {
					/* translators: 1. Product title */
					wc_add_notice( sprintf( __( '%s has been added to your cart!', 'woocommerce-smart-coupons' ), implode( ', ', $product_names ) ) );
					if ( is_product() && ! empty( wc_get_notices( 'success' ) ) ) {
						wc_print_notices();
					}
				}
			}

		}

		/**
		 * Remove products from cart if the coupon, which added the product, is removed
		 *
		 * @param string $coupon_code The coupon code.
		 */
		public function remove_product_from_cart( $coupon_code = '' ) {

			if ( is_admin() && ! wp_doing_ajax() ) {
				return;
			}

			if ( ! empty( $coupon_code ) ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( isset( $cart_item['wc_sc_product_source'] ) && $cart_item['wc_sc_product_source'] === $coupon_code ) {
						// Action 'woocommerce_before_calculate_totals' is hooked by WooCommerce Subscription while removing coupons in local WooCommerce Cart variable in which we don't need to remove added cart item.
						if ( ! doing_action( 'woocommerce_before_calculate_totals' ) ) {
							WC()->cart->set_quantity( $cart_item_key, 0 );
						}
					}
				}
			}

		}

		/**
		 * Remove products added by coupon actions when the coupon is removed from the order.
		 */
		public function remove_product_from_order() {
			$action   = ( ! empty( $_POST['action'] ) ) ? wc_clean( wp_unslash( $_POST['action'] ) ) : ''; // phpcs:ignore
			$coupon_code = ( ! empty( $_POST['coupon'] ) ) ? wc_clean( wp_unslash( $_POST['coupon'] ) ) : ''; // phpcs:ignore
			$order_id    = ( ! empty( $_POST['order_id'] ) ) ? wc_clean( wp_unslash( $_POST['order_id'] ) ) : 0; // phpcs:ignore

			if ( 'woocommerce_remove_order_coupon' !== $action || empty( $order_id ) || empty( $coupon_code ) ) {
				return;
			}

			$order = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order ) {
				return;
			}

			foreach ( $order->get_items() as $item_id => $item ) {
				if ( $item->get_meta( '_wc_sc_product_source', true ) === $coupon_code ) {
					$order->remove_item( $item_id );
				}
			}

			$order->calculate_totals(); // Recalculate totals.
			$order->save();             // Save the order.
		}

		/**
		 * Review cart items
		 */
		public function review_cart_items() {
			$cart            = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
			$applied_coupons = isset( $cart->applied_coupons ) ? (array) $cart->applied_coupons : array();

			$products = array();
			if ( $cart instanceof WC_Cart && is_callable( array( $cart, 'get_cart' ) ) ) {
				foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( ! empty( $cart_item['wc_sc_product_source'] ) && ! in_array( $cart_item['wc_sc_product_source'], $applied_coupons, true ) ) {
						$cart->set_quantity( $cart_item_key, 0 );
						$coupon_code = $cart_item['wc_sc_product_source'];
						if ( empty( $products[ $coupon_code ] ) || ! is_array( $products[ $coupon_code ] ) ) {
							$products[ $coupon_code ] = array();
						}
						$products[ $coupon_code ][] = ( is_object( $cart_item['data'] ) && is_callable( array( $cart_item['data'], 'get_name' ) ) ) ? $cart_item['data']->get_name() : '';
						$products[ $coupon_code ]   = array_filter( $products[ $coupon_code ] );
					}
				}
			}

			if ( ! empty( $products ) && ! is_scalar( $products ) ) {
				foreach ( $products as $coupon_code => $product_names ) {
					/* translators: 1. Product/s 2. Product names 3. is/are 4. Coupons code */
					wc_add_notice( sprintf( __( '%1$s %2$s %3$s removed because coupon %4$s is removed.', 'woocommerce-smart-coupons' ), _n( 'Product', 'Products', count( $products[ $coupon_code ] ), 'woocommerce-smart-coupons' ), '<strong>' . implode( ', ', $products[ $coupon_code ] ) . '</strong>', _n( 'is', 'are', count( $products[ $coupon_code ] ), 'woocommerce-smart-coupons' ), '<code>' . $coupon_code . '</code>' ), 'error' );
				}
			}

		}

		/**
		 * Add product source in order item meta
		 *
		 * @param mixed    $item          The item.
		 * @param string   $cart_item_key The cart item key.
		 * @param array    $values        The cart item.
		 * @param WC_Order $order         The order.
		 */
		public function add_product_source_in_order_item_meta( $item = null, $cart_item_key = '', $values = array(), $order = null ) {

			if ( isset( $values['wc_sc_product_source'] ) ) {
				$item->add_meta_data( '_wc_sc_product_source', $values['wc_sc_product_source'], true );
			}

		}

		/**
		 * Get product data
		 *
		 * @param  mixed $product The product object.
		 * @return array
		 */
		public function get_product_data( $product = null ) {

			if ( empty( $product ) ) {
				return array();
			}

			if ( $this->is_wc_gte_30() ) {
				$product_id = ( is_object( $product ) && is_callable( array( $product, 'get_id' ) ) ) ? $product->get_id() : 0;
			} else {
				$product_id = ( ! empty( $product->id ) ) ? $product->id : 0;
			}

			$product_type = ( is_object( $product ) && is_callable( array( $product, 'get_type' ) ) ) ? $product->get_type() : '';

			if ( 'variation' === $product_type ) {
				$variation_id = $product_id;
				if ( $this->is_wc_gte_30() ) {
					$parent_id      = ( is_object( $product ) && is_callable( array( $product, 'get_parent_id' ) ) ) ? $product->get_parent_id() : 0;
					$variation_data = wc_get_product_variation_attributes( $variation_id );
				} else {
					$parent_id      = ( is_object( $product ) && is_callable( array( $product, 'get_parent' ) ) ) ? $product->get_parent() : 0;
					$variation_data = ( ! empty( $product->variation_data ) ) ? $product->variation_data : array();
				}
				$product_id = $parent_id;
			} else {
				$variation_id   = 0;
				$variation_data = array();
			}

			$product_data = array(
				'product_id'     => $product_id,
				'variation_id'   => $variation_id,
				'variation_data' => $variation_data,
			);

			return apply_filters( 'wc_sc_product_data', $product_data, array( 'product_obj' => $product ) );

		}

		/**
		 * Add action's meta in export headers
		 *
		 * @param  array $headers Existing headers.
		 * @return array
		 */
		public function export_headers( $headers = array() ) {

			$action_headers = array(
				'wc_sc_add_product_details' => __( 'Add product details', 'woocommerce-smart-coupons' ),
			);

			return array_merge( $headers, $action_headers );

		}

		/**
		 * Function to handle coupon meta data during export of existing coupons
		 *
		 * @param  mixed $meta_value The meta value.
		 * @param  array $args       Additional arguments.
		 * @return string Processed meta value
		 */
		public function export_coupon_meta_data( $meta_value = '', $args = array() ) {

			$index       = ( ! empty( $args['index'] ) ) ? $args['index'] : -1;
			$meta_keys   = ( ! empty( $args['meta_keys'] ) ) ? $args['meta_keys'] : array();
			$meta_values = ( ! empty( $args['meta_values'] ) ) ? $args['meta_values'] : array();

			if ( $index >= 0 && ! empty( $meta_keys[ $index ] ) && 'wc_sc_add_product_details' === $meta_keys[ $index ] ) {

				if ( ! empty( $meta_value ) && is_array( $meta_value ) ) {
					$product_details = array();
					foreach ( $meta_value as $value ) {
						$product_details[] = implode( ',', $value );
					}
					$meta_value = implode( '|', $product_details );
				}
			}

			return $meta_value;

		}

		/**
		 * Post meta defaults for action's meta
		 *
		 * @param  array $defaults Existing postmeta defaults.
		 * @return array
		 */
		public function postmeta_defaults( $defaults = array() ) {

			$actions_defaults = array(
				'wc_sc_add_product_details' => '',
			);

			return array_merge( $defaults, $actions_defaults );
		}

		/**
		 * Add action's meta with value in coupon meta
		 *
		 * @param  array $data The row data.
		 * @param  array $post The POST values.
		 * @return array Modified data
		 */
		public function generate_coupon_meta( $data = array(), $post = array() ) {

			if ( isset( $post['wc_sc_add_product_ids'] ) ) {
				if ( $this->is_wc_gte_30() ) {
					$product_ids = wc_clean( wp_unslash( $post['wc_sc_add_product_ids'] ) ); // phpcs:ignore
				} else {
					$product_ids = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $post['wc_sc_add_product_ids'] ) ) ) ) ); // phpcs:ignore
				}
				$add_product_details = array();
				if ( ! empty( $product_ids ) && ! is_scalar( $product_ids ) ) {
					$quantity        = ( isset( $post['wc_sc_add_product_qty'] ) ) ? wc_clean( wp_unslash( $post['wc_sc_add_product_qty'] ) ) : 1;
					$discount_amount = ( isset( $post['wc_sc_product_discount_amount'] ) ) ? wc_clean( wp_unslash( $post['wc_sc_product_discount_amount'] ) ) : '';
					$discount_type   = ( isset( $post['wc_sc_product_discount_type'] ) ) ? wc_clean( wp_unslash( $post['wc_sc_product_discount_type'] ) ) : '';
					foreach ( $product_ids as $id ) {
						$product_data                    = array();
						$product_data['product_id']      = $id;
						$product_data['quantity']        = $quantity;
						$product_data['discount_amount'] = $discount_amount;
						$product_data['discount_type']   = $discount_type;
						$add_product_details[]           = implode( ',', $product_data );
					}
				}
				$data['wc_sc_add_product_details'] = implode( '|', $add_product_details );
			}

			return $data;
		}

		/**
		 * Make meta data of SC actions, protected
		 *
		 * @param bool   $protected Is protected.
		 * @param string $meta_key The meta key.
		 * @param string $meta_type The meta type.
		 * @return bool $protected
		 */
		public function make_action_meta_protected( $protected, $meta_key, $meta_type ) {
			$sc_meta = array(
				'wc_sc_add_product_details',
			);
			if ( in_array( $meta_key, $sc_meta, true ) ) {
				return true;
			}
			return $protected;
		}

		/**
		 * Function to copy coupon action meta in newly generated coupon
		 *
		 * @param  array $args The arguments.
		 */
		public function copy_coupon_action_meta( $args = array() ) {

			$new_coupon_id = ( ! empty( $args['new_coupon_id'] ) ) ? absint( $args['new_coupon_id'] ) : 0;
			$coupon        = ( ! empty( $args['ref_coupon'] ) ) ? $args['ref_coupon'] : false;

			if ( empty( $new_coupon_id ) || empty( $coupon ) ) {
				return;
			}

			$add_product_details = array();
			if ( $this->is_wc_gte_30() ) {
				$add_product_details = $coupon->get_meta( 'wc_sc_add_product_details' );
			} else {
				$old_coupon_id       = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
				$add_product_details = get_post_meta( $old_coupon_id, 'wc_sc_add_product_details', true );
			}
			$this->update_post_meta( $new_coupon_id, 'wc_sc_add_product_details', $add_product_details );

		}

		/**
		 * Function to validate whether to show the coupon or not
		 *
		 * @param  boolean $is_show Show or not.
		 * @param  array   $args    Additional arguments.
		 * @return boolean
		 */
		public function show_coupon_with_actions( $is_show = false, $args = array() ) {

			$coupon = ( ! empty( $args['coupon'] ) ) ? $args['coupon'] : null;

			if ( empty( $coupon ) ) {
				return $is_show;
			}

			if ( $this->is_wc_gte_30() ) {
				$coupon_code = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_code' ) ) ) ? $coupon->get_code() : '';
			} else {
				$coupon_code = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
			}

			$coupon_actions = $this->get_coupon_actions( $coupon_code );

			if ( ! empty( $coupon_actions ) ) {
				return true;
			}

			return $is_show;

		}

		/**
		 * Allow auto-generate of coupon with coupon action
		 *
		 * @param  boolean $is_auto_generate Whether to auto-generate or not.
		 * @param  array   $args             Additional parameters.
		 * @return boolean $is_auto_generate
		 */
		public function auto_generate_coupon_with_actions( $is_auto_generate = false, $args = array() ) {

			$coupon    = ( ! empty( $args['coupon_obj'] ) && $args['coupon_obj'] instanceof WC_Coupon ) ? $args['coupon_obj'] : false;
			$coupon_id = ( ! empty( $args['coupon_id'] ) ) ? $args['coupon_id'] : false;

			if ( ! empty( $coupon ) && ! empty( $coupon_id ) ) {
				if ( $this->is_wc_gte_30() ) {
					$coupon_code = $coupon->get_code();
				} else {
					$coupon_code = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
				}
				if ( ! empty( $coupon_code ) ) {
					$actions        = ( $this->is_callable( $coupon, 'get_meta' ) ) ? $coupon->get_meta( 'wc_sc_add_product_details' ) : get_post_meta( $coupon_id, 'wc_sc_add_product_details', true );
					$coupon_actions = apply_filters(
						'wc_sc_coupon_actions',
						$actions,
						array(
							'coupon_code' => $coupon_code,
							'source'      => $this,
						)
					);
					if ( ! empty( $coupon_actions ) ) {
						return true;
					}
				}
			}

			return $is_auto_generate;
		}

		/**
		 * Validate coupon having actions but without an amount
		 *
		 * @param  boolean $is_valid_coupon_amount Whether the amount is validate or not.
		 * @param  array   $args                   Additional parameters.
		 * @return boolean
		 */
		public function validate_coupon_amount( $is_valid_coupon_amount = true, $args = array() ) {

			if ( ! $is_valid_coupon_amount ) {
				$coupon_amount = ( ! empty( $args['coupon_amount'] ) ) ? $args['coupon_amount'] : 0;
				$discount_type = ( ! empty( $args['discount_type'] ) ) ? $args['discount_type'] : '';
				$coupon_code   = ( ! empty( $args['coupon_code'] ) ) ? $args['coupon_code'] : '';

				$coupon_actions = ( ! empty( $coupon_code ) ) ? $this->get_coupon_actions( $coupon_code ) : array();

				if ( 'smart_coupon' === $discount_type && $coupon_amount <= 0 && ! empty( $coupon_actions ) ) {
					return true;
				}
			}

			return $is_valid_coupon_amount;
		}

		/**
		 * Handle coupon actions when the cart is empty
		 *
		 * @param  boolean $is_hold Whether to hold the coupon in cookie.
		 * @param  array   $args    Additional arguments.
		 * @return boolean
		 */
		public function maybe_run_coupon_actions( $is_hold = true, $args = array() ) {
			$cart = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
			if ( empty( $cart ) || WC()->cart->is_empty() ) {
				$coupons_data = ( ! empty( $args['coupons_data'] ) ) ? $args['coupons_data'] : array();
				if ( ! empty( $coupons_data ) && ! is_scalar( $coupons_data ) ) {
					foreach ( $coupons_data as $coupon_data ) {
						$coupon_code = ( ! empty( $coupon_data['coupon-code'] ) ) ? $coupon_data['coupon-code'] : '';
						if ( ! empty( $coupon_code ) ) {
							$coupon          = new WC_Coupon( $coupon_code );
							$coupon_actions  = $this->get_coupon_actions( $coupon_code );
							$coupon_products = ( ! empty( $coupon_actions ) ) ? wp_list_pluck( $coupon_actions, 'product_id' ) : array();
							if ( ! empty( $coupon_products ) ) {
								if ( $this->is_valid( $coupon ) && ! WC()->cart->has_discount( $coupon_code ) ) {
									WC()->cart->add_discount( trim( $coupon_code ) );
									$is_hold = false;
								}
							}
						}
					}
				}
			}
			return $is_hold;
		}

		/**
		 * Stop product update option for action tab products.
		 *
		 * @param string  $cart_item_key contains the id of the cart item.
		 * @param int     $quantity Current quantity of the item.
		 * @param int     $old_quantity Old quantity of the item.
		 * @param WC_Cart $cart  Cart object.
		 * @return void
		 */
		public function stop_cart_item_quantity_update( $cart_item_key = '', $quantity = 1, $old_quantity = 1, $cart = object ) {

			if ( empty( $cart_item_key ) || ! is_object( $cart ) || ! is_a( $cart, 'WC_Cart' ) ) {
				return;
			}

			$cart_data = is_callable( array( $cart, 'get_cart' ) ) ? $cart->get_cart() : array();
			$cart_item = ( ! empty( $cart_data[ $cart_item_key ] ) ) ? $cart_data[ $cart_item_key ] : array();

			if ( empty( $cart_item ) ) {
				return;
			}

			$applied_coupons = is_callable( array( $cart, 'get_applied_coupons' ) ) ? $cart->get_applied_coupons() : array();

			if ( ! empty( $cart_item['wc_sc_product_source'] ) && in_array( $cart_item['wc_sc_product_source'], $applied_coupons, true ) ) {
				$cart->cart_contents[ $cart_item_key ]['quantity'] = $old_quantity;
			}

		}

		/**
		 * Filters the quantity of action tab product.
		 *
		 * @param mixed       $value The value being filtered.
		 * @param \WC_Product $product The product object.
		 * @param array|null  $cart_item The cart item if the product exists in the cart, or null.
		 * @return mixed
		 */
		public function store_api_restrict_product_quantity_in_cart_item( $value = true, $product = object, $cart_item = array() ) {

			$cart            = WC()->cart;
			$applied_coupons = is_callable( array( $cart, 'get_applied_coupons' ) ) ? $cart->get_applied_coupons() : array();
			return ! empty( $cart_item['wc_sc_product_source'] ) && in_array( $cart_item['wc_sc_product_source'], $applied_coupons, true ) ? false : $value;
		}

		/**
		 * Remove item removal link from cart item.
		 *
		 * @param mixed      $link The link for the cart item.
		 * @param array|null $cart_item_key The cart item key.
		 * @return mixed
		 */
		public function remove_action_product_link_for_classic_cart( $link, $cart_item_key ) {

			if ( ! isset( WC()->cart->cart_contents[ $cart_item_key ]['wc_sc_product_source'] ) ) {
				return $link;
			}
			return in_array( WC()->cart->cart_contents[ $cart_item_key ]['wc_sc_product_source'], WC()->cart->get_applied_coupons(), true ) ? '' : $link;

		}

		/**
		 * Display a message prompting customers to select a free gift when a coupon is applied.
		 *
		 * If a coupon offers a free product selection, this function adds a WooCommerce notice
		 * to guide customers in choosing their eligible gift product.
		 */
		public function display_gift_selection_message() {

			// Check if WooCommerce cart is available.
			if ( ! WC()->cart ) {
				return;
			}

			$applied_coupons = WC()->cart->get_applied_coupons();

			// Exit if no coupons are applied.
			if ( empty( $applied_coupons ) || ! is_array( $applied_coupons ) ) {
				return;
			}

			$coupon_messages = array();

			// Loop through each applied coupon.
			foreach ( $applied_coupons as $coupon_code ) {
				$coupon_code = wc_format_coupon_code( $coupon_code );
				$coupon      = new WC_Coupon( $coupon_code );

				// Ensure the coupon object is valid and get_meta is callable.
				if ( $coupon instanceof WC_Coupon ) {
					if ( $this->is_wc_gte_30() ) {
						$coupon_id = ( ! empty( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
					} else {
						$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
					}

					if ( is_callable( array( $coupon, 'get_meta' ) ) ) {
						$no_of_selectable_product = $coupon->get_meta( 'wc_sc_no_of_selectable_product' );
					} else {
						$no_of_selectable_product = get_post_meta( $coupon_id, 'wc_sc_no_of_selectable_product', true );
					}

					$coupon_actions = $this->get_coupon_actions( $coupon_code );

					// Skip if no actions exist, or if selection isn't allowed.
					if ( empty( $coupon_actions ) || ! is_array( $coupon_actions ) || 'yes' !== $no_of_selectable_product ) {
						continue;
					}

					// If coupon allows selecting free products, prepare a notice message.
					$coupon_messages[] = sprintf(
						/* translators: %1$s is the formatted coupon code, %2$s is the coupon code used as modal ID. */
						_x( 'Choose your gift now with coupon %1$s! %2$s', 'Gift selection notice', 'woocommerce-smart-coupons' ),
						'<strong>' . esc_html( wc_format_coupon_code( $coupon_code ) ) . '</strong>',
						'<a class="sc_select_product" style="cursor: pointer;" data-modal-id="' . esc_attr( 'sc_popup_' . $coupon_code ) . '">' . _x( 'Click here to select your eligible product.', 'Gift selection notice link', 'woocommerce-smart-coupons' ) . '</a>'
					);
				}
			}

			// Display notices if there are any messages.
			if ( ! empty( $coupon_messages ) ) {
				foreach ( $coupon_messages as $message ) {
					if ( is_cart() ) {
						wc_print_notice( wp_kses_post( $message ), 'notice' );
					} else {
						is_ajax() ? wc_add_notice( wp_kses_post( $message ), 'notice' ) : null;
					}
				}
			}
		}

		/**
		 * Generate and return the HTML for the free product selection popup.
		 *
		 * Retrieves eligible products based on applied coupons, checks if selection is required,
		 * and constructs a popup allowing customers to choose a free product.
		 *
		 * @return void Outputs JSON response with the popup HTML.
		 */
		public function get_coupon_product_selection_html() {

			// Verify nonce for security.
			check_ajax_referer( 'wc-sc-get-coupon-action-data', 'security' );

			// Sanitize and retrieve the coupon code(s).
			$coupon       = isset( $_POST['coupon'] ) ? wc_clean( wp_unslash( $_POST['coupon'] ) ) : ''; //phpcs:ignore
			$coupon_codes = ! empty( $coupon ) ? ( is_array( $coupon ) ? array_map( 'sanitize_text_field', $coupon ) : array( sanitize_text_field( $coupon ) ) ) : WC()->cart->get_applied_coupons();

			// Exit if no valid coupons are found.
			if ( empty( $coupon_codes ) ) {
				wp_send_json_error( _x( 'No applicable coupons found.', 'Error message', 'woocommerce-smart-coupons' ) );
			}

			$popup_htmls = '';

			// Loop through each applied coupon.
			foreach ( $coupon_codes as $coupon_code ) {
				$coupon_code = wc_format_coupon_code( $coupon_code );
				$coupon      = new WC_Coupon( $coupon_code );

				// Ensure the coupon object is valid and supports meta retrieval.
				if ( $coupon instanceof WC_Coupon ) {
					if ( $this->is_wc_gte_30() ) {
						$coupon_id = ( ! empty( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
					} else {
						$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
					}

					if ( is_callable( array( $coupon, 'get_meta' ) ) ) {
						$no_of_selectable_product = $coupon->get_meta( 'wc_sc_no_of_selectable_product' );
					} else {
						$no_of_selectable_product = get_post_meta( $coupon_id, 'wc_sc_no_of_selectable_product', true );
					}

					$coupon_actions = $this->get_coupon_actions( $coupon_code );

					// Skip if no actions exist, or if selection isn't allowed.
					if ( empty( $coupon_actions ) || ! is_array( $coupon_actions ) || 'yes' !== $no_of_selectable_product ) {
						continue;
					}

					$popup_products_html = '';

					// Process each coupon action.
					foreach ( $coupon_actions as $coupon_action ) {
						if ( empty( $coupon_action['product_id'] ) ) {
							continue;
						}

						$product_id = absint( $coupon_action['product_id'] );
						$product    = wc_get_product( $product_id );

						// Skip invalid products.
						if ( ! $product ) {
							continue;
						}

						// Check if product is already in the cart with meta wc_sc_product_source.
						$is_product_in_cart = false;
						foreach ( WC()->cart->get_cart() as $cart_item ) {
							if ( isset( $cart_item['wc_sc_product_source'] ) && $cart_item['wc_sc_product_source'] === $coupon_code && absint( $cart_item['product_id'] ) === $product_id ) {
								$is_product_in_cart = true;
								break;
							}
						}

						// Generate HTML for each product in the selection popup.
						$popup_products_html .= sprintf(
							'<div class="sc_product_item">
								<label class="sc_product_item" for="sc_product_%1$s_%2$s">
									<input type="radio" id="sc_product_%1$s_%2$s" name="sc_product_%2$s" value="%1$s" %3$s>
									%4$s
									<span>%5$s</span>
								</label>
							</div>',
							esc_attr( $product_id ),
							esc_attr( $coupon_code ), // Added coupon code to make it unique.
							checked( $is_product_in_cart, true, false ), // Pre-select if product is in cart.
							$product->get_image( 'thumbnail' ),
							esc_html( $product->get_name() )
						);
					}
					$discount_type = ( ! empty( $coupon_action['discount_type'] ) ) ? $coupon_action['discount_type'] : 'percent';

					// Generate popup HTML if eligible products exist.
					if ( ! empty( $popup_products_html ) ) {
						$popup_htmls .= sprintf(
							'<div id="sc_popup_%1$s" class="sc_popup" style="display:none;">
								<span class="sc_close_popup" style="font-size: 1.5rem; cursor: pointer;">&times;</span>
								<h3 class="sc_popup_title">%2$s</h3>
								<p class="sc_popup_description">%3$s</p>
								<form class="sc_popup_product_selection">
									<div class="sc_product_list">%4$s</div>
									<button type="submit" class="button add_to_cart_button ajax_add_to_cart">%5$s</button>
								</form>
							</div>',
							esc_attr( $coupon_code ),
							_x( 'Choose an option.', 'Popup title', 'woocommerce-smart-coupons' ),
							// translators: %1$s: coupon code, %2$d: number of options, %3$s: discount amount, %4$s: currency symbol, %5$s: discount type.
							sprintf( _x( 'Redeem Your "%1$s" and choose 1 product from %2$d options with %3$s %4$s %5$s off ', 'Popup description', 'woocommerce-smart-coupons' ), $coupon_code, count( $coupon_actions ), (float) $coupon_action['discount_amount'], 'flat' === $discount_type ? get_woocommerce_currency_symbol() : '', $discount_type ),
							$popup_products_html,
							_x( 'Add to Cart', 'Add to Cart button text', 'woocommerce-smart-coupons' )
						);
					}
				}
			}

			// Send response.
			wp_send_json_success( array( 'html' => $popup_htmls ) );
		}

		/**
		 * Handles the addition of a coupon-associated product to the cart via AJAX.
		 *
		 * Ensures that the selected product is valid for the applied coupon, removes any
		 * previously selected product if necessary, and adds the new product to the cart
		 * with the appropriate quantity and metadata. Provides success or error messages
		 * based on the outcome.
		 *
		 * @throws Exception If the product or coupon is invalid, if the coupon action is missing,
		 *                   if the selected product is not eligible, or if the product addition fails.
		 */
		public function handle_coupon_product_addition() {
			try {
				// Verify nonce for security.
				check_ajax_referer( 'wc-sc-add-to-cart', 'security' );

				// Get data from AJAX request.
				$product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
				$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';

				if ( ! $product_id || empty( $coupon_code ) ) {
					throw new Exception( _x( 'Invalid product or coupon.', 'Error message when product or coupon is missing', 'woocommerce-smart-coupons' ) );
				}

				// Get coupon and check validity.
				$coupon = new WC_Coupon( $coupon_code );

				if ( ! $coupon instanceof WC_Coupon ) {
					throw new Exception( _x( 'Coupon not found.', 'Error message when the provided coupon is invalid', 'woocommerce-smart-coupons' ) );
				}

				if ( $this->is_wc_gte_30() ) {
					$coupon_id = ( ! empty( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
				} else {
					$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
				}

				if ( is_callable( array( $coupon, 'get_meta' ) ) ) {
					$no_of_selectable_product = $coupon->get_meta( 'wc_sc_no_of_selectable_product' );
				} else {
					$no_of_selectable_product = get_post_meta( $coupon_id, 'wc_sc_no_of_selectable_product', true );
				}

				// Get coupon actions.
				$coupon_actions = $this->get_coupon_actions( $coupon_code );
				if ( empty( $coupon_actions ) || ! is_array( $coupon_actions ) ) {
					throw new Exception( _x( 'Coupon action not found.', 'Error message when coupon actions are missing', 'woocommerce-smart-coupons' ) );
				}

				$search_key = array_search( $product_id, array_map( 'intval', wp_list_pluck( $coupon_actions, 'product_id' ) ), true );
				if ( false === $search_key ) {
					throw new Exception( _x( 'Selected product is not valid for this coupon.', 'Error message when a product does not match coupon restrictions', 'woocommerce-smart-coupons' ) );
				}

				$coupon_action = $coupon_actions[ $search_key ];

				$product = wc_get_product( $product_id );
				if ( ! $product ) {
					throw new Exception( _x( 'Product not found.', 'Error message when the product does not exist', 'woocommerce-smart-coupons' ) );
				}

				$product_data = $this->get_product_data( $product );
				$variation_id = ! empty( $product_data['variation_id'] ) ? absint( $product_data['variation_id'] ) : 0;
				$variation    = ! empty( $variation_id ) ? $product->get_variation_attributes() : array();

				$quantity = absint( $coupon_action['quantity'] );

				$cart_item_data = array(
					'wc_sc_product_source' => $coupon_code,
				);

				// If only one product should be selectable, check cart for existing coupon products.
				if ( 'yes' === $no_of_selectable_product ) {
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						if ( isset( $cart_item['wc_sc_product_source'] ) && $cart_item['wc_sc_product_source'] === $coupon_code ) {
							if ( absint( $cart_item['product_id'] ) === $product_id ) {
								throw new Exception(
									sprintf(
										/* translators: %s: Product title */
										_x( '"%s" is already in your cart.', 'Error message when product is already in cart', 'woocommerce-smart-coupons' ),
										esc_html( get_the_title( $product_id ) )
									)
								);
							} else {
								// Remove the previously added product.
								WC()->cart->remove_cart_item( $cart_item_key );
							}
						}
					}
				}

				// Add product to cart.
				$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
				if ( empty( $cart_item_key ) ) {
					throw new Exception( _x( 'Failed to add product to cart.', 'Error message when product addition fails', 'woocommerce-smart-coupons' ) );
				}

				// Add success notice in WC session.
				wc_add_notice(
					sprintf(
						/* translators: %s: Product title */
						_x( '"%s" has been added to your cart.', 'Success message when a product is added to the cart', 'woocommerce-smart-coupons' ),
						esc_html( get_the_title( $product_id ) )
					),
					'success'
				);

				wp_send_json_success();

			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
				wp_send_json_error( array( 'error' => $e->getMessage() ) );
			}
		}

		/**
		 * Loads and injects the necessary HTML, CSS, and JavaScript for the coupon action modal.
		 */
		public function sc_load_coupon_action_modal_js_css() {
			if ( ! is_cart() || ! is_checkout() && ( has_block( 'woocommerce/cart' ) || has_block( 'woocommerce/checkout' ) ) ) {
				return;
			}

			$applied_coupons = WC()->cart->get_applied_coupons();
			?>
				<!-- Custom Popup HTML -->
				<div id="sc_overlay" style="display:none;"></div>
				<div id="sc_coupon_popups_container"></div>

				<!-- Custom Popup Styles -->
				<style type="text/css">
					/* Overlay Styling */
					#sc_overlay {
						position: fixed;
						top: 0;
						left: 0;
						width: 100%;
						height: 100%;
						background: rgba(0, 0, 0, 0.5);
						z-index: 999;
					}

					/* Popup Styling */
					.sc_popup {
						position: fixed;
						top: 50%;
						left: 50%;
						transform: translate(-50%, -50%);
						z-index: 1000;
						background: #fff;
						padding: 1.25rem;
						border-radius: 0.5rem;
						box-shadow: 0px 0.25rem 0.9375rem rgba(0, 0, 0, 0.3);
						width: 25rem;
						max-width: 90%;
					}

					/* Title Styling */
					.sc_popup_title {
						margin: 0 0 0.625rem;
						font-size: 1.25rem;
						text-align: center;
					}

					/* Product List Styling */
					.sc_product_list {
						max-height: 12.5rem;
						overflow-y: auto;
						margin-bottom: 0.9375rem;
					}

					/* Product Item Styling */
					.sc_product_item {
						padding: 0.625rem;
						border-bottom: 1px solid #eee;
					}

					.sc_product_item label {
						display: flex;
						align-items: center;
						width: 100%;
						cursor: pointer;
						gap: 0.625rem;
						border: none;
					}

					/* Image Styling */
					.sc_product_item img {
						width: 3.125rem;
						height: 3.125rem;
						border: 1px solid #ddd;
						border-radius: 0.25rem;
					}

					/* Text Styling */
					.sc_product_item span {
						font-size: 1rem;
						line-height: 1.5;
						flex: 1;
					}

					/* Radio Button Styling */
					.sc_product_item input[type="radio"] {
						margin-right: 0.625rem;
					}

					/* Close Button Styling */
					.sc_close_popup {
						position: absolute;
						top: 0.5rem;
						right: 0.5rem;
						cursor: pointer;
						font-size: 1.5rem;
						color: #333;
					}

					/* Add to Cart Button Styling */
					.add_to_cart_button {
						display: block;
						width: auto;
						margin: 0 auto;
					}
				</style>
				<!-- Custom Popup JavaScript -->
				<script type="text/javascript">

					var $is_coupon_apply = false;

					jQuery(document).ready(function ($) {

						/**
						 * Show popup and overlay.
						 * @param {string} modalId - The modal ID to display.
						 */
						function showPopup(modalId) {
							if (!modalId) return;
							$('#sc_overlay, #' + modalId).fadeIn();
						}

						/**
						 * Close all popups.
						 */
						function closePopup() {
							$('#sc_overlay, .sc_popup').fadeOut();
						}

						/**
						 * Load coupon action data via AJAX.
						 * @param {string} couponCode - The coupon code (empty on page load).
						 */
						function loadCouponActionData(couponCode = '') {
							if (!couponCode) {
								$('#sc_coupon_popups_container').empty(); // Clear existing popups.
							}

							$.ajax({
								url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>", // Ensure this is properly localized in PHP.
								type: 'POST',
								dataType: 'json', // Expect JSON response.
								data: {
									security: '<?php echo esc_attr( wp_create_nonce( 'wc-sc-get-coupon-action-data' ) ); ?>',
									action: 'get_coupon_product_selection_html',
									coupon: couponCode
								},
								success: function (response) {
									if (response?.success && response?.data?.html) {
										$('#sc_coupon_popups_container').append(response.data.html); // Update popup container.
										if (couponCode) {
											showPopup('sc_popup_' + couponCode.toLocaleLowerCase());
										}
									}
								}
							});
						}

						<?php
						if ( ! empty( $applied_coupons ) ) {
							?>
							// Run on page load to get applied coupons (empty couponCode).
							loadCouponActionData('');
							<?php
						}
						?>

						// Listen for WooCommerce AJAX coupon application.
						$(document).ajaxComplete(function (event, xhr, settings) {
							if ( xhr?.status !== 200 ) {
								return;
							}

							// Handle coupon apply.
							if (settings?.url?.includes('apply_coupon')) {
								let responseText = xhr?.responseText;
								let urlParams = new URLSearchParams(settings.data);
								let couponCode = urlParams.get('coupon_code');

								if (couponCode) {
									couponCode = decodeURIComponent(couponCode); // Decode URL encoding.
								}

								if (responseText?.includes("<?php echo esc_js( __( 'Coupon code applied successfully.', 'woocommerce' ) ); ?>")) {
									$is_coupon_apply = true;
									loadCouponActionData(couponCode);
								}
							}

							// Handle coupon removal.
							if (settings?.url?.includes('remove_coupon')) {
								let urlParams = new URLSearchParams(settings.data);
								let removedCouponCode = urlParams.get('coupon');
								if (removedCouponCode) {
									removedCouponCode = decodeURIComponent(removedCouponCode);
									$('#sc_popup_' + removedCouponCode)?.remove(); // Remove the popup modal.
								}
							}
						});

						// Show popup when clicking "Select Product" button.
						$(document).on('click', '.sc_select_product', function (e) {
							e.preventDefault();
							let modalId = $(this).data('modal-id');
							showPopup(modalId);
						});

						// Close popup when clicking outside or close button.
						$(document).on('click', '.sc_close_popup, #sc_overlay', function () {
							closePopup();
						});

						// Handle product selection inside the popup.
						$(document).on('submit', '.sc_popup_product_selection', function (e) {
							e.preventDefault();

							let $form = $(this);
							let couponCode = $form.closest('.sc_popup').attr('id').replace('sc_popup_', '');
							let selectedProduct = $form.find('input[name="sc_product_' + couponCode + '"]:checked').val();

							if (!selectedProduct) {
								alert('<?php echo esc_js( _x( 'Please select a product.', 'Error Please select a product.', 'woocommerce-smart-coupons' ) ); ?>');
								return;
							}

							$.ajax({
								url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
								type: 'POST',
								dataType: 'json',
								data: {
									action: 'handle_coupon_product_addition',
									security: '<?php echo esc_attr( wp_create_nonce( 'wc-sc-add-to-cart' ) ); ?>',
									product_id: selectedProduct,
									coupon_code: couponCode
								},
								complete: function () {
									// Trigger WooCommerce cart and checkout updates.
									$(document.body)?.trigger('wc_update_cart');
									if ( $( '.woocommerce-checkout' ).length ) {
										$( document.body ).trigger( 'update_checkout' );
									}
									// Close popup after adding to cart.
									closePopup();
								},
								error: function (xhr, status, error) {
									alert('<?php echo esc_js( _x( 'Error processing request.', 'Error processing request', 'woocommerce-smart-coupons' ) ); ?>');
								}
							});
						});

						$( document.body ).on( 'updated_shipping_method', function(){
							$(document.body).trigger('wc_update_cart');
						});

						$( document.body ).on( 'wc_update_cart wc_fragments_refreshed', function(){
							if ( ! $is_coupon_apply ) {
								loadCouponActionData('');
							}
							$is_coupon_apply = false;
						});
					});
				</script>
			<?php
		}

	}

}

WC_SC_Coupon_Actions::get_instance();
