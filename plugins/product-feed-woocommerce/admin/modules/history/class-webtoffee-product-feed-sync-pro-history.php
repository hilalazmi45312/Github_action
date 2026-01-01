<?php
/**
 * Handles the History actions.
 *
 * @package   Webtoffee_Product_Feed_Sync_Pro\Admin\Modules\History
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_History' ) ) {
	/**
	 * Webtoffee_Product_Feed_Sync_Pro_History Class.
	 */
	class Webtoffee_Product_Feed_Sync_Pro_History {
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $module_id = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $module_id_static = '';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $module_base = 'history';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $status_arr = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $status_label_arr = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $action_label_arr = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $max_records = 20;
		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->module_id = Webtoffee_Product_Feed_Sync_Pro::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;

			self::$status_arr = array(
				'pending' => 0, // running...
				'finished' => 1, // completed.
				'failed' => 2, // failed.
			);

			add_action( 'init', array( $this, 'wt_product_feed_pro_load_translations_history' ) );

			/* Admin menu for hostory listing */
			add_filter( 'wt_pf_admin_menu_pro', array( $this, 'add_admin_pages' ), 10, 1 );

			/* advanced plugin settings */
			add_filter( 'wt_pf_advanced_setting_fields_pro', array( $this, 'advanced_setting_fields' ), 12 );

			/* main ajax hook. The callback function will decide which action is to execute. */
			add_action( 'wp_ajax_iew_history_ajax_pro', array( $this, 'ajax_main' ), 11 );

			/* Hook to perform actions after advanced settings was updated */
			add_action( 'wt_pf_after_advanced_setting_update_pro', array( $this, 'after_advanced_setting_update' ), 11 );
		}

		/**
		 * Load translations.
		 */
		public function wt_product_feed_pro_load_translations_history() {
			self::$status_label_arr = array(
				0 => __( 'Running/Incomplete', 'product-feed-woocommerce' ),
				1 => __( 'Finished', 'product-feed-woocommerce' ),
				2 => __( 'Failed', 'product-feed-woocommerce' ),
			);

			self::$action_label_arr = array(
				'export' => __( 'Export', 'product-feed-woocommerce' ),
				'import' => __( 'Import', 'product-feed-woocommerce' ),
				'export_image' => __( 'Image Export', 'product-feed-woocommerce' ),
			);
		}

		/**
		 * Adding admin menus
		 *
		 * @param array $menus Menus.
		 */
		public function add_admin_pages( $menus ) {

			$menus[ $this->module_base ] = array(
				'submenu',
				WEBTOFFEE_PRODUCT_FEED_PRO_ID,
				__( 'Manage Feeds', 'product-feed-woocommerce' ),
				__( 'Manage Feeds', 'product-feed-woocommerce' ),
				/**
				 * Capability for menus.
				 *
				 * @since 1.0.0
				 *
				 * @param string $capability Capability.
				 */
					apply_filters( 'wt_import_export_allowed_capability', 'import' ),
				$this->module_id,
				array( $this, 'admin_settings_page' ),
			);

				return $menus;
		}
		/**
		 * Ajax main function - all history ajax action nonce verification is done here
		 */
		public function ajax_main() {
			// phpcs:ignore Nonce and user role check handled by check_write_access method.
			if ( Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
				// Nonce already verified on main function - sanitization through helper functions.
				$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
				if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
					return false;
				}
				$allowed_ajax_actions = array( 'view_log' );

				$out = array(
					'status' => 0,
					'msg' => __( 'Error', 'product-feed-woocommerce' ),
				);

				$history_action = isset( $_POST['history_action'] ) ? sanitize_text_field( wp_unslash( $_POST['history_action'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$data_type = isset( $_POST['data_type'] ) ? sanitize_text_field( wp_unslash( $_POST['data_type'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( method_exists( $this, $history_action ) && in_array( $history_action, $allowed_ajax_actions ) ) {
					$out = $this->{$history_action}( $out );
				}

				if ( 'json' == $data_type ) {
					echo json_encode( $out );
				}
			}
			exit();
		}

		/**
		 *    Fields for advanced settings
		 *
		 * @param array $fields Form fields.
		 */
		public function advanced_setting_fields( $fields ) {

			return $fields;
		}


		/**
		 *  History list page
		 */
		public function admin_settings_page() {
			global $wpdb;

			/* delete action */
			if ( isset( $_GET['wt_pf_delete_history'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				// phpcs:ignore Nonce and user role check handled by check_write_access method.
				if ( Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {

					$history_id_arr = isset( $_GET['wt_pf_history_id'] ) ? map_deep( explode( ',', sanitize_text_field( wp_unslash( $_GET['wt_pf_history_id'] ) ) ), 'absint' ) : array();// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
					$history_id_arr = Wt_Pf_Sh::sanitize_item( $history_id_arr, 'absint_arr' );

					if ( count( $history_id_arr ) > 0 ) {
						self::delete_history_by_id( $history_id_arr );
						self::delete_cron_by_histoy_id( $history_id_arr );
					}
				}
			}

			/**
			*   Lising page section
			*/
			$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$history_tb;

			$post_type_arr = self::get_disticnt_items( 'item_type' );
			$action_type_arr = self::get_disticnt_items( 'template_type' );
			$status_arr = self::get_disticnt_items( 'status' );
			/**
			 * Filter the importer post types.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $post_types    Exporter post types.
			 */
			$importer_post_types = apply_filters( 'wt_pf_importer_post_types_basic', array() );
			/**
			 * Filter the exporter post types.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $post_types    Exporter post types.
			 */
			$exporter_post_types = apply_filters( 'wt_pf_exporter_post_types_basic', array() );
			$post_type_label_arr = array_merge( $importer_post_types, $exporter_post_types );

			/**
			*   Get history entries by Schedule ID
			*/
			$cron_id = ( isset( $_GET['wt_pf_cron_id'] ) ? absint( $_GET['wt_pf_cron_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
			$history_arr = array();
			$list_by_cron = false;
			if ( $cron_id > 0 ) {
				$cron_module_obj = Webtoffee_Product_Feed_Sync_Pro::load_modules( 'cron' );
				if ( ! is_null( $cron_module_obj ) ) {
					$cron_data = $cron_module_obj->get_cron_by_id( $cron_id );
					if ( $cron_data ) {
						$history_id_arr = ( '' != $cron_data['history_id_list'] ? maybe_unserialize( $cron_data['history_id_list'] ) : array() );
						$history_id_arr = ( is_array( $history_id_arr ) ? $history_id_arr : array() );
						$list_by_cron = true;
					} else {
						$cron_id = 0; // invalid cron id.
					}
				} else {
					$cron_id = 0; // cron module not enabled.
				}
			}

			/**
			*   Filter by form fields
			*/
			$filter_by = array(
				'item_type' => array(
					'label' => __( 'Post type', 'product-feed-woocommerce' ),
					'values' => $post_type_arr,
					'val_labels' => $post_type_label_arr,
					'val_type' => '%s',
					'selected_val' => '',
				),
				'template_type' => array(
					'label' => __( 'Action type', 'product-feed-woocommerce' ),
					'values' => $action_type_arr,
					'val_labels' => self::$action_label_arr,
					'val_type' => '%s',
					'selected_val' => '',
				),
				'status' => array(
					'label' => __( 'Status', 'product-feed-woocommerce' ),
					'values' => $status_arr,
					'val_labels' => self::$status_label_arr,
					'validation_rule' => array( 'type' => 'absint' ),
					'val_type' => '%d',
					'selected_val' => '',
				),
			);

			if ( $list_by_cron ) {
				unset( $filter_by['item_type'] );
				unset( $filter_by['template_type'] );
			}

			/**
			*   Order by field vals
			*/
			$order_by = array(
				'date_desc' => array(
					'label' => __( 'Date descending', 'product-feed-woocommerce' ),
					'sql' => 'created_at DESC',
				),
				'date_asc' => array(
					'label' => __( 'Date ascending', 'product-feed-woocommerce' ),
					'sql' => 'created_at ASC',
				),
			);

			/* just applying a text validation */
			$conf_arr           = isset( $_GET['wt_pf_history'] ) ? map_deep( wp_unslash( $_GET['wt_pf_history'] ), 'sanitize_text_field' ) : array();// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
			$url_params_allowed = array(); // this array will only include the allowed $_GET params. This will use in pagination section.

			/**
			*   Filter by block
			*/
			$where_qry_val_arr = array(); // sql query WHERE clause val array.
			$where_qry_format_arr = array(); // sql query  WHERE clause val format array.
			if ( isset( $conf_arr['filter_by'] ) ) {
				$url_params_allowed['filter_by'] = array();/* for pagination purpose */

				$filter_by_conf = ( is_array( $conf_arr['filter_by'] ) ? $conf_arr['filter_by'] : array() );
				$filter_by_validation_rule = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::extract_validation_rules( $filter_by );
				foreach ( $filter_by as $filter_key => $filter_val ) {
					if ( isset( $filter_by_conf[ $filter_key ] ) && trim( $filter_by_conf[ $filter_key ] ) != '' ) {
						$where_qry_format_arr[] = $filter_key . '=' . $filter_val['val_type'];
						$filter_by[ $filter_key ]['selected_val'] = Wt_Pf_Sh::sanitize_data( $filter_by_conf[ $filter_key ], $filter_key, $filter_by_validation_rule );
						$where_qry_val_arr[] = $filter_by[ $filter_key ]['selected_val'];

						$url_params_allowed['filter_by'][ $filter_key ] = $filter_by[ $filter_key ]['selected_val']; /* for pagination purpose */
					}
				}
			}

			/**
			*   Order by block
			*/
			$default_order_by = array_keys( $order_by )[0];
			$order_by_val = $default_order_by;
			$order_qry_val_arr = array(); // sql query ORDER clause val array.
			if ( isset( $conf_arr['order_by'] ) ) {
				$order_by_val = ( is_array( $conf_arr['order_by'] ) ? $default_order_by : $conf_arr['order_by'] );
			}
			if ( isset( $order_by[ $order_by_val ] ) ) {
				$order_qry_val_arr[] = $order_by[ $order_by_val ]['sql'];
				$url_params_allowed['order_by'] = $order_by_val; /* for pagination purpose */
			}

			/**
			*   Pagination block
			*/
			$max_data = ( isset( $conf_arr['max_data'] ) ? absint( $conf_arr['max_data'] ) : $this->max_records );
			$this->max_records = ( $max_data > 0 ? $max_data : $this->max_records );

			$offset = ( isset( $_GET['offset'] ) ? absint( $_GET['offset'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
			$url_params_allowed['max_data'] = $this->max_records;
			$pagination_url_params = array(
				'wt_pf_history' => $url_params_allowed,
				'tab'            => 'history',
				'page' => 'webtoffee_product_feed_main_pro_export',
			);
			$offset_qry_str = " LIMIT $offset, " . $this->max_records;
			$no_records = false;

			if ( $list_by_cron ) {
				$pagination_url_params['wt_pf_cron_id'] = $cron_id; /* adding cron id to URL params */

				$total_history_ids = count( $history_id_arr );
				if ( $total_history_ids > 0 ) {
					$where_qry_format_arr[] = 'id IN(' . implode( ',', array_fill( 0, $total_history_ids, '%d' ) ) . ')';
					$where_qry_val_arr = array_merge( $where_qry_val_arr, $history_id_arr );

				} else // reset all where, order by queries.
				{
					$where_qry_format_arr = array();
					$where_qry_val_arr = array();
					$no_records = true;
				}
			}

			if ( $no_records ) {
				// in list_by cron, history IDs are not available.
				$total_records = 0;
				$history_list  = array();
			} else {

				if ( $list_by_cron && $total_history_ids > 0 ) {
					$total_records = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history WHERE id IN( " . implode( ',', array_fill( 0, count( $history_id_arr ), '%d' ) ) . ' )', $where_qry_val_arr ), ARRAY_A );// @codingStandardsIgnoreLine.
					$where_qry_val_arr[] = $offset;
					$where_qry_val_arr[] = $this->max_records;
					$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE id IN( " . implode( ',', array_fill( 0, count( $history_id_arr ), '%d' ) ) . ' )  LIMIT %d, %d', $where_qry_val_arr ), ARRAY_A );// @codingStandardsIgnoreLine.
				} elseif ( ! empty( $where_qry_val_pair ) ) {
					if ( isset( $where_qry_val_pair['item_type'] ) && isset( $where_qry_val_pair['template_type'] ) && isset( $where_qry_val_pair['status'] ) ) {
						$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE item_type=%s AND template_type=%s AND status=%d ORDER BY %1s LIMIT %d, %d", $where_qry_val_pair['item_type'], $where_qry_val_pair['template_type'], $where_qry_val_pair['status'], $order_qry_val_arr[0], $offset, $this->max_records ), ARRAY_A );// @codingStandardsIgnoreLine.
						$total_records = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history WHERE item_type=%s AND template_type=%s AND status=%d", $where_qry_val_pair['item_type'], $where_qry_val_pair['template_type'], $where_qry_val_pair['status'] ), ARRAY_A );// @codingStandardsIgnoreLine.
					} elseif ( isset( $where_qry_val_pair['item_type'] ) && isset( $where_qry_val_pair['template_type'] ) ) {
						$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE item_type=%s AND template_type=%s ORDER BY %1s LIMIT %d, %d", $where_qry_val_pair['item_type'], $where_qry_val_pair['template_type'], $order_qry_val_arr[0], $offset, $this->max_records ), ARRAY_A );// @codingStandardsIgnoreLine.
						$total_records = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history WHERE item_type=%s AND template_type=%s", $where_qry_val_pair['item_type'], $where_qry_val_pair['template_type'] ), ARRAY_A );// @codingStandardsIgnoreLine.
					} elseif ( isset( $where_qry_val_pair['item_type'] ) && isset( $where_qry_val_pair['status'] ) ) {
						$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE item_type=%s AND status=%d ORDER BY %1s LIMIT %d, %d", $where_qry_val_pair['item_type'], $where_qry_val_pair['status'], $order_qry_val_arr[0], $offset, $this->max_records ), ARRAY_A );// @codingStandardsIgnoreLine.
						$total_records = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history WHERE item_type=%s AND status=%d", $where_qry_val_pair['item_type'], $where_qry_val_pair['status'] ), ARRAY_A );// @codingStandardsIgnoreLine.
					} elseif ( isset( $where_qry_val_pair['template_type'] ) && isset( $where_qry_val_pair['status'] ) ) {
						$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE template_type=%s AND status=%d ORDER BY %1s LIMIT %d, %d", $where_qry_val_pair['template_type'], $where_qry_val_pair['status'], $order_qry_val_arr[0], $offset, $this->max_records ), ARRAY_A );// @codingStandardsIgnoreLine.
						$total_records = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history WHERE template_type=%s AND status=%d", $where_qry_val_pair['template_type'], $where_qry_val_pair['status'] ), ARRAY_A );// @codingStandardsIgnoreLine.
					} elseif ( isset( $where_qry_val_pair['item_type'] ) ) {
						$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE item_type=%s ORDER BY %1s LIMIT %d, %d", $where_qry_val_pair['item_type'], $order_qry_val_arr[0], $offset, $this->max_records ), ARRAY_A );// @codingStandardsIgnoreLine.
						$total_records = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history WHERE item_type=%s", $where_qry_val_pair['item_type'] ), ARRAY_A );// @codingStandardsIgnoreLine.
					} elseif ( isset( $where_qry_val_pair['template_type'] ) ) {
						$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE template_type=%s ORDER BY %1s LIMIT %d, %d", $where_qry_val_pair['template_type'], $order_qry_val_arr[0], $offset, $this->max_records ), ARRAY_A );// @codingStandardsIgnoreLine.
						$total_records = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history WHERE template_type=%s", $where_qry_val_pair['template_type'] ), ARRAY_A );// @codingStandardsIgnoreLine.
					} elseif ( isset( $where_qry_val_pair['status'] ) ) {
						$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE status=%d ORDER BY %1s LIMIT %d, %d", $where_qry_val_pair['status'], $order_qry_val_arr[0], $offset, $this->max_records ), ARRAY_A );// @codingStandardsIgnoreLine.
						$total_records = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history WHERE status=%d", $where_qry_val_pair['status'] ), ARRAY_A );// @codingStandardsIgnoreLine.
					}
				} else {
					$history_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history  ORDER BY %1s LIMIT %d, %d", $order_qry_val_arr[0], $offset, $this->max_records ), ARRAY_A );// @codingStandardsIgnoreLine.
					$total_records = $wpdb->get_row( "SELECT COUNT(id) AS total_records FROM {$wpdb->prefix}wt_pf_action_history", ARRAY_A );// @codingStandardsIgnoreLine.

				}

				$total_records = ( $total_records && isset( $total_records['total_records'] ) ? $total_records['total_records'] : 0 );
				$history_list = ( $history_list ? $history_list : array() );
			}

			$delete_url_params = $pagination_url_params;
			$delete_url_params['wt_pf_delete_history'] = 1;
			$delete_url_params['wt_pf_history_id'] = '_history_id_';
			$delete_url_params['offset'] = $offset;
			$delete_url = wp_nonce_url( admin_url( 'admin.php?' . http_build_query( $delete_url_params ) ), WEBTOFFEE_PRODUCT_FEED_PRO_ID );

			// enqueue script.
			if ( isset( $_GET['page'] ) && 'webtoffee_product_feed_main_pro_export' == $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( isset( $_GET['tab'] ) && 'history' == $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification
					$this->enqueue_scripts( $delete_url );
				}
			}

			include plugin_dir_path( __FILE__ ) . 'views/settings.php';
		}
		/**
		 * Enques scripts
		 *
		 * @param string $delete_url Delete URL.
		 */
		private function enqueue_scripts( $delete_url ) {

			if ( Webtoffee_Product_Feed_Sync_Pro_Common_Helper::wt_is_screen_allowed() ) {

				wp_enqueue_script( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery' ), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, false );

				$params = array(
					'delete_url' => $delete_url,
					'msgs' => array(
						'sure' => __( 'Are you sure?', 'product-feed-woocommerce' ),
					),
					'copied_msg' => __( 'URL copied to clipboard', 'product-feed-woocommerce' ),
				);
				wp_localize_script( $this->module_id, 'wt_pf_history_basic_params', $params );
			}
		}
		/**
		 * Record failure
		 *
		 * @param string $history_id Delete URL.
		 * @param string $msg Delete URL.
		 */
		public static function record_failure( $history_id, $msg ) {
			$update_data = array(
				'status' => self::$status_arr['failed'],
				'status_text' => $msg, // no need to add translation function.
			);
			$update_data_type = array(
				'%d',
				'%s',
			);
			self::update_history_entry( $history_id, $update_data, $update_data_type );
		}

		/**
		 *  Delete history entry from DB and also associated files (Export files only)
		 *
		 *  @param array|int $id history entry IDs.
		 */
		public static function delete_history_by_id( $id ) {
			global $wpdb;
			$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$history_tb;
			if ( is_array( $id ) ) {
				$where = ' IN(' . implode( ',', array_fill( 0, count( $id ), '%d' ) ) . ')';
				$where_data = $id;
			} else {
				$where = '=%d';
				$where_data = array( $id );
			}

			// first remove files associated with it. give argument as array then no need to check the result array type.
			$allowed_ext_arr = array( 'csv', 'xml' ); // please update this array if new file types introduced.
			$list = self::get_history_entry_by_id( $where_data );
			if ( $list ) {
				foreach ( $list as $listv ) {
					if ( 'export' == $listv['template_type'] ) {
						if ( Webtoffee_Product_Feed_Sync_Pro_Admin::module_exists( 'export' ) ) {
							$ext_arr = explode( '.', $listv['file_name'] );
							$ext = end( $ext_arr );
							if ( in_array( $ext, $allowed_ext_arr ) ) {
								$file_path = Webtoffee_Product_Feed_Sync_Pro_Export::get_file_path( $listv['file_name'] );
								if ( $file_path && file_exists( $file_path ) ) {
									wp_delete_file( $file_path );
								}
							}
						}
					}
				}
			}

			if ( is_array( $id ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching 
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wt_pf_action_history WHERE id IN( " . implode( ',', array_fill( 0, count( $id ), '%d' ) ) . ' )', $where_data ) );
			} else {
				$where_data = array( $id );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wt_pf_action_history WHERE id =%d", $where_data ) );
			}
		}


		/**
		 *  Delete history entry from DB and also associated files (Export files only)
		 *
		 * @param array|int $id history entry IDs.
		 */
		public static function delete_cron_by_histoy_id( $id ) {
			global $wpdb;
			$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$cron_tb;
			if ( is_array( $id ) ) {
				$where = ' IN(' . implode( ',', array_fill( 0, count( $id ), '%d' ) ) . ')';
				$where_data = $id;
			} else {
				$where = '=%d';
				$where_data = array( $id );
			}

			if ( is_array( $id ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wt_pf_cron WHERE history_id IN( " . implode( ',', array_fill( 0, count( $id ), '%d' ) ) . ' )', $where_data ) );
			} else {
				$where_data = array( $id );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wt_pf_cron WHERE history_id =%d", $where_data ) );
			}
		}
		/**
		 *    Update history
		 *
		 * @param integer $history_id Advanced settings.
		 * @param array   $update_data Update data.
		 * @param string  $update_data_type Update type.
		 */
		public static function update_history_entry( $history_id, $update_data, $update_data_type ) {
			global $wpdb;
			// updating the data.
			$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$history_tb;
			$update_where = array(
				'id' => $history_id,
			);
			$update_where_type = array(
				'%d',
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->update( $tb, $update_data, $update_where, $update_data_type, $update_where_type ) !== false ) {
				return true;
			}
			return false;
		}

		/**
		 *    Method perform actions after advanced settings was updated
		 *
		 * @param array $advanced_settings Advanced settings.
		 */
		public function after_advanced_setting_update( $advanced_settings ) {
			/* Check auto deletion enabled */
			if ( isset( $advanced_settings['wt_pf_enable_history_auto_delete'] ) && 1 == $advanced_settings['wt_pf_enable_history_auto_delete'] ) {
				$record_count = ( isset( $advanced_settings['wt_pf_auto_delete_history_count'] ) ? absint( $advanced_settings['wt_pf_auto_delete_history_count'] ) : 0 );
				if ( $record_count > 0 ) {
					self::auto_delete_history_entry( $record_count );
				}
			}
		}

		/**
		 * Check and delete history entry. If auto deletion enabled
		 *
		 * @global type $wpdb
		 * @param type $record_count Record count.
		 */
		public static function auto_delete_history_entry( $record_count = 0 ) {
			if ( 0 == $record_count ) {
				if ( Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'enable_history_auto_delete' ) == 1 ) {
					$record_count = absint( Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings( 'auto_delete_history_count' ) );
				}
			}
			if ( $record_count >= 1 ) {
				global $wpdb;
				$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$history_tb;
				$limit_record_count = $record_count - 1;
				$data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE status = 1 AND id<(SELECT id FROM {$wpdb->prefix}wt_pf_action_history ORDER BY id DESC LIMIT %1s, 1 ) ", $limit_record_count ), ARRAY_A );// @codingStandardsIgnoreLine.
				if ( $data && is_array( $data ) ) {
					$id_arr = array_column( $data, 'id' );
					self::delete_history_by_id( $id_arr );
				}
			}
		}

		/**
		 * Create a history entry before starting export/import
		 *
		 * @param  string  $file_name String export/import file name.
		 * @param  array   $form_data  Array export/import formdata.
		 * @param  string  $to_process  String export or import.
		 * @param integer $action  Int DB id if success otherwise zero.
		 * @return integer  0 or created id.
		 */
		public static function create_history_entry( $file_name, $form_data, $to_process, $action ) {
			global $wpdb;

			$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$history_tb;
			$insert_data = array(
				'template_type' => $action,
				'item_type' => $to_process, // item type Eg: product.
				'file_name' => $file_name, // export/import file name.
				'created_at' => time(), // craeted time.
				'updated_at' => time(), // craeted time.
				'data' => maybe_serialize( $form_data ), // formadata.
				'status' => self::$status_arr['pending'], // pending.
				'status_text' => 'Pending', // pending, No need to add translate function. we can add this on printing page.
				'offset' => 0, // current offset, its always 0 on start.
				'total' => 0, // total records, not available now.
			);
			$insert_data_type = array(
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%d',
				'%d',
				'%d',
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$insert_response = $wpdb->insert( $tb, $insert_data, $insert_data_type );

			/* check for auto delete */
			self::auto_delete_history_entry();

			if ( $insert_response ) {
				return $wpdb->insert_id;
			}
			return 0;
		}

		/**
		 *   Get distinct column values from history table
		 *
		 *   @param string $column table column name.
		 *   @return array array of distinct column values.
		 */
		private static function get_disticnt_items( $column ) {
			global $wpdb;
			$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$history_tb;
			$data = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT %1s FROM {$wpdb->prefix}wt_pf_action_history ORDER BY %s ASC", $column, $column ), ARRAY_A ); // @codingStandardsIgnoreLine.
			$data = is_array( $data ) ? $data : array();
			return array_column( $data, $column );
		}

		/**
		 * Get feed filenames
		 *
		 * @global object $wpdb WPDB.
		 * @return array
		 */
		public static function get_filename_items() {
			global $wpdb;
			$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$history_tb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$data = $wpdb->get_results( "SELECT file_name FROM {$wpdb->prefix}wt_pf_action_history", ARRAY_A );
			$data = is_array( $data ) ? $data : array();
			return $data;
		}

		/**
		 *     Taking history entry by ID
		 *
		 * @param integer $id History entry id.
		 */
		public static function get_history_entry_by_id( $id ) {
			global $wpdb;
			$tb = $wpdb->prefix . Webtoffee_Product_Feed_Sync_Pro::$history_tb;
			if ( is_array( $id ) ) {
				$where = ' IN(' . implode( ',', array_fill( 0, count( $id ), '%d' ) ) . ')';
				$where_data = $id;
			} else {
				$where = '=%d';
				$where_data = array( $id );
			}
			if ( is_array( $id ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE id IN( " . implode( ',', array_fill( 0, count( $id ), '%d' ) ) . ' )', $where_data ), ARRAY_A );
			} else {
				$where_data = array( $id );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wt_pf_action_history WHERE id =%d", $where_data ), ARRAY_A );
			}
		}

		/**
		 *     Generate pagination HTML
		 *
		 * @param type $total Description.
		 * @param type $limit Description.
		 * @param type $offset Description.
		 * @param type $url Description.
		 * @param type $url_params Description.
		 * @param type $mxnav Description.
		 */
		public static function gen_pagination_html( $total, $limit, $offset, $url, $url_params = array(), $mxnav = 6 ) {
			if ( $total <= 0 ) {
				return '';
			}
			/* taking current page */
			$crpage = ( $offset + $limit ) / $limit;

			$limit = $limit <= 0 ? 1 : $limit;
			$ttpg = ceil( $total / $limit );
			if ( $ttpg < $crpage ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:disable WordPress.PHP.DevelopmentFunctions
					error_log( 'Total page less than current page' );
				}
			}

			// calculations.
			$mxnav = $ttpg < $mxnav ? $ttpg : $mxnav;

			$mxnav_mid = floor( $mxnav / 2 );
			$pgstart = $mxnav_mid >= $crpage ? 1 : $crpage - $mxnav_mid;
			$mxnav_mid += $mxnav_mid >= $crpage ? ( $mxnav_mid - $crpage ) : 0;  // adjusting other half with first half balance.
			$pgend = $crpage + $mxnav_mid;
			if ( $pgend > $ttpg ) {
				$pgend = $ttpg;
			}

			$html = '<span class="wt_pf_pagination_total_info">' . $total . __( ' record(s)', 'product-feed-woocommerce' ) . '</span>';
			$url_params_string = http_build_query( $url_params );
			$url_params_string = ( '' != $url_params_string ) ? '&' . $url_params_string : '';
			$url = ( false !== strpos( $url, '?' ) ? $url . '&' : $url . '?' );
			$href_attr = ' href="' . $url . 'offset={offset}' . $url_params_string . '"';

			$prev_onclick = '';
			if ( $crpage > 1 ) {
				$offset = ( ( $crpage - 2 ) * $limit );
				$prev_onclick = str_replace( '{offset}', $offset, $href_attr );
			}

			$html .= '<a class="' . ( $crpage > 1 ? 'wt_pf_page' : 'wt_pf_pagedisabled' ) . '"' . $prev_onclick . '>‹</a>';
			for ( $i = $pgstart; $i <= $pgend; $i++ ) {
				$page_offset = '';
				$onclick = '';
				$offset = ( $i * $limit ) - $limit;
				if ( $i != $crpage ) {
					$onclick = str_replace( '{offset}', $offset, $href_attr );
				}
				$html .= '<a class="' . ( $i == $crpage ? 'wt_pf_pageactive' : 'wt_pf_page' ) . '" ' . $onclick . '>' . $i . '</a>';
			}

			$next_onclick = '';
			if ( $crpage < $ttpg ) {
				$offset = ( $crpage * $limit );
				$next_onclick = str_replace( '{offset}', $offset, $href_attr );
			}

			$html .= '<a class="' . ( $crpage < $ttpg ? 'wt_pf_page' : 'wt_pf_pagedisabled' ) . '"' . $next_onclick . '>›</a>';
			return '<div class="wt_pf_pagination"><span>' . $html . '</div>';
		}
	}
}
Webtoffee_Product_Feed_Sync_Pro::$loaded_modules['history'] = new Webtoffee_Product_Feed_Sync_Pro_History();
