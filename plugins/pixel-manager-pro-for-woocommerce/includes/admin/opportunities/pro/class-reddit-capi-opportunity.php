<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Reddit CAPI
 *
 * @since 1.52.0
 */
class Reddit_CAPI extends Opportunity {

	public static function available() {

		// Reddit Pixel must be enabled
		if (!Options::is_reddit_active()) {
			return false;
		}

		// Reddit CAPI must be disabled
		if (Options::is_reddit_capi_active()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'          => 'reddit-capi',
			'title'       => esc_html__(
				'Reddit Conversions API',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description' => [
				esc_html__(
					'The Pixel Manager detected that the Reddit Pixel is enabled, but Reddit Conversions API has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Reddit Conversions API will improve conversion tracking accuracy and help you track more conversions that otherwise might get lost due to ad blockers or browser restrictions.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'      => esc_html__(
				'high',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'  => Documentation::get_link('reddit_capi_token'),
			//			'learn_more_link' => Documentation::get_link('opportunity_reddit_capi'),
			'since'       => time(), // timestamp
		];
	}
}
