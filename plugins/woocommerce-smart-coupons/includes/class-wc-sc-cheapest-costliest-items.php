<?php
/**
 * Smart Coupons field for findings Cheapest & Costliest items
 *
 * @author      StoreApps
 * @since       9.4.0
 * @version     1.5.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Cheapest_Costliest_Items' ) ) {

	/**
	 * Class for handling Smart Coupons field for findings Cheapest & Costliest items
	 */
	class WC_SC_Cheapest_Costliest_Items {

		/**
		 * Variable to hold instance of WC_SC_Cheapest_Costliest_Items
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {

			add_action( 'wc_sc_coupon_options_general', array( $this, 'coupon_options' ), 10, 2 );
			add_action( 'woocommerce_coupon_options_save', array( $this, 'coupon_options_save' ), 10, 2 );

			add_action( 'woocommerce_api_create_coupon', array( $this, 'woocommerce_legacy_api_process_smart_coupon_meta' ), 10, 2 );
			add_action( 'woocommerce_api_edit_coupon', array( $this, 'woocommerce_legacy_api_process_smart_coupon_meta' ), 10, 2 );

			add_filter( 'woocommerce_coupon_get_items_to_apply', array( $this, 'find_valid_items' ), 10, 3 );
			add_filter( 'woocommerce_coupon_get_apply_quantity', array( $this, 'remove_products_from_validation' ), 10, 4 );

			add_action( 'woocommerce_cart_item_set_quantity', array( $this, 'reset_session' ), 99 );
			add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'reset_session' ), 99 );
			add_action( 'woocommerce_cart_item_removed', array( $this, 'reset_session' ), 99 );
			add_action( 'woocommerce_add_to_cart', array( $this, 'reset_session' ), 99 );
			add_action( 'admin_init', array( $this, 'handle_coupon_removal_in_order' ), 11 );

			add_filter( 'wc_smart_coupons_export_headers', array( $this, 'export_headers' ) );
			add_filter( 'smart_coupons_parser_postmeta_defaults', array( $this, 'postmeta_defaults' ) );
			add_filter( 'sc_generate_coupon_meta', array( $this, 'generate_coupon_meta' ), 10, 2 );
			add_filter( 'wc_sc_process_coupon_meta_value_for_import', array( $this, 'process_coupon_meta_value_for_import' ), 10, 2 );
			add_filter( 'is_protected_meta', array( $this, 'make_action_meta_protected' ), 10, 3 );
			add_action( 'wc_sc_new_coupon_generated', array( $this, 'copy_coupon_action_meta' ) );
			add_filter( 'wc_sc_export_coupon_meta', array( $this, 'export_coupon_meta' ), 10, 2 );

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
		 * Get single instance of WC_SC_Cheapest_Costliest_Items
		 *
		 * @return WC_SC_Cheapest_Costliest_Items Singleton object of WC_SC_Cheapest_Costliest_Items
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Function to display the coupon data meta box.
		 *
		 * @param  int       $coupon_id Coupon ID.
		 * @param  WC_Coupon $coupon Coupon Object.
		 */
		public function coupon_options( $coupon_id = 0, $coupon = null ) {
			global $post;

			if ( is_null( $coupon ) || ! is_a( $coupon, 'WC_Coupon' ) ) {
				if ( empty( $coupon_id ) ) {
					$coupon_id = ( ! empty( $post->ID ) ) ? $post->ID : 0;
				}
				$coupon = ( ! empty( $coupon_id ) ) ? new WC_Coupon( $coupon_id ) : null;
			}

			$cheapest_costliest_settings = $this->is_callable( $coupon, 'get_meta' ) ? $coupon->get_meta( 'wc_sc_cheapest_costliest_settings' ) : '';

			list( $cheapest_costliest_type, $cheapest_costliest_count ) = $this->process_cheapest_costliest_settings( $cheapest_costliest_settings );

			woocommerce_wp_select(
				array(
					'id'      => 'wc_sc_cheapest_costliest_type',
					'label'   => __( 'Apply discount on', 'woocommerce-smart-coupons' ),
					'options' => array(
						''               => __( 'All qualifying products', 'woocommerce-smart-coupons' ),
						'cheapest_cart'  => __( 'Cheapest qualifying product in cart', 'woocommerce-smart-coupons' ),
						'costliest_cart' => __( 'Highest priced qualifying product in cart', 'woocommerce-smart-coupons' ),
					),
					'value'   => $cheapest_costliest_type,
				)
			);

		}

		/**
		 * Function to process smart coupon meta
		 *
		 * @param int       $post_id The post id.
		 * @param WC_Coupon $coupon The coupon object.
		 */
		public function coupon_options_save( $post_id = 0, $coupon = null ) {

			if ( empty( $post_id ) ) {
				return;
			}

			$coupon = new WC_Coupon( $coupon );

			$post_cheapest_costliest_type  = ( isset( $_POST['wc_sc_cheapest_costliest_type'] ) ) ? wc_clean( wp_unslash( $_POST['wc_sc_cheapest_costliest_type'] ) ) : '';   // phpcs:ignore
			$post_cheapest_costliest_count = ( isset( $_POST['wc_sc_cheapest_costliest_count'] ) ) ? wc_clean( wp_unslash( $_POST['wc_sc_cheapest_costliest_count'] ) ) : 1;  // phpcs:ignore

			$post_cheapest_costliest_settings = $post_cheapest_costliest_count . '_' . $post_cheapest_costliest_type;

			if ( true === $this->is_callable( $coupon, 'update_meta_data' ) ) {
				if ( isset( $post_cheapest_costliest_settings ) ) { // phpcs:ignore
					$coupon->update_meta_data( 'wc_sc_cheapest_costliest_settings', $post_cheapest_costliest_settings );
				} else {
					$coupon->update_meta_data( 'wc_sc_cheapest_costliest_settings', '' );
				}
			} else {
				if ( isset( $post_cheapest_costliest_settings ) ) { // phpcs:ignore
					update_post_meta( $post_id, 'wc_sc_cheapest_costliest_settings', $post_cheapest_costliest_settings );
				} else {
					update_post_meta( $post_id, 'wc_sc_cheapest_costliest_settings', '' );
				}
			}

			if ( $this->is_callable( $coupon, 'save' ) ) {
				$coupon->save();
			}

		}

		/**
		 * Function to process smart coupon meta for legacy api
		 *
		 * @param int   $coupon_id The coupon id.
		 * @param array $data request body.
		 */
		public function woocommerce_legacy_api_process_smart_coupon_meta( $coupon_id = 0, $data = null ) {
			if ( empty( $coupon_id ) ) {
				return;
			}
			$coupon = new WC_Coupon( $coupon_id );
			if ( ! $coupon instanceof WC_Coupon ) {
				return;
			}
			if ( ! empty( $data ) && ! is_array( $data ) ) {
				return;
			}

			$post_cheapest_costliest_type  = ( isset( $data['wc_sc_cheapest_costliest_type'] ) ) ? wc_clean( wp_unslash( $data['wc_sc_cheapest_costliest_type'] ) ) : '';   // phpcs:ignore
			$post_cheapest_costliest_count = ( isset( $data['wc_sc_cheapest_costliest_count'] ) ) ? wc_clean( wp_unslash( $data['wc_sc_cheapest_costliest_count'] ) ) : 1;  // phpcs:ignore

			$post_cheapest_costliest_settings = $post_cheapest_costliest_count . '_' . $post_cheapest_costliest_type;

			if ( true === $this->is_callable( $coupon, 'update_meta_data' ) ) {
				if ( isset( $post_cheapest_costliest_settings ) ) { // phpcs:ignore
					$coupon->update_meta_data( 'wc_sc_cheapest_costliest_settings', $post_cheapest_costliest_settings );
				} else {
					$coupon->update_meta_data( 'wc_sc_cheapest_costliest_settings', '' );
				}

				if ( $this->is_callable( $coupon, 'save' ) ) {
					$coupon->save();
				}
			}
		}

		/**
		 * Find items on which cheapest or costliest rule will be applicable
		 *
		 * @param array        $items_to_apply Cart items to apply discount.
		 * @param WC_Coupon    $coupon The coupon object.
		 * @param WC_Discounts $discounts The discount object.
		 * @return array
		 */
		public function find_valid_items( $items_to_apply = array(), $coupon = null, $discounts = null ) {
			if ( empty( $items_to_apply ) || empty( $coupon ) ) {
				return $items_to_apply;
			}

			$items_to_keep           = array();
			$item_keys               = array();
			$coupon_code             = ( $this->is_callable( $coupon, 'get_code' ) ) ? $coupon->get_code() : '';
			$is_check_products_price = apply_filters(
				'wc_sc_is_check_products_price',
				true,
				array(
					'source'                  => $this,
					'items_to_apply_discount' => $items_to_apply,
					'coupon_obj'              => $coupon,
					'discounts_obj'           => $discounts,
				)
			);
			$item_key_to_price       = array();
			if ( true === $is_check_products_price ) {
				if ( ! empty( $items_to_apply ) && ! is_scalar( $items_to_apply ) ) {
					foreach ( $items_to_apply as $item ) {
						$key                       = $item->key ?? '';
						$price                     = $item->price ?? 0;
						$quantity                  = $item->quantity ?? 1;
						$item_key_to_price[ $key ] = $price / $quantity;
					}
				}
			} else {
				$item_key_to_price = wp_list_pluck( $items_to_apply, 'price', 'key' );
			}

			if ( is_callable( 'WC' ) && is_object( WC() ) && is_object( WC()->session ) && is_callable( array( WC()->session, 'get' ) ) ) {
				$items_to_keep = WC()->session->get( 'wc_sc_cheapest_costliest_items_session' );
			}

			if ( empty( $items_to_keep[ $coupon_code ] ) || ! is_array( $items_to_keep[ $coupon_code ] ) ) {
				$items_to_keep[ $coupon_code ] = array();
			}

			$cheapest_costliest_settings = ( $this->is_callable( $coupon, 'get_meta' ) ) ? $coupon->get_meta( 'wc_sc_cheapest_costliest_settings' ) : '';
			if ( empty( $cheapest_costliest_settings ) ) {
				$items_to_keep[ $coupon_code ] = array_merge( $items_to_keep[ $coupon_code ], array_keys( $item_key_to_price ) );
			}

			list( $cheapest_costliest_type, $cheapest_costliest_count ) = $this->process_cheapest_costliest_settings( $cheapest_costliest_settings );
			if ( empty( $cheapest_costliest_type ) ) {
				$items_to_keep[ $coupon_code ] = array_merge( $items_to_keep[ $coupon_code ], array_keys( $item_key_to_price ) );
			}

			switch ( $cheapest_costliest_type ) {
				case 'cheapest_cart':
					$item_keys = $this->get_items_from_cart( $item_key_to_price, 'cheapest', $cheapest_costliest_count );
					break;
				case 'costliest_cart':
					$item_keys = $this->get_items_from_cart( $item_key_to_price, 'costliest', $cheapest_costliest_count );
					break;
			}

			$items_to_keep[ $coupon_code ] = array_merge( $items_to_keep[ $coupon_code ], $item_keys );

			if ( is_callable( 'WC' ) && is_object( WC() ) && is_object( WC()->session ) && is_callable( array( WC()->session, 'set' ) ) ) {
				$items_to_keep[ $coupon_code ] = array_filter( array_unique( $items_to_keep[ $coupon_code ] ) );
				WC()->session->set( 'wc_sc_cheapest_costliest_items_session', $items_to_keep );
			}
			return $items_to_apply;
		}

		/**
		 * Remove products from validation which are not applicable as per cheapest or costliest rule
		 *
		 * @param integer      $product_quantity The product quantity.
		 * @param stdClass     $item Cart item object.
		 * @param WC_Coupon    $coupon The coupon object.
		 * @param WC_Discounts $discounts The discounts object.
		 * @return integer
		 */
		public function remove_products_from_validation( $product_quantity = 1, $item = null, $coupon = null, $discounts = null ) {
			if ( empty( $product_quantity ) || empty( $item ) || empty( $coupon ) ) {
				return $product_quantity;
			}

			$cheapest_costliest_settings = ( $this->is_callable( $coupon, 'get_meta' ) ) ? $coupon->get_meta( 'wc_sc_cheapest_costliest_settings' ) : '';
			if ( empty( $cheapest_costliest_settings ) ) {
				return $product_quantity;
			}
			list( $cheapest_costliest_type, $cheapest_costliest_count ) = $this->process_cheapest_costliest_settings( $cheapest_costliest_settings );
			if ( empty( $cheapest_costliest_type ) ) {
				return $product_quantity;
			}

			if ( is_callable( 'WC' ) && is_object( WC() ) && is_object( WC()->session ) && is_callable( array( WC()->session, 'get' ) ) ) {
				$items_to_keep = WC()->session->get( 'wc_sc_cheapest_costliest_items_session' );
				if ( ! empty( $items_to_keep[ $coupon->get_code() ] ) && ! empty( $item->key ) && ! in_array( $item->key, $items_to_keep[ $coupon->get_code() ], true ) ) {
					$product_quantity = 0;
				}
			}

			return $product_quantity;
		}

		/**
		 * Process cheapest costliest settings
		 *
		 * @param string $cheapest_or_costliest The cheapest-costliest settings.
		 * @return array
		 */
		public function process_cheapest_costliest_settings( $cheapest_or_costliest = '' ) {
			if ( empty( $cheapest_or_costliest ) ) {
				return array( '', 1 );
			}
			$cheapest_costliest_exploded = explode( '_', $cheapest_or_costliest );
			if ( count( $cheapest_costliest_exploded ) !== 3 ) {
				return array( '', 1 );
			}
			$cheapest_costliest_count = ( ! empty( $cheapest_costliest_exploded[0] ) ) ? absint( $cheapest_costliest_exploded[0] ) : 1;
			unset( $cheapest_costliest_exploded[0] );
			$cheapest_costliest_type = implode( '_', $cheapest_costliest_exploded );
			return array(
				$cheapest_costliest_type,
				$cheapest_costliest_count,
			);
		}

		/**
		 * Get cart items based on type cheapest or costliest
		 *
		 * @param array   $item_key_to_price Cart items.
		 * @param string  $type The type.
		 * @param integer $count Number of items required.
		 * @return array
		 */
		public function get_items_from_cart( $item_key_to_price = array(), $type = 'cheapest', $count = 1 ) {
			switch ( $type ) {
				case 'costliest':
					arsort( $item_key_to_price );
					break;
				case 'cheapest':
				default:
					asort( $item_key_to_price );
					break;
			}
			$item_key_to_price = array_slice( $item_key_to_price, 0, $count, true );
			return array_keys( $item_key_to_price );
		}

		/**
		 * Function to refresh session on Cart Update.
		 */
		public function reset_session() {
			WC()->session->__unset( 'wc_sc_cheapest_costliest_items_session' );
		}

		/**
		 * Add meta in export headers
		 *
		 * @param  array $headers Existing headers.
		 * @return array
		 */
		public function export_headers( $headers = array() ) {

			$headers['wc_sc_cheapest_costliest_settings'] = __( 'Apply discount on', 'woocommerce-smart-coupons' );

			return $headers;
		}

		/**
		 * Post meta defaults for coupon meta
		 *
		 * @param  array $defaults Existing postmeta defaults.
		 * @return array $defaults Modified postmeta defaults
		 */
		public function postmeta_defaults( $defaults = array() ) {

			$defaults['wc_sc_cheapest_costliest_settings'] = '';

			return $defaults;
		}

		/**
		 * Add coupon's meta with value in coupon meta
		 *
		 * @param  array $data The row data.
		 * @param  array $post The POST values.
		 * @return array $data Modified row data
		 */
		public function generate_coupon_meta( $data = array(), $post = array() ) {

			$post_cheapest_costliest_count = ( isset( $post['wc_sc_cheapest_costliest_count'] ) ) ? wc_clean( wp_unslash( $post['wc_sc_cheapest_costliest_count'] ) ) : 1;  // phpcs:ignore
			$post_cheapest_costliest_type  = ( isset( $post['wc_sc_cheapest_costliest_type'] ) ) ? wc_clean( wp_unslash( $post['wc_sc_cheapest_costliest_type'] ) ) : '';   // phpcs:ignore

			$data['wc_sc_cheapest_costliest_settings'] = $post_cheapest_costliest_count . '_' . $post_cheapest_costliest_type;

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

			if ( ! empty( $args['meta_key'] ) && 'wc_sc_cheapest_costliest_settings' === $args['meta_key'] ) {
				$meta_value = ( isset( $args['postmeta']['wc_sc_cheapest_costliest_settings'] ) ) ? wc_clean( wp_unslash( $args['postmeta']['wc_sc_cheapest_costliest_settings'] ) ) : '';  // phpcs:ignore
			}

			return $meta_value;
		}

		/**
		 * Make meta data of coupon meta protected
		 *
		 * @param bool   $protected Is protected.
		 * @param string $meta_key The meta key.
		 * @param string $meta_type The meta type.
		 * @return bool $protected
		 */
		public function make_action_meta_protected( $protected = false, $meta_key = '', $meta_type = '' ) {

			if ( 'wc_sc_cheapest_costliest_settings' === $meta_key ) {
				return true;
			}

			return $protected;
		}

		/**
		 * Function to copy coupon meta in newly generated coupon
		 *
		 * @param  array $args The arguments.
		 */
		public function copy_coupon_action_meta( $args = array() ) {

			$new_coupon_id = ( ! empty( $args['new_coupon_id'] ) ) ? absint( $args['new_coupon_id'] ) : 0;
			$coupon        = ( ! empty( $args['ref_coupon'] ) ) ? $args['ref_coupon'] : false;

			if ( empty( $new_coupon_id ) || empty( $coupon ) ) {
				return;
			}

			$cheapest_costliest_settings = '';
			if ( $this->is_wc_gte_30() ) {
				$cheapest_costliest_settings = $coupon->get_meta( 'wc_sc_cheapest_costliest_settings' );
			} else {
				$old_coupon_id               = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
				$cheapest_costliest_settings = get_post_meta( $old_coupon_id, 'wc_sc_cheapest_costliest_settings', true );
			}
			$this->update_post_meta( $new_coupon_id, 'wc_sc_cheapest_costliest_settings', $cheapest_costliest_settings );

		}

		/**
		 * Function to handle coupon meta data during export of existing coupons
		 *
		 * @param  mixed $meta_value The meta value.
		 * @param  array $args       Additional arguments.
		 * @return string Processed meta value
		 */
		public function export_coupon_meta( $meta_value = '', $args = array() ) {

			$index       = ( ! empty( $args['index'] ) ) ? $args['index'] : -1;
			$meta_keys   = ( ! empty( $args['meta_keys'] ) ) ? $args['meta_keys'] : array();
			$meta_values = ( ! empty( $args['meta_values'] ) ) ? $args['meta_values'] : array();

			if ( $index >= 0 && ! empty( $meta_keys[ $index ] ) && 'wc_sc_cheapest_costliest_settings' === $meta_keys[ $index ] && ! empty( $meta_values[ $index ] ) ) {
				$meta_value = $meta_values[ $index ];
			}

			return $meta_value;

		}

		/**
		 * Detects coupon removal from an order in the admin panel and updates the session accordingly.
		 */
		public function handle_coupon_removal_in_order() {
			// Sanitize and validate inputs.
			$action   = ( ! empty( $_POST['action'] ) ) ? wc_clean( wp_unslash( $_POST['action'] ) ) : ''; // phpcs:ignore
			$coupon_code = ( ! empty( $_POST['coupon'] ) ) ? wc_clean( wp_unslash( $_POST['coupon'] ) ) : ''; // phpcs:ignore

			if ( 'woocommerce_remove_order_coupon' !== $action || empty( $coupon_code ) ) {
				return;
			}

			// Ensure session is initialized (usually unavailable in admin).
			if ( ! WC()->session ) {
				WC()->initialize_session();
			}

			// Get session data.
			$items_in_session = WC()->session->get( 'wc_sc_cheapest_costliest_items_session', array() );

			// If coupon is in session, remove it.
			if ( isset( $items_in_session[ $coupon_code ] ) ) {
				unset( $items_in_session[ $coupon_code ] );

				// Update the session.
				WC()->session->set( 'wc_sc_cheapest_costliest_items_session', $items_in_session );
			}
		}

	}

}

WC_SC_Cheapest_Costliest_Items::get_instance();
