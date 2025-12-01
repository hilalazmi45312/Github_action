<?php
/**
 * Defines Custom Post Type for Order statuses
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Order_Status_CPT')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Order_Status_CPT {
		/**
		 * Constructor of the class
		 */
		public function __construct() {
			// Create CPT.
			add_action('init', array( $this, 'create_order_status_CPT' ));
			// Add the custom columns to the order status post type.
			add_filter('manage_order_status_posts_columns', array( $this, 'set_order_status_post_column' ));
			// Add Value to Columns.
			add_action('manage_posts_custom_column', array( $this, 'order_status_list_columns_value' ), 10, 2);
		}

		/**
		 * Function to create custom post type for custom order status
		 */
		public function create_order_status_CPT() {
			register_post_type(
				'order_status',
				array(
					'labels'             => array(
						'name'               => __('Order Statuses', 'addify_osm'),
						'singular_name'      => __('Order Status', 'addify_osm'),
						'add_new'            => __('Add Order Status', 'addify_osm'),
						'add_new_item'       => __('New Order Status', 'addify_osm'),
						'edit_item'          => __('Edit Order Status', 'addify_osm'),
						'search_items'       => __('Search Order Status', 'addify_osm'),
						'view_item'          => __('View Custom Order Status', 'addify_osm'),
						'not_found'          => __('No Custom Order Status found.', 'addify_osm'),
						'not_found_in_trash' => __('No Custom Order Status found in Trash.', 'addify_osm'),
					),
					'public'             => false,
					'publicly_queryable' => false,
					'show_ui'            => true,
					'query_var'          => true,
					'capability_type'    => 'post',
					'has_archive'        => false,
					'hierarchical'       => false,
					'show_in_menu'       => 'woocommerce',
					'supports'           => array( '' ),

				)
			);
		}

		/**
		 * Function to add columns on custom post type listing page
		 */
		public function set_order_status_post_column( $columns ) {
			unset($columns['title']);
			unset($columns['date']);
			$columns['osm_name'] = __('Status Name', 'addify_osm');
			$columns['osm_slug'] = __('Status Slug', 'addify_osm');
			$columns['osm_desc'] = __('Status Description', 'addify_osm');
			$columns['osm_date'] = __('Date', 'addify_osm');

			return $columns;
		}

		/**
		 * Function to add values to the columns on custom order status listing page
		 */
		public function order_status_list_columns_value( $column ) {
			if ('osm_desc' == $column) {
				$order_status_desc = esc_html(get_post_meta(get_the_ID(), 'osm_desc', true));
				esc_html_e(sanitize_text_field($order_status_desc, 'addify_osm'));
				if (empty($order_status_desc)) {
					echo esc_html__('No Description Found!', 'addify_osm');
				}
			}
			if ('osm_slug' == $column) {
				$order_status_slug = esc_html_e(get_post_meta(get_the_ID(), 'osm_slug', true), 'addify_osm');
				echo esc_html(sanitize_text_field($order_status_slug));
			}
			if ('osm_name' == $column) {
				$order_status_name   = esc_html(get_post_meta(get_the_ID(), 'osm_name', true));
				$order_status_icon   = esc_html(get_post_meta(get_the_ID(), 'osm_icon', true));
				$background_color    = esc_html(get_post_meta(get_the_ID(), 'osm_color', true)); // Custom order status background color
				$order_status_select = esc_html(get_post_meta(get_the_ID(), 'osm_select', true));
				$text_color          = esc_html(get_post_meta(get_the_ID(), 'osm_text_color', true)); // Custom order status text color
				if (empty($background_color)) {
					$background_color = '#ffffff';
				}
				if (empty($text_color)) {
					$text_color ='#000000';
				}
				if ('text' == $order_status_select) {
					echo '<span class="admin-status-name" style="background-color:' . esc_attr($background_color) . '; color: ' . esc_attr($text_color) . ';">' . esc_html($order_status_name) . '</span>';
				} elseif ('icon' == $order_status_select) {
					echo '<span class="dashicons ' . esc_attr($order_status_icon) . ' admin-status-icon" style="color:' . esc_attr($text_color) . '"></span>';
				} else {
					echo esc_html(sanitize_text_field($order_status_name));
				}
			}
			if ('osm_date' == $column) {
				$date = get_the_date();
				echo esc_html(sanitize_text_field($date));
			}
		}
	}
	new KA_Osm_Order_Status_CPT();
}
