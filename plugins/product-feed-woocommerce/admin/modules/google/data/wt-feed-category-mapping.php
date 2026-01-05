<?php
/**
 * Google category mapping view
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Category mapping.
if ( ! function_exists( 'wt_google_feed_render_categories' ) ) {
	/**
	 * Get Product Categories
	 *
	 * @param int    $parent Parent ID.
	 * @param string $par separator.
	 * @param string $value mapped values.
	 */
	function wt_google_feed_render_categories( $parent = 0, $par = '', $value = '' ) {
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
								<?php
								$allowed_tags = array(
									'select' => array(
										'id' => array(),
										'class' => array(),
										'name' => array(),
									),
									'option' => array(
										'value' => array(),
										'selected' => array(),
									),
								);
								echo wp_kses( wt_google_feed_category_dropdown(), $allowed_tags );
								?>
							</select>
					</td>
				</tr>
				<?php
				// call for child category if any.
				if ( ! empty( $par ) ) {
					wt_google_feed_render_categories( $cat->term_id, $par . $cat->name, $value );
				}
			}
		} else {
            ?>
                <tr class="treegrid-1">
					<td colspan="2">
						<?php esc_html_e('All categories have already been mapped', 'product-feed-woocommerce'); ?>
					</td>
                </tr>
            <?php
        }
	}
}

// FB Category dropdown caching.
if ( ! function_exists( 'wt_fb_feed_category_dropdown' ) ) {
	/**
	 * FB category dropdown
	 *
	 * @param string $selected Selected.
	 * @return string
	 */
	function wt_google_feed_category_dropdown( $selected = '' ) {

		$category_dropdown = wp_cache_get( 'wt_googlefeed_dropdown_product_categories' );

		if ( false === $category_dropdown ) {
			$categories = Webtoffee_Product_Feed_Sync_Pro_Google::get_category_array();

			// Primary Attributes.
			$category_dropdown = '';

			foreach ( $categories as $key => $value ) {
				$category_dropdown .= sprintf( '<option value="%s">%s</option>', $key, $value );
			}

			wp_cache_set( 'wt_googlefeed_dropdown_product_categories', $category_dropdown, '', WEEK_IN_SECONDS );
		}

		if ( $selected && strpos( $category_dropdown, 'value="' . $selected . '"' ) !== false ) {
			$category_dropdown = str_replace( 'value="' . $selected . '"', 'value="' . $selected . '"  selected', $category_dropdown );
		}

		return $category_dropdown;
	}
}





$value           = array();

?>
<div class="wt-wrap">

	
<h4><?php esc_html_e( 'Map WooCommerce categories with Google categories.', 'product-feed-woocommerce' ); ?></h4>
<?php
		$feed_channel_name = ucwords( $this->to_export );
if ( 'tiktok' === $this->to_export ) {
	$feed_channel_name = 'TikTok Ads';
}
if ( 'tiktokshop' === $this->to_export ) {
	$feed_channel_name = 'TikTok Shop';
}
if ( 'price_grabber' === $this->to_export ) {
	$feed_channel_name = 'Price Grabber';
}
?>
				
	<?php if ( 'google' === $this->to_export ) : ?>
	<span><?php esc_html_e( 'Google has a', 'product-feed-woocommerce' ); ?> <a target="_blank" href="https://www.google.com/basepages/producttype/taxonomy.en-US.txt"><?php esc_html_e( 'pre-defined set of categories', 'product-feed-woocommerce' ); ?></a>. <?php esc_html_e( 'Mapping your store categories with the Google categories will give more visibility to your products in Google ads and listings. To edit the mapping go to the respective', 'product-feed-woocommerce' ); ?> <a target="_blank" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>"><?php esc_html_e( 'categories page', 'product-feed-woocommerce' ); ?></a></span>
<?php else : ?>
	<span>
	<?php
	echo esc_html( $feed_channel_name );
	if ( 'tiktok' === $this->to_export ) {
		$feed_channel_name = 'TikTok';} // To avoid ads text multiple times
	?>
	 <?php esc_html_e( 'uses', 'product-feed-woocommerce' ); ?> <a target="_blank" href="https://www.google.com/basepages/producttype/taxonomy.en-US.txt"><?php esc_html_e( 'Google categories', 'product-feed-woocommerce' ); ?></a>. <?php esc_html_e( 'Mapping your store categories with the Google categories will give more visibility to your products in', 'product-feed-woocommerce' ); ?> <?php echo esc_html( $feed_channel_name ); ?> <?php esc_html_e( 'ads. To edit the mapping go to the respective', 'product-feed-woocommerce' ); ?> <a target="_blank" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>"><?php esc_html_e( 'categories page', 'product-feed-woocommerce' ); ?></a></span>
	<?php endif; ?>
<form action="" name="feed" id="category-mapping-form" class="category-mapping-form" method="post" autocomplete="off">
	<?php wp_nonce_field( 'wt-category-mapping' ); ?>

	<br/>
	<table class="table tree widefat fixed wt-pf-category-default-mapping-tb">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Store Categories', 'product-feed-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Google Category', 'product-feed-woocommerce' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php wt_google_feed_render_categories( 0, '', $value ); ?>
		</tbody>
	</table>
</form>
</div>
