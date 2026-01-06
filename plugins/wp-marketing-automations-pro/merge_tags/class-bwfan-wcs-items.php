<?php

class BWFAN_WCS_Items extends Merge_Tag_Abstract_Product_Display {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'subscription_items';
		$this->tag_description = __( 'Subscription Items', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_subscription_items', array( $this, 'parse_shortcode' ) );
		$this->priority = 10;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Parse the merge tag and return its value.
	 *
	 * @param $attr
	 *
	 * @return mixed|string|void
	 */
	public function parse_shortcode( $attr ) {
		if ( false === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			$data = BWFAN_Merge_Tag_Loader::get_data();
			if ( ! is_array( $data ) || empty( $data ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}
			$subscription_id = isset( $data['wc_subscription_id'] ) ? $data['wc_subscription_id'] : '';

			if ( ! function_exists( 'wcs_get_subscription' ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}
			$subscription = wcs_get_subscription( $subscription_id );

			/* setting subscription object in order for order table view */
			$this->order = $subscription;

			if ( ! $subscription instanceof WC_Subscription ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			$items    = $subscription->get_items();
			$products = [];
			foreach ( $items as $item ) {
				$product                                       = $item->get_product();
				$products[]                                    = $product;
				$this->products_quantity[ $product->get_id() ] = $item->get_quantity();
			}

			$this->products = $products;
		}
		$output = $this->process_shortcode( $attr );

		return $this->parse_shortcode_output( $output, $attr );
	}

	/**
	 * Return mergetag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		$options = [
			[
				'value' => '',
				'label' => __( 'Product Grid - 2 Column', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'product-grid-3-col',
				'label' => __( 'Product Grid - 3 Column', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'product-rows',
				'label' => __( 'Product Rows', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'review-rows',
				'label' => __( 'Product Rows (With Review Button)', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'order-table',
				'label' => __( 'WooCommerce Order Summary Layout', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'list-comma-separated',
				'label' => __( 'List - Comma Separated (Product Names only)', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'list-comma-separated-with-quantity',
				'label' => __( 'List - Comma Separated (Product Names with Quantity)', 'wp-marketing-automations-pro' ),
			]
		];

		return [
			[
				'id'          => 'template',
				'type'        => 'select',
				'options'     => $options,
				'label'       => __( 'Select Template', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Product Grid - 2 Column', 'wp-marketing-automations-pro' ),
				"required"    => false,
				"description" => ""
			],
		];
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_subscription', 'BWFAN_WCS_Items' );
}
