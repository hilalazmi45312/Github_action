<?php
/**
 * Compatibility - WooCommerce Booking
 *
 * Tested upto: 2.6.1
 *
 * @since 6.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'EFW_WC_Booking_Compatibility' ) ) {

	/**
	 * Class.
	 *
	 * @since 6.3.0
	 */
	class EFW_WC_Booking_Compatibility extends EFW_Compatibility {

		/**
		 * Class constructor.
		 *
		 * @since 6.3.0
		 */
		public function __construct() {
			$this->id = 'woocommerce_booking';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 * @since 6.3.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists( 'WC_Bookings' );
		}

		/**
		 * Actions.
		 *
		 * @since 6.3.0
		 */
		public function actions() {
			add_filter( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'create_fee_order_line_item' ), 10, 4 );
		}

		/**
		 * Actions.
		 *
		 * @since 6.3.0
		 */
		public function frontend_action() {
			add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'display_fee_for_booking' ), 10 );
			add_action( 'wp_ajax_efw_display_booking_cost' , array( __CLASS__, 'get_booking_cost' ) , 10 ) ;
		}

		/**
		 * Create the product fee order line item.
		 *
		 * @return void
		 * */
		public static function display_fee_for_booking() {
			efw_get_template( 'single/booking-cost.php' );
		}

		public static function get_booking_cost() {
			check_ajax_referer( 'efw-booking-nonce', 'efw_security' );

			try {
				if ( ! isset( $_REQUEST[ 'form' ] ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				$content = '';
				$posted = array() ;
				$requested = filter_var($_REQUEST[ 'form' ], FILTER_SANITIZE_STRING);
				parse_str( $requested, $posted ) ;
				if ( isset( $posted[ 'add-to-cart' ] ) ) {
					$product_id  = $posted[ 'add-to-cart' ] ;
					$product     = wc_get_product($product_id);
					$booking_data = wc_bookings_get_posted_data( $posted, $product );
					$cost = ( 'yes' == get_option('efw_productfee_qty_restriction_enabled') ) ? $product->get_price() : WC_Bookings_Cost_Calculation::calculate_booking_cost( $booking_data, $product );
					if ( ! is_wp_error( $cost ) ) {
						$product_fee = EFW_Fees_Handler::product_fee( $product_id, $cost, 'fee' );
						if ( '2' === get_option( 'efw_productfee_fee_setup' ) ) {
							if ( 'yes' == get_post_meta( $product_id, '_efw_enable_fee', true ) ) {
								if ( '1' === get_post_meta( $product_id, '_efw_fee_type', true )  && isset($booking_data['_persons'])) {
									$product_fee = ( 'yes' == get_option('efw_productfee_qty_restriction_enabled') ) ? $product_fee : $product_fee * $booking_data['_persons'][0];
								}
							}
						}
						$booking_cost = WC_Bookings_Cost_Calculation::calculate_booking_cost( $booking_data, $product );
						$total_payable_amount = EFW_Fees_Handler::rule_fees( $product_id, $product_fee, $cost, $booking_data['_persons'][0], $booking_data );
						$fee_text = efw_get_fee_text( $product_id );
						$rule_fee_text = efw_get_rule_fee_text( $product_id, $cost, $booking_data['_persons'][0], $booking_data ) ;
						ob_start();
						efw_get_template( 'single/product-fee-notice.php', 
							array(
								'product'              => $product,
								'product_fee'          => $product_fee,
								'fee_text'             => $fee_text,
								'rule_fee_texts'       => $rule_fee_text,
								'price'                => $booking_cost,
								'total_payable_amount' => $total_payable_amount,
								'display_price'        => false,
							) 
						);
						$content = ob_get_contents();
						ob_end_clean();
					}
				}

				wp_send_json_success( array( 'html' => $content ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Create the product fee order line item.
		 *
		 * @return void
		 * */
		public static function create_fee_order_line_item( $item, $cart_item_key, $values, $order ) {
			if (!isset($values['booking'])) {
				return;
			}

			$product_id = ! empty( $values['variation_id'] ) ? $values['variation_id'] : $values['product_id'];

			$product = wc_get_product( $product_id );

			$price = $values['booking']['_cost'];
			$product_fee = EFW_Fees_Handler::product_fee( $product_id, $price, 'fee', $values['booking'] );

			$key_name = efw_get_fee_text( $product_id );
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

			$item->add_meta_data( $key_name, wc_price( $product_fee ) );

			$product_fee = EFW_Fees_Handler::rule_fees( $product_id, $product_fee, $price, $values['quantity'], $values['booking'] );

			$rule_fee_texts = ( '2' === get_option( 'efw_productfee_fee_setup' ) ) ? efw_get_rule_fee_text( $product_id, $price, $values['quantity'], $values['booking'] ) : array();

			if ( efw_check_is_array( $rule_fee_texts ) ) {
				foreach ( $rule_fee_texts as $rule_id => $rule_fee_value ) {
					$rule_object = efw_get_fee_rule( $rule_id );
					if ( ! is_object( $rule_object ) ) {
						continue;
					}

					$fee_descriptions = efw_get_rule_fee_descriptions( $product_id );
					$fee_description  = isset( $fee_descriptions[ $rule_id ] ) ? $fee_descriptions[ $rule_id ] : '';
					if ( $fee_description ) {
						ob_start();
						efw_get_template(
							'popup/product-fee/fee-text-rule-hyperlink.php',
							array(
								'rule_id'       => $rule_id,
								'product'       => $product,
								'rule_fee_text' => $rule_object->get_fee_text(),
							)
						);
						$rule_fee_text = ob_get_contents();
						ob_end_clean();
					}

					$item->add_meta_data( $rule_fee_text, wc_price( $rule_fee_value ) );
				}
			}

			// Insert Product Fee Value.
			EFW_Fees_Handler::update_product_fee_value( $order, $order->get_id(), array( $product_fee ) );
		}
	}

}
