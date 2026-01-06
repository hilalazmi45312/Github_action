<?php

/**
 * Class BWFAN_Rest_API_Get_Contacts
 */

namespace BWFAN\Rest_API;
class Get_Contacts extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method = \WP_REST_Server::READABLE;
		$this->route  = '/contacts';

		add_filter( 'bwfan_contact_sql_final_where_query', [ $this, 'bwfan_public_api_contact_where_sql' ], 10, 1 );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}
	public function process_api_call() {
		$email      = $this->get_sanitized_arg( 'email', 'email' );
		$limit      = isset( $this->args['limit'] ) ? $this->args['limit'] : 30;
		$offset     = isset( $this->args['offset'] ) ? $this->args['offset'] : 0;
		$sort_order = isset( $this->args['sort_order'] ) ? $this->args['sort_order'] : 'DESC';
		$sort_field = isset( $this->args['sort_field'] ) ? $this->args['sort_field'] : 'creation_date';
		$status     = isset( $this->args['status'] ) ? $this->args['status'] : '';

		// setting filters statically as only 'status' is being used to filter the contact
		$filters = array();
		if ( ! empty( $status ) ) {
			//formatting the filters
			$filters['c'][] = array(
				'key'   => 'status',
				'rule'  => 'is',
				'type'  => \BWFCRM_Filters::$TYPE_NUMBER_EXACT,
				'value' => $status
			);
		}

		// if not provided, then taking the defaults
		$additional_info = array( 'order_by' => $sort_field, 'order' => $sort_order, 'grab_totals' => true );

		$contact_data = \BWFCRM_Model_Contact::get_contacts( $email, $limit, $offset, $filters, $additional_info );


		if ( empty( $contact_data ) ) {
			$this->error_response( '', 422, 'data_not_found' );
		}
		/** Modify the status if contact email/phone is in unsubscribe table */
		$unsubscribed = self::get_unsubscribed_contacts( $contact_data );

		if ( ! empty( $unsubscribed ) ) {
			$processed_contacts = array_map( function ( $contact ) use ( $unsubscribed ) {
				$email      = $contact['email'] ?? '';
				$contact_no = $contact['contact_no'] ?? '';

				if ( in_array( $email, $unsubscribed, true ) || in_array( $contact_no, $unsubscribed, true ) ) {
					$contact['status'] = '3';
				}

				return $contact;
			}, $contact_data['contacts'] );

			$contact_data['contacts'] = $processed_contacts;
		}
		return $this->success_response( [ 'contact' => $contact_data ], __( 'Contact(s) listed successfully', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * Get all un-subscriber
	 *
	 * @param $contact_data
	 *
	 * @return array
	 */

	public static function get_unsubscribed_contacts( $contact_data ) {
		if ( ! isset( $contact_data['contacts'] ) || empty( $contact_data['contacts'] ) ) {
			return [];
		}
		$emails      = array_column( $contact_data['contacts'], 'email' );
		$contact_nos = array_column( $contact_data['contacts'], 'contact_no' );
		$recipients  = array_filter( array_unique( array_merge( $emails, $contact_nos ) ) );
		if ( empty( $recipients ) ) {
			return [];
		}
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $recipients ), '%s' ) );
		$query        = " SELECT recipient  FROM {$wpdb->prefix}bwfan_message_unsubscribe  WHERE recipient IN ($placeholders)";

		return $wpdb->get_col( $wpdb->prepare( $query, $recipients ) );//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
	}

	/**
	 * @param $filter_where
	 * appending apis filters to contacts where query
	 *
	 * @return mixed|string
	 */
	public function bwfan_public_api_contact_where_sql( $filter_where ) {
		$from         = isset( $this->args['from'] ) ? date( 'Y-m-d', strtotime( $this->args['from'] ) ) : '';
		$to           = isset( $this->args['to'] ) ? date( 'Y-m-d', strtotime( $this->args['to'] ) ) : '';
		$updated_from = isset( $this->args['updated_from'] ) ? date( 'Y-m-d', strtotime( $this->args['updated_from'] ) ) : '';
		$updated_to   = isset( $this->args['updated_to'] ) ? date( 'Y-m-d', strtotime( $this->args['updated_to'] ) ) : '';
		$tags         = isset( $this->args['tag'] ) ? $this->args['tag'] : array();
		$lists        = isset( $this->args['list'] ) ? $this->args['list'] : array();

		if ( ! empty( $from ) ) {
			$filter_where .= "AND (c.creation_date >='$from')";
		}

		if ( ! empty( $to ) ) {
			$filter_where .= "AND (c.creation_date <='$to')";
		}

		if ( ! empty( $updated_from ) ) {
			$filter_where .= "AND (c.last_modified >'$updated_from')";
		}

		if ( ! empty( $updated_to ) ) {
			$filter_where .= "AND (c.last_modified <'$updated_to')";
		}

		// formatting tags with 'like' and 'or' operator
		if ( ! empty( $tags ) ) {
			$tags = array_map( function ( $tag ) {
				return "c.tags LIKE '%\"$tag\"%'";
			}, $tags );

			$tags         = implode( ' OR ', $tags );
			$filter_where .= "AND ($tags)";
		}

		// formatting list with 'like' and 'or' operator
		if ( ! empty( $lists ) ) {
			$lists = array_map( function ( $list ) {
				return "c.lists LIKE '%\"$list\"%'";
			}, $lists );

			$lists        = implode( ' OR ', $lists );
			$filter_where .= "AND ($lists)";
		}

		return $filter_where;
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Get_Contacts' );
