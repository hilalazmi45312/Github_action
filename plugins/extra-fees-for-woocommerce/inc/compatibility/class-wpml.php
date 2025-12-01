<?php

/**
 * WPML Compatibility.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_WPML_Compatibility' ) ) {

	/**
	 * Class EFW_WPML_Compatibility.
	 */
	class EFW_WPML_Compatibility extends EFW_Compatibility {

		/**
		 * Context
		 * 
		 * @var string
		 */
		private $context = 'extra-fees-for-woocommerce' ;

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'wpml' ;

			parent::__construct() ;
		}

		/**
		 * Is plugin enabled?.
		 * 
		 *  @return bool
		 * */
		public function is_plugin_enabled() {

			return function_exists( 'icl_register_string' ) ;
		}

		/**
		 * Admin Action.
		 */
		public function admin_action() {

			// Register the string.
			add_filter( 'admin_init' , array( $this, 'register_string' ) , 10 , 3 ) ;
		}

		/**
		 * Action
		 */
		public function actions() {

			// Get the string.
			add_filter( 'efw_custom_field_translate_string' , array( $this, 'translate_string' ) , 10 , 3 ) ;
		}

		/**
		 * Register the string in WPML.
		 * 
		 * @return bool
		 */
		public function register_string() {

			$available_gateway_ids = efw_get_wc_available_gateways(true) ;
			// Return if the custom field ids not exists.
			if ( ! efw_check_is_array( $available_gateway_ids ) ) {
				return ;
			}

			foreach ( $available_gateway_ids as $available_gateway_id => $available_gateway_title ) {

								$value = get_option('efw_fee_text_for_' . $available_gateway_id);
								// Registering the custom field string.
				icl_register_string( $this->context , 'efw_fee_text_for_' . $available_gateway_id , $value ) ;
			}
		}

		/**
		 * Get the string in WPML.
		 * 
		 * @return string
		 */
		public function translate_string( $value, $option_name, $language ) {
			$has_translation = null ;

			return icl_translate( $this->context , $option_name , $value , false , $has_translation , $language ) ;
		}
	}

}
