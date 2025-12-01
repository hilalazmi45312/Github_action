<?php

/**
 * WooCommerce Blocks Store API.
 *
 * @since 3.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

/**
 * Class for extend store API of cart/checkout.
 *
 * @since 3.7.0
 */
class DEY_WC_Blocks_Store_API {

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @since 3.7.0
	 * @var string
	 */
	const IDENTIFIER = 'dey-delivery-slots';

	/**
	 * Bootstrap.
	 *
	 * @since 3.7.0
	 */
	public static function init() {
		// Extend StoreAPI.
		self::extend_store();

		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'maybe_assign_checkout_fields_value' ), 10, 2 );
	}

	/**
	 * Register extensibility points.
	 *
	 * @since 3.7.0
	 */
	protected static function extend_store() {
		if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'      => CartSchema::IDENTIFIER,
					'namespace'     => self::IDENTIFIER,
					'data_callback' => array( 'DEY_WC_Blocks_Store_API', 'extend_cart_data' ),
					'schema_type'   => ARRAY_A,
				)
			);

			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'        => CheckoutSchema::IDENTIFIER,
					'namespace'       => self::IDENTIFIER,
					'data_callback'   => array( 'DEY_WC_Blocks_Store_API', 'extend_cart_data' ),
					'schema_callback' => array( 'DEY_WC_Blocks_Store_API', 'extend_cart_schema' ),
					'schema_type'     => ARRAY_A,
				)
			);
		}

		if ( function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			woocommerce_store_api_register_update_callback(
				array(
					'namespace' => self::IDENTIFIER,
					'callback'  => array( 'DEY_WC_Blocks_Store_API', 'rest_handle_endpoint' ),
				)
			);
		}
	}

	/**
	 * Register delivery and pickup scheduler schema in the cart schema.
	 *
	 * @since 3.7.0
	 * @return array
	 */
	public static function extend_cart_schema() {
		return array(
			'delivery_fields' => array(
				'description' => __( 'Delivery related fields', 'delivery-slots-for-woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'properties'  => array(
					'dey_order_scheduler_type'           => array(
						'description' => __( 'Scheduler Type', 'delivery-slots-for-woocommerce' ),
						'type'        => array( 'string', 'boolean' ),
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
					'dey_delivery_date'                  => array(
						'description' => __( 'Delivery Date', 'delivery-slots-for-woocommerce' ),
						'type'        => array( 'string', 'boolean' ),
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
					'dey_local_pickup_date'              => array(
						'description' => __( 'Pickup Date', 'delivery-slots-for-woocommerce' ),
						'type'        => array( 'string', 'boolean' ),
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
					'dey_pickup_location'                => array(
						'description' => __( 'Pickup Location', 'delivery-slots-for-woocommerce' ),
						'type'        => array( 'string', 'boolean' ),
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
					'dey_order_delivery_date_time_slots' => array(
						'description' => __( 'Delivery Time Slots', 'delivery-slots-for-woocommerce' ),
						'type'        => array( 'string', 'boolean' ),
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
					'dey_order_local_pickup_date_time_slots' => array(
						'description' => __( 'Pickup Time Slots', 'delivery-slots-for-woocommerce' ),
						'type'        => array( 'string', 'boolean' ),
						'context'     => array( 'view', 'edit' ),
						'required'    => true,
					),
				),
				'arg_options' => array(
					'validate_callback' => array( 'DEY_WC_Blocks_Store_API', 'validate_callback' ),
				),
			),
		);
	}

	/**
	 * Register delivery and pickup scheduler data in the cart API.
	 *
	 * @since 3.7.0
	 * @return array
	 */
	public static function extend_cart_data() {
		/**
		 * This hook is used to alter the extend cart data.
		 *
		 * @since 3.7.0
		 */
		return apply_filters(
			'dey_extend_cart_data',
			array(
				'order_tip_title'                => dey_get_order_tip_title_label(),
				'delivery_pickup_scheduler_html' => dey_get_delivery_pickup_scheduler_html(),
				'cart_order_tip_html'            => dey_get_cart_block_order_tip_html(),
				'checkout_order_tip_html'        => dey_get_checkout_block_order_tip_html(),
				'fee_html'                       => dey_get_block_tip_fee_html(),
			)
		);
	}

	/**
	 * Validate the given address object.
	 *
	 * @since 3.7.0
	 * @param array            $delivery_fields Value being sanitized.
	 * @param \WP_REST_Request $request The Request.
	 * @param string           $param The param being sanitized.
	 * @return true|\WP_Error
	 */
	public static function validate_callback( $delivery_fields, $request, $param ) {
		/**
		 * This hook is used to restrict the checkout fields validation.
		 *
		 * @since 3.9.1
		 */
		if ( ! apply_filters( 'dey_validate_checkout_fields', true ) ) {
			return true;
		}

		if ( ! dey_check_is_array( $delivery_fields ) ) {
			return true;
		}

		$errors = DEY_Checkout_Fields_Validator::validate_post_data( $delivery_fields );

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Handles delivery and pickup scheduler rest endpoints.
	 *
	 * @since 3.7.0
	 * @param array $args
	 */
	public static function rest_handle_endpoint( $args ) {
	}

	/**
	 * May be assign checkout fields value in request global variable.
	 *
	 * @since 3.7.0
	 * @param object $order
	 * @param object $request
	 */
	public static function maybe_assign_checkout_fields_value( $order, $request ) {
		if ( ! is_object( $request ) ) {
			return;
		}

		$extensions = $request->get_param( 'extensions' );
		$params     = $extensions['dey-delivery-slots'] ? $extensions['dey-delivery-slots'] : array();
		if ( empty( $params ) ) {
			return;
		}

		$_REQUEST = array_merge( $_REQUEST, $extensions['dey-delivery-slots']['delivery_fields'] );
	}
}
