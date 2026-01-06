<?php

#[AllowDynamicProperties]
final class BWFAN_TVE_Architect_Form_Submit extends BWFAN_Event {
	private static $instance = null;
	public $post_id = 0;
	public $form_page_id = 0;
	public $form_title = '';
	public $entry = [];
	public $fields = [];
	public $email = '';
	public $tcb_id = '';
	public $form_identifier = '';
	public $mark_subscribe = false;
	public $first_name = '';
	public $last_name = '';
	public $contact_phone = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'thrive_architect_forms', 'bwf_contact' );
		$this->event_name             = esc_html__( 'Form Submits', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a form is submitted', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'thrive-architect-forms',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = esc_html__( 'Thrive Architect', 'wp-marketing-automations-pro' );
		$this->priority               = 10;
		$this->customer_email_tag     = '';
		$this->v2                     = true;
		$this->support_v1             = false;
		$this->force_async            = false;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'tcb_api_form_submit', array( $this, 'process_form_submit' ), 10, 1 );
		add_action( 'wp_ajax_bwfan_get_thrive_fields', array( $this, 'get_ajax_fields' ) );
		add_filter( 'bwfan_get_form_submit_events', array( $this, 'add_tve_architect_to_form_submit_events' ), 10, 1 );
	}

	/**
	 * @param $form_data
	 *
	 * @return void
	 */
	public function process_form_submit( $form_data ) {
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return;
		}
		$this->form_page_id    = $form_data['post_id'] ?? 0;
		$this->tcb_id          = $form_data['_tcb_id'] ?? 0;
		$this->form_identifier = $form_data['form_identifier'] ?? '';
		$this->email           = $form_data['email'] ?? '';
		$this->entry           = $form_data; // Store all form data in entry

		$data                    = $this->get_default_data();
		$data['form_page_id']    = $this->form_page_id;
		$data['tcb_id']          = $this->tcb_id;
		$data['email']           = $this->email;
		$data['form_identifier'] = $this->form_identifier;
		$data['fields']          = $form_data; // Store all form fields
		$this->send_async_call( $data );
	}

	/**
	 * @return void
	 */
	public function get_ajax_fields() {
		$form_id = absint( $_POST['id'] ?? 0 );
		$fields  = $this->get_form_fields( $form_id );

		if ( isset( $_POST['fromApp'] ) && $_POST['fromApp'] ) {
			$finalarr = [];
			foreach ( $fields as $key => $field ) {
				$finalarr[] = [
					'key'   => $field['key'],
					'value' => $field['value']
				];
			}

			wp_send_json( array(
				'results' => $finalarr
			) );
			exit;
		}

		wp_send_json( array(
			'fields' => $fields,
		) );
	}

	public function get_form_fields( $post_id ) {
		$form_post = get_post( $post_id );
		if ( ! $form_post ) {
			return [];
		}

		$form_settings = json_decode( $form_post->post_content, true );
		if ( empty( $form_settings['inputs'] ) ) {
			return [];
		}

		$fields = [];
		foreach ( $form_settings['inputs'] as $field_id => $field ) {
			if ( isset( $field['type'] ) && in_array( $field['type'], [ 'password', 'confirm_password' ] ) ) {
				continue;
			}

			$fields[] = array(
				'key'   => $field_id,
				'value' => $field['label'] ?? $field_id,
			);
		}

		return $fields;
	}

	/**
	 * Captures and processes V2 form data to map field values, update automation data, and create or update a contact.
	 *
	 * @param array $automation_data An associative array containing automation-related data, including form fields, meta data, and identifiers.
	 *
	 * @return array Updated automation data including mapped field values and contact information.
	 */

	public function capture_v2_data( $automation_data ) {
		$field_map = $automation_data['event_meta']['bwfan-form-field-map'] ?? [];

		$this->form_page_id    = $automation_data['form_page_id'] ?? 0;
		$this->tcb_id          = $automation_data['tcb_id'] ?? 0;
		$this->form_identifier = $automation_data['form_identifier'] ?? '';
		$this->entry           = $automation_data['fields'] ?? [];

		/** Get mapped values */
		$this->email          = $this->get_mapped_value( $field_map, 'email' );
		$this->first_name     = $this->get_mapped_value( $field_map, 'first_name' );
		$this->last_name      = $this->get_mapped_value( $field_map, 'last_name' );
		$this->contact_phone  = $this->get_mapped_value( $field_map, 'phone' );
		$this->mark_subscribe = $automation_data['event_meta']['bwfan-mark-contact-subscribed'] ?? false;

		/**  Update automation data with captured values */
		$automation_data['email']         = $this->email;
		$automation_data['first_name']    = $this->first_name;
		$automation_data['last_name']     = $this->last_name;
		$automation_data['contact_phone'] = $this->contact_phone;

		/** Create/update contact */
		BWFAN_PRO_Common::maybe_create_update_contact( $automation_data );

		return $automation_data;
	}

	/**
	 * @param $map
	 * @param $field
	 *
	 * @return mixed|string
	 */
	private function get_mapped_value( $map, $field ) {
		$map_key  = "bwfan_{$field}_field_map";
		$field_id = $map[ $map_key ] ?? '';

		if ( empty( $field_id ) ) {
			return '';
		}
		if ( isset( $this->entry[ $field_id ] ) ) {
			return $this->entry[ $field_id ];
		}

		foreach ( $this->entry as $key => $value ) {
			if ( is_array( $value ) && isset( $value[ $field_id ] ) ) {
				return $value[ $field_id ];
			}
		}

		return '';
	}

	/**
	 * @return array[]
	 */

	public function get_event_data() {
		return [
			'global' => [
				'form_page_id'    => $this->form_page_id,
				'tcb_id'          => $this->tcb_id,
				'form_identifier' => $this->form_identifier,
				'email'           => $this->email,
				'first_name'      => $this->first_name,
				'last_name'       => $this->last_name,
				'contact_phone'   => $this->contact_phone,
				'fields'          => $this->entry,
			],
		];
	}

	/**
	 * @param $automation_data
	 *
	 * @return bool
	 */

	public function validate_v2_event_settings( $automation_data ) {
		if ( intval( $automation_data['tcb_id'] ) === intval( $automation_data['event_meta']['bwfan-thrive_form_id'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return array
	 */

	public function get_view_data() {
		$forms = get_posts( array(
			'posts_per_page' => - 1,
			'post_type'      => '_tcb_form_settings',
			'post_status'    => 'any',
		) );

		$options = [ '' => __( 'Select a form', 'wp-marketing-automations-pro' ) ];
		foreach ( $forms as $form ) {
			$form_data            = json_decode( $form->post_content, true );
			$form_title           = $form_data['form_identifier'] ?? '';
			$options[ $form->ID ] = $form_title . " (" . get_the_title( $form->post_parent ) . ")";
		}

		return $options;
	}

	/**
	 * @return array[]
	 */

	public function get_fields_schema() {
		$forms = $this->get_view_data();
		$forms = empty( $forms ) ? [ '' => __( 'No forms to select', 'wp-marketing-automations-pro' ) ] : array_replace( [ '' => 'Select' ], $forms );

		$get_forms_list = BWFAN_PRO_Common::prepared_field_options( $forms );

		return [
			[
				'id'          => 'bwfan-thrive_form_id',
				'type'        => 'select',
				'options'     => $get_forms_list,
				'label'       => __( 'Select Form', 'wp-marketing-automations-pro' ),
				'required'    => true,
				'placeholder' => __( 'Select a form', 'wp-marketing-automations-pro' )
			],
			[
				'id'          => 'bwfan-form-field-map',
				'type'        => 'bwf_form_submit',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				'ajax_cb'     => 'bwfan_get_thrive_fields',
				'ajax_field'  => [
					'id' => 'bwfan-thrive_form_id'
				],
				"fieldChange" => 'bwfan-thrive_form_id',
				"toggler"     => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-thrive_form_id',
							'value' => '',
						)
					),
					'relation' => 'AND',
				]
			],
			[
				'id'            => 'bwfan-mark-contact-subscribed',
				'type'          => 'checkbox',
				'checkboxlabel' => __( 'Mark Contact as Subscribed', 'wp-marketing-automations-pro' ),
				"toggler"       => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-thrive_form_id',
							'value' => '',
						)
					),
					'relation' => 'AND',
				]
			]
		];
	}

	/**
	 * Adds the TVE Architect form submit event to the list of events.
	 *
	 * @param array $events An array of existing events.
	 *
	 * @return array Updated array of events including the TVE Architect form submit event.
	 */
	public function add_tve_architect_to_form_submit_events( $events ) {
		$events[] = 'BWFAN_TVE_Architect_Form_Submit';

		return $events;
	}
}

// Register this event to a source (Only for Thrive Architect)
if ( bwfan_is_tve_architect_active() ) {
	return 'BWFAN_TVE_Architect_Form_Submit';
}