<?php // phpcs:disable Internal.NoCodeFound ?>
<p>
	<input type="checkbox" class="woocommerce_gpf_field_selector" name="woocommerce_gpf_config[expanded_schema]" id="woocommerce_gpf_config[expanded_schema]" {expanded_schema_selected}>
	<label for="woocommerce_gpf_config[expanded_schema]">
		<strong><?php echo esc_html( _x( '[BETA]', 'Beta label for expanded schema option', 'woocommerce_gpf' ) ); ?></strong>
		<?php echo esc_html( __( 'Include expanded schema markup on product pages.', 'woocommerce_gpf' ) ); ?>
	</label>
	<br>
	<strong><?php echo esc_html( _x( 'Note:', 'Introduction to description for "expanded schema" option', 'woocommerce_gpf' ) ); ?></strong>
	<?php
	echo wp_kses(
		sprintf(
			// Translators: %s is the link destination URL
			__(
				'Before enabling this option, please see <a href="%s" target="_blank" rel="nofollow noopener">this article</a> about the status of the feature, and the potential consequences of using this pre-release feature.',
				'woocommere_gpf'
			),
			'https://woocommerce.com/document/google-product-feed-expanded-structured-data/'
		),
		[
			'a' => [
				'href'   => true,
				'target' => true,
				'rel'    => true,
			],
		]
	);
	?>
</p>
