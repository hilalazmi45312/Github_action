<?php

#[AllowDynamicProperties]
final class BWFAN_AFFWP_Referral_Signup extends BWFAN_Event {
	private static $instance = null;
	public $affiliate_id = null;
	public $referral_id = null;
	public $first_name = '';
	public $last_name = '';
	public $email = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'aff_affiliate', 'bwf_contact' );
		$this->optgroup_label         = __( 'AffiliateWP', 'wp-marketing-automations-pro' );
		$this->event_name             = __( 'Referral Sign Up', 'wp-marketing-automations-pro' );
		$this->event_desc             = __( 'This event runs after an Referral signed up.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'affiliatewp',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->priority               = 63;
		$this->is_time_independent    = false;
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
		add_action( 'affwp_insert_referral', array( $this, 'referral_created' ), 999, 1 );
	}

	/**
	 * @param $affiliate_id
	 * @param $status
	 * @param $args
	 */
	public function referral_created( $referral_id ) {
		$referral = affwp_get_referral( $referral_id );
		if ( ! $referral ) {
			return;
		}
		if ( empty( $referral->affiliate_id ) ) {
			return;
		}
		$this->referral_id  = $referral_id;
		$this->affiliate_id = $referral->affiliate_id;
		$this->process( $referral->affiliate_id, $referral_id );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 */
	public function process( $affiliate_id, $referral_id ) {
		$data                 = $this->get_default_data();
		$data['affiliate_id'] = $affiliate_id;
		$data['referral_id']  = $referral_id;
		$this->send_async_call( $data );
	}

	public function capture_v2_data( $automation_data ) {
		$this->affiliate_id = BWFAN_Common::$events_async_data['affiliate_id'];
		$this->referral_id  = BWFAN_Common::$events_async_data['referral_id'];

		$referral = affwp_get_referral( $this->referral_id );
		if ( ! $referral || empty( $referral->customer_id ) ) {
			return '';
		}

		$customer_data    = BWFAN_PRO_Common::get_referral_data( $referral->customer_id );
		$email            = isset( $customer_data['email'] ) ? $customer_data['email'] : '';
		$this->email      = ! empty( $email ) ? $email : affwp_get_affiliate_email( $this->affiliate_id );
		$this->first_name = isset( $customer_data['first_name'] ) ? $customer_data['first_name'] : '';
		$this->last_name  = isset( $customer_data['last_name'] ) ? $customer_data['last_name'] : '';

		$automation_data['affiliate_id']   = $this->affiliate_id;
		$automation_data['referral_count'] = $this->referral_id;
		$automation_data['email']          = $this->email;
		$automation_data['last_name']      = $this->last_name;
		$automation_data['first_name']     = $this->first_name;

		return $automation_data;
	}

	public function get_event_data() {
		$data_to_send                           = [ 'global' => [] ];
		$data_to_send['global']['affiliate_id'] = $this->affiliate_id;
		$data_to_send['global']['referral_id']  = $this->referral_id;
		$data_to_send['global']['email']        = $this->email;

		return $data_to_send;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'affiliate_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['affiliate_id'] ) ) ) {
			$set_data = array(
				'affiliate_id' => intval( $task_meta['global']['affiliate_id'] ),
				'referral_id'  => intval( $task_meta['global']['referral_id'] ),
				'email'        => $task_meta['global']['email'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * v2 Method: Get field schema
	 * @return array
	 */
	public function get_field_schema() {
		return [];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_affiliatewp_active() ) {
	return 'BWFAN_AFFWP_Referral_Signup';
}
