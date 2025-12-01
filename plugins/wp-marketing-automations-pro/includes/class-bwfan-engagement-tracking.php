<?php

#[AllowDynamicProperties]
class BWFAN_Engagement_Tracking {
	private $_db_row = [];

	private $_merge_tags = [];
	private $_has_changes = false;

	/**
	 * @var BWFCRM_Contact $_contact
	 */
	private $_contact = null;

	public function __construct( $id_or_data = '' ) {
		if ( empty( $id_or_data ) ) {
			return;
		}

		if ( is_numeric( $id_or_data ) ) {
			$row = BWFAN_Model_Engagement_Tracking::get( absint( $id_or_data ) );
			$this->fill_object_from_array( $row );
			$this->fill_merge_tags_from_db();
		}

		if ( is_array( $id_or_data ) ) {
			$this->fill_object_from_array( $id_or_data );
		}
	}

	public function fill_merge_tags_from_db() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$tags = BWFAN_Model_Engagement_Trackingmeta::get_merge_tags( $this->get_id() );
		if ( empty( $tags ) || ! is_array( $tags ) ) {
			return false;
		}

		$this->_merge_tags = $tags;

		return true;
	}

	public function add_merge_tags_from_string( $string, $merge_tags_data = [] ) {
		if ( empty( $string ) ) {
			return false;
		}

		$merge_tags = BWFCRM_Core()->merge_tags->fetch_tags_from_string( $string, $merge_tags_data );

		$this->_merge_tags  = is_array( $this->_merge_tags ) ? array_replace( $this->_merge_tags, $merge_tags ) : $merge_tags;
		$this->_has_changes = true;

		return true;
	}

	public function is_valid() {
		return ! empty( $this->get_id() ) && is_numeric( $this->get_id() ) && $this->_contact instanceof BWFCRM_Contact && $this->_contact->is_contact_exists();
	}

	public function is_contact_valid() {
		return $this->_contact instanceof BWFCRM_Contact && $this->_contact->is_contact_exists();
	}

	public function fill_object_from_array( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['ID'] ) || ! isset( $data['oid'] ) ) {
			return;
		}

		$this->_db_row = $data;
		if ( ! isset( $this->_db_row['cid'] ) || ! is_numeric( $this->_db_row['cid'] ) ) {
			return;
		}

		$cid            = absint( $this->_db_row['cid'] );
		$this->_contact = new BWFCRM_Contact( $cid );
	}

	public function set_contact( $contact ) {
		if ( empty( $contact ) ) {
			return;
		}

		$contact = $contact instanceof BWFCRM_Contact ? $contact : new BWFCRM_Contact( $contact );
		if ( $this->is_contact_valid() && $this->_contact->get_id() !== $contact->get_id() ) {
			$this->_has_changes = true;
		}

		$this->_contact = $contact;
	}

	public function set_mode( $mode ) {
		if ( empty( $mode ) || ! is_numeric( $mode ) ) {
			return;
		}

		if ( isset( $this->_db_row['mode'] ) && absint( $this->_db_row['mode'] ) !== absint( $mode ) ) {
			$this->_has_changes = true;
		}

		$this->_db_row['mode'] = absint( $mode );
	}

	public function set_type( $type ) {
		if ( empty( $type ) || ! is_numeric( $type ) ) {
			return;
		}

		if ( isset( $this->_db_row['type'] ) && absint( $this->_db_row['type'] ) !== absint( $type ) ) {
			$this->_has_changes = true;
		}

		$this->_db_row['type'] = absint( $type );
	}

	public function set_send_to( $send_to ) {
		if ( empty( $send_to ) ) {
			return;
		}

		if ( isset( $this->_db_row['send_to'] ) && $this->_db_row['send_to'] !== $send_to ) {
			$this->_has_changes = true;
		}

		$this->_db_row['send_to'] = $send_to;
	}

	public function set_template_id( $tid ) {
		if ( empty( $tid ) ) {
			return;
		}

		if ( isset( $this->_db_row['tid'] ) && absint( $this->_db_row['tid'] ) !== absint( $tid ) ) {
			$this->_has_changes = true;
		}

		$this->_db_row['tid'] = absint( $tid );
	}

	public function set_status( $status ) {
		if ( empty( $status ) ) {
			return;
		}

		if ( isset( $this->_db_row['c_status'] ) && absint( $this->_db_row['c_status'] ) !== absint( $status ) ) {
			$this->_has_changes = true;
		}

		$this->_db_row['c_status'] = absint( $status );
	}

	public function set_oid( $oid ) {
		if ( empty( $oid ) ) {
			return;
		}

		if ( isset( $this->_db_row['oid'] ) && absint( $this->_db_row['oid'] ) !== absint( $oid ) ) {
			$this->_has_changes = true;
		}

		$this->_db_row['oid'] = absint( $oid );
	}

	public function set_merge_tags( $merge_tags ) {
		if ( empty( $merge_tags ) || ! is_array( $merge_tags ) ) {
			return;
		}

		$this->_merge_tags  = $merge_tags;
		$this->_has_changes = true;
	}

	/**
	 * Compute Hash Code
	 */
	public function enable_tracking() {
		$send_to = $this->get_send_to();
		if ( ! $this->is_contact_valid() || empty( $send_to ) ) {
			return false;
		}

		$hash                  = md5( time() . $send_to );
		$this->_db_row['hash'] = $hash;
		$this->_has_changes    = true;

		return $hash;
	}

	public function disable_tracking() {
		if ( ! empty( $this->_db_row['hash'] ) ) {
			$this->_has_changes = true;
		}

		$this->_db_row['hash'] = '';
	}

	public function get_mode() {
		if ( ! isset( $this->_db_row['mode'] ) || empty( $this->_db_row['mode'] ) ) {
			return BWFAN_Email_Conversations::$MODE_EMAIL;
		}

		return absint( $this->_db_row['mode'] );
	}

	public function get_send_to() {
		if ( ! isset( $this->_db_row['send_to'] ) || empty( $this->_db_row['send_to'] ) ) {
			return false;
		}

		return $this->_db_row['send_to'];
	}

	public function get_hash() {
		if ( ! isset( $this->_db_row['hash'] ) || empty( $this->_db_row['hash'] ) ) {
			return '';
		}

		return $this->_db_row['hash'];
	}

	public function get_created_at() {
		if ( ! isset( $this->_db_row['created_at'] ) || empty( $this->_db_row['created_at'] ) ) {
			return '';
		}

		return $this->_db_row['created_at'];
	}

	public function get_updated_at() {
		if ( ! isset( $this->_db_row['updated_at'] ) || empty( $this->_db_row['updated_at'] ) ) {
			return '';
		}

		return $this->_db_row['updated_at'];
	}

	public function get_type() {
		if ( ! isset( $this->_db_row['type'] ) || empty( $this->_db_row['type'] ) ) {
			return 0;
		}

		return absint( $this->_db_row['type'] );
	}

	public function get_opens() {
		if ( ! isset( $this->_db_row['open'] ) || empty( $this->_db_row['open'] ) ) {
			return 0;
		}

		return absint( $this->_db_row['open'] );
	}

	public function get_clicks() {
		if ( ! isset( $this->_db_row['click'] ) || empty( $this->_db_row['click'] ) ) {
			return 0;
		}

		return absint( $this->_db_row['click'] );
	}

	public function get_oid() {
		if ( ! isset( $this->_db_row['oid'] ) || empty( $this->_db_row['oid'] ) ) {
			return 0;
		}

		return absint( $this->_db_row['oid'] );
	}

	public function get_author_id() {
		if ( ! isset( $this->_db_row['author_id'] ) || empty( $this->_db_row['author_id'] ) ) {
			return get_current_user_id();
		}

		return absint( $this->_db_row['author_id'] );
	}

	public function get_template_id() {
		if ( ! isset( $this->_db_row['tid'] ) || empty( $this->_db_row['tid'] ) ) {
			return 0;
		}

		return absint( $this->_db_row['tid'] );
	}

	public function get_status() {
		if ( ! isset( $this->_db_row['c_status'] ) || empty( $this->_db_row['c_status'] ) ) {
			return BWFAN_Email_Conversations::$STATUS_DRAFT;
		}

		return absint( $this->_db_row['c_status'] );
	}

	public function get_id() {
		return ! empty( $this->_db_row['ID'] ) ? absint( $this->_db_row['ID'] ) : false;
	}

	public function get_contact() {
		return $this->_contact;
	}

	public function get_merge_tags() {
		return $this->_merge_tags;
	}

	public function save() {
		if ( ! $this->is_contact_valid() ) {
			return false;
		}

		$current_time = current_time( 'mysql', 1 );

		$conversation = [
			'cid'        => $this->_contact->get_id(),
			'hash_code'  => $this->get_hash(),
			'created_at' => $this->is_valid() && ! empty( $this->get_created_at() ) ? $this->get_created_at() : $current_time,
			'updated_at' => true === $this->_has_changes ? $current_time : $this->get_updated_at(),
			'mode'       => $this->get_mode(),
			'send_to'    => $this->get_send_to(),
			'type'       => $this->get_type(),
			'open'       => $this->get_opens(),
			'click'      => $this->get_clicks(),
			'oid'        => $this->get_oid(),
			'author_id'  => $this->get_author_id(),
			'tid'        => $this->get_template_id(),
			'c_status'   => $this->get_status(),
		];

		if ( ! $this->is_valid() ) {
			BWFCRM_Common::insert_engagement( $conversation, [
				'%d', // cid
				'%s', // hash_code
				'%s', // created_at
				'%s', // updated_at
				'%d', // mode
				'%s', // send_to
				'%d', // type
				'%d', // open
				'%d', // click
				'%d', // oid
				'%d', // author_id
				'%d', // tid
				'%d', // c_status
			] );
			$id = BWFAN_Model_Engagement_Tracking::insert_id();
			if ( ! empty( $id ) ) {
				$this->_db_row['ID'] = absint( $id );
			}
		} else {
			BWFAN_Model_Engagement_Tracking::update( $conversation, [ 'ID' => $this->get_id() ] );
		}

		$this->save_merge_tags();

		$this->_has_changes = false;

		return true;
	}

	public function save_merge_tags() {
		if ( empty( $this->_merge_tags ) || ! is_array( $this->_merge_tags ) ) {
			return false;
		}

		BWFAN_Model_Engagement_Trackingmeta::insert( array(
			'eid'        => $this->get_id(),
			'meta_key'   => 'merge_tags',
			'meta_value' => wp_json_encode( $this->_merge_tags )
		) );

		return true;
	}

	public function fail_the_conversation() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$error = BWFCRM_Common::$captured_email_failed_message;
		$error = ! empty( $error ) ? $error : __( 'Email not sent (Error Unknown)', 'wp-marketing-automations-pro' );

		return BWFCRM_Core()->conversation->fail_the_conversation( $this->get_id(), $error );
	}
}