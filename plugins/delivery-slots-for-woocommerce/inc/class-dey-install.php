<?php

/**
 * Initialize the Plugin.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Install' ) ) {

	/**
	 * Class.
	 */
	class DEY_Install {

		/**
		 * Class initialization.
		 */
		public static function init() {
			// Update.
			add_action( 'init', array( __CLASS__, 'install' ) );
			add_filter( 'plugin_action_links_' . DEY_PLUGIN_SLUG, array( __CLASS__, 'settings_link' ) );
		}

		/**
		 * Install.
		 */
		public static function install() {
			DEY_Updates::maybe_run();
			self::set_default_values(); // default values.
			self::update_version();
		}

		/**
		 * Update the current version.
		 */
		private static function update_version() {
			update_option( 'dey_version', DEY_VERSION );
		}

		/**
		 * Add the plugin settings link in plugin page.
		 */
		public static function settings_link( $links ) {
			$setting_page_link = '<a href="' . dey_get_settings_page_url() . '">' . __( 'Settings', 'delivery-slots-for-woocommerce' ) . '</a>';
			array_unshift( $links, $setting_page_link );

			return $links;
		}

		/**
		 * May be set the settings default values.
		 */
		public static function set_default_values() {
			if ( ! class_exists( 'DEY_Settings' ) ) {
				include_once DEY_PLUGIN_PATH . '/inc/admin/menu/class-dey-settings.php';
			}

			// Get the settings.
			$settings = DEY_Settings::get_settings_pages();
			foreach ( $settings as $setting ) {
				$sections = $setting->get_sections();
				if ( ! dey_check_is_array( $sections ) ) {
					continue;
				}

				foreach ( $sections as $section_key => $section ) {
					$settings_array = $setting->get_settings( $section_key );
					foreach ( $settings_array as $value ) {
						// Check if the default and id key is exists.
						if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
							// Check if option are saved or not.
							if ( get_option( $value['id'] ) === false ) {
								add_option( $value['id'], $value['default'] );
							}
						}
					}
				}
			}

			// default for the notifications.
			$notifications = DEY_Notification_Instances::get_notifications();

			foreach ( $notifications as $notification ) {
				$settings = $notification->get_settings_array();

				if ( ! dey_check_is_array( $settings ) ) {
					continue;
				}

				foreach ( $settings as $setting ) {
					// Check if the default and id key is exists.
					if ( isset( $setting['default'] ) && isset( $setting['id'] ) ) {
						// Check if option are saved or not.
						if ( get_option( $setting['id'] ) === false ) {
							add_option( $setting['id'], $setting['default'] );
						}
					}
				}
			}

			// Create the default scheduler rule.
			self::create_default_scheduler_rule();
		}

		/**
		 * Create default scheduler rule for new users.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function create_default_scheduler_rule() {
			// Return if the scheduler rule has already updated.
			if ( 'yes' === get_option( 'dey_default_scheduler_rule_updated', 'no' ) ) {
				return;
			}

			// Create default scheduler rule.
			dey_create_new_scheduler_rule(
				dey_get_scheduler_rule_default_data(),
				array(
					'post_title'  => __( 'Order Scheduler', 'delivery-slots-for-woocommerce' ),
					'post_status' => 'dey_inactive',
				)
			);

			update_option( 'dey_default_scheduler_rule_updated', 'yes' );
		}
	}

	DEY_Install::init();
}
