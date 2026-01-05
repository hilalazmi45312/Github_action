<?php

#[AllowDynamicProperties]
final class BWFAN_UpStroke_Offer_Rejected extends BWFAN_Event {
	private static $instance = null;
	public $order = null;
	public $funnel_id = null;
	public $offer_id = null;
	public $offer_type = null;
	public $details = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_order', 'wc_funnel', 'wc_offer' );

		$this->optgroup_label    = esc_html__( 'One-Click Upsells', 'wp-marketing-automations-pro' );
		$this->event_name        = esc_html__( 'Offer Rejected', 'wp-marketing-automations-pro' );
		$this->event_desc        = esc_html__( 'This event runs after an offer is rejected by the customer.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups = array(
			'wc_order',
			'wc_customer',
			'upstroke_funnel_offers',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->support_lang      = true;
		$this->priority          = 45.4;
		$this->v2                = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'wfocu_offer_rejected_event', [ $this, 'offer_rejected' ], 999, 1 );
	}

	/**
	 * Set up rules data
	 *
	 * @param $value
	 */
	public function pre_executable_actions( $value ) {
		BWFAN_Core()->rules->setRulesData( $this->order, 'wc_order' );
		BWFAN_Core()->rules->setRulesData( $this->funnel_id, 'upstroke_funnel_id' );
		BWFAN_Core()->rules->setRulesData( $this->offer_id, 'upstroke_offer_id' );
		BWFAN_Core()->rules->setRulesData( $this->event_automation_id, 'automation_id' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->order->get_billing_email(), $this->order->get_user_id() ), 'bwf_customer' );
	}

	public function offer_rejected( $details ) {
		$this->process( $details );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $details
	 */
	public function process( $details ) {
		if ( isset( $details['order_id'] ) && isset( $details['funnel_id'] ) && isset( $details['offer_id'] ) && isset( $details['offer_type'] ) ) {
			$data            = $this->get_default_data();
			$data['details'] = $details;

			$this->send_async_call( $data );
		}
	}

	/**
	 * Make the view data for the current event which will be shown in task listing screen.
	 *
	 * @param $global_data
	 *
	 * @return false|string
	 */
	public function get_task_view( $global_data ) {
		ob_start();
		?>
        <li>
            <strong><?php echo esc_html__( 'Funnel ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url( 'admin.php' ) . '?page=upstroke&section=rules&edit=' . $global_data['funnel_id']; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $global_data['funnel_id'] ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'User Email:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['email'] ); ?></span>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Registers the tasks for current event.
	 *
	 * @param $automation_id
	 * @param $integration_data
	 * @param $event_data
	 */
	public function register_tasks( $automation_id, $integration_data, $event_data ) {
		if ( ! is_array( $integration_data ) ) {
			return;
		}

		$data_to_send = $this->get_event_data();

		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data() {
		$data_to_send                        = [ 'global' => [] ];
		$data_to_send['global']['order_id']  = is_object( $this->order ) ? BWFAN_Woocommerce_Compatibility::get_order_id( $this->order ) : '';
		$data_to_send['global']['wc_order']  = is_object( $this->order ) ? $this->order : '';
		$data_to_send['global']['email']     = is_object( $this->order ) ? BWFAN_Woocommerce_Compatibility::get_billing_email( $this->order ) : '';
		$data_to_send['global']['funnel_id'] = $this->funnel_id;
		$data_to_send['global']['offer_id']  = $this->offer_id;

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
				'offer_id'    => intval( $task_meta['global']['offer_id'] ),
				'wc_order'    => $task_meta['global']['wc_order'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$details          = BWFAN_Common::$events_async_data['details'];
		$this->details    = $details;
		$this->order      = wc_get_order( $details['order_id'] );
		$this->funnel_id  = $this->details['funnel_id'];
		$this->offer_id   = $this->details['offer_id'];
		$this->offer_type = $this->details['offer_type'];

		return $this->run_automations();
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$details                               = BWFAN_Common::$events_async_data['details'];
		$this->details                         = $details;
		$this->order                           = wc_get_order( $details['order_id'] );
		$this->funnel_id                       = $this->details['funnel_id'];
		$this->offer_id                        = $this->details['offer_id'];
		$this->offer_type                      = $this->details['offer_type'];
		$automation_data['details']            = $this->details;
		$automation_data['order_id']           = $details['order_id'];
		$automation_data['upstroke_funnel_id'] = $this->funnel_id;
		$automation_data['upstroke_offer_id']  = $this->offer_id;
		$automation_data['offer_type']         = $this->offer_type;

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
if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	return 'BWFAN_UpStroke_Offer_Rejected';
}
