<?php

/**
 * WC Subscription Compatibility.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_WooCommerce_Subscription_Compatibility' ) ) {

	/**
	 * Class EFW_WooCommerce_Subscription_Compatibility.
	 */
	class EFW_WooCommerce_Subscription_Compatibility extends EFW_Compatibility {

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
			$this->id = 'wc-subscriptions';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 *  @return bool
		 * */
		public function is_plugin_enabled() {

			return class_exists( 'WC_Subscriptions' );
		}

		/**
		 * Action
		 */
		public function admin_action() {
			add_filter('efw_product_fee_settings', array( $this, 'wc_subscription_compatability_for_product_fee' ), 10);
			add_filter('efw_order_fee_settings', array( $this, 'wc_subscription_compatability_for_order_fee' ));
		}

		/**
		 * Action
		 */
		public function actions() {
			add_filter( 'efw_check_if_cart_contain_renewal', array( $this, 'check_is_cart_contain_renewal' ), 10, 2);
			add_filter( 'efw_cart_subtotal', array( $this, 'alter_cart_subtotal' ), 10, 2 );
			add_filter( 'efw_cart_order_total', array( $this, 'alter_cart_order_total' ), 10, 2 );
			add_filter( 'efw_product_fee_before_custom_item_data', array( $this, 'do_not_show_product_fee_in_cart' ), 20, 2 );
			add_action( 'woocommerce_checkout_subscription_created', array( $this, 'add_fee_details_in_subscription_object' ), 10, 3 );
			add_action( 'woocommerce_scheduled_subscription_payment', array( $this, 'remove_fee_for_manual_renewal' ), -10, 1 );
			add_filter( 'wcs_renewal_order_created', array( $this, 'add_fee_for_renewal_order' ), 10, 2 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'add_or_remove_fee_in_initial_order' ), 10, 3 );
			add_action( 'woocommerce_subscription_details_table', array( $this, 'add_fee_in_renewal_popup' ), 1 );
		}

		/**
		 * Get Product Fee Settings section array.
		 */
		public function wc_subscription_compatability_for_product_fee( $settings ) {
			$updated_settings = array();
			foreach ( $settings as $section ) {
				if ( isset( $section['id'] ) && 'efw_product_fee_settings' == $section['id'] &&
						isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
						$updated_settings[] = array(
							'title'   => esc_html__( 'Product Fee for Renewal Orders', 'extra-fees-for-woocommerce' ),
							'type'    => 'checkbox',
							'default' => 'no',
							'id'      => 'efw_productfee_fee_for_existing_subscription',
							'desc'    => esc_html__( 'If enabled, the product fee will be charged for renewal orders synced with the older subscriptions.', 'extra-fees-for-woocommerce' ),
							'class'   => 'show-if-product-fee-enable',
						);
				}

				if ( isset( $section['id'] ) && 'efw_product_fee_restriction_settings' == $section['id'] &&
						isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
						$updated_settings[] = array(
							'title'   => esc_html__( 'Restrict Product Fee for Renewal Orders', 'extra-fees-for-woocommerce' ),
							'type'    => 'checkbox',
							'default' => 'no',
							'id'      => 'efw_productfee_restrict_for_renewal',
							'desc'    => esc_html__( 'By enabling this checkbox, you can charge the product fee only for parent order.', 'extra-fees-for-woocommerce' ),
							'class'   => 'show-if-product-fee-enable',
						);
				}
				$updated_settings[] = $section;
			}
			return $updated_settings;
		}

		/**
		 * Get Order Fee Settings section array.
		 */
		public function wc_subscription_compatability_for_order_fee( $settings ) {
			$updated_settings = array();
			foreach ( $settings as $section ) {
				if ( isset( $section['id'] ) && 'efw_order_total_fee_settings' == $section['id'] &&
					isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
					$updated_settings[] = array(
						'title'   => esc_html__( 'Restrict Order Fee for Renewal Orders', 'extra-fees-for-woocommerce' ),
						'type'    => 'checkbox',
						'default' => 'no',
						'id'      => 'efw_ordertotalfee_restrict_for_renewal',
						'desc'    => esc_html__( 'By enabling this checkbox, you can charge the product fee only for parent order.', 'extra-fees-for-woocommerce' ),
						'class'   => 'show-if-order-fee-enable',
					);
				}
				$updated_settings[] = $section;
			}
		
			return $updated_settings;
		}

		/**
		 * Get the recurring total for the order.
		 *
		 * @param float  $subtotal Cart subtotal.
		 * @param Object $cart_object Cart Object.
		 */
		public function alter_cart_subtotal( $subtotal, $cart_object ) {
			static $bool = false;
			if ( false === $bool && empty( $cart_object->recurring_cart_key ) ) {
				$bool = true;
				$cart_object->calculate_totals();

				if (efw_check_is_array($cart_object->recurring_carts)) {
					foreach ( $cart_object->recurring_carts as $_cart ) {
						return $_cart->get_subtotal();
					}
				}
			}

			return $subtotal;
		}

		/**
		 * Get the recurring total for the order.
		 *
		 * @param float  $total Cart subtotal.
		 * @param Object $cart_object Cart Object.
		 */
		public function alter_cart_order_total( $total, $cart_object ) {
			static $bool = false;
			if ( false === $bool && empty( $cart_object->recurring_cart_key ) ) {
				$bool = true;
				$cart_object->calculate_totals();

				if (efw_check_is_array($cart_object->recurring_carts)) {
					foreach ( $cart_object->recurring_carts as $_cart ) {
						return (float) $_cart->get_total( 'edit' );
					}
				}
			}

			return $total;
		}

		/**
		 * Hide product fee in cart for renewal order.
		 *
		 * @param float $product_fee Product Fee.
		 * @param array $cart_item Cart Item.
		 */
		public function do_not_show_product_fee_in_cart( $product_fee, $cart_item ) {
			if ( isset( $cart_item['subscription_renewal']['renewal_order_id'] ) ) {
				$product_fee = null;
			}

			return $product_fee;
		}

		/**
		 * Add Fee detail in Subscription object.
		 *
		 * @param Object  $subscription Subscription Object.
		 * @param WP_Post $order Order Object.
		 * @param Object  $recurring_cart Recurring Cart Object.
		 */
		public function add_fee_details_in_subscription_object( $subscription, $order, $recurring_cart ) {
			//Add Gateway Fee detail in Subscription Object.
			self::add_gateway_fee_details($subscription, $order, $recurring_cart);
			//Add Product Fee detail in Subscription Object
			self::add_product_fee_details($subscription, $order);
		}

		/**
		 * Add Gateway Fee detail in Subscription object.
		 *
		 * @param Object  $subscription Subscription Object.
		 * @param WP_Post $order Order Object.
		 * @param Object  $recurring_cart Recurring Cart Object.
		 */
		public function add_gateway_fee_details( $subscription, $order, $recurring_cart ) {

			$subscription_id = $subscription->get_id();
			foreach ( $recurring_cart->get_fees() as $fee_key => $fee ) {
				if ( 'efw_gateway_fee' != $fee_key ) {
					continue;
				}

				$subscription->update_meta_data( 'efw_gateway_fee_name', $fee->name );
				$subscription->update_meta_data( 'efw_gateway_fee_amount', $fee->amount );
				$subscription->update_meta_data( 'efw_gateway_fee_tax_class', $fee->tax_class );
				$subscription->update_meta_data( 'efw_gateway_fee_taxable', $fee->taxable );
			}
		}

		/**
		 * Add Product Fee detail in Subscription object.
		 *
		 * @param Object  $subscription Subscription Object.
		 * @param WP_Post $order Order Object.
		 */
		public function add_product_fee_details( $subscription, $order ) {

			$this->update_subscription_item_meta( $subscription );

			$subscription_id = $subscription->get_id();

			$this->add_fee_in_renewal_order( $order, $subscription, false );

			$subscription->calculate_totals();

			$subscription->save();
		}

		/**
		 * Add Fee for Renewal Order.
		 *
		 * @param int $subscription Subscription.
		 */
		public function add_fee_for_renewal_order( $renewal_order, $subscription ) {
			if ( ! empty( $subscription->get_meta( 'efw_product_fee_name' ) ) ) {
				return $renewal_order;
			}

			if ( ! $this->apply_product_fee_for_existing_subscription()) {
				return $renewal_order;
			}

			$this->update_subscription_item_meta( $subscription );

			$this->add_fee_in_renewal_order( $renewal_order, $subscription );

			$subscription->calculate_totals();

			$subscription->save();

			$renewal_order->calculate_totals();

			$renewal_order->save();

			return $renewal_order;
		}

		/**
		 * May be update to new subscription price for early renewal payment(via modal) if the price is changed in the subscription product.
		 */
		public function add_fee_in_renewal_popup( $subscription ) {
			
			if ( ! $this->apply_product_fee_for_existing_subscription()) {
				return;
			}

			$this->update_subscription_item_meta( $subscription );

			$product_fee_name = $subscription->get_meta( 'efw_product_fee_name' );
			if ( empty( $product_fee_name ) ) {
				$latest_renewal_order = $subscription->get_last_order( 'all', 'renewal' );
				if ( ! $latest_renewal_order ) {
					$latest_renewal_order = $subscription->get_last_order( 'all', 'any' );
				}

				if ( empty( $latest_renewal_order ) ) {
					return;
				}

				$this->add_fee_in_renewal_order( $latest_renewal_order, $subscription );
			}

			$subscription->calculate_totals();

			$subscription->save();
		}

		/**
		 * Remove Fee for Manual Renewal.
		 *
		 * @param int $subscription_id Subscription ID.
		 */
		public function remove_fee_for_manual_renewal( $subscription_id ) {

			$subscription = is_object( $subscription_id ) ? $subscription_id : wcs_get_subscription( $subscription_id ) ;

			if ( $subscription->is_manual() ) {
				$gateway_fee_name = $subscription->get_meta( 'efw_gateway_fee_name' );
				$this->fee_removal( $subscription, $gateway_fee_name );
			}

			if ( ! $this->apply_product_fee_for_existing_subscription()) {
				return;
			}

			$this->update_subscription_item_meta( $subscription );

			$product_fee_name = $subscription->get_meta( 'efw_product_fee_name' );
			if ( empty( $product_fee_name ) ) {
				$latest_renewal_order = $subscription->get_last_order( 'all', 'renewal' );
				if ( empty( $latest_renewal_order ) ) {
					return;
				}

				$this->add_fee_in_renewal_order( $latest_renewal_order, $subscription );

				$subscription->calculate_totals();

				$subscription->save();
			}
		}

		/**
		 * Update Subscription Item Meta.
		 *
		 * @param int $subscription SUbscription Object.
		 */
		public function apply_product_fee_for_existing_subscription() {
			return ( 'yes' == get_option('efw_productfee_fee_for_existing_subscription') );
		}

		/**
		 * Update Subscription Item Meta.
		 *
		 * @param int $subscription SUbscription Object.
		 */
		public function update_subscription_item_meta( $subscription ) {
			foreach ( $subscription->get_items() as $item_id => $each_item ) {
					
				$product_id = ! empty( $each_item['variation_id'] ) ? $each_item['variation_id'] : $each_item['product_id'];

				$product = wc_get_product( $product_id );

				if ( ! is_object( $product ) ) {
					continue;
				}

				$price = wc_get_price_excluding_tax( $product ) + efw_get_wc_signup_fee( $product );

				$product_fee = EFW_Fees_Handler::product_fee( $product_id, $price, $subscription->get_user_id(), 'fee' );
				if ( empty( $product_fee ) ) {
					continue;
				}

				$qty = isset( $each_item['quantity'] ) ? $each_item['quantity'] : 1;
				$qty = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $qty;

				$product_fee = EFW_Fees_Handler::rule_fees( $product_id, $product_fee, $price, $qty );
	
				$key_name = efw_get_fee_text( $product_id );

				$each_item->update_meta_data( $key_name, wc_price( $product_fee * $qty ) );

				$rule_fee_texts = efw_get_rule_fee_text( $product_id, $price, $qty );

				if ( efw_check_is_array( $rule_fee_texts ) ) {
					foreach ( $rule_fee_texts as $rule_id => $rule_fee_value ) {
						$rule_object = efw_get_fee_rule( $rule_id );
						if ( ! is_object( $rule_object ) ) {
							continue;
						}

						$rule_fee_value = $rule_fee_value * $qty;
						$each_item->update_meta_data( $rule_object->get_fee_text(), wc_price( $rule_fee_value ) );
					}
				}

				$each_item->save();
			}
		}

		/**
		 * Add Fee in Renewal Order.
		 *
		 * @param int $latest_renewal_order Order Object.
		 * @param int $subscription Subscription Object.
		 */
		public function add_fee_in_renewal_order( $latest_renewal_order, $subscription, $add_fee = true ) {
			if ('yes' != get_option( 'efw_productfee_enable' )) {
				return;
			}

			$total_product_fee = array();
			$tax_class = ( 'yes' == get_option( 'efw_productfee_tax_setup' ) ) ? get_option( 'efw_productfee_tax_class', 'standard' ) : '';
			$taxable   = ( 'not-required' === $tax_class || 'no' === get_option( 'efw_productfee_tax_setup' ) ) ? false : true;
			
			foreach ( $latest_renewal_order->get_items() as $item_id => $each_item ) {

				$product_id = ! empty( $each_item['variation_id'] ) ? $each_item['variation_id'] : $each_item['product_id'];

				$product = wc_get_product( $product_id );

				if ( ! is_object( $product ) ) {
					continue;
				}

				$price = wc_get_price_excluding_tax( $product ) + efw_get_wc_signup_fee( $product );

				$product_fee = EFW_Fees_Handler::product_fee( $product_id, $price, $latest_renewal_order->get_user_id(), 'fee' );
				if ( empty( $product_fee ) ) {
					continue;
				}

				$qty = isset( $each_item['quantity'] ) ? $each_item['quantity'] : 1;
				$qty = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $qty;

				$product_fee = EFW_Fees_Handler::rule_fees( $product_id, $product_fee, $price, $qty );

				$total_product_fee[] = $product_fee * $qty;
	
				$key_name = efw_get_fee_text( $product_id );

				$each_item->update_meta_data( $key_name, wc_price( $product_fee * $qty ) );

				$rule_fee_texts = efw_get_rule_fee_text( $product_id, $price, $qty ) ;

				if ( efw_check_is_array( $rule_fee_texts ) ) {
					foreach ( $rule_fee_texts as $rule_id => $rule_fee_value ) {
						$rule_object = efw_get_fee_rule( $rule_id );
						if ( ! is_object( $rule_object ) ) {
							continue;
						}

						$rule_fee_value = $rule_fee_value * $qty;
						$each_item->update_meta_data( $rule_object->get_fee_text(), wc_price( $rule_fee_value ) );
					}
				}

				if ( $add_fee ) {
					if ( 'yes' == get_option( 'efw_productfee_tax_setup' ) || 'yes' == get_option( 'efw_productfee_qty_restriction_enabled' ) ) {
						continue;
					}

					$price_to_alter = ( (float) $each_item['total'] + ( $product_fee * $qty ) );
					self::alter_subscription_price( $subscription );

					$each_item->set_subtotal( $price_to_alter );
					$each_item->set_total( $price_to_alter );

					$subscription->update_meta_data( 'efw_product_fee_name', $key_name );
					$subscription->update_meta_data( 'efw_product_fee_amount', $product_fee );
					$subscription->update_meta_data( 'efw_product_fee_tax_class', $tax_class );
					$subscription->update_meta_data( 'efw_product_fee_taxable', $taxable );
				} else {
					$subscription->update_meta_data( 'efw_product_fee_name', $key_name );
					$subscription->update_meta_data( 'efw_product_fee_amount', $product_fee );
					$subscription->update_meta_data( 'efw_product_fee_tax_class', $tax_class );
					$subscription->update_meta_data( 'efw_product_fee_taxable', $taxable );
				}

				$each_item->save();
			}

			$total_fee_amount = array_sum( $total_product_fee );
			if ( $add_fee && ! empty( $total_fee_amount ) ) {
				if ( 'yes' == get_option( 'efw_productfee_tax_setup' ) || 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) {
					$fee_args = array(
						'amount' => $total_fee_amount,
						'total' => $total_fee_amount,
						'name' => ! empty( get_option( 'efw_productfee_overall_fee_text' ) ) ? get_option( 'efw_productfee_overall_fee_text' ) : 'Product Fee',
						'tax_status' => $taxable,
						'tax_class' => $tax_class,
					);
		
					$meta_args = array(
						'amount' => 'efw_product_fee_amount',
						'name' => 'efw_product_fee_name',
						'tax_status' => 'efw_product_fee_taxable',
						'tax_class' => 'efw_product_fee_tax_class',
					);
		
					$this->add_fee_to_subscription( $subscription, $fee_args, $meta_args );

					$this->add_fee_to_renewal_order( $latest_renewal_order, $fee_args, $meta_args );
				}
			}
		}

		/**
		 * Alter Subscription Price.
		 *
		 * @param int $subscription SUbscription Object.
		 */
		public function alter_subscription_price( $subscription ) {
			foreach ( $subscription->get_items( 'line_item' ) as $each_item ) {
				$product = $each_item->get_product();
				if ( ! $product ) {
					continue;
				}

				if ( 'yes' == $each_item->get_meta('_efw_price_updated')) {
					continue;
				}

				$product_id = ! empty( $each_item['variation_id'] ) ? $each_item['variation_id'] : $each_item['product_id'];

				$price = wc_get_price_excluding_tax( $product ) + efw_get_wc_signup_fee( $product );

				$product_fee = EFW_Fees_Handler::product_fee( $product_id, $price, $subscription->get_user_id(), 'fee' );
				if ( empty( $product_fee ) ) {
					continue;
				}

				$qty = isset( $each_item['quantity'] ) ? $each_item['quantity'] : 1;
				$qty = ( 'yes' === get_option( 'efw_productfee_qty_restriction_enabled' ) ) ? 1 : $qty;

				$product_fee = EFW_Fees_Handler::rule_fees( $product_id, $product_fee, $price, $qty );

				$price_to_alter = ( (float) $each_item['total'] + ( $product_fee * $qty ) );

				$each_item->set_subtotal( $price_to_alter );
				$each_item->set_total( $price_to_alter );

				$each_item->update_meta_data('_efw_price_updated', 'yes');

				$each_item->save();
			}
		}

		/**
		 * Add or Remove Fee in Initial Order.
		 *
		 * @param int    $order_id Order ID.
		 * @param string $old_order_status Previous Order Status.
		 * @param int    $new_order_status New Order Status.
		 */
		public function add_or_remove_fee_in_initial_order( $order_id, $old_order_status, $new_order_status ) {

			//Add Gateway Fee detail in Order Object.
			self::add_or_remove_gateway_fee_in_order($order_id);
			//Add Product Fee detail in Order Object
			self::add_or_remove_product_fee_in_order($order_id);
		}

		/**
		 * Add or Remove Fee in Initial Order.
		 *
		 * @param int $order_id Order ID.
		 * @param int $fee_name Fee name.
		 */
		public function add_or_remove_gateway_fee_in_order( $order_id ) {
			if ( wcs_order_contains_subscription( $order_id, 'parent' ) ) {

				$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'parent' ) );

				foreach ( $subscriptions as $subscription ) {

					$subscription_id = $subscription->get_id();

					if ( $subscription->is_manual() ) {
						$fee_name = $subscription->get_meta( 'efw_gateway_fee_name' );
						$this->fee_removal( $subscription, $fee_name );
					}

					$subscription->calculate_totals();

					$subscription->save();
				}
			} elseif ( wcs_order_contains_subscription( $order_id, 'renewal' ) ) {

				$order         = wc_get_order( $order_id );
				$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'renewal' ) );

				foreach ( $subscriptions as $subscription ) {

					$subscription_id = $subscription->get_id();

					$fee_name = $subscription->get_meta( 'efw_gateway_fee_name' );

					if ( ! efw_check_is_array( $order->get_items( 'fee' ) ) ) {
						if ( $subscription->is_manual() ) {
							$this->fee_removal( $subscription, $fee_name );

							$subscription->calculate_totals();

							$subscription->save();

							continue;
						}
					}

					foreach ( $order->get_items( 'fee' ) as $item_id => $item_fee ) {

						if ( ! $subscription->is_manual() ) {
							if ( $fee_name === $item_fee->get_name() ) {

								$this->fee_removal( $subscription, $fee_name );

								$fee_args = array(
									'amount' => $item_fee->get_amount(),
									'total' => $item_fee->get_total(),
									'name' => $item_fee->get_name(),
									'tax_status' => $item_fee->get_tax_status(),
									'tax_class' => $item_fee->get_tax_class(),
								);

								$meta_args = array(
									'amount' => 'efw_gateway_fee_amount',
									'name' => 'efw_gateway_fee_name',
									'tax_status' => 'efw_gateway_fee_taxable',
									'tax_class' => 'efw_gateway_fee_tax_class',
								);

								$this->add_fee_to_subscription( $subscription, $fee_args, $meta_args );
							}
						} else {
							$this->fee_removal( $subscription, $fee_name );
						}
					}

					$subscription->calculate_totals();

					$subscription->save();
				}
			}
		}

		/**
		 * Add or Remove Fee in Initial Order.
		 *
		 * @param int $order_id Order ID.
		 * @param int $fee_name Fee name.
		 */
		public function add_or_remove_product_fee_in_order( $order_id ) {
			if ( wcs_order_contains_subscription( $order_id, 'parent' ) ) {

				$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'parent' ) );

				foreach ( $subscriptions as $subscription ) {

					$subscription_id = $subscription->get_id();

					if ( $subscription->is_manual() ) {
						$fee_name = $subscription->get_meta( 'efw_product_fee_name' );
						$this->fee_removal( $subscription, $fee_name );
					}

					$subscription->calculate_totals();

					$subscription->save();
				}
			} elseif ( wcs_order_contains_subscription( $order_id, 'renewal' ) ) {

				$order         = wc_get_order( $order_id );
				$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'renewal' ) );

				foreach ( $subscriptions as $subscription ) {

					$subscription_id = $subscription->get_id();

					$fee_name = $subscription->get_meta( 'efw_product_fee_name' );

					if ( ! efw_check_is_array( $order->get_items( 'fee' ) ) ) {
						if ( $subscription->is_manual() ) {
							$this->fee_removal( $subscription, $fee_name );

							$subscription->calculate_totals();

							$subscription->save();

							continue;
						}
					}

					foreach ( $order->get_items( 'fee' ) as $item_id => $item_fee ) {

						if ( ! $subscription->is_manual() ) {
							if ( $fee_name === $item_fee->get_name() ) {

								$this->fee_removal( $subscription, $fee_name );
								
								$fee_args = array(
									'amount' => $item_fee->get_amount(),
									'total' => $item_fee->get_total(),
									'name' => $item_fee->get_name(),
									'tax_status' => $item_fee->get_tax_status(),
									'tax_class' => $item_fee->get_tax_class(),
								);

								$meta_args = array(
									'amount' => 'efw_product_fee_amount',
									'name' => 'efw_product_fee_name',
									'tax_status' => 'efw_product_fee_taxable',
									'tax_class' => 'efw_product_fee_tax_class',
								);

								$this->add_fee_to_subscription( $subscription, $fee_args, $meta_args );
							}
						} else {
							$this->fee_removal( $subscription, $fee_name );
						}
					}

					$subscription->calculate_totals();

					$subscription->save();
				}
			}
		}

		/**
		 * Remove Fee.
		 *
		 * @param Object $subscription Subscription Object.
		 * @param string $fee_name Fee name to remove.
		 */
		public function fee_removal( $subscription, $fee_name ) {
			foreach ( $subscription->get_items( 'fee' ) as $subscription_item_id => $subscription_item_fee ) {
				if ( $fee_name === $subscription_item_fee->get_name() ) {
					$subscription->remove_item( $subscription_item_fee->get_id() );
				}
			}
		}

		/**
		 * Add Fee to SUbscription Object.
		 *
		 * @param Object $subscription Subscription Object.
		 * @param string $fee_args Fee Args.
		 * @param string $meta_args Meta Args.
		 */
		public function add_fee_to_renewal_order( $renewal_order, $fee_args, $meta_args ) {
			$fees = $renewal_order->get_fees();
			if ( efw_check_is_array( $fees ) ) {
				foreach ( $fees as $item_id => $item ) {
					if ( $item->get_name() === $fee_args['name'] ) {
						$item->set_total( $fee_args['amount'] );
						$item->save();
					}
				}
			} else {
				$item_fee = new WC_Order_Item_Fee();

				$item_fee->set_id( 'efw_product_fee' );
				$item_fee->set_name( $fee_args['name'] );
				$item_fee->set_amount( $fee_args['amount'] );
				if ( ! empty($fee_args['tax_class']) && ( 'not-required' != $fee_args['tax_class'] )) {
					if ( ! in_array( $fee_args['tax_class'], array( 'standard', 'not-required' ) ) ) {
						$item_fee->set_tax_class( $fee_args['tax_class'] );
					}
					$item_fee->set_tax_status( $fee_args['tax_status'] );
				} else {
					$item_fee->set_tax_class( '' );
					$item_fee->set_tax_status( 'none' );
				}
				$item_fee->set_total( $fee_args['amount'] );
	
				// Add fee to the order.
				$renewal_order->add_item( $item_fee );
			}

			$renewal_order->save();
		}

		/**
		 * Add Fee to SUbscription Object.
		 *
		 * @param Object $subscription Subscription Object.
		 * @param string $fee_args Fee Args.
		 * @param string $meta_args Meta Args.
		 */
		public function add_fee_to_subscription( $subscription, $fee_args, $meta_args ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_amount( $fee_args['amount'] );
			$fee->set_total( $fee_args['total'] );
			$fee->set_name( $fee_args['name'] );
			if ( ! empty($fee_args['tax_class']) && ( 'not-required' != $fee_args['tax_class'] ) ) {
				$fee->set_tax_status( $fee_args['tax_status'] );
				$fee->set_tax_class( $fee_args['tax_class'] );
				$subscription->update_meta_data( $meta_args['tax_class'], $fee_args['tax_class'] );
				$subscription->update_meta_data( $meta_args['tax_status'], $fee_args['tax_status'] );
			} else {
				$fee->set_tax_status( 'none' );
				$fee->set_tax_class( '' );
				$subscription->update_meta_data( $meta_args['tax_class'], '' );
				$subscription->update_meta_data( $meta_args['tax_status'], 'none' );
			}

			$fee->save();

			$subscription->update_meta_data( $meta_args['name'], $fee_args['name'] );
			$subscription->update_meta_data( $meta_args['amount'], $fee_args['amount'] );

			$subscription->add_item( $fee );
		}

		/**
		 * Remove Fee from cart in Renewal Order.
		 */
		public function check_is_cart_contain_renewal( $bool ) {
			$cart_item = wcs_cart_contains_renewal();
			
			if (isset($cart_item['subscription_renewal']['renewal_order_id'])) {
				return true;
			}
			
			return $bool;
		}
	}

}
