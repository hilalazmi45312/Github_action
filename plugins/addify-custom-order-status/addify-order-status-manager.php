<?php

/**
 * Plugin Name:       Custom Order Status
 * Requires Plugins: woocommerce
 * Plugin URI:        https://woocommerce.com/products/custom-order-status/
 * Description:       Create custom order statuses and assign them to orders manually or through automation rules.
 * Version:           1.2.0
 * Author:            Addify
 * Developed By:      Addify
 * Author URI:        https://woocommerce.com/vendor/addify/
 * Support:           https://woocommerce.com/vendor/addify/
 * License:           GNU General Public License v3.0
 * License            URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path:       /languages
 * TextDomain :       addify_osm
 * WC requires at least: 4.0
 * WC tested up to: 10.*.*
 * Requires at least: 6.5
 * Tested up to: 6.*.*
 * Requires PHP: 7.4
 * 
 * @package Order Status Manager
 * Woo: 18734001361912:4e619bf763d5c7c87b4fd5db5dd59e0a

 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Order_Status_Manager')) {
	/**
	 * Define class.
	 */
	class KA_Osm_Order_Status_Manager {
		public $plugin_name;
		public function __construct() {
			$this->plugin_name = plugin_basename(__FILE__);
			define('OSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
			define('OSM_PLUGIN_URL', plugin_dir_url(__FILE__));
			// Register Custom Order Status.
			add_action('init', array( $this, 'register_new_order_status' ));
			// Add custom order statuses in the list of order statuses.
			add_filter('wc_order_statuses', array( $this, 'add_custom_status_to_order_statuses' ));
			// Create CPT to get order status data.
			$this->create_CPT_for_status_data();
			// Add settings of email in wooCommerce settings.
			add_filter('woocommerce_email_classes', array( $this, 'add_email_settings' ), 90, 1);


			add_action('woocommerce_order_status_changed', array( $this, 'add_email_actions' ), 100, 3);
			// Include link for front end settings.
			add_filter("plugin_action_links_$this->plugin_name", array( $this, 'custom_order_status_settings_link' ), 10, 2);
			// Define text domain
			add_action('wp_loaded', array( $this, 'load_text_domain' ));
			// Schedule cron for auto status change.
			add_action('init', array( $this, 'schedule_status_change_cron' ));

			//HPOs Compatibility
			add_action( 'before_woocommerce_init', array( $this, 'osm_HOPS_Compatibility' ) );

			add_action( 'plugins_loaded', array( $this, 'osm_plugin_check' ) );
		}

		public function osm_HOPS_Compatibility() {

			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}


		public function osm_plugin_check() {

			if ( ! is_multisite() && ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

				add_action( 'admin_notices', array( $this, 'osm_plugin_loaded' ) );

			}
		}
		public function osm_plugin_loaded() {
				// Deactivate the plugin.
				
				$osm_woo_check = '<div class="notice notice-error is-dismissible">
			<p><strong>' . __('Custom Order Status Plugin is inactive.', 'addify_osm') . '</strong> The <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce plugin</a> ' . __('must be active for this plugin to work. Please install &amp; activate WooCommerce.', 'addify_osm') . ' »</p></div>';
			echo wp_kses_post($osm_woo_check);
		}

		public function add_email_actions( $order_id, $from_status, $to_status ) {
			wc()->mailer();

			do_action('woocommerce_order_status_' . $from_status . '_to_' . $to_status . '_email_notification', $order_id);

			do_action('woocommerce_order_status_' . $to_status . '_email_notification', $order_id);
		}

		public function load_text_domain() {
			if (function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain('addify_osm', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
		}

		/**
		 * Function to add settings of email in WooCommerce Settings and create an instance of class.
		 */
		public function add_email_settings( $email_classes ) {
			// Include file for customer email settings.
			$email_classes['KA_Osm_Customer_Email'] = include OSM_PLUGIN_DIR . 'includes/emails/class-osm-customer-email.php';
			// Include file for Admin email settings.
			$email_classes['KA_Osm_Admin_Email'] = include OSM_PLUGIN_DIR . 'includes/emails/class-osm-admin-email.php';

			return $email_classes;
		}

		/**
		 * Function to include files 
		 */
		public function create_CPT_for_status_data() {
			// Include  CPT file for custom order status.
			require_once OSM_PLUGIN_DIR . 'includes/class-osm-order-status-cpt.php';
			// Include  CPT file for custom order status Email.
			require_once OSM_PLUGIN_DIR . 'includes/emails/class-osm-email-cpt.php';
			// Include meta box file
			require_once OSM_PLUGIN_DIR . 'includes/class-osm-order-status-metabox.php';
			// Include file to process order status rules.
			require_once OSM_PLUGIN_DIR . 'includes/class-osm-process-status-rules.php';
			// Include file for export.
			require_once OSM_PLUGIN_DIR . 'includes/class-osm-status-export.php';
			// Include file for import.
			require_once OSM_PLUGIN_DIR . 'includes/class-osm-status-import.php';
			// Include file for email meta boxes.
			require_once OSM_PLUGIN_DIR . 'includes/emails/class-osm-email-metabox-settings.php';
			// Include file for admin settings.
			require_once OSM_PLUGIN_DIR . 'includes/admin/admin-settings/class-osm-admin-settings.php';
			// Include file for automation rules CPT.
			require_once OSM_PLUGIN_DIR . 'includes/automation/class-osm-automation-cpt.php';
			// Include file for automation rules settings.
			require_once OSM_PLUGIN_DIR . 'includes/automation/class-osm-automation-settings.php';
			// Include file to process automation rules.
			require_once OSM_PLUGIN_DIR . 'includes/automation/class-osm-process-auto-rules.php';
			// Include file for cron settings.
			require_once OSM_PLUGIN_DIR . 'includes/admin/admin-settings/class-osm-cron-settings.php';
			// Include file for admin side functionalities.
			require_once OSM_PLUGIN_DIR . 'includes/admin/class-osm-order-status.php';
		}

		/**
		 * Function to get the data of order status from Custom Post Type
		 */
		public function get_custom_order_statuses_from_CPT() {
			$custom_order_status_new = array();

			$arg                   = array(
				'numberposts' => -1,
				'post_type'   => 'order_status',
				'fields'      => 'ids',
			);
			$custom_order_statuses = get_posts($arg);
			if (!empty($custom_order_statuses)) {
				foreach ($custom_order_statuses as $post_id) {
					$slug                             = get_post_meta($post_id, 'osm_slug', true);
					$status_name                      = get_post_meta($post_id, 'osm_name', true);
					$bulk_check                       = get_post_meta($post_id, 'osm_bulk_check', true);
					$custom_order_status_new[ $slug ] = array(
						'name'       => $status_name,
						'bulk_check' => $bulk_check,
					);
				}
				return $custom_order_status_new;
			}
		}

		/**
		 * Function to register custom order status
		 */
		public function register_new_order_status() {

			$prefix = 'wc-';
			if (is_array($this->get_custom_order_statuses_from_CPT()) || is_object($this->get_custom_order_statuses_from_CPT())) {
				foreach ($this->get_custom_order_statuses_from_CPT() as $key => $label) {

					$dat = $label['name'];

					register_post_status($prefix . $key, array(
						'label'                     => $label['name'],
						'public'                    => true,
						'exclude_from_search'       => false,
						'show_in_admin_all_list'    => true,
						'show_in_admin_status_list' => true,
						/* translators: %s: search term */
						//'label_count'               => _n_noop($label['name'] . ' <span class="count">(%s)</span>', $label['name'] . ' <span class="count">(%s)</span>') 
					));
				}
			}
		}

		/**
		 * Function to add custom order statuses in core order status array
		 */
		public function add_custom_status_to_order_statuses( $order_statuses ) {
			$prefix                = 'wc-';
			$sorted_order_statuses = array(); // Initializing.

			foreach ($order_statuses as $key => $label) {
				$sorted_order_statuses[ $key ] = $label;

				if ('wc-completed' === $key) {
					// Loop through custom order statuses array (key/label pairs).
					if (is_array($this->get_custom_order_statuses_from_CPT()) || is_object($this->get_custom_order_statuses_from_CPT())) {
						foreach ($this->get_custom_order_statuses_from_CPT() as $custom_key => $custom_label) {

							$sorted_order_statuses[ $prefix . $custom_key ] = $custom_label['name'];
						}
					}
				}
			}

			return $sorted_order_statuses;
		}
		/**
		 * Function to schedule the cron
		 */
		public function schedule_status_change_cron() {

			if (!wp_next_scheduled('schedule_status_custom_status_hook')) {
				wp_schedule_event(time() + 10, 'kaosm_cron_schedule', 'schedule_status_custom_status_hook');
			}
		}

		/**
		 * Function for settings link after activating the plugin
		 */
		public function custom_order_status_settings_link( $links ) {
			/**
			 * Checks the activation of wooCommerce plugin
			 *
			 * @since 0.71
			 */
			if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
				$settings_url = '<a href="edit.php?post_type=order_status">Settings</a>';
				array_push($links, $settings_url);
			}
			return $links;
		}
	}

	$KA_status_manager = new KA_Osm_Order_Status_Manager(); // Creating instance of class.
}
