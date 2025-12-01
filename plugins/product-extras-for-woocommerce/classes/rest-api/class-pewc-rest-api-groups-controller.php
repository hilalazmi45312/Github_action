<?php
/**
 * REST API Groups Controller for Product Add-Ons Ultimate Groups
 *
 * Handles requests to /pewc
 * @since 3.11.x
 * @package WooCommerce Product Add-Ons Ultimate
 * @extends WC_REST_Controller
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class PEWC_REST_API_Groups_Controller extends WC_REST_Controller {

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
	 * Register the routes for product add-ons ultimate
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<product_id>[\d]+)', array(
			'args' => array(
				'product_id' => array(
					'description' => __( 'Unique identifier for the product.', 'woocommerce' ),
					'type'        => 'integer',
					'required'	  => true
				)
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'pewc_permissions_check' ),
				//'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'pewc_permissions_check' ),
				'args'				  => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE )
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

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
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'pewc_permissions_check' ),
				//'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'pewc_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
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
		}

		return true;
	}

	/**
	 * Retrieve group order for this product
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$group_order = pewc_get_group_order( $this->product_id );

		$data['product_id'] = $this->product_id;
		$data['group_order'] = ! empty( $group_order ) ? $group_order : '';

		// prepare group for response
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Retrieve data for this group
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		$data['group_id'] = $this->group_id;
		$data['group_title'] = get_post_meta( $this->group_id, 'group_title', true );
		$data['group_description'] = get_post_meta( $this->group_id, 'group_description', true );
		$data['group_layout'] = get_post_meta( $this->group_id, 'group_layout', true );
		$data['field_ids'] = array_values( get_post_meta( $this->group_id, 'field_ids', true ) );

		// prepare group for response
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Create an add-on group
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		$new_group = pewc_create_new_group( $this->product_id, '' );
		if ( false === $new_group ) {
			return new WP_Error( 'pewc_rest_group_not_created', __( 'Group not created', 'pewc' ), array( 'status' => 500 ) );
		}
		// clear transient
		delete_transient( 'pewc_extra_fields_'.$this->product_id );

		$data['group_id'] = $new_group['group_id'];
		$data['group_order'] = $new_group['group_order'];

		// allow users to pass other group data so that a group can be created with name, description, etc in one request
		$prepared_group = $this->prepare_item_for_database( $request );
		$data = array_merge( $data, $this->update_group( $data['group_id'], $prepared_group, true ) );

		// prepare group for response
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update an add-on group
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {

		// allow users to pass other group data so that a group can be created with name, description, etc in one request
		$prepared_group = $this->prepare_item_for_database( $request );

		if ( empty( $prepared_group ) ) {
			return new WP_Error( 'pewc_rest_group_empty_data', __( 'Missing group data. Please pass at least one value to use this endpoint.', 'pewc' ), array( 'status' => 404 ) );
		}

		$data = $this->update_group( $this->group_id, $prepared_group );

		// prepare group for response
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update an add-on group data
	 * We use this in both create_item and update_item
	 * Maybe get post meta as well?
	 *
	 */
	private function update_group( $group_id, $prepared_group, $create = false ) {
		$data['group_id'] = $group_id;
		$data['group_title'] = ! empty( $prepared_group['group_title'] ) ? $prepared_group['group_title'] : '';
		$data['group_description'] = ! empty( $prepared_group['group_description'] ) ? $prepared_group['group_description'] : '';
		$data['group_layout'] = ! empty( $prepared_group['group_layout'] ) ? $prepared_group['group_layout'] : '';

		if ( isset( $prepared_group['group_title'] ) || $create ) {
			update_post_meta( $group_id, 'group_title', sanitize_text_field( $data['group_title'] ) );
		} else {
			$data['group_title'] = get_post_meta( $group_id, 'group_title', true );
		}
		if ( isset( $prepared_group['group_description'] ) || $create ) {
			update_post_meta( $group_id, 'group_description', wp_kses_post( $data['group_description'] ) );
		} else {
			$data['group_description'] = get_post_meta( $group_id, 'group_description', true );
		}
		if ( isset( $prepared_group['group_layout'] ) || $create ) {
			update_post_meta( $group_id, 'group_layout', sanitize_text_field( $data['group_layout'] ) );
		} else {
			$data['group_layout'] = get_post_meta( $group_id, 'group_layout', true );
		}

		return $data;
	}

	/**
	 * Delete an add-on group
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {

		// ok to delete, taken from pewc_remove_group_id()
		// get product's group order first
		$group_order = pewc_get_group_order( $this->product_id );

		// delete group
		$delete = wp_delete_post( $this->group_id, true );

		if ( ! $delete ) {
			return new WP_Error( 'pewc_rest_group_cannot_delete', __( 'Cannot delete add-on group.', 'pewc' ), array( 'status' => 500 ) );
		}

		// delete the group from the groups list
		$group_order = pewc_remove_group_from_group_order( $group_order, $this->group_id );
		update_post_meta( $this->product_id, 'group_order', $group_order );

		// build data to return
		$data['product_id'] = $this->product_id;
		$data['group_order'] = $group_order;
		$data['deleted_group_id'] = $this->group_id;

		// also delete extra fields under this group?
		$args = array(
			'post_parent' => $this->group_id,
			'post_type' => 'pewc_field'
		);
		$fields = get_children( $args );

		if ( ! empty( $fields ) ) {
			$deleted_fields = array();
			foreach ( $fields as $field_id => $field_data ) {
				wp_delete_post( $field_id, true );
				$deleted_fields[] = $field_id;
			}
			if ( ! empty( $deleted_fields ) ) {
				$data['deleted_fields'] = $deleted_fields;
			}
		}

		// clear transient
		delete_transient( 'pewc_extra_fields_'.$this->product_id );

		// prepare group for response
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Prepare group data to be inserted into the database.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return array|WP_Error  $prepared
	 */
	protected function prepare_item_for_database( $request ) {

		$prepared = array();
		$params = $request->get_json_params(); // only process data passed via JSON

		if ( isset( $params['group_title'] ) ) {
			$prepared['group_title'] = (string) $params['group_title'];
		}
		if ( isset( $params['group_description'] ) ) {
			$prepared['group_description'] = (string) $params['group_description'];
		}
		$prepared['group_layout'] = ! empty( $params['group_layout'] ) ? (string) $params['group_layout'] : 'ul';

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
	 * Get the Product Add-On Group's schema, conforming to JSON Schema.
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
					'description' => __( 'Unique identifier for the add-on group.', 'pewc' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'group_order' => array(
					'description' => __( 'List of add-on groups under a product.', 'pewc' ),
					'type'		=> 'string',
					'context'	=> array( 'view' ),
					'readonly'	=> true
				),
				'group_title' => array(
					'description' => __( 'Add-on group title.', 'pewc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'group_description' => array(
					'description' => __( 'Add-on group description.', 'pewc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'group_layout' => array(
					'description' => __( 'Add-on group layout.', 'pewc' ),
					'type'        => 'string',
					'default'	  => 'ul',
					'enum'		  => array( 'ul', 'table', 'cols-2', 'cols-3' ),
					'context'     => array( 'view', 'edit' ),
				)
			)
		);

		return $schema;
	}

}
