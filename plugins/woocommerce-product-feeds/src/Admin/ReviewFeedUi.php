<?php

namespace Ademti\WoocommerceProductFeeds\Admin;

use Ademti\WoocommerceProductFeeds\Helpers\TemplateLoader;
use function add_action;
use function add_filter;
use function add_meta_box;
use function delete_comment_meta;
use function get_comment_meta;
use function get_current_screen;
use function update_comment_meta;

class ReviewFeedUi {
	// Dependencies.
	protected TemplateLoader $template_loader;

	/**
	 * @param TemplateLoader $template_loader
	 */
	public function __construct( TemplateLoader $template_loader ) {
		$this->template_loader = $template_loader;
	}

	/**
	 * Registers some always used actions (Such as registering endpoints). Also checks to see
	 * if this is a feed request, and if so registers the hooks needed to generate the feed.
	 */
	public function initialise(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 30 );
		add_filter( 'comment_edit_redirect', [ $this, 'save_comment_meta' ], 1, 2 );
	}

	/**
	 * Show a metabox on the comment edit pages.
	 */
	public function add_meta_boxes(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( 'comment' === get_current_screen()->id && isset( $_GET['c'] ) ) {
			if ( ! $this->is_review_comment( (int) $_GET['c'] ) ) {
				return;
			}
			add_meta_box(
				'wc-prf-rating',
				__( 'Product Review feed settings', 'woocommerce_gpf' ),
				[ $this, 'render_meta_box' ],
				'comment',
				'normal',
				'high'
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render the metabox on the comment edit pages.
	 */
	public function render_meta_box( $comment ): void {
		$excluded   = get_comment_meta( $comment->comment_ID, '_wc_prf_no_feed', true );
		$anonymised = get_comment_meta( $comment->comment_ID, '_wc_prf_anonymised', true );
		$this->template_loader->output_template_with_variables(
			'woo-gpf-admin',
			'review-metabox',
			[
				'excluded_checked'   => $excluded ? 'checked="checked"' : '',
				'anonymised_checked' => $anonymised ? 'checked="checked"' : '',
			]
		);
	}

	/**
	 * Save the metabox info on the comment edit pages.
	 *
	 * @param string $location
	 * @param int $comment_id
	 *
	 * @return string
	 */
	public function save_comment_meta( string $location, int $comment_id ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$excluded = isset( $_POST['_wc_prf_no_feed'] ) ? ( 'on' === $_POST['_wc_prf_no_feed'] ) : 0;
		if ( $excluded ) {
			update_comment_meta( $comment_id, '_wc_prf_no_feed', $excluded );
		} else {
			delete_comment_meta( $comment_id, '_wc_prf_no_feed' );
		}
		$anonymised = isset( $_POST['_wc_prf_anonymised'] ) ? ( 'on' === $_POST['_wc_prf_anonymised'] ) : 0;
		if ( $anonymised ) {
			update_comment_meta( $comment_id, '_wc_prf_anonymised', $anonymised );
		} else {
			delete_comment_meta( $comment_id, '_wc_prf_anonymised' );
		}

		return $location;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * @param $comment_id
	 *
	 * @return bool
	 */
	private function is_review_comment( int $comment_id ): bool {
		$meta = get_comment_meta( $comment_id, 'rating', true );

		return is_numeric( $meta );
	}
}
