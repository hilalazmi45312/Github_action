<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wb_tab_metabox_container">
	<input type="hidden" name="wb_tab_meta_box" value="1">
	
	<label class="wb_tab_form_label"><?php esc_html_e( 'Tab slug', 'wb-custom-product-tabs-for-woocommerce' ); ?></label>
	<input type="text" name="wb_tab_tab_slug" value="<?php echo esc_attr( $tab_slug ); ?>"> <a class="wb_cptb_slug_generate_btn"><?php esc_html_e( 'Generate tab slug from title.', 'wb-custom-product-tabs-for-woocommerce' ); ?></a>
	<div class="wb_tabpanel_hlp" style="float:none;"><?php esc_html_e( 'SEO friendly URL for tab. Allowed characters: letters, numbers, and hyphens only.', 'wb-custom-product-tabs-for-woocommerce' ); ?></div>


	<label class="wb_tab_form_label"><?php esc_html_e( 'Tab nickname', 'wb-custom-product-tabs-for-woocommerce' ); ?></label>
	<input type="text" name="wb_tab_tab_nickname" value="<?php echo esc_attr( $tab_nickname ); ?>">
	<div class="wb_tabpanel_hlp" style="float:none;"><?php esc_html_e( 'Use this nickname to identify tabs in the backend', 'wb-custom-product-tabs-for-woocommerce' ); ?></div>

	<label class="wb_tab_form_label"><?php esc_html_e( 'Tab position', 'wb-custom-product-tabs-for-woocommerce' ); ?></label>
	<input type="text" name="wb_tab_tab_position" value="<?php echo esc_attr( $tab_position ); ?>">
	<div style="margin-top:10px; display:inline-block;">
		<a href="https://webbuilder143.com/how-to-arrange-woocommerce-custom-product-tabs/?utm_source=plugin&utm_medium=global-tab&utm_campaign=tab-position&utm_content=positioning" target="_blank"><?php esc_html_e( 'Know more', 'wb-custom-product-tabs-for-woocommerce' ); ?> <span class="dashicons dashicons-external" style="text-decoration:none;"></span></a>
	</div>

	<?php 
	$this->show_translation_request_banner();
	?>
</div>