<?php
/**
 * class-rest-shop-controller.php
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
 * REST Shop Controller
 */
class REST_Shop_Controller extends REST_Controller {

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'rest-shop-controller';

	/**
	 * Cache lifetime.
	 *
	 * @var int
	 */
	const CACHE_LIFETIME = Cache::HOUR;

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
	protected $rest_base = 'shop';

	/**
	 * @var integer
	 */
	protected $total = 0;

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

		$result = apply_filters( 'woocommerce_product_search_rest_shop_controller_get_permission', true, $request );
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
	 * @return array
	 */
	public function get_collection_params() {

		$products_controller = new REST_Products_Controller();
		$params = $products_controller->get_collection_params();

		$params['context'] = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['product-data'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Include corresponding products.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['taxonomy-data'] = array(
			'required'          => false,
			'description'       => __( 'Include corresponding terms from product taxonomies.', 'woocommerce-product-search' )
		);

		$params['sale-data'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Include sale data for corresponding products.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['stock-data'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Include stock data for corresponding products.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['price-data'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Include price data for corresponding products.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['rating-data'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Include ratings data for corresponding products.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['featured-data'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Include featured data for corresponding products.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['route-data'] = array(
			'required'          => false,
			'default'           => true,
			'type'              => 'boolean',
			'description'       => __( 'Include route data for the request.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params = apply_filters( 'woocommerce_product_search_rest_shop_controller_collection_params', $params, $this );

		return $params;
	}

	/**
	 * Provides the Controller's Terms schema.
	 *
	 * @see \WC_REST_Terms_Controller
	 *
	 * {@inheritDoc}
	 *
	 * @see \WP_REST_Controller::get_item_schema()
	 */
	public function get_item_schema() {

		$products_controller = new REST_Products_Controller();
		$product_properties = $products_controller->get_item_schema()['properties'];

		$terms_controller = new REST_Terms_Controller();
		$term_properties = $terms_controller->get_item_schema()['properties'];

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'term',
			'type'       => 'object',
			'properties' => array(
				'products' => array(
					'type' => 'object',
					'properties' => array(
						'products' => array(
							'type' => 'array',
							'items' => array(
								'type' => 'object',
								'properties' => $product_properties
							)
						),
						'total' => array(
							'type' => 'integer'
						)
					)
				),
				'terms' => array(
					'type' => 'array',
					'items' => array(
						'type' => 'object',
						'properties' => array(
							'taxonomy' => array(
								'taxonomy'   => __( 'Term taxonomy.', 'woocommerce-product-search' ),
								'type'       => 'string',
								'context'    => array( 'view' ),
								'readonly'   => true
							),
							'terms' => array(
								'type' => 'array',
								'items' => array(
									'type' => 'object',
									'properties' => $term_properties
								)
							),
							'total' => array(
								'type' => 'integer'
							)
						)
					)
				),
				'sale' => array(
					'type' => 'object',
					'properties' => array(
						'onsale' => array(
							'type' => 'integer'
						),
						'notonsale' => array(
							'type' => 'integer'
						)
					)
				),
				'stock' => array(
					'type' => 'object',
					'properties' => array(
						'instock' => array(
							'type' => 'integer'
						),
						'outofstock' => array(
							'type' => 'integer'
						),
						'onbackorder' => array(
							'type' => 'integer'
						)
					)
				),
				'price' => array(
					'type' => 'object',
					'properties' => array(
						'min_price' => array(
							'type' => 'number'
						),
						'max_price' => array(
							'type' => 'number'
						),
						'total' => array(
							'type' => 'integer'
						)
					)
				),
				'rating' => array(
					'type' => 'object',
					'properties' => array(
						'min_average_rating' => array(
							'type' => 'number'
						),
						'max_average_rating' => array(
							'type' => 'number'
						),
						'ratings' => array(
							'type' => 'array',
							'items' => array(
								'type' => 'object',
								'properties' => array(
									'rounded_average_rating' => array(
										'type' => 'number'
									),
									'count' => array(
										'type' => 'integer'
									)
								)
							)
						),
						'total' => array(
							'type' => 'integer'
						)
					)
				),
				'featured' => array(
					'type' => 'object',
					'properties' => array(
						'featured' => array(
							'type' => 'integer'
						),
						'notfeatured' => array(
							'type' => 'integer'
						)
					)
				),
				'route' => array(
					'type' => 'object',
					'properties' => array(
						'route' => array(
							'type' => 'string'
						),
						'params' => array(
							'type' => 'array'
						),
						'has_filters' => array(
							'type' => 'boolean'
						)
					)
				)
			),
		);

		$schema = $this->add_additional_fields_schema( $schema );

		$schema = apply_filters( 'woocommerce_product_search_rest_shop_controller_item_schema', $schema );

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
		$schema['properties'] = apply_filters( 'woocommerce_product_search_rest_shop_schema_properties', $schema['properties'], $this );
		return $schema;
	}

	/**
	 * Provide a collection of terms corresponding to the current search and filter context.
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

		$request = apply_filters( 'woocommerce_product_search_rest_shop_controller_get_items_request', $request, $this );

		$cache_context = $request->get_params();
		$cache_key = self::get_cache_key( $cache_context );
		$cache = Cache::get_instance();
		$stored = $cache->get( $cache_key, self::CACHE_GROUP );
		if ( $stored !== null ) {
			$this->total = $stored['total'];
			return $stored['response'];
		}

		$data = array();

		$product_data = $request->get_param( 'product-data' );
		$products_controller = new REST_Products_Controller();
		$products = $products_controller->get_items( $request );
		$this->total = $products_controller->get_total();
		if ( !empty( $product_data ) ) {
			$data['products']= array(
				'products' => $products->get_data(),
				'total' => $this->total
			);
		}

		$taxonomy_data = $request->get_param( 'taxonomy-data' );

		if ( $taxonomy_data === null ) {
			$taxonomy_data = true;
		} else {
			if ( is_scalar( $taxonomy_data ) ) {
				if ( is_string( $taxonomy_data ) ) {
					switch ( $taxonomy_data ) {
						case '':
						case 'true':
							$taxonomy_data = true;
							break;
						case 'false':
							$taxonomy_data = false;
							break;
					}
				}
			}
		}

		if ( $taxonomy_data === true ) {
			$all_product_taxonomies = \WooCommerce_Product_Search_Service::get_product_taxonomies();
			$all_product_taxonomies = apply_filters( 'woocommerce_product_search_rest_shop_controller_all_product_taxonomies', $all_product_taxonomies, $request, $this );
			if ( !is_array( $all_product_taxonomies ) ) {
				$all_product_taxonomies = array();
			}
			$taxonomy_data = array();
			foreach ( $all_product_taxonomies as $taxonomy ) {
				$taxonomy_data[$taxonomy] = array();
			}
		}

		if ( $taxonomy_data !== false && !empty( $taxonomy_data ) ) {
			$data['terms'] = array();
			if ( !empty( $taxonomy_data ) ) { // @phpstan-ignore-line

				if ( is_string( $taxonomy_data ) ) {
					try {
						$taxonomy_data = json_decode( $taxonomy_data, true );

					} catch ( \Error $error ) {
						$taxonomy_data = null;
					}
				}

				/**
				 * @var \WP_REST_Request $base_request
				 */

				$base_request = clone $request;
				$base_request->set_param( 'limit', -1 );
				$base_request->set_param( 'offset', -1 );
				$base_request->set_param( 'per_page', -1 );
				$base_request->set_param( 'page', -1 );

				if ( is_array( $taxonomy_data ) ) {
					foreach ( $taxonomy_data as $taxonomy => $params ) {

						$taxonomy = \WooCommerce_Product_Search_Utility::sanitize_taxonomy( trim( $taxonomy ) );

						if ( !taxonomy_exists( $taxonomy ) ) {

							$taxonomy = '';
							if ( isset( $params['taxonomy'] ) && is_string( $params['taxonomy'] ) ) {
								$taxonomy = $params['taxonomy'];

								$taxonomy = \WooCommerce_Product_Search_Utility::sanitize_taxonomy( trim( $taxonomy ) );
							}
						}

						if ( is_string( $taxonomy ) && strlen( $taxonomy ) > 0 && taxonomy_exists( $taxonomy ) ) {
							$extra = null;
							if ( is_string( $params ) ) {
								$params = trim( $params );
								try {
									$extra = json_decode( $params, true );
								} catch ( \Error $error ) {
								}
							} else if ( is_array( $params ) ) {
								$extra = $params;
							} else if ( is_object( $params ) ) {
								try {
									$extra = json_decode( json_encode( $params ), true );
								} catch ( \Error $error ) {
								}
							}
							if ( is_array( $extra ) ) {
								$params = array_merge( $base_request->get_params(), $extra );
							} else {
								$params = $base_request->get_params();
							}

							$params['taxonomy'] = $taxonomy;
							$terms_request = new \WP_REST_Request();
							$terms_request->set_query_params( $params );
							$terms_controller = new REST_Terms_Controller();

							foreach ( $terms_controller->get_collection_params() as $arg => $options ) {
								if ( isset( $options['default'] ) ) {
									if ( !$terms_request->has_param( $arg ) ) {
										$terms_request->set_param( $arg, $options['default'] );
									}
								}
							}

							$terms = array();
							$terms['taxonomy'] = $taxonomy;
							$terms['terms'] = $terms_controller->get_items( $terms_request )->get_data();
							$terms['total'] = $terms_controller->get_total();
							$data['terms'][] = $terms;
						}
					}
				}
			}
		}

		$sale_data = $request->get_param( 'sale-data' );
		if ( $sale_data ) {
			$data['sale'] = $this->get_sale_stats( $request );
		}

		$stock_data = $request->get_param( 'stock-data' );
		if ( $stock_data ) {
			$data['stock'] = $this->get_stock_stats( $request );
		}

		$price_data = $request->get_param( 'price-data' );
		if ( $price_data ) {
			$data['price'] = $this->get_price_stats( $request );
		}

		$rating_data = $request->get_param( 'rating-data' );
		if ( $rating_data ) {
			$data['rating'] = $this->get_rating_stats( $request );
		}

		$featured_data = $request->get_param( 'featured-data' );
		if ( $featured_data ) {
			$data['featured'] = $this->get_featured_stats( $request );
		}

		$route_data = $request->get_param( 'route-data' );
		if ( $route_data ) {
			$data['route'] = $this->get_route_stats( $request );
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', $this->total );
		$per_page = $request->get_param( 'per_page' );
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
	 * Provide stats related to products on sale.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function get_sale_stats( $request ) {

		global $wpdb;

		$request = clone $request;

		$request->set_param( 'page', -1 );
		$request->set_param( 'per_page', -1 );
		$request->set_param( 'offset', -1 );
		$request->set_param( 'limit', -1 );
		$request->set_param( 'include_variations', false );

		$cache_context = $request->get_params();
		$cache_key = 'sale_counts_' . self::get_cache_key( $cache_context );
		$cache = Cache::get_instance();
		$counts = $cache->get( $cache_key, self::CACHE_GROUP );
		if ( $counts === null ) {
			$counts = array(
				'onsale' => 0,
				'notonsale' => 0
			);
			$products_controller = new REST_Products_Controller();
			$product_ids = $products_controller->get_product_ids( $request );
			if ( count( $product_ids ) > 0 ) {
				if ( property_exists( $wpdb, 'wc_product_meta_lookup' ) ) {

					$m_total = new Matrix();
					$m_total->inc_stage();

					$m = new Matrix();
					$m->inc_stage();

					$query =
						"SELECT pml.product_id, p.post_parent AS parent_product_id, pml.onsale FROM $wpdb->wc_product_meta_lookup pml " .
						"LEFT JOIN $wpdb->posts p ON p.ID = pml.product_id ";
					$items = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( is_array( $items ) && count( $items ) > 0 ) {
						foreach ( $items as $item ) {

							if ( $item->onsale ) {
								if ( !empty( $item->parent_product_id ) ) {
									$m->inc( $item->parent_product_id );
								} else {
									$m->inc( $item->product_id );
								}
							}

							if ( !empty( $item->parent_product_id ) ) {
								$m_total->inc( $item->parent_product_id );
							} else {
								$m_total->inc( $item->product_id );
							}
						}
						$m->inc_stage();
						$m_total->inc_stage();
						foreach ( $product_ids as $product_id ) {
							$m->inc( $product_id );
							$m_total->inc( $product_id );
						}
					}
					$m->evaluate();
					$m_total->evaluate();
					$counts['onsale'] = count( $m->get_ids() );
					$counts['notonsale'] = count( $m_total->get_ids() ) - $counts['onsale'];
				}
			}
			$cache->set( $cache_key, $counts, self::CACHE_GROUP, self::CACHE_LIFETIME );
		}

		return $counts;
	}

	/**
	 * Provide product stock status stats.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function get_stock_stats( $request ) {

		global $wpdb;

		$request = clone $request;

		$request->set_param( 'page', -1 );
		$request->set_param( 'per_page', -1 );
		$request->set_param( 'offset', -1 );
		$request->set_param( 'limit', -1 );
		$request->set_param( 'include_variations', false );

		$cache_context = $request->get_params();
		$cache_key = 'stock_counts_' . self::get_cache_key( $cache_context );

		$cache = Cache::get_instance();
		$counts = $cache->get( $cache_key, self::CACHE_GROUP );
		if ( $counts === null ) {
			$counts = array(
				'instock' => 0,
				'outofstock' => 0,
				'onbackorder' => 0
			);
			$products_controller = new REST_Products_Controller();
			$product_ids = $products_controller->get_product_ids( $request );
			if ( count( $product_ids ) > 0 ) {
				if ( property_exists( $wpdb, 'wc_product_meta_lookup' ) ) {

					$query =
						"SELECT pml.product_id, p.post_parent AS parent_product_id, pml.stock_status FROM $wpdb->wc_product_meta_lookup pml " .
						"LEFT JOIN $wpdb->posts p ON p.ID = pml.product_id ";
					$stocks = array();
					$items = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( is_array( $items ) && count( $items ) > 0 ) {
						foreach ( $items as $item ) {
							if ( !isset( $stocks[$item->stock_status] ) ) {
								$m = new Matrix();
								$m->inc_stage();
								$stocks[$item->stock_status] = $m;
							} else {
								$m = $stocks[$item->stock_status];
							}
							if ( !empty( $item->parent_product_id ) ) {
								$m->inc( $item->parent_product_id );
							} else {
								$m->inc( $item->product_id );
							}
						}
					}
					foreach ( $stocks as $status => $stock ) {
						$stock->inc_stage();
						foreach ( $product_ids as $product_id ) {
							$stock->inc( $product_id );
						}
						$stock->evaluate();
						$counts[$status] = count( $stock->get_ids() );
					}
				}
			}
			$_counts = apply_filters( 'woocommerce_product_search_rest_shop_controller_stock_counts', $counts, $request, $this );
			foreach ( array_keys( $counts ) as $key ) {
				if ( isset( $_counts[$key] ) ) {
					$counts[$key] = max( 0, intval( $_counts[$key] ) );
				}
			}
			$cache->set( $cache_key, $counts, self::CACHE_GROUP, self::CACHE_LIFETIME );
		}
		return $counts;
	}

	/**
	 * Provide price stats.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function get_price_stats( $request ) {

		global $wpdb;

		$request = clone $request;

		$request->set_param( 'page', -1 );
		$request->set_param( 'per_page', -1 );
		$request->set_param( 'offset', -1 );
		$request->set_param( 'limit', -1 );
		$request->set_param( 'include_variations', false );

		$cache_context = $request->get_params();
		$cache_key = 'price_stats_' . self::get_cache_key( $cache_context );
		$cache = Cache::get_instance();
		$stats = $cache->get( $cache_key, self::CACHE_GROUP );
		if ( $stats === null ) {
			$stats = array(
				'min_price' => null,
				'max_price' => null,
				'total'     => 0
			);
			$products_controller = new REST_Products_Controller();
			$product_ids = $products_controller->get_product_ids( $request );
			if ( count( $product_ids ) > 0 ) {
				if ( property_exists( $wpdb, 'wc_product_meta_lookup' ) ) {
					$query =
						"SELECT pml.product_id, p.post_parent AS parent_product_id, pml.min_price, pml.max_price FROM $wpdb->wc_product_meta_lookup pml " .
						"LEFT JOIN $wpdb->posts p ON p.ID = pml.product_id ";
					$items = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( is_array( $items ) && count( $items ) > 0 ) {

						$prices = array();
						foreach ( $items as $item ) {
							if ( !empty( $item->parent_product_id ) ) {
								$product_id = $item->parent_product_id;
							} else {
								$product_id = $item->product_id;
							}
							$range = isset( $prices[$product_id] ) ? $prices[$product_id] : null;
							if ( $range === null ) {

								$prices[$product_id] = array(
									$item->min_price,
									$item->max_price,
									empty( $item->parent_product_id )
								);
							} else {

								if ( !empty( $item->parent_product_id ) ) {

									if ( $range[2] ) {
										$prices[$product_id] = array(
											$item->min_price,
											$item->max_price,
											false
										);
									} else {

										$prices[$product_id] = array(
											min( $item->min_price, $range[0] ),
											max( $item->max_price, $range[1] ),
											false
										);
									}
								} else {

									if ( $range[2] ) {
										$prices[$product_id] = array(
											min( $item->min_price, $range[0] ),
											max( $item->max_price, $range[1] ),
											false
										);
									}
								}
							}
						}

						foreach ( $product_ids as $product_id ) {
							if ( isset( $prices[$product_id] ) ) {
								$range = $prices[$product_id];
								$stats['min_price'] = $stats['min_price'] !== null ? min( $stats['min_price'], $range[0] ) : $range[0];
								$stats['max_price'] = $stats['max_price'] !== null ? max( $stats['max_price'], $range[1] ) : $range[1];
								$stats['total']++;
								unset( $prices[$product_id] );
							}
						}
					}
				}
			}
			$cache->set( $cache_key, $stats, self::CACHE_GROUP, self::CACHE_LIFETIME );
		}

		if (
			$stats['min_price'] !== null ||
			$stats['max_price'] !== null
		) {
			global $woocommerce_wpml;
			if (
				isset( $woocommerce_wpml ) &&
				class_exists( '\woocommerce_wpml' ) &&
				( $woocommerce_wpml instanceof \woocommerce_wpml )
			) {
				$multi_currency = $woocommerce_wpml->get_multi_currency();
				if (
					!empty( $multi_currency->prices ) &&
					class_exists( '\WCML_Multi_Currency_Prices' ) &&
					( $multi_currency->prices instanceof \WCML_Multi_Currency_Prices )
				) {
					if ( method_exists( $multi_currency, 'get_client_currency' ) ) {
						$currency = $multi_currency->get_client_currency();
						if ( function_exists( 'wcml_get_woocommerce_currency_option' ) ) {
							if ( $currency !== wcml_get_woocommerce_currency_option() ) {
								if ( method_exists( $multi_currency->prices, 'convert_price_amount' ) ) {
									if ( $stats['min_price'] !== null ) {
										$stats['min_price'] = $multi_currency->prices->convert_price_amount( $stats['min_price'], $currency );
									}
									if ( $stats['max_price'] !== null ) {
										$stats['max_price'] = $multi_currency->prices->convert_price_amount( $stats['max_price'], $currency );
									}
								}
							}
						}
					}
				}
			}
		}

		return $stats;
	}

	/**
	 * Provide rating stats.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function get_rating_stats( $request ) {

		global $wpdb;

		$request = clone $request;

		$request->set_param( 'page', -1 );
		$request->set_param( 'per_page', -1 );
		$request->set_param( 'offset', -1 );
		$request->set_param( 'limit', -1 );
		$request->set_param( 'include_variations', false );

		$cache_context = $request->get_params();
		$cache_key = 'rating_stats_' . self::get_cache_key( $cache_context );
		$cache = Cache::get_instance();
		$stats = $cache->get( $cache_key, self::CACHE_GROUP );
		if ( $stats === null ) {
			$stats = array(
				'min_average_rating' => null,
				'max_average_rating' => null,
				'ratings'            => array(),
				'total'              => 0
			);
			$products_controller = new REST_Products_Controller();
			$product_ids = $products_controller->get_product_ids( $request );
			if ( count( $product_ids ) > 0 ) {
				if ( property_exists( $wpdb, 'wc_product_meta_lookup' ) ) {
					$query =
						"SELECT pml.product_id, pml.average_rating, pml.rating_count FROM $wpdb->wc_product_meta_lookup pml " .
						"LEFT JOIN $wpdb->posts p ON p.ID = pml.product_id " .
						"WHERE p.post_parent = 0 AND pml.rating_count > 0";
					$items = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( is_array( $items ) && count( $items ) > 0 ) {

						$ratings = array();
						foreach ( $items as $item ) {
							$product_id = intval( $item->product_id );
							$rating = intval( round( $item->average_rating ) );
							$count  = intval( $item->rating_count );
							if ( !isset( $ratings[$product_id] ) ) {
								$ratings[$product_id] = array(
									floatval( $item->average_rating ),
									$rating,
									$count
								);
							}
						}

						foreach ( $product_ids as $product_id ) {
							if ( isset( $ratings[$product_id] ) ) {
								$rating = $ratings[$product_id];
								$stats['min_average_rating'] = $stats['min_average_rating'] !== null ? min( $stats['min_average_rating'], $rating[0] ) : $rating[0];
								$stats['max_average_rating'] = $stats['max_average_rating'] !== null ? max( $stats['max_average_rating'], $rating[0] ) : $rating[0];
								if ( !isset( $stats['ratings'][$rating[1]] ) ) {
									$stats['ratings'][$rating[1]] = 0;
								}
								$stats['ratings'][$rating[1]] += $rating[2];
								$stats['total']++;
								unset( $ratings[$product_id] );
							}
						}
						if ( count( $stats['ratings'] ) > 0 ) {
							$ratings = array();
							foreach ( $stats['ratings'] as $rating => $count ) {
								$ratings[] = array( 'rounded_average_rating' => $rating, 'count' => $count );
							}
							$stats['ratings'] = $ratings;
						}
					}
				}
			}
			$cache->set( $cache_key, $stats, self::CACHE_GROUP, self::CACHE_LIFETIME );
		}
		return $stats;
	}

	/**
	 * Provide stats related to featured products.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function get_featured_stats( $request ) {

		global $wpdb;

		$request = clone $request;

		$request->set_param( 'page', -1 );
		$request->set_param( 'per_page', -1 );
		$request->set_param( 'offset', -1 );
		$request->set_param( 'limit', -1 );
		$request->set_param( 'include_variations', false );

		$cache_context = $request->get_params();
		$cache_key = 'featured_counts_' . self::get_cache_key( $cache_context );
		$cache = Cache::get_instance();
		$counts = $cache->get( $cache_key, self::CACHE_GROUP );
		if ( $counts === null ) {
			$counts = array(
				'featured' => 0,
				'notfeatured' => 0
			);
			$products_controller = new REST_Products_Controller();
			$product_ids = $products_controller->get_product_ids( $request );
			if ( count( $product_ids ) > 0 ) {
				if ( property_exists( $wpdb, 'wc_product_meta_lookup' ) ) {

					$m = new Matrix();
					$m->inc_stage();

					$product_visibility_term_ids = wc_get_product_visibility_term_ids();
					if ( isset( $product_visibility_term_ids['featured'] ) ) {
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						$query = $wpdb->prepare(
							"SELECT ID, post_parent FROM $wpdb->posts " .
							"WHERE " .
							"post_type IN ('product', 'product_variation') " .
							"AND ( " .
							"ID IN ( SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d ) " .
							"OR " .
							"post_parent IN ( SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d ) " .
							")",
							intval( $product_visibility_term_ids['featured'] ),
							intval( $product_visibility_term_ids['featured'] )
						);

						$items = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						if ( is_array( $items ) && count( $items ) > 0 ) {
							foreach ( $items as $item ) {

								if ( !empty( $item->post_parent ) ) {
									$m->inc( $item->post_parent );
								} else {
									$m->inc( $item->ID );
								}
							}
							$m->inc_stage();
							foreach ( $product_ids as $product_id ) {
								$m->inc( $product_id );
							}
						}
					}
					$m->evaluate();
					$counts['featured'] = count( $m->get_ids() );
					$counts['notfeatured'] = count( $product_ids ) - $counts['featured'];
				}
			}
			$cache->set( $cache_key, $counts, self::CACHE_GROUP, self::CACHE_LIFETIME );
		}

		return $counts;
	}

	/**
	 * Provide route and parameter data and filter status.
	 *
	 * @param \WP_REST_Request $request request object
	 *
	 * @return array
	 */
	protected function get_route_stats( $request ) {

		$request = clone $request;

		$has_filters = false;
		$keys = array(
			'q',
			'search',
			'min_price',
			'max_price',
			'sale',
			'stock',
			'featured',
			't'
		);
		foreach ( $keys as $key ) {
			if ( $request->get_param( $key ) !== null ) {
				$has_filters = true;
				break;
			}
		}

		$stats = array(
			'route'       => $request->get_route(),
			'params'      => $request->get_params(),
			'has_filters' => $has_filters
		);

		return $stats;
	}

}
