<?php
/**
 * Enqueue Admin Assets Files.
 *
 * @package Extra Fees for WooCommerce/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'EFW_Admin_Assets' ) ) {

	/**
	 * EFW_Admin_Assets Class.
	 */
	class EFW_Admin_Assets {

		/**
		 * EFW_Admin_Assets Class Initialization.
		 */
		public static function init() {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'efw_enqueue_scripts' ) );
		}

				/**
				 * Enqueue external css/js files.
				 */
		public static function efw_enqueue_scripts() {

			// Enqueue external js files.
			self::external_js_files();

			// Enqueue external css files.
			self::external_css_files();
		}

		/**
		 * Enqueue external css files.
		 */
		public static function external_css_files() {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$screen_ids   = efw_page_screen_ids();
			$newscreenids = get_current_screen();
			$screenid     = str_replace( 'edit-', '', $newscreenids->id );

			wp_enqueue_style( 'efw-admin', EFW_PLUGIN_URL . '/assets/css/admin.css', array( 'wc-admin-layout' ), EFW_VERSION );

			if ( ! in_array( $screenid, $screen_ids ) ) {
				return;
			}
			/**
			 * Hook:efw_admin_after_enqueue_css.
			 *
			 * @since 1.0
			 */
			do_action( 'efw_admin_after_enqueue_css' );
		}

		/**
		 * Enqueue Admin end required JS files.
		 */
		public static function external_js_files() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$screen_ids   = efw_page_screen_ids();
			$newscreenids = get_current_screen();
			$screenid     = str_replace( 'edit-', '', $newscreenids->id );

			$enqueue_array = array(
				'efw-toggle'      => array(
					'callable' => array( 'EFW_Admin_Assets', 'toggle_section' ),
					'restrict' => in_array( $screenid, $screen_ids ),
				),
				'efw-select2'     => array(
					'callable' => array( 'EFW_Admin_Assets', 'select2' ),
					'restrict' => in_array( $screenid, $screen_ids ),
				),
				'efw-bulk-update' => array(
					'callable' => array( 'EFW_Admin_Assets', 'bulk_update' ),
					'restrict' => true,
				),
			);
			/**
			 * Hook:efw_admin_enqueue_scripts.
			 *
			 * @since 1.0
			 */
			$enqueue_array = apply_filters( 'efw_admin_enqueue_scripts', $enqueue_array );
			if ( ! efw_check_is_array( $enqueue_array ) ) {
				return;
			}

			foreach ( $enqueue_array as $key => $enqueue ) {
				if ( ! efw_check_is_array( $enqueue ) ) {
					continue;
				}

				if ( $enqueue['restrict'] ) {
					call_user_func_array( $enqueue['callable'], array( $suffix ) );
				}
			}
			/**
			 * Hook:efw_admin_after_enqueue_js.
			 *
			 * @since 1.0
			 */
			do_action( 'efw_admin_after_enqueue_js' );
		}

		/**
		 * Enqueue Section Toggle scripts.
		 */
		public static function toggle_section() {
			// Enqueue media.
			wp_enqueue_media();

			wp_enqueue_script( 'efw-admin', EFW_PLUGIN_URL . '/assets/js/admin.js', array( 'jquery' ), EFW_VERSION, false );
			wp_localize_script(
				'efw-admin',
				'efw_admin_param',
				array(
					'rule_nonce'            => wp_create_nonce( 'efw-rule-nonce' ),
					'delete_rule'           => esc_html__( 'Are you sure you want to delete this rule?', 'extra-fees-for-woocommerce' ),
					'ajaxurl'               => EFW_ADMIN_AJAX_URL,
					'update_fee_data_nonce' => wp_create_nonce( 'efw-update-fee-data-nonce' ),
					'export_nonce' => wp_create_nonce( 'efw-export-nonce' ),
					'import_nonce' => wp_create_nonce( 'efw-import-nonce' ),
					'remove_multiple_fee'   => esc_html__( 'Are you sure you want to remove?', 'extra-fees-for-woocommerce' ),
				)
			);
		}

		/**
		 * Enqueue Section Toggle scripts.
		 */
		public static function bulk_update() {
			// Enqueue media.
			wp_enqueue_media();

			wp_enqueue_script( 'efw-bulk-update', EFW_PLUGIN_URL . '/assets/js/bulk-update.js', array( 'jquery' ), EFW_VERSION, false );
			wp_localize_script(
				'efw-bulk-update',
				'efw_bulk_update_param',
				array(
					'ajaxurl'                => EFW_ADMIN_AJAX_URL,
					'bulk_update_nonce'      => wp_create_nonce( 'efw-bulk-update-nonce' ),
					'fixed_fee_error'        => __( 'Fixed Fee Value cannot be empty', 'extra-fees-for-woocommerce' ),
					'percentage_fee_error'   => __( 'Percentage Fee Value cannot be empty', 'extra-fees-for-woocommerce' ),
					'fee_text_error'         => __( 'Fee Text cannot be empty', 'extra-fees-for-woocommerce' ),
					'products_empty_error'   => __( 'Please select at least one product', 'extra-fees-for-woocommerce' ),
					'categories_empty_error' => __( 'Please select at least one category', 'extra-fees-for-woocommerce' ),
					'tag_empty_error'        => __( 'Please select at least one tag', 'extra-fees-for-woocommerce' ),
				)
			);
		}

		/**
		 * Enqueue select2 scripts.
		 */
		public static function select2() {
			wp_enqueue_script( 'efw-enhanced', EFW_PLUGIN_URL . '/assets/js/efw-enhanced.js', array( 'jquery', 'select2', 'jquery-ui-datepicker', 'iris' ), EFW_VERSION, false );
			wp_localize_script(
				'efw-enhanced',
				'efw_enhanced_select_params',
				array(
					'search_nonce' => wp_create_nonce( 'efw-search-nonce' ),
					'ajaxurl'      => EFW_ADMIN_AJAX_URL,
				)
			);
		}
	}

	EFW_Admin_Assets::init();
}
