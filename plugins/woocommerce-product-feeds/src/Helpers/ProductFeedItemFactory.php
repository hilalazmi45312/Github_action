<?php

namespace Ademti\WoocommerceProductFeeds\Helpers;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigRepository;
use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Container;
use Ademti\WoocommerceProductFeeds\DTOs\FallbackRequest;
use Ademti\WoocommerceProductFeeds\DTOs\ProductFeedItem;
use DateInterval;
use Exception;
use WC_DateTime;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;
use function apply_filters;
use function array_diff;
use function array_intersect_key;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_unique;
use function call_user_func;
use function count;
use function do_action;
use function explode;
use function get_children;
use function get_option;
use function get_post_meta;
use function get_terms;
use function get_the_terms;
use function is_array;
use function is_callable;
use function is_wp_error;
use function preg_replace;
use function sprintf;
use function str_replace;
use function update_option;
use function WC;
use function wc_get_dimension;
use function wc_get_formatted_variation;
use function wc_get_price_excluding_tax;
use function wc_get_price_including_tax;
use function wp_cache_get;
use function wp_cache_set;
use function wp_get_attachment_image_src;
use function wp_list_pluck;

class ProductFeedItemFactory {

	// Dependencies.
	protected Configuration $configuration;
	protected DebugService $debug;
	protected TermDepthRepository $term_depth_repository;
	protected Container $container;
	protected FeedConfigRepository $feed_config_repository;

	/**
	 * Whether the class has been configured for use.
	 *
	 * @var bool
	 */
	private bool $configured = false;

	/********************************************************
	 * General configuration. These apply to all built items.
	 *******************************************************/

	/**
	 * Image style to be used when generated the image URLs.
	 *
	 * Defaults to full. Override by using the filter 'woocommerce_gpf_image_style'
	 */
	private string $image_style = '';

	/**
	 * Unit of measurement that the shipping height/width/length are in
	 *
	 * Defaults to the standard store dimension unit.
	 * Override by using the filter 'woocommerce_gpf_shipping_dimension_unit'.
	 * Valid values are 'in', or 'cm'.
	 */
	private string $shipping_dimension_unit = '';

	/**
	 *  Unit of measure that shipping weights are returned from WooCommerce in.
	 *
	 * @var string
	 */
	private $shipping_weight_original_unit;

	/**
	 *  Unit of measure that shipping weights are in.
	 *
	 *  Leave as-is to submit in the store weight unit.
	 *  Override by using the filter 'woocommerce_gpf_shipping_weight_unit'.
	 *
	 *  Valid values are lbs, oz, g, kg
	 *
	 * @var string
	 */
	private $shipping_weight_unit;

	/**
	 * Which description to use, "full" or "short".
	 *
	 * Defaults to full. Override by using the filter 'woocommerce_gpf_description_type'
	 */
	private string $description_type = '';

	/**
	 * @var bool
	 */
	private bool $use_variation_images;

	/*****************************************************************************
	 * Item specific storage. These values are set for each individual item build.
	 *****************************************************************************/

	/**
	 * The specific WC_Product that this item represents.
	 *
	 * @var WC_Product|WC_Product_Variation
	 */
	private WC_Product $specific_product;

	/**
	 * The "general" WC_Product. Either the product, or its parent in the case of a variation.
	 */
	private WC_Product $general_product;

	/**
	 * The specific ID represented by this item.
	 *
	 * For variations, this will be the variation ID. For simple products, it
	 * will be the product ID.
	 */
	private int $specific_id;

	/**
	 * The post ID of the most general product represented by this item.
	 *
	 * For variations, this will be the parent product ID. For simple products,
	 * it will be the product ID.
	 */
	private int $general_id;

	/**
	 * Whether  we are calculating prices.
	 */
	private bool $calculate_prices;

	/**
	 * The feed format that the item is being prepared for.
	 */
	private string $feed_format;

	/**
	 * Whether this item represents a variation.
	 */
	private bool $is_variation;

	/**
	 * Array of image sources.
	 */
	private array $image_sources = [];

	/**
	 * List of discovered, ordered images.
	 */
	private array $ordered_images = [];

	/**
	 * @var array
	 */
	private array $coupon_category_map;

	/**
	 * @var array
	 */
	private array $coupon_brand_map;

	/**
	 * @var array
	 */
	private $post_type_relationships = [];

	/**
	 * @param  Configuration  $configuration
	 * @param  DebugService  $debug
	 * @param  TermDepthRepository  $term_depth_repository
	 * @param  FeedConfigRepository  $feed_config_repository
	 * @param  Container  $container
	 */
	public function __construct(
		Configuration $configuration,
		DebugService $debug,
		TermDepthRepository $term_depth_repository,
		FeedConfigRepository $feed_config_repository,
		Container $container
	) {
		$this->configuration          = $configuration;
		$this->debug                  = $debug;
		$this->term_depth_repository  = $term_depth_repository;
		$this->feed_config_repository = $feed_config_repository;
		$this->container              = $container;
	}

	/**
	 * @param $feed_format
	 * @param WC_Product $specific_product
	 * @param WC_Product $general_product
	 * @param bool $calculate_prices
	 *
	 * @return ProductFeedItem
	 * @throws Exception
	 */
	public function create(
		string $feed_format,
		$specific_product,
		$general_product,
		$calculate_prices = true
	): ProductFeedItem {

		$this->ensure_configured();

		// Set properties needed by detailed build methods.
		$this->specific_product = $specific_product;
		$this->general_product  = $general_product;
		$this->specific_id      = $specific_product->get_id();
		$this->general_id       = $general_product->get_id();
		$this->calculate_prices = $calculate_prices;
		$this->feed_format      = empty( $feed_format ) ? 'all' : $feed_format;
		$this->is_variation     = $specific_product instanceof WC_Product_Variation;

		// Set taxable address.
		add_filter( 'woocommerce_get_tax_location', [ $this, 'set_taxable_address_to_base' ] );

		// Build the item data.
		$dto_data = $this->build_item();

		// Restore taxable address.
		remove_filter( 'woocommerce_get_tax_location', [ $this, 'set_taxable_address_to_base' ] );

		// Create the DTO
		$feed_item = new ProductFeedItem(
			$dto_data['specific_id'],
			$dto_data['general_id'],
			$dto_data['additional_elements'],
			$dto_data['additional_images'],
			$dto_data['description'],
			$dto_data['descriptions'],
			$dto_data['guid'],
			$dto_data['ID'],
			$dto_data['image_link'],
			$this->image_sources,
			$dto_data['is_in_stock'],
			$dto_data['is_on_backorder'],
			$dto_data['item_group_id'],
			$dto_data['lifestyle_image_link'] ?? null,
			$this->ordered_images,
			$dto_data['purchase_link'],
			$dto_data['shipping_weight'],
			$dto_data['shipping_weight_unit'],
			$dto_data['sku'],
			$dto_data['stock_quantity'],
			$dto_data['title'],
			$dto_data['price_ex_tax'],
			$dto_data['price_inc_tax'],
			$dto_data['regular_price_ex_tax'],
			$dto_data['regular_price_inc_tax'],
			$dto_data['sale_price_ex_tax'],
			$dto_data['sale_price_inc_tax'],
			$dto_data['sale_price_start_date'],
			$dto_data['sale_price_end_date'],
			$dto_data['raw_price'],
			$dto_data['raw_regular_price'],
			$dto_data['raw_sale_price']
		);

		// Filter the item allowing customisations & integrations to modify if required.
		$feed_item = apply_filters( 'woocommerce_gpf_feed_item', $feed_item, $specific_product );
		$feed_item = apply_filters( 'woocommerce_gpf_feed_item_' . $feed_format, $feed_item, $specific_product );

		// Clean up.
		$this->ordered_images = [];
		$this->image_sources  = [];
		unset( $this->specific_product );
		unset( $this->general_product );

		return $feed_item;
	}

	/**
	 * Configures the builder according to filterable preferences.
	 *
	 * @return void
	 */
	private function ensure_configured(): void {
		if ( $this->configured ) {
			return;
		}
		if ( empty( $this->image_style ) ) {
			$this->image_style = apply_filters(
				'woocommerce_gpf_image_style',
				'full'
			);
		}
		if ( empty( $this->description_type ) ) {
			$this->description_type = apply_filters(
				'woocommerce_gpf_description_type',
				$this->description_type
			);
		}
		if ( ! isset( $this->shipping_weight_original_unit ) ) {
			$this->shipping_weight_original_unit = get_option( 'woocommerce_weight_unit' );
		}
		if ( ! isset( $this->shipping_weight_unit ) ) {
			$this->shipping_weight_unit = apply_filters(
				'woocommerce_gpf_shipping_weight_unit',
				$this->shipping_weight_original_unit
			);
		}
		if ( empty( $this->shipping_dimension_unit ) ) {
			$this->shipping_dimension_unit = apply_filters(
				'woocommerce_gpf_shipping_dimension_unit',
				get_option( 'woocommerce_dimension_unit' )
			);
		}
		$this->use_variation_images = apply_filters( 'woocommerce_gpf_use_variation_images', true );

		// Load, or build the coupon maps.
		$coupon_category_map = get_option( 'woocommerce_gpf_coupon_category_map' );
		if ( $coupon_category_map === false ) {
			$coupon_repository   = $this->container['CouponRepository'];
			$coupon_category_map = $coupon_repository->generate_category_coupon_map();
			update_option(
				'woocommerce_gpf_coupon_category_map',
				$coupon_category_map
			);
		}
		$this->coupon_category_map = $coupon_category_map;

		$coupon_brand_map = get_option( 'woocommerce_gpf_coupon_brand_map' );
		if ( $coupon_brand_map === false ) {
			$coupon_repository = $this->container['CouponRepository'];
			$coupon_brand_map  = $coupon_repository->generate_brand_coupon_map();
			update_option(
				'woocommerce_gpf_coupon_brand_map',
				$coupon_brand_map
			);
		}
		$this->coupon_brand_map = $coupon_brand_map;

		// Mark the instance as configured.
		$this->configured = true;
	}

	/**
	 * Override the taxable address to the store base location.
	 *
	 * @param $address
	 *
	 * @return array
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
	public function set_taxable_address_to_base( $address ) {
		$wc_class = WC();

		return [
			$wc_class->countries->get_base_country(),
			$wc_class->countries->get_base_state(),
			$wc_class->countries->get_base_postcode(),
			$wc_class->countries->get_base_city(),
		];
	}
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Build the data required to instantiate the DTO, and return it as an associative array.
	 *
	 * @return array
	 * @throws Exception
	 */
	private function build_item(): array {
		$data = [];
		// Assemble the DTO data.
		$data['general_id']        = $this->general_id;
		$data['specific_id']       = $this->specific_id;
		$data['additional_images'] = [];

		$data['ID']                   = $this->specific_id;
		$data['guid']                 = 'woocommerce_gpf_' . $data['ID'];
		$data['item_group_id']        = 'woocommerce_gpf_' . $this->general_id;
		$data['purchase_link']        = $this->specific_product->get_permalink();
		$data['is_in_stock']          = $this->specific_product->is_in_stock();
		$data['is_on_backorder']      = $this->specific_product->is_on_backorder();
		$data['stock_quantity']       = $this->specific_product->get_stock_quantity();
		$data['sku']                  = $this->specific_product->get_sku();
		$data['shipping_weight']      = $this->get_shipping_weight();
		$data['shipping_weight_unit'] = $this->shipping_weight_unit;

		do_action( 'woocommerce_gpf_before_description_generation', $this->specific_id, $this->general_id );
		$data['descriptions'] = $this->get_product_descriptions();
		$data['description']  = $this->pick_product_description( $data['descriptions'] );
		$data['description']  = apply_filters(
			'woocommerce_gpf_description',
			$data['description'],
			$this->general_id,
			$this->is_variation ? $this->specific_id : null
		);
		do_action( 'woocommerce_gpf_after_description_generation', $this->specific_id, $this->general_id );

		// Standard element calculations.
		$data['additional_elements'] = $this->calculate_general_elements();

		// Add image properties.
		$data = $this->add_image_props( $data );

		// Calculate the various prices we need.
		$data = array_merge(
			$data,
			$this->get_product_prices()
		);

		// Element-specific modifications to the calculated values below here.

		// Move title out of additional_elements, and back into the main property if set
		// or fall back to the product_title if not populated.
		if ( ! empty( $data['additional_elements']['title'][0] ) ) {
			$data['title'] = $this->decorate_title( $data['additional_elements']['title'][0] );
		} else {
			$data['title'] = $this->decorate_title( $this->specific_product->get_title() );

		}
		unset( $data['additional_elements']['title'] );
		$data['title'] = apply_filters(
			'woocommerce_gpf_title',
			$data['title'],
			$this->specific_id,
			$this->general_id
		);

		// Shipping elements only when calculating the Google Feed (could be unconditional?)
		if ( 'google' === $this->feed_format ) {
			$dimension_elements          = $this->shipping_dimensions();
			$dimension_elements          = $this->all_or_nothing_shipping_elements( $dimension_elements );
			$data['additional_elements'] = array_merge(
				$data['additional_elements'],
				$dimension_elements,
			);
		}

		// Flatten down the availability based on the chosen options and the product status.
		$data['additional_elements']['availability'] = $this->calculate_availability(
			$data['additional_elements'],
			$data['is_in_stock'],
			$data['is_on_backorder']
		);
		unset(
			$data['additional_elements']['availability_instock'],
			$data['additional_elements']['availability_backorder'],
			$data['additional_elements']['availability_outofstock']
		);

		// Clear out certification elements if they don't have actual values.
		if ( ! empty( $data['additional_elements']['certification'] ) ) {
			$data['additional_elements']['certification'] = array_filter(
				$data['additional_elements']['certification'],
				static fn( $record ) => ! empty( $record['certification_code'] )
			);
		}

		// Clear out certification elements if they don't have actual values.
		if ( ! empty( $this->additional_elements['certification'] ) ) {
			$this->additional_elements['certification'] = array_filter(
				$this->additional_elements['certification'],
				static fn( $record ) => ! empty( $record['certification_code'] )
			);
		}

		// General, or feed-specific items
		$data['additional_elements'] = apply_filters(
			'woocommerce_gpf_elements',
			$data['additional_elements'],
			$this->general_id,
			( $this->specific_id !== $this->general_id ) ? $this->specific_id : null
		);
		$data['additional_elements'] = apply_filters(
			'woocommerce_gpf_elements_' . $this->feed_format,
			$data['additional_elements'],
			$this->general_id,
			( $this->specific_id !== $this->general_id ) ? $this->specific_id : null
		);

		return $data;
	}

	/**
	 * Get the available description parts for a product.
	 *
	 * @return array  Keyed array of various description types.
	 */
	private function get_product_descriptions(): array {
		$descriptions = [];

		// Populate the various required descriptions.
		$descriptions['main_product']       = $this->general_product->get_description();
		$descriptions['main_product_short'] = $this->general_product->get_short_description();
		$descriptions['variation']          = '';
		if ( $this->general_id !== $this->specific_id ) {
			$descriptions['variation'] = $this->specific_product->get_description();
		}

		return $descriptions;
	}

	/**
	 * @return string
	 */
	private function pick_product_description( array $descriptions ): string {
		$description = '';
		// Work out which description to use.
		$prepopulations     = $this->configuration->get_prepopulations();
		$description_option = ! empty( $prepopulations['description'] ) ?
			$prepopulations['description'] :
			'description:varfull';

		// Support for legacy woocommerce_gpf_description_type filter.
		if ( 'short' === $this->description_type ) {
			$description_option = 'description:varshort';
		}

		switch ( $description_option ) {
			case 'description:shortvar':
				$description = ! empty( $descriptions['main_product_short'] ) ?
					$descriptions['main_product_short'] :
					$descriptions['main_product'];
				if ( ! empty( $descriptions['variation'] ) ) {
					if ( ! empty( $description ) ) {
						$description .= PHP_EOL;
					}
					$description .= $descriptions['variation'];
				}
				break;
			case 'description:full':
				$description = ! empty( $descriptions['main_product'] ) ?
					$descriptions['main_product'] :
					$descriptions['main_product_short'];
				break;
			case 'description:short':
				$description = ! empty( $descriptions['main_product_short'] ) ?
					$descriptions['main_product_short'] :
					$descriptions['main_product'];
				break;
			case 'description:varfull':
				$description = ! empty( $descriptions['main_product'] ) ?
					$descriptions['main_product'] :
					$descriptions['main_product_short'];
				if ( ! empty( $descriptions['variation'] ) ) {
					$description = $descriptions['variation'];
				}
				break;
			case 'description:varshort':
			case 'short':
				$description = ! empty( $descriptions['main_product_short'] ) ?
					$descriptions['main_product_short'] :
					$descriptions['main_product'];
				if ( ! empty( $descriptions['variation'] ) ) {
					$description = $descriptions['variation'];
				}
				break;
			case 'description:fullvar':
				$description = ! empty( $descriptions['main_product'] ) ?
					$descriptions['main_product'] :
					$descriptions['main_product_short'];
				if ( ! empty( $this->descriptions['variation'] ) ) {
					if ( ! empty( $description ) ) {
						$description .= PHP_EOL;
					}
					$description .= $this->descriptions['variation'];
				}
				break;
			default:
				$product_values = $this->calculate_values_for_product();
				$description    = $product_values['description'][0] ?? '';
				break;
		}

		if ( apply_filters( 'woocommerce_gpf_apply_the_content_filter', true ) ) {
			$description = apply_filters(
				'the_content',
				$description
			);
		}

		// Strip out invalid unicode.
		$description = preg_replace(
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u',
			'',
			$description
		);

		// Strip SCRIPT and STYLE tags INCLUDING their content. Taken from wp_strip_all_tags().
		$description = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $description );

		// Strip out HTML comments.
		$description = preg_replace( '/<!--.*?-->/s', '', $description );

		return $description;
	}

	/**
	 * Calculate the values for a product / variation.
	 *
	 * Takes into account:
	 * - values set specifically against the variation
	 * - Values set specifically against the product
	 * - pre-populations that may apply to the variation
	 * - pre-populations that may apply to the variation
	 * - category defaults
	 * - store wide defaults
	 */
	private function calculate_values_for_product(): array {
		// Grab the values against the product.
		$product_values         = $this->get_specific_values_for_product( 'general' );
		$product_prepopulations = $this->get_prepopulations_for_product( 'general' );

		// Grab the values against the variation if different from product ID.
		if ( $this->specific_id !== $this->general_id ) {
			$variation_values         = $this->get_specific_values_for_product( 'specific' );
			$variation_prepopulations = $this->get_prepopulations_for_product( 'specific' );
		} else {
			$variation_values         = [];
			$variation_prepopulations = [];
		}

		$category_values = $this->configuration->get_category_values_for_product( $this->general_id );
		$store_values    = $this->configuration->get_store_default_values();

		// If child.specific then use that
		// elseif parent.specific then use that
		// elseif child.prepulate then use that
		// elseif parent.prepopulate then use that
		// else use category defaults
		// else use store defaults
		$calculated = array_merge(
			$store_values,
			$category_values,
			$product_prepopulations,
			$variation_prepopulations,
			$product_values,
			$variation_values
		);

		// Some values (auto-generated promotion IDs for example) should be merged into the calculated
		// settings, not hierarchically applied, so we add them after the data-sources have been flattened.
		$calculated = $this->merge_calculated_values_for_product( 'general', $calculated );

		if ( 'all' !== $this->feed_format ) {
			$calculated = $this->configuration->remove_other_feeds( $calculated, $this->feed_format );
		}

		return $this->configuration->limit_max_values( $calculated );
	}

	/**
	 * Retrieve specific values set against a product.
	 *
	 * @param $which_product string  Whether to pull info for the 'general' or 'specific' product being generated.
	 *
	 * @return array
	 *
	 * @psalm-param 'general'|'specific' $which_product
	 */
	private function get_specific_values_for_product( string $which_product ) {
		if ( 'general' === $which_product ) {
			$product_settings = get_post_meta( $this->general_id, '_woocommerce_gpf_data', true );
		} else {
			$product_settings = get_post_meta( $this->specific_id, '_woocommerce_gpf_data', true );
		}
		if ( ! is_array( $product_settings ) ) {
			return [];
		}

		return $this->configuration->remove_blanks( $product_settings );
	}

	/**
	 * Get the information that would be pre-populated for a product.
	 *
	 * @param $which_product string  Whether to pull info for the 'general' or 'specific' product being generated.
	 *
	 * @psalm-param 'general'|'specific' $which_product
	 */
	private function get_prepopulations_for_product( string $which_product ): array {
		$results        = [];
		$prepopulations = $this->configuration->get_prepopulations();
		if ( empty( $prepopulations ) ) {
			return $results;
		}
		foreach ( $prepopulations as $gpf_key => $prepopulate ) {
			if ( empty( $prepopulate ) ) {
				continue;
			}
			$value = $this->get_prepopulate_value_for_product( $prepopulate, $which_product );
			if ( ! empty( $value ) ) {
				$results[ $gpf_key ] = $value;
			}
		}

		return $this->configuration->remove_blanks( $results );
	}

	/**
	 * Gets a specific prepopulated value for a product.
	 *
	 * @param string $prepopulate The prepopulation value for a product.
	 * @param string $which_product Whether to pull info for the 'general' or 'specific' product being generated.
	 *
	 * @return array                The prepopulated value for this product.
	 */
	private function get_prepopulate_value_for_product( $prepopulate, $which_product ) {

		list( $type, $value ) = explode( ':', $prepopulate );
		switch ( $type ) {
			case 'tax':
				$result = $this->get_tax_prepopulate_value_for_product( $value, $which_product );
				break;
			case 'taxhierarchy':
				$result = $this->get_tax_hierarchy_prepopulate_value_for_product( $value, $which_product );
				break;
			case 'field':
				$result = $this->get_field_prepopulate_value_for_product( $value, $which_product );
				break;
			case 'meta':
				$result = $this->get_meta_prepopulate_value_for_product( $value, $which_product );
				break;
			case 'cattribute':
				$result = $this->get_custom_attribute_prepopulate_value_for_product( $value, $which_product );
				break;
			case 'method':
				$result = $this->get_method_prepopulate_value_for_product(
					$prepopulate,
					$which_product
				);
				break;
			default:
				$result = apply_filters(
					'woocommerce_gpf_prepopulate_value_for_product',
					[],
					$prepopulate,
					$which_product,
					$this->specific_product,
					$this->general_product
				);
		}

		if ( $result instanceof FallbackRequest ) {
			$result = $this->get_prepopulate_value_for_product( $result->get_destination(), $which_product );
		}

		return $result;
	}

	/**
	 * @param $prepopulate
	 * @param $which_product
	 *
	 * @return array|mixed
	 */
	private function get_method_prepopulate_value_for_product( string $prepopulate, string $which_product ) {

		static $prepopulation_options = null;

		// Grab the correct product.
		if ( 'general' === $which_product ) {
			$product = $this->general_product;
		} else {
			$product = $this->specific_product;
		}

		// Check $fq_method is valid.
		if ( is_null( $prepopulation_options ) ) {
			$prepopulation_options = $this->configuration->get_prepopulate_options();
		}
		if ( ! array_key_exists( $prepopulate, $prepopulation_options ) ) {
			return [];
		}

		// Call the specific method.
		$fq_method          = str_replace( 'method:', '', $prepopulate );
		[ $class, $method ] = explode( '::', $fq_method );
		// Try the $class as a container reference first....
		if ( in_array( $class, $this->container->keys(), true ) ) {
			$class_instance = $this->container[ $class ];
			if ( is_callable( [ $class_instance, $method ] ) ) {
				return call_user_func( [ $class_instance, $method ], $product );
			}
		}
		// If not, try it as an actual class name.
		if ( is_callable( [ $class, $method ] ) ) {
			return call_user_func( [ $class, $method ], $product );
		}

		return [];
	}

	/**
	 * Get an ordered list of terms to prepopulate from for a given taxonomy/product.
	 *
	 * @param string $taxonomy The taxonomy to grab values for.
	 * @param string $which_product Whether to pull info for the 'general' or 'specific' product being generated.
	 *
	 * @return array Array of WP_Term objects.
	 */
	private function get_prepopulate_tax_terms_for_product(
		string $taxonomy,
		string $which_product
	): array {
		if ( 'general' === $which_product ) {
			$product    = $this->general_product;
			$product_id = $this->general_id;
		} else {
			$product    = $this->specific_product;
			$product_id = $this->specific_id;
		}

		// Look at attributes first.
		if ( $product instanceof WC_Product_Variation ) {
			$attributes = $product->get_variation_attributes();
		} else {
			$attributes = $product->get_attributes();
		}

		// If the requested taxonomy is used as an attribute, grab its value for this variation.
		if ( ! empty( $attributes[ 'attribute_' . $taxonomy ] ) ) {
			// Get the name of the assigned attribute value.
			$terms = get_terms(
				[
					'taxonomy' => $taxonomy,
					'slug'     => $attributes[ 'attribute_' . $taxonomy ],
				]
			);
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				$terms = [];
			} else {
				$terms = [ $terms[0] ];
			}

			return $terms;
		}

		// Try looking for a direct taxonomy match against the product.
		if ( ! $this->post_type_has_taxonomy( get_post_type( $product_id ), $taxonomy ) ) {
			// Do not bother looking for one if this post type is not attached to this taxonomy.
			return [];
		}
		return $this->get_ordered_tax_terms_for_product( $product_id, $taxonomy );
	}

	/**
	 * Gets a taxonomy hierarchy string for a product to prepopulate.
	 */
	private function get_ordered_tax_terms_for_product( int $product_id, string $taxonomy ): array {
		$terms = get_the_terms( $product_id, $taxonomy );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return array_reverse(
				$this->term_depth_repository->order_terms_by_depth( $terms )
			);
		}

		return [];
	}

	/**
	 * Gets a taxonomy value for a product to prepopulate.
	 *
	 * @param string $taxonomy The taxonomy to grab values for.
	 * @param string $which_product Whether to pull info for the 'general' or 'specific' product being generated.
	 *
	 * @return array              Array of values to use.
	 */
	private function get_tax_prepopulate_value_for_product(
		string $taxonomy,
		string $which_product
	): array {
		$terms = $this->get_prepopulate_tax_terms_for_product( $taxonomy, $which_product );

		return wp_list_pluck( $terms, 'name' );
	}

	/**
	 * Get a full hierarchy taxonomy value for a product to prepopulate.
	 *
	 * @param string $taxonomy The taxonomy to grab values for.
	 * @param string $which_product Whether to pull info for the 'general' or 'specific' product being generated.
	 *
	 * @return array Array of values to use
	 */
	private function get_tax_hierarchy_prepopulate_value_for_product(
		string $taxonomy,
		string $which_product
	): array {
		$terms = $this->get_prepopulate_tax_terms_for_product( $taxonomy, $which_product );
		foreach ( $terms as $idx => $term ) {
			$terms[ $idx ] = $this->term_depth_repository->get_hierarchy_string( $term );
		}

		return $terms;
	}

	/**
	 * Get a prepopulate value for a specific field for a product.
	 *
	 * @param string $field Details of the field we want.
	 * @param string $which_product Whether to pull info for the 'general' or 'specific' product being generated.
	 *
	 * @return array                The value for this field on this product.
	 */
	private function get_field_prepopulate_value_for_product(
		string $field,
		string $which_product
	): array {
		if ( 'general' === $which_product ) {
			$product = $this->general_product;
		} else {
			$product = $this->specific_product;
		}
		if ( 'product_title' === $field ) {
			return [ $this->general_product->get_title() ];
		}
		if ( 'variation_title' === $field ) {
			if ( $this->specific_product instanceof WC_Product_Variation ) {
				return [ $this->decorate_title( $this->specific_product->get_title() ) ];
			}
			return [];
		}
		if ( 'stock_qty' === $field ) {
			$qty = $product->get_stock_quantity();
			if ( is_null( $qty ) ) {
				return [];
			}

			return [ $product->get_stock_quantity() ];
		}
		if ( 'tax_class' === $field ) {
			$tax_class = $product->get_tax_class();

			return [ ! empty( $tax_class ) ? $tax_class : 'standard' ];
		}
		if ( 'weight_with_unit' === $field ) {
			$return = $product->get_weight();
			if ( ! empty( $return ) ) {
				$return .= ' ' . get_option( 'woocommerce_weight_unit' );

				return [ $return ];
			}

			return [];
		}
		if ( 'cogs' === $field ) {
			if ( ! is_callable( [ $product, 'get_cogs_total_value' ] ) ) {
				return [];
			}
			$cost_price = $product->get_cogs_total_value();
			if ( 0 === $cost_price ) {
				return [];
			}
			$price_string = number_format( (float) $cost_price, 2, '.', '' );

			return [ $price_string . ' ' . get_woocommerce_currency() ];
		}
		if ( is_callable( [ $product, 'get_' . $field ] ) ) {
			$getter = 'get_' . $field;

			return [ $product->$getter() ];
		}

		return [];
	}

	/**
	 * Get a prepopulate value for a specific meta key for a product.
	 *
	 * @param string $meta_key Details of the meta key we're interested in.
	 * @param string $which_product Whether to pull info for the 'general' or 'specific' product being generated.
	 *
	 * @return array                The value for the requested meta key on this product.
	 */
	private function get_meta_prepopulate_value_for_product(
		string $meta_key,
		string $which_product
	): array {
		if ( 'general' === $which_product ) {
			$product_id = $this->general_id;
		} else {
			$product_id = $this->specific_id;
		}

		$values = get_post_meta( $product_id, $meta_key, false );

		$return_values = [];
		foreach ( $values as $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$return_values = array_merge( $return_values, $value );
				continue;
			}
			$return_values[] = $value;
		}

		return $return_values;
	}

	/**
	 * Get a prepopulate value for a custom attribute for a product.
	 *
	 * @param string $attribute_key
	 * @param string $which_product
	 *
	 * @return array
	 */
	private function get_custom_attribute_prepopulate_value_for_product(
		string $attribute_key,
		string $which_product
	): array {
		if ( 'general' === $which_product ) {
			$product = $this->general_product;
		} else {
			$product = $this->specific_product;
		}
		$values = $product->get_attribute( $attribute_key );
		$values = explode( ' | ', $values );

		return $values;
	}

	/**
	 * @return string
	 */
	private function get_shipping_weight(): string {
		$raw_weight = apply_filters(
			'woocommerce_gpf_shipping_weight',
			$this->specific_product->get_weight(),
			$this->specific_id
		);

		if ( $this->shipping_weight_unit !== $this->shipping_weight_original_unit ) {
			return (string) wc_get_weight( $raw_weight, $this->shipping_weight_unit );
		}

		return $raw_weight;
	}

	/**
	 * Add the "advanced" information to the field based on either the
	 * per-product settings, category settings, or store defaults.
	 */
	private function calculate_general_elements(): array {
		$elements       = [];
		$product_values = $this->calculate_values_for_product();
		if ( ! empty( $product_values ) ) {
			foreach ( $product_values as $key => $value ) {
				// Skip description as it is handled separately.
				if ( 'description' === $key ) {
					continue;
				}
				// Fix for legacy product_highlight data stored incorrectly
				if ( 'product_highlight' === $key ) {
					$value = $this->fix_product_highlight_data( $value );
				}
				// Deal with fields that can have multiple, comma separated values
				if ( isset( $this->configuration->product_fields[ $key ]['multiple'] ) && $this->configuration->product_fields[ $key ]['multiple'] && ! is_array( $value ) ) {
					$value = explode( ',', $value );
				}
				$elements[ $key ] = (array) $value;
				// Deal with fields that should be output in the Google feed as a single concatenated set of values
				if ( ! empty( $this->configuration->product_fields[ $key ]['google_single_output'] ) ) {
					$values           = implode(
						$this->configuration->product_fields[ $key ]['google_single_output'],
						$elements[ $key ]
					);
					$elements[ $key ] = [ $values ];
				}
			}
		}

		return $elements;
	}

	/**
	 * Fix legacy data that may have been saved with an extra erroneous hierarchy depth.
	 *
	 * @param $values
	 *
	 * @return mixed
	 */
	private function fix_product_highlight_data( $values ) {
		foreach ( $values as $idx => $value ) {
			if ( is_array( $value ) && isset( $value['highlight'] ) ) {
				$values[ $idx ] = $value['highlight'];
				if ( '' === $values[ $idx ] ) {
					unset( $values[ $idx ] );
				}
			}
		}

		return $values;
	}

	/**
	 * Add additional images to the feed item.
	 */
	private function add_image_props( array $data ): array {
		// Grab the variation thumbnail if available.
		if ( $this->is_variation && $this->use_variation_images ) {
			$image_id = $this->get_the_product_thumbnail_id( $this->specific_product );
			if ( $image_id ) {
				list( $image_link ) = wp_get_attachment_image_src( $image_id, $this->image_style, false );
				if ( ! empty( $image_link ) ) {
					$this->register_image_source( (int) $image_id, $image_link, 'variation_image' );
				}
			}
		}

		// Grab the "product image" from main / parent product next.
		// This can be disabled via a filter iff this is a variation *and* we have a variation image already
		$include_parent_image = true;
		if ( $this->is_variation && ! empty( $image_link ) ) {
			$include_parent_image = apply_filters(
				'woocommerce_gpf_include_parent_image_on_variation',
				$include_parent_image,
				$this->specific_product
			);
		}

		if ( $include_parent_image ) {
			$image_id = $this->get_the_product_thumbnail_id( $this->general_product );
			if ( $image_id ) {
				list ( $image_link ) = wp_get_attachment_image_src( $image_id, $this->image_style, false );
				if ( ! empty( $image_link ) ) {
					$this->register_image_source( (int) $image_id, $image_link, 'product_image' );
				}
			}
		}

		// Get the product ID to inspect for additional images.
		$product_id = $this->specific_id;

		// Work out whether to include additional images on variations. Bail if not.
		$include_on_variations = apply_filters( 'woocommerce_gpf_include_additional_images_on_variations', true, $product_id );
		if ( ! $this->is_variation || $include_on_variations ) {
			// When processing additional images on variations, grab them from the main product.
			if ( $this->is_variation ) {
				$product_id = $this->general_id;
			}

			// List product gallery images first.
			if ( apply_filters( 'woocommerce_gpf_include_product_gallery_images', true ) ) {
				$product_gallery_images = get_post_meta( $product_id, '_product_image_gallery', true );
				if ( ! empty( $product_gallery_images ) ) {
					$product_gallery_images = explode( ',', $product_gallery_images );
					foreach ( $product_gallery_images as $product_gallery_image_id ) {
						$full_image_src = wp_get_attachment_image_src( $product_gallery_image_id, $this->image_style, false );
						// Skip if invalid / missing.
						if ( ! $full_image_src ) {
							continue;
						}
						$this->register_image_source( (int) $product_gallery_image_id, $full_image_src[0], 'product_gallery' );
					}
				}
			}

			// Then attached media.
			if ( apply_filters( 'woocommerce_gpf_include_attached_images', true ) ) {
				$found = false;
				if ( $this->is_variation ) {
					$images = wp_cache_get( 'children_' . $product_id, 'woocommerce_gpf', false, $found );
				}
				if ( false === $found ) {
					$images = get_children(
						[
							'post_parent'    => $product_id,
							'post_status'    => 'inherit',
							'post_type'      => 'attachment',
							'post_mime_type' => 'image',
							'order'          => 'ASC',
							'orderby'        => 'menu_order',
						]
					);
					if ( $this->is_variation ) {
						wp_cache_set( 'children_' . $product_id, $images, 'woocommerce_gpf', 10 );
					}
				}

				if ( is_array( $images ) && count( $images ) ) {
					foreach ( $images as $image ) {
						// Ignore any broken images.
						if ( ! $image ) {
							continue;
						}
						$full_image_src = wp_get_attachment_image_src( $image->ID, $this->image_style, false );
						if ( ! $full_image_src || empty( $full_image_src[0] ) ) {
							continue;
						}
						$this->register_image_source( (int) $image->ID, $full_image_src[0], 'attachment' );
					}
				}
			}
		}

		// Defaults
		$data['image_link']        = '';
		$data['additional_images'] = [];

		/**
		 * Allow integrations to add images to the list.
		 * Integrations should return the information in the following structure:
		 *
		 * [
		 *     'source' => [
		 *         $id => $url,
		 *         $id => $url,
		 *      ],
		 * ]
		 */
		$additional_images_to_register = apply_filters(
			'woocommerce_gpf_additional_images_to_register',
			[],
			$this->specific_product,
			$this->general_product,
			$this->image_style
		);
		foreach ( $additional_images_to_register as $source => $images ) {
			foreach ( $images as $image_id => $image_url ) {
				$this->register_image_source( (int) $image_id, $image_url, $source );
			}
		}

		// Uniqueify the ordered_image_sources array...
		$this->ordered_images = array_unique( $this->ordered_images, SORT_REGULAR );

		/**
		 * Move the first found image into the primary image slot, and the
		 * rest into the "additional images" list.
		 */
		$done_primary_image = false;

		// Retrieve requested primary image ID from meta and use it if set.
		$primary_media_id = $this->general_product->get_meta( 'woocommerce_gpf_primary_media_id', true );
		if ( ! empty( $primary_media_id ) && ! empty( $this->image_sources[ $primary_media_id ] ) ) {
			$data['image_link'] = $this->image_sources[ $primary_media_id ]['url'];
			$done_primary_image = true;
		}

		$lifestyle_media_id = $this->general_product->get_meta( 'woocommerce_gpf_lifestyle_media_id', true );
		if ( ! empty( $lifestyle_media_id ) && ! empty( $this->image_sources[ $lifestyle_media_id ] ) ) {
			$data['lifestyle_image_link'] = $this->image_sources[ $lifestyle_media_id ]['url'];
		}

		// Get the list of image IDs to exclude.
		$excluded_ids = $this->general_product->get_meta( 'woocommerce_gpf_excluded_media_ids', true );
		if ( empty( $excluded_ids ) || ! is_array( $excluded_ids ) ) {
			$excluded_ids = [];
		}

		// Process the list of images and pull through into the image_link / additional image properties.
		foreach ( $this->ordered_images as $image ) {
			// Skip if excluded.
			if ( in_array( $image['id'], $excluded_ids, true ) ) {
				continue;
			}
			// Skip if this is the primary ID as we've already set it outside the loop.
			// Note: lifestyle image ID is not excluded from here since that is Google-feed specific.
			if ( (int) $image['id'] === (int) $primary_media_id ) {
				continue;
			}
			if ( $done_primary_image ) {
				$data['additional_images'][] = $image['url'];
			} else {
				$data['image_link'] = $image['url'];
				$done_primary_image = true;
			}
		}

		return $data;
	}

	/**
	 * Get the ID of the main product image.
	 *
	 * @param WC_Product $product
	 *
	 * @return string|false
	 */
	private function get_the_product_thumbnail_id( WC_Product $product ) {
		$post_thumbnail_id = $product->get_image_id();
		if ( ! $post_thumbnail_id ) {
			return false;
		}

		return $post_thumbnail_id;
	}

	/**
	 * Register an image source for a URL.
	 *
	 * @param $image_id int The media ID
	 * @param $image_link string The URL of the image
	 * @param $source string     The source for that image. @see get_image_sources()
	 */
	private function register_image_source( int $image_id, string $image_link, string $source ): void {
		// Allow filters to ignore images
		if ( ! apply_filters( 'woocommerce_gpf_include_image', true, $image_id, $image_link ) ) {
			return;
		}
		// Make sure $image_link is a proper URL, not just a relative path. Attempt to fix it if it isn't.
		if ( ! isset( wp_parse_url( $image_link )['host'] ) ) {
			$home       = wp_parse_url( home_url() );
			$image_link = $home['scheme'] . '://' . $home['host'] . $image_link;
		}
		// Create a record if we've not seen this image before.
		if ( ! isset( $this->image_sources[ $image_id ] ) ) {
			$this->image_sources[ $image_id ] = [
				'url'     => $image_link,
				'sources' => [
					$source,
				],
			];
			$this->ordered_images[]           = [
				'id'  => (int) $image_id,
				'url' => $image_link,
			];

			return;
		}
		// Add this source to the image's record.
		$this->image_sources[ $image_id ]['sources'] = array_unique(
			array_merge(
				$this->image_sources[ $image_id ]['sources'],
				[
					$source,
				]
			)
		);
	}

	/**
	 * Add the measurements for a product in the store base unit.
	 *
	 * @param $data
	 *
	 * @return array
	 */
	private function shipping_dimensions(): array {
		$length = $this->specific_product->get_length();
		$width  = $this->specific_product->get_width();
		$height = $this->specific_product->get_height();

		// Use cm if the unit isn't supported.
		if ( ! in_array( $this->shipping_dimension_unit, [ 'in', 'cm' ], true ) ) {
			$this->shipping_dimension_unit = 'cm';
		}
		$length = wc_get_dimension( (float) $length, $this->shipping_dimension_unit );
		$width  = wc_get_dimension( (float) $width, $this->shipping_dimension_unit );
		$height = wc_get_dimension( (float) $height, $this->shipping_dimension_unit );

		$dimensions = [];
		if ( $length > 0 ) {
			$dimensions['shipping_length'] = [ $length . ' ' . $this->shipping_dimension_unit ];
		}
		if ( $width > 0 ) {
			$dimensions['shipping_width'] = [ $width . ' ' . $this->shipping_dimension_unit ];
		}
		if ( $height > 0 ) {
			$dimensions['shipping_height'] = [ $height . ' ' . $this->shipping_dimension_unit ];
		}

		return $dimensions;
	}

	/**
	 * Send all shipping measurements, or none.
	 *
	 * Make sure that *if* we have length, width or height, that we send all three. If we're
	 * missing any then we send none of them.
	 *
	 * @param array $elements The current feed item elements relating to shipping measurements.
	 *
	 * @return array
	 */
	private function all_or_nothing_shipping_elements( array $elements ): array {
		if ( empty( $elements['shipping_width'] ) ||
			empty( $elements['shipping_length'] ) ||
			empty( $elements['shipping_height'] ) ) {
			return [];
		}

		return $elements;
	}

	/**
	 * Calculate the availability to send based on the three availability elements.
	 *
	 * @param array $elements The current additional_elements array.
	 * @param bool $is_in_stock
	 * @param bool $is_on_backorder
	 *
	 * @return array
	 */
	private function calculate_availability( array $elements, bool $is_in_stock, bool $is_on_backorder ): array {
		if ( $is_on_backorder ) {
			return ! empty( $elements['availability_backorder'] ) ?
				$elements['availability_backorder'] :
				[ 'in stock' ];
		}

		if ( ! $is_in_stock ) {
			return ! empty( $elements['availability_outofstock'] ) ?
				$elements['availability_outofstock'] :
				[ 'out of stock' ];
		}

		return ! empty( $elements['availability_instock'] ) ?
			$elements['availability_instock'] :
			[ 'in stock' ];
	}

	/**
	 * Determines the lowest price (inc & ex. VAT) for a product, taking into
	 * account its child products as well as the main product price.
	 */
	private function get_product_prices(): array {
		if ( ! $this->calculate_prices ) {
			return [
				'sale_price_ex_tax'     => '',
				'sale_price_inc_tax'    => '',
				'regular_price_ex_tax'  => '',
				'regular_price_inc_tax' => '',
				'sale_price_start_date' => '',
				'sale_price_end_date'   => '',
				'price_ex_tax'          => '',
				'price_inc_tax'         => '',

				'raw_sale_price'        => '',
				'raw_regular_price'     => '',
				'raw_price'             => '',
			];
		}
		// Grab the price of the main product.
		$prices = $this->generate_prices_for_product( $this->specific_product );

		// Adjust the price on variable products if there are cheaper child products.
		if ( $this->specific_product instanceof WC_Product_Variable ) {
			$prices = $this->adjust_prices_for_children( $prices );
		}

		$prices = apply_filters(
			'woocommerce_gpf_item_prices',
			$prices,
			$this->specific_product,
			$this->general_product
		);

		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$this->debug->log( 'Prices calculated for %d are: %s', [ $this->specific_id, var_export( $prices, true ) ] );

		// phpcs:enable

		return $prices;
	}

	/**
	 * Generates the inc, and ex. tax prices for both the regular, and sale
	 * price for a specific product, and returns them.
	 *
	 * @param WC_Product $product Optional product to use. If not provided then
	 *                             $this->specific_product is used.
	 *
	 * @return array
	 */
	private function generate_prices_for_product( WC_Product $product ) {
		// Initialise defaults.
		$prices = [
			'sale_price_ex_tax'     => '',
			'sale_price_inc_tax'    => '',
			'sale_price_start_date' => '',
			'sale_price_end_date'   => '',
			'raw_sale_price'        => '',
			'regular_price_ex_tax'  => '',
			'regular_price_inc_tax' => '',
			'raw_regular_price'     => '',
			'price_ex_tax'          => '',
			'price_inc_tax'         => '',
			'raw_price'             => '',
		];

		// Find out the product type.
		$product_type = $product->get_type();

		// Allow custom price calculators.
		$price_calculator = apply_filters(
			'woocommerce_gpf_product_price_calculator_callback',
			null,
			$product_type,
			$product
		);
		if ( is_callable( $price_calculator ) ) {
			return $price_calculator( $product, $prices );
		}

		// Standard price calculator functions.
		if ( $product instanceof WC_Product_Variable ) {
			// Variable products shouldn't have prices. Works around issue in WC
			// core : https://github.com/woocommerce/woocommerce/issues/16145
			return $prices;
		}

		// Grab the regular price of the base product.
		$regular_price = $product->get_regular_price() ?? '';
		if ( '' !== $regular_price ) {
			$prices['regular_price_ex_tax']  = wc_get_price_excluding_tax( $product, [ 'price' => $regular_price ] );
			$prices['regular_price_inc_tax'] = wc_get_price_including_tax( $product, [ 'price' => $regular_price ] );
			$prices['raw_regular_price']     = $regular_price;
		}
		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$this->debug->log(
			'get_regular_price() for %d is: %s',
			[
				$product->get_id(),
				var_export( $regular_price, true ),
			]
		);
		// phpcs:enable

		// Grab the sale price of the base product. Some plugins (Dyanmic
		// pricing as an example) filter the active price, but not the sale
		// price. If the active price < the regular price treat it as a sale
		// price.
		$sale_price   = $product->get_sale_price() ?? '';
		$active_price = $product->get_price() ?? '';

		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$this->debug->log( 'get_sale_price() for %d is: %s', [ $product->get_id(), var_export( $sale_price, true ) ] );
		$this->debug->log( 'get_active_price() for %d is: %s', [ $product->get_id(), var_export( $active_price, true ) ] );
		// phpcs:enable

		if ( ( empty( $sale_price ) && $active_price < $regular_price ) ||
			( ! empty( $sale_price ) && $active_price < $sale_price ) ) {
			$sale_price = $active_price;
		}
		if ( $sale_price !== '' ) {
			$prices['sale_price_ex_tax']     = wc_get_price_excluding_tax( $product, [ 'price' => $sale_price ] );
			$prices['sale_price_inc_tax']    = wc_get_price_including_tax( $product, [ 'price' => $sale_price ] );
			$prices['sale_price_start_date'] = $product->get_date_on_sale_from();
			$prices['sale_price_end_date']   = $product->get_date_on_sale_to();
			$prices['raw_sale_price']        = $sale_price;
		}

		// If the sale price dates no longer apply, make sure we don't include a sale price.
		$now = new WC_DateTime();
		if ( ! empty( $prices['sale_price_end_date'] ) && $prices['sale_price_end_date'] < $now ) {
			$prices['sale_price_end_date']   = '';
			$prices['sale_price_start_date'] = '';
			$prices['sale_price_ex_tax']     = '';
			$prices['sale_price_inc_tax']    = '';
			$prices['raw_sale_price']        = '';
		}
		// If we have a sale end date in the future, but no start date, set the start date to now()
		if ( ! empty( $prices['sale_price_end_date'] ) &&
			$prices['sale_price_end_date'] > $now &&
			empty( $prices['sale_price_start_date'] )
		) {
			$prices['sale_price_start_date'] = $now;
		}
		// If we have a sale start date in the past, but no end date, do not include the start date.
		if ( ! empty( $prices['sale_price_start_date'] ) &&
			$prices['sale_price_start_date'] < $now &&
			empty( $prices['sale_price_end_date'] )
		) {
			$prices['sale_price_start_date'] = null;
		}
		// If we have a start date in the future, but no end date, assume a one-day sale.
		if ( ! empty( $prices['sale_price_start_date'] ) &&
			$prices['sale_price_start_date'] > $now &&
			empty( $prices['sale_price_end_date'] )
		) {
			$prices['sale_price_end_date'] = clone $prices['sale_price_start_date'];
			$prices['sale_price_end_date']->add( new DateInterval( 'P1D' ) );
		}

		// Populate a "price", using the sale price if there is one, the actual price if not.
		if ( $prices['sale_price_ex_tax'] !== '' ) {
			$prices['price_ex_tax']  = $prices['sale_price_ex_tax'];
			$prices['price_inc_tax'] = $prices['sale_price_inc_tax'];
			$prices['raw_price']     = $prices['raw_sale_price'];
		} else {
			$prices['price_ex_tax']  = $prices['regular_price_ex_tax'];
			$prices['price_inc_tax'] = $prices['regular_price_inc_tax'];
			$prices['raw_price']     = $prices['raw_regular_price'];
		}

		return $prices;
	}

	/**
	 * Adjusts the prices of the feed item according to child products.
	 */
	private function adjust_prices_for_children( array $current_prices ) {
		if ( ! $this->specific_product->has_child() ) {
			return $current_prices;
		}
		$children = $this->specific_product->get_children();
		foreach ( $children as $child ) {
			$child_product = wc_get_product( $child );
			if ( ! $child_product ) {
				continue;
			}
			if ( $child_product instanceof WC_Product_Variation ) {
				$child_is_visible = $child_product->variation_is_visible();
			} else {
				$child_is_visible = $child_product->is_visible();
			}
			if ( ! $child_is_visible ) {
				continue;
			}
			$child_prices = $this->generate_prices_for_product( $child_product );
			if ( ( 0 === (int) $current_prices['price_inc_tax'] ) && ( (float) $child_prices['price_inc_tax'] > 0.0 ) ) {
				$current_prices = $child_prices;
			} elseif ( ( (float) $child_prices['price_inc_tax'] > 0.0 ) && ( $child_prices['price_inc_tax'] < $current_prices['price_inc_tax'] ) ) {
				$current_prices = $child_prices;
			}
		}

		return $current_prices;
	}

	/**
	 * Adds promotion IDs if required.
	 *
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	private function get_promotion_ids_for_product( WC_Product $product ): array {
		// Shortcut if no coupons have category/brand restrictions.
		if ( empty( $this->coupon_category_map ) && empty( $this->coupon_brand_map ) ) {
			return [];
		}

		// Get details of the categories & brands the product is in.
		$product_category_ids = array_flip( $product->get_category_ids() ?? [] );
		$product_brand_ids    = get_the_terms(
			$product->get_id(),
			'product_brand',
		);
		if ( ! is_array( $product_brand_ids ) ) {
			$product_brand_ids = [];
		}
		$product_brand_ids = array_flip(
			array_map(
				fn( $term ) => $term->term_id,
				$product_brand_ids
			)
		);

		// Work out which promotions are available based on those categories/brands.
		$included_promotions = array_merge(
			...array_intersect_key( $this->coupon_category_map['categories'], $product_category_ids ),
			...array_intersect_key( $this->coupon_brand_map['brands'], $product_brand_ids )
		);
		// Work out which promotions are excluded based on those categories/brands.
		$excluded_promotions = array_merge(
			...array_intersect_key( $this->coupon_category_map['excluded_categories'], $product_category_ids ),
			...array_intersect_key( $this->coupon_brand_map['excluded_brands'], $product_brand_ids )
		);

		// Remove the exclusions from the removals, unique-ify and return.
		return array_unique( array_diff( $included_promotions, $excluded_promotions ) );
	}

	/**
	 * @param string $which_product
	 * @param array $calculated
	 *
	 * @return array
	 */
	private function merge_calculated_values_for_product( string $which_product, array $calculated ): array {

		static $promotion_id_required = null;

		// Currently the only thing that this method is used for is automatically-generated promotion IDs, e.g.
		// Where a promotion is limited by category/brand and we need to include the promotion IDs against the products
		// in the product feed. See if the user has a promotion feed that would need us to handle this.
		// If not, we're done.
		$promotion_id_required ??= $this->feed_config_repository->has_active_feed_of_type( 'googlepromotions' );
		if ( ! $promotion_id_required ) {
			return $calculated;
		}

		// Work out which product we're generating for.
		if ( 'general' === $which_product ) {
			$product = $this->general_product;
		} else {
			$product = $this->specific_product;
		}

		if ( empty( $calculated['promotion_id'] ) ) {
			$calculated['promotion_id'] = [];
		} else {
			$calculated['promotion_id'] = explode( ',', $calculated['promotion_id'] );
		}
		$promotion_ids_to_merge     = $this->get_promotion_ids_for_product( $product );
		$calculated['promotion_id'] = array_unique(
			array_merge(
				$calculated['promotion_id'],
				$promotion_ids_to_merge
			)
		);
		if ( ! empty( $calculated['promotion_id'] ) ) {
			$calculated['promotion_id'] = implode( ',', $calculated['promotion_id'] );
		}

		return $calculated;
	}

	/**
	 * Decorate a title with variation-specific suffix if required.
	 *
	 * @param string $title
	 *
	 * @return string
	 * @throws Exception
	 */
	private function decorate_title( string $title ): string {
		// Decorate the title with variation data if required.
		if ( $this->is_variation &&
			apply_filters( 'woocommerce_gpf_include_variation_attributes_in_title', true )
		) {
			$include_labels = apply_filters( 'woocommerce_gpf_include_attribute_labels_in_title', true );
			$suffix         = wc_get_formatted_variation( $this->specific_product, true, $include_labels );
			if ( ! empty( $suffix ) ) {
				$title .= sprintf(
				// Translators: %s is the list of variation attributes to be added to the product title to identify this particular variation.
					_x(
						' (%s)',
						'Variation product suffix wrapper',
						'woocommerce_gpf'
					),
					$suffix
				);
			}
		}
		return $title;
	}

	/**
	 * Whether a given post type has a relationship to a given taxonomy.
	 *
	 * @param string $type
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	private function post_type_has_taxonomy( string $type, string $taxonomy ) {
		if ( ! isset( $this->post_type_relationships[ $type ] ) ) {
			$this->post_type_relationships[ $type ] = get_object_taxonomies( $type );
		}

		return in_array( $taxonomy, $this->post_type_relationships[ $type ], true );
	}
}
