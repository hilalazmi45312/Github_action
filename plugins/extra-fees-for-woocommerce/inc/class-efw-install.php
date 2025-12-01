<?php

/**
 * Initialize the plugin.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Install' ) ) {

	/**
	 * EFW_Install Class.
	 */
	class EFW_Install {

		/**
		 *  Class initialization.
		 */
		public static function init() {
			add_action( 'woocommerce_init', array( __CLASS__, 'check_version' ) ) ;
			add_action( 'init', array( 'EFW_Updates', 'maybe_run' ), 20 );
			add_filter( 'plugin_action_links_' . EFW_PLUGIN_SLUG , array( __CLASS__, 'settings_link' ) ) ;
		}
				
		/**
		 * Check Extra Fees version and run the updater is required.
		 *
		 * This check is done on all requests and runs if the versions do not match.
		 */
		public static function check_version() {
			if ( get_option( 'efw_version' ) == EFW_VERSION ) {
				return ;
			}
			self::install() ;
		}

		/**
		 * Install.
		 */
		public static function install() {
			//Default values.
			self::set_default_values() ;
			// Update version.
			self::update_version() ;
		}
				
		/**
		 * Update current version.
		 */
		private static function update_version() {
			update_option( 'efw_version' , EFW_VERSION ) ;
		}

		/**
		 *  Settings link.
		 */
		public static function settings_link( $links ) {
			$setting_page_link = '<a href="' . efw_get_settings_page_url() . '">' . esc_html__( 'Settings' , 'extra-fees-for-woocommerce' ) . '</a>' ;

			array_unshift( $links , $setting_page_link ) ;

			return $links ;
		}

		/**
		 *  Set settings default values.
		 */
		public static function set_default_values() {
			if ( ! class_exists( 'EFW_Settings' ) ) {
				include_once EFW_PLUGIN_PATH . '/inc/admin/menu/class-efw-settings.php' ;
			}

			//Default for settings.
			$settings = EFW_Settings::get_settings_pages() ;

			foreach ( $settings as $section_key => $setting ) {
				$settings_array = $setting->get_settings( $section_key ) ;
				if ( ! efw_check_is_array( $settings_array ) ) {
					continue ;
				}

				foreach ( $settings_array as $value ) {
					if ( isset( $value[ 'default' ] ) && isset( $value[ 'id' ] ) ) {
						if ( get_option( $value[ 'id' ] ) === false ) {
							add_option( $value[ 'id' ] , $value[ 'default' ] ) ;
						}
					}
				}
			}
		}
	}

	EFW_Install::init() ;
}
