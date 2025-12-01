<?php

/**
 * Additional Fee Rules.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Additional_Fee_Rule' ) ) {

	/**
	 * EFW_Additional_Fee_Rule Class.
	 */
	class EFW_Additional_Fee_Rule extends EFW_Post {

		/**
		 * Post Type.
		 *
		 * @var string
		 */
		protected $post_type = EFW_Register_Post_Type::ADDITIONAL_FEE_RULES_POSTTYPE;

		/**
		 * Post Status.
		 *
		 * @var string
		 */
		protected $post_status = 'publish';

		/**
		 * Minimum Quantity.
		 *
		 * @var int
		 */
		protected $efw_min_cart_qty;

		/**
		 * Maximum Quantity.
		 *
		 * @var int
		 */
		protected $efw_max_cart_qty;

		/**
		 * Fee Value.
		 *
		 * @var int
		 */
		protected $efw_fee_value;

		/**
		 * Meta data keys
		 *
		 * @var array
		 */
		protected $meta_data_keys = array(
			'efw_min_cart_qty' => '',
			'efw_max_cart_qty' => '',
			'efw_fee_value' => '',
		);

		/**
		 * Set Id.
		 */
		public function set_id( $value ) {

			$this->id = $value;
		}

		/**
		 * Set Minimum Quantity.
		 */
		public function set_min_cart_qty( $value ) {

			$this->efw_min_cart_qty = $value;
		}

		/**
		 * Set Maximum Quantity.
		 */
		public function set_max_cart_qty( $value ) {

			$this->efw_max_cart_qty = $value;
		}

		/**
		 * Set Fee Value.
		 */
		public function set_fee_value( $value ) {

			$this->efw_fee_value = $value;
		}

		/**
		 * Get Id.
		 */
		public function get_id() {

			return $this->id;
		}

		/**
		 * Set Minimum Quantity.
		 */
		public function get_minimum_quantity() {
			return $this->efw_min_cart_qty;
		}

		/**
		 * Get Maximum Quantity.
		 */
		public function get_maximum_quantity() {
			return $this->efw_max_cart_qty;
		}

		/**
		 * Get Fee Value.
		 */
		public function get_fee_value() {
			return $this->efw_fee_value;
		}
	}

}
