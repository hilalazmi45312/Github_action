<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Pinterest Advanced Matching
 *
 * @since 1.53.0
 */
class Pinterest_Advanced_Matching extends Opportunity {

	public static function available() {

		// Pinterest must be active
		if (!Options::is_pinterest_active()) {
			return false;
		}

		// Advanced Matching must be disabled
		if (Options::is_pinterest_advanced_matching_active()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return [
			'id'              => 'pinterest-advanced-matching',
			'title'           => esc_html__(
				'Pinterest Advanced Matching',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => [
				esc_html__(
					'Pinterest is enabled but Advanced Matching is not active.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Advanced Matching sends hashed customer data (e.g. email) to Pinterest, improving conversion attribution and audience matching accuracy, especially when cookies are blocked or users switch devices.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			],
			'impact'          => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('pinterest_advanced_matching'),
			'learn_more_link' => 'https://help.pinterest.com/en/business/article/enhanced-match',
			'since'           => 1733529600, // timestamp: December 7, 2025
		];
	}
}
