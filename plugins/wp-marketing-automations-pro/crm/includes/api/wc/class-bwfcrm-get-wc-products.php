<?php

class BWFCRM_Api_Get_WC_Products extends BWFCRM_API_Base {

	public static $ins;
	public $products = array();

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/products/';
		$this->request_args = array(
			'search' => array(
				'description' => __( 'Search from name', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
		);

	}

	public function process_api_call() {
		$search = ! empty( $this->get_sanitized_arg( 'search', 'text_field' ) ) ? $this->get_sanitized_arg( 'search', 'text_field' ) : '';
		$ids    = BWFAN_Common::search_products( $search, true );

		if ( empty( $ids ) ) {
			return $this->success_response( [] );
		}

		/**
		 * Products types that are allowed in the offers
		 */
		$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_editable' );
		$products        = array();
		foreach ( $product_objects as $product_object ) {
			if ( ! $product_object instanceof WC_Product ) {
				continue;
			}

			if ( 'pending' === $product_object->get_status() ) {
				continue;
			}

			$image_url       = wp_get_attachment_image_src( get_post_thumbnail_id( $product_object->get_id() ), 'thumbnail', true );
			$image_url       = isset( $image_url[0] ) ? $image_url[0] : wc_placeholder_img_src();
			$image_file_name = basename( $image_url );
			$products[]      = array(
				'id'    => $product_object->get_id(),
				'name'  => strip_tags( rawurldecode( BWFAN_Common::get_formatted_product_name( $product_object ) ) ),
				"image" => array( "src" => $image_url, "name" => $image_file_name ),
			);
		}

		$this->response_code = 200;

		return $this->success_response( $products );
	}

}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_WC_Products' );