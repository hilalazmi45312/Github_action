<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Reddit Advanced Matching
 *
 * @since 1.53.0
 */
class Reddit_Advanced_Matching extends Opportunity {

	public static function available() {

		// Reddit must be active
		if (!Options::is_reddit_active()) {
			return false;
		}

		// Advanced Matching must be disabled
		if (Options::is_reddit_advanced_matching_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return array(
			'id'              => 'reddit-advanced-matching',
			'title'           => esc_html__(
				'Reddit Advanced Matching',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => array(
				esc_html__(
					'Reddit Pixel is enabled but Advanced Matching is not active.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Advanced Matching sends hashed customer data (e.g. email) to Reddit, improving conversion attribution and audience matching, especially when cookies are blocked or users switch devices.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			),
			'impact'          => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('reddit_advanced_matching'),
			'learn_more_link' => 'https://business.reddithelp.com/s/article/about-advanced-matching',
			'since'           => 1733529600, // timestamp: December 7, 2025
		);
	}
}
