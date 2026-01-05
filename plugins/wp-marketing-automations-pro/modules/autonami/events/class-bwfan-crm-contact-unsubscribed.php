<?php

#[AllowDynamicProperties]
final class BWFAN_CRM_Contact_UnSubscribed extends BWFAN_Event {
	private static $instance = null;
	public $phone = null;
	public $email = null;
	public $object_id = null;
	public $object_type = null;
	public $contact_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact' );
		$this->optgroup_label         = esc_html__( 'Contact', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Contact Unsubscribes', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after contact is unsubscribed.', 'wp-marketing-automations-pro' );
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
		$this->automation_add         = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfcrm_after_contact_unsubscribed', [ $this, 'process' ], 10, 1 );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $contact
	 */
	public function process( $insert_data ) {
		if ( ! is_array( $insert_data ) ) {
			return;
		}
		$data = $this->get_default_data();

		foreach ( $insert_data as $data_key => $unsubscribe_data ) {
			$mode                = is_email( trim( $unsubscribe_data['recipient'] ) ) ? 1 : 2;
			$data['email']       = 1 === absint( $mode ) ? $unsubscribe_data['recipient'] : '';
			$data['phone']       = 2 === absint( $mode ) ? $unsubscribe_data['recipient'] : '';
			$data['object_id']   = isset( $unsubscribe_data['automation_id'] ) ? $unsubscribe_data['automation_id'] : 0;
			$data['object_type'] = isset( $unsubscribe_data['c_type'] ) ? $unsubscribe_data['c_type'] : 3;
			$data['contact_id']  = ( new WooFunnels_Contact( '', $data['email'], $data['phone'] ) )->get_id();

			if ( empty( $data['email'] ) ) {
				$data['email'] = $this->email;
			}

			$this->send_async_call( $data );
		}

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
		$data_to_send                          = [ 'global' => [] ];
		$data_to_send['global']['contact_id']  = $this->contact_id;
		$data_to_send['global']['email']       = $this->email;
		$data_to_send['global']['phone']       = $this->phone;
		$data_to_send['global']['object_id']   = $this->object_id;
		$data_to_send['global']['object_type'] = $this->object_type;

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
		if ( ! empty( $contact_id ) ) {
			?>
            <li>
                <strong><?php echo esc_html__( 'Contact_id:', 'wp-marketing-automations-pro' ); ?> </strong>
                <a href="<?php echo esc_attr( $contact_url ); ?>"><?php echo esc_html__( $global_data['contact_id'] ); ?></a>
            </li>
			<?php
		}
		if ( isset( $global_data['email'] ) && ! empty( $global_data['email'] ) ) {
			?>

            <li>
                <strong><?php echo esc_html__( 'Email:', 'wp-marketing-automations-pro' ); ?> </strong>
				<?php echo esc_html__( $global_data['email'] ); ?>
            </li>
			<?php
		}

		if ( isset( $global_data['phone'] ) && ! empty( $global_data['phone'] ) ) {
			?>
            <li>
                <strong><?php echo esc_html__( 'Phone:', 'wp-marketing-automations-pro' ); ?> </strong>
				<?php echo esc_html__( $global_data['phone'] ); ?>
            </li>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->contact_id  = BWFAN_Common::$events_async_data['contact_id'];
		$this->email       = BWFAN_Common::$events_async_data['email'];
		$this->phone       = BWFAN_Common::$events_async_data['phone'];
		$this->object_id   = BWFAN_Common::$events_async_data['object_id'];
		$this->object_type = BWFAN_Common::$events_async_data['object_type'];
		if ( empty( $this->email ) ) {
			return;
		}

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
				'contact_id'  => intval( $task_meta['global']['contact_id'] ),
				'email'       => $task_meta['global']['email'],
				'phone'       => $task_meta['global']['phone'],
				'object_type' => $task_meta['global']['object_type'],
				'object_id'   => $task_meta['global']['object_id'],
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
		BWFAN_Core()->rules->setRulesData( $this->phone, 'phone' );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->contact_id  = BWFAN_Common::$events_async_data['contact_id'];
		$this->email       = BWFAN_Common::$events_async_data['email'];
		$this->phone       = BWFAN_Common::$events_async_data['phone'];
		$this->object_id   = BWFAN_Common::$events_async_data['object_id'];
		$this->object_type = BWFAN_Common::$events_async_data['object_type'];
		if ( empty( $this->email ) ) {
			return false;
		}

		$automation_data['contact_id']  = $this->contact_id;
		$automation_data['email']       = $this->email;
		$automation_data['phone']       = $this->phone;
		$automation_data['object_id']   = $this->object_id;
		$automation_data['object_type'] = $this->object_type;

		return $automation_data;
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

		/** Return if contact is un-subscribed */
		if ( BWFCRM_Contact::$DISPLAY_STATUS_SUBSCRIBED !== intval( $contact->get_display_status() ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( 'Contact status is not Un-subscribed', 'wp-marketing-automations-pro' ) ];
		}
		$this->contact_id = $contact->get_id();
		$this->email      = $contact->contact->get_email();

		return array_merge( $automation_data, [ 'contact_id' => $this->contact_id, 'email' => $this->email ] );
	}

	public function get_fields_schema() {
		return [];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
return 'BWFAN_CRM_Contact_UnSubscribed';
