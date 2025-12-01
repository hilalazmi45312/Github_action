<?php

/*
 * Post Function.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! function_exists( 'efw_create_new_fees' ) ) {

	/**
	 * Create New Fee.
	 *
	 * @return Object
	 */
	function efw_create_new_fees( $meta_args, $post_args = array() ) {

		$object = new EFW_Fee() ;

		return $object->create( $meta_args , $post_args ) ;
	}

}

if ( ! function_exists( 'efw_get_fees' ) ) {

	/**
	 * Get Fees.
	 *
	 * @return Object
	 */
	function efw_get_fees( $id ) {

		return new EFW_Fee( $id ) ;
	}

}

if ( ! function_exists( 'efw_get_fees_ids' ) ) {

	/**
	 * Get Deal Ids.
	 *
	 * @return Array
	 */
	function efw_get_fees_ids( $args = array() ) {
		$default_args = array(
			'numberposts' => -1,
			'post_type'   => EFW_Register_Post_Type::FEES_POSTTYPE,
			'post_status' => 'publish',
			'order'       => 'ASC',
			'fields'      => 'ids',
				) ;

		$parsed_data = wp_parse_args( $args , $default_args ) ;

		return get_posts( $parsed_data ) ;
	}

}

if ( ! function_exists( 'efw_update_fees' ) ) {

	/**
	 * Update Fees.
	 *
	 * @return Object
	 */
	function efw_update_fees( $id, $meta_args, $post_args = array() ) {

		$object = new EFW_Fee( $id ) ;

		return $object->update( $meta_args , $post_args ) ;
	}

}

if ( ! function_exists( 'efw_create_new_fee_rule' ) ) {

	/**
	 * Create New Fee Rule.
	 *
	 * @return Object
	 */
	function efw_create_new_fee_rule( $meta_args, $post_args = array() ) {

		$object = new EFW_Rule() ;

		return $object->create( $meta_args , $post_args ) ;
	}

}

if ( ! function_exists( 'efw_get_fee_rule' ) ) {

	/**
	 * Get Fee Rule.
	 *
	 * @return Object
	 */
	function efw_get_fee_rule( $id ) {

		return new EFW_Rule( $id ) ;
	}

}

if ( ! function_exists( 'efw_get_fee_rule_ids' ) ) {

	/**
	 * Get Fee Rule Ids.
	 *
	 * @return Array
	 */
	function efw_get_fee_rule_ids( $args = array() ) {
		$default_args = array(
			'numberposts' => -1,
			'post_type'   => EFW_Register_Post_Type::FEE_RULES_POSTTYPE,
			'post_status' => 'publish',
			'order'       => 'ASC',
			'fields'      => 'ids',
				) ;

		$parsed_data = wp_parse_args( $args , $default_args ) ;

		return get_posts( $parsed_data ) ;
	}

}

if ( ! function_exists( 'efw_update_fee_rule' ) ) {

	/**
	 * Update Rule Fees.
	 *
	 * @return Object
	 */
	function efw_update_fee_rule( $id, $meta_args, $post_args = array() ) {

		$object = new EFW_Rule( $id ) ;

		return $object->update( $meta_args , $post_args ) ;
	}

}

if ( ! function_exists( 'efw_create_new_additional_fee_rule' ) ) {

	/**
	 * Create New Additional Fee Rule.
	 *
	 * @return Object
	 */
	function efw_create_new_additional_fee_rule( $meta_args, $post_args = array() ) {

		$object = new EFW_Additional_Fee_Rule() ;

		return $object->create( $meta_args , $post_args ) ;
	}

}

if ( ! function_exists( 'efw_get_additional_fee' ) ) {

	/**
	 * Get Additional Fee Rule.
	 *
	 * @return Object
	 */
	function efw_get_additional_fee( $id ) {

		return new EFW_Additional_Fee_Rule( $id ) ;
	}

}

if ( ! function_exists( 'efw_get_additional_fee_ids' ) ) {

	/**
	 * Get Additional Fee Rule Ids.
	 *
	 * @return Array
	 */
	function efw_get_additional_fee_ids( $args = array() ) {
		$default_args = array(
			'numberposts' => -1,
			'post_type'   => EFW_Register_Post_Type::ADDITIONAL_FEE_RULES_POSTTYPE,
			'post_status' => 'publish',
			'order'       => 'ASC',
			'fields'      => 'ids',
				) ;

		$parsed_data = wp_parse_args( $args , $default_args ) ;

		return get_posts( $parsed_data ) ;
	}

}

if ( ! function_exists( 'efw_update_additional_fee_rule' ) ) {

	/**
	 * Update Additional Fee Rule.
	 *
	 * @return Object
	 */
	function efw_update_additional_fee_rule( $id, $meta_args, $post_args = array() ) {

		$object = new EFW_Additional_Fee_Rule( $id ) ;

		return $object->update( $meta_args , $post_args ) ;
	}

}

if ( ! function_exists( 'efw_delete_post' ) ) {

	/**
	 * Delete Post.
	 *
	 * @return bool
	 */
	function efw_delete_post( $id, $force = true ) {

		wp_delete_post( $id , $force ) ;

		return true ;
	}

}
