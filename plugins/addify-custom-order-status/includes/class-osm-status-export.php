<?php
/**
 * Exports Custom Order Statuses
 */

if (!defined('ABSPATH')) {
	die;
}

/**
 * Class start.
 */
if (!class_exists('KA_Osm_Status_Export')) {
	/**
	 * Define Class
	 */
	class KA_Osm_Status_Export {
		/**
		 * Constructor of the class
		 */
		public function __construct() {
			// Add Export button
			add_filter('admin_head-edit.php', array( $this, 'export_statuses_button' ));
			// Export Csv function
			add_action('admin_init', array( $this, 'export_status_csv' ));
		}
		
		/**
		 * Function to create export button
		 */
		public function export_statuses_button() {
			global $current_screen;
			if ('order_status' != $current_screen->post_type) {
				return;
			}
			?>
			<a href="<?php print esc_url(wp_nonce_url(admin_url('edit.php?post_type=order_status&action=export_csv'), 'export_csv_nonce', 'export_nonce')); ?>"  id="export-csv" class="add-new-h2">Export Custom Statuses</a>

			<?php
		}
		
		/**
		 * Export csv file
		 */
		public function export_status_csv() {
			
			if (isset($_GET['action']) && 'export_csv' == $_GET['action']) {
				ob_start();
				// Define the name of the csv file name.
				$filename = 'order_status-' . time() . '.csv';
				// Define the header row for csv.
				$header_row  = array(
					'Status Name',
					'Status Slug',
					'Status Description',
					'Background Color',
					'Text Color',
					'Graphic Style',
					'User can cancel',
					'Is paid status',
					'Bulk Action',
					'Display in Reports',
					'Hide from Customer',
				);
				$status_rows = array();
				$arguments   = array(
					'posts_per_page' => -1,
					'post_type'      =>  'order_status',
					'post_status'    => 'publish',
				);
				// Get all custom order statuses.
				$posts = get_posts($arguments);
				foreach ($posts as $post) {
					$row = array(
						get_post_meta($post->ID, 'osm_name', true),
						get_post_meta($post->ID, 'osm_slug', true),
						get_post_meta($post->ID, 'osm_desc', true),
						get_post_meta($post->ID, 'osm_color', true),
						get_post_meta($post->ID, 'osm_text_color', true),
						get_post_meta($post->ID, 'osm_select', true),
						get_post_meta($post->ID, 'osm_user_can_cancel', true),
						get_post_meta($post->ID, 'osm_paid_select', true),
						get_post_meta($post->ID, 'osm_bulk_check', true),
						get_post_meta($post->ID, 'osm_report_check', true),
						get_post_meta($post->ID, 'osm_hide_check', true),
					);
					// Store all custom fields.
					$status_rows[] = $row;
				}

				
				$fh = fopen( OSM_PLUGIN_DIR . 'assests/export/export.csv', 'w+');

				fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Content-Description: File Transfer');
				header('Content-type: text/csv');
				header('Content-Disposition: attachment; filename="export.csv";' );
				header('Expires: 0');
				header('Pragma: public');
				fputcsv($fh, $header_row);
				foreach ($status_rows as $status_row) {
					fputcsv($fh, $status_row);
				}
				echo wp_kses_post( file_get_contents( OSM_PLUGIN_DIR . 'assests/export/export.csv' ) );
				fclose($fh);

				ob_end_flush();

				die();
			}
		}
	}
	new KA_Osm_Status_Export();
}
