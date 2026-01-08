<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Snapchat Advanced Matching
 *
 * @since 1.40.2
 */
class Snapchat_Advanced_Matching extends Opportunity {

	public static function available() {

		// Snapchat must be enabled
		if (!Options::is_snapchat_active()) {
			return false;
		}

		// Snapchat Advanced Matching must not be disabled
		if (Options::is_snapchat_advanced_matching_enabled()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return array(
			'id'              => 'snapchat-advanced-matching',
			'title'           => esc_html__(
				'Snapchat Advanced Matching',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => array(
				esc_html__(
					'The Pixel Manager detected that the Snapchat pixel is active on your site, but Advanced Matching is not enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Snapchat Advanced Matching will improve the accuracy of your Snapchat pixel data by sending hashed customer data to Snapchat.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			),
			'impact'          => esc_html__(
				'medium',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
//          'setup_link'      => Documentation::get_link('snapchat_advanced_matching'),
//          'learn_more_link' => Documentation::get_link('opportunity_snapchat_advanced_matching'),
			'since'           => 1710570765, // timestamp
		);
	}
}
