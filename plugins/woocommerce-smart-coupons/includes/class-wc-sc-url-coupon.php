<?php
/**
 * Coupons via URL
 *
 * @author      StoreApps
 * @since       3.3.0
 * @version     2.12.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_SC_URL_Coupon' ) ) {

	/**
	 * Class for handling coupons applied via URL
	 */
	class WC_SC_URL_Coupon {

		/**
		 * Variable to hold instance of WC_SC_URL_Coupon
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
		 * Cache key used for storing reserved slugs
		 *
		 * @var string
		 */
		private $reserved_cache_key = 'wc_sc_reserved_slugs_v1';

		/**
		 * Constructor
		 */
		private function __construct() {

			add_action( 'wp_loaded', array( $this, 'apply_coupon_from_url' ), 19 );
			add_action( 'wp_loaded', array( $this, 'apply_coupon_from_session' ), 20 );

			// Apply coupon from session when updating customer cart via WooCommerce API.
			add_action( 'woocommerce_store_api_cart_update_customer_from_request', array( $this, 'apply_coupon_from_session' ) );

			add_action( 'wp_loaded', array( $this, 'move_applied_coupon_from_cookies_to_account' ) );
			add_action( 'wp_head', array( $this, 'convert_sc_coupon_notices_to_wc_notices' ) );
			add_filter( 'the_content', array( $this, 'show_coupon_notices' ) );
			add_action( 'wp_footer', array( $this, 'styles_and_scripts' ) );

			// Hooks for setting user email in WooCommerce session via AJAX.
			add_action( 'wp_ajax_wc_sc_set_session', array( $this, 'maybe_set_session' ) );
			add_action( 'wp_ajax_nopriv_wc_sc_set_session', array( $this, 'maybe_set_session' ) );

			// Rewrite rules.
			add_action( 'init', array( $this, 'add_rewrite_rules' ) );

			// Cache clear hooks.
			add_action( 'save_post', array( $this, 'clear_reserved_cache' ) );
			add_action( 'deleted_post', array( $this, 'clear_reserved_cache' ) );
			add_action( 'create_term', array( $this, 'clear_reserved_cache' ) );
			add_action( 'edit_term', array( $this, 'clear_reserved_cache' ) );
			add_action( 'delete_term', array( $this, 'clear_reserved_cache' ) );
			add_action( 'wp_update_nav_menu', array( $this, 'clear_reserved_cache' ) );
		}

		/**
		 * Get single instance of WC_SC_URL_Coupon
		 *
		 * @return WC_SC_URL_Coupon Singleton object of WC_SC_URL_Coupon
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
		 * Apply coupon code if passed in the url.
		 */
		public function apply_coupon_from_url() {
			try {
				// Skip non-front-end requests.
				if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_rest_endpoint() || defined( 'REST_REQUEST' ) ) {
					return;
				}
				parse_str( wp_unslash( $_SERVER['QUERY_STRING'] ?? '' ), $coupon_args ); // phpcs:ignore
				$coupon_args = wc_clean( $coupon_args );

				if ( empty( $coupon_args['coupon-code'] ) ) {
					$path     		= trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' ); // phpcs:ignore
					$segments       = explode( '/', $path );
					$url_coupon_key = array_search( 'coupon-code', $segments, true );

					if ( false === strpos( $path, 'coupon-code' ) || empty( $segments ) || false === $url_coupon_key || ! isset( $segments[ 1 + $url_coupon_key ] ) || empty( sanitize_text_field( urldecode( $segments[ $url_coupon_key + 1 ] ) ) ) ) {
						return;
					}

					$coupon_code     = sanitize_text_field( urldecode( $segments[ 1 + $url_coupon_key ] ) ?? '' );
					$redirect_target = isset( $segments[ 2 + $url_coupon_key ] ) ? sanitize_text_field( $segments[ 2 + $url_coupon_key ] ) : '';

					$add_to_cart = '';
					if ( isset( $segments[ 3 + $url_coupon_key ] ) && is_numeric( $segments[ 3 + $url_coupon_key ] ) ) {
						$add_to_cart = absint( $segments[ 3 + $url_coupon_key ] );
					}

					$coupon_args = array(
						'coupon-code' => $coupon_code,
						'sc-page'     => $redirect_target,
						'add-to-cart' => ! empty( $coupon_args['add-to-cart'] ) ? $coupon_args['add-to-cart'] : '',
					);
					$coupon_args = wc_clean( $coupon_args );
				}

				if ( ! is_array( $coupon_args ) || empty( $coupon_args ) ) {
					return;
				}

				if ( empty( $coupon_args['coupon-code'] ) ) {
					return;
				}

				$coupons_data = array();

				$coupon_args['coupon-code'] = urldecode( $coupon_args['coupon-code'] );

				$coupon_codes = explode( ',', $coupon_args['coupon-code'] );
				$coupon_codes = array_filter( $coupon_codes ); // Remove empty coupon codes if any.

				$max_url_coupons_limit = apply_filters(
					'wc_sc_max_url_coupons_limit',
					get_option( 'wc_sc_max_url_coupons_limit', 5 ),
					array(
						'source'     => $this,
						'query_args' => $coupon_args,
					)
				);

				if ( is_array( $coupon_codes ) ) {
					foreach ( $coupon_codes as $coupon_index => $coupon_code ) {
						// Process only first five coupons to avoid GET request parameter limit.
						if ( $max_url_coupons_limit === $coupon_index ) {
							break;
						}

						if ( empty( $coupon_code ) ) {
							continue;
						}

						$coupons_data[] = array(
							'coupon-code' => $coupon_code,
						);
					}
				}

				if ( empty( $coupons_data ) ) {
					return;
				}

				$cart          = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
				$is_cart_empty = is_a( $cart, 'WC_Cart' ) && is_callable( array( $cart, 'is_empty' ) ) && $cart->is_empty();

				if ( true === $is_cart_empty ) {
					$is_hold = apply_filters(
						'wc_sc_hold_applied_coupons',
						true,
						array(
							'coupons_data' => $coupons_data,
							'source'       => $this,
						)
					);
					if ( true === $is_hold ) {
						$this->hold_applied_coupon( $coupons_data );
					}
					// Set a session cookie to persist the coupon in case the cart is empty. This code will persist the coupon even if the param sc-page is not supplied.
					WC()->session->set_customer_session_cookie( true ); // Thanks to: Devon Godfrey.
				} else {
					foreach ( $coupons_data as $coupon_data ) {
						$coupon_code = $coupon_data['coupon-code'];

						if ( ! WC()->cart->has_discount( $coupon_code ) ) {
							WC()->cart->add_discount( trim( $coupon_code ) );
						}
					}
				}

				if ( ! empty( $coupon_args['add-to-cart'] ) ) {
					add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'add_to_cart_redirect' ), 20, 2 );
					return; // Redirection handed over to WooCommerce.
				}

				if ( is_array( $coupon_codes ) && count( $coupon_codes ) === 1 ) {
					$coupon_code = trim( $coupon_codes[0] );

					if ( ! empty( $coupon_code ) ) {
						$slug = sanitize_title( $coupon_code );
						$page = ( function_exists( 'wpcom_vip_get_page_by_path' ) ) ? wpcom_vip_get_page_by_path( $slug, OBJECT, 'page' ) : get_page_by_path( $slug, OBJECT, 'page' ); // phpcs:ignore

						if ( $page && strtolower( $page->post_name ) === strtolower( $slug ) ) {
							$redirect_url = get_permalink( $page->ID );
							wp_safe_redirect( $redirect_url );
							exit;
						}
					}
				}

				if ( empty( $coupon_args['sc-page'] ) ) {
					if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
						return;
					}

					wp_safe_redirect( home_url() );
					exit;
				}

				$redirect_url = $this->get_sc_redirect_url( $coupon_args );

				wp_safe_redirect( $redirect_url );
				exit;
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}

		}

		/**
		 * Get Smart Coupons redirect url.
		 *
		 * @param array $coupon_args Coupon args.
		 * @return string
		 */
		public function get_sc_redirect_url( $coupon_args = array() ) {

			$redirect_url = '';

			if ( empty( $coupon_args ) || ! is_array( $coupon_args ) ) {
				return $redirect_url;
			}

			if ( in_array( $coupon_args['sc-page'], array( 'shop', 'cart', 'checkout', 'myaccount' ), true ) ) {
				$page_id      = $this->is_wc_gte_30() ? wc_get_page_id( $coupon_args['sc-page'] ) : woocommerce_get_page_id( $coupon_args['sc-page'] );
				$redirect_url = get_permalink( $page_id );
			} elseif ( is_string( $coupon_args['sc-page'] ) ) {
				if ( is_numeric( $coupon_args['sc-page'] ) && ! is_float( $coupon_args['sc-page'] ) ) {
					$page = $coupon_args['sc-page'];
				} else {
					$page = ( function_exists( 'wpcom_vip_get_page_by_path' ) ) ? wpcom_vip_get_page_by_path( $coupon_args['sc-page'], OBJECT, get_post_types() ) : get_page_by_path( $coupon_args['sc-page'], OBJECT, get_post_types() ); // phpcs:ignore
				}
				$redirect_url = get_permalink( $page );
			} elseif ( is_numeric( $coupon_args['sc-page'] ) && ! is_float( $coupon_args['sc-page'] ) ) {
				$redirect_url = get_permalink( $coupon_args['sc-page'] );
			}

			if ( empty( $redirect_url ) ) {
				$redirect_url = home_url();
			}

			// unset known values in the array to re-build URL params below.
			if ( isset( $coupon_args['coupon-code'] ) ) {
				unset( $coupon_args['coupon-code'] );
			}
			if ( isset( $coupon_args['sc-page'] ) ) {
				unset( $coupon_args['sc-page'] );
			}
			if ( isset( $coupon_args['add-to-cart'] ) ) {
				unset( $coupon_args['add-to-cart'] );
			}

			// Not using WP's build_query due to performance.
			$additional_url_params = http_build_query( $coupon_args );
			if ( ! empty( $additional_url_params ) ) {
				$redirect_url .= ( ( false === strpos( $additional_url_params, '?' ) ) ? '?' : '&' ) . $additional_url_params;
			}

			return $this->get_redirect_url_after_smart_coupons_process( $redirect_url );
		}

		/**
		 * WooCommerce handles add to cart redirect.
		 *
		 * @param string     $url The redirect URL.
		 * @param WC_Product $product Product.
		 * @return string
		 */
		public function add_to_cart_redirect( $url = '', $product = null ) {
			try {
				remove_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'add_to_cart_redirect' ), 20 );

				parse_str( wp_unslash( $_SERVER['QUERY_STRING'] ?? '' ), $coupon_args ); // phpcs:ignore
				$coupon_args = wc_clean( $coupon_args );

				if ( empty( $coupon_args['coupon-code'] ) ) {
					$path     		= trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' ); // phpcs:ignore
					$segments       = explode( '/', $path );
					$url_coupon_key = array_search( 'coupon-code', $segments, true );

					if ( false === strpos( $path, 'coupon-code' ) || empty( $segments ) || false === $url_coupon_key || ! isset( $segments[ 1 + $url_coupon_key ] ) || empty( sanitize_text_field( urldecode( $segments[ $url_coupon_key + 1 ] ) ) ) ) {
						return $url;
					}

					$coupon_code     = sanitize_text_field( urldecode( $segments[ 1 + $url_coupon_key ] ) ?? '' );
					$redirect_target = isset( $segments[ 2 + $url_coupon_key ] ) ? sanitize_text_field( $segments[ 2 + $url_coupon_key ] ) : '';

					$add_to_cart = '';
					if ( isset( $segments[ 3 + $url_coupon_key ] ) && is_numeric( $segments[ 3 + $url_coupon_key ] ) ) {
						$add_to_cart = absint( $segments[ 3 + $url_coupon_key ] );
					}

					$coupon_args = array(
						'coupon-code' => $coupon_code,
						'sc-page'     => $redirect_target,
						'add-to-cart' => $add_to_cart,
					);

					$coupon_args = wc_clean( $coupon_args );
				}

				$cart          = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
				$is_cart_empty = is_a( $cart, 'WC_Cart' ) && is_callable( array( $cart, 'is_empty' ) ) && $cart->is_empty();

				if ( false === $is_cart_empty && ! empty( $coupon_args['coupon-code'] ) ) {
					$coupon_args['coupon-code'] = urldecode( $coupon_args['coupon-code'] );

					$coupon_codes = explode( ',', $coupon_args['coupon-code'] );
					$coupon_codes = array_filter( $coupon_codes ); // Remove empty coupon codes if any.

					if ( ! empty( $coupon_codes ) ) {
						$max_url_coupons_limit = apply_filters( 'wc_sc_max_url_coupons_limit', 5 );
						$coupon_codes          = ( ! empty( $max_url_coupons_limit ) ) ? array_slice( $coupon_codes, 0, $max_url_coupons_limit ) : array();
						foreach ( $coupon_codes as $coupon_code ) {
							$coupon = new WC_Coupon( $coupon_code );
							if ( ! WC()->cart->has_discount( $coupon_code ) && $this->is_valid( $coupon ) ) {
								WC()->cart->add_discount( trim( $coupon_code ) );
							}
						}
					}
				}

				if ( ! empty( $coupon_args['sc-page'] ) ) {
					return $this->get_sc_redirect_url( $coupon_args );
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}

			return $url;
		}

		/**
		 * Apply coupon code from session, if any.
		 */
		public function apply_coupon_from_session() {
			try {
				// Skip non-front-end requests.
				if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_rest_endpoint() || defined( 'REST_REQUEST' ) ) {
					return;
				}
				$cart = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
				if ( empty( $cart ) || WC()->cart->is_empty() ) {
					return;
				}

				$user_id = get_current_user_id();

				if ( 0 === $user_id ) {
					$unique_id               = ( ! empty( $_COOKIE['sc_applied_coupon_profile_id'] ) ) ? wc_clean( wp_unslash( $_COOKIE['sc_applied_coupon_profile_id'] ) ) : ''; // phpcs:ignore
					$applied_coupon_from_url = ( ! empty( $unique_id ) ) ? $this->get_applied_coupons_by_guest_user( $unique_id ) : array();
				} else {
					$applied_coupon_from_url = get_user_meta( $user_id, 'sc_applied_coupon_from_url', true );
				}

				if ( empty( $applied_coupon_from_url ) || ! is_array( $applied_coupon_from_url ) ) {
					return;
				}

				foreach ( $applied_coupon_from_url as $index => $coupon_code ) {
					$coupon = new WC_Coupon( $coupon_code );
					if ( $this->is_valid( $coupon ) && ! WC()->cart->has_discount( $coupon_code ) ) {
						WC()->cart->add_discount( trim( $coupon_code ) );
						unset( $applied_coupon_from_url[ $index ] );
					}
				}

				if ( 0 === $user_id ) {
					$this->set_applied_coupon_for_guest_user( $unique_id, $applied_coupon_from_url );
				} else {
					update_user_meta( $user_id, 'sc_applied_coupon_from_url', $applied_coupon_from_url );
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}

		}

		/**
		 * Apply coupon code from session, if any.
		 *
		 * @param array $coupons_args The coupon arguments.
		 */
		public function hold_applied_coupon( $coupons_args = array() ) {

			if ( empty( $coupons_args ) ) {
				return;
			}

			$user_id = get_current_user_id();

			$saved_status = array();
			$saved_status = ( 0 === $user_id ) ? $this->save_applied_coupon_in_cookie( $coupons_args ) : $this->save_applied_coupon_in_account( $coupons_args, $user_id );
			if ( empty( $saved_status ) ) {
				return;
			}

			foreach ( $coupons_args as $coupon_args ) {
				$coupon_code = $coupon_args['coupon-code'];
				$save_status = isset( $saved_status[ $coupon_code ] ) ? $saved_status[ $coupon_code ] : '';
				if ( 'saved' === $save_status ) {
					/* translators: %s: $coupon_code coupon code */
					$notice = sprintf( _x( 'Coupon code "%s" applied successfully. Please add some products to the cart to see the discount.', 'This notice will be shown on the cart or the checkout page if the coupon will be applied successfully.', 'woocommerce-smart-coupons' ), $coupon_code );
					wc_add_notice( $notice, 'success' );
				} elseif ( 'already_saved' === $save_status ) {
					/* translators: %s: $coupon_code coupon code */
					$notice = sprintf( _x( 'Coupon code "%s" already applied! Please add some products to the cart to see the discount.', 'This notice will be shown on the cart or the checkout page if the coupon is already applied.', 'woocommerce-smart-coupons' ), $coupon_code );
					wc_add_notice( $notice, 'error' );
				}
			}

		}

		/**
		 * Apply coupon code from session, if any.
		 *
		 * @param array $coupons_args The coupon arguments.
		 * @return array $saved_status
		 */
		public function save_applied_coupon_in_cookie( $coupons_args = array() ) {

			// Variable to store whether coupons saved/already saved in cookie.
			$saved_status = array();

			if ( empty( $coupons_args ) ) {
				return $saved_status;
			}

			if ( empty( $_COOKIE['sc_applied_coupon_profile_id'] ) ) {
				$unique_id = $this->generate_unique_id();
			} else {
				$unique_id = wc_clean( wp_unslash( $_COOKIE['sc_applied_coupon_profile_id'] ) ); // phpcs:ignore
			}

			$applied_coupons = $this->get_applied_coupons_by_guest_user( $unique_id );

			foreach ( $coupons_args as $coupon_args ) {
				$coupon_code = isset( $coupon_args['coupon-code'] ) ? $coupon_args['coupon-code'] : '';
				if ( is_array( $applied_coupons ) && $this->sc_coupon_code_exists( $coupon_code, $applied_coupons ) ) {
					$saved_status[ $coupon_code ] = 'already_saved';
				} else {
					$applied_coupons[]            = $coupon_code;
					$saved_status[ $coupon_code ] = 'saved';
				}
			}

			$this->set_applied_coupon_for_guest_user( $unique_id, $applied_coupons );
			wc_setcookie( 'sc_applied_coupon_profile_id', $unique_id, $this->get_cookie_life() );

			return $saved_status;
		}

		/**
		 * Apply coupon code from session, if any.
		 *
		 * @param array $coupons_args The coupon arguments.
		 * @param int   $user_id The user id.
		 * @return array $saved_status
		 */
		public function save_applied_coupon_in_account( $coupons_args = array(), $user_id = 0 ) {

			// Variable to store whether coupons saved/already saved in user meta.
			$saved_status = array();

			if ( ! empty( $coupons_args ) ) {

				$applied_coupons = get_user_meta( $user_id, 'sc_applied_coupon_from_url', true );

				if ( empty( $applied_coupons ) ) {
					$applied_coupons = array();
				}

				foreach ( $coupons_args as $coupon_args ) {
					$coupon_code = $coupon_args['coupon-code'];
					if ( ! $this->sc_coupon_code_exists( $coupon_code, $applied_coupons ) ) {
						$applied_coupons[]            = $coupon_args['coupon-code'];
						$saved_status[ $coupon_code ] = 'saved';
					} else {
						$saved_status[ $coupon_code ] = 'already_saved';
					}
				}

				update_user_meta( $user_id, 'sc_applied_coupon_from_url', $applied_coupons );
			}

			return $saved_status;

		}

		/**
		 * Apply coupon code from session, if any
		 */
		public function move_applied_coupon_from_cookies_to_account() {
			try {
				// Skip non-front-end requests.
				if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_rest_endpoint() || defined( 'REST_REQUEST' ) ) {
					return;
				}
				$user_id = get_current_user_id();

				if ( $user_id > 0 && ! empty( $_COOKIE['sc_applied_coupon_profile_id'] ) ) {

					$unique_id = wc_clean( wp_unslash( $_COOKIE['sc_applied_coupon_profile_id'] ) ); // phpcs:ignore

					$applied_coupons = $this->get_applied_coupons_by_guest_user( $unique_id );

					if ( false !== $applied_coupons && is_array( $applied_coupons ) && ! empty( $applied_coupons ) ) {

						$saved_coupons = get_user_meta( $user_id, 'sc_applied_coupon_from_url', true );
						if ( empty( $saved_coupons ) || ! is_array( $saved_coupons ) ) {
							$saved_coupons = array();
						}
						$saved_coupons = array_merge( $saved_coupons, $applied_coupons );
						update_user_meta( $user_id, 'sc_applied_coupon_from_url', $saved_coupons );
						wc_setcookie( 'sc_applied_coupon_profile_id', '' );
						$this->delete_applied_coupons_of_guest_user( $unique_id );
						delete_option( 'sc_applied_coupon_profile_' . $unique_id );
					}
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}

		}

		/**
		 * Function to get redirect URL after processing Smart Coupons params
		 *
		 * @param string $url The URL.
		 * @return string $url
		 */
		public function get_redirect_url_after_smart_coupons_process( $url = '' ) {
			try {
				if ( empty( $url ) ) {
					return $url;
				}

				$query_string 	= ( ! empty( $_SERVER['QUERY_STRING'] ) ) ? wc_clean( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : ''; // phpcs:ignore
				$url_args     = array();
				parse_str( $query_string, $url_args );

				if ( empty( $url_args['coupon-code'] ) ) {
					$path     = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' ); // phpcs:ignore
					$segments = explode( '/', $path );

					$url_coupon_key = array_search( 'coupon-code', $segments, true );
					$coupon_code    = sanitize_text_field( urldecode( $segments[ 1 + $url_coupon_key ] ) ?? '' );

					$redirect_target = isset( $segments[ 2 + $url_coupon_key ] ) ? sanitize_text_field( $segments[ 2 + $url_coupon_key ] ) : '';

					$coupon_args = array(
						'coupon-code' => $coupon_code,
						'sc-page'     => $redirect_target,
					);

					$url_args = wc_clean( $coupon_args );
				}

				$sc_params = array( 'coupon-code', 'sc-page' );

				$url_params = array_diff_key( $url_args, array_flip( $sc_params ) );

				if ( empty( $url_params['add-to-cart'] ) ) {
					$redirect_url = apply_filters( 'wc_sc_redirect_url_after_smart_coupons_process', add_query_arg( $url_params, $url ), array( 'source' => $this ) );
				} else {
					$redirect_url = apply_filters( 'wc_sc_redirect_url_after_smart_coupons_process', $url, array( 'source' => $this ) );
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
				$redirect_url = $url;
			}

			return $redirect_url;
		}

		/**
		 * Function to convert sc coupon notices to wc notices
		 */
		public function convert_sc_coupon_notices_to_wc_notices() {
			try {
				// Skip non-front-end requests.
				if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_rest_endpoint() || defined( 'REST_REQUEST' ) ) {
					return;
				}
				$coupon_notices = $this->get_coupon_notices();
				// If we have coupon notices to be shown and we are on a woocommerce page then convert them to wc notices.
				if ( count( $coupon_notices ) > 0 && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
					foreach ( $coupon_notices as $notice_type => $notices ) {
						if ( count( $notices ) > 0 ) {
							foreach ( $notices as $notice ) {
								wc_add_notice( $notice, $notice_type );
							}
						}
					}
					$this->remove_coupon_notices();
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Function to get sc coupon notices
		 */
		public function get_coupon_notices() {
			return apply_filters( 'wc_sc_coupon_notices', $this->coupon_notices );
		}


		/**
		 * Function to remove sc coupon notices
		 */
		public function remove_coupon_notices() {
			$this->coupon_notices = array();
		}

		/**
		 * Function to add coupon notices to wp content
		 *
		 * @param string $content page content.
		 * @return string $content page content
		 */
		public function show_coupon_notices( $content = '' ) {
			try {
				$coupon_notices = $this->get_coupon_notices();

				if ( count( $coupon_notices ) > 0 ) {

					// Buffer output.
					ob_start();

					foreach ( $coupon_notices as $notice_type => $notices ) {
						if ( count( $coupon_notices[ $notice_type ] ) > 0 ) {
							wc_get_template(
								"notices/{$notice_type}.php",
								array(
									'messages' => $coupon_notices[ $notice_type ],
								)
							);
						}
					}

					$notices = wc_kses_notice( ob_get_clean() );
					$content = $notices . $content;
					$this->remove_coupon_notices(); // Empty out notice data.
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}

			return $content;
		}

		/**
		 * Function to get coupon codes by guest user's unique ID.
		 *
		 * @param  string $unique_id Unique ID for guest user.
		 *
		 * @return array.
		 */
		public function get_applied_coupons_by_guest_user( $unique_id = '' ) {

			$key = sprintf( 'sc_applied_coupon_profile_%s', $unique_id );

			// Get coupons from `transient`.
			$coupons = get_transient( $key );
			if ( ! empty( $coupons ) && is_array( $coupons ) ) {
				return $coupons;
			}
			// Get coupon from `wp_option`.
			return get_option( $key, array() );
		}

		/**
		 * Function to set applied coupons for guest user.
		 *
		 * @param  string $unique_id Unique id for guest user.
		 * @param  array  $coupons   Array of coupon codes.
		 *
		 * @return bool.
		 */
		public function set_applied_coupon_for_guest_user( $unique_id = '', $coupons = array() ) {
			if ( ! empty( $unique_id ) && is_array( $coupons ) ) {
				$key = sprintf( 'sc_applied_coupon_profile_%s', $unique_id );

				if ( empty( $coupons ) ) {
					return delete_transient( $key );
				} else {
					return set_transient(
						$key,
						$coupons,
						apply_filters( 'wc_sc_applied_coupon_by_url_expire_time', MONTH_IN_SECONDS )
					);
				}
			}

			return false;
		}

		/**
		 * Function to delete all applied coupons for a guest user.
		 *
		 * @param  string $unique_id Unique id for guest user.
		 *
		 * @return bool.
		 */
		public function delete_applied_coupons_of_guest_user( $unique_id = '' ) {

			if ( ! empty( $unique_id ) ) {
				$key = sprintf( 'sc_applied_coupon_profile_%s', $unique_id );
				return delete_transient( $key );
			}

			return false;
		}

		/**
		 * AJAX handler to set data in session.
		 *
		 * Sends JSON response based on success or failure.
		 */
		public function maybe_set_session() {
			try {
				// Sanitize and verify nonce for security.
				$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
				if ( ! wp_verify_nonce( $nonce, 'wc_sc_set_session_nonce' ) ) {
					wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'woocommerce-smart-coupons' ) ) );
				}

				// Check if email is provided in the POST data.
				if ( ! isset( $_POST['email'] ) ) {
					wp_send_json_error( array( 'message' => __( 'No email provided.', 'woocommerce-smart-coupons' ) ) );
				}

				// Sanitize and validate the email address.
				$billing_email = sanitize_email( wp_unslash( $_POST['email'] ) );
				if ( ! is_email( $billing_email ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid email format.', 'woocommerce-smart-coupons' ) ) );
				}

				// Set the email in WooCommerce session.
				wc()->customer->set_billing_email( $billing_email );

				// Send success response.
				wp_send_json_success( array( 'message' => __( 'Email set in session successfully.', 'woocommerce-smart-coupons' ) ) );
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
				wp_send_json_error( array( 'message' => __( 'An unexpected error occurred.', 'woocommerce-smart-coupons' ) ) );
			}
		}

		/**
		 * Add styles & javascript code to the page.
		 *
		 * Hooks this function to 'wp_footer' action.
		 */
		public function styles_and_scripts() {

			if ( is_checkout() && ! WC()->is_rest_api_request() ) {
				$ajax_url = admin_url( 'admin-ajax.php' );
				$nonce    = wp_create_nonce( 'wc_sc_set_session_nonce' );
				$js       = "
					const billingEmailInput = document.querySelector('input[name=billing_email]');

					let debounceTimeout;
					let lastEmail = '';

					// Debounced version of setSesssion
					async function debouncedSetSession(event) {
						const email = event.target.value.trim();

						// If the email hasn't changed, do nothing
						if (email === lastEmail) {
							return;
						}

						if (email) {
							// Clear the previous timeout to reset the debounce timer
							clearTimeout(debounceTimeout);

							// Set a new timeout for executing setSesssion after 500ms of inactivity
							debounceTimeout = setTimeout(async () => {
								try {
									const response = await fetch('$ajax_url', {
										method: 'POST',
										headers: {
											'Content-Type': 'application/x-www-form-urlencoded'
										},
										body: new URLSearchParams({
											action: 'wc_sc_set_session',
											email: email,
											_wpnonce: '$nonce'
										})
									});

									if (response.ok) {
										const result = await response.json();
										if (result.success) {
											document.body.dispatchEvent(new Event('update_checkout'));
											lastEmail = email;
										} else {
											console.error(" . wp_json_encode( __( 'Error updating checkout:', 'woocommerce-smart-coupons' ) ) /* phpcs:ignore */ . ", result?.data?.message );
										}
									} else {
										console.error(" . wp_json_encode( __( 'Network error:', 'woocommerce-smart-coupons' ) ) /* phpcs:ignore */ . ", response.statusText);
									}
								} catch (error) {
									console.error(" . wp_json_encode( __( 'Fetch error:', 'woocommerce-smart-coupons' ) ) . ", error);
								}
							}, 500);
						}
					}

					if (billingEmailInput) {
						billingEmailInput.addEventListener('change', debouncedSetSession);
						billingEmailInput.addEventListener('blur', debouncedSetSession);
					}
				";

				wc_enqueue_js( $js );
			}
		}

		/**
		 * Check if a coupon apply from session via url.
		 *
		 * @param string $coupon_code The coupon arguments.
		 * @return boolean either true or false
		 */
		public function is_coupon_applied_via_url( $coupon_code = '' ) {

			if ( empty( $coupon_code ) || ! is_string( $coupon_code ) ) {
				return false;
			}

			$user_id = get_current_user_id();

			if ( 0 === $user_id ) {
				$unique_id               = ( ! empty( $_COOKIE['sc_applied_coupon_profile_id'] ) ) ? wc_clean( wp_unslash( $_COOKIE['sc_applied_coupon_profile_id'] ) ) : ''; // phpcs:ignore
				$applied_coupon_from_url = ( ! empty( $unique_id ) ) ? $this->get_applied_coupons_by_guest_user( $unique_id ) : array();
			} else {
				$applied_coupon_from_url = get_user_meta( $user_id, 'sc_applied_coupon_from_url', true );
			}

			if ( empty( $applied_coupon_from_url ) || ! is_array( $applied_coupon_from_url ) ) {
				if ( isset( $_REQUEST['coupon-code'] ) ) { // phpcs:ignore
					return $this->sc_coupon_code_exists( $coupon_code, WC()->cart->get_applied_coupons() );
				}
				return false;
			}

			return $this->sc_coupon_code_exists( $coupon_code, $applied_coupon_from_url );
		}

		/**
		 * Add rewrite rules for coupons via URL
		 */
		public function add_rewrite_rules() {
			// Skip non-front-end requests.
			if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_rest_endpoint() || defined( 'REST_REQUEST' ) ) {
				return;
			}

			$reserved = $this->get_reserved_slugs();

			add_rewrite_rule(
				'^coupon-code/((?!' . $reserved . ')[^/]+)' . // coupon code (second segment).
				'(?:/([^/]+))?' . // optional page (third).
				'(?:/([0-9]+))?/?$',
				'index.php?coupon-code=$matches[1]&sc-page=$matches[2]&add-to-cart=$matches[3]',
				'bottom'
			);
		}

		/**
		 * Build regex for reserved slugs (cached)
		 */
		private function get_reserved_slugs() {
			$cached = get_transient( $this->reserved_cache_key );
			if ( $cached ) {
				return $cached;
			}

			$reserved = array(
				'wp-admin',
				'wp-login.php',
				'feed',
				'comments',
			);

			// WooCommerce endpoints.
			if ( function_exists( 'WC' ) && WC()->query ) {
				$wc_qvars = array_keys( WC()->query->get_query_vars() );
				if ( is_array( $wc_qvars ) ) {
					$reserved = array_merge( $reserved, $wc_qvars );
				}
			}

			// All published page slugs.
			$pages = get_pages( array( 'post_status' => 'publish' ) );
			foreach ( $pages as $p ) {
				if ( ! empty( $p->post_name ) ) {
					$reserved[] = $p->post_name;
				}
			}

			// Post types.
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			foreach ( $post_types as $pt ) {
				if ( ! empty( $pt->rewrite['slug'] ) ) {
					$reserved[] = $pt->rewrite['slug'];
				}
				$reserved[] = $pt->name;
			}

			// Taxonomies.
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
			foreach ( $taxonomies as $tax ) {
				if ( ! empty( $tax->rewrite['slug'] ) ) {
					$reserved[] = $tax->rewrite['slug'];
				}
				$reserved[] = $tax->name;
			}

			// Normalize, dedupe, escape.
			$reserved = array_filter( array_unique( array_map( 'trim', $reserved ) ) );
			$escaped  = array_map(
				function( $s ) {
					return preg_quote( $s, '/' );
				},
				$reserved
			);

			$result = implode( '|', $escaped );

			set_transient( $this->reserved_cache_key, $result, 12 * HOUR_IN_SECONDS );
			return $result;
		}

		/**
		 * Clear reserved slugs cache
		 */
		public function clear_reserved_cache() {
			if ( is_admin() && function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				if ( ! empty( $screen->post_type ) && 'product' === $screen->post_type ) {
					return; // No need to run the following code when working with products on the admin side.
				}
			}
			delete_transient( $this->reserved_cache_key );
		}

	}
}

WC_SC_URL_Coupon::get_instance();
