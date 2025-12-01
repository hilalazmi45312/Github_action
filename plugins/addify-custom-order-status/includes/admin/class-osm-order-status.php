<?php

/**
 * Admin side functionalities of order status Manager
 *
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
if (!class_exists('KA_Osm_Order_Status_Admin')) {
	class KA_Osm_Order_Status_Admin {
		public function __construct() {
			// Bulk action for custom order status.
			add_filter('bulk_actions-edit-shop_order', array( $this, 'custom_order_status_bulk_action' ));
			//For HPOS.
			add_filter('bulk_actions-woocommerce_page_wc-orders', array( $this, 'custom_order_status_bulk_action' ));

			// Enqueue style and scripts for admin.
			add_action('admin_enqueue_scripts', array( $this, 'order_status_manager_admin_scripts' ), 99);
		}

		/**
		 * Function to add bulk action for custom order status
		 *
		 * @param mixed $order_statuses args.
		 */
		public function custom_order_status_bulk_action( $order_statuses ) {
			$prefix                = 'mark_';
			$sorted_order_statuses = array(); // Initializing.

			foreach ($order_statuses as $key => $label) {
				$sorted_order_statuses[ $key ] = $label;
				// Loop through custom order statuses array (key/label pairs).
				if (is_array($this->get_custom_order_statuses_from_CPT()) || is_object($this->get_custom_order_statuses_from_CPT())) {
					foreach ($this->get_custom_order_statuses_from_CPT() as $custom_key => $custom_label) {

						if ('yes' == $custom_label['bulk_check']) {
							$sorted_order_statuses[ $prefix . $custom_key ] = 'Change status to ' . $custom_label['name'];
						}
					}
				}
			}

			return $sorted_order_statuses;
		}

		/**
		 * Function to enqueue scripts and styles for admin
		 */
		public function order_status_manager_admin_scripts() {

			$screen = get_current_screen();

			if ( !in_array( $screen->id, array( 'edit-order_status', 'order_status', 'edit-status_emails', 'status_emails', 'edit-status_automation', 'status_automation', 'woocommerce_page_addify_osm_cron_setting' ) ) ) {
				return;
			}


			wp_enqueue_style('thickbox');
			wp_enqueue_script('thickbox');
			wp_enqueue_script('media-upload');
			wp_enqueue_media();
			
			wp_enqueue_style('my__osm_admin_style', OSM_PLUGIN_URL . 'assests/css/osm-admin-style.css', array(), '1.1');
			// Enqueuing dash icons picker.
			$css = OSM_PLUGIN_URL . 'assests/css/dashicons-picker.css';
			wp_enqueue_style('dashicons-picker', $css, array( 'dashicons' ), '1.0');
			$js = OSM_PLUGIN_URL . 'assests/js/dashicons-picker.js';
			// Enqueuing admin scripts.
			wp_enqueue_script('dashicons-picker', $js, array( 'jquery' ), '1.0', true);
			wp_enqueue_script('my__osm_admin_script', OSM_PLUGIN_URL . 'assests/js/osm-admin-js.js', array(), '1.0', false);
			// Enqueuing scripts for select2 library.
			// Add the Select2 CSS file.
			wp_enqueue_style('ka_osm-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
			//Add the Select2 JavaScript file.
			wp_enqueue_script('ka_osm-select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0', true);
			//Add a JavaScript file to initialize the Select2 elements.
			wp_enqueue_script('ka_osm-select2-init', OSM_PLUGIN_URL . 'assests/js/osm-select2-init-js.js', 'jquery', '4.1.0-rc.0', true);

			


			// Get values and pass them in localize function.
			$args = array(
				'post_type'   => 'order_status',
				'numberposts' => -1,
				'fields'      => 'ids',

			);
			$status_slug  = array();
			$status_name  = array();
			$current_post = get_posts($args);
			foreach ($current_post as $value_ID) {
				$status_slug[] = get_post_meta($value_ID, 'osm_slug', true);
				$status_name[] = get_post_meta($value_ID, 'osm_name', true);
			}

			wp_localize_script('my__osm_admin_script', 'osm_vars', array(
				'status_name' => $status_name,
				'status_slug' => $status_slug,
			));
			wp_localize_script('my__osm_admin_script', 'my_ajax_delete', array(
				'ajax_url'     => admin_url('admin-ajax.php'),
				'delete_nonce' => wp_create_nonce('delete_ajax_nonce'),
			));
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
	}
	new KA_Osm_Order_Status_Admin();
}
