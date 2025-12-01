<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\ProductFeeds\Google;

use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use function apply_filters;

class LocalProductInventoryFeed extends ProductFeedRenderer {

	/*
	 * Output the "title" element in the feed intro.
	 */
	protected function render_feed_title(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '    <title>' . $this->esc_xml( $this->store_info->blog_name . ' Local Product Inventory' ) . "</title>\n";
	}

	/**
	 * Generate the item ID in the feed for an item.
	 *
	 * @param $feed_item
	 *
	 * @return string
	 */
	protected function generate_item_id( ProductFeedItem $feed_item ): string {
		return '      <g:itemid>' . $feed_item->guid . "</g:itemid>\n";
	}

	/**
	 * Generate the output for an individual item, and return it
	 *
	 * @access public
	 *
	 * @param ProductFeedItem $feed_item The information about the item.
	 *
	 * @return  string             The rendered output for this item.
	 */
	public function render_item( ProductFeedItem $feed_item ): string {
		// Google do not allow free items in the feed.
		if ( empty( $feed_item->price_inc_tax ) ) {
			return '';
		}
		$output  = '';
		$output .= "    <item>\n";
		$output .= $this->generate_item_id( $feed_item );
		$output .= $this->render_prices( $feed_item );

		// Shop code
		$shop_code = ! empty( $this->settings['shop_code'] ) ?
			$this->settings['shop_code'] :
			'shop_001';
		$shop_code = apply_filters( 'woocommerce_gpf_googlelocalproductinventory_store_code', $shop_code, $feed_item );
		$output   .= '<g:store_code>' . $shop_code . '</g:store_code>';

		// Stock quantity
		$stock_quantity = ! empty( $feed_item->stock_quantity ) ?
			$feed_item->stock_quantity :
			apply_filters( 'woocommerce_gpf_local_product_inventory_default_stock_qty', 10, $feed_item );
		if ( ! $feed_item->is_in_stock || $feed_item->is_on_backorder ) {
			$stock_quantity = 0;
		}

		// Calculate availability.
		$availability = ! empty( $feed_item->additional_elements['availability'][0] ) ?
			$feed_item->additional_elements['availability'][0] :
			'';
		// Only send the value if the product is in stock, otherwise force to "out of stock".
		if ( $stock_quantity <= 0 ) {
			$availability = 'out of stock';
		}
		// Override availability & stock level if product set to 'on display to order'.
		if ( ! empty( $feed_item->additional_elements['ondisplaytoorder'] ) &&
			$feed_item->additional_elements['ondisplaytoorder'][0] === 'on' ) {
			$stock_quantity = 1;
			$availability   = 'on_display_to_order';
		}

		// Output calculated stock level / availability.
		$output .= '<g:quantity>' . $stock_quantity . '</g:quantity>';
		$output .= '<g:availability>' . $availability . '</g:availability>';

		$output .= "    </item>\n";

		return $output;
	}
}
