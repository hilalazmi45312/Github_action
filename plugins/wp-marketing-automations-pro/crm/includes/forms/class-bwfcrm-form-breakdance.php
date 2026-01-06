<?php

#[AllowDynamicProperties]
class BWFCRM_Form_Breakdance extends BWFCRM_Form_Base {
	private $total_selections = 1;
	private $source = 'breakdance';

	/** Form Submission Captured Data */
	public $form_id = 0;
	public $form_title = '';
	public $fields = [];
	public $email = '';
	public $first_name = '';
	public $last_name = '';
	public $contact_phone = '';
	public $mark_subscribe = false;
	public $entry = [];
	private $autonami_event = '';

	public function get_source() {
		return $this->source;
	}

	/**
	 * Get the Breakdance form editor link.
	 *
	 * @param BWFCRM_Form_Feed $feed
	 *
	 * @return string
	 */
	public function get_form_link( $feed ) {
		$form_id = $feed->get_data( 'form_id' );
		$page_id = $feed->get_data( 'page_id' );

		if ( $form_id && $page_id ) {
			return admin_url( 'post.php?post=' . absint( $page_id ) . '&action=edit' ); // Fixed missing closing parenthesis
		}

		return '';
	}

	/**
	 * Capture asynchronous form submission.
	 */
	public function capture_async_submission() {
		$this->form_post_id = isset( BWFAN_Common::$events_async_data['form_post_id'] ) ? BWFAN_Common::$events_async_data['form_post_id'] : '';
		$this->form_id      = get_post_meta( $this->form_post_id, '_breakdance_form_id', true );
		$form_fields        = get_post_meta( $this->form_post_id, '_breakdance_fields', true );
		$this->email        = sanitize_email( $form_fields['email'] ?? '' );
		$this->entry        = $form_fields;
		$this->find_feeds_and_create_contacts();
	}

	/**
	 * Filter feeds for the current form entry.
	 *
	 * @return array
	 */
	public function filter_feeds_for_current_entry() {
		return array_filter( $this->feeds, function ( $feed ) {
			$feed_form_id = $feed->get_data( 'form_id' );

			return absint( $this->form_id ) === absint( $feed_form_id );
		} );
	}

	/**
	 * Prepare contact data from mapped fields.
	 *
	 * @param array $mapped_fields
	 *
	 * @return array
	 */
	public function prepare_contact_data_from_feed_entry( $mapped_fields ) {
		$contact_data = [];
		foreach ( $this->entry as $key => $item ) {
			if ( isset( $mapped_fields[ $key ] ) ) {
				$contact_field                  = is_numeric( $mapped_fields[ $key ] ) ? absint( $mapped_fields[ $key ] ) : sanitize_text_field( $mapped_fields[ $key ] );
				$contact_data[ $contact_field ] = sanitize_text_field( $this->entry[ $key ] );
			}
		}

		return $contact_data;
	}

	/**
	 * Get form fields for a specific feed.
	 *
	 * @param BWFCRM_Form_Feed $feed
	 *
	 * @return array|WP_Error
	 */
	public function get_form_fields( $feed ) {
		$form_id = $feed->get_data( 'form_id' );

		if ( empty( $form_id ) ) {
			return BWFCRM_Common::crm_error( __( 'No Form ID provided', 'wp-marketing-automations-pro' ) );
		}

		return $this->get_breakdance_form_fields( $form_id );
	}

	/**
	 * Get Breakdance form fields by form ID.
	 *
	 * @param int $form_id
	 *
	 * @return array|WP_Error
	 */
	public function get_breakdance_form_fields( $form_id ) {
		if ( empty( $form_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Form Feed doesn\'t have sufficient data to get fields: ' . $form_id, 'wp-marketing-automations-pro' ) );
		}

		/** @var BWFAN_Breakdance_Form_Submit $event */
		$event = BWFAN_Core()->sources->get_event( 'breakdance_form_submit' );
		if ( ! $event instanceof BWFAN_Breakdance_Form_Submit ) {
			return BWFCRM_Common::crm_error( __( 'Form Funnelkit Automations Event not found for Feed: ' . $form_id, 'wp-marketing-automations-pro' ) );
		}

		$final_arr   = [];
		$form_fields = BWFAN_PRO_Common::get_breakdance_form_fields( $form_id );

		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $key => $field_name ) {
				if ( ! empty( $key ) && ! empty( $field_name ) ) {
					$final_arr[ $key ] = sanitize_text_field( $field_name );
				}
			}
		}

		return $final_arr;
	}

	/**
	 * Get form selection options.
	 *
	 * @param array $args
	 * @param bool $return_all_available
	 *
	 * @return array
	 */
	public function get_form_selection( $args, $return_all_available = false ) {
		$form_options = [];
		$forms        = BWFAN_PRO_Common::get_all_breakdance_forms();

		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				$form_options[ $form['id'] ] = esc_html( $form['title'] );
			}
		}

		return $this->get_step_selection_array( 'Form', 'form_id', 1, [ 'default' => $form_options ] );
	}

	public function get_total_selection_steps() {
		return $this->total_selections;
	}

	public function get_meta() {
		return [
			'form_selection_fields' => [
				'form_id' => 'Form ID'
			]
		];
	}

	/**
	 * Update form selection for a feed.
	 *
	 * @param array $args
	 * @param int $feed_id
	 *
	 * @return bool|WP_Error
	 */
	public function update_form_selection( $args, $feed_id ) {
		if ( empty( $feed_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Empty Feed ID provided', 'wp-marketing-automations-pro' ) );
		}

		$form_id = isset( $args['form_id'] ) && ! empty( $args['form_id'] ) ? absint( $args['form_id'] ) : false;
		$feed    = new BWFCRM_Form_Feed( $feed_id );

		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'Feed with ID does not exist: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $form_id ) && $this->source === $feed->get_source() ) {
			return false;
		}

		$feed->unset_data( 'form_id' );
		$feed->get_source() !== $this->source && $feed->set_source( $this->source );
		! empty( $form_id ) && $feed->set_data( 'form_id', $form_id );

		return $feed->save( true );
	}
}

if ( function_exists( 'bwfan_is_breakdance_active' ) && bwfan_is_breakdance_active() ) {
	BWFCRM_Core()->forms->register( 'breakdance', 'BWFCRM_Form_Breakdance', 'Breakdance', [ 'breakdance_form_submit' ] );
}
