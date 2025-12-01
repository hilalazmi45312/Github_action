<?php

namespace Ademti\WoocommerceProductFeeds\DTOs;

use DateInterval;
use Exception;
use RuntimeException;
use Throwable;
use WC_Coupon;
use function add_filter;
use function apply_filters;
use function array_map;
use function remove_filter;
use function sprintf;
use function substr;
use function wp_strip_all_tags;

class PromotionFeedItem {

	/**
	 * @var WC_Coupon
	 */
	private WC_Coupon $coupon;

	/**
	 * Props as per Google's specification: https://support.google.com/merchants/answer/2906014
	 */
	private array $props = [
		'coupon_value_type'            => '',
		'description'                  => '',

		'generic_redemption_code'      => '',
		'limit_value'                  => '',
		'limit_quantity'               => '',
		'long_title'                   => '',
		'minimum_purchase_amount'      => '',
		'money_off_amount'             => '',
		'offer_type'                   => '',
		'percent_off'                  => '',
		'product_applicability'        => '',
		'promotion_effective_dates'    => '',
		'promotion_id'                 => '',

		'item_ids'                     => [],
		'item_id_exclusions'           => [],
		'product_types'                => [],
		'product_type_exclusions'      => [],
		'product_brands'               => [],
		'product_brand_exclusions'     => [],
		'promotion_destination'        => [],

		// @TODO - could be implement with datetime-local controls on the edit coupon page.
		'promotion_display_dates'      => '',

		// @TODO - potentially relevant for other "coupon" plugins.
		// Not currently relevant/supported for standard coupons.
		'buy_this_quantity'            => '',
		'get_this_quantity_discounted' => '',
		'free_gift_description'        => '',
		'free_gift_value'              => '',
		'free_shipping'                => '',
	];

	/**
	 * @var StoreInfo
	 */
	private StoreInfo $store_info;

	/**
	 * @param WC_Coupon $coupon
	 * @param StoreInfo $store_info
	 */
	public function __construct( WC_Coupon $coupon, StoreInfo $store_info ) {
		// Store passed data.
		$this->coupon     = $coupon;
		$this->store_info = $store_info;

		$this->build_props();
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	private function build_props() {
		// Core props
		$this->set_prop( 'promotion_id', 'woocommerce-promotion-' . $this->coupon->get_id() );
		$this->set_prop( 'description', $this->coupon->get_description() );
		$this->set_prop( 'generic_redemption_code', $this->coupon->get_code() );
		$this->set_prop( 'offer_type', 'generic_code' );
		$this->set_prop( 'promotion_effective_dates', $this->calculate_promotion_effective_dates() );
		if ( ! empty( $this->coupon->get_minimum_amount() ) ) {
			$this->set_prop( 'minimum_purchase_amount', $this->with_currency_string( $this->coupon->get_minimum_amount() ) );
		}
		if ( ! empty( $this->coupon->get_maximum_amount() ) ) {
			$this->set_prop( 'limit_value', $this->with_currency_string( $this->coupon->get_maximum_amount() ) );
		}
		if ( ! empty( $this->coupon->get_limit_usage_to_x_items() ) ) {
			$this->set_prop( 'limit_quantity', $this->coupon->get_limit_usage_to_x_items() );
		}

		// Type-specific props
		$this->set_prop( 'coupon_value_type', $this->calculate_coupon_value_type() );
		if ( $this->props['coupon_value_type'] === 'percent_off' ) {
			$this->set_prop( 'percent_off', floor( $this->coupon->get_amount() ) );
		} elseif ( $this->props['coupon_value_type'] === 'money_off' ) {
			if ( $this->coupon->get_amount() > 0.01 ) {
				$this->set_prop( 'money_off_amount', $this->with_currency_string( $this->coupon->get_amount() ) );
			}
		}

		// Eligibility
		$this->set_prop( 'item_ids', $this->calculate_item_ids() );
		$this->set_prop( 'item_id_exclusions', $this->calculate_item_id_exclusions() );
		$this->set_prop( 'product_types', $this->coupon->get_product_categories() );
		$this->set_prop( 'product_type_exclusions', $this->coupon->get_excluded_product_categories() );
		if ( is_callable( [ '\WC_Brands_Brand_Settings_Manager', 'get_brand_settings_on_coupon' ] ) ) {
			$product_brands = $this->coupon->get_meta( 'product_brands' );
			$this->set_prop( 'product_brands', $product_brands !== '' ? $product_brands : [] );
			$product_brand_exclusions = $this->coupon->get_meta( 'exclude_product_brands' );
			$this->set_prop( 'product_brand_exclusions', $product_brand_exclusions !== '' ? $product_brand_exclusions : [] );
		}

		if ( empty( $this->props['item_ids'] ) &&
			empty( $this->props['item_id_exclusions'] ) &&
			empty( $this->props['product_types'] ) &&
			empty( $this->props['product_type_exclusions'] ) &&
			empty( $this->props['product_brands'] ) &&
			empty( $this->props['product_brand_exclusions'] ) ) {
			$this->set_prop( 'product_applicability', 'all_products' );
		} else {
			$this->set_prop( 'product_applicability', 'specific_products' );
		}

		// Title is dependent on, and must be calculated after percent_off, money_off_amount,
		// coupon_value_type and product_applicability
		$this->set_prop( 'long_title', $this->calculate_title() );

		$promotion_destinations = $this->coupon->get_meta( 'woocommerce_gpf_promotion_destination', true );
		if ( ! empty( $promotion_destinations ) ) {
			$this->set_prop(
				'promotion_destination',
				unserialize( $promotion_destinations, [ 'allowed_classes' => false ] )
			);
		}
	}

	/**
	 * Work out whether the coupon can be submitted.
	 *
	 * Checks:
	 *   - That we've managed to map the coupon onto one of Google's supported types
	 *   - That the coupon is not restricted to only specific customers
	 *   - That the coupon does not exclude sale items - FIXME - check.
	 *
	 * @return bool
	 */
	public function is_eligible(): bool {
		return ! empty( $this->props['coupon_value_type'] ) &&
				! empty( $this->props['generic_redemption_code'] ) &&
				count( $this->props['item_ids'] ) < 1000 &&
				count( $this->props['item_id_exclusions'] ) < 1000 &&
				empty( $this->coupon->get_email_restrictions() ) &&
				empty( $this->coupon->get_exclude_sale_items() ) &&
				! empty( $this->props['promotion_effective_dates'] );
	}

	/**
	 * Implement magic get_{$prop} methods.
	 *
	 * @param $name
	 * @param $args
	 *
	 * @return mixed|string
	 */
	public function __call( $name, $args ) {
		if ( substr( $name, 0, 4 ) !== 'get_' || count( $args ) ) {
			// phpcs:ignore
			throw new RuntimeException( esc_html( 'Invalid __call() access on ' . __CLASS__ . ': ' . $name ) );
		}
		$prop = substr( $name, 4 );
		if ( isset( $this->props[ $prop ] ) ) {
			return $this->props[ $prop ];
		}
		// phpcs:ignore
		throw new RuntimeException( esc_html( 'Invalid prop access on ' . __CLASS__ . ': ' . $prop ) );
	}

	/**
	 * Set properties, passing them through a filter first.
	 *
	 * @param $prop
	 * @param array|float|string $value
	 *
	 * @return void
	 */
	private function set_prop( string $prop, $value ): void {
		$this->props[ $prop ] = apply_filters( 'woocommerce_gpf_promotion_prop_' . $prop, $value, $this->coupon );
	}

	/**
	 * @return string
	 */
	private function calculate_coupon_value_type() {
		// If the coupon has no value, and has free shipping, set the coupon type to free shipping.
		if ( ! $this->coupon->get_amount() && $this->coupon->get_free_shipping() ) {
			return 'free_delivery_standard';
		}
		// Otherwise map the coupon type.
		switch ( $this->coupon->get_discount_type() ) {
			case 'percent':
				return 'percent_off';
				break;
			case 'fixed_cart':
			case 'fixed_product':
				return 'money_off';
				break;
			default:
				return '';
		}
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private function calculate_title() {
		// First, look and see if a custom title has been set.
		$custom_title = $this->coupon->get_meta( 'woocommerce_gpf_promotion_long_title', true );
		if ( ! empty( $custom_title ) ) {
			return apply_filters( 'woocommerce_gpf_promotion_long_title', $custom_title, $this->coupon );
		}
		// Try and calculate a useful default title based on the promotion configuration.
		try {
			switch ( $this->props['coupon_value_type'] ) {
				case 'free_delivery_standard':
					$title = __( 'Free delivery', 'woocommerce_gpf' );
					break;
				case 'percent_off':
					$title = sprintf(
					// Translators: %1$d is a percentage amount.
						__( '%1$d%% off', 'woocommerce_gpf' ),
						$this->props['percent_off']
					);
					break;
				case 'money_off':
					add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
					$price = wp_strip_all_tags( wc_price( $this->coupon->get_amount() ) );
					remove_filter( 'woocommerce_price_trim_zeros', '__return_true' );

					$title = sprintf(
					// Translators: %1$s is a price amount.
						__( '%1$s off', 'woocommerce_gpf' ),
						$price
					);
					break;
				default:
					$title = '';
			}
			if ( empty( $title ) ) {
				return apply_filters( 'woocommerce_gpf_promotion_long_title', '', $this->coupon );
			}
			if ( $this->props['product_applicability'] === 'specific_products' ) {
				$title .= _x( ' specific products', 'Suffix added to promotion titles', 'woocommerce_gpf' );
			}
			return apply_filters( 'woocommerce_gpf_promotion_long_title', $title, $this->coupon );
		} catch ( Throwable $t ) {
			return apply_filters( 'woocommerce_gpf_promotion_long_title', '', $this->coupon );
		}
	}

	/**
	 * @return string
	 */
	private function calculate_promotion_effective_dates(): string {
		// Coupon creation time to coupon expiry time.
		$start = $this->coupon->get_date_created();
		if ( $start === null ) {
			return '';
		}
		$end = $this->coupon->get_date_expires();
		// If no end date specified, assume 6 month duration from start.
		if ( ! $end ) {
			$end = clone $start;
			$end = $end->add( new DateInterval( 'P180D' ) );
		}

		// Format for output.
		return $start->date( 'c' ) . '/' . $end->date( 'c' );
	}

	/**
	 * Add the currency string after a monetary amount.
	 *
	 * @param float $amount
	 *
	 * @return string
	 */
	private function with_currency_string( float $amount ): string {
		return $amount . ' ' . $this->store_info->currency;
	}

	/**
	 * @return array
	 */
	private function calculate_item_ids() {
		$item_ids = $this->coupon->get_product_ids();

		return array_map(
			[ $this, 'map_product_feed_item_id' ],
			$item_ids
		);
	}

	/**
	 * @return array
	 */
	private function calculate_item_id_exclusions() {
		$item_ids = $this->coupon->get_excluded_product_ids();

		return array_map(
			[ $this, 'map_product_feed_item_id' ],
			$item_ids
		);
	}

	/**
	 * @return string
	 * @SuppressWarnings(PMD.UnusedPrivateMethod)
	 */
	private function map_product_feed_item_id( $item_id ) {
		return apply_filters(
			'woocommerce_gpf_promotion_prop_product_feed_item_id',
			'woocommerce_gpf_' . $item_id,
			$item_id
		);
	}
}
