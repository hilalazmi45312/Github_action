<?php
/**
 * Compatibility with Mollie Payments for WooCommerce
 * https://wordpress.org/plugins/mollie-payments-for-woocommerce/
 * 
 * This compatibility class ensures that Mollie payment webhooks can properly find and update
 * deposit sub-orders which use the custom post type 'awcdp_payment' instead of 'shop_order'.
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Comp_Mollie_Payments' ) ) {

	class Comp_Mollie_Payments {

		/**
		 * The single instance of the class
		 *
		 * @var Comp_Mollie_Payments
		 */
		private static $instance;
		
		/**
		 * Mollie plugin active flag
		 *
		 * @var bool
		 */
		private $mollie_active = false;
		
		/**
		 * Get single instance of the class
		 *
		 * @return Comp_Mollie_Payments
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			
			// Check if Mollie plugin is active
			$this->mollie_active = $this->is_mollie_active();
			
			if ( $this->mollie_active ) {
				$this->init_hooks();
			}
		}
		
		/**
		 * Initialize compatibility hooks
		 */
		private function init_hooks() {
			
			// Hook into WooCommerce order queries to include deposit payment types
			add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'include_deposit_orders_in_query' ), 10, 2 );
			
			// Ensure deposit orders use the correct order class
			add_filter( 'woocommerce_order_class', array( $this, 'use_deposit_order_class' ), 10, 2 );
			
			// Hook into pre_get_posts for direct WP_Query modifications
			add_action( 'pre_get_posts', array( $this, 'modify_order_query_for_mollie' ), 10 );
			
			// Add support for Mollie's order getter function
			add_filter( 'woocommerce_get_order_types', array( $this, 'add_deposit_order_type' ), 10, 2 );
			
			// Log when Mollie processes deposit orders (for debugging)
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				add_action( 'mollie-payments-for-woocommerce_before_webhook_payment_action', array( $this, 'log_deposit_order_processing' ), 10, 2 );
				add_action( 'mollie_wc_gateway_payment_completed', array( $this, 'log_deposit_order_completion' ), 10, 2 );
			}
			
			// Ensure Mollie recognizes deposit orders as valid orders
			add_filter( 'woocommerce_is_order_received_page', array( $this, 'handle_deposit_order_received_page' ) );
			
			// Handle payment gateway detection for deposit orders
			add_filter( 'woocommerce_order_get_payment_method', array( $this, 'get_deposit_order_payment_method' ), 10, 2 );
		}
		
		/**
		 * Check if Mollie Payments plugin is active
		 *
		 * @return bool
		 */
		private function is_mollie_active() {
			// Check by plugin file
			if ( in_array( 'mollie-payments-for-woocommerce/mollie-payments-for-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				return true;
			}
			
			// Check for multisite
			if ( is_multisite() ) {
				$plugins = get_site_option( 'active_sitewide_plugins' );
				if ( isset( $plugins['mollie-payments-for-woocommerce/mollie-payments-for-woocommerce.php'] ) ) {
					return true;
				}
			}
			
			// Check by constant
			if ( defined( 'M4W_PLUGIN_DIR' ) || defined( 'M4W_FILE' ) ) {
				return true;
			}
			
			// Check by class existence
			if ( class_exists( '\Mollie\WooCommerce\Payment\MollieOrderService' ) || 
			     class_exists( 'Mollie_WC_Plugin' ) ) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Modify WooCommerce order query to include deposit payment types
		 * when searching for orders by transaction ID or Mollie payment IDs
		 *
		 * @param array $query_vars Query variables
		 * @param array $query_args Query arguments
		 * @return array Modified query variables
		 */
		public function include_deposit_orders_in_query( $query_vars, $query_args ) {
			
			// Check if this is a Mollie-related query
			$is_mollie_query = false;
			
			// Check for transaction_id (used by Mollie webhooks)
			if ( isset( $query_args['transaction_id'] ) && ! empty( $query_args['transaction_id'] ) ) {
				$is_mollie_query = true;
			}
			
			// Check for Mollie-specific meta keys
			if ( isset( $query_args['meta_key'] ) && 
			     in_array( $query_args['meta_key'], array( '_mollie_order_id', '_mollie_payment_id', '_transaction_id' ) ) ) {
				$is_mollie_query = true;
			}
			
			// Check if we're in a Mollie webhook context
			if ( $this->is_mollie_webhook_request() ) {
				$is_mollie_query = true;
			}
			
			// If this is a Mollie query, include deposit payment types
			if ( $is_mollie_query ) {
				$query_vars = $this->add_deposit_type_to_query( $query_vars );
			}
			
			return $query_vars;
		}
		
		/**
		 * Ensure deposit orders use the correct order class
		 *
		 * @param string $classname Order class name
		 * @param string $order_type Order type
		 * @return string Modified class name
		 */
		public function use_deposit_order_class( $classname, $order_type ) {
			if ( $order_type === AWCDP_POST_TYPE && class_exists( 'AWCDP_Order' ) ) {
				return 'AWCDP_Order';
			}
			return $classname;
		}
		
		/**
		 * Modify WP_Query to include deposit order types when Mollie is searching
		 *
		 * @param WP_Query $query
		 */
		public function modify_order_query_for_mollie( $query ) {
			
			// Only modify queries in admin/AJAX/REST contexts
			if ( ! is_admin() && ! defined( 'DOING_AJAX' ) && ! defined( 'REST_REQUEST' ) ) {
				return;
			}
			
			// Check if this might be a Mollie-related query
			if ( ! $this->is_mollie_context( $query ) ) {
				return;
			}
			
			$post_type = $query->get( 'post_type' );
			
			// If querying for shop_order, also include awcdp_payment
			if ( $post_type === 'shop_order' ) {
				$query->set( 'post_type', array( 'shop_order', AWCDP_POST_TYPE ) );
			} elseif ( is_array( $post_type ) && in_array( 'shop_order', $post_type ) && ! in_array( AWCDP_POST_TYPE, $post_type ) ) {
				$post_type[] = AWCDP_POST_TYPE;
				$query->set( 'post_type', $post_type );
			}
		}
		
		/**
		 * Add deposit order type to WooCommerce order types
		 *
		 * @param array $order_types Order types
		 * @param string $for Context for order types
		 * @return array Modified order types
		 */
		public function add_deposit_order_type( $order_types, $for = '' ) {
			// Only add for order queries in Mollie context
			if ( $this->is_mollie_webhook_request() || $this->is_mollie_return_url() ) {
				if ( ! in_array( AWCDP_POST_TYPE, $order_types ) ) {
					$order_types[] = AWCDP_POST_TYPE;
				}
			}
			return $order_types;
		}
		
		/**
		 * Log when Mollie processes deposit orders (for debugging)
		 *
		 * @param object $payment Mollie payment object
		 * @param WC_Order $order WooCommerce order
		 */
		public function log_deposit_order_processing( $payment, $order ) {
			if ( $order->get_type() === AWCDP_POST_TYPE ) {
				error_log( sprintf(
					'[Mollie Deposits Compatibility] Processing deposit sub-order #%d (Payment: %s, Status: %s)',
					$order->get_id(),
					$payment->id,
					$payment->status
				) );
			}
		}
		
		/**
		 * Log when Mollie completes payment for deposit orders
		 *
		 * @param WC_Order $order WooCommerce order
		 * @param object $payment Mollie payment object
		 */
		public function log_deposit_order_completion( $order, $payment ) {
			if ( $order->get_type() === AWCDP_POST_TYPE ) {
				error_log( sprintf(
					'[Mollie Deposits Compatibility] Completed payment for deposit sub-order #%d (Payment: %s)',
					$order->get_id(),
					$payment->id
				) );
			}
		}
		
		/**
		 * Handle order received page for deposit orders
		 *
		 * @param bool $is_order_received_page
		 * @return bool
		 */
		public function handle_deposit_order_received_page( $is_order_received_page ) {
			global $wp;
			
			if ( ! empty( $wp->query_vars['order-received'] ) ) {
				$order = wc_get_order( absint( $wp->query_vars['order-received'] ) );
				if ( $order && $order->get_type() === AWCDP_POST_TYPE ) {
					return true;
				}
			}
			
			return $is_order_received_page;
		}
		
		/**
		 * Get payment method for deposit orders
		 *
		 * @param string $payment_method Payment method
		 * @param WC_Order $order Order object
		 * @return string
		 */
		public function get_deposit_order_payment_method( $payment_method, $order ) {
			if ( $order->get_type() === AWCDP_POST_TYPE ) {
				// Get parent order payment method if deposit order doesn't have one
				if ( empty( $payment_method ) ) {
					$parent_id = $order->get_parent_id();
					if ( $parent_id ) {
						$parent_order = wc_get_order( $parent_id );
						if ( $parent_order ) {
							return $parent_order->get_payment_method();
						}
					}
				}
			}
			return $payment_method;
		}
		
		/**
		 * Check if current request is a Mollie webhook request
		 *
		 * @return bool
		 */
		private function is_mollie_webhook_request() {
			// Check REST API webhook route
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				$request_uri = $_SERVER['REQUEST_URI'] ?? '';
				if ( strpos( $request_uri, 'mollie/v1/webhook' ) !== false ) {
					return true;
				}
			}
			
			// Check legacy webhook endpoint
			if ( isset( $_GET['wc-api'] ) && 
			     ( strpos( $_GET['wc-api'], 'mollie' ) !== false || 
			       $_GET['wc-api'] === 'mollie_return' ) ) {
				return true;
			}
			
			// Check if Mollie payment ID is in POST data
			if ( isset( $_POST['id'] ) && 
			     ( strpos( $_POST['id'], 'tr_' ) === 0 || 
			       strpos( $_POST['id'], 'ord_' ) === 0 ) ) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Check if current request is a Mollie return URL
		 *
		 * @return bool
		 */
		private function is_mollie_return_url() {
			return isset( $_GET['order_id'] ) && 
			       isset( $_GET['key'] ) && 
			       ( isset( $_GET['payment_id'] ) || isset( $_GET['order_id'] ) );
		}
		
		/**
		 * Check if query is in Mollie context
		 *
		 * @param WP_Query $query
		 * @return bool
		 */
		private function is_mollie_context( $query ) {
			// Check for Mollie webhook/return requests
			if ( $this->is_mollie_webhook_request() || $this->is_mollie_return_url() ) {
				return true;
			}
			
			// Check if searching for Mollie-specific meta
			$meta_query = $query->get( 'meta_query' );
			if ( is_array( $meta_query ) ) {
				foreach ( $meta_query as $meta ) {
					if ( isset( $meta['key'] ) && 
					     in_array( $meta['key'], array( '_mollie_order_id', '_mollie_payment_id', '_transaction_id' ) ) ) {
						return true;
					}
				}
			}
			
			// Check meta_key directly
			$meta_key = $query->get( 'meta_key' );
			if ( $meta_key && in_array( $meta_key, array( '_mollie_order_id', '_mollie_payment_id', '_transaction_id' ) ) ) {
				return true;
			}
			
			return false;
		}
		
		/**
		 * Add deposit type to query variables
		 *
		 * @param array $query_vars Query variables
		 * @return array Modified query variables
		 */
		private function add_deposit_type_to_query( $query_vars ) {
			if ( ! isset( $query_vars['type'] ) ) {
				$query_vars['type'] = array( 'shop_order', AWCDP_POST_TYPE );
			} elseif ( is_string( $query_vars['type'] ) ) {
				if ( $query_vars['type'] === 'shop_order' ) {
					$query_vars['type'] = array( 'shop_order', AWCDP_POST_TYPE );
				} elseif ( $query_vars['type'] !== AWCDP_POST_TYPE ) {
					$query_vars['type'] = array( $query_vars['type'], AWCDP_POST_TYPE );
				}
			} elseif ( is_array( $query_vars['type'] ) && ! in_array( AWCDP_POST_TYPE, $query_vars['type'] ) ) {
				$query_vars['type'][] = AWCDP_POST_TYPE;
			}
			
			return $query_vars;
		}
		
		/**
		 * Debug logging helper
		 *
		 * @param string $message Log message
		 * @param mixed $data Optional data to log
		 */
		private function log_debug( $message, $data = null ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				$log_message = '[Mollie Deposits Compatibility] ' . $message;
				if ( $data !== null ) {
					$log_message .= ' | Data: ' . print_r( $data, true );
				}
				error_log( $log_message );
			}
		}
	}
}

// Initialize the compatibility class
Comp_Mollie_Payments::get_instance();
