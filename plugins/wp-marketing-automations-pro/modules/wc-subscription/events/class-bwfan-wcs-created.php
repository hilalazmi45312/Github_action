<?php

#[AllowDynamicProperties]
final class BWFAN_WCS_Created extends BWFAN_Event {
	private static $instance = null;
	public $subscription = null;
	public $order = null;
	public $email = null;
	public $subscription_id = null;
	public $order_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_subscription' );

		$this->event_name        = esc_html__( 'Subscriptions Created', 'wp-marketing-automations-pro' );
		$this->event_desc        = esc_html__( 'This event runs after a new subscription is created.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups = array(
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
		$this->optgroup_label    = esc_html__( 'Subscription', 'wp-marketing-automations-pro' );
		$this->support_lang      = true;
		$this->priority          = 25;
		$this->v2                = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'woocommerce_checkout_subscription_created', [ $this, 'subscription_created' ], 20, 2 );
		add_filter( 'bwfan_before_making_logs', array( $this, 'check_if_bulk_process_executing' ), 10, 1 );
		add_action( 'wcs_api_subscription_created', [ $this, 'subscription_created' ], 20 );
		add_action( 'woocommerce_admin_created_subscription', [ $this, 'subscription_created' ], 20 );
		add_action( 'wfocu_subscription_created_for_upsell', [ $this, 'upsell_subscription_created' ], 10, 3 );
	}

	/**
	 * @param $subscription WC_Subscription
	 * @param $order WC_Order
	 *
	 * @return void
	 */
	public function subscription_created( $subscription, $order = '' ) {
		$subscription_id = $subscription->get_id();
		$this->email     = $subscription->get_billing_email();
		$order_id        = $order instanceof WC_Order ? $order->get_id() : $subscription->get_last_order();
		$this->process( $subscription_id, $order_id );
	}

	/**
	 * @param $subscription WC_Subscription
	 * @param $product_hash
	 * @param $subscription_order WC_Order
	 *
	 * @return void
	 */
	public function upsell_subscription_created( $subscription, $product_hash, $subscription_order ) {
		$subscription_id = $subscription->get_id();
		$this->email     = $subscription->get_billing_email();
		$order_id        = $subscription_order instanceof WC_Order ? $subscription_order->get_id() : $subscription->get_last_order();
		$this->process( $subscription_id, $order_id );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $subscription_id
	 * @param $order_id
	 */
	public function process( $subscription_id, $order_id ) {
		$data                       = $this->get_default_data();
		$data['wc_subscription_id'] = $subscription_id;
		$data['order_id']           = $order_id;
		$data['email']              = $this->email;

		$this->send_async_call( $data );
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->order, 'wc_order' );
		BWFAN_Core()->rules->setRulesData( $this->subscription, 'wc_subscription' );
		BWFAN_Core()->rules->setRulesData( $this->event_automation_id, 'automation_id' );
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

		$data_to_send = $this->get_event_data();

		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data() {
		$user_id = $this->get_user_id_event();

		$data_to_send                                 = [ 'global' => [] ];
		$data_to_send['global']['wc_order']           = is_object( $this->order ) ? $this->order : '';
		$data_to_send['global']['order_id']           = $this->order_id;
		$data_to_send['global']['wc_subscription_id'] = $this->subscription_id;
		$data_to_send['global']['wc_subscription']    = is_object( $this->subscription ) ? $this->subscription : '';
		$data_to_send['global']['email']              = $this->email;

		if ( intval( $user_id ) > 0 ) {
			$data_to_send['global']['user_id'] = $user_id;
			$email                             = BWFAN_PRO_Common::get_contact_email( $user_id );
			if ( ! empty( $email ) ) {
				$data_to_send['global']['email'] = $email;
			}
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
		?>
        <li>
            <strong><?php echo esc_html__( 'Subscription ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo get_edit_post_link( $global_data['wc_subscription_id'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( '#' . $global_data['wc_subscription_id'] ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Subscription Email:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['email'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	public function get_email_event() {
		if ( $this->order instanceof WC_Order ) {
			return $this->order->get_billing_email();
		}

		if ( $this->subscription instanceof WC_Subscription ) {
			return $this->subscription->get_billing_email();
		}

		return false;
	}

	public function get_user_id_event() {
		if ( $this->order instanceof WC_Order ) {
			return $this->order->get_user_id();
		}

		if ( $this->subscription instanceof WC_Subscription ) {
			return $this->subscription->get_user_id();
		}

		return 0;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'wc_subscription_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['wc_subscription_id'] ) ) && function_exists( 'wcs_get_subscription' ) ) {
			$set_data = array(
				'wc_subscription_id' => intval( $task_meta['global']['wc_subscription_id'] ),
				'email'              => $task_meta['global']['email'],
				'wc_subscription'    => $task_meta['global']['wc_subscription'],
				'wc_order'           => $task_meta['global']['wc_order'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	public function validate_event_data_before_executing_task( $data ) {
		return $this->validate_subscription( $data );
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		if ( ! $this->validate_subscription( $automation_data ) ) {
			return false;
		}
		$event_meta            = isset( $automation_data['event_meta'] ) ? $automation_data['event_meta'] : [];
		$subscription_contains = isset( $event_meta['subscription-contains'] ) ? $event_meta['subscription-contains'] : '';

		/** Any product case */
		if ( empty( $subscription_contains ) || 'any' === $subscription_contains ) {
			return true;
		}

		/** Specific product case */
		$subscription         = wcs_get_subscription( $automation_data['wc_subscription_id'] );
		$get_selected_product = $event_meta['products'];
		$ordered_products     = array();
		foreach ( $subscription->get_items() as $subscription_product ) {
			$ordered_products[] = $subscription_product->get_product_id();
			/** In case variation */
			if ( $subscription_product->get_variation_id() ) {
				$ordered_products[] = $subscription_product->get_variation_id();
			}
		}
		/** Selected product and ordered products */
		$product_selected = array_column( $get_selected_product, 'id' );
		$ordered_products = array_unique( $ordered_products );
		sort( $ordered_products );

		return count( array_intersect( $product_selected, $ordered_products ) ) > 0;
	}

	/**
	 * Recalculate action's execution time with respect to order date.
	 * eg.
	 * today is 22 jan.
	 * order was placed on 17 jan.
	 * user set an email to send after 10 days of order placing.
	 * user setup the sync process.
	 * email should be sent on 27 Jan as the order date was 17 jan.
	 *
	 * @param $actions
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function recalculate_actions_time( $actions ) {
		$subscription_date = $this->subscription->get_date( 'start' );
		$subscription_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $subscription_date );
		$actions           = $this->calculate_actions_time( $actions, $subscription_date );

		return $actions;
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return false|void
	 */
	public function capture_async_data() {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return false;
		}

		$this->subscription_id = BWFAN_Common::$events_async_data['wc_subscription_id'];
		$this->order_id        = BWFAN_Common::$events_async_data['order_id'];
		$subscription          = wcs_get_subscription( $this->subscription_id );
		$order                 = wc_get_order( $this->order_id );
		$this->subscription    = $subscription;
		$this->order           = $order;
		$this->email           = BWFAN_Common::$events_async_data['email'];

		$this->run_automations();
	}

	/**
	 * v2 capture data.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return $automation_data;
		}

		$this->subscription_id = BWFAN_Common::$events_async_data['wc_subscription_id'];
		$this->subscription    = wcs_get_subscription( $this->subscription_id );
		$this->order_id        = BWFAN_Common::$events_async_data['order_id'];
		$this->order           = wc_get_order( $this->order_id );
		$this->email           = BWFAN_Common::$events_async_data['email'];

		$automation_data['wc_subscription_id'] = $this->subscription_id;
		$automation_data['order_id']           = $this->order_id;
		$automation_data['email']              = $this->email;

		return $automation_data;
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'subscription-contains',
				'label'       => __( 'Subscription Contains', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Any Product', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label' => __( 'Specific Products', 'wp-marketing-automations-pro' ),
						'value' => 'selected_product'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => false,
				"description" => ""
			],
			[
				'id'                  => 'products',
				'label'               => __( 'Select Products', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'wcs_products',
					'slug'      => 'wcs_products',
					'labelText' => 'WooCommerce Subscription products '
				],
				'class'               => '',
				'placeholder'         => '',
				'required'            => true,
				'toggler'             => [
					'fields' => [
						[
							'id'    => 'subscription-contains',
							'value' => 'selected_product',
						]
					]
				],
			],
		];
	}

	/** Set default setting  */
	public function get_default_values() {
		return [
			'subscription-contains' => 'any',
		];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	return 'BWFAN_WCS_Created';
}
