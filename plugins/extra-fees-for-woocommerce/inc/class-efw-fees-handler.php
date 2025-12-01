<?php
/**
 * Fee Handler.
 *
 * @package Extra Fees for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'EFW_Fees_Handler' ) ) {

	/**
	 * Class.
	 */
	class EFW_Fees_Handler {

		/**
		 * Fee Details.
		 *
		 * @var bool
		 * */
		protected static $combined_fee_details;

		/**
		 * Date filter.
		 *
		 * @var bool
		 * */
		protected static $date_filter;

		/**
		 * Product filter.
		 *
		 * @var bool
		 * */
		protected static $product_filter;

		/**
		 * Product Price Updated.
		 *
		 * @var bool
		 * */
		protected static $updated_price = array();

		/**
		 * User filter for Product.
		 *
		 * @var bool
		 * */
		protected static $user_filter_for_product;

		/**
		 * Class Initialization.
		 */
		public static function init() {
			// Add Custom Cart Item Data cart item data.
			add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_custom_cart_item_data' ), 999, 4 );
			// Add Product Fee in Cart/Checkout.
			add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'set_product_price' ), 9999, 1 );
			// Display Fee Details in Single Product Page.
			add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'fee_details_in_product_before_add_to_cart' ), 10 );
			// Display Fee Details in Single Product Page.
			add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'fee_details_in_product_after_add_to_cart' ), 10 );
			// Display Fee Details for Variation in Single Product Page.
			add_action( 'wp_ajax_efw_variation_notice', array( __CLASS__, 'efw_variation_notice' ) );
			// Display Fee Details for Variation in Single Product Page.
			add_action( 'wp_ajax_nopriv_efw_variation_notice', array( __CLASS__, 'efw_variation_notice' ) );
			// Set session for WooCommerce Subscription Compatability.
			add_action( 'wp_ajax_efw_automatic_subscription', array( __CLASS__, 'efw_automatic_payment' ) );
			// Set session for WooCommerce Subscription Compatability.
			add_action( 'wp_ajax_nopriv_efw_automatic_subscription', array( __CLASS__, 'efw_automatic_payment' ) );
			// Set session for WooCommerce Subscription Compatability.
			add_action( 'wp_ajax_efw_fee_in_pay_for_order', array( __CLASS__, 'efw_fee_in_pay_for_order' ) );
			// Set session for WooCommerce Subscription Compatability.
			add_action( 'wp_ajax_nopriv_efw_fee_in_pay_for_order', array( __CLASS__, 'efw_fee_in_pay_for_order' ) );
			// Change Add To Cart text in Shop Page.
			add_action( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'add_to_cart_text_in_shop' ), 10, 2 );
			// Change Add To Cart URL in Shop Page.
			add_action( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'add_to_cart_url_in_shop' ), 10, 2 );
			// Change Add To Cart URL in Shop Page when Ajax is enabled.
			add_filter( 'woocommerce_loop_add_to_cart_args', array( __CLASS__, 'add_to_cart_url_in_shop_for_ajax' ), 10, 2 );
			// Display cart item data.
			add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'add_custom_item_data' ), 10, 2 );
			// Add Gateway Fee in Cart/Checkout.
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'gateway_fee' ), 1001 );
			// Add Gateway Fee in Cart/Checkout.
			add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'register_wc_blocks' ), 10 );
			// Add Order Total Fee in Cart/Checkout.
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'order_total_fee' ), 10 );
			// May be add shipping fee.
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'may_be_add_shipping_fee' ), 10 );
			// Add Product Fee in Cart/Checkout.
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'may_be_add_product_fee' ), 10 );
			// Add Additional Fee in Cart/Checkout.
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_additional_fee' ), 10 );
			// Add Combined Fee in Cart/Checkout.
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_combined_fee' ), 10 );
			// Update Fee Details in Order Meta.
			add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'update_fee_details' ), 999 );
			// Update Fee Details in Order Meta.
			add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'update_fee_details' ), 999 );
			// Add Gateway Fee in Cart/Checkout.
			add_action( 'woocommerce_before_pay_action', array( __CLASS__, 'update_fee_details_in_pay_for_order' ), 1001 );
			// Display Variation Price.
			add_action( 'woocommerce_get_price_html', array( __CLASS__, 'display_variation_price' ), 10, 2 );
			// Add Product in Admin Post Table.
			add_filter( 'manage_edit-product_columns', array( __CLASS__, 'product_fee_columns' ), 12 );
			// Add Product Data in Admin Post Table.
			add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'product_fee_content' ), 12, 2 );
			// Render fee details in shop page.
			add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'render_fee_details_shop' ), 20 );
			// Render Fee Description in Cart totals fee html.
			add_filter( 'woocommerce_cart_totals_fee_html', array( __CLASS__, 'render_fee_description' ), 999, 2 );
			// Update Product Fee for Manual Order Creation.
			add_action( 'woocommerce_admin_order_item_values', array( __CLASS__, 'maybe_update_product_price' ), 10, 3 );
			// Update Product Fee for Manual Order Creation.
			add_action( 'woocommerce_order_before_calculate_totals', array( __CLASS__, 'maybe_update_product_fee' ), 50, 2 );
			// Update Gateway Fee for Manual Order Creation.
			add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'maybe_update_gateway_fee' ), 50, 2 );
			// Update Order Fee for Manual Order Creation.
			add_action( 'woocommerce_order_before_calculate_totals', array( __CLASS__, 'maybe_update_order_fee' ), 50, 2 );
			// Update Shipping Fee for Manual Order Creation.
			add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'maybe_update_shipping_fee' ), 50, 2 );
			// Update Fee Details in Order Meta for Manual Order.
			add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'update_fee_details_for_manual_order' ), 50, 2 );
			// Remove Order Item Meta key.
			add_filter('woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hide_order_item_meta_key' ), 10, 2);
			// Update order meta.
			add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'update_order_item' ), 10, 4 );
		}

		/**
		 * Register & Apply Gateway Fee for WooCommerce Blocks
		 *
		 * @since 5.5.0
		 */
		public static function register_wc_blocks() {
			woocommerce_store_api_register_update_callback(
				array( 
					'namespace' => 'efw-add-gateway-fee',
					'callback'  => function ( $data ) {
						self::add_gateway_fee_for_wc_blocks( $data );
					},
				)
			);
		}

		/**
		 * Add Gateway Fee for WooCommerce Blocks
		 *
		 * @since 5.5.0
		 */
		public static function add_gateway_fee_for_wc_blocks( $data ) {

			if ('add-fee' != $data['action']) {
				return;
			}

			if (empty($data['gateway_id'])) {
				WC()->session->__unset( 'efw_gateway_id' );
				return;
			}

			WC()->session->set( 'efw_gateway_id', $data['gateway_id'] );
		}

		/**
		 * Update Product Price for Manual Order.
		 *
		 * @param int    $product_object Product Object.
		 * @param object $item Item Object.
		 * @param int    $item_id Item ID.
		 * @since 4.4.0
		 */
		public static function maybe_update_product_price( $product_object, $item, $item_id ) {
			if ( ! is_admin() || ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				return;
			}

			if ( ! is_object( $product_object ) ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if (( 'yes' == $item->get_meta('_efw_product_fee_awarded') )) {
				return;
			}

			if ( 'yes' == get_option( 'efw_productfee_qty_restriction_enabled' ) || 'yes' == get_option( 'efw_productfee_tax_setup' ) ) {
				return;
			}

			$product_id = $product_object->get_id();
			$item_qty   = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $item->get_quantity();
			$price      = $item->get_total() / $item_qty;

			$product_fee = self::product_fee( $product_id, $price, 0 );
			$signup_fee  = efw_get_wc_signup_fee( $item );

			if ( empty( $product_fee ) && empty( $signup_fee ) && in_array( $item->get_type(), array( 'subscription', 'subscription_variation' ) ) ) {
				return;
			}

			if ( empty( $product_fee ) ) {
				return;
			}

			$product_fee = self::rule_fees( $product_id, $product_fee, $price, $item_qty );

			// Only for subscription products.
			if ( in_array( $item->get_type(), array( 'subscription', 'subscription_variation' ) ) ) {
				$product_fee = self::product_fee( $product_id, $signup_fee, 0 );
				// Change subscription Sign up fee.
				$item->update_meta_data( '_subscription_sign_up_fee', $product_fee );
			}

			$key_name                 = efw_get_fee_text( $product_id );
			$meta_data                = $item->get_meta_data();
			$product_fee_meta_updated = false;
			foreach ( $meta_data as $meta ) {
				if ( $meta->key === $key_name ) {
					$product_fee_meta_updated = true;
				}
			}

			if ( ! $product_fee_meta_updated ) {

				// Set the new price for the product.
				$item->set_total( $product_fee * $item_qty );
				$item->save();

				self::maybe_update_product_fee_meta( $item_id, $product_id, $item_qty, 0 );
			}
		}

		/**
		 * Update Product Fee for Manual Order.
		 *
		 * @param int    $order_id Order ID.
		 * @param object $order Order object.
		 * @since 4.9.0
		 */
		public static function maybe_update_product_fee( $add_taxes, $order ) {
			if ( 'auto-draft' != $order->get_status() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_qty_restriction_enabled' ) && 'yes' !== get_option( 'efw_productfee_tax_setup' ) ) {
				return;
			}

			$total_product_fee = array();

			if ( ! self::validate_coupon( $order ) ) {
				return;
			}

			foreach ( $order->get_items() as $item_id => $cart_value ) {
				$product_id = ! empty( $cart_value['variation_id'] ) ? $cart_value['variation_id'] : $cart_value['product_id'];
				$product    = wc_get_product( $product_id );

				if ( ! is_object( $product ) ) {
					continue;
				}

				$qty = isset( $cart_value['qty'] ) ? $cart_value['qty'] : 1;
				$qty = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $qty;
				$price = wc_get_price_excluding_tax( $product ) + efw_get_wc_signup_fee( $product );
				$product_fee = self::product_fee( $product_id, $price, $order->get_user_id(), 'fee' );

				if ( empty( $product_fee ) ) {
					continue;
				}

				$total_product_fee[] = $product_fee * $qty;

				self::maybe_update_product_fee_meta( $item_id, $product_id, $qty, $order->get_user_id() );
			}

			$fee_value = array_sum( $total_product_fee );

			if ( empty( $fee_value ) ) {
				return;
			}
			
			$tax_class = get_option( 'efw_productfee_tax_class', 'standard' );

			$taxable = ( 'not-required' === $tax_class ) ? 'none' : 'taxable';
			$fee_text = ! empty( get_option( 'efw_productfee_overall_fee_text' ) ) ? get_option( 'efw_productfee_overall_fee_text' ) : 'Product Fee';

			$fees = $order->get_fees();
			if ( efw_check_is_array( $fees ) ) {
				foreach ( $fees as $item_id => $item ) {
					if ( $item->get_name() === $fee_text ) {
						$item->set_total( $fee_value );
						$item->save();
					}
				}
			} else {
				$item_fee = new WC_Order_Item_Fee();

				$item_fee->set_id( 'efw_product_fee' );
				$item_fee->set_name( $fee_text );
				$item_fee->set_amount( $fee_value );
				if ( ! in_array( $tax_class, array( 'standard', 'not-required' ) ) ) {
					$item_fee->set_tax_class( $tax_class );
				}
				$item_fee->set_tax_status( $taxable );
				$item_fee->set_total( $fee_value );
	
				// Add Gateway fee to the order.
				$order->add_item( $item_fee );
			}

			$order->save();
		}

		/**
		 * Update Gateway Fee for Manual Order.
		 *
		 * @param int    $order_id Order ID.
		 * @param object $order Order object.
		 * @since 4.9.0
		 */
		public static function maybe_update_gateway_fee( $order_id, $order ) {
			if ( 'yes' !== get_option( 'efw_gatewayfee_enable' ) ) {
				return;
			}

			$order      = wc_get_order( $order_id );
			$gateway_id = $order->get_payment_method();
			$billing_country = is_object( WC()->customer ) ? WC()->customer->get_billing_country() : $order->get_billing_country();
			$billing_state = is_object( WC()->customer ) ? WC()->customer->get_billing_state() : $order->get_billing_state();
			if ('1' == get_option('efw_add_fee_based_on_' . $gateway_id)) {
				$allowed_countries = get_option( 'efw_include_countries_for_' . $gateway_id );
				if ( ! empty( $allowed_countries ) && ! in_array( $billing_country, $allowed_countries ) ) {
					return;
				}
			} else {
				$allowed_states = get_option( 'efw_include_states_for_' . $gateway_id );
				$selected_state = efw_get_selected_state($billing_country, $billing_state);
				
				if ( ! empty( $allowed_states ) && ! in_array( $selected_state, $allowed_states ) ) {
					return;
				}
			}

			$value    = get_option( 'efw_fee_text_for_' . $gateway_id );
			$fee_text = efw_get_custom_field_translate_string( 'efw_fee_text_for_' . $gateway_id, $value );

			if ( ! self::check_if_fee_already_exists( $order, $fee_text ) ) {
				return;
			}

			// Return if the gateway fee is disabled.
			if ( 'yes' !== get_option( 'efw_enable_fee_for_' . $gateway_id ) ) {
				return;
			}

			self::$date_filter = self::validate_date( $gateway_id );
			if ( ! self::$date_filter ) {
				return;
			}

			if ( ! EFW_Fee_Validation::is_valid( 'gateway', $gateway_id, $order->get_items(), $order->get_user_id(), $order ) ) {
				return;
			}

			$fee_details = array(
				'type'            => 'gateway',
				'fee_type'        => get_option( 'efw_fee_type_for_' . $gateway_id ),
				'fixed_value'     => get_option( 'efw_fixed_value_for_' . $gateway_id ),
				'percentage_type' => get_option( 'efw_percentage_type_for_' . $gateway_id ),
				'percentage_fee_type' => get_option( 'efw_percentage_fee_type_for_' . $gateway_id ),
				'add_fixed_fee_on' => get_option( 'efw_add_fixed_for_' . $gateway_id ),
				'cart_sub_total'  => get_option( 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ),
				'min_fee'         => get_option( 'efw_min_fee_for_' . $gateway_id ),
				'max_fee'         => get_option( 'efw_max_fee_for_' . $gateway_id ),
				'min_sub_total'   => get_option( 'efw_min_subtotal_for_' . $gateway_id ),
				'max_sub_total'   => get_option( 'efw_max_subtotal_for_' . $gateway_id ),
			);

			$fee_value = self::fee_value( $fee_details, $order, 'manual' );

			/**
			 * Hook:efw_gateway_fee_after_calculate.
			 *
			 * @since 1.0
			 */
			$fee_value = apply_filters( 'efw_gateway_fee_after_calculate', $fee_value );

			if ( empty( $fee_value ) ) {
				return;
			}

			$tax_class = get_option( 'efw_tax_class_for_' . $gateway_id );

			$taxable = ( 'not-required' === $tax_class ) ? 'none' : 'taxable';

			$item_fee = new WC_Order_Item_Fee();

			$item_fee->set_id( 'efw_gateway_fee' );
			$item_fee->set_name( $fee_text );
			$item_fee->set_amount( $fee_value );
			if ( ! in_array( $tax_class, array( 'standard', 'not-required' ) ) ) {
				$item_fee->set_tax_class( $tax_class );
			}
			$item_fee->set_tax_status( $taxable );
			$item_fee->set_total( $fee_value );

			// Add Gateway fee to the order.
			$order->add_item( $item_fee );

			$order->save();
		}

		/**
		 * Update Order Fee for Manual Order.
		 *
		 * @param int    $order_id Order ID.
		 * @param object $order Order object.
		 * @since 4.9.0
		 */
		public static function maybe_update_order_fee( $order_id, $order ) {
			if ( 'auto-draft' != $order->get_status() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_ordertotalfee_enable' ) ) {
				return;
			}

			$fee_text = get_option( 'efw_ordertotalfee_fee_text' );

			if ( ! self::check_if_fee_already_exists( $order, $fee_text ) ) {
				return;
			}

			$user_filter_data = array(
				'user_filter_type'  => get_option( 'efw_ordertotalfee_user_filter' ),
				'include_users'     => get_option( 'efw_ordertotalfee_include_users' ),
				'exclude_users'     => get_option( 'efw_ordertotalfee_exclude_users' ),
				'include_user_role' => get_option( 'efw_ordertotalfee_include_userrole' ),
				'exclude_user_role' => get_option( 'efw_ordertotalfee_exclude_userrole' ),
			);

			if ( ! EFW_Fee_Validation::is_valid( 'order', '', $order->get_items(), $order->get_user_id(), $order ) ) {
				return;
			}

			$excluded_shipping = get_option( 'efw_ordertotalfee_excluded_shipping' );
			$selected_shipping = efw_get_shipping_method_from_order( $order->get_items( 'shipping' ) );
			$blocked_shipping  = array_intersect( $excluded_shipping, $selected_shipping );

			if ( efw_check_is_array( $blocked_shipping ) ) {
				return;
			}

			$fee_details = array(
				'type'              => 'order',
				'fee_type'          => get_option( 'efw_ordertotalfee_fee_type' ),
				'fixed_value'       => get_option( 'efw_ordertotalfee_fixed_value' ),
				'cart_sub_total'    => get_option( 'efw_ordertotalfee_cart_subtotal_percentage' ),
				'min_fee'           => 0,
				'min_sub_total'     => get_option( 'efw_ordertotalfee_min_sub_total' ),
				'max_sub_total'     => get_option( 'efw_ordertotalfee_max_sub_total' ),
				'fee_configuration' => get_option( 'efw_ordertotalfee_fee_configuration', '1' ),
			);

			$tax_class = get_option( 'efw_ordertotalfee_tax_class' );

			$fee_value = self::fee_value( $fee_details, $order, 'manual' );
			/**
			 * Hook:efw_order_fee_after_calculate.
			 *
			 * @since 1.0
			 */
			$fee_value = apply_filters( 'efw_order_fee_after_calculate', $fee_value );

			if ( empty( $fee_value ) ) {
				return;
			}

			$taxable = ( 'not-required' === $tax_class ) ? 'none' : 'taxable';

			$item_fee = new WC_Order_Item_Fee();

			$item_fee->set_id( 'efw_order_fee' );
			$item_fee->set_name( $fee_text );
			$item_fee->set_amount( $fee_value );
			if ( ! in_array( $tax_class, array( 'standard', 'not-required' ) ) ) {
				$item_fee->set_tax_class( $tax_class );
			}
			$item_fee->set_tax_status( $taxable );
			$item_fee->set_total( $fee_value );

			// Add Gateway fee to the order.
			$order->add_item( $item_fee );

			$order->save();
		}

		/**
		 * Update Shipping Fee for Manual Order.
		 *
		 * @param int    $order_id Order ID.
		 * @param object $order Order object.
		 * @since 4.9.0
		 */
		public static function maybe_update_shipping_fee( $order_id, $order ) {
			if ( 'yes' !== get_option( 'efw_shippingfee_enable' ) ) {
				return;
			}

			$order = wc_get_order( $order_id );         
			if ( ! self::validate_coupon( $order ) ) {
				return;
			}

			foreach ( $order->get_items( 'shipping' ) as $shipping_method ) {

				$chosen_shipping_method_id = $shipping_method->get_method_id();
				if ( 'on' !== get_option( 'efw_enable_' . $chosen_shipping_method_id ) ) {
					return;
				}

				$fee_text = get_option( 'efw_shipping_fee_text_' . $chosen_shipping_method_id );
				if ( ! self::check_if_fee_already_exists( $order, $fee_text ) ) {
					return;
				}

				$fee_details = array(
					'type'            => 'shipping',
					'fee_type'        => get_option( 'efw_shipping_fee_type_' . $chosen_shipping_method_id ),
					'fixed_value'     => get_option( 'efw_shipping_fixed_value_' . $chosen_shipping_method_id ),
					'percentage_type' => get_option( 'efw_percentage_based_on_' . $chosen_shipping_method_id ),
					'percentage_fee_type' => get_option( 'efw_percentage_fee_type_for_' . $chosen_shipping_method_id ),
					'add_fixed_fee_on' => get_option( 'efw_add_fixed_for_' . $chosen_shipping_method_id ),
					'cart_sub_total'  => get_option( 'efw_shipping_percentage_value_' . $chosen_shipping_method_id ),
					'min_fee'         => get_option( 'efw_shipping_minimum_fee_value_' . $chosen_shipping_method_id ),
					'max_fee'         => get_option( 'efw_shipping_maximum_fee_value_' . $chosen_shipping_method_id ),
					'min_sub_total'   => get_option( 'efw_shipping_fee_minimum_restriction_value_' . $chosen_shipping_method_id ),
					'max_sub_total'   => get_option( 'efw_shipping_fee_maximum_restriction_value_' . $chosen_shipping_method_id ),
				);

				$tax_class = str_replace( '_', '-', get_option( 'efw_shipping_tax_class_' . $chosen_shipping_method_id ) );

				$fee_value = self::fee_value( $fee_details, $order, 'manual' );
				/**
				 * Hook:efw_shipping_fee_after_calculate.
				 *
				 * @since 1.0
				 */
				$fee_value = apply_filters( 'efw_shipping_fee_after_calculate', $fee_value );
				if ( empty( $fee_value ) ) {
					return;
				}

				$taxable = ( 'not-required' === $tax_class ) ? 'none' : 'taxable';

				$item_fee = new WC_Order_Item_Fee();

				$item_fee->set_id( 'efw_shipping_fee' );
				$item_fee->set_name( $fee_text );
				$item_fee->set_amount( $fee_value );
				if ( ! in_array( $tax_class, array( 'standard', 'not-required' ) ) ) {
					$item_fee->set_tax_class( $tax_class );
				}
				$item_fee->set_tax_status( $taxable );
				$item_fee->set_total( $fee_value );

				// Add Shipping fee to the order.
				$order->add_item( $item_fee );

				$order->save();
			}
		}

		/**
		 * Check if Fee Already exists
		 *
		 * @since 4.9.0
		 * @param int    $order Item ID.
		 * @param string $fee_text Fee Text.
		 */
		public static function check_if_fee_already_exists( $order, $fee_text ) {
			$return = true;
			foreach ( $order->get_items( 'fee' ) as $item_id => $item_fee ) {
				if ( $fee_text === $item_fee->get_name() ) {
					$return = false;
					break;
				}
			}

			return $return;
		}

		/**
		 * Maybe add product fee meta
		 *
		 * @since 4.4.0
		 * @param int $item_id Item ID.
		 * @param int $product_id Product ID.
		 * @param int $qty Quantity.
		 */
		public static function maybe_update_product_fee_meta( $item_id, $product_id, $qty, $user_id ) {
			$product = wc_get_product( $product_id );

			if ( ! is_object( $product ) ) {
				return;
			}

			$price       = wc_get_price_excluding_tax( $product ) + efw_get_wc_signup_fee( $product );
			$product_fee = self::product_fee( $product_id, $price, $user_id, 'fee' );

			if ( ! $product_fee ) {
				return;
			}

			$key_name = efw_get_fee_text( $product_id );

			wc_update_order_item_meta( $item_id, $key_name, wc_price( $product_fee * $qty ) );

			$product_fee         = self::rule_fees( $product_id, $product_fee, $price, $qty );
			$total_product_fee[] = $product_fee * $qty;
			$rule_fee_texts      = efw_get_rule_fee_text( $product_id, $price, $qty );

			if ( efw_check_is_array( $rule_fee_texts ) ) {
				foreach ( $rule_fee_texts as $rule_id => $rule_fee_value ) {
					$rule_object = efw_get_fee_rule( $rule_id );
					if ( ! is_object( $rule_object ) ) {
						continue;
					}

					$rule_fee_value = $rule_fee_value * $qty;
					wc_update_order_item_meta( $item_id, $rule_object->get_fee_text(), wc_price( $rule_fee_value ) );
				}
			}
		}

		/**
		 * Check if it was automatic payment.
		 */
		public static function efw_automatic_payment() {
			check_ajax_referer( 'efw-fee-nonce', 'efw_security' );

			try {
				$gateway_id         = isset( $_REQUEST['gatewayid'] ) ? filter_input( INPUT_POST, 'gatewayid' ) : '';
				$automatic_payments = efw_get_automatic_payment_ids();
				if ( 'yes' === get_option( 'efw_enable_fee_for_' . $gateway_id ) && in_array( $gateway_id, $automatic_payments ) ) {
					WC()->session->set( 'efw_automatic_payment', 'yes' );
				} else {
					WC()->session->__unset( 'efw_automatic_payment' );
				}

				wp_send_json_success();
			} catch ( exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Check if it was automatic payment.
		 */
		public static function efw_fee_in_pay_for_order() {
			check_ajax_referer( 'efw-fee-nonce', 'efw_security' );

			try {
				$response = '';
				$order_id = isset($_REQUEST['order_id']) ? absint($_REQUEST['order_id']) : 0;
				if ( isset( $_REQUEST['pay_for_order'] ) && ! empty( $order_id ) ) {
					ob_start();
					$gateway_id = isset( $_REQUEST['gatewayid'] ) ? filter_input( INPUT_POST, 'gatewayid' ) : '';
					
					self::add_fee_in_pay_for_order_page( $order_id, $gateway_id );

					wc_get_template( 'checkout/form-pay.php', array(
							'order'              => wc_get_order( $order_id ),
							'available_gateways' => WC()->payment_gateways->get_available_payment_gateways(),
							/**
							 * This hook is used to alter pay order button text.
							 *
							 * @since 1.0.0
							 */
							'order_button_text'  => apply_filters( 'woocommerce_pay_order_button_text', __( 'Pay for order', 'extra-fees-for-woocommerce' ) ),
					) );

					$response = ob_get_contents();
					ob_end_clean();

					$response = self::get_pay_for_order_table($response);
				}

				wp_send_json_success(array( 'pay_for_order' => $response ));
			} catch ( exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get Pay for Order Table.
		 */
		public static function get_pay_for_order_table( $form_element ) {
			$start = stripos( $form_element, '<table' );

			if ( ! $start ) {
				return $form_element;
			}

			$new_element = substr( $form_element, $start );

			$end = stripos( $new_element, '</table>' );

			$table = ( ! $end ) ? $new_element : substr( $new_element, 0, $end );

			$table .= '</table>';

			return $table;
		}

		/**
		 * Add Fee in Pay for Order.
		 */
		public static function add_fee_in_pay_for_order_page( $order_id, $gateway_id ) {
			$order = wc_get_order( $order_id );
			$applied_fee = $order->get_meta('efw_applied_fee_id');
			if ( ! empty( $applied_fee ) ) {
				$order->remove_item( $applied_fee );
				$order->calculate_totals();
				$order->save();
			}

			$value    = get_option( 'efw_fee_text_for_' . $gateway_id );
			$fee_text = efw_get_custom_field_translate_string( 'efw_fee_text_for_' . $gateway_id, $value );
			if ( ! self::check_if_fee_already_exists( $order, $fee_text ) ) {
				return ;
			}

			// Return if the gateway fee is disabled.
			if ( 'yes' !== get_option( 'efw_enable_fee_for_' . $gateway_id ) ) {
				return ;
			}

			$billing_country = is_object( WC()->customer ) ? WC()->customer->get_billing_country() : $order->get_billing_country();
			$billing_state = is_object( WC()->customer ) ? WC()->customer->get_billing_state() : $order->get_billing_state();
			if ('1' == get_option('efw_add_fee_based_on_' . $gateway_id)) {
				$allowed_countries = get_option( 'efw_include_countries_for_' . $gateway_id );
				if ( ! empty( $allowed_countries ) && ! in_array( $billing_country, $allowed_countries ) ) {
					return ;
				}
			} else {
				$allowed_states = get_option( 'efw_include_states_for_' . $gateway_id );
				$selected_state = efw_get_selected_state($billing_country, $billing_state);
				
				if ( ! empty( $allowed_states ) && ! in_array( $selected_state, $allowed_states ) ) {
					return ;
				}
			}

			self::$date_filter = self::validate_date( $gateway_id );
			if ( ! self::$date_filter ) {
				return ;
			}

			if ( ! EFW_Fee_Validation::is_valid( 'gateway', $gateway_id, $order->get_items(), $order->get_user_id(), $order ) ) {
				return ;
			}

			$fee_details = array(
				'type'            => 'gateway',
				'fee_type'        => get_option( 'efw_fee_type_for_' . $gateway_id ),
				'fixed_value'     => get_option( 'efw_fixed_value_for_' . $gateway_id ),
				'percentage_type' => get_option( 'efw_percentage_type_for_' . $gateway_id ),
				'percentage_fee_type' => get_option( 'efw_percentage_fee_type_for_' . $gateway_id ),
				'add_fixed_fee_on' => get_option( 'efw_add_fixed_for_' . $gateway_id ),
				'cart_sub_total'  => get_option( 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ),
				'min_fee'         => get_option( 'efw_min_fee_for_' . $gateway_id ),
				'max_fee'         => get_option( 'efw_max_fee_for_' . $gateway_id ),
				'min_sub_total'   => get_option( 'efw_min_subtotal_for_' . $gateway_id ),
				'max_sub_total'   => get_option( 'efw_max_subtotal_for_' . $gateway_id ),
			);

			$fee_value = self::fee_value( $fee_details, $order, 'manual' );

			/**
			 * Hook:efw_gateway_fee_after_calculate.
			 *
			 * @since 1.0
			 */
			$fee_value = apply_filters( 'efw_gateway_fee_after_calculate', $fee_value );

			if ( empty( $fee_value ) ) {
				return ;
			}

			$tax_class = get_option( 'efw_tax_class_for_' . $gateway_id );
			$taxable = ( 'not-required' === $tax_class ) ? 'none' : 'taxable';

			$item_fee = new WC_Order_Item_Fee();

			$item_fee->set_id( 'efw_gateway_fee' );
			$item_fee->set_name( $fee_text );
			$item_fee->set_amount( $fee_value );
			if ( ! in_array( $tax_class, array( 'standard', 'not-required' ) ) ) {
				$item_fee->set_tax_class( $tax_class );
			}
			$item_fee->set_tax_status( $taxable );
			$item_fee->set_total( $fee_value );
			$item_fee->save();

			//Add Gateway fee to the order.
			$order->add_item( $item_fee );

			$order->update_meta_data('efw_applied_fee_id', $item_fee->get_id());

			$order->calculate_totals();
			$order->save();
		}

		/**
		 * Display Fee Details for Variation in Single Product Page.
		 */
		public static function efw_variation_notice() {
			check_ajax_referer( 'efw-fee-nonce', 'efw_security' );

			try {
				$variation_id = isset( $_REQUEST['variationid'] ) ? absint( $_REQUEST['variationid'] ) : 0;

				$product_obj = wc_get_product( $variation_id );
				$html        = '';
				if ( is_object( $product_obj ) ) {

					$price = wc_get_price_to_display( $product_obj ) + efw_get_wc_signup_fee( $product_obj, $variation_id );

					$product_fee = self::product_fee( $variation_id, $price, get_current_user_id(), 'fee' );

					$total_payable_amount = self::rule_fees( $variation_id, $product_fee, $price, '1' );
					
					if ('yes' == get_option('efw_productfee_show_product_fee_in_single_product')) {
						$fee_text = efw_get_fee_text( $variation_id );

						$rule_fee_text = efw_get_rule_fee_text( $variation_id, $price, '1' ) ;
	
						if ( ! empty( $product_fee ) ) {
							ob_start();
							efw_get_template(
								'single/product-fee-notice.php',
								array(
									'product'              => $product_obj,
									'product_fee'          => $product_fee,
									'fee_text'             => $fee_text,
									'rule_fee_texts'       => $rule_fee_text,
									'total_payable_amount' => $total_payable_amount,
									'price'                => $price,
								)
							);
							$html = ob_get_clean();
						} else {
							$html = wc_price( $price );
						}
					} elseif ('add-to-price' == get_option('efw_productfee_show_product_fee_in_single_product')) {
						$html = wc_price( $total_payable_amount + $price );
					}
				}

				wp_send_json_success( array( 'html' => $html ) );
			} catch ( exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Display Fee details in Single Product Page.
		 */
		public static function fee_details_in_product_before_add_to_cart() {
			if ('yes' == get_option('efw_productfee_info_position_in_product_page')) {
				return;
			}

			self::fee_details_in_product();
		}

		/**
		 * Display Fee details in Single Product Page.
		 */
		public static function fee_details_in_product_after_add_to_cart() {
			if ('yes' != get_option('efw_productfee_info_position_in_product_page')) {
				return;
			}

			self::fee_details_in_product();
		}

		/**
		 * Display Fee details in Single Product Page.
		 */
		public static function fee_details_in_product() {
			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ('no' == get_option('efw_productfee_show_product_fee_in_single_product') || 'add-to-price' == get_option('efw_productfee_show_product_fee_in_single_product')) {
				return;
			}

			global $post, $product;

			$product_obj = wc_get_product( $post->ID );

			if ( ! is_object( $product_obj ) ) {
				return;
			}

			$price = wc_get_price_to_display( $product_obj ) + efw_get_wc_signup_fee( $product_obj );

			$product_fee = self::product_fee( $post->ID, $price, get_current_user_id(), 'fee' );

			$total_payable_amount = self::rule_fees( $post->ID, $product_fee, $price, '1' );

			if ( empty( $product_fee ) ) {
				return;
			}

			if ( 'variable' === $product_obj->get_type() || 'variable-subscription' === $product_obj->get_type() ) {
				efw_get_template('single/variation-notice.php');
				return;
			}

			$fee_text = efw_get_fee_text( $post->ID );

			$rule_fee_text = efw_get_rule_fee_text( $post->ID, $price, '1' ) ;

			efw_get_template(
				'single/product-fee-notice.php',
				array(
					'product'              => $product_obj,
					'product_fee'          => $product_fee,
					'fee_text'             => $fee_text,
					'rule_fee_texts'       => $rule_fee_text,
					'price'                => $price,
					'total_payable_amount' => $total_payable_amount,
				)
			);
		}

		/**
		 * Change Add To Cart text in Shop Page.
		 *
		 * @param string $text Cart Text.
		 * @param object $product_obj Product object.
		 */
		public static function add_to_cart_text_in_shop( $text, $product_obj ) {

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return $text;
			}

			if ( 'no' !== get_option( 'efw_productfee_show_product_fee_shop' ) ) {
				return $text;
			}

			if ( 'variable' === $product_obj->get_type() ) {
				return $text;
			}

			$post_id = $product_obj->get_id();

			$price = (float) wc_get_price_including_tax( $product_obj ) + efw_get_wc_signup_fee( $product_obj );

			$product_fee = self::product_fee( $post_id, $price, get_current_user_id(), 'fee' );

			return empty( $product_fee ) ? $text : get_option( 'efw_productfee_add_to_cart_label', 'View Final Value' );
		}

		/**
		 * Change Add To Cart URL in Shop Page.
		 *
		 * @param string $url Add to Cart URL.
		 * @param object $product_obj Product object.
		 */
		public static function add_to_cart_url_in_shop( $url, $product_obj ) {

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return $url;
			}

			if ( 'no' !== get_option( 'efw_productfee_show_product_fee_shop' ) ) {
				return $url;
			}

			$post_id = $product_obj->get_id();

			$price = (float) wc_get_price_including_tax( $product_obj ) + efw_get_wc_signup_fee( $product_obj );

			$product_fee = self::product_fee( $post_id, $price, get_current_user_id(), 'fee' );

			return empty( $product_fee ) ? $url : get_the_permalink();
		}

		/**
		 * Change Add To Cart URL in Shop Page when Ajax enabled.
		 *
		 * @param array  $args Add to Cart class name.
		 * @param object $product Product object.
		 */
		public static function add_to_cart_url_in_shop_for_ajax( $args, $product ) {

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return $args;
			}

			if ( 'no' !== get_option( 'efw_productfee_show_product_fee_shop' ) ) {
				return $args;
			}

			$post_id = $product->get_id();

			$price = (float) wc_get_price_including_tax( $product ) + efw_get_wc_signup_fee( $product );

			$product_fee = self::product_fee( $post_id, $price, get_current_user_id(), 'fee' );

			$args['class'] = empty( $product_fee ) ? $args['class'] : str_replace( 'ajax_add_to_cart', '', $args['class'] );

			return $args;
		}

		/**
		 *  Add custom item data
		 *
		 * @param array $item_data Cart Item Data.
		 * @param array $cart_item Cart Items.
		 */
		public static function add_custom_item_data( $item_data, $cart_item ) {

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return $item_data;
			}

			if (!self::validate_coupon()) {
				return $item_data;
			}

			$product_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'];

			$product = wc_get_product( $product_id );

			if (isset($cart_item['booking']['_cost'])) {
				if ('yes' == get_option('efw_productfee_qty_restriction_enabled')) {
					$price = $product->get_price();
				} else {
					$price = $cart_item['booking']['_cost'];
				}
			} else {
				$price = isset( $cart_item['nyp'] ) ? $cart_item['nyp'] : (float) $product->get_price() + efw_get_wc_signup_fee( $product );
			}

			$booking_data = isset($cart_item['booking']) ? $cart_item['booking'] : array();

			$product_fee = self::product_fee( $product_id, $price, get_current_user_id(), 'fee', $booking_data );

			if ( empty( $product_fee ) ) {
				return $item_data;
			}

			$qty = isset( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;
			$qty = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $qty;

			$product_fee = ( $product_fee * $qty );
			/**
			 * Hook:efw_product_fee_before_custom_item_data.
			 *
			 * @since 1.0
			 */
			$product_fee = apply_filters( 'efw_product_fee_before_custom_item_data', $product_fee, $cart_item );

			if ( ! is_numeric( $product_fee ) ) {
				return $item_data;
			}

			if ( empty ( $product_fee ) ) {
				return $item_data;
			}

			$key_name        = efw_get_fee_text( $product_id );
			$fee_description = efw_get_fee_description( $product->get_id() );
			if ( $fee_description ) {
				ob_start();
				efw_get_template(
					'popup/product-fee/fee-text-hyperlink.php',
					array(
						'fee_text' => $key_name,
						'product'  => $product,
					)
				);
				$key_name = ob_get_contents();
				ob_end_clean();
			}

			if ( is_cart() || is_checkout() ) {
				if (( 'no' == get_option('efw_productfee_hide_product_fee_in_cart') )) {
					$item_data[] = array(
						'key'    => $key_name,
						'value' => wc_price( $product_fee ),
					);
				}
			} else {
				$item_data[] = array(
					'key'    => $key_name,
					'value' => wc_price( $product_fee ),
				);
			}

			$rule_fee_texts = efw_get_rule_fee_text( $product_id, $price, $cart_item['quantity'], $booking_data ) ;

			if ( efw_check_is_array( $rule_fee_texts ) ) {
				foreach ( $rule_fee_texts as $rule_id => $rule_fee_value ) {
					$rule_object = efw_get_fee_rule( $rule_id );
					if ( ! is_object( $rule_object ) ) {
						continue;
					}

					$rule_fee_text    = $rule_object->get_fee_text();
					$fee_descriptions = efw_get_rule_fee_descriptions( $product_id );
					$fee_description  = isset( $fee_descriptions[ $rule_id ] ) ? $fee_descriptions[ $rule_id ] : '';
					if ( $fee_description ) {
						ob_start();
						efw_get_template(
							'popup/product-fee/fee-text-rule-hyperlink.php',
							array(
								'rule_id'       => $rule_id,
								'product'       => $product,
								'rule_fee_text' => $rule_fee_text,
							)
						);
						$rule_fee_text = ob_get_contents();
						ob_end_clean();
					}

					$rule_fee_value = ( $rule_fee_value * $qty );
					$item_data[]    = array(
						'key'    => $rule_fee_text,
						'value' => wc_price( $rule_fee_value ),
					);
				}
			}

			return $item_data;
		}

		/**
		 * Add Custom Cart Item Data Fee.
		 *
		 * @param array  $cart_object Session data.
		 */
		public static function add_custom_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
			$product_id = empty( $variation_id ) ? $product_id : $variation_id;
			$product_obj = wc_get_product( $product_id );
			if (isset($cart_item_data['booking']['_cost'])) {
				if ( 'yes' == get_option('efw_productfee_qty_restriction_enabled')) {
					$price = (float) $product_obj->get_price();
				} else {
					$price = $cart_item_data['booking']['_cost'];
				}
			} else {
				$price = isset( $cart_item_data['nyp'] ) ? $cart_item_data['nyp'] : (float) $product_obj->get_price();
			}

			$booking_data = isset($cart_item_data['booking']) ? $cart_item_data['booking'] : array();
			$product_price_with_fee = self::product_fee( $product_id, $price, get_current_user_id(), 'total', $booking_data );
			if ( empty( $product_price_with_fee ) ) {
				return $cart_item_data;
			}

			$product_fee_without_price = self::product_fee( $product_id, $price, get_current_user_id(), 'fee', $booking_data );
			
			$cart_item_data['efw_product_fee'] = $product_price_with_fee;
			$cart_item_data['efw_product_fee_total'] = $product_fee_without_price;
			$cart_item_data['efw_signup_fee'] = efw_get_wc_signup_fee( $product_obj );

			return $cart_item_data;
		}

		/**
		 * Add Product Fee.
		 *
		 * @param array  $cart_object Session data.
		 */
		public static function set_product_price( $cart_object ) {
			if ( 'yes' !== get_option( 'efw_productfee_enable' ) || 'yes' === get_option( 'efw_productfee_tax_setup' ) || 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) {
				return;
			}

			foreach ( $cart_object->cart_contents as $key => $value ) {
				if ( ! isset( $value['efw_product_fee'] ) ) {
					continue;
				}

				if (in_array($key, self::$updated_price)) {
					continue;
				}
				
				/**
				 * Hook:efw_calculate_product_fee_in_cart.
				 *
				 * @since 1.0
				 */
				if ( ! apply_filters( 'efw_calculate_product_fee_in_cart', true, $value ) ) {
					continue ;
				}
				
				if ( ! self::validate_coupon() ) {
					continue ;
				}

				$product_id = empty($value['variation_id']) ? $value['product_id'] : $value['variation_id'];
				$product_fee = $value['efw_product_fee'];

				$signup_fee = $value['efw_signup_fee'];

				if ( in_array( $value['data']->get_type(), array( 'subscription', 'subscription_variation' ) ) ) {
					if ( empty( $product_fee ) && empty( $signup_fee ) ) {
						continue ;
					}
				} elseif ( empty( $product_fee ) ) {
						continue ;
				}

				$booking_data = isset($value['booking']) ? $value['booking'] : array();
				$product_fee = self::rule_fees( $product_id, $product_fee, $value['data']->get_price(), $value['quantity'], $booking_data );

				/**
				 * Hook:efw_product_fee_before_set_price.
				 *
				 * @since 1.0
				 */
				$product_fee = apply_filters( 'efw_product_fee_before_set_price', $product_fee );

				$value['data']->set_price( $product_fee );

				// Only for subscription products.
				if ( in_array( $value['data']->get_type(), array( 'subscription', 'subscription_variation' ) ) ) {
					// Change subscription Sign up fee.
					$value['data']->update_meta_data( '_subscription_sign_up_fee', $signup_fee );
				}

				self::$updated_price[] = $key;
			}
		}


		/**
		 * Get Product Fee.
		 *
		 * @param int    $product_id Product Id.
		 * @param float  $price Price.
		 * @param string $fee Fee Type.
		 */
		public static function product_fee( $product_id, $price, $user_id, $fee = 'total', $booking_data = array() ) {

			$fee_value = 0;

			$user_filter_data = array(
				'user_filter_type'  => get_option( 'efw_productfee_user_filter' ),
				'include_users'     => get_option( 'efw_productfee_include_users' ),
				'exclude_users'     => get_option( 'efw_productfee_exclude_users' ),
				'include_user_role' => get_option( 'efw_productfee_include_userrole' ),
				'exclude_user_role' => get_option( 'efw_productfee_exclude_userrole' ),
			);

			self::$user_filter_for_product = self::validate_users( $user_filter_data, $user_id );
			if ( ! self::$user_filter_for_product ) {
				return $fee_value;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return $fee_value;
			}

			if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
				if ( ! efw_product_filter( $product_id ) ) {
					return $fee_value;
				}

				$fee_value = efw_global_fee_value( $price );
			} else {
				if ( 'yes' !== get_post_meta( $product_id, '_efw_enable_fee', true ) ) {
					return $fee_value;
				}

				if ('2' == get_post_meta( $product_id, '_efw_fee_from', true )) {
					$fee_value = self::get_fee_from_category( $product_id, $price, $booking_data);
				} elseif ( '1' === get_post_meta( $product_id, '_efw_fee_type', true ) ) {
						$fee_value = get_post_meta( $product_id, '_efw_fixed_value', true );
					if ('yes' != get_option('efw_productfee_qty_restriction_enabled')) {
						$fee_value = isset($booking_data['_persons'][0]) ? $fee_value * $booking_data['_persons'][0] : $fee_value;
					}
				} else {
					$percent_value = get_post_meta( $product_id, '_efw_percent_value', true );
					$percent_value = ! empty( $percent_value ) ? $percent_value / 100 : 0;
					$fee_value     = (float) $percent_value * (float) $price;
				}
			}

			return ( 'total' === $fee ) ? ( (float) $fee_value + (float) $price ) : (float) $fee_value;
		}

		/**
		 * Get Product Fee from Category.
		 *
		 * @param int   $product_id Product Id.
		 * @param float $price Product Price
		 */
		public static function get_fee_from_category( $product_id, $price, $booking_data ) {
			$fee_value = 0;
			$product      = wc_get_product( $product_id );
			$productid    = empty( $product->get_parent_id() ) ? $product_id : $product->get_parent_id();
			
			$category_lists = get_the_terms( $productid, 'product_cat' );
			if ( ! efw_check_is_array( $category_lists ) ) {
				return $fee_value;
			}

			foreach ($category_lists as $category_list) {
				if ( 'yes' !== get_term_meta( $category_list->term_id, '_efw_enable_fee', true ) ) {
					continue;
				}

				if ( '1' === get_term_meta( $category_list->term_id, '_efw_fee_type', true ) ) {
					$fee_value = get_term_meta( $category_list->term_id, '_efw_fixed_value', true );

					if (empty($fee_value)) {
						continue;
					}

					if ('yes' != get_option('efw_productfee_qty_restriction_enabled')) {
						$fee_value = isset($booking_data['_persons'][0]) ? $fee_value * $booking_data['_persons'][0] : $fee_value;
					}
				} else {
					$percent_value = get_term_meta( $category_list->term_id, '_efw_percent_value', true );
					
					if (empty($percent_value)) {
						continue;
					}

					$fee_value = ( (float) $percent_value / 100 ) * (float) $price;
				}

				if ( ! empty($fee_value) ) {
					return $fee_value;
				}
			}

			return $fee_value;
		}

		/**
		 * Get Product Fee from Rules.
		 *
		 * @param int   $product_id Product Id.
		 * @param float $fee_value Fee Value.
		 * @param float $price Product Price.
		 */
		public static function rule_fees( $product_id, $fee_value, $price, $qty, $booking_data = array() ) {
			if ( 'category' == efw_get_fee_configured_level( $product_id )) {
				return $fee_value;
			}

			if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
				$args = array(
					'meta_key' => 'efw_settings_level',
					'meta_value' => 'global',
				);
			} else {
				$args = array(
					'post_parent' => $product_id,
				);
			}

			$rule_ids = efw_get_fee_rule_ids( $args );

			if ( ! efw_check_is_array( $rule_ids ) ) {
				return $fee_value;
			}

			$fee_values = array();
			foreach ( $rule_ids as $rule_id ) {
				$rule = efw_get_fee_rule( $rule_id );

				if ( ! efw_date_filter( $rule ) ) {
					continue;
				}

				if ( ! efw_quantity_filter( $rule, $qty )) {
					continue;
				}

				if ( '1' === $rule->get_fee_type() ) {
					if ('yes' == get_option('efw_productfee_qty_restriction_enabled')) {
						$fee_values[] = $rule->get_fixed_fee();
					} else {
						$fee_values[] = isset($booking_data['_persons'][0]) ? $rule->get_fixed_fee() * $booking_data['_persons'][0] : $rule->get_fixed_fee();
					}
				} else {
					$percent_value = (float) $rule->get_percent_fee();
					$fee_values[]  = ( $percent_value / 100 ) * (float) $price;
				}
			}

			$rule_fee = $fee_value + array_sum( $fee_values );

			return $rule_fee;
		}

		/**
		 * Add Gateway Fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function gateway_fee( $cart_obj ) {

			/**
			 * Hook:efw_check_if_cart_contain_renewal.
			 *
			 * @since 5.1.0
			 */
			if ( apply_filters('efw_check_if_cart_contain_renewal', false) ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_gatewayfee_enable' ) ) {
				return;
			}

			if ( 'yes' == get_option('efw_advance_combine_fee') ) {
				return;
			}

			$gateway_id = WC()->session->get( 'efw_gateway_id' );
			$gateway_id = empty( $gateway_id ) ? WC()->session->get( 'chosen_payment_method' ) : $gateway_id;
			if ( empty( $gateway_id ) ) {
				return;
			}

			$fee_data = self::get_gateway_fee_value( $cart_obj );
			$fee_value = $fee_data['fee_value'];

			if ( empty( $fee_value ) ) {
				return;
			}

			$value    = get_option( 'efw_fee_text_for_' . $gateway_id );
			$fee_text = efw_get_custom_field_translate_string( 'efw_fee_text_for_' . $gateway_id, $value );

			$tax_class = get_option( 'efw_tax_class_for_' . $gateway_id );

			$taxable = ( 'not-required' === $tax_class ) ? false : true;

			$cart_obj = ( 'yes' === WC()->session->get( 'efw_automatic_payment' ) ) ? $cart_obj : WC()->cart;

			$cart_obj->fees_api()->add_fee(
				array(
					'id'        => 'efw_gateway_fee',
					'name'      => $fee_text,
					'amount'    => $fee_value,
					'taxable'   => $taxable,
					'tax_class' => $tax_class,
				)
			);
		}

		/**
		 * Add Gateway Total Fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function get_gateway_fee_value( $cart_obj ) {
			$fee_value = array(
				'fee_value' => 0,
				'fee_text'  => '',
			);

			if ('yes' != get_option('efw_gatewayfee_enable')) {
				return $fee_value;
			}

			// Return if the gateway id is empty.
			$gateway_id = WC()->session->get( 'efw_gateway_id' );
			$gateway_id = empty( $gateway_id ) ? WC()->session->get( 'chosen_payment_method' ) : $gateway_id;
			
			if ( empty( $gateway_id ) ) {
				return $fee_value;
			}

			$billing_country = is_object( WC()->customer ) ? WC()->customer->get_billing_country() : '';
			if ('1' == get_option('efw_add_fee_based_on_' . $gateway_id)) {
				$allowed_countries = get_option( 'efw_include_countries_for_' . $gateway_id );
				if ( ! empty( $allowed_countries ) && ! in_array( $billing_country, $allowed_countries ) ) {
					return $fee_value;
				}
			} else {
				$billing_state = is_object( WC()->customer ) ? WC()->customer->get_billing_state() : '';
				$allowed_states = get_option( 'efw_include_states_for_' . $gateway_id );
				$selected_state = efw_get_selected_state($billing_country, $billing_state);
				
				if ( ! empty( $allowed_states ) && ! in_array( $selected_state, $allowed_states ) ) {
					return $fee_value;
				}
			}

			$value    = get_option( 'efw_fee_text_for_' . $gateway_id );
			$fee_text = efw_get_custom_field_translate_string( 'efw_fee_text_for_' . $gateway_id, $value );

			// Return if the gateway fee is disabled.
			if ( 'yes' !== get_option( 'efw_enable_fee_for_' . $gateway_id ) ) {
				self::remove_fee( $fee_text );
				return $fee_value;
			}

			self::$date_filter = self::validate_date( $gateway_id );
			if ( ! self::$date_filter ) {
				self::remove_fee( $fee_text );
				return $fee_value;
			}

			if ( ! EFW_Fee_Validation::is_valid( 'gateway', $gateway_id, $cart_obj, get_current_user_id() ) ) {
				self::remove_fee( $fee_text );
				return $fee_value;
			}

			$fee_details = array(
				'type'            => 'gateway',
				'fee_type'        => get_option( 'efw_fee_type_for_' . $gateway_id ),
				'fixed_value'     => get_option( 'efw_fixed_value_for_' . $gateway_id ),
				'percentage_type' => get_option( 'efw_percentage_type_for_' . $gateway_id ),
				'percentage_fee_type' => get_option( 'efw_percentage_fee_type_for_' . $gateway_id ),
				'add_fixed_fee_on' => get_option( 'efw_add_fixed_for_' . $gateway_id ),
				'cart_sub_total'  => get_option( 'efw_percent_value_of_cart_subtotal_for_' . $gateway_id ),
				'min_fee'         => get_option( 'efw_min_fee_for_' . $gateway_id ),
				'max_fee'         => get_option( 'efw_max_fee_for_' . $gateway_id ),
				'min_sub_total'   => get_option( 'efw_min_subtotal_for_' . $gateway_id ),
				'max_sub_total'   => get_option( 'efw_max_subtotal_for_' . $gateway_id ),
			);

			$fee_value = self::fee_value( $fee_details, $cart_obj );

			/**
			 * Hook:efw_gateway_fee_after_calculate.
			 *
			 * @since 1.0
			 */
			$fee_value = apply_filters( 'efw_gateway_fee_after_calculate', $fee_value );

			return array( 'fee_value' => $fee_value, 'fee_text' => $fee_text );
		}

		/**
		 * Get Order Total Fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function get_order_fee_value( $cart_obj ) {
			$fee_value = array(
				'fee_value' => 0,
				'fee_text'  => '',
			);

			if ('yes' != get_option('efw_ordertotalfee_enable')) {
				return $fee_value;
			}

			$user_filter_data = array(
				'user_filter_type'  => get_option( 'efw_ordertotalfee_user_filter' ),
				'include_users'     => get_option( 'efw_ordertotalfee_include_users' ),
				'exclude_users'     => get_option( 'efw_ordertotalfee_exclude_users' ),
				'include_user_role' => get_option( 'efw_ordertotalfee_include_userrole' ),
				'exclude_user_role' => get_option( 'efw_ordertotalfee_exclude_userrole' ),
			);

			$fee_text = get_option( 'efw_ordertotalfee_fee_text' );

			if ( ! EFW_Fee_Validation::is_valid( 'order', '', $cart_obj, get_current_user_id() ) ) {
				self::remove_fee( $fee_text );
				return $fee_value;
			}

			if ('1' == get_option('efw_ordertotalfee_shipping_based_on')) {
				$excluded_shipping = get_option( 'efw_ordertotalfee_excluded_shipping' , array());
				$selected_shipping = wc_get_chosen_shipping_method_ids();
				$blocked_shipping  = array_intersect( (array) $excluded_shipping, $selected_shipping );
	
				if ( efw_check_is_array( $blocked_shipping ) ) {
					return $fee_value;
				}
			} else {
				$chosen_shipping_method_ids = WC()->session->get( 'chosen_shipping_methods' );
				$restricted_shipping_zone = get_option( 'efw_ordertotalfee_excluded_shipping_zone' );
				if ( efw_check_is_array( $chosen_shipping_method_ids ) && efw_check_is_array( $restricted_shipping_zone ) ) {
					foreach ( $chosen_shipping_method_ids as $chosen_shipping_method_id ) {
						$exploded_array = explode(':', $chosen_shipping_method_id);
						$shipping_method_key = implode('_', $exploded_array);
		
						if ( in_array($shipping_method_key, $restricted_shipping_zone) ) {
							return $fee_value;
						}
					}
				}
			}

			$fee_details = array(
				'type'              => 'order',
				'fee_type'          => get_option( 'efw_ordertotalfee_fee_type' ),
				'fixed_value'       => get_option( 'efw_ordertotalfee_fixed_value' ),
				'cart_sub_total'    => get_option( 'efw_ordertotalfee_cart_subtotal_percentage' ),
				'min_fee'           => 0,
				'min_sub_total'     => get_option( 'efw_ordertotalfee_min_sub_total' ),
				'max_sub_total'     => get_option( 'efw_ordertotalfee_max_sub_total' ),
				'fee_configuration' => get_option( 'efw_ordertotalfee_fee_configuration', '1' ),
			);

			$fee_value = self::fee_value( $fee_details, $cart_obj );
			/**
			 * Hook:efw_order_fee_after_calculate.
			 *
			 * @since 1.0
			 */
			$fee_value = apply_filters( 'efw_order_fee_after_calculate', $fee_value );

			return array( 'fee_value' => $fee_value, 'fee_text' => $fee_text );
		}

		/**
		 * Get Shipping Total Fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function get_shipping_fee_value( $cart_obj ) {
			$fee_value = array(
				'fee_value' => 0,
				'fee_text'  => '',
			);

			if ('yes' != get_option('efw_shippingfee_enable')) {
				return $fee_value;
			}

			$chosen_shipping_method_ids = WC()->session->get( 'chosen_shipping_methods' );
			if ( ! efw_check_is_array( $chosen_shipping_method_ids ) ) {
				return $fee_value;
			}

			foreach ( $chosen_shipping_method_ids as $chosen_shipping_method_id ) {
				$exploded_array = explode(':', $chosen_shipping_method_id);
				$shipping_method_key = implode('_', $exploded_array);

				if ( 'on' !== get_option( 'efw_enable_' . $shipping_method_key ) ) {
					continue;
				}

				if ( ! self::validate_shipping_fee( $shipping_method_key, $cart_obj ) ) {
					continue;
				}

				$fee_details = array(
					'type'            => 'shipping',
					'fee_type'        => get_option( 'efw_shipping_fee_type_' . $shipping_method_key ),
					'fixed_value'     => get_option( 'efw_shipping_fixed_value_' . $shipping_method_key ),
					'percentage_type' => get_option( 'efw_percentage_based_on_' . $shipping_method_key ),
					'percentage_fee_type' => get_option( 'efw_percentage_fee_type_for_' . $shipping_method_key ),
					'add_fixed_fee_on' => get_option( 'efw_add_fixed_for_' . $shipping_method_key ),
					'cart_sub_total'  => get_option( 'efw_shipping_percentage_value_' . $shipping_method_key ),
					'min_fee'         => get_option( 'efw_shipping_minimum_fee_value_' . $shipping_method_key ),
					'max_fee'         => get_option( 'efw_shipping_maximum_fee_value_' . $shipping_method_key ),
					'min_sub_total'   => get_option( 'efw_shipping_fee_minimum_restriction_value_' . $shipping_method_key ),
					'max_sub_total'   => get_option( 'efw_shipping_fee_maximum_restriction_value_' . $shipping_method_key ),
				);

				$fee_value = self::fee_value( $fee_details, $cart_obj );
				/**
				 * Hook:efw_shipping_fee_after_calculate.
				 *
				 * @since 1.0
				 */
				if ( ! apply_filters( 'efw_shipping_fee_after_calculate', $fee_value ) ) {
					continue;
				}

				$fee_text = get_option( 'efw_shipping_fee_text_' . $shipping_method_key );
				return array( 'fee_value' => $fee_value, 'fee_text' => $fee_text );
			}

			return $fee_value;
		}

		/**
		 * Get Product Total Fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function get_product_fee_value( $cart_obj ) {
			$fee_value = array(
				'fee_value' => 0,
				'fee_text'  => '',
			);

			if ('yes' != get_option('efw_productfee_enable')) {
				return $fee_value;
			}

			if ( 'yes' !== get_option( 'efw_productfee_qty_restriction_enabled' ) && 'yes' !== get_option( 'efw_productfee_tax_setup' ) ) {
				return $fee_value;
			}

			$fee_type = array();

			foreach ( $cart_obj->cart_contents as $cart_value ) {
				$product_id   = isset( $cart_value['product_id'] ) ? $cart_value['product_id'] : '';
				$variation_id = isset( $cart_value['variation_id'] ) ? $cart_value['variation_id'] : '';
				$product_id   = empty( $variation_id ) ? $product_id : $variation_id;
				$product      = wc_get_product( $product_id );

				if ( ! is_object( $product ) ) {
					continue;
				}

				$qty = isset( $cart_value['quantity'] ) ? $cart_value['quantity'] : 1;

				$price = isset( $cart_value['nyp'] ) ? $cart_value['nyp'] : (float) $product->get_price() + efw_get_wc_signup_fee( $product );

				$product_fee = self::product_fee( $product_id, $price, get_current_user_id(), 'fee' );

				if ( empty( $product_fee ) ) {
					continue;
				}

				if ( '1' === get_option( 'efw_productfee_fee_setup' ) ) {
					if ( ! efw_product_filter( $product_id ) ) {
						continue;
					}
	
					$fee_type[] = get_option( 'efw_productfee_fee_type' );
				} else {
					if ( 'yes' !== get_post_meta( $product_id, '_efw_enable_fee', true ) ) {
						continue;
					}

					$fee_type[] = get_post_meta( $product_id, '_efw_fee_type', true );
				}
			}

			$total_fee_amount = EFW_Fee_Based_On_Discount::get_fee( 'cart', false, $fee_type, $cart_obj );
			$fee_text = ! empty( get_option( 'efw_productfee_overall_fee_text' ) ) ? get_option( 'efw_productfee_overall_fee_text' ) : 'Product Fee';

			return array( 'fee_value' => array_sum( $total_fee_amount ), 'fee_text' => $fee_text );
		}

		/**
		 * Get Additional Fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function get_additional_fee_value( $cart_obj ) {
			$fee_value = array(
				'fee_value' => 0,
				'fee_text'  => '',
			);

			if ('yes' != get_option('efw_advance_additional_fee')) {
				return $fee_value;
			}

			$total_quantity = WC()->cart->get_cart_contents_count();

			$additional_fee_rule_ids = efw_get_additional_fee_ids();
			if ( ! efw_check_is_array( $additional_fee_rule_ids ) ) {
				return $fee_value;
			}

			$matched_fee_value = array();
			foreach ( $additional_fee_rule_ids as $additional_fee_rule_id) {
				$additional_fee = efw_get_additional_fee($additional_fee_rule_id);

				if ( ! is_object( $additional_fee ) ) {
					continue;
				}

				if ( $total_quantity >= $additional_fee->get_minimum_quantity() && $total_quantity <= $additional_fee->get_maximum_quantity() ) {
					$matched_fee_value[] = $additional_fee->get_fee_value();
				}
			}

			if ( ! efw_check_is_array( $matched_fee_value ) ) {
				return $fee_value;
			}

			if ('1' == get_option('efw_advance_rule_priority_for_additional_fee')) {
				$fee_value = reset($matched_fee_value);
			} elseif ('2' == get_option('efw_advance_rule_priority_for_additional_fee')) {
				$fee_value = end($matched_fee_value);
			} elseif ('3' == get_option('efw_advance_rule_priority_for_additional_fee')) {
				$fee_value = min($matched_fee_value);
			} else {
				$fee_value = max($matched_fee_value);
			}

			$fee_text = get_option( 'efw_advance_additional_fee_text', 'Additional Fee' );

			return array( 'fee_value' => (float) $fee_value, 'fee_text' => $fee_text );
		}

		/**
		 * Remove Fee from Cart
		 *
		 * @param string $fee_text Fee Text.
		 */
		public static function remove_fee( $fee_text ) {
			$fees = WC()->cart->get_fees();

			foreach ( $fees as $key => $fee ) {
				if ( ( $fees[ $key ]->name === $fee_text ) ) {
					unset( $fees[ $key ] );
				}
			}

			WC()->cart->fees_api()->set_fees( $fees );
		}

		/**
		 * Validate from/to date
		 *
		 * @param int $gateway_id Gateway ID.
		 */
		public static function validate_date( $gateway_id ) {
			$return       = false;
			$from_date    = true;
			$to_date      = true;
			$current_date = time();
			$fromdate     = get_option( 'efw_from_date_for_' . $gateway_id );
			$todate       = get_option( 'efw_to_date_for_' . $gateway_id );

			// Validate from date.
			if ( $fromdate ) {
				$from_date_object = EFW_Date_Time::get_date_time_object( $fromdate );

				if ( $from_date_object->getTimestamp() > $current_date ) {
					$from_date = false;
				}
			}
			// Validate to date.
			if ( $todate ) {
				$to_date_object = EFW_Date_Time::get_date_time_object( $todate );
				$to_date_object->modify( '+1 days' );

				if ( $to_date_object->getTimestamp() < $current_date ) {
					$to_date = false;
				}
			}

			if ( $from_date && $to_date ) {
				$return = true ;

				$selected_weekday = get_option('efw_weekdays_for_' . $gateway_id);
				if ( efw_check_is_array( $selected_weekday ) ) {
					$today  = gmdate( 'N' , current_time( 'timestamp' ) ) ;
					if ( ! in_array( $today , $selected_weekday ) ) {
						$return = false ;
					}
				}
			}

			/**
			 * Hook:efw_validate_rule_from_to_date.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'efw_validate_rule_from_to_date', $return );
		}

		/**
		 * Add Order Total Fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function order_total_fee( $cart_obj ) {

			/**
			 * Hook:efw_check_if_cart_contain_renewal.
			 *
			 * @since 5.1.0
			 */
			if ( apply_filters('efw_check_if_cart_contain_renewal', false) ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_ordertotalfee_enable' ) ) {
				return;
			}

			if ( 'yes' == get_option('efw_advance_combine_fee') ) {
				return;
			}

			$fee_data = self::get_order_fee_value( $cart_obj );
			$fee_value = $fee_data['fee_value'];

			$fee_text = get_option( 'efw_ordertotalfee_fee_text' );

			if ( empty( $fee_value ) ) {
				return;
			}

			$tax_class = get_option( 'efw_ordertotalfee_tax_class' );

			$taxable = ( 'not-required' === $tax_class ) ? false : true;

			$cart_obj = ( 'yes' == get_option('efw_ordertotalfee_restrict_for_renewal') ) ? WC()->cart : $cart_obj;

			$cart_obj->fees_api()->add_fee(
				array(
					'id'        => 'efw_order_fee',
					'name'      => $fee_text,
					'amount'    => $fee_value,
					'taxable'   => $taxable,
					'tax_class' => $tax_class,
				)
			);
		}

		/**
		 * Calculate Fee.
		 *
		 * @param array  $fee_details Fee Details.
		 * @param object $cart_obj cart Object.
		 */
		public static function fee_value( $fee_details, $object, $type = 'automatic' ) {
			$cart_sub_total = (float) $fee_details['cart_sub_total'];
			$fixed_value    = (float) $fee_details['fixed_value'];
			$cart_obj       = ( 'automatic' === $type ) ? $object : $object->get_items();
			$subtotal       = ( 'automatic' === $type ) ? efw_get_wc_cart_subtotal( $cart_obj ) : $object->get_subtotal();
			$total          = ( 'automatic' === $type ) ? efw_get_wc_cart_total( $cart_obj ) : (float) $object->get_total();
			$percentage_fee_type = '1';
			if ( ( 'gateway' === $fee_details['type'] ) || ( 'shipping' === $fee_details['type'] ) ) {
				$cart_subtotal = ( '1' === $fee_details['percentage_type'] ) ? $subtotal : $total;
				$cart_subtotal = ( '1' == $fee_details['add_fixed_fee_on'] ) ? $cart_subtotal : $cart_subtotal + $fixed_value;
				$percentage_fee_type = $fee_details['percentage_fee_type'];
			} elseif ( '1' === $fee_details['fee_configuration'] ) {
				$cart_subtotal = ( '2' === $fee_details['fee_type'] ) ? $subtotal : $total;
			} else {
				$fee_values              = self::get_multiple_level_order_fee( $object, $type );
				$fee_details['fee_type'] = $fee_values['fee_type'];
				$fixed_value             = $fee_values['fee_value'];
				$cart_subtotal           = ( '2' === $fee_values['fee_type'] ) ? $subtotal : $total;
				$cart_sub_total          = $fixed_value;
			}
			

			if ( 'order' !== $fee_details['type'] || ( 'order' === $fee_details['type'] && '1' === $fee_details['fee_configuration'] ) ) {
				if ( ( ! empty( $fee_details['min_sub_total'] ) && ( $cart_subtotal < $fee_details['min_sub_total'] ) ) || ( ! empty( $fee_details['max_sub_total'] ) && ( $cart_subtotal > $fee_details['max_sub_total'] ) ) ) {
					return 0;
				}
			}

			if ( '1' === $fee_details['fee_type'] ) {
				$fee_value = $fixed_value;
			} elseif ( '2' === $fee_details['fee_type'] ) {
				if ( '1' == $percentage_fee_type) {
					$percent_value = ( ( $cart_sub_total / 100 ) * $cart_subtotal );
				} else {
					$percent_value = ( ( $cart_subtotal * 100.0 ) / ( 100.0 - $cart_sub_total ) ) - $cart_subtotal;
				}
				
				if ( !empty($fee_details['min_fee']) && empty($fee_details['max_fee'])) {
					$fee_value     = ( $percent_value > $fee_details['min_fee'] ) ? $percent_value : $fee_details['min_fee'];
				} elseif ( empty($fee_details['min_fee']) && !empty($fee_details['max_fee'])) {
					$fee_value     = ( $percent_value > $fee_details['max_fee'] ) ? $fee_details['max_fee'] : $percent_value ;
				} elseif ( ! empty($fee_details['min_fee']) && !empty($fee_details['max_fee'])) {
					if (( $percent_value > $fee_details['min_fee'] ) && ( $percent_value < $fee_details['max_fee'] )) {
						$fee_value     = $percent_value;
					} elseif (( $percent_value < $fee_details['min_fee'] )) {
						$fee_value     = $fee_details['min_fee'];
					} elseif (( $percent_value > $fee_details['max_fee'] )) {
						$fee_value     = $fee_details['max_fee'];
					}
				} else {
					$fee_value     = $percent_value;
				}
			} else {
				if ( '1' == $percentage_fee_type) {
					$percent_value = ( ( $cart_sub_total / 100 ) * $cart_subtotal );
				} else {
					$percent_value = ( ( $cart_subtotal * 100.0 ) / ( 100.0 - $cart_sub_total ) ) - $cart_subtotal;
				}
				$fee_value     = ( 'gateway' === $fee_details['type'] || 'shipping' === $fee_details['type'] ) ? ( $fixed_value + $percent_value ) : $percent_value;
				if (!empty($fee_details['min_fee']) && empty($fee_details['max_fee'])) {
					$fee_value     = ( $fee_value > $fee_details['min_fee'] ) ? $fee_value : $fee_details['min_fee'];
				} elseif (empty($fee_details['min_fee']) && !empty($fee_details['max_fee'])) {
					$fee_value     = ( $fee_value > $fee_details['max_fee'] ) ? $fee_details['max_fee'] : $fee_value ;
				} elseif (!empty($fee_details['min_fee']) && !empty($fee_details['max_fee'])) {
					if (( $fee_value < $fee_details['min_fee'] )) {
						$fee_value     = $fee_details['min_fee'];
					} elseif (( $fee_value > $fee_details['max_fee'] )) {
						$fee_value     = $fee_details['max_fee'];
					}
				}
			}

			return $fee_value;
		}

		/**
		 * Get Multiple level order fee
		 *
		 * @since 3.6
		 * @return array
		 */
		public static function get_multiple_level_order_fee( $object, $type ) {
			$rule_priority         = get_option( 'efw_ordertotalfee_rule_priority', '1' );
			$multiple_level_values = get_option( 'efw_fee_rule', efw_order_fee_table_default_values() );
			$fee_values            = array(
				'fee_value' => '',
				'fee_type'  => '1',
			);

			if ( ! efw_check_is_array( $multiple_level_values ) ) {
				return $fee_values;
			}

			$cart_obj = ( 'automatic' === $type ) ? $object : $object->get_items();
			$subtotal = ( 'automatic' === $type ) ? efw_get_wc_cart_subtotal( $cart_obj ) : $object->get_subtotal();

			foreach ( $multiple_level_values as $rules ) {
				if ( ( empty( $rules['min_cart_fee'] ) && empty( $rules['max_cart_fee'] ) ) ||
						( ! empty( $rules['min_cart_fee'] ) && empty( $rules['max_cart_fee'] ) && ( $subtotal >= $rules['min_cart_fee'] ) ) ||
						( empty( $rules['min_cart_fee'] ) && ! empty( $rules['max_cart_fee'] ) && ( $subtotal <= $rules['max_cart_fee'] ) ) ||
						( ( $subtotal >= $rules['min_cart_fee'] ) && ( $subtotal <= $rules['max_cart_fee'] ) ) ) {
					$fee_values['fee_value'] = $rules['fee_value'];
					$fee_values['fee_type']  = $rules['fee_type'];
				}

				if ( ( '1' === $rule_priority ) && ( ! empty( $fee_values['fee_value'] ) ) ) {
					break;
				}
			}

			return $fee_values;
		}

		/**
		 * Update Fee details in Order for Manual Order
		 *
		 * @param int $order_id Order Id.
		 */
		public static function update_fee_details_for_manual_order( $and_taxes, $order ) {
			if ( ! is_admin() || ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				return;
			}

			// Update Fee Details.
			self::update_fee_details( $order->get_id() );
		}

		/**
		 * Update Fee details in Pay for Order
		 *
		 * @param int $order_id Order Id.
		 */
		public static function update_fee_details_in_pay_for_order( $order ) {
			// Update Fee Details.
			self::update_fee_details( $order->get_id() );
		}

		/**
		 * Update Fee details in Order
		 *
		 * @param int $order_id Order Id.
		 */
		public static function update_fee_details( $order_id ) {

			$order = is_object( $order_id ) ? $order_id : wc_get_order( $order_id );

			$total_product_fee = array();

			if (!self::validate_coupon($order)) {
				return;
			}

			foreach ( $order->get_items() as $item_id => $each_item ) {

				$product_id = ! empty( $each_item['variation_id'] ) ? $each_item['variation_id'] : $each_item['product_id'];

				$product = wc_get_product( $product_id );

				if ( ! is_object( $product ) ) {
					continue;
				}

				if ('booking' == $product->get_type()) {
					continue;
				}
				
				if (( 'yes' == $each_item->get_meta('_efw_product_fee_awarded') )) {
					continue;
				}

				$price = wc_get_price_excluding_tax( $product ) + efw_get_wc_signup_fee( $product );
				$product_fee = self::product_fee( $product_id, $price, $order->get_user_id(), 'fee' );

				if ( empty( $product_fee ) ) {
					continue;
				}

				$qty = isset( $each_item['qty'] ) ? $each_item['qty'] : 1;
				$qty = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $qty;

				$productfee = $product_fee * $qty;

				$product_fee = self::rule_fees( $product_id, $product_fee, $price, $qty );

				$total_product_fee[] = $product_fee * $qty;

				$each_item->add_meta_data('_efw_product_fee_awarded', 'yes');
				$each_item->save();
			}

			// Insert Product Fee Value.
			self::update_product_fee_value( $order, $total_product_fee );
			// Insert Order Fee Value.
			self::update_order_fee_value( $order );
			// Insert Gateway Fee Value.
			self::update_gateway_fee_value( $order );
			// Insert Shipping Fee Value.
			self::update_shipping_fee_value( $order );
		}

		/**
		 * Update Product Fee Value in Order
		 *
		 * @param WP_Post $order Order Object.
		 * @param float   $total_product_fee Product Fee.
		 */
		public static function update_product_fee_value( $order, $total_product_fee ) {
			$total_fee = efw_check_is_array( $total_product_fee ) ? array_sum( $total_product_fee ) : $total_product_fee;
			if (empty($total_fee)) {
				return;
			}

			$args = array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'efw_user_id',
						'value'   => $order->get_user_id(),
						'compare' => '==',
					),
					array(
						'key'     => 'efw_order_id',
						'value'   => $order->get_id(),
						'compare' => '==',
					),
					array(
						'key'     => 'efw_product_fee',
						'compare' => 'NOT EXISTS',
					),
				),
			);

			$fee_ids = efw_get_fees_ids( $args );

			$meta_args = array(
				'efw_user_id'  => $order->get_user_id(),
				'efw_order_id' => $order->get_id(),
			);

			$meta_args['efw_fee_type'] = 'product';

			if ( ! efw_check_is_array( $fee_ids ) ) {
				$meta_args['efw_product_fee'] = $total_fee;
				efw_create_new_fees( $meta_args );
			} else {
				$fee_id      = reset( $fee_ids );
				$fee         = efw_get_fees( $fee_id );
				$updated_fee = $fee->get_product_fee() + $total_fee;

				$meta_args['efw_product_fee'] = $updated_fee;

				efw_update_fees( $fee_id, $meta_args );
			}
		}

		/**
		 * Update Order Fee Value in Order
		 *
		 * @param WP_Post $order Order Object.
		 */
		public static function update_order_fee_value( $order ) {
			$args = array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'efw_user_id',
						'value'   => $order->get_user_id(),
						'compare' => '==',
					),
					array(
						'key'     => 'efw_order_id',
						'value'   => $order->get_id(),
						'compare' => '==',
					),
					array(
						'key'     => 'efw_order_fee',
						'compare' => 'NOT EXISTS',
					),
				),
			);

			$fee_ids = efw_get_fees_ids( $args );

			$total_order_fee = array();

			$meta_args = array(
				'efw_user_id'  => $order->get_user_id(),
				'efw_order_id' => $order->get_id(),
			);

			$order_fee_text = get_option( 'efw_ordertotalfee_fee_text' );

			foreach ( $order->get_fees() as $fee ) {
				if ( $order_fee_text === $fee->get_name() ) {
					$total_order_fee[] = ( 'yes' === get_option( 'woocommerce_calc_taxes' ) ) ? abs( $fee->get_total() + $fee->get_total_tax() ) : abs( $fee->get_total() );
				}
			}

			$total_fee = array_sum( $total_order_fee );
			if (empty($total_fee)) {
				return;
			}

			$meta_args['efw_fee_type'] = 'order';

			if ( ! efw_check_is_array( $fee_ids ) ) {
				$meta_args['efw_order_fee'] = $total_fee;
				efw_create_new_fees( $meta_args );
			} else {
				$fee_id      = reset( $fee_ids );
				$fees        = efw_get_fees( $fee_id );
				$updated_fee = $fees->get_order_fee() + $total_fee;

				$meta_args['efw_order_fee'] = $updated_fee;

				efw_update_fees( $fee_id, $meta_args );
			}
		}

		/**
		 * Update Gateway Fee Value in Order
		 *
		 * @param WP_Post $order Order Object.
		 */
		public static function update_gateway_fee_value( $order ) {
			$args = array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'efw_user_id',
						'value'   => $order->get_user_id(),
						'compare' => '==',
					),
					array(
						'key'     => 'efw_order_id',
						'value'   => $order->get_id(),
						'compare' => '==',
					),
					array(
						'key'     => 'efw_gateway_fee',
						'compare' => 'NOT EXISTS',
					),
				),
			);

			$fee_ids = efw_get_fees_ids( $args );

			$total_gateway_fee = array();

			$meta_args = array(
				'efw_user_id'  => $order->get_user_id(),
				'efw_order_id' => $order->get_id(),
			);

			$gateway_id = isset( $_REQUEST['payment_method'] ) ? wc_clean( wp_unslash( $_REQUEST['payment_method'] ) ) : $order->get_payment_method();
			if ( empty($gateway_id) ) {
				return;
			}

			$value            = get_option( 'efw_fee_text_for_' . $gateway_id );
			$gateway_fee_text = efw_get_custom_field_translate_string( 'efw_fee_text_for_' . $gateway_id, $value );

			foreach ( $order->get_fees() as $fee ) {
				if ( $gateway_fee_text === $fee->get_name() ) {
					$total_gateway_fee[] = ( 'yes' === get_option( 'woocommerce_calc_taxes' ) ) ? abs( $fee->get_total() + $fee->get_total_tax() ) : abs( $fee->get_total() );
				}
			}

			if ( ! efw_check_is_array($total_gateway_fee) ) {
				return;
			}

			$total_fee = array_sum( $total_gateway_fee );
			if (empty($total_fee)) {
				return;
			}

			$meta_args['efw_fee_type'] = 'gateway';

			if ( ! efw_check_is_array( $fee_ids ) ) {
				$meta_args['efw_gateway_fee'] = $total_fee;
				efw_create_new_fees( $meta_args );
			} else {
				$fee_id      = reset( $fee_ids );
				$fees        = efw_get_fees( $fee_id );
				$updated_fee = $fees->get_gateway_fee() + $total_fee;

				$meta_args['efw_gateway_fee'] = $updated_fee;

				efw_update_fees( $fee_id, $meta_args );
			}

			WC()->session->__unset( 'efw_gateway_id' );
		}

		/**
		 * Update Shipping Fee Value in Order
		 *
		 * @param WP_Post $order Order Object.
		 */
		public static function update_shipping_fee_value( $order ) {
			if ( ! is_object( $order ) ) {
				return;
			}

			$args = array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'efw_user_id',
						'value'   => $order->get_user_id(),
						'compare' => '==',
					),
					array(
						'key'     => 'efw_order_id',
						'value'   => $order->get_id(),
						'compare' => '==',
					),
					array(
						'key'     => 'efw_shipping_fee',
						'compare' => 'NOT EXISTS',
					),
				),
			);

			$fee_ids = efw_get_fees_ids( $args );

			$total_shipping_fees = array();

			$meta_args = array(
				'efw_user_id'  => $order->get_user_id(),
				'efw_order_id' => $order->get_id(),
			);

			$cart_fees = $order->get_fees();

			if ( ! efw_check_is_array( $cart_fees ) ) {
				return;
			}

			$shipping_fee_text = array();
			foreach ( $order->get_items( 'shipping' ) as $shipping_method ) {

				$shipping_fee_text[] = get_option( 'efw_shipping_fee_text_' . $shipping_method->get_method_id() );
			}

			foreach ( $cart_fees as $fee ) {
				if ( ! is_object( $fee ) ) {
					continue;
				}

				if ( ! in_array( $fee->get_name(), $shipping_fee_text ) ) {
					continue;
				}

				$total_shipping_fees[] = ( 'yes' === get_option( 'woocommerce_calc_taxes' ) ) ? abs( $fee->get_total() + $fee->get_total_tax() ) : abs( $fee->get_total() );
			}

			if ( ! efw_check_is_array( $total_shipping_fees ) ) {
				return;
			}

			$total_fee = array_sum( $total_shipping_fees );
			if (empty($total_fee)) {
				return;
			}

			$meta_args['efw_fee_type'] = 'shipping';

			if ( ! efw_check_is_array( $fee_ids ) ) {
				$meta_args['efw_shipping_fee'] = $total_fee;
				efw_create_new_fees( $meta_args );
			} else {
				$fee_id      = reset( $fee_ids );
				$fees        = efw_get_fees( $fee_id );
				$updated_fee = $fees->get_shipping_fee() + $total_fee;

				$meta_args['efw_shipping_fee'] = $updated_fee;

				efw_update_fees( $fee_id, $meta_args );
			}
		}

		/**
		 * Display Variation Price.
		 *
		 * @param string $price_html Variation Price.
		 * @param object $product Product object.
		 * @return string
		 */
		public static function display_variation_price( $price_html, $product ) {
			if ( ! is_a( $product, 'WC_Product' ) ) {
				return $price_html;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return $price_html;
			}

			if ( 'variable' === $product->get_type() ) {
				if ( ! is_product() && ! is_shop() && ! is_product_category() ) {
					return $price_html;
				}

				$display_type = is_product() ? get_option( 'efw_productfee_show_product_fee_in_single_product' ) : get_option( 'efw_productfee_show_product_fee_shop' );
				if ( 'add-to-price' !== $display_type ) {
					return $price_html;
				}

				$variation_price = array();
				$child_ids       = $product->get_children();

				foreach ( $child_ids as $child_id ) {
					$product              = wc_get_product( $child_id );
					$price                = (float) wc_get_price_to_display( $product ) + efw_get_wc_signup_fee( $product );
					$product_fee          = self::product_fee( $product->get_id(), $price, get_current_user_id(), 'fee' );
					$total_payable_amount = self::rule_fees( $product->get_id(), $product_fee, $price, '1' );
					$variation_price[]    = $total_payable_amount + $price;
				}

				if ( ! efw_check_is_array( $variation_price ) ) {
					return $price_html;
				}

				if ( min( $variation_price ) === max( $variation_price ) ) {
					return wc_price( min( $variation_price ) );
				}

				$price_html = wc_price( min( $variation_price ) ) . ' - ' . wc_price( max( $variation_price ) );

				return $price_html;
			} else {
				if ( 'simple' !== $product->get_type() ) {
					return $price_html;
				}

				if ( ! is_product() && ! is_shop() && ! is_product_category() ) {
					return $price_html;
				}

				$display_type = is_product() ? get_option( 'efw_productfee_show_product_fee_in_single_product' ) : get_option( 'efw_productfee_show_product_fee_shop' );
				if ( 'add-to-price' !== $display_type ) {
					return $price_html;
				}

				$price       = (float) wc_get_price_to_display( $product ) + efw_get_wc_signup_fee( $product );
				$product_fee = self::product_fee( $product->get_id(), $price, get_current_user_id(), 'fee' );

				if ( empty( $product_fee ) ) {
					return $price_html;
				}

				$total_payable_amount = self::rule_fees( $product->get_id(), $product_fee, $price, '1' );
				$price_html           = wc_price( $total_payable_amount + $price );

				return $price_html;
			}

			return $price_html;
		}

		/**
		 * Validate Users for Fee.
		 *
		 * @param array $filter_data User Filters.
		 * @return bool
		 */
		public static function validate_users( $filter_data, $user_id ) {
			$user_filter_type = $filter_data['user_filter_type'];

			switch ( $user_filter_type ) {

				case '2':
					$include_user = $filter_data['include_users'];
					if ( in_array( $user_id, $include_user ) ) {
						return true;
					}

					break;
				case '3':
					$exclude_user = $filter_data['exclude_users'];
					if ( in_array( $user_id, $exclude_user ) ) {
						return false;
					}

					return true;
				case '4':
					$user = get_userdata($user_id);

					$include_user_role = $filter_data['include_user_role'];
					// Logged in user restriction.
					if ( efw_check_is_array( $user->roles ) ) {
						foreach ( $user->roles as $role ) {
							if ( in_array( $role, $include_user_role ) ) {
								return true;
							}
						}
					} elseif ( in_array( 'guest', $include_user_role ) ) {// Guest user restriction.
							return true;
					}

					break;
				case '5':
					$user = get_userdata($user_id);

					$exclude_user_role = $filter_data['exclude_user_role'];
					// Loggedin user restriction.
					if ( efw_check_is_array( $user->roles ) ) {
						foreach ( $user->roles as $role ) {
							if ( in_array( $role, $exclude_user_role ) ) {
								return false;
							}
						}
					} elseif ( in_array( 'guest', $exclude_user_role ) ) {// Guest user restriction.
							return false;
					}

					return true;
				default:
					return true;
			}

			return false;
		}

		/**
		 * Add Fee in Product Table.
		 *
		 * @param array $columns Columns.
		 */
		public static function product_fee_columns( $columns ) {

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return $columns;
			}

			$add_column = array();

			foreach ( $columns as $key => $column ) {
				$add_column[ $key ] = $column;

				if ( 'price' === $key ) {
					$add_column['product_fee'] = esc_html__( 'Product Fee', 'extra-fees-for-woocommerce' );
				}
			}

			return $add_column;
		}

		/**
		 * Add Fee in Product Table.
		 *
		 * @param array $column Columns.
		 * @param int   $post_id Post Id.
		 */
		public static function product_fee_content( $column, $post_id ) {

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) ) {
				return;
			}

			if ( 'product_fee' !== $column ) {
				return;
			}

			$product = wc_get_product( $post_id );

			$price = wc_get_price_excluding_tax( $product );

			$product_fee = self::product_fee( $post_id, $price, get_current_user_id(), 'fee' );

			$product_fee = self::rule_fees( $post_id, $product_fee, $price, '1' );

			echo wp_kses_post( ! empty( $product_fee ) ? wc_price( $product_fee ) : '-' );
		}

		/**
		 * May be add shipping fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function may_be_add_shipping_fee( $cart_obj ) {

			if ( ! is_object( $cart_obj ) ) {
				return;
			}

			/**
			 * Hook:efw_check_if_cart_contain_renewal.
			 *
			 * @since 5.1.0
			 */
			if ( apply_filters('efw_check_if_cart_contain_renewal', false) ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_shippingfee_enable' ) ) {
				return;
			}

			if ( 'yes' == get_option('efw_advance_combine_fee') ) {
				return;
			}

			$chosen_shipping_method_ids = WC()->session->get( 'chosen_shipping_methods' );
			if ( ! efw_check_is_array( $chosen_shipping_method_ids ) ) {
				return;
			}

			foreach ( $chosen_shipping_method_ids as $chosen_shipping_method_id ) {
				$exploded_array = explode(':', $chosen_shipping_method_id);
				$shipping_method_key = implode('_', $exploded_array);

				if ( 'on' !== get_option( 'efw_enable_' . $shipping_method_key ) ) {
					continue;
				}

				if ( ! self::validate_shipping_fee( $shipping_method_key, $cart_obj ) ) {
					continue;
				}

				$fee_details = array(
					'type'            => 'shipping',
					'fee_type'        => get_option( 'efw_shipping_fee_type_' . $shipping_method_key ),
					'fixed_value'     => get_option( 'efw_shipping_fixed_value_' . $shipping_method_key ),
					'percentage_type' => get_option( 'efw_percentage_based_on_' . $shipping_method_key ),
					'percentage_fee_type' => get_option( 'efw_percentage_fee_type_for_' . $shipping_method_key ),
					'add_fixed_fee_on' => get_option( 'efw_add_fixed_for_' . $shipping_method_key ),
					'cart_sub_total'  => get_option( 'efw_shipping_percentage_value_' . $shipping_method_key ),
					'min_fee'         => get_option( 'efw_shipping_minimum_fee_value_' . $shipping_method_key ),
					'max_fee'         => get_option( 'efw_shipping_maximum_fee_value_' . $shipping_method_key ),
					'min_sub_total'   => get_option( 'efw_shipping_fee_minimum_restriction_value_' . $shipping_method_key ),
					'max_sub_total'   => get_option( 'efw_shipping_fee_maximum_restriction_value_' . $shipping_method_key ),
				);

				$fee_value = self::fee_value( $fee_details, $cart_obj );
				/**
				 * Hook:efw_shipping_fee_after_calculate.
				 *
				 * @since 1.0
				 */
				if ( ! apply_filters( 'efw_shipping_fee_after_calculate', $fee_value ) ) {
					continue;
				}

				if ( empty( $fee_value ) ) {
					continue;
				}

				$tax_class = str_replace( '_', '-', get_option( 'efw_shipping_tax_class_' . $shipping_method_key ) );

				$taxable = ( 'not-required' === $tax_class ) ? false : true;

				$fee_text = get_option( 'efw_shipping_fee_text_' . $shipping_method_key );

				// Add fee.
				$cart_obj->fees_api()->add_fee(
					array(
						'id'        => 'efw_shipping_fee',
						'name'      => $fee_text,
						'amount'    => $fee_value,
						'taxable'   => $taxable,
						'tax_class' => $tax_class,
					)
				);
			}
		}

		/**
		 * Validate shipping fee.
		 *
		 * @param string $chosen_shipping_method_id Shipping method Id.
		 * @param object $cart_obj Cart object.
		 */
		public static function validate_shipping_fee( $chosen_shipping_method_id, $cart_obj ) {

			if ( ! self::validate_shipping_fee_date_filter( $chosen_shipping_method_id ) ) {
				return false;
			}

			if ( ! EFW_Fee_Validation::is_valid( 'shipping', $chosen_shipping_method_id, $cart_obj, get_current_user_id() ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Validate shipping fee from/to date filter.
		 *
		 * @param string $chosen_shipping_method_id Shipping method Id.
		 */
		public static function validate_shipping_fee_date_filter( $chosen_shipping_method_id ) {

			$return       = false;
			$from_date    = true;
			$to_date      = true;
			$current_date = time();
			$fromdate     = get_option( 'efw_shipping_from_date_' . $chosen_shipping_method_id );
			$todate       = get_option( 'efw_shipping_to_date_' . $chosen_shipping_method_id );

			// Validate from date.
			if ( $fromdate ) {
				$from_date_object = EFW_Date_Time::get_date_time_object( $fromdate );

				if ( $from_date_object->getTimestamp() > $current_date ) {
					$from_date = false;
				}
			}

			// Validate to date.
			if ( $todate ) {
				$to_date_object = EFW_Date_Time::get_date_time_object( $todate );
				$to_date_object->modify( '+1 days' );

				if ( $to_date_object->getTimestamp() < $current_date ) {
					$to_date = false;
				}
			}

			if ( $from_date && $to_date ) {
				$return = true;

				$selected_weekday = get_option('efw_shipping_weekdays_for_' . $chosen_shipping_method_id);
				if ( efw_check_is_array( $selected_weekday ) ) {
					$today  = gmdate( 'N' , current_time( 'timestamp' ) ) ;
					if ( ! in_array( $today , $selected_weekday ) ) {
						$return = false ;
					}
				}
			}
			/**
			 * Hook:efw_validate_shipping_from_to_date.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'efw_validate_shipping_from_to_date', $return );
		}

		/**
		 * Render fee details in shop page.
		 */
		public static function render_fee_details_shop() {
			global $product;
			if ( ! is_object( $product ) || 'variable' == $product->get_type() || 'variable-subscription' == $product->get_type() ) {
				return;
			}

			if ( 'yes' !== get_option( 'efw_productfee_show_product_fee_shop' ) ) {
				return;
			}

			$price       = (float) wc_get_price_to_display( $product ) + efw_get_wc_signup_fee( $product );
			$product_fee = self::product_fee( $product->get_id(), $price, get_current_user_id(), 'fee' );
			if ( empty( $product_fee ) ) {
				return;
			}

			$total_payable_amount = self::rule_fees( $product->get_id(), $product_fee, $price, '1' );
			$fee_text             = efw_get_fee_text( $product->get_id() );
			$rule_fee_text        = efw_get_rule_fee_text( $product->get_id(), $price, '1' ) ;

			efw_get_template(
				'loop/product-fee-details.php',
				array(
					'product'              => $product,
					'product_fee'          => $product_fee,
					'fee_text'             => $fee_text,
					'rule_fee_texts'       => $rule_fee_text,
					'price'                => $price,
					'total_payable_amount' => $total_payable_amount,
				)
			);
		}

		/**
		 * Render fee description in cart totals fee.
		 *
		 * @param string $cart_totals_fee_html Cart Total.
		 * @param object $fee Fee Object.
		 */
		public static function render_fee_description( $cart_totals_fee_html, $fee ) {
			if ( ! isset( WC()->session ) ) {
				return $cart_totals_fee_html;
			}

			$gateway_id = WC()->session->get( 'chosen_payment_method' );
			if ( $gateway_id && get_option( 'efw_fee_text_for_' . $gateway_id ) === $fee->name && get_option( 'efw_fee_description_for_' . $gateway_id ) ) {
				ob_start();
				efw_get_template( 'popup/gateway-fee/description-hyperlink.php', array( 'gateway_id' => $gateway_id ) );
				$content = ob_get_contents();
				ob_end_clean();
				return $cart_totals_fee_html . ' ' . $content;
			}

			if ( get_option( 'efw_ordertotalfee_fee_text' ) === $fee->name && get_option( 'efw_ordertotalfee_fee_description' ) ) {
				ob_start();
				efw_get_template( 'popup/order-fee/description-hyperlink.php' );
				$content = ob_get_contents();
				ob_end_clean();
				return $cart_totals_fee_html . ' ' . $content;
			}

			if ( 'efw_shipping_fee' === $fee->id ) {
				$chosen_shipping_method_ids = WC()->session->get( 'chosen_shipping_methods' );
				if ( efw_check_is_array( $chosen_shipping_method_ids ) ) {
					foreach ( $chosen_shipping_method_ids as $chosen_shipping_method_id ) {
						$exploded_array = explode(':', $chosen_shipping_method_id);
						$shipping_method_key = implode('_', $exploded_array);
		
						if ( get_option( 'efw_shipping_fee_text_' . $shipping_method_key ) != $fee->name || ! get_option( 'efw_shipping_fee_description_' . $shipping_method_key ) ) {
							continue;
						}

						ob_start();
						efw_get_template( 'popup/shipping-fee/description-hyperlink.php', array( 'shipping_id' => $shipping_method_key ) );
						$content = ob_get_contents();
						ob_end_clean();
						return $cart_totals_fee_html . ' ' . $content;
					}
				}
			}

			if ( ( 'yes' == get_option( 'efw_advance_combine_fee' ) ) && get_option( 'efw_advance_combine_fee_text' ) === $fee->name ) {
				ob_start();
				efw_get_template( 'popup/combined-fee/description-hyperlink.php', array( 'fee_detail' => self::$combined_fee_details ) );
				$content = ob_get_contents();
				ob_end_clean();

				return $cart_totals_fee_html . ' ' . $content;
			}

			return $cart_totals_fee_html;
		}

		/**
		 * May be add product fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function may_be_add_product_fee( $cart_obj ) {

			if ( 'yes' !== get_option( 'efw_productfee_qty_restriction_enabled' ) && 'yes' !== get_option( 'efw_productfee_tax_setup' ) ) {
				return;
			}

			if ( ! is_object( $cart_obj ) ) {
				return;
			}

			/**
			 * Hook:efw_check_if_cart_contain_renewal.
			 *
			 * @since 5.1.0
			 */
			if ( apply_filters('efw_check_if_cart_contain_renewal', false) ) {
				return;
			}

			if ( 'yes' == get_option('efw_advance_combine_fee') ) {
				return;
			}

			if ( ! self::validate_coupon() ) {
				return;
			}

			$fee_data = self::get_product_fee_value( $cart_obj );
			$total_fee_amount = $fee_data['fee_value'];

			if ( ! $total_fee_amount ) {
				return;
			}

			$tax_class = ( 'no' === get_option( 'efw_productfee_tax_setup' ) ) ? get_option( 'efw_productfee_tax_class', 'standard' ) : '';
			$taxable   = ( 'not-required' == $tax_class || 'no' === get_option( 'efw_productfee_tax_setup' ) ) ? false : true;

			$cart_obj = ( 'yes' == get_option('efw_productfee_restrict_for_renewal') ) ? WC()->cart : $cart_obj;
			
			// Add fee.
			$cart_obj->fees_api()->add_fee(
				array(
					'id'        => 'efw_product_fee',
					'name'      => ! empty( get_option( 'efw_productfee_overall_fee_text' ) ) ? get_option( 'efw_productfee_overall_fee_text' ) : 'Product Fee',
					'amount'    => $total_fee_amount,
					'taxable'   => $taxable,
					'tax_class' => $tax_class,
				)
			);
		}

		/**
		 * Add Additional Fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function add_additional_fee( $cart_obj ) {

			if ( 'yes' !== get_option( 'efw_advance_additional_fee' ) ) {
				return;
			}

			if ( ! is_object( $cart_obj ) ) {
				return;
			}

			if ( 'yes' == get_option('efw_advance_combine_fee') ) {
				return;
			}

			$fee_data = self::get_additional_fee_value( $cart_obj );
			$total_fee_amount = $fee_data['fee_value'];

			if ( ! $total_fee_amount ) {
				return;
			}

			$tax_class = get_option( 'efw_advance_tax_class_for_additional_fee', 'standard' );
			$taxable   = ( 'not-required' == $tax_class || 'no' === get_option( 'efw_advance_tax_class_for_additional_fee' ) ) ? false : true;

			// Add fee.
			$cart_obj->fees_api()->add_fee(
				array(
					'id'        => 'efw_additional_fee',
					'name'      => get_option( 'efw_advance_additional_fee_text', 'Additional Fee' ),
					'amount'    => $total_fee_amount,
					'taxable'   => $taxable,
					'tax_class' => $tax_class,
				)
			);
		}

		/**
		 * Add Combined fee.
		 *
		 * @param object $cart_obj Cart object.
		 */
		public static function add_combined_fee( $cart_obj ) {
			if ( 'yes' != get_option('efw_advance_combine_fee') ) {
				return;
			}

			self::$combined_fee_details = array();

			$gateway_fee_value = self::get_gateway_fee_value( $cart_obj );
			$order_fee_value = self::get_order_fee_value( $cart_obj );
			$shipping_fee_value = self::get_shipping_fee_value( $cart_obj );
			$product_fee_value = self::get_product_fee_value( $cart_obj );
			$additional_fee_value = self::get_additional_fee_value( $cart_obj );

			self::$combined_fee_details = array(
				'gateway_fee' => $gateway_fee_value,
				'order_fee' => $order_fee_value,
				'shipping_fee' => $shipping_fee_value,
				'product_fee' => $product_fee_value,
				'additional_fee' => $additional_fee_value,
			);

			$fee_value = $gateway_fee_value['fee_value'] + $order_fee_value['fee_value'] + $shipping_fee_value['fee_value'] + $product_fee_value['fee_value'] + $additional_fee_value['fee_value'];
			
			if ( empty( $fee_value ) ) {
				return;
			}

			$fee_text = get_option('efw_advance_combine_fee_text');
			$tax_class = get_option( 'efw_advance_tax_class_for_combined_fee' );
			$taxable = ( 'not-required' === $tax_class ) ? false : true;

			// Add fee.
			$cart_obj->fees_api()->add_fee(
				array(
					'id'        => 'efw_combined_fee',
					'name'      => $fee_text,
					'amount'    => $fee_value,
					'taxable'   => $taxable,
					'tax_class' => $tax_class,
				)
			);
		}

		/**
		 * Add the custom hidden order item meta.
		 *
		 * @return array
		 * */
		public static function validate_coupon( $order_obj = false ) {
			$return      = true;
			$cart_obj    = is_object( WC()->cart ) ? WC()->cart->get_applied_coupons() : array();
			$applied_coupons = $order_obj ? $order_obj->get_coupon_codes() : $cart_obj;

			if ( ! efw_check_is_array( $applied_coupons ) ) {
				return $return;
			}

			foreach ( $applied_coupons as $code ) {
				$coupon_obj = new WC_Coupon( $code );

				if ('yes' == get_post_meta( $coupon_obj->get_id(), '_efw_enable_product_fee', true )) {
					return false;
				}           
			}

			return $return;
		}

		/**
		 * Update order item meta.
		 *
		 * @since 1.0.0
		 * @param WC_Order_Item $item Item Object.
		 * @param String        $cart_item_key Cart item key.
		 * @param Array         $value Values.
		 * @param WC_Order      $order Order.
		 */
		public static function update_order_item( $item, $cart_item_key, $value, $order ) {
			if ( ! isset( $value['efw_product_fee_total'] ) ) {
				return;
			}

			if ( isset( $value['booking'] )) {
				return;
			}

			/**
			 * Hook:efw_calculate_product_fee_in_cart.
			 *
			 * @since 1.0
			 */
			if ( ! apply_filters( 'efw_calculate_product_fee_in_cart', true, $value ) ) {
				return ;
			}
			
			if ( ! self::validate_coupon() ) {
				return ;
			}

			$product_id = ! empty( $value['variation_id'] ) ? $value['variation_id'] : $value['product_id'];

			$product = wc_get_product( $product_id );

			if ( ! is_object( $product ) ) {
				return;
			}

			$product_fee = $value['efw_product_fee_total'];

			if ( empty( $product_fee ) ) {
					return ;
			}

			$price = isset($value['nyp']) ? $value['nyp'] : $product->get_price();
			$qty = isset( $value['quantity'] ) ? $value['quantity'] : 1;
			$qty = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $qty;

			$product_fee = $product_fee * $qty;

			/**
			 * Hook:efw_product_fee_before_set_price.
			 *
			 * @since 1.0.0
			 */
			$product_fee = apply_filters( 'efw_product_fee_before_set_price', $product_fee );

			$key_name        = efw_get_fee_text( $product_id );
			$fee_description = efw_get_fee_description( $product->get_id() );
			if ( $fee_description ) {
				ob_start();
				efw_get_template(
					'popup/product-fee/fee-text-hyperlink.php',
					array(
						'fee_text' => $key_name,
						'product'  => $product,
					)
				);
				$key_name = ob_get_contents();
				ob_end_clean();
			}

			// Update order item meta.
			$item->add_meta_data( $key_name, wc_price($product_fee) );

			$rule_fee_texts = efw_get_rule_fee_text( $product_id, $price, $qty );

			if ( efw_check_is_array( $rule_fee_texts ) ) {
				foreach ( $rule_fee_texts as $rule_id => $rule_fee_value ) {
					$rule_object = efw_get_fee_rule( $rule_id );
					if ( ! is_object( $rule_object ) ) {
						continue;
					}

					$rule_fee_value = $rule_fee_value * $qty;
					$rule_fee_text = $rule_object->get_fee_text();
					$fee_descriptions = efw_get_rule_fee_descriptions( $product_id );
					$fee_description  = isset( $fee_descriptions[ $rule_id ] ) ? $fee_descriptions[ $rule_id ] : '';
					if ( $fee_description ) {
						ob_start();
						efw_get_template(
							'popup/product-fee/fee-text-rule-hyperlink.php',
							array(
								'rule_id'       => $rule_id,
								'product'       => $product,
								'rule_fee_text' => $rule_fee_text,
							)
						);
						$rule_fee_text = ob_get_contents();
						ob_end_clean();
					}

					$item->add_meta_data( $rule_fee_text, wc_price($rule_fee_value) );
				}
			}
		}

		/**
		 * Add the custom hidden order item meta.
		 *
		 * @return array
		 * */
		public static function hide_order_item_meta_key( $hidden_order_itemmeta ) {
			$custom_order_itemmeta = array( '_efw_product_fee_awarded', '_efw_price_updated' );

			return array_merge($hidden_order_itemmeta, $custom_order_itemmeta);
		}
	}

	EFW_Fees_Handler::init();
}
