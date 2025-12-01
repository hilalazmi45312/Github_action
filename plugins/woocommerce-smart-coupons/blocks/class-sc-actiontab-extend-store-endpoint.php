<?php
/**
 * WooCommerce Smart Coupon Extend Store API for coupon action's tab.
 *
 * A class to extend the store public API with coupon action tab related data
 * for each coupon item
 *
 * @author      StoreApps
 * @version     1.2.0
 * @since       9.15.0
 * @package woocommerce-smart-coupons/blocks/
 */

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

/**
 * Action Tab Class
 */
class SC_ActionTab_Extend_Store_Endpoint {

	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendSchema
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'woocommerce-smart-coupons';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 * @param ExtendSchema $extend_rest_api An instance of the ExtendSchema class.
	 *
	 * @since 3.1.0
	 */
	public static function init( ExtendSchema $extend_rest_api ) {
		self::$extend = $extend_rest_api;
		self::extend_store();
	}

	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store() {
		// Register into `cart`.
		self::$extend->register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( 'SC_ActionTab_Extend_Store_Endpoint', 'extend_cart_data' ),
				'schema_callback' => array( 'SC_ActionTab_Extend_Store_Endpoint', 'extend_cart_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);

		// Register into `cart`.
		self::$extend->register_endpoint_data(
			array(
				'endpoint'        => CartItemSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( 'SC_ActionTab_Extend_Store_Endpoint', 'extend_cart_item_data' ),
				'schema_callback' => array( 'SC_ActionTab_Extend_Store_Endpoint', 'extend_cart_item_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => self::IDENTIFIER,
				'callback'  => function( $data ) {
					switch ( $data['action'] ) {
						case 'add_to_cart':
							$selected_product_id = isset( $data['data'] ) && isset( $data['data']['product_id'] ) ? absint( $data['data']['product_id'] ) : 0;
							$add_to_cart_qty = isset( $data['data'] ) && isset( $data['data']['settings'] ) ? absint( $data['data']['settings']['quantity'] ) : 0;
							$coupon_code = $data['data']['coupon_code'];
							$coupon_action = WC_SC_Coupon_Actions::get_instance();
							$coupon_action->add_action_tab_selected_product_to_cart( $selected_product_id, $coupon_code, $add_to_cart_qty );
							break;
					}

				},
			)
		);
	}

	/**
	 * Summary of cart schema
	 *
	 * @return array[]
	 */
	public static function extend_cart_schema() {
		return array(
			'coupons' => array(
				'description' => __( 'Billing period for the subscription.', 'woocommerce-smart-coupons' ),
				'type'        => array( 'array', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Extend cart blocks to provide coupon action's tab data.
	 *
	 * @return array
	 */
	public static function extend_cart_data() {
		$coupon_actions_class = WC_SC_Coupon_Actions::get_instance();
		$cart                 = WC()->cart;
		$coupons_array        = array();
		if ( $cart instanceof WC_Cart ) {
			foreach ( $cart->get_applied_coupons() as $coupon_code ) {
				$coupon_actions = $coupon_actions_class->get_coupon_actions( $coupon_code );
				if ( ! empty( $coupon_actions ) && ! is_scalar( $coupon_actions ) ) {
					$coupon = new WC_Coupon( $coupon_code );
					if ( $coupon instanceof WC_Coupon ) {
						if ( is_callable( array( $coupon, 'get_meta' ) ) ) {
							$no_of_selectable_product = $coupon->get_meta( 'wc_sc_no_of_selectable_product' );
						} else {
							$no_of_selectable_product = get_post_meta( $coupon->get_id(), 'wc_sc_no_of_selectable_product', true );
						}
						if ( 'yes' !== $no_of_selectable_product ) {
							continue;
						}

						$coupons_array[ $coupon_code ]['settings'] = array(
							'no_of_selectable_product' => empty( $no_of_selectable_product ) ? 1 : $no_of_selectable_product,
							'currency_symbol'          => html_entity_decode( get_woocommerce_currency_symbol(), ENT_HTML5, 'UTF-8' ),
						);

						foreach ( $coupon_actions as $coupon_action ) {

							if ( empty( $coupon_action['product_id'] ) ) {
								continue;
							}

							$id              = absint( $coupon_action['product_id'] );
							$product         = wc_get_product( $id );
							$price           = (float) $product->get_price();
							$quantity        = absint( $coupon_action['quantity'] );
							$discount_amount = (float) $coupon_action['discount_amount'];
							$discount_type   = ( ! empty( $coupon_action['discount_type'] ) ) ? $coupon_action['discount_type'] : 'percent';
							$coupon_message  = $coupon->get_meta( 'wc_coupon_message' );

							if ( empty( $coupons_array[ $coupon_code ]['settings']['discount_type'] ) ) {
								$coupons_array[ $coupon_code ]['settings']['discount_type'] = $discount_type;
							}

							if ( empty( $coupons_array[ $coupon_code ]['settings']['discount_amount'] ) ) {
								$coupons_array[ $coupon_code ]['settings']['discount_amount'] = $discount_amount;
							}
							if ( empty( $coupons_array[ $coupon_code ]['settings']['quantity'] ) ) {
								$coupons_array[ $coupon_code ]['settings']['quantity'] = empty( $quantity ) ? 1 : $quantity;
							}

							switch ( $discount_type ) {
								case 'flat':
									$discount = $coupon_actions_class->convert_price( $discount_amount );
									break;

								case 'percent':
									$discount = ( $price * $discount_amount ) / 100;
									break;
							}

							// Calculated the discounted amount.
							$discounted_price = ( $price - $discount ) * $quantity;

							$discounted_price = $discounted_price < 0 ? 0 : $discounted_price;

							if ( empty( preg_replace( '/\s|&nbsp;/', '', $coupon_message ) ) ) {
								// translators: %1$s: coupon code, %2$d: number of options, %3$s: discount amount, %4$s: currency symbol, %5$s: discount type.
								$coupon_message = sprintf( __( 'Redeem Your "%1$s" and choose 1 product from %2$d options with %3$s %4$s %5$s off ', 'woocommerce-smart-coupons' ), $coupon_code, count( $coupon_actions ), $coupons_array[ $coupon_code ]['settings']['discount_amount'], 'flat' === $coupons_array[ $coupon_code ]['settings']['discount_type'] ? $coupons_array[ $coupon_code ]['settings']['currency_symbol'] : '', $coupons_array[ $coupon_code ]['settings']['discount_type'] );
							}

							$coupons_array[ $coupon_code ]['settings']['message'] = $coupon_message;

							if ( $product instanceof WC_Product ) {
								$coupons_array[ $coupon_code ]['products'][] = array(
									'id'               => $product->get_id(),
									'name'             => $product->get_title(),
									'description'      => $product->get_short_description(),
									'price'            => wc_format_decimal( $product->get_price(), wc_get_price_decimals() ),
									'discount_amount'  => $discount * $quantity,
									'discounted_price' => wc_format_decimal( $discounted_price, wc_get_price_decimals() ),
									'currency_symbol'  => html_entity_decode( get_woocommerce_currency_symbol(), ENT_HTML5, 'UTF-8' ),
									'quantity'         => $coupon_action['quantity'],
									'image'            => $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) : wc_placeholder_img_src(),
									'no_of_selectable_product' => empty( $no_of_selectable_product ) ? 'no' : $no_of_selectable_product,
								);
							}
						}
					}
				}
			}
		}
		return array(
			'coupon_metadata' => $coupons_array,
		);
	}

	/**
	 * Summary of extend_cart_data
	 *
	 * @param array $cart_item cart item data.
	 * @return array
	 */
	public static function extend_cart_item_data( $cart_item ) {
		$coupon_code = isset( $cart_item['wc_sc_product_source'] ) ? $cart_item['wc_sc_product_source'] : null;
		return array(
			'coupon_action' => array(
				'wc_sc_product_source' => $coupon_code,
			),
		);
	}

	/**
	 * Summary of extend_cart_schema
	 *
	 * @return array[]
	 */
	public static function extend_cart_item_schema() {
		return array(
			'properties' => array(
				'coupon_action' => array(
					'type' => 'string',
				),
			),
		);
	}

}
