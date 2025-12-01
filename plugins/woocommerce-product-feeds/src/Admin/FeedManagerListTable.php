<?php

namespace Ademti\WoocommerceProductFeeds\Admin;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigRepository;
use Ademti\WoocommerceProductFeeds\Helpers\TermDepthRepository;
use Exception;
use WP_List_Table;

/**
 * List table class for the admin pages
 */
class FeedManagerListTable extends WP_List_Table {

	// Dependencies.
	protected Configuration $configuration;
	protected FeedConfigRepository $repository;
	private TermDepthRepository $term_depth_repository;

	/**
	 * Constructor
	 *
	 * This class is instantiated early in the request lifecycle. To avoid issues with attempting to call parts of
	 * WordPress that aren't ready yet, we lazily call __construct() on the parent class when prepare_items() is
	 * invoked.
	 *
	 * @param FeedConfigRepository $repository
	 * @param Configuration $configuration
	 * @param TermDepthRepository $term_depth_repository
	 */
	public function __construct(
		FeedConfigRepository $repository,
		Configuration $configuration,
		TermDepthRepository $term_depth_repository
	) {
		$this->repository            = $repository;
		$this->configuration         = $configuration;
		$this->term_depth_repository = $term_depth_repository;
	}

	/**
	 * Description shown when no replacements configured
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No feeds configured yet.', 'woocommerce_gpf' );
	}

	/**
	 * Specify the list of columns in the table.
	 *
	 * @return array The list of columns
	 * @throws Exception
	 */
	public function get_columns() {
		$columns = [
			'id'         => 'Feed ID (Internal)',
			'name'       => __( 'Name', 'woocommerce_gpf' ),
			'type'       => __( 'Type', 'woocommerce_gpf' ),
			'categories' => __( 'Category filtering', 'woocommerce_gpf' ),
			'edit'       => '',
			'delete'     => '',
		];

		return apply_filters(
			'woocommerce_gpf_feed_list_columns',
			$columns
		);
	}

	/**
	 * Retrieve the items for display
	 *
	 * @return void
	 */
	public function prepare_items() {

		// Late construction to avoid issues with autoloading running stuff before WordPress is ready.
		parent::__construct();

		$columns               = $this->get_columns();
		$hidden                = apply_filters( 'woocommerce_gpf_feed_list_hidden_columns', [ 'id' ] );
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$this->items = $this->repository->all();
	}

	/**
	 * Indicate which columns are sortable
	 * @return array A list of the columns that are sortable.
	 */
	protected function get_sortable_columns() {
		return apply_filters(
			'woocommerce_gpf_feed_list_sortable_columns',
			[
				'name' => [ 'name', true ],
				'type' => [ 'type', true ],
			]
		);
	}

	/**
	 * Set the primary column.
	 *
	 * @return string The name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'name';
	}

	/**
	 * Remove table-fixed from the table classes.
	 *
	 * @return string[]
	 */
	protected function get_table_classes() {
		return [ 'widefat', 'striped' ];
	}

	/**
	 * Output column data
	 *
	 * @return void
	 */
	protected function column_default( $item, $column_name ) {
		$callable = apply_filters( 'woocommerce_gpf_feed_list_column_callback', null, $column_name );
		if ( ! is_callable( $callable ) ) {
			echo esc_html( $column_name );
			return;
		}
		call_user_func( $callable, $item, $column_name );
	}

	/**
	 * Render the "Name" column.
	 *
	 * @param $item
	 *
	 * @return string
	 */
	protected function column_name( $item ) {
		$url = home_url( '/woocommerce_gpf/' . $item->id );

		return sprintf(
			'%1$s<br><a href="%2$s" target="black" rel="noopener noreferrer">%3$s</a>',
			esc_html( $item->name ),
			esc_attr( $url ),
			$url
		);
	}

	/**
	 * Render the "Type" column.
	 *
	 * @param $item
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function column_type( $item ) {
		$feed_types = $this->configuration->get_feed_types();
		$output     = $item->type;
		if ( isset( $feed_types[ $output ]['name'] ) ) {
			$output = $feed_types[ $output ]['name'];
		}
		if ( 'googlereview' === $item->type ) {
			$limit = $item->limit;
			switch ( $limit ) {
				case 'week':
					$output .= '<br>' . esc_html( __( '(Reviews in the last 7 days)', 'woocommerce_gpf' ) );
					break;
				case 'yesterday':
					$output .= '<br>' . esc_html( __( '(Reviews yesterday)', 'woocommerce_gpf' ) );
					break;
				default:
					$output .= '<br>' . esc_html( __( '(All reviews)', 'woocommerce_gpf' ) );
			}
		}
		return apply_filters( 'woocommerce_gpf_feed_list_feed_type', $output, $item, $feed_types );
	}

	/**
	 * Render the "categories" column.
	 *
	 * @param $item
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function column_categories( $item ) {
		$content = '';
		switch ( $item->category_filter ?? '' ) {
			case 'only':
				$content    = __( '<em>Only</em>:', 'woocommerce_gpf' );
				$list_class = 'woo-gpf-list-included';
				break;
			case 'except':
				$content    = __( 'All <em>except</em>:', 'woocommerce_gpf' );
				$list_class = 'woo-gpf-list-excluded';
				break;
			default:
				return '<p>' . __( 'All', 'woocommerce_gpf' ) . '</p>';
		}
		$content = '<p>' . $content . '<ul class="' . $list_class . '">';
		foreach ( $item->categories as $category_id ) {
			$category = get_term( $category_id );
			if ( ! $category ) {
				$content .= '<li>' . _x(
					'Unknown',
					'Term name to use when passed an invalid term ID',
					'woocommerce_gpf'
				) . '</li>';
				continue;
			}
			$content .= '<li>' . $this->term_depth_repository->get_hierarchy_string( $category ) . '</li>';
		}
		$content .= '</ul></p>';

		return $content;
	}

	/**
	 * Render the "actions" column.
	 *
	 * @param $item
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function column_edit( $item ) {
		$url = add_query_arg(
			[
				'page'       => 'woocommerce-gpf-manage-feeds',
				'gpf_action' => 'edit',
				'feed_id'    => $item->id,
			],
			admin_url( 'admin.php' )
		);

		return '<a href="' . esc_attr( $url ) . '">' . __( 'Edit', 'woocommerce_gpf' ) . '</a>';
	}

	protected function column_delete( $item ): string {
		$url = wp_nonce_url(
			add_query_arg(
				[
					'page'       => 'woocommerce-gpf-manage-feeds',
					'gpf_action' => 'delete-ask',
					'feed_id'    => $item->id,
				],
				admin_url( 'admin.php' )
			),
			'gpf_delete_ask_feed'
		);

		return '<a href="' . esc_attr( $url ) . '">' . __( 'Delete', 'woocommerce_gpf' ) . '</a>';
	}
}
