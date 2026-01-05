<?php

class BWFCRM_Api_Get_Merge_Tags extends BWFCRM_API_Base {

	public static $ins;
	private $_form_merge_tags = [ 'bwfan_contact_confirmation_link' ];
	public $merge_tags = [];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::READABLE;
		$this->route         = 'broadcast/merge-tags';
		$this->response_code = 200;
		$this->merge_tags    = array();
	}

	public function process_api_call() {
		$context         = $this->get_sanitized_arg( 'context', 'text_field' );
		$fetch_for_forms = 'forms' === $context;

		$custom_context = [];
		if ( 'forms' !== $context ) {
			$custom_context = explode( ',', $context );
		}
		$custom_context[] = 'bwfan_default';

		try {
			if ( BWFAN_PRO_Common::is_lite_3_0() ) {
				$merge_tags = BWFAN_Core()->merge_tags->get_localize_tags_with_group( $fetch_for_forms, $custom_context );
				$merge_tags = $this->get_all_data_for_merge_tags( $merge_tags, $fetch_for_forms );
			} else {
				$merge_tags = BWFCRM_Core()->merge_tags->get_registered_grouped_tags( true, $fetch_for_forms );
			}
		} catch ( Error $e ) {
			$this->response_code = 404;
			$response            = $e->getMessage();

			return $this->error_response( $response );
		}

		if ( empty( $merge_tags ) ) {
			$this->response_code = 404;
			$response            = __( "No merge tags found", "wp-marketing-automations-pro" );

			return $this->error_response( $response );
		}
		$this->merge_tags = $merge_tags;

		return $this->success_response( $merge_tags, __( 'Got All Merge Tags', 'wp-marketing-automations-pro' ) );
	}

	public function get_all_data_for_merge_tags( $merge_tags, $fetch_for_forms ) {
		$merge_tags_data = [];

		$broadcast_merge_tag_groups = array_keys( $merge_tags );
		$form_merge_tags            = $this->_form_merge_tags;

		$custom_tag_data = [];
		foreach ( $broadcast_merge_tag_groups as $merge_tag_group ) {
			if ( ! isset( $merge_tags[ $merge_tag_group ] ) ) {
				continue;
			}
			/** creating custom contact field merge tag if present */
			if ( isset( $merge_tags[ $merge_tag_group ]['bwfan_contact_field'] ) ) {
				$field_merge_tags = $merge_tags[ $merge_tag_group ]['bwfan_contact_field'];
				$custom_tag_data  = $this->get_contact_fields_tags( $field_merge_tags->get_priority() );
				unset( $merge_tags[ $merge_tag_group ]['bwfan_contact_field'] ); // unsetting so that it will not loop over again
			}

			$tag_data = array_map( function ( $tags ) use ( $fetch_for_forms, $form_merge_tags ) {
				if ( false === $fetch_for_forms && in_array( $tags->get_name(), $form_merge_tags ) ) {
					return false;
				}

				$data = [
					'tag_name'        => $tags->get_name(),
					'tag_description' => $tags->get_description(),
					'priority'        => $tags->get_priority(),
				];
				if ( method_exists( $tags, 'get_setting_schema' ) ) {
					$data['schema'] = $tags->get_setting_schema();
				}
				if ( method_exists( $tags, 'get_default_values' ) ) {
					$data['default_val'] = $tags->get_default_values();
				}

				return $data;
			}, $merge_tags[ $merge_tag_group ] );

			if ( ! empty( $custom_tag_data ) ) {
				$tag_data = array_merge( $tag_data, $custom_tag_data );
			}

			$tag_data = array_filter( $tag_data );
			uasort( $tag_data, function ( $a, $b ) {
				return $a['priority'] <= $b['priority'] ? - 1 : 1;
			} );

			if ( ! empty( $merge_tags[ $merge_tag_group ] ) ) {
				$merge_tags_data[ $merge_tag_group ] = array_merge( $merge_tags[ $merge_tag_group ], $tag_data );
			} else {
				$merge_tags_data[ $merge_tag_group ] = $tag_data;
			}
		}

		foreach ( $merge_tags_data as $group => $merge_tag_det ) {
			foreach ( $merge_tag_det as $key => $v ) {
				if ( false === $fetch_for_forms && in_array( $key, $this->_form_merge_tags ) ) {
					unset( $merge_tags_data[ $group ][ $key ] );
				}
			}
		}

		return $merge_tags_data;
	}

	/**
	 * Fetching all the contact custom fields for creating merge tag
	 *
	 * @param $priority
	 *
	 * @return array
	 */
	public function get_contact_fields_tags( $priority ) {
		$fields = BWFCRM_Fields::get_custom_fields( 1, 1, null );

		$return = [];
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['slug'] ) ) {
				continue;
			}

			$field_group = 'contact_field key="' . $field['slug'] . '"';

			$return[ $field_group ]['tag_name']        = $field_group;
			$return[ $field_group ]['tag_description'] = $field['name'];
			$return[ $field_group ]['priority']        = $priority;
		}

		return $return;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Merge_Tags' );
