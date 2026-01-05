<?php
/**
 * Campaign Get API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Get API class
 */
class BWFCRM_API_Get_Broadcasts_Stats extends BWFCRM_API_Base {

	public static $ins;

	/**
	 * Return class instance
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/broadcasts-stats';
	}

	/**
	 * API callback
	 */
	public function process_api_call() {

		$broadcasts_ids      = isset( $this->args['broadcast_ids'] ) ? $this->args['broadcast_ids'] : [];
		$child_broadcast_ids = BWFAN_Model_Broadcast::get_broadcast_child( $broadcasts_ids, true );

		/** Fetch second level child */
		$child2_broadcast_ids = ! empty( $child_broadcast_ids ) ? BWFAN_Model_Broadcast::get_broadcast_child( $child_broadcast_ids, true ) : [];

		$broadcasts_ids = array_merge( $broadcasts_ids, $child_broadcast_ids, $child2_broadcast_ids );

		$final_data = [];
		$cached_key = 'bwfan_broadcast_stats';
		$force      = filter_input( INPUT_GET, 'force' );
		$exp        = BWFAN_Common::get_admin_analytics_cache_lifespan();

		try {
			if ( 'false' === $force ) {
				$stats = get_transient( $cached_key );
				if ( ! empty( $stats ) ) {
					$broadcasts_ids = array_filter( $broadcasts_ids, function ( $id ) use ( $stats ) {
						return ! array_key_exists( $id, $stats );
					} );
					if ( count( $broadcasts_ids ) > 0 ) {
						sort( $broadcasts_ids );
					}
					$final_data = $stats;
				}
			}

			if ( is_array( $broadcasts_ids ) && count( $broadcasts_ids ) > 0 ) {
				$ids         = $broadcasts_ids;
				$batch_size  = 10;
				$data        = [];
				$conversions = [];
				while ( count( $ids ) > 0 ) {
					/** Get ids in batches */
					$selected_ids = ( count( $ids ) > $batch_size ) ? array_slice( $ids, 0, $batch_size ) : $ids;

					/** update broadcast ids for next run */
					$ids = array_diff( $ids, $selected_ids );
					sort( $ids );

					$batch_data = BWFAN_Model_Broadcast::include_open_click_rate( $selected_ids, [], true );

					$data = array_merge( $data, $batch_data );
					/** Get formatted ids for conversions */
					$formatted_ids = [];
					foreach ( $selected_ids as $id ) {
						$formatted_ids[] = [ 'id' => $id ];
					}
					/** Get conversions */
					$batch_conversions = BWFAN_Model_Broadcast::include_conversion_into_campaigns( $formatted_ids, true );
					$conversions       = array_merge( $conversions, $batch_conversions );
				}

				$broadcast_ids  = empty( $data ) ? [] : array_column( $data, 'oid' );
				$conversion_ids = empty( $conversions ) ? [] : array_column( $conversions, 'oid' );

				foreach ( $broadcasts_ids as $id ) {
					$index      = array_search( $id, $broadcast_ids );
					$open_rate  = 0;
					$sent       = 0;
					$click_rate = 0;
					if ( false !== $index ) {
						$open_rate  = isset( $data[ $index ]['open_rate'] ) ? $data[ $index ]['open_rate'] : 0;
						$sent       = isset( $data[ $index ]['sent'] ) ? $data[ $index ]['sent'] : 0;
						$click_rate = isset( $data[ $index ]['click_rate'] ) ? $data[ $index ]['click_rate'] : 0;
					}

					$index            = array_search( $id, $conversion_ids );
					$conversion_count = 0;
					$revenue          = 0;
					if ( false !== $index ) {
						$conversion_count = isset( $conversions[ $index ]['conversions'] ) ? $conversions[ $index ]['conversions'] : 0;
						$revenue          = isset( $conversions[ $index ]['revenue'] ) ? $conversions[ $index ]['revenue'] : 0;
					}

					$final_data[ $id ] = [
						'open_rate'   => $open_rate,
						'sent'        => $sent,
						'click_rate'  => $click_rate,
						'conversions' => $conversion_count,
						'revenue'     => $revenue,
					];
				}

				set_transient( $cached_key, $final_data, $exp );
				BWFAN_Common::validate_scheduled_recurring_actions();
			}

			$this->response_code = 200;

			return $this->success_response( $final_data, '' );
		} catch ( Error $e ) {
			$this->response_code = 404;

			return $this->error_response( $e['message'] );
		}
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Broadcasts_Stats' );
