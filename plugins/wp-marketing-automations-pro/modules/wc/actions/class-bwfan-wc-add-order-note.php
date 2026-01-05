<?php

final class BWFAN_WC_Add_Order_Note extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Add Order Note', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action adds an order note to the WC order', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'order_id', 'body' );
		$this->included_events = array(
			'wc_new_order',
			'wc_order_note_added',
			'wc_order_status_change',
			'wc_product_purchased',
			'wc_product_refunded',
			'wc_product_stock_reduced',
			'wc_order_status_pending',
		);

		$this->action_priority = 25;
		$this->support_v2      = true;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            body = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'body')) ? data.actionSavedData.data.body : '';
            note_type = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'note_type')) ? data.actionSavedData.data.note_type : '';
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Order Note', 'wp-marketing-automations-pro' ); ?><?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?></label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0">
                    <textarea required class="bwfan-input-wrapper" rows="4" placeholder="<?php echo esc_html__( 'Order Note', 'wp-marketing-automations-pro' ); ?>" name="bwfan[{{data.action_id}}][data][body]">{{body}}</textarea>
                </div>
            </div>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Note Type', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper bwfan-single-select" name="bwfan[{{data.action_id}}][data][note_type]">
                    <option {{ note_type===
                    'private' ? 'selected' : '' }} value="private"><?php echo esc_html__( 'Private', 'wp-marketing-automations-pro' ); ?></option>
                    <option {{ note_type===
                    'public' ? 'selected' : '' }} value="public"><?php echo esc_html__( 'Note to Customer', 'wp-marketing-automations-pro' ); ?></option>
                </select>
            </div>
        </script>
		<?php
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $integration_object
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$this->set_data_for_merge_tags( $task_meta );
		$body                     = $task_meta['data']['body'];
		$note_type                = $task_meta['data']['note_type'];
		$body                     = BWFAN_Common::decode_merge_tags( $body );
		$data_to_set              = array();
		$data_to_set['body']      = $body;
		$data_to_set['note_type'] = $note_type;

		foreach ( $task_meta['global'] as $key1 => $value1 ) {
			$data_to_set[ $key1 ] = $value1;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set              = array();
		$data_to_set['body']      = BWFAN_Common::decode_merge_tags( $step_data['body'] );
		$data_to_set['note_type'] = $step_data['note_type'];

		$email                   = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';
		$data_to_set['order_id'] = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
		$user                    = is_email( $email ) ? get_user_by( 'email', $email ) : '';
		$data_to_set['user_id']  = $user instanceof WP_User ? $user->ID : 0;
		$data_to_set['email']    = $email;

		return $data_to_set;
	}

	/**
	 * Execute the current action.
	 * Return 3 for successful execution , 4 for permanent failure.
	 *
	 * @param $action_data
	 *
	 * @return array
	 */
	public function execute_action( $action_data ) {
		$this->set_data( $action_data['processed_data'] );
		$result = $this->process();

		if ( true === $result ) {
			return array(
				'status' => 3,
			);
		}

		return $result;
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}
		$order = BWFAN_Merge_Tag_Loader::get_data( 'wc_order' );

		if ( ! $order instanceof WC_Order ) {
			return array(
				'status'  => 4,
				'message' => __( 'Not a WooCommerce Order Object', 'wp-marketing-automations-pro' ),
			);
		}

		$note             = $this->data['body'];
		$is_customer_note = 'public' === $this->data['note_type'] ? true : false;

		if ( false === $is_customer_note ) {
			/** Prefix adding when private note */
			add_filter( 'woocommerce_new_order_note_data', array( $this, 'add_autonami_prefix_in_notes' ), 9999, 2 );
		}

		$order->add_order_note( $note, $is_customer_note );

		if ( false === $is_customer_note ) {
			remove_filter( 'woocommerce_new_order_note_data', array( $this, 'add_autonami_prefix_in_notes' ), 9999, 2 );
		}

		return true;
	}

	public function process_v2() {
		$order_id = absint( $this->data['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return $this->skipped_response( __( 'WooCommerce Order not found', 'wp-marketing-automations-pro' ) );
		}

		$note             = $this->data['body'];
		$is_customer_note = 'public' === $this->data['note_type'] ? true : false;

		if ( false === $is_customer_note ) {
			/** Prefix adding when private note */
			add_filter( 'woocommerce_new_order_note_data', array( $this, 'add_autonami_prefix_in_notes' ), 9999, 2 );
		}

		$order->add_order_note( $note, $is_customer_note );

		if ( false === $is_customer_note ) {
			remove_filter( 'woocommerce_new_order_note_data', array( $this, 'add_autonami_prefix_in_notes' ), 9999, 2 );
		}

		return $this->success_message( __( 'Order note added.', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * Append Autonami prefix in notes so that can be identified the note is added by Autonami
	 *
	 * @param $note
	 * @param $data
	 *
	 * @return mixed
	 */
	public function add_autonami_prefix_in_notes( $note, $data ) {
		if ( isset( $this->data['order_id'] ) && intval( $this->data['order_id'] ) === intval( $data['order_id'] ) ) {
			$note['comment_content'] = 'FKA: ' . $note['comment_content'];
		}

		return $note;
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'body',
				'type'        => 'textarea',
				'label'       => __( 'Order Note', 'wp-marketing-automations-pro' ),
				'placeholder' => __( 'Order Note', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			],
			[
				'id'          => 'note_type',
				'type'        => 'wp_select',
				'label'       => __( 'Note Type', 'wp-marketing-automations-pro' ),
				'options'     => [
					[
						'label' => __( 'Private', 'wp-marketing-automations-pro' ),
						'value' => 'private'
					],
					[
						'label' => __( 'Note to Customer', 'wp-marketing-automations-pro' ),
						'value' => 'public'
					],
				],
				'placeholder' => __( 'Choose Note Type', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			]
		];
	}

	/** set default values */
	public function get_default_values() {
		return [
			'note_type' => 'public',
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['body'] ) || empty( $data['body'] ) ) {
			return '';
		}

		return $data['body'];
	}
}

return 'BWFAN_WC_Add_Order_Note';
