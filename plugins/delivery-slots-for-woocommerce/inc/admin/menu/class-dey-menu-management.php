<?php

/**
 * Menu Management.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Menu_Management' ) ) {

	include_once 'class-dey-settings.php';

	/**
	 * DEY_Menu_Management Class.
	 */
	class DEY_Menu_Management {

		/**
		 * Plugin slug.
		 *
		 * @var string
		 */
		protected static $plugin_slug = 'dey';

		/**
		 * Menu slug.
		 *
		 * @var string
		 */
		protected static $menu_slug = 'dey_delivery';

		/**
		 * Calender slug.
		 *
		 * @var string
		 */
		protected static $calender_slug = 'dey_calender';

		/**
		 * Settings slug.
		 *
		 * @var string
		 */
		protected static $settings_slug = 'dey_settings';

		/**
		 * Class initialization.
		 */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
			add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_custom_wc_screen_ids' ), 9, 1 );
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( 'DEY_Settings', 'prepare_field_value' ), 10, 3 );
			add_action( 'admin_notices', array( __CLASS__, 'display_error_notices' ) );
			add_action( 'admin_init', array( __CLASS__, 'dismiss_notices' ), 10 );
		}

		/**
		 * Add the custom screen IDs in WooCommerce.
		 *
		 * @since 1.0.0
		 * @param array $wc_screen_ids Screen IDs.
		 * @return array
		 */
		public static function add_custom_wc_screen_ids( $wc_screen_ids ) {
			$newscreenids = get_current_screen();
			if ( ! is_object( $newscreenids ) ) {
				return $wc_screen_ids;
			}

			// Return if the current page is not delivery slots page.
			if ( ! in_array( dey_current_page_screen_id(), dey_page_screen_ids() ) ) {
				return $wc_screen_ids;
			}

			$wc_screen_ids[] = $newscreenids->id;

			return $wc_screen_ids;
		}

		/**
		 * Add the menu pages.
		 */
		public static function add_menu_pages() {
			$url = DEY_PLUGIN_URL . '/assets/images/dash-icon.png';
			// Delivery and Pickup Scheduler Menu.
			add_menu_page( DEY()->menu_name(), DEY()->menu_name(), 'manage_woocommerce', self::$menu_slug, null, $url );
			// Calender Submenu.
			$calender_page = add_submenu_page( self::$menu_slug, __( 'Calender', 'delivery-slots-for-woocommerce' ), __( 'Calender', 'delivery-slots-for-woocommerce' ), 'manage_woocommerce', self::$calender_slug, array( 'DEY_Calender', 'output' ) );
			// Settings Submenu.
			$settings_page = add_submenu_page( self::$menu_slug, __( 'Settings', 'delivery-slots-for-woocommerce' ), __( 'Settings', 'delivery-slots-for-woocommerce' ), 'manage_woocommerce', self::$settings_slug, array( 'DEY_Settings', 'output' ) );

			add_action( 'load-' . $calender_page, array( __CLASS__, 'calender_page_init' ) );
			add_action( 'load-' . $settings_page, array( __CLASS__, 'settings_page_init' ) );
		}

		/**
		 * Initialize the Settings page.
		 */
		public static function settings_page_init() {
			global $current_tab, $current_section, $current_sub_section;

			// Include settings pages.
			$settings = DEY_Settings::get_settings_pages();
			$tabs     = dey_get_allowed_setting_tabs();

			// prepare current tab/section.
			$current_tab = key( $tabs );
			if ( ! empty( $_GET['tab'] ) ) {
				$sanitize_current_tab = sanitize_title( wp_unslash( $_GET[ 'tab' ] ) ) ; // @codingStandardsIgnoreLine.
				if ( array_key_exists( $sanitize_current_tab, $tabs ) ) {
					$current_tab = $sanitize_current_tab;
				}
			}

			$section             = isset( $settings[ $current_tab ] ) ? $settings[ $current_tab ]->get_sections() : array();
			$current_section     = empty( $_REQUEST[ 'section' ] ) ? key( $section ) : sanitize_title( wp_unslash( $_REQUEST[ 'section' ] ) ) ; // @codingStandardsIgnoreLine.
			$current_section     = empty( $current_section ) ? $current_tab : $current_section;
			$current_sub_section = empty( $_REQUEST[ 'subsection' ] ) ? '' : sanitize_title( wp_unslash( $_REQUEST[ 'subsection' ] ) ) ; // @codingStandardsIgnoreLine.

			/**
			 * This hook is used to do extra action after settings loaded.
			 *
			 * @hooked DEY_Settings_Page->save - 10 (save the settings).
			 * @hooked DEY_Settings_Page->reset - 20 (reset the settings).
			 * @since 1.0
			 */
			do_action( sanitize_key( self::$plugin_slug . '_settings_loaded_' . $current_tab ), $current_section );

			add_action( 'dey_settings_content', array( 'DEY_Settings', 'show_messages' ) );
			add_action( 'woocommerce_admin_field_dey_custom_fields', array( 'DEY_Settings', 'output_fields' ) );
		}

		/**
		 * Initialize the Calender page.
		 */
		public static function calender_page_init() {
			global $current_tab;

			$tabs = DEY_Calender::get_tabs();

			// prepare current tab.
			$current_tab = key( $tabs );
			if ( ! empty( $_GET['tab'] ) ) {
				$sanitize_current_tab = sanitize_title( wp_unslash( $_GET[ 'tab' ] ) ) ; // @codingStandardsIgnoreLine.
				if ( array_key_exists( $sanitize_current_tab, $tabs ) ) {
					$current_tab = $sanitize_current_tab;
				}
			}
		}

		/**
		 * Display the admin error notices.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function display_error_notices() {
			self::display_order_pickup_location_warning_message();
			self::display_scheduler_rule_warning_message();
			self::display_usability_related_messages();
		}

		/**
		 * Display the order pickup location warning message.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function display_order_pickup_location_warning_message() {
			if ( 'dey_pickup_locations' !== dey_current_page_screen_id() ) {
				return;
			}

			if ( dey_check_is_array( dey_get_order_local_pickup_scheduler_rules() ) ) {
				return;
			}

			$message = __( 'At least one Scheduler Rule must be created in Local Pickup Mode in Scheduler Rules section to allow Pickup using Pickup Locations.', 'delivery-slots-for-woocommerce' );  
			?>
			<div class='notice notice-error'><p><?php echo wp_kses_post( $message ); ?></p></div>
			<?php
		}

		/**
		 * Display the scheduler rule warning message.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function display_scheduler_rule_warning_message() {
			if ( 'dey_scheduler_rule' !== dey_current_page_screen_id() ) {
				return;
			}

			$shipping_methods = dey_get_shipping_methods();
			unset( $shipping_methods[''] ); // Unset the default all shipping method.
			if ( dey_check_is_array( $shipping_methods ) ) {
				return;
			}

			$message = __( 'There are no shipping methods enabled. Please enable at least one to ensure the scheduler rule works.', 'delivery-slots-for-woocommerce' );
			?>
			<div class='notice notice-error'><p><?php echo wp_kses_post( $message ); ?></p></div>
			<?php
		}

		/**
		 * Display the usability related messages.
		 *
		 * @since 4.1.0
		 * @global string $current_tab Current tab. 
		 * @return void
		 */
		public static function display_usability_related_messages() {
			global $current_tab;
			
			// Return if the current tab is not order delivery or order local pickup tab.
			if ( ! in_array( $current_tab, array( 'order_delivery', 'local_pickup' ) ) ) {
				return;
			}

			// Return if the user has already dismissed the notice.
			if ( self::user_has_dismissed_notice( 'usability_related' ) ) {
				return;
			}

			if ( false !== get_option( 'dey_order_delivery_enable' ) ) { // Existing user.
				$notice = __( "We have revamped the Plugin's Settings. The Settings which were previously available in this section[Except Business Days] have been moved to a Rule-based Configuration. For your convenience, we have created a Rule with the values you have already configured. To customize the Rule, Navigate to", 'delivery-slots-for-woocommerce' );
			} else { // New user.
				$notice = __( 'This Section uses a Rule-Based configuration. For your convenience, we have created a Rule that can work for all Shipping Methods. You can customize the rule as per your needs. To customize the Rule, Navigate to', 'delivery-slots-for-woocommerce' );
			}

			$scheduler_rules_page_url = add_query_arg( array( 'post_type' => DEY_Register_Post_Types::SCHEDULER_RULE_POSTTYPE ), admin_url( 'edit.php' ) );

			/* translators: %1$s: Notice, %2$s: Scheduler rules page URL, %3$s: Button label */
			$message     = sprintf( '%1$s <a href="%2$s">%3$s</a>', $notice, $scheduler_rules_page_url, __( 'Delivery and Pickup Scheduler > Scheduler Rules.', 'delivery-slots-for-woocommerce' ) );
			$notice_name = 'usability_related';
		
			include_once DEY_PLUGIN_PATH . '/inc/admin/menu/views/html-dismiss-notice.php';
		}

		/**
		 * Handle dismiss notices.
		 * 
		 * @since 4.1.0
		 */
		public static function dismiss_notices() {
			if ( ! isset( $_GET['dey_dismiss_notice'], $_GET['dey_notice_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['dey_notice_nonce'] ) ), 'dey-dismiss-notice' ) ) {
				return;
			}

			$notice_name = sanitize_text_field( wp_unslash( $_GET['dey_dismiss_notice'] ) );

			update_user_meta( get_current_user_id(), "dey_dismissed_{$notice_name}_notice", true );
		}

		/**
		 * Check if a given user has dismissed a given admin notice.
		 *
		 * @since 4.1.0
		 * @param string $notice_name Notice name.
		 * @return bool
		 */
		public static function user_has_dismissed_notice( $notice_name ) {
			return (bool) get_user_meta( get_current_user_id(), "dey_dismissed_{$notice_name}_notice", true );
		}
	}

	DEY_Menu_Management::init();
}
