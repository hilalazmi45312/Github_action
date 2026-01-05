<?php

class BWFAN_Yith_Wishlist_Items extends Merge_Tag_Abstract_Product_Display {

	private static $instance = null;
	public $supports_order_table = true;

	public function __construct() {
		$this->tag_name        = 'yith_wishlist_items';
		$this->tag_description = __( 'Wishlist Items', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_yith_wishlist_items', array( $this, 'parse_shortcode' ) );
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

		global $wpdb;

		$query = $wpdb->prepare( "SELECT `prod_id`, `quantity` FROM {$wpdb->prefix}yith_wcwl WHERE `wishlist_id` = %d", $wishlist_id );

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$items = $wpdb->get_results( $query, ARRAY_A );
		if ( empty( $items ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$products = [];
		foreach ( $items as $item ) {
			$product = wc_get_product( $item['prod_id'] );
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$products[]                                    = $product;
			$this->products_quantity[ $product->get_id() ] = $item['quantity'];
		}
		if ( empty( $products ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$this->products = $products;
		$output         = $this->process_shortcode( $attr );

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
if ( bwfan_is_woocommerce_active() && function_exists( 'bwfan_is_yith_wishlist_active' ) && bwfan_is_yith_wishlist_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'yith_wishlist', 'BWFAN_Yith_Wishlist_Items', null, __( 'Yith Wishlist', 'wp-marketing-automations-pro' ) );
}
