<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Google;

class LocalProductFeedRenderer extends ProductFeedRenderer {

	/*
	 * Output the "title" element in the feed intro.
	 */
	protected function render_feed_title(): void {

		echo '<title>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->esc_xml( $this->store_info->blog_name . __( ' Local Products', 'woocommerce_gpf' ) );
		echo "</title>\n";
	}

	/**
	 * Generate the item ID in the feed for an item.
	 *
	 * @param $feed_item
	 *
	 * @return string
	 */
	protected function generate_item_id( $feed_item ): string {
		return '      <g:itemid>' . $feed_item->guid . "</g:itemid>\n" .
				'      <g:webitemid>' . $feed_item->guid . "</g:webitemid>\n";
	}

	/**
	 * Generate the link for a product.
	 *
	 * @param $feed_item
	 *
	 * @return string
	 */
	protected function generate_link( $feed_item ): string {
		return '';
	}
}
