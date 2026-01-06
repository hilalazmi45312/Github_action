<?php

#[AllowDynamicProperties]
final class BWFAN_Formidable_Form_Submit extends BWFAN_Event {
	private static $instance = null;
	public $form_id = 0;
	public $form_title = '';
	public $entry = [];
	public $fields = [];
	public $email = '';
	public $entry_id = '';
	public $first_name = '';
	public $last_name = '';
	public $contact_phone = '';
	public $mark_subscribe = false;

	private function __construct() {
		// merge tags of this integration will be visible in these merge tag group.
		$this->event_merge_tag_groups = array( 'formidable_forms', 'bwf_contact' );
		$this->event_name             = esc_html__( 'Form Submits', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a form is submitted', 'wp-marketing-automations-pro' );

		// rules of this integration will be visible in these rule groups
		$this->event_rule_groups  = array(
			'formidable_forms',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label     = esc_html__( 'Formidable Forms', 'wp-marketing-automations-pro' );
		$this->priority           = 10;
		$this->customer_email_tag = '';

		// v2 and support_v1 property allows to control the visibility of this event in respective versions
		$this->v2          = true;
		$this->support_v1  = false;
		$this->force_async = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_filter( 'bwfan_get_form_submit_events', array( $this, 'add_formidable_to_form_submit_events' ), 10, 1 );
		add_action( 'wp_ajax_bwfan_get_formidable_form_fields', array( $this, 'bwfan_get_formidable_form_fields' ) );
		add_action( 'frm_success_action', array( $this, 'process' ), 10, 5 );
	}

	public function bwfan_get_formidable_form_fields() {
		BWFAN_PRO_Common::nocache_headers();
		$form_id = absint( sanitize_text_field( $_POST['id'] ) ); // WordPress.CSRF.NonceVerification.NoNonceVerification
		$fields  = [];

		/** If form not selected then return empty array  */
		if ( empty( $form_id ) ) {
			wp_send_json( array(
				'results' => $fields,
			) );
		}

		/** Fetch the field of the selected form */
		$fields = $this->get_form_fields( $form_id );


		$finalarr = [];
		if ( ! empty( $fields ) ) {
			$finalarr = array_map( function ( $f_key, $f_value ) {
				return array(
					'key'   => $f_key,
					'value' => $f_value
				);
			}, array_keys( $fields ), array_values( $fields ) );

		}

		wp_send_json( array(
			'results' => $finalarr
		) );
		exit;
	}

	/**
	 * getting published forms : id and name
	 * @return array
	 */
	public function get_view_data() {
		$options = [];
		$forms   = FrmForm::getAll();

		/** Get id and name of the published forms */
		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				if ( $form->status !== 'published' ) {
					continue;
				}
				$options[ $form->id ] = $form->name;
			}
		}

		return $options;
	}

	/**
	 * @param $form_id
	 * getting form non-hidden fields : id and name
	 *
	 * @return array
	 */
	public function get_form_fields( $form_id ) {
		if ( empty( $form_id ) ) {
			return array();
		}

		$form_fields = FrmField::get_all_for_form( $form_id );
		$fields      = array();

		/**  get id and name of fields */
		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $field ) {
				if ( ! isset( $field->name ) || ! isset( $field->id ) ) {
					continue;
				}

				$fields[ $field->id ] = $field->name;
			}
		}

		return $fields;
	}

	/**
	 * @param $conf_method
	 * @param $form
	 * @param $form_options
	 * @param $entry_id
	 * @param $extra_args
	 *
	 * @return void
	 */
	public function process( $conf_method, $form, $form_options, $entry_id, $extra_args ) {

		// return if form_id or entry id is missing
		if ( empty( $form->id ) || empty( $entry_id ) ) {
			return;
		}

		$data               = $this->get_default_data();
		$data['form_id']    = $form->id;
		$data['form_title'] = isset( $form->name ) ? $form->name : '';
		$data['entry_id']   = $entry_id;
		$fields_array       = [];

		//fetch the entries by entry id and store in data with entry index
		$entry_metas = FrmEntryMeta::get_entry_meta_info( $entry_id );

		$entries = array();
		if ( ! empty( $entry_metas ) && is_array( $entry_metas ) ) {
			foreach ( $entry_metas as $meta ) {
				$entries[ $meta->field_id ] = maybe_unserialize( $meta->meta_value );
			}
		}

		$data['entry'] = $entries;
		// extracting field and storing in data with index fields
		if ( isset( $extra_args['fields'] ) && is_array( $extra_args['fields'] ) ) {
			foreach ( $extra_args['fields'] as $field ) {
				$fields_array[ $field->id ] = $field->name;
			}
		}
		$data['fields'] = $fields_array;

		$this->send_async_call( $data );
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		$email_map   = $automation_data['event_meta']['email_map'];
		$this->email = ( ! empty( $email_map ) && isset( $this->entry[ $email_map ] ) && is_email( $this->entry[ $email_map ] ) ) ? $this->entry[ $email_map ] : '';

		BWFAN_Core()->rules->setRulesData( $this->form_id, 'form_id' );
		BWFAN_Core()->rules->setRulesData( $this->form_title, 'form_title' );
		BWFAN_Core()->rules->setRulesData( $this->entry, 'entry' );
		BWFAN_Core()->rules->setRulesData( $this->entry_id, 'entry_id' );
		BWFAN_Core()->rules->setRulesData( $this->fields, 'fields' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->email, $this->get_user_id_event() ), 'bwf_customer' );
	}

	/**
	 * @return bool|int
	 */
	public function get_user_id_event() {
		if ( is_email( $this->email ) ) {
			$user = get_user_by( 'email', $this->email );

			return ( $user instanceof WP_User ) ? $user->ID : false;
		}

		return false;
	}

	/**
	 * Registers the tasks for current event.
	 *
	 * @param $automation_id
	 * @param $integration_data
	 * @param $event_data
	 */
	public function register_tasks( $automation_id, $integration_data, $event_data ) {
		if ( ! is_array( $integration_data ) ) {
			return;
		}

		$data_to_send = $this->get_event_data();

		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	/**
	 * @return array
	 */
	public function get_event_data() {
		$data_to_send                         = [ 'global' => [] ];
		$data_to_send['global']['form_id']    = $this->form_id;
		$data_to_send['global']['form_title'] = $this->form_title;
		$data_to_send['global']['entry']      = $this->entry;
		$data_to_send['global']['fields']     = $this->fields;
		$data_to_send['global']['entry_id']   = $this->entry_id;
		$data_to_send['global']['email']      = $this->email;

		return $data_to_send;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'form_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['form_id'] ) ) ) {
			$set_data = array(
				'form_id'    => intval( $task_meta['global']['form_id'] ),
				'form_title' => $task_meta['global']['form_title'],
				'entry'      => $task_meta['global']['entry'],
				'fields'     => $task_meta['global']['fields'],
				'entry_id'   => $task_meta['global']['entry_id'],
				'email'      => $task_meta['global']['email'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * @return bool|string
	 */
	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		if ( absint( $automation_data['form_id'] ) !== absint( $automation_data['event_meta']['bwfan-formidable_form_submit_form_id'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$map_fields     = isset( $automation_data['event_meta']['bwfan-form-field-map'] ) ? $automation_data['event_meta']['bwfan-form-field-map'] : [];
		$email_map      = isset( $map_fields['bwfan_email_field_map'] ) ? $map_fields['bwfan_email_field_map'] : '';
		$first_name_map = isset( $map_fields['bwfan_first_name_field_map'] ) ? $map_fields['bwfan_first_name_field_map'] : '';
		$last_name_map  = isset( $map_fields['bwfan_last_name_field_map'] ) ? $map_fields['bwfan_last_name_field_map'] : '';
		$phone_map      = isset( $map_fields['bwfan_phone_field_map'] ) ? $map_fields['bwfan_phone_field_map'] : '';

		$this->form_id    = isset( BWFAN_Common::$events_async_data['form_id'] ) ? BWFAN_Common::$events_async_data['form_id'] : '';
		$this->form_title = isset( BWFAN_Common::$events_async_data['form_title'] ) ? BWFAN_Common::$events_async_data['form_title'] : '';
		$this->entry      = isset( BWFAN_Common::$events_async_data['entry'] ) ? BWFAN_Common::$events_async_data['entry'] : '';
		$this->fields     = isset( BWFAN_Common::$events_async_data['fields'] ) ? BWFAN_Common::$events_async_data['fields'] : [];
		$this->entry_id   = isset( BWFAN_Common::$events_async_data['entry_id'] ) ? BWFAN_Common::$events_async_data['entry_id'] : '';

		$this->email                                = ( ! empty( $email_map ) && isset( $this->entry[ $email_map ] ) && is_email( $this->entry[ $email_map ] ) ) ? $this->entry[ $email_map ] : '';
		$first_name                                 = ( ! empty( $first_name_map ) && isset( $this->entry[ $first_name_map ] ) ) ? $this->entry[ $first_name_map ] : '';
		$this->first_name                           = ( is_array( $first_name ) ) ? implode( " ", array_values( $first_name ) ) : $first_name;
		$this->last_name                            = ( ! empty( $last_name_map ) && isset( $this->entry[ $last_name_map ] ) ) ? $this->entry[ $last_name_map ] : '';
		$this->contact_phone                        = ( ! empty( $phone_map ) && isset( $this->entry[ $phone_map ] ) ) ? $this->entry[ $phone_map ] : '';
		$this->mark_subscribe                       = isset( $automation_data['event_meta']['bwfan-mark-contact-subscribed'] ) ? $automation_data['event_meta']['bwfan-mark-contact-subscribed'] : 0;
		$automation_data['form_id']                 = $this->form_id;
		$automation_data['form_title']              = $this->form_title;
		$automation_data['fields']                  = $this->fields;
		$automation_data['email']                   = $this->email;
		$automation_data['entry']                   = $this->entry;
		$automation_data['entry_id']                = $this->entry_id;
		$automation_data['first_name']              = $this->first_name;
		$automation_data['last_name']               = $this->last_name;
		$automation_data['contact_phone']           = $this->contact_phone;
		$automation_data['mark_contact_subscribed'] = $this->mark_subscribe;

		BWFAN_PRO_Common::maybe_create_update_contact( $automation_data );

		return $automation_data;
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		$forms = $this->get_view_data();
		$forms = empty( $forms ) ? [ '' => 'No forms to select' ] : array_replace( [ '' => 'Select' ], $forms );
		$forms = BWFAN_PRO_Common::prepared_field_options( $forms );

		return [
			[
				'id'          => 'bwfan-formidable_form_submit_form_id',
				'type'        => 'select',
				'options'     => $forms,
				'label'       => __( 'Select Form', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => 'Select',
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
				"ajax_cb"     => 'bwfan_get_formidable_form_fields',
				"ajax_field"  => [
					'id' => 'bwfan-formidable_form_submit_form_id'
				],
				"fieldChange" => 'bwfan-formidable_form_submit_form_id',
				"toggler"     => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-formidable_form_submit_form_id',
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
				'description'   => '',
				"toggler"       => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-formidable_form_submit_form_id',
							'value' => '',
						),
					),
					'relation' => 'AND',
				]
			]
		];
	}

	public function add_formidable_to_form_submit_events( $events ) {
		$events[] = 'BWFAN_Formidable_Form_Submit';

		return $events;
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( function_exists( 'bwfan_is_formidable_forms_active' ) && bwfan_is_formidable_forms_active() ) {
	return 'BWFAN_Formidable_Form_Submit';
}
