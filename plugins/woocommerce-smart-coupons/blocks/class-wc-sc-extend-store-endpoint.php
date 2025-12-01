<?php
/**
 * Smart Coupons Block extend store endpoints
 *
 * @author      StoreApps
 * @since       8.7.0
 * @version     1.1.0
 *
 * @package     woocommerce-smart-coupons/blocks/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Smart Coupons Extend Store API.
 *
 * A class to extend the store public API with Smart Coupons related data
 *
 * @package WooCommerce Smart Coupons
 */
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

if ( ! class_exists( 'WC_SC_Extend_Store_Endpoint' ) ) {

	/**
	 * Extend store endpoint
	 */
	class WC_SC_Extend_Store_Endpoint {

		/**
		 * Stores Rest Extending instance.
		 *
		 * @var ExtendSchema
		 */
		private static $extend;

		/**
		 * Plugin Identifier, unique to each plugin.
		 *
		 * @var string
		 */
		const IDENTIFIER = 'woocommerce-smart-coupons';

		/**
		 * Bootstraps the class and hooks required data.
		 */
		public static function init() {
			self::$extend = StoreApi::container()->get( ExtendSchema::class );
			self::extend_store();
		}

		/**
		 * Registers the actual data into each endpoint.
		 */
		public static function extend_store() {

			if ( is_callable( array( self::$extend, 'register_endpoint_data' ) ) ) {
				self::$extend->register_endpoint_data(
					array(
						'endpoint'        => CheckoutSchema::IDENTIFIER,
						'namespace'       => self::IDENTIFIER,
						'schema_callback' => array( 'WC_SC_Extend_Store_Endpoint', 'extend_checkout_schema' ),
						'schema_type'     => ARRAY_A,
					)
				);
			}
		}

		/**
		 * Register subscription product schema into cart/items endpoint.
		 *
		 * @return array Registered schema.
		 */
		public static function extend_checkout_schema() {
			return array(
				'is_gift'                     => array(
					'description' => _x( 'Whether to send coupons to someone else.', 'REST API', 'woocommerce-smart-coupons' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'optional'    => true,
					'arg_options' => array(
						'validate_callback' => function( $value ) {
							return is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'send_to'                     => array(
					'description' => _x( 'Whether to send coupons to only one person or multiple people.', 'REST API', 'woocommerce-smart-coupons' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'arg_options' => array(
						'validate_callback' => function( $value ) {
							return is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'wc_sc_schedule_gift_sending' => array(
					'description' => _x( 'Whether to send coupons now or schedule for later.', 'REST API', 'woocommerce-smart-coupons' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'arg_options' => array(
						'validate_callback' => function( $value ) {
							return is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'gift_receiver_email'         => array(
					'description' => _x( 'Email address of recipients', 'REST API', 'woocommerce-smart-coupons' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'arg_options' => array(
						'validate_callback' => function( $value ) {
							return is_array( $value );
						},
						'sanitize_callback' => 'wc_clean',
					),
					'items'       => array(
						'type' => 'string',
					),
				),
				'gift_sending_timestamp'      => array(
					'description' => _x( 'Coupons scheduled date & time', 'REST API', 'woocommerce-smart-coupons' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'arg_options' => array(
						'validate_callback' => function( $value ) {
							return is_array( $value );
						},
						'sanitize_callback' => 'wc_clean',
					),
					'items'       => array(
						'type' => 'string',
					),
				),
				'gift_receiver_message'       => array(
					'description' => _x( 'Message for coupon recipient', 'REST API', 'woocommerce-smart-coupons' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'arg_options' => array(
						'validate_callback' => function( $value ) {
							return is_array( $value );
						},
						'sanitize_callback' => 'wc_clean',
					),
					'items'       => array(
						'type' => 'string',
					),
				),
			);
		}
	}

}

