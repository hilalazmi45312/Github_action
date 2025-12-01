<?php

/**
 * Admin Assets.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'DEY_Admin_Assets' ) ) {

	/**
	 * Class.
	 */
	class DEY_Admin_Assets {

		/**
		 * Suffix.
		 *
		 * @var string
		 */
		private static $suffix;

		/**
		 * In Footer.
		 *
		 * @since 4.0.0
		 * @var bool
		 */
		private static $in_footer = false;

		/**
		 * Localize the scripts.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $wp_localized_scripts = array();

		/**
		 * Scripts.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $scripts = array();

		/**
		 * Styles.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $styles = array();

		/**
		 * Additional localize scripts.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $additional_localize_scripts = array();

		/**
		 * Additional styles.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $additional_styles = array();

		/**
		 * Class initialization.
		 */
		public static function init() {
			self::$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
			add_action( 'admin_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
			add_action( 'admin_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );

			add_action( 'admin_print_styles', array( __CLASS__, 'localize_printed_styles' ), 5 );
		}

		/**
		 * Register and enqueue admin scripts.
		 *
		 * @since 4.0.0
		 */
		public static function load_scripts() {
			// Register admin scripts and styles.
			self::register_scripts();
			self::register_styles();

			// Enqueue registered scripts and styles.
			self::enqueue_registered_scripts();
			self::enqueue_registered_styles();
		}

		/**
		 * Register all scripts.
		 *
		 * @since 4.0.0
		 */
		private static function register_scripts() {
			$default_scripts = self::get_default_scripts();
			// Returns if there is no scripts to register.
			if ( ! dey_check_is_array( $default_scripts ) ) {
				return;
			}

			foreach ( $default_scripts as $handle => $script ) {
				if ( ! isset( $script['src'] ) ) {
					continue;
				}

				if ( ! $script['can_register'] ) {
					continue;
				}

				$deps      = isset( $script['deps'] ) ? array_merge( array( 'jquery' ), $script['deps'] ) : array( 'jquery' );
				$version   = isset( $script['version'] ) ? $script['version'] : DEY_VERSION;
				$in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : self::$in_footer;
				if ( ! wp_register_script( $handle, $script['src'], $deps, $version, $in_footer ) ) {
					continue;
				}

				self::$scripts[] = $handle;
			}
		}

		/**
		 * Register all styles.
		 *
		 * @since 4.0.0
		 */
		private static function register_styles() {
			$default_styles = self::get_default_styles();
			// Returns if there is no styles to register.
			if ( ! dey_check_is_array( $default_styles ) ) {
				return;
			}

			foreach ( $default_styles as $handle => $style ) {
				if ( ! isset( $style['src'] ) ) {
					continue;
				}

				if ( ! $style['can_register'] ) {
					continue;
				}

				$deps    = isset( $style['deps'] ) ? $style['deps'] : array();
				$version = isset( $style['version'] ) ? $style['version'] : DEY_VERSION;
				$media   = isset( $style['media'] ) ? $style['media'] : 'all';
				$has_rtl = isset( $style['has_rtl'] ) ? $style['has_rtl'] : false;
				if ( ! wp_register_style( $handle, $style['src'], $deps, $version, $media ) ) {
					continue;
				}

				self::$styles[] = $handle;

				if ( $has_rtl ) {
					wp_style_add_data( $handle, 'rtl', 'replace' );
				}
			}
		}

		/**
		 * Get the default scripts to register.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		private static function get_default_scripts() {
			$screen_id  = dey_current_page_screen_id();
			$screen_ids = dey_page_screen_ids();

			/**
			 * This hook is used to alter the admin default register scripts.
			 *
			 * @since 4.0.0
			 */
			return apply_filters(
				'dey_admin_default_register_scripts',
				array(
					'dey-admin'                    => array(
						'src'          => self::get_asset_url( 'assets/js/admin/admin.js' ),
						'deps'         => array( 'jquery-blockui', 'wc-backbone-modal' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-print'                    => array(
						'src'          => self::get_asset_url( 'assets/lib/print/print.min.js' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'jquery-modal'                 => array(
						'src'          => self::get_asset_url( 'assets/lib/jquery-modal/jquery.modal.js' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-product'                  => array(
						'src'          => self::get_asset_url( 'assets/js/admin/product.js' ),
						'deps'         => array( 'jquery-blockui', 'wc-admin-meta-boxes' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-pickup-location'          => array(
						'src'          => self::get_asset_url( 'assets/js/admin/pickup-location.js' ),
						'deps'         => array( 'jquery-blockui', 'wc-admin-meta-boxes' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-pickup-location-criteria' => array(
						'src'          => self::get_asset_url( 'assets/js/admin/pickup-location-criteria.js' ),
						'deps'         => array( 'jquery-blockui', 'wc-admin-meta-boxes' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-scheduler-rule'           => array(
						'src'          => self::get_asset_url( 'assets/js/admin/scheduler-rule.js' ),
						'deps'         => array( 'jquery-blockui', 'wc-admin-meta-boxes' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-scheduler-rule-criteria'  => array(
						'src'          => self::get_asset_url( 'assets/js/admin/scheduler-rule-criteria.js' ),
						'deps'         => array( 'jquery-blockui', 'wc-admin-meta-boxes' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'jquery-ui-timpicker-addon'    => array(
						'src'          => self::get_asset_url( 'assets/lib/timepicker-addon/jquery-ui-timepicker-addon' . self::$suffix . '.js' ),
						'deps'         => array( 'jquery-ui-datepicker' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-enhanced'                 => array(
						'src'          => self::get_asset_url( 'assets/js/dey-enhanced.js' ),
						'deps'         => array( 'select2' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'fullcalender'                 => array(
						'src'          => self::get_asset_url( 'assets/lib/fullcalender/main' . self::$suffix . '.js' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-calender'                 => array(
						'src'          => self::get_asset_url( 'assets/js/admin/calender-enhanced.js' ),
						'deps'         => array( 'jquery-blockui' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'jquery-qtip'                  => array(
						'src'          => self::get_asset_url( 'assets/lib/qtip/jquery.qtip' . self::$suffix . '.js' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
				)
			);
		}

		/**
		 * Get script data.
		 *
		 * @since 4.0.0
		 * @param string $handle
		 * @return array|false
		 */
		public static function get_script_data( $handle ) {
			switch ( $handle ) {
				case 'dey-admin':
					$params = array(
						'print_nonce'               => wp_create_nonce( 'dey-print-nonce' ),
						'or_label'                  => __( 'OR', 'delivery-slots-for-woocommerce' ),
						'and_label'                 => __( 'AND', 'delivery-slots-for-woocommerce' ),
						'duplicate_confirm_msg'     => __( 'Are you sure you want to duplicate?', 'delivery-slots-for-woocommerce' ),
						'delete_confirm_msg'        => __( 'Are you sure you want to delete?', 'delivery-slots-for-woocommerce' ),
						'send_email_confirm_msg'    => __( 'Are you sure you want to send the email notification?', 'delivery-slots-for-woocommerce' ),
						'scheduler_rule_nonce'      => wp_create_nonce( 'dey-scheduler-rule' ),
						'metabox_nonce'             => wp_create_nonce( 'dey-metabox' ),
						'rule_name_empty_error_msg' => __( 'Rule Name cannot be empty', 'delivery-slots-for-woocommerce' ),
					);
					break;

				case 'dey-product':
					$params = array( 'product_nonce' => wp_create_nonce( 'dey-product-nonce' ) );
					break;

				case 'dey-scheduler-rule':
					$params = array(
						'metabox_nonce'              => wp_create_nonce( 'dey-metabox' ),
						'reset_time_slot_msg'        => __( 'Are you sure you want to reset this order count?', 'delivery-slots-for-woocommerce' ),
						'reset_special_day_msg'      => __( 'Are you sure you want to reset this order count?', 'delivery-slots-for-woocommerce' ),
						'name_error_msg'             => __( 'The name field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'time_slot_time_error_msg'   => __( 'From Time or To Time should be set.', 'delivery-slots-for-woocommerce' ),
						'holiday_date_error_msg'     => __( 'From Date or To Date should be set.', 'delivery-slots-for-woocommerce' ),
						'special_day_date_error_msg' => __( 'The date field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'inactive_rule_alert_msg'    => __( 'Saving the Scheduler Rule with Inactive status will have no effect. Do you want to continue?', 'delivery-slots-for-woocommerce' ),
						'order_delivery_days_availability_empty_alert_msg' => __( 'The Number of Days for Delivery Availability field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'order_delivery_time_selector_empty_alert_msg' => __( 'The Minimum or Maximum Time for the Order Delivery Time Selector must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'time_slots_empty_alert_msg' => __( 'Time slots must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'order_delivery_expected_from_date_empty_alert_msg' => __( 'Expected Delivery From field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'order_delivery_expected_to_date_empty_alert_msg' => __( 'Expected Delivery To field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'order_pickup_days_availability_empty_alert_msg' => __( 'The Number of Days for Pickup Availability field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'order_pickup_time_selector_empty_alert_msg' => __( 'The Minimum or Maximum Time for the Order Pickup Time Selector must not be left blank.', 'delivery-slots-for-woocommerce' ),
					);
					break;

				case 'dey-pickup-location':
					$params = array(
						'metabox_nonce'              => wp_create_nonce( 'dey-metabox' ),
						'reset_time_slot_msg'        => __( 'Are you sure you want to reset this order count?', 'delivery-slots-for-woocommerce' ),
						'reset_special_day_msg'      => __( 'Are you sure you want to reset this order count?', 'delivery-slots-for-woocommerce' ),
						'name_error_msg'             => __( 'The name field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'time_slot_time_error_msg'   => __( 'From Time or To Time should be set.', 'delivery-slots-for-woocommerce' ),
						'holiday_date_error_msg'     => __( 'From Date or To Date should be set.', 'delivery-slots-for-woocommerce' ),
						'special_day_date_error_msg' => __( 'The date field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'inactive_rule_alert_msg'    => __( 'Saving the Pickup Location with Inactive status will have no effect. Do you want to continue?', 'delivery-slots-for-woocommerce' ),
						'order_pickup_days_availability_empty_alert_msg' => __( 'The Number of Days for Pickup Availability field must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'order_pickup_time_selector_empty_alert_msg' => __( 'The Minimum or Maximum Time for the Pickup Time Selector must not be left blank.', 'delivery-slots-for-woocommerce' ),
						'time_slots_empty_alert_msg' => __( 'Time slots must not be left blank.', 'delivery-slots-for-woocommerce' ),
					);
					break;

				case 'dey-enhanced':
					$params = array(
						'i18n_no_matches'           => __( 'No matches found', 'delivery-slots-for-woocommerce' ),
						'i18n_input_too_short_1'    => __( 'Please enter 1 or more characters', 'delivery-slots-for-woocommerce' ),
						'i18n_input_too_short_n'    => __( 'Please enter %qty% or more characters', 'delivery-slots-for-woocommerce' ),
						'i18n_input_too_long_1'     => __( 'Please delete 1 character', 'delivery-slots-for-woocommerce' ),
						'i18n_input_too_long_n'     => __( 'Please delete %qty% characters', 'delivery-slots-for-woocommerce' ),
						'i18n_selection_too_long_1' => __( 'You can only select 1 item', 'delivery-slots-for-woocommerce' ),
						'i18n_selection_too_long_n' => __( 'You can only select %qty% items', 'delivery-slots-for-woocommerce' ),
						'i18n_load_more'            => __( 'Loading more results&hellip;', 'delivery-slots-for-woocommerce' ),
						'i18n_searching'            => __( 'Searching&hellip;', 'delivery-slots-for-woocommerce' ),
						'search_nonce'              => wp_create_nonce( 'dey-search-nonce' ),
						'ajaxurl'                   => DEY_ADMIN_AJAX_URL,
						'calendar_image'            => WC()->plugin_url() . '/assets/images/calendar.png',
						'date_format'               => dey_convert_wp_date_format_php_to_jquery(),
					);
					break;

				case 'dey-calender':
					$params = array(
						'calender_events_nonce' => wp_create_nonce( 'dey-calender-events-nonce' ),
						'holidays'              => DEY_Order_Delivery_Handler::get_holidays(),
						'code'                  => str_replace( '_', '-', strtolower( determine_locale() ) ),
						'week'                  => array(
							'dow' => absint( get_option( 'start_of_week' ) ),
							'doy' => 4,
						),
						'direction'             => is_rtl() ? 'rtl' : 'ltr',
						'buttonText'            => array(
							'prev'  => __( 'Prev', 'delivery-slots-for-woocommerce' ),
							'next'  => __( 'Next', 'delivery-slots-for-woocommerce' ),
							'today' => __( 'Today', 'delivery-slots-for-woocommerce' ),
							'year'  => __( 'Year', 'delivery-slots-for-woocommerce' ),
							'month' => __( 'Month', 'delivery-slots-for-woocommerce' ),
							'week'  => __( 'Week', 'delivery-slots-for-woocommerce' ),
							'day'   => __( 'Day', 'delivery-slots-for-woocommerce' ),
							'list'  => __( 'List', 'delivery-slots-for-woocommerce' ),
						),
						'weekText'              => __( 'W', 'delivery-slots-for-woocommerce' ),
						'allDayText'            => __( 'all-day', 'delivery-slots-for-woocommerce' ),
						'moreLinkText'          => __( 'more', 'delivery-slots-for-woocommerce' ),
						'noEventsText'          => __( 'No events to display', 'delivery-slots-for-woocommerce' ),
					);
					break;

				default:
					$params = false;
					break;
			}

			return $params;
		}

		/**
		 * Get the default styles to register.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		private static function get_default_styles() {
			$screen_id  = dey_current_page_screen_id();
			$screen_ids = dey_page_screen_ids();

			/**
			 * This hook is used to alter the admin default register styles.
			 *
			 * @since 4.0.0
			 */
			return apply_filters(
				'dey_admin_default_register_styles',
				array(
					'dey-admin'                  => array(
						'src'          => self::get_asset_url( 'assets/css/admin.css' ),
						'deps'         => array( 'wc-admin-layout' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'jquery-ui-datepicker-addon' => array(
						'src'          => self::get_asset_url( 'assets/lib/timepicker-addon/jquery-ui-timepicker-addon' . self::$suffix . '.css' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'jquery-modal'               => array(
						'src'          => self::get_asset_url( 'assets/lib/jquery-modal/jquery.modal.css' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'dey-print'                  => array(
						'src'          => self::get_asset_url( 'assets/lib/print/print.min.css' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'fullcalender'               => array(
						'src'          => self::get_asset_url( 'assets/lib/fullcalender/main' . self::$suffix . '.css' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
					'jquery-qtip'                => array(
						'src'          => self::get_asset_url( 'assets/lib/qtip/jquery.qtip' . self::$suffix . '.css' ),
						'can_register' => in_array( $screen_id, $screen_ids ),
					),
				)
			);
		}

		/**
		 * Enqueue all registered scripts.
		 *
		 * @since 4.0.0
		 */
		private static function enqueue_registered_scripts() {
			foreach ( self::$scripts as $handle ) {
				self::enqueue_script( $handle );
			}
		}

		/**
		 * Enqueue script.
		 *
		 * @param string $handle
		 * @since 4.0.0
		 */
		public static function enqueue_script( $handle ) {
			if ( ! wp_script_is( $handle, 'registered' ) ) {
				return;
			}

			wp_enqueue_script( $handle );
		}

		/**
		 * Enqueue all registered styles.
		 *
		 * @since 4.0.0
		 */
		private static function enqueue_registered_styles() {
			foreach ( self::$styles as $handle ) {
				self::enqueue_style( $handle );
			}
		}

		/**
		 * Print the localized styles when registered handle.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function localize_printed_styles() {
			foreach ( self::$additional_styles as $handle ) {
				self::enqueue_style( $handle );
			}
		}

		/**
		 * Enqueue style.
		 *
		 * @param string $handle
		 * @since 4.0.0
		 */
		public static function enqueue_style( $handle ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				return;
			}

			wp_enqueue_style( $handle );
		}

		/**
		 * Set the localize the scripts.
		 *
		 * @since 4.0.0
		 * @param string $handle
		 * @param string $object_name
		 * @param array  $l10n
		 * @return void
		 */
		public static function set_additional_localize_scripts( $handle, $object_name, $l10n ) {
			self::$additional_localize_scripts[ $handle ] = array(
				'object_name' => $object_name,
				'l10n'        => $l10n,
			);
		}

		/**
		 * Print the localized scripts when registered handle.
		 *
		 * @since 3.2.0
		 * @return void
		 */
		public static function localize_printed_scripts() {
			foreach ( self::$scripts as $handle ) {
				self::localize_script( $handle );
			}

			foreach ( self::$additional_localize_scripts as $handle => $data ) {
				if ( ! wp_script_is( $handle ) ) {
					continue;
				}

				wp_localize_script( $handle, $data['object_name'], $data['l10n'] );
			}

			dey_localize_jquery_ui_timepicker_addon();
		}

		/**
		 * Set the additional styles.
		 *
		 * @since 4.0.0
		 * @param string $handle
		 * @return void
		 */
		public static function set_additional_styles( $handle ) {
			self::$additional_styles[] = $handle;
		}

		/**
		 * Localize the enqueued script.
		 *
		 * @since 4.0.0
		 * @param string $handle
		 * @return null
		 */
		public static function localize_script( $handle ) {
			// Return if already localized script or not enqueued script.
			if ( in_array( $handle, self::$wp_localized_scripts, true ) || ! wp_script_is( $handle ) ) {
				return;
			}

			// Get the data for current script.
			$data = self::get_script_data( $handle );
			if ( ! $data ) {
				return;
			}

			$name = str_replace( '-', '_', $handle ) . '_params';

			/**
			 * This hook is used to alter the script data.
			 *
			 * @since 4.0.0
			 */
			if ( wp_localize_script( $handle, $name, apply_filters( $name, $data ) ) ) {
				self::$wp_localized_scripts[] = $handle;
			}
		}

		/**
		 * Get asset URL.
		 *
		 * @since 4.0.0
		 * @param string $path Assets path.
		 * @return string
		 */
		private static function get_asset_url( $path ) {
			/**
			 * This hook is used to alter the admin asset URL.
			 *
			 * @since 4.0.0
			 */
			return apply_filters( 'dey_admin_get_asset_url', DEY_PLUGIN_URL . '/' . $path, $path );
		}
	}

	DEY_Admin_Assets::init();
}
