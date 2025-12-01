<?php

class BWFCRM_API_Sync_Feed_Selection extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total = 0;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::CREATABLE;
		$this->route  = '/form-feeds/(?P<feed_id>[\\d]+)/sync-selection';
	}

	public function process_api_call() {
		$feed_id            = $this->get_sanitized_arg( 'feed_id' );
		$form               = $this->get_sanitized_arg( 'form' );
		$return_all_options = $this->get_sanitized_arg( 'return_all_options', 'bool' );
		$selection          = $this->args['selection'];
		if ( ! is_array( $selection ) ) {
			$selection = [];
		}

		$selection = BWFCRM_Core()->forms->sync_feed_selection( $feed_id, $form, $selection, $return_all_options );
		if ( is_wp_error( $selection ) ) {
			return $this->error_response( '', $selection );
		}

		return $this->success_response( $selection );
	}

	public function get_result_total_count() {
		return $this->total;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Sync_Feed_Selection' );
