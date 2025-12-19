<?php
/**
 * class-query-control.php
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
 * @since 5.0.0
 */

namespace com\itthinx\woocommerce\search\engine;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Control.
 */
class Query_Control {

	const LIMIT         = 'limit';
	const DEFAULT_LIMIT = 0;

	const TITLE         = 'wps-title';
	const EXCERPT       = 'wps-excerpt';
	const CONTENT       = 'wps-content';
	const CATEGORIES    = 'wps-categories';
	const TAGS          = 'wps-tags';
	const SKU           = 'wps-sku';
	const ATTRIBUTES    = 'wps-attributes';
	const VARIATIONS    = 'wps-variations';

	const MIN_PRICE     = 'min_price';
	const MAX_PRICE     = 'max_price';

	const ON_SALE       = 'on_sale';
	const RATING        = 'rating';
	const IN_STOCK      = 'in_stock';

	const DEFAULT_TITLE      = true;
	const DEFAULT_EXCERPT    = true;
	const DEFAULT_CONTENT    = true;
	const DEFAULT_TAGS       = true;
	const DEFAULT_CATEGORIES = true;
	const DEFAULT_SKU        = true;
	const DEFAULT_ATTRIBUTES = true;
	const DEFAULT_VARIATIONS = false;

	const DEFAULT_ON_SALE    = false;
	const DEFAULT_RATING     = null;
	const DEFAULT_IN_STOCK   = false;

	const ORDER    = 'order';
	const ORDER_BY = 'order_by';

	const OBJECT_TERM_LIMIT = 100;

	const PRE_GET_POSTS_ACTION_PRIORITY = 10000;

	const REQUEST_FILTER_PRIORITY = -10000;

	/**
	 * @var boolean
	 */
	private static $do_pre_get_posts = true;

	/**
	 * @var Query_Control
	 */
	private static $instance = null;

	/**
	 * @var \WP_Query
	 */
	private $query = null;

	/**
	 * @var bool|int|null
	 */
	private $pre_get_posts = null;

	/**
	 * @var boolean
	 */
	private $handle_query = false;

	/**
	 * @var boolean
	 */
	private $toggle_handle_query = null;

	/**
	 * @var boolean
	 */
	private $doing_pre_get_posts = false;

	/**
	 * @var array
	 */
	private static $parameters = array();

	/**
	 * @var string
	 */
	private static $ixurl = null;

	/**
	 * @var string
	 */
	private static $ix_product_collection = false;

	/**
	 * @var array
	 */
	private static $handle_collection_stack = array();

	/**
	 * Initialize class and pre_get_posts handler instance.
	 */
	public static function init() {
		if ( self::$do_pre_get_posts ) {
			if ( self::$instance === null ) {
				self::$instance = new Query_Control();
				add_action( 'pre_get_posts', array( self::$instance, 'pre_get_posts' ), self::PRE_GET_POSTS_ACTION_PRIORITY );
			}
		}
		add_action( 'request', array( __CLASS__, 'request' ), self::REQUEST_FILTER_PRIORITY );
		add_filter( 'query_loop_block_query_vars', array( __CLASS__, 'query_loop_block_query_vars' ), 10, 3 );
		add_filter( 'render_block_data', array( __CLASS__, 'render_block_data' ), 10, 3 );
		add_filter( 'render_block', array( __CLASS__, 'render_block' ), 10, 3 );
	}

	/**
	 * Hooked on render_block_data.
	 *
	 * @since 5.6.0
	 *
	 * @param array $parsed_block
	 * @param array $source_block
	 * @param \WP_Block|null $parent_block
	 *
	 * @return array
	 */
	public static function render_block_data( $parsed_block, $source_block, $parent_block ) {

		$handle = true;

		if ( $parent_block !== null ) {
			if ( $parent_block->name === 'woocommerce/product-collection' ) {
				$handle_collections = \WooCommerce_Product_Search_Service::get_handle_collections();
				if ( isset( $parent_block->attributes['collection'] ) ) {
					$collection = $parent_block->attributes['collection'];
					switch ( $collection ) {
						case 'woocommerce/product-collection/featured':
						case 'woocommerce/product-collection/top-rated':
						case 'woocommerce/product-collection/on-sale':
						case 'woocommerce/product-collection/best-sellers':
						case 'woocommerce/product-collection/new-arrivals':
							$handle = isset( $handle_collections[$collection] ) && is_scalar( $handle_collections[$collection] ) ? boolval( $handle_collections[$collection] ) : false;
							break;
						default:
							$handle = isset( $handle_collections['woocommerce/product-collection/product-catalog'] ) && is_scalar( $handle_collections['woocommerce/product-collection/product-catalog'] ) ? boolval( $handle_collections['woocommerce/product-collection/product-catalog'] ) : $handle;
					}
				} else {
					$handle = isset( $handle_collections['woocommerce/product-collection/product-catalog'] ) && is_scalar( $handle_collections['woocommerce/product-collection/product-catalog'] ) ? boolval( $handle_collections['woocommerce/product-collection/product-catalog'] ) : $handle;
				}
			}
		}

		array_push( self::$handle_collection_stack, $handle );

		return $parsed_block;
	}

	/**
	 * Hooked on render_block.
	 *
	 * @since 5.6.0
	 *
	 * @param string $block_content
	 * @param array $block
	 * @param \WP_Block $instance
	 *
	 * @return string
	 */
	public static function render_block( $block_content, $block, $instance ) {

		array_pop( self::$handle_collection_stack );
		return $block_content;
	}

	/**
	 * Hooked on query_loop_block_query_vars filter.
	 *
	 * @since 5.6.0
	 *
	 * @param array $query
	 * @param \WP_Block $block
	 * @param int $page
	 *
	 * @return array
	 */
	public static function query_loop_block_query_vars( $query, $block, $page ) {

		if ( isset( $query['post_type'] ) && $query['post_type'] === 'product' ) {

			$handle = empty( self::$handle_collection_stack ) || !in_array( false, self::$handle_collection_stack );
			$handle = apply_filters( 'woocommerce_product_search_query_control_handle_block_query', $handle, $query, $block, $page );
			$handle = is_scalar( $handle ) ? boolval( $handle ) : false;
			if ( !$handle ) {
				return $query;
			}

			switch ( $block->name ) {
				case 'woocommerce/product-collection-no-results':
				case 'core/query-pagination-next':
				case 'core/query-pagination-previous':
				case 'core/query-pagination-numbers':
				case 'woocommerce/product-collection':
				case 'woocommerce/product-template':
					$query_control = Query_Control::get_instance();
					if ( $query_control !== null ) {
						$handle_query = $query_control->get_handle_query();
						$query_control->set_handle_query( true );
						$query_control->set_toggle_handle_query( $handle_query );
					}
					break;
			}
		}
		return $query;
	}

	/**
	 * Enable or disable pre_get_posts processing.
	 *
	 * @param boolean $do
	 */
	public static function do_pre_get_posts( $do ) {
		self::$do_pre_get_posts = boolval( $do );
	}

	/**
	 * Provide the main instance.
	 *
	 * @return \com\itthinx\woocommerce\search\engine\Query_Control
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Filter request.
	 *
	 * @param array $query_vars requested query variables
	 */
	public static function request( $query_vars ) {

		if ( !self::is_request_to_store_api() ) {
			if ( isset( $_REQUEST['ix-product-collection'] ) ) {
				self::$ix_product_collection = true;
			}

		}
		return $query_vars;
	}

	/**
	 * Determine whether the query should be handled.
	 *
	 * @param boolean $handle
	 */
	public function set_handle_query( $handle ) {

		if ( is_bool( $handle ) ) {
			$this->handle_query = boolval( $handle );
		}
	}

	/**
	 * Toggle whether the query should be handled.
	 *
	 * @param boolean $handle
	 */
	public function set_toggle_handle_query( $handle ) {

		if ( is_bool( $handle ) ) {
			$this->toggle_handle_query = boolval( $handle );
		}
	}

	/**
	 * Whether the query should be handled.
	 *
	 * @return boolean
	 */
	public function get_handle_query() {
		return $this->handle_query;
	}

	/**
	 * New instance initialization.
	 */
	public function __construct() {

		add_action( 'woocommerce_product_search_engine_process_start', array( $this, 'woocommerce_product_search_engine_process_start' ) );
		add_action( 'woocommerce_product_search_engine_process_end', array( $this, 'woocommerce_product_search_engine_process_end' ) );
	}

	/**
	 * Instance destruction.
	 */
	public function __destruct() {
		remove_action( 'woocommerce_product_search_engine_process_start', array( $this, 'woocommerce_product_search_engine_process_start' ) );
		remove_action( 'woocommerce_product_search_engine_process_end', array( $this, 'woocommerce_product_search_engine_process_end' ) );
	}

	/**
	 * Engine processing start action handler.
	 *
	 * @param Engine $engine
	 */
	public function woocommerce_product_search_engine_process_start( $engine ) {
		$this->pre_get_posts = has_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		if ( $this->pre_get_posts !== false ) {
			remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		}
	}

	/**
	 * Engine processing end action handler.
	 *
	 * @param Engine $engine
	 */
	public function woocommerce_product_search_engine_process_end( $engine ) {
		if ( $this->pre_get_posts !== null && $this->pre_get_posts !== false ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), self::PRE_GET_POSTS_ACTION_PRIORITY );
		}
	}

	/**
	 * Set the WP_Query for this instance, or remove it by supplying null.
	 *
	 * @param \WP_Query $query
	 */
	public function set_query( $query ) {
		if ( $query instanceof \WP_Query || $query === null ) {
			$this->query = $query;
		}
	}

	/**
	 * The stored query for this instance.
	 *
	 * @return \WP_Query|null
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Handler for pre_get_posts.
	 *
	 * @param \WP_Query $wp_query query object
	 */
	public function pre_get_posts( $wp_query ) {

		if ( self::$do_pre_get_posts ) {
			$this->doing_pre_get_posts = true;
			$this->process_query( $wp_query );
			$this->doing_pre_get_posts = false;
			if ( $this->toggle_handle_query !== null ) {
				$this->set_handle_query( $this->toggle_handle_query );
				$this->toggle_handle_query = null;
			}
		}
	}

	/**
	 * Whether the current request is for the Store API.
	 *
	 * @return boolean
	 */
	public static function is_request_to_store_api() {

		global $wp;
		return
			defined( 'REST_REQUEST' ) &&
			REST_REQUEST &&
			!empty( $wp ) &&
			!empty( $wp->query_vars['rest_route'] ) &&
			is_string( $wp->query_vars['rest_route'] ) &&
			strpos( $wp->query_vars['rest_route'], '/wc/store' ) === 0;
	}

	/**
	 * Provide the parameter source, $_REQUEST or $_REQUEST['ixurl']
	 *
	 * @return array
	 */
	public static function get_source() {

		$source = $_REQUEST;
		if ( self::is_request_to_store_api() ) {
			if ( isset( $_REQUEST['ixurl'] ) ) {
				$url = parse_url( $_REQUEST['ixurl'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( is_array( $url ) && isset( $url['query'] ) ) {
					$query = $url['query'];
					parse_str( $query, $source );
				}
			}
		}
		return $source;
	}

	/**
	 * Request parameters.
	 *
	 * @return array
	 */
	public function get_request_parameters() {

		$key = $this->query !== null ? md5( serialize( $this->query ) ) : '';
		if ( isset( self::$parameters[$key] ) ) {
			return self::$parameters[$key];
		}

		$source = self::get_source();

		$title      = isset( $source[self::TITLE] ) && is_scalar( $source[self::TITLE] ) ? intval( $source[self::TITLE] ) > 0 : self::DEFAULT_TITLE;
		$excerpt    = isset( $source[self::EXCERPT] ) && is_scalar( $source[self::EXCERPT] ) ? intval( $source[self::EXCERPT] ) > 0 : self::DEFAULT_EXCERPT;
		$content    = isset( $source[self::CONTENT] ) && is_scalar( $source[self::CONTENT] ) ? intval( $source[self::CONTENT] ) > 0 : self::DEFAULT_CONTENT;
		$tags       = isset( $source[self::TAGS] ) && is_scalar( $source[self::TAGS] ) ? intval( $source[self::TAGS] ) > 0 : self::DEFAULT_TAGS;
		$sku        = isset( $source[self::SKU] ) && is_scalar( $source[self::SKU] ) ? intval( $source[self::SKU] ) > 0 : self::DEFAULT_SKU;
		$categories = isset( $source[self::CATEGORIES] ) && is_scalar( $source[self::CATEGORIES] ) ? intval( $source[self::CATEGORIES] ) > 0 : self::DEFAULT_CATEGORIES;
		$attributes = isset( $source[self::ATTRIBUTES] ) && is_scalar( $source[self::ATTRIBUTES] ) ? intval( $source[self::ATTRIBUTES] ) > 0 : self::DEFAULT_ATTRIBUTES;
		$variations = isset( $source[self::VARIATIONS] ) && is_scalar( $source[self::VARIATIONS] ) ? intval( $source[self::VARIATIONS] ) > 0 : self::DEFAULT_VARIATIONS;
		$min_price  = isset( $source[self::MIN_PRICE] ) && is_scalar( $source[self::MIN_PRICE] ) ? \WooCommerce_Product_Search_Utility::to_float( $source[self::MIN_PRICE] ) : null;
		$max_price  = isset( $source[self::MAX_PRICE] ) && is_scalar( $source[self::MAX_PRICE] ) ? \WooCommerce_Product_Search_Utility::to_float( $source[self::MAX_PRICE] ) : null;
		$on_sale    = isset( $source[self::ON_SALE] ) && is_scalar( $source[self::ON_SALE] ) ? intval( $source[self::ON_SALE] ) > 0 : self::DEFAULT_ON_SALE;
		$rating     = isset( $source[self::RATING] ) && is_scalar( $source[self::RATING] ) ? intval( $source[self::RATING] ) : self::DEFAULT_RATING;
		$in_stock   = isset( $source[self::IN_STOCK] ) && is_scalar( $source[self::IN_STOCK] ) ? intval( $source[self::IN_STOCK] ) > 0 : self::DEFAULT_IN_STOCK;

		$search_query = isset( $source[Base::SEARCH_QUERY] ) && is_string( $source[Base::SEARCH_QUERY] ) ? sanitize_text_field( $source[Base::SEARCH_QUERY] ) : null;
		if ( $search_query !== null ) {
			$search_query = trim( preg_replace( '/\s+/', ' ', $search_query ) );
			if ( strlen( $search_query ) === 0 ) {
				$search_query = null;
			}
		}
		$ixwpss = isset( $source['ixwpss'] ) && is_string( $source['ixwpss'] ) ? sanitize_text_field( $source['ixwpss'] ) : null;
		if ( $ixwpss !== null ) {
			$ixwpss = trim( preg_replace( '/\s+/', ' ', $ixwpss ) );
			if ( strlen( $ixwpss ) === 0 ) {
				$ixwpss = null;
			}
		}
		if ( $search_query === null ) {
			$s = \WooCommerce_Product_Search_Service::get_s();
			if ( is_string( $s ) ) {
				$s = trim( sanitize_text_field( $s ) );
				if ( strlen( $s ) === 0 ) {
					$s = null;
				}
			} else {
				$s = null;
			}
			if ( $ixwpss !== null ) {
				$search_query = $ixwpss;
				if ( $s !== null && $s !== $ixwpss ) {
					$search_query .= ' ' . $s;
				}
			} else if ( $s !== null ) {
				$search_query = $s;
			}
		}

		$limit = isset( $source[self::LIMIT] ) && is_numeric( $source[self::LIMIT] ) ? intval( $source[self::LIMIT] ) : self::DEFAULT_LIMIT;
		$limit = max( 0, intval( apply_filters( 'product_search_limit', $limit ) ) );

		$offset = isset( $source['offset'] ) && is_numeric( $source['offset'] ) ? max( 0, intval( $source['offset'] ) ) : null;
		$page = isset( $source['page'] ) && is_numeric( $source['page'] ) ? max( 1, intval( $source['page'] ) ) : null;
		$per_page = isset( $source['per_page'] ) && is_numeric( $source['per_page'] ) ? max( 1, intval( $source['per_page'] ) ) : null;

		$order = isset( $source[self::ORDER] ) && is_string( $source[self::ORDER] ) ? strtoupper( sanitize_text_field( trim( $source[self::ORDER] ) ) ) : null;
		switch ( $order ) {
			case 'DESC' :
			case 'ASC' :
				break;
			default :
				$order = null;
		}
		$order_by = isset( $source[self::ORDER_BY] ) && is_string( $source[self::ORDER_BY] ) ? sanitize_text_field( trim( $source[self::ORDER_BY] ) ) : null;

		$ixwpse = isset( $source['ixwpse'] ) && is_scalar( $source['ixwpse'] ) ? boolval( $source['ixwpse'] ) : false;
		$ixwpsp = isset( $source['ixwpsp'] ) && is_scalar( $source['ixwpsp'] ) ? boolval( $source['ixwpsp'] ) : false;

		$ixwpst = Term_Control::get_ixwpst( $this->query );

		$ixwpsf = array();
		$_ixwpsf = isset( $source['ixwpsf'] ) ? $source['ixwpsf'] : null;
		if ( is_array( $_ixwpsf ) ) {
			foreach ( $_ixwpsf as $type => $data ) {
				switch ( $type ) {
					case 'taxonomy':
						if ( is_array( $data ) ) {
							foreach ( $data as $taxonomy => $_options ) {
								$taxonomy = sanitize_text_field( $taxonomy );
								if ( taxonomy_exists( $taxonomy ) ) {
									if ( is_array( $_options ) ) {
										$options = array();
										foreach ( $_options as $option_key => $value ) {
											$option_key = sanitize_text_field( $option_key );
											switch ( $option_key ) {
												case 'multiple':
												case 'filter':
													$value = !empty( $value );
													$options[$option_key] = $value;
													break;
												case 'show':
												case 'op':
													$value = sanitize_text_field( $value );
													$options[$option_key] = $value;
													break;
											}
										}
										if ( count( $options ) > 0 ) {
											$ixwpsf['taxonomy'][$taxonomy] = $options;
										}
									}
								}
							}
						}
						break;
				}
			}
		}

		$term_limit = apply_filters( 'woocommerce_product_search_process_query_object_term_limit', self::OBJECT_TERM_LIMIT );
		if ( is_numeric( $term_limit ) ) {
			$term_limit = intval( $term_limit );
		} else {
			$term_limit = self::OBJECT_TERM_LIMIT;
		}
		$term_limit = max( 1, $term_limit );
		foreach ( $ixwpst as $taxonomy => $term_ids ) {
			$term_count = count( $term_ids );
			if ( $term_count > $term_limit ) {
				$term_ids = array_slice( $term_ids, 0, $term_limit );
				$ixwpst[$taxonomy] = $term_ids;
				if ( WPS_DEBUG_VERBOSE ) {
					wps_log_warning(
						sprintf(
							'The number of processed terms [%s] has been limited to %d, the number of requested terms was %d.',
							esc_html( $taxonomy ),
							$term_limit,
							$term_count
						)
					);
				}
			}
		}

		$parameters = array(
			'title' => $title,
			'excerpt' => $excerpt,
			'content' => $content,
			'tags' => $tags,
			'sku' => $sku,
			'categories' => $categories,
			'attributes' => $attributes,
			'variations' => $variations,
			'min_price' => $min_price,
			'max_price' => $max_price,
			'on_sale' => $on_sale,
			'rating' => $rating,
			'in_stock' => $in_stock,
			'search_query' => $search_query,
			'limit' => $limit,
			'offset' => $offset,
			'page' => $page,
			'per_page' => $per_page,
			'order' => $order,
			'orderby' => $order_by,
			'ixwpse' => $ixwpse,
			'ixwpsp' => $ixwpsp,
			'ixwpss' => $ixwpss,
			'ixwpst' => $ixwpst
		);

		if ( count( $ixwpsf ) > 0 ) {
			$parameters['ixwpsf'] = $ixwpsf;
		}

		self::$parameters[$key] = $parameters;

		return $parameters;
	}

	/**
	 * Handle the query?
	 *
	 * @param \WP_Query $wp_query
	 *
	 * @return boolean
	 */
	private function handle( $wp_query ) {

		$handle = false;

		$s = \WooCommerce_Product_Search_Service::get_s();

		if ( $s === null ) {
			if ( defined( 'WP_CLI' ) && WP_CLI && $wp_query->get( 'post_type' ) === 'product' ) {
				$settings = Settings::get_instance();
				$auto_replace_rest = $settings->get( \WooCommerce_Product_Search::AUTO_REPLACE_REST, \WooCommerce_Product_Search::AUTO_REPLACE_REST_DEFAULT );
				if ( $auto_replace_rest ) {
					$s = $wp_query->get( 's', null );
				}
			}
		}

		$is_search =
			$s !== null &&
			(
				$wp_query->is_search()
				||
				$wp_query->get( 'product_search', false )
				||
				defined( 'REST_REQUEST' ) && REST_REQUEST && $wp_query->get( 'post_type' ) === 'product' && $wp_query->get( 'search', false )
				||
				defined( 'WP_CLI' ) && WP_CLI && $wp_query->get( 'post_type' ) === 'product' && $wp_query->get( 's', false )
			);

		$use_engine = \WooCommerce_Product_Search_Service::use_engine();

		$params = $this->get_request_parameters();
		$is_filtering =
			$params['ixwpss'] !== null ||
			!empty( $params['ixwpst'] ) ||
			$params['ixwpsp'] ||
			$params['ixwpse'];

		$process_query = false;
		$post_type     = $wp_query->get( 'post_type' );
		if ( $post_type === 'product' ) {
			$process_query = true;
		} else if ( empty( $post_type ) ) {
			if ( $wp_query->is_tax ) {
				$product_taxonomies = \WooCommerce_Product_Search_Service::get_product_taxonomies();
				$product_taxonomies = apply_filters( 'woocommerce_product_search_process_query_product_taxonomies', $product_taxonomies, $wp_query );
				if ( !is_array( $product_taxonomies ) ) {
					$product_taxonomies = array();
				}
				$product_taxonomies = array_unique( $product_taxonomies );
				$queried_object     = $wp_query->get_queried_object();
				if ( is_object( $queried_object ) ) {
					if ( in_array( $queried_object->taxonomy, $product_taxonomies ) ) {
						$process_query = true;
					}
				}
			}
		}

		$is_main_query = $wp_query->is_main_query();

		$is_request_to_store_api = self::is_request_to_store_api();

		$is_ixurl = false;
		if ( $is_request_to_store_api ) {

			if ( $wp_query->get( 'post_type' ) === 'product' ) {
				$is_ixurl = isset( $_REQUEST['ixurl'] );
			}
		}

		$is_ix_product_collection = false;
		if ( !$is_request_to_store_api && self::$ix_product_collection ) {
			if ( $wp_query->get( 'post_type' ) === 'product' ) {
				$is_ix_product_collection = apply_filters( 'woocommerce_product_search_query_control_handle_product_collection', $is_ix_product_collection, $wp_query, $this );
				$is_ix_product_collection = boolval( $is_ix_product_collection );
			}
		}

		$handle = $process_query && (
			$is_search && $use_engine ||
			$is_filtering && ( $is_main_query || $this->handle_query ) ||
			$is_request_to_store_api && $is_ixurl ||
			$is_ix_product_collection
		);
		$handle = apply_filters( 'woocommerce_product_search_query_control_handle', $handle, $wp_query, $this );
		return $handle;
	}

	/**
	 * Process the query.
	 *
	 * @param \WP_Query $wp_query
	 */
	private function process_query( $wp_query ) {

		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), self::PRE_GET_POSTS_ACTION_PRIORITY );

		global $wps_process_query;

		if ( isset( $wps_process_query ) && !$wps_process_query ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), self::PRE_GET_POSTS_ACTION_PRIORITY );
			return;
		}

		$this->set_query( $wp_query );

		if ( !$this->handle( $wp_query ) ) {

			$this->set_query( null );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), self::PRE_GET_POSTS_ACTION_PRIORITY );
			return;
		}

		$params = $this->get_request_parameters();
		$params['offset'] = 0;
		$params['page'] = null;
		$params['per_page'] = null;

		$post_ids = null;

		$s = \WooCommerce_Product_Search_Service::get_s();

		if ( $s === null ) {
			if ( defined( 'WP_CLI' ) && WP_CLI && $wp_query->get( 'post_type' ) === 'product' ) {
				$settings = Settings::get_instance();
				$auto_replace_rest = $settings->get( \WooCommerce_Product_Search::AUTO_REPLACE_REST, \WooCommerce_Product_Search::AUTO_REPLACE_REST_DEFAULT );
				if ( $auto_replace_rest ) {
					$s = $wp_query->get( 's', null );
				}
			}
		}

		if ( $params['search_query'] !== null ) {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST && $wp_query->get( 'post_type' ) === 'product' && $wp_query->get( 'search', false ) ) {
				$wp_query->set( 'search', null );
				$params['variations'] = true;

			}
		}

		$post_ids = $this->get_ids( $params );

		if ( $post_ids !== null ) {
			if ( count( $post_ids ) > 0 ) {
				$wp_query->set( 'post__in', $post_ids );
			} else {
				$wp_query->set( 'post__in', \WooCommerce_Product_Search_Service::NONE );
			}
		}

		if ( $params['ixwpsp'] ) {
			$meta_query = $wp_query->get( 'meta_query' );
			if ( isset( $meta_query['price_filter'] ) ) {
				unset( $meta_query['price_filter'] );
				$wp_query->set( 'meta_query', $meta_query );
			}
		}

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), self::PRE_GET_POSTS_ACTION_PRIORITY );
	}

	/**
	 * Provide results
	 *
	 * @param $params array
	 *
	 * @return array|null
	 */
	public function get_ids( $params = null ) {

		global $wps_doing_ajax;

		$title      = $params['title'];
		$excerpt    = $params['excerpt'];
		$content    = $params['content'];
		$tags       = $params['tags'];
		$sku        = $params['sku'];
		$categories = $params['categories'];
		$attributes = $params['attributes'];
		$variations = $params['variations'];
		$limit      = $params['limit'];
		$offset     = $params['offset'];
		$page       = $params['page'];
		$per_page   = $params['per_page'];
		$min_price  = $params['min_price'];
		$max_price  = $params['max_price'];
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
		$on_sale = $params['on_sale'];
		$rating = $params['rating'];
		if ( $rating !== self::DEFAULT_RATING ) {
			if ( $rating < \WooCommerce_Product_Search_Filter_Rating::MIN_RATING ) {
				$rating = \WooCommerce_Product_Search_Filter_Rating::MIN_RATING;
			}
			if ( $rating > \WooCommerce_Product_Search_Filter_Rating::MAX_RATING ) {
				$rating = \WooCommerce_Product_Search_Filter_Rating::MAX_RATING;
			}
		}
		$in_stock = $params['in_stock'];
		$search_query = $params['search_query'];
		$order = $params['order'];
		$orderby = $params['orderby'];

		$stage_variations = true;

		$engine = new \com\itthinx\woocommerce\search\engine\Engine();

		if ( !empty( $search_query ) ) {
			$args = array(
				'q' => $search_query,
				'title' => $title,
				'excerpt' => $excerpt,
				'content' => $content,
				'tags' => $tags,
				'sku' => $sku,
				'categories' => $categories,
				'attributes' => $attributes,
				'variations' => $stage_variations
			);
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Words( $args );
			$engine->attach_stage( $stage );
		}

		if ( $min_price !== null || $max_price !== null ) {
			$args = array( 'variations' => $stage_variations );
			if ( $min_price !== null ) {
				$args['min_price'] = trim( sanitize_text_field( $min_price ) );
			}
			if ( $max_price !== null ) {
				$args['max_price'] = trim( sanitize_text_field( $max_price ) );
			}
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Price( $args );
			$engine->attach_stage( $stage );
		}

		if ( $on_sale ) {
			$args = array( 'sale' => 'onsale', 'variations' => $stage_variations );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Sale( $args );
			$engine->attach_stage( $stage );
		}

		if ( $rating ) {
			$args = array( 'rating' => $rating, 'variations' => $stage_variations );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Rating( $args );
			$engine->attach_stage( $stage );
		}

		if ( is_admin() && !isset( $wps_doing_ajax ) ) {
		} else {

			if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) {
				$in_stock = true;
			}
		}
		if ( $in_stock ) {
			$args = array( 'stock' => array( 'instock', 'onbackorder' ), 'variations' => $stage_variations );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Stock( $args );
			$engine->attach_stage( $stage );
		}

		if ( is_admin() && !isset( $wps_doing_ajax ) ) {
		} else {

			global $wp_query;
			$is_product_search = false;
			if ( $wp_query->is_main_query() ) {

				$is_product_search = isset( $_REQUEST[Base::SEARCH_TOKEN] );

				if ( !$is_product_search ) {
					$post_type = $wp_query->get( 'post_type', false );
					if (
						is_string( $post_type ) && $post_type === 'product' ||
						is_array( $post_type ) && in_array( 'product', $post_type )
					) {
						$is_product_search =
							$wp_query->is_search() ||
							$wp_query->get( 'product_search', false );;
					}
				}
			}
			$visibility = null;
			if ( $is_product_search ) {
				$visibility = 'search';
			} else if ( \WooCommerce_Product_Search_Utility::is_shop() ) {
				$visibility = 'catalog';
			}
			if ( $visibility !== null ) {
				$args = array( 'visibility' => $visibility, 'variations' => $stage_variations );
				$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Visibility( $args );
				$engine->attach_stage( $stage );
			}
		}

		if ( is_admin() && !isset( $wps_doing_ajax ) ) {
		} else {
			if ( is_array( $params['ixwpst'] ) && count( $params['ixwpst'] ) > 0 ) {
				$ixwpsf_taxonomies = array();
				if ( isset( $params['ixwpsf'] ) ) {
					$ixwpsf_taxonomies = $params['ixwpsf']['taxonomy'];
				}
				foreach ( $params['ixwpst'] as $taxonomy => $term_ids ) {
					if ( count( $term_ids ) > 0 ) {

						$op = 'or';
						$i = 0;
						foreach ( $ixwpsf_taxonomies as $ixwpsf_taxonomy => $options ) {
							if ( $ixwpsf_taxonomy === $taxonomy ) {
								if ( isset( $options['op'] ) ) {
									switch ( $options['op'] ) {
										case 'or':
										case 'and':
										case 'not':
											$op = $options['op'];
											break;
									}
								}
								array_splice( $ixwpsf_taxonomies, $i, 1 );
							}
							$i++;
						}
						$args = array(
							'taxonomy' => $taxonomy,
							'terms'    => $term_ids,
							'id_by'    => 'id',
							'op'       => $op,
							'variations' => $stage_variations
						);
						$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Terms( $args );
						$engine->attach_stage( $stage );
					}
				}
			}
		}

		if ( $engine->get_stage_count() > 0 ) {
			$args = array( 'variations' => $stage_variations );
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Synchrotron( $args );
			$engine->attach_stage( $stage );
		}

		if ( !$this->doing_pre_get_posts ) {
			$post_status = \WooCommerce_Product_Search_Service::get_post_status();
			$args = array(
				'order'      => $order,
				'orderby'    => $orderby,
				'status'     => $post_status,
				'variations' => $variations
			);
			$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Posts( $args );
			$engine->attach_stage( $stage );
		}

		if ( $engine->get_stage_count() > 0 ) {

			if ( $limit !== null && $limit > 0 || $offset !== null ) {

				$args = array(
					'limit' => $limit,
					'offset' => $offset !== null ? $offset : 0,
					'page' => null,
					'per_page' => null
				);
				$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Pagination( $args );
				$engine->attach_stage( $stage );
			} else if ( $per_page !== null && $page !== null ) {
				$args = array(
					'limit' => null,
					'offset' => null,
					'page' => $page,
					'per_page' => $per_page
				);
				$stage = new \com\itthinx\woocommerce\search\engine\Engine_Stage_Pagination( $args );
				$engine->attach_stage( $stage );
			}
		}

		if ( $this->doing_pre_get_posts && $engine->get_stage_count() === 0 ) {
			return null;
		}

		$ids = $engine->get_ids();

		if ( has_action( 'woocommerce_product_search_service_post_ids_for_request' ) ) {
			$context = array(
				'title'        => $title,
				'excerpt'      => $excerpt,
				'content'      => $content,
				'tags'         => $tags,
				'sku'          => $sku,
				'categories'   => $categories,
				'attributes'   => $attributes,
				'variations'   => $variations,
				'search_query' => $search_query,
				'min_price'    => $min_price,
				'max_price'    => $max_price,
				'on_sale'      => $on_sale,
				'rating'       => $rating,
				'in_stock'     => $in_stock
			);
			do_action_ref_array(
				'woocommerce_product_search_service_post_ids_for_request',
				array( &$ids, $context )
			);
			foreach ( $ids as $key => $value ) {
				$ids[$key] = intval( $value );
			}
		}

		$count = count( $ids );

		if ( !empty( $search_query ) ) {
			$record_search_query = \WooCommerce_Product_Search_Indexer::equalize( $search_query );
			\WooCommerce_Product_Search_Service::maybe_record_hit( $record_search_query, $count );
		}

		return $ids;
	}

}

Query_Control::init();
