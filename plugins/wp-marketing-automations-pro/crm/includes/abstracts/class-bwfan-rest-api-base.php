<?php

/**
 * Class BWFAN_Rest_API_Base
 */
if ( ! class_exists( 'BWFAN_Rest_API_Base' ) ) {
	abstract class BWFAN_Rest_API_Base {
		public $route = null;

		public $method = null;

		public $required = [];
		public $validate = [];

		public $response_code = 200;

		/**
		 * @var stdClass $pagination
		 *
		 * It contains two keys: Limit and Offset, for pagination purposes
		 */
		public $pagination = null;


		public $args = array();

		public $request_args = array();

		public function __construct() {
			$this->pagination         = new stdClass();
			$this->pagination->limit  = 0;
			$this->pagination->offset = 0;
		}

		/**
		 * @param WP_REST_Request $request
		 * common function for all the apis
		 * it collects the data and prepare it for processing
		 *
		 * @return WP_Error
		 */
		public function api_call( WP_REST_Request $request ) {
			$params = WP_REST_Server::EDITABLE === $this->method ? $request->get_params() : false;

			if ( false === $params ) {
				$query_params   = $request->get_query_params();
				$query_params   = is_array( $query_params ) ? $query_params : array();
				$request_params = $request->get_params();
				$request_params = is_array( $request_params ) ? $request_params : array();
				$params         = array_replace( $query_params, $request_params );
			}

			$params['limit']         = $params['limit'] ?? 25;
			$params['files']         = $request->get_file_params();
			$this->pagination->limit = $params['limit'] ?? $this->pagination->limit;
			$page                    = isset( $params['page'] ) && ! empty( $params['page'] ) ? intval( $params['page'] ) : 0;
			if ( isset( $params['offset'] ) && ! empty( $params['offset'] ) ) {
				$this->pagination->offset = $params['offset'];
			} else {
				$this->pagination->offset = ( $page > 0 ) ? ( ( $page - 1 ) * $this->pagination->limit ) : 0;
				$params['offset']         = $this->pagination->offset;
			}
			$this->args = wp_parse_args( $params, $this->default_args_values() );

			try {
				/** Check if required fields missing */
				$this->check_required_fields();
				$data_obj = $this->validate_data();

				return $this->process_api_call( $data_obj );
			} catch ( Error $e ) {
				$this->error_response( $e->getMessage(), 500 );
			}
		}

		protected function get_current_api_method() {
			return $_SERVER['REQUEST_METHOD'];
		}

		public function check_required_fields() {
			if ( empty( $this->required ) || ! is_array( $this->required ) ) {
				return;
			}
			if ( array_intersect( array_keys( $this->args ), $this->required ) ) {
				$verified = true;
				foreach ( $this->required as $field ) {
					/**
					 * Checking required field is empty
					 */
					if ( empty( $this->args[ $field ] ) ) {
						$verified = false;
						break;
					}
				}
				if ( true === $verified ) {
					return;
				}
			}
			BWFCRM_Core()->response_handler->error_response( '', 400, null, 'required_fields_missing' );
		}

		/**
		 * Validate requested data
		 * @return void
		 */
		public function validate_data() {
			if ( empty( $this->validate ) || ! is_array( $this->validate ) ) {
				return;
			}
			$validated   = true;
			$err_code    = '';
			$data_obj    = '';
			$data_obj_id = [];
			$code        = 422;

			$id = $this->get_data_id();

			foreach ( $this->validate as $data ) {
				switch ( true ) {
					case 'contact_already_exists' === $data:
						if ( ! isset( $data_obj_id['contact'] ) || ! isset( $data_obj_id['contact']['id'] ) ) {
							$data_obj_id['contact']['id'] = $id;
							$data_obj                     = BWFAN_Rest_API_Common::is_contact_exists( $id );
						}
						if ( false !== $data_obj ) {
							$validated = false;
							$err_code  = 'already_exists';
						}
						break;
					case 'contact_not_exists' === $data:
						if ( ! isset( $data_obj_id['contact'] ) || ! isset( $data_obj_id['contact']['id'] ) ) {
							$data_obj_id['contact']['id'] = $id;
							$data_obj                     = BWFAN_Rest_API_Common::is_contact_exists( $id );
						}
						if ( false === $data_obj ) {
							$validated = false;
							$err_code  = 'contact_not_exists';
						}
						break;
					case 'field_not_exists' === $data:
						if ( ! isset( $data_obj_id['field'] ) || ! isset( $data_obj_id['field']['id'] ) ) {
							$data_obj_id['field']['id'] = $id;
							$data_obj                   = BWFCRM_Fields::is_field_exists( $id );
						}

						if ( false === $data_obj ) {
							$validated = false;
							$code      = 404;
							$err_code  = 'data_not_found';
						}
						break;
					case 'list_not_exists' === $data:
						if ( ! isset( $data_obj_id['list'] ) || ! isset( $data_obj_id['list']['id'] ) ) {
							$data_obj_id['list']['id'] = $id;
							$data_obj                  = new BWFCRM_Lists( $id );
						}

						if ( false === $data_obj->is_exists() ) {
							$validated = false;
							$code      = 404;
							$err_code  = 'data_not_found';
						}
						break;
					case 'tag_not_exists' === $data:
						if ( ! isset( $data_obj_id['tag'] ) || ! isset( $data_obj_id['tag']['id'] ) ) {
							$data_obj_id['tag']['id'] = $id;
							$data_obj                 = new BWFCRM_Tag( $id );
						}

						if ( false === $data_obj->is_exists() ) {
							$validated = false;
							$code      = 404;
							$err_code  = 'data_not_found';
						}
						break;
				}
				if ( false === $validated ) {
					break;
				}
			}
			/** Return if data is validated */
			if ( true === $validated ) {
				return $data_obj;
			}

			BWFCRM_Core()->response_handler->error_response( '', $code, null, $err_code );
		}

		/**
		 * Get requested data id
		 * @return mixed|string
		 */
		public function get_data_id() {
			$email = isset( $this->args['email'] ) ? $this->args['email'] : '';

			return isset( $this->args['id'] ) ? $this->args['id'] : $email;
		}

		public function error_response( $message = '', $code = 0, $err_code = '', $wp_error = null ) {
			BWFCRM_Core()->response_handler->error_response( $message, $code, $wp_error, $err_code );
		}

		/**
		 * @param string $key
		 * @param string $is_a
		 * @param string $collection
		 *
		 * @return bool|array|mixed
		 */
		public function get_sanitized_arg( $key = '', $is_a = 'key', $collection = '' ) {
			$sanitize_method = ( 'bool' === $is_a ? 'rest_sanitize_boolean' : 'sanitize_' . $is_a );
			if ( ! is_array( $collection ) ) {
				$collection = $this->args;
			}

			if ( ! empty( $key ) && isset( $collection[ $key ] ) && ! empty( $collection[ $key ] ) ) {
				return call_user_func( $sanitize_method, $collection[ $key ] );
			}

			if ( ! empty( $key ) ) {
				return false;
			}

			return array_map( $sanitize_method, $collection );
		}

		/** To be implemented in Child Class. Override in Child Class */
		public function get_result_total_count() {
			return 0;
		}

		/** To be implemented in Child Class. Override in Child Class */
		public function get_result_count_data() {
			return 0;
		}

		/** To be implemented in Child Class. Override in Child Class */
		public function get_result_extra_data() {
			return [];
		}

		public function default_args_values() {
			return array();
		}

		/**
		 * @param $result_array
		 * @param string $message
		 * adding extra response data and sending to response handler
		 *
		 * @return mixed
		 */
		public function success_response( $result_array, $message = '' ) {
			BWFCRM_Core()->response_handler->response_code = 200;
			$extra_data                                    = array();
			/** Total Count */
			$total_count = $this->get_result_total_count();
			if ( ! empty( $total_count ) ) {
				$extra_data['total_count'] = $total_count;
			}

			/** Count Data */
			$count_data = $this->get_result_count_data();
			if ( ! empty( $count_data ) ) {
				$extra_data['count_data'] = $count_data;
			}

			/** Extra data */
			$extra_data = $this->get_result_extra_data();
			if ( ! empty( $extra_data ) ) {
				$extra_data['extra_data'] = $extra_data;
			}
			$api_method = ! empty( $this->get_current_api_method() ) ? $this->get_current_api_method() : 'GET';
			/** Pagination */

			if ( isset( $this->pagination->limit ) && ( 0 === $this->pagination->limit || ! empty( $this->pagination->limit ) ) && 'GET' === $api_method ) {
				$extra_data['limit'] = absint( $this->pagination->limit );
			}

			if ( isset( $this->pagination->offset ) && ( 0 === $this->pagination->offset || ! empty( $this->pagination->offset ) ) && 'GET' === $api_method ) {
				$extra_data['offset'] = absint( $this->pagination->offset );
			}

			return BWFCRM_Core()->response_handler->success_response( $result_array, $message, $extra_data );
		}

		/**
		 * This function should be present in all the classes inheriting this class
		 *
		 * @param $data_obj
		 *
		 * @return mixed
		 */
		abstract public function process_api_call();

		protected function get_contact_statuses() {
			return array( "unverified" => 0, "subscribed" => 1, "bounced" => 2, "unsubscribed" => 3 );
		}
	}
}