<?php

/**
 * Admin Ajax.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'EFW_Admin_Ajax' ) ) {

	/**
	 * EFW_Admin_Ajax Class.
	 */
	class EFW_Admin_Ajax {

		/**
		 * EFW_Admin_Ajax Class initialization.
		 */
		public static function init() {

			$actions = array(
				'product_search'          => false,
				'customers_search'        => false,
				'fee_data'                => false,
				'add_rule_for_simple'     => false,
				'add_rule_for_variable'   => false,
				'delete_rule'             => false,
				'progress_bar_action'     => false,
				'fee_desc_popup'          => true,
				'fee_desc_rule_popup'     => true,
				'fee_gateway_desc_popup'  => true,
				'fee_shipping_desc_popup' => true,
				'fee_order_desc_popup'    => true,
				'fee_combined_desc_popup'    => true,
				'export_plugin_settings'  => false,
			);

			foreach ( $actions as $action => $nopriv ) {
				add_action( 'wp_ajax_efw_' . $action, array( __CLASS__, $action ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_efw_' . $action, array( __CLASS__, $action ) );
				}
			}
		}

		/**
		 * Product search.
		 */
		public static function product_search() {
			check_ajax_referer( 'efw-search-nonce', 'efw_security' );

			try {
				$term = isset( $_GET['term'] ) ? (string) wc_clean( wp_unslash( $_GET['term'] ) ) : '';

				if ( empty( $term ) ) {
					throw new Exception( esc_html__( 'No Product(s) found', 'extra-fees-for-woocommerce' ) );
				}

				$data_store = WC_Data_Store::load( 'product' );
				$ids        = $data_store->search_products( $term, '', true, false, 30 );

				$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_readable' );
				$products        = array();

				$exclude_global_variable = isset( $_GET[ 'exclude_global_variable' ] ) ? wc_clean( wp_unslash( $_GET[ 'exclude_global_variable' ] ) ) : 'no' ; // @codingStandardsIgnoreLine.
				foreach ( $product_objects as $product_object ) {
					if ( 'yes' == $exclude_global_variable && $product_object->is_type( 'variable' ) ) {
						continue;
					}

					$products[ $product_object->get_id() ] = rawurldecode( $product_object->get_formatted_name() );
				}
				wp_send_json( $products );
			} catch ( Exception $ex ) {
				wp_die();
			}
		}

		/**
		 * Customer search.
		 */
		public static function customers_search() {
			check_ajax_referer( 'efw-search-nonce', 'efw_security' );

			try {
				$term = isset( $_GET['term'] ) ? (string) wc_clean( wp_unslash( $_GET['term'] ) ) : '';

				if ( empty( $term ) ) {
					throw new Exception( esc_html__( 'No Customer(s) found', 'extra-fees-for-woocommerce' ) );
				}

				$exclude = isset( $_GET['exclude'] ) ? (string) wc_clean( wp_unslash( $_GET['exclude'] ) ) : '';
				$exclude = ! empty( $exclude ) ? array_map( 'intval', explode( ',', $exclude ) ) : array();

				$found_customers = array();

				$customers_query = new WP_User_Query(
					array(
						'fields'         => 'all',
						'orderby'        => 'display_name',
						'search'         => '*' . $term . '*',
						'search_columns' => array( 'ID', 'user_login', 'user_email', 'user_nicename' ),
					)
				);

				$customers = $customers_query->get_results();

				if ( efw_check_is_array( $customers ) ) {
					foreach ( $customers as $customer ) {
						if ( ! in_array( $customer->ID, $exclude ) ) {
							$found_customers[ $customer->ID ] = $customer->display_name . ' (#' . $customer->ID . ' &ndash; ' . sanitize_email( $customer->user_email ) . ')';
						}
					}
				}

				wp_send_json( $found_customers );
			} catch ( Exception $ex ) {
				wp_die();
			}
		}

		/**
		 * Fee Data.
		 */
		public static function fee_data() {
			check_ajax_referer( 'efw-update-fee-data-nonce', 'efw_security' );

			try {
				$duration_type = isset( $_REQUEST['duration_type'] ) ? wc_clean( wp_unslash( $_REQUEST['duration_type'] ) ) : '';

				if ( 'custom_date' === $duration_type ) {
					$from_date = isset( $_REQUEST['from_date'] ) ? wc_clean( wp_unslash( $_REQUEST['from_date'] ) ) : '';
					$to_date   = isset( $_REQUEST['to_date'] ) ? wc_clean( wp_unslash( $_REQUEST['to_date'] ) ) : '';

					if ( ! $from_date ) {
						throw new Exception( esc_html__( 'From date cannot be empty', 'extra-fees-for-woocommerce' ) );
					}

					if ( ! $to_date ) {
						throw new Exception( esc_html__( 'To date cannot be empty', 'extra-fees-for-woocommerce' ) );
					}
				}

				ob_start();

				global $wpdb;

				if ( 'this_week' == $duration_type ) {
					$day        = date_i18n( 'w' );
					$start_date = date_i18n( 'Y-m-d', strtotime( '-' . $day . ' days' ) );
					$end_date   = date_i18n( 'Y-m-d', strtotime( '+' . ( 6 - $day ) . ' days' ) );
				} elseif ( 'last_week' == $duration_type ) {
					$previous_week = strtotime( '-1 week +1 day' );

					$start_week = strtotime( 'last sunday midnight', $previous_week );
					$end_week   = strtotime( 'next saturday', $start_week );

					$start_date = date_i18n( 'Y-m-d', $start_week );
					$end_date   = date_i18n( 'Y-m-d', $end_week );
				} elseif ( 'this_month' == $duration_type ) {
					$start_date = date_i18n( 'Y-m-01' );
					$end_date   = date_i18n( 'Y-m-t' );
				} elseif ( 'last_month' == $duration_type ) {
					$start_date = date_i18n( 'Y-m-d', strtotime( 'first day of previous month' ) );
					$end_date   = date_i18n( 'Y-m-d', strtotime( 'last day of previous month' ) );
				} elseif ( 'custom_date' === $duration_type ) {
					$start_date = date_i18n( 'Y-m-d', strtotime( $from_date ) );
					$end_date   = date_i18n( 'Y-m-d', strtotime( $to_date . '+1 day' ) );
				}

				if ( 'all' == $duration_type ) {
					$order_fee_query    = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as orderfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_order_fee' and p.ID=pm.post_id", EFW_Register_Post_Type::FEES_POSTTYPE ), ARRAY_A );
					$product_fee_query  = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as productfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_product_fee' and p.ID=pm.post_id", EFW_Register_Post_Type::FEES_POSTTYPE ), ARRAY_A );
					$gateway_fee_query  = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as gatewayfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_gateway_fee' and p.ID=pm.post_id", EFW_Register_Post_Type::FEES_POSTTYPE ), ARRAY_A );
					$shipping_fee_query = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as shippingfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_shipping_fee' and p.ID=pm.post_id", EFW_Register_Post_Type::FEES_POSTTYPE ), ARRAY_A );
				} else {
					$order_fee_query    = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as orderfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_order_fee' and p.ID=pm.post_id and p.post_date_gmt >= %s and p.post_date_gmt <= %s", EFW_Register_Post_Type::FEES_POSTTYPE, $start_date, $end_date ), ARRAY_A );
					$product_fee_query  = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as productfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_product_fee' and p.ID=pm.post_id and p.post_date_gmt >= %s and p.post_date_gmt <= %s", EFW_Register_Post_Type::FEES_POSTTYPE, $start_date, $end_date ), ARRAY_A );
					$gateway_fee_query  = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as gatewayfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_gateway_fee' and p.ID=pm.post_id and p.post_date_gmt >= %s and p.post_date_gmt <= %s", EFW_Register_Post_Type::FEES_POSTTYPE, $start_date, $end_date ), ARRAY_A );
					$shipping_fee_query = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(pm.meta_value) as shippingfee FROM {$wpdb->posts} AS p , {$wpdb->postmeta} AS pm where p.post_type=%s and p.post_status IN('publish') and pm.meta_key='efw_shipping_fee' and p.ID=pm.post_id and p.post_date_gmt >= %s and p.post_date_gmt <= %s", EFW_Register_Post_Type::FEES_POSTTYPE, $start_date, $end_date ), ARRAY_A );
				}

				$order_fee          = end( $order_fee_query );
				$product_fee        = end( $product_fee_query );
				$gateway_fee        = end( $gateway_fee_query );
				$shipping_fee       = end( $shipping_fee_query );
				$order_fee_value    = efw_check_is_array( $order_fee ) ? $order_fee['orderfee'] : 0;
				$product_fee_value  = efw_check_is_array( $product_fee ) ? $product_fee['productfee'] : 0;
				$gateway_fee_value  = efw_check_is_array( $gateway_fee ) ? $gateway_fee['gatewayfee'] : 0;
				$shipping_fee_value = efw_check_is_array( $shipping_fee ) ? $shipping_fee['shippingfee'] : 0;
				$total_fee          = $order_fee_value + $product_fee_value + $gateway_fee_value + $shipping_fee_value;

				include_once EFW_ABSPATH . 'inc/admin/menu/views/reports/reports-data.php';
				
				$html = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $html ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Add Rule for Simple Product.
		 */
		public static function add_rule_for_simple() {
			check_ajax_referer( 'efw-rule-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST['count'] ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				ob_start();

				$key = absint( $_POST['count'] );

				include_once EFW_ABSPATH . 'inc/admin/menu/views/simple/simple-product-fees-new.php';

				$field = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'field' => $field ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Add Rule for Variable Product.
		 */
		public static function add_rule_for_variable() {
			check_ajax_referer( 'efw-rule-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST['count'] ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				ob_start();
				$loop = isset( $_POST['loop'] ) ? absint( $_POST['loop'] ) : 0;

				$key = absint( $_POST['count'] );

				include_once EFW_ABSPATH . 'inc/admin/menu/views/variable/variable-product-fees-new.php';

				$field = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'field' => $field ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Delete Rule.
		 */
		public static function delete_rule() {
			check_ajax_referer( 'efw-rule-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST['rule_id'] ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				$rule_id = absint( $_POST['rule_id'] );

				efw_delete_post( $rule_id );

				wp_send_json_success();
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Progress bar action.
		 * */
		public static function progress_bar_action() {
			check_ajax_referer( 'efw-bulk-update-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST ) ) {
					throw new exception( esc_html__( 'Invalid data', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['action_scheduler_class_id'] ) ) {
					throw new exception( esc_html__( 'Invalid data', 'extra-fees-for-woocommerce' ) );
				}

				$action_scheduler_id = isset( $_POST['action_scheduler_class_id'] ) ? wc_clean( wp_unslash( $_POST['action_scheduler_class_id'] ) ) : '';

				if ( ! $action_scheduler_id ) {
					throw new exception( esc_html__( 'Invalid data', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! class_exists( 'DFW_Action_Scheduler_Instances' ) ) {
					include_once EFW_ABSPATH . 'inc/action-scheduler/class-efw-action-scheduler-instances.php';
				}

				$action_scheduler_object = EFW_Action_Scheduler_Instances::get_action_scheduler_by_id( $action_scheduler_id );

				if ( ! is_object( $action_scheduler_object ) ) {
					throw new exception( esc_html__( 'Invalid data', 'extra-fees-for-woocommerce' ) );
				}

				$progress_count = $action_scheduler_object->get_progress_count();

				if ( ! $progress_count ) {
					$action_scheduler_object->update_progress_count( 2 );
				}

				$scheduled_actions = as_get_scheduled_actions(
					array(
						'hook'   => $action_scheduler_object->get_action_scheduler_name(),
						'status' => 'pending',
					)
				);

				if ( ! efw_check_is_array( $scheduled_actions ) ) {
					if ( $progress_count < 10 ) {
						$action_scheduler_object->update_progress_count( 10 );
					}

					$scheduled_chunk_actions = as_get_scheduled_actions(
						array(
							'hook'   => $action_scheduler_object->get_chunked_action_scheduler_name(),
							'status' => 'pending',
						)
					);

					if ( ! efw_check_is_array( $scheduled_chunk_actions ) && $progress_count < 100 && $progress_count >= 80 ) {
						$action_scheduler_object->update_progress_count( 100 );
					} elseif ( $progress_count >= 10 && $progress_count <= 80 ) {
						$percentage = $action_scheduler_object->get_progress_count() + 5;
						$action_scheduler_object->update_progress_count( $percentage );
					}
				}

				$percentage = $action_scheduler_object->get_progress_count();
				$response   = array(
					'percentage' => $percentage,
					'completed'  => 'no',
				);

				if ( 100 === $percentage ) {
					$response['completed']    = 'yes';
					$response['msg']          = $action_scheduler_object->get_success_message();
					$response['redirect_url'] = $action_scheduler_object->get_redirect_url();
				}

				wp_send_json_success( $response );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Product Fee description popup action.
		 * */
		public static function fee_desc_popup() {
			check_ajax_referer( 'efw-fee-desc-popup-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['product_id'] ) ) {
					throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

						$product_id = absint( $_POST['product_id'] );
						$product    = wc_get_product( $product_id );
				if ( ! is_object( $product ) ) {
						throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

						$fee_description = wpautop( efw_get_fee_description( $product->get_id() ) );
				if ( ! $fee_description ) {
						throw new Exception( esc_html__( 'No Data Found', 'extra-fees-for-woocommerce' ) );
				}

						ob_start();
						efw_get_template(
							'popup/product-fee/description-popup.php',
							array(
								'fee_description' => $fee_description,
								'product'         => $product,
							)
						);
						$content = ob_get_contents();
						ob_end_clean();

						wp_send_json_success( array( 'html' => $content ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Product Fee description rule popup action.
		 * */
		public static function fee_desc_rule_popup() {
			check_ajax_referer( 'efw-fee-desc-rule-popup-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['product_id'] ) ) {
					throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['rule_id'] ) ) {
					throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

						$product_id = absint( $_POST['product_id'] );
						$product    = wc_get_product( $product_id );
				if ( ! is_object( $product ) ) {
						throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

						$rule_id = isset( $_POST['rule_id'] ) ? absint( $_POST['rule_id'] ) : '';
				if ( ! $rule_id ) {
						throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

						$rule = efw_get_fee_rule( $rule_id );
				if ( ! is_object( $rule ) ) {
						throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

						$fee_descriptions = efw_get_rule_fee_descriptions( $product->get_id() );
						$fee_description  = isset( $fee_descriptions[ $rule_id ] ) ? $fee_descriptions[ $rule_id ] : '';
				if ( ! $fee_description ) {
						throw new Exception( esc_html__( 'No Data Found', 'extra-fees-for-woocommerce' ) );
				}

						ob_start();
						efw_get_template(
							'popup/product-fee/description-rule-popup.php',
							array(
								'fee_description' => $fee_description,
								'product'         => $product,
							)
						);
						$content = ob_get_contents();
						ob_end_clean();

						wp_send_json_success( array( 'html' => $content ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Gateway Fee description popup action.
		 * */
		public static function fee_gateway_desc_popup() {
			check_ajax_referer( 'efw-fee-gateway-desc-popup-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['gateway_id'] ) ) {
						throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

						$gateway_id = wc_clean( wp_unslash( $_POST['gateway_id'] ) );

						ob_start();
						efw_get_template( 'popup/gateway-fee/description-popup.php', array( 'fee_description' => get_option( 'efw_fee_description_for_' . $gateway_id ) ) );
						$content = ob_get_contents();
						ob_end_clean();

						wp_send_json_success( array( 'html' => $content ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Shipping Fee description popup action.
		 * */
		public static function fee_shipping_desc_popup() {
			check_ajax_referer( 'efw-fee-shipping-desc-popup-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! isset( $_POST['shipping_id'] ) ) {
						throw new Exception( esc_html__( 'Invalid Data', 'extra-fees-for-woocommerce' ) );
				}

						$shipping_id = wc_clean( wp_unslash( $_POST['shipping_id'] ) );
				if ( ! get_option( 'efw_shipping_fee_description_' . $shipping_id ) ) {
						throw new Exception( esc_html__( 'No Data Found', 'extra-fees-for-woocommerce' ) );
				}

						ob_start();
						efw_get_template( 'popup/shipping-fee/description-popup.php', array( 'fee_description' => get_option( 'efw_shipping_fee_description_' . $shipping_id ) ) );
						$content = ob_get_contents();
						ob_end_clean();

						wp_send_json_success( array( 'html' => $content ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Order Fee description popup action.
		 * */
		public static function fee_order_desc_popup() {
			check_ajax_referer( 'efw-fee-order-desc-popup-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! get_option( 'efw_ordertotalfee_fee_description' ) ) {
						throw new Exception( esc_html__( 'No Data Found', 'extra-fees-for-woocommerce' ) );
				}

						ob_start();
						efw_get_template( 'popup/order-fee/description-popup.php', array( 'fee_description' => get_option( 'efw_ordertotalfee_fee_description' ) ) );
						$content = ob_get_contents();
						ob_end_clean();

						wp_send_json_success( array( 'html' => $content ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Combined Fee description popup action.
		 * */
		public static function fee_combined_desc_popup() {
			check_ajax_referer( 'efw-combined-fee-desc-popup-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				if ( ! get_option( 'efw_advance_combine_fee_description' ) ) {
					throw new Exception( esc_html__( 'No Data Found', 'extra-fees-for-woocommerce' ) );
				}

				$fee_detail = isset($_REQUEST['fee_detail']) ? ( wc_clean($_REQUEST['fee_detail']) ) : array();

				ob_start();
				efw_get_template( 'popup/combined-fee/description-popup.php', array( 'fee_description' => get_option( 'efw_advance_combine_fee_description' ), 'fee_detail' => $fee_detail ) );
				$content = ob_get_contents();
				ob_end_clean();

				wp_send_json_success( array( 'html' => $content ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Export csv.
		 *
		 * @return void
		 * */
		public static function export_plugin_settings() {

			check_ajax_referer( 'efw-export-nonce', 'efw_security' );

			try {
				// Return if post is invalid.
				if ( ! isset( $_POST ) ) {
					throw new exception( __( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				// Return if current user not have permission.
				if ( ! current_user_can( 'edit_posts' ) ) {
					throw new exception( __( "You don't have permission to do this action", 'extra-fees-for-woocommerce' ) );
				}

				include_once EFW_ABSPATH . 'inc/admin/class-efw-export-csv.php';

				$exporter = new EFW_Export_CSV();

				$exporter->set_limit( 1000 );

				$step = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;

				$exporter->set_page( $step );

				$exporter->generate_file();
				
				/**
				 * This hook is used to alter the export action query arguments.
				 *
				 * @since 1.0
				 */
				$query_args = apply_filters(
					'efw_export_action_query_args',
					array(
						'page' => 'efw_settings',
						'tab'  => 'reports',
						'efw_action'    => 'view',
						'filename'      => $exporter->get_filename(),
						'efw_nonce'     => wp_create_nonce( 'efw-export-csv' ),
					)
				);

				if ( 100 === $exporter->get_percent_complete() ) {
					wp_send_json_success(
						array(
							'step'       => 'done',
							'percentage' => 100,
							'url'        => add_query_arg( $query_args, admin_url('admin.php') ),
						)
					);
				} else {
					wp_send_json_success(
						array(
							'step'       => ++$step,
							'percentage' => $exporter->get_percent_complete(),
						)
					);
				}
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}
	}

	EFW_Admin_Ajax::init();
}
