<?php

#[AllowDynamicProperties]
class BWFCRM_Form_Funnel_Optin extends BWFCRM_Form_Base {
	private $total_selections = 1;
	private $source = 'funnel_optin';

	/** Form Submission Captured Data */
	private $form_id = '';
	private $form_title = '';
	private $fields = [];
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
		$form_id = intval( $feed->get_data( 'form_id' ) );
		if ( empty( $form_id ) ) {
			return '';
		}

		$url = admin_url( 'admin.php?page=wf-op&edit=' . $form_id );
		if ( version_compare( WFFN_VERSION, 3.0, '>=' ) ) {
			$url = admin_url( 'admin.php?page=bwf&path=/funnel-optin/' . $form_id . '/design' );
		}

		return $url;
	}

	public function capture_async_submission() {
		$this->form_id    = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title = BWFAN_Common::$events_async_data['form_title'];
		$this->fields     = BWFAN_Common::$events_async_data['fields'];
		$this->cid        = isset( BWFAN_Common::$events_async_data['cid'] ) ? absint( BWFAN_Common::$events_async_data['cid'] ) : 0;

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
		$contact_data = [];
		$field_types  = $this->get_field_types();
		foreach ( $this->fields as $key => $item ) {
			if ( ! isset( $mapped_fields[ $key ] ) ) {
				continue;
			}

			$contact_field = is_numeric( $mapped_fields[ $key ] ) ? intval( $mapped_fields[ $key ] ) : $mapped_fields[ $key ];
			$value         = $this->fields[ $key ];
			/** If field type checkbox */
			if ( isset( $field_types[ $key ] ) && 'checkbox' === $field_types[ $key ] ) {
				$value = ( 'on' === $this->fields[ $key ] ) ? 'yes' : 'no';
			}
			$contact_data[ $contact_field ] = $value;
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
			return BWFCRM_Common::crm_error( __( 'Form Feed does not have the sufficient data to get fields: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		return $this->get_optin_form_fields( $form_id );
	}

	/**
	 * Get optin form fields
	 *
	 * @param $form_id
	 *
	 * @return mixed | Array
	 */
	public function get_optin_form_fields( $form_id ) {
		$optin_form_instance = BWFAN_Core()->sources->get_event( 'funnel_optin_form_submit' );
		$fields              = $optin_form_instance->get_form_fields( $form_id );

		return $fields;
	}

	public function get_form_selection( $args, $return_all_available = false ) {
		/** Form ID Handling */
		$optin_form_instance = BWFAN_Core()->sources->get_event( 'funnel_optin_form_submit' );
		$data                = $optin_form_instance->get_view_data();

		$form_options = array( 'default' => $data );
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

		if ( empty( $form_id ) && 'funnel_optin' === $feed->get_source() ) {
			return false;
		}

		$feed->unset_data( 'form_id' );
		$feed->get_source() !== 'funnel_optin' && $feed->set_source( 'funnel_optin' );
		! empty( $form_id ) && $feed->set_data( 'form_id', $form_id );

		return ! ! $feed->save( true );
	}

	/**
	 * Get field types
	 *
	 * @return array
	 */
	public function get_field_types() {
		if ( empty( $this->form_id ) || ! function_exists( 'WFOPP_Core' ) ) {
			return [];
		}

		$form_fields = WFOPP_Core()->optin_pages->form_builder->get_optin_layout( $this->form_id );
		if ( empty( $form_fields ) ) {
			return [];
		}

		$types = [];
		foreach ( $form_fields as $step_field ) {
			/** empty step fields than continue */
			if ( empty( $step_field ) ) {
				continue;
			}
			foreach ( $step_field as $field ) {
				$types[ $field['InputName'] ] = $field['type'];
			}
		}

		return $types;
	}
}

if ( bwfan_is_optin_forms_active() ) {
	BWFCRM_Core()->forms->register( 'funnel_optin', 'BWFCRM_Form_Funnel_Optin', 'FunnelKit Optin', array(
		'funnel_optin_form_submit'
	) );
}
