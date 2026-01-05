<?php
#[AllowDynamicProperties]
abstract class BWFCRM_Merge_Tag_Base {
	protected $_tag_name = '';
	protected $_tag_description = '';

	public function shortcode_callback( $atts = array() ) {
		$tag_data = BWFCRM_Core()->merge_tags->get_data();
		$atts     = is_array( $atts ) ? array_replace( $atts, $tag_data ) : $tag_data;

		/** assign current user id */
		if ( isset( $atts['is_preview'] ) && true === $atts['is_preview'] && empty( $atts['contact_id'] ) ) {
			$contact = BWFCRM_Common::get_current_user_crm_object();
			if ( ! empty( $contact ) ) {
				$atts['contact_id'] = $contact->get_id();
			}
		}

		$final_val = isset( $atts['is_preview'] ) && true === $atts['is_preview'] ? $this->get_dummy_value( $atts ) : $this->get_value( $atts );
		if ( $final_val instanceof WP_Error ) {
			BWFAN_Core()->logger->log( $final_val->get_error_message() . ': ' . get_class( $this ), 'crm_errors' );

			return '';
		}

		return $final_val;
	}

	public function get_value( $data = array() ) {
		return '';
	}

	public function get_dummy_value( $atts ) {
		return '';
	}

	public function get_name() {
		return $this->_tag_name;
	}

	public function get_description() {
		return $this->_tag_description;
	}

	public function get_array() {
		return [
			'name'        => $this->get_name(),
			'description' => $this->get_description()
		];
	}

	/**
	 * Get date value in WordPress set date format
	 *
	 * @param $date_value
	 *
	 * @return string
	 */
	public function get_formatted_date_value( $date_value ) {
		if ( empty( $date_value ) ) {
			return '';
		}
		if ( false === $this->validate_date( $date_value ) ) {
			return $date_value;
		}
		$date_format = get_option( 'date_format' ); // e.g. "F j, Y"
		$date_value  = date( $date_format, strtotime( $date_value ) );

		return $date_value;
	}

	/**
	 * Validate date
	 *
	 * @param $date
	 * @param string $format
	 *
	 * @return bool
	 */
	public function validate_date( $date, $format = 'Y-m-d' ) {
		$d = $date !== null ? DateTime::createFromFormat( $format, $date ) : '';

		return $d && $d->format( $format ) === $date;
	}

	public function maybe_get_contact( $data = array() ) {
		if ( ! isset( $data['contact_id'] ) || empty( $data['contact_id'] ) ) {
			return BWFCRM_Common::crm_error( __( 'No Contact ID passed in merge tag', 'wp-marketing-automations-pro' ) );
		}

		$contact_id = absint( $data['contact_id'] );
		$contact    = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			return BWFCRM_Common::crm_error( __( "No contact found with ID: $contact_id", 'wp-marketing-automations-pro' ) );
		}

		return $contact;
	}
}
