<?php
/**
 * class-woocommerce-product-search-compat-polylang.php
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
 * @since 6.6.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use com\itthinx\woocommerce\search\engine\Engine;
use com\itthinx\woocommerce\search\engine\Engine_Stage;
use com\itthinx\woocommerce\search\engine\Query_Control;
use com\itthinx\woocommerce\search\engine\Tools;

/**
 * Polylang compatibility.
 */
class WooCommerce_Product_Search_Compat_Polylang {

	/**
	 * Filter priorities.
	 *
	 * @var array
	 */
	private static $priority = null;

	/**
	 * Class action hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ), 100 );
	}

	/**
	 * Hooked on the init action.
	 */
	public static function wp_init() {

		if ( function_exists( 'pll_current_language' ) ) {
			add_filter( 'woocommerce_product_search_engine_cache_context', array( __CLASS__, 'woocommerce_product_search_engine_cache_context' ), 10, 2 );
			add_filter( 'woocommerce_product_search_engine_stage_cache_context', array( __CLASS__, 'woocommerce_product_search_engine_stage_cache_context' ), 10, 2 );
			add_filter( 'woocommerce_product_search_filter_product_loop_cache_context', array( __CLASS__, 'woocommerce_product_search_filter_product_loop_cache_context' ), 10, 3 );
			add_filter( 'woocommerce_product_search_term_control_get_term_counts_cache_context', array( __CLASS__, 'woocommerce_product_search_term_control_get_term_counts_cache_context' ), 10, 2 );
			add_filter( 'woocommerce_product_search_term_control_get_term_ids_cache_context', array( __CLASS__, 'woocommerce_product_search_term_control_get_term_ids_cache_context' ), 10, 3 );
			add_filter( 'woocommerce_product_search_term_control_get_term_ids_where_clauses', array( __CLASS__, 'woocommerce_product_search_term_control_get_term_ids_where_clauses' ), 10, 4 );
		}
	}

	/**
	 * Add constraints.
	 *
	 * @param string[] $where_clauses
	 * @param Query_Control $query_control
	 * @param array $params
	 * @param array $taxonomies
	 *
	 * @return string[]
	 */
	public static function woocommerce_product_search_term_control_get_term_ids_where_clauses( $where_clauses, $query_control, $params, $taxonomies ) {

		if ( function_exists( 'pll_current_language' ) ) {
			$current_language = pll_current_language();
			if ( is_string( $current_language ) && strlen( $current_language ) > 0 ) {
				$object_term_table = \WooCommerce_Product_Search_Controller::get_tablename( 'object_term' );
				$post_ids = $query_control->get_ids( $params );
				if ( $post_ids !== null ) {
					if ( count( $post_ids ) === 0 ) {
						$post_ids = array( -1 );
					}
					Tools::int( $post_ids );
					$where_clauses[] = "ot.term_id IN ( SELECT DISTINCT term_id FROM $object_term_table WHERE object_id IN (" . implode( ',', $post_ids ) . ') )'; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}
			}
		}
		return $where_clauses;
	}

	/**
	 * Establish language-dependent cache context.
	 *
	 * @param array $cache_context
	 * @param Engine $engine
	 *
	 * @return array
	 */
	public static function woocommerce_product_search_engine_cache_context( $cache_context, $engine ) {
		if ( function_exists( 'pll_current_language' ) ) {
			$current = pll_current_language();
			if ( !empty( $current ) && is_string( $current ) && strlen( $current ) > 0 ) {
				$cache_context['lang'] = $current;
			}
		}
		return $cache_context;
	}

	/**
	 * Establish language-dependent cache context.
	 *
	 * @param array $cache_context
	 * @param Engine_Stage $stage
	 *
	 * @return array
	 */
	public static function woocommerce_product_search_engine_stage_cache_context( $cache_context, $stage ) {
		if ( function_exists( 'pll_current_language' ) ) {
			$current = pll_current_language();
			if ( !empty( $current ) && is_string( $current ) && strlen( $current ) > 0 ) {
				$cache_context['lang'] = $current;
			}
		}
		return $cache_context;
	}

	/**
	 * Establish language-dependent cache context.
	 *
	 * @param array $cache_context
	 * @param array $atts shortcode attributes
	 * @param string $loop_name identifies the product filter loop
	 *
	 * @return array
	 */
	public static function woocommerce_product_search_filter_product_loop_cache_context( $cache_context, $atts, $loop_name ) {
		if ( function_exists( 'pll_current_language' ) ) {
			$current = pll_current_language();
			if ( !empty( $current ) && is_string( $current ) && strlen( $current ) > 0 ) {
				$cache_context['lang'] = $current;
			}
		}
		return $cache_context;
	}

	/**
	 * Establish language-dependent cache context.
	 *
	 * @param array $cache_context
	 * @param string $taxonomy
	 *
	 * @return array
	 */
	public static function woocommerce_product_search_term_control_get_term_counts_cache_context( $cache_context, $taxonomy ) {
		if ( function_exists( 'pll_current_language' ) ) {
			$current = pll_current_language();
			if ( !empty( $current ) && is_string( $current ) && strlen( $current ) > 0 ) {
				$cache_context['lang'] = $current;
			}
		}
		return $cache_context;
	}

	/**
	 * Establish language-dependent cache context.
	 *
	 * @param array $cache_context
	 * @param array $args
	 * @param array|string $taxonomies
	 *
	 * @return array
	 */
	public static function woocommerce_product_search_term_control_get_term_ids_cache_context( $cache_context, $args, $taxonomies ) {
		if ( function_exists( 'pll_current_language' ) ) {
			$current = pll_current_language();
			if ( !empty( $current ) && is_string( $current ) && strlen( $current ) > 0 ) {
				$cache_context['lang'] = $current;
			}
		}
		return $cache_context;
	}

}

WooCommerce_Product_Search_Compat_Polylang::init();
