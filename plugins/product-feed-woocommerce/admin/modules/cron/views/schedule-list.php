<?php
/**
 * Schedule listing
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( isset( $cron_list ) && is_array( $cron_list ) && count( $cron_list ) > 0 ) {
	?>
<div class="cron_list_wrapper">
	<table class="wp-list-table widefat fixed striped cron_list_tb" style="margin-bottom:55px;">
	<thead>
		<tr>
			<th width="50">
			<?php
			esc_html_e(
				'No.',
				'product-feed-woocommerce'
			);
			?>
							</th>
			<th width="100">
			<?php
			esc_html_e(
				'Channel',
				'product-feed-woocommerce'
			);
			?>
							</th>
			<th width="100">
			<?php
			esc_html_e(
				'Cron type',
				'product-feed-woocommerce'
			);
			?>
							</th>
			<th width="100">
				<?php
				esc_html_e(
					'Status',
					'product-feed-woocommerce'
				);
				?>
				<span class="dashicons wtdashicons-editor-help wt-pfd-tips" 
					data-wt-pfd-tip="					
					<span class='wt_productfeed_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */ printf( esc_html__( '%1$sFinished%2$s - Process completed', 'product-feed-woocommerce' ), '<b>', '</b>' ); ?></span><br />
					<span class='wt_productfeed_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */printf( esc_html__( '%1$sDisabled%2$s - The process has been disabled temporarily', 'product-feed-woocommerce' ), '<b>', '</b>' ); ?> </span><br />
					<span class='wt_productfeed_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */printf( esc_html__( '%1$sRunning%2$s - Process currently active and running', 'product-feed-woocommerce' ), '<b>', '</b>' ); ?> </span><br />
					<span class='wt_productfeed_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */printf( esc_html__( '%1$sUploading%2$s - Processed records are being uploaded to the specified location, finalizing export.', 'product-feed-woocommerce' ), '<b>', '</b>' ); ?> </span><br />
					<span class='wt_productfeed_tooltip_span'><?php /* translators: 1: html b open. 2: html b close */printf( esc_html__( '%1$sDownloading%2$s - Input records are being downloaded from the specified location prior to import process.', 'product-feed-woocommerce' ), '<b>', '</b>' ); ?> </span>"> 			
				</span>
			</th>
			<th>
			<?php
			esc_html_e(
				'Time',
				'product-feed-woocommerce'
			);
			?>
				</th>
			<th width="200">
			<?php
			esc_html_e(
				'Actions',
				'product-feed-woocommerce'
			);
			?>
			</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$i = 0;
	foreach ( $cron_list as $key => $cron_item ) {
		$i++;
				$item_type = ucfirst( $cron_item['item_type'] );
		?>
		<tr>
			<td><?php echo absint( $i ); ?></td>
			<td><?php echo esc_attr( $item_type ); ?></td>
			<td>
			<?php
			( 'server_cron' == $cron_item['schedule_type'] ? esc_html_e(
				'Server cron',
				'product-feed-woocommerce'
			) : esc_html_e(
				'WordPress cron',
				'product-feed-woocommerce'
			) );
			?>
			</td>
			<td>
				<?php $td_style_bg = isset( self::$status_color_arr[ $cron_item['status'] ] ) ? 'background:' . self::$status_color_arr[ $cron_item['status'] ] : ''; ?>
				<span class="wt_productfeed_badge" style="padding:5px;color:white;<?php echo wp_kses_post( $td_style_bg ); ?>">
					<?php
					$td_status_text = isset( self::$status_label_arr[ $cron_item['status'] ] ) ? self::$status_label_arr[ $cron_item['status'] ] : __( 'Unknown', 'product-feed-woocommerce' );
					echo esc_attr( $td_status_text );
					?>
				</span>
				<?php
				/**
				 *   Show completed percentage if status is running
				 */
				if ( $cron_item['status'] == self::$status_arr['running'] && $cron_item['history_id'] > 0 ) {
					$history_module_obj = Webtoffee_Product_Feed_Sync_Pro::load_modules( 'history' );
					if ( ! is_null( $history_module_obj ) ) {
						$history_entry = $history_module_obj->get_history_entry_by_id( $cron_item['history_id'] );
						if ( $history_entry ) {
							echo wp_kses_post( '<br />' . number_format( ( ( $history_entry['offset'] / $history_entry['total'] ) * 100 ), 2 ) . '% ' . __( ' Done', 'product-feed-woocommerce' ) );
						}
					}
				}
				?>
			</td>
			<td>
				<?php
				if ( $cron_item['status'] == self::$status_arr['finished'] || $cron_item['status'] == self::$status_arr['disabled'] ) {
					if ( $cron_item['last_run'] > 0 ) {
						/* translators:%s: last access date */
						$last_run_string = sprintf( __( 'Last run: %s', 'product-feed-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $cron_item['last_run'] ) );
						echo esc_attr( $last_run_string ) . '<br />';
					}

					/**
					*   Finished, so waiting for next run
					*/
					if ( $cron_item['status'] == self::$status_arr['finished'] && $cron_item['start_time'] > 0 && $cron_item['start_time'] != $cron_item['last_run'] ) {
						/* translators:%s: next access date */
						$next_run_string = sprintf( __( 'Next run: %s', 'product-feed-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $cron_item['start_time'] ) );
						echo esc_attr( $next_run_string ) . '<br />';
					}
				}

				if ( $cron_item['status'] == self::$status_arr['running'] || $cron_item['status'] == self::$status_arr['uploading'] || $cron_item['status'] == self::$status_arr['downloading'] ) {
					if ( $cron_item['last_run'] > 0 && $cron_item['start_time'] != $cron_item['last_run'] ) {
						/* translators:%s: last access date */
						$last_run_stringi = sprintf( __( 'Last run: %s', 'product-feed-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $cron_item['last_run'] ) );
						echo esc_attr( $last_run_stringi ) . '<br />';
					} else {
						/* translators:%s: next access date */
						$next_run_string = sprintf( __( 'Started at: %s', 'product-feed-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $cron_item['start_time'] ) );
						echo esc_attr( $next_run_string ) . '<br />';
					}
				}

				if ( $cron_item['status'] == self::$status_arr['not_started'] && $cron_item['start_time'] > 0 ) {

						/* translators:%s: next access date */
						$next_run_string = sprintf( __( 'Will start at: %s', 'product-feed-woocommerce' ), date_i18n( 'Y-m-d h:i:s A', $cron_item['start_time'] ) );
						echo esc_attr( $next_run_string ) . '<br />';
				}
				?>
			</td>
			<td>
				<?php
				$page_id = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification

				/* status change section */
				$action_label = __( 'Disable', 'product-feed-woocommerce' );
				$action_str = 'disable';
				if ( $cron_item['status'] == self::$status_arr['disabled'] ) {
					$action_str = 'enable';
					$action_label = __( 'Enable', 'product-feed-woocommerce' );
				}
				$action_url = wp_nonce_url( admin_url( 'admin.php?page=' . $page_id . '&tab=cron&wt_productfeed_change_schedule_status=' . $action_str . '&wt_productfeed_cron_id=' . $cron_item['id'] ), WT_PF_PLUGIN_ID );

				/* delete section */
				$delete_url = wp_nonce_url( admin_url( 'admin.php?page=' . $page_id . '&tab=cron&wt_productfeed_delete_schedule=1&wt_productfeed_cron_id=' . $cron_item['id'] ), WT_PF_PLUGIN_ID );

								/* edit action */
				if ( 'import' == $cron_item['action_type'] ) {
					$edit_url = admin_url( 'admin.php?page=wt_import_export_for_woo_import&wt_productfeed_cron_edit_id=' . $cron_item['id'] );
				} else {
					$edit_url = admin_url( 'admin.php?page=wt_import_export_for_woo_export&wt_productfeed_cron_edit_id=' . $cron_item['id'] );
				}

				if ( ! class_exists( "Webtoffee_Product_Feed_Sync_Pro_$item_type" ) ) {
					$edit_url = '#';
				}

				?>
						<a href="<?php echo esc_url( $action_url ); ?>"><?php echo esc_attr( $action_label ); ?></a> | <a title="<?php esc_html_e( 'Delete cron entry', 'product-feed-woocommerce' ); ?>" class="wt_productfeed_delete_cron" data-href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'product-feed-woocommerce' ); ?></a>
				<?php
				if ( 'server_cron' == $cron_item['schedule_type'] ) {
					$cron_url = $this->generate_cron_url( $cron_item['id'], $cron_item['action_type'], $cron_item['item_type'] );
					?>
					| <a class="wt_productfeed_cron_url" data-href="<?php echo esc_url( $cron_url ); ?>" title="<?php esc_html_e( 'Generate new cron URL.', 'product-feed-woocommerce' ); ?>"><?php esc_html_e( 'Cron URL', 'product-feed-woocommerce' ); ?></a>
					<?php
				}
				?>
			</td>
		</tr>
		<?php
	}
	?>
	</tbody>
	</table>
</div>

	<?php
} else {
	?>
	<h4 style="margin-bottom:55px; text-align:center; background:#fff; padding:15px 0px;"><?php esc_html_e( 'No scheduled actions found.', 'product-feed-woocommerce' ); ?></h4>
	<?php
}
?>
