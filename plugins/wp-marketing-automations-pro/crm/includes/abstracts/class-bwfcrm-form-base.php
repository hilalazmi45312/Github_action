<?php

#[AllowDynamicProperties]
abstract class BWFCRM_Form_Base {
	/** Active Feeds based on form, for Create/Update contact */
	/** @var BWFCRM_Form_Feed[] $feeds */
	protected $feeds = array();
	protected $cid = 0;

	abstract public function capture_async_submission();

	public function find_feeds_and_create_contacts() {
		/** Form Source */
		$source = $this->get_source();

		/** Find Active Feeds */
		/** @var BWFCRM_Form_Feed[] $feeds */
		$this->feeds = BWFCRM_Core()->forms->get_active_feeds_by_form( $source );
		if ( ! is_array( $this->feeds ) || empty( $this->feeds ) ) {
			return;
		}

		/** Filter for feeds related to current submitted form entry */
		$this->feeds = $this->filter_feeds_for_current_entry();
		if ( ! is_array( $this->feeds ) || empty( $this->feeds ) ) {
			return;
		}

		/** Create Contacts based on those filtered & active feeds */
		$this->create_contacts_from_feeds();
	}

	abstract public function filter_feeds_for_current_entry();

	public function create_contacts_from_feeds() {
		foreach ( $this->feeds as $feed ) {
			$mapped_fields   = $feed->get_data( 'mapped_fields' );
			$update_existing = $feed->get_data( 'update_existing' );

			// Parameter usage is different as per name ( if enabled restrict blank data from update )
			$dont_update_blank = $feed->get_data( 'update_blank' );

			$contact_data = $this->prepare_contact_data_from_feed_entry( $mapped_fields );

			/** Filter blank value from data */
			if ( $dont_update_blank ) {
				$contact_data = array_filter( $contact_data, function ( $data ) {
					return '' !== $data;
				} );
			}

			if ( ! is_array( $contact_data ) || empty( $contact_data ) || ! isset( $contact_data['email'] ) ) {
				continue;
			}

			$contact_data['email'] = trim( $contact_data['email'] );
			$cid                   = isset( $this->cid ) && ! empty( $this->cid ) ? absint( $this->cid ) : 0;

			/** Try to Get that Contact */
			$contact = new BWFCRM_Contact( ! empty( $cid ) ? $cid : $contact_data['email'] );

			/** Increment the Submissions */
			$feed->increment_contacts();
			$feed->save();

			/** Conditions to check if contact is freshly created or not */
			$contact_created_at     = $contact->is_contact_exists() && ! empty( $contact->contact->get_creation_date() ) ? strtotime( $contact->contact->get_creation_date() ) : false;
			$contact_recently_added = ! empty( $contact_created_at ) && ( strtotime( current_time( 'mysql' ) ) - $contact_created_at <= 120 );

			/**
			 * If Contact not exists, or Recently Added, or on FLAG:update existing contact = true.
			 * Then save the contact, and it's fields
			 */
			if ( ! $contact->is_contact_exists() || $contact_recently_added || 1 === absint( $update_existing ) ) {
				$newly_created = ( ! $contact->is_contact_exists() || $contact_recently_added );
				$contact       = $this->set_contact_data( $contact, $feed, $contact_data, $newly_created );

				$trigger_events = $feed->get_data( 'trigger_events' );
				if ( ! $trigger_events && isset( $contact->contact->is_subscribed ) ) {
					$contact->contact->is_subscribed = false;
				}

				$contact->save();
				$contact->save_fields();
			}
			if ( $feed->get_data( 'not_send_to_subscribed' ) && $contact->contact->get_status() == 1 && empty( $contact->check_contact_unsubscribed() ) ) { //also check for not unsubscribe
				continue;
			}
			BWFCRM_Core()->forms->send_incentive_email( $contact, $feed );
		}
	}

	/**
	 * @param BWFCRM_Contact $contact
	 * @param BWFCRM_Form_Feed $feed
	 * @param $contact_data
	 * @param $new Bool
	 *
	 * @return BWFCRM_Contact $contact
	 */
	public function set_contact_data( $contact, $feed, $contact_data, $new ) {
		$cid = isset( $this->cid ) && ! empty( $this->cid ) ? absint( $this->cid ) : 0;
		$contact->set_data( $contact_data, $cid );
		if ( ! $contact->contact instanceof WooFunnels_Contact ) {
			return $contact;
		}

		/** Set Source to current Form, if source is empty */
		if ( empty( $contact->contact->get_source() ) ) {
			$contact->contact->set_source( $this->get_source() );
		}

		/** Set Form Feed ID field, if empty */
		if ( empty( $contact->get_field_by_slug( 'form-feed-id' ) ) ) {
			$contact->set_field_by_slug( 'form-feed-id', $feed->get_id() );
		}

		$marketing_status = $feed->get_data( 'marketing_status' );
		/** If no status available to set */
		if ( 0 === intval( $marketing_status ) ) {
			return $this->apply_terms_to_contact_from_feed( $contact, $feed );
		}

		/** Auto-confirm contacts is enable */
		/** If contact unsubscribed then resubscribe */
		if ( 1 === intval( $contact->contact->get_status() ) && $contact->check_contact_unsubscribed() ) {
			$contact->resubscribe();

			/** Set Tags and Lists */
			return $this->apply_terms_to_contact_from_feed( $contact, $feed );
		}

		/** Set status */
		$contact->contact->set_status( intval( $marketing_status ) );

		/** Set Tags and Lists */
		return $this->apply_terms_to_contact_from_feed( $contact, $feed );
	}

	/**
	 * @param BWFCRM_Contact $contact
	 * @param BWFCRM_Form_Feed $feed
	 */
	public function apply_terms_to_contact_from_feed( $contact, $feed ) {
		$tags           = $feed->get_data( 'tags' );
		$lists          = $feed->get_data( 'lists' );
		$trigger_events = $feed->get_data( 'trigger_events' );

		$tags  = is_array( $tags ) ? array_filter( array_values( $tags ) ) : array();
		$lists = is_array( $lists ) ? array_filter( array_values( $lists ) ) : array();

		! empty( $tags ) && $contact->set_tags( $tags, false, ! $trigger_events );
		! empty( $lists ) && $contact->set_lists( $lists, false, ! $trigger_events );

		return $contact;
	}

	/**
	 * @param BWFCRM_Form_Feed $feed
	 *
	 * @return bool
	 */
	public function is_selected_form_valid( $feed ) {
		if ( ! $feed instanceof BWFCRM_Form_Feed ) {
			return false;
		}

		$data = $feed->get_data();
		if ( ! is_array( $data ) || empty( $data ) ) {
			return false;
		}

		$steps = $this->get_form_selection( $data, true );

		return $this->verify_selected_data( $steps, $data );
	}

	/**
	 * Verify if the selected data is valid
	 *
	 * @param array $steps
	 * @param array $data
	 *
	 * @return bool
	 */
	public function verify_selected_data( $steps, $data ) {
		if ( ! is_array( $steps ) || empty( $steps ) ) {
			return false;
		}

		$form_meta = $this->get_meta();
		$form_meta = array_keys( $form_meta['form_selection_fields'] );
		foreach ( $form_meta as $index => $selection ) {
			if ( ! isset( $data[ $selection ] ) || empty( $data[ $selection ] ) ) {
				return false;
			}

			$step_no = absint( $index ) + 1;
			if ( ! isset( $steps[ $step_no ] ) || ! is_array( $steps[ $step_no ] ) ) {
				return false;
			}

			$step        = $steps[ $step_no ]['options'];
			$step_value  = $data[ $selection ];
			$value_found = false;
			foreach ( $step as $group ) {
				if ( is_array( $group ) && isset( $group[ $step_value ] ) ) {
					$value_found = true;
					break;
				}
			}

			if ( false === $value_found ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $mapped_fields
	 *
	 * @return mixed
	 */
	abstract public function prepare_contact_data_from_feed_entry( $mapped_fields );

	abstract public function get_form_selection( $args, $return_all_available );

	abstract public function update_form_selection( $args, $feed_id );

	abstract public function get_total_selection_steps();

	/**
	 * Returned Form Fields must be in this format ['field_slug'=>'field_name']
	 *
	 * @param $feed
	 *
	 * @return mixed
	 */
	abstract public function get_form_fields( $feed );

	public function format_form_fields_for_mapping( $fields ) {
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return array();
		}

		$map_fields = array();
		foreach ( $fields as $slug => $field ) {
			$map_fields[] = array(
				'index'  => $slug,
				'header' => $field,
			);
		}

		return $map_fields;
	}

	/**
	 * @return array
	 */
	abstract public function get_meta();

	/** Get Form Source Slug */
	abstract public function get_source();

	/** Provide link for the id */
	abstract public function get_form_link( $feed );

	public function get_step_selection_array( $step_name, $step_slug, $step_no, $step_options = array() ) {
		return array(
			$step_no => array(
				'name'    => $step_name,
				'slug'    => $step_slug,
				'options' => $step_options,
			),
		);
	}
}
