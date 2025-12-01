<?php

/**
 * Admin Custom Post Type.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Register_Post_Type' ) ) {

	/**
	 * Class.
	 */
	class EFW_Register_Post_Type {

		/**
		 * Fee Post Type.
		 */
		const FEES_POSTTYPE = 'efw_fees' ;
				
		/**
		 * Fee Rules Post Type.
		 */
		const FEE_RULES_POSTTYPE = 'efw_fee_rules' ;

		/**
		 * Additional Fee Rules Post Type.
		 */
		const ADDITIONAL_FEE_RULES_POSTTYPE = 'efw_additional_fee' ;

		/**
		 * Class initialization.
		 */
		public static function init() {
			add_action( 'init' , array( __CLASS__, 'register_custom_post_types' ) , 5 ) ;
		}

		/**
		 * Register Custom Post types.
		 */
		public static function register_custom_post_types() {
			if ( ! is_blog_installed() ) {
				return ;
			}

			$custom_post_type = array(
				self::FEES_POSTTYPE       => array( 'EFW_Register_Post_Type', 'fees_post_type_args' ),
				self::FEE_RULES_POSTTYPE  => array( 'EFW_Register_Post_Type', 'fee_rules_post_type_args' ),
				self::ADDITIONAL_FEE_RULES_POSTTYPE  => array( 'EFW_Register_Post_Type', 'additional_fee_rules_post_type_args' ),
					) ;
						/**
						 * Hook:efw_add_custom_post_type. 
						 *
						 * @since 1.0
						 */
			$custom_post_type = apply_filters( 'efw_add_custom_post_type' , $custom_post_type ) ;

			if ( ! efw_check_is_array( $custom_post_type ) ) {
				return ;
			}

			foreach ( $custom_post_type as $post_type => $args_function ) {
				$args = array() ;
				if ( $args_function ) {
					$args = call_user_func_array( $args_function , $args ) ;
				}

				if ( ! post_type_exists( $post_type ) ) {

					// Register custom post type.
					register_post_type( $post_type , $args ) ;
				}
			}
		}

		/**
		 * Prepare Fees Post type arguments
		 */
		public static function fees_post_type_args() {
						/**
						 * Hook:efw_fees_post_type_args. 
						 *
						 * @since 1.0
						 */
			return apply_filters( 'efw_fees_post_type_args' , array(
				'label'           => esc_html__( 'Fees' , 'extra-fees-for-woocommerce' ),
				'public'          => false,
				'hierarchical'    => false,
				'supports'        => false,
				'capability_type' => 'post',
				'rewrite'         => false,
					)
					) ;
		}
				
		/**
		 * Prepare Rules Post type arguments
		 */
		public static function fee_rules_post_type_args() {
						/**
						 * Hook:efw_fees_rules_post_type_args. 
						 *
						 * @since 1.0
						 */
			return apply_filters( 'efw_fees_rules_post_type_args' , array(
				'label'           => esc_html__( 'Fee Rules' , 'extra-fees-for-woocommerce' ),
				'public'          => false,
				'hierarchical'    => false,
				'supports'        => false,
				'capability_type' => 'post',
				'rewrite'         => false,
					)
					) ;
		}

		/**
		 * Prepare Additional Rules Post type arguments
		 */
		public static function additional_fee_rules_post_type_args() {
			/**
			 * Hook:efw_additional_fees_rules_post_type_args. 
			 *
			 * @since 6.1.3
			 */
			return apply_filters( 'efw_additional_fees_rules_post_type_args' , array(
				'label'           => esc_html__( 'Additional Fee Rules' , 'extra-fees-for-woocommerce' ),
				'public'          => false,
				'hierarchical'    => false,
				'supports'        => false,
				'capability_type' => 'post',
				'rewrite'         => false,
					)
					) ;
		}
	}

	EFW_Register_Post_Type::init() ;
}
