<?php

#[AllowDynamicProperties]
final class BWFAN_CRM_Contact_Subscribed extends BWFAN_Event {
	private static $instance = null;
	public $contact_id = null;
	public $email = null;
	public $user_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact' );
		$this->optgroup_label         = esc_html__( 'Contact', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Contact Subscribes', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after contact is subscribed.', 'wp-marketing-automations-pro' );
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
		$this->priority               = 30;
		$this->customer_email_tag     = '{{contact_email}}';
		$this->v2                     = true;
		$this->is_goal                = true;
		$this->automation_add         = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfcrm_after_contact_subscribed', [ $this, 'process' ], 10 );
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
		$data_to_send                         = [ 'global' => [] ];
		$data_to_send['global']['contact_id'] = $this->contact_id;
		$data_to_send['global']['email']      = $this->email;

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

		$contact_id  = isset( $global_data['contact_id'] ) ? $global_data['contact_id'] : 0;
		$contact_url = admin_url( "admin.php?page=autonami&path=/contact/" . $contact_id );
		?>
        <li>
            <strong><?php echo esc_html__( 'Contact_id:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a href="<?php echo esc_attr( $contact_url ); ?>"><?php echo esc_html__( $global_data['contact_id'] ); ?></a>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->contact_id = BWFAN_Common::$events_async_data['contact_id'];
		$this->email      = BWFAN_Common::$events_async_data['email'];

		return $this->run_automations();
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
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'contact_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['contact_id'] ) ) ) {
			$set_data = array(
				'contact_id' => intval( $task_meta['global']['contact_id'] ),
				'email'      => $task_meta['global']['email'],
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
		BWFAN_Core()->rules->setRulesData( $this->contact_id, 'contact_id' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
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

	public function validate_goal_settings( $automation_settings, $automation_data ) {
		/** settings not found */
		if ( ! isset( $automation_data['event'] ) ) {
			return false;
		}

		return $automation_data['event'] === $this->get_slug();
	}

	/**
	 * get contact automation data
	 *
	 * @param $automation_data
	 * @param $cid
	 *
	 * @return array|null[]
	 */
	public function get_manually_added_contact_automation_data( $automation_data, $cid ) {
		$contact = new BWFCRM_Contact( $cid );

		/** Check if contact exists */
		if ( ! $contact->is_contact_exists() ) {
			return [ 'status' => 0, 'type' => 'contact_not_found' ];
		}
		/** Return if contact is not subscribed */
		if ( BWFCRM_Contact::$DISPLAY_STATUS_SUBSCRIBED !== intval( $contact->get_display_status() ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( 'Contact status is not Subscribed', 'wp-marketing-automations-pro' ) ];
		}
		$this->contact_id = $contact->get_id();
		$this->email      = $contact->contact->get_email();

		return array_merge( $automation_data, [ 'contact_id' => $this->contact_id, 'email' => $this->email ] );
	}

	public function pre_validate_goal( $step_data, $contact_id ) {
		if ( empty( $contact_id ) ) {
			return false;
		}
		$contact         = new WooFunnels_Contact( '', '', '', $contact_id );
		$is_unsubscribed = $this->check_contact_unsubscribed( $contact );

		return ( 1 === intval( $contact->get_status() ) && empty( $is_unsubscribed ) );
	}

	/**
	 * @param $contact WooFunnels_Contact
	 *
	 * @return array|object|string|null
	 */
	public function check_contact_unsubscribed( $contact ) {
		$email      = $contact->get_email();
		$contact_no = $contact->get_contact_no();
		$data       = array(
			'recipient' => array( $email, $contact_no ),
		);

		return BWFAN_Model_Message_Unsubscribe::get_message_unsubscribe_row( $data, true );
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( class_exists( 'BWFCRM_Contact' ) ) {
	return 'BWFAN_CRM_Contact_Subscribed';
}
