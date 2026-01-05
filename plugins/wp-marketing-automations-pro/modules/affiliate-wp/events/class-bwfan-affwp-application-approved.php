<?php

#[AllowDynamicProperties]
final class BWFAN_AFFWP_Application_Approved extends BWFAN_Event {
	private static $instance = null;
	public $affiliate_id = null;
	public $status = null;
	public $old_status = null;
	public $user_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'aff_affiliate', 'bwf_contact' );
		$this->optgroup_label         = esc_html__( 'AffiliateWP', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Application Approved', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after an affiliate application is approved.', 'wp-marketing-automations-pro' );
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
		$this->priority               = 10;
		$this->customer_email_tag     = '{{affwp_affiliate_email}}';
		$this->v2                     = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'affwp_set_affiliate_status', array( $this, 'process' ), 10, 3 );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $affiliate_id
	 * @param $status
	 * @param $old_status
	 */
	public function process( $affiliate_id, $status, $old_status ) {
		if ( 'active' !== $status || $status === $old_status ) {
			return;
		}

		$data                 = $this->get_default_data();
		$data['affiliate_id'] = $affiliate_id;
		$data['status']       = $status;
		$data['old_status']   = $old_status;

		$this->send_async_call( $data );
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
		$data_to_send                           = [ 'global' => [] ];
		$data_to_send['global']['affiliate_id'] = $this->affiliate_id;
		$data_to_send['global']['status']       = $this->status;
		$data_to_send['global']['old_status']   = $this->old_status;
		$data_to_send['global']['user_id']      = $this->user_id;

		return $data_to_send;
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

		$affiliate_id = isset( $global_data['affiliate_id'] ) ? $global_data['affiliate_id'] : 0;
		?>
        <li>
            <strong><?php echo esc_html__( 'Affiliate Id:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url( 'admin.php' ) . '?page=affiliate-wp-affiliates&affiliate_id=' . $affiliate_id . '&action=edit_affiliate'; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $affiliate_id ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Status:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['status'] ); ?></span>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Old Status:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['old_status'] ); ?></span>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->affiliate_id = BWFAN_Common::$events_async_data['affiliate_id'];
		$this->status       = BWFAN_Common::$events_async_data['status'];
		$this->old_status   = BWFAN_Common::$events_async_data['old_status'];
		$this->user_id      = affwp_get_affiliate_user_id( $this->affiliate_id );

		return $this->run_automations();
	}

	public function get_email_event() {
		if ( ! empty( absint( $this->user_id ) ) ) {
			$user = get_user_by( 'id', absint( $this->user_id ) );

			return ( $user instanceof WP_User ) ? $user->user_email : false;
		}

		return false;
	}

	public function get_user_id_event() {
		return ! empty( absint( $this->user_id ) ) ? absint( $this->user_id ) : false;
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
				'user_id'      => intval( $task_meta['global']['user_id'] ),
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Set up rules data
	 *
	 * @param $value
	 */
	public function pre_executable_actions( $value ) {
		BWFAN_Core()->rules->setRulesData( $this->affiliate_id, 'affiliate_id' );
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->affiliate_id = BWFAN_Common::$events_async_data['affiliate_id'];
		$this->status       = BWFAN_Common::$events_async_data['status'];
		$this->old_status   = BWFAN_Common::$events_async_data['old_status'];
		$this->user_id      = affwp_get_affiliate_user_id( $this->affiliate_id );

		$automation_data['affiliate_id'] = $this->affiliate_id;
		$automation_data['status']       = $this->status;
		$automation_data['old_status']   = $this->old_status;
		$automation_data['user_id']      = $this->user_id;

		return $automation_data;
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
	return 'BWFAN_AFFWP_Application_Approved';
}
