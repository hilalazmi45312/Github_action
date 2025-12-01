<?php

namespace Ademti\WoocommerceProductFeeds\Admin;

use Ademti\WoocommerceProductFeeds\Helpers\TemplateLoader;
use Exception;
use WC_Coupon;
use function add_action;
use function add_filter;
use function as_has_scheduled_action;
use function as_schedule_single_action;
use function html_entity_decode;
use function sanitize_html_class;
use function sanitize_text_field;
use function serialize;
use function time;
use const ENT_COMPAT;

class PromotionFeedUi {

	// Dependencies.
	private TemplateLoader $template;

	/**
	 * @param TemplateLoader $template
	 */
	public function __construct( TemplateLoader $template ) {
		$this->template = $template;
	}

	/**
	 * Add our hooks / filters.
	 *
	 * @return void
	 */
	public function initialise(): void {
		add_filter( 'woocommerce_coupon_data_tabs', [ $this, 'register_metabox_tab' ] );
		add_action( 'woocommerce_coupon_data_panels', [ $this, 'coupon_metabox' ], 10, 2 );
		add_action( 'save_post_shop_coupon', [ $this, 'save_coupon' ] );
		add_action( 'created_product_cat', [ $this, 'save_category' ], 10 );
		add_action( 'edited_product_cat', [ $this, 'save_category' ], 10 );
		add_action( 'delete_product_cat', [ $this, 'save_category' ], 10 );
		add_action( 'created_product_brand', [ $this, 'save_brand' ], 10 );
		add_action( 'edited_product_brand', [ $this, 'save_brand' ], 10 );
		add_action( 'delete_product_brand', [ $this, 'save_brand' ], 10 );
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function register_metabox_tab( array $tabs ) {
		$tabs['woocommerce_gpf'] = [
			'label'  => __( 'Promotion feed visibility', 'woocommerce_gpf' ),
			'target' => 'woocommerce-gpf',
			'class'  => 'woocommerce_gpf',
		];

		return $tabs;
	}

	/**
	 * @param $coupon_id
	 * @param WC_Coupon $coupon
	 *
	 * @return void
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function coupon_metabox( $coupon_id, WC_Coupon $coupon ): void {
		$visibility            = $coupon->get_meta( 'woocommerce_gpf_visibility', true ) ?? false;
		$promotion_destination = $coupon->get_meta( 'woocommerce_gpf_promotion_destination', true );
		$promotion_long_title  = $coupon->get_meta( 'woocommerce_gpf_promotion_long_title', true );
		if ( ! empty( $promotion_destination ) ) {
			$promotion_destination = unserialize( $promotion_destination, [ 'allowed_classes' => false ] );
		} else {
			$promotion_destination = [];
		}
		$destination_free_listings_selected     = in_array( 'Free_listings', $promotion_destination, true ) ?
			'selected' :
			'';
		$destination_shopping_ads_selected      = in_array( 'Shopping_ads', $promotion_destination, true ) ?
			'selected' :
			'';
		$destination_youtube_affiliate_selected = in_array( 'YouTube_affiliate', $promotion_destination, true ) ?
			'selected' :
			'';
		$vars                                   = [
			'yes_selected'                           => $visibility ? 'selected' : '',
			'no_selected'                            => $visibility ? '' : 'selected',
			'destination_free_listings_selected'     => $destination_free_listings_selected,
			'destination_shopping_ads_selected'      => $destination_shopping_ads_selected,
			'destination_youtube_affiliate_selected' => $destination_youtube_affiliate_selected,
			'long_title'                             => esc_attr( $promotion_long_title ),
		];
		$this->template->output_template_with_variables( 'woo-gpf', 'admin-promotions-metabox', $vars );
	}

	/**
	 * @param $coupon_id
	 *
	 * @return void
	 */
	public function save_coupon( $coupon_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Load the coupon.
		$coupon   = new WC_Coupon( $coupon_id );
		$is_dirty = false;

		// Handle updates to visibility.
		$current_visibility = $coupon->get_meta( 'woocommerce_gpf_visibility', true ) ?? '0';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$target_visibility = (string) ( ( $_POST['woocommerce_gpf_visibility'] ?? '' ) === 'yes' );
		if ( $current_visibility !== $target_visibility ) {
			$coupon->update_meta_data( 'woocommerce_gpf_visibility', $target_visibility );
			$is_dirty = true;
		}
		// Handle updates to promotion destinations.
		$current_destinations = $coupon->get_meta( 'woocommerce_gpf_promotion_destination', true );
		if ( empty( $current_destinations ) ) {
			$current_destinations = serialize( [] );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$target_destinations = $_POST['woocommerce_gpf_promotion_destination'] ?? [];
		foreach ( $target_destinations as $key => $destination ) {
			$target_destinations[ $key ] = sanitize_html_class( $destination );
		}
		$target_destinations = serialize( $target_destinations );
		if ( $current_destinations !== $target_destinations ) {
			$coupon->update_meta_data( 'woocommerce_gpf_promotion_destination', $target_destinations );
			$is_dirty = true;
		}
		// Handle updates to the long title.
		$current_title = $coupon->get_meta( 'woocommerce_gpf_promotion_long_title', true );
		$new_title     = html_entity_decode(
			sanitize_text_field( $_POST['woocommerce_gpf_promotion_long_title'] ?? '' ),
			ENT_COMPAT
		);
		if ( $new_title !== $current_title ) {
			$coupon->update_meta_data( 'woocommerce_gpf_promotion_long_title', $new_title );
			$is_dirty = true;
		}

		// If we've changed any of our values, save the coupon.
		if ( $is_dirty ) {
			$coupon->save();
		}

		// Either way, invalidate the map and trigger a job to rebuild since changes to
		// other coupon settings values may invalidate the map.
		$this->trigger_map_rebuild( 'category_map' );
		$this->trigger_map_rebuild( 'brand_map' );
	}

	/**
	 * @return void
	 */
	public function save_category(): void {
		$this->trigger_map_rebuild( 'category_map' );
	}

	/**
	 * @return void
	 */
	public function save_brand(): void {
		$this->trigger_map_rebuild( 'brand_map' );
	}

	/**
	 * Remove a map, and trigger a job to rebuild it.
	 *
	 * @param $map
	 *
	 * @return void
	 */
	private function trigger_map_rebuild( $map ) {
		delete_option( 'woocommerce_gpf_coupon_' . $map );
		$has_pending = as_has_scheduled_action(
			'woocommerce_product_feeds_refresh_coupon_' . $map,
			[],
			'woocommerce-product-feeds'
		);
		// Do not trigger if we already have a queued action.
		if ( ! $has_pending ) {
			as_schedule_single_action(
				time(),
				'woocommerce_product_feeds_refresh_coupon_' . $map,
				[],
				'woocommerce-product-feeds'
			);
		}
	}
}
