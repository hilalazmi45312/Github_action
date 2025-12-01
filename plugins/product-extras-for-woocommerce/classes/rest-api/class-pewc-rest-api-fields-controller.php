<?php
/**
 * REST API Fields Controller for Product Add-Ons Ultimate Fields
 *
 * Handles requests to /pewc
 * @since 3.11.x
 * @package WooCommerce Product Add-Ons Ultimate
 * @extends WC_REST_Controller
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class PEWC_REST_API_Fields_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'pewc';

	/**
	 * Product ID for this request
	 *
	 * @var integer
	 */
	protected $product_id = false;

	/**
	 * Group ID for this request
	 *
	 * @var integer
	 */
	protected $group_id = false;

	/**
	 * Field ID for this request
	 *
	 * @var integer
	 */
	protected $field_id = false;

	/**
	 * Register the routes for product add-ons ultimate
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<product_id>[\d]+)/(?P<group_id>[\d]+)', array(
			'args' => array(
				'product_id' => array(
					'description' => __( 'Unique identifier for the product.', 'woocommerce' ),
					'type'        => 'integer',
					'required' => true
				),
				'group_id' => array(
					'description' => __( 'Unique identifier for the add-on group.', 'pewc' ),
					'type'        => 'integer',
					'required' => true
				)
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'pewc_permissions_check' ),
				'args'				  => array_merge ( $this->get_endpoint_args_for_item_schema( 'POST' ), array(
					'field_type' => array(
						'description' => __( 'Type of product add-on field', 'pewc' ),
						'type' 		  => 'string',
						'enum'		  => array_keys( pewc_field_types() ),
						'required'	  => true
					)
				) )
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<product_id>[\d]+)/(?P<group_id>[\d]+)/(?P<field_id>[\d]+)', array(
			'args' => array(
				'product_id' => array(
					'description' => __( 'Unique identifier for the product.', 'woocommerce' ),
					'type'        => 'integer',
					'required' => true
				),
				'group_id' => array(
					'description' => __( 'Unique identifier for the add-on group.', 'pewc' ),
					'type'        => 'integer',
					'required' => true
				),
				'field_id' => array(
					'description' => __( 'Unique identifier for the add-on field.', 'pewc' ),
					'type'        => 'integer',
					'required' => true
				)
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'pewc_permissions_check' ),
				//'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'pewc_permissions_check' ),
				'args'				  => $this->get_endpoint_args_for_item_schema( 'PUT' )
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'pewc_permissions_check' ), // use same permissions
				//'args'                => array(), // no additional args
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check if a given request has access to add/edit/delete a product add-on group or field. Users must be able to edit a product in order to manage add-on fields
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function pewc_permissions_check( $request ) {
		$params = $request->get_url_params();
		$product_id = (int) $params['product_id'];

		if ( 'product' !== get_post_type( $product_id ) ) {
			return new WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( ! wc_rest_check_post_permissions( 'product', 'edit', $product_id ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$this->product_id = $product_id; // set the product ID for future use

		if ( isset( $params['group_id'] ) ) {
			$group_id = (int) $params['group_id'];
			// check if this group is under the parent product.. maybe also catches invalid group IDs
			if ( empty( $group_id) || $this->product_id !== wp_get_post_parent_id( $group_id ) || 'pewc_group' !== get_post_type( $group_id ) ) {
				return new WP_Error( 'pewc_rest_group_invalid_id', __( 'Invalid Add-on Group ID.', 'pewc' ), array( 'status' => 404 ) );
			}

			$this->group_id = $group_id; // set the group ID for future use

			if ( isset( $params['field_id'] ) ) {
				$field_id = (int) $params['field_id'];
				// check if this field is under the parent group.. maybe also catches invalid field IDs
				if ( empty( $field_id) || $this->group_id !== wp_get_post_parent_id( $field_id ) || 'pewc_field' !== get_post_type( $field_id ) ) {
					return new WP_Error( 'pewc_rest_field_invalid_id', __( 'Invalid Add-on Field ID.', 'pewc' ), array( 'status' => 404 ) );
				}
	
				$this->field_id = $field_id; // set the field ID for future use
			}
		}

		return true;
	}

	/**
	 * Retrieve data for this field
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$data = $this->get_field_data();

		// prepare group for response
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Create an add-on field
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		$field_id = pewc_create_new_field( $this->group_id );

		if ( false === $field_id ) {
			return new WP_Error( 'pewc_rest_field_not_created', __( 'Field not created', 'pewc' ), array( 'status' => 500 ) );
		}
		$this->field_id = $field_id;

		// this checks if the passed fields are valid
		$prepared = $this->prepare_item_for_database( $request );

		if ( empty( $prepared ) ) {
			// delete created field first
			wp_delete_post( $this->field_id, true );

			return new WP_Error( 'pewc_rest_field_empty_data', __( 'Missing field data. Please pass at least one value to use this endpoint.', 'pewc' ), array( 'status' => 404 ) );
		}

		// this is how fields are saved in pewc_save_product_extra_options()
		// $prepared only contains non-empty values at this point
		// add these 2 required data
		$prepared['id'] = 'pewc_group_'.$this->group_id.'_'.$this->field_id;
		$prepared['group_id'] = $this->group_id;
		// now save
		$this->save_field_data( $prepared );

		// clear transient
		delete_transient( 'pewc_extra_fields_'.$this->product_id );

		// get field data to return
		$data = $this->get_field_data();

		// prepare for response
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update an add-on field
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {

		// this checks if the passed fields are valid
		$prepared = $this->prepare_item_for_database( $request );

		if ( empty( $prepared ) ) {
			return new WP_Error( 'pewc_rest_field_empty_data', __( 'Missing field data. Please pass at least one value to use this endpoint.', 'pewc' ), array( 'status' => 404 ) );
		}

		// this is how fields are saved in pewc_save_product_extra_options()
		// $prepared only contains non-empty values at this point
		// now save
		$this->save_field_data( $prepared );

		// clear transient
		delete_transient( 'pewc_extra_fields_'.$this->product_id );

		// get field data to return
		$data = $this->get_field_data();

		// prepare for response
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Delete an add-on field
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {

		$delete = wp_delete_post( $this->field_id, true );

		if ( ! $delete ) {
			return new WP_Error( 'pewc_rest_field_cannot_delete', __( 'Cannot delete add-on field.', 'pewc' ), array( 'status' => 500 ) );
		}

		// Remove this field ID from the parent group
		$fields = get_post_meta( $this->group_id, 'field_ids', true );
		// Unset element by value
		if( ( $key = array_search( $this->field_id, $fields ) ) !== false ) {
    		unset( $fields[$key] );
		}
		update_post_meta( $this->group_id, 'field_ids', $fields );

		// clear transient
		delete_transient( 'pewc_extra_fields_'.$this->product_id );

		// build data to return
		$data['product_id'] = $this->product_id;
		$data['group_id'] = $this->group_id;
		$data['deleted_field_id'] = $this->field_id;
		$data['field_ids'] = ! empty( $fields ) ? implode( ',', $fields ) : '';

		// prepare for response
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Get field data.
	 *
	 * @return array
	 */
	protected function get_field_data() {
		$data['group_id'] = $this->group_id;
		$data['field_id'] = $this->field_id;

		$field_params = pewc_get_field_params();

		foreach ( $field_params as $param ) {
			if ( ! isset( $data[$param] ) ) {
				$data[$param] = get_post_meta( $this->field_id, $param, true );
			}
		}

		return $data;
	}

	/**
	 * Save field data into the database.
	 *
	 * @param  $prepared data.
	 * @return array|WP_Error  $prepared ?
	 */
	protected function save_field_data( $prepared ) {

		$all_params = get_post_meta( $this->field_id, 'all_params', true );

		if ( empty( $all_params ) ) {
			$all_params = array();
		}
		$field_params = pewc_get_field_params();

		// this is needed
		$all_params['field_id'] = $this->field_id;

		foreach ( $field_params as $param ) {
			$value = isset( $prepared[$param] ) ? $prepared[$param] : '';

			// Ensure the options array doesn't get out of sync
			if( in_array( $param, array( 'field_options', 'condition_field', 'condition_rule', 'condition_value' ) ) ) {
				$value = ! empty( $value ) ? array_values( $value ) : array();
			}

			// Need to sanitise this
			// only do this if value was passed, or if all_params is not set i.e. creating new field
			// || ! isset( $all_params[$param] )
			if ( isset( $prepared[$param] ) ) {
				update_post_meta( $this->field_id, $param, $value );
				$all_params[$param] = $value;
			}
		}

		// Filter any values here just before they're saved
		$all_params = apply_filters( 'pewc_before_update_field_all_params', $all_params, $this->field_id );

		update_post_meta( $this->field_id, 'all_params', $all_params );

		// Delete the item object transient used on the front end
		delete_transient( 'pewc_item_object_' . $this->field_id );

		// Now create the transient nice and fresh
		pewc_create_item_object( $this->field_id );
	}

	/**
	 * Prepare group data to be inserted into the database.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return array|WP_Error  $prepared
	 */
	protected function prepare_item_for_database( $request ) {

		$prepared = array();
		$params = apply_filters( 'pewc_prepare_item_for_database_params', $request->get_json_params() ); // only process data passed via JSON

		// pewc_save_product_extra_options()
		$field_params = pewc_get_field_params();

		// loop through each valid field input
		foreach ( $field_params as $param ) {
			if ( 'id' === $param || 'group_id' === $param )
				continue; // skip these two
			if ( isset( $params[$param] ) ) {
				// we don't use ! empty because it's ok to pass 0 to mean unchecking a checkbox setting
				$prepared[$param] = $params[$param];
			}
		}

		return $prepared;
	}

	/**
	 * Prepare a single product review output for response.
	 *
	 * @param WP_Comment $review Product review object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $data, $request ) {
		$schema = $this->get_item_schema();
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$response = rest_filter_response_by_context( $data, $schema, $context );

		return $response;
	}

	/**
	 * Get the Product Add-On Field's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema         = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'pewc_group',
			'type'       => 'object',
			'properties' => array(
				'group_id' => array(
					'description' => __( "Unique identifier for the add-on field's group.", 'pewc' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'field_id' => array(
					'description' => __( 'Unique identifier for the add-on field.', 'pewc' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'field_type' => array(
					'description' => __( 'Type of product add-on field', 'pewc' ),
					'type' 		  => 'string',
					'enum'		  => array_keys( pewc_field_types() ),
					'context'	  => array( 'view', 'edit' )
				),
				'field_label' => array(
					'description' => __( 'Field label', 'pewc' ),
					'type' 		  => 'string',
					'context'	  => array( 'view', 'edit' )
				)
			)
		);

		// get all valid field input, but they won't have descriptions and type?
		// field with checkboxes can accept a string or numeric 1
		// e.g. "field_required":1 and "field_flatrate":"1" works
		$params = pewc_get_field_params();

		foreach ( $params as $param ) {
			if ( 'id' === $param )
				continue; // skip
			if ( ! isset( $schema['properties'][$param] ) ) {
				$schema['properties'][$param] = array(
					'description' => ucwords( str_replace( '_', ' ', $param ) ),
					'type'		  => array( 'string', 'number', 'array' ), // WordPress throws a PHP Warning if this is missing
					'context' 	  => array( 'view', 'edit' )
				);
			}
		}

		return $schema;
	}
}
