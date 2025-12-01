<?php
if ( ! class_exists( 'BWFAN_WC_Create_Order' ) ) {
	final class BWFAN_WC_Create_Order extends BWFAN_Action {

		private static $ins = null;

		protected function __construct() {
			$this->action_name     = __( 'Create Order', 'wp-marketing-automations-pro' );
			$this->action_desc     = __( 'This action creates a new WooCommerce order for a customer', 'wp-marketing-automations-pro' );
			$this->action_priority = 7;
			$this->support_v2      = true;
			$this->support_v1      = false;
		}

		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 * Generate the order data for processing
		 *
		 * @param array $automation_data
		 * @param array $step_data
		 *
		 * @return array
		 */
		public function make_v2_data( $automation_data, $step_data ) {
			$data_to_set = [];

			$data_to_set['status']              = $step_data['status'] ?? 'wc-on-hold';
			$data_to_set['is_shipping_address'] = $step_data['is_shipping_address'] ?? 'same_as_billing';

			/** Validate and set email */
			$email                      = isset( $step_data['billing_email'] ) ? BWFAN_Common::decode_merge_tags( $step_data['billing_email'] ) : '';
			$user                       = is_email( $email ) ? get_user_by( 'email', $email ) : '';
			$data_to_set['customer_id'] = $user instanceof WP_User ? $user->ID : 0;

			/** Set products */
			$data_to_set['products'] = $step_data['products'] ?? [];

			/** Set billing and shipping information */
			$data_to_set['billing']    = self::get_billing_addresses( $step_data, $email );
			$data_to_set['shipping']   = self::get_shipping_addresses( $step_data );
			$data_to_set['order_note'] = BWFAN_Common::decode_merge_tags( $step_data['order_note'] ) ?? '';

			/**  Payment method */
			$data_to_set['payment_method']         = BWFAN_Common::decode_merge_tags( $step_data['payment_method'] ) ?? '';
			$data_to_set['shipping_methods']       = BWFAN_Common::decode_merge_tags( $step_data['shipping_methods'] ) ?? '';
			$data_to_set['shipping_cost']          = isset( $step_data['shipping_cost'] ) ? BWFAN_Common::decode_merge_tags( $step_data['shipping_cost'] ) : '';
			$data_to_set['contact_id']             = $automation_data['global']['contact_id'] ?? $automation_data['global']['cid'] ?? 0;
			$data_to_set['bwfan_coupons']          = self::check_for_available_coupons( $step_data );
			$data_to_set['skip_order_if_products'] = isset( $step_data['skip_order_if_products'] ) && (bool) $step_data['skip_order_if_products'];
			$data_to_set['order_custom_fields']    = ! empty( $step_data['order_custom_fields'] ) ? $step_data['order_custom_fields'] : [];

			return $data_to_set;
		}

		/**
		 * Process the order creation
		 *
		 * @return array
		 */
		public function process_v2() {
			if ( ! function_exists( 'wc_create_order' ) ) {
				return $this->error_response( __( '`wc_create_order` function is missing.', 'wp-marketing-automations-pro' ) );
			}

			$order_data = $this->data;
			if ( ! isset( $order_data['products'] ) || empty( $order_data['products'] ) ) {
				return $this->skipped_response( __( 'No products found. Add at least one product to proceed with the order.', 'wp-marketing-automations-pro' ) );
			}

			/** Validate email */
			if ( empty( $order_data['billing']['email'] ) || ! is_email( $order_data['billing']['email'] ) ) {
				return $this->error_response( __( 'Invalid or missing email address.', 'wp-marketing-automations-pro' ) );
			}

			$product_validation = self::validate_order_products( $order_data['products'] );
			if ( empty( $product_validation ) ) {
				return $this->error_response( __( 'Order not created. No valid products found.', 'wp-marketing-automations-pro' ) );
			}

			if ( isset( $order_data['skip_order_if_products'] ) && $order_data['skip_order_if_products'] && count( $product_validation ) !== count( $order_data['products'] ) ) {
				return $this->skipped_response( __( 'Order not created. Some products are invalid or missing.', 'wp-marketing-automations-pro' ) );
			}

			$args  = array(
				'created_via' => __( 'Order created via FunnelKit Automations.', 'wp-marketing-automations-pro' ),
				'customer_id' => $order_data['customer_id'],
			);
			$order = wc_create_order( $args );
			if ( is_wp_error( $order ) ) {
				return $this->error_response( __( 'Failed to create order: ', 'wp-marketing-automations-pro' ) . $order->get_error_message() );
			}

			/** Add validated products */
			foreach ( $product_validation as $item ) {
				$order->add_product( $item['product'], $item['quantity'], isset( $item['price'] ) ? [ 'totals' => [ 'subtotal' => $item['price'], 'total' => $item['price'] ] ] : [] );
			}

			/** Set addresses */
			$order->set_address( $order_data['billing'] );
			if ( 'same_as_billing' === $order_data['is_shipping_address'] ) {
				$order->set_address( $order_data['billing'], 'shipping' );
			} elseif ( 'different_shipping_address' === $order_data['is_shipping_address'] ) {
				$order->set_address( $order_data['shipping'], 'shipping' );
			}

			/** Set shipping methods */
			if ( ! empty( $order_data['shipping_methods'] ) ) {
				$shipping_method_id = is_array( $order_data['shipping_methods'] ) ? reset( $order_data['shipping_methods'] ) : $order_data['shipping_methods'];
				$shipping_method    = $this->set_shipping_method( $shipping_method_id, $order_data['shipping_cost'] );
				if ( ! empty( $shipping_method ) ) {
					$order->add_item( $shipping_method );
				}
			}
			// Calculate totals after adding products and shipping methods
			$order->calculate_totals();

			/** Add coupons */
			if ( ! empty( $order_data['bwfan_coupons'] ) && is_array( $order_data['bwfan_coupons'] ) ) {
				foreach ( $order_data['bwfan_coupons'] as $coupon_code ) {
					try {
						$result = $order->apply_coupon( $coupon_code );
						if ( is_wp_error( $result ) ) {
							$error_message = $result->get_error_message();
							BWFAN_Common::log_test_data( sprintf( 'Coupon "%s" failed to apply on Order #%d: %s', $coupon_code, $order->get_id(), $error_message ), 'fka_order_created_log', true );
						}
					} catch ( Error $e ) {
						return $this->skipped_response( sprintf( __( 'Order #%d was created but further processing was skipped due to coupon error: %s', 'wp-marketing-automations-pro' ), $order->get_id(), $e->getMessage() ) );
					}
				}
			}

			/** Add custom fields */
			if ( ! empty( $order_data['order_custom_fields'] ) ) {
				foreach ( $order_data['order_custom_fields'] as $field ) {
					if ( ! empty( $field['field'] ) && ! empty( $field['field_value'] ) ) {
						$order->update_meta_data( $field['field'], BWFAN_Common::decode_merge_tags( $field['field_value'] ) );
					}
				}
			}

			/** Set order note */
			$order->add_order_note( $args['created_via'] );

			/** Set payment method */
			$order->set_payment_method( $order_data['payment_method'] );

			/** Add order note */
			if ( isset( $order_data['order_note'] ) && ! empty( trim( $order_data['order_note'] ) ) ) {
				$order->add_order_note( $order_data['order_note'] );
			}

			/** Set contact ID */
			$order->update_meta_data( '_woofunnel_cid', $order_data['contact_id'] ?? 0 );

			/** set status */
			$order->update_status( $order_data['status'] );

			$order->save();

			do_action( 'bwfan_order_created', $order, $order->get_id(), $order_data );

			return $this->success_message( __( 'Order created successfully.', 'wp-marketing-automations-pro' ) );
		}

		/**
		 * Check for available coupons
		 *
		 * @param $data
		 *
		 * @return array
		 */
		public static function check_for_available_coupons( $data ) {
			if ( ! isset( $data['couponType'] ) ) {
				return [];
			}

			$coupons = [];
			switch ( $data['couponType'] ) {
				case 'static':
					if ( ! empty( $data['bwfan_coupons'] ) || ! is_array( $data['bwfan_coupons'] ) ) {
						foreach ( $data['bwfan_coupons'] as $coupon ) {
							if ( ! empty( $coupon['name'] ) ) {
								$coupons[] = wc_format_coupon_code( BWFAN_Common::decode_merge_tags( $coupon['name'] ) );
							}
						}
					}
					break;
				case 'dynamic':
					if ( ! empty( $data['dynamic_coupon'] ) ) {
						$dynamic_coupon = explode( ',', $data['dynamic_coupon'] );
						foreach ( $dynamic_coupon as $coupon ) {
							$coupons[] = ! empty( $coupon ) ? wc_format_coupon_code( BWFAN_Common::decode_merge_tags( $coupon ) ) : '';
						}
					}
					break;
				default:
					return [];
			}

			return array_filter( $coupons );
		}

		/**
		 * @param $method_id
		 * @param $shipping_cost
		 *
		 * @return WC_Order_Item_Shipping|null
		 */
		private function set_shipping_method( $method_id, $shipping_cost ) {
			if ( ! class_exists( '\WC_Order_Item_Shipping' ) ) {
				return null;
			}

			$shipping_methods = $this->get_available_shipping_methods();
			if ( empty( $shipping_methods[ $method_id ] ) ) {
				return null;
			}

			$item = new \WC_Order_Item_Shipping();
			try {
				$item->set_method_title( $shipping_methods[ $method_id ] );
				$item->set_method_id( $method_id );
				$item->set_total( floatval( $shipping_cost ) );
			} catch ( \Exception $e ) {
				$item = null;
			}

			return $item;
		}

		/**
		 * @param $products_data
		 *
		 * @return array
		 */
		public static function validate_order_products( $products_data ) {
			$valid_products   = [];
			$invalid_products = [];
			if ( empty( $products_data ) || ! is_array( $products_data ) ) {
				BWFAN_Common::log_test_data( __( 'No products found. Add at least one product to proceed with the order.', 'wp-marketing-automations-pro' ), 'order_creation_errors', true );

				return $valid_products;
			}

			foreach ( $products_data as $product_group ) {
				if ( ! isset( $product_group['products'] ) || ! is_array( $product_group['products'] ) ) {
					BWFAN_Common::log_test_data( __( 'Invalid product group: products array missing or invalid', 'wp-marketing-automations-pro' ), 'order_creation_errors', true );
					continue;
				}
				foreach ( $product_group['products'] as $product ) {
					$product_id = is_string( $product['id'] ) ? BWFAN_Common::decode_merge_tags( $product['id'] ) : $product['id'];
					if ( ! is_numeric( $product_id ) ) {
						$invalid_products[] = $product['id'];
						BWFAN_Common::log_test_data( sprintf( __( 'Non-numeric product ID: "%s" (decoded: "%s")', 'wp-marketing-automations-pro' ), $product['id'], $product_id ), 'order_creation_errors', true );
						continue;
					}

					$product_obj = wc_get_product( $product_id );
					if ( ! $product_obj || ! $product_obj->exists() ) {
						$invalid_products[] = $product['id'];
						BWFAN_Common::log_test_data( sprintf( __( 'Product does not exist: %d', 'wp-marketing-automations-pro' ), $product_id ), 'order_creation_errors', true );
						continue;
					}

					$product_data = [
						'product'  => $product_obj,
						'quantity' => isset( $product_group['quantity'] ) && intval( $product_group['quantity'] ) > 0 ? floatval( $product_group['quantity'] ) : 1,
					];
					if ( isset( $product_group['price'] ) && '' !== $product_group['price'] ) {
						$product_data['price'] = floatval( $product_group['price'] ) * $product_data['quantity'];
					}
					$valid_products[] = $product_data;
				}
			}

			return $valid_products;
		}

		/**
		 * Returns decoded billing data
		 *
		 * @param $step_data
		 * @param $email
		 *
		 * @return array
		 */
		static function get_billing_addresses( $step_data, $email ) {
			$country    = BWFAN_Common::decode_merge_tags( $step_data['billing_country'][0]['id'] ?? '' );
			$code       = $step_data['billing_state'][0]['id'] ?? '';
			$state_name = $step_data['billing_state'][0]['name'] ?? '';
			$state      = self::validate_state_for_country( $code, $country, $state_name );

			return [
				'first_name' => BWFAN_Common::decode_merge_tags( $step_data['billing_first_name'] ?? '' ),
				'last_name'  => BWFAN_Common::decode_merge_tags( $step_data['billing_last_name'] ?? '' ),
				'company'    => BWFAN_Common::decode_merge_tags( $step_data['billing_company'] ?? '' ),
				'address_1'  => BWFAN_Common::decode_merge_tags( $step_data['billing_address_line_1'] ?? '' ),
				'address_2'  => BWFAN_Common::decode_merge_tags( $step_data['billing_address_line_2'] ?? '' ),
				'city'       => BWFAN_Common::decode_merge_tags( $step_data['billing_city'] ?? '' ),
				'postcode'   => BWFAN_Common::decode_merge_tags( $step_data['billing_postcode'] ?? '' ),
				'country'    => $country,
				'state'      => $state,
				'email'      => $email,
				'phone'      => BWFAN_Common::decode_merge_tags( $step_data['billing_phone_no'] ?? '' ),
			];
		}

		/**
		 * Returns decoded shipping addresses
		 *
		 * @param $step_data
		 *
		 * @return array
		 */
		static function get_shipping_addresses( $step_data ) {
			$country    = BWFAN_Common::decode_merge_tags( $step_data['shipping_country'][0]['id'] ?? '' );
			$code       = $step_data['shipping_state'][0]['id'] ?? '';
			$state_name = $step_data['shipping_state'][0]['name'] ?? '';
			$state      = self::validate_state_for_country( $code, $country, $state_name );

			return [
				'first_name' => BWFAN_Common::decode_merge_tags( $step_data['shipping_first_name'] ?? '' ),
				'last_name'  => BWFAN_Common::decode_merge_tags( $step_data['shipping_last_name'] ?? '' ),
				'company'    => BWFAN_Common::decode_merge_tags( $step_data['shipping_company'] ?? '' ),
				'address_1'  => BWFAN_Common::decode_merge_tags( $step_data['shipping_address_line_1'] ?? '' ),
				'address_2'  => BWFAN_Common::decode_merge_tags( $step_data['shipping_address_line_2'] ?? '' ),
				'city'       => BWFAN_Common::decode_merge_tags( $step_data['shipping_city'] ?? '' ),
				'postcode'   => BWFAN_Common::decode_merge_tags( $step_data['shipping_postcode'] ?? '' ),
				'country'    => $country,
				'state'      => BWFAN_Common::decode_merge_tags( $state ),
				'phone'      => BWFAN_Common::decode_merge_tags( $step_data['shipping_phone_no'] ?? '' ),
			];
		}

		/**
		 * Validate if state exists for the given country
		 *
		 * @param string $state_code
		 * @param string $country
		 * @param string $state_name
		 *
		 * @return string
		 */
		static function validate_state_for_country( $state_code, $country, $state_name ) {
			if ( empty( $country ) ) {
				return '';
			}

			$states = WC()->countries->get_states( $country );
			if ( empty( $states ) ) {
				return $state_name;
			}

			return isset( $states[ $state_code ] ) ? $state_code : '';
		}

		/**
		 * Added Field schema
		 */
		public function get_fields_schema() {
			$status               = BWFAN_PRO_Common::prepared_field_options( wc_get_order_statuses() );
			$set_payment_gateways = array_replace( [ '' => 'N/A' ], self:: payment_gateways() );
			$payment_gateways     = BWFAN_PRO_Common::prepared_field_options( $set_payment_gateways );
			$schema               = [
				[
					'id'           => 'products',
					'label'        => __( 'Products', 'wp-marketing-automations-pro' ),
					'type'         => 'repeater',
					'add_btn_text' => __( 'Add Product', 'wp-marketing-automations-pro' ),
					'class'        => 'bwf-repeater-alter-w',
					'required'     => true,
					"hint"         => __( "Quantity and price are optional. If left blank, a quantity of 1 and the product's default unit price will be applied.", 'wp-marketing-automations-pro' ),
					'tip'          => __( 'Select products or use merge tag (e.g., {{product_id}}) to dynamically set the product during order creation.', 'wp-marketing-automations-pro' ),
					'fields'       => [
						[
							'id'                  => 'products',
							'type'                => 'custom_search',
							'autocompleterOption' => [
								'path'          => 'wc_new_order_product',
								'slug'          => 'wc_new_order_product',
								'labelText'     => __( 'Product', 'wp-marketing-automations-pro' ),
								'freeTextLabel' => __( 'Use merge tag {{query /}} to dynamically insert a product ID', 'wp-marketing-automations-pro' ),
							],
							'label'               => __( 'Product', 'wp-marketing-automations-pro' ),
							"allowFreeTextSearch" => true,
							"multiple"            => false,
						],
						[
							'id'          => 'quantity',
							'type'        => 'number',
							'label'       => __( 'Quantity', 'wp-marketing-automations-pro' ),
							'placeholder' => __( 'Quantity', 'wp-marketing-automations-pro' ),
							'min'         => 1,
							'default'     => 1,
						],
						[
							'id'          => 'price',
							'type'        => 'number',
							'label'       => __( 'Price', 'wp-marketing-automations-pro' ),
							'placeholder' => __( 'Price', 'wp-marketing-automations-pro' ),
						],
					],
				],
				[
					'id'            => 'skip_order_if_products',
					'type'          => 'checkbox',
					'checkboxlabel' => __( 'Skip order creation if all product line items fail to add', 'wp-marketing-automations-pro' ),
					'description'   => '',
				],
				[
					'id'                    => 'billing_email',
					'label'                 => __( 'Email', 'wp-marketing-automations-pro' ),
					'type'                  => 'text',
					'placeholder'           => __( 'Email', 'wp-marketing-automations-pro' ),
					'class'                 => 'bwfan-input-wrapper bwfan-field-crm_create_contact bwf-2-col-item',
					'description'           => '',
					'required'              => true,
					'automation_merge_tags' => true
				],
				[
					'id'          => 'billing_phone_no',
					'label'       => __( 'Billing Phone', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'Phone', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-2-col-item',
					'description' => '',
					'required'    => false,
				],
				[
					'id'          => 'billing_first_name',
					'label'       => __( 'Billing First Name', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'First Name', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-3-col-item',
					'description' => '',
					'required'    => false,

				],
				[
					'id'          => 'billing_last_name',
					'label'       => __( 'Billing Last Name', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'last Name', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-3-col-item',
					'description' => '',
					'required'    => false,

				],
				[
					'id'          => 'billing_company',
					'label'       => __( 'Billing Company', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'Company', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-3-col-item',
					'description' => '',
					'required'    => false,

				],
				[
					'id'          => 'billing_address_line_1',
					'label'       => __( 'Billing Address 1', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'Address 1', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-2-col-item',
					'description' => '',
					'required'    => false,

				],
				[
					'id'          => 'billing_address_line_2',
					'label'       => __( 'Billing Address 2', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'Address 2', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-2-col-item',
					'description' => '',
					'required'    => false,

				],
				[
					'id'          => 'billing_city',
					'label'       => __( 'Billing City', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'City', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-2-col-item',
					'description' => '',
					'required'    => false,

				],
				[
					'id'          => 'billing_postcode',
					'label'       => __( 'Billing Postcode / ZIP', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'Postcode / ZIP', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-2-col-item',
					'description' => '',
					'required'    => false,

				],
				[
					'id'                  => 'billing_country',
					'label'               => __( 'Billing Country / Region', 'wp-marketing-automations-pro' ),
					"type"                => 'custom_search',
					'autocompleterOption' => [
						'path'      => 'wc_countries',
						'slug'      => 'wc_countries',
						'labelText' => __( 'Countries', 'wp-marketing-automations-pro' ),
					],
					"allowFreeTextSearch" => false,
					"multiple"            => false,
					"class"               => 'bwfan-input-wrapper bwf-2-col-item',
				],
				[
					'id'                  => 'billing_state',
					'label'               => __( 'Billing State / Province', 'wp-marketing-automations-pro' ),
					"type"                => 'custom_search',
					'autocompleterOption' => [
						'path'      => 'wc_states',
						'slug'      => 'wc_states',
						'labelText' => __( 'States', 'wp-marketing-automations-pro' ),
						'extraData' => [
							'country' => 'billing_country'
						]
					],
					"allowFreeTextSearch" => true,
					"multiple"            => false,
					"class"               => 'bwfan-input-wrapper bwf-2-col-item',
				],

				[
					'id'          => 'payment_method',
					'label'       => __( 'Payment method', 'wp-marketing-automations-pro' ),
					'type'        => 'wp_select',
					'options'     => $payment_gateways,
					'placeholder' => __( 'Payment method', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-2-col-item',
					'description' => '',
					'required'    => false,

				],
			];

			/** Shipping field if active */
			$shipping_methods = $this->get_available_shipping_methods();
			if ( ! empty( $shipping_methods ) ) {
				$set_shipping_methods = array_replace( [ '' => 'N/A' ], $shipping_methods );
				$shipping_methods     = BWFAN_PRO_Common::prepared_field_options( $set_shipping_methods );
				$schema[]             = [
					'id'          => 'shipping_methods',
					'label'       => __( 'Shipping Methods', 'wp-marketing-automations-pro' ),
					'type'        => 'wp_select',
					'options'     => $shipping_methods,
					"class"       => 'bwfan-input-wrapper bwf-2-col-item',
					'description' => '',
					'required'    => false,

				];
				$schema[]             = [
					'id'          => 'shipping_cost',
					'label'       => __( 'Shipping Cost', 'wp-marketing-automations-pro' ),
					'type'        => 'number',
					'min'         => 0,
					'placeholder' => __( 'Enter', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-2-col-item',
					"toggler"     => [
						'fields' => [
							[
								'id'    => 'shipping_methods',
								'value' => '',
							]
						],
					],
				];
			}

			/** Coupon fields if active */
			$coupons_enabled = get_option( 'woocommerce_enable_coupons' ) === 'yes';
			if ( $coupons_enabled ) {
				$coupon_schema = [
					[
						'id'      => 'couponType',
						'label'   => __( 'Coupon Type', 'wp-marketing-automations-pro' ),
						'type'    => 'radio',
						'options' => [
							[
								'label' => __( 'Store Coupon', 'wp-marketing-automations-pro' ),
								'value' => 'static'
							],
							[
								'label' => __( 'Dynamic Coupon', 'wp-marketing-automations-pro' ),
								'value' => 'dynamic'
							],
						],
					],
					[
						'id'            => 'bwfan_coupons',
						'label'         => __( "Coupon", 'wp-marketing-automations-pro' ),
						'type'          => 'search',
						'autocompleter' => 'coupons',
						"multiple"      => true,
						"required"      => false,
						"toggler"       => [
							'fields' => [
								[
									'id'    => 'couponType',
									'value' => 'static',
								]
							]
						],
					],
					[
						'id'          => 'dynamic_coupon',
						'label'       => __( 'Dynamic Coupon', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						"class"       => 'bwfan-input-wrapper ',
						'hint'        => __( 'Use comma seperated values for multiple options.', 'wp-marketing-automations-pro' ),
						"description" => "",
						"placeholder" => __( 'Dynamic coupon merge tag', 'wp-marketing-automations-pro' ),
						"required"    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'couponType',
									'value' => 'dynamic',
								]
							]
						],
					]
				];

				$schema = array_merge( $schema, $coupon_schema );
			}

			$schema = array_merge( $schema, [
				[
					'id'          => 'status',
					'label'       => __( "Order Status", 'wp-marketing-automations-pro' ),
					'type'        => 'wp_select',
					'options'     => $status,
					'placeholder' => __( 'Status', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper ',
					'tip'         => "",
					"description" => "",
					"required"    => true,
				],
				[
					'id'          => 'order_note',
					'label'       => __( 'Order Note', 'wp-marketing-automations-pro' ),
					'type'        => 'text',
					"class"       => 'bwfan-input-wrapper ',
					'tip'         => "",
					"description" => "",
					"placeholder" => __( 'Note text', 'wp-marketing-automations-pro' ),
					"required"    => false,
				]
			] );

			if ( $this->is_shipping_active() ) {
				$schema = array_merge( $schema, [
					[
						'id'      => 'is_shipping_address',
						'label'   => __( 'Shipping Address', 'wp-marketing-automations-pro' ),
						'type'    => 'radio',
						'options' => [
							[
								'label' => __( 'Same as Billing', 'wp-marketing-automations-pro' ),
								'value' => 'same_as_billing'
							],
							[
								'label' => __( 'Different Shipping Address', 'wp-marketing-automations-pro' ),
								'value' => 'different_shipping_address'
							],
						],
					],
					[
						'id'          => 'shipping_first_name',
						'label'       => __( 'Shipping First Name', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						'placeholder' => __( 'First Name', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper bwf-3-col-item',
						'description' => '',
						'required'    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'          => 'shipping_last_name',
						'label'       => __( 'Shipping Last Name', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						'placeholder' => __( 'last Name', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper bwf-3-col-item',
						'description' => '',
						'required'    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'          => 'shipping_company',
						'label'       => __( 'Shipping Company', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						'placeholder' => __( 'Company', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper bwf-3-col-item',
						'description' => '',
						'required'    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'          => 'shipping_address_line_1',
						'label'       => __( 'Shipping Address 1', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						'placeholder' => __( 'Address 1', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper bwf-2-col-item',
						'description' => '',
						'required'    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'          => 'shipping_address_line_2',
						'label'       => __( 'Shipping Address 2', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						'placeholder' => __( 'Address 2', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper bwf-2-col-item',
						'description' => '',
						'required'    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'          => 'shipping_city',
						'label'       => __( 'Shipping City', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						'placeholder' => __( 'City', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper bwf-2-col-item',
						'description' => '',
						'required'    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'          => 'shipping_postcode',
						'label'       => __( 'Shipping Postcode / ZIP', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						'placeholder' => __( 'Postcode / ZIP', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper bwf-2-col-item',
						'description' => '',
						'required'    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'                  => 'shipping_country',
						'label'               => __( 'Shipping Country / Region', 'wp-marketing-automations-pro' ),
						"type"                => 'custom_search',
						'autocompleterOption' => [
							'path'      => 'wc_countries',
							'slug'      => 'wc_countries',
							'labelText' => __( 'Countries', 'wp-marketing-automations-pro' ),
						],
						"allowFreeTextSearch" => false,
						"multiple"            => false,
						"class"               => 'bwfan-input-wrapper bwf-2-col-item',
						'toggler'             => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'                  => 'shipping_state',
						'label'               => __( 'Shipping State / Province', 'wp-marketing-automations-pro' ),
						"type"                => 'custom_search',
						'autocompleterOption' => [
							'path'      => 'wc_states',
							'slug'      => 'wc_states',
							'labelText' => __( 'States', 'wp-marketing-automations-pro' ),
							'extraData' => [
								'country' => 'shipping_country'
							]
						],
						"allowFreeTextSearch" => true,
						"multiple"            => false,
						"class"               => 'bwfan-input-wrapper bwf-2-col-item',
						'toggler'             => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					],
					[
						'id'          => 'shipping_phone_no',
						'label'       => __( 'Shipping Phone', 'wp-marketing-automations-pro' ),
						'type'        => 'text',
						'placeholder' => __( 'Phone', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper bwf-2-col-item',
						'description' => '',
						'required'    => false,
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'is_shipping_address',
									'value' => 'different_shipping_address',
								]
							]
						],
					]
				] );
			}

			return array_merge( $schema, [
				[
					'id'           => 'order_custom_fields',
					'type'         => 'repeater',
					'add_btn_text' => __( 'Add Field', 'wp-marketing-automations-pro' ),
					'label'        => __( 'Custom Meta Fields', 'wp-marketing-automations-pro' ),
					"fields"       => [
						[
							'id'          => 'field',
							'type'        => 'text',
							'label'       => __( 'Field Key', 'wp-marketing-automations-pro' ),
							'tip'         => "",
							"description" => "",
							"required"    => false,
							"placeholder" => __( 'Field Key (e.g., custom_label)', 'wp-marketing-automations-pro' ),

						],
						[
							"id"          => 'field_value',
							"label"       => __( 'Field Value', 'wp-marketing-automations-pro' ),
							"type"        => 'text',
							"class"       => 'bwfan-input-wrapper',
							"description" => "",
							"required"    => false,
							"placeholder" => __( 'Field Value (e.g., 1234 or info@example.com)', 'wp-marketing-automations-pro' ),
						]
					],
					'tip'          => "",
					"description"  => "",
					'wrap_after'   => wc_tax_enabled() ? '<div class="bwf-single-automation-notice is-info bwf-mt-8">' . __( 'Tax will be automatically calculated and applied based on the order details at the time of creation.', 'wp-marketing-automations-pro' ) . '</div>' : '',
				],
			] );
		}

		/**
		 * @return array
		 * Added Default  value
		 */
		public function get_default_values() {
			return [
				'billing_email'       => '{{contact_email}}',
				'billing_first_name'  => '{{contact_first_name}}',
				'billing_last_name'   => '{{contact_last_name}}',
				'billing_company'     => '{{contact_company}}',
				'billing_phone_no'    => '{{contact_phone}}',
				'shipping_first_name' => '{{contact_first_name}}',
				'shipping_last_name'  => '{{contact_last_name}}',
				'shipping_company'    => '{{contact_company}}',
				'shipping_phone_no'   => '{{contact_phone}}',
				'is_shipping_address' => 'same_as_billing',
				'couponType'          => 'static',
				'status'              => 'wc-pending',
				'order_custom_fields' => [],
			];
		}

		/**
		 * Helper method to get available shipping methods
		 */
		private function get_available_shipping_methods() {
			if ( ! $this->is_shipping_active() ) {
				return [];
			}
			$methods = [];
			$zones   = WC_Shipping_Zones::get_zones();

			foreach ( $zones as $zone ) {
				foreach ( $zone['shipping_methods'] as $method ) {
					if ( $method->is_enabled() ) {
						$methods[ $method->id ] = $method->get_title();
					}
				}
			}

			// Check default zone (zone 0)
			$default_zone = WC_Shipping_Zones::get_zone( 0 );
			foreach ( $default_zone->get_shipping_methods() as $method ) {
				if ( $method->is_enabled() ) {
					$methods[ $method->id ] = $method->get_title();
				}
			}

			return $methods;
		}

		private function is_shipping_active() {
			$woocommerce_ship_to_countries = get_option( 'woocommerce_ship_to_countries' );

			return 'disabled' !== $woocommerce_ship_to_countries;
		}

		/**
		 * @return array
		 */
		static function payment_gateways() {
			$result = array();
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( 'yes' === $gateway->enabled ) {
					$result[ $gateway->id ] = $gateway->get_title();
					$result[ $gateway->id ] = $gateway->get_title();
				}
			}

			return $result;
		}
	}

	/**
	 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
	 */
	return 'BWFAN_WC_Create_Order';
}
