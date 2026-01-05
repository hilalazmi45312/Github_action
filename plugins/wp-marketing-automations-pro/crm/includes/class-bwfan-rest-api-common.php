<?php

/**
 * Class BWFAN_Rest_API_Common
 */

if ( ! class_exists( 'BWFAN_Rest_API_Common' ) ) {
	class BWFAN_Rest_API_Common {
		/**
		 * @param $id
		 *
		 * @return BWFCRM_Contact|false
		 */
		public static function is_contact_exists( $id ) {
			if ( empty( $id ) ) {
				return false;
			}
			$contact = new BWFCRM_Contact( $id );
			if ( ! $contact->is_contact_exists() ) {
				return false;
			}

			return $contact;
		}

		/**
		 * @param $term_ids
		 * associating term_id with their respective term name
		 *
		 * @return array
		 */
		public static function get_term_name( $term_ids ) {
			$term_data = array();
			foreach ( $term_ids as $id ) {
				$terms = BWFAN_Model_Terms::get( absint( $id ) );
				if ( ! is_array( $terms ) || empty( $terms ) ) {
					continue;
				}
				// array of id and value
				$term_data[] = array( 'id' => $id, 'value' => $terms['name'] );
			}

			return $term_data;
		}

		/**
		 * @param $fields
		 * removing the fields which does not exists
		 *
		 * @return mixed
		 */
		public static function remove_invalid_fields( $fields ) {
			$result    = array();
			$field_ids = implode( ',', array_keys( $fields ) );

			//fetching the fields by provided fields ids
			$field_types_db = BWFAN_Model_Fields::get_results( 'SELECT ID, type, meta FROM {table_name} WHERE ID IN (' . $field_ids . ') AND vmode = 1' );

			// creating array of field id
			$field_types_db = array_map( function ( $types ) {
				return $types['ID'];
			}, $field_types_db );

			// unsetting the fields which does not exists
			foreach ( $fields as $key => $values ) {
				if ( ! in_array( absint( $key ), $field_types_db ) ) {
					unset( $fields[ absint( $key ) ] );
					$result['field_not_exists'][] = $key;
				}
			}

			$result['fields'] = $fields;

			return $result;
		}

		/**
		 * Create contact
		 *
		 * @param $email
		 * @param $params
		 * @param $force_create
		 *
		 * @return BWFCRM_Contact
		 */
		public static function create_contact( $email, $params = [], $force_create = false ) {

			return new BWFCRM_Contact( $email, $force_create, $params );
		}

		/**
		 * @param $contact BWFCRM_Contact
		 * @param $status
		 *
		 * @return bool|mixed
		 */
		public static function change_contact_status( $contact, $status ) {
			$contact_status = strtolower( $contact->get_marketing_status() );
			$result         = true;
			switch ( true ) {
				case 'subscribed' === $status && 'subscribed' !== $contact_status :
					$result = $contact->resubscribe();
					break;
				case 'unsubscribed' === $status && 'unsubscribed' !== $contact_status :
					$result = $contact->unsubscribe();
					break;
				case 'verified' === $status && 'subscribed' !== $contact_status :
					$result = $contact->verify();
					break;
				case 'bounced' === $status && 'bounced' !== $contact_status :
					$result = $contact->mark_as_bounced();
					break;
				case 'unverified' === $status && 'unverified' !== $contact_status :
					$result = $contact->mark_as_unverified();
					break;
				case 'softbounced' === $status :
					if ( method_exists( $contact, 'mark_as_soft_bounced' ) ) {
						$result = $contact->mark_as_soft_bounced();
					} else {
						$result = $contact->mark_as_bounced();
					}
					break;
				case 'complaint' === $status && 'complaint' !== $contact_status :
					if ( method_exists( $contact, 'mark_as_complaint' ) ) {
						$result = $contact->mark_as_complaint();
					} else {
						$result = $contact->mark_as_bounced();
					}
					break;
			}

			return $result;
		}

		/**
		 * Set dbstatus of contact to 3 if the email/phone is in unsubscribe table
		 *
		 * @param $contact BWFCRM_Contact
		 *
		 * @return BWFCRM_Contact
		 */
		public static function get_formatted_contact_data( $contact ) {

			if ( ! $contact instanceof BWFCRM_Contact ) {
				return $contact;
			}
			$unsubscribed = $contact->check_contact_unsubscribed();
			if ( empty( $unsubscribed['ID'] ) || ! $contact->contact instanceof WooFunnels_Contact ) {
				return $contact;
			}
			$contact->contact->db_contact->status = '3';

			return $contact;
		}

		public static function set_contact_status( $contact ) {
			if ( ! $contact instanceof BWFCRM_Contact ) {
				return $contact;
			}
			$unsubscribed = $contact->check_contact_unsubscribed();
			if ( empty( $unsubscribed['ID'] ) || ! $contact->contact instanceof WooFunnels_Contact ) {
				return $contact;
			}
			$contact->contact->status = 3;

			return $contact;

		}
	}
}