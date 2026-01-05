<?php

final class BWFAN_WP_Custom_Callback extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Custom Callback', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action is used by developers to run the custom code through callback', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'callback_name' );

		$this->action_priority = 15;
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
            callback_name = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'callback_name')) ? data.actionSavedData.data.callback_name : '';
            #>
            <div class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?> bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Callback Name', 'wp-marketing-automations-pro' ); ?></label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0">
                    <input class="bwfan-input-wrapper" type="text" name="bwfan[{{data.action_id}}][data][callback_name]" id="bwfan_callback_name" value="{{callback_name}}"/>
                </div>
                <div class="bwfan_field_desc"><?php echo esc_html__( 'Enter the callback name', 'wp-marketing-automations-pro' ); ?></div>
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
		$callback                     = $task_meta['data']['callback_name'];
		$data_to_set                  = array();
		$data_to_set['callback_name'] = $callback;

		foreach ( $task_meta['global'] as $key1 => $value1 ) {
			$data_to_set[ $key1 ] = $value1;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$callback                     = $step_data['callback_name'];
		$data_to_set                  = array();
		$data_to_set['callback_name'] = $callback;

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
		$status = $this->process();

		/** Checking if required field error */
		if ( isset( $status['bwfan_response'] ) ) {
			return array(
				'status'  => 4,
				'message' => $status['bwfan_response'],
			);
		}

		return $status;
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

		$callback = $this->data['callback_name'];

		if ( empty( $callback ) ) {
			return array(
				'status'  => 4,
				'message' => __( 'No callback provided', 'wp-marketing-automations-pro' ),
			);
		}

		if ( false === has_action( $callback ) ) {
			return array(
				'status'  => 4,
				'message' => __( 'No callback found', 'wp-marketing-automations-pro' ),
			);
		}

		try {
			do_action( $callback, $this->data );
		} catch ( Error $e ) {
			$error_msg = $e->getMessage();

			return array(
				'status'  => '', // Will retry
				'message' => __( 'PHP Fatal error occurred', 'wp-marketing-automations-pro' ) . ( $error_msg ? ': ' . $error_msg : '' ),
			);
		}

		return array(
			'status'  => 3,
			'message' => __( 'Callback Executed', 'wp-marketing-automations-pro' ),
		);
	}

	public function process_v2() {
		$callback = $this->data['callback_name'];

		if ( empty( $callback ) ) {
			return $this->skipped_response( __( 'No callback provided', 'wp-marketing-automations-pro' ) );
		}

		if ( false === has_action( $callback ) ) {
			return $this->skipped_response( __( 'No callback found', 'wp-marketing-automations-pro' ) );
		}

		try {
			do_action( $callback, $this->data );
		} catch ( Error $e ) {
			$error_msg = $e->getMessage();

			return $this->error_response( $error_msg );
		}

		return $this->success_message( __( 'Callback Executed', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				'id'       => 'callback_name',
				'type'     => 'text',
				'label'    => __( 'Callback Name', 'wp-marketing-automations-pro' ),
				'tip'      => __( "Enter the callback name", 'wp-marketing-automations-pro' ),
				"required" => true,
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['callback_name'] ) || empty( $data['callback_name'] ) ) {
			return '';
		}

		return $data['callback_name'];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WP_Custom_Callback';
