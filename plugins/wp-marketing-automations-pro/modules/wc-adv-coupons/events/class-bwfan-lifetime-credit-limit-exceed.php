<?php

#[AllowDynamicProperties]
final class BWFAN_ADV_Lifetime_Credit_Limit_Exceed extends BWFAN_Event {
	private static $instance = null;
	public $total_credit = null;
	public $user_id = null;
	public $email = null;
	private $type = null;

	private function __construct() {
		$this->optgroup_label         = esc_html__( 'Advanced Coupons', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Lifetime Store Credit Reached', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event will run if life time store credits crossed specific amount ', 'wp-marketing-automations-pro' );
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_order' );
		$this->event_rule_groups      = array(
			'wc_order',
			'aerocheckout',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast',
			'store_credit',
		);

		$this->support_lang = true;
		$this->v2           = true;
		$this->support_v1   = false;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'acfw_create_store_credit_entry', [ $this, 'process' ] );
	}

	public function process( $entry ) {
		if ( isset( $entry['type'] ) && 'decrease' === $entry['type'] ) {
			return;
		}

		if ( ! isset( $entry['user_id'] ) || 0 === intval( $entry['user_id'] ) ) {
			return;
		}

		$data            = $this->get_default_data();
		$data['user_id'] = intval( $entry['user_id'] );
		$data['amount']  = floatval( $entry['amount'] );
		$data['type']    = $entry['type'];

		$this->send_async_call( $data );
	}

	public function get_event_data() {
		$data_to_send['global']['amount']  = $this->total_credit;
		$data_to_send['global']['user_id'] = $this->user_id;
		$data_to_send['global']['type']    = $this->type;

		return $data_to_send;
	}

	/**
	 * validate v2 event settings
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		$credits      = BWFAN_ADV_Coupon_Source::get_user_credits( $automation_data['user_id'] );
		$total_credit = ! empty( $credits ) && isset( $credits['increase'] ) ? $credits['increase'] : 0;

		return ( floatval( $automation_data['event_meta']['bwfan_limit_amount'] ) < floatval( $total_credit ) );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$user = get_user_by( 'id', $automation_data['user_id'] );
		if ( ! $user instanceof WP_User ) {
			return $automation_data;
		}

		$this->user_id      = BWFAN_Common::$events_async_data['user_id'];
		$this->type         = BWFAN_Common::$events_async_data['type'];
		$credits            = BWFAN_ADV_Coupon_Source::get_user_credits( $this->user_id );
		$this->total_credit = ! empty( $credits ) && isset( $credits['increase'] ) ? $credits['increase'] : 0;

		$this->email = $user->user_email;

		$automation_data['amount']     = $this->total_credit;
		$automation_data['user_id']    = $this->user_id;
		$automation_data['type']       = $this->type;
		$automation_data['email']      = $this->email;
		$automation_data['first_name'] = $user->first_name;
		$automation_data['last_name']  = $user->last_name;

		return $automation_data;
	}

	/**
	 * v2 Method: Get fields schema
	 *
	 * @return array[][]
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'bwfan_limit_amount',
				'type'        => 'text',
				'label'       => __( 'Amount', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Amount', 'wp-marketing-automations-pro' ),
				"required"    => true,
				"errorMsg"    => __( 'Enter amount.', 'wp-marketing-automations-pro' ),
				"description" => ""
			],
		];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_advanced_coupon_for_woocommerce_active() ) {
	return 'BWFAN_ADV_Lifetime_Credit_Limit_Exceed';
}
