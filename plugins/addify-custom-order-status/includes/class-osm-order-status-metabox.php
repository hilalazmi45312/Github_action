<?php

/**
 * Defines Meta box settings for Order Statuses
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Order_Status_Data')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Order_Status_Data {
		/**
		 * Constructor of the class
		 */
		public function __construct() {
			// Add custom meta box.
			add_action('add_meta_boxes', array( $this, 'order_status_data_metabox' ));
			// Save meta box.
			add_action('save_post_order_status', array( $this, 'save_order_status_metabox' ));
			// Show ajax response on delete.
			add_action('wp_ajax_delete_ajax_request', array( $this, 'delete_ajax_request' ));
			add_action('wp_ajax_delete_ajax_request', array( $this, 'delete_ajax_request' ));
			// Reassign Status on delete.
			add_action('woocommerce_after_register_post_type', array( $this, 'reassign_status' ));
		}

		/**
		 * Function to create meta box to get custom order status data
		 */
		public function order_status_data_metabox() {
			add_meta_box(
				'osm_data',                 // Unique ID.
				__('Order Status Data', 'addify_osm'),      // Box title.
				array( $this, 'order_status_data_html' ),  // Content callback, must be of type callable.
				'order_status', // Post type.
				'normal',
				'low'
			);
		}

		/**
		 * Function to generate html for custom order status data meta box
		 */
		public function order_status_data_html() {
			global $post;
			$osm_name            = get_post_meta($post->ID, 'osm_name', true);
			$osm_slug            = get_post_meta($post->ID, 'osm_slug', true);
			$osm_desc            = get_post_meta($post->ID, 'osm_desc', true);
			$osm_color           = get_post_meta($post->ID, 'osm_color', true);
			$osm_text_color      = get_post_meta($post->ID, 'osm_text_color', true);
			$osm_icon            = get_post_meta($post->ID, 'osm_icon', true);
			$osm_select          = get_post_meta($post->ID, 'osm_select', true);
			$osm_user_can_cancel = get_post_meta($post->ID);

			$osm_bulk_check   = get_post_meta($post->ID);
			$osm_show_check   = get_post_meta($post->ID);
			$osm_report_check = get_post_meta($post->ID);
			$osm_paid_select  = get_post_meta($post->ID, 'osm_paid_select', true);
			// Make slug read only after it is created.
			if ('' !== get_post_meta($post->ID, 'osm_slug', true)) {
				$slug_readonly = 'readonly';
			} else {
				$slug_readonly = '';
			}

			?>
			<table class="status_fields">
				<!-- Name -->
				<tr>
					<th class="status-head"><label for="osm_order_status_name"><?php esc_html_e('Name', 'addify_osm'); ?></label></th>
					<td class="status-row"><input required pattern="[a-zA-Z ]+" class="status-data" id="osm-name" type="text" name="osm_name" value="<?php esc_attr_e($osm_name); ?>" onkeyup="check_status_name(this)" /><br>
						<span><em>&nbsp;<?php esc_html_e('(The name should contain only alphabets.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!-- Slug -->
					<tr>
					<th class="status-head"><label for="osm_order_status_slug"><?php esc_html_e('Slug', 'addify_osm'); ?></label></th>
		<td class="status-row">
  <input 
	required 
	type="text" 
	id="osm-slug" 
	name="osm_slug"
	class="status-data" 
	value="<?php esc_attr_e($osm_slug); ?>" 
	<?php echo esc_attr($slug_readonly); ?> 
	pattern="^[a-z]+(-[a-z]+)*$" 
	maxlength="15" 
	title="Only lowercase letters and hyphens allowed. No spaces or uppercase letters. Max 15 characters."
  />
  <br>
  <span>
	<em>&nbsp;<?php esc_html_e('(The slug should contain only lowercase alphabets without spaces. You can use hyphen - between words.)', 'addify_osm'); ?></em>
	<b><?php esc_html_e(' The slug should consist of 15 characters only.', 'addify_osm'); ?></b>
  </span>
</td>
				</tr>
				<!-- Description -->
				<tr>
					<th class="status-head"><label for="order_status_description"><?php esc_html_e('Description', 'addify_osm'); ?></label></th>
					<td class="status-row"><textarea class="osm-textarea" name="osm_desc" rows="4"><?php esc_html_e($osm_desc); ?></textarea></td>
				</tr>
				<tr>
					<td colspan="2">
						<hr />
					</td>
				</tr>
				<!-- Background Color -->
				<tr>
					<th class="status-head"><label for="order_status_color"><?php esc_html_e('Background Color', 'addify_osm'); ?></label></th>
					<td class="status-row"><input required name="osm_color" type="color" value="<?php echo esc_attr($osm_color); ?>" style="width:30%;" /><span><em>&nbsp;<?php esc_html_e('(Color displayed behind order status name.)', 'addify_osm'); ?></em></span></td>
				</tr>
				<!-- Text Color -->
				<tr>
					<th class="status-head"><label for="osm_text_color"><?php esc_html_e('Text Color', 'addify_osm'); ?></label></th>
					<td class="status-row"><input required name="osm_text_color" type="color" value="<?php echo esc_attr($osm_text_color); ?>" style="width:30%;" /><span><em>&nbsp;<?php esc_html_e('(Icon Color or Text color of order status name.)', 'addify_osm'); ?></em></span></td>
				</tr>

				<!-- Graphic Style-->
				<tr>
					<th class="status-head"><label for="order_status_select"><?php esc_html_e('Graphic Style', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_select" class="status-select">
							<option value="text" <?php selected($osm_select, 'text'); ?>><?php esc_html_e('Text', 'addify_osm'); ?></option>
							<option value="icon" <?php selected($osm_select, 'icon'); ?>><?php esc_html_e('Icon', 'addify_osm'); ?></option>

						</select>
					</td>
				</tr>
				<!-- Icon -->
				<tr class="status-icon">
					<th class="status-head"><label for="order_status_icon"><?php esc_html_e('Icon', 'addify_osm'); ?></label></th>
					<td class="status-row"><input class="regular-text" id="dashicons_picker_example_icon1" type="text" name="osm_icon" value="<?php esc_html_e($osm_icon); ?>" />
						<input class="button dashicons-picker" type="button" value="Choose Icon" data-target="#dashicons_picker_example_icon1" /><span><em>&nbsp;<?php esc_html_e('(Icon of your Status.)', 'addify_osm'); ?></em></span>
				</tr>

				<tr>
					<td colspan="2">
						<hr />
					</td>
				</tr>
				<!-- User can cancel check -->
				<tr>
					<th class="status-head"><label for="osm_order_status_cancel"><?php esc_html_e('User can cancel', 'addify_osm'); ?></label></th>
					<td class="status-row"><input type="checkbox" name="osm_user_can_cancel" value="no" 
					<?php
					if (isset($osm_user_can_cancel['osm_user_can_cancel'])) {
						checked($osm_user_can_cancel['osm_user_can_cancel'][0], 'yes');
					}
					?>
																										/>
						<span><em>&nbsp;<?php esc_html_e('(Choose whether customers can cancel orders.)', 'addify_osm'); ?></em></span>
					</td>

				</tr>
				<!-- Paid -->
				<tr>
					<th class="status-head"><label for="order_paid_status"><?php esc_html_e('Paid', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_paid_select" class="status-select">
							<option value="osm_not_paid" <?php selected($osm_paid_select, 'osm_not_paid'); ?>><?php esc_html_e('Orders are neither paid or nor require payment.', 'addify_osm'); ?></option>
							<option value="osm_paid_status" <?php selected($osm_paid_select, 'osm_paid_status'); ?>><?php esc_html_e('Orders with this status have been paid.', 'addify_osm'); ?></option>
							<option value="osm_need_payment" <?php selected($osm_paid_select, 'osm_need_payment'); ?>><?php esc_html_e('Orders with this status require payment (similar to pending payment).', 'addify_osm'); ?></option>


						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<hr />
					</td>
				</tr>
				<!-- Bulk Action Check -->
				<tr>
					<th class="status-head"><label for="bulk_action"><?php esc_html_e('Bulk Action', 'addify_osm'); ?></label></th>
					<td class="status-row"><input type="checkbox" name="osm_bulk_check" value="no" 
					<?php
					if (isset($osm_bulk_check['osm_bulk_check'])) {
						checked($osm_bulk_check['osm_bulk_check'][0], 'yes');
					}
					?>
																									/>
						<span><em>&nbsp;<?php esc_html_e('(Check this to add this order status to Order list table Bulk action list.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!-- Display in Reports Check -->
				<tr>
					<th class="status-head"><label for="osm_report_check"><?php esc_html_e('Display in Reports', 'addify_osm'); ?></label></th>
					<td class="status-row"><input type="checkbox" name="osm_report_check" value="no" 
					<?php
					if (isset($osm_report_check['osm_report_check'])) {
						checked($osm_report_check['osm_report_check'][0], 'yes');
					}
					?>
																										/>
						<span><em>&nbsp;<?php esc_html_e('(Choose whether you want to include orders marked with this status in Reports.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!-- Hide Custom Status from customer -->
				<tr>
					<th class="status-head"><label for="osm_hide_check"><?php esc_html_e('Hide from customer', 'addify_osm'); ?></label></th>
					<td class="status-row"><input type="checkbox" name="osm_hide_check" value="no" 
					<?php
					if (isset($osm_show_check['osm_hide_check'])) {
						checked($osm_show_check['osm_hide_check'][0], 'yes');
					}
					?>
																									/>
						<span><em>&nbsp;<?php esc_html_e('(Check this if you do not want to show this status to customer.)', 'addify_osm'); ?></em></span><br>
						<span class="hide-note"><em>&nbsp;<?php esc_html_e('(Note: If Hide from customer check is enabled, Cancel and Pay Buttons will not be visible to the customers.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
			</table>
			<?php
			// Creating nonce.
			wp_nonce_field('custom_order_status_nonce', 'order_status_nonce');
		}

		/**
		 * Function to save meta box values
		 */
		public function save_order_status_metabox( $post_id ) {

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
				$nonce = isset($_POST['order_status_nonce']) ? sanitize_text_field($_POST['order_status_nonce']) : 0;
				if (!wp_verify_nonce($nonce, 'custom_order_status_nonce')) {
					wp_die(esc_html_e('Failed Security Check!', 'addify_osm'));
				}
				// Status Name.
				$status_name = ( isset($_POST['osm_name']) && !empty($_POST['osm_name']) ) ? sanitize_text_field(wp_unslash($_POST['osm_name'])) : '';
				update_post_meta($post_id, 'osm_name', $status_name);
				// Status Description.
				$status_desc = ( isset($_POST['osm_desc']) && !empty($_POST['osm_desc']) ) ? sanitize_text_field(wp_unslash($_POST['osm_desc'])) : '';
				update_post_meta($post_id, 'osm_desc', $status_desc);
				// Background Color.
				$osm_color = ( isset($_POST['osm_color']) && !empty($_POST['osm_color']) ) ? sanitize_text_field(wp_unslash($_POST['osm_color'])) : '#ffffff';
				update_post_meta($post_id, 'osm_color', $osm_color);
				// Background Color.
				$text_color = ( isset($_POST['osm_text_color']) && !empty($_POST['osm_text_color']) ) ? sanitize_text_field(wp_unslash($_POST['osm_text_color'])) : '#000000';
				update_post_meta($post_id, 'osm_text_color', $text_color);
				// Status Slug.
				$status_slug = ( isset($_POST['osm_slug']) && !empty($_POST['osm_slug']) ) ? sanitize_text_field(wp_unslash($_POST['osm_slug'])) : '';
				update_post_meta($post_id, 'osm_slug', $status_slug);
				// Status Icon.
				$status_icon = ( isset($_POST['osm_icon']) && !empty($_POST['osm_icon']) ) ? sanitize_text_field(wp_unslash($_POST['osm_icon'])) : 'dashicons-cart';
				update_post_meta($post_id, 'osm_icon', $status_icon);
				// Graphic Style Select.
				$graphic_style = ( isset($_POST['osm_select']) && !empty($_POST['osm_select']) ) ? sanitize_text_field(wp_unslash($_POST['osm_select'])) : '';
				update_post_meta($post_id, 'osm_select', $graphic_style);

				// User can cancel check.
				if (isset($_POST['osm_user_can_cancel'])) {
					update_post_meta($post_id, 'osm_user_can_cancel', 'yes');
				} else {
					update_post_meta($post_id, 'osm_user_can_cancel', 'no');
				}
				// Is paid Status.
				$paid_select = ( isset($_POST['osm_paid_select']) && !empty($_POST['osm_paid_select']) ) ? sanitize_text_field(wp_unslash($_POST['osm_paid_select'])) : 'osm_not_paid';
				update_post_meta($post_id, 'osm_paid_select', $paid_select);
				// Bulk Check.
				if (isset($_POST['osm_bulk_check'])) {
					update_post_meta($post_id, 'osm_bulk_check', 'yes');
				} else {
					update_post_meta($post_id, 'osm_bulk_check', 'no');
				}
				// Hide from Customer.
				if (isset($_POST['osm_hide_check'])) {
					update_post_meta($post_id, 'osm_hide_check', 'yes');
				} else {
					update_post_meta($post_id, 'osm_hide_check', 'no');
				}
				// Display in Reports check.
				if (isset($_POST['osm_report_check'])) {
					update_post_meta($post_id, 'osm_report_check', 'yes');
				} else {
					update_post_meta($post_id, 'osm_report_check', 'no');
				}
			}
		}


		/**
		 * Function which will generate ajax response on clicking trash.
		 */
		public function delete_ajax_request() {
			if (!isset($_POST['delete_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['delete_nonce']), 'delete_ajax_nonce')) {
				wp_die(esc_html_e('Our site is protected!', 'addify_osm'), esc_html_e('Error', 'addify_osm'), array(
					'response'  => 403,
					'back_link' => 'edit.php?post_type=order_status',
				));
			} else {
				// Get all order Statuses
				$statuses = array();
				$statuses = wc_get_order_statuses();
				$post_id  = '';
				if (!empty($_POST['post_id'])) {
					$post_id = sanitize_text_field($_POST['post_id']);
				}

				// Grab the id after excluding (post-)
				$status_id      = substr($post_id, strpos($post_id, '-') + 1);
				$current_status = get_post_meta($status_id, 'osm_slug', true);
				$args           = array(
					'post_type'      => 'shop_order',
					'posts_per_page' => '-1',
					'fields'         => 'ids',
					'post_status'    => 'wc-' . $current_status,
				);
				$my_query       = new WP_Query($args);
				$orders         = $my_query->get_posts();

				// Check if orders exists with current status
				if (!empty($orders)) {
					?>
					<form method="POST">

						<div id="myModal" class="modal">
							<div class="modal-content">
								<span class="delete-close">&times;</span>
								<h6> Are you sure that you want to delete this order status?</h6>
								<p style="color:#2271b1"> Currently, there are <b><?php esc_html_e(count($orders)); ?></b> order(s) with status <b><?php esc_html_e($current_status); ?></b>. </p>
								<p>Please choose to reassign the status of existing orders with another before delete.</p>
								<select id="delete-select" name="osm_delete_select">
									<?php
									foreach ($statuses as $key => $status) {
										?>
										<option value="<?php esc_attr_e($key); ?>" <?php selected(get_post_meta(get_the_ID(), 'osm_delete_select', true), $key); ?>><?php esc_html_e($status); ?> </option>
										<?php
									}
									?>
								</select>
								<button class="reassign-btn add-new-h2" type="submit" name="reassign_delete">Reassign and Delete</button>
								<input type=hidden name="status_id" value="<?php esc_attr_e($status_id); ?>">
							</div>
						</div>
					</form>
					<?php
				} else {

					esc_html_e('status_not_found');
					wp_trash_post($status_id);
				}
			}

			wp_die();
		}

		/**
		 * Function which will change the status of order when user clicks on delete and reassign button.
		 */
		public function reassign_status() {
			
			if (isset($_POST['reassign_delete'])) {

				// Nounce Verify.
				if ( empty( $_POST['delete_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['delete_nonce'] ), 'delete_ajax_nonce' ) ) {
					
					wp_die(esc_html_e('Our site is protected!', 'addify_osm'), esc_html_e('Error', 'addify_osm'), array(
						'response'  => 403,
						'back_link' => 'edit.php?post_type=order_status',
					));

				} 

				$status_id = '';

				$new_status = '';
				if (!empty($_POST['status_id'])) {
					$status_id = sanitize_text_field($_POST['status_id']);
				}

				$current_status = get_post_meta($status_id, 'osm_slug', true);
				if (!empty($_POST['osm_delete_select'])) {
					$new_status = sanitize_text_field($_POST['osm_delete_select']);
				}

				if (get_post_type($status_id) == 'order_status') {
					$args     = array(
						'post_type'      => 'shop_order',
						'posts_per_page' => '-1',
						'post_status'    => 'wc-' . $current_status,
						'fields'         => 'ids',
					);
					$my_query = new WP_Query($args);
					$orders   = $my_query->get_posts();

					// If orders with current status exists then update
					if ($orders) {
						foreach ($orders as $order_id) {


							$order = wc_get_order($order_id);


							// If status order status matches with current status then update it new status
							if ($order->get_status() == $current_status) {


								$updated_order = array(
									'ID'          => $order_id,
									'post_status' => $new_status,
								);
								wp_update_post($updated_order);
								wp_trash_post($status_id);
								add_action('admin_notices', array( $this, 'status_delete_info' ));
							}
						}
					}
				}
			}
		}

		public function status_delete_info() {

			$osm_woo_info = '<div class="notice notice-info is-dismissible">
				<p>' . __('Order Status has been moved to trash successfully!', 'addify_osm') . ' </p></div>';
			echo wp_kses_post($osm_woo_info);
		}
	}
	new KA_Osm_Order_Status_Data();
}
