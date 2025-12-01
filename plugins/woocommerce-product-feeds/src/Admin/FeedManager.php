<?php

namespace Ademti\WoocommerceProductFeeds\Admin;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\Configuration\FeedConfigRepository;
use Ademti\WoocommerceProductFeeds\Helpers\TemplateLoader;

class FeedManager {

	// Dependencies.
	private FeedConfigRepository $repository;
	private FeedManagerListTable $list_table;
	private TemplateLoader $template;
	private Configuration $configuration;

	/**
	 * WoocommerceProductFeedsFeedManager constructor.
	 *
	 * @param FeedConfigRepository $repository
	 * @param TemplateLoader $template
	 * @param Configuration $common
	 * @param FeedManagerListTable $list_table
	 */
	public function __construct(
		FeedConfigRepository $repository,
		TemplateLoader $template,
		Configuration $common,
		FeedManagerListTable $list_table
	) {
		$this->repository    = $repository;
		$this->list_table    = $list_table;
		$this->template      = $template;
		$this->configuration = $common;
	}

	/**
	 * Run the class features.
	 */
	public function initialise(): void {
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 99 );
	}

	/**
	 * Register our menu links/page.
	 *
	 * @return void
	 */
	// phpcs:disable WordPress.WP.Capabilities.Unknown
	public function admin_menu() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$page = add_submenu_page(
			'woocommerce',
			__( 'Manage product feeds', 'woocommerce_gpf' ),
			__( 'Product Feeds', 'woocommerce_gpf' ),
			'manage_woocommerce',
			'woocommerce-gpf-manage-feeds',
			[ $this, 'admin_page' ]
		);
		add_action( 'admin_print_styles-' . $page, [ $this, 'enqueue_scripts' ] );
	}
	// phpcs:enable WordPress.WP.Capabilities.Unknown

	/**
	 * Enqueue styles / scripts for the manage feeds page.
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
	}

	/**
	 * The manage feeds page.
	 *
	 * @return void
	 */
	public function admin_page() {
		// phpcs:disable WordPress.WP.Capabilities.Unknown
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		// phpcs:enable WordPress.WP.Capabilities.Unknown
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$gpf_action = isset( $_REQUEST['gpf_action'] ) ? sanitize_text_field( $_REQUEST['gpf_action'] ) : '';
		switch ( $gpf_action ) {
			case 'add':
			case 'edit':
				$this->edit_feed();
				break;
			case 'delete-ask':
				$this->delete_ask_feed();
				break;
			case 'delete':
				$this->delete_feed();
				wp_safe_redirect( admin_url( 'admin.php?page=woocommerce-gpf-manage-feeds&gpf_msg=2' ) );
				exit;
				break;
			case 'update':
				$msgid = $this->update_feed();
				wp_safe_redirect( admin_url( 'admin.php?page=woocommerce-gpf-manage-feeds&gpf_msg=' . $msgid ) );
				exit;
				break;
			default:
				$this->list_feeds();
				break;
		}
	}

	/**
	 * Show the list of configured feeds.
	 */
	private function list_feeds(): void {
		$this->template->output_template_with_variables(
			'woo-gpf',
			'admin-feed-list-header',
			[
				'add_link' => esc_attr( admin_url( 'admin.php?page=woocommerce-gpf-manage-feeds&gpf_action=add' ) ),
			]
		);
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['gpf_msg'] ) && in_array( (int) $_GET['gpf_msg'], [ 1, 2, 3 ], true ) ) {
			$this->template->output_template_with_variables( 'woo-gpf', 'admin-feed-list-msg-' . (int) $_GET['gpf_msg'], [] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$this->list_table->prepare_items();
		$this->list_table->display();
		$this->template->output_template_with_variables( 'woo-gpf', 'admin-feed-list-footer', [] );
	}

	/**
	 * Show the "edit feed" screen.
	 *
	 * Also used for "add".
	 */
	private function edit_feed(): void {
		$feed    = [];
		$feed_id = '';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_REQUEST['feed_id'] ) ) {
			$feed_id = sanitize_text_field( $_REQUEST['feed_id'] );
			$feed    = $this->repository->get( $feed_id );
			$feed    = $feed->to_array();
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$categories  = $this->get_categories( $feed['categories'] ?? [] );
		$page_header = ! empty( $feed_id ) ?
			__( 'Edit feed', 'woocommerce_gpf' ) :
			__( 'Add feed', 'woocommerce_gpf' );

		$feed_type  = $feed['type'] ?? '';
		$feed_types = array_filter(
			$this->configuration->get_feed_types(),
			static function ( $config, $type ) use ( $feed_type ) {
				return ( $config['legacy'] ?? false ) !== true ||
					$type === $feed_type;
			},
			ARRAY_FILTER_USE_BOTH
		);

		$vars = [
			'feed'            => $feed,
			'feed_id'         => $feed_id,
			'page_header'     => $page_header,
			'name'            => esc_attr( $feed['name'] ?? '' ),
			'type'            => $feed_type,
			'types'           => $feed_types,
			'categories'      => $categories,
			'category_filter' => $feed['category_filter'] ?? '',
			'limit'           => $feed['limit'] ?? '',
		];
		$this->template->output_template_with_variables( 'woo-gpf', 'admin-feed-edit', $vars );
	}

	/**
	 * Get a list of all categories, with pre-selected choices formatted for use as a Select2 data source.
	 *
	 * @param $selected
	 *
	 * @return array
	 */
	private function get_categories( $selected ) {
		$categories = $this->get_term_hierarchy_for_select2( 'product_cat' );
		foreach ( $categories as $idx => $category ) {
			if ( in_array( (string) $category['id'], $selected, true ) ) {
				$categories[ $idx ]['selected'] = true;
			}
		}

		return array_values( $categories );
	}

	/**
	 * @param $taxonomy
	 * @param int $parent
	 * @param string $prefix
	 *
	 * @return array
	 *
	 * @psalm-param 'product_cat' $taxonomy
	 */
	private function get_term_hierarchy_for_select2( string $taxonomy, $parent = 0, $prefix = '' ) {
		$terms    = get_terms(
			[
				'taxonomy' => $taxonomy,
				'parent'   => $parent,
			]
		);
		$children = [];
		// go through all the direct descendants of $parent, and gather their children
		foreach ( $terms as $term ) {
			// add the term to our new array
			$name = $term->name;
			if ( ! empty( $prefix ) ) {
				$name = $prefix . ' > ' . $name;
			}
			$children[ $term->term_id ] = [
				'id'   => $term->term_id,
				'text' => $name,
			];
			// recurse to add the direct descendants of "this" term
			$children += $this->get_term_hierarchy_for_select2( $taxonomy, $term->term_id, $name );
		}

		// send the results back to the caller
		return $children;
	}

	/**
	 * Update a feed record.
	 *
	 * @return int 1 if existing feed updated. 3 if new feed added.
	 */
	private function update_feed() {
		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ?? '' ), 'gpf_update_feed' ) ) {
			wp_die( 'Invalid request' );
		}
		$feed_id     = ! empty( $_POST['feed_id'] ) ? sanitize_text_field( $_POST['feed_id'] ) : null;
		$feed_config = $_POST;
		unset( $feed_config['_wpnonce'] );
		unset( $feed_config['_wp_http_referer'] );
		unset( $feed_config['save'] );
		unset( $feed_config['feed_id'] );
		$feed_config = stripslashes_deep( $feed_config );
		$this->repository->save( $feed_config, $feed_id );

		if ( ! empty( $feed_id ) ) {
			return 1;
		}

		return 3;
	}

	private function delete_ask_feed(): void {
		if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ?? '' ), 'gpf_delete_ask_feed' ) ) {
			wp_die( 'Invalid request' );
		}
		$feed_id     = sanitize_text_field( $_GET['feed_id'] );
		$feed_config = $this->repository->get( $feed_id );
		if ( ! $feed_config ) {
			wp_die( 'Invalid request' );
		}
		$all_types        = $this->configuration->get_feed_types();
		$type_description = $all_types[ $feed_config->type ]['name'] ?? $feed_config->type;
		$vars             = [
			'feed_id'     => esc_attr( $feed_id ),
			'name'        => esc_html( $feed_config->name ),
			'type'        => $type_description,
			'page_header' => sprintf(
			// Translators: %s is the feed "name".
				__( 'Delete %s', 'woocommerce_gpf' ),
				esc_html( $feed_config->name )
			),
		];
		$this->template->output_template_with_variables( 'woo-gpf', 'admin-feed-delete-ask', $vars );
	}

	private function delete_feed(): void {
		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ?? '' ), 'gpf_delete_feed' ) ) {
			wp_die( 'Invalid request' );
		}
		$feed_id     = sanitize_text_field( $_POST['feed_id'] );
		$feed_config = $this->repository->get( $feed_id );
		if ( ! $feed_config ) {
			wp_die( 'Invalid request' );
		}
		$this->repository->delete( $feed_id );
	}
}
