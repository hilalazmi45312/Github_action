<?php
/**
 * History list page
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wt_pf_history_page">
	<h2 class="wt_pf_page_hd"><?php esc_html_e( 'Product Feed', 'product-feed-woocommerce' ); ?>
	<span class="wt-webtoffee-icon" style="float: <?php echo ( ! is_rtl() ) ? 'right' : 'left'; ?>;">
		<span style="font-size:14px;">
		<?php
		esc_html_e(
			'Developed by',
			'product-feed-woocommerce'
		);
		?>
		</span>
	<a target="_blank" href="https://woocommerce.com/vendor/webtoffee/">
		<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
		<img src="<?php echo esc_url( WT_PRODUCT_FEED_PRO_PLUGIN_URL . '/assets/images/webtoffee-logo_small.png' ); ?>" style="max-width:100px;">
	</a>
</span>
	</h2>
		
	<hr>
	
<?php
	// Get the active tab from the $_GET param.
	$default_tab = null;
	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $default_tab; // phpcs:ignore WordPress.Security.NonceVerification
?>
	<nav class="nav-tab-wrapper">        
	  <a href="?page=webtoffee_product_feed_main_pro_export" class="nav-tab 
	  <?php
		if ( null === $current_tab ) :
			?>
			nav-tab-active
			<?php
	 endif;
		?>
	 ">Create Feed</a>      
	  <a href="?page=webtoffee_product_feed_main_pro_export&tab=history" class="nav-tab 
	  <?php
		if ( 'history' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	 endif;
		?>
	 ">Manage Feeds</a>
	  <a href="?page=webtoffee_product_feed_main_pro_export&tab=cron" class="nav-tab 
	  <?php
		if ( 'cron' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	 endif;
		?>
	 ">Scheduled actions</a>
	  <a href="?page=webtoffee_product_feed_main_pro_export&tab=settings" class="nav-tab 
	  <?php
		if ( 'settings' === $current_tab ) :
			?>
			nav-tab-active
			<?php
	 endif;
		?>
	 ">Settings</a>
	</nav>	
	
	
	<h2 class="wp-heading-inline">
	<?php
	esc_html_e(
		'Manage feeds',
		'product-feed-woocommerce'
	);
	?>
	</h2>
	<?php
	echo wp_kses_post( self::gen_pagination_html( $total_records, $this->max_records, $offset, 'admin.php', $pagination_url_params ) );
	?>
	<?php
	if ( isset( $history_list ) && is_array( $history_list ) && count( $history_list ) > 0 ) {
		?>
		<table class="wp-list-table widefat fixed striped history_list_tb">
		<thead>
			<tr>				
				<th width="150">
				<?php
				esc_html_e(
					'Name',
					'product-feed-woocommerce'
				);
				?>
								</th>
				<th width="90">
				<?php
				esc_html_e(
					'Catalog type',
					'product-feed-woocommerce'
				);
				?>
								</th>
				<th width="55">
				<?php
				esc_html_e(
					'File type',
					'product-feed-woocommerce'
				);
				?>
				</th>
				<th width="">
				<?php
				esc_html_e(
					'URL',
					'product-feed-woocommerce'
				);
				?>
				</th>				
				<th width="60">
				<?php
				esc_html_e(
					'Items',
					'product-feed-woocommerce'
				);
				?>
				</th>				
				<th width="55">
				<?php
				esc_html_e(
					'Refresh interval',
					'product-feed-woocommerce'
				);
				?>
				</th>					
				<th width="165">
				<?php
				esc_html_e(
					'Last updated',
					'product-feed-woocommerce'
				);
				?>
				</th>
				<th width="150">
					<?php esc_html_e( 'Actions', 'product-feed-woocommerce' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
		<?php
		$i = $offset;

		foreach ( $history_list as $key => $history_item ) {

			$i++;
			?>
			<tr>
				<th style="vertical-align:top;">
				<?php $form_data = maybe_unserialize( $history_item['data'] ); ?>
				<?php echo esc_html( ucfirst( pathinfo( $history_item['file_name'], PATHINFO_FILENAME ) ) ); ?></td>
				<td><?php echo esc_html( ucfirst( $history_item['item_type'] ) ); ?></td>
				<td><?php echo esc_html( strtoupper( pathinfo( $history_item['file_name'], PATHINFO_EXTENSION ) ) ); ?></td>
				<td><?php echo esc_url( content_url() . '/uploads/webtoffee_product_feed/' . ( $history_item['file_name'] ) ); ?><br/><button data-uri = "<?php echo esc_url( content_url() . '/uploads/webtoffee_product_feed/' . ( $history_item['file_name'] ) ); ?>" class="button button-primary wt_pf_copy"><?php esc_html_e( 'Copy URL', 'product-feed-woocommerce' ); ?></button></td>				
				<td><?php echo esc_html( ucfirst( $history_item['total'] ) ); ?></td>
				<td>
				<?php

				$generate_inreval = isset( $form_data['post_type_form_data']['item_gen_interval'] ) ? $form_data['post_type_form_data']['item_gen_interval'] : '';
				if ( '' === $generate_inreval ) {
					$generate_inreval = isset( $form_data['post_type_form_data']['wt_pf_export_catalog_interval'] ) ? $form_data['post_type_form_data']['wt_pf_export_catalog_interval'] : '';
				}
				echo esc_html( ucfirst( $generate_inreval ) );
				?>
				</td>
				<td><?php echo esc_html( date_i18n( 'Y-m-d h:i:s A', $history_item['updated_at'] ) ); ?></td>
				<td>
					<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>					
					<a class="wt_pf_delete_history wt_manage_feed_icons" data-href="<?php echo esc_html( str_replace( '_history_id_', $history_item['id'], $delete_url ) ); ?>"><img src="<?php echo esc_url( WT_PRODUCT_FEED_PRO_PLUGIN_URL . '/assets/images/wt_fi_trash.svg' ); ?>" alt="<?php esc_html_e( 'Delete', 'product-feed-woocommerce' ); ?>" title="<?php esc_html_e( 'Delete', 'product-feed-woocommerce' ); ?>"/></a>
					<?php
					$action_type = $history_item['template_type'];
					if ( $form_data && is_array( $form_data ) ) {
						$to_process = ( isset( $form_data['post_type_form_data'] ) && isset( $form_data['post_type_form_data']['item_type'] ) ? $form_data['post_type_form_data']['item_type'] : '' );
						if ( '' == $to_process ) {
							$to_process = ( isset( $form_data['post_type_form_data'] ) && isset( $form_data['post_type_form_data']['wt_pf_export_post_type'] ) ? $form_data['post_type_form_data']['wt_pf_export_post_type'] : '' );
						}
						if ( '' != $to_process ) {
							if ( Webtoffee_Product_Feed_Sync_Pro_Admin::module_exists( $action_type ) ) {
								$action_module_id = WEBTOFFEE_PRODUCT_FEED_MAIN_PRO_ID;
								$url = admin_url( 'admin.php?page=' . $action_module_id . '&wt_pf_rerun=' . $history_item['id'] );
								?>
									<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
								   <a class="wt_pf_export_edit_btn wt_manage_feed_icons" href="<?php echo esc_url( $url ); ?>" target="_blank"><img src="<?php echo esc_url( WT_PRODUCT_FEED_PRO_PLUGIN_URL . '/assets/images/wt_fi_edit.svg' ); ?>" alt="<?php esc_html_e( 'Edit', 'product-feed-woocommerce' ); ?>" title="<?php esc_html_e( 'Edit', 'product-feed-woocommerce' ); ?>"/></a>
								<?php
							}
						}
					}

					if ( 'export' == $action_type && Webtoffee_Product_Feed_Sync_Pro_Admin::module_exists( $action_type ) ) {
						$export_download_url = wp_nonce_url( admin_url( 'admin.php?wt_pf_export_download=true&file=' . $history_item['file_name'] ), WEBTOFFEE_PRODUCT_FEED_PRO_ID );
						?>
							<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
							<a class="wt_pf_export_download_btn wt_manage_feed_icons" target="_blank" href="<?php echo esc_url( $export_download_url ); ?>"><img src="<?php echo esc_url( WT_PRODUCT_FEED_PRO_PLUGIN_URL . '/assets/images/wt_fi_download.svg' ); ?>" alt="<?php esc_html_e( 'Download', 'product-feed-woocommerce' ); ?>" title="<?php esc_html_e( 'Download', 'product-feed-woocommerce' ); ?>"/></a>
						<?php
					}
					?>
						
					<?php if ( 'manual' !== $generate_inreval ) { ?>
					<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
					<a class="wt_pf_export_refresh_btn wt_manage_feed_icons" href="javascript:void(0);" data-cron_id="<?php echo esc_html( $history_item['id'] ); ?>"><img src="<?php echo esc_url( WT_PRODUCT_FEED_PRO_PLUGIN_URL . '/assets/images/wt_fi_refresh.svg' ); ?>" alt="<?php esc_html_e( 'Refresh', 'product-feed-woocommerce' ); ?>" title="<?php esc_html_e( 'Refresh', 'product-feed-woocommerce' ); ?>"/></a>
					<?php } ?>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
		</table>
		<?php
		echo wp_kses_post( self::gen_pagination_html( $total_records, $this->max_records, $offset, 'admin.php', $pagination_url_params ) );
	} else {
		?>
		<h4 class="wt_pf_history_no_records"><?php esc_html_e( 'No records found.', 'product-feed-woocommerce' ); ?></h4>
		<?php
	}
	?>
</div>
