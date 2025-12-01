<?php

/**
 * Rules.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Rule' ) ) {

	/**
	 * EFW_Rule Class.
	 */
	class EFW_Rule extends EFW_Post {

		/**
		 * Post Type.
		 *
		 * @var string
		 */
		protected $post_type = EFW_Register_Post_Type::FEE_RULES_POSTTYPE;

		/**
		 * Post Status.
		 *
		 * @var string
		 */
		protected $post_status = 'publish';

		/**
		 * Fee Name.
		 *
		 * @var string
		 */
		protected $efw_name;

		/**
		 * Fee Text.
		 *
		 * @var string
		 */
		protected $efw_fee_text;

		/**
		 * Fee Description.
		 *
		 * @var string
		 */
		protected $efw_fee_description;

		/**
		 * Fee Type.
		 *
		 * @var string
		 */
		protected $efw_fee_type;

		/**
		 * Fixed Fee.
		 *
		 * @var string
		 */
		protected $efw_fixed_fee;

		/**
		 * Percentage Fee.
		 *
		 * @var string
		 */
		protected $efw_percent_fee;

		/**
		 * From Date.
		 *
		 * @var string
		 */
		protected $efw_from_date;

		/**
		 * To Date.
		 *
		 * @var string
		 */
		protected $efw_to_date;

		/**
		 * Minimum Quantity.
		 *
		 * @var int
		 */
		protected $efw_minimum_qty;

		/**
		 * Maximum Quantity.
		 *
		 * @var int
		 */
		protected $efw_maximum_qty;

		/**
		 * Settings Level.
		 *
		 * @var int
		 */
		protected $efw_settings_level;

		/**
		 * Meta data keys
		 *
		 * @var array
		 */
		protected $meta_data_keys = array(
			'efw_name'            => '',
			'efw_fee_text'        => '',
			'efw_fee_description' => '',
			'efw_fee_type'        => '1',
			'efw_fixed_fee'       => '',
			'efw_percent_fee'     => '',
			'efw_from_date'       => '',
			'efw_to_date'         => '',
			'efw_minimum_qty'     => '',
			'efw_maximum_qty'     => '',
			'efw_settings_level'  => '',
		);

		/**
		 * Set Id.
		 */
		public function set_id( $value ) {

			$this->id = $value;
		}

		/**
		 * Set Fee Name.
		 */
		public function set_name( $value ) {

			$this->efw_name = $value;
		}

		/**
		 * Set Fee Text.
		 */
		public function set_fee_text( $value ) {

			$this->efw_fee_text = $value;
		}

				/**
				 * Set Fee Description.
				 */
		public function set_fee_description( $value ) {

			$this->efw_fee_description = $value;
		}

		/**
		 * Set Fee Type.
		 */
		public function set_fee_type( $value ) {

			$this->efw_fee_type = $value;
		}

		/**
		 * Set Fixed Fee.
		 */
		public function set_fixed_fee( $value ) {

			$this->efw_fixed_fee = $value;
		}

		/**
		 * Set Percentage Fee.
		 */
		public function set_percent_fee( $value ) {

			$this->efw_percent_fee = $value;
		}

		/**
		 * Set From Date.
		 */
		public function set_from_date( $value ) {

			$this->efw_from_date = $value;
		}

		/**
		 * Set From Date.
		 */
		public function set_to_date( $value ) {

			$this->efw_to_date = $value;
		}

		/**
		 * Set Minimum Quantity.
		 */
		public function set_minimum_qty( $value ) {

			$this->efw_minimum_qty = $value;
		}

		/**
		 * Set Maximum Quantity.
		 */
		public function set_maximum_qty( $value ) {

			$this->efw_maximum_qty = $value;
		}

		/**
		 * Set Settings Level.
		 */
		public function set_settings_level( $value ) {

			$this->efw_settings_level = $value;
		}

		/**
		 * Get Id.
		 */
		public function get_id() {

			return $this->id;
		}

		/**
		 * Get Name.
		 */
		public function get_name() {

			return $this->efw_name;
		}

		/**
		 * Get Fee Text.
		 */
		public function get_fee_text() {

			return $this->efw_fee_text;
		}

				/**
				 * Get Fee Description.
				 */
		public function get_fee_description() {
				return $this->efw_fee_description;
		}

		/**
		 * Get Fee Type.
		 */
		public function get_fee_type() {

			return $this->efw_fee_type;
		}

		/**
		 * Get Fixed Fee.
		 */
		public function get_fixed_fee() {

			return $this->efw_fixed_fee;
		}

		/**
		 * Get Percentage Fee.
		 */
		public function get_percent_fee() {

			return $this->efw_percent_fee;
		}

		/**
		 * Get From Date.
		 */
		public function get_from_date() {

			return $this->efw_from_date;
		}

		/**
		 * Get To Date.
		 */
		public function get_to_date() {

			return $this->efw_to_date;
		}

		/**
		 * Get Minimum Quantity.
		 */
		public function get_minimum_qty() {

			return $this->efw_minimum_qty;
		}

		/**
		 * Get Maximum Quantity.
		 */
		public function get_maximum_qty() {

			return $this->efw_maximum_qty;
		}

		/**
		 * Get Settings Level.
		 */
		public function get_settings_level() {

			return $this->efw_settings_level;
		}
	}

}
