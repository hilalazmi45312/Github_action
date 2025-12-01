<?php
/**
 * Initialize the plugin.
 *
 * @package Extra Fees for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Extra_Fees' ) ) {

	/**
	 * EFW_Extra_Fees Class.
	 */
	final class EFW_Extra_Fees {

		/**
		 * EFW_Extra_Fees Version.
		 */
		public $version = '7.0.0';

		/**
		 * The single instance of the class.
		 */
		protected static $_instance = null;

		/**
		 * Load EFW_Extra_Fees Class in Single Instance.
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
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, 'You are not allowed to perform this action!!!', esc_html( $this->version ) );
		}

		/**
		 * EFW_Extra_Fees Class Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->include_files();
			$this->init_hooks();
		}

		/**
		 * Initialize the Translate Files.
		 */
		private function load_plugin_textdomain() {
			if ( function_exists( 'determine_locale' ) ) {
				$locale = determine_locale();
			} else {
				// @todo Remove when start supporting WP 5.0 or later.
				$locale = is_admin() ? get_user_locale() : get_locale();
			}
						/**
						 * Hook:plugin_locale.
						 *
						 * @since 1.0
						 */
			$locale = apply_filters( 'plugin_locale', $locale, 'extra-fees-for-woocommerce' );

			unload_textdomain( 'extra-fees-for-woocommerce' );
			load_textdomain( 'extra-fees-for-woocommerce', WP_LANG_DIR . '/extra-fees-for-woocommerce/extra-fees-for-woocommerce-' . $locale . '.mo' );
			load_textdomain( 'extra-fees-for-woocommerce', WP_LANG_DIR . '/plugins/extra-fees-for-woocommerce-' . $locale . '.mo' );
			load_plugin_textdomain( 'extra-fees-for-woocommerce', false, dirname( plugin_basename( EFW_PLUGIN_FILE ) ) . '/languages' );
		}

		/**
		 * Prepare the Constants value array.
		 */
		private function define_constants() {
			$protocol = 'http://';

			if ( 
				( isset( $_SERVER['HTTPS'] ) && ( ( 'on' == $_SERVER['HTTPS'] ) || ( 1 == $_SERVER['HTTPS'] ) ) )
				|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) )
			) {
				$protocol = 'https://';
			}

			global $wpdb;
			$constant_array = array(
				'EFW_VERSION'        => $this->version,
				'EFW_FOLDER_NAME'    => 'extra-fees-for-woocommerce',
				'EFW_PROTOCOL'       => $protocol,
				'EFW_ABSPATH'        => dirname( EFW_PLUGIN_FILE ) . '/',
				'EFW_ADMIN_URL'      => admin_url( 'admin.php' ),
				'EFW_ADMIN_AJAX_URL' => admin_url( 'admin-ajax.php' ),
				'EFW_PLUGIN_SLUG'    => plugin_basename( EFW_PLUGIN_FILE ),
				'EFW_PLUGIN_PATH'    => untrailingslashit( plugin_dir_path( EFW_PLUGIN_FILE ) ),
				'EFW_PLUGIN_URL'     => untrailingslashit( plugins_url( '/', EFW_PLUGIN_FILE ) ),
			);
						/**
						 * Hook:efw_define_constants.
						 *
						 * @since 1.0
						 */
			$constant_array = apply_filters( 'efw_define_constants', $constant_array );

			if ( is_array( $constant_array ) && ! empty( $constant_array ) ) {
				foreach ( $constant_array as $name => $value ) {
					$this->define_constant( $name, $value );
				}
			}
		}

		/**
		 * Define the Constants value.
		 */
		private function define_constant( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required files.
		 */
		private function include_files() {
			// Function
			include_once EFW_ABSPATH . 'inc/efw-common-functions.php';

			// Abstract
			include_once EFW_ABSPATH . 'inc/abstracts/class-efw-post.php';
			include_once EFW_ABSPATH . 'inc/abstracts/abstract-efw-action-scheduler.php';
			include_once EFW_ABSPATH . 'inc/class-efw-register-post-type.php';

			// Entity
			include_once EFW_ABSPATH . 'inc/entity/class-efw-fees.php';
			include_once EFW_ABSPATH . 'inc/entity/class-efw-rules.php';
			include_once EFW_ABSPATH . 'inc/entity/class-efw-additional-fee-rules.php';

			include_once EFW_ABSPATH . 'inc/compatibility/class-efw-compatibility-instances.php';

			include_once EFW_ABSPATH . 'inc/class-efw-wc-log.php';

			include_once EFW_ABSPATH . 'inc/action-scheduler/class-efw-action-scheduler-instances.php';

			include_once EFW_ABSPATH . 'inc/class-efw-install.php';
			include_once EFW_ABSPATH . 'inc/privacy/class-efw-privacy.php';
			include_once EFW_ABSPATH . 'inc/class-efw-date-time.php';
			include_once EFW_ABSPATH . 'inc/class-efw-updates.php';

			include_once EFW_ABSPATH . 'inc/class-efw-fee-validation.php';
			include_once EFW_ABSPATH . 'inc/class-efw-fees-handler.php';
			include_once EFW_ABSPATH . 'inc/class-efw-fee-based-on-discount.php';

			if ( is_admin() ) {
				$this->include_admin_files();
			}

			if ( ! is_admin() ) {
				$this->include_frontend_files();
			}
		}

		/**
		 * Include Admin End files.
		 */
		private function include_admin_files() {
			include_once EFW_ABSPATH . 'inc/admin/class-efw-admin-assets.php';
			include_once EFW_ABSPATH . 'inc/admin/class-efw-admin-ajax.php';
			include_once EFW_ABSPATH . 'inc/admin/class-efw-product-fee-settings.php';
			include_once EFW_ABSPATH . 'inc/admin/class-efw-coupon-level-settings.php';
			include_once EFW_ABSPATH . 'inc/admin/menu/class-efw-menu-management.php';
		}

		/**
		 * Include Front End files.
		 */
		private function include_frontend_files() {
			include_once EFW_ABSPATH . 'inc/frontend/class-efw-frontend-assets.php';
		}

		/**
		 * Define the hooks.
		 */
		private function init_hooks() {
			add_action( 'init', array( $this, 'init' ), 5 );
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );
			// Register the plugin.
			register_activation_hook( EFW_PLUGIN_FILE, array( 'EFW_Install', 'install' ) );
			// HPOS Compatibility.
			add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		}

		/**
		 * Init
		 * */
		public function init() {
			$this->load_plugin_textdomain();
		}

		/**
		 * HPOS Compatibility.
		 *
		 * @since 3.3.0
		 */
		public function declare_hpos_compatibility() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', EFW_PLUGIN_FILE, true );
			}
		}

		/**
		 * Plugins Loaded
		 * */
		public function plugins_loaded() {
			/**
			 * Hook: efw_before_plugin_loaded.
			 *
			 * @since 1.0
			 */
			do_action( 'efw_before_plugin_loaded' );

			EFW_Compatibility_Instances::instance();
			EFW_Action_Scheduler_Instances::instance();
			/**
			 * Hook: efw_after_plugin_loaded.
			 *
			 * @since 1.0
			 */
			do_action( 'efw_after_plugin_loaded' );
		}

		/**
		 * Templates.
		 */
		public function templates() {
			return EFW_PLUGIN_PATH . '/templates/';
		}
	}

}
