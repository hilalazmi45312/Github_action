<?php
/**
 * WooCommerce Yoast SEO plugin file.
 *
 * @package WPSEO/WooCommerce
 */

use Yoast\WP\SEO\Config\Schema_IDs;

/**
 * Class WPSEO_WooCommerce_Schema.
 */
class WPSEO_WooCommerce_Schema {

	/**
	 * The schema data we're going to output.
	 *
	 * @var array<string, string|int|array<string, string|int>>
	 */
	protected $data;

	/**
	 * WooCommerce version number.
	 *
	 * @var string
	 */
	protected $wc_version;

	/**
	 * The list of product variation images.
	 *
	 * @var array <string,string>
	 */
	private $variation_images;

	/**
	 * The list of product attributes that are allowed in the schema.
	 *
	 * @var array <string>
	 */
	private $allowed_product_attributes = [
		'color',
		'pattern',
		'material',
		'size',
	];

	/**
	 * WPSEO_WooCommerce_Schema constructor.
	 *
	 * @param string $wc_version The WooCommerce version.
	 */
	public function __construct( $wc_version = WC_VERSION ) {
		$this->wc_version       = $wc_version;
		$this->variation_images = [];

		/**
		 * Filter: 'wpseo_allowed_product_attributes' - Allow changing the allowed product attributes.
		 *
		 * @param array<string> $allowed_product_attributes The default product attributes allowed.
		 */
		$this->allowed_product_attributes = apply_filters( 'wpseo_allowed_product_attributes', $this->allowed_product_attributes );

		// Filters & actions below in order of execution.
		add_filter( 'wpseo_frontend_presenters', [ $this, 'remove_unneeded_presenters' ] );
		add_filter( 'wpseo_schema_webpage', [ $this, 'filter_webpage' ], 10, 1 );
		add_filter( 'wpseo_schema_organization', [ $this, 'filter_organization' ], 10, 1 );
		add_filter( 'woocommerce_structured_data_product', [ $this, 'change_product' ], 10, 2 );
		add_filter( 'woocommerce_structured_data_type_for_page', [ $this, 'remove_woo_breadcrumbs' ] );

		// Only needed for WooCommerce versions before 3.8.1.
		if ( version_compare( $this->get_wc_version(), '3.8.1' ) < 0 ) {
			add_filter( 'woocommerce_structured_data_review', [ $this, 'change_reviewed_entity' ] );
		}

		add_action( 'wp_footer', [ $this, 'output_schema_footer' ] );
	}

	/**
	 * Get the WooCommerce version.
	 *
	 * @return string The WooCommerce version.
	 */
	public function get_wc_version() {
		return $this->wc_version;
	}

	/**
	 * If this is a product page, remove some of the presenters so we don't output them.
	 *
	 * @param array<string> $presenters Array of presenters.
	 *
	 * @return array<string> Array of presenters.
	 */
	public function remove_unneeded_presenters( $presenters ) {
		if ( is_product() ) {
			foreach ( $presenters as $key => $object ) {
				if (
					is_a( $object, 'Yoast\WP\SEO\Presenters\Open_Graph\Article_Publisher_Presenter' )
					|| is_a( $object, 'Yoast\WP\SEO\Presenters\Open_Graph\Article_Author_Presenter' )
				) {
					unset( $presenters[ $key ] );
				}
			}
		}

		return $presenters;
	}

	/**
	 * Should the yoast schema output be used.
	 *
	 * @return bool Whether the Yoast SEO schema should be output.
	 */
	public static function should_output_yoast_schema() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Using WPSEO hook.
		return apply_filters( 'wpseo_json_ld_output', true );
	}

	/**
	 * Outputs the Woo Schema blob in the footer.
	 *
	 * @return bool False when there's nothing to output, true when we did output something.
	 */
	public function output_schema_footer() {
		if ( ! is_array( $this->data ) || $this->data === [] ) {
			return false;
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- We need to output HTML. If we escape this we break it.
		echo new WPSEO_WooCommerce_Schema_Presenter(
			[ $this->data ],
			[
				'yoast-schema-graph',
				'yoast-schema-graph--woo',
				'yoast-schema-graph--footer',
			]
		);

		// phpcs:enable

		return true;
	}

	/**
	 * Changes the WebPage output to point to Product as the main entity.
	 *
	 * @param array<string, string|array<string>> $webpage_data Product Schema data.
	 *
	 * @return array<string, string|array<string>> Product Schema data.
	 */
	public function filter_webpage( $webpage_data ) {
		if ( is_product() ) {
			// We force the page type to be WebPage and ItemPage.
			$webpage_data['@type'] = [ 'WebPage', 'ItemPage' ];
			// We normally add a `ReadAction` on pages, we're replacing with a `BuyAction` on product pages.
			$webpage_data['potentialAction'] = [
				'@type'  => 'BuyAction',
				'target' => YoastSEO()->meta->for_current_page()->canonical,
			];
			unset( $webpage_data['datePublished'], $webpage_data['dateModified'] );
		}
		if ( is_checkout() || is_checkout_pay_page() ) {
			$webpage_data['@type'] = 'CheckoutPage';
			// We normally add a `ReadAction` on pages, adding that on a checkout makes no sense.
			unset( $webpage_data['potentialAction'] );
		}

		return $webpage_data;
	}

	/**
	 * Changes the Organization output to add a return policy if its available.
	 *
	 * @param array<string, string|array<string>> $organization_data Organization schema data.
	 *
	 * @return array<string, string|array<string>> Organization Schema data.
	 */
	public function filter_organization( $organization_data ) {
		$schema_return_policy_id = WPSEO_Options::get( 'woo_schema_return_policy' );
		if ( ! empty( $schema_return_policy_id ) ) {
			$url = get_permalink( $schema_return_policy_id );
			if ( $url ) {
				$organization_data['hasMerchantReturnPolicy'] = [
					'@type'              => 'MerchantReturnPolicy',
					'merchantReturnLink' => esc_url( $url ),
				];
			}
		}

		return $organization_data;
	}

	/**
	 * Changes the Review output to point to Product as the reviewed Item.
	 *
	 * @param array<string|array<string>> $data Review Schema data.
	 *
	 * @return array<string|array<string>> Review Schema data.
	 */
	public function change_reviewed_entity( $data ) {
		unset( $data['@type'] );
		unset( $data['itemReviewed'] );

		$this->data['review'][] = $data;

		/**
		 * Filter: 'wpseo_schema_review' - Allow changing the Review type.
		 *
		 * @param array $data The Schema Review data.
		 */
		$this->data = apply_filters( 'wpseo_schema_review', $this->data );

		return [];
	}

	/**
	 * Filter Schema Product data to work.
	 *
	 * @param array<string, string|int|array<string, string|int>> $data    Schema Product data.
	 * @param WC_Product                                          $product Product object.
	 *
	 * @return array<string, string|int|array<string, string|int>> Schema Product data.
	 */
	public function change_product( $data, $product ) {
		$data = $this->change_seller_in_offers( $data );
		$data = $this->filter_reviews( $data, $product );
		$data = $this->filter_sku( $data, $product );

		if ( $product instanceof WC_Product_Variable ) {
			$data = $this->filter_variations( $data, $product );
		}
		else {
			$data = $this->filter_offers( $data, $product );
		}

		// This product is the main entity of this page, so we set it as such.
		$data['mainEntityOfPage'] = [
			'@id' => YoastSEO()->meta->for_current_page()->main_schema_id,
		];

		// Now let's add this data to our overall output.
		$this->data = $data;

		$this->add_image();
		$this->add_variation_images();
		$this->add_brand( $product );
		$this->add_manufacturer( $product );
		$this->maybe_add_product_attributes( $product );
		$this->add_global_identifier( $product );

		/**
		 * Filter: 'wpseo_schema_product' - Allow changing the Product type.
		 *
		 * @param array $data The Schema Product data.
		 */
		$this->data = apply_filters( 'wpseo_schema_product', $this->data );

		return [];
	}

	/**
	 * Filters the offers array to enrich it.
	 *
	 * @param array<string, string|int|array<string, string|int>> $data    Schema Product data.
	 * @param WC_Product                                          $product The product.
	 *
	 * @return array<string, string|int|array<string, string|int>> Schema Product data.
	 */
	protected function filter_offers( $data, $product ) {
		if ( ! isset( $data['offers'] ) || $data['offers'] === [] ) {
			return $data;
		}

		$data['offers'] = $this->filter_sales( $data['offers'], $product );

		foreach ( $data['offers'] as $key => $offer ) {

			// Add an @id to the offer.
			$data['offers'][ $key ]['@id'] = YoastSEO()->meta->for_current_page()->site_url . '#/schema/offer/' . $product->get_id() . '-' . $key;

			// WooCommerce 9.5.0 introduced the usage of UnitPriceSpecification for offers.
			if ( version_compare( $this->get_wc_version(), '9.5.0' ) < 0 ) {
				$price = WPSEO_WooCommerce_Utils::get_product_display_price( $product );
				$this->add_price_specifications( $data, $key, $price );
			}
			else {
				$this->add_unit_price_specifications( $data, $key, $offer['priceSpecification'], $product );
			}

			$data['offers'][ $key ]['seller'] = [ '@id' => YoastSEO()->meta->for_current_page()->site_url . '#organization' ];

			// Remove price property from Schema output by WooCommerce.
			if ( isset( $data['offers'][ $key ]['price'] ) ) {
				unset( $data['offers'][ $key ]['price'] );
			}
			// Remove priceCurrency property from Schema output by WooCommerce.
			if ( isset( $data['offers'][ $key ]['priceCurrency'] ) ) {
				unset( $data['offers'][ $key ]['priceCurrency'] );
			}

			// Alter availability when product is "on backorder".
			if ( $product->is_on_backorder() ) {
				$data['offers'][ $key ]['availability'] = 'https://schema.org/PreOrder';
			}
		}

		// We don't want an array with keys, we just need the offers.
		$data['offers'] = array_values( $data['offers'] );

		return $data;
	}

	/**
	 * Filters the offers array to wrap and enrich it.
	 *
	 * @param array<string, string|int|array<string, string|int>> $data    Schema Product data.
	 * @param WC_Product                                          $product The product.
	 *
	 * @return array<string, string|int|array<string, string|int>> Schema Product data.
	 */
	protected function filter_variations( $data, $product ) {
		if ( ! isset( $data['offers'] ) || $data['offers'] === [] ) {
			return $data;
		}

		$data['@type'] = 'ProductGroup';
		if ( isset( $data['sku'] ) ) {
			$data['productGroupID'] = $data['sku'];
		}

		$variation_attributes_names = $this->get_variation_attributes_names( $product );
		if ( ! empty( $variation_attributes_names ) ) {
			$data['variesBy'] = $variation_attributes_names;
		}

		$data['hasVariant'] = [];
		unset( $data['offers'] );

		$product_variations = $product->get_available_variations( 'object' );
		foreach ( $product_variations as $key => $variation ) {
			$variant_schema = $this->add_individual_product_variation( $product, $variation, $key );
			if ( isset( $variant_schema['image'] ) ) {
				$this->variation_images[] = [ '@id' => $variant_schema['image']['@id'] ];
			}
			$data['hasVariant'][] = $variant_schema;
		}

		// We don't want an array with keys, we just need the offers.
		$data['hasVariant'] = array_values( $data['hasVariant'] );

		return $data;
	}

	/**
	 * Filters the offers array on sales, possibly unset them.
	 *
	 * @param array<string, string|int|array<string, string|int>> $offers  Schema Offer data.
	 * @param WC_Product                                          $product The product.
	 *
	 * @return array<string, string|int|array<string, string|int>> Schema Offer data.
	 */
	protected function filter_sales( $offers, $product ) {
		foreach ( $offers as $key => $offer ) {
			/*
			 * WooCommerce assumes all prices will be valid until the end of next year,
			 * unless on sale and there is an end date. We keep the `priceValidUntil`
			 * property only for products with a sale price and a sale end date.
			 */

			if ( ! $product->is_on_sale() || ! $product->get_date_on_sale_to() ) {
				unset( $offers[ $key ]['priceValidUntil'] );
			}
		}

		return $offers;
	}

	/**
	 * Removes the SKU when it's empty to prevent the WooCommerce fallback to the product's ID.
	 *
	 * @param array<string, string|int|array<string, string|int>> $data    Schema Product data.
	 * @param WC_Product                                          $product The product.
	 *
	 * @return array<string, string|int|array<string, string|int>> Schema Product data.
	 */
	protected function filter_sku( $data, $product ) {
		/*
		 * When the SKU of a product is left empty, WooCommerce makes it the value of the product's id.
		 * In this method we check for that and unset it if done so.
		 */
		if ( empty( $product->get_sku() ) ) {
			unset( $data['sku'] );
		}

		return $data;
	}

	/**
	 * Removes the Woo Breadcrumbs from their Schema output.
	 *
	 * @param array<string> $types Types of Schema Woo will render.
	 *
	 * @return array<string> Types of Schema Woo will render.
	 */
	public function remove_woo_breadcrumbs( $types ) {
		foreach ( $types as $key => $type ) {
			if ( $type === 'breadcrumblist' ) {
				unset( $types[ $key ] );
			}
		}

		return $types;
	}

	/**
	 * Retrieve the global identifier type and value if we have one.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function add_global_identifier( $product ) {
		$product_id               = $product->get_id();
		$global_identifier_values = get_post_meta( $product_id, 'wpseo_global_identifier_values', true );

		if ( ! is_array( $global_identifier_values ) || $global_identifier_values === [] ) {
			return false;
		}

		foreach ( $global_identifier_values as $type => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			$this->data[ $type ] = $value;
			if ( $type === 'isbn' ) {
				if ( ! isset( $this->data['@type'] ) ) {
					$this->data['@type'] = 'Product';
				}
				if ( ! is_array( $this->data['@type'] ) ) {
					$this->data['@type'] = [ $this->data['@type'] ];
				}
				$this->data['@type'] = array_merge( [ 'Book' ], $this->data['@type'] );
			}
		}

		return true;
	}

	/**
	 * Update the seller attribute to reference the Organization, when it is set.
	 *
	 * @param array<string, string|int|array<string, string|int>> $data Schema Product data.
	 *
	 * @return array<string, string|int|array<string, string|int>> Schema Product data.
	 */
	protected function change_seller_in_offers( $data ) {
		$company_or_person = WPSEO_Options::get( 'company_or_person', false );
		$company_name      = WPSEO_Options::get( 'company_name' );

		if ( $company_or_person !== 'company' || empty( $company_name ) ) {
			return $data;
		}

		if ( ! empty( $data['offers'] ) ) {
			foreach ( $data['offers'] as $key => $offer ) {
				$data['offers'][ $key ]['seller'] = [
					'@id' => trailingslashit( YoastSEO()->meta->for_current_page()->site_url ) . Schema_IDs::ORGANIZATION_HASH,
				];
			}
		}

		return $data;
	}

	/**
	 * Add brand to our output.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return void
	 */
	private function add_brand( $product ) {
		$schema_brand = WPSEO_Options::get( 'woo_schema_brand' );
		if ( ! empty( $schema_brand ) ) {
			$this->add_attribute_as( 'brand', $product, $schema_brand, 'Brand' );
		}
	}

	/**
	 * Add manufacturer to our output.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return void
	 */
	private function add_manufacturer( $product ) {
		$schema_manufacturer = WPSEO_Options::get( 'woo_schema_manufacturer' );
		if ( ! empty( $schema_manufacturer ) ) {
			$this->add_attribute_as( 'manufacturer', $product, $schema_manufacturer );
		}
	}

	/**
	 * Adds an attribute to our Product data array with the value from a taxonomy, as an Organization,
	 *
	 * @param string     $attribute The attribute we're adding to Product.
	 * @param WC_Product $product   The WooCommerce product we're working with.
	 * @param string     $taxonomy  The taxonomy to get the attribute's value from.
	 * @param string     $type      The Schema type to use.
	 *
	 * @return void
	 */
	private function add_attribute_as( $attribute, $product, $taxonomy, $type = 'Organization' ) {
		$term = $this->get_primary_term_or_first_term( $taxonomy, $product->get_id() );

		if ( $term !== null ) {
			$this->data[ $attribute ] = [
				'@type' => $type,
				'name'  => wp_strip_all_tags( $term->name ),
			];
		}
	}

	/**
	 * Adds image schema.
	 *
	 * @return void
	 */
	private function add_image() {
		/**
		 * WooCommerce will set the image to false if none is available. This is incorrect schema and we should fix it
		 * for our users for now.
		 *
		 * See https://github.com/woocommerce/woocommerce/issues/24188.
		 */
		if ( isset( $this->data['image'] ) && $this->data['image'] === false ) {
			unset( $this->data['image'] );
		}

		if ( has_post_thumbnail() ) {
			$this->data['image'] = [
				'@id' => YoastSEO()->meta->for_current_page()->canonical . Schema_IDs::PRIMARY_IMAGE_HASH,
			];

			return;
		}

		// Fallback to WooCommerce placeholder image.
		if ( function_exists( 'wc_placeholder_img_src' ) ) {
			$image_schema_id     = YoastSEO()->meta->for_current_page()->canonical . '#woocommerceimageplaceholder';
			$placeholder_img_src = wc_placeholder_img_src();
			$this->data['image'] = YoastSEO()->helpers->schema->image->generate_from_url( $image_schema_id, $placeholder_img_src, '', false, false );
		}
	}

	/**
	 * Adds image schema for product variations to main node.
	 *
	 * @return void
	 */
	private function add_variation_images() {
		$image_list = [];
		if ( is_array( $this->variation_images ) && count( $this->variation_images ) !== 0 ) {
			$image_list[] = $this->data['image'];
			foreach ( $this->variation_images as $image ) {
				$image_list[] = $image;
			}
			$this->data['image'] = $image_list;
		}
	}

	/**
	 * Adds the product attributes to the Schema output.
	 *
	 * @param WC_Product $product The product object.
	 *
	 * @return void
	 */
	private function maybe_add_product_attributes( $product ) {
		if ( $product->get_type() === 'variable' ) {
			return;
		}

		$attributes     = $product->get_attributes();
		$product_schema = $this->data;

		foreach ( $attributes as $attribute ) {
			$attribute_name = strtolower( wc_attribute_label( $attribute->get_name() ) );

			if ( ! in_array( $attribute_name, $this->allowed_product_attributes, true ) ) {
				continue;
			}

			$attribute_options = $attribute->get_options();

			if ( count( $attribute_options ) > 1 || is_null( $attribute_options ) ) {
				continue;
			}

			$attribute_value_label = $this->get_attribute_label( reset( $attribute_options ) );

			if ( ! empty( $attribute_value_label ) ) {
				$product_schema[ $attribute_name ] = $attribute_value_label;
			}
		}

		$this->data = $product_schema;
	}

	/**
	 * Get the label of an attribute value.
	 *
	 * @param int $attribute_value_id The attribute values id.
	 *
	 * @return string|null The attribute value label.
	 */
	private function get_attribute_label( $attribute_value_id ) {
		$term = get_term( $attribute_value_id );
		if ( ! is_wp_error( $term ) && $term ) {
			return $term->name;
		}

		return null;
	}

	/**
	 * Tries to get the primary term, then the first term, null if none found.
	 *
	 * @param string $taxonomy_name Taxonomy name for the term.
	 * @param int    $post_id       Post ID for the term.
	 *
	 * @return WP_Term|null The primary term, the first term or null.
	 */
	protected function get_primary_term_or_first_term( $taxonomy_name, $post_id ) {
		$primary_term    = new WPSEO_Primary_Term( $taxonomy_name, $post_id );
		$primary_term_id = $primary_term->get_primary_term();

		if ( $primary_term_id !== false ) {
			$primary_term = get_term( $primary_term_id );
			if ( $primary_term instanceof WP_Term ) {
				return $primary_term;
			}
		}

		$terms = get_the_terms( $post_id, $taxonomy_name );

		if ( is_array( $terms ) && count( $terms ) > 0 ) {
			return $terms[0];
		}

		return null;
	}

	/**
	 * Adds the individual product variants as variants of the offer.
	 *
	 * @param WC_Product           $product   The WooCommerce Product we're working with.
	 * @param WC_Product_Variation $variation The WooCommerce variation we're working with.
	 * @param int                  $key       The nth product variation.
	 *
	 * @return array<string|int|array<string|int>> Schema Offers data.
	 */
	protected function add_individual_offer( $product, $variation, $key ) {

		$currency           = get_woocommerce_currency();
		$tax_enabled        = wc_tax_enabled();
		$prices_include_tax = WPSEO_WooCommerce_Utils::prices_have_tax_included();
		$decimals           = wc_get_price_decimals();
		$product_id         = $product->get_id();
		$product_name       = $product->get_name();
		$variation_name     = implode( ' / ', $variation->get_attributes() );

		$offer = [
			'@type'              => 'Offer',
			'@id'                => YoastSEO()->meta->for_current_page()->site_url . '#/schema/offer/' . $product_id . '-' . $key,
			'name'               => $product_name . ' - ' . $variation_name,
			'url'                => get_permalink( $variation->get_id() ),
			'priceSpecification' => [
				[
					'@type'         => 'UnitPriceSpecification',
					'price'         => wc_format_decimal( $variation->get_regular_price(), $decimals ),
					'priceCurrency' => $currency,
				],
			],
		];

		if ( $tax_enabled ) {
			$offer['priceSpecification'][0]['valueAddedTaxIncluded'] = $prices_include_tax;
		}

		if ( $variation->is_on_sale() ) {
			// If there is a sale the original price should be marked with ListPrice.
			$offer['priceSpecification'][0]['priceType'] = 'https://schema.org/ListPrice';
			$sale_offer                                  = [
				'@type'         => 'UnitPriceSpecification',
				'price'         => wc_format_decimal( $variation->get_sale_price(), $decimals ),
				'priceCurrency' => $currency,
			];
			if ( $this->is_sale_date_specified( $variation ) ) {
				$sale_offer['validThrough'] = $variation->get_date_on_sale_to()->date_i18n();
			}
			if ( $tax_enabled ) {
				$sale_offer['valueAddedTaxIncluded'] = $prices_include_tax;
			}
			$offer['priceSpecification'][] = $sale_offer;

		}
		$offer['priceSpecification'] = array_values( $offer['priceSpecification'] );
		if ( $product->is_on_backorder() ) {
			$offer['availability'] = 'https://schema.org/PreOrder';
		}

		/**
		 * Filter: 'wpseo_schema_offer' - Allow changing the offer schema.
		 *
		 * @param array<string|int|array<string|int>> $offer     The schema offer data.
		 * @param WC_Product_Variation                $variation The WooCommerce product variation we're working with.
		 * @param WC_Product                          $product   The WooCommerce product we're working with.
		 */
		$data = apply_filters( 'wpseo_schema_offer', $offer, $variation, $product );

		if ( is_array( $data ) ) {
			return $data;
		}

		return $offer;
	}

	/**
	 * Adds the individual product variants.
	 *
	 * @param WC_Product           $product   The WooCommerce product we're working with.
	 * @param WC_Product_Variation $variation The variation data.
	 * @param int                  $key       The nth product variation data.
	 *
	 * @return array<string, string|int|array<string, string|int>> Schema Product data.
	 */
	protected function add_individual_product_variation( $product, $variation, $key ) {
		$product_id         = $product->get_id();
		$product_name       = $product->get_name();
		$product_global_ids = get_post_meta( $product_id, 'wpseo_global_identifier_values', true );

		$variation_attributes = $variation->get_attributes();
		$variation_name       = implode( ' / ', $variation_attributes );

		$product_schema = [
			'@type' => 'Product',
			'@id'   => YoastSEO()->meta->for_current_page()->site_url . '#/product/' . $product_id . '-' . $key,
			'name'  => $product_name . ' - ' . $variation_name,
			'url'   => get_permalink( $variation->get_id() ),
			'image' => $this->add_variation_image( $variation ),

		];

		// Add the color, pattern and material attributes to the schema (if present).
		foreach ( $variation_attributes as $attribute => $value ) {
			$attribute_name = strtolower( wc_attribute_label( $attribute ) );

			if ( in_array( $attribute_name, $this->allowed_product_attributes, true ) ) {
				$product_schema[ $attribute_name ] = $value;
			}
		}

		if ( $variation->get_sku() ) {
			$product_schema['sku'] = $variation->get_sku();
		}

		if ( $variation->get_description() !== '' ) {
			$product_schema['description'] = YoastSEO()->helpers->string->strip_all_tags( stripslashes( $variation->get_description() ) );
		}
		// Adds variation's global identifiers to the $offer array.
		$variation_global_ids    = get_post_meta( $variation->get_id(), 'wpseo_variation_global_identifiers_values', true );
		$global_identifier_types = [
			'gtin8',
			'gtin12',
			'gtin13',
			'gtin14',
			'mpn',
		];

		foreach ( $global_identifier_types as $global_identifier_type ) {
			if ( isset( $variation_global_ids[ $global_identifier_type ] ) && ! empty( $variation_global_ids[ $global_identifier_type ] ) ) {
				$product_schema[ $global_identifier_type ] = $variation_global_ids[ $global_identifier_type ];
			}
			elseif ( isset( $product_global_ids[ $global_identifier_type ] ) && ! empty( $product_global_ids[ $global_identifier_type ] ) ) {
				$product_schema[ $global_identifier_type ] = $product_global_ids[ $global_identifier_type ];
			}
		}

		$product_schema['offers'] = $this->add_individual_offer( $product, $variation, $key );

		return $product_schema;
	}

	/**
	 * Adds image schema for a product variation.
	 *
	 * @param WC_Product_Variation $variation The variation data.
	 *
	 * @return array<string, string> The imageObject schema.
	 */
	private function add_variation_image( $variation ) {
		$image_id             = $variation->get_image_id();
		$base_image_schema_id = YoastSEO()->meta->for_current_page()->canonical;

		// Fallback to WooCommerce placeholder image.
		if ( empty( $image_id ) && function_exists( 'wc_placeholder_img_src' ) ) {
			$image_schema_id     = "$base_image_schema_id#woocommerceimageplaceholder";
			$placeholder_img_src = wc_placeholder_img_src();

			return YoastSEO()->helpers->schema->image->generate_from_url( $image_schema_id, $placeholder_img_src, '', false, false );
		}

		$metadata      = YoastSEO()->helpers->image->get_metadata( $image_id );
		$image_title   = wp_basename( $metadata['file'] );
		$image_caption = $metadata['image_meta']['caption'];

		$image_schema_id = "$base_image_schema_id#$image_title";

		return YoastSEO()->helpers->schema->image->generate_from_attachment_id( $image_schema_id, $image_id, $image_caption );
	}

	/**
	 * Adds the VAT to the price specification.
	 *
	 * @param array<string, string|int|array<string, string|int>> $price_specification The price specification object.
	 *
	 * @return void
	 */
	private function maybe_add_vat( &$price_specification ) {
		if ( ! is_array( $price_specification ) ) {
			return;
		}

		if ( wc_tax_enabled() ) {
			$price_specification['valueAddedTaxIncluded'] = WPSEO_WooCommerce_Utils::prices_have_tax_included();
		}
		elseif ( isset( $price_specification['valueAddedTaxIncluded'] ) ) {
			unset( $price_specification['valueAddedTaxIncluded'] );
		}
	}

	/**
	 * Adds the price specification to the Schema Product data in case it is expressed with UnitPriceSpecification
	 * objects.
	 *
	 * @param array<string,string|int|array<string,string|int>> $data                 Schema Product data.
	 * @param int                                               $key                  The current offer key.
	 * @param array<array<string,string|int>>                   $price_specifications The price specification object.
	 * @param WC_Product                                        $product              The WooCommerce product we're
	 *                                                                                working with.
	 *
	 * @return void
	 */
	private function add_unit_price_specifications( &$data, $key, $price_specifications, $product ) {
		foreach ( $price_specifications as &$price_specification ) {
			$this->maybe_add_vat( $price_specification );
			// We don't support WooCommerce validThrough date for ListPrice as it will be set by default to the end of the next year.
			if ( $this->is_sale_date_specified( $product ) && ! $this->is_list_price( $price_specification ) ) {
				continue;
			}

			if ( isset( $price_specification['validThrough'] ) ) {
				unset( $price_specification['validThrough'] );
			}
		}
		$data['offers'][ $key ]['priceSpecification'] = $price_specifications;
	}

	/**
	 * Adds the price specification to the Schema Product data.
	 *
	 * @param array<string,string|int|array<string,string|int>> $data  Schema Product data.
	 * @param int                                               $key   The current offer key.
	 * @param float                                             $price The price associated to the offer.
	 *
	 * @return void
	 */
	private function add_price_specifications( &$data, $key, $price ) {

		$data['offers'][ $key ]['priceSpecification']['@type'] = 'PriceSpecification';
		$data['offers'][ $key ]['priceSpecification']['price'] = $price;

		$this->maybe_add_vat( $data['offers'][ $key ]['priceSpecification'] );
	}

	/**
	 * Enhances the review data output by WooCommerce.
	 *
	 * @param array<string, string|int|array<string, string|int>> $data    Review Schema data.
	 * @param WC_Product                                          $product The WooCommerce product we're working with.
	 *
	 * @return array<string, string|int|array<string, string|int>> Review Schema data.
	 */
	protected function filter_reviews( $data, $product ) {
		if ( ! isset( $data['review'] ) || $data['review'] === [] ) {
			return $data;
		}

		$product_id   = $product->get_id();
		$product_name = $product->get_name();

		foreach ( $data['review'] as $key => $review ) {
			$data['review'][ $key ]['@id']  = YoastSEO()->meta->for_current_page()->site_url . '#/schema/review/' . $product_id . '-' . $key;
			$data['review'][ $key ]['name'] = $product_name;
		}

		return $data;
	}

	/**
	 * Check if the product is on sale and the sale end date is specified.
	 *
	 * @param WC_Product $product The WooCommerce product we're working with.
	 *
	 * @return bool True if the product is on sale and the sale end date is specified, false otherwise.
	 */
	protected function is_sale_date_specified( $product ) {
		return $product->is_on_sale() && $product->get_date_on_sale_to();
	}

	/**
	 * Check if the price specification is a ListPrice.
	 *
	 * @param array<string,string|int> $price_specification The price specification object.
	 *
	 * @return bool True if the price specification is a ListPrice, false otherwise.
	 */
	protected function is_list_price( $price_specification ) {
		return isset( $price_specification['priceType'] ) && $price_specification['priceType'] === 'https://schema.org/ListPrice';
	}

	/**
	 * Gets the variation attributes names for the product.
	 *
	 * @param WC_Product $product The product object.
	 *
	 * @return array<string>
	 */
	public function get_variation_attributes_names( WC_Product $product ): array {
		$variation_attributes       = $product->get_variation_attributes();
		$variation_attributes_names = [];
		foreach ( $variation_attributes as $attribute_name => $attribute_value ) {
			$attribute_label = strtolower( wc_attribute_label( $attribute_name ) );
			if ( in_array( $attribute_label, $this->allowed_product_attributes, true ) ) {
				$variation_attributes_names[] = 'https://www.schema.org/' . $attribute_label;
			}
		}

		return $variation_attributes_names;
	}
}
