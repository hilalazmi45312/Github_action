<?php

namespace Ademti\WoocommerceProductFeeds\DTOs;

use RuntimeException;
use function esc_html;
use function in_array;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class ProductFeedItem {

	/**
	 * Additional elements that apply to this item.
	 */
	public array $additional_elements;

	/**
	 * Array of additional images that apply to this feed item.
	 */
	public array $additional_images;

	/**
	 * The calculated description of the feed item.
	 */
	public string $description;

	/**
	 * The various different descriptions available for the product.
	 */
	public array $descriptions;

	/**
	 * The post ID of the most general product represented by this item.
	 *
	 * For variations, this will be the parent product ID. For simple products,
	 * it will be the product ID.
	 */
	private int $general_id;

	/**
	 * The GUID of the feed item.
	 */
	public string $guid;

	/**
	 * The ID of the feed item.
	 */
	public string $ID;

	/**
	 * The image link for this feed item.
	 */
	public string $image_link;

	/**
	 * Array of image sources.
	 */
	public array $image_sources = [];

	/**
	 * Whether the product is in stock.
	 */
	public bool $is_in_stock;

	/**
	 * Whether the product is on backorder
	 */
	public bool $is_on_backorder;

	/**
	 * The item_group_id of the feed item.
	 */
	public string $item_group_id;

	/**
	 * Link to use as the lifestyle image link.
	 * @var string|null
	 */
	public ?string $lifestyle_image_link;

	/**
	 * List of discovered, ordered images.
	 *
	 * @var array
	 */
	public array $ordered_images;

	/**
	 * The purchase link for this feed item.
	 */
	public string $purchase_link;

	/**
	 * The SKU of the feed item.
	 */
	public string $sku;

	/**
	 * The shipping weight of the item.
	 */
	public ?string $shipping_weight;

	/**
	 * The shipping weight unit.
	 */
	public string $shipping_weight_unit;

	/**
	 * The specific ID represented by this item.
	 *
	 * For variations, this will be the variation ID. For simple products, it
	 * will be the product ID.
	 */
	private int $specific_id;

	/**
	 * The quantity of stock for this item.
	 */
	public ?int $stock_quantity;

	/**
	 * The title of the item.
	 */
	public string $title;

	/********************************************************************
	 * Price related properties
	 *******************************************************************/

	/**
	 * The final calculated price of the item excluding taxes.
	 */
	public string $price_ex_tax;

	/**
	 * The final calculated price of the item including taxes.
	 */
	public string $price_inc_tax;

	/**
	 * The raw price as returned by get_regular_price/get_sale_price in case we need to manipulate it in the future.
	 */
	public string $raw_price;

	/**
	 * The raw price as returned by get_regular_price in case we need to manipulate it in the future.
	 */
	public string $raw_regular_price;

	/**
	 * The regular price exclusive of taxes.
	 */
	public string $regular_price_ex_tax;

	/**
	 * The regular price including taxes.
	 */
	public string $regular_price_inc_tax;

	/**
	 * The sale price exclusive of taxes.
	 */
	public string $sale_price_ex_tax;

	/**
	 * The sale price including taxes.
	 */
	public string $sale_price_inc_tax;

	/**
	 * The start date that the sale price applies.
	 */
	public ?string $sale_price_start_date;

	/**
	 * The end date that the sale price applies.
	 */
	public ?string $sale_price_end_date;

	/**
	 * The raw price as returned by get_sale_price in case we need to manipulate it in the future.
	 */
	public string $raw_sale_price;

	/**
	 * @param int $specific_id
	 * @param int $general_id
	 * @param array $additional_elements
	 * @param array $additional_images
	 * @param string $description
	 * @param array $descriptions
	 * @param string $guid
	 * @param string $ID
	 * @param string $image_link
	 * @param array $image_sources
	 * @param bool $is_in_stock
	 * @param bool $is_on_backorder
	 * @param string $item_group_id
	 * @param string|null $lifestyle_image_link
	 * @param array $ordered_images
	 * @param string $purchase_link
	 * @param string $shipping_weight
	 * @param string $shipping_weight_unit
	 * @param string $sku
	 * @param int|null $stock_quantity
	 * @param string $title
	 * @param string $price_ex_tax
	 * @param string $price_inc_tax
	 * @param string $regular_price_ex_tax
	 * @param string $regular_price_inc_tax
	 * @param string $sale_price_ex_tax
	 * @param string $sale_price_inc_tax
	 * @param string $sale_price_start_date
	 * @param string $sale_price_end_date
	 * @param string $raw_price
	 * @param string $raw_regular_price
	 * @param string $raw_sale_price
	 */
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	public function __construct(
		int $specific_id,
		int $general_id,
		array $additional_elements,
		array $additional_images,
		string $description,
		array $descriptions,
		string $guid,
		string $ID,
		string $image_link,
		array $image_sources,
		bool $is_in_stock,
		bool $is_on_backorder,
		string $item_group_id,
		?string $lifestyle_image_link,
		array $ordered_images,
		string $purchase_link,
		string $shipping_weight,
		string $shipping_weight_unit,
		string $sku,
		?int $stock_quantity,
		string $title,
		string $price_ex_tax,
		string $price_inc_tax,
		string $regular_price_ex_tax,
		string $regular_price_inc_tax,
		string $sale_price_ex_tax,
		string $sale_price_inc_tax,
		?string $sale_price_start_date,
		?string $sale_price_end_date,
		string $raw_price,
		string $raw_regular_price,
		string $raw_sale_price
	) {

		$this->specific_id          = $specific_id;
		$this->general_id           = $general_id;
		$this->additional_elements  = $additional_elements;
		$this->additional_images    = $additional_images;
		$this->description          = $description;
		$this->descriptions         = $descriptions;
		$this->guid                 = $guid;
		$this->ID                   = $ID;
		$this->image_link           = $image_link;
		$this->image_sources        = $image_sources;
		$this->is_in_stock          = $is_in_stock;
		$this->is_on_backorder      = $is_on_backorder;
		$this->item_group_id        = $item_group_id;
		$this->lifestyle_image_link = $lifestyle_image_link;
		$this->ordered_images       = $ordered_images;
		$this->purchase_link        = $purchase_link;
		$this->shipping_weight      = $shipping_weight;
		$this->shipping_weight_unit = $shipping_weight_unit;
		$this->sku                  = $sku;
		$this->stock_quantity       = $stock_quantity;
		$this->title                = $title;

		$this->price_ex_tax          = $price_ex_tax;
		$this->price_inc_tax         = $price_inc_tax;
		$this->regular_price_ex_tax  = $regular_price_ex_tax;
		$this->regular_price_inc_tax = $regular_price_inc_tax;
		$this->sale_price_ex_tax     = $sale_price_ex_tax;
		$this->sale_price_inc_tax    = $sale_price_inc_tax;
		$this->sale_price_start_date = $sale_price_start_date;
		$this->sale_price_end_date   = $sale_price_end_date;
		$this->raw_price             = $raw_price;
		$this->raw_regular_price     = $raw_regular_price;
		$this->raw_sale_price        = $raw_sale_price;
	}
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

	public function __get( string $key ) {
		if ( in_array(
			$key,
			[
				'specific_id',
				'general_id',
			],
			true
		) ) {
			return $this->{$key};
		}
		throw new RuntimeException(
			esc_html( 'Invalid property access (' . $key . ') on ProductFeedItem' )
		);
	}
}
