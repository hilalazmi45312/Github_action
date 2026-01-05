<?php

class BWFAN_Ti_Wc_Wishlist_Items extends Merge_Tag_Abstract_Product_Display {
	protected $support_v2 = true;
	protected $support_v1 = false;
	private static $instance = null;
	public $supports_order_table = true;

	public function __construct() {
		$this->tag_name        = 'ti_wc_wishlist_items';
		$this->tag_description = __( 'Wishlist Items', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ti_wc_wishlist_items', array( $this, 'parse_shortcode' ) );
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
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$wishlist_id = BWFAN_Merge_Tag_Loader::get_data( 'wishlist_id' );
		if ( empty( $wishlist_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$wishlist_data = BWFAN_PRO_Common::get_ti_wc_wishlist_data_by_id( $wishlist_id );
		if ( ! $wishlist_data ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$product_ids = isset( $wishlist_data['product_ids'] ) ? $wishlist_data['product_ids'] : [];
		if ( empty( $product_ids ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$products = [];
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$products[] = $product;
		}
		if ( empty( $products ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$this->products = $products;

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
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_ti_wc_wishlist_active' ) && bwfan_is_ti_wc_wishlist_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'ti_wc_wishlist', 'BWFAN_Ti_Wc_Wishlist_Items', null, __( 'TI WooCommerce Wishlist', 'wp-marketing-automations-pro' ) );
}
