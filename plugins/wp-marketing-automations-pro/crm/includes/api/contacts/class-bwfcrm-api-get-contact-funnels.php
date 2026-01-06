<?php

class BWFCRM_API_Get_Contact_Funnels extends BWFCRM_API_Base {
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
		$this->route  = '/contacts/(?P<contact_id>[\\d]+)/funnels';
	}

	public function default_args_values() {
		return array(
			'contact_id' => '',
		);
	}

	public function process_api_call() {
		/** checking if search present in params **/

		$contact_id = $this->get_sanitized_arg( 'contact_id', 'key' );
		$offset     = $this->get_sanitized_arg( 'offset', 'key' );
		$limit      = $this->get_sanitized_arg( 'limit', 'key' );

		$offset = empty( $offset ) ? $this->pagination->offset : $offset;
		$limit  = empty( $offset ) ? $this->pagination->limit : $limit;

		if ( empty( $contact_id ) ) {
			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No contact found with given id #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

		$contacts_funnel           = array();
		$contacts_funnel['funnel'] = $contact->get_contact_funnels_array();

		if ( function_exists( 'WFACP_Core' ) ) {
			$contacts_funnel['funnel']['checkout'] = [];
		}

		if ( function_exists( 'WFOB_Core' ) ) {
			$contacts_funnel['funnel']['order_bump'] = [];
		}

		if ( function_exists( 'WFOPP_Core' ) ) {
			$optin_etries = $contact->get_contact_optin_array();

			if ( is_array( $optin_etries ) && ! empty( $optin_etries ) ) {
				/** Add email in optin entry */
				$optin_etries = array_map( function ( $optin ) {
					$entry = json_decode( $optin['entry'], true );
					if ( ! empty( $optin['email'] ) && ! empty( $entry ) ) {
						$entry['optin_email'] = $optin['email'];
					}
					$optin['entry'] = wp_json_encode( $entry );

					return $optin;
				}, $optin_etries );
			}

			$contacts_funnel['funnel']['optin'] = $optin_etries;
		}

		if ( function_exists( 'WFOCU_Core' ) ) {
			$contacts_funnel['funnel']['upsells'] = [];
		}

		$this->response_code = 200;
		$success_message     = __( 'Contacts funnels', 'wp-marketing-automations-pro' );

		return $this->success_response( $contacts_funnel, $success_message );
	}
}

if ( class_exists( 'WFFN_Core' ) ) {
	BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Contact_Funnels' );
}
