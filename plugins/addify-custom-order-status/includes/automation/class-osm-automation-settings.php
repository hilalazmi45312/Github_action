<?php

/**
 * Defines Meta box settings for automation rules
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Automation_Settings')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Automation_Settings {
		/**
		 * Constructor of the class
		 */
		public function __construct() {
			// Add custom meta box.
			add_action('add_meta_boxes', array( $this, 'status_automation_metabox' ));
			// Save meta box.
			add_action('save_post_status_automation', array( $this, 'save_status_automation_metabox' ));

			// Show ajax response on select2.
			add_action('wp_ajax_osm_get_products', array( $this, 'osm_get_products' ));
			add_action('wp_ajax_osm_get_products', array( $this, 'osm_get_products' ));
		}
		/**
		 * Add Meta box
		 */

		public function status_automation_metabox() {
			add_meta_box(
				'osm_status_automation',                 // Unique ID.
				__('Order Status', 'addify_osm'),      // Box title.
				array( $this, 'status_automation_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);
			add_meta_box(
				'osm_status_time',                 // Unique ID.
				__('Time Interval', 'addify_osm'),      // Box title.
				array( $this, 'auto_time_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);
			add_meta_box(
				'osm_status_quantity',                 // Unique ID.
				__('Order Quantity', 'addify_osm'),      // Box title.
				array( $this, 'auto_quantity_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);

			add_meta_box(
				'osm_status_amount',                 // Unique ID.
				__('Order Amount', 'addify_osm'),      // Box title.
				array( $this, 'auto_amount_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);
			add_meta_box(
				'osm_status_product',                 // Unique ID.
				__('Products', 'addify_osm'),      // Box title.
				array( $this, 'auto_product_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);
			add_meta_box(
				'osm_status_country',                 // Unique ID.
				__('Countries', 'addify_osm'),      // Box title.
				array( $this, 'auto_country_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);
			add_meta_box(
				'osm_status_roles',                 // Unique ID.
				__('User Roles', 'addify_osm'),      // Box title.
				array( $this, 'auto_roles_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);
			add_meta_box(
				'osm_status_date',                 // Unique ID.
				__('Date Created', 'addify_osm'),      // Box title.
				array( $this, 'auto_date_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);
			add_meta_box(
				'osm_status_email',                 // Unique ID.
				__('Email Settings', 'addify_osm'),      // Box title.
				array( $this, 'auto_email_html' ),  // Content callback, must be of type callable.
				'status_automation', // Post type.
				'normal',
				'low'
			);
		}

		/**
		 * Meta box html for Order Amount
		 */
		public function auto_amount_html() {
			global $post;
			$osm_maximum_amount = get_post_meta($post->ID, 'osm_maximum_amount', true);
			$osm_minimum_amount = get_post_meta($post->ID, 'osm_minimum_amount', true);
			?>

			<table class="status_fields">

				<!-- Order Maximum Amount -->
				<tr>
					<th class="time-head"><label for="osm_maximum_amount"><?php esc_html_e('Maximum Amount', 'addify_osm'); ?></label></th>
					<td class="status-row"><input min="0" step="0.000001" class="osm-rules" id="osm_maximum_amount" type="number" name="osm_maximum_amount" value="<?php esc_attr_e($osm_maximum_amount); ?>" /><br>
						<span><em>&nbsp;<?php esc_html_e('(Set maximum amount, If you want the rule to be applied only for orders with subtotal equal or less than some value. Ignored, if empty.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!-- Order Minimum Amount -->
				<tr>
					<th class="time-head"><label for="osm_minimum_amount"><?php esc_html_e('Minimum Amount', 'addify_osm'); ?></label></th>
					<td class="status-row"><input min="0" step="0.000001" class="osm-rules" id="osm_minimum_amount" type="number" name="osm_minimum_amount" value="<?php esc_attr_e($osm_minimum_amount); ?>" /><br>
						<span><em>&nbsp;<?php esc_html_e('(Set maximum amount, If you want the rule to be applied only for orders with subtotal equal or greater than some value. Ignored, if empty.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}
		/**
		 * Meta box html for Email Settings
		 */
		public function auto_quantity_html() {
			global $post;
			$osm_order_max_quantity = get_post_meta($post->ID, 'osm_order_max_quantity', true);
			$osm_order_min_quantity = get_post_meta($post->ID, 'osm_order_min_quantity', true);
			?>

			<table class="status_fields">
				<!-- Maximum Order Quantity -->
				<tr>
					<th class="time-head"><label for="osm_order_max_quantity"><?php esc_html_e('Maximum Quantity', 'addify_osm'); ?></label></th>
					<td class="status-row"><input min="0" class="osm-rules" id="osm_order_max_quantity" type="number" name="osm_order_max_quantity" value="<?php esc_attr_e($osm_order_max_quantity); ?>" /><br>
						<span><em>&nbsp;<?php esc_html_e('(Set maximum order quantity, If you want the order rule to be applied only for orders with number of items less than or equal to some value, Ignored if empty.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!-- Minimum Order Quantity -->
				<tr>
					<th class="time-head"><label for="osm_order_min_quantity"><?php esc_html_e('Minimum Quantity', 'addify_osm'); ?></label></th>
					<td class="status-row"><input min="0" class="osm-rules" id="osm_order_min_quantity" type="number" name="osm_order_min_quantity" value="<?php esc_attr_e($osm_order_min_quantity); ?>" /><br>
						<span><em>&nbsp;<?php esc_html_e('(Set minimum order quantity, If you want the order rule to be applied only for orders with number of items equal or greater to some value, Ignored if empty.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}
		/**
		 * Meta box html for time interval
		 */
		public function auto_time_html() {
			global $post;
			$osm_auto_time   = get_post_meta($post->ID, 'osm_auto_time', true);
			$osm_unit_select = get_post_meta($post->ID, 'osm_unit_select', true);
			?>
			<!--  Time Interval -->
			<table class="status_fields">
				<tr>
					<th class="time-head"><label for="osm_auto_time"><?php esc_html_e('Set Time Interval', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<input required class="time-data" id="osm_auto_time" type="number" name="osm_auto_time" value="<?php esc_attr_e($osm_auto_time); ?>" />

						<select name="osm_unit_select" class="unit-select">
							<option value="minute" <?php selected($osm_unit_select, 'minute'); ?>><?php esc_html_e('Minutes', 'addify_osm'); ?></option>
							<option value="hour" <?php selected($osm_unit_select, 'hour'); ?>><?php esc_html_e('Hours', 'addify_osm'); ?></option>
							<option value="day" <?php selected($osm_unit_select, 'day'); ?>><?php esc_html_e('Days', 'addify_osm'); ?></option>
						</select>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(Enter the time interval for order, after which its status will be changed automatically from "From Status" to "To Status".)', 'addify_osm'); ?></em></span>

					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}
		/**
		 * Meta box html for Products
		 */
		public function auto_product_html() {
			global $post;

			$product_values = get_post_meta($post->ID, 'osm_select_product', true);
			$cat_values     = get_post_meta($post->ID, 'osm_select_category', true);


			?>

			<table class="status_fields">
				<!-- Product in Order-->
				<tr>
					<th class="time-head"><label for="osm_select_product"><?php esc_html_e('Products', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_select_product[]" multiple id="osm-products">
							<?php
							if ($product_values) {
								foreach ($product_values as $id) {

									$selected = ( in_array($id, $product_values) ) ? 'selected="selected"' :  '';
									?>
									<option value="<?php esc_attr_e($id); ?>" <?php esc_html_e($selected); ?>><?php esc_html_e(get_the_title($id), 'addify_osm'); ?></option>
									<?php
								}
							}
							?>
						</select>

						<br>
						<span><em>&nbsp;<?php esc_html_e('(If you want the rule to be applied only for orders with selected products, you can set them here. Ignored, if empty.)', 'addify_osm'); ?></em></span>

					</td>
				</tr>
				<!--  Category of product in order -->
				<tr>
					<th class="time-head"><label for="osm_select_category"><?php esc_html_e('Product Categories', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_select_category[]" multiple id="osm_categories" class="chosen_select">
							<?php


							foreach ($this->get_categories() as $cat_id => $cat_name) {
								if ($cat_values) {
									$selected = ( in_array($cat_id, $cat_values) ) ? 'selected="selected"' :  '';
								} else {
									$selected = '';
								}
								?>
								<option value="<?php esc_attr_e($cat_id); ?>" <?php esc_html_e($selected); ?>><?php esc_html_e($cat_name, 'addify_osm'); ?></option>
								<?php
							}

							?>
						</select>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(If you want the rule to be applied only for orders with selected product categories, you can set them here. Ignored, if empty.)', 'addify_osm'); ?></em></span>

					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}

		/**
		 * Meta box html for Countries
		 */
		public function auto_country_html() {
			global $post;
			?>

			<table class="status_fields">


				<!--  Billing countries -->
				<tr>
					<th class="time-head"><label for="osm_select_billing_country"><?php esc_html_e('Billing Countries', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_select_billing_country[]" multiple id="osm_billing_country" class="chosen_select">
							<?php

							$billing_countries = get_post_meta($post->ID, 'osm_select_billing_country', true);
							foreach (WC()->countries->get_countries() as $billing_key => $billing_country) {
								if ($billing_countries) {
									$selected = ( in_array($billing_key, $billing_countries) ) ? 'selected="selected"' :  '';
								} else {
									$selected = '';
								}

								?>
								<option value="<?php esc_attr_e($billing_key); ?>" <?php esc_html_e($selected); ?>><?php esc_html_e($billing_country, 'addify_osm'); ?></option>
								<?php
							}
							?>
						</select>
						<br>
						<button class="add-new-h2 osm-select-all"> Select All </button> <button class="add-new-h2 osm-deselect-all"> Deselect All </button>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(If you want the rule to be applied only for orders with selected billing countries, you can set them here. Ignored, if empty.)', 'addify_osm'); ?></em></span>

					</td>
				</tr>
				<!--  Shipping countries -->
				<tr>
					<th class="time-head"><label for="osm_select_shipping_country"><?php esc_html_e('Shipping Countries', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_select_shipping_country[]" multiple id="osm_shipping_country" class="chosen_select">
							<?php

							$shipping_countries = get_post_meta($post->ID, 'osm_select_shipping_country', true);
							foreach (WC()->countries->get_countries() as $shipping_key => $shipping_country) {
								if ($shipping_countries) {
									$selected = ( in_array($shipping_key, $shipping_countries) ) ? 'selected="selected"' :  '';
								} else {
									$selected = '';
								}
								?>
								<option value="<?php esc_attr_e($shipping_key); ?>" <?php esc_html_e($selected); ?>><?php esc_html_e($shipping_country, 'addify_osm'); ?></option>
								<?php
							}
							?>
						</select>
						<br>
						<button class="add-new-h2 osm-select-all"> Select All </button> <button class="add-new-h2 osm-deselect-all"> Deselect All </button>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(If you want the rule to be applied only for orders with selected shipping countries, you can set them here. Ignored, if empty.)', 'addify_osm'); ?></em></span>

					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}
		/**
		 * Meta box html for Order User Roles
		 */
		public function auto_roles_html() {
			global $post;
			?>

			<table class="status_fields">
				<!--  User Roles -->
				<tr>
					<th class="time-head"><label for="osm_select_user_roles"><?php esc_html_e('User Roles', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_select_user_roles[]" multiple id="osm-user-roles" class="chosen_select">
							<?php
							$user_role_values = get_post_meta($post->ID, 'osm_select_user_roles', true);
							foreach ($this->get_user_roles() as $key => $user_role) {
								if ($user_role_values) {
									$selected = ( in_array($key, $user_role_values) ) ? 'selected="selected"' :  '';
								} else {
									$selected = '';
								}
								?>
								<option value="<?php esc_attr_e($key); ?>" <?php esc_html_e($selected); ?>><?php esc_html_e($user_role, 'addify_osm'); ?></option>
								<?php
							}
							?>
						</select>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(If you want the rule to be applied only for orders from selected user roles, you can set them here. Ignored, if empty.)', 'addify_osm'); ?></em></span>

					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}
		/**
		 * Meta box html for Order Quantity
		 */
		public function auto_date_html() {
			global $post;

			$osm_date_created_before = get_post_meta($post->ID, 'osm_date_created_before', true);
			$osm_date_created_after  = get_post_meta($post->ID, 'osm_date_created_after', true);
			?>

			<table class="status_fields">

				<!--  Date Created before -->
				<tr>
					<th class="time-head"><label for="osm_date_created_before"><?php esc_html_e('Date Created Before', 'addify_osm'); ?></label></th>
					<td class="status-row"><input class="date-data" id="osm_date_created_before" type="date" name="osm_date_created_before" value="<?php esc_attr_e($osm_date_created_before); ?>" /><br>
						<span><em>&nbsp;<?php esc_html_e('(If you want the rule to be applied only for orders created before some date, you can set it here. Ignored, if empty.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!--  Date Created after -->
				<tr>
					<th class="time-head"><label for="osm_date_created_after"><?php esc_html_e('Date Created After', 'addify_osm'); ?></label></th>
					<td class="status-row"><input class="date-data" id="osm_date_created_after" type="date" name="osm_date_created_after" value="<?php esc_attr_e($osm_date_created_after); ?>" /><br>
						<span><em>&nbsp;<?php esc_html_e('(If you want the rule to be applied only for orders created after some date, you can set it here. Ignored, if empty.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}
		/**
		 * Meta box html for Email Settings
		 */
		public function auto_email_html() {
			global $post;
			$osm_customer_notify = get_post_meta($post->ID);
			$osm_admin_notify    = get_post_meta($post->ID);
			?>

			<table class="status_fields">
				<!-- Disable email notification for customer -->

				<tr>
					<th class="head-email"><label for="disable_customer_notification"><?php esc_html_e('Disable Customer Email Notification', 'addify_osm'); ?></label></th>
					<td class="status-cus"><input type="checkbox" name="osm_disable_customer_notification" value="no" 
					<?php
					if (isset($osm_customer_notify['osm_disable_customer_notification'])) {
						checked($osm_customer_notify['osm_disable_customer_notification'][0], 'yes');
					}
					?>
																														/>
						<span><em>&nbsp;<?php esc_html_e('(Check this if you do not want to notify customer by email.)', 'addify_osm'); ?></em></span><br>
					</td>
				</tr>

				<!-- Disable email notification for admin -->
				<tr>
					<th class="head-email"><label for="disable_admin_notification"><?php esc_html_e('Disable Admin Email Notification', 'addify_osm'); ?></label></th>
					<td class=""><input type="checkbox" name="osm_disable_admin_notification" value="no" 
					<?php
					if (isset($osm_admin_notify['osm_disable_admin_notification'])) {
						checked($osm_admin_notify['osm_disable_admin_notification'][0], 'yes');
					}
					?>
																											/>
						<span><em>&nbsp;<?php esc_html_e('(Check this if you do not want to notify Admin by email.)', 'addify_osm'); ?></em></span><br>
					</td>
				</tr>
			</table>
			<?php
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}
		/**
		 * Meta box Html
		 */
		public function status_automation_html() {

			// Get all order Statuses.
			$statuses = array();
			$statuses = wc_get_order_statuses();

			?>

			<table class="status_fields">



				<!-- From status -->

				<tr>
					<th class="time-head"><label for="osm_auto_from_select"><?php esc_html_e('From Status', 'ka_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_auto_from_select" class="status-select">
							<?php
							foreach ($statuses as $key => $status) {
								?>
								<option value="<?php esc_attr_e($key); ?>" <?php selected(get_post_meta(get_the_ID(), 'osm_auto_from_select', true), $key); ?>><?php esc_html_e($status, 'addify_osm'); ?></option>
								<?php
							}
							?>

						</select>

					</td>
				</tr>
				<!-- To status -->
				<tr>
					<th class="time-head"><label for="osm_auto_to_select"><?php esc_html_e('To Status', 'ka_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_auto_to_select" class="status-select">
							<?php
							foreach ($statuses as $key => $status) {
								?>
								<option value="<?php esc_attr_e($key); ?>" <?php selected(get_post_meta(get_the_ID(), 'osm_auto_to_select', true), $key); ?>><?php esc_html_e($status, 'addify_osm'); ?></option>
								<?php
							}
							?>
						</select>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(The "From Status" and "To Status" should be different.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>

			</table>
			<?php
			// Creating nonce.
			wp_nonce_field('status_auto_rules_nonce', 'auto_rules_nonce');
		}

		/**
		 * Save meta box
		 */
		public function save_status_automation_metabox( $post_id ) {
			// If our current user can't edit this post, return.
			if (!current_user_can('edit_posts')) {
				return;
			}
			// Return if the post is in trash.
			$exclude_statuses = array(
				'auto-draft',
				'trash',
			);
			$action_osm       = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
			if (!in_array(get_post_status($post_id), $exclude_statuses) && !is_ajax() && 'untrash' != $action_osm) {

				// Verifying nonce.
				$nonce = isset( $_POST['auto_rules_nonce'] ) ? sanitize_text_field( $_POST['auto_rules_nonce'] ) : 0;
				if ( ! wp_verify_nonce( $nonce, 'status_auto_rules_nonce' ) ) {
					wp_die( esc_html_e( 'Failed Security Check!', 'addify_osm' ) );
				}
				// Rule from status.
				$from_status = ( isset($_POST['osm_auto_from_select']) && !empty($_POST['osm_auto_from_select']) ) ? sanitize_text_field(wp_unslash($_POST['osm_auto_from_select'])) : '';
				update_post_meta($post_id, 'osm_auto_from_select', $from_status);
				// Rule to status.
				$to_status = ( isset($_POST['osm_auto_to_select']) && !empty($_POST['osm_auto_to_select']) ) ? sanitize_text_field(wp_unslash($_POST['osm_auto_to_select'])) : '';
				update_post_meta($post_id, 'osm_auto_to_select', $to_status);
				// Time Intervals.
				$time = ( isset($_POST['osm_auto_time']) && !empty($_POST['osm_auto_time']) ) ? sanitize_text_field(wp_unslash($_POST['osm_auto_time'])) : '';
				update_post_meta($post_id, 'osm_auto_time', $time);

				$unit = ( isset($_POST['osm_unit_select']) && !empty($_POST['osm_unit_select']) ) ? sanitize_text_field(wp_unslash($_POST['osm_unit_select'])) : '';
				update_post_meta($post_id, 'osm_unit_select', $unit);

				// User Roles.

				if (isset($_POST['osm_select_user_roles'])) {

					update_post_meta($post_id, 'osm_select_user_roles', sanitize_meta('', $_POST['osm_select_user_roles'], ''));
				} else {
					delete_post_meta($post_id, 'osm_select_user_roles');
				}
				// Max and Min Order quantity.
				$max_order_quantity = ( isset($_POST['osm_order_max_quantity']) && !empty($_POST['osm_order_max_quantity']) ) ? sanitize_text_field(wp_unslash($_POST['osm_order_max_quantity'])) : '';
				update_post_meta($post_id, 'osm_order_max_quantity', $max_order_quantity);
				$min_order_quantity = ( isset($_POST['osm_order_min_quantity']) && !empty($_POST['osm_order_min_quantity']) ) ? sanitize_text_field(wp_unslash($_POST['osm_order_min_quantity'])) : '';
				update_post_meta($post_id, 'osm_order_min_quantity', $min_order_quantity);
				// Maximum Amount.
				$maximum_amount = ( isset($_POST['osm_maximum_amount']) && !empty($_POST['osm_maximum_amount']) ) ? sanitize_text_field(wp_unslash($_POST['osm_maximum_amount'])) : '';
				update_post_meta($post_id, 'osm_maximum_amount', $maximum_amount);
				// Minimum Amount.
				$minimum_amount = ( isset($_POST['osm_minimum_amount']) && !empty($_POST['osm_minimum_amount']) ) ? sanitize_text_field(wp_unslash($_POST['osm_minimum_amount'])) : '';
				update_post_meta($post_id, 'osm_minimum_amount', $minimum_amount);
				// Products.
				if (isset($_POST['osm_select_product'])) {

					update_post_meta($post_id, 'osm_select_product', sanitize_meta('', $_POST['osm_select_product'], ''));
				} else {
					delete_post_meta($post_id, 'osm_select_product');
				}
				// Categories.
				if (isset($_POST['osm_select_category'])) {

					update_post_meta($post_id, 'osm_select_category', sanitize_meta('', $_POST['osm_select_category'], ''));
				} else {
					delete_post_meta($post_id, 'osm_select_category');
				}
				// Billing Countries.
				if (isset($_POST['osm_select_billing_country'])) {

					update_post_meta($post_id, 'osm_select_billing_country', sanitize_meta('', $_POST['osm_select_billing_country'], ''));
				} else {
					delete_post_meta($post_id, 'osm_select_billing_country');
				}
				// Shipping Countries.
				if (isset($_POST['osm_select_shipping_country'])) {

					update_post_meta($post_id, 'osm_select_shipping_country', sanitize_meta('', $_POST['osm_select_shipping_country'], ''));
				} else {
					delete_post_meta($post_id, 'osm_select_shipping_country');
				}
				// Date created before.
				$date_created_before = ( isset($_POST['osm_date_created_before']) && !empty($_POST['osm_date_created_before']) ) ? sanitize_text_field(wp_unslash($_POST['osm_date_created_before'])) : '';
				update_post_meta($post_id, 'osm_date_created_before', $date_created_before);
				// Date created after.
				$date_created_after = ( isset($_POST['osm_date_created_after']) && !empty($_POST['osm_date_created_after']) ) ? sanitize_text_field(wp_unslash($_POST['osm_date_created_after'])) : '';
				update_post_meta($post_id, 'osm_date_created_after', $date_created_after);
				// Disable customer Notification.
				if (isset($_POST['osm_disable_customer_notification'])) {
					update_post_meta($post_id, 'osm_disable_customer_notification', 'yes');
				} else {
					update_post_meta($post_id, 'osm_disable_customer_notification', 'no');
				}
				// Disable admin Notification.
				if (isset($_POST['osm_disable_admin_notification'])) {
					update_post_meta($post_id, 'osm_disable_admin_notification', 'yes');
				} else {
					update_post_meta($post_id, 'osm_disable_admin_notification', 'no');
				}
			}
		}

		/**
		 * Function to get all user roles
		 */
		public function get_user_roles() {
			global $wp_roles;

			$all_roles = array_merge(array( 'guest' => __('Guest', 'addify_osm') ), $wp_roles->get_names());

			return $all_roles;
		}

		/**
		 * Function to get all categories
		 */
		public  function get_categories() {
			$args = array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,

			);
			$categories = get_terms($args);
			foreach ($categories as $category) {
				$id         = $category->term_id;
				$name       = $category->name;
				$cat[ $id ] = $name;
			}
			return $cat;
		}


		/**
		 * Function to get search results for products
		 */
		public function osm_get_products() {
			// we will pass post IDs and titles to this array.
			$return = array();
			
			// Use WP_Query, query_posts() or get_posts() here  to get posts.
			if (!empty($_GET['q'])) {
				$query = sanitize_text_field($_GET['q']);
			}
			$search_results = new WP_Query(array(
				's'           => $query, // the search query.
				'post_status' => 'publish', // if you don't want drafts to be returned.
				'post_type'   => 'product',
				'numberposts' => -1,
			));
			if ($search_results->have_posts()) :
				while ($search_results->have_posts()) :
					$search_results->the_post();
					// shorten the title a little.
					$title    = ( mb_strlen($search_results->post->post_title) > 50 ) ? mb_substr($search_results->post->post_title, 0, 49) . '...' : $search_results->post->post_title;
					$return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title ).
				endwhile;
			endif;
			echo wp_json_encode($return);
			die;
		}
	}

	new KA_Osm_Automation_Settings();
}
