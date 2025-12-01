<?php
/**
 * Delivery and Pickup Scheduler for WooCommerce Main Class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'FP_Delivery_Slots' ) ) {

	/**
	 * Main Class.
	 * */
	final class FP_Delivery_Slots {

		/**
		 * Version.
		 *
		 * @var string
		 * */
		private $version = '4.5.0';

		/**
		 * Locale.
		 *
		 * @var string
		 * */
		private $locale = 'delivery-slots-for-woocommerce';

		/**
		 * Folder Name.
		 *
		 * @var string
		 * */
		private $folder_name = 'delivery-slots-for-woocommerce';

		/**
		 * WC minimum version.
		 *
		 * @var string
		 */
		public static $wc_minimum_version = '3.5.0';

		/**
		 * WP minimum version.
		 *
		 * @var string
		 */
		public static $wp_minimum_version = '4.6.0';

		/**
		 * The single instance of the class.
		 *
		 * @var Object
		 * */
		protected static $_instance = null;

		/**
		 * Order Tip Fee name.
		 *
		 * @since 3.7.0
		 * @var string
		 */
		private $order_tip_fee_name = 'dey_order_tip';

		/**
		 * Notifications.
		 *
		 * @var array
		 * */
		protected $notifications;

		/**
		 * Load the Class in Single Instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Cloning has been forbidden.
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, 'You are not allowed to perform this action!!!', esc_html( $this->version ) );
		}

		/**
		 * Unserialize the class data has been forbidden.
		 * */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, 'You are not allowed to perform this action!!!', esc_html( $this->version ) );
		}

		/**
		 * Constructor.
		 * */
		public function __construct() {
			$this->define_constants();
			$this->include_files();
			$this->init_hooks();
		}

		/**
		 * Load the plugin current language MO file.
		 * */
		private function load_plugin_textdomain() {
			$locale = determine_locale();
			/**
			 * This hook is used to alter the plugin locale.
			 *
			 * @since 1.0
			 */
			$locale = apply_filters( 'plugin_locale', $locale, DEY_LOCALE );

			// Unload the text domain if other plugins/themes loaded the same text domain by mistake.
			unload_textdomain( DEY_LOCALE );

			// Load the text domain from the "wp-content" languages folder. we have handles the plugin folder in languages folder for easily handle it.
			load_textdomain( DEY_LOCALE, WP_LANG_DIR . '/' . DEY_FOLDER_NAME . '/' . DEY_LOCALE . '-' . $locale . '.mo' );

			// Load the text domain from the current plugin languages folder.
			load_plugin_textdomain( DEY_LOCALE, false, dirname( plugin_basename( DEY_PLUGIN_FILE ) ) . '/languages' );
		}

		/**
		 * Prepare the constants value array.
		 * */
		private function define_constants() {

			$constant_array = array(
				'DEY_VERSION'        => $this->version,
				'DEY_LOCALE'         => $this->locale,
				'DEY_FOLDER_NAME'    => $this->folder_name,
				'DEY_ABSPATH'        => dirname( DEY_PLUGIN_FILE ) . '/',
				'DEY_ADMIN_URL'      => admin_url( 'admin.php' ),
				'DEY_ADMIN_AJAX_URL' => admin_url( 'admin-ajax.php' ),
				'DEY_PLUGIN_SLUG'    => plugin_basename( DEY_PLUGIN_FILE ),
				'DEY_PLUGIN_PATH'    => untrailingslashit( plugin_dir_path( DEY_PLUGIN_FILE ) ),
				'DEY_PLUGIN_URL'     => untrailingslashit( plugins_url( '/', DEY_PLUGIN_FILE ) ),
			);

			/**
			 * This hook is used to alter the define constants.
			 *
			 * @since 1.0
			 */
			$constant_array = apply_filters( 'dey_define_constants', $constant_array );

			if ( is_array( $constant_array ) && ! empty( $constant_array ) ) {
				foreach ( $constant_array as $name => $value ) {
					$this->define_constant( $name, $value );
				}
			}
		}

		/**
		 * Define the Constants value.
		 * */
		private function define_constant( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required files.
		 * */
		private function include_files() {

			// Function.
			include_once DEY_ABSPATH . 'inc/dey-common-functions.php';
			include_once DEY_ABSPATH . 'inc/admin/dey-admin-functions.php';
			include_once DEY_ABSPATH . 'inc/dey-frontend-functions.php';

			// Abstract classes.
			include_once DEY_ABSPATH . 'inc/abstracts/abstract-dey-post.php';

			include_once DEY_ABSPATH . 'inc/class-dey-register-post-types.php';
			include_once DEY_ABSPATH . 'inc/class-dey-register-post-status.php';

			include_once DEY_ABSPATH . 'inc/class-dey-install.php';
			include_once DEY_ABSPATH . 'inc/privacy/class-dey-privacy.php';
			include_once DEY_ABSPATH . 'inc/class-dey-date-time.php';
			include_once DEY_ABSPATH . 'inc/class-dey-query.php';
			include_once DEY_ABSPATH . 'inc/class-dey-updates.php';

			// Instances.
			include_once DEY_ABSPATH . 'inc/notifications/class-dey-notification-instances.php';

			// Compatibility.
			include_once DEY_ABSPATH . 'inc/compatibility/class-dey-compatibility-instances.php';

			// Entity.
			include_once DEY_ABSPATH . 'inc/entity/class-dey-product-delivery.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-product-local-pickup.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-order-delivery.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-order-local-pickup.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-time-slot.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-holiday.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-special-day.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-order-tip.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-pickup-location.php';
			include_once DEY_ABSPATH . 'inc/entity/class-dey-scheduler-rule.php';

			// Handler.
			include_once DEY_ABSPATH . 'inc/class-dey-session-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-order-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-order-delivery-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-scheduler-rule-order-local-pickup-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-pickup-location-order-local-pickup-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-product-delivery-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-product-local-pickup-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-cron-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-pickup-location-filter-handler.php';
			include_once DEY_ABSPATH . 'inc/class-dey-product-data-store.php';
			include_once DEY_ABSPATH . 'inc/validator/class-dey-scheduler-rule-validator.php';
			include_once DEY_ABSPATH . 'inc/validator/class-dey-order-pickup-location-validator.php';

			// Block compatibility.
			include_once DEY_ABSPATH . 'inc/wc-blocks/class-dey-wc-blocks-compatibility.php';

			if ( is_admin() ) {
				$this->include_admin_files();
			}

			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
				$this->include_frontend_files();
			}
		}

		/**
		 * Include admin files.
		 * */
		private function include_admin_files() {
			include_once DEY_ABSPATH . 'inc/admin/class-dey-admin-assets.php';
			include_once DEY_ABSPATH . 'inc/admin/class-dey-admin-ajax.php';
			include_once DEY_ABSPATH . 'inc/admin/menu/class-dey-menu-management.php';
			include_once DEY_ABSPATH . 'inc/admin/menu/class-dey-calender.php';
			include_once DEY_ABSPATH . 'inc/admin/class-dey-product-settings.php';
			include_once DEY_ABSPATH . 'inc/admin/class-dey-admin-post-type-handler.php';
		}

		/**
		 * Include frontend files.
		 * */
		private function include_frontend_files() {
			include_once DEY_ABSPATH . 'inc/frontend/class-dey-frontend-assets.php';
			include_once DEY_ABSPATH . 'inc/frontend/class-dey-frontend.php';
			include_once DEY_ABSPATH . 'inc/frontend/class-dey-cart-handler.php';
			include_once DEY_ABSPATH . 'inc/frontend/class-dey-shortcodes.php';
			include_once DEY_ABSPATH . 'inc/frontend/class-dey-scheduler-rule-checkout-fields-validator.php';
			include_once DEY_ABSPATH . 'inc/frontend/class-dey-pickup-location-checkout-fields-validator.php';
			include_once DEY_ABSPATH . 'inc/frontend/class-dey-checkout-fields-validator.php';
		}

		/**
		 * Define the hooks.
		 * */
		private function init_hooks() {
			// Init the plugin.
			add_action( 'init', array( $this, 'init' ), 0 );
			// Plugins loaded.
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
			// Register the plugin.
			register_activation_hook( DEY_PLUGIN_FILE, array( 'DEY_Install', 'install' ) );
			// WC compatibility to the plugin.
			add_action( 'before_woocommerce_init', array( $this, 'declare_WC_compatibility' ) );
		}

		/**
		 * Declare the plugin is compatibility with WC features.
		 *
		 * @since 3.7.0
		 */
		public function declare_WC_compatibility() {
			// HPOS compatibility.
			$this->declare_WC_HPOS_compatibility();

			// Block compatibility.
			$this->declare_WC_Block_compatibility();
		}

		/**
		 * Declare the plugin is compatibility with WC HPOS.
		 *
		 * @since 3.2.0
		 */
		public function declare_WC_HPOS_compatibility() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', DEY_PLUGIN_FILE, true );
			}
		}

		/**
		 * Declare the plugin is compatibility with WC block.
		 *
		 * @since 3.7.0
		 */
		public function declare_WC_Block_compatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', DEY_PLUGIN_FILE, true );
			}
		}

		/**
		 * Init.
		 *
		 * @return void
		 * */
		public function init() {
			$this->load_plugin_textdomain();

			$this->notifications = DEY_Notification_Instances::get_notifications();
		}

		/**
		 * Plugins Loaded.
		 * */
		public function plugins_loaded() {
			/**
			 * This hook is used to do extra action before plugin loaded.
			 *
			 * @since 1.0
			 */
			do_action( 'dey_before_plugin_loaded' );

			DEY_Compatibility_Instances::instance();

			/**
			 * This hook is used to do extra action after plugin loaded.
			 *
			 * @since 1.0
			 */
			do_action( 'dey_after_plugin_loaded' );
		}

		/**
		 * Templates.
		 * */
		public function templates() {
			return DEY_PLUGIN_PATH . '/templates/';
		}

		/**
		 * Notifications.
		 * */
		public function notifications() {
			return $this->notifications;
		}

		/**
		 * Get the menu name.
		 *
		 * @return string
		 * */
		public function menu_name() {
			return __( 'Delivery and Pickup Scheduler', 'delivery-slots-for-woocommerce' );
		}

		/**
		 * Get the order tip fee name.
		 *
		 * @since 3.7.0
		 * @return string
		 * */
		public function order_tip_fee_name() {
			return $this->order_tip_fee_name;
		}
	}

}
