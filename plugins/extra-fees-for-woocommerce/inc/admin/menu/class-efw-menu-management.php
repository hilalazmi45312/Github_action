<?php

/**
 * Menu Management.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Menu_Management' ) ) {

	include_once 'class-efw-settings.php' ;

	/**
	 * EFW_Menu_Management Class.
	 */
	class EFW_Menu_Management {

		/**
		 * Plugin slug.
		 */
		protected static $plugin_slug = 'efw' ;

		/**
		 * Menu slug.
		 */
		protected static $menu_slug = 'woocommerce' ;

		/**
		 * Settings slug.
		 */
		protected static $settings_slug = 'efw_settings' ;

		/**
		 * Class initialization.
		 */
		public static function init() {
			//Add Admin Menu Page.
			add_action( 'admin_menu' , array( __CLASS__, 'add_menu_pages' ) ) ;
			//Add Custom Screen Ids.
			add_filter( 'woocommerce_screen_ids' , array( __CLASS__, 'add_custom_wc_screen_ids' ) , 9 , 1 ) ;
			//Save Custom Field in Settings.
			add_filter( 'woocommerce_admin_settings_sanitize_option' , array( __CLASS__, 'save_custom_fields' ) , 10 , 3 ) ;
			add_action( 'admin_init', array( __CLASS__, 'export_settings_option' ) );
		}

		/**
		 * Add Custom Screen IDs in WooCommerce.
		 */
		public static function add_custom_wc_screen_ids( $wc_screen_ids ) {
			$screen_ids = efw_page_screen_ids() ;

			$newscreenids = get_current_screen() ;
			$screenid     = str_replace( 'edit-' , '' , $newscreenids->id ) ;

			//Return if current page is not refund page.
			if ( ! in_array( $screenid , $screen_ids ) ) {
				return $wc_screen_ids ;
			}

			$wc_screen_ids[] = $screenid ;

			return $wc_screen_ids ;
		}

		/**
		 * Add menu pages.
		 */
		public static function add_menu_pages() {
			//Settings Submenu.
			$settings_page = add_submenu_page( self::$menu_slug , esc_html__( 'Extra Fees' , 'extra-fees-for-woocommerce' ) , esc_html__( 'Extra Fees' , 'extra-fees-for-woocommerce' ) , 'manage_woocommerce' , self::$settings_slug , array( __CLASS__, 'settings_page' ) ) ;

			add_action( 'load-' . $settings_page , array( __CLASS__, 'settings_page_init' ) ) ;
		}

		/**
		 * Settings page init.
		 */
		public static function settings_page_init() {
			global $current_tab, $current_section, $current_sub_section, $current_action ;

			//Include settings pages.
			$settings = EFW_Settings::get_settings_pages() ;

			$tabs = efw_get_allowed_setting_tabs() ;

			//Get current tab/section.
			$current_tab = ( ! isset( $_GET[ 'tab' ] ) || empty( $_GET[ 'tab' ] ) || ! array_key_exists( wc_clean( wp_unslash( $_GET[ 'tab' ] ) ) , $tabs ) ) ? key( $tabs ) : wc_clean( wp_unslash( $_GET[ 'tab' ] ) ) ;

			$section = isset( $settings[ $current_tab ] ) ? $settings[ $current_tab ]->get_sections() : array() ;

			$current_section     = empty( $_REQUEST[ 'section' ] ) ? key( $section ) : wc_clean( wp_unslash( $_REQUEST[ 'section' ] ) ) ;
			$current_section     = empty( $current_section ) ? $current_tab : $current_section ;
			$current_sub_section = empty( $_REQUEST[ 'subsection' ] ) ? '' : wc_clean( wp_unslash( $_REQUEST[ 'subsection' ] ) ) ;
			$current_action      = empty( $_REQUEST[ 'action' ] ) ? '' : wc_clean( wp_unslash( $_REQUEST[ 'action' ] ) ) ;
						/**
			 * Trigger settings save. 
			 *
			 * @since 1.0
			 */
			do_action( sanitize_key( self::$plugin_slug . '_settings_save_' . $current_tab ) , $current_section ) ;
						/**
			 * Trigger settings reset. 
			 *
			 * @since 1.0
			 */
			do_action( sanitize_key( self::$plugin_slug . '_settings_reset_' . $current_tab ) , $current_section ) ;

			//Add Custom Field in Settings.
			add_action( 'woocommerce_admin_field_efw_custom_fields' , array( __CLASS__, 'custom_fields_output' ) ) ;
		}

		/**
		 * Settings page output.
		 */
		public static function settings_page() {
			EFW_Settings::output() ;
		}

		/**
		 * Output the custom field settings.
		 */
		public static function custom_fields_output( $options ) {

			EFW_Settings::output_fields( $options ) ;
		}

		/**
		 * Save Custom Field settings.
		 */
		public static function save_custom_fields( $value, $option, $raw_value ) {

			if ( 'efw_custom_fields' == $option[ 'type' ] ) {
				$value = EFW_Settings::save_fields( $value , $option , $raw_value ) ;
			}

			return $value ;
		}

		/**
		 * Export Settings Option.
		 *
		 * @return void
		 * */
		public static function export_settings_option() {

			if ( ! isset( $_GET['efw_action'], $_GET['efw_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( wc_clean( wp_unslash( $_GET['efw_nonce'] ) ), 'efw-export-csv' ) ) {
				return;
			}

			include_once EFW_ABSPATH . 'inc/admin/class-efw-export-csv.php';

			$exporter = new EFW_Export_CSV();

			if ( ! empty( $_GET['filename'] ) ) { // WPCS: input var ok.
				$exporter->set_filename( sanitize_file_name( wp_unslash( $_GET['filename'] ) ) ); // WPCS: input var ok, sanitization ok.
			}


			$exporter->export();
		}
	}

	EFW_Menu_Management::init() ;
}
