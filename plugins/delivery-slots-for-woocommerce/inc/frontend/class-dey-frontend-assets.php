<?php
/**
 * Frontend Assets.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Frontend_Assets' ) ) {

	/**
	 * Class.
	 */
	class DEY_Frontend_Assets {

		/**
		 * Suffix.
		 *
		 * @var string
		 */
		private static $suffix;

		/**
		 * In Footer.
		 *
		 * @var bool
		 */
		private static $in_footer = false;

		/**
		 * Localize the scripts.
		 *
		 * @since 3.2.0
		 * @var array
		 */
		private static $wp_localized_scripts = array();

		/**
		 * Scripts.
		 *
		 * @since 3.9.1
		 * @var array
		 */
		private static $scripts = array();

		/**
		 * Styles.
		 *
		 * @since 3.9.1
		 * @var array
		 */
		private static $styles = array();

		/**
		 * Additional localize scripts.
		 *
		 * @since 3.9.1
		 * @var array
		 */
		private static $additional_localize_scripts = array();

		/**
		 * Additional localize scripts.
		 *
		 * @since 3.9.1
		 * @var array
		 */
		private static $additional_scripts = array();

		/**
		 * Additional styles.
		 *
		 * @since 3.9.1
		 * @var array
		 */
		private static $additional_styles = array();

		/**
		 * Default enqueue scripts.
		 *
		 * @since 3.9.1
		 * @var array
		 */
		private static $default_enqueue_scripts = array( 'dey-frontend' );

		/**
		 * Default enqueue styles.
		 *
		 * @since 3.9.1
		 * @var array
		 */
		private static $default_enqueue_styles = array( 'dey-frontend', 'jquery-ui-datepicker-addon' );

		/**
		 * Class Initialization.
		 */
		public static function init() {
			self::$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Enqueue script in footer.
			if ( '2' == get_option( 'dey_advanced_frontend_enqueue_scripts_type' ) ) {
				self::$in_footer = true;
			}

			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
			add_action( 'wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
			add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );

			add_action( 'wp_print_styles', array( __CLASS__, 'localize_printed_styles' ), 5 );
		}

		/**
		 * Register and enqueue frontend scripts.
		 *
		 * @since 3.9.1
		 */
		public static function load_scripts() {
			self::register_scripts();
			self::register_styles();

			self::enqueue_default_scripts();
			self::enqueue_default_styles();

			self::add_inline_style();
		}

		/**
		 * Register all scripts.
		 *
		 * @since 3.9.1
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
		 * @since 3.9.1
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
		 * @since 3.9.1
		 * @return array
		 */
		private static function get_default_scripts() {
			/**
			 * This hook is used to alter the default register scripts.
			 *
			 * @since 3.9.1
			 */
			return apply_filters(
				'dey_default_register_scripts',
				array(
					'dey-frontend'                        => array(
						'src'  => self::get_asset_url( 'assets/js/frontend/frontend.js' ),
						'deps' => array( 'jquery-blockui' ),
					),
					'jquery-ui-timpicker-addon'           => array(
						'src'  => self::get_asset_url( 'assets/lib/timepicker-addon/jquery-ui-timepicker-addon' . self::$suffix . '.js' ),
						'deps' => array( 'jquery-ui-datepicker', 'jquery-ui-slider' ),
					),
					'dey-order-delivery'                  => array(
						'src'  => self::get_asset_url( 'assets/js/frontend/order-delivery-datepicker.js' ),
						'deps' => array( 'jquery-blockui', 'jquery-ui-datepicker', 'jquery-ui-slider' ),
					),
					'dey-order-local-pickup'              => array(
						'src'  => self::get_asset_url( 'assets/js/frontend/order-local-pickup-datepicker.js' ),
						'deps' => array( 'jquery-blockui', 'jquery-ui-datepicker', 'jquery-ui-slider' ),
					),
					'dey-product-delivery-datepicker'     => array(
						'src'  => self::get_asset_url( 'assets/js/frontend/product-delivery-datepicker.js' ),
						'deps' => array( 'jquery-blockui', 'jquery-ui-datepicker', 'jquery-ui-slider' ),
					),
					'dey-product-local-pickup-datepicker' => array(
						'src'  => self::get_asset_url( 'assets/js/frontend/product-local-pickup-datepicker.js' ),
						'deps' => array( 'jquery-blockui', 'jquery-ui-datepicker', 'jquery-ui-slider' ),
					),
				)
			);
		}

		/**
		 * Get script data.
		 *
		 * @since 3.9.1
		 * @param string $handle
		 * @return array|false
		 */
		public static function get_script_data( $handle ) {
			$params = array();
			switch ( $handle ) {
				case 'dey-frontend':
					$params = array(
						'ajax_url'                 => DEY_ADMIN_AJAX_URL,
						'datepicker_nonce'         => wp_create_nonce( 'dey-datepicker' ),
						'order_tip_nonce'          => wp_create_nonce( 'dey-order-tip' ),
						'order_tip_display_type'   => get_option( 'dey_order_tip_display_type' ),
						'order_scheduler_nonce'    => wp_create_nonce( 'dey-order-scheduler' ),
						'is_block_cart'            => dey_is_block_cart(),
						'is_block_checkout'        => dey_is_block_checkout(),
						'selected_shipping_method' => dey_get_chosen_shipping_method_id(),
						'selected_country' => dey_get_chosen_billing_country_code(),
					);
					break;

				case 'dey-product-delivery-datepicker':
				case 'dey-product-local-pickup-datepicker':
				case 'dey-order-delivery':
				case 'dey-order-local-pickup':
					dey_localize_jquery_ui_datepicker();
					dey_localize_jquery_ui_timepicker_addon();
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
		 * @since 3.9.1
		 * @return array
		 */
		private static function get_default_styles() {
			/**
			 * This hook is used to alter the default register styles.
			 *
			 * @since 3.9.1
			 */
			return apply_filters(
				'dey_default_register_styles',
				array(
					'dey-frontend'               => array(
						'src' => self::get_asset_url( 'assets/css/frontend.css' ),
					),
					'dey-jquery-ui'              => array(
						'src' => self::get_asset_url( 'assets/lib/jquery-ui/themes/' . get_option( 'dey_advanced_calender_theme', 'base' ) . '/jquery-ui' . self::$suffix . '.css' ),
					),
					'jquery-ui-datepicker-addon' => array(
						'src' => self::get_asset_url( 'assets/lib/timepicker-addon/jquery-ui-timepicker-addon' . self::$suffix . '.css' ),
					),
				)
			);
		}

		/**
		 * Set the additional scripts.
		 *
		 * @since 3.9.1
		 * @param string $handle
		 * @param string $object_name
		 * @param array  $l10n
		 * @return void
		 */
		public static function set_additional_scripts( $handle ) {
			self::$additional_scripts[] = $handle;
		}

		/**
		 * Set the additional localize scripts.
		 *
		 * @since 3.9.1
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
		 * Set the additional styles.
		 *
		 * @since 3.9.1
		 * @param string $handle
		 * @return void
		 */
		public static function set_additional_styles( $handle ) {
			self::$additional_styles[] = $handle;
		}

		/**
		 * Print the localized scripts when registered handle.
		 *
		 * @since 3.2.0
		 * @return void
		 */
		public static function localize_printed_scripts() {
			foreach ( self::$additional_scripts as $handle ) {
				self::enqueue_script( $handle );
			}

			foreach ( self::$scripts as $handle ) {
				self::localize_script( $handle );
			}

			foreach ( self::$additional_localize_scripts as $handle => $data ) {
				if ( ! wp_script_is( $handle ) ) {
					continue;
				}

				wp_localize_script( $handle, $data['object_name'], $data['l10n'] );
			}
		}

		/**
		 * Print the localized styles when registered handle.
		 *
		 * @since 3.9.1
		 * @return void
		 */
		public static function localize_printed_styles() {
			foreach ( self::$additional_styles as $handle ) {
				self::enqueue_style( $handle );
			}
		}

		/**
		 * Add Inline style.
		 */
		public static function add_inline_style() {
			$contents = '.dey-calender-day a, .dey-calender-available-dot:before {
                background: ' . get_option( 'dey_advanced_calender_day_bg_color', '#00cc66' ) . ' !important;
                border: 1px solid ' . get_option( 'dey_advanced_calender_day_bg_color', '#00cc66' ) . ' !important;
                color: ' . get_option( 'dey_advanced_calender_day_color', '#000000' ) . ' !important;
                }
                
                .dey-holiday .ui-state-default, .dey-calender-holiday-dot:before {
                background: ' . get_option( 'dey_advanced_holiday_bg_color', '#dd9944' ) . ' !important;
                border: 1px solid ' . get_option( 'dey_advanced_holiday_bg_color', '#dd9944' ) . ' !important;
                color: ' . get_option( 'dey_advanced_holiday_color', '#000000' ) . ' !important;
                }
                
                .dey-partial-booked a, .dey-calender-partial-booked-dot:before {
                background: ' . get_option( 'dey_advanced_partial_booked_bg_color', '#ffcc00' ) . ' !important;
                border: 1px solid ' . get_option( 'dey_advanced_partial_booked_bg_color', '#ffcc00' ) . ' !important;
                color: ' . get_option( 'dey_advanced_partial_booked_color', '#000000' ) . ' !important;
                }
                
                .dey-booked .ui-state-default, .dey-calender-booked-dot:before {
                background: ' . get_option( 'dey_advanced_booked_bg_color', '#ff3300' ) . ' !important;
                border: 1px solid ' . get_option( 'dey_advanced_booked_bg_color', '#ff3300' ) . ' !important;
                color: ' . get_option( 'dey_advanced_booked_color', '#000000' ) . ' !important;
                }
                
                .dey-order-expected-delivery-info-wrapper, .dey-product-expected-delivery-info-wrapper {
                color: ' . get_option( 'dey_advanced_expected_delivery_info_color', '#6d6d6d' ) . ' !important;
                }
                
                .dey-order-tip-wrapper .dey-order-tip-predefined-button {
                background: ' . get_option( 'dey_order_tip_predefined_button_bg_color', '#eeeeee' ) . ' !important;
                border: 1px solid ' . get_option( 'dey_order_tip_predefined_button_border_color', '#eeeeee' ) . ' !important;
                color: ' . get_option( 'dey_order_tip_predefined_button_font_color', '#333333' ) . ' !important;
                }
                
                .dey-order-tip-wrapper .dey-active {
                background: ' . get_option( 'dey_order_tip_active_predefined_button_bg_color', '#33a8ce' ) . ' !important;
                border: 1px solid ' . get_option( 'dey_order_tip_active_predefined_button_border_color', '#33a8ce' ) . ' !important;
                color: ' . get_option( 'dey_order_tip_active_predefined_button_font_color', '#ffffff' ) . ' !important;
                }
                
                .dey-order-tip-wrapper .dey-order-tip-custom-amount-button {
                background: ' . get_option( 'dey_order_tip_custom_add_tip_button_bg_color', '#52ce33' ) . ' !important;
                border: 1px solid ' . get_option( 'dey_order_tip_custom_add_tip_button_border_color', '#52ce33' ) . ' !important;
                color: ' . get_option( 'dey_order_tip_custom_add_tip_button_font_color', '#ffffff' ) . ' !important;
                }' . get_option( 'dey_advanced_custom_css', '' );

			if ( ! $contents ) {
				return;
			}

			wp_register_style('dey-inline-style', false, array(), DEY_VERSION); // phpcs:ignore
			wp_enqueue_style( 'dey-inline-style' );

			// Add custom css as inline style.
			wp_add_inline_style( 'dey-inline-style', $contents );
		}

		/**
		 * Enqueue default scripts.
		 *
		 * @since 3.9.1
		 */
		private static function enqueue_default_scripts() {
			foreach ( self::$default_enqueue_scripts as $handle ) {
				self::enqueue_script( $handle );
			}
		}

		/**
		 * Enqueue all registered scripts.
		 *
		 * @since 3.9.1
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
		 * @since 3.9.1
		 */
		public static function enqueue_script( $handle ) {
			if ( ! dey_check_is_array(self::$scripts) ) {
				self::register_scripts();
			}

			if ( ! wp_script_is( $handle, 'registered' ) ) {
				return;
			}

			wp_enqueue_script( $handle );
		}

		/**
		 * Enqueue default styles.
		 *
		 * @since 3.9.1
		 */
		private static function enqueue_default_styles() {
			foreach ( self::$default_enqueue_styles as $handle ) {
				self::enqueue_style( $handle );
			}
		}

		/**
		 * Enqueue all registered styles.
		 *
		 * @since 3.9.1
		 */
		private static function enqueue_registered_styles() {
			foreach ( self::$styles as $handle ) {
				self::enqueue_style( $handle );
			}
		}

		/**
		 * Enqueue style.
		 *
		 * @param string $handle
		 * @since 3.9.1
		 */
		public static function enqueue_style( $handle ) {
			if ( ! dey_check_is_array(self::$styles) ) {
				self::register_styles();
			}
			
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				return;
			}

			wp_enqueue_style( $handle );
		}

		/**
		 * Localize the enqueued script.
		 *
		 * @since 3.9.1
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
			 * @since 3.9.1
			 */
			if ( wp_localize_script( $handle, $name, apply_filters( $name, $data ) ) ) {
				self::$wp_localized_scripts[] = $handle;
			}
		}

		/**
		 * Get asset URL.
		 *
		 * @since 3.9.1
		 * @param string $path Assets path.
		 * @return string
		 */
		private static function get_asset_url( $path ) {
			/**
			 * This hook is used to alter the asset URL.
			 *
			 * @since 3.9.1
			 */
			return apply_filters( 'dey_get_asset_url', DEY_PLUGIN_URL . '/' . $path, $path );
		}
	}

	DEY_Frontend_Assets::init();
}
