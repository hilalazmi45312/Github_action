<?php

/**
 * Fees.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Fee' ) ) {

	/**
	 * EFW_Fee Class.
	 */
	class EFW_Fee extends EFW_Post {

		/**
		 * Post Type.
		 */
		protected $post_type = EFW_Register_Post_Type::FEES_POSTTYPE ;

		/**
		 * Post Status.
		 */
		protected $post_status = 'publish' ;

		/**
		 * User Ids.
		 */
		protected $efw_user_id ;
				
				/**
		 * Product Fee.
		 */
		protected $efw_product_fee ;
				
				/**
		 * Gateway Fee.
		 */
		protected $efw_gateway_fee ;
				
				/**
		 * Shipping Fee.
		 */
				protected $efw_shipping_fee;
				
				/**
		 * Order Fee.
		 */
		protected $efw_order_fee ;
				
				/**
		 * Order Id.
		 */
		protected $efw_order_id ;
				
						/**
		 * Order Id.
		 */
		protected $efw_fee_type ;

		/**
		 * Meta data keys
		 */
		protected $meta_data_keys = array(
			'efw_user_id'          => '',
			'efw_product_fee'      => 0,
			'efw_gateway_fee'      => 0,
			'efw_shipping_fee'     => 0,
			'efw_order_fee'        => 0,
			'efw_order_id'         => 0,
			'efw_fee_type'         => '',
			);

		/**
		 * Prepare extra post data
		 */
		protected function load_extra_postdata() {
			$this->efw_user_id = $this->post->post_parent ;
		}

		/**
		 * Set Id.
		 */
		public function set_id( $value ) {

			$this->id = $value ;
		}
				
				/**
		 * Set Product Fee.
		 */
		public function set_product_fee( $value ) {

			$this->efw_product_fee = $value ;
		}
				
				/**
		 * Set Gateway Fee.
		 */
		public function set_gateway_fee( $value ) {

			$this->efw_gateway_fee = $value ;
		}
				
				/**
		 * Set Shipping Fee.
		 */
		public function set_shipping_fee( $value ) {

			$this->efw_shipping_fee = $value ;
		}
				
				/**
		 * Set Order Fee.
		 */
		public function set_order_fee( $value ) {

			$this->efw_order_fee = $value ;
		}
				
				/**
		 * Set Order Id.
		 */
		public function set_order_id( $value ) {

			$this->efw_order_id = $value ;
		}
				
						/**
		 * Set Fee Type.
		 */
		public function set_fee_type( $value ) {

			$this->efw_fee_type = $value ;
		}

		/**
		 * Get Id.
		 */
		public function get_id() {

			return $this->id ;
		}
				
				/**
		 * Get Product Fee.
		 */
		public function get_product_fee() {

			return $this->efw_product_fee ;
		}
				
				/**
		 * Get Gateway Fee.
		 */
		public function get_gateway_fee() {

			return $this->efw_gateway_fee ;
		}
				
				/**
		 * Get Shipping Fee.
		 */
		public function get_shipping_fee() {
				return $this->efw_shipping_fee ;
		}
				
				/**
		 * Get Order Fee.
		 */
		public function get_order_fee() {

			return $this->efw_order_fee ;
		}
				
				/**
		 * Get Order Id.
		 */
		public function get_order_id() {

			return $this->efw_order_id ;
		}
				
						/**
		 * Get Fee Type.
		 */
		public function get_fee_type() {

			return $this->efw_fee_type ;
		}
	}

}
