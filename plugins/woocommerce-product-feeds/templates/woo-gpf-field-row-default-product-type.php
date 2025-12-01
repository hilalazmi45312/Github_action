<input name="_woocommerce_gpf_data[{key}]" type="text" class="woocommerce_gpf_product_type_{raw_key} woocommerce-gpf-store-default" value="{current_data}" style="width: 100%; max-width: 750px;"{placeholder}>
<p class="help-text"><small><?php esc_html_e( 'Start typing to see suggestions from the official Google taxonomy. The following localised taxonomies will be searched: ', 'woocommerce_gpf' ); ?>{locale_list}</small></p>
<script type="text/javascript">
	jQuery(function(){
			woo_gpf_autocomplete('.woocommerce_gpf_product_type_{raw_key}', 'index.php?woocommerce_gpf_search=true');
	});
</script>
