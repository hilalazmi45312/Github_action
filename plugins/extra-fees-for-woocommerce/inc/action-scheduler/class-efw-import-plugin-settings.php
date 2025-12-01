<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'EFW_Import_Plugin_Settings' ) ) {

	/**
	 * EFW_Import_Plugin_Settings Class.
	 */
	class EFW_Import_Plugin_Settings extends EFW_Action_Scheduler {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id                            = 'efw_import_settings_action_data';
			$this->action_scheduler_name         = 'efw_import_settings_action_scheduler';
			$this->chunked_action_scheduler_name = 'efw_chunk_single_action_import_settings';
			$this->settings_option_name          = 'efw_import_settings_args';

			add_action( 'admin_init', array( $this, 'import_plugin_settings' ) );
			parent::__construct();
		}

		/**
		 * Get progress bar label.
		 *
		 * @return String
		 */
		public function get_progress_bar_label() {
			return esc_html__( 'Importing Plugin settings is under progress...', 'extra-fees-for-woocommerce' );
		}

		/**
		 * Get success message.
		 *
		 * @return String
		 */
		public function get_success_message() {
			return esc_html__( 'Plugin settings imported successfully.', 'extra-fees-for-woocommerce' );
		}

		/**
		 * Get redirect URL.
		 */
		public function get_redirect_url() {
			return efw_get_settings_page_url(
				array( 'tab' => 'advance' )
			);
		}

		/**
		 * Import Plugin Settings
		 *
		 * @throws Exception If $_POST is empty.
		 * */
		public function import_plugin_settings() {

			if ( isset( $_REQUEST[ 'efw_advance_import_plugin_settings' ] ) ) {
				$imported_settings = get_option('efw_imported_data');
				if ( ! efw_check_is_array( $imported_settings )) {
					return;
				}
				// Schedule import settings action.
				$this->schedule_action( $imported_settings, array() );

				$redirect_url = efw_get_settings_page_url(
					array(
						'tab'                  => 'advance',
						'efw_action_scheduler' => $this->get_id(),
					)
				);
				
				wp_safe_redirect( $redirect_url );
				exit();
			}
		}

		/**
		 * May be update product meta.
		 *
		 * @param array $product_ids Product Ids.
		 */
		public function chunked_scheduler_action( $imported_settings ) {
			if ( ! efw_check_is_array( $imported_settings ) ) {
				return;
			}

			foreach ( $imported_settings as $imported_setting ) {

				if ( ! efw_check_is_array( $imported_setting ) ) {
					continue;
				}

				$meta_key = isset($imported_setting['0']) ? $imported_setting['0'] : '';

				if (empty($meta_key)) {
					continue;
				}

				$meta_value = isset($imported_setting['1']) ? $imported_setting['1'] : '';

				update_option($meta_key, $meta_value);
			}
		}

		/**
		 * Display progress bar.
		 */
		public function progress_bar() {

			$percent = $this->get_progress_count();
			?>
			<div class="efw_progress_bar_wrapper">
				<h1><?php esc_html_e( 'Extra Fees for WooCommerce', 'extra-fees-for-woocommerce' ); ?></h1>
				<div id="efw_progress_bar_label">
					<h2><?php esc_html_e( 'Importing settings is under progress...', 'extra-fees-for-woocommerce' ); ?></h2>
				</div>
				<div class="efw_progress_bar_outer">
					<div class="efw_progress_bar_inner" style="width: <?php echo esc_attr( $percent ); ?>%">

					</div>
				</div>
				<div id="efw_progress_bar_status">
					<span id="efw_progress_bar_current_status"><?php echo esc_html( $percent ); ?></span>
					<?php esc_html_e( '% Completed', 'extra-fees-for-woocommerce' ); ?>
				</div>
				<p>
					<span>
						<?php
						$scheduler_url = add_query_arg(
							array(
								'page'   => 'action-scheduler',
								'status' => 'pending',
							),
							admin_url( 'tools.php' )
						);
						/* translators: %s : Scheduled Action URL */
						echo wp_kses_post( sprintf( '<b>Note:</b> <br/> 1. Please check and run the <b>Action Scheduler</b> name <b>"efw_bulk_update_action_scheduler"</b> in <b>"Tools -> <a href="%s" target="_blank">Scheduled Actions</a>"</b> if you find any difficulties. <br/> 2. If you wish to check the Bulk Update process once the action is scheduled, then please check the Action Scheduler name <b>"efw_chunk_single_action_bulk_update"</b>.', esc_url( $scheduler_url ) ) );
						?>
					</span>
				</p>
			</div>
			<?php
		}
	}
}
