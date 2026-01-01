<?php
/**
 * Tiktok category mapping view
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Category mapping.
if ( ! function_exists( 'wt_tiktok_feed_render_categories' ) ) {
	/**
	 * Get Product Categories
	 *
	 * @param int    $parent Parent ID.
	 * @param string $par separator.
	 * @param string $value mapped values.
	 */
	function wt_tiktok_feed_render_categories( $parent = 0, $par = '', $value = '' ) {
		$category_args = array(
			'taxonomy'       => 'product_cat',
			'parent'         => $parent,
			'orderby'        => 'term_group',
			'show_count'     => 1,
			'pad_counts'     => 1,
			'hierarchical'   => 1,
			'title_li'       => '',
			'hide_empty'     => 0,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'        => 'wt_google_category',
					'compare'    => 'NOT EXISTS',
				),
			),
		);
		$categories   = get_categories( $category_args );
		if ( ! empty( $categories ) ) {
			if ( ! empty( $par ) ) {
				$par = $par . ' > ';
			}

			foreach ( $categories as $cat ) {
				$class = $parent ? "treegrid-parent-{$parent} category-mapping" : 'treegrid-parent category-mapping';
				?>
				<tr class="treegrid-1 ">
					<th>
						<label for="cat_mapping_<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $par . $cat->name ); ?></label>
					</th>
					<td><!--suppress HtmlUnknownAttribute -->
						

						<select id= "cat_mapping_<?php echo esc_attr( $cat->term_id ); ?>" name="map_to[<?php echo esc_attr( $cat->term_id ); ?>]">
								<?php echo wp_kses_post( wt_tiktok_feed_category_dropdown() ); ?>
							</select>
					</td>
				</tr>
				<?php
				// call for child category if any.
				if ( ! empty( $par ) ) {
					wt_tiktok_feed_render_categories( $cat->term_id, $par . $cat->name, $value );
				}
			}
		}
	}
}

// FB Category dropdown caching.
if ( ! function_exists( 'wt_fb_feed_category_dropdown' ) ) {
	/**
	 * Category dropdown
	 *
	 * @param string $selected Selected category.
	 * @return string
	 */
	function wt_tiktok_feed_category_dropdown( $selected = '' ) {

		$category_dropdown = wp_cache_get( 'wt_tiktokfeed_dropdown_product_categories' );

		if ( false === $category_dropdown ) {
			$categories = Webtoffee_Product_Feed_Sync_Pro_Tiktok::get_category_array();

			// Primary Attributes.
			$category_dropdown = '';

			foreach ( $categories as $key => $value ) {
				$category_dropdown .= sprintf( '<option value="%s">%s</option>', $key, $value );
			}

			wp_cache_set( 'wt_tiktokfeed_dropdown_product_categories', $category_dropdown, '', WEEK_IN_SECONDS );
		}

		if ( $selected && strpos( $category_dropdown, 'value="' . $selected . '"' ) !== false ) {
			$category_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"  selected', $category_dropdown );
		}

		return $category_dropdown;
	}
}





$value = array();

?>
<div class="wt-wrap">

	
	<h4>
	<?php
	esc_html_e(
		'Map WooCommerce categories with Tiktok categories.',
		'product-feed-woocommerce'
	);
	?>
	</h4>
	<span>
	<?php
	esc_html_e(
		'Tiktok has a pre-defined set of',
		'product-feed-woocommerce'
	);
	?>
	 <a target="_blank" href="https://www.tiktok.com/basepages/producttype/taxonomy.en-US.txt">
 <?php
	esc_html_e(
		'categories',
		'product-feed-woocommerce'
	);
	?>
</a>. 
<?php
esc_html_e(
	'It is important that you map the categories defined within your store with the Tiktok categories respectively so that the products will be mapped accordingly. Everytime we come across a new category that has not been mapped prior we will produce it in the below section for you to verify.You can always edit the prior mapping under the respective',
	'product-feed-woocommerce'
);
?>
 <a target="_blank" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>">
									   <?php
										esc_html_e(
											'categories',
											'product-feed-woocommerce'
										);
										?>
</a></span>
	
	<form action="" name="feed" id="category-mapping-form" class="category-mapping-form" method="post" autocomplete="off">
		<?php wp_nonce_field( 'wt-category-mapping' ); ?>

		<br/>
		<table class="table tree widefat fixed wt-pf-category-default-mapping-tb">
			<thead>
			<tr>
				<th>
				<?php
				esc_html_e(
					'Store Categories',
					'product-feed-woocommerce'
				);
				?>
				</th>
				<th>
				<?php
				esc_html_e(
					'Tiktok Category',
					'product-feed-woocommerce'
				);
				?>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php wt_tiktok_feed_render_categories( 0, '', $value ); ?>
			</tbody>
		</table>
	</form>
</div>
