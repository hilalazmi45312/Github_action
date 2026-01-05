<?php

final class BWFAN_WP_Debug extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Debug', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action is used by the developers for debugging of action\'s data', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'body' );

		$this->action_priority = 20;
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
            #>
            <div class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?>">
                <label for="" class="bwfan-label-title">
					<?php echo esc_html__( 'Message', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                </label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                    <textarea required class="bwfan-input-wrapper" rows="4" placeholder="<?php echo esc_html__( 'Message', 'wp-marketing-automations-pro' ); ?>" name="bwfan[{{data.action_id}}][data][body]">{{body}}</textarea>
                </div>
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
		$body                = $task_meta['data']['body'];
		$body                = BWFAN_Common::decode_merge_tags( $body );
		$data_to_set         = array();
		$data_to_set['body'] = $body;

		foreach ( $task_meta['global'] as $key1 => $value1 ) {
			$data_to_set[ $key1 ] = $value1;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$body                = BWFAN_Common::decode_merge_tags( $step_data['body'] );
		$data_to_set         = array();
		$data_to_set['body'] = $body;

		foreach ( $automation_data['global'] as $key1 => $value1 ) {
			$data_to_set[ $key1 ] = $value1;
		}

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

		if ( $result ) {
			return array(
				'status' => 3,
			);
		}

		return array(
			'status'  => 4,
			'message' => __( 'Something went wrong', 'wp-marketing-automations-pro' ),
		);
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

		BWFAN_Common::log_test_data( $this->data['body'], 'debug-action', true );

		return 1;
	}

	public function process_v2() {
		BWFAN_Common::log_test_data( $this->data['body'], 'debug-action', true );

		return $this->success_message( __( 'Message logged', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'body',
				'type'        => 'textarea',
				'label'       => __( 'Message', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				'placeholder' => __( "Message", 'wp-marketing-automations-pro' ),
				'hint'        => __( 'Logs will be saved to FunnelKit Automations > Settings > Logs. Look for file with debug-action name followed by a date.', 'wp-marketing-automations-pro' ),
				"description" => "",
				"required"    => true,
			]
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

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WP_Debug';
