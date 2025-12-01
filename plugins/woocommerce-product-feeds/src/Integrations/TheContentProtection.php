<?php

namespace Ademti\WoocommerceProductFeeds\Integrations;

use WP_Post;

/**
 * Avoid issues with various extensions that abuse the_content filter
 */
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
class TheContentProtection {

	/**
	 * @var WP_Post
	 */
	private WP_Post $original_post;

	/**
	 * Run the integration.
	 */
	public function run(): void {
		add_action( 'woocommerce_gpf_before_description_generation', [ $this, 'before_processing' ], 10, 1 );
		add_action( 'woocommerce_gpf_after_description_generation', [ $this, 'after_processing' ], 10, 0 );
	}

	/**
	 * Setup postdata before we grab info so that plugins that expect it set when the_content filter called still work.
	 *
	 * @param $specific_id int
	 */
	public function before_processing( $specific_id ): void {
		global $post;
		if ( is_null( $post ) ) {
			return;
		}
		$this->original_post = $post;
		$post                = get_post( $specific_id );
		setup_postdata( $post );
	}

	/**
	 * Restore postdata after the_content has been used.
	 *
	 * @SuppressWarnings (PHPMD.UnusedFormalParameter)
	 */
	public function after_processing(): void {
		global $post;
		if ( ! isset( $this->original_post ) ) {
			return;
		}
		$post = $this->original_post;
		wp_reset_postdata();
	}
}
