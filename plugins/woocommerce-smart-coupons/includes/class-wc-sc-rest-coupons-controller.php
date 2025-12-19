<?php
/**
 * REST API Coupons controller for Smart Coupons
 *
 * Handles requests to the wc/sc/v1/coupons endpoint.
 *
 * @author      StoreApps
 * @since       4.10.0
 * @version     1.6.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_SC_REST_Coupons_Controller' ) ) {

	/**
	 * REST API Coupons controller class.
	 *
	 * @package Automattic/WooCommerce/RestApi
	 * @extends WC_REST_Coupons_Controller
	 */
	class WC_SC_REST_Coupons_Controller extends WC_REST_Coupons_Controller {

		/**
		 * Endpoint namespace.
		 *
		 * @var string
		 */
		protected $namespace = 'wc/v3/sc';

		/**
		 * Route base.
		 *
		 * @var string
		 */
		protected $rest_base = 'coupons';

		/**
		 * Post type.
		 *
		 * @var string
		 */
		protected $post_type = 'shop_coupon';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( "woocommerce_rest_prepare_{$this->post_type}_object", array( $this, 'handle_response_data' ), 99, 3 );
			add_action( "woocommerce_rest_pre_insert_{$this->post_type}_object", array( $this, 'save_custom_coupon_meta' ), 10, 2 );
			// Disable show available coupons session mechanism.
			$display_coupons = WC_SC_Display_Coupons::get_instance();
			add_filter( $display_coupons->session_key_available_coupons . '_session', array( $this, 'disable_session_for_rest_api' ), 10, 2 );
		}

		/**
		 * Register the routes for coupons.
		 */
		public function register_routes() {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_item' ),
						'permission_callback' => array( $this, 'create_item_permissions_check' ),
						'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);

			register_rest_route(
				'wc/v3',
				'/users/me/store-credits',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_current_user_store_credits' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
					'schema'              => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		/**
		 * Prepare a single coupon for create or update.
		 *
		 * @param  WP_REST_Request $request Request object.
		 * @param  bool            $creating If is creating a new object.
		 * @return WP_Error|WC_Data
		 */
		protected function prepare_object_for_database( $request, $creating = false ) {
			try {
				global $woocommerce_smart_coupon;

				$id                 = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
				$coupon             = new WC_Coupon( $id );
				$schema             = $this->get_item_schema();
				$data_keys          = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );
				$email_restrictions = ( ! empty( $request['email_restrictions'] ) && count( $request['email_restrictions'] ) === 1 ) ? $request['email_restrictions'] : '';

				// Validate required POST fields.
				if ( $creating ) {
					if ( empty( $request['code'] ) ) {
						$request['code'] = $woocommerce_smart_coupon->generate_unique_code( $email_restrictions );
					} else {
						$_coupon          = new WC_Coupon( $request['code'] );
						$is_auto_generate = ( is_object( $_coupon ) && is_callable( array( $_coupon, 'get_meta' ) ) ) ? $_coupon->get_meta( 'auto_generate_coupon' ) : 'no';
						if ( 'yes' === $is_auto_generate ) {
							$request['code'] = $woocommerce_smart_coupon->generate_unique_code( $email_restrictions );
							foreach ( $data_keys as $key ) {
								if ( empty( $request[ $key ] ) ) {
									switch ( $key ) {
										case 'code':
											// do nothing.
											break;
										case 'meta_data':
											$meta_data     = ( is_object( $_coupon ) && is_callable( array( $_coupon, 'get_meta_data' ) ) ) ? $_coupon->get_meta_data() : null;
											$new_meta_data = array();
											if ( ! empty( $meta_data ) ) {
												foreach ( $meta_data as $meta ) {
													if ( is_object( $meta ) && is_callable( array( $meta, 'get_data' ) ) ) {
														$data = $meta->get_data();
														if ( isset( $data['id'] ) ) {
															unset( $data['id'] );
														}
														$new_meta_data[] = $data;
													}
												}
											}
											$request[ $key ] = $new_meta_data;
											break;
										case 'description':
											$request[ $key ] = ( is_object( $_coupon ) && is_callable( array( $_coupon, 'get_description' ) ) ) ? $_coupon->get_description() : null;
											break;
										default:
											if ( is_callable( array( $_coupon, "get_{$key}" ) ) ) {
												$request[ $key ] = $_coupon->{"get_{$key}"}();
											}
											break;
									}
								}
							}
						}
					}
				}

				// Handle all writable props.
				foreach ( $data_keys as $key ) {
					$value = $request[ $key ];

					if ( ! is_null( $value ) ) {
						switch ( $key ) {
							case 'code':
								$coupon_code  = wc_format_coupon_code( $value );
								$id           = $coupon->get_id() ? $coupon->get_id() : 0;
								$id_from_code = wc_get_coupon_id_by_code( $coupon_code, $id );

								if ( $id_from_code ) {
									return new WP_Error( 'woocommerce_rest_coupon_code_already_exists', __( 'The coupon code already exists', 'woocommerce-smart-coupons' ), array( 'status' => 400 ) );
								}

								$coupon->set_code( $coupon_code );
								break;
							case 'meta_data':
								if ( is_array( $value ) ) {
									foreach ( $value as $meta ) {
										$coupon->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
									}
								}
								break;
							case 'description':
								$coupon->set_description( wp_filter_post_kses( $value ) );
								break;
							default:
								if ( is_callable( array( $coupon, "set_{$key}" ) ) ) {
									$coupon->{"set_{$key}"}( $value );
								}
								break;
						}
					}
				}

				/**
				 * Filters an object before it is inserted via the REST API.
				 *
				 * The dynamic portion of the hook name, `$this->post_type`,
				 * refers to the object type slug.
				 *
				 * @param WC_Data         $coupon   Object object.
				 * @param WP_REST_Request $request  Request object.
				 * @param bool            $creating If is creating a new object.
				 */
				return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $coupon, $request, $creating );
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}

				return new WP_Error( 'woocommerce_rest_coupon_preparation_failed', __( 'Failed to prepare coupon object for database.', 'woocommerce-smart-coupons' ), array( 'status' => 500 ) );
			}
		}

		/**
		 * Save coupon create via post WC Coupon.
		 *
		 * @param  WC_Coupon       $coupon Coupon object.
		 * @param  WP_REST_Request $request Request object.
		 * @return WP_Error|WC_Coupon
		 */
		public function save_custom_coupon_meta( $coupon = null, $request = null ) {

			if ( ! $request instanceof WC_Rest_Request || ! is_callable( array( $request, 'get_json_params' ) ) ) {
				return $coupon;
			}
			$body = $request->get_json_params();
			if ( empty( $body ) ) {
				return $coupon;
			}
			if ( ! $coupon instanceof WC_Coupon ) {
				return $coupon;
			}
			$coupon = $this->process_meta_rest_api( $body, $coupon );
			$data   = apply_filters( 'sc_generate_coupon_meta', $body, $body );
			if ( ! class_exists( 'WC_SC_Coupon_Fields' ) && file_exists( WP_PLUGIN_DIR . '/woocommerce-smart-coupons/includes/class-wc-sc-coupon-fields.php' ) ) {
				include_once WP_PLUGIN_DIR . '/woocommerce-smart-coupons/includes/class-wc-sc-coupon-fields.php';
			}
			$coupon = WC_SC_Coupon_Fields::get_instance()->save_defaults_smart_coupon_meta_data( $data, $coupon );
			return $coupon;
		}

		/**
		 * Handle REST response data
		 *
		 * @param WP_REST_Response|mixed $response The response.
		 * @param WC_Coupon|mixed        $object The object.
		 * @param WP_REST_Request|mixed  $request The request.
		 * @return WP_REST_Response
		 */
		public function handle_response_data( $response = null, $object = null, $request = null ) {
			try {
				global $woocommerce_smart_coupon;

				if ( ! empty( $request['sc_is_send_email'] ) && 'yes' === $request['sc_is_send_email'] ) {
					$is_send_email      = $woocommerce_smart_coupon->is_email_template_enabled();
					$email_restrictions = ( ! empty( $request['email_restrictions'] ) ) ? current( $request['email_restrictions'] ) : '';
					if ( 'yes' === $is_send_email && ! empty( $email_restrictions ) ) {
						$coupon      = array(
							'code'   => ( is_object( $object ) && is_callable( array( $object, 'get_code' ) ) ) ? $object->get_code() : '',
							'amount' => ( is_object( $object ) && is_callable( array( $object, 'get_amount' ) ) ) ? $object->get_amount() : 0,
						);
						$action_args = apply_filters(
							'wc_sc_email_coupon_notification_args',
							array(
								'email'         => $email_restrictions,
								'coupon'        => $coupon,
								'discount_type' => ( is_object( $object ) && is_callable( array( $object, 'get_discount_type' ) ) ) ? $object->get_discount_type() : '',
							)
						);
						// Trigger email notification.
						do_action( 'wc_sc_email_coupon_notification', $action_args );
					}
				}

				if ( ! empty( $request['sc_is_html'] ) && 'yes' === $request['sc_is_html'] ) {
					$data = '';
					ob_start();
					do_action(
						'wc_sc_paint_coupon',
						array(
							'coupon'         => $object,
							'with_css'       => 'yes',
							'with_container' => 'yes',
						)
					);
					$data     = ob_get_clean();
					$response = rest_ensure_response( $data );
				}
			} catch ( \Throwable $e ) {
				if ( is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
			}
			return $response;
		}

		/**
		 * Save coupon by product quantity data in meta.
		 * Updates the coupon object with product and category quantity restrictions from REST API input.
		 * Handles removal of placeholder entries and ensures proper data formatting.
		 *
		 * @param array     $post The coupon post ID.
		 * @param WC_Coupon $coupon The coupon object.
		 * @return WC_Coupon $coupon The coupon object.
		 */
		public function process_meta_rest_api( $post = array(), $coupon = null ) {
			global $woocommerce_smart_coupon;
			try {
				if ( empty( $post ) || ! $coupon instanceof WC_Coupon ) {
					return $coupon;
				}

				// Handle process_meta for product quantity restriction.
				$product_quantity_restrictions = (isset($post['wc_sc_product_quantity_restrictions'])) ? wc_clean(wp_unslash($post['wc_sc_product_quantity_restrictions'])) : array(); // phpcs:ignore
				if ( ! isset( $product_quantity_restrictions['condition'] ) ) {
					$product_quantity_restrictions['condition'] = 'any'; // Values: any, all.
				}

				if ( isset( $product_quantity_restrictions['values']['product']['{i}'] ) ) {
					unset( $product_quantity_restrictions['values']['product']['{i}'] );
				}

				if ( isset( $product_quantity_restrictions['values']['product_category']['{i}'] ) ) {
					unset( $product_quantity_restrictions['values']['product_category']['{i}'] );
				}

				if ( ! empty( $product_quantity_restrictions ) ) {
					foreach ( $product_quantity_restrictions as $restriction_key => $restrictions ) {
						if ( 'values' === $restriction_key ) {
							// Max quantity feature not included for product quantity.
							foreach ( $restrictions['product'] as $id => $restriction ) {
								$id = absint( $id );
								if ( 0 !== $id && isset( $product_quantity_restrictions['values']['product'][ $id ]['max'] ) ) {
									$product_quantity_restrictions['values']['product'][ $id ]['max'] = ! empty( $restriction['max'] ) ? intval( $restriction['max'] ) : '';
								}
							}

							// Max quantity feature not included for product category quantity.
							foreach ( $restrictions['product_category'] as $id => $restriction ) {
								$id = absint( $id );
								if ( 0 !== $id && isset( $product_quantity_restrictions['values']['product_category'][ $id ]['max'] ) ) {
									$product_quantity_restrictions['values']['product_category'][ $id ]['max'] = ! empty( $restriction['max'] ) ? intval( $restriction['max'] ) : '';
								}
							}
						}
					}
				}

				if ( is_callable( array( $coupon, 'update_meta_data' ) ) ) {
					$coupon->update_meta_data( 'wc_sc_product_quantity_restrictions', $product_quantity_restrictions );
				}

				// End process_meta for product quantity restriction.

			} catch ( \Throwable $e ) {
				$woocommerce_smart_coupon->sc_block_catch_error( $e );
			}
			return $coupon;
		}

		/**
		 * Retrieves the store credits for the currently authenticated user.
		 * Callback for a custom REST API endpoint.
		 * Returns the user's valid & invalid store credits, and total credit balance.
		 *
		 * @param WP_REST_Request $request The REST API request object.
		 * @return array|WP_Error Returns an array with valid and invalid store credits, or WP_Error if unauthenticated.
		 */
		public function get_current_user_store_credits( $request = null ) {
			global $woocommerce_smart_coupon;
			if ( ! class_exists( 'WC_SC_Display_Coupons' ) && file_exists( WP_PLUGIN_DIR . '/woocommerce-smart-coupons/includes/class-wc-sc-display-coupons.php' ) ) {
				include_once WP_PLUGIN_DIR . '/woocommerce-smart-coupons/includes/class-wc-sc-display-coupons.php';
			}
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return new WP_Error(
					'woocommerce_rest_coupon_preparation_failed',
					__( 'User not authenticated.', 'woocommerce-smart-coupons' ),
					array( 'status' => 401 )
				);
			}

			try {
				$response = array(
					'valid'   => array(),
					'invalid' => array(),
				);

				$customer      = new WC_Customer( $user_id );
				$billing_email = $customer->get_billing_email();

				// Ensure cart is loaded and set billing email.
				if ( null === WC()->cart ) {
					wc_load_cart();
				}
				WC()->cart->get_customer()->set_billing_email( $billing_email );

				$display_coupons    = WC_SC_Display_Coupons::get_instance();
				$get_coupons_method = in_array(
					$display_coupons->get_db_status_for( '9.8.0' ),
					array( 'completed', 'done' ),
					true
				)
					? 'sc_get_available_coupons_list'
					: 'sc_get_available_coupons_list_old';

				$coupons = $display_coupons->$get_coupons_method( array() );

				if ( empty( $coupons ) ) {
					return new WP_Error(
						'woocommerce_rest_coupon_preparation_failed',
						__( 'Sorry, no coupons available for you.', 'woocommerce-smart-coupons' ),
						array( 'status' => 404 )
					);
				}

				$total_store_credit = 0;
				$coupons_applied    = ( is_object( WC()->cart ) && is_callable( array( WC()->cart, 'get_applied_coupons' ) ) )
				? WC()->cart->get_applied_coupons()
				: array();

				$allowed_types = (array) apply_filters( 'sc_rest_extend_discount_type', array( 'smart_coupon' ) );

				foreach ( $coupons as $code ) {
					$coupon_id = ! empty( $code->id ) ? absint( $code->id ) : 0;
					if ( ! $coupon_id ) {
						continue;
					}

					$coupon = new WC_Coupon( $coupon_id );
					if ( ! $coupon instanceof WC_Coupon || ! is_callable( array( $coupon, 'get_code' ) ) ) {
						continue;
					}

					if ( ! in_array( $coupon->get_discount_type(), $allowed_types, true ) ) {
						continue;
					}

					if ( $display_coupons->sc_coupon_code_exists( $coupon->get_code(), $coupons_applied ) ) {
						continue;
					}

					try {
						$coupon_response = rest_ensure_response( $coupon->get_data() );
					} catch ( Exception $e ) {
						return new WP_Error(
							'rest_response_error',
							'Failed to prepare REST response: ' . $e->getMessage(),
							array( 'status' => 500 )
						);
					}

					if ( ! $coupon_response instanceof WP_REST_Response || ! is_callable( array( $coupon_response, 'get_data' ) ) ) {
						continue;
					}

					$show_as_valid = apply_filters(
						'wc_sc_show_as_valid',
						$woocommerce_smart_coupon->is_valid( $coupon ),
						array( 'coupon_obj' => $coupon )
					);

					$coupon_data = $coupon_response->get_data();

					if ( true === $show_as_valid ) {
						if ( 'smart_coupon' === $coupon->get_discount_type() ) {
							$total_store_credit += (float) $coupon->get_amount();
						}
						$response['valid'][] = $coupon_data;
					} else {
						$response['invalid'][] = $coupon_data;
					}
				}

				$response['store_credit_balance'] = wc_price( $total_store_credit );
				$response['no_of_valid']          = count( $response['valid'] );
				$response['no_of_invalid']        = count( $response['invalid'] );

				return $response;

			} catch ( \Throwable $e ) {
				if ( isset( $woocommerce_smart_coupon ) && is_object( $woocommerce_smart_coupon ) && method_exists( $woocommerce_smart_coupon, 'sc_block_catch_error' ) ) {
					$woocommerce_smart_coupon->sc_block_catch_error( $e );
				}
				return new WP_Error(
					'rest_internal_server_error',
					'An unexpected error occurred.',
					array( 'status' => 500 )
				);
			}
		}

		/**
		 * Disable show available coupons session mechanism.
		 *
		 * This function run sql query to get updated data.
		 *
		 * @param bool  $flag session on/off.
		 * @param array $data extra data.
		 * @return bool|WP_Error Returns an array with user ID and store credits, or WP_Error if unauthenticated.
		 */
		public function disable_session_for_rest_api( $flag = true, $data = array() ) {
			return false;
		}


	} // End class.
}
