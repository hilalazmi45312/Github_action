<?php

#[AllowDynamicProperties]
final class BWFAN_Breakdance_Form_Submit extends BWFAN_Event {
	private static $instance = null;
	public $form_post_id = 0;
	public $email = '';
	public $fields = [];
	public $last_name = '';

	public function __construct() {
		$this->event_merge_tag_groups = array( 'breakdance_forms', 'bwf_contact' );
		$this->event_name             = __( 'Breakdance Form Submits', 'wp-marketing-automations-pro' );
		$this->event_desc             = __( 'This event runs after a Breakdance form is submitted', 'wp-marketing-automations-pro' );

		$this->event_rule_groups = array(
			'breakdance_forms',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);

		$this->optgroup_label     = __( 'Breakdance Forms', 'wp-marketing-automations-pro' );
		$this->priority           = 10;
		$this->customer_email_tag = '';

		$this->v2          = true;
		$this->support_v1  = false;
		$this->force_async = false;
	}

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'wp_insert_post', array( $this, 'process' ), 10, 2 );
		add_filter( 'bwfan_get_form_submit_events', array( $this, 'add_breakdance_to_form_submit_events' ), 10, 1 );
		add_action( 'wp_ajax_bwfan_get_breakdance_form_fields', array( $this, 'bwfan_get_breakdance_form_fields' ) );
	}

	public function process( $post_id, $post ) {
		if ( $post->post_type !== 'breakdance_form_res' ) {
			return;
		}

		$data                 = $this->get_default_data();
		$data['form_post_id'] = $post_id;
		$this->send_async_call( $data );
	}

	public function get_event_data() {
		return [
			'global' => [
				'form_post_id'  => $this->form_post_id,
				'fields'        => get_post_meta( $this->form_post_id, '_breakdance_fields', true ),
				'email'         => $this->email,
				'first_name'    => $this->first_name,
				'last_name'     => $this->last_name,
				'contact_phone' => $this->contact_phone,
			],
		];

	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'form_post_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['form_post_id'] ) ) ) {
			$set_data = array(
				'form_post_id' => intval( $task_meta['global']['form_post_id'] ),
				'fields'       => $task_meta['global']['fields'],
				'email'        => $task_meta['global']['email'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		$submitted_post_id = $automation_data['form_post_id'] ?? '';
		$save_form_id      = $automation_data['event_meta']['bwfan-breakdance_form_submit_form_id'] ?? 0;
		$submitted_form_id = get_post_meta( $submitted_post_id, '_breakdance_form_id', true );

		return absint( $submitted_form_id ) === absint( $save_form_id );
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$event_meta          = $automation_data['event_meta']['bwfan-form-field-map'] ?? [];
		$get_email           = $event_meta['bwfan_email_field_map'] ?? '';
		$get_first_name      = $event_meta['bwfan_first_name_field_map'] ?? '';
		$get_last_name       = $event_meta['bwfan_last_name_field_map'] ?? '';
		$get_contact_phone   = $event_meta['bwfan_phone_field_map'] ?? '';
		$form_fields         = get_post_meta( $automation_data['form_post_id'], '_breakdance_fields', true ) ?? [];
		$this->form_post_id  = $automation_data['form_post_id'];
		$this->fields        = $form_fields;
		$this->email         = $get_email && isset( $form_fields[ $get_email ] ) ? $form_fields[ $get_email ] : '';
		$this->first_name    = $get_first_name && isset( $form_fields[ $get_first_name ] ) ? $form_fields[ $get_first_name ] : '';
		$this->last_name     = $get_last_name && isset( $form_fields[ $get_last_name ] ) ? $form_fields[ $get_last_name ] : '';
		$this->contact_phone = $get_contact_phone && isset( $form_fields[ $get_contact_phone ] ) ? $form_fields[ $get_contact_phone ] : '';

		$automation_data['form_post_id']  = $this->form_post_id;
		$automation_data['fields']        = $this->fields;
		$automation_data['email']         = $this->email;
		$automation_data['first_name']    = $this->first_name;
		$automation_data['last_name']     = $this->last_name;
		$automation_data['contact_phone'] = $this->contact_phone;

		BWFAN_PRO_Common::maybe_create_update_contact( $automation_data );

		return $automation_data;
	}


	/**
	 * Fetch Breakdance Form Fields via AJAX using only Form ID
	 */
	public function bwfan_get_breakdance_form_fields() {
		BWFAN_PRO_Common::nocache_headers();

		$form_post_id = absint( $_POST['id'] ?? 0 );
		if ( empty( $form_post_id ) ) {
			wp_send_json( [ 'results' => [] ] );
			exit;
		}

		$fields = $this->get_form_fields( $form_post_id );

		if ( empty( $fields ) ) {
			return [];
		}

		$finalarr = [];
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $key => $value ) {
				$finalarr[] = [ 'key' => $key, 'value' => $value ];
			}
		}

		wp_send_json( [ 'results' => $finalarr ] );
		exit;
	}

	public function get_form_fields( $form_post_id ) {
		if ( empty( $form_post_id ) ) {
			return array();
		}

		return BWFAN_PRO_Common::get_breakdance_form_fields( $form_post_id );
	}

	/**
	 * Get Fields Schema for UI
	 */
	public function get_fields_schema() {
		$forms = $this->get_view_data();
		$forms = empty( $forms ) ? [ '' => __( 'No forms to select', 'wp-marketing-automations-pro' ) ] : array_replace( [ '' => 'Select' ], $forms );
		$forms = BWFAN_PRO_Common::prepared_field_options( $forms );

		return [
			[
				'id'          => 'bwfan-breakdance_form_submit_form_id',
				'type'        => 'select',
				'options'     => $forms,
				'label'       => __( 'Select Form', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Select', 'wp-marketing-automations-pro' ),
				"required"    => true,
				"errorMsg"    => __( "Form is required.", 'wp-marketing-automations-pro' ),
				"description" => ""
			],
			[
				'id'          => 'bwfan-form-field-map',
				'type'        => 'bwf_form_submit',
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_breakdance_form_fields',
				"ajax_field"  => [
					'id' => 'bwfan-breakdance_form_submit_form_id'
				],
				"fieldChange" => 'bwfan-breakdance_form_submit_form_id'
			],
			[
				'id'            => 'bwfan-mark-contact-subscribed',
				'type'          => 'checkbox',
				'checkboxlabel' => __( 'Mark Contact as Subscribed', 'wp-marketing-automations-pro' ),
				'description'   => '',
				"toggler"       => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-breakdance_form_submit_form_id',
							'value' => '',
						),
					),
					'relation' => 'AND',
				]
			]
		];
	}

	/**
	 * Fetch All Breakdance Forms for UI Dropdown
	 */
	public function get_view_data() {
		$options = [];
		$forms   = BWFAN_PRO_Common::get_all_breakdance_forms();

		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				$options[ $form['id'] ] = $form['title'];
			}
		}

		return $options;
	}

	/**
	 * Register Breakdance Form Event
	 */
	public function add_breakdance_to_form_submit_events( $events ) {
		$events[] = 'BWFAN_Breakdance_Form_Submit';

		return $events;
	}
}

return 'BWFAN_Breakdance_Form_Submit';
