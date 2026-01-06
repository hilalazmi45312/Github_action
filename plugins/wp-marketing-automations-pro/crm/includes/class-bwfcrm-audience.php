<?php

#[AllowDynamicProperties]
class BWFCRM_Audience {
	public $_id = 0;
	public $_name = null;
	public $_data = null;
	public $_created_at = null;
	public $_updated_at = null;

	public function __construct( $a_id = 0 ) {
		if ( empty( absint( $a_id ) ) ) {
			return;
		}

		$audience = BWFAN_Model_Terms::get( absint( $a_id ) );
		if ( ! is_array( $audience ) || empty( $audience ) ) {
			return;
		}

		$this->set_audience( $audience['ID'], $audience['name'], $audience['data'], $audience['created_at'], $audience['updated_at'] );
	}

	public function is_audience_exists() {
		return $this->get_id() > 0;
	}

	public function get_id() {
		return absint( $this->_id );
	}

	public function get_name() {
		return $this->_name;
	}

	public function get_data() {
		return $this->_data;
	}

	public function get_created_at() {
		return $this->_created_at;
	}

	public function get_updated_at() {
		return $this->_created_at;
	}

	public function get_array() {
		return array(
			'ID'         => $this->get_id(),
			'name'       => $this->get_name(),
			'type'       => 3,
			'data'       => $this->get_data(),
			'created_at' => $this->get_created_at(),
			'updated_at' => $this->get_updated_at(),
		);
	}

	public function set_audience( $id, $name, $data, $created_at, $updated_at ) {
		! empty( absint( $id ) ) && $this->set_id( absint( $id ) );
		! empty( $name ) && $this->set_name( $name );
		! empty( $data ) && $this->set_data( $data );
		! empty( $created_at ) && $this->set_created_at( $created_at );
		! empty( $updated_at ) && $this->set_updated_at( $updated_at );
	}

	public function set_id( $id ) {
		$this->_id = absint( $id );
	}

	public function set_name( $name ) {
		$this->_name = $name;
	}

	public function set_data( $data ) {
		$this->_data = $data;
	}

	public function set_created_at( $created_at ) {
		$this->_created_at = $created_at;
	}

	public function set_updated_at( $created_at ) {
		$this->_created_at = $created_at;
	}

	/**
	 * @return int
	 */
	public function save() {
		$data = [
			'name'       => $this->get_name(),
			'data'       => wp_json_encode( $this->get_data() ),
			'updated_at' => $this->get_updated_at(),
		];

		if ( 0 === $this->get_id() ) {
			$data['type']       = 3;
			$data['created_at'] = $this->get_created_at();
			BWFAN_Model_Terms::insert( $data );
			$this->set_id( BWFAN_Model_Terms::insert_id() );
		} else {
			BWFAN_Model_Terms::update( $data, [ 'ID' => $this->get_id() ] );
		}

		return $this->get_id();
	}

	/**
	 * Check audience is exists by name and return count of same name audiences
	 *
	 * @param $name
	 *
	 * @return int
	 */
	public function check_audience_is_exists( $name ) {
		return BWFAN_Model_Terms::get_terms_count( '3', $name, [], 'name_with_dash' );
	}

	/**
	 * Get first audience id
	 *
	 * @param $name
	 *
	 * @return int|null
	 */
	public static function get_first_audience_id() {
		return method_exists( 'BWFAN_Model_Terms', 'get_first_term_by_type' ) ? BWFAN_Model_Terms::get_first_term_by_type( '3' ) : null;
	}

}
