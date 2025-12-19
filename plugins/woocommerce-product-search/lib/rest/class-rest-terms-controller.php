<?php
/**
 * class-rest-terms-controller.php
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
 * REST Terms Controller
 */
class REST_Terms_Controller extends REST_Controller {

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'rest-terms-controller';

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
	protected $rest_base = 'terms';

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
		$is_privileged = apply_filters( 'woocommerce_product_search_rest_terms_controller_is_privileged', $is_privileged );
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

		$result = apply_filters( 'woocommerce_product_search_rest_terms_controller_get_permission', true, $request );
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

		unset( $params['offset']['default'] );
		unset( $params['limit']['default'] );
		unset( $params['page']['default'] );
		unset( $params['per_page']['default'] );

		$params['taxonomy'] = array(
			'description'       => __( 'The product taxonomy (or taxonomies separated by comma) whose terms to retrieve for the current context.', 'woocommerce-product-search' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['except'] = array(
			'description'       => __( 'Except the product taxonomy (or taxonomies separated by comma) from restricting the current context.', 'woocommerce-product-search' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['orderby'] = array(
			'default'           => 'order',
			'description'       => __( 'Sort terms by indicated option.', 'woocommerce-product-search' ),
			'type'              => 'string',
			'enum'              => array( '', 'id', 'term_id', 'menu_order', 'order', 'name', 'slug', 'count' ),
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

		$params['hide_empty'] = array(
			'default'           => true,
			'required'          => false,
			'type'              => 'boolean',
			'description'       => __( 'Omit terms which have no products that match the current context.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['sum_counts'] = array(
			'default'           => true,
			'required'          => false,
			'type'              => 'boolean',
			'description'       => __( 'Provide sum of child counts for parent terms.', 'woocommerce-product-search' ),
			'sanitize_callback' => array( $this, 'to_boolean' ),
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['include'] = array(
			'required'         => false,
			'type'             => array( 'string', 'array' ),
			'description'      => __( 'Restrict the result set to particular terms given by their ids.', 'woocommerce-product-search' ),
			'items'            => array(
				'type'         => 'integer'
			),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params['exclude'] = array(
			'required'         => false,
			'type'             => array( 'string', 'array' ),
			'description'      => __( 'Exclude particular terms given by their ids from the result set.', 'woocommerce-product-search' ),
			'items'            => array(
				'type'         => 'integer'
			),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg'
		);

		$params = apply_filters( 'woocommerce_product_search_rest_terms_controller_collection_params', $params, $this );

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

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'term',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'parent' => array(
					'description' => __( 'Parent identifier.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true
				),
				'taxonomy' => array(
					'taxonomy'   => __( 'Term taxonomy.', 'woocommerce-product-search' ),
					'type'       => 'string',
					'context'    => array( 'view' ),
					'readonly'   => true
				),
				'name' => array(
					'description' => __( 'Term name.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'slug' => array(
					'description' => __( 'Term slug.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'permalink' => array(
					'description' => __( 'Term archive URL.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Term description.', 'woocommerce-product-search' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'count' => array(
					'description' => __( 'Related product count.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'images' => array(
					'description' => __( 'List of images.', 'woocommerce-product-search' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Image ID.', 'woocommerce-product-search' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'date_created' => array(
								'description' => __( "The date the image was created, in the site's timezone.", 'woocommerce-product-search' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'date_created_gmt' => array(
								'description' => __( 'The date the image was created, as GMT.', 'woocommerce-product-search' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'date_modified' => array(
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
							'src' => array(
								'description' => __( 'Image URL.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view' ),
							),
							'name' => array(
								'description' => __( 'Image name.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'alt' => array(
								'description' => __( 'Image alternative text.', 'woocommerce-product-search' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
						),
					),
				),
				'menu_order' => array(
					'description' => __( 'Menu order, used to custom sort terms.', 'woocommerce-product-search' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),

				'match' => array(
					'description' => __( 'Whether the term matches the current search and filter context.', 'woocommerce-product-search' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				)
			),
		);

		$schema = $this->add_additional_fields_schema( $schema );

		if ( !$this->is_privileged() ) {
			$properties = array(
				'id',
				'name',
				'parent',
				'slug',
				'permalink',
				'type',
				'featured',
				'description',
				'count',
				'images',
				'menu_order',
				'taxonomy',
				'match'
			);

			$properties = apply_filters( 'woocommerce_product_search_rest_terms_controller_item_schema_properties_unprivileged', $properties, $schema, $this );
			if ( is_array( $properties ) ) {
				foreach ( array_keys( $schema['properties'] ) as $key ) {
					if ( !in_array( $key, $properties ) ) {
						unset( $schema['properties'][$key] );
					}
				}
			}
		} else {
			$properties = apply_filters( 'woocommerce_product_search_rest_terms_controller_item_schema_properties_privileged', $schema['properties'], $schema, $this );
			if ( is_array( $properties ) ) {
				$schema['properties'] = $properties;
			}
		}

		$schema = apply_filters( 'woocommerce_product_search_rest_terms_controller_item_schema', $schema );

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

		$schema['properties'] = apply_filters( 'woocommerce_product_search_rest_terms_schema_properties', $schema['properties'], $this );
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

		global $wpdb;

		$request = clone $request;

		$request = apply_filters( 'woocommerce_product_search_rest_terms_controller_get_items_request', $request, $this );

		$cache_context = $request->get_params();
		if ( $this->is_privileged() ) {
			if ( $cache_context['status'] === null ) {
				$cache_context['status'] = 'publish';
			}
		} else {
			$cache_context['status'] = 'publish';
		}
		$cache_key = self::get_cache_key( $cache_context );
		$cache = Cache::get_instance();
		$stored = $cache->get( $cache_key, self::CACHE_GROUP );
		if ( $stored !== null ) {
			$this->total = $stored['total'];
			return $stored['response'];
		}

		$all_product_taxonomies = \WooCommerce_Product_Search_Service::get_product_taxonomies();
		$all_product_taxonomies = apply_filters( 'woocommerce_product_search_rest_terms_controller_all_product_taxonomies', $all_product_taxonomies, $request, $this );
		if ( !is_array( $all_product_taxonomies ) ) {
			$all_product_taxonomies = array();
		}

		$taxonomies = $request->get_param( 'taxonomy' );
		if ( is_string( $taxonomies ) ) {
			$taxonomies = array_map( 'trim', explode( ',', $taxonomies ) );
		}
		if ( !is_array( $taxonomies ) ) {
			$taxonomies = array();
		}
		if ( is_array( $taxonomies ) && count( $taxonomies ) > 0 ) {
			$taxonomies = array_unique( $taxonomies );

			$product_taxonomies = array_intersect( $taxonomies, $all_product_taxonomies );
			$taxonomies = $product_taxonomies;
		}

		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$offset   = $request->get_param( 'offset' );
		$limit    = $request->get_param( 'limit' );
		$status   = $request->get_param( 'status' );
		$include_variations = $request->get_param( 'include_variations' );

		$include = $request->get_param( 'include' );
		$exclude = $request->get_param( 'exclude' );

		$orderby = $request->get_param( 'orderby' );

		$except = $request->get_param( 'except' );
		if ( !empty( $except ) ) {
			$t = $request->get_param( 't' );

			if ( is_string( $t ) ) {
				try {
					$t = json_decode( $t, true );
				} catch ( \Error $error ) {
				}
			}
			if ( is_array( $t ) ) {
				$_t = $t;
				$except = array_unique( array_map( 'trim', explode( ',', $except ) ) );
				foreach ( $t as $key => $parameters ) {

					if ( in_array( $key, $except ) ) {
						unset( $_t[$key] );
					}

					if ( is_array( $parameters ) && array_key_exists( 'taxonomy', $parameters ) ) {
						if ( in_array( $parameters['taxonomy'], $except ) ) {
							unset( $_t[$key] );
						}
					}
				}

				foreach ( array_keys( $t ) as $taxonomy ) {
					if ( in_array( $taxonomy, $except ) ) {
						unset( $_t[$taxonomy] );
					}
				}
				$request->set_param( 't', $_t );
			}
		}

		$request->set_param( 'page', -1 );
		$request->set_param( 'per_page', -1 );
		$request->set_param( 'offset', -1 );
		$request->set_param( 'limit', -1 );
		$request->set_param( 'include_variations', true );

		if ( !empty( $include ) ) {
			$request->set_param( 'include', null );
		}
		if ( !empty( $exclude ) ) {
			$request->set_param( 'exclude', null );
		}

		$request->set_param( 'orderby', '' );

		if ( $this->is_privileged() ) {
			if ( $status === null ) {
				$request->set_param( 'status', 'publish' );
			}
		} else {
			$request->set_param( 'status', 'publish' );
		}

		$products_controller = new REST_Products_Controller();
		$product_ids = $products_controller->get_product_ids( $request );

		$request->set_param( 'page', $page );
		$request->set_param( 'per_page', $per_page );
		$request->set_param( 'offset', $offset );
		$request->set_param( 'limit', $limit );
		$request->set_param( 'status', $status );
		$request->set_param( 'include_variations', $include_variations );
		if ( !empty( $except ) ) {
			$request->set_param( 't', $t ); // @phpstan-ignore variable.undefined ($t is defined when $exempt is not empty)
		}

		if ( !empty( $include ) ) {
			$request->set_param( 'include', $include );
		}
		if ( !empty( $exclude ) ) {
			$request->set_param( 'exclude', $exclude );
		}

		$request->set_param( 'orderby', $orderby );

		$context_nodes = array();

		$hide_empty = $request->get_param( 'hide_empty' );
		switch ( $hide_empty ) {
			case 'true':
				$hide_empty = true;
				break;
			case 'false':
				$hide_empty = false;
				break;
		}

		if ( count( $product_ids ) > 0 || !$hide_empty ) {

			$object_term_table = \WooCommerce_Product_Search_Controller::get_tablename( 'object_term' );

			$where = array( 'term_id != 0' );

			if ( !$hide_empty ) {
				array_unshift( $product_ids, 0 );
			}

			if ( count( $product_ids ) > 0 ) {
				Tools::int( $product_ids );
				$product_ids_condition = "object_id IN (" . implode( ',', $product_ids ) . ')';
				$where[] = $product_ids_condition;
			}

			$disjunctive_parts = array();
			foreach ( $taxonomies as $taxonomy ) {
				$disjunctive_parts[] = "taxonomy = '" . esc_sql( $taxonomy ) . "'";
			}
			if ( count( $disjunctive_parts ) > 0 ) {
				$where[] = ' ( ' . implode( " OR ", $disjunctive_parts ) . ' ) ';
			}

			$query = "SELECT object_id, parent_object_id, term_id, parent_term_id, object_type, taxonomy, inherit FROM $object_term_table ";
			if ( count( $where ) > 0 ) {
				$query .= "WHERE " . implode( ' AND ', $where );
			}
			$rows = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( is_array( $rows ) && count( $rows ) > 0 ) {
				$nodes = self::get_nodes( count( $taxonomies ) > 0 ? $taxonomies : $all_product_taxonomies );
				$object_term_counts = array();
				foreach ( $rows as $object_term ) {
					if ( !empty( $object_term->term_id ) ) {
						$term_id = $object_term->term_id;
						if ( !isset( $object_term_counts[$term_id] ) ) {
							$object_term_counts[$term_id] = array();
						}
						if ( !empty( $object_term->object_id ) ) {
							$object_id = null;
							switch ( $object_term->object_type ) {
								case 'variable':
								case 'variable-subscription':
									if ( $object_term->inherit ) {
										$object_id = $object_term->object_id;
									}
									break;
								default:
									$object_id = $object_term->object_id;
							}

							if ( !empty( $object_term->parent_object_id ) ) {
								$object_id = $object_term->parent_object_id;
							}

							if ( $object_id !== null ) {
								if( !isset( $object_term_counts[$term_id][$object_id] ) ) {
									$object_term_counts[$term_id][$object_id] = 0;
								}
								$object_term_counts[$term_id][$object_id]++;
							}
						}
					}
				}

				foreach ( $object_term_counts as $term_id => $object_ids ) {
					$nodes[$term_id]['count'] = count( $object_ids );
				}

				$sum_counts = $request->get_param( 'sum_counts' );
				switch ( $sum_counts ) {
					case 'true':
						$sum_counts = true;
						break;
					case 'false':
						$sum_counts = false;
						break;
				}
				$tree = array( 'term_id' => null, 'parent' => null, 'count' => 0, 'children' => array() );
				self::nodes_to_tree( $nodes, $tree, $sum_counts );
				self::tree_sort( $tree, $request->get_param( 'orderby' ), $request->get_param( 'order' ) );
				$context_nodes = self::flatten_tree( $tree );
			}
		}

		if ( is_array( $include ) && count( $include ) > 0 ) {
			$purged_context_nodes = array();
			while( count( $context_nodes ) > 0 ) {
				$node = array_shift( $context_nodes );
				if ( in_array( $node['term_id'], $include ) ) {
					$purged_context_nodes[] = $node;
				}
			}
			$context_nodes = $purged_context_nodes;
		}

		if ( is_array( $exclude ) && count( $exclude ) > 0 ) {
			$purged_context_nodes = array();
			while( count( $context_nodes ) > 0 ) {
				$node = array_shift( $context_nodes );
				if ( !in_array( $node['term_id'], $exclude ) ) {
					$purged_context_nodes[] = $node;
				}
			}
			$context_nodes = $purged_context_nodes;
		}

		if ( $hide_empty ) {
			$purged_context_nodes = array();
			while( count( $context_nodes ) > 0 ) {
				$node = array_shift( $context_nodes );
				if ( $node['count'] > 0 ) {
					$purged_context_nodes[] = $node;
				}
			}
			$context_nodes = $purged_context_nodes;
		}

		if ( $offset <= 0 ) {
			$offset = null;
		}
		if ( $limit <= 0 ) {
			$limit = null;
		}
		if ( $offset !== null || $limit !== null ) {
			$context_nodes = array_slice( $context_nodes, $offset !== null ? $offset : 0, $limit );
		}

		$this->total = count( $context_nodes );

		if ( $per_page <= 0 ) {
			$per_page = null;
		}
		if ( $page <= 0 ) {
			$page = 1;
		}
		if ( $per_page !== null ) {
			$page_offset = ( $page - 1 ) * $per_page;
			$context_nodes = array_slice( $context_nodes, $page_offset, $per_page );
		}

		$results = array();
		foreach ( $context_nodes as $node ) {
			$data = $this->prepare_item_for_response( $node, $request );
			if ( $data !== null ) {
				$results[] = $this->prepare_response_for_collection( $data );
			}
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
	 * Create tree from nodes.
	 *
	 * @param array $nodes
	 * @param array $current_node
	 * @param boolean $sum_counts
	 */
	private static function nodes_to_tree( $nodes, &$current_node, $sum_counts = true ) {

		foreach ( $nodes as $term_id => $node ) {
			if ( $current_node['term_id'] === $node['parent'] ) {
				$child = array(
					'term_id'     => $term_id,
					'name'        => $node['name'],
					'slug'        => $node['slug'],
					'taxonomy'    => $node['taxonomy'],
					'description' => $node['description'],
					'parent'      => $node['parent'],
					'order'       => $node['order'],
					'count'       => $node['count'],
					'children'    => array()
				);
				unset( $nodes[$term_id] );
				self::nodes_to_tree( $nodes, $child, $sum_counts );
				$current_node['children'][] = $child;
				if ( $sum_counts ) {
					$current_node['count'] += $child['count'];
				}
			}
		}
	}

	/**
	 * Provide the flattened structure based on the tree.
	 *
	 * @param array $tree
	 *
	 * @return array
	 */
	private static function flatten_tree( $tree ) {
		$nodes = array();
		foreach ( $tree['children'] as $child ) {
			$nodes[] = $child;
			$child_nodes = self::flatten_tree( $child );
			foreach ( $child_nodes as $node ) {
				$nodes[] = $node;
			}
		}
		return $nodes;
	}

	/**
	 * Provide a node in the tree corresponding to a term ID or null if not found.
	 *
	 * @param array $tree
	 * @param int $term_id
	 *
	 * @return array|null
	 */
	private static function tree_get_node( $tree, $term_id ) {
		$node = null;
		if ( $tree['term_id'] === $term_id ) {
			$node = $tree;
		} else {
			foreach ( $tree['children'] as $child ) {
				$node = self::tree_get_node( $child, $term_id );
				if ( $node !== null ) {
					break;
				}
			}
		}
		return $node;
	}

	/**
	 * Sort the node tree.
	 *
	 * @param array $tree
	 * @param string $orderby
	 * @param string $order
	 */
	private static function tree_sort( &$tree, $orderby = 'order', $order = 'asc' ) {

		if ( is_array( $tree['children'] ) && count( $tree['children'] ) > 0 ) {
			usort(
				$tree['children'],
				function ( $n1, $n2 ) use ( $orderby , $order ) {
					switch ( $orderby ) {
						case 'id':
						case 'term_id':
							$c = $n1['term_id'] - $n2['term_id'];
							break;
						case 'name':
							$c = strcmp( $n1['name'], $n2['name'] );
							break;
						case 'slug':
							$c = strcmp( $n1['slug'], $n2['slug'] );
							break;
						case 'count':
							$c = $n1['count'] - $n2['count'];
							break;
						case 'menu_order':
						case 'order':
							$c = $n1['order'] - $n2['order'];
							break;
						default:
							$c = 0;
					}
					if ( $order === 'desc' ) {
						$c = -$c;
					}
					return $c;
				}
			);

			for ( $i = 0; $i < count( $tree['children'] ); $i++ ) {
				self::tree_sort( $tree['children'][$i], $orderby, $order );
			}
		}
	}

	/**
	 * Arrange the terms.
	 *
	 * @param array $terms term_id => parent_term_id
	 * @param array $sorted
	 * @param int $parent
	 */
	private function arrange_hierarchically( $terms, &$sorted, $parent = 0 ) {

		foreach ( $terms as $term_id => $parent_term_id ) {
			if ( $parent_term_id === $parent ) {
				array_push( $sorted, $term_id );
				unset( $terms[$term_id] );
				self::arrange_hierarchically( $terms, $sorted, $term_id );
			}
		}
	}

	/**
	 * Compute nodes.
	 *
	 * @param string[] $taxonomies
	 *
	 * @return array
	 */
	private static function get_nodes( $taxonomies ) {

		global $wpdb;

		$nodes = array();

		$query = "SELECT t.term_id AS term_id, tt.parent AS parent, t.name AS name, t.slug AS slug, tt.description AS description, tt.taxonomy AS taxonomy, tm.meta_value AS `order` ".
			"FROM $wpdb->terms t " .
			"LEFT JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id " .
			"LEFT JOIN $wpdb->termmeta tm ON t.term_id = tm.term_id AND tm.meta_key = 'order'";
		if ( is_array( $taxonomies ) && count( $taxonomies ) > 0 ) {
			$query .= " WHERE tt.taxonomy IN ( '" . implode( "', '", array_map( 'esc_sql', $taxonomies ) ) . "' )";
		}
		$terms = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( is_array( $terms ) && count( $terms ) > 0 ) {

			$got_terms = get_terms( array(
				'fields'     => 'ids',
				'hide_empty' => false,
				'include'    => array_column( $terms, 'term_id' )
			) );
			$terms = array_filter(
				$terms,
				function ( $item ) use ( $got_terms ) {
					return in_array( $item->term_id, $got_terms );
				}
			);

			foreach ( $terms as $term ) {
				$term_id = (int) $term->term_id;
				$parent = !empty( $term->parent ) ? (int) $term->parent : null;
				if ( !isset( $nodes[$term_id] ) ) {
					$nodes[$term_id] = array(
						'term_id'     => $term_id,
						'count'       => 0,
						'parent'      => $parent,
						'children'    => null,
						'name'        => $term->name,
						'slug'        => $term->slug,
						'description' => $term->description,
						'taxonomy'    => $term->taxonomy,
						'order'       => is_numeric( $term->order ) ? intval( $term->order ) : 0
					);
				} else {
					if ( $parent !== null && $nodes[$term_id]['parent'] === null ) {

						$nodes[$term_id]['parent'] = $parent;
					}
				}
				if ( $parent !== null && $parent > 0 ) {
					if ( !isset( $nodes[$parent] ) ) {

						$nodes[$parent] = array(
							'term_id'     => $term_id,
							'count'       => 0,
							'parent'      => null,
							'children'    => array( $term_id ),
							'name'        => $term->name,
							'slug'        => $term->slug,
							'description' => $term->description,
							'taxonomy'    => $term->taxonomy,
							'order'       => is_numeric( $term->order ) ? intval( $term->order ) : 0
						);
					} else {
						if ( $nodes[$parent]['children'] === null || !in_array( $term_id, $nodes[$parent]['children'] ) ) {
							$nodes[$parent]['children'][] = $term_id;
						}
					}
				}
			}
		}

		return $nodes;
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
		$response = null;

		$data = $this->get_term_data( $item, $request );

		$context = !empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );

		$data = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_term_links( $item, $request ) );

		$response = apply_filters( 'woocommerce_rest_prepare_term', $response, $item, $request );

		$response = apply_filters( 'woocommerce_product_search_rest_prepare_term', $response, $item, $request );

		return $response;
	}

	/**
	 * Filter term data according to privileged or unprivileged access.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function filter_term_data( $data ) {
		$properties = array_keys( $data );
		if ( $this->is_privileged() ) {
			$properties = apply_filters( 'woocommerce_product_search_rest_terms_controller_filter_term_data_privileged', $properties, $data, $this );
		} else {
			$properties = array(
				'id',
				'parent',
				'name',
				'slug',
				'permalink',
				'description',
				'count',
				'images',
				'order',
				'menu_order',
				'taxonomy',
				'match'
			);
			$properties = apply_filters( 'woocommerce_product_search_rest_terms_controller_filter_term_data_unprivileged', $properties, $data, $this );
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
	 * @param array $item the term item
	 * @param \WP_REST_Request $request the request object
	 * @param string $context the given context
	 *
	 * @return array the product data
	 */
	protected function get_term_data( $item, $request, $context = 'view' ) {

		$fields = $this->get_fields_for_response( $request );

		$data = array();
		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$data['id'] = $item['term_id'];
					break;
				case 'parent':
					$data['parent'] = $item['parent'];
					break;
				case 'name':
					$data['name'] = $item['name'];
					break;
				case 'slug':
					$data['slug'] = $item['slug'];
					break;
				case 'permalink':
					$data['permalink'] = get_term_link( $item['term_id'] );
					break;
				case 'description':
					$data['description'] = $item['description'];
					break;
				case 'count':
					$data['count'] = $item['count'];
					break;
				case 'images':
					$data['images'] = $this->get_images( $item['term_id'] );
					break;
				case 'order':
				case 'menu_order':
					$data['menu_order'] = $item['order'];
					break;
				case 'taxonomy':
					$data['taxonomy'] = $item['taxonomy'];
					break;
				case 'match':
					$data['match'] = $item['count'] > 0;
					break;
			}
		}

		$data = $this->filter_term_data( $data );

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
	 * Get the images for a term.
	 *
	 * @param \WP_Term|int $term term object
	 *
	 * @return array
	 */
	protected function get_images( $term ) {

		$images         = array();
		$attachment_ids = array();

		if ( is_numeric( $term ) ) {
			$term_id = intval( $term );
		} else if ( $term instanceof \WP_Term ) {
			$term_id = $term->term_id;
		}

		$attachment_ids = array();

		$thumbnail_id = get_term_meta( $term_id, 'thumbnail_id', true );
		if ( $thumbnail_id ) {
			$thumbnail_id = intval( $thumbnail_id );
			$attachment_ids[] = $thumbnail_id;
		}

		$product_search_image_id = intval( get_term_meta( $term_id, 'product_search_image_id', true ) );
		if ( $product_search_image_id ) {
			$attachment_ids[] = $product_search_image_id;
		}

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$attachment_id = intval( $attachment_id );
			$type = 'attachment';
			if ( $attachment_id === $thumbnail_id ) {
				$type = 'thumbnail';
			}
			if ( $attachment_id === $product_search_image_id ) {
				$type = 'product-search-image';
			}

			$images[] = array(
				'id'                => $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'type'              => $type
			);
		}

		return $images;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param array $item term item
	 * @param \WP_REST_Request $request request object
	 *
	 * @return array links for the given object
	 */
	protected function prepare_term_links( $item, $request ) {
		$base = '/' . $this->namespace . '/' . $this->rest_base;

		$links = array(
			'self' => array(
				'href' => rest_url(
					add_query_arg(
						array(
							'taxonomy' => $item['taxonomy'],

							'include' => $item['term_id'],
							'hide_empty' => 'false'
						),
						$base
					)
				),
			),
			'collection' => array(
				'href' => rest_url( add_query_arg( 'taxonomy', $item['taxonomy'], $base ) ),
			),
		);

		if ( !empty( $item['parent'] ) ) {
			$links['up'] = array(
				'href' => rest_url(
					add_query_arg(
						array(
							'taxonomy' => $item['taxonomy'],

							'include' => $item['parent'],
							'hide_empty' => 'false'
						),
					$base
					)
				),
			);
		}

		return $links;
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
