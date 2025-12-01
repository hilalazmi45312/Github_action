<?php

#[AllowDynamicProperties]
final class BWFAN_ADV_Coupons_Store_credits_update extends BWFAN_Event {
	private static $instance = null;
	public $total_credit = null;
	public $user_id = null;
	public $email = null;
	private $credit_type = [];

	private function __construct() {
		$this->optgroup_label         = esc_html__( 'Advanced Coupons', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Store Credits Updated', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event will run if store credit is updated ', 'wp-marketing-automations-pro' );
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
		$this->priority               = 100;
		$this->optgroup_priority      = 25;
		$this->support_lang           = true;
		$this->credit_type            = [
			'both'     => __( 'Both', 'wp-marketing-automations-pro' ),
			'increase' => __( 'Increase', 'wp-marketing-automations-pro' ),
			'decrease' => __( 'Decrease', 'wp-marketing-automations-pro' ),
		];
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
		add_action( 'acfw_store_credits_total_changed', [ $this, 'process' ], 10, 1 );
	}

	public function process( $entry ) {

		$data                = $this->get_default_data();
		$data['amount']      = $entry->get_prop( 'amount' );
		$data['user_id']     = $entry->get_prop( 'user_id' );
		$data['credit_type'] = $entry->get_prop( 'type' );

		$this->send_async_call( $data );
	}

	public function get_event_data() {
		$data_to_send['global']['amount']      = $this->total_credit;
		$data_to_send['global']['user_id']     = $this->user_id;
		$data_to_send['global']['credit_type'] = $this->credit_type;

		return $data_to_send;
	}


	/**
	 * validate v2 event settings
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		if ( 'both' === $automation_data['event_meta']['bwfan_credit_type'] || $automation_data['credit_type'] === $automation_data['event_meta']['bwfan_credit_type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->user_id = BWFAN_Common::$events_async_data['user_id'];
		$user          = get_user_by( 'id', $this->user_id );
		if ( ! $user instanceof WP_User ) {
			return $automation_data;
		}
		$this->credit_type  = BWFAN_Common::$events_async_data['credit_type'];
		$credits            = BWFAN_ADV_Coupon_Source::get_user_credits( $this->user_id );
		$this->total_credit = ! empty( $credits ) && isset( $credits['increase'] ) ? $credits['increase'] : 0;
		$this->email        = $user->user_email;

		$automation_data['amount']      = $this->total_credit;
		$automation_data['user_id']     = $this->user_id;
		$automation_data['credit_type'] = $this->credit_type;
		$automation_data['email']       = $this->email;
		$automation_data['first_name']  = $user->first_name;
		$automation_data['last_name']   = $user->last_name;

		return $automation_data;
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		$credit_type = BWFAN_Common::prepared_field_options( $this->credit_type );

		return [
			[
				'id'          => 'bwfan_credit_type',
				'type'        => 'wp_select',
				'options'     => $credit_type,
				'label'       => __( 'Credit Type', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => 'Select',
				"required"    => true,
				"errorMsg"    => __( "Select Store Credit.", 'wp-marketing-automations-pro' ),
				"description" => ""
			],
		];
	}

	/** set default values */
	public function get_default_values() {
		return [
			'bwfan_credit_type' => 'both',
		];
	}


}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_advanced_coupon_for_woocommerce_active() ) {
	return 'BWFAN_ADV_Coupons_Store_credits_update';
}
