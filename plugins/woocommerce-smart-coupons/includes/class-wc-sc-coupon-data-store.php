<?php
/**
 * Class to generate available coupon data in DB
 *
 * @package     woocommerce-smart-coupons/includes/
 * @since       9.8.0
 * @version     1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



if ( ! class_exists( 'WC_SC_Coupon_Data_Store' ) ) {

	/**
	 * WC_SC_Coupon_Data_Store class.
	 */
	class WC_SC_Coupon_Data_Store {

		/**
		 * Variable to hold instance of WC_SC_Coupon_Data_Store
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {
			add_action( 'woocommerce_after_data_object_save', array( $this, 'store_save_coupons_data' ), 99, 2 );
			add_action( 'woocommerce_rest_insert_shop_coupon_object', array( $this, 'store_save_coupons_data' ), 99, 2 );

			add_action( 'deleted_post', array( $this, 'remove_coupon_from_custom_table' ) );
			// Status transitions.
			add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
			add_action( 'trash_to_publish', array( $this, 'restore_coupon_smart_coupons_db' ) );
		}


		/**
		 * Get single instance of WC_SC_Coupon_Data_Store
		 *
		 * @return WC_SC_Coupon_Data_Store Singleton object of WC_SC_Coupon_Data_Store
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
		 * Save auto apply coupon in meta
		 *
		 * @param  WC_Coupon     $coupon    The coupon object.
		 * @param WC_Data_Store $data_store Data store of coupon.
		 */
		public function store_save_coupons_data( $coupon = null, $data_store = null ) {
			if ( ! in_array( $this->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) {
				return;
			}
			if ( ! $coupon instanceof WC_Coupon ) {
				return;
			}
			$coupon_id = $coupon->get_id();
			if ( 'shop_coupon' !== $this->get_post_type( $coupon_id ) ) {
				return;
			}
			if ( ! $this->is_callable( $coupon, 'get_meta' ) ) {
				return;
			}
			if ( 'publish' !== get_post_status( $coupon_id ) ) {
				return;
			}
			$this->maybe_update_coupons_data( $coupon_id );
		}


		/**
		 * When a post status changes.
		 *
		 * @param string  $new_status New status.
		 * @param string  $old_status Old status.
		 * @param WP_Post $post       Post data.
		 */
		public function transition_post_status( $new_status = '', $old_status = '', $post = null ) {
			if ( ! $post instanceof WP_Post || ! isset( $post->post_type ) || ( isset( $post->post_type ) && ! in_array( $post->post_type, array( 'shop_coupon' ), true ) ) ) {
				return;
			}
			if ( ( 'publish' !== $new_status && 'publish' === $old_status ) && in_array( $post->post_type, array( 'shop_coupon' ), true ) ) {
				$this->remove_coupon_from_custom_table( $post->ID );
			}
		}

		/**
		 * When a post status changes from .
		 *
		 * @param WP_Post $post Post data.
		 */
		public function restore_coupon_smart_coupons_db( $post = null ) {

			if ( ! $post instanceof WP_Post || ! isset( $post->ID ) || ! isset( $post->post_type ) || ( isset( $post->post_type ) && ! in_array( $post->post_type, array( 'shop_coupon' ), true ) ) ) {
				return;
			}

			$this->maybe_update_coupons_data( $post->ID );
		}

		/**
		 * Store auto apply coupon id of auto apply coupons
		 *
		 * @param int $coupon_id The coupon id.
		 */
		public function remove_coupon_from_custom_table( $coupon_id = null ) {
			if ( ! in_array( $this->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) {
				return;
			}
			if ( empty( $coupon_id ) ) {
				return;
			}
			if ( 'shop_coupon' !== $this->get_post_type( $coupon_id ) ) {
				return;
			}

			$coupon = new WC_Coupon( $coupon_id );

			if ( is_a( $coupon, 'WC_Coupon' ) ) {
				// delete coupon from custom table.
				global $wpdb;
				$wpdb->delete( $wpdb->prefix . 'wc_smart_coupons', array( 'id' => $coupon_id ) ); // phpcs:ignore
				return;
			}
		}

		/**
		 * Store auto apply coupon id to usermeta and option table.
		 *
		 * @param int $coupon_id The coupon id.
		 */
		public function maybe_update_coupons_data( $coupon_id = null ) {
			if ( ! in_array( $this->get_db_status_for( '9.8.0' ), array( 'completed', 'done' ), true ) ) {
				return;
			}
			try {
				global $wpdb;
				$replace_query = $wpdb->prepare(
					"REPLACE INTO {$wpdb->prefix}wc_smart_coupons (
						id, discount_type, coupon_amount, minimum_amount, maximum_amount, wc_sc_original_amount, date_expires, usage_limit,
						usage_limit_per_user, limit_usage_to_x_items, usage_count, sc_coupon_validity, validity_suffix, coupon_title_prefix,
						coupon_title_suffix, wc_sc_max_discount, wc_sc_cheapest_costliest_settings, sa_cbl_locations_lookup_in, _used_by,
						individual_use, free_shipping, exclude_sale_items, sc_restrict_to_new_user, auto_generate_coupon, apply_before_tax,
						sc_is_visible_storewide, sc_disable_email_restriction, is_pick_price_of_product, wc_sc_auto_apply_coupon, wc_email_message,
						customer_email, product_ids, exclude_product_ids, product_categories, exclude_product_categories, wc_sc_add_product_details,
						wc_sc_payment_method_ids, wc_sc_shipping_method_ids, wc_sc_user_role_ids, wc_sc_exclude_user_role_ids, wc_sc_product_attribute_ids,
						wc_sc_exclude_product_attribute_ids, wc_sc_taxonomy_restrictions, wc_sc_excluded_customer_email, wc_sc_product_quantity_restrictions,
						wc_coupon_message, sa_cbl_billing_locations, sa_cbl_shipping_locations, generated_from_order_id
					) 
					SELECT
						pm.post_id, pm.discount_type, pm.coupon_amount, pm.minimum_amount, pm.maximum_amount, pm.wc_sc_original_amount,
						CASE WHEN (pm.date_expires + COALESCE(pm.wc_sc_expiry_time, 0)) > 0 THEN FROM_UNIXTIME(pm.date_expires + COALESCE(pm.wc_sc_expiry_time, 0)) ELSE NULL END,
						pm.usage_limit, pm.usage_limit_per_user, pm.limit_usage_to_x_items, pm.usage_count, pm.sc_coupon_validity, pm.validity_suffix,
						pm.coupon_title_prefix, pm.coupon_title_suffix, pm.wc_sc_max_discount, pm.wc_sc_cheapest_costliest_settings, pm.sa_cbl_locations_lookup_in,
						pm._used_by, pm.individual_use, pm.free_shipping, pm.exclude_sale_items, pm.sc_restrict_to_new_user, pm.auto_generate_coupon,
						pm.apply_before_tax, pm.sc_is_visible_storewide, pm.sc_disable_email_restriction, pm.is_pick_price_of_product,
						pm.wc_sc_auto_apply_coupon, pm.wc_email_message, pm.customer_email, pm.product_ids, pm.exclude_product_ids, pm.product_categories,
						pm.exclude_product_categories, pm.wc_sc_add_product_details, pm.wc_sc_payment_method_ids, pm.wc_sc_shipping_method_ids,
						pm.wc_sc_user_role_ids, pm.wc_sc_exclude_user_role_ids, pm.wc_sc_product_attribute_ids, pm.wc_sc_exclude_product_attribute_ids,
						pm.wc_sc_taxonomy_restrictions, pm.wc_sc_excluded_customer_email, pm.wc_sc_product_quantity_restrictions, pm.wc_coupon_message,
						pm.sa_cbl_billing_locations, pm.sa_cbl_shipping_locations, pm.generated_from_order_id
					FROM (
						
						SELECT post_id,
							MAX(CASE WHEN meta_key = 'discount_type' THEN meta_value END) AS discount_type,
							MAX(CASE WHEN meta_key = 'coupon_amount' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS DECIMAL(26,8)) ELSE NULL END ELSE NULL END) AS coupon_amount,
							MAX(CASE WHEN meta_key = 'minimum_amount' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS DECIMAL(26,8)) ELSE NULL END END) AS minimum_amount,
							MAX(CASE WHEN meta_key = 'maximum_amount' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS DECIMAL(26,8)) ELSE NULL END END) AS maximum_amount,
							MAX(CASE WHEN meta_key = 'wc_sc_max_discount' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_max_discount,
							MAX(CASE WHEN meta_key = 'wc_sc_original_amount' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS DECIMAL(26,8)) ELSE NULL END END) AS wc_sc_original_amount,
							MAX(CASE WHEN meta_key = 'date_expires' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS UNSIGNED) ELSE NULL END END) AS date_expires,
							MAX(CASE WHEN meta_key = 'usage_limit' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS UNSIGNED) ELSE NULL END END) AS usage_limit,
							MAX(CASE WHEN meta_key = 'usage_limit_per_user' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS UNSIGNED) ELSE NULL END END) AS usage_limit_per_user,
							MAX(CASE WHEN meta_key = 'limit_usage_to_x_items' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS UNSIGNED) ELSE NULL END END) AS limit_usage_to_x_items,
							MAX(CASE WHEN meta_key = 'usage_count' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS UNSIGNED) ELSE NULL END END) AS usage_count,
							MAX(CASE WHEN meta_key = 'sc_coupon_validity' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS sc_coupon_validity,
							MAX(CASE WHEN meta_key = 'validity_suffix' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS validity_suffix,
							MAX(CASE WHEN meta_key = 'coupon_title_prefix' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS coupon_title_prefix,
							MAX(CASE WHEN meta_key = 'coupon_title_suffix' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS coupon_title_suffix,
							MAX(CASE WHEN meta_key = 'wc_sc_cheapest_costliest_settings' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_cheapest_costliest_settings,
							MAX(CASE WHEN meta_key = 'sa_cbl_locations_lookup_in' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS sa_cbl_locations_lookup_in,
							MAX(CASE WHEN meta_key = '_used_by' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS _used_by,
							COALESCE(MAX(CASE WHEN meta_key = 'individual_use' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS individual_use,
							COALESCE(MAX(CASE WHEN meta_key = 'free_shipping' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS free_shipping,
							COALESCE(MAX(CASE WHEN meta_key = 'exclude_sale_items' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS exclude_sale_items,
							COALESCE(MAX(CASE WHEN meta_key = 'sc_restrict_to_new_user' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS sc_restrict_to_new_user,
							COALESCE(MAX(CASE WHEN meta_key = 'auto_generate_coupon' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS auto_generate_coupon,
							COALESCE(MAX(CASE WHEN meta_key = 'apply_before_tax' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS apply_before_tax,
							COALESCE(MAX(CASE WHEN meta_key = 'sc_is_visible_storewide' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS sc_is_visible_storewide,
							COALESCE(MAX(CASE WHEN meta_key = 'sc_disable_email_restriction' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS sc_disable_email_restriction,
							COALESCE(MAX(CASE WHEN meta_key = 'is_pick_price_of_product' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS is_pick_price_of_product,
							COALESCE(MAX(CASE WHEN meta_key = 'wc_sc_auto_apply_coupon' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS wc_sc_auto_apply_coupon,
							COALESCE(MAX(CASE WHEN meta_key = 'wc_email_message' THEN (CASE WHEN meta_value = 'yes' THEN 1 ELSE 0 END) END), 0) AS wc_email_message,
							MAX(CASE WHEN meta_key = 'customer_email' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS customer_email,
							MAX(CASE WHEN meta_key = 'product_ids' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS product_ids,
							MAX(CASE WHEN meta_key = 'exclude_product_ids' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS exclude_product_ids,
							MAX(CASE WHEN meta_key = 'product_categories' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS product_categories,
							MAX(CASE WHEN meta_key = 'exclude_product_categories' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS exclude_product_categories,
							MAX(CASE WHEN meta_key = 'wc_sc_add_product_details' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_add_product_details,
							MAX(CASE WHEN meta_key = 'wc_sc_payment_method_ids' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_payment_method_ids,
							MAX(CASE WHEN meta_key = 'wc_sc_shipping_method_ids' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_shipping_method_ids,
							MAX(CASE WHEN meta_key = 'wc_sc_user_role_ids' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_user_role_ids,
							MAX(CASE WHEN meta_key = 'wc_sc_exclude_user_role_ids' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_exclude_user_role_ids,
							MAX(CASE WHEN meta_key = 'wc_sc_product_attribute_ids' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_product_attribute_ids,
							MAX(CASE WHEN meta_key = 'wc_sc_exclude_product_attribute_ids' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_exclude_product_attribute_ids,
							MAX(CASE WHEN meta_key = 'wc_sc_taxonomy_restrictions' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_taxonomy_restrictions,
							MAX(CASE WHEN meta_key = 'wc_sc_excluded_customer_email' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_excluded_customer_email,
							MAX(CASE WHEN meta_key = 'wc_sc_product_quantity_restrictions' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_sc_product_quantity_restrictions,
							MAX(CASE WHEN meta_key = 'wc_coupon_message' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS wc_coupon_message,
							MAX(CASE WHEN meta_key = 'sa_cbl_billing_locations' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS sa_cbl_billing_locations,
							MAX(CASE WHEN meta_key = 'sa_cbl_shipping_locations' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS sa_cbl_shipping_locations,
							MAX(CASE WHEN meta_key = 'generated_from_order_id' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN meta_value ELSE NULL END END) AS generated_from_order_id,
							MAX(CASE WHEN meta_key = 'wc_sc_expiry_time' THEN CASE WHEN meta_value <> '' AND meta_value IS NOT NULL THEN CAST(meta_value AS UNSIGNED) ELSE NULL END END) AS wc_sc_expiry_time
						FROM {$wpdb->postmeta}
						WHERE post_id = %d
						GROUP BY post_id
					) pm",
					absint( $coupon_id )
				);

				$wpdb->query($replace_query); // phpcs:ignore
			} catch ( Exception $e ) {
				/* translators: 1. Error Message */
				$this->log( 'error', sprintf( __( 'Sync of coupon data for coupon ID: %1$d failed. Reason: %2$s', 'woocommerce-smart-coupons' ), $coupon_id, $e->getMessage() ) );
			}

		}



	}
}

WC_SC_Coupon_Data_Store::get_instance();
