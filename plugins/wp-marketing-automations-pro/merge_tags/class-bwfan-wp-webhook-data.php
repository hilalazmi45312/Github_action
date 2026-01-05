<?php

class BWFAN_WP_Webhook_Data extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wp_webhook_data';
		$this->tag_description = __( 'Webhook Data', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wp_webhook_data', array( $this, 'parse_shortcode' ) );
		add_action( 'wp_ajax_bwfan_get_automation_webhook_data', array( $this, 'bwfan_get_automation_webhook_data' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Show the html in popup for the merge tag.
	 */
	public function get_view() {
		$this->get_back_button();
		?>
        <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Select Field', 'wp-marketing-automations-pro' ); ?></label>
        <select id="" class="bwfan-input-wrapper bwfan-mb-15 bwfan_tag_select bwfan_wp_webhook_keys" style="padding-right: 5px;padding-left: 5px;" name="field"></select>
		<?php
		if ( $this->support_fallback ) {
			$this->get_fallback();
		}

		$this->get_preview();
		$this->get_copy_button();
	}

	/**
	 * Parse the merge tag and return its value.
	 *
	 * @param $attr
	 *
	 * @return mixed|string|void
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}
		$entry = BWFAN_Merge_Tag_Loader::get_data( 'webhook_data' );
		if ( isset( $entry[ $attr['field'] ] ) ) {
			return $this->parse_shortcode_output( $entry[ $attr['field'] ], $attr );
		}

		if ( false === strpos( $attr['field'], '.' ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$field_keys = explode( '.', $attr['field'] );
		foreach ( $field_keys as $val ) {
			if ( ! isset( $entry[ $val ] ) ) {
				continue;
			}
			$entry = $entry[ $val ];
		}

		return $this->parse_shortcode_output( $entry, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return 'Test';
	}

	public function bwfan_get_automation_webhook_data() {
		BWFAN_PRO_Common::nocache_headers();
		if ( ! isset( $_POST['automationId'] ) ) {
			wp_send_json( array(
				'fields' => [],
			) );
			exit;
		}

		$automation_id = absint( sanitize_text_field( $_POST['automationId'] ) );
		$meta          = BWFAN_Model_Automationmeta::get_meta( $automation_id, 'event_meta' );
		if ( ! isset( $meta['bwfan_unique_key'] ) || ! isset( $meta['webhook_data'] ) || empty( $meta['webhook_data'] ) ) {
			wp_send_json( array(
				'results' => []
			) );
			exit;
		}
		/** Get webhook data */
		$fields         = $meta['webhook_data'];
		$webhook_fields = $this->get_webhook_fields( $fields );

		/** Format webhook data */
		$final_arr = [];
		foreach ( $webhook_fields as $key => $value ) {
			$final_arr[] = [
				'key'   => $key,
				'value' => $key
			];
		}

		wp_send_json( array(
			'results' => $final_arr
		) );
		exit;
	}

	/**
	 * Format webhook fields to inner level
	 *
	 * @param $webhook_fields
	 * @param $fields
	 * @param $rootField
	 *
	 * @return array|mixed
	 */
	public function get_webhook_fields( $webhook_fields, $fields = array(), $rootField = '' ) {
		if ( empty( $webhook_fields ) || ! is_array( $webhook_fields ) ) {
			return [];
		}

		foreach ( $webhook_fields as $field_key => $data ) {
			$rootField_key = empty( $rootField ) ? $field_key : $rootField . '.' . $field_key;

			if ( is_array( $webhook_fields[ $field_key ] ) && ! empty( $webhook_fields[ $field_key ] ) ) {
				$fields = $this->get_webhook_fields( $webhook_fields[ $field_key ], $fields, $rootField_key );
				continue;
			}

			$fields[ $rootField_key ] = $field_key;
		}

		return $fields;
	}

	/**
	 * Returns merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		return [
			[
				'id'          => 'field',
				'type'        => 'ajax',
				'label'       => __( 'Select Field', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_automation_webhook_data',
			]
		];
	}
}

/**
 * Register this merge tag to a group.
 */
BWFAN_Merge_Tag_Loader::register( 'wp_webhook', 'BWFAN_WP_Webhook_Data', null, __( 'Webhook', 'wp-marketing-automations-pro' ) );

