<?php

class BWFAN_TVE_Form_Field extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'thrive_form_field';
		$this->tag_description = __( 'Form Field', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_thrive_form_field', array( $this, 'parse_shortcode' ) );
		add_action( 'wp_ajax_bwfan_get_automation_tve_form_fields', array( $this, 'bwfan_get_automation_tve_form_fields' ) );
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
        <select id="" class="bwfan-input-wrapper bwfan-mb-15 bwfan_tag_select bwfan_tve_form_fields" style="padding-left:10px;" name="field"></select>
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

		$entry       = BWFAN_Merge_Tag_Loader::get_data( 'entry' );
		$field_value = '';
		if ( isset( $attr['field'] ) && isset( $entry[ $attr['field'] ] ) ) {
			$field_value = $entry[ $attr['field'] ];
		}

		return $this->parse_shortcode_output( $field_value, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Test';
	}

	public function bwfan_get_automation_tve_form_fields() {
		BWFAN_PRO_Common::nocache_headers();
		$finalarr     = [];
		$automationId = absint( sanitize_text_field( $_POST['automationId'] ) );

		/** Check Automation */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automationId );

		/** Check for automation exists */
		if ( empty( $automation_obj->error ) ) {
			$automation_meta = $automation_obj->get_automation_meta_data();

			if ( isset( $automation_meta['event_meta'] ) && isset( $automation_meta['event_meta']['bwfan-tve_form_submit_form_id'] ) ) {
				$form_id = sanitize_text_field( $automation_meta['event_meta']['bwfan-tve_form_submit_form_id'] );
				$obj     = BWFAN_TVE_Lead_Form_Submit::get_instance();
				$fields  = $obj->get_form_fields( $form_id );
				if ( empty( $fields ) ) {
					wp_send_json( array(
						'results' => array(),
					) );
				}

				/** Handling form fields for the v2 automations */
				foreach ( $fields as $key => $value ) {
					$finalarr[] = [
						'key'   => $key,
						'value' => $value
					];
				}
			}
		}

		wp_send_json( array(
			'results' => $finalarr
		) );
		exit;
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
				"ajax_cb"     => 'bwfan_get_automation_tve_form_fields',
			]
		];
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_tve_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'thrive-forms', 'BWFAN_TVE_Form_Field' );
}
