<?php
/**
 * class-rest-products-controller.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package woocommerce-product-search
 * @since 6.0.0
 */

namespace com\itthinx\woocommerce\search\engine;

use Automattic\WooCommerce\Utilities\I18nUtil;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST Products Controller
 */
class REST_Products_Controller extends REST_Controller {

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'rest-products-controller';

	/**
	 * Cache lifetime.
	 *
	 * @var int
	 */
	const CACHE_LIFETIME = Cache::HOUR;

	/**
	 * Default minimum for unprivileged requests, used as minimum for 'per_page' and 'limit' parameters.
	 *
	 * @var int
	 */
	const UNPRIVILEGED_MINIMUM = 1;

	/**
	 * Default maximum for unprivileged requests, used as maximum for 'per_page' and 'limit' parameters.
	 *
	 * @var integer
	 */
	const UNPRIVILEGED_MAXIMUM = 100;

	/**
	 * Default per page.
	 *
	 * @var integer
	 */
	const PER_PAGE = 10;

	/**
	 * Default page.
	 *
	 * @var integer
	 */
	const PAGE = 1;

	/**
	 * Default order.
	 *
	 * @var string
	 */
	const ORDER = 'desc';

	/**
	 * Default orderby.
	 *
	 * @var string
	 */
	const ORDERBY = 'date';

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wps/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products';

	/**
	 * @var integer
	 */
	protected $total = 0;

	/**
	 * Whether the request is made with privileges.
	 *
	 * @return boolean
	 */
	protected function is_privileged() {
		$is_privileged = false;
		$post_type_obj = get_post_type_object( 'product' );
		if ( $post_type_obj !== null ) {
			$is_privileged =
				function_exists( 'current_user_can' ) &&
				(
					current_user_can( $post_type_obj->cap->edit_posts ) ||
					current_user_can( 'manage_woocommerce' )
				);
		}
		$is_privileged = apply_filters( 'woocommerce_product_search_rest_products_controller_is_privileged', $is_privileged );
		return $is_privileged;
	}

	/**
	 * Register the REST routes for products retrieved through the search engine.
	 *
	 * {@inheritDoc}
	 *
	 * @see \WP_REST_Controller::register_routes()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_items'),
					'permission_callback' => array( $this, 'get_items_permission_check' ),
					'args' => $this->get_collection_params()
				),
				'schema' => array( $this, 'get_public_item_schema' )
			),
			true
		);
	}

	/**
	 * Check if get (read) access is granted for the request.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return boolean|\WP_Error
	 */
	public function get_items_permission_check( $request ) {

		$result = apply_filters( 'woocommerce_product_search_rest_products_controller_get_permission', true, $request );
		if ( is_scalar( $result ) ) {
			$result = boolval( $result );
		} else if ( !( $result instanceof \WP_Error ) ) {
			$result = false;
		}
		return $result;
	}

	/**
	 * Provides the query parameters that are accepted for the request.
	 *
	 * {@inheritDoc}
	 *
	 * @see \WP_REST_Controller::get_collection_params()
	 *
	 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params = array();

		$is_privileged = $this->is_privileged();

		$params['context'] = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['status'] = array(
			'default'           => 'publish',
			'description'       => __( 'Limit the results by post status.', 'woocommerce-product-search' ),
			'type'              => 'string',
			'enum'              => $is_privileged ? array_merge( array( 'any', 'future', 'trash' ), array_keys( get_post_statuses() ) ) : array( 'publish' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'default'           => 'menu_order',
			'description'       => __( 'Sort products by indicated option.', 'woocommerce-product-search' ),
			'type'              => 'string',
			'enum'              =>
				$is_privileged ?
				array( '', 'date', 'id', 'menu_order', 'modified', 'name', 'price', 'price-desc', 'popularity', 'rand', 'rating', 'relevance', 'sku', 'slug', 'title' ) :
				array( '', 'date', 'id', 'menu_order', 'modified', 'name', 'price', 'price-desc', 'popularity', 'rating', 'relevance', 'sku', 'slug', 'title' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['order'] = array(
			'default'           => 'asc',
			'description'       => __( 'Sort order.', 'woocommerce-product-search' ),
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['q'] = array(
			'required'          => false,
			'type'              => 'string',
			'description'       => __( 'Search query string', 'woocommerce-product-search' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['search'] = array(
			'required'          => false,
			'type'              => 'string',
			'description'       => __( 'Search query string (alias for q)', 'woocommerce-product-search' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['title'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Search in titles', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['excerpt'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Search in excerpts', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['content'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Search in content', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['sku'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Search in SKUs', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['categories'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Search in related categories', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['tags'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Search in related tags', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['attributes'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Search in related attributes', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['variations'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Search in variations', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['page'] = array(
			'required'          => false,
			'default'           => self::PAGE,
			'type'              => 'integer',
			'description'       => __( 'Current results page.', 'woocommerce-product-search' ),
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1
		);
		$params['per_page'] = array(
			'required'          => false,
			'default'           => self::PER_PAGE,
			'type'              => 'integer',
			'description'       => __( 'Results per page.', 'woocommerce-product-search' ),
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => $is_privileged ? null : self::UNPRIVILEGED_MINIMUM,
			'maximum'           => $is_privileged ? null : self::UNPRIVILEGED_MAXIMUM
		);
		$params['offset'] = array(
			'required'          => false,
			'type'              => 'integer',
			'description'       => __( 'Offset results by as many.', 'woocommerce-product-search' ),
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 0
		);
		$params['limit'] = array(
			'required'          => false,
			'type'              => 'integer',
			'description'       => __( 'Limit to a maximum number of results.', 'woocommerce-product-search' ),
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => $is_privileged ? null : self::UNPRIVILEGED_MINIMUM,
			'maximum'           => $is_privileged ? null : self::UNPRIVILEGED_MAXIMUM
		);

		$params['min_price'] = array(
			'required'          => false,
			'type'              => 'string',
			'description'       => __( 'Limit results based on a minimum price.', 'woocommerce-product-search' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['max_price'] = array(
			'required'          => false,
			'type'              => 'string',
			'description'       => __( 'Limit results based on a maximum price.', 'woocommerce-product-search' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['sale'] = array(
			'required'          => false,
			'type'              => 'boolean',
			'description'       => __( 'Limit results based on whether they are on sale or not on sale.', 'woocommerce-product-search' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['stock'] = array(
			'required'          => false,
			'type'              => array( 'string', 'array' ),
			'enum'              => array( 'instock', 'outofstock', 'onbackorder' ),
			'description'       => __( 'Limit results based on whether they are in stock, out of stock or available on backorder.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'sanitize_string_array' ),
			'validate_callback' => array( $this, 'enum_string_array' )
		);
		$params['featured'] = array(
			'required'          => false,
			'type'              => 'boolean',
			'description'       => __( 'Limit results based on whether they are featured.', 'woocommerce-product-search' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);
		$params['visibility'] = array(
			'required'          => false,
			'type'              => 'string',
			'enum'              => array( 'visible', 'catalog', 'search', 'hidden', 'exclude-from-search', 'exclude-from-catalog' ),
			'description'       => __( 'Limit results based on their visibility.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'sanitize_string_array' ),
			'validate_callback' => array( $this, 'enum_string_array' )
		);

		$params['t'] = array(
			'required'          => false,
			'description'       => __( 'Limit results to those related to terms.', 'woocommerce-product-search' )
		);

		$params['include_variations'] = array(
			'required'          => false,
			'default'           => false,
			'type'              => 'boolean',
			'description'       => __( 'Include variations', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params = apply_filters( 'woocommerce_product_search_rest_products_controller_collection_params', $params, $this );

		return $params;
	}

	/**
	 * Provides the Controller's Products schema.
	 *
	 * @see \WC_REST_Products_Controller
	 *
	 * {@inheritDoc}
	 *
	 * @see \WP_REST_Controller::get_item_schema()
	 */
	public function get_item_schema() {

		$weight_unit_label    = I18nUtil::get_weight_unit_label( get_option( 'woocommerce_weight_unit', 'kg' ) );
		$dimension_unit_label = I18nUtil::get_dimensions_unit_label( get_option( 'woocommerce_dimension_unit', 'cm' ) );
		$schema               = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product',
			'type'       => 'object',
			'properties' => array(
				'id'                    => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'                  => array(
					'description' => __( 'Product name.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'slug'                  => array(
					'description' => __( 'Product slug.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'permalink'             => array(
					'description' => __( 'Product URL.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_created'          => array(
					'description' => __( "The date the product was created, in the site's timezone.", 'woocommerce-product-search' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_created_gmt'      => array(
					'description' => __( 'The date the product was created, as GMT.', 'woocommerce-product-search' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_modified'         => array(
					'description' => __( "The date the product was last modified, in the site's timezone.", 'woocommerce-product-search' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_modified_gmt'     => array(
					'description' => __( 'The date the product was last modified, as GMT.', 'woocommerce-product-search' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'type'                  => array(
					'description' => __( 'Product type.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'default'     => 'simple',
					'enum'        => array_keys( wc_get_product_types() ),
					'context'     => array( 'view' ),
				),
				'status'                => array(
					'description' => __( 'Product status (post status).', 'woocommerce-product-search' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future', 'auto-draft', 'trash' ) ),
					'context'     => array( 'view' ),
				),
				'featured'              => array(
					'description' => __( 'Featured product.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'catalog_visibility'    => array(
					'description' => __( 'Catalog visibility.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'default'     => 'visible',
					'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
					'context'     => array( 'view' ),
				),
				'description'           => array(
					'description' => __( 'Product description.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'short_description'     => array(
					'description' => __( 'Product short description.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'sku'                   => array(
					'description' => __( 'Unique identifier.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'price'                 => array(
					'description' => __( 'Current product price.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'regular_price'         => array(
					'description' => __( 'Product regular price.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'sale_price'            => array(
					'description' => __( 'Product sale price.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'date_on_sale_from'     => array(
					'description' => __( "Start date of sale price, in the site's timezone.", 'woocommerce-product-search' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_on_sale_from_gmt' => array(
					'description' => __( 'Start date of sale price, as GMT.', 'woocommerce-product-search' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_on_sale_to'       => array(
					'description' => __( "End date of sale price, in the site's timezone.", 'woocommerce-product-search' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_on_sale_to_gmt'   => array(
					'description' => __( "End date of sale price, in the site's timezone.", 'woocommerce-product-search' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'price_html'            => array(
					'description' => __( 'Price formatted in HTML.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'on_sale'               => array(
					'description' => __( 'Shows if the product is on sale.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'purchasable'           => array(
					'description' => __( 'Shows if the product can be bought.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_sales'           => array(
					'description' => __( 'Amount of sales.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'virtual'               => array(
					'description' => __( 'If the product is virtual.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'downloadable'          => array(
					'description' => __( 'If the product is downloadable.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'downloads'             => array(
					'description' => __( 'List of downloadable files.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'File ID.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'name' => array(
								'description' => __( 'File name.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'file' => array(
								'description' => __( 'File URL.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
						),
					),
				),
				'download_limit'        => array(
					'description' => __( 'Number of times downloadable files can be downloaded after purchase.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'default'     => -1,
					'context'     => array( 'view' ),
				),
				'download_expiry'       => array(
					'description' => __( 'Number of days until access to downloadable files expires.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'default'     => -1,
					'context'     => array( 'view' ),
				),
				'external_url'          => array(
					'description' => __( 'Product external URL. Only for external products.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
				),
				'button_text'           => array(
					'description' => __( 'Product external button text. Only for external products.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'tax_status'            => array(
					'description' => __( 'Tax status.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'default'     => 'taxable',
					'enum'        => array( 'taxable', 'shipping', 'none' ),
					'context'     => array( 'view' ),
				),
				'tax_class'             => array(
					'description' => __( 'Tax class.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'manage_stock'          => array(
					'description' => __( 'Stock management at product level.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'stock_quantity'        => array(
					'description' => __( 'Stock quantity.', 'woocommerce-product-search' ),
					'type'        => has_filter( 'woocommerce_stock_amount', 'intval' ) ? 'integer' : 'number',
					'context'     => array( 'view' ),
				),
				'stock_status'          => array(
					'description' => __( 'Controls the stock status of the product.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'default'     => 'instock',
					'enum'        => array_keys( wc_get_product_stock_status_options() ),
					'context'     => array( 'view' ),
				),
				'backorders'            => array(
					'description' => __( 'If managing stock, this controls if backorders are allowed.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'default'     => 'no',
					'enum'        => array( 'no', 'notify', 'yes' ),
					'context'     => array( 'view' ),
				),
				'backorders_allowed'    => array(
					'description' => __( 'Shows if backorders are allowed.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'backordered'           => array(
					'description' => __( 'Shows if the product is on backordered.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'low_stock_amount'      => array(
					'description' => __( 'Low Stock amount for the product.', 'woocommerce-product-search' ),
					'type'        => array( 'integer', 'null' ),
					'context'     => array( 'view' ),
				),
				'sold_individually'     => array(
					'description' => __( 'Allow one item to be bought in a single order.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'weight'                => array(
					/* translators: %s: weight unit */
					'description' => sprintf( __( 'Product weight (%s).', 'woocommerce-product-search' ), $weight_unit_label ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'dimensions'            => array(
					'description' => __( 'Product dimensions.', 'woocommerce-product-search' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'properties'  => array(
						'length' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product length (%s).', 'woocommerce-product-search' ), $dimension_unit_label ),
							'type'        => 'string',
							'context'     => array( 'view' ),
						),
						'width'  => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product width (%s).', 'woocommerce-product-search' ), $dimension_unit_label ),
							'type'        => 'string',
							'context'     => array( 'view' ),
						),
						'height' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product height (%s).', 'woocommerce-product-search' ), $dimension_unit_label ),
							'type'        => 'string',
							'context'     => array( 'view' ),
						),
					),
				),
				'shipping_required'     => array(
					'description' => __( 'Shows if the product need to be shipped.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'shipping_taxable'      => array(
					'description' => __( 'Shows whether or not the product shipping is taxable.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'shipping_class'        => array(
					'description' => __( 'Shipping class slug.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'shipping_class_id'     => array(
					'description' => __( 'Shipping class ID.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'reviews_allowed'       => array(
					'description' => __( 'Allow reviews.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view' ),
				),

				'average_rating'        => array(
					'description' => __( 'Reviews average rating.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'rating_count'          => array(
					'description' => __( 'Amount of reviews that the product have.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'related_ids'           => array(
					'description' => __( 'List of related products IDs.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'upsell_ids'            => array(
					'description' => __( 'List of up-sell products IDs.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view' ),
				),
				'cross_sell_ids'        => array(
					'description' => __( 'List of cross-sell products IDs.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view' ),
				),
				'parent_id'             => array(
					'description' => __( 'Product parent ID.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'purchase_note'         => array(
					'description' => __( 'Optional note to send the customer after purchase.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'categories'            => array(
					'description' => __( 'List of categories.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Category ID.', 'woocommerce-product-search' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name' => array(
								'description' => __( 'Category name.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Category slug.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
				'tags'                  => array(
					'description' => __( 'List of tags.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Tag ID.', 'woocommerce-product-search' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name' => array(
								'description' => __( 'Tag name.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Tag slug.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
				'images'                => array(
					'description' => __( 'List of images.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                => array(
								'description' => __( 'Image ID.', 'woocommerce-product-search' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'date_created'      => array(
								'description' => __( "The date the image was created, in the site's timezone.", 'woocommerce-product-search' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'date_created_gmt'  => array(
								'description' => __( 'The date the image was created, as GMT.', 'woocommerce-product-search' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'date_modified'     => array(
								'description' => __( "The date the image was last modified, in the site's timezone.", 'woocommerce-product-search' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'date_modified_gmt' => array(
								'description' => __( 'The date the image was last modified, as GMT.', 'woocommerce-product-search' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'src'               => array(
								'description' => __( 'Image URL.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view' ),
							),
							'name'              => array(
								'description' => __( 'Image name.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'alt'               => array(
								'description' => __( 'Image alternative text.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
						),
					),
				),
				'has_options'           => array(
					'description' => __( 'Shows if the product needs to be configured before it can be bought.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'attributes'            => array(
					'description' => __( 'List of attributes.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'        => array(
								'description' => __( 'Attribute ID.', 'woocommerce-product-search' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name'      => array(
								'description' => __( 'Attribute name.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'position'  => array(
								'description' => __( 'Attribute position.', 'woocommerce-product-search' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'visible'   => array(
								'description' => __( "Define if the attribute is visible on the \"Additional information\" tab in the product's page.", 'woocommerce-product-search' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view' ),
							),
							'variation' => array(
								'description' => __( 'Define if the attribute can be used as variation.', 'woocommerce-product-search' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view' ),
							),
							'options'   => array(
								'description' => __( 'List of available term names of the attribute.', 'woocommerce-product-search' ),
								'type'        => 'array',
								'items'       => array(
									'type' => 'string',
								),
								'context'     => array( 'view' ),
							),
						),
					),
				),
				'default_attributes'    => array(
					'description' => __( 'Defaults variation attributes.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array(
								'description' => __( 'Attribute ID.', 'woocommerce-product-search' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name'   => array(
								'description' => __( 'Attribute name.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'option' => array(
								'description' => __( 'Selected attribute term name.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
						),
					),
				),
				'variations'            => array(
					'description' => __( 'List of variations IDs.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type' => 'integer',
					),
					'readonly'    => true,
				),
				'grouped_products'      => array(
					'description' => __( 'List of grouped products ID.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'menu_order'            => array(
					'description' => __( 'Menu order, used to custom sort products.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'meta_data'             => array(
					'description' => __( 'Meta data.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', 'woocommerce-product-search' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'woocommerce-product-search' ),
								'type'        => 'mixed',
								'context'     => array( 'view' ),
							),
						),
					),
				),
			),
		);

		$use_brands =
			get_option( 'wc_feature_woocommerce_brands_enabled' ) === 'yes' ||
			defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '9.6.0' ) >= 0;
		if ( $use_brands ) {
			$schema['properties']['brands'] = array(
				'description' => __( 'List of brands.', 'woocommerce-product-search' ),
				'type'        => 'array',
				'context'     => array( 'view' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'   => array(
							'description' => __( 'Brand ID.', 'woocommerce-product-search' ),
							'type'        => 'integer',
							'context'     => array( 'view' ),
						),
						'name' => array(
							'description' => __( 'Brand name.', 'woocommerce-product-search' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'slug' => array(
							'description' => __( 'Brand slug.', 'woocommerce-product-search' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
			);
		}

		$schema = $this->add_additional_fields_schema( $schema );

		if ( !$this->is_privileged() ) {
			$properties = array(
				'id',
				'name',
				'slug',
				'permalink',
				'type',
				'featured',
				'description',
				'short_description',
				'sku',
				'price',
				'price_html',
				'on_sale',
				'purchasable',
				'virtual',
				'downloadable',
				'external_url',
				'button_text',
				'backorders_allowed',
				'backordered',
				'sold_individually',
				'weight',
				'dimensions',
				'shipping_required',
				'shipping_class',
				'shipping_class_id',
				'reviews_allowed',
				'average_rating',
				'rating_count',
				'related_ids',
				'upsell_ids',
				'cross_sell_ids',
				'parent_id',
				'categories',
				'tags',
				'images',
				'has_options',
				'attributes',
				'default_attributes',
				'variations',
				'grouped_products',
				'menu_order',
				'stock_status',
				'stock_quantity'
			);

			if ( $use_brands ) {
				$properties[] = 'brands';
			}

			$properties = apply_filters( 'woocommerce_product_search_rest_products_controller_item_schema_properties_unprivileged', $properties, $schema, $this );
			if ( is_array( $properties ) ) {
				foreach ( array_keys( $schema['properties'] ) as $key ) {
					if ( !in_array( $key, $properties ) ) {
						unset( $schema['properties'][$key] );
					}
				}
			}
		} else {
			$properties = apply_filters( 'woocommerce_product_search_rest_products_controller_item_schema_properties_privileged', $schema['properties'], $schema, $this );
			if ( is_array( $properties ) ) {
				$schema['properties'] = $properties;
			}
		}

		$schema = apply_filters( 'woocommerce_product_search_rest_products_controller_item_schema', $schema );

		return $schema;
	}

	/**
	 * Add the schema from additional fields to the schema.
	 *
	 * {@inheritDoc}
	 *
	 * @see \WP_REST_Controller::add_additional_fields_schema()
	 */
	protected function add_additional_fields_schema( $schema ) {
		if ( empty( $schema['title'] ) ) {
			return $schema;
		}
		$schema = parent::add_additional_fields_schema( $schema );
		$schema['properties'] = apply_filters( 'woocommerce_rest_product_schema', $schema['properties'] );
		$schema['properties'] = apply_filters( 'woocommerce_product_search_rest_product_schema_properties', $schema['properties'], $this );
		return $schema;
	}

	/**
	 * Provide a collection of products.
	 *
	 * {@inheritDoc}
	 *
	 * @see \WP_REST_Controller::get_items()
	 *
	 * @param \WP_REST_Request $request request object
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {

		$request = apply_filters( 'woocommerce_product_search_rest_products_controller_get_items_request', $request, $this );

		$cache_context = $request->get_params();
		$cache_key = self::get_cache_key( $cache_context );
		$cache = Cache::get_instance();
		$stored = $cache->get( $cache_key, self::CACHE_GROUP );
		if ( $stored !== null ) {
			$this->total = $stored['total'];
			return $stored['response'];
		}

		$status = $request->get_param( 'status' );
		if ( $this->is_privileged() ) {
			if ( $status === null ) {
				$request->set_param( 'status', 'publish' );
			}
		} else {
			$request->set_param( 'status', 'publish' );
		}

		$per_page = $request->get_param( 'per_page' );
		$limit    = $request->get_param( 'limit' );
		if ( !$this->is_privileged() ) {
			if ( $per_page !== null ) {
				if ( $per_page < self::UNPRIVILEGED_MINIMUM ) {
					$request->set_param( 'per_page', self::UNPRIVILEGED_MINIMUM );
				}
				if ( $per_page > self::UNPRIVILEGED_MAXIMUM ) {
					$request->set_param( 'per_page', self::UNPRIVILEGED_MAXIMUM);
				}
			}
			if ( $limit !== null ) {
				if ( $limit < self::UNPRIVILEGED_MINIMUM ) {
					$request->set_param( 'limit', self::UNPRIVILEGED_MINIMUM );
				}
				if ( $limit > self::UNPRIVILEGED_MAXIMUM ) {
					$request->set_param( 'limit', self::UNPRIVILEGED_MAXIMUM );
				}
			}
		}

		$ids = $this->get_product_ids( $request );

		$request->set_param( 'status', $status );
		$request->set_param( 'per_page', $per_page );
		$request->set_param( 'limit', $limit );

		$results = array();
		foreach ( $ids as $id ) {
			$data = $this->prepare_item_for_response( $id, $request );
			$results[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $results );
		$response->header( 'X-WP-Total', $this->total );
		if ( $per_page !== null && $per_page > 0 ) {
			$max_pages = (int) ceil( $this->total / $per_page );
			$response->header( 'X-WP-TotalPages', $max_pages );
		}

		$store = array(
			'response' => $response,
			'total' => $this->total
		);

		$cache->set( $cache_key, $store, self::CACHE_GROUP, self::CACHE_LIFETIME );

		return $response;
	}

	/**
	 * Provide matching product IDs for the search and filter context of the request.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return int[]
	 */
	public function get_product_ids( $request ) {

		$engine = new \com\itthinx\woocommerce\search\engine\Engine();

		$q = $request->get_param( 'q' );
		if ( empty( $q ) ) {
			$q = $request->get_param( 'search' );
		}
		if ( !empty( $q ) ) {
			$args = array( 'q' => $q );

			$title = $request->get_param( 'title' );
			if ( $title !== null ) {
				$args['title'] = $title;
			}
			$excerpt = $request->get_param( 'excerpt' );
			if ( $excerpt !== null ) {
				$args['excerpt'] = $excerpt;
			}
			$content = $request->get_param( 'content' );
			if ( $content !== null ) {
				$args['content'] = $content;
			}
			$tags = $request->get_param( 'tags' );
			if ( $tags !== null ) {
				$args['tags'] = $tags;
			}
			$sku = $request->get_param( 'sku' );
			if ( $sku !== null ) {
				$args['sku'] = $sku;
			}
			$categories = $request->get_param( 'categories' );
			if ( $categories !== null ) {
				$args['categories'] = $categories;
			}
			$attributes = $request->get_param( 'attributes' );
			if ( $attributes !== null ) {
				$args['attributes'] = $attributes;
			}
			$variations = $request->get_param( 'variations' );
			if ( $variations !== null ) {
				$args['variations'] = $variations;
			}

			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Words( $args );
			$engine->attach_stage( $stage );
		}

		$taxonomies = $request->get_param( 't' );
		if ( !empty( $taxonomies ) ) {
			if ( is_string( $taxonomies ) ) {
				try {
					$taxonomies = json_decode( $taxonomies, true );
				} catch ( \Error $error ) {
				}
			}
			if ( is_array( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy => $params ) {

					$taxonomy = \WooCommerce_Product_Search_Utility::sanitize_taxonomy( trim( $taxonomy ) );

					if ( !taxonomy_exists( $taxonomy ) ) {

						if ( isset( $params['taxonomy'] ) && is_string( $params['taxonomy'] ) ) {
							$taxonomy = $params['taxonomy'];

							$taxonomy = \WooCommerce_Product_Search_Utility::sanitize_taxonomy( trim( $taxonomy ) );
						}
					}

					if ( is_string( $taxonomy ) && strlen( $taxonomy ) > 0 ) {
						if ( is_array( $params ) ) {
							$terms = null;
							$op = null;
							$id_by = null;
							$limit = null;

							if ( isset( $params['t'] ) ) {
								$terms = array();
								if ( is_string( $params['t'] ) ) {
									$ts = explode( ',', $params['t'] );
								} else if ( is_array( $params['t'] ) ) {
									$ts = $params['t'];
								} else {
									$ts = array();
								}
								foreach ( $ts as $term ) {
									$term = trim( sanitize_text_field( $term ) );
									$terms[] = $term;
								}
								$terms = array_unique( $terms );
							}

							if ( isset( $params['op'] ) ) {
								$op = strtolower( trim( sanitize_text_field( $params['op'] ) ) );
								switch ( $op ) {
									case 'and':
									case 'or':
									case 'not':
										break;
									default:
										$op = null;
								}
							}

							if ( isset( $params['id_by'] ) ) {
								$id_by = strtolower( trim( sanitize_text_field( $params['id_by'] ) ) );
								switch ( $id_by ) {
									case 'slug':
									case 'id':
										break;
									default:
										$id_by = null;
								}
							}

							if ( isset( $params['limit'] ) ) {
								if ( is_numeric( $params['limit'] ) ) {
									$limit = intval( $params['limit'] );
									if ( $limit <= 0 ) {
										$limit = null;
									}
								}
							}
							if ( $terms !== null && count( $terms ) > 0 ) {
								$terms_args = array(
									'terms' => $terms,
									'taxonomy' => $taxonomy,
									'variations' => true
								);
								if ( $op !== null ) {
									$terms_args['op'] = $op;
								}
								if ( $id_by !== null ) {
									$terms_args['id_by'] = $id_by;
								}
								if ( $limit !== null ) {
									$terms_args['limit'] = $limit;
								}
								$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Terms( $terms_args );
								$engine->attach_stage( $stage );
							}
						}
					}
				}
			}
		}

		$min_price = $request->get_param( 'min_price' );
		$max_price = $request->get_param( 'max_price' );
		if ( $min_price !== null || $max_price !== null ) {
			if ( $min_price !== null && $min_price <= 0 ) {
				$min_price = null;
			}
			if ( $max_price !== null && $max_price <= 0 ) {
				$max_price = null;
			}
			if ( $min_price !== null && $max_price !== null && $max_price < $min_price ) {
				$max_price = null;
			}
			\WooCommerce_Product_Search_Service::min_max_price_adjust( $min_price, $max_price );
			$args = array( 'variations' => true );
			if ( $min_price !== null ) {
				$args['min_price'] = trim( sanitize_text_field( $min_price ) );
			}
			if ( $max_price !== null ) {
				$args['max_price'] = trim( sanitize_text_field( $max_price ) );
			}
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Price( $args );
			$engine->attach_stage( $stage );
		}

		$rating_delta = $request->get_param( 'rating_delta' );
		$rating       = $request->get_param( 'rating' );
		$min_rating   = $request->get_param( 'min_rating' );
		$max_rating   = $request->get_param( 'max_rating' );
		if ( !empty( $rating ) || !empty( $min_rating ) || !empty( $max_rating ) ) {
			$args = array( 'variations' => true );
			if ( !empty( $rating_delta ) ) {
				$args['delta'] = trim( sanitize_text_field( $rating_delta ) );
			}
			if ( !empty( $rating ) ) {
				$args['rating'] = trim( sanitize_text_field( $rating ) );
			}
			if ( !empty( $min_rating ) ) {
				$args['min_rating'] = trim( sanitize_text_field( $min_rating ) );
			}
			if ( !empty( $max_rating ) ) {
				$args['max_rating'] = trim( sanitize_text_field( $max_rating ) );
			}
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Rating( $args );
			$engine->attach_stage( $stage );
		}

		$sale = $request->get_param( 'sale' );
		if ( $sale !== null ) {
			$args = array( 'sale' => $sale, 'variations' => true );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Sale( $args );
			$engine->attach_stage( $stage );
		}

		$stock = $request->get_param( 'stock' );
		if ( $stock !== null ) {
			$args = array( 'stock' => $stock, 'variations' => true );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Stock( $args );
			$engine->attach_stage( $stage );
		}

		$featured = $request->get_param( 'featured' );
		if ( $featured !== null ) {
			$args = array( 'featured' => $featured, 'variations' => true );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Featured( $args );
			$engine->attach_stage( $stage );
		}

		$visibility = $request->get_param( 'visibility' );

		if ( $visibility === null ) {
			$visibility = 'visible';;
		}
		if ( $visibility !== null ) {
			$args = array( 'visibility' => $visibility, 'variations' => true );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Visibility( $args );
			$engine->attach_stage( $stage );
		}

		if ( $engine->get_stage_count() > 0 ) {
			$args = array( 'variations' => true );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Synchrotron( $args );
			$engine->attach_stage( $stage );
		}

		$order              = $request->get_param( 'order' );
		$orderby            = $request->get_param( 'orderby' );
		$status             = $request->get_param( 'status' );
		$include_variations = $request->get_param( 'include_variations' );

		$args = array();
		if ( $order !== null ) {
			$args['order'] = $order;
		}
		if ( $orderby !== null && $orderby !== '' ) {
			$args['orderby'] = $orderby;
		}

		if ( $status !== null ) {
			$args['status'] = $status;
		}

		if ( $include_variations !== null ) {
			$args['variations'] = $include_variations;
		}
		$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Posts( $args );
		$engine->attach_stage( $stage );

		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$offset   = $request->get_param( 'offset' );
		$limit    = $request->get_param( 'limit' );

		$page = apply_filters( 'woocommerce_product_search_rest_products_controller_page', $page, $request );
		if ( $page < 0 ) {
			$page = null;
		}

		$per_page = apply_filters( 'woocommerce_product_search_rest_products_controller_per_page', $per_page, $request );
		if ( $per_page <= 0 ) {
			$per_page = null;
		}

		$offset = apply_filters( 'woocommerce_product_search_rest_products_controller_offset', $offset, $request );
		if ( $offset < 0 ) {
			$offset = null;
		}

		$limit = apply_filters( 'woocommerce_product_search_rest_products_controller_limit', $limit, $request );
		if ( $limit <= 0 ) {
			$limit = null;
		}

		if ( $limit !== null && $offset === null ) {
			$offset = 0;
		}

		if (
			$page !== null ||
			$per_page !== null ||
			$offset !== null ||
			$limit !== null
		) {
			$args = array();
			if ( $page !== null ) {
				$args['page'] = $page;
			}
			if ( $per_page !== null ) {
				$args['per_page'] = $per_page;
			}
			if ( $limit !== null ) {
				$args['limit'] = $limit;
			}
			if ( $offset !== null ) {
				$args['offset'] = $offset;
			}
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Pagination( $args );
			$engine->attach_stage( $stage );
		}

		$ids = $engine->get_ids();

		$this->total = $engine->get_total();

		return $ids;
	}

	/**
	 * Prepare the item for the REST response.
	 *
	 * {@inheritDoc}
	 *
	 * @see \WP_REST_Controller::prepare_item_for_response()
	 *
	 * @param mixed $item
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function prepare_item_for_response( $item, $request ) {

		$product = wc_get_product( $item );

		$data = $this->get_product_data( $product, $request );

		$context = !empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );

		$data = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $product, $request ) );

		$response = apply_filters( 'woocommerce_rest_prepare_product', $response, $item, $request );

		$response = apply_filters( 'woocommerce_product_search_rest_prepare_product', $response, $item, $request );

		return $response;
	}

	/**
	 * Filter product data according to privileged or unprivileged access.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function filter_product_data( $data ) {
		$properties = array_keys( $data );
		if ( $this->is_privileged() ) {
			$properties = apply_filters( 'woocommerce_product_search_rest_products_controller_filter_product_data_privileged', $properties, $data, $this );
		} else {
			$properties = array(
				'id',
				'name',
				'slug',
				'permalink',
				'type',
				'featured',
				'description',
				'short_description',
				'sku',
				'price',
				'regular_price',
				'sale_price',
				'price_html',
				'on_sale',
				'purchasable',
				'virtual',
				'downloadable',
				'external_url',
				'button_text',
				'backorders_allowed',
				'backordered',
				'sold_individually',
				'weight',
				'dimensions',
				'shipping_required',
				'shipping_class',
				'shipping_class_id',
				'reviews_allowed',
				'average_rating',
				'rating_count',
				'related_ids',
				'upsell_ids',
				'cross_sell_ids',
				'parent_id',
				'categories',
				'tags',
				'images',
				'has_options',
				'attributes',
				'default_attributes',
				'variations',
				'grouped_products',
				'menu_order',
				'stock_status',
				'stock_quantity',
				'brands'
			);
			$properties = apply_filters( 'woocommerce_product_search_rest_products_controller_filter_product_data_unprivileged', $properties, $data, $this );
		}

		foreach ( array_keys( $data ) as $key ) {
			if ( !is_array( $properties ) || !in_array( $key, $properties ) ) {
				unset( $data[$key] );
			}
		}
		return $data;
	}

	/**
	 * Provide the product data based on the product object and the fields for the request.
	 *
	 * @see \WC_REST_Products_V2_Controller::get_product_data()
	 * @see \WC_REST_Products_Controller::get_product_data()
	 *
	 * @param \WC_Product $product the product object
	 * @param \WP_REST_Request $request the request object
	 * @param string $context the given context
	 *
	 * @return array the product data
	 */
	protected function get_product_data( $product, $request, $context = 'view' ) {

		$fields  = $this->get_fields_for_response( $request );

		$base_data = array();
		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$base_data['id'] = $product->get_id();
					break;
				case 'name':
					$base_data['name'] = $product->get_name( $context );
					break;
				case 'slug':
					$base_data['slug'] = $product->get_slug( $context );
					break;
				case 'permalink':
					$base_data['permalink'] = $product->get_permalink();
					break;
				case 'date_created':
					$base_data['date_created'] = wc_rest_prepare_date_response( $product->get_date_created( $context ), false );
					break;
				case 'date_created_gmt':
					$base_data['date_created_gmt'] = wc_rest_prepare_date_response( $product->get_date_created( $context ) );
					break;
				case 'date_modified':
					$base_data['date_modified'] = wc_rest_prepare_date_response( $product->get_date_modified( $context ), false );
					break;
				case 'date_modified_gmt':
					$base_data['date_modified_gmt'] = wc_rest_prepare_date_response( $product->get_date_modified( $context ) );
					break;
				case 'type':
					$base_data['type'] = $product->get_type();
					break;
				case 'status':
					$base_data['status'] = $product->get_status( $context );
					break;
				case 'featured':
					$base_data['featured'] = $product->is_featured();
					break;
				case 'catalog_visibility':
					$base_data['catalog_visibility'] = $product->get_catalog_visibility( $context );
					break;
				case 'description':
					$base_data['description'] = 'view' === $context ? wpautop( do_shortcode( $product->get_description() ) ) : $product->get_description( $context );
					break;
				case 'short_description':
					$base_data['short_description'] = 'view' === $context ? apply_filters( 'woocommerce_short_description', $product->get_short_description() ) : $product->get_short_description( $context );
					break;
				case 'sku':
					$base_data['sku'] = $product->get_sku( $context );
					break;
				case 'price':
					$base_data['price'] = $product->get_price( $context );
					break;
				case 'regular_price':
					$base_data['regular_price'] = $product->get_regular_price( $context );
					break;
				case 'sale_price':
					$base_data['sale_price'] = $product->get_sale_price( $context ) ? $product->get_sale_price( $context ) : '';
					break;
				case 'date_on_sale_from':
					$base_data['date_on_sale_from'] = wc_rest_prepare_date_response( $product->get_date_on_sale_from( $context ), false );
					break;
				case 'date_on_sale_from_gmt':
					$base_data['date_on_sale_from_gmt'] = wc_rest_prepare_date_response( $product->get_date_on_sale_from( $context ) );
					break;
				case 'date_on_sale_to':
					$base_data['date_on_sale_to'] = wc_rest_prepare_date_response( $product->get_date_on_sale_to( $context ), false );
					break;
				case 'date_on_sale_to_gmt':
					$base_data['date_on_sale_to_gmt'] = wc_rest_prepare_date_response( $product->get_date_on_sale_to( $context ) );
					break;
				case 'price_html':
					$base_data['price_html'] = $product->get_price_html();
					break;
				case 'on_sale':
					$base_data['on_sale'] = $product->is_on_sale( $context );
					break;
				case 'purchasable':
					$base_data['purchasable'] = $product->is_purchasable();
					break;
				case 'total_sales':
					$base_data['total_sales'] = $product->get_total_sales( $context );
					break;
				case 'virtual':
					$base_data['virtual'] = $product->is_virtual();
					break;
				case 'downloadable':
					$base_data['downloadable'] = $product->is_downloadable();
					break;
				case 'downloads':
					$base_data['downloads'] = $this->get_downloads( $product, $context );
					break;
				case 'download_limit':
					$base_data['download_limit'] = $product->get_download_limit( $context );
					break;
				case 'download_expiry':
					$base_data['download_expiry'] = $product->get_download_expiry( $context );
					break;
				case 'external_url':
					$base_data['external_url'] = $product->is_type( 'external' ) && method_exists( $product, 'get_product_url' ) ? $product->get_product_url( $context ) : '';
					break;
				case 'button_text':
					$base_data['button_text'] = $product->is_type( 'external' ) && method_exists( $product, 'get_button_text' ) ? $product->get_button_text( $context ) : '';
					break;
				case 'tax_status':
					$base_data['tax_status'] = $product->get_tax_status( $context );
					break;
				case 'tax_class':
					$base_data['tax_class'] = $product->get_tax_class( $context );
					break;
				case 'manage_stock':
					$base_data['manage_stock'] = $product->managing_stock();
					break;
				case 'stock_quantity':
					$base_data['stock_quantity'] = $product->get_stock_quantity( $context );
					break;
				case 'in_stock':
					$base_data['in_stock'] = $product->is_in_stock();
					break;
				case 'backorders':
					$base_data['backorders'] = $product->get_backorders( $context );
					break;
				case 'backorders_allowed':
					$base_data['backorders_allowed'] = $product->backorders_allowed();
					break;
				case 'backordered':
					$base_data['backordered'] = $product->is_on_backorder();
					break;
				case 'low_stock_amount':
					$base_data['low_stock_amount'] = '' === $product->get_low_stock_amount() ? null : $product->get_low_stock_amount();
					break;
				case 'sold_individually':
					$base_data['sold_individually'] = $product->is_sold_individually();
					break;
				case 'weight':
					$base_data['weight'] = $product->get_weight( $context );
					break;
				case 'dimensions':
					$base_data['dimensions'] = array(
						'length' => $product->get_length( $context ),
						'width'  => $product->get_width( $context ),
						'height' => $product->get_height( $context ),
					);
					break;
				case 'shipping_required':
					$base_data['shipping_required'] = $product->needs_shipping();
					break;
				case 'shipping_taxable':
					$base_data['shipping_taxable'] = $product->is_shipping_taxable();
					break;
				case 'shipping_class':
					$base_data['shipping_class'] = $product->get_shipping_class();
					break;
				case 'shipping_class_id':
					$base_data['shipping_class_id'] = $product->get_shipping_class_id( $context );
					break;
				case 'reviews_allowed':
					$base_data['reviews_allowed'] = $product->get_reviews_allowed( $context );
					break;
				case 'average_rating':
					$base_data['average_rating'] = 'view' === $context ? wc_format_decimal( $product->get_average_rating(), 2 ) : $product->get_average_rating( $context );
					break;
				case 'rating_count':
					$base_data['rating_count'] = $product->get_rating_count();
					break;
				case 'upsell_ids':
					$base_data['upsell_ids'] = array_map( 'absint', $product->get_upsell_ids( $context ) );
					break;
				case 'cross_sell_ids':
					$base_data['cross_sell_ids'] = array_map( 'absint', $product->get_cross_sell_ids( $context ) );
					break;
				case 'parent_id':
					$base_data['parent_id'] = $product->get_parent_id( $context );
					break;
				case 'purchase_note':
					$base_data['purchase_note'] = 'view' === $context ? wpautop( do_shortcode( wp_kses_post( $product->get_purchase_note() ) ) ) : $product->get_purchase_note( $context );
					break;
				case 'categories':
					$base_data['categories'] = $this->get_taxonomy_terms( $product );
					break;
				case 'tags':
					$base_data['tags'] = $this->get_taxonomy_terms( $product, 'tag' );
					break;
				case 'images':
					$base_data['images'] = $this->get_images( $product );
					break;
				case 'attributes':
					$base_data['attributes'] = $this->get_attributes( $product );
					break;
				case 'default_attributes':
					$base_data['default_attributes'] = $this->get_default_attributes( $product );
					break;
				case 'variations':
					$base_data['variations'] = array();
					if ( $product->is_type( 'variable' ) && $product->has_child() ) {
						$base_data['variations'] = $this->get_variation_data( $product );
					}
					break;
				case 'grouped_products':
					$base_data['grouped_products'] = array();
					if ( $product->is_type( 'grouped' ) && $product->has_child() ) {
						$base_data['grouped_products'] = $product->get_children();
					}
					break;
				case 'menu_order':
					$base_data['menu_order'] = $product->get_menu_order( $context );
					break;
				case 'brands':
					$base_data['brands'] = $this->get_taxonomy_terms( $product, 'brand' );
					break;
			}
		}

		$data = array_merge(
			$base_data,
			$this->fetch_fields_using_getters( $product, $context, $fields )
		);

		if ( isset( $this->request ) ) {
			$fields = $this->get_fields_for_response( $this->request );

			if ( in_array( 'stock_status', $fields, true ) ) {
				$data['stock_status'] = $product->get_stock_status( $context );
			}

			if ( in_array( 'has_options', $fields, true ) ) {
				$data['has_options'] = $product->has_options();
			}

		}

		$data = $this->filter_product_data( $data );

		return $data;
	}

	/**
	 * Get fields for an object if getter is defined.
	 *
	 * @param object $object  Object we are fetching response for.
	 * @param string $context Context of the request. Can be `view` or `edit`.
	 * @param array  $fields  List of fields to fetch.
	 *
	 * @return array Data fetched from getters.
	 */
	public function fetch_fields_using_getters( $object, $context, $fields ) {
		$data = array();
		foreach ( $fields as $field ) {
			if ( method_exists( $this, "api_get_$field" ) ) {
				$data[ $field ] = $this->{"api_get_$field"}( $object, $context );
			}
		}
		return $data;
	}

	/**
	 * Get attribute options.
	 *
	 * @param int $product_id product ID
	 * @param array $attribute  attribute data
	 *
	 * @return array
	 */
	protected function get_attribute_options( $product_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms(
				$product_id,
				$attribute['name'],
				array(
					'fields' => 'names',
				)
				);
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}
		return array();
	}

	/**
	 * Get product attribute taxonomy name.
	 *
	 * @param string $slug taxonomy name
	 * @param \WC_Product $product product object
	 *
	 * @return string
	 */
	protected function get_attribute_taxonomy_name( $slug, $product ) {

		$slug       = wc_attribute_taxonomy_slug( $slug );
		$attributes = array_combine(
			array_map( 'wc_sanitize_taxonomy_name', array_keys( $product->get_attributes() ) ),
			array_values( $product->get_attributes() )
		);

		$attribute = false;

		if ( isset( $attributes[ wc_attribute_taxonomy_name( $slug ) ] ) ) {
			$attribute = $attributes[ wc_attribute_taxonomy_name( $slug ) ];
		} elseif ( isset( $attributes[ $slug ] ) ) {
			$attribute = $attributes[ $slug ];
		}

		if ( ! $attribute ) {
			return $slug;
		}

		if ( $attribute->is_taxonomy() ) {
			$taxonomy = $attribute->get_taxonomy_object();
			return $taxonomy->attribute_label;
		}

		return $attribute->get_name();
	}

	/**
	 * Get the attributes for a product or product variation.
	 *
	 * @param \WC_Product|\WC_Product_Variation $product product object
	 *
	 * @return array
	 */
	protected function get_attributes( $product ) {
		$attributes = array();

		if ( $product->is_type( 'variation' ) && method_exists( $product, 'get_variation_attributes' ) ) {
			$_product = wc_get_product( $product->get_parent_id() );
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
				$name = str_replace( 'attribute_', '', $attribute_name );

				if ( empty( $attribute ) && '0' !== $attribute ) {
					continue;
				}

				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$option_term  = get_term_by( 'slug', $attribute, $name );
					$attributes[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
						'slug'   => $name,
						'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
						'slug'   => $name,
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $product->get_attributes() as $attribute ) {
				$attributes[] = array(
					'id'        => $attribute['is_taxonomy'] ? wc_attribute_taxonomy_id_by_name( $attribute['name'] ) : 0,
					'name'      => $this->get_attribute_taxonomy_name( $attribute['name'], $product ),
					'slug'      => $attribute['name'],
					'position'  => (int) $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
				);
			}
		}

		return $attributes;
	}

	/**
	 * Get default attributes.
	 *
	 * @param \WC_Product $product product object
	 *
	 * @return array
	 */
	protected function get_default_attributes( $product ) {
		$default = array();

		if ( $product->is_type( 'variable' ) ) {
			foreach ( array_filter( (array) $product->get_default_attributes(), 'strlen' ) as $key => $value ) {
				if ( 0 === strpos( $key, 'pa_' ) ) {
					$default[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $key ),
						'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
						'option' => $value,
					);
				} else {
					$default[] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
						'option' => $value,
					);
				}
			}
		}

		return $default;
	}

	/**
	 * Get an individual variation's data.
	 *
	 * @see \WC_REST_Products_V1_Controller::get_variation_data()
	 *
	 * @param \WC_Product $product Product instance.
	 *
	 * @return array
	 */
	protected function get_variation_data( $product ) {
		$variations = array();
		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation || ! $variation->exists() ) {
				continue;
			}
			$data = array(
				'id'                 => $variation->get_id(),
				'date_created'       => wc_rest_prepare_date_response( $variation->get_date_created() ),
				'date_modified'      => wc_rest_prepare_date_response( $variation->get_date_modified() ),
				'permalink'          => $variation->get_permalink(),
				'sku'                => $variation->get_sku(),
				'price'              => $variation->get_price(),
				'regular_price'      => $variation->get_regular_price(),
				'sale_price'         => $variation->get_sale_price(),
				'date_on_sale_from'  => $variation->get_date_on_sale_from() ? date( 'Y-m-d', $variation->get_date_on_sale_from()->getTimestamp() ) : '',
				'date_on_sale_to'    => $variation->get_date_on_sale_to() ? date( 'Y-m-d', $variation->get_date_on_sale_to()->getTimestamp() ) : '',
				'on_sale'            => $variation->is_on_sale(),
				'purchasable'        => $variation->is_purchasable(),
				'visible'            => $variation->is_visible(),
				'virtual'            => $variation->is_virtual(),
				'downloadable'       => $variation->is_downloadable(),
				'downloads'          => $this->get_downloads( $variation ),
				'download_limit'     => '' !== $variation->get_download_limit() ? (int) $variation->get_download_limit() : -1,
				'download_expiry'    => '' !== $variation->get_download_expiry() ? (int) $variation->get_download_expiry() : -1,
				'tax_status'         => $variation->get_tax_status(),
				'tax_class'          => $variation->get_tax_class(),
				'manage_stock'       => $variation->managing_stock(),
				'stock_quantity'     => $variation->get_stock_quantity(),
				'stock_status'       => $variation->get_stock_status(),
				'in_stock'           => $variation->is_in_stock(),
				'backorders'         => $variation->get_backorders(),
				'backorders_allowed' => $variation->backorders_allowed(),
				'backordered'        => $variation->is_on_backorder(),
				'weight'             => $variation->get_weight(),
				'dimensions'         => array(
					'length' => $variation->get_length(),
					'width'  => $variation->get_width(),
					'height' => $variation->get_height(),
				),
				'shipping_class'     => $variation->get_shipping_class(),
				'shipping_class_id'  => $variation->get_shipping_class_id(),
				'image'              => $this->get_images( $variation ),
				'attributes'         => $this->get_attributes( $variation ),
			);

			$data = $this->filter_product_data( $data );

			$variations[] = $data;
		}
		return $variations;
	}

	/**
	 * Provides the downloads related to the product.
	 *
	 * @param \WC_Product $product product object
	 *
	 * @return array
	 */
	protected function get_downloads( $product, $context = 'view' ) {
		$downloads = array();
		if ( $product->is_downloadable() || 'edit' === $context ) {
			foreach ( $product->get_downloads() as $file_id => $file ) {
				$downloads[] = array(
					'id'   => $file_id,
					'name' => $file['name'],
					'file' => $file['file'],
				);
			}
		}
		return $downloads;
	}

	/**
	 * Get the images for a product or product variation.
	 *
	 * @param \WC_Product|\WC_Product_Variation $product product object
	 *
	 * @return array
	 */
	protected function get_images( $product ) {
		$images         = array();
		$attachment_ids = array();
		$featured_id = null;

		if ( $product->get_image_id() ) {
			$attachment_ids[] = $product->get_image_id();
			$featured_id = (int) $product->get_image_id();
		}

		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'featured'          => $featured_id === (int) $attachment_id
			);
		}

		return $images;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param \WC_Product $product product object
	 * @param \WP_REST_Request $request request object
	 *
	 * @return array links for the given object
	 */
	protected function prepare_links( $product, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $product->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);
		return $links;
	}

	/**
	 * Provides the product's related taxonomy terms.
	 *
	 * @param \WC_Product $product product object
	 * @param string $taxonomy taxonomy slug
	 *
	 * @return array
	 */
	protected function get_taxonomy_terms( $product, $taxonomy = 'cat' ) {
		$terms = array();
		foreach ( wc_get_object_terms( $product->get_id(), 'product_' . $taxonomy ) as $term ) {
			$terms[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}
		return $terms;
	}

	/**
	 * Provide the total number of results pre-pagination.
	 *
	 * @return int
	 */
	public function get_total() {
		return $this->total;
	}
}
