<?php

#[AllowDynamicProperties]
final class BWFAN_CRM_Contact_Bounced extends BWFAN_Event {
	private static $instance = null;
	public $contact_id = null;
	public $email = null;
	public $user_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact' );
		$this->optgroup_label         = esc_html__( 'Contact', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Contact Bounced', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after contact is bounced.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->priority               = 31;
		$this->customer_email_tag     = '{{contact_email}}';
		$this->v2                     = true;
		$this->support_v1             = false;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfcrm_after_contact_bounced', [ $this, 'process' ], 10 );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $contact
	 */
	public function process( $contact ) {
		if ( ! $contact instanceof WooFunnels_Contact ) {
			return;
		}

		$data               = $this->get_default_data();
		$data['contact_id'] = $contact->get_id();
		$data['email']      = $contact->get_email();

		$this->send_async_call( $data );
	}

	public function get_event_data() {
		$data_to_send                         = [ 'global' => [] ];
		$data_to_send['global']['contact_id'] = $this->contact_id;
		$data_to_send['global']['email']      = $this->email;

		return $data_to_send;
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
	}

	public function get_user_id_event() {
		if ( is_email( $this->email ) ) {
			$user = get_user_by( 'email', absint( $this->email ) );

			return ( $user instanceof WP_User ) ? $user->ID : false;
		}

		return ! empty( absint( $this->user_id ) ) ? absint( $this->user_id ) : false;
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->contact_id = BWFAN_Common::$events_async_data['contact_id'];
		$this->email      = BWFAN_Common::$events_async_data['email'];

		$automation_data['contact_id'] = $this->contact_id;
		$automation_data['email']      = $this->email;

		return $automation_data;
	}

	public function get_fields_schema() {
		return [];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( class_exists( 'BWFCRM_Contact' ) ) {
	return 'BWFAN_CRM_Contact_Bounced';
}
