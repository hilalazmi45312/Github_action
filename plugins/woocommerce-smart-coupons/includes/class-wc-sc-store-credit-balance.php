<?php
/**
 * Store Credit Balance Display
 *
 * @author      StoreApps
 * @since       9.55.0
 * @version     1.1.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Store_Credit_Balance' ) ) {

	/**
	 * Class for handling Store Credit balance display functionality
	 */
	class WC_SC_Store_Credit_Balance {

		/**
		 * Variable to hold instance of WC_SC_Store_Credit_Balance
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of WC_SC_Store_Credit_Balance
		 *
		 * @return WC_SC_Store_Credit_Balance Singleton object of WC_SC_Store_Credit_Balance
		 */
		public static function get_instance() {

			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {

				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'initialize' ) );
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 *
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
		 * Initialize the class.
		 */
		public function initialize() {
			// Clear cache when Store Credit changes.
			add_action( 'woocommerce_order_status_completed', array( $this, 'clear_user_balance_cache_on_order_complete' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'clear_user_balance_cache_on_order_complete' ) );
			add_action( 'wc_sc_new_coupon_generated', array( $this, 'clear_user_balance_cache_on_coupon_generated' ) );
			add_action( 'woocommerce_coupon_used', array( $this, 'clear_user_balance_cache_on_coupon_used' ) );
			add_action( 'save_post', array( $this, 'clear_cache_on_coupon_save' ), 10, 2 );

			// Shortcodes.
			add_shortcode( 'wc_sc_balance', array( $this, 'store_credit_balance_shortcode' ) ); // Alternative shortcode.
			add_shortcode( 'smart_coupons_balance', array( $this, 'store_credit_balance_shortcode' ) ); // Alternative shortcode.
		}

		/**
		 * Store Credit Balance Shortcode
		 *
		 * Usage: [store_credit_balance] or [sc_balance]
		 * Parameters:
		 * - format: 'amount' (default) or 'raw'
		 * - label: Custom text before balance (default: 'Store Credit: ')
		 * - class: Custom CSS class
		 * - user_id: Specific user ID (admin only)
		 * - show_label: true/false (default: true) - Whether to show the text label
		 *
		 * @param array $atts Shortcode attributes.
		 *
		 * @return string HTML output
		 */
		public function store_credit_balance_shortcode( $atts ) {
			if ( ! is_user_logged_in() ) {
				return '';
			}

			$atts = shortcode_atts(
				array(
					'format'        => 'amount',
					'label'         => __( 'Store Credit: ', 'woocommerce-smart-coupons' ),
					'class'         => 'wc-sc-balance',
					'user_id'       => null,
					'force_refresh' => false,
					'show_label'    => true,
				),
				$atts,
				'store_credit_balance'
			);

			$user_id = $atts['user_id'];

			// Security check: only admins can view other users' balances.
			if ( $user_id && get_current_user_id() !== $user_id && ! current_user_can( 'manage_woocommerce' ) ) {
				$user_id = null;
			}

			$force_refresh = wc_string_to_bool( $atts['force_refresh'] );
			$balance       = $this->get_customer_store_credit_balance( $user_id, $force_refresh );

			if ( 'raw' === $atts['format'] ) {
				return wp_kses_post( $balance );
			}

			$formatted_balance = $this->format_balance( $balance );
			$css_class         = esc_attr( $atts['class'] );
			$show_label        = wc_string_to_bool( $atts['show_label'] );
			$text              = $show_label ? esc_html( $atts['label'] ) : '';

			return wp_kses_post(
				sprintf(
					'<span class="%s">%s%s</span>',
					$css_class,
					$show_label ? '<span class="wc-sc-balance-label" style="padding-right: 5px;">' . esc_html( $atts['label'] ) . '</span>' : '',
					$formatted_balance
				)
			);
		}

		/**
		 * Get customer's total Store Credit balance using sc_endpoint_content logic
		 *
		 * @param int  $user_id User ID (optional, defaults to current user).
		 * @param bool $force_refresh Force refresh without cache.
		 *
		 * @return float Total Store Credit balance
		 */
		public function get_customer_store_credit_balance( $user_id = null, $force_refresh = false ) {
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			if ( ! $user_id ) {
				return 0;
			}

			global $woocommerce_smart_coupon;

			if ( ! isset( $woocommerce_smart_coupon ) || ! is_a( $woocommerce_smart_coupon, 'WC_Smart_Coupons' ) ) {
				return 0;
			}

			try {
				$user = get_userdata( $user_id );
				if ( ! $user ) {
					return 0;
				}

				// Check cache first (unless force refresh is requested).
				$cache_key = 'wc_sc_balance_' . $user_id;
				if ( ! $force_refresh ) {
					$cached_balance = wp_cache_get( $cache_key, 'woocommerce_smart_coupons' );
					if ( false !== $cached_balance ) {
						return floatval( $cached_balance );
					}
				}

				$total_store_credit = 0;

				// Use the same logic as sc_endpoint_content function.
				$total_store_credit = $this->get_balance_using_sc_endpoint_logic( $user_id );

				// Cache the result for 30 seconds to avoid excessive queries.
				wp_cache_set( $cache_key, $total_store_credit, 'woocommerce_smart_coupons', 30 );

				return $total_store_credit;

			} catch ( Exception $e ) {
				$this->log( 'error', 'Store Credit Balance Error: ' . $e->getMessage() ); // phpcs:ignore
				return 0;
			}
		}

		/**
		 * Get customer's Store Credit balance using the same logic as sc_endpoint_content
		 *
		 * @param int $user_id User ID.
		 *
		 * @return float Total Store Credit balance
		 */
		public function get_balance_using_sc_endpoint_logic( $user_id ) {
			global $woocommerce_smart_coupon;

			if ( ! class_exists( 'WC_SC_Display_Coupons' ) ) {
				include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-display-coupons.php';
			}
			$wc_sc_display_coupons = WC_SC_Display_Coupons::get_instance();

			if ( ! is_object( $wc_sc_display_coupons ) ) {
				return 0;
			}

			try {
				$max_coupon_to_show = absint( get_option( 'wc_sc_setting_max_coupon_to_show', 5 ) );
				$max_coupon_to_show = get_option( 'wc_sc_setting_max_coupon_to_show_on_myaccount', ( $max_coupon_to_show * 10 ) );
				$max_coupon_to_show = apply_filters( 'wc_sc_max_coupon_to_show_on_myaccount', $max_coupon_to_show, array( 'source' => $wc_sc_display_coupons ) );
				$max_coupon_to_show = absint( $max_coupon_to_show );

				$coupons = array();
				if ( $max_coupon_to_show > 0 ) {
					$coupons = ( ! in_array( $wc_sc_display_coupons->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) ? $wc_sc_display_coupons->sc_get_available_coupons_list_old( array() ) : $wc_sc_display_coupons->sc_get_available_coupons_list( array() );
				}

				$total_store_credit = 0;
				$coupons_applied    = ( is_object( WC()->cart ) && is_callable( array( WC()->cart, 'get_applied_coupons' ) ) ) ? WC()->cart->get_applied_coupons() : array();

				foreach ( $coupons as $code ) {
					$coupon_id = ( isset( $code->id ) && ! empty( $code->id ) ) ? absint( $code->id ) : 0;

					if ( empty( $coupon_id ) ) {
						continue;
					}

					$coupon = new WC_Coupon( $coupon_id );

					if ( ! $coupon instanceof WC_Coupon || ! $wc_sc_display_coupons->is_callable( $coupon, 'get_code' ) ) {
						continue;
					}

					if ( $wc_sc_display_coupons->sc_coupon_code_exists( $coupon->get_code(), $coupons_applied ) ) {
						continue;
					}

					if ( $wc_sc_display_coupons->is_wc_gte_30() ) {
						$discount_type = $coupon->get_discount_type();
					} else {
						$discount_type = ( ! empty( $coupon->discount_type ) ) ? $coupon->discount_type : '';
					}

					if ( empty( $discount_type ) ) {
						continue;
					}

					$coupon_amount = $wc_sc_display_coupons->get_amount( $coupon, true );

					// Only count smart_coupon (Store Credit) types - same logic as sc_endpoint_content line 1351.
					if ( 'smart_coupon' === $discount_type ) {
						$show_as_valid = apply_filters( 'wc_sc_show_as_valid', $wc_sc_display_coupons->is_valid( $coupon ), array( 'coupon_obj' => $coupon ) );

						if ( true === $show_as_valid ) {
							$total_store_credit += $coupon_amount;
						}
					}
				}

				return $total_store_credit;

			} catch ( Exception $e ) {
				$this->log( 'error', 'Store Credit Balance sc_endpoint Logic Error: ' . $e->getMessage() ); // phpcs:ignore
				return 0;
			}
		}

		/**
		 * Format Store Credit balance for display
		 *
		 * @param float $balance The balance amount.
		 * @param bool  $include_currency Whether to include currency symbol.
		 *
		 * @return string Formatted balance
		 */
		public function format_balance( $balance, $include_currency = true ) {
			if ( $include_currency ) {
				return wc_price( $balance );
			}

			return wc_format_decimal( $balance, wc_get_price_decimals() );
		}

		/**
		 * Clear cache when coupon is saved/updated
		 *
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post Post object.
		 */
		public function clear_cache_on_coupon_save( $post_id, $post ) {
			try {
				if ( 'shop_coupon' !== $post->post_type ) {
					return;
				}

				$coupon = new WC_Coupon( $post_id );
				if ( 'smart_coupon' !== $coupon->get_discount_type() ) {
					return;
				}

				// Clear cache for all users associated with this coupon.
				$customer_emails = $coupon->get_meta( 'customer_email' );
				if ( ! empty( $customer_emails ) ) {
					if ( is_string( $customer_emails ) ) {
						$customer_emails = maybe_unserialize( $customer_emails );
					}

					if ( is_array( $customer_emails ) ) {
						foreach ( $customer_emails as $email ) {
							$user = get_user_by( 'email', $email );
							if ( $user ) {
								$this->clear_user_balance_cache( $user->ID );
							}
						}
					}
				}
			} catch ( Exception $e ) {
				$this->log( 'error', 'Store Credit Balance Cache Clear Error: ' . $e->getMessage() ); // phpcs:ignore
			}
		}

		/**
		 * Clear balance cache for specific user
		 *
		 * @param int $user_id User ID.
		 */
		public function clear_user_balance_cache( $user_id ) {
			$cache_key = 'wc_sc_balance_' . $user_id;
			wp_cache_delete( $cache_key, 'woocommerce_smart_coupons' );
		}

		/**
		 * Clear user balance cache when order is completed (Store Credit purchased)
		 *
		 * @param int $order_id Order ID.
		 */
		public function clear_user_balance_cache_on_order_complete( $order_id ) {
			try {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					return;
				}

				$user_id = $order->get_user_id();
				if ( $user_id ) {
					$this->clear_user_balance_cache( $user_id );
				}
			} catch ( Exception $e ) {
				$this->log( 'error', 'Store Credit Balance Cache Clear Error: ' . $e->getMessage() ); // phpcs:ignore
			}
		}

		/**
		 * Clear user balance cache when new coupon is generated
		 *
		 * @param array $args Coupon generation arguments.
		 */
		public function clear_user_balance_cache_on_coupon_generated( $args ) {
			try {
				if ( ! empty( $args['receiver_email'] ) ) {
					$user = get_user_by( 'email', $args['receiver_email'] );
					if ( $user ) {
						$this->clear_user_balance_cache( $user->ID );
					}
				}

				if ( ! empty( $args['order_id'] ) ) {
					$order = wc_get_order( $args['order_id'] );
					if ( $order && $order->get_user_id() ) {
						$this->clear_user_balance_cache( $order->get_user_id() );
					}
				}
			} catch ( Exception $e ) {
				$this->log( 'error', 'Store Credit Balance Cache Clear Error: ' . $e->getMessage() ); // phpcs:ignore
			}
		}

		/**
		 * Clear user balance cache when coupon is used.
		 *
		 * @param WC_Coupon $coupon Coupon object.
		 */
		public function clear_user_balance_cache_on_coupon_used( $coupon ) {
			try {
				if ( ! $coupon || 'smart_coupon' !== $coupon->get_discount_type() ) {
					return;
				}

				// Clear cache for current user.
				$user_id = get_current_user_id();
				if ( $user_id ) {
					$this->clear_user_balance_cache( $user_id );
				}

				// Also clear for any users associated with this coupon.
				$customer_emails = $coupon->get_meta( 'customer_email' );
				if ( ! empty( $customer_emails ) && is_array( $customer_emails ) ) {
					foreach ( $customer_emails as $email ) {
						$user = get_user_by( 'email', $email );
						if ( $user ) {
							$this->clear_user_balance_cache( $user->ID );
						}
					}
				}
			} catch ( Exception $e ) {
				$this->log( 'error', 'Store Credit Balance Cache Clear Error: ' . $e->getMessage() ); // phpcs:ignore
			}
		}
	}
}

WC_SC_Store_Credit_Balance::get_instance();
