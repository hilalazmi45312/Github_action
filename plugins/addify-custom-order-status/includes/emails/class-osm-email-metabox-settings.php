<?php

/**
 * Defines email meta box settings
 */
if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Email_Settings')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Email_Settings {
		/**
		 * Constructor of the class
		 */
		public function __construct() {
			// Add custom meta box.
			add_action('add_meta_boxes', array( $this, 'order_status_email_metabox' ));
			// Save meta box.
			add_action('save_post_status_emails', array( $this, 'save_order_status_email_metabox' ));
		}
		public function order_status_email_metabox() {
			add_meta_box(
				'osm_status_email',                 // Unique ID.
				__('Order Status Email Data', 'addify_osm'),      // Box title.
				array( $this, 'order_status_email_html' ),  // Content callback, must be of type callable.
				'status_emails', // Post type.
				'normal',
				'low'
			);
		}
		/** 
		 * Content for customer email.
		 */
		public function order_status_email_html() {
			global $post;
			$osm_email_name       = get_post_meta($post->ID, 'osm_email_name', true);
			$osm_email_desc       = get_post_meta($post->ID, 'osm_email_desc', true);
			$osm_select_recipient = get_post_meta($post->ID, 'osm_select_recipient', true);
			$osm_email_subject    = get_post_meta($post->ID, 'osm_email_subject', true);
			$osm_email_heading    = get_post_meta($post->ID, 'osm_email_heading', true);
			$osm_email_custom_msg = get_post_meta($post->ID, 'osm_email_custom_msg', true);

			// Get all order Statuses.
			$statuses = array();
			$statuses = wc_get_order_statuses();
			?>
			<table class="status_fields">
				<!--  Email Rule Name -->
				<tr>
					<th class="status-head"><label for="osm_email_name"><?php esc_html_e('Rule Title', 'addify_osm'); ?></label></th>
					<td class="status-row"><input required class="status-data" id="osm_email_name" type="text" name="osm_email_name" value="<?php esc_attr_e($osm_email_name); ?>" /><br>
						<span><em>&nbsp;<?php esc_html_e('(Name to display in emails list for your reference.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!-- Email Rule Description -->
				<tr>
					<th class="status-head"><label for="osm_email_desc"><?php esc_html_e('Rule Description', 'addify_osm'); ?></label></th>
					<td class="status-row"><textarea class="osm-textarea" name="osm_email_desc" rows="2"><?php esc_html_e($osm_email_desc); ?></textarea><br>
						<span><em>&nbsp;<?php esc_html_e('(Description in emails list for your reference.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!--  Email Subject -->
				<tr>
					<th class="status-head"><label for="osm_email_subject"><?php esc_html_e('Email Subject', 'addify_osm'); ?></label></th>
					<td class="status-row"><input class="status-data" placeholder="<?php esc_html_e('Your order from {site_title} on {order_date} status is currently, {order_status}.', 'addify_osm'); ?>" id="osm_email_subject" type="text" name="osm_email_subject" value="<?php esc_attr_e($osm_email_subject); ?>" /><br>
						<span>&nbsp;<?php esc_html_e('Available placeholders:', 'addify_osm'); ?><b> <?php esc_html_e('{first_name}, {last_name}, {site_title}, {order_date}, {order_number}, {order_status}', 'addify_osm'); ?> </b></span><br>
						<span class="email-placeholders">&nbsp;<?php esc_html_e('The first and last name indicates customer\'s first and last name.', 'addify_osm'); ?></span>
					</td>

				</tr>
				<!--  Email Heading -->
				<tr>
					<th class="status-head"><label for="osm_email_heading"><?php esc_html_e('Email Heading', 'addify_osm'); ?></label></th>
					<td class="status-row"><input class="status-data" placeholder="<?php esc_html_e('The status of your order is {order_status}.', 'addify_osm'); ?>" id="osm_email_heading" type="text" name="osm_email_heading" value="<?php esc_attr_e($osm_email_heading); ?>" /><br>
						<span>&nbsp;<?php esc_html_e('Available placeholders:', 'addify_osm'); ?><b> <?php esc_html_e('{first_name}, {last_name}, {site_title}, {order_date}, {order_number}, {order_status}', 'addify_osm'); ?> </b></span><br>
						<span class="email-placeholders">&nbsp;<?php esc_html_e('The first and last name indicates customer\'s first and last name.', 'addify_osm'); ?></span>
					</td>
				</tr>
				<!-- Email Description -->
				<tr>
					<th class="status-head"><label for="osm_email_custom_msg"><?php esc_html_e('Custom Message', 'addify_osm'); ?></label></th>
					<td class="status-row">
						<!-- <textarea placeholder="Add custom message that you want to include in status email alert. (Basic order details will be automatically included after this message.)" class="osm-textarea" name="osm_email_custom_msg" rows="6"><?php esc_html_e($osm_email_custom_msg); ?></textarea><br> -->
						<?php
						 $args = array(
							 'textarea_name' => 'osm_email_custom_msg', // Name of the textarea
							 'textarea_rows' => 10, // Number of rows
							 'media_buttons' => true, // Show media upload buttons
							 'teeny'         => true, // Use the minimal editor (no kitchen sink)
							 'wpautop' => false,
							 'tinymce'       => array(
								 'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink',
								 'toolbar2' => 'undo,redo,removeformat,fullscreen',
								 'block_formats' => 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;', // Include all available block formats
							 ),
						 );
					
						// Display the editor
						wp_editor($osm_email_custom_msg, 'osm_email_custom_msg', $args);
							?>
						<span>&nbsp;<?php esc_html_e('Available placeholders:', 'addify_osm'); ?><b> <?php esc_html_e('{first_name}, {last_name}, {site_title}, {order_date}, {order_number}, {order_status}', 'addify_osm'); ?> </b></span><br>
						<span class="email-placeholders">&nbsp;<?php esc_html_e('The first and last name indicates customer\'s first and last name.', 'addify_osm'); ?></span>
					</td>
				</tr>
				<!-- Select for email recipient-->
				<tr>
					<th class="status-head"><label for="osm_select_recipient"><?php esc_html_e('Send Email to', 'ka_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_select_recipient" class="status-select">
							<option value="customer" <?php selected($osm_select_recipient, 'customer'); ?>><?php esc_html_e('Customer', 'addify_osm'); ?></option>
							<option value="admin" <?php selected($osm_select_recipient, 'admin'); ?>><?php esc_html_e('Admin', 'addify_osm'); ?></option>

						</select>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(Select the recipient of the email.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>

				<!-- From status -->

				<tr>
					<th class="status-head"><label for="osm_from_select"><?php esc_html_e('From Status', 'ka_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_from_select" class="status-select">
							<option value="any" <?php selected(get_post_meta(get_the_ID(), 'osm_from_select', true), 'any'); ?>><?php esc_html_e('Any', 'addify_osm'); ?></option>
							<?php
							foreach ($statuses as $key => $status) {
								?>
								<option value="<?php esc_attr_e($key); ?>" <?php selected(get_post_meta(get_the_ID(), 'osm_from_select', true), $key); ?>><?php esc_html_e($status, 'addify_osm'); ?></option>
								<?php
							}
							?>

						</select>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(Select from status.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>
				<!-- To status -->
				<tr>
					<th class="status-head"><label for="osm_to_select"><?php esc_html_e('To Status', 'ka_osm'); ?></label></th>
					<td class="status-row">
						<select name="osm_to_select" class="status-select">
							<?php
							foreach ($statuses as $key => $status) {
								?>
								<option value="<?php esc_attr_e($key); ?>" <?php selected(get_post_meta(get_the_ID(), 'osm_to_select', true), $key); ?>><?php esc_html_e($status, 'addify_osm'); ?></option>
								<?php
							}
							?>
						</select>
						<br>
						<span><em>&nbsp;<?php esc_html_e('(Select to status.)', 'addify_osm'); ?></em></span>
					</td>
				</tr>


			</table>

			<?php
			// Creating nonce.
			wp_nonce_field('email_status_nonce', 'emails_nonce');
		}
		public function save_order_status_email_metabox( $post_id ) {
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
				$nonce = isset( $_POST['emails_nonce'] ) ? sanitize_text_field( $_POST['emails_nonce'] ) : 0;
				if ( ! wp_verify_nonce( $nonce, 'email_status_nonce' ) ) {
					wp_die( esc_html_e( 'Failed Security Check!', 'addify_osm' ) );
				}
				// Rule Name.
				$email_name = ( isset($_POST['osm_email_name']) && !empty($_POST['osm_email_name']) ) ? sanitize_text_field(wp_unslash($_POST['osm_email_name'])) : '';
				update_post_meta($post_id, 'osm_email_name', $email_name);
				//Select Email Recipient.
				$email_recipient = ( isset($_POST['osm_select_recipient']) && !empty($_POST['osm_select_recipient']) ) ? sanitize_text_field(wp_unslash($_POST['osm_select_recipient'])) : '';
				update_post_meta($post_id, 'osm_select_recipient', $email_recipient);
				// Rule Description.
				$email_desc = ( isset($_POST['osm_email_desc']) && !empty($_POST['osm_email_desc']) ) ? sanitize_text_field(wp_unslash($_POST['osm_email_desc'])) : '';
				update_post_meta($post_id, 'osm_email_desc', $email_desc);
				// Select From Status.
				$from_status = ( isset($_POST['osm_from_select']) && !empty($_POST['osm_from_select']) ) ? sanitize_text_field(wp_unslash($_POST['osm_from_select'])) : '';
				update_post_meta($post_id, 'osm_from_select', $from_status);
				// Select To Status.
				$to_status = ( isset($_POST['osm_to_select']) && !empty($_POST['osm_to_select']) ) ? sanitize_text_field(wp_unslash($_POST['osm_to_select'])) : '';
				update_post_meta($post_id, 'osm_to_select', $to_status);
				// Email Subject.
				$email_subject = ( isset($_POST['osm_email_subject']) && !empty($_POST['osm_email_subject']) ) ? sanitize_text_field(wp_unslash($_POST['osm_email_subject'])) : '';
				update_post_meta($post_id, 'osm_email_subject', $email_subject);
				// Email Heading.
				$email_heading = ( isset($_POST['osm_email_heading']) && !empty($_POST['osm_email_heading']) ) ? sanitize_text_field(wp_unslash($_POST['osm_email_heading'])) : '';
				update_post_meta($post_id, 'osm_email_heading', $email_heading);
				// Email Custom Message.
					$custom_msg = ( isset($_POST['osm_email_custom_msg']) && !empty($_POST['osm_email_custom_msg']) ) ? sanitize_meta('', $_POST['osm_email_custom_msg'], '') : '';
				update_post_meta($post_id, 'osm_email_custom_msg', $custom_msg);
			}
		}
	}
	new KA_Osm_Email_Settings();
}
