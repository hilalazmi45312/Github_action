<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id='wb_custom_tabs' class='panel woocommerce_options_panel'>
	<div class="options_group">
		<div class="wb_tab_main_hd">
			<span class="wb_tab_main_hd_inner"><?php esc_html_e( 'Custom tabs', 'wb-custom-product-tabs-for-woocommerce' ); ?></span>
			<p class="wb_tab_addnew_btn_container">
				<a class="button button-secondary" target="_blank" href="<?php echo esc_url( admin_url( 'options-general.php?page=wb-product-tab-settings&wb_cptb_tab=general' ) ); ?>"> 
					<span class="dashicons dashicons-admin-generic" style="margin-top:7px; font-size:14px;"></span> 
					<?php esc_html_e( 'Tab settings', 'wb-custom-product-tabs-for-woocommerce' ); ?>
				</a>
				<a class="button button-secondary" target="_blank" href="<?php echo esc_url( admin_url( 'options-general.php?page=wb-product-tab-settings&wb_cptb_tab=help' ) ); ?>">
					<span class="dashicons dashicons-sos" style="margin-top:7px; font-size:14px;"></span>  
					<?php esc_html_e( 'Help & FAQ', 'wb-custom-product-tabs-for-woocommerce' ); ?>
				</a>
				<button class="button button-primary wb_tab_addnew_btn" type="button"><span class="dashicons dashicons-plus-alt" style="margin-top:7px; font-size:14px;"></span> <?php esc_html_e( 'Add new tab', 'wb-custom-product-tabs-for-woocommerce' ); ?></button></p>
		</div>
		<div class="wb_tab_main_inner">
			<?php
			$local_tabs_total = 0;
			if ( $tabs ) {
				foreach ( $tabs as $key => $tab ) {
					$tab_title            = $tab['title'];
					$tab_nickname         = ( isset( $tab['nickname'] ) ? $tab['nickname'] : '' );
					$tab_slug             = ( isset( $tab['slug'] ) ? $tab['slug'] : '' );
					$tab_content          = $tab['content'];
					$tab_type             = $tab['tab_type'];
					$position             = $tab['position'];
					$tab_id               = isset( $tab['tab_id'] ) ? $tab['tab_id'] : 0;
					$is_hidden_global_tab = ( 'local' !== $tab_type && 'publish' !== get_post_status( $tab_id ) );
					$tab_edit_url         = ( 0 < $tab_id ? get_edit_post_link( $tab_id ) : '' ); //applicable for global tabs

					if ( $tab_type == 'local' ) {
						++$local_tabs_total;
					}
					include '_tab_single.php';
				}
			} else {
				?>
				<div class="wb_no_tabs">
					<div class="wb_no_tabs_inner">
						<span class="wb_no_tabs_icon">!</span> <br />
						<?php esc_html_e( 'No tabs', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					</div>
				</div>
				<?php
			}
			if ( $local_tabs_total == 0 ) {
				?>
				<div class="wb_tab_default">
					<?php
					$tab_title            = '';
					$tab_nickname         = '';
					$tab_content          = '';
					$tab_type             = 'local';
					$position             = 20;
					$tab_id               = 0;
					$key                  = 0;
					$is_hidden_global_tab = false;
					$tab_edit_url         = '';
					$tab_slug             = '';

					include '_tab_single.php';
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	
	<?php
	if ( ! empty( $tabs ) ) {
		?>
		<div class="options_group" style="padding:0px 15px;">
			<?php 
			$this->show_translation_request_banner();
			?>		
		</div>
		<?php
	}
	?>

</div>