<?php

class BWFCRM_API_FunnelKit_Get_Checkout extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/funnelkit/checkouts';
	}

	public function process_api_call() {
		$ids    = ! empty( $this->get_sanitized_arg( 'ids', 'text_field' ) ) ? $this->get_sanitized_arg( 'ids', 'text_field' ) : '';
		$ids    = ! empty( $ids ) ? explode( ',', $ids ) : array();
		$offset = ! empty( $this->get_sanitized_arg( 'offset' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;
		$limit  = ! empty( $this->get_sanitized_arg( 'limit' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 10;
		$search = $this->get_sanitized_arg( 'search' );

		if ( ! class_exists( 'WFACP_Common' ) ) {
			$this->success_response( [] );
		}

		$data = $this->get_fk_checkout_pages( $search, $limit, $offset );

		if ( ! is_array( $data ) || 0 === count( $data ) ) {
			return array();
		}

		$result = array();

		foreach ( $data as $v ) {
			$id           = get_post_meta( $v['ID'], '_bwf_in_funnel', true );
			$funnel_title = '';
			if ( ! empty( $id ) ) {
				$get_funnel   = new WFFN_Funnel( $id );
				$funnel_title = $get_funnel instanceof WFFN_Funnel && 0 !== $get_funnel->get_id() ? $get_funnel->get_title() : '';
			}

			$funnel_title       = ! empty( $funnel_title ) ? " ($funnel_title - #{$v['ID']})" : " (#{$v['ID']})";
			$result[ $v['ID'] ] = $v['post_title'] . $funnel_title;
		}

		if ( ! empty( $ids ) ) {
			// trimming and converting ids to int value
			$ids = array_map( 'trim', $ids );
			$ids = array_map( 'intval', $ids );

			$result = array_filter( $result, function ( $checkout ) use ( $ids ) {
				return in_array( intval( $checkout ), $ids, true );
			}, ARRAY_FILTER_USE_KEY );
		}

		return $this->success_response( $result );
	}

	/**
	 * Get funnelKit checkout pages
	 *
	 * @param $search
	 * @param $limit
	 * @param $offset
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function get_fk_checkout_pages( $search = '', $limit = 10, $offset = 0 ) {

		global $wpdb;
		$query = "SELECT `ID`, `post_title`, `post_type` FROM `{$wpdb->prefix}posts` WHERE `post_type` = %s AND `post_title` != '' AND `post_status` = 'publish' ";
		$args  = [ WFACP_Common::get_post_type_slug() ];
		if ( ! empty( $search ) ) {
			$query  .= " AND `post_title` LIKE %s ";
			$args[] = "%$search%";
		}
		$query .= "ORDER BY `post_title` ASC";
		$query = $wpdb->prepare( $query, $args );
		if ( ! empty( $limit ) ) {
			global $wpdb;
			$query .= " LIMIT %d, %d";
			$query = $wpdb->prepare( $query, $offset, $limit );
		}

		return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_FunnelKit_Get_Checkout' );
