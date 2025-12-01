<?php
/**
 * Defines custom post type for emails
 *
 * @package Order Status Manager
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Email_CPT')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Email_CPT {
		/**
		 * Constructor of the class
		 */
		public function __construct() {
			// Create CPT.
			add_action('init', array( $this, 'create_email_CPT' ));
			// Add the custom columns to the order status post type.
			add_filter('manage_status_emails_posts_columns', array( $this, 'set_status_email_post_column' ));
			// Add Value to Columns.
			add_action('manage_posts_custom_column', array( $this, 'status_emails_list_columns_value' ), 10, 2);
		}

		/**
		 * Function to create custom post type for custom order status
		 */
		public function create_email_CPT() {
			register_post_type(
				'status_emails',
				array(
					'labels'             => array(
						'name'               => __('Order Status Emails', 'addify_osm'),
						'menu_name'          =>  __('Order Statuses', 'addify_osm'),
						'singular_name'      => __('Order Status Email', 'addify_osm'),
						'add_new'            => __('Add Order Status Email', 'addify_osm'),
						'add_new_item'       => __('New Order Status Email', 'addify_osm'),
						'edit_item'          => __('Edit Order Status Email', 'addify_osm'),
						'search_items'       => __('Search Order Status Emails', 'addify_osm'),
						'view_item'          => __('View Order Status Emails', 'addify_osm'),
						'not_found'          => __('No Order Status emails found.', 'addify_osm'),
						'not_found_in_trash' => __('No Order Status emails found in Trash.', 'addify_osm'),
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
		public function set_status_email_post_column( $columns ) {
			unset($columns['title']);
			unset($columns['date']);
			$columns['osm_email_name']       = __('Rule Title', 'addify_osm');
			$columns['osm_email_desc']       = __('Rule Description', 'addify_osm');
			$columns['osm_select_recipient'] = __('Type', 'addify_osm');
			$columns['osm_date']             = __('Date', 'addify_osm');

			return $columns;
		}
		/**
		 * Function to add values to the columns on Order status emails listing page
		 *
		 * @param mixed $column
		 */
		public function status_emails_list_columns_value( $column ) {
			if ('osm_email_name' == $column) {
				$email_name = esc_html(get_post_meta(get_the_ID(), 'osm_email_name', true));?>
				<span class="email-title"> <?php echo esc_html__(sanitize_text_field($email_name), 'addify_osm'); ?> </span>
				<?php
			}
			if ('osm_email_desc' == $column) {
				$email_desc = esc_html(get_post_meta(get_the_ID(), 'osm_email_desc', true));
				echo esc_html__(sanitize_text_field($email_desc), 'addify_osm');
				if (empty($email_desc)) {
					esc_html_e('No description found!', 'addify_osm');
				}
			
			}
			if ('osm_select_recipient' == $column) {
				$email_recipient = esc_html(get_post_meta(get_the_ID(), 'osm_select_recipient', true));
				?>
				<b> <?php echo esc_html__(sanitize_text_field($email_recipient), 'addify_osm'); ?> </b>
				<?php
			}
		}
	}
	new KA_Osm_Email_CPT();
}
