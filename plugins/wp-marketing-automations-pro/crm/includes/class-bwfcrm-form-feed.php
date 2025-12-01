<?php
#[AllowDynamicProperties]
class BWFCRM_Form_Feed {
	private $_id = 0;
	private $_source = '';
	private $_title = '';
	private $_contacts = 0;
	private $_status = 1;
	private $_data = array();
	private $_db_row = array();

	public static $STATUS_DRAFT = 1;
	public static $STATUS_ACTIVE = 2;
	public static $STATUS_INACTIVE = 3;

	public function __construct( $feed_id = 0 ) {
		if ( empty( absint( $feed_id ) ) ) {
			return;
		}

		$feed = BWFAN_Model_Form_Feeds::get( absint( $feed_id ) );
		if ( ! is_array( $feed ) || empty( $feed ) ) {
			return;
		}

		/** Set the DB row for further use */
		$this->set_db_row( $feed );

		$this->set_form_feed( $feed['ID'], $feed['title'], $feed['contacts'], $feed['source'], $feed['status'], $feed['data'] );
	}

	public function is_feed_exists() {
		return $this->get_id() > 0;
	}

	public function set_form_feed( $id, $title, $contacts, $source, $status, $data ) {
		! empty( absint( $id ) ) && $this->set_id( absint( $id ) );
		! empty( $title ) && $this->set_title( $title );
		! empty( $contacts ) && $this->set_contacts( absint( $contacts ) );
		! empty( $source ) && $this->set_source( $source );
		! empty( absint( $status ) ) && $this->set_status( $status );

		if ( ! is_array( $data ) && ! empty( $data ) ) {
			$data = json_decode( $data, true );
		}

		if ( is_array( $data ) && ! empty( $data ) ) {
			foreach ( $data as $key => $datum ) {
				$this->set_data( $key, $datum );
			}
		}
	}

	public function set_db_row( $db_row = [] ) {
		if ( empty( $db_row ) ) {
			return;
		}

		$this->_db_row = $db_row;
	}

	public function get_id() {
		return absint( $this->_id );
	}

	public function get_source() {
		return $this->_source;
	}

	public function get_title() {
		return $this->_title;
	}

	public function get_contacts() {
		return $this->_contacts;
	}

	public function get_db_row() {
		return $this->_db_row;
	}

	public function get_created_at() {
		$feed = $this->get_db_row();
		if ( empty( $feed ) || ! isset( $feed['created_at'] ) ) {
			return '';
		}

		return $feed['created_at'];
	}

    public function get_updated_at() {
        $feed = $this->get_db_row();
        if ( empty( $feed ) || ! isset( $feed['updated_at'] ) ) {
            return '';
        }

        return $feed['updated_at'];
    }

	public function get_status() {
		return absint( $this->_status );
	}

	public function get_status_text() {
		switch ( $this->_status ) {
			case self::$STATUS_DRAFT:
				return __( 'Draft', 'wp-marketing-automations-pro' );
			case self::$STATUS_ACTIVE:
				return __( 'Active', 'wp-marketing-automations-pro' );
			case self::$STATUS_INACTIVE:
				return __( 'Inactive', 'wp-marketing-automations-pro' );
			default:
				return '';
		}
	}

	public function get_data( $key = '' ) {
		if ( empty( $key ) ) {
			return $this->_data;
		}

		return isset( $this->_data[ $key ] ) ? $this->_data[ $key ] : '';
	}

	public function set_id( $id ) {
		$this->_id = absint( $id );
	}

	public function set_source( $source ) {
		$this->_source = $source;
	}

	public function set_title( $title ) {
		$this->_title = $title;
	}

	public function set_status( $status ) {
		$this->_status = absint( $status );
	}

	public function set_contacts( $count ) {
		$this->_contacts = absint( $count );
	}

	public function increment_contacts() {
		$this->_contacts ++;
	}

	public function decrement_contacts() {
		$this->_contacts --;
	}

	public function set_data( $key, $value ) {
		if ( empty( $key ) ) {
			return false;
		}

		$this->_data[ $key ] = $value;

		return true;
	}

	public function empty_data() {
		$this->_data = [];
	}

	public function unset_data( $key ) {
		if ( ! isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
		}
	}

	/**
	 * @param bool $reset_previous_data
	 *
	 * @return bool|WP_Error
	 */
	public function save_data( $reset_previous_data = false ) {
		if ( 0 === $this->get_id() ) {
			return BWFCRM_Common::crm_error( __( 'Feed not exists', 'wp-marketing-automations-pro' ) );
		}

		$feed_data = $this->get_data();
		if ( ! $reset_previous_data ) {
			$feed = BWFAN_Model_Form_Feeds::get( $this->get_id() );
			if ( ! is_array( $feed ) || empty( $feed ) ) {
				return BWFCRM_Common::crm_error( __( 'Invalid DB Feed', 'wp-marketing-automations-pro' ) );
			}

			$feed_data = isset( $feed['data'] ) && ! empty( $feed['data'] ) ? $feed['data'] : '';
			$feed_data = json_decode( $feed_data, true );
			$feed_data = ! is_array( $feed_data ) ? [] : $feed_data;

			$feed_data = array_replace( $feed_data, $this->get_data() );
		}

		$feed_data = wp_json_encode( $feed_data );
		if ( false === BWFAN_Model_Form_Feeds::update( [ 'data' => $feed_data, 'updated_at' => current_time( 'mysql', 1 ) ], [ 'ID' => $this->get_id() ] ) ) {
			return BWFCRM_Common::crm_error( __( 'Unable to update data', 'wp-marketing-automations-pro' ) );
		}

		return true;
	}

	/**
	 * @param bool $reset_previous_data
	 *
	 * @return int
	 */
	public function save( $reset_previous_data = false ) {
		if ( 0 === $this->get_id() ) {
			BWFAN_Model_Form_Feeds::insert( array(
				'title'      => $this->get_title(),
				'contacts'   => $this->get_contacts(),
				'source'     => $this->get_source(),
				'status'     => $this->get_status(),
				'created_at' => current_time( 'mysql', 1 ),
				'updated_at' => current_time( 'mysql', 1 ),
			) );

			$this->set_id( BWFAN_Model_Form_Feeds::insert_id() );
		} else {
			BWFAN_Model_Form_Feeds::update( array(
				'title'      => $this->get_title(),
				'contacts'   => $this->get_contacts(),
				'source'     => $this->get_source(),
				'status'     => $this->get_status(),
				'updated_at' => current_time( 'mysql', 1 ),
			), [ 'ID' => $this->get_id() ] );
		}

		$this->save_data( $reset_previous_data );

		return $this->get_id();
	}


	public function get_array( $slim_data = false ) {
		$form      = BWFCRM_Core()->forms->get_form( $this->get_source() );
		$form_link = '';
		/** Get Form link to the feed */
		if ( $form instanceof BWFCRM_Form_Base ) {
			$form_link = $form->get_form_link( $this );
		}

		$feed = array(
			'id'               => $this->get_id(),
			'title'            => empty( $this->get_title() ) ? "Feed #{$this->get_id()}" : $this->get_title(),
			'source'           => empty( $this->get_source() ) ? '' : $this->get_source(),
			'contacts_created' => $this->get_contacts(),
			'status'           => $this->get_status(),
			'status_text'      => $this->get_status_text(),
			'valid'            => ! $slim_data ? $this->is_feed_valid() : '',
			'form_link'        => $form_link,
            'created_on'       => get_date_from_gmt( $this->get_created_at() ),
            'last_updated'     => $this->get_updated_at(),
		);

		if ( true === $slim_data ) {
			return $feed;
		}

		$feed['data'] = $this->get_data();

		/** Fetch updated list */
		if ( isset( $feed['data']['lists'] ) ) {
			$feed['data']['lists'] = BWFAN_Common::get_updated_tags_and_list( $feed['data']['lists'], 'id','value' );
		}

		/** Fetch updated tags */
		if ( isset( $feed['data']['tags'] ) ) {
			$feed['data']['tags'] = BWFAN_Common::get_updated_tags_and_list( $feed['data']['tags'], 'id','value' );
		}

		$feed['source_meta'] = $this->get_source_meta();

		return $feed;
	}

	public function get_source_meta() {
		$form = BWFCRM_Core()->forms->get_form( $this->get_source() );
		if ( is_null( $form ) ) {
			return array();
		}

		return $form->get_meta();
	}

	public function is_feed_valid() {
		if ( ! $this->is_feed_exists() ) {
			return false;
		}

		if ( self::$STATUS_DRAFT === $this->get_status() ) {
			return true;
		}

		$form = BWFCRM_Core()->forms->get_form( $this->get_source() );
		if ( is_null( $form ) ) {
			return false;
		}

		return $form->is_selected_form_valid( $this );
		/** todo: Check if the selected form mapped fields valid */
	}

	public function delete() {
		BWFAN_Model_Form_Feeds::delete( $this->get_id() );

		/** Reset All */
		$this->reset_object();
	}

	public function reset_object() {
		$this->set_id( 0 );
		$this->set_source( '' );
		$this->set_status( 1 );
		$this->set_contacts( 0 );
		$this->set_title( '' );
		$this->_data = [];
	}
}