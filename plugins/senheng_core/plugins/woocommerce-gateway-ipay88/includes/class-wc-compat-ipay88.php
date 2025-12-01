<?php
/*
 * Class to ensure compatibility in the transition of WC from v2.4.0 to v3.0.0
 *
 * Version 1.7.1
 * Author: VanboDevelops
 * Author URI: http://www.vanbodevelops.com
 *
 *	Copyright: (c) 2012 - 2018 VanboDevelops
 *	License: GNU General Public License v3.0
 *	License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Compat_iPay88 {

	/**
	 * Is WC 2.4+
	 * @var bool
	 */
	public static $is_wc_2_4;
	/**
	 * Is WC 2.5+
	 * @var bool
	 */
	public static $is_wc_2_5;
	/**
	 * Is WC 2.6+
	 * @var bool
	 */
	public static $is_wc_2_6;
	/**
	 * Is WC 3.0+
	 * @var bool
	 */
	public static $is_wc_3_0;

	/**
	 * Detect, if we are using WC 2.4+
	 *
	 * @since 1.3.2
	 * @return bool
	 */
	public static function is_wc_2_4() {
		if ( is_bool( self::$is_wc_2_4 ) ) {
			return self::$is_wc_2_4;
		}

		return self::$is_wc_2_4 = ( version_compare( self::get_wc_version_constant(), '2.4.0', '>=' ) );
	}

	public static function is_wc_2_5() {
		if ( is_bool( self::$is_wc_2_5 ) ) {
			return self::$is_wc_2_5;
		}

		return self::$is_wc_2_5 = ( version_compare( self::get_wc_version_constant(), '2.5.0', '>=' ) );
	}

	public static function is_wc_2_6() {
		if ( is_bool( self::$is_wc_2_6 ) ) {
			return self::$is_wc_2_6;
		}

		return self::$is_wc_2_6 = ( version_compare( self::get_wc_version_constant(), '2.6.0', '>=' ) );
	}

	public static function is_wc_3_0() {
		if ( is_bool( self::$is_wc_3_0 ) ) {
			return self::$is_wc_3_0;
		}

		return self::$is_wc_3_0 = ( version_compare( self::get_wc_version_constant(), '3.0.0', '>=' ) );
	}

	/**
	 * Get the Gateway settings page
	 *
	 * @param string $class_name
	 *
	 * @return string Formatted URL
	 */
	public static function gateway_settings_page( $class_name ) {
		$page    = 'wc-settings';
		$tab     = 'checkout';
		$section = strtolower( $class_name );

		return admin_url( 'admin.php?page=' . $page . '&tab=' . $tab . '&section=' . $section );
	}

	/**
	 * Get the WC logger object
	 *
	 * @return \WC_Logger
	 */
	public static function get_wc_logger() {
		if ( self::is_wc_3_0() ) {
			return wc_get_logger();
		} else {
			return new WC_Logger();
		}
	}

	/**
	 * Get the stacked site notices
	 *
	 * TODO: Remove from code
	 *
	 * @param string $notice_type
	 *
	 * @return int
	 */
	public static function wc_notice_count( $notice_type = '' ) {
		return wc_notice_count( $notice_type );
	}

	/**
	 * Add site notices
	 *
	 * TODO: Remove from code
	 *
	 * @param string $message     The message to be logged.
	 * @param string $notice_type (Optional) Name of the notice type. Can be success, message, error, notice.
	 *
	 * @return void
	 */
	public static function wc_add_notice( $message, $notice_type = 'success' ) {
		wc_add_notice( $message, $notice_type );
	}

	/**
	 * Get the global WC object
	 *
	 * TODO: Remove from code
	 *
	 * @return WooCommerce
	 */
	public static function get_wc_global() {
		return WC();
	}

	/**
	 * Force SSL on a URL
	 *
	 * TODO: Remove from code
	 *
	 * @param string $url The URL to format
	 *
	 * @return string
	 */
	public static function force_https( $url ) {
		return WC_HTTPS::force_https_url( $url );
	}

	/**
	 * Empty the user cart session
	 */
	public static function empty_cart() {
		if ( self::is_wc_2_5() ) {
			wc_empty_cart();
		} else {
			WC()->cart->empty_cart();
		}
	}

	/**
	 * Get Order shipping total
	 *
	 * @param WC_Order $order
	 *
	 * @return double
	 */
	public static function get_total_shipping( WC_Order $order ) {
		if ( self::is_wc_3_0() ) {
			return $order->get_shipping_total();
		} else {
			return $order->get_total_shipping();
		}
	}

	/**
	 * Get My Account URL
	 *
	 * @return string Formatted URL string
	 */
	public static function get_myaccount_url() {
		return wc_get_page_permalink( 'myaccount' );
	}

	/**
	 * Get Order meta object.
	 *
	 * TODO: Remove from code
	 *
	 * @param array $item
	 *
	 * @return \WC_Order_Item_Meta
	 */
	public static function get_order_item_meta( $item ) {
		return new WC_Order_Item_Meta( $item );
	}

	/**
	 * Get WC version constant.
	 *
	 * @return string|null
	 */
	public static function get_wc_version_constant() {
		if ( defined( 'WC_VERSION' ) && WC_VERSION ) {
			return WC_VERSION;
		}

		return null;
	}

	/**
	 * Clear all transients cache for product data.
	 *
	 * TODO: Remove from the code
	 *
	 * @since 1.0.3
	 *
	 * @param int $product_id
	 */
	public static function clear_product_transients( $product_id ) {
		wc_delete_product_transients( $product_id );
	}

	/**
	 * Format date
	 *
	 * TODO: Remove from the code
	 *
	 * @return string
	 */
	public static function wc_date_format() {
		return wc_date_format();
	}

	/**
	 * Format decimal numbers ready for DB storage
	 *
	 * TODO: Remove from code
	 *
	 * @param float $number
	 * @param mixed $dp
	 * @param bool  $trim_zeros
	 *
	 * @return float
	 */
	public static function wc_format_decimal( $number, $dp = false, $trim_zeros = false ) {
		return wc_format_decimal( $number, $dp, $trim_zeros );
	}

	/**
	 * Get WC order currency
	 *
	 * @since   1.0.3
	 * @updated 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return int
	 */
	public static function get_order_currency( WC_Order $order ) {
		if ( self::is_wc_3_0() ) {
			return $order->get_currency();
		} else {
			return $order->get_order_currency();
		}
	}

	/**
	 * Get order object
	 *
	 * TODO: REMOVE from the code
	 *
	 * @since 1.1
	 *
	 * @param int|WP_Post|WC_Abstract_Order $order_id
	 *
	 * @return WC_Order
	 */
	public static function wc_get_order( $order_id ) {
		return wc_get_order( $order_id );
	}

	/**
	 * Get debug file path
	 *
	 * TODO: REMOVE from code
	 *
	 * @since 1.1
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public static function wc_get_debug_file_path( $name ) {
		return wc_get_log_file_path( $name );
	}

	/**
	 * Get available order status ids.<br/>
	 * Formatted in WC <= 2.1 = term_id or term_slug -> name<br/>
	 * Formatted in WC >= 2.2 = status slug -> name
	 *
	 * TODO: Remove from code
	 *
	 * @since 1.1
	 *
	 * @param string $format_by
	 *
	 * @return array
	 */
	public static function wc_get_order_statuses( $format_by = 'term_id' ) {
		return wc_get_order_statuses();
	}

	/**
	 * Get product object
	 *
	 * TODO: REMOVE from code
	 *
	 * @since 1.1
	 *
	 * @param int $id
	 *
	 * @return WC_Product
	 */
	public static function wc_get_product( $id ) {
		return wc_get_product( $id );
	}

	/**
	 * Get the count of all processing orders
	 *
	 * TODO: REMOVE from code
	 *
	 * @since 1.1.1
	 * @return int
	 */
	public static function get_processing_order_count() {
		return wc_processing_order_count();
	}

	/**
	 * Save transaction ID to the order
	 *
	 * TODO: REMOVE from code
	 *
	 * @since 1.1.2
	 *
	 * @param WC_Order $order
	 * @param string   $transaction_id
	 *
	 * @return bool
	 */
	public static function payment_complete( $order, $transaction_id = '' ) {
		return $order->payment_complete( $transaction_id );
	}

	/**
	 * Get order total discount.
	 *
	 * TODO: REMOVE from code
	 *
	 * @since 1.3
	 *
	 * @param WC_Order $order
	 *
	 * @return float
	 */
	public static function get_total_discount( $order ) {
		$ex_tax = 'yes' == self::get_prop( $order, 'prices_include_tax' ) ? true : false;

		return $order->get_total_discount( $ex_tax );
	}

	/**
	 * Get a request URL to use for external requests
	 *
	 * TODO: REMOVE from code
	 *
	 * @since 1.4
	 *
	 * @param $request
	 *
	 * @return string
	 */
	public static function api_request_url( $request ) {
		return WC()->api_request_url( $request );
	}

	/**
	 * Get WC Order status
	 *
	 * TODO: REMOVE from code
	 *
	 * @param WC_Order $order
	 *
	 * @since 1.4
	 * @return mixed|string
	 */
	public static function get_order_status( WC_Order $order ) {
		return $order->get_status();
	}

	/**
	 * Get WC Order User ID
	 *
	 * TODO: REMOVE from code
	 *
	 * @param WC_Order $order
	 *
	 * @since 1.5
	 * @return int
	 */
	public static function get_order_user_id( WC_Order $order ) {
		return $order->get_user_id();
	}

	/**
	 * Returns the product from the order item.
	 *
	 * @since 1.6
	 *
	 * @param array|WC_Order_Item_Product $item
	 * @param bool|WC_Order               $order
	 *
	 * @return WC_Product
	 */
	public static function get_product_from_item( $item, $order = false ) {
		if ( self::is_wc_3_0() && is_callable( array( $item, 'get_product' ) ) ) {
			return $item->get_product();
		} else {
			return $order->get_product_from_item( $item );
		}
	}

	/**
	 * Returns the order item meta for gateway display
	 *
	 * @since 1.6
	 *
	 * @param $item
	 *
	 * @return string|WC_Order_Item_Meta
	 */
	public static function wc_display_item_meta( $item ) {
		if ( self::is_wc_3_0() ) {
			$item_meta = strip_tags( wc_display_item_meta( $item, array(
				'before'    => "",
				'separator' => ", ",
				'after'     => "",
				'echo'      => false,
				'autop'     => false,
			) ) );
		} else {
			$meta = new WC_Order_Item_Meta( $item );

			$item_meta = $meta->display( true, true );
		}

		return $item_meta;
	}

	/**
	 * Returns the item name
	 *
	 * @since 1.6
	 *
	 * @param array|WC_Order_item $item
	 *
	 * @return mixed
	 */
	public static function get_item_name( $item ) {
		if ( self::is_wc_3_0() && is_callable( array( $item, 'get_name' ) ) ) {
			$name = $item->get_name();
		} else {
			$name = $item['name'];
		}

		return $name;
	}

	/**
	 * Returns the item quantity
	 *
	 * @since 1.6
	 *
	 * @param array|WC_Order_item $item
	 *
	 * @return mixed
	 */
	public static function get_item_quantity( $item ) {
		if ( self::is_wc_3_0() && is_callable( array( $item, 'get_quantity' ) ) ) {
			$name = $item->get_quantity();
		} else {
			$name = $item['qty'];
		}

		return $name;
	}

	/**
	 * Returns an order property
	 *
	 * @since 1.6.1
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_prop( $order, $name ) {
		if ( self::is_wc_3_0() && is_callable( array( $order, 'get_' . $name ) ) ) {
			$method = 'get_' . $name;

			return $order->{$method}();
		}

		return $order->{$name};
	}

	/**
	 * Returns an order property
	 *
	 * @since 1.6.2
	 *
	 * @param object $object
	 *
	 * @return mixed
	 */
	public static function get_prop( $object, $name ) {
		if ( self::is_wc_3_0() && is_callable( array( $object, 'get_' . $name ) ) ) {
			$method = 'get_' . $name;

			return $object->{$method}();
		}

		return $object->{$name};
	}

	/**
	 * Returns the order ID.
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_id( $order ) {
		return self::get_order_prop( $order, 'id' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_first_name( $order ) {
		return self::get_order_prop( $order, 'billing_first_name' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_last_name( $order ) {
		return self::get_order_prop( $order, 'billing_last_name' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_company( $order ) {
		return self::get_order_prop( $order, 'billing_company' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_address_1( $order ) {
		return self::get_order_prop( $order, 'billing_address_1' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_address_2( $order ) {
		return self::get_order_prop( $order, 'billing_address_2' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_city( $order ) {
		return self::get_order_prop( $order, 'billing_city' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_postcode( $order ) {
		return self::get_order_prop( $order, 'billing_postcode' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_state( $order ) {
		return self::get_order_prop( $order, 'billing_state' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_country( $order ) {
		return self::get_order_prop( $order, 'billing_country' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_phone( $order ) {
		return self::get_order_prop( $order, 'billing_phone' );
	}

	/**
	 * Returns the order billing .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_billing_email( $order ) {
		return self::get_order_prop( $order, 'billing_email' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_first_name( $order ) {
		return self::get_order_prop( $order, 'shipping_first_name' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_last_name( $order ) {
		return self::get_order_prop( $order, 'shipping_last_name' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_company( $order ) {
		return self::get_order_prop( $order, 'shipping_company' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_address_1( $order ) {
		return self::get_order_prop( $order, 'shipping_address_1' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_address_2( $order ) {
		return self::get_order_prop( $order, 'shipping_address_2' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_city( $order ) {
		return self::get_order_prop( $order, 'shipping_city' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_postcode( $order ) {
		return self::get_order_prop( $order, 'shipping_postcode' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_state( $order ) {
		return self::get_order_prop( $order, 'shipping_state' );
	}

	/**
	 * Returns the order shipping .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_shipping_country( $order ) {
		return self::get_order_prop( $order, 'shipping_country' );
	}

	/**
	 * Returns the order customer note .
	 *
	 * @since 1.6
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public static function get_order_customer_note( $order ) {
		return self::get_order_prop( $order, 'customer_note' );
	}

	/**
	 * Return Cart URL
	 *
	 * @since 1.6.3
	 *
	 * @return string
	 */
	public static function wc_get_cart_url() {
		if ( self::is_wc_2_5() ) {
			return wc_get_cart_url();
		}

		return WC()->cart->get_cart_url();
	}

	/**
	 * Reduces stock levels
	 *
	 * @since 1.6.4
	 *
	 * @param WC_Order $order
	 */
	public static function wc_reduce_stock_levels( $order ) {
		if ( self::is_wc_3_0() ) {
			$order->reduce_order_stock();
		} else {
			wc_reduce_stock_levels( self::get_prop( $order, 'id' ) );
		}
	}

	/**
	 * Retrieves meta data from WC CRUD objects
	 *
	 * @since 1.6.4
	 *
	 * @param WC_Order|WC_Product|WC_Subscription $object
	 * @param string                              $meta_name
	 * @param bool                                $single
	 * @param mixed                               $default
	 *
	 * @return mixed $value
	 */
	public static function get_meta( $object, $meta_name, $single = true, $default = null ) {
		$prefix       = '_';
		$prefixed     = ( substr( $meta_name, 0, strlen( $prefix ) ) != $prefix ) ? $prefix . $meta_name : $meta_name;
		$not_prefixed = ( substr( $meta_name, 0, strlen( $prefix ) ) != $prefix ) ? $meta_name : ltrim( $meta_name, $prefix );

		$value = ! is_null( $default ) ? $default : ( ( true == $single ) ? null : array() );

		// If we don't have a method for this specific property, but we are using WC 3.0, it may be set as meta data on the object so check if we can use that
		if ( method_exists( $object, 'get_meta' ) ) {
			if ( $object->meta_exists( $meta_name ) ) {
				if ( true === $single ) {
					$value = $object->get_meta( $meta_name, true );
				} else {
					// WC_Data::get_meta() returns an array of stdClass objects with id, key & value properties when meta is available
					$value = wp_list_pluck( $object->get_meta( $meta_name, false ), 'value' );
				}
			}
		} elseif ( true === $single && isset( $object->{$not_prefixed} ) ) { // WC < 3.0
			$value = $object->{$not_prefixed};
		} elseif ( strtolower( $meta_name ) !== 'id' && metadata_exists( 'post', self::get_prop( $object, 'id' ), $meta_name ) ) {
			// If we couldn't find a property or function, fallback to using post meta as that's what many __get() methods in WC < 3.0 did
			if ( true === $single ) {
				$value = get_post_meta( self::get_prop( $object, 'id' ), $meta_name, true );
			} else {
				// Get all the meta values
				$value = get_post_meta( self::get_prop( $object, 'id' ), $meta_name, false );
			}
		}

		return $value;
	}

	/**
	 * Returns an order property
	 *
	 * @since 1.6.5
	 *
	 * @param WC_Product $product
	 *
	 * @return mixed
	 */
	public static function get_stock_quantity( $product ) {
		if ( self::is_wc_3_0() && is_callable( array( $product, 'get_stock_quantity' ) ) ) {
			return $product->get_stock_quantity();
		}

		return $product->stock;
	}

	/**
	 * Returns an order property
	 *
	 * @since 1.6.5
	 *
	 * @param WC_Product $product
	 *
	 * @return mixed
	 */
	public static function get_product_type( $product ) {
		// Always use the modern method if available (WC 3.0+)
		if ( is_callable( array( $product, 'get_type' ) ) ) {
			return $product->get_type();
		}

		// Fallback for very old WooCommerce versions (pre-3.0)
		if ( isset( $product->product_type ) ) {
			return $product->product_type;
		}

		// Final fallback
		return 'simple';
	}

	/**
	 * Returns an order property
	 *
	 * @since 1.6.5
	 *
	 * @param WC_Product $product
	 *
	 * @return mixed
	 */
	public static function get_product_title( $product ) {
		if ( self::is_wc_3_0() && is_callable( array( $product, 'get_title' ) ) ) {
			return $product->get_name();
		}

		return get_the_title( WC_Compat_Linnworks::get_prop( $product, 'id' ) );
	}

	/**
	 * Returns the order statuses for a paid order
	 *
	 * @since 1.7
	 *
	 * @return array|mixed|void
	 */
	public static function get_is_paid_statuses() {
		if ( self::is_wc_3_0() ) {
			return wc_get_is_paid_statuses();
		}

		return apply_filters( 'woocommerce_order_is_paid_statuses', array(
			'processing',
			'completed',
		) );
	}
}