<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.webtoffee.com
 * @since      1.0.0
 *
 * @package    Webtoffee_Product_Feed_Sync_Pro
 * @subpackage Webtoffee_Product_Feed_Sync_Pro/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Webtoffee_Product_Feed_Sync_Pro
 * @subpackage Webtoffee_Product_Feed_Sync_Pro/admin
 */
if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro_Admin' ) ) {
	/**
	 * Class for admin request
	 */
	class Webtoffee_Product_Feed_Sync_Pro_Admin {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;
		/**
		 * Module list, Module folder and main file must be same as that of module name
		 * Please check the `register_modules` method for more details
		 *
		 * @var      array    $modules    The current version of this plugin.
		 */
		public static $modules = array(
			'history',
			'export',
			'cron',
		);
		/**
		 * Module list, Module folder and main file must be same as that of module name
		 * Please check the `register_modules` method for more details
		 *
		 * @var      array    $existing_modules    The current version of this plugin.
		 */
		public static $existing_modules = array();
		/**
		 * Module list, Module folder and main file must be same as that of module name
		 * Please check the `register_modules` method for more details
		 *
		 * @var      array    $addon_modules    The current version of this plugin.
		 */
		public static $addon_modules = array();

		/**
		 * The pages plugin handles
		 *
		 * @var      array    $wt_pages    The current version of this plugin.
		 */
		public $wt_pages = array(
			'woocommerce_page_product-feed-for-woocommerce',
			'webtoffee-product-feed_page_product-feed-for-woocommerce',
			'product-feed-for-woocommerce_page_product-feed-for-woocommerce',
		);

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @param      string $plugin_name       The name of this plugin.
		 * @param      string $version    The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version = $version;
		}

		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles() {

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Webtoffee_Product_Feed_Sync_Pro_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Webtoffee_Product_Feed_Sync_Pro_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */
			$current_screen = get_current_screen();

			if ( isset( $current_screen->id ) && in_array( $current_screen->id, $this->wt_pages ) ) {

				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/webtoffee-product-feed-admin.css', array(), $this->version, 'all' );
			}
			if ( Webtoffee_Product_Feed_Sync_Pro_Common_Helper::wt_is_screen_allowed() ) {
				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wt-product-feed-admin.css', array(), $this->version, 'all' );
			}
		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts() {

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Webtoffee_Product_Feed_Sync_Pro_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Webtoffee_Product_Feed_Sync_Pro_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */
			$current_screen = get_current_screen();

			if ( isset( $current_screen->id ) && in_array( $current_screen->id, $this->wt_pages ) ) {

				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/webtoffee-product-feed-admin.js', array( 'jquery' ), $this->version, false );
				wp_enqueue_script( $this->plugin_name . '-steps', plugin_dir_url( __FILE__ ) . 'js/jquery.steps.js', array( 'jquery' ), $this->version, false );
				$params = array(
					'nonces' => array(
						'main' => wp_create_nonce( WEBTOFFEE_PRODUCT_FEED_PRO_ID ),
					),
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'plugin_id' => WEBTOFFEE_PRODUCT_FEED_PRO_ID,
					'msgs' => array(
						'error' => __(
							'Error.',
							'product-feed-woocommerce'
						),
						'success' => __(
							'Success.',
							'product-feed-woocommerce'
						),
						'loading' => __(
							'Loading...',
							'product-feed-woocommerce'
						),
						'process' => __(
							'Processing Sync...',
							'product-feed-woocommerce'
						),
						'sync_now' => __(
							'Sync now',
							'product-feed-woocommerce'
						),
						'sync_schedule' => __(
							'Schedule Sync',
							'product-feed-woocommerce'
						),
						'back' => __(
							'Back',
							'product-feed-woocommerce'
						),
						'next' => __(
							'Next',
							'product-feed-woocommerce'
						),
					),
				);
				wp_localize_script( $this->plugin_name, 'wt_feed_params', $params );
			}

			if ( Webtoffee_Product_Feed_Sync_Pro_Common_Helper::wt_is_screen_allowed() ) {
				/* enqueue scripts */
				if ( ! function_exists( 'is_plugin_active' ) ) {
					include_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					if ( ! wp_script_is( 'jquery-tiptip' ) ) {
						wp_enqueue_script( 'jquery-tiptip' );
					}
					wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wt-product-feed-admin.js', array( 'jquery', 'jquery-tiptip' ), $this->version, false );
				} else {
					wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wt-product-feed-admin.js', array( 'jquery' ), $this->version, false );
					wp_enqueue_script( WEBTOFFEE_PRODUCT_FEED_PRO_ID . '-tiptip', WT_PRODUCT_FEED_PRO_PLUGIN_URL . 'admin/js/tiptip.js', array( 'jquery' ), WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION, false );
				}

				$order_addon_active_status = false;
				$user_addon_active_status = false;
				if ( is_plugin_active( 'order-import-export-for-woocommerce/order-import-export-for-woocommerce.php' ) ) {
					$order_addon_active_status = true;
				}
				if ( is_plugin_active( 'users-customers-import-export-for-wp-woocommerce/users-customers-import-export-for-wp-woocommerce.php' ) ) {
					$user_addon_active_status = true;
				}

				$params = array(
					'nonces' => array(
						'main' => wp_create_nonce( WEBTOFFEE_PRODUCT_FEED_PRO_ID ),
					),
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'plugin_id' => WEBTOFFEE_PRODUCT_FEED_PRO_ID,
					'msgs' => array(
						'settings_success' => __(
							'Settings updated.',
							'product-feed-woocommerce'
						),
						'all_fields_mandatory' => __(
							'All fields are mandatory',
							'product-feed-woocommerce'
						),
						'settings_error' => __(
							'Unable to update Settingss.',
							'product-feed-woocommerce'
						),
						'template_del_error' => __(
							'Unable to delete template',
							'product-feed-woocommerce'
						),
						'template_del_loader' => __(
							'Deleting template...',
							'product-feed-woocommerce'
						),
						'value_empty' => __(
							'Value is empty.',
							'product-feed-woocommerce'
						),
						'error' => __(
							'Something went wrong. Please reload the page and check',
							'product-feed-woocommerce'
						),
						'success' => __(
							'Success.',
							'product-feed-woocommerce'
						),
						'loading' => __(
							'Loading...',
							'product-feed-woocommerce'
						),
						'sure' => __(
							'Are you sure?',
							'product-feed-woocommerce'
						),
						'use_expression' => __(
							'Use expression as value.',
							'product-feed-woocommerce'
						),
						'cancel' => __(
							'Cancel',
							'product-feed-woocommerce'
						),
						'export_canceled' => __(
							'Feed creation cancelled',
							'product-feed-woocommerce'
						),
						'send_req' => __(
							'Send feature request',
							'product-feed-woocommerce'
						),
						'sending_req' => __(
							'Sending...',
							'product-feed-woocommerce'
						),
						'copied_msg' => __(
							'URL copied to clipboard',
							'product-feed-woocommerce'
						),
						'changes_not_saved_warn' => __(
							'Changes that you made may not be saved.',
							'product-feed-woocommerce'
						),
						'search_category_placeholder' => __(
							'Search for a category',
							'product-feed-woocommerce'
						),
					),
				);
				wp_localize_script( $this->plugin_name, 'wt_pf_basic_params', $params );
			}
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @param mixed $links Plugin action links.
		 *
		 * @return array
		 */
		public function add_productfeed_action_links( $links ) {

			$plugin_links = array(
				'<a href="' . esc_url( admin_url( 'admin.php?page=webtoffee_product_feed_main_pro_export' ) ) . '">' . __(
					'Settings',
					'product-feed-woocommerce'
				) . '</a>',
				'<a target="_blank" href="https://woocommerce.com/my-account/contact-support/">' . __(
					'Support',
					'product-feed-woocommerce'
				) . '</a>',
				'<a target="_blank" href="https://woocommerce.com/document/product-feed-woocommerce/">' . __(
					'Documentation',
					'product-feed-woocommerce'
				) . '</a>',
			);
			if ( array_key_exists( 'deactivate', $links ) ) {
				$links['deactivate'] = str_replace( '<a', '<a class="productfeed-deactivate-link"', $links['deactivate'] );
			}
			return array_merge( $plugin_links, $links );
		}


		/**
		 * Save category mapping
		 */
		public function wt_fbfeed_ajax_save_category() {
			// phpcs:ignore Nonce and user role check handled by check_write_access method.
			if ( ! Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
				return;
			}
			
			if ( isset( $_POST['form'] ) ) {
				parse_str( sanitize_text_field( wp_unslash( $_POST['form'] ) ), $form );
				$form = (array) $form;
				$_REQUEST = $form;
				check_admin_referer( 'wt-category-mapping' );

				$mapping_option = 'wt_fbfeed_category_mapping';

				$map_to_cats = ! empty( $_REQUEST['map_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['map_to'] ) ) : array();
				$mapping_data = array_map( 'absint', $map_to_cats );

				foreach ( $mapping_data as $local_category_id => $fb_category_id ) {
					if ( $fb_category_id ) {
						update_term_meta( $local_category_id, 'wt_fb_category', $fb_category_id );
					}
				}

				// Delete product categories dropdown cache.
				wp_cache_delete( 'wt_fbfeed_dropdown_product_categories' );

				if ( update_option( $mapping_option, $mapping_data, false ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					update_option(
						'wt_mapping_message',
						esc_html__(
							'Mapping Added Successfully',
							'product-feed-woocommerce'
						),
						false
					);
					echo json_encode(
						array(
							'step' => 'done',
							'message' => 'success',
						)
					);
				} else {
					update_option(
						'wt_mapping_message',
						esc_html__(
							'Failed To Add Mapping',
							'product-feed-woocommerce'
						),
						false
					);
					echo json_encode(
						array(
							'step' => 'done',
							'message' => 'failure',
						)
					);
				}

				exit();
			}
		}

		/**
		 * Registers menu options
		 * Hooked into admin_menu
		 *
		 * @since    1.0.0
		 */
		public function admin_menu() {

			$menus = array(
				'general-settings' => array(
					'menu',
					__( 'General Settings', 'product-feed-woocommerce' ),
					__( 'General Settings', 'product-feed-woocommerce' ),
					/**
					* Filter the query arguments for a request.
					*
					* Enables adding extra arguments or setting defaults for a post
					* collection request.
			 *
			 * @since 1.0.0
			 *
					* @param string          $capability    Allowed capability.
					*/
						   apply_filters( 'wt_import_export_allowed_capability', 'import' ),
					WEBTOFFEE_PRODUCT_FEED_PRO_ID,
					array( $this, 'admin_settings_page' ),
					'dashicons-controls-repeat',
					56,
				),
			);
				/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @since 1.0.0
		 *
		 * @param array           $menus    Menus.
		 */
			$menus = apply_filters( 'wt_pf_admin_menu_pro', $menus );

			$menu_order = array( 'export', 'export-sub', 'import', 'history', 'history_log', 'cron' );
			$this->wt_menu_order_changer( $menus, $menu_order );

			$main_menu = reset( $menus ); // main menu must be first one.

			$parent_menu_key = $main_menu ? $main_menu[4] : WEBTOFFEE_PRODUCT_FEED_PRO_ID;

			/* adding general settings menu */
			$menus['general-settings-sub'] = array(
				'submenu',
				$parent_menu_key,
				__( 'General Settings', 'product-feed-woocommerce' ),
				__( 'General Settings', 'product-feed-woocommerce' ),
				/**
				* Filter the query arguments for a request.
				*
				* Enables adding extra arguments or setting defaults for a post
				* collection request.
			 *
			 * @since 1.0.0
			 *
				* @param string          $capability    Allowed capability.
				*/
				   apply_filters( 'wt_import_export_allowed_capability', 'import' ),
				WEBTOFFEE_PRODUCT_FEED_PRO_ID,
				array( $this, 'admin_settings_page' ),
			);
			if ( count( $menus ) > 0 ) {

						$i = 0;
				foreach ( $menus as $menu ) {
					if ( 'submenu' == $menu[0] && 1 == $i ) {
						/* currently we are only allowing one parent menu */
						add_submenu_page( 'woocommerce', $menu[2], 'Product Feed Suite', $menu[4], 'webtoffee_product_feed_main_pro_export', $menu[6] );
					}
					$i++;
				}
			}
		}
		/**
		 * Menu order adjust
		 *
		 * @param array $arr Menus.
		 * @param array $index_arr Index.
		 */
		public function wt_menu_order_changer( &$arr, $index_arr ) {
			$arr_t = array();
			foreach ( $index_arr as $i => $v ) {
				foreach ( $arr as $k => $b ) {
					if ( $k == $v ) {
						$arr_t[ $k ] = $b;
					}
				}
			}
			$arr = $arr_t;
		}
		/**
		 * Admin settings page
		 */
		public function admin_settings_page() {
			include plugin_dir_path( __FILE__ ) . 'partials/webtoffee-product-feed-admin-display.php';
		}

					/**
					 *  Save admin settings and module settings ajax hook
					 */
		public function save_settings() {
			$out = array(
				'status' => false,
				'msg' => __( 'Error', 'product-feed-woocommerce' ),
			);

			// phpcs:ignore Nonce and user role check handled by check_write_access method.
			if ( Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
				// Nonce already verified on main function - sanitization thorugh helper functions.
				$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
				if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
					return false;
				}
				$advanced_settings = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings();
				$advanced_fields = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings_fields();
				$validation_rule = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::extract_validation_rules( $advanced_fields );
				$new_advanced_settings = array();
				foreach ( $advanced_fields as $key => $value ) {
					$form_field_name = isset( $value['field_name'] ) ? $value['field_name'] : '';
					$field_name = ( substr( $form_field_name, 0, 8 ) !== 'wt_pf_' ? 'wt_pf_' : '' ) . $form_field_name;
					$validation_key = str_replace( 'wt_pf_', '', $field_name );
					if ( isset( $_POST[ $field_name ] ) ) {
						$new_advanced_settings[ $field_name ] = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
					}
				}

				$checkbox_items = array( 'wt_pf_enable_import_log', 'wt_pf_enable_history_auto_delete', 'wt_pf_include_bom', 'wt_pf_all_shipping_zone' );
				foreach ( $checkbox_items as $checkbox_item ) {
					$new_advanced_settings[ $checkbox_item ] = isset( $new_advanced_settings[ $checkbox_item ] ) ? $new_advanced_settings[ $checkbox_item ] : 0;
				}

				Webtoffee_Product_Feed_Sync_Pro_Common_Helper::set_advanced_settings( $new_advanced_settings );
				$out['status'] = true;
				$out['msg'] = __(
					'Settings Updated',
					'product-feed-woocommerce'
				);
				/**
				 * Filter the query arguments for a request.
				 *
				 * Enables adding extra arguments or setting defaults for a post
				 * collection request.
				 *
				 * @since 1.0.0
				 *
				 * @param array          $new_advanced_settings    Advanced settings options.
				 */
				do_action( 'wt_pf_after_advanced_setting_update_pro', $new_advanced_settings );
			}
			echo json_encode( $out );
			exit();
		}

		/**
		 *  Delete pre-saved temaplates entry from DB - ajax hook
		 */
		public function delete_template() {
			$out = array(
				'status' => false,
				'msg' => __(
					'Error',
					'product-feed-woocommerce'
				),
			);

			// phpcs:ignore Nonce and user role check handled by check_write_access method.
			if ( Wt_Pf_Sh::check_write_access( WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) {
				// Nonce already verified on main function - sanitization thorugh helper functions.
				$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
				if ( ! ( wp_verify_nonce( $nonce, WEBTOFFEE_PRODUCT_FEED_PRO_ID ) ) ) {
					return $out;
				}
				if ( isset( $_POST['template_id'] ) ) {

					global $wpdb;
					$template_id = absint( $_POST['template_id'] );
					$where_data = array( $template_id );
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wt_pf_mapping_template WHERE id =%d", $where_data ) ); // @codingStandardsIgnoreLine
					$out['status'] = true;
					$out['msg'] = __( 'Template deleted successfully', 'product-feed-woocommerce' );
					$out['template_id'] = $template_id;
				}
			}
			wp_send_json( $out );
		}

		/**
		 *Registers modules: admin
		*/
		public function admin_modules() {
			$wt_pf_admin_modules = get_option( 'wt_pf_admin_modules' );

			if ( false === $wt_pf_admin_modules ) {
				$wt_pf_admin_modules = array();
			}
			foreach ( self::$modules as $module ) { // loop through module list and include its file.
				$is_active = 1;
				if ( isset( $wt_pf_admin_modules[ $module ] ) ) {
					$is_active = $wt_pf_admin_modules[ $module ]; // checking module status.
				} else {
					$wt_pf_admin_modules[ $module ] = 1; // default status is active.
				}
				$module_file = plugin_dir_path( __FILE__ ) . "modules/$module/class-webtoffee-product-feed-sync-pro-$module.php";
				if ( file_exists( $module_file ) && 1 == $is_active ) {
					self::$existing_modules[] = $module; // this is for module_exits checking.
					require_once $module_file;
				} else {
					$wt_pf_admin_modules[ $module ] = 0;
				}
			}
			$out = array();
			foreach ( $wt_pf_admin_modules as $k => $m ) {
				if ( in_array( $k, self::$modules ) ) {
					$out[ $k ] = $m;
				}
			}

			update_option( 'wt_pf_admin_modules', $out );

			// Explode the plugin path into an array.
			$plugin_path_array = explode( '/', WT_PRODUCT_FEED_PRO_BASE_NAME );

			// Plugin folder is the first element.
			$plugin_folder_name = reset( $plugin_path_array );

			/**
			 *  Add on modules
			 */
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			foreach ( self::$addon_modules as $module ) { // loop through module list and include its file.
				$plugin_file = "$plugin_folder_name-$module/$plugin_folder_name-$module.php";
				if ( is_plugin_active( $plugin_file ) ) {
					$module_file = WP_PLUGIN_DIR . "/$plugin_folder_name-$module/$module/class-webtoffee-product-feed-sync-pro-$module.php";
					if ( file_exists( $module_file ) ) {
						self::$existing_modules[] = $module;
						require_once $module_file;
					}
				}
			}

			$addon_modules_basic = array(

				'custom' => $plugin_folder_name,
				'google' => $plugin_folder_name,
				'google-local-product-inventory' => $plugin_folder_name,
				'google-promotions' => $plugin_folder_name,
				'google-product-reviews' => $plugin_folder_name,
				'buyon-google' => $plugin_folder_name,
				'facebook' => $plugin_folder_name,
				'tiktok' => $plugin_folder_name,
				'pinterest' => $plugin_folder_name,
				'pinterest-rss' => $plugin_folder_name,
				'bing' => $plugin_folder_name,
				'snapchat' => $plugin_folder_name,
				'idealo' => $plugin_folder_name,
				'twitter' => $plugin_folder_name,
			);
			foreach ( $addon_modules_basic as $module_key => $module_path ) {
				if ( is_plugin_active( "{$module_path}/{$module_path}.php" ) ) {
					$module_file = WP_PLUGIN_DIR . "/{$module_path}/admin/modules/$module_key/class-webtoffee-product-feed-sync-pro-$module_key.php";

					if ( file_exists( $module_file ) ) {
						self::$existing_modules[] = $module_key;
						require_once $module_file;
					}
				}
			}
		}
		/**
		 * Module exist check
		 *
		 * @param string $module Module name.
		 * @return bool
		 */
		public static function module_exists( $module ) {
			return in_array( $module, self::$existing_modules );
		}
		/**
		 * Envelope settings tab content with tab div.
		 * relative path is not acceptable in view file
		 *
		 * @param string  $target_id Target id.
		 * @param string  $view_file View file.
		 * @param string  $html Is html.
		 * @param array   $variables Variables.
		 * @param boolean $need_submit_btn Is submit button needed.
		 */
		public static function envelope_settings_tabcontent( $target_id, $view_file = '', $html = '', $variables = array(), $need_submit_btn = 0 ) {
			// extract( $variables );.
			?>
			<div class="wt-pfd-tab-content" data-id="<?php echo esc_html( $target_id ); ?>">
			<?php
			if ( '' != $view_file && file_exists( $view_file ) ) {
				include_once $view_file;
			} else {
				echo wp_kses_post( $html );
			}
			?>
			<?php
			if ( 1 == $need_submit_btn ) {
				include WT_PRODUCT_FEED_PRO_PLUGIN_PATH . 'admin/views/admin-settings-save-button.php';
			}
			?>
			</div>
			<?php
		}
	}

}
