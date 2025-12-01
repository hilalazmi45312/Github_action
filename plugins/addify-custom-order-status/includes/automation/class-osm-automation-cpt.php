<?php

/**
 * Defines Custom Post Type for automation rules
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Automation_CPT')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Automation_CPT {
		public function __construct() {
			// Create CPT.
			add_action('init', array( $this, 'create_automation_CPT' ));
		
			// Add the custom columns to the order status post type.
			add_filter('manage_status_automation_posts_columns', array( $this, 'set_status_auto_post_column' ));
			// Add Value to Columns.
			add_action('manage_posts_custom_column', array( $this, 'status_auto_list_columns_value' ), 10, 2);
		}

		/**
		 * Function to create custom post type for custom order status
		 */
		public function create_automation_CPT() {
			register_post_type(
				'status_automation',
				array(
					'labels'             => array(
						'name'               => __('Order Status Automation Rules', 'addify_osm'),
						'menu_name'          =>  __('Order Statuses', 'addify_osm'),
						'singular_name'      => __('Order Status Automation Rule', 'addify_osm'),
						'attributes'         => 'Rule Priority',
						'add_new'            => __('Add Automation Rule', 'addify_osm'),
						'add_new_item'       => __('New Automation Rule', 'addify_osm'),
						'edit_item'          => __('Edit Automation Rule', 'addify_osm'),
						'search_items'       => __('Search Automation Rules', 'addify_osm'),
						'view_item'          => __('View Automation Rules', 'addify_osm'),
						'not_found'          => __('No Automation Rules found.', 'addify_osm'),
						'not_found_in_trash' => __('No Status Automation Rules found in Trash.', 'addify_osm'),
					),
					'public'             => false,
					'publicly_queryable' => false,
					'show_ui'            => true,
					'query_var'          => true,
					'capability_type'    => 'post',
					'has_archive'        => false,
					'hierarchical'       => false,
					'show_in_menu'       => 'woocommerce',
					'supports'           => array(
						'title',
						'page-attributes',
					),

				)
			);
		}
		/**
		 * Function to add columns on custom post type listing page
		 */
		public function set_status_auto_post_column( $columns ) {    
			$columns['osm_rule_status'] = __('Rule Status', 'addify_osm');
			
			$columns['osm_rule_priority'] = __('Rule Priority', 'addify_osm');
			
			return $columns;
		}
		/**
		 * Function to add values to the columns on Order status emails listing page
		 *
		 * @param mixed $column
		 */
		public function status_auto_list_columns_value( $column ) {
			if ('osm_rule_priority' == $column) {
				
				$post = get_post(get_the_ID());
				echo esc_html($post->menu_order);
			}
			if ('osm_rule_status' == $column) {
				if ('publish' === get_post_status(get_the_ID())) {
					echo '<span style ="color:green">' . esc_html__('Enabled', 'addify_cofu') . '</span>';
				} else {
					esc_html_e('Disabled', 'addify_cofu');
				}
			
			}
		}
	}
	new KA_Osm_Automation_CPT();
}
