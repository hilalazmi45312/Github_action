<?php
// Add the action to register REST API route
add_action( 'rest_api_init', function () {
	register_rest_route( 'gtmpb/v1', '/product-badge/available-slots', array(
		'methods'             => 'GET',
		'callback'            => 'gtmpb_rest_get_available_slots',
		'permission_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
		'schema'              => array(
			'$schema' => 'https://json-schema.org/draft-07/schema',
			'title'   => esc_html__( 'Available Slots', 'product-promotion-badge' ),
			'type'    => 'array',
			'items'   => array(
				'type'       => 'object',
				'properties' => array(
					'label' => array(
						'description' => esc_html__( 'The human-readable label for the slot.', 'product-promotion-badge' ),
						'type'        => 'string',
					),
					'name'  => array(
						'description' => esc_html__( 'The identifier for the slot.', 'product-promotion-badge' ),
						'type'        => 'string',
					),
				),
				'required'   => array( 'label', 'name' ),
			),
		),
	) );
} );

// Callback function for the endpoint
function gtmpb_rest_get_available_slots( WP_REST_Request $request ) {
	$available_slots = apply_filters( 'gtmpb_get_available_slots', array() );
	return rest_ensure_response( $available_slots );
}
