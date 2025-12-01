<?php

#[AllowDynamicProperties]
final class BWFAN_UpStroke_Funnel_Ended extends BWFAN_Event {
	private static $instance = null;
	/** @var WC_Order $order */
	public $order = null;
	public $funnel_id = null;
	public $funnel_name = null;
	public $email = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_order', 'wc_funnel' );
		$this->optgroup_label         = esc_html__( 'Funnel', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Funnel Ended', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a funnel is ended.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'wc_order',
			'wc_customer',
			'funnel',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->support_lang           = true;
		$this->priority               = 45.1;
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
		add_action( 'wffn_funnel_ended_event', [ $this, 'process' ], 999, 2 );

		/** For native thank you page */
		add_action( 'wffn_ty_funnel_ended_event', [ $this, 'process_ty' ], 999, 2 );
	}

	/**
	 * @param $current_step
	 * @param $funnel WFFN_Funnel
	 *
	 * @return array|void
	 */
	public function process( $current_step, $funnel ) {
		$data     = $this->get_default_data();
		$order_id = WFFN_Core()->data->get( 'wc_order' );
		$opid     = WFFN_Core()->data->get( 'opid' );
		if ( empty( $order_id ) && empty( $opid ) ) {
			return;
		}

		$data['order_id'] = $order_id;

		$order = ! empty( $order_id ) ? wc_get_order( $order_id ) : '';
		$email = $order instanceof WC_Order ? $order->get_billing_email() : '';
		if ( ! is_email( $email ) && ! empty( $opid ) ) {
			$bwf_optin = WFFN_DB_Optin::get_instance();
			$optin     = $bwf_optin->get_contact_by_opid( $opid );
			if ( empty( $optin ) || ( $optin->id === 0 && $optin->email !== '' ) ) {
				return;
			}
			$email = $optin->email;
		}
		$data['funnel_id']   = $funnel->get_id();
		$data['funnel_name'] = $funnel->title;
		$data['email']       = $email;
		$this->send_async_call( $data );
	}

	/**
	 * Process callback for native thank you page
	 *
	 * @param $funnel WFFN_Funnel
	 * @param $order_id
	 *
	 * @return void
	 */
	public function process_ty( $funnel, $order_id ) {
		$data                = $this->get_default_data();
		$data['order_id']    = absint( $order_id );
		$data['funnel_id']   = $funnel->get_id();
		$data['funnel_name'] = $funnel->title;

		$order         = ( absint( $order_id ) > 0 ) ? wc_get_order( $order_id ) : '';
		$data['email'] = $order instanceof WC_Order ? $order->get_billing_email() : '';

		$this->send_async_call( $data );
	}


	public function get_event_data() {
		$data_to_send                          = [ 'global' => [] ];
		$data_to_send['global']['order_id']    = is_object( $this->order ) ? BWFAN_Woocommerce_Compatibility::get_order_id( $this->order ) : '';
		$data_to_send['global']['wc_order']    = is_object( $this->order ) ? $this->order : '';
		$data_to_send['global']['email']       = $this->email;
		$data_to_send['global']['funnel_id']   = $this->funnel_id;
		$data_to_send['global']['funnel_name'] = $this->funnel_name;

		return $data_to_send;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$wc_order_id = BWFAN_Merge_Tag_Loader::get_data( 'wc_order_id' );
		if ( empty( $wc_order_id ) || intval( $wc_order_id ) !== intval( $task_meta['global']['order_id'] ) ) {
			$set_data = array(
				'wc_order_id' => intval( $task_meta['global']['order_id'] ),
				'email'       => $task_meta['global']['email'],
				'funnel_id'   => intval( $task_meta['global']['funnel_id'] ),
				'funnel_name' => $task_meta['global']['funnel_name'],
				'wc_order'    => $task_meta['global']['wc_order'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {

		$order_id                       = BWFAN_Common::$events_async_data['order_id'];
		$funnel_id                      = BWFAN_Common::$events_async_data['funnel_id'];
		$this->email                    = BWFAN_Common::$events_async_data['email'];
		$this->funnel_name              = BWFAN_Common::$events_async_data['funnel_name'];
		$this->funnel_id                = $funnel_id;
		$this->order                    = ( intval( $order_id ) > 0 ) ? wc_get_order( $order_id ) : '';
		$automation_data['email']       = $this->email;
		$automation_data['funnel_name'] = $this->funnel_name;
		$automation_data['order_id']    = ( intval( $order_id ) > 0 ) ? $order_id : '';
		$automation_data['funnel_id']   = $this->funnel_id;

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
if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_active() ) {
	return 'BWFAN_UpStroke_Funnel_Ended';
}
