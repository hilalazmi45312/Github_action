<?php

#[AllowDynamicProperties]
class BWFCRM_Form_Thrive extends BWFCRM_Form_Base {
	private $total_selections = 1;
	private $source = 'thrive';

	/** Form Submission Captured Data */
	private $form_id = '';
	private $form_title = '';
	private $fields = [];
	private $entry = [];

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
			$url = admin_url( 'admin.php?page=thrive_leads_dashboard#form-type/' . absint( $form_id ) );
		}

		return $url;
	}

	public function capture_async_submission() {
		$this->form_id    = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title = BWFAN_Common::$events_async_data['form_title'];
		$this->entry      = BWFAN_Common::$events_async_data['entry'];
		$this->fields     = BWFAN_Common::$events_async_data['fields'];

		$this->autonami_event = BWFAN_Common::$events_async_data['event'];
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
			if ( ! isset( $mapped_fields[ $key ] ) ) {
				continue;
			}

			$contact_field = is_numeric( $mapped_fields[ $key ] ) ? absint( $mapped_fields[ $key ] ) : $mapped_fields[ $key ];
			if ( 'country' === $contact_field ) {
				$contact_data[ $contact_field ] = BWFAN_PRO_Common::get_country_iso_code( $this->entry[ $key ] );
				continue;
			}

			$contact_data[ $contact_field ] = $this->entry[ $key ];
		}

		return $contact_data;
	}

	public function get_form_fields( $feed ) {
		if ( ! $feed instanceof BWFCRM_Form_Feed ) {
			return BWFCRM_Common::crm_error( __( 'Feed Not Exists ', 'wp-marketing-automations-pro' ) );
		}
		$feed_id = $feed->get_id();

		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'No Feed Exists: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		$form_id = $feed->get_data( 'form_id' );
		/** @var BWFAN_TVE_Lead_Form_Submit $event */
		$event = BWFAN_Core()->sources->get_event( 'tve_lead_form_submit' );
		if ( ! $event instanceof BWFAN_TVE_Lead_Form_Submit ) {
			return BWFCRM_Common::crm_error( __( 'Thrive From integration not found', 'wp-marketing-automations-pro' ) );
		}

		return $event->get_form_fields( $form_id );
	}

	public function get_form_selection( $args, $return_all_available = false ) {

		/** @var BWFAN_TVE_Lead_Form_Submit $event */
		$event = BWFAN_Core()->sources->get_event( 'tve_lead_form_submit' );
		if ( ! $event instanceof BWFAN_TVE_Lead_Form_Submit ) {
			return BWFCRM_Common::crm_error( __( 'Thrive From integration not found', 'wp-marketing-automations-pro' ) );
		}
		$form_options = $event->get_view_data();

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

		/** Get the submitted Form ID */
		$form_id = isset( $args['form_id'] ) && ! empty( $args['form_id'] ) ? $args['form_id'] : false;

		/** Check if the feed with the provided ID exists */
		$feed = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'Feed with ID not exists: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		/** Check If submitted Form ID is valid */
		if ( empty( $form_id ) ) {
			return false;
		}

		/** Unset previous data (if exists), and set new data and save */
		$feed->unset_data( 'form_id' );
		$feed->get_source() !== $this->source && $feed->set_source( $this->source );
		! empty( $form_id ) && $feed->set_data( 'form_id', $form_id );

		return ! ! $feed->save( true );
	}
}

if ( bwfan_is_tve_active() ) {
	BWFCRM_Core()->forms->register( 'thrive', 'BWFCRM_Form_Thrive', 'Thrive Lead Forms', array(
		'tve_lead_form_submit'
	) );
}
