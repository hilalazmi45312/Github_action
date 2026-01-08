<?php

namespace SweetCode\Pixel_Manager\Admin\Opportunities\Free;

use SweetCode\Pixel_Manager\Admin\Documentation;
use SweetCode\Pixel_Manager\Admin\Opportunities\Opportunity;
use SweetCode\Pixel_Manager\Options;

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Opportunity: Google Ads Conversion Adjustments
 *
 * @since 1.28.0
 */
class Google_Ads_Conversion_Adjustments extends Opportunity {

	public static function available() {

		// Google Ads purchase conversion must be enabled
		if (!Options::is_google_ads_purchase_conversion_enabled()) {
			return false;
		}

		// Conversion Adjustments conversions must be disabled
		if (Options::is_google_ads_conversion_adjustments_active()) {
			return false;
		}

		return true;
	}

	public static function card_data() {

		return array(
			'id'              => 'google-ads-conversion-adjustments',
			'title'           => esc_html__(
				'Google Ads Conversion Adjustments',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'description'     => array(
				esc_html__(
					'The Pixel Manager detected that Google Ads purchase conversion is enabled, but Google Ads Conversion Adjustments has yet to be enabled.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
				esc_html__(
					'Enabling Google Ads Conversion Adjustments will improve conversion value accuracy by adjusting existing conversion values after processing refunds and cancellations.',
					'woocommerce-google-adwords-conversion-tracking-tag'
				),
			),
			'impact'          => esc_html__(
				'high',
				'woocommerce-google-adwords-conversion-tracking-tag'
			),
			'setup_link'      => Documentation::get_link('google_ads_conversion_adjustments'),
			'learn_more_link' => Documentation::get_link('opportunity_google_ads_conversion_adjustments'),
			'since'           => 1672895375, // timestamp
		);
	}
}
