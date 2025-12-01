<?php
/**
 * Auto apply coupon
 *
 * @author      StoreApps
 * @since       4.6.0
 * @version     3.14.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Auto_Apply_Coupon' ) ) {

	/**
	 * Class for handling coupons applied via URL
	 */
	class WC_SC_Auto_Apply_Coupon {

		/**
		 * Variable to hold instance of WC_SC_Auto_Apply_Coupon
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Variable to hold coupon notices
		 *
		 * @var $coupon_notices
		 */
		private $coupon_notices = array();

		/**
		 * Session key for auto apply coupon
		 *
		 * @var $session_key_auto_apply_coupons
		 */
		public $session_key_auto_apply_coupons = 'wc_sc_auto_apply_coupons';

		/**
		 * Constructor
		 */
		private function __construct() {

			add_filter( 'wc_smart_coupons_export_headers', array( $this, 'export_headers' ) );
			add_filter( 'smart_coupons_parser_postmeta_defaults', array( $this, 'postmeta_defaults' ) );
			add_filter( 'sc_generate_coupon_meta', array( $this, 'generate_coupon_meta' ), 10, 2 );
			add_filter( 'wc_sc_process_coupon_meta_value_for_import', array( $this, 'process_coupon_meta_value_for_import' ), 10, 2 );
			add_filter( 'is_protected_meta', array( $this, 'make_action_meta_protected' ), 10, 3 );

			add_action( 'wp_loaded', array( $this, 'handle_auto_apply_hooks' ), 15 );

			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'remove_coupon_if_zero' ), 10, 1 );
			add_action( 'woocommerce_applied_coupon', array( $this, 'display_notice_auto_apply_coupon_product_page' ) );
		}

		/**
		 * Get single instance of WC_SC_Auto_Apply_Coupon
		 *
		 * @return WC_SC_Auto_Apply_Coupon Singleton object of WC_SC_Auto_Apply_Coupon
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
		 * Add meta in export headers
		 *
		 * @param  array $headers Existing headers.
		 * @return array
		 */
		public function export_headers( $headers = array() ) {

			$headers['wc_sc_auto_apply_coupon'] = __( 'Auto apply?', 'woocommerce-smart-coupons' );

			return $headers;
		}

		/**
		 * Post meta defaults for auto apply coupon meta
		 *
		 * @param  array $defaults Existing postmeta defaults.
		 * @return array $defaults Modified postmeta defaults
		 */
		public function postmeta_defaults( $defaults = array() ) {

			$defaults['wc_sc_auto_apply_coupon'] = '';

			return $defaults;
		}

		/**
		 * Add auto apply coupon's meta with value in coupon meta
		 *
		 * @param  array $data The row data.
		 * @param  array $post The POST values.
		 * @return array $data Modified row data
		 */
		public function generate_coupon_meta( $data = array(), $post = array() ) {

			if ( isset( $post['discount_type'] ) && 'smart_coupon' !== $post['discount_type'] ) {
				$data['wc_sc_auto_apply_coupon'] = ( isset( $post['wc_sc_auto_apply_coupon'] ) ) ? $post['wc_sc_auto_apply_coupon'] : '';
			}

			return $data;
		}

		/**
		 * Process coupon meta value for import
		 *
		 * @param  mixed $meta_value The meta value.
		 * @param  array $args       Additional Arguments.
		 * @return mixed $meta_value
		 */
		public function process_coupon_meta_value_for_import( $meta_value = null, $args = array() ) {

			$discount_type = isset( $args['discount_type'] ) ? $args['discount_type'] : '';
			if ( 'smart_coupon' !== $discount_type && ! empty( $args['meta_key'] ) && 'wc_sc_auto_apply_coupon' === $args['meta_key'] ) {
				$auto_apply_coupon = $meta_value;
				if ( 'yes' === $auto_apply_coupon ) {
					$auto_apply_coupon_ids = get_option( 'wc_sc_auto_apply_coupon_ids', array() );
					$auto_apply_coupon_ids = ( empty( $auto_apply_coupon_ids ) || ! is_array( $auto_apply_coupon_ids ) ) ? array() : $auto_apply_coupon_ids;
					$auto_apply_coupon_ids = array_map( 'absint', $auto_apply_coupon_ids );
					$coupon_id             = ( isset( $args['post']['post_id'] ) ) ? absint( $args['post']['post_id'] ) : 0;
					if ( ! empty( $coupon_id ) && ! in_array( $coupon_id, $auto_apply_coupon_ids, true ) ) {
						$auto_apply_coupon_ids[] = $coupon_id;
						update_option( 'wc_sc_auto_apply_coupon_ids', $auto_apply_coupon_ids, 'no' );
					}
				}
			}

			return $meta_value;
		}

		/**
		 * Make meta data of auto apply coupon meta protected
		 *
		 * @param bool   $protected Is protected.
		 * @param string $meta_key The meta key.
		 * @param string $meta_type The meta type.
		 * @return bool $protected
		 */
		public function make_action_meta_protected( $protected = false, $meta_key = '', $meta_type = '' ) {

			if ( 'wc_sc_auto_apply_coupon' === $meta_key ) {
				return true;
			}

			return $protected;
		}

		/**
		 * Get auto applied coupons
		 *
		 * @since 4.27.0
		 * @return array
		 */
		public function get_auto_applied_coupons() {
			$coupons = ( is_object( WC()->session ) && is_callable( array( WC()->session, 'get' ) ) ) ? WC()->session->get( 'wc_sc_auto_applied_coupons' ) : array();
			$coupons = ( ! empty( $coupons ) && is_array( $coupons ) ) ? array_filter( array_unique( $coupons ) ) : array();
			return apply_filters( 'wc_sc_' . __FUNCTION__, $coupons, array( 'source' => $this ) );
		}

		/**
		 * Add auto applied coupon to WC session
		 *
		 * @since 4.27.0
		 * @param string $coupon_code Coupon Code.
		 */
		public function set_auto_applied_coupon( $coupon_code = '' ) {
			if ( ! empty( $coupon_code ) ) {
				$coupons = $this->get_auto_applied_coupons();
				// Check if auto applied coupons are not empty.
				if ( ! empty( $coupons ) && is_array( $coupons ) ) {
					$coupons[] = $coupon_code;
				} else {
					$coupons = array( $coupon_code );
				}
				if ( is_object( WC()->session ) && is_callable( array( WC()->session, 'set' ) ) ) {
					WC()->session->set( 'wc_sc_auto_applied_coupons', $coupons );
				}
			}
		}

		/**
		 * Remove an auto applied coupon from WC session
		 *
		 * @since 4.31.0
		 * @param string $coupon_code Coupon Code.
		 */
		public function unset_auto_applied_coupon( $coupon_code = '' ) {
			if ( ! empty( $coupon_code ) ) {
				$update  = false;
				$coupons = $this->get_auto_applied_coupons();
				// Check if auto applied coupons are not empty.
				if ( ! empty( $coupons ) && in_array( $coupon_code, $coupons, true ) ) {
					$coupons = array_diff( $coupons, array( $coupon_code ) );
					$update  = true;
				}
				if ( true === $update && is_object( WC()->session ) && is_callable( array( WC()->session, 'set' ) ) ) {
					$coupons = array_values( array_filter( $coupons ) );
					WC()->session->set( 'wc_sc_auto_applied_coupons', $coupons );
				}
			}
		}

		/**
		 * Reset cart session data.
		 *
		 * @since 4.27.0
		 */
		public function reset_auto_applied_coupons_session() {
			if ( is_object( WC()->session ) && is_callable( array( WC()->session, 'set' ) ) ) {
				WC()->session->set( 'wc_sc_auto_applied_coupons', null );
			}
		}

		/**
		 * Runs after a coupon is removed
		 *
		 * @since 4.31.0
		 * @param string $coupon_code The coupon code.
		 * @return void
		 */
		public function wc_sc_removed_coupon( $coupon_code = '' ) {
			$backtrace    = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore
			$is_automatic = true;
			if ( ! empty( $backtrace ) ) {
				foreach ( $backtrace as $trace ) {
					if (
						isset( $trace['file'] ) &&
						(
							false !== strpos( $trace['file'], 'StoreApi/Routes/V1/CartRemoveCoupon.php' ) ||
							( ! empty( $trace['function'] ) && 'remove_coupon' === $trace['function'] && ! empty( $trace['class'] ) && in_array( $trace['class'], array( 'WC_AJAX', 'WC_Cart' ), true ) )
						)
					) {
						$is_automatic = false;

						// Call auto_apply_coupons() if removal is from StoreApi/Routes/V1/CartRemoveCoupon.php.
						$this->auto_apply_coupons();
						return; // Exit function after calling auto_apply_coupons().
					}
				}
			}

			if ( $is_automatic ) {
				$this->unset_auto_applied_coupon( $coupon_code );
			}
		}

		/**
		 * Check if auto apply coupon allowed in the cart
		 *
		 * @since 4.27.0
		 * @return bool.
		 */
		public function is_allow_auto_apply_coupons() {
			$auto_applied_coupons         = $this->get_auto_applied_coupons();
			$auto_applied_coupons_count   = ! empty( $auto_applied_coupons ) && is_array( $auto_applied_coupons ) ? count( $auto_applied_coupons ) : 0;
			$max_auto_apply_coupons_limit = apply_filters( 'wc_sc_max_auto_apply_coupons_limit', get_option( 'wc_sc_max_auto_apply_coupons_limit', 5 ), array( 'source' => $this ) );

			return apply_filters(
				'wc_sc_' . __FUNCTION__,
				$auto_applied_coupons_count < $max_auto_apply_coupons_limit,
				array(
					'source'               => $this,
					'auto_applied_coupons' => $auto_applied_coupons,
				)
			);
		}

		/**
		 * Check if the auto apply removable
		 *
		 * @since 4.27.0
		 * @param string $coupon_code Coupon Code.
		 * @return bool.
		 */
		public function is_auto_apply_coupon_removable( $coupon_code = '' ) {

			return apply_filters(
				'wc_sc_' . __FUNCTION__,
				get_option( 'wc_sc_auto_apply_coupon_removable', 'yes' ),
				array(
					'source'      => $this,
					'coupon_code' => $coupon_code,
				)
			);
		}

		/**
		 * Check if the coupon is applied through auto apply
		 *
		 * @since 4.27.0
		 * @param string $coupon_code Coupon Code.
		 * @return bool.
		 */
		public function is_coupon_applied_by_auto_apply( $coupon_code = '' ) {
			if ( ! empty( $coupon_code ) ) {
				$applied_coupons = $this->get_auto_applied_coupons();
				if ( ! empty( $applied_coupons ) && is_array( $applied_coupons ) && in_array( $coupon_code, $applied_coupons, true ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Check if coupon is applicable for auto apply
		 *
		 * @since 4.26.0
		 * @param WC_Coupon $coupon WooCommerce coupon object.
		 * @return bool
		 */
		public function is_coupon_valid_for_auto_apply( $coupon = null ) {

			$valid = false;
			if ( is_object( $coupon ) && $coupon instanceof WC_Coupon ) {

				if ( $this->is_wc_gte_30() ) {
					$coupon_code               = is_callable( array( $coupon, 'get_code' ) ) ? $coupon->get_code() : '';
					$discount_type             = is_callable( array( $coupon, 'get_discount_type' ) ) ? $coupon->get_discount_type() : '';
					$is_auto_generate_coupon   = is_callable( array( $coupon, 'get_meta' ) ) ? $coupon->get_meta( 'auto_generate_coupon' ) : 'no';
					$is_disable_email_restrict = is_callable( array( $coupon, 'get_meta' ) ) ? $coupon->get_meta( 'sc_disable_email_restriction' ) : 'no';
				} else {
					$coupon_id                 = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
					$coupon_code               = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
					$discount_type             = get_post_meta( $coupon_id, 'discount_type', true );
					$is_auto_generate_coupon   = get_post_meta( $coupon_id, 'auto_generate_coupon', true );
					$is_disable_email_restrict = get_post_meta( $coupon_id, 'sc_disable_email_restriction', true );
				}

				$is_removable    = $this->is_auto_apply_coupon_removable( $coupon_code );
				$is_auto_applied = $this->is_coupon_applied_by_auto_apply( $coupon_code );

				/**
				 * Validate coupon for auto apply if
				 *
				 * Discount type is not smart_coupon.
				 * Auto generate is not enabled.
				 * Coupon should not be auto applied OR auto applied coupon should not be removable.
				 * Coupon code is valid.
				 */
				$valid = 'smart_coupon' !== $discount_type
							&& 'yes' !== $is_auto_generate_coupon
							&& ( ! $is_auto_applied || 'yes' !== $is_removable )
							&& $this->is_valid( $coupon );
			}

			return apply_filters(
				'wc_sc_' . __FUNCTION__,
				$valid,
				array(
					'coupon_obj' => $coupon,
					'source'     => $this,
				)
			);
		}

		/**
		 * Function to apply coupons automatically.
		 *
		 * TODO: IF we need another variable for removed coupons;
		 * There will be 2 session variables: wc_sc_auto_applied_coupons and wc_sc_removed_auto_applied_coupons.
		 * Whenever a coupon will be auto-applied, it'll be stored in wc_sc_auto_applied_coupons.
		 * Whenever a coupon will be removed, it'll be moved from wc_sc_auto_applied_coupons to wc_sc_removed_auto_applied_coupons.
		 * And before applying an auto-apply coupon, it'll be made sure that the coupon doesn't exist in wc_sc_removed_auto_applied_coupons
		 * And sum of counts of both session variable will be considered before auto applying coupons. It will be made sure that the sum of counts in not exceeding option `wc_sc_max_auto_apply_coupons_limit`
		 * Reference: issues/234#note_27085
		 */
		public function auto_apply_coupons() {
			( ! in_array( $this->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) ? $this->auto_apply_coupons_old() : $this->auto_apply_coupons_new();
		}

		/**
		 * Function to auto apply coupons new mechanism.
		 */
		public function auto_apply_coupons_new() {

			if ( is_admin() ) {
				return;
			}
			if ( ! class_exists( 'WC_SC_Coupon_Data_Store' ) ) {
				if ( file_exists( trailingslashit( WP_PLUGIN_DIR . '/' . WC_SC_PLUGIN_DIRNAME ) . 'includes/class-wc-sc-coupon-data-store.php' ) ) {
					include_once trailingslashit( WP_PLUGIN_DIR . '/' . WC_SC_PLUGIN_DIRNAME ) . 'includes/class-wc-sc-coupon-data-store.php';
				}
			}

			$cart = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;

			if ( is_object( $cart ) && is_callable( array( $cart, 'is_empty' ) ) && ! $cart->is_empty() && $this->is_allow_auto_apply_coupons() ) {

				$exclude_applied_coupon_ids = array();
				$cart_product_ids           = array();
				$cart_category_ids          = array();
				$cart_attribute_ids         = array();

				$wc_session            = ! empty( WC()->session ) ? WC()->session : null;
				$max_auto_apply_coupon = absint( get_option( 'wc_sc_max_auto_apply_coupons_limit', 5 ) );
				// Fetch already applied coupon.
				$applied_coupons = $cart->get_applied_coupons();

				if ( ! empty( $applied_coupons ) ) {
					foreach ( $applied_coupons as $code ) {
						$coupon = new WC_Coupon( $code );
						if ( ! $this->is_callable( $coupon, 'get_id' ) ) {
							continue;
						}
						$exclude_applied_coupon_ids[] = $coupon->get_id();
					}
				}

				$subtotal                  = ( wc_prices_include_tax() && $cart->display_prices_including_tax() ) ? $cart->get_subtotal() + $cart->get_subtotal_tax() : $cart->get_subtotal();
				$selected_payment_method   = WC()->session->get( 'chosen_payment_method' );
				$selected_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
				$cart_items                = $cart->get_cart();
				// Prepare product IDs for matching.

				if ( ! empty( $cart_items ) ) {
					if ( ! class_exists( 'WC_SC_Coupons_By_Product_Attribute' ) ) {
						include_once 'class-wc-sc-coupons-by-product-attribute.php';
					}
					$wc_sc_product_attribute = WC_SC_Coupons_By_Product_Attribute::get_instance();
					foreach ( $cart_items as $item ) {
						if ( ! isset( $item['data'] ) || empty( $item['data'] ) ) {
							continue;
						}
						$product = $item['data'];
						if ( ! $product instanceof WC_Product || ! $this->is_callable( $product, 'get_id' ) ) {
							continue;
						}
						$cart_product_ids[] = $product->get_id();
						if ( ! empty( $product->get_parent_id() ) ) {
							$cart_product_ids[] = $product->get_parent_id();
						}
						$cart_category_ids = array_merge( $cart_category_ids, wc_get_product_cat_ids( $product->get_id() ) );

						$cart_category_ids = apply_filters( 'wc_sc_auto_apply_coupons_cart_category_ids', $cart_category_ids, $product );

						$cart_attribute_ids = array_merge( $cart_attribute_ids, $wc_sc_product_attribute->get_product_attributes( $product ) );
					}
				}

				// Fetch auto apply coupons.
				$auto_apply_coupon_ids = ( ! empty( $wc_session ) && is_a( $wc_session, 'WC_Session' ) && is_callable( array( $wc_session, 'get' ) ) ) ? $wc_session->get( $this->session_key_auto_apply_coupons ) : array();
				$set_in_session        = apply_filters( $this->session_key_auto_apply_coupons . '_session', true, array( 'source' => $this ) );
				$cart_hash             = md5( wp_json_encode( $cart->get_cart_for_session() ) );
				$stored_cart_hash      = WC()->session->get( 'wc_sc_auto_apply_cart_hash' );
				if ( empty( $auto_apply_coupon_ids ) || ! $set_in_session || $cart_hash !== $stored_cart_hash ) {
					global $wpdb;
					$user_role                    = '';
					$query_exclude_user_role      = '';
					$email                        = '';
					$query_exclude_customer_email = '';
					$limit                        = $max_auto_apply_coupon * apply_filters( 'wc_sc_max_auto_apply_coupons_multiplier', get_option( 'wc_sc_max_auto_apply_coupons_multiplier', 50 ) );
					$query_autoapply_coupons      = $wpdb->prepare(
						"SELECT id
							FROM {$wpdb->prefix}wc_smart_coupons
								WHERE wc_sc_auto_apply_coupon = %d
								AND discount_type != %s
								AND (date_expires IS NULL OR date_expires >= DATE_ADD(NOW(), INTERVAL %d MINUTE))
								",
						1,
						'smart_coupon',
						absint( apply_filters( 'auto_apply_coupons_expires_limit', 5 ) )
					);

					if ( ! empty( $exclude_applied_coupon_ids ) && is_array( $exclude_applied_coupon_ids ) ) {
						$exclude_applied_coupon_ids = implode( ',', $exclude_applied_coupon_ids );
						$query_autoapply_coupons   .= " AND id NOT IN ( $exclude_applied_coupon_ids )";
					}

					// Condition for minimum & maximum spend.
					$query_autoapply_coupons .= " AND (minimum_amount = '' OR minimum_amount IS NULL OR minimum_amount <= " . $subtotal . ') ';
					$query_autoapply_coupons .= " AND (maximum_amount = '' OR maximum_amount IS NULL OR maximum_amount >= " . $subtotal . ') ';

					// Condition for selected payment method.
					if ( ! empty( $selected_payment_method ) && is_string( $selected_payment_method ) ) {
						$query_autoapply_coupons .= $wpdb->prepare( ' AND ( wc_sc_payment_method_ids = %s OR wc_sc_payment_method_ids IS NULL OR wc_sc_payment_method_ids LIKE %s )', 'a:0:{}', '%' . $wpdb->esc_like( '"' . $selected_payment_method . '"' ) . '%' );
					}

					// Condition for selected shipping method.
					if ( ! empty( $selected_shipping_methods ) && is_array( $selected_shipping_methods ) ) {
						$sub_query = '';
						foreach ( $selected_shipping_methods as $shipping_method ) {
							$shipping_method = explode( ':', $shipping_method )[0];
							$sub_query      .= $wpdb->prepare( ' OR wc_sc_shipping_method_ids LIKE %s', '%' . $wpdb->esc_like( '"' . $shipping_method . '"' ) . '%' );

						}
						$query_autoapply_coupons .= $wpdb->prepare( " AND ( wc_sc_shipping_method_ids = %s OR wc_sc_shipping_method_ids IS NULL $sub_query ) ", 'a:0:{}' ); // phpcs:ignore
					}

					// Filter by product ids.
					if ( ! empty( $cart_product_ids ) && is_array( $cart_product_ids ) ) {
						$cart_product_ids  = array_unique( $cart_product_ids );
						$sub_query         = '';
						$exclude_sub_query = '';
						foreach ( $cart_product_ids as $id ) {
							$sub_query         .= $wpdb->prepare( ' OR product_ids LIKE %s', '%' . $wpdb->esc_like( $id ) . '%' );
							$exclude_sub_query .= $wpdb->prepare( ' OR exclude_product_ids NOT LIKE %s', '%' . $wpdb->esc_like( $id ) . '%' );
						}
						$query_autoapply_coupons .= " AND (product_ids = '' OR product_ids IS NULL $sub_query )";

						// Filter by exclude product ids.
						$query_autoapply_coupons .= " AND (exclude_product_ids = '' OR exclude_product_ids IS NULL $exclude_sub_query )";
					}

					// Filter by cart_attribute_ids.
					if ( ! empty( $cart_attribute_ids ) && is_array( $cart_attribute_ids ) ) {
						$cart_attribute_ids = array_unique( $cart_attribute_ids );
						$sub_query          = '';
						$exclude_sub_query  = '';
						foreach ( $cart_attribute_ids as $id ) {
							$sub_query         .= $wpdb->prepare( ' OR wc_sc_product_attribute_ids LIKE %s', '%' . $wpdb->esc_like( $id ) . '%' );
							$exclude_sub_query .= $wpdb->prepare( ' OR wc_sc_exclude_product_attribute_ids NOT LIKE %s', '%' . $wpdb->esc_like( $id ) . '%' );
						}
						$query_autoapply_coupons .= " AND (wc_sc_product_attribute_ids IN ('', 'a:0:{}') OR wc_sc_product_attribute_ids IS NULL $sub_query )";

						// Filter by exclude category ids.
						$query_autoapply_coupons .= " AND (wc_sc_exclude_product_attribute_ids IN ('', 'a:0:{}') OR wc_sc_exclude_product_attribute_ids IS NULL $exclude_sub_query )";
					}

					// Filter by category ids.
					if ( ! empty( $cart_category_ids ) && is_array( $cart_category_ids ) ) {
						$cart_category_ids = array_unique( $cart_category_ids );
						$sub_query         = '';
						$exclude_sub_query = '';
						foreach ( $cart_category_ids as $cat_id ) {
							$sub_query         .= $wpdb->prepare( ' OR product_categories LIKE %s', '%' . $wpdb->esc_like( 'i:' . $cat_id ) . '%' );
							$exclude_sub_query .= $wpdb->prepare( ' OR exclude_product_categories NOT LIKE %s', '%' . $wpdb->esc_like( 'i:' . $cat_id ) . '%' );
						}
						$query_autoapply_coupons .= " AND (product_categories IN ('', 'a:0:{}') OR product_categories IS NULL $sub_query )";

						// Filter by exclude category ids.
						$query_autoapply_coupons .= " AND (exclude_product_categories IN ('', 'a:0:{}') OR exclude_product_categories IS NULL $exclude_sub_query )";
					}

					$query_customer_email = $wpdb->prepare( ' customer_email = %s OR customer_email IS NULL', 'a:0:{}' );
					$query_user_role      = $wpdb->prepare( ' wc_sc_user_role_ids = %s OR wc_sc_user_role_ids IS NULL', 'a:0:{}' );

					$current_user = wp_get_current_user();
					if ( ! is_user_logged_in() ) {
						$email                 = function_exists( 'WC' ) && isset( WC()->customer ) && WC()->customer instanceof WC_Customer && is_callable( array( WC()->customer, 'get_billing_email' ) ) ? WC()->customer->get_billing_email() : '';
						$current_user          = ( ! empty( $email ) ) ? get_user_by( 'email', $email ) : $current_user;
						$email_domain          = '*' . strstr( $email, '@' );
						$query_customer_email .= $wpdb->prepare(
							' OR customer_email LIKE %s OR customer_email LIKE %s',
							'%' . $wpdb->esc_like( '"' . $email_domain . '"' ) . '%',
							'%' . $wpdb->esc_like( '"' . $email . '"' ) . '%'
						);
					}

					if ( isset( $current_user->ID ) && ! empty( $current_user->ID ) ) {
						$max_user_roles_limit = apply_filters( 'wc_sc_max_user_roles_limit', 5 );
						$user_roles           = ( ! empty( $current_user->roles ) ) ? $current_user->roles : array();
						if ( count( $user_roles ) > $max_user_roles_limit ) {
							$user_roles = array_slice( $user_roles, 0, $max_user_roles_limit );
						}
						$email        = function_exists( 'WC' ) && isset( WC()->customer ) && WC()->customer instanceof WC_Customer && is_callable( array( WC()->customer, 'get_billing_email' ) ) ? WC()->customer->get_billing_email() : get_user_meta( $current_user->ID, 'billing_email', true );
						$email        = ( ! empty( $email ) ) ? $email : $current_user->user_email;
						$email_domain = '*' . strstr( $email, '@' );

						if ( $email !== $current_user->user_email ) {
							$current_user_email_domain = '*' . strstr( $current_user->user_email, '@' );
							$query_customer_email     .= $wpdb->prepare(
								' OR customer_email LIKE %s OR customer_email LIKE %s OR customer_email LIKE %s OR customer_email LIKE %s',
								'%' . $wpdb->esc_like( '"' . $current_user_email_domain . '"' ) . '%',
								'%' . $wpdb->esc_like( '"' . $email_domain . '"' ) . '%',
								'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%',
								'%' . $wpdb->esc_like( '"' . $email . '"' ) . '%'
							);

							// add exclude customer email.
							$query_exclude_customer_email .= $wpdb->prepare(
								"wc_sc_excluded_customer_email = '' OR wc_sc_excluded_customer_email IS NULL OR wc_sc_excluded_customer_email NOT LIKE %s AND  wc_sc_excluded_customer_email NOT LIKE %s",
								'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%',
								'%' . $wpdb->esc_like( '"' . $email . '"' ) . '%'
							);
						} else {
							$query_customer_email .= $wpdb->prepare(
								' OR customer_email LIKE %s OR customer_email LIKE %s',
								'%' . $wpdb->esc_like( '"' . $email_domain . '"' ) . '%',
								'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%'
							);
							// add exclude customer email.
							$query_exclude_customer_email .= $wpdb->prepare(
								"wc_sc_excluded_customer_email = '' OR wc_sc_excluded_customer_email IS NULL OR wc_sc_excluded_customer_email NOT LIKE %s",
								'%' . $wpdb->esc_like( '"' . $current_user->user_email . '"' ) . '%'
							);
						}

						if ( ! is_scalar( $user_roles ) && ! empty( $user_roles ) ) {
							$query_user_roles         = array();
							$query_exclude_user_roles = array();
							foreach ( $user_roles as $role ) {
								$query_user_roles[]         = $wpdb->prepare( ' wc_sc_user_role_ids LIKE %s', '%' . $wpdb->esc_like( '"' . $role . '"' ) . '%' );
								$query_exclude_user_roles[] = $wpdb->prepare( ' wc_sc_exclude_user_role_ids NOT LIKE %s', '%' . $wpdb->esc_like( '"' . $role . '"' ) . '%' );
							}
							if ( ! empty( $query_user_roles ) ) {
								$query_user_role .= ' OR (' . implode( ' OR ', $query_user_roles ) . ')';
							}

							if ( ! empty( $query_exclude_user_roles ) ) {
								$query_exclude_user_role .= implode( ' AND ', $query_exclude_user_roles );
							}
						}
					}

					$query_autoapply_coupons .= ! empty( trim( $query_customer_email ) ) ? ' AND (' . $query_customer_email . ')' : '';
					$query_autoapply_coupons .= ! empty( trim( $query_exclude_customer_email ) ) ? ' AND (' . $query_exclude_customer_email . ')' : '';
					$query_autoapply_coupons .= ! empty( trim( $query_user_role ) ) ? ' AND (' . $query_user_role . ')' : '';
					$query_autoapply_coupons .= ! empty( trim( $query_exclude_user_role ) ) ? ' AND (' . $query_exclude_user_role . ')' : '';

					$query_autoapply_coupons .= " ORDER BY id DESC LIMIT $limit";
					$auto_apply_coupon_ids    = $wpdb->get_col( $query_autoapply_coupons ); // phpcs:ignore
					$auto_apply_coupon_ids    = ( empty( $auto_apply_coupon_ids ) || ! is_array( $auto_apply_coupon_ids ) ) ? array() : array_unique( array_filter( array_map( 'absint', $auto_apply_coupon_ids ) ) );
					if ( ! empty( $wc_session ) && is_a( $wc_session, 'WC_Session' ) && is_callable( array( $wc_session, 'set' ) ) ) {
						$wc_session->set( $this->session_key_auto_apply_coupons, $auto_apply_coupon_ids );
						$wc_session->set( 'wc_sc_auto_apply_cart_hash', $cart_hash );
					}
				}

				$auto_apply_coupon_ids = apply_filters( $this->session_key_auto_apply_coupons, $auto_apply_coupon_ids, array( 'source' => $this ) );

				if ( ! empty( $auto_apply_coupon_ids ) && is_array( $auto_apply_coupon_ids ) ) {
					$valid_coupon_counter         = 0;
					$max_auto_apply_coupons_limit = apply_filters( 'wc_sc_max_auto_apply_coupons_limit', $max_auto_apply_coupon, array( 'source' => $this ) );
					$current_filter               = current_filter();
					do_action(
						'wc_sc_before_auto_apply_coupons',
						array(
							'source'         => $this,
							'current_filter' => $current_filter,
						)
					);
					foreach ( $auto_apply_coupon_ids as $apply_coupon_id ) {
						// Process only five coupons.
						if ( absint( $max_auto_apply_coupons_limit ) === $valid_coupon_counter ) {
							break;
						}
						$coupon = new WC_Coupon( absint( $apply_coupon_id ) );
						if ( $this->is_wc_gte_30() ) {
							$coupon_id   = ( ! empty( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
							$coupon_code = ( ! empty( $coupon ) && is_callable( array( $coupon, 'get_code' ) ) ) ? $coupon->get_code() : '';
						} else {
							$coupon_id   = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
							$coupon_code = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
						}

						// If coupon has payment method restriction and already store wc_sc_auto_applied_coupons.
						$payment_method_ids = $this->get_post_meta( $coupon_id, 'wc_sc_payment_method_ids', true );
						if ( ( is_array( $payment_method_ids ) && count( $payment_method_ids ) > 0 ) && $this->is_coupon_applied_by_auto_apply( $coupon_code ) ) {
							$this->unset_auto_applied_coupon( $coupon_code );
						}

						// Guest usage limit validation.
						if ( $this->has_coupon_reached_usage_limit( $coupon ) ) {
							continue;
						}

						// Check if it is a valid coupon object.
						if ( $apply_coupon_id === $coupon_id && ! empty( $coupon_code ) && $this->is_coupon_valid_for_auto_apply( $coupon ) ) {
								$cart_total    = ( $this->is_wc_greater_than( '3.1.2' ) ) ? $cart->get_cart_contents_total() : $cart->cart_contents_total;
								$is_auto_apply = apply_filters(
									'wc_sc_is_auto_apply',
									( $cart_total > 0 ),
									array(
										'source'     => $this,
										'cart_obj'   => $cart,
										'coupon_obj' => $coupon,
										'cart_total' => $cart_total,
									)
								);
								// Check if cart still requires a coupon discount and does not have coupon already applied.
							if ( true === $is_auto_apply && ! $cart->has_discount( $coupon_code ) ) {
								$cart->add_discount( $coupon_code );
								$cart->calculate_shipping();
								$cart->calculate_totals();
								$this->set_auto_applied_coupon( $coupon_code );
							}
							$valid_coupon_counter++;
						} // End if to check valid coupon.
					}
				}
			}
		}

		/**
		 * Function to auto apply coupons older mechanism.
		 */
		public function auto_apply_coupons_old() {
			$cart = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
			if ( is_object( $cart ) && is_callable( array( $cart, 'is_empty' ) ) && ! $cart->is_empty() && $this->is_allow_auto_apply_coupons() ) {
				global $wpdb;
				$user_role = '';
				$email     = '';
				if ( ! is_admin() ) {
					$current_user = wp_get_current_user();
					if ( ! empty( $current_user->ID ) ) {
						$max_user_roles_limit = apply_filters( 'wc_sc_max_user_roles_limit', 5 );
						$user_roles           = ( ! empty( $current_user->roles ) ) ? $current_user->roles : array();
						if ( count( $user_roles ) > $max_user_roles_limit ) {
							$user_roles = array_slice( $user_roles, 0, $max_user_roles_limit );
						}
						$email = get_user_meta( $current_user->ID, 'billing_email', true );
						$email = ( ! empty( $email ) ) ? $email : $current_user->user_email;
					}
				}
				$query = $wpdb->prepare(
					"SELECT DISTINCT p.ID
						FROM {$wpdb->posts} AS p
							JOIN {$wpdb->postmeta} AS pm1
								ON (p.ID = pm1.post_id
									AND p.post_type = %s
									AND p.post_status = %s
									AND pm1.meta_key = %s
									AND pm1.meta_value = %s)
							JOIN {$wpdb->postmeta} AS pm2
								ON (p.ID = pm2.post_id
									AND pm2.meta_key IN ('wc_sc_user_role_ids', 'customer_email')
									AND (pm2.meta_value = ''
											OR pm2.meta_value = 'a:0:{}'",
					'shop_coupon',
					'publish',
					'wc_sc_auto_apply_coupon',
					'yes'
				);
				if ( ! empty( $user_roles ) ) {
					foreach ( $user_roles as $user_role ) {
						$query .= $wpdb->prepare(
							' OR pm2.meta_value LIKE %s',
							'%' . $wpdb->esc_like( $user_role ) . '%'
						);
					}
				}
				if ( ! empty( $email ) ) {
					$query .= $wpdb->prepare(
						' OR pm2.meta_value LIKE %s',
						'%' . $wpdb->esc_like( $email ) . '%'
					);
				}
				$query                .= '))';
				$auto_apply_coupon_ids = $wpdb->get_col( $query ); // phpcs:ignore
				$auto_apply_coupon_ids = ( empty( $auto_apply_coupon_ids ) || ! is_array( $auto_apply_coupon_ids ) ) ? array() : $auto_apply_coupon_ids;
				$auto_apply_coupon_ids = array_filter( array_map( 'absint', $auto_apply_coupon_ids ) );
				if ( ! empty( $auto_apply_coupon_ids ) && is_array( $auto_apply_coupon_ids ) ) {
					$valid_coupon_counter         = 0;
					$max_auto_apply_coupons_limit = apply_filters( 'wc_sc_max_auto_apply_coupons_limit', get_option( 'wc_sc_max_auto_apply_coupons_limit', 5 ), array( 'source' => $this ) );
					$current_filter               = current_filter();
					do_action(
						'wc_sc_before_auto_apply_coupons',
						array(
							'source'         => $this,
							'current_filter' => $current_filter,
						)
					);
					foreach ( $auto_apply_coupon_ids as $apply_coupon_id ) {
						// Process only five coupons.
						if ( absint( $max_auto_apply_coupons_limit ) === $valid_coupon_counter ) {
							break;
						}
						$coupon = new WC_Coupon( absint( $apply_coupon_id ) );
						if ( $this->is_wc_gte_30() ) {
							$coupon_id   = ( ! empty( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : 0;
							$coupon_code = ( ! empty( $coupon ) && is_callable( array( $coupon, 'get_code' ) ) ) ? $coupon->get_code() : '';
						} else {
							$coupon_id   = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
							$coupon_code = ( ! empty( $coupon->code ) ) ? $coupon->code : '';
						}

						// If coupon has payment method restriction and already store wc_sc_auto_applied_coupons.
						$payment_method_ids = $this->get_post_meta( $coupon_id, 'wc_sc_payment_method_ids', true );
						if ( ( is_array( $payment_method_ids ) && count( $payment_method_ids ) > 0 ) && $this->is_coupon_applied_by_auto_apply( $coupon_code ) ) {
							$this->unset_auto_applied_coupon( $coupon_code );
						}

						// Check if it is a valid coupon object.
						if ( $apply_coupon_id === $coupon_id && ! empty( $coupon_code ) && $this->is_coupon_valid_for_auto_apply( $coupon ) ) {
								$cart_total    = ( $this->is_wc_greater_than( '3.1.2' ) ) ? $cart->get_cart_contents_total() : $cart->cart_contents_total;
								$is_auto_apply = apply_filters(
									'wc_sc_is_auto_apply',
									( $cart_total > 0 ),
									array(
										'source'     => $this,
										'cart_obj'   => $cart,
										'coupon_obj' => $coupon,
										'cart_total' => $cart_total,
									)
								);
								// Check if cart still requires a coupon discount and does not have coupon already applied.
							if ( true === $is_auto_apply && ! $cart->has_discount( $coupon_code ) ) {
								$cart->add_discount( $coupon_code );
								$cart->calculate_shipping();
								$cart->calculate_totals();
								$this->set_auto_applied_coupon( $coupon_code );
							}
							$valid_coupon_counter++;
						} // End if to check valid coupon.
					}
				}
			}
		}

		/**
		 * Determines whether the guest has reached the usage limit for a given coupon.
		 *
		 * This method checks the `_used_by` meta of the coupon to determine how many times the current user
		 * or guest (by billing email) has used the coupon. If the usage count is greater than or equal to
		 * the per-user usage limit set on the coupon, it returns true.
		 *
		 * @since 1.0.0
		 *
		 * @param WC_Coupon $coupon WooCommerce coupon object.
		 * @return bool True if the usage limit per user has been reached, false otherwise.
		 */
		public function has_coupon_reached_usage_limit( $coupon ) {
			if ( ! $coupon instanceof WC_Coupon ) {
				return false;
			}

			$limit_per_user = $coupon->get_usage_limit_per_user();

			if ( $limit_per_user <= 0 || is_user_logged_in() ) {
				return false;
			}

			$coupon_id = $coupon->get_id();

			// Retrieve the list of user IDs or billing emails that used the coupon.
			$used_by = (array) $coupon->get_used_by();

			// Normalize values to strings and trim spaces for comparison.
			$used_by = array_map( 'strval', array_map( 'trim', $used_by ) );

			$billing_email = WC()->customer ? WC()->customer->get_billing_email() : '';
			$identifier    = strtolower( sanitize_email( $billing_email ) );

			if ( ! empty( $identifier ) ) {
				$used_count = count(
					array_filter(
						$used_by,
						function ( $used ) use ( $identifier ) {
							return strcasecmp( $used, $identifier ) === 0;
						}
					)
				);

				return $used_count >= $limit_per_user;
			}

			return false;
		}

		/**
		 * Function to apply coupons for cart and checkout block.
		 *
		 * @param bool  $pre_render Is protected.
		 * @param array $parsed_block The meta key.
		 * @param array $parent_block The meta type.
		 * @return bool
		 */
		public function auto_apply_coupons_to_cart_checkout_block( $pre_render = null, $parsed_block = array(), $parent_block = null ) {
			if ( isset( $parsed_block['blockName'] ) && in_array( $parsed_block['blockName'], array( 'woocommerce/cart', 'woocommerce/checkout' ), true ) ) {
				$this->auto_apply_coupons();
			}
			return $pre_render;
		}

		/**
		 * Prevent applying coupon for guest users if usage limit is reached.
		 *
		 * @param bool      $is_valid Whether the coupon is valid.
		 * @param WC_Coupon $coupon   Coupon object.
		 * @return bool True if valid, false if guest has reached usage limit.
		 */
		public function validate_coupon_usage_limit_for_guests_only( $is_valid, $coupon ) {
			if ( ! $is_valid || ! $coupon instanceof WC_Coupon ) {
				return $is_valid;
			}

			// Only apply limit check for guest users.
			if ( $this->has_coupon_reached_usage_limit( $coupon ) ) {
				wc_add_notice( sprintf( __( 'Coupon usage limit has been reached.', 'woocommerce-smart-coupons' ) ), 'error' );
				return false;
			}

			return $is_valid;
		}

		/**
		 * Function to Automatically Apply Coupons on Cart Update.
		 */
		public function auto_apply_coupons_on_cart_update() {
			WC()->session->__unset( $this->session_key_auto_apply_coupons );
			if ( WC()->is_rest_api_request() ) {
				$this->auto_apply_coupons();
			}
		}

		/**
		 * Automatically apply coupons during the WooCommerce checkout order review update process.
		 * This function checks for changes in the shipping method and updates the chosen shipping method in the session.
		 * Then, it automatically applies any eligible coupons.
		 */
		public function wc_checkout_update_order_review_auto_apply_coupons() {
			check_ajax_referer( 'update-order-review', 'security' );

			// Get the posted shipping methods from the form data, if available.
			$posted_shipping_methods = isset( $_POST['shipping_method'] ) ? wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : array(); // phpcs:ignore

			if ( ! empty( $posted_shipping_methods ) ) {
				// Retrieve the current chosen shipping methods from the session.
				$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

				if ( is_array( $posted_shipping_methods ) ) {
					// Update the chosen shipping methods with the posted values.
					foreach ( $posted_shipping_methods as $i => $value ) {
						if ( ! is_string( $value ) ) {
							continue;
						}
						$chosen_shipping_methods[ $i ] = $value;
					}
				}

				// Save the updated chosen shipping methods back to the session.
				WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
			}

			// Get the posted payment methods from the form data, if available.
			$posted_payment_method = isset( $_POST['payment_method'] ) ? wc_clean( wp_unslash( $_POST['payment_method'] ) ) : array(); // phpcs:ignore

			if ( ! empty( $posted_payment_method ) ) {

				// Retrieve the current chosen payment method from the session.
				$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );

				// Update the session only if the new method is different.
				if ( ! empty( $posted_payment_method ) && $posted_payment_method !== $chosen_payment_method ) {
					WC()->session->set( 'chosen_payment_method', $posted_payment_method );
				}
			}

			// Automatically apply any eligible coupons.
			$this->auto_apply_coupons();
		}

		/**
		 * Remove coupons that result in zero discount.
		 *
		 * This method loops through all applied coupons in the cart and checks if they are
		 * not present in the coupon discount totals. If a coupon is found to have a zero
		 * discount (i.e., it is not in the coupon discount totals), it is removed from the
		 * applied coupons list and the auto-applied coupon logic is handled.
		 *
		 * @param WC_Cart $cart The WooCommerce cart object.
		 */
		public function remove_coupon_if_zero( $cart ) {
			// Get all applied coupons.
			$applied_coupons      = $cart->get_applied_coupons();
			$auto_applied_coupons = $this->get_auto_applied_coupons();
			$auto_applied_coupons = array_map( 'strtolower', $auto_applied_coupons );

			if ( ! empty( $applied_coupons ) ) {
				foreach ( $applied_coupons as $coupon_code ) {
					// Check if the coupon is not in the coupon discount totals (indicating a zero discount).
					if ( in_array( strtolower( $coupon_code ), $auto_applied_coupons, true ) && ! array_key_exists( strtolower( $coupon_code ), $cart->get_coupon_discount_totals() ) ) {
						// Remove the coupon from the applied coupons array.
						$updated_coupons = array_diff( $applied_coupons, array( $coupon_code ) );
						$cart->set_applied_coupons( $updated_coupons );

						// Unset the auto-applied coupon from session.
						$this->unset_auto_applied_coupon( $coupon_code );
					}

					// Guest usage limit validation.
					$coupon = new WC_Coupon( $coupon_code );
					if ( $this->has_coupon_reached_usage_limit( $coupon ) ) {
						// Remove the coupon if usage limit is reached.
						$updated_coupons = array_diff( $applied_coupons, array( $coupon_code ) );
						$cart->set_applied_coupons( $updated_coupons );

						// Unset the auto-applied coupon from session.
						$this->unset_auto_applied_coupon( $coupon_code ); // Usage limit reached.
					}
				}
			}
		}

		/**
		 * Automatically apply coupons to the cart when the shipping method is changed.
		 * This method checks if the request is an AJAX request to update the shipping method,
		 * and if so, it calls the auto_apply_coupons method to apply any eligible coupons.
		 */
		public function auto_apply_coupons_to_cart_on_shipping_change() {
			if ( is_ajax() ) {
				// Check if the request is an AJAX request to update the shipping method.
				// use wp_verify_nonce instead of check_ajax_referer to avoid conflict with other third party ajax reuest.
				if ( isset( $_POST['security'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_POST['security'] ) ), 'update-shipping-method' ) ) { // phpcs:ignore
					$this->auto_apply_coupons();
				}
			}
		}

		/**
		 * Handle auto apply related hooks
		 */
		public function handle_auto_apply_hooks() {
			$is_allow_auto_apply = $this->sc_get_option( 'wc_sc_allow_auto_apply', 'yes' );
			if ( 'yes' === $is_allow_auto_apply ) {
				// Action to auto apply coupons.
				add_action( 'woocommerce_cart_is_empty', array( $this, 'auto_apply_coupons' ) );
				add_action( 'woocommerce_shortcode_before_product_cat_loop', array( $this, 'auto_apply_coupons' ) );
				add_action( 'woocommerce_before_shop_loop', array( $this, 'auto_apply_coupons' ) );
				add_action( 'woocommerce_before_single_product', array( $this, 'auto_apply_coupons' ) );
				add_action( 'woocommerce_before_cart', array( $this, 'auto_apply_coupons' ) );
				add_action( 'woocommerce_before_checkout_form', array( $this, 'auto_apply_coupons' ) );
				add_action( 'woocommerce_add_to_cart', array( $this, 'auto_apply_coupons' ) );
				add_action( 'woocommerce_account_content', array( $this, 'auto_apply_coupons' ) );
				add_action( 'woocommerce_checkout_update_order_review', array( $this, 'wc_checkout_update_order_review_auto_apply_coupons' ) );
				add_action( 'woocommerce_cart_emptied', array( $this, 'reset_auto_applied_coupons_session' ) );
				add_action( 'woocommerce_removed_coupon', array( $this, 'blocks_removed_coupon' ), 20 );
				add_action( 'woocommerce_removed_coupon', array( $this, 'wc_sc_removed_coupon' ), 99 );

				add_filter( 'pre_render_block', array( $this, 'auto_apply_coupons_to_cart_checkout_block' ), 10, 3 );
				add_filter( 'woocommerce_coupon_is_valid', array( $this, 'validate_coupon_usage_limit_for_guests_only' ), 10, 3 );
				add_action( 'woocommerce_cart_item_set_quantity', array( $this, 'auto_apply_coupons_on_cart_update' ), 99 );
				add_action( 'woocommerce_cart_item_removed', array( $this, 'auto_apply_coupons_on_cart_update' ), 99 );

				add_action( 'woocommerce_before_cart_totals', array( $this, 'auto_apply_coupons_to_cart_on_shipping_change' ) );

				add_action( 'smart_coupons_before_calculate_totals', array( $this, 'auto_apply_coupons' ) );
			}
		}

		/**
		 * Recheck coupon removed from auto-apply
		 *
		 * @param string $code The coupon code.
		 */
		public function blocks_removed_coupon( $code = '' ) {
			if ( empty( $code ) ) {
				return;
			}

			if ( function_exists( 'WC' ) && isset( WC()->cart ) && is_a( WC()->cart, 'WC_Cart' ) ) {
				$is_removable    = $this->is_auto_apply_coupon_removable( $code );
				$is_auto_applied = $this->is_coupon_applied_by_auto_apply( $code );
				if ( true === $is_auto_applied && 'yes' !== $is_removable && $this->is_callable( WC()->cart, 'add_discount' ) ) {
					WC()->cart->add_discount( $code );
				}
			}
		}

		/**
		 * Show coupon notices of apply coupon
		 *
		 * @param string $code The coupon code.
		 */
		public function display_notice_auto_apply_coupon_product_page( $code ) {
			if ( is_product() && ! empty( $code ) && ! empty( wc_get_notices( 'success' ) ) ) {
				$coupon = new WC_Coupon( $code );
				if ( $this->is_callable( $coupon, 'get_meta' ) && 'yes' === $coupon->get_meta( 'wc_sc_auto_apply_coupon' ) ) {
					wc_print_notices();
				}
			}
		}
	}

}

WC_SC_Auto_Apply_Coupon::get_instance();
