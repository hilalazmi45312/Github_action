<?php
#[AllowDynamicProperties]
class BWFCRM_Form_Fluent_Forms extends BWFCRM_Form_Base {
	private $total_selections = 1;
	private $source = 'fluent';

	/** Form Submission Captured Data */
	private $form_id = '';
	private $form_title = '';
	private $fields = [];
	private $insert_id = '';

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
			$url = admin_url( 'admin.php?page=fluent_forms&route=editor&form_id=' . absint( $form_id ) );
		}

		return $url;
	}

	public function capture_async_submission() {
		$this->form_id    = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title = BWFAN_Common::$events_async_data['form_title'];
		$this->fields     = BWFAN_Common::$events_async_data['fields'];
		$this->insert_id  = BWFAN_Common::$events_async_data['insert_id'];

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
		foreach ( $this->fields as $key => $item ) {
			/** If Entry field is not group of field values ie: name[first, last] */
			if ( ! is_array( $item ) && isset( $mapped_fields[ $key ] ) ) {
				$contact_field                  = is_numeric( $mapped_fields[ $key ] ) ? absint( $mapped_fields[ $key ] ) : $mapped_fields[ $key ];
				$contact_data[ $contact_field ] = $item;
				continue;
			}

			/** If Entry field is a group of field values ie: name[first, last] */
			if ( is_array( $item ) ) {
				$key_value = $this->maybe_get_key_value_from_grouped_field( $key, $item, $mapped_fields );
				if ( is_array( $key_value ) && ! empty( $key_value ) ) {
					$contact_data = array_replace( $contact_data, $key_value );
				}
			}
		}

		return $contact_data;
	}

	public function maybe_get_key_value_from_grouped_field( $entry_key, $entry_value, $mapped_fields ) {
		$key_value = [];
		foreach ( $mapped_fields as $key => $crm_field ) {
			/** Entry Group not matched with $mapped_field key's group */
			if ( false === strpos( $key, $entry_key . ':' ) ) {
				if ( $key === $entry_key && isset( $mapped_fields[ $key ] ) ) {
					$key_value[ $crm_field ] = is_array( $entry_value ) ? wp_json_encode( $entry_value ) : $entry_value;
				}
				continue;
			}

			$mapped_array = explode( ':', $key );
			//$mapped_group = $mapped_array[0];
			$mapped_field = $mapped_array[1];
			if ( isset( $entry_value[ $mapped_field ] ) ) {
				$key_value[ $crm_field ] = is_array( $entry_value[ $mapped_field ] ) ? wp_json_encode( $entry_value[ $mapped_field ] ) : $entry_value[ $mapped_field ];
			}
		}

		return $key_value;
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
		if ( empty( $form_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Form Feed doesn\'t have sufficient data to get fields: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		/** @var BWFAN_Fluent_Form_Submit $event */
		$event = BWFAN_Core()->sources->get_event( 'fluent_form_submit' );
		if ( ! $event instanceof BWFAN_Fluent_Form_Submit ) {
			return BWFCRM_Common::crm_error( __( 'Form FunnelKit Automations Event doesn\'t found for Feed: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		return $event->get_form_fields( $form_id );
	}

	public function get_form_selection( $args, $return_all_available = false ) {
		/** Form ID Handling */
		$fluent_forms = wpFluent()->table( 'fluentform_forms' )->select( [ 'id', 'title' ] )->orderBy( 'id', 'DESC' )->get();
		$form_options = [];

		foreach ( $fluent_forms as $form ) {
			$form_options[ $form->id ] = $form->title;
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

if ( bwfan_is_fluent_forms_active() ) {
	BWFCRM_Core()->forms->register( 'fluent', 'BWFCRM_Form_Fluent_Forms', 'Fluent Forms', array(
		'fluent_form_submit'
	) );
}
