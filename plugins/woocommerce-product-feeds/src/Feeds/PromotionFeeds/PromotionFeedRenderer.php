<?php

namespace Ademti\WoocommerceProductFeeds\Feeds\PromotionFeeds;

use Ademti\WoocommerceProductFeeds\Configuration\Configuration;
use Ademti\WoocommerceProductFeeds\DTOs\FeedConfig;
use Ademti\WoocommerceProductFeeds\DTOs\PromotionFeedItem;
use Ademti\WoocommerceProductFeeds\DTOs\StoreInfo;
use Ademti\WoocommerceProductFeeds\Helpers\DebugService;
use Ademti\WoocommerceProductFeeds\Helpers\TemplateLoader;
use Ademti\WoocommerceProductFeeds\Traits\FormatsFeedOutput;
use WC_Coupon;

class PromotionFeedRenderer {

	use FormatsFeedOutput;

	// Dependencies.
	private TemplateLoader $template_loader;
	private Configuration $configuration;
	private DebugService $debug;
	private StoreInfo $store_info;

	private FeedConfig $feed_config;

	/**
	 * @param TemplateLoader $template_loader
	 * @param Configuration $configuration
	 * @param DebugService $debug
	 * @param StoreInfo $store_info
	 */
	public function __construct(
		TemplateLoader $template_loader,
		Configuration $configuration,
		DebugService $debug,
		StoreInfo $store_info
	) {
		$this->template_loader = $template_loader;
		$this->configuration   = $configuration;
		$this->debug           = $debug;
		$this->store_info      = $store_info;
	}

	/**
	 * @param FeedConfig $feed_config
	 *
	 * @return void
	 */
	public function set_feed_config( FeedConfig $feed_config ) {
		$this->feed_config = $feed_config;
	}

	/**
	 * @return void
	 */
	public function render_header(): void {
		header( 'Content-Type: application/xml; charset=UTF-8' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['feeddownload'] ) ) {
			header( 'Content-Disposition: attachment; filename="woocommerce-promotions.xml"' );
		} else {
			header( 'Content-Disposition: inline; filename="woocommerce-promotions.xml"' );
		}
		$variables = [
			'store_name' => $this->esc_xml( $this->store_info->blog_name ),
			'store_url'  => $this->esc_xml( $this->store_info->site_url ),
			'version'    => WOOCOMMERCE_GPF_VERSION,
			'feed_url'   => $this->store_info->feed_url_base . $this->feed_config->id,
		];

		$this->template_loader->output_template_with_variables(
			'woo-gpf',
			'promotions-xml-header',
			$variables
		);
	}

	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public function render_item( $item ): bool {
		$coupon = new WC_Coupon( $item->ID );
		if ( ! $coupon ) {
			return false;
		}
		$coupon_feed_item = new PromotionFeedItem( $coupon, $this->store_info );
		if ( ! $coupon_feed_item->is_eligible() ) {
			return false;
		}

		echo '<item>';
		$this->render_element( 'g:promotion_id', $coupon_feed_item->get_promotion_id() );
		$this->render_element( 'g:generic_redemption_code', $coupon_feed_item->get_generic_redemption_code() );
		$this->render_element( 'g:coupon_value_type', $coupon_feed_item->get_coupon_value_type() );
		$this->render_element( 'g:offer_type', $coupon_feed_item->get_offer_type() );

		$this->render_element( 'g:description', $coupon_feed_item->get_description() );
		foreach ( $coupon_feed_item->get_item_ids() as $item_id ) {
			$this->render_element( 'g:item_id', $item_id );
		}
		foreach ( $coupon_feed_item->get_item_id_exclusions() as $item_id ) {
			$this->render_element( 'g:item_id_exclusion', $item_id );
		}
		$this->render_element( 'g:limit_quantity', $coupon_feed_item->get_limit_quantity() );
		$this->render_element( 'g:limit_value', $coupon_feed_item->get_limit_value() );
		$this->render_element( 'g:long_title', $coupon_feed_item->get_long_title() );
		$this->render_element( 'g:minimum_purchase_amount', $coupon_feed_item->get_minimum_purchase_amount() );
		$this->render_element( 'g:money_off_amount', $coupon_feed_item->get_money_off_amount() );
		$this->render_element( 'g:percent_off', $coupon_feed_item->get_percent_off() );
		$this->render_element( 'g:product_applicability', $coupon_feed_item->get_product_applicability() );
		$this->render_element( 'g:promotion_effective_dates', $coupon_feed_item->get_promotion_effective_dates() );
		$promotion_destinations = $coupon_feed_item->get_promotion_destination();
		foreach ( $promotion_destinations as $promotion_destination ) {
			$this->render_element( 'promotion_destination', $promotion_destination );
		}
		echo '</item>';

		return true;
	}

	/**
	 * @return void
	 */
	public function render_footer(): void {
		$this->template_loader->output_template_with_variables(
			'woo-gpf',
			'promotions-xml-footer'
		);
	}

	/**
	 * @return void
	 */
	private function render_element( string $tag, $value ) {
		if ( empty( $value ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<' . $tag . '>';
		echo $this->esc_xml( $value );
		echo '</' . $tag . '>' . PHP_EOL;
		// phpcs:enable
	}
}
