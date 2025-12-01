<?php
/**
 * Imports Custom Order Statuses
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Status_Import')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Status_Import {
		public function __construct() {
			// Add Import button.
			add_filter('admin_head-edit.php', array( $this, 'import_statuses_button' ));
			add_action('admin_init', array( $this, 'import_status_csv' ));
		}

		/**
		 * Add import custom order status button
		 */
		public function import_statuses_button() {
			global $current_screen;
			if ('order_status' != $current_screen->post_type) {
				return;
			}
			?>

			<!-- Trigger/Open The Modal -->
			<button id="import-csv" class="add-new-h2">Import Custom Statuses</button>

			<!-- The Modal -->
			<div id="fileModal" class="modal file-modal">

				<!-- Modal content -->
				<div class="modal-content wrap">
					<div class="import-modal">
						<span class="delete-close">&times;</span>
						<h4>Please choose a file and click upload.</h4>
						<form method="POST" enctype="multipart/form-data" id="import-form">
							<?php
							// Creating nonce.
							
							wp_nonce_field('custom_order_status_nonce', 'order_status_nonce');
							?>
							<input required type="file" accept=".csv" name="osm_file" id=""><br>
							<button type="submit" name="osm-import" id='upload-csv' class="add-new-h2">Upload</button>
							<span id="import-msg">Please upload the file in <b>csv</b> format.</span><br>
							<span id="import-format">Please Download the file below to see the file <b>Import</b> format.</span>
							<br>
							<a href="<?php echo esc_url(OSM_PLUGIN_URL); ?>assests/files/statuses_format.xlsx" id="download-csv">Download Import File Format</a>
						</form>

					</div>
				</div>
			</div>
			<?php
		}
		
		/**
		 * Import csv file
		 */
		public function import_status_csv() {
			
			if (isset($_POST['osm-import'])) {

				if ( empty( $_POST['order_status_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['order_status_nonce'] ), 'custom_order_status_nonce' ) ) {
					
					wp_die(esc_html_e('Our site is protected!', 'addify_osm'), esc_html_e('Error', 'addify_osm'), array(
						'response'  => 403,
						'back_link' => 'edit.php?post_type=order_status',
					));

				} 
				
				if (!empty($_FILES['osm_file']['tmp_name'])) {
					$import_filename = sanitize_text_field($_FILES['osm_file']['tmp_name']);
					
				}
				if (!empty($_FILES['osm_file']['size'])) {
					if ($_FILES['osm_file']['size'] > 0) {

						$file_import = fopen($import_filename, 'r');
						fgetcsv($file_import); // Discard the file header.

						while ($get_data = fgetcsv($file_import)) {
							$status_name     = $get_data[0];
							$status_slug     = $get_data[1];
							$status_desc     = $get_data[2];
							$bg_color        = $get_data[3];
							$text_color      = $get_data[4];
							$graphic_style   = $get_data[5];
							$user_cancel     = $get_data[6];
							$paid_Status     = $get_data[7];
							$bulk_action     = $get_data[8];
							$show_in_reports = $get_data[9];
							$hide_status     = $get_data[10];




							//Create a new post.
							$new_post = array(
								'post_type'   => 'order_status',
								'post_status' => 'publish',
							);
							$post_id  = wp_insert_post($new_post);
							update_post_meta($post_id, 'osm_name', $status_name);
							update_post_meta($post_id, 'osm_slug', $status_slug);
							update_post_meta($post_id, 'osm_desc', $status_desc);
							update_post_meta($post_id, 'osm_color', $bg_color);
							update_post_meta($post_id, 'osm_text_color', $text_color);
							update_post_meta($post_id, 'osm_select', $graphic_style);
							update_post_meta($post_id, 'osm_user_can_cancel', $user_cancel);
							update_post_meta($post_id, 'osm_paid_select', $paid_Status);
							update_post_meta($post_id, 'osm_bulk_check', $bulk_action);
							update_post_meta($post_id, 'osm_report_check', $show_in_reports);
							update_post_meta($post_id, 'osm_hide_check', $hide_status);
						}

						fclose($file_import);
						
					}
				}
			}
		}
	}
	new KA_Osm_Status_Import();
}
