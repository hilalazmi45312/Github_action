<?php

/**
 *  Class BWFAN_WCS_Note_Added
 */
#[AllowDynamicProperties]
final class BWFAN_WCS_Note_Added extends BWFAN_Event {
	private static $instance = null;
	public $subscription_id = null;
	public $subscription = null;
	private $is_customer_note = false;
	private $comment_content = '';
	private $subscriptions_note_types = [];
	private $email = null;

	private function __construct() {
		$this->optgroup_label = esc_html__( 'Subscription', 'wp-marketing-automations-pro' );
		$this->event_name     = esc_html__( 'Subscription Note Added', 'wp-marketing-automations-pro' );
		$this->event_desc     = esc_html__( 'This event runs after a new subscriptions note is added.', 'wp-marketing-automations-pro' );

		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_subscription', 'wc_subscription_note' );

		$this->event_rule_groups        = array(
			'wc_subscription',
			'wc_customer',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->priority                 = 30;
		$this->support_lang             = true;
		$this->subscriptions_note_types = [
			'both'    => __( 'Both', 'wp-marketing-automations-pro' ),
			'private' => __( 'Private', 'wp-marketing-automations-pro' ),
			'public'  => __( 'Customer', 'wp-marketing-automations-pro' ),
		];
		$this->v2                       = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_filter( 'woocommerce_new_order_note_data', [ $this, 'process_note' ], 10, 2 );
	}

	/**
	 * Localize data for html fields for the current event.
	 */
	public function admin_enqueue_assets() {
		BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'subscription_note_type', $this->subscriptions_note_types );
	}

	/**
	 * Returns the current event settings set in the automation at the time of task creation.
	 *
	 * @param $value
	 *
	 * @return array
	 */
	public function get_automation_event_data( $value ) {
		return [
			'event_source'   => $value['source'],
			'event_slug'     => $value['event'],
			'validate_event' => ( isset( $value['event_meta']['validate_event'] ) ) ? 1 : 0,
		];
	}

	public function validate_event_data_before_executing_task( $data ) {
		return $this->validate_subscription( $data );
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
		$order = wc_get_order( $global_data['wc_subscription_id'] );
		if ( $order instanceof WC_Order ) {
			?>
            <li>
                <strong><?php esc_html_e( 'Subscription:', 'wp-marketing-automations-pro' ); ?> </strong>
                <a target="_blank" href="<?php echo get_edit_post_link( $global_data['wc_subscription_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput
				?>"><?php echo '#' . esc_html( $global_data['wc_subscription_id'] . ' ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></a>
            </li>
		<?php } ?>
        <li>
            <strong><?php esc_html_e( 'Email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php esc_html_e( $global_data['email'] ); ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Note:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo $global_data['wcs_note_content']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
        </li>
        <li>
            <strong><?php esc_html_e( 'Type:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php

			if ( wc_string_to_bool( $global_data['wcs_customer_note_type'] ) ) {
				esc_html_e( 'Note to customer', 'woocommerce' );
			} else {
				esc_html_e( 'Private note', 'woocommerce' );
			}
			?>
        </li>
		<?php
		return ob_get_clean();
	}

	public function get_email_event() {
		if ( $this->subscription instanceof WC_Subscription ) {
			return $this->subscription->get_billing_email();
		}

		if ( ! empty( absint( $this->subscription_id ) ) && function_exists( 'wcs_get_subscription' ) ) {
			$subscription = wcs_get_subscription( absint( $this->subscription_id ) );

			return $subscription instanceof WC_Subscription ? $subscription->get_billing_email() : false;
		}

		return false;
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {
		?>
        <script type="text/html" id="tmpl-event-<?php esc_attr_e( $this->get_slug() ); ?>">
            <div class="bwfan_mt15"></div>
            <label for="bwfan-select-box-order-note" class="bwfan-label-title"><?php esc_html_e( 'Select Subscription Note Mode', 'wp-marketing-automations-pro' ); ?></label>
            <div class="bwfan-select-box">
                <#
                selected_statuses = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'bwfan_subscription_note_type')) ? data.eventSavedData.bwfan_subscription_note_type : 'both';
                #>
                <select name="event_meta[bwfan_subscription_note_type]" id="bwfan-select-box-order-note" class="bwfan-input-wrapper">
                    <#
                    if(_.has(bwfan_events_js_data, 'wcs_note_added') && _.isObject(bwfan_events_js_data.wcs_note_added.subscription_note_type) ) {
                    _.each( bwfan_events_js_data.wcs_note_added.subscription_note_type, function( title, key ){
                    selected = (key == selected_statuses) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{title}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>
        </script>
		<?php
	}

	/**
	 * Admin add customer note
	 *
	 * @param $comment_data
	 * @param $data
	 *
	 * @return mixed
	 */
	public function process_note( $comment_data, $data ) {
		$subscription = wcs_get_subscription( $comment_data['comment_post_ID'] );
		if ( ! $subscription instanceof WC_Subscription || 'shop_subscription' !== $subscription->get_type() || $comment_data['comment_type'] !== 'order_note' ) {
			return $comment_data;
		}

		$data['comment_content']    = $comment_data['comment_content'];
		$data['wc_subscription_id'] = $subscription->get_id();
		$data['comment_data']       = $comment_data;
		$data['email']              = $subscription->get_billing_email();

		$this->process( $data );

		return $comment_data;
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $order_id
	 */
	public function process( $comment_data ) {
		$data                       = $this->get_default_data();
		$data['wc_subscription_id'] = $comment_data['wc_subscription_id'];
		$data['comment_content']    = $comment_data['comment_content'];
		$data['is_customer_note']   = $comment_data['is_customer_note'];
		$data['email']              = $comment_data['email'];
		$this->send_async_call( $data );
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return false;
		}
		$subscription_id        = BWFAN_Common::$events_async_data['wc_subscription_id'];
		$subscription           = wcs_get_subscription( $subscription_id );
		$this->comment_content  = BWFAN_Common::$events_async_data['comment_content'];
		$this->subscription     = $subscription;
		$this->subscription_id  = $subscription_id;
		$this->is_customer_note = BWFAN_Common::$events_async_data['is_customer_note'];
		$this->email            = BWFAN_Common::$events_async_data['email'];

		return $this->run_automations();
	}

	public function run_automations() {
		BWFAN_Core()->public->load_active_automations( $this->get_slug() );

		if ( ! is_array( $this->automations_arr ) || count( $this->automations_arr ) === 0 ) {
			BWFAN_Core()->logger->log( 'Async callback: No active automations found. Event - ' . $this->get_slug(), $this->log_type );

			return false;
		}

		$automation_actions = [];
		foreach ( $this->automations_arr as $automation_id => $value1 ) {
			if ( $this->get_slug() !== $value1['event'] ) {
				wp_send_json( $this->get_slug() !== $value1['event'] );
				continue;
			}

			$ran_actions                          = $this->handle_single_automation_run( $value1, $automation_id );
			$automation_actions[ $automation_id ] = $ran_actions;
		}

		return $automation_actions;
	}

	public function get_user_id_event() {
		if ( $this->subscription instanceof WC_Subscription ) {
			return $this->subscription->get_user_id();
		}

		if ( ! empty( absint( $this->subscription_id ) ) && function_exists( 'wcs_get_subscription' ) ) {
			$subscription = wcs_get_subscription( absint( $this->subscription_id ) );

			return $subscription instanceof WC_Subscription ? $subscription->get_user_id() : 0;
		}

		return 0;
	}

	/**
	 * Set up rules data
	 *
	 * @param $value
	 */
	public function pre_executable_actions( $value ) {
		BWFAN_Core()->rules->setRulesData( $this->event_automation_id, 'automation_id' );
		BWFAN_Core()->rules->setRulesData( $this->subscription, 'wc_subscription' );
		BWFAN_Core()->rules->setRulesData( $this->comment_content, 'wcs_subscription_note' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->subscription->get_billing_email(), $this->subscription->get_user_id() ), 'bwf_customer' );
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

		$meta = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'event_meta' );
		if ( '' === $meta || ! is_array( $meta ) || ! isset( $meta['bwfan_subscription_note_type'] ) ) {
			return;
		}

		/** @var bool $note_type - if true then public */
		$note_type = wc_string_to_bool( $this->is_customer_note );
		$save_type = $meta['bwfan_subscription_note_type'];

		$register_task = false;
		if ( 'both' === $save_type ) {
			$register_task = true;
		} elseif ( 'public' === $save_type && true === $note_type ) {
			$register_task = true;
		} elseif ( 'private' === $save_type && true !== $note_type ) {
			$register_task = true;
		}

		if ( false === $register_task ) {
			return;
		}

		$data_to_send = $this->get_event_data( $save_type );
		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data( $save_type = '' ) {
		$data_to_send                                     = [ 'global' => [] ];
		$data_to_send['global']['wc_subscription_id']     = $this->subscription_id;
		$data_to_send['global']['wcs_note_content']       = $this->comment_content;
		$data_to_send['global']['wcs_customer_note_type'] = $this->is_customer_note;
		$data_to_send['global']['wcs_save_note_type']     = $save_type;

		$this->subscription                        = $this->subscription instanceof WC_Subscription ? $this->subscription : wcs_get_subscription( $this->subscription_id );
		$data_to_send['global']['email']           = $this->email;
		$data_to_send['global']['phone']           = $this->subscription instanceof WC_Subscription ? $this->subscription->get_billing_phone() : '';
		$data_to_send['global']['wc_subscription'] = $this->subscription;
		$user_id                                   = $this->get_user_id_event();
		if ( intval( $user_id ) > 0 ) {
			$data_to_send['global']['user_id'] = $user_id;
			$email                             = BWFAN_PRO_Common::get_contact_email( $user_id );
			if ( ! empty( $email ) ) {
				$data_to_send['global']['email'] = $email;
			}
		}

		return $data_to_send;
	}

	public function set_merge_tags_data( $task_meta ) {
		$subscription_id = BWFAN_Merge_Tag_Loader::get_data( 'wc_subscription_id' );

		if ( ( empty( $subscription_id ) || intval( $subscription_id ) !== intval( $task_meta['global']['wc_subscription_id'] ) ) && function_exists( 'wcs_get_subscription' ) ) {
			$set_data = array(
				'wc_subscription_id' => intval( $task_meta['global']['wc_subscription_id'] ),
				'email'              => $task_meta['global']['email'],
				'wc_subscription'    => $task_meta['global']['wc_subscription'],
				'wcs_note_content'   => $task_meta['global']['wcs_note_content'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		if ( ! isset( $automation_data['event_meta'] ) || empty( $automation_data['event_meta'] ) ) {
			return false;
		}

		$save_type = ( isset( $automation_data['event_meta'] ) && isset( $automation_data['event_meta']['bwfan_subscription_note_type'] ) ) ? $automation_data['event_meta']['bwfan_subscription_note_type'] : 'both';

		/** @var bool $note_type - if true then public */
		$note_type = wc_string_to_bool( $automation_data['is_customer_note'] );

		if ( 'both' === $save_type ) {
			return true;
		} elseif ( 'public' === $save_type && true === $note_type ) {
			return true;
		} elseif ( 'private' === $save_type && true !== $note_type ) {
			return true;
		}

		return false;
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return $automation_data;
		}

		$this->subscription_id  = BWFAN_Common::$events_async_data['wc_subscription_id'];
		$this->subscription     = wcs_get_subscription( $this->subscription_id );
		$this->comment_content  = BWFAN_Common::$events_async_data['comment_content'];
		$this->is_customer_note = BWFAN_Common::$events_async_data['is_customer_note'];
		$this->email            = BWFAN_Common::$events_async_data['email'];

		$automation_data['wc_subscription_id'] = $this->subscription_id;
		$automation_data['comment_content']    = $this->comment_content;
		$automation_data['is_customer_note']   = $this->is_customer_note;
		$automation_data['email']              = $this->email;

		return $automation_data;
	}

	public function get_fields_schema() {
		$options = BWFAN_PRO_Common::prepared_field_options( $this->subscriptions_note_types );

		return [
			[
				'id'          => 'bwfan_subscription_note_type',
				'label'       => __( "Select Subscription Note Mode", 'wp-marketing-automations-pro' ),
				'type'        => 'wp_select',
				'options'     => $options,
				'placeholder' => __( 'Choose note type', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				'tip'         => "",
				"description" => "",
				"required"    => true,
			],
		];
	}

	/** set default values */
	public function get_default_values() {
		return [
			'bwfan_subscription_note_type' => 'both',
		];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	return 'BWFAN_WCS_Note_Added';
}
