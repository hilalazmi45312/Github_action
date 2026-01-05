<?php

/**
 * BWFAN_Divi_Forms_Common
 */
class BWFAN_Divi_Forms_Common {

	/**
	 * Extract form info from the Divi shortcode from wp post
	 *
	 * @return array
	 */
	public static function extract_forms_and_fields() {
		global $wpdb;
		$query      = $wpdb->prepare( "SELECT `ID`, `post_content`, `post_title` FROM $wpdb->posts WHERE post_status NOT IN('trash', 'inherit', 'auto-draft') AND post_type IS NOT NULL AND post_type NOT LIKE %s AND post_content LIKE %s", 'revision', '%%et_pb_contact_form%%' );
		$form_posts = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$data = array();
		if ( empty( $form_posts ) ) {
			return $data;
		}
		foreach ( $form_posts as $form_post ) {
			// Get forms
			$pattern_regex = '/\[et_pb_contact_form(.*?)](.+?)\[\/et_pb_contact_form]/';
			preg_match_all( $pattern_regex, $form_post->post_content, $forms, PREG_SET_ORDER );
			if ( empty( $forms ) ) {
				continue;
			}

			foreach ( $forms as $form_number => $form ) {
				$pattern_form = get_shortcode_regex( array( 'et_pb_contact_form' ) );
				preg_match_all( "/$pattern_form/", $form[0], $forms_extracted, PREG_SET_ORDER );

				if ( empty( $forms_extracted ) ) {
					continue;
				}

				foreach ( $forms_extracted as $form_extracted ) {
					$form_attrs = shortcode_parse_atts( $form_extracted[3] );
					$form_id    = isset( $form_attrs['_unique_id'] ) ? $form_attrs['_unique_id'] : '';
					if ( empty( $form_id ) ) {
						continue;
					}
					$form_id    = sprintf( '%d-%s', $form_post->ID, $form_id ) . "-$form_number";
					$form_title = isset( $form_attrs['title'] ) ? $form_attrs['title'] : __( 'No form title', 'wp-marketing-automations-pro' );
					$form_title = sprintf( '%s - %s', $form_post->post_title, $form_title );
					// extracting form fields from each form
					$fields = self::extract_form_fields( $form[0] );

					$data[ $form_id ]['title']  = $form_title;
					$data[ $form_id ]['fields'] = $fields;
				}
			}
		}

		return $data;
	}

	/**
	 * Extracting fields from the form shortcode
	 *
	 * @param $content_shortcode
	 *
	 * @return array
	 */
	public static function extract_form_fields( $content_shortcode ) {
		$fields  = array();
		$pattern = get_shortcode_regex( array( 'et_pb_contact_field' ) );

		preg_match_all( "/$pattern/", $content_shortcode, $contact_fields, PREG_SET_ORDER );

		if ( empty( $contact_fields ) ) {
			return $fields;
		}

		foreach ( $contact_fields as $contact_field ) {
			$contact_field_attrs = shortcode_parse_atts( $contact_field[3] );
			$field_id            = strtolower( self::array_get( $contact_field_attrs, 'field_id' ) );

			$fields[] = array(
				'field_title' => self::array_get( $contact_field_attrs, 'field_title', __( 'No title', 'wp-marketing-automations-pro' ) ),
				'field_id'    => $field_id,
				'field_type'  => self::array_get( $contact_field_attrs, 'field_type', 'text' )
			);
		}

		return $fields;
	}

	/**
	 * @param $array
	 * @param $address
	 * @param $default
	 * extract each field from fields array
	 *
	 * @return mixed|string
	 */
	public static function array_get( $array, $address, $default = '' ) {
		$keys  = is_array( $address ) ? $address : explode( '.', $address );
		$value = $array;

		foreach ( $keys as $key ) {
			if ( ! empty( $key ) && isset( $key[0] ) && '[' === $key[0] ) {
				$index = substr( $key, 1, - 1 );

				if ( is_numeric( $index ) ) {
					$key = (int) $index;
				}
			}

			if ( ! isset( $value[ $key ] ) ) {
				return $default;
			}

			$value = $value[ $key ];
		}

		return $value;
	}

	public static function extract_unique_form_id( $form_id ) {
		if ( strpos( $form_id, '-' ) === false ) {
			return $form_id;
		}
		$form_unique_id = explode( '-', $form_id );
		/** Remove form number */
		array_pop( $form_unique_id );
		/** Remove post id */
		array_shift( $form_unique_id );

		return implode( '-', $form_unique_id );
	}

}
