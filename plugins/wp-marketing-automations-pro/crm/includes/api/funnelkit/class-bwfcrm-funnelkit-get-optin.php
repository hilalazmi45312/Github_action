<?php

class BWFCRM_API_FunnelKit_Get_Optin extends BWFCRM_API_Base {
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
		$this->route  = '/funnelkit/optins';
	}

	public function process_api_call() {
		$ids    = ! empty( $this->get_sanitized_arg( 'ids', 'text_field' ) ) ? $this->get_sanitized_arg( 'ids', 'text_field' ) : '';
		$ids    = ! empty( $ids ) ? explode( ',', $ids ) : array();
		$offset = ! empty( $this->get_sanitized_arg( 'offset' ) ) ? $this->get_sanitized_arg( 'offset', 'text_field' ) : 0;
		$limit  = ! empty( $this->get_sanitized_arg( 'limit' ) ) ? $this->get_sanitized_arg( 'limit', 'text_field' ) : 10;
		$search = $this->get_sanitized_arg( 'search' );

		if ( ! function_exists( 'WFOPP_Core' ) ) {
			$this->success_response( [] );
		}

		$data = $this->get_optin_forms( $search, $limit, $search );

		if ( ! is_array( $data ) || 0 === count( $data ) ) {
			return array();
		}

		$result = array();

		foreach ( $data as $v ) {
			$id                 = get_post_meta( $v['ID'], '_bwf_in_funnel', true );
			$get_funnel         = new WFFN_Funnel( $id );
			$funnel_title       = $get_funnel instanceof WFFN_Funnel && 0 !== $get_funnel->get_id() ? $get_funnel->get_title() : '';
			$funnel_title       = ! empty( $funnel_title ) ? " ($funnel_title - #{$v['ID']})" : " (#{$v['ID']})";
			$result[ $v['ID'] ] = $v['post_title'] . $funnel_title;
		}

		if ( ! empty( $ids ) ) {
			// trimming and converting ids to int value
			$ids = array_map( 'trim', $ids );
			$ids = array_map( 'intval', $ids );

			$result = array_filter( $result, function ( $optin ) use ( $ids ) {
				return in_array( intval( $optin ), $ids, true );
			}, ARRAY_FILTER_USE_KEY );
		}

		return $this->success_response( $result );
	}

	/**
	 * Get optin forms
	 *
	 * @param $search
	 * @param $limit
	 * @param $offset
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function get_optin_forms( $search = '', $limit = 10, $offset = 0 ) {
		global $wpdb;
		$query = "SELECT `ID`, `post_title` FROM `{$wpdb->prefix}posts` WHERE `post_type` = %s AND `post_title` != '' AND `post_status` = %s";
		$args  = [ WFOPP_Core()->optin_pages->get_post_type_slug(), 'publish' ];
		if ( ! empty( $search ) ) {
			$query  .= " AND `post_title` LIKE %s ";
			$args[] = "%$search%";
		}
		if ( ! empty( $limit ) ) {
			$query  .= " LIMIT %d, %d";
			$args[] = $offset;
			$args[] = $limit;
		}

		return $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_FunnelKit_Get_Optin' );
