<?php // phpcs:disable Internal.NoCodeFound ?>
<input name="_woocommerce_gpf_data[{key}]" class="woocommerce_gpf_product_type_{raw_key} woocommerce-gpf-store-default" value="{current_data}" style="width: 100%; max-width: 750px;"{placeholder}>
<script type="text/javascript">
	jQuery(function(){
			woo_gpf_autocomplete( '.woocommerce_gpf_product_type_{raw_key}', 'index.php?woocommerce_gpf_bing_search=true' );
	});
</script>
