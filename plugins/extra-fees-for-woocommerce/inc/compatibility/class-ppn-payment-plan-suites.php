<?php

/**
 * Payment Plan Suites Compatibility.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Payment_Plan_Compatibility' ) ) {

	/**
	 * Class EFW_Payment_Plan_Compatibility.
	 */
	class EFW_Payment_Plan_Compatibility extends EFW_Compatibility {

		/**
		 * Context
		 *
		 * @var string
		 */
		private $context = 'extra-fees-for-woocommerce';

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'payment-plan-suites';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 *  @return bool
		 * */
		public function is_plugin_enabled() {

			return class_exists( 'WC_Payment_Plan_Suite' );
		}

		/**
		 * Action
		 */
		public function admin_action() {
			add_filter('efw_product_fee_settings', array( $this, 'payment_plan_compatibility_for_product_fee' ), 10);
		}

		/**
		 * Frontend Action
		 */
		public function frontend_action() {
			add_filter( 'efw_calculate_product_fee_in_cart', array( $this, 'remove_fee_for_payment_plan_from_session' ), 10, 2 );
			add_filter( 'efw_product_fee_before_custom_item_data', array( $this, 'update_fee_for_payment_plan_from_session' ), 10, 2 );
			add_filter( 'ppn_get_installments_from_plan_for_product', array( $this, 'add_fee_in_installment_for_product_page' ), 10, 3 );
			add_filter( 'ppn_get_installments_from_plan_for_cart_item', array( $this, 'add_fee_in_installment_for_cart_and_checkout_page' ), 10, 3 );
			add_action( 'ppn_user_payment_installments_details_table_after_installment_amount', array( $this, 'fee_info_for_admin' ), 10, 3 );
			add_action( 'woocommerce_ppn_user_payment_installments_details_table_after_installment_amount', array( $this, 'fee_info_in_email_and_page' ), 10, 3 );
			add_action( 'ppn_view_installments_after_installment_amount', array( $this, 'fee_info_in_cart_and_checkout' ), 10, 3 );
		}

		/**
		 * Get Product Fee Settings section array.
		 */
		public function payment_plan_compatibility_for_product_fee( $settings ) {
			$updated_settings = array();
			foreach ( $settings as $section ) {
				if ( isset( $section['id'] ) && 'efw_product_fee_restriction_settings' == $section['id'] &&
						isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
						$updated_settings[] = array(
							'title'   => esc_html__( 'Charge Product Fee on', 'extra-fees-for-woocommerce' ),
							'type'    => 'select',
							'default' => '1',
							'id'      => 'efw_productfee_apply_fee_on_installment',
							'class'   => 'show-if-product-fee-enable show-if-tax-or-quantity-restriction-disable',
							'options' => array(
								'1' => esc_html__('First Installment', 'extra-fees-for-woocommerce'),
								'2' => esc_html__('Final Installment', 'extra-fees-for-woocommerce'),
							),
						);
				}
				$updated_settings[] = $section;
			}
			return $updated_settings;
		}

		/**
		 * Remove Fee from Price for Payment Plan in Cart.
		 *
		 * @param float  $product_price Product Price.
		 * @param Object $cart_item Cart Items.
		 * @param string $cart_item_key Cart Item Key.
		 */
		public function remove_fee_for_payment_plan_from_session( $bool, $session_data ) {          
			if ( isset( $session_data['ppn_payment_plan'] ) ) {
				return false;
			}

			return $bool;
		}

		/**
		 * Update Fee from Price for Payment Plan in Cart.
		 *
		 * @param float  $product_fee Product Fee.
		 * @param Object $cart_item Cart Items.
		 */
		public function update_fee_for_payment_plan_from_session( $product_fee, $cart_item ) {  
			if ( ! isset($cart_item['ppn_payment_plan']) ) {
				return $product_fee;
			}

			$installment_for = get_option( 'efw_productfee_apply_fee_on_installment' );
			if ( '2' == $installment_for ) {
				return 0;
			}

			return $product_fee;
		}

		/**
		 * Add Fee in Installments.
		 *
		 * @param array  $installments Plan Installments.
		 * @param Object $plan Payment Plan.
		 * @param Object $product Product Object.
		 */
		public function add_fee_in_installment_for_product_page( $installments, $plan, $product ) {
			if ( 'yes' == get_option( 'efw_productfee_qty_restriction_enabled' ) || 'yes' == get_option( 'efw_productfee_tax_setup' ) ) {
				return $installments;
			}

			$product_object = is_object( $product ) ? $product : wc_get_product( $product );
			$price = ppn_get_product_price_before( $product_object );
			$product_fee = EFW_Fees_Handler::product_fee( $product_object->get_id(), $price, 'fee' );
			$product_fee = EFW_Fees_Handler::rule_fees( $product_object->get_id(), $product_fee, $price, '1' );
			
			$installment_for = get_option( 'efw_productfee_apply_fee_on_installment' );
			if ( '1' == $installment_for ) {
				$start = 1; // To find the first installment
				if ( isset( $installments[ $start ][ 'amount' ] ) ) {
					$installments[ $start ][ 'metadata' ][ 'efw_product_fee' ] = $product_fee; // Pass your custom amount to sum up with the first installment amount
					$installments[ $start ][ 'amount' ] += $installments[ $start ][ 'metadata' ][ 'efw_product_fee' ];
				}
			} elseif ( '2' == $installment_for ) {
				$end = count( $installments ); // To find the last installment
				if ( isset( $installments[ $end ][ 'amount' ] ) ) {
					$installments[ $end ][ 'metadata' ][ 'efw_product_fee' ] = $product_fee; // Pass your custom amount to sum up with the last installment amount
					$installments[ $end ][ 'amount' ] += $installments[ $end ][ 'metadata' ][ 'efw_product_fee' ];
				}
			}
		
			return $installments;
		}

		/**
		 * Add Fee in Installments.
		 *
		 * @param array  $installments Plan Installments.
		 * @param Object $plan Payment Plan.
		 * @param Object $product Product Object.
		 */
		public function add_fee_in_installment_for_cart_and_checkout_page( $installments, $plan, $cart_item ) {
			if ( 'yes' == get_option( 'efw_productfee_qty_restriction_enabled' ) || 'yes' == get_option( 'efw_productfee_tax_setup' ) ) {
				return $installments;
			}
			
			if ( ! efw_check_is_array( $cart_item ) ) {
				return $installments;
			}

			if ( ! isset( $cart_item['ppn_payment_plan'] ) ) {
				return $installments;
			}

			$product_id = empty( $cart_item['variation_id'] ) ? $cart_item['product_id'] : $cart_item['variation_id'] ;
			$quantity = isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;
			$product = wc_get_product( $product_id );
			$price = ppn_get_product_price_before( $product );
			$product_fee = EFW_Fees_Handler::product_fee( $product_id, $price, 'fee' );
			$product_fee = EFW_Fees_Handler::rule_fees( $product_id, $product_fee, $price, $quantity );

			$installment_for = get_option( 'efw_productfee_apply_fee_on_installment' );
			if ( '1' == $installment_for ) {
				$start = 1; // To find the first installment
				if ( isset( $installments[ $start ][ 'amount' ] ) ) {
					$installments[ $start ][ 'metadata' ][ 'efw_product_fee' ] = $product_fee * $quantity; // Pass your custom amount to sum up with the first installment amount
					$installments[ $start ][ 'amount' ] += $product_fee;
				}
			} elseif ( '2' == $installment_for ) {
				$end = count( $installments ); // To find the last installment
				if ( isset( $installments[ $end ][ 'amount' ] ) ) {
					$installments[ $end ][ 'metadata' ][ 'efw_product_fee' ] = $product_fee * $quantity; // Pass your custom amount to sum up with the last installment amount
					$installments[ $end ][ 'amount' ] += $product_fee;
				}
			}
		
			return $installments;
		}   

		/**
		 * Add Fee info in Installments for Admin.
		 *
		 * @param array  $installments Plan Installments.
		 * @param int $number Installment Number.
		 * @param Object $user_payment Plan.
		 */
		public function fee_info_for_admin( $installment, $number, $user_payment ) {
			$this->fee_info_in_installments( $installment, $number, $user_payment->get_installments() );
		}

		/**
		 * Add Fee info in Installments for Email and My Account Page.
		 *
		 * @param array  $installments Plan Installments.
		 * @param int $number Installment Number.
		 * @param Object $user_payment Plan.
		 */
		public function fee_info_in_email_and_page( $installment, $number, $user_payment ) {
			$this->fee_info_in_installments( $installment, $number, $user_payment->get_installments() );
		}

		/**
		 * Add Fee info in Installments for Cart and Checkout Page.
		 *
		 * @param array  $installments Plan Installments.
		 * @param int $number Installment Number.
		 * @param Object $plan Plan.
		 */
		public function fee_info_in_cart_and_checkout( $installment, $number, $plan ) {
			$this->fee_info_in_installments( $installment, $number, $plan->get_installments() );
		}

		/**
		 * Add Fee info in Installments.
		 *
		 * @param array  $installment Plan Installments.
		 * @param int $number Installment Number.
		 * @param Object $installments Plan.
		 */
		public function fee_info_in_installments( $installment, $number, $installments ) {
			if ( isset( $installment[ 'metadata' ][ 'efw_product_fee' ] ) ) {
				$installment_for = get_option( 'efw_productfee_apply_fee_on_installment' );
				if ( '1' == $installment_for ) {
					if ( 1 == $number ) { // To find the first installment
						echo wp_kses_post('&nbsp; (includes  ' . wc_price( $installment[ 'metadata' ][ 'efw_product_fee' ] ) . ' fee)');
					}
				} else if ( '2' == $installment_for ) {
					$end = count( $installments );
					if ( $end == $number ) { // To find the last installment
						echo wp_kses_post('&nbsp; (includes ' . wc_price( $installment[ 'metadata' ][ 'efw_product_fee' ] ) . ' fee)');
					}
				}
			}
		}
	}

}
