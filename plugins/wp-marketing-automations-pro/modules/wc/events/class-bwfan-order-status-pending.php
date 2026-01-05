<?php

final class BWFAN_WC_Order_Status_Pending extends BWFAN_Event {
	private static $instance = null;
	public $user_id = 0;
	public $email = '';
	public $order_id = 0;

	/** @var $order WC_Order|null */
	public $order = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'wc_order', 'bwf_contact' );
		$this->event_name             = esc_html__( 'Order Status Pending', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a Pending order is created.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'wc_order',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = esc_html__( 'Orders', 'wp-marketing-automations-pro' );
		$this->source_type            = 'wc';
		$this->priority               = 15.2;
		$this->is_time_independent    = true;
		$this->v2                     = true;
		$this->supported_blocks       = [ 'order' ];
		$this->automation_add         = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		/** Hooked over 5 mins action when active automations found */
		add_action( 'bwfan_5_min_action', array( $this, 'checking_eligible_orders' ) );
	}

	public function checking_eligible_orders( $active_automations ) {
		/** Filter active automations for this event */
		$event_automations = array_filter( $active_automations, function ( $automation ) {
			return $this->get_slug() === $automation['event'];
		} );

		if ( 0 === count( $event_automations ) ) {
			return;
		}

		/** Fetching pending state orders with a gap of 10 mins default to current time and a 3 hr interval */
		$mins_gap  = apply_filters( 'bwfan_wc_pending_order_mins_gap', 10 );
		$mins_gap  = absint( $mins_gap ) > 0 ? absint( $mins_gap ) : 10;
		$to        = time() - ( MINUTE_IN_SECONDS * $mins_gap );
		$from      = apply_filters( 'bwfan_wc_pending_order_hrs_interval', 3 );
		$from      = absint( $from ) > 0 ? absint( $from ) : 3;
		$from      = $to - ( $from * HOUR_IN_SECONDS );
		$order_ids = $this->get_unpaid_orders( $from, $to );
		if ( ! is_array( $order_ids ) || ! count( $order_ids ) > 0 ) {
			return;
		}

		/** Trigger event on each order & mark the order as processed for this event */
		foreach ( $order_ids as $order_id ) {
			$this->process( $order_id );
		}
	}

	public function get_unpaid_orders( $from, $to ) {
		global $wpdb;

		if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$order_ids = $wpdb->get_col( $wpdb->prepare( "SELECT distinct(posts.id)
				FROM {$wpdb->prefix}wc_orders AS posts
				WHERE   posts.type = 'shop_order'
				AND     posts.status = 'wc-pending'
				AND     posts.date_created_gmt > %s
				AND     posts.date_created_gmt < %s
				AND     (SELECT count(*) FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = posts.id and meta_key = '_bwfan_pending_checked') = 0", gmdate( 'Y-m-d H:i:s', intval( $from ) ), gmdate( 'Y-m-d H:i:s', intval( $to ) ) ) );

			return $order_ids;
		}

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $wpdb->prepare( "SELECT distinct(posts.ID)
				FROM {$wpdb->posts} AS posts
				WHERE   posts.post_type = 'shop_order'
				AND     posts.post_status = 'wc-pending'
				AND     posts.post_date_gmt > %s
				AND     posts.post_date_gmt < %s
				AND     (SELECT count(*) FROM {$wpdb->postmeta} where post_id = posts.ID and meta_key = '_bwfan_pending_checked') = 0", gmdate( 'Y-m-d H:i:s', absint( $from ) ), gmdate( 'Y-m-d H:i:s', absint( $to ) ) ) );
	}

	public function process( $order_id ) {
		$order = $order_id > 0 ? wc_get_order( $order_id ) : false;
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$this->order_id = $order_id;
		$this->order    = wc_get_order( $this->order_id );
		if ( ! $this->order instanceof WC_Order ) {
			return;
		}

		BWFAN_Core()->public->load_active_automations( $this->get_slug() );
		BWFAN_Core()->public->load_active_v2_automations( $this->get_slug() );

		if ( ( ! is_array( $this->automations_arr ) || count( $this->automations_arr ) === 0 ) && ( ! is_array( $this->automations_v2_arr ) || count( $this->automations_v2_arr ) === 0 ) ) {
			return;
		}

		$this->order->update_meta_data( '_bwfan_pending_checked', 1 );
		$this->order->save();

		$this->user_id = $this->order->get_user_id();
		$this->email   = $this->order->get_billing_email();

		$contact_data_v2 = array(
			'order_id' => absint( $this->order_id ),
			'email'    => $this->email,
			'user_id'  => $this->user_id,
			'event'    => $this->get_slug(),
			'version'  => 2
		);
		BWFAN_Common::maybe_run_v2_automations( $this->get_slug(), $contact_data_v2 );

		if ( empty( $this->automations_arr ) ) {
			return;
		}
		$this->run_automations();
	}

	/**
	 * Override method to change the state of Cart based on Automations found
	 * v1 code
	 *
	 * @return array|bool|void
	 */
	public function run_automations() {
		foreach ( $this->automations_arr as $automation_id => $automation_data ) {
			if ( 0 !== intval( $automation_data['requires_update'] ) ) {
				continue;
			}
			$this->handle_single_automation_run( $automation_data, $automation_id );
		}
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->user_id, 'user_id' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $this->order, 'wc_order' );
		BWFAN_Core()->rules->setRulesData( $this->order_id, 'wc_order_id' );
		BWFAN_Core()->rules->setRulesData( $this->order_id, 'order_id' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->email, $this->user_id ), 'bwf_customer' );
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
		$data_to_send['global']['user_id']     = $this->user_id;
		$data_to_send['global']['email']       = $this->email;
		$data_to_send['global']['wc_order_id'] = $this->order_id;
		$data_to_send['global']['order_id']    = $this->order_id;
		$data_to_send['global']['wc_order']    = $this->order;

		$order_lang = BWFAN_PRO_Common::passing_event_language( [ 'order_id' => $this->order_id ] );
		if ( is_array( $order_lang ) && isset( $order_lang['language'] ) && ! empty( $order_lang['language'] ) ) {
			$data_to_send['global']['language'] = $order_lang['language'];
		}

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
		if ( absint( $global_data['wc_order_id'] ) > 0 ) {
			$order = wc_get_order( absint( $global_data['wc_order_id'] ) );
			if ( $order instanceof WC_Order ) { ?>
                <li>
                    <strong><?php echo esc_html__( 'Order: ', 'wp-marketing-automations-pro' ); ?></strong>
                    <a target="_blank" href="<?php echo get_edit_post_link( $global_data['order_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput
					?>"><?php echo '#' . esc_attr( $global_data['order_id'] . ' ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></a>
                </li>
				<?php
			}
		}
		?>
        <li>
            <strong><?php echo esc_html__( 'Email: ', 'wp-marketing-automations-pro' ); ?></strong>
			<?php echo esc_html__( $global_data['email'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$set_data = array(
			'user_id'     => $task_meta['global']['user_id'],
			'email'       => $task_meta['global']['email'],
			'wc_order_id' => $task_meta['global']['wc_order_id'],
			'order_id'    => $task_meta['global']['order_id'],
			'wc_order'    => $task_meta['global']['wc_order']
		);
		BWFAN_Merge_Tag_Loader::set_data( $set_data );
	}

	public function get_email_event() {
		$email = is_email( $this->email ) ? $this->email : false;
		$email = ( false === $email && $this->user_id > 0 ) ? get_user_by( 'id', $this->user_id ) : false;
		$email = $email instanceof WP_User ? $email->user_email : false;
		$email = is_email( $email ) ? $email : false;

		return $email;
	}

	public function get_user_id_event() {
		$user_id = $this->user_id > 0 ? $this->user_id : false;
		$user_id = ( false === $user_id && $this->order instanceof WC_Order ) ? $this->order->get_user_id() : false;
		$user_id = ( false === $user_id && is_email( $this->email ) ) ? get_user_by( 'email', $this->email ) : false;
		$user_id = $user_id instanceof WP_User ? $user_id->ID : false;

		return $user_id;
	}

	public function validate_event_data_before_executing_task( $data ) {
		if ( ! isset( $data['order_id'] ) ) {
			return false;
		}

		$order = wc_get_order( absint( $data['order_id'] ) );
		if ( $order instanceof WC_Order ) {
			return ( 'pending' === $order->get_status() );
		}

		$order = isset( $data['wc_order_id'] ) ? wc_get_order( absint( $data['wc_order_id'] ) ) : false;
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		return ( 'pending' === $order->get_status() );
	}

	public function get_fields_schema() {
		return [
			[
				'id'       => 'notice',
				'type'     => 'notice',
				'class'    => '',
				'status'   => 'warning',
				'message'  => __( "This event runs automatically for orders marked as 'Pending', starting 10 minutes after creation. Order status is checked before execution.", 'wp-marketing-automations-pro' ),
				'dismiss'  => false,
				'required' => false,
				'isHtml'   => true,
				'toggler'  => [],
			],
		];
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
		$contact = new WooFunnels_Contact( '', '', '', $cid );

		/** Check if contact exists */
		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return [ 'status' => 0, 'type' => __( 'contact_not_found', 'wp-marketing-automations-pro' ) ];
		}

		$unpaid_order_id = $this->get_last_unpaid_order( $cid );
		$order           = wc_get_order( $unpaid_order_id );
		if ( empty( $unpaid_order_id ) || ! $order instanceof WC_Order ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( "Contact doesn't have any pending order.", 'wp-marketing-automations-pro' ) ];
		}

		$this->user_id = $order->get_user_id();
		$this->email   = $order->get_billing_email();

		return array_merge( $automation_data, [ 'order_id' => $unpaid_order_id, 'user_id' => $this->user_id, 'email' => $this->email ] );
	}

	public function get_last_unpaid_order( $cid ) {
		global $wpdb;

		if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$query = $wpdb->prepare( "SELECT o.id FROM {$wpdb->prefix}wc_orders AS o JOIN {$wpdb->prefix}wc_orders_meta AS om ON o.id=om.order_id WHERE `type` = %s AND `status` = %s AND om.meta_key = %s AND om.meta_value=%d ORDER BY o.id DESC LIMIT 1", 'shop_order', 'wc-pending', '_woofunnel_cid', $cid );

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return $wpdb->get_var( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} AS p JOIN {$wpdb->postmeta} AS pm ON p.ID=pm.post_id WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_key = %s AND pm.meta_value=%d ORDER BY p.ID DESC LIMIT 1 ", 'shop_order', 'wc-pending', '_woofunnel_cid', $cid ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() ) {
	return 'BWFAN_WC_Order_Status_Pending';
}
