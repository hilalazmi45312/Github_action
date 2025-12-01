<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'EFW_Product_Bulk_Update_Action_Scheduler' ) ) {

	/**
	 * EFW_Product_Bulk_Update_Action_Scheduler Class.
	 */
	class EFW_Product_Bulk_Update_Action_Scheduler extends EFW_Action_Scheduler {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id                            = 'efw_bulk_update_action_data';
			$this->action_scheduler_name         = 'efw_bulk_update_action_scheduler';
			$this->chunked_action_scheduler_name = 'efw_chunk_single_action_bulk_update';
			$this->settings_option_name          = 'efw_bulk_update_settings_args';

			add_action( 'wp_ajax_efw_bulk_update_product_fee', array( $this, 'bulk_update_product_fee' ) ); // Do ajax action.
			parent::__construct();
		}

		/**
		 * Get progress bar label.
		 *
		 * @return String
		 */
		public function get_progress_bar_label() {
			return esc_html__( 'Product Fee Update is under progress...', 'extra-fees-for-woocommerce' );
		}

		/**
		 * Get success message.
		 *
		 * @return String
		 */
		public function get_success_message() {
			return esc_html__( 'Product Fee Updated successfully.', 'extra-fees-for-woocommerce' );
		}

		/**
		 * Get redirect URL.
		 */
		public function get_redirect_url() {
			return efw_get_settings_page_url();
		}

		/**
		 * Update Product Fee.
		 *
		 * @throws Exception If $_POST is empty.
		 * */
		public function bulk_update_product_fee() {

			check_ajax_referer( 'efw-bulk-update-nonce', 'efw_security' );

			try {
				if ( ! isset( $_POST ) ) {
					throw new Exception( esc_html__( 'Invalid Request', 'extra-fees-for-woocommerce' ) );
				}

				$args = array(
					'efw_product_filter'  => isset( $_POST['product_filter'] ) ? filter_input( INPUT_POST, 'product_filter' ) : '1',
					'efw_inc_products'    => isset( $_POST['inc_products'] ) ? wc_clean( wp_unslash( $_POST['inc_products'] ) ) : array(),
					'efw_exc_products'    => isset( $_POST['exc_products'] ) ? wc_clean( wp_unslash( $_POST['exc_products'] ) ) : array(),
					'efw_inc_category'    => isset( $_POST['inc_category'] ) ? wc_clean( wp_unslash( $_POST['inc_category'] ) ) : array(),
					'efw_exc_category'    => isset( $_POST['exc_category'] ) ? wc_clean( wp_unslash( $_POST['exc_category'] ) ) : array(),
					'efw_inc_tag'         => isset( $_POST['inc_tag'] ) ? wc_clean( wp_unslash( $_POST['inc_tag'] ) ) : array(),
					'efw_exc_tag'         => isset( $_POST['exc_tag'] ) ? wc_clean( wp_unslash( $_POST['exc_tag'] ) ) : array(),
					'efw_bulk_enable_fee' => ( isset( $_POST['enable_fee'] ) ) ? filter_input( INPUT_POST, 'enable_fee' ) : 'no',
					'efw_fee_from'        => isset( $_POST['fee_from'] ) ? filter_input( INPUT_POST, 'fee_from' ) : '1',
					'efw_text_from'       => isset( $_POST['text_from'] ) ? filter_input( INPUT_POST, 'text_from' ) : '1',
					'efw_fee_text'        => isset( $_POST['fee_text'] ) ? filter_input( INPUT_POST, 'fee_text' ) : '',
					'efw_fee_type'        => isset( $_POST['fee_type'] ) ? filter_input( INPUT_POST, 'fee_type' ) : '1',
					'efw_fee_description' => isset( $_POST['fee_description'] ) ? filter_input( INPUT_POST, 'fee_description' ) : '',
					'efw_fixed_value'     => isset( $_POST['fixed_value'] ) ? filter_input( INPUT_POST, 'fixed_value' ) : '',
					'efw_percent_value'   => isset( $_POST['percent_value'] ) ? filter_input( INPUT_POST, 'percent_value' ) : '',
					'efw_productfee_fee_setup'   => isset( $_POST['fee_mode'] ) ? filter_input( INPUT_POST, 'fee_mode' ) : '1',
				);

				foreach ( $args as $key => $value ) {
					update_option( $key, $value );
				}

				$option_args = array(
					'efw_productfee_product_filters'      => $args['efw_product_filter'],
					'efw_productfee_update_inc_products'  => $args['efw_inc_products'],
					'efw_productfee_update_exc_products'  => $args['efw_exc_products'],
					'efw_productfee_update_inc_category'  => $args['efw_inc_category'],
					'efw_productfee_update_exc_category'  => $args['efw_exc_category'],
					'efw_productfee_update_inc_tag'       => $args['efw_inc_tag'],
					'efw_productfee_update_exc_tag'       => $args['efw_exc_tag'],
					'efw_productfee_bulk_enable'          => $args['efw_bulk_enable_fee'],
					'efw_productfee_bulk_fee_from'        => $args['efw_fee_from'],
					'efw_productfee_bulk_text_from'       => $args['efw_text_from'],
					'efw_productfee_bulk_fee_text'        => $args['efw_fee_text'],
					'efw_productfee_bulk_fee_type'        => $args['efw_fee_type'],
					'efw_productfee_bulk_fee_description' => $args['efw_fee_description'],
					'efw_productfee_bulk_fixed_value'     => $args['efw_fixed_value'],
					'efw_productfee_bulk_percent_value'   => $args['efw_percent_value'],
				);

				foreach ( $option_args as $option_key => $option_value ) {
					update_option( $option_key, $option_value );
				}
				
				$product_ids = $this->get_product_ids();
				// Schedule bulk update action.
				$this->schedule_action( $product_ids, $option_args );

				$redirect_url = efw_get_settings_page_url(
					array(
						'tab'                  => 'productfee',
						'efw_action_scheduler' => $this->get_id(),
					)
				);

				wp_send_json_success( array( 'redirect_url' => $redirect_url ) );
			} catch ( Exception $ex ) {
				wp_send_json_error( array( 'error' => $ex->getMessage() ) );
			}
		}

		/**
		 * Get product ids.
		 */
		public function get_product_ids() {

			$product_filter = get_option( 'efw_product_filter' );

			$args = array(
				'post_type'      => array( 'product', 'product_variation' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			switch ( $product_filter ) {
				case '2':
					$args['post__in'] = efw_check_is_array( get_option( 'efw_inc_products' ) ) ? get_option( 'efw_inc_products' ) : array();
					break;

				case '3':
					$args['post__not_in'] = efw_check_is_array( get_option( 'efw_exc_products' ) ) ? get_option( 'efw_exc_products' ) : array();
					break;

				case '4':
					$args['tax_query'] = array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => get_option( 'efw_inc_category' ),
							'operator' => 'IN',
						),
					);

					$category_product_ids = get_posts( $args );
					$args['post__in']     = $this->get_product_ids_by_tags_and_categories( $category_product_ids );
					break;

				case '5':
					$args['tax_query'] = array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => get_option( 'efw_exc_category' ),
							'operator' => 'NOT IN',
						),
					);

					$category_product_ids = get_posts( $args );
					$args['post__in']     = $this->get_product_ids_by_tags_and_categories( $category_product_ids );
					break;
				case '6':
					$args['tax_query'] = array(
						array(
							'taxonomy' => 'product_tag',
							'field'    => 'term_id',
							'terms'    => get_option( 'efw_inc_tag' ),
							'operator' => 'IN',
						),
					);

					$tag_product_ids  = get_posts( $args );
					$args['post__in'] = $this->get_product_ids_by_tags_and_categories( $tag_product_ids );
					break;

				case '7':
					$args['tax_query'] = array(
						array(
							'taxonomy' => 'product_tag',
							'field'    => 'term_id',
							'terms'    => get_option( 'efw_exc_tag' ),
							'operator' => 'NOT IN',
						),
					);

					$tag_product_ids  = get_posts( $args );
					$args['post__in'] = $this->get_product_ids_by_tags_and_categories( $tag_product_ids );
					break;
			}

			return get_posts( $args );
		}

		/**
		 * Get Product IDs by Tags & Categories.
		 *
		 * @param array $product_ids Product Ids.
		 */
		public function get_product_ids_by_tags_and_categories( $product_ids ) {

			$variation_product_ids = get_posts(
				array(
					'post_type'       => array( 'product_variation' ),
					'post_status'     => 'publish',
					'fields'          => 'ids',
					'post_parent__in' => $product_ids,
				)
			);

			if ( efw_check_is_array( $variation_product_ids ) ) {
				return array_merge( $product_ids, $variation_product_ids );
			}

			return $product_ids;
		}

		/**
		 * May be update product meta.
		 *
		 * @param array $product_ids Product Ids.
		 */
		public function chunked_scheduler_action( $product_ids ) {

			$settings_data = $this->get_settings_data();
			
			$meta_args = array(
				'_efw_enable_fee'      => $settings_data['efw_productfee_bulk_enable'],
				'_efw_fee_from'       => $settings_data['efw_productfee_bulk_fee_from'],
				'_efw_text_from'       => $settings_data['efw_productfee_bulk_text_from'],
				'_efw_fee_text'        => $settings_data['efw_productfee_bulk_fee_text'],
				'_efw_fee_description' => $settings_data['efw_productfee_bulk_fee_description'],
				'_efw_fee_type'        => $settings_data['efw_productfee_bulk_fee_type'],
				'_efw_fixed_value'     => $settings_data['efw_productfee_bulk_fixed_value'],
				'_efw_percent_value'   => $settings_data['efw_productfee_bulk_percent_value'],
			);

			foreach ( $product_ids as $product_id ) {
				foreach ( $meta_args as $meta_key => $meta_value ) {
					// Update meta.
					update_post_meta( $product_id, sanitize_key( $meta_key ), $meta_value );
				}
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
					<h2><?php esc_html_e( 'Product Fee Update is under progress...', 'extra-fees-for-woocommerce' ); ?></h2>
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
