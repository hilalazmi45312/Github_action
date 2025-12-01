<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\ReviewFeeds;

use Ademti\WoocommerceProductFeeds\Cache\Cache;
use Ademti\WoocommerceProductFeeds\DTOs\FeedConfig;
use Ademti\WoocommerceProductFeeds\Traits\ClearsUserContext;
use DateInterval;
use WP_Comment;
use WP_User;
use function apply_filters;
use function count;
use function date_create_from_format;
use function get_comment_meta;
use function get_comments;
use function get_the_permalink;
use function remove_action;
use function substr;
use function trim;
use function wp_cache_flush;
use function wp_strip_all_tags;

class ReviewFeed {

	use ClearsUserContext;

	// Dependencies.
	private Cache $cache;
	private ReviewFeedGoogle $feed;
	private ReviewProductInfo $review_product_info;

	/**
	 * The type of feed being generated.
	 * @var string
	 */
	private string $feed_type = '';

	/**
	 * The feed config.
	 *
	 * @var FeedConfig
	 */
	private FeedConfig $feed_config;

	/**
	 * Used for temporary storage of user context to restore after feed generation.
	 * @var WP_User|null
	 */
	private ?WP_User $original_user;

	/**
	 * Constructor.
	 *
	 * @param Cache $cache
	 * @param ReviewFeedGoogle $review_feed_google
	 * @param ReviewProductInfo $review_product_info
	 */
	public function __construct(
		Cache $cache,
		ReviewFeedGoogle $review_feed_google,
		ReviewProductInfo $review_product_info
	) {
		$this->cache               = $cache;
		$this->review_product_info = $review_product_info;
		$this->feed                = $review_feed_google;
	}

	/**
	 * Actually handle feed generation for a given feed config.
	 *
	 * @param FeedConfig $feed_config
	 */
	public function initialise( FeedConfig $feed_config ): void {
		$this->feed_config = $feed_config;
		$this->feed_type   = $this->feed_config->type;
		add_action( 'template_redirect', [ $this, 'render_feed' ], 99 );
	}

	/**
	 * Set up WordPress for best performance rendering the feed on a variety of hosts / configs. Then
	 * invoke render_items() to fetch the data and render the feed.
	 *
	 * @SuppressWarnings(PHPMD.ErrorControlOperator)
	 *
	 * @return never
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

		// Disable WooCommerce Product Reviews Pro from excluding comments on products that are below
		// the contribution threshold.
		if ( function_exists( 'wc_product_reviews_pro' ) ) {
			$wcprp_frontend = \wc_product_reviews_pro()->get_frontend_instance();
			remove_action( 'pre_get_comments', [ $wcprp_frontend, 'handle_contributions_threshold' ], -1 );
		}

		$this->render_items();

		exit();
	}

	/**
	 * Fetch the review data, and render the data.
	 */
	public function render_items(): void {

		global $_wp_using_ext_object_cache;

		if ( $this->cache->is_enabled() ) {
			$chunk_size = 100;
		} else {
			$chunk_size = 10;
		}

		$this->clear_user_context();

		$this->feed->render_header();

		// Query for comments in chunks for memory performance reasons
		$chunk_size = apply_filters( 'woocommerce_prf_chunk_size', $chunk_size, $this->cache->is_enabled() );

		$date_query = null;
		$limit      = $this->feed_config->limit;
		if ( empty( $limit ) ) {
			$limit = -1;
		}
		switch ( $limit ) {
			case 'week':
				// Add date-based filtering.
				$today = date_create_from_format( 'Y-m-d H:i:s', current_time( 'Y-m-d' ) . ' 00:00:00' );
				$since = clone $today;
				$since->sub( new DateInterval( 'PT' . ( WEEK_IN_SECONDS + 1 ) . 'S' ) );
				$date_query = [
					[
						'after' => $since->format( 'Y-m-d H:i:s' ),
					],
					[
						'before' => $today->format( 'Y-m-d H:i:s' ),
					],
					'relation' => 'AND',
					'column'   => 'comment_date',
				];
				// Remove numeric filtering.
				$limit = -1;
				break;
			case 'yesterday':
				// Add date-based filtering.
				$today = date_create_from_format( 'Y-m-d H:i:s', current_time( 'Y-m-d' ) . ' 00:00:00' );
				$since = clone $today;
				$since->sub( new DateInterval( 'PT' . ( DAY_IN_SECONDS + 1 ) . 'S' ) );
				$date_query = [
					[
						'after' => $since->format( 'Y-m-d H:i:s' ),
					],
					[
						'before' => $today->format( 'Y-m-d H:i:s' ),
					],
					'relation' => 'AND',
					'column'   => 'comment_date',
				];
				// Remove numeric filtering.
				$limit = -1;
				break;
			default:
				$limit = (int) $limit;
				break;
		}

		// Args used to query for reviews.
		$args = [
			'status'      => 'approve',
			'post_status' => 'publish',
			'post_type'   => 'product',
			'date_query'  => $date_query,
			'number'      => $chunk_size,
			'orderby'     => 'comment_date_gmt',
			'order'       => 'ASC',
			'offset'      => (int) $this->feed_config->start,
			'meta_query'  => [
				[
					'key'     => 'rating',
					'compare' => 'exists',
				],
				[
					'key'     => '_wc_prf_no_feed',
					'compare' => 'not exists',
				],
			],
		];

		$output_count = 0;
		$reviews      = get_comments( $args );
		$review_count = count( $reviews );
		while ( $review_count ) {
			foreach ( $reviews as $review ) {
				// Skip reviews with no content.
				if ( empty( trim( wp_strip_all_tags( $review->comment_content ) ) ) ) {
					continue;
				}
				// Skip reviews with no rating.
				$review->rating = get_comment_meta( $review->comment_ID, 'rating', true );
				if ( empty( $review->rating ) ) {
					continue;
				}
				if ( $this->render_item( $review ) ) {
					++$output_count;
				}
				// Quit if we've done all the reviews
				if ( -1 !== $limit && $output_count >= $limit ) {
					break;
				}
			}
			if ( -1 !== $limit && $output_count >= $limit ) {
				break;
			}
			$args['offset'] += $chunk_size;

			// If we're using the built-in object cache then flush it every chunk so
			// that we don't keep churning through memory.
			if ( ! $_wp_using_ext_object_cache ) {
				wp_cache_flush();
			}

			$reviews      = get_comments( $args );
			$review_count = count( $reviews );
		}
		$this->feed->render_footer();
		$this->restore_user_context();
	}

	/**
	 * Render an item.
	 *
	 * @param WP_Comment $item
	 *
	 * @return bool
	 */
	private function render_item( WP_Comment $item ) {
		$feed_item                      = [];
		$feed_item['user_id']           = $item->user_id;
		$feed_item['review_id']         = $item->comment_ID;
		$feed_item['review_timestamp']  = substr( $item->comment_date_gmt, 0, 10 ) . 'T';
		$feed_item['review_timestamp'] .= substr( $item->comment_date_gmt, 11, 8 ) . 'Z';
		$feed_item['review_content']    = $item->comment_content;
		$feed_item['product_id']        = $item->comment_post_ID;
		$feed_item['product_url']       = get_the_permalink( $item->comment_post_ID );
		$feed_item['product_name']      = $item->post_title;
		$feed_item['review_rating']     = (int) $item->rating;
		$feed_item['reviewer_id']       = $item->user_id;
		$feed_item['collection_method'] = apply_filters(
			'woocommerce_gpf_review_feed_item_collection_method',
			'unsolicited',
			$item
		);
		$is_anonymous                   = empty( $item->user_id ) && empty( $item->comment_author );
		$anonymised                     = get_comment_meta( $item->comment_ID, '_wc_prf_anonymised', true );
		$is_anonymous                   = $is_anonymous || $anonymised;
		$feed_item['name_is_anonymous'] = apply_filters(
			'woocommerce_gpf_review_feed_item_is_anonymous',
			$is_anonymous
		);

		if ( $feed_item['name_is_anonymous'] ) {
			$feed_item['reviewer_name'] = '';
		} else {
			$feed_item['reviewer_name'] = $item->comment_author;
		}

		$product_info = $this->review_product_info->get_product_info( $feed_item['product_id'] );
		if ( isset( $product_info['excluded'] ) && true === $product_info['excluded'] ) {
			return false;
		}
		$feed_item = array_merge( $feed_item, $product_info );

		$feed_item = apply_filters( 'woocommerce_gpf_review_feed_item', $feed_item );
		$feed_item = apply_filters( 'woocommerce_gpf_review_feed_item_' . $this->feed_type, $feed_item );

		if ( apply_filters( 'woocommerce_gpf_review_feed_item_excluded', false, $feed_item, $item ) ) {
			return false;
		}

		return $this->feed->render_item( $feed_item );
	}

	/**
	 * Ensures that product data is generated without any user context.
	 * @return void
	 */
	private function clear_user_context() {
		$this->original_user = wp_get_current_user();
		if ( $this->original_user->ID !== 0 ) {
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Discouraged
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Restores the user context after generation.
	 * @return void
	 */
	private function restore_user_context() {
		if ( ! empty( $this->original_user ) ) {
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Discouraged
			wp_set_current_user( $this->original_user->ID );
		}
	}
}
