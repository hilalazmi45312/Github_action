<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wb_tab_metabox_container">
	<p> <?php esc_html_e( 'The tab will be displayed on the selected product pages below.', 'wb-custom-product-tabs-for-woocommerce' ); ?> </p>

	<select class="wc-product-search" name="_wb_tab_products[]" multiple="multiple" 
		style="width: 100%;" data-placeholder="<?php esc_attr_e( 'Search for a product...', 'wb-custom-product-tabs-for-woocommerce' ); ?>" data-action="woocommerce_json_search_products">
		<?php
		foreach ( $tab_products as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				echo '<option value="' . esc_attr( $product_id ) . '" selected="selected">' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
			}
		}
		?>
	</select>
</div>