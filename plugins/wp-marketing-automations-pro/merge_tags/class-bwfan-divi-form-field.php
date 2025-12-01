<?php

class BWFAN_Divi_Form_Field extends BWFAN_Merge_Tag {

	private static $instance = null;
	protected $support_v2 = true;
	protected $support_v1 = false;

	public function __construct() {
		$this->tag_name        = 'divi_form_field';
		$this->tag_description = __( 'Form Field', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_divi_form_field', array( $this, 'parse_shortcode' ) );
		add_action( 'wp_ajax_bwfan_get_automation_divi_form_fields', array( $this, 'bwfan_get_automation_divi_form_fields' ) );
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
	/**
	 * Show the html in popup for the merge tag.
	 */
	public function get_view() {
		$this->get_back_button();
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
		$get_data = BWFAN_Merge_Tag_Loader::get_data();

		if ( true === $get_data['is_preview'] ) {
			return $this->parse_shortcode_output( $this->get_dummy_preview(), $attr );
		}

		$entry       = $get_data['entry'];
		$field_value = '';

		if ( ! isset( $attr['field'] ) || ! isset( $entry[ $attr['field'] ] ) ) {
			return $this->parse_shortcode_output( $field_value, $attr );
		}

		$field_value = $entry[ $attr['field'] ];

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

	/**
	 * get the fields
	 * @return void
	 */
	public function bwfan_get_automation_divi_form_fields() {
		BWFAN_PRO_Common::nocache_headers();
		$finalarr     = [];
		$automationId = absint( sanitize_text_field( $_POST['automationId'] ) );

		/** Check Automation */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automationId );

		/** Check for automation exists */
		if ( empty( $automation_obj->error ) ) {
			$automation_meta = $automation_obj->get_automation_meta_data();

			if ( isset( $automation_meta['event_meta'] ) && isset( $automation_meta['event_meta']['bwfan-divi_form_submit_form_id'] ) ) {
				$form_id = sanitize_text_field( $automation_meta['event_meta']['bwfan-divi_form_submit_form_id'] );
				$fields  = [];
				if ( ! empty( $form_id ) && isset( BWFAN_Divi_Forms_Common::extract_forms_and_fields()[ $form_id ] ) ) {
					$fields = BWFAN_Divi_Forms_Common::extract_forms_and_fields()[ $form_id ]['fields'];
				}

				/** Handling form fields for the v2 automations */
				foreach ( $fields as $field ) {
					$finalarr[] = [
						'key'   => $field['field_id'],
						'value' => $field['field_title']
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
				"ajax_cb"     => 'bwfan_get_automation_divi_form_fields',
			]
		];
	}
}

/**
 * Register this merge tag to a group.
 */
if ( function_exists( 'bwfan_is_divi_forms_active' ) && bwfan_is_divi_forms_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'divi_forms', 'BWFAN_Divi_Form_Field', null, __( 'Divi Forms', 'wp-marketing-automations-pro' ) );
}
