<?php
/**
 * Class to generate available coupon data in DB
 *
 * @package     woocommerce-smart-coupons/includes/
 * @since       9.8.0
 * @version     1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_SC_Background_Process', false ) ) {
	if ( file_exists( trailingslashit( WP_PLUGIN_DIR . '/' . WC_SC_PLUGIN_DIRNAME ) . 'includes/abstracts/class-wc-sc-background-process.php' ) ) {
		include_once trailingslashit( WP_PLUGIN_DIR . '/' . WC_SC_PLUGIN_DIRNAME ) . 'includes/abstracts/class-wc-sc-background-process.php';
	}
}

if ( ! class_exists( 'WC_SC_Coupon_Table' ) && class_exists( 'WC_SC_Background_Process' ) ) {

	/**
	 * WC_SC_Background_Process class.
	 */
	class WC_SC_Coupon_Table extends WC_SC_Background_Process {

		/**
		 * Variable to hold instance of this class.
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get the single instance of this class
		 *
		 * @return WC_SC_Coupon_Table
		 */
		public static function get_instance() {
			// Check if the instance already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {

			// Set the batch limit.
			$this->batch_limit = 1;

			// Set the action name.
			$this->action = 'wc_sc_coupon_table';

			// Initialize the parent class to execute background process.
			parent::__construct();

			add_action( $this->action . '_process_completed', array( $this, 'finalize' ) );
		}

		/**
		 * Execute the task for each batch.
		 *
		 * @param string $current_item The current status of the table creation.
		 *
		 * @throws Exception If any problem during the process.
		 */
		public function task( $current_item = '' ) {
			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			switch ( $current_item ) {
				case 'create':
					$collate = '';

					if ( $wpdb->has_cap( 'collation' ) ) {
						if ( ! empty( $wpdb->charset ) ) {
							$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
						}
						if ( ! empty( $wpdb->collate ) ) {
							$collate .= " COLLATE $wpdb->collate";
						}
					}

					$create_table_query = "
								CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_smart_coupons (
									id BIGINT UNSIGNED NOT NULL,
									discount_type VARCHAR(50) DEFAULT NULL,
									coupon_amount DECIMAL(26,8) DEFAULT NULL,
									minimum_amount DECIMAL(26,8) DEFAULT NULL,
									maximum_amount DECIMAL(26,8) DEFAULT NULL,
									wc_sc_max_discount DECIMAL(26,8) DEFAULT NULL,
									wc_sc_original_amount DECIMAL(26,8) DEFAULT NULL,
									date_expires TIMESTAMP NULL,
									usage_limit BIGINT DEFAULT NULL,
									usage_limit_per_user BIGINT DEFAULT NULL,
									limit_usage_to_x_items BIGINT DEFAULT NULL,
									usage_count BIGINT DEFAULT NULL,
									sc_coupon_validity BIGINT(5) DEFAULT NULL,
									validity_suffix VARCHAR(10) DEFAULT NULL,
									coupon_title_prefix VARCHAR(20) DEFAULT NULL,
									coupon_title_suffix VARCHAR(20) DEFAULT NULL,
									wc_sc_cheapest_costliest_settings VARCHAR(100) DEFAULT NULL,
									sa_cbl_locations_lookup_in VARCHAR(255) DEFAULT NULL,
									_used_by VARCHAR(320) DEFAULT NULL,
									individual_use TINYINT(1) DEFAULT NULL,
									free_shipping TINYINT(1) DEFAULT NULL,
									exclude_sale_items TINYINT(1) DEFAULT NULL,
									sc_restrict_to_new_user TINYINT(1) DEFAULT NULL,
									auto_generate_coupon TINYINT(1) DEFAULT NULL,
									apply_before_tax TINYINT(1) DEFAULT NULL,
									sc_is_visible_storewide TINYINT(1) DEFAULT NULL,
									sc_disable_email_restriction TINYINT(1) DEFAULT NULL,
									is_pick_price_of_product TINYINT(1) DEFAULT NULL,
									wc_sc_auto_apply_coupon TINYINT(1) DEFAULT NULL,
									wc_email_message TINYINT(1) DEFAULT NULL,
									customer_email LONGTEXT DEFAULT NULL,
									product_ids LONGTEXT DEFAULT NULL,
									exclude_product_ids LONGTEXT DEFAULT NULL,
									product_categories LONGTEXT DEFAULT NULL,
									exclude_product_categories LONGTEXT DEFAULT NULL,
									wc_sc_add_product_details LONGTEXT DEFAULT NULL,
									wc_sc_payment_method_ids LONGTEXT DEFAULT NULL,
									wc_sc_shipping_method_ids LONGTEXT DEFAULT NULL,
									wc_sc_user_role_ids LONGTEXT DEFAULT NULL,
									wc_sc_exclude_user_role_ids LONGTEXT DEFAULT NULL,
									wc_sc_product_attribute_ids LONGTEXT DEFAULT NULL,
									wc_sc_exclude_product_attribute_ids LONGTEXT DEFAULT NULL,
									wc_sc_taxonomy_restrictions LONGTEXT DEFAULT NULL,
									wc_sc_excluded_customer_email LONGTEXT DEFAULT NULL,
									wc_sc_product_quantity_restrictions LONGTEXT DEFAULT NULL,
									wc_coupon_message LONGTEXT DEFAULT NULL,
									sa_cbl_billing_locations LONGTEXT DEFAULT NULL,
									sa_cbl_shipping_locations LONGTEXT DEFAULT NULL,
									generated_from_order_id BIGINT DEFAULT NULL,
									PRIMARY KEY (id),
									KEY discount_type (discount_type),
									KEY date_expires (date_expires),
									KEY sc_restrict_to_new_user (sc_restrict_to_new_user),
									KEY auto_generate_coupon (auto_generate_coupon),
									KEY sc_is_visible_storewide (sc_is_visible_storewide),
									KEY is_pick_price_of_product (is_pick_price_of_product),
									KEY wc_sc_auto_apply_coupon (wc_sc_auto_apply_coupon),
									KEY generated_from_order_id (generated_from_order_id),
									KEY wc_sc_user_role_ids (wc_sc_user_role_ids(200)),
									KEY purchase_credit (auto_generate_coupon,is_pick_price_of_product),
									KEY global_auto_apply (sc_is_visible_storewide,wc_sc_auto_apply_coupon),
									KEY smart_coupons_auto_apply_new_user (discount_type,sc_restrict_to_new_user,wc_sc_auto_apply_coupon),
									KEY smart_coupons_global_auto_apply (discount_type,auto_generate_coupon,sc_is_visible_storewide,is_pick_price_of_product,wc_sc_auto_apply_coupon)
								) $collate";

					@ini_set( 'max_execution_time', '300' ); // phpcs:ignore

					$table_exists = false;
					if ( function_exists( 'maybe_create_table' ) && is_callable( 'maybe_create_table' ) ) {
						$table_exists = maybe_create_table( $wpdb->prefix . 'wc_smart_coupons', $create_table_query );
					} elseif ( function_exists( 'dbDelta' ) && is_callable( 'dbDelta' ) ) {
						dbDelta( $create_table_query );
					}

					$this->remove_status_from_remaining_items( 'create' );
					break;

				case 'insert':
				case 'update':
					// phpcs:disable
					$wpdb->query(
						$wpdb->prepare(
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
								WHERE post_id IN (SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s)
									AND post_id NOT IN (SELECT id FROM {$wpdb->prefix}wc_smart_coupons)
								GROUP BY post_id
								LIMIT %d
							) pm",
							'shop_coupon',
							'publish',
							apply_filters( 'wc_sc_data_batch_size_for_custom_table', 10000, array( 'source' => $this ) )
						)
					);

					$remaining_coupon_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT DISTINCT ID
								FROM {$wpdb->posts}
								WHERE post_type = %s
									AND post_status = %s
									AND ID NOT IN (SELECT id FROM {$wpdb->prefix}wc_smart_coupons)
								ORDER BY ID DESC
								LIMIT 1",
							'shop_coupon',
							'publish'
						)
					);
					// phpcs:enable

					$remaining_coupon_id = absint( $remaining_coupon_id );

					if ( empty( $remaining_coupon_id ) ) {
						$this->remove_status_from_remaining_items( array( 'insert', 'update' ) );
					}

					break;

			}

			// Check process health before continuing.
			if ( ! $this->health_status() ) {
				throw new Exception(
					sprintf(
						/* translators: 1: The task class name */
						_x( 'Batch stopped due to health status in task: %s', 'Logger for stopped batch process due to health status', 'woocommerce-smart-coupons' ),
						__CLASS__
					)
				);
			}
		}

		/**
		 * Get the remaining items for doing the action.
		 *
		 * @return string The function that needs to be processed.
		 */
		public function get_remaining_items() {
			global $wpdb;

			$all_statuses = array( 'create', 'insert', 'update' );
			$rows         = get_option( 'wc_sc_table_wc_smart_coupons_creation_status' );
			foreach ( $all_statuses as $status ) {
				if ( in_array( $status, $rows, true ) ) {
					return $status;
				}
			}

			return '';
		}

		/**
		 * Remove status from remaining items
		 *
		 * @param mixed $statuses Status to remove.
		 */
		public function remove_status_from_remaining_items( $statuses = null ) {
			if ( empty( $statuses ) ) {
				return;
			}
			$rows = get_option( 'wc_sc_table_wc_smart_coupons_creation_status' );
			if ( is_array( $statuses ) ) {
				$rows = array_diff( $rows, $statuses );
			} else {
				$rows = array_diff( $rows, array( $statuses ) );
			}
			update_option( 'wc_sc_table_wc_smart_coupons_creation_status', $rows, true );
		}

		/**
		 * Update option once migration complete.
		 */
		public function finalize() {
			delete_option( 'wc_sc_table_wc_smart_coupons_creation_status' );
		}

	}
}

WC_SC_Coupon_Table::get_instance();
