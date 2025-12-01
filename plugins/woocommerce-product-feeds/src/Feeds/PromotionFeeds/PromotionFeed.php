<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\PromotionFeeds;

use Ademti\WoocommerceProductFeeds\DTOs\FeedConfig;
use Ademti\WoocommerceProductFeeds\Helpers\CouponRepository;
use function add_action;
use function count;
use function wp_cache_flush;

class PromotionFeed {

	// Dependencies.
	private CouponRepository $coupon_repository;
	private PromotionFeedRenderer $renderer;

	/**
	 * @param CouponRepository $coupon_repository
	 * @param PromotionFeedRenderer $renderer
	 */
	public function __construct(
		CouponRepository $coupon_repository,
		PromotionFeedRenderer $renderer
	) {
		$this->coupon_repository = $coupon_repository;
		$this->renderer          = $renderer;
	}

	/**
	 * @param FeedConfig $feed_config
	 */
	public function initialise( FeedConfig $feed_config ): void {
		$this->renderer->set_feed_config( $feed_config );
		add_action( 'template_redirect', [ $this, 'render_feed' ], 99 );
	}

	/**
	 * @return never
	 *
	 * @SuppressWarnings(PMD.ErrorControlOperator)
	 */
	public function render_feed() {
		global $wpdb;

		// Don't cache feed.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! headers_sent() ) {
			header( 'Cache-Control: no-store, must-revalidate, max-age=0' );
		}

		// Cater for large stores. Hide errors, set no time limit.
		$wpdb->hide_errors();
		@set_time_limit( 0 );
		while ( ob_get_level() ) {
			@ob_end_clean();
		}

		$this->render_items();

		exit();
	}

	/**
	 * @return void
	 */
	public function render_items() {

		global $_wp_using_ext_object_cache;

		$this->renderer->render_header();

		$chunk_size = $this->coupon_repository->get_chunk_size();
		$offset     = 0;

		$promotions      = $this->coupon_repository->get_coupons( $offset );
		$promotion_count = count( $promotions );
		while ( $promotion_count ) {
			foreach ( $promotions as $promotion ) {
				$this->renderer->render_item( $promotion );
			}
			$offset += $chunk_size;

			// If we're using the built-in object cache then flush it every chunk so
			// that we don't keep churning through memory.
			if ( ! $_wp_using_ext_object_cache ) {
				wp_cache_flush();
			}

			$promotions      = $this->coupon_repository->get_coupons( $offset );
			$promotion_count = count( $promotions );
		}

		$this->renderer->render_footer();
	}
}
