<div id="woocommerce-gpf" class="panel woocommerce_options_panel">
		<p class="form-field">
				<label for="woocommerce-gpf-visibility"><?php esc_html_e( 'Submit to Google', 'woocommerce_gpf' ); ?></label>
				<select id="woocommerce-gpf-visibility" name="woocommerce_gpf_visibility" style="width: 50%;">
						<option value="" {no_selected}><?php esc_html_e( 'No', 'woocommerce_gpf' ); ?></option>
						<option value="yes" {yes_selected}><?php esc_html_e( 'Yes', 'woocommerce_gpf' ); ?></option>
				</select>
		</p>
	<p class="form-field">
		<label for="woocommerce-gpf-promotion-long-title"><?php esc_html_e( 'Promotion title', 'woocommerce_gpf' ); ?></label>
		<input id="woocommerce-gpf-promotion-long-title" name="woocommerce_gpf_promotion_long_title" type="text" value="{long_title}">
	</p>
	<p class="form-field">
		<label for="woocommerce-gpf-promotion-destination"><?php esc_html_e( 'Promotion destinations', 'woocommerce_gpf' ); ?></label>
		<select id="woocommerce-gpf-promotion-destination" name="woocommerce_gpf_promotion_destination[]" style="width: 50%;" multiple="multiple">
			<option value="Free_listings" {destination_free_listings_selected}><?php esc_html_e( 'Free listings', 'woocommerce_gpf' ); ?></option>
			<option value="Shopping_ads" {destination_shopping_ads_selected}><?php esc_html_e( 'Shopping ads', 'woocommerce_gpf' ); ?></option>
			<option value="YouTube_affiliate" {destination_youtube_affiliate_selected}><?php esc_html_e( 'YouTube affiliate', 'woocommerce_gpf' ); ?></option>
		</select>
	</p>
</div>

<script>
	document.addEventListener("DOMContentLoaded", function() {
		jQuery('#woocommerce-gpf-promotion-destination').selectWoo({
			placeholder: <?php echo wp_json_encode( esc_html( __( 'All destinations. Click to change', 'woocommerce_gpf ' ) ) ); ?>,
		});
	});
</script>
