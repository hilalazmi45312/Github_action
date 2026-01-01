<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.webtoffee.com
 * @since      1.0.0
 *
 * @package    Webtoffee_Product_Feed_Sync_Pro
 * @subpackage Webtoffee_Product_Feed_Sync_Pro/includes
 */

if ( ! class_exists( 'Webtoffee_Product_Feed_Sync_Pro' ) ) {
	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Webtoffee_Product_Feed_Sync_Pro
	 * @subpackage Webtoffee_Product_Feed_Sync_Pro/includes
	 */
	class Webtoffee_Product_Feed_Sync_Pro {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @var      Webtoffee_Product_Feed_Sync_Pro_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $version    The current version of the plugin.
		 */
		protected $version;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $loaded_modules = array();
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $template_tb = 'wt_pf_mapping_template';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $history_tb = 'wt_pf_action_history';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public static $cron_tb = 'wt_pf_cron';

		/**
		 * The sync DB table of this plugin.
		 *
		 * @since    1.0.3
		 * @var      string    $sync_table    The sync DB table of this plugin.
		 */
		public static $sync_table = 'wt_pf_facebook_sync';
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $plugin_admin;
		/**
		 * Module ID
		 *
		 * @var string
		 */
		public $plugin_public;
		/**
		 * Plugin base name
		 *
		 * @var string
		 */
		public $plugin_base_name;
		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {
			if ( defined( 'WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION' ) ) {
				$this->version = WEBTOFFEE_PRODUCT_FEED_PRO_SYNC_VERSION;
			} else {
				$this->version = '1.0.4';
			}
			$this->plugin_name = 'product-feed-woocommerce';
			$this->plugin_base_name  = WT_PRODUCT_FEED_PRO_BASE_NAME;

			$this->load_dependencies();
			if(get_bloginfo('version') < 6.7){
				$this->set_locale();
			}
			$this->define_admin_hooks();
		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Webtoffee_Product_Feed_Sync_Pro_Loader. Orchestrates the hooks of the plugin.
		 * - Webtoffee_Product_Feed_Sync_Pro_I18n. Defines internationalization functionality.
		 * - Webtoffee_Product_Feed_Sync_Pro_Admin. Defines all hooks for the admin area.
		 * - Webtoffee_Product_Feed_Sync_Pro_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks.
		 * with WordPress.
		 *
		 * @since    1.0.0
		 */
		private function load_dependencies() {

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			require_once plugin_dir_path( __DIR__ ) . 'includes/class-webtoffee-product-feed-sync-pro-loader.php';

			/**
			 * The class responsible for defining internationalization functionality
			 * of the plugin.
			 */
			require_once plugin_dir_path( __DIR__ ) . 'includes/class-webtoffee-product-feed-sync-pro-i18n.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once plugin_dir_path( __DIR__ ) . 'admin/class-webtoffee-product-feed-sync-pro-admin.php';

			/**
			 * Class includes input sanitization and role checking
			 */
			require_once plugin_dir_path( __DIR__ ) . 'helpers/class-wt-pf-sh.php';

			/**
			 * Class includes common helper functions
			 */
			require_once plugin_dir_path( __DIR__ ) . 'helpers/class-webtoffee-product-feed-sync-pro-common-helper.php';

			/**
			 * Class includes helper functions for import and export modules
			 */
			require_once plugin_dir_path( __DIR__ ) . 'helpers/class-wt-pf-catalog-export-helper.php';

			require_once plugin_dir_path( __DIR__ ) . 'helpers/class-webtoffee-product-feed-shipping.php';

			require_once plugin_dir_path( __DIR__ ) . 'includes/class-webtoffee-product-feed-sync-pro-review-request.php';

			$this->loader = new Webtoffee_Product_Feed_Sync_Pro_Loader();
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the Webtoffee_Product_Feed_Sync_Pro_I18n class in order to set the domain and to register the hook.
		 * with WordPress.
		 *
		 * @since    1.0.0
		 */
		private function set_locale() {

			$plugin_i18n = new Webtoffee_Product_Feed_Sync_Pro_I18n();

			$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 */
		private function define_admin_hooks() {

			$plugin_admin = new Webtoffee_Product_Feed_Sync_Pro_Admin( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

				/* Loading admin modules */
			$plugin_admin->admin_modules();

				/* Admin menus */
			$this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu', 11 );

			$this->loader->add_action( 'wp_ajax_wt_pf_save_settings_pro', $plugin_admin, 'save_settings' );

			$this->loader->add_action( 'wp_ajax_wt_fbfeed_ajax_upload', $plugin_admin, 'wt_fbfeed_ajax_upload' );
			$this->loader->add_action( 'wp_ajax_fbfeed_batch_status_ajax', $plugin_admin, 'wt_fbfeed_batch_status' );

			$this->loader->add_action( 'wp_ajax_wt_fbfeed_set_auto_upload', $plugin_admin, 'wt_fbfeed_set_auto_upload' );

			$this->loader->add_action( 'wp_ajax_wt_fbfeed_ajax_save_category', $plugin_admin, 'wt_fbfeed_ajax_save_category' );
			$this->loader->add_filter( 'plugin_action_links_' . $this->get_plugin_base_name(), $plugin_admin, 'add_productfeed_action_links' );
		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run() {
			$this->loader->run();
		}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since     1.0.0
		 * @return    string    The name of the plugin.
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * The name of the plugin basefile used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since     1.0.0
		 * @return    string    The name of the plugin basefile.
		 */
		public function get_plugin_base_name() {
			return $this->plugin_base_name;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since     1.0.0
		 * @return    Webtoffee_Product_Feed_Sync_Pro_Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader() {
			return $this->loader;
		}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @since     1.0.0
		 * @return    string    The version number of the plugin.
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * Get module id
		 *
		 * @param object $module_base Module base.
		 * @return string
		 */
		public static function get_module_id( $module_base ) {
			return WEBTOFFEE_PRODUCT_FEED_MAIN_PRO_ID . '_' . $module_base;
		}

		/**
		 * Some modules are not start by default. So need to initialize via code OR get object of already started modules
		 *
		 * @param object $module Module base.
		 * @since    1.0.0
		 */
		public static function load_modules( $module ) {
			if ( Webtoffee_Product_Feed_Sync_Pro_Admin::module_exists( $module ) ) {
				if ( ! isset( self::$loaded_modules[ $module ] ) ) {
					$module_class = 'Webtoffee_Product_Feed_Sync_Pro_' . ucfirst( $module );
					self::$loaded_modules[ $module ] = new $module_class();
				}
				return self::$loaded_modules[ $module ];
			} else {
				return null;
			}
		}

		/**
		 * Generate tab head for settings page.
		 *
		 * @param array  $title_arr Title array.
		 * @param string $type Type.
		 * @since     1.0.0
		 */
		public static function generate_settings_tabhead( $title_arr, $type = 'plugin' ) {
			/**
			 * Filter the query arguments for a request.
			 *
			 * Enables changing the title.
			 *
			 * @since 1.0.0
			 *
			 * @param string   $title_arr  Title of the settings tabs.
			 */
			$out_arr = apply_filters( 'wt_pf_' . $type . '_settings_tabhead', $title_arr );

			foreach ( $out_arr as $k => $v ) {
				if ( is_array( $v ) ) {
					$v = ( isset( $v[2] ) ? $v[2] : '' ) . $v[0] . ' ' . ( isset( $v[1] ) ? $v[1] : '' );
				}
				?>
			<a class="nav-tab" href="#<?php echo esc_html( $k ); ?>"><?php echo esc_html( $v ); ?></a>
				<?php
			}
		}

		/**
		 *   Get remote file adapters. Eg: FTP, Gdrive, OneDrive
		 *
		 *   @param string $action action to be executed, If the current adapter is not suitable for a specific action then skip it.
		 *   @param string $adapter optional specify an adapter name to retrive the specific one.
		 *   @return array|single array of remote adapters or single adapter if the adapter name specified.
		 */
		public static function get_remote_adapters( $action, $adapter = '' ) {
			$adapters = array();
			/**
			 * Filter the query arguments for a request.
			 *
			 * Enables changing the title.
			 *
			 * @since 1.0.0
			 *
			 * @param string   $title_arr  Title of the settings tabs.
			 */
			$adapters = apply_filters( 'wt_pf_remote_adapters_basic', $adapters, $action, $adapter );
			if ( '' != $adapter ) {
				return ( isset( $adapters[ $adapter ] ) ? $adapters[ $adapter ] : null );
			}
			return $adapters;
		}
	}
}
