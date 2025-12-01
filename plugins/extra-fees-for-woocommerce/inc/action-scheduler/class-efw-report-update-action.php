<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'EFW_Report_Update_Action' ) ) {

	/**
	 * EFW_Report_Update_Action Class.
	 */
	class EFW_Report_Update_Action extends EFW_Action_Scheduler {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id                            = 'efw_report_update_action_data';
			$this->action_scheduler_name         = 'efw_report_update_action_scheduler';
			$this->chunked_action_scheduler_name = 'efw_chunk_single_action_report_update';

			parent::__construct();
		}

		/**
		 * Get progress bar label.
		 *
		 * @return String
		 */
		public function get_progress_bar_label() {
			return esc_html__( 'Report Update is under progress...', 'extra-fees-for-woocommerce' );
		}

		/**
		 * Get success message.
		 *
		 * @return String
		 */
		public function get_success_message() {
			return esc_html__( 'Report Updated successfully.', 'extra-fees-for-woocommerce' );
		}

		/**
		 * Get redirect URL.
		 */
		public function get_redirect_url() {
			return efw_get_settings_page_url(
				array( 'tab' => 'productfee' )
			);
		}

		/**
		 * Update Report
		 *
		 * @throws Exception If $_POST is empty.
		 * */
		public function efw_update_report() {
			$args = array(
				'meta_query' => array(
					array(
						'key'     => 'efw_order_id',
						'value'   => '0',
						'compare' => '!=',
					),
				),
			);
			$fee_ids = efw_get_fees_ids( $args );
			if ( ! efw_check_is_array( $fee_ids )) {
				return;
			}
			// Schedule import settings action.
			$this->schedule_action( $fee_ids, array() );

			$redirect_url = efw_get_settings_page_url(
				array(
					'tab'                  => 'productfee',
					'efw_action_scheduler' => $this->get_id(),
				)
			);
			
			wp_safe_redirect( $redirect_url );
		}

		/**
		 * May be update product meta.
		 *
		 * @param array $fee_ids Fee Ids.
		 */
		public function chunked_scheduler_action( $fee_ids ) {
			if ( ! efw_check_is_array( $fee_ids ) ) {
				return;
			}

			$meta_args = array();
			foreach ( $fee_ids as $fee_id ) {
				$fee = efw_get_fees($fee_id);

				$order = $fee->get_order_id();
				if ( !is_object($order)) {
					continue;
				}

				$meta_args['efw_order_id'] = $order->get_id();

				efw_update_fees( $fee_id, $meta_args );
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
					<h2><?php esc_html_e( 'Report Update is under progress...', 'extra-fees-for-woocommerce' ); ?></h2>
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
