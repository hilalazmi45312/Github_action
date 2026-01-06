<?php
#[AllowDynamicProperties]
class BWFCRM_Form_Formidable extends BWFCRM_Form_Base {
	private $total_selections = 1;
	private $source = 'formidable';

	/** Form Submission Captured Data */
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
	private $autonami_event = '';

	public function get_source() {
		return $this->source;
	}

	/**
	 * @param BWFCRM_Form_Feed $feed
	 *
	 * @return string|void
	 */
	public function get_form_link( $feed ) {
		$url     = '';
		$form_id = $feed->get_data( 'form_id' );
		if ( $form_id ) {
			$url = admin_url( 'admin.php?page=formidable-cform-wizard&id=' . absint( $form_id ) );
		}

		return $url;
	}

	/**
	 * Async
	 *
	 */

	public function capture_async_submission() {
		$this->entry          = isset( BWFAN_Common::$events_async_data['entry'] ) ? BWFAN_Common::$events_async_data['entry'] : array();
		$this->fields         = isset( BWFAN_Common::$events_async_data['fields'] ) ? BWFAN_Common::$events_async_data['fields'] : array();
		$this->autonami_event = isset( BWFAN_Common::$events_async_data['event'] ) ? BWFAN_Common::$events_async_data['event'] : '';
		$this->form_id        = isset( BWFAN_Common::$events_async_data['form_id'] ) ? BWFAN_Common::$events_async_data['form_id'] : '';
		$this->form_title     = isset( BWFAN_Common::$events_async_data['form_title'] ) ? BWFAN_Common::$events_async_data['form_title'] : '';
		$this->first_name     = isset( BWFAN_Common::$events_async_data['first_name'] ) ? BWFAN_Common::$events_async_data['first_name'] : '';
		$this->last_name      = isset( BWFAN_Common::$events_async_data['last_name'] ) ? BWFAN_Common::$events_async_data['last_name'] : '';
		$this->contact_phone  = isset( BWFAN_Common::$events_async_data['contact_phone'] ) ? BWFAN_Common::$events_async_data['contact_phone'] : '';
		$this->entry_id       = isset( BWFAN_Common::$events_async_data['entry_id'] ) ? BWFAN_Common::$events_async_data['entry_id'] : '';
		$this->email          = isset( BWFAN_Common::$events_async_data['email'] ) ? BWFAN_Common::$events_async_data['email'] : '';
		$this->find_feeds_and_create_contacts();
	}

	public function filter_feeds_for_current_entry() {
		return array_filter( array_map( function ( $feed ) {
			$feed_form_id = $feed->get_data( 'form_id' );
			if ( absint( $this->form_id ) !== absint( $feed_form_id ) ) {
				return false;
			}

			return $feed;
		}, $this->feeds ) );
	}

	public function prepare_contact_data_from_feed_entry( $mapped_fields ) {
		$contact_data = array();
		foreach ( $this->entry as $key => $item ) {
			if ( isset( $mapped_fields[ $key ] ) ) {
				$contact_field                  = is_numeric( $mapped_fields[ $key ] ) ? absint( $mapped_fields[ $key ] ) : $mapped_fields[ $key ];
				$contact_data[ $contact_field ] = $this->entry[ $key ];
			}
		}

		return $contact_data;
	}

	public function get_form_fields( $feed ) {

		if ( ! $feed instanceof BWFCRM_Form_Feed ) {
			return BWFCRM_Common::crm_error( __( 'Feed  not Exists: ', 'wp-marketing-automations-pro' ) );
		}
		$feed_id = $feed->get_id();
		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'No Feed Exists: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		$form_id = $feed->get_data( 'form_id' );
		if ( empty( $form_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Form Feed doesn\'t have sufficient data to get fields: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		return $this->get_formidable_form_fields( $form_id );
	}

	public function get_formidable_form_fields( $form_id ) {
		if ( empty( $form_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Form Feed doesn\'t have sufficient data to get fields: ' . $form_id, 'wp-marketing-automations-pro' ) );
		}

		/** @var BWFAN_Formidable_Form_Submit $event */
		$event = BWFAN_Core()->sources->get_event( 'formidable_form_submit' );
		if ( ! $event instanceof BWFAN_Formidable_Form_Submit ) {
			return BWFCRM_Common::crm_error( __( 'Form Funnelkit Automations Event doesn\'t found for Feed: ' . $form_id, 'wp-marketing-automations-pro' ) );
		}

		$final_arr   = [];
		$form_fields = FrmField::get_all_for_form( $form_id );

		// get id and name of non-hidden fields
		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $field ) {
				if ( ! isset( $field->name ) || ! isset( $field->id ) ) {
					continue;
				}
				$final_arr[ $field->id ] = $field->name;
			}
		}

		return $final_arr;
	}

	/**
	 * Select form
	 *
	 * @param $args
	 * @param $return_all_available
	 *
	 * @return array[]
	 */
	public function get_form_selection( $args, $return_all_available = false ) {
		/** Form ID Handling */
		$form_options = [];
		$forms        = FrmForm::getAll();
		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				if ( $form->status !== 'published' ) {
					continue;
				}
				$form_options[ $form->id ] = $form->name;
			}
		}

		$form_options = array( 'default' => $form_options );
		$form_options = $this->get_step_selection_array( 'Form', 'form_id', 1, $form_options );

		return $form_options;
	}

	public function get_total_selection_steps() {
		return $this->total_selections;
	}

	public function get_meta() {
		return array(
			'form_selection_fields' => array(
				'form_id' => 'Form ID'
			)
		);
	}

	/**
	 * @param $args
	 * @param $feed_id
	 *
	 * @return bool|WP_Error
	 */
	public function update_form_selection( $args, $feed_id ) {
		if ( empty( $feed_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Empty Feed ID provided', 'wp-marketing-automations-pro' ) );
		}

		$form_id = isset( $args['form_id'] ) && ! empty( $args['form_id'] ) ? $args['form_id'] : false;
		$feed    = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'Feed with ID not exists: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $form_id ) && $this->source === $feed->get_source() ) {
			return false;
		}

		$feed->unset_data( 'form_id' );
		$feed->get_source() !== $this->source && $feed->set_source( $this->source );
		! empty( $form_id ) && $feed->set_data( 'form_id', $form_id );

		return ! ! $feed->save( true );
	}
}

if ( function_exists( 'bwfan_is_formidable_forms_active' ) && bwfan_is_formidable_forms_active() ) {
	BWFCRM_Core()->forms->register( 'formidable', 'BWFCRM_Form_Formidable', 'Formidable', array(
		'formidable_form_submit'
	) );
}
