<?php

/**
 * Merge tags register for breakdance form fields
 */
if ( ! class_exists( 'BWFAN_Tve_Architect_Form_Field' ) ) {
	class BWFAN_Tve_Architect_Form_Field extends BWFAN_Merge_Tag {

		private static $instance = null;

		public function __construct() {
			$this->tag_name        = 'thrive_architect_form_fields';
			$this->tag_description = __( 'Form Fields', 'wp-marketing-automations-pro' );
			add_shortcode( 'bwfan_thrive_architect_form_fields', array( $this, 'parse_shortcode' ) );
			add_action( 'wp_ajax_bwfan_get_automation_tve_architect_form_fields', array( $this, 'bwfan_get_automation_tve_architect_form_fields' ) );
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
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
			$form_fields = BWFAN_Merge_Tag_Loader::get_data( 'fields' ) ?? [];
			$field_value = isset( $form_fields[ $attr['field'] ] ) ? $form_fields[ $attr['field'] ] : '';
			// Handle array values (e.g., checkboxes)
			if ( is_array( $field_value ) ) {
				$field_value = implode( ', ', $field_value );
			}

			return $this->parse_shortcode_output( $field_value, $attr );
		}

		/**
		 * Show dummy value of the current merge tag.
		 *
		 * @return string
		 */
		public function get_dummy_preview() {
			return 'Test';
		}

		public function bwfan_get_automation_tve_architect_form_fields() {
			BWFAN_PRO_Common::nocache_headers();
			$final_arr    = [];
			$automationId = absint( sanitize_text_field( $_POST['automationId'] ) );

			/** Check Automation */
			$automation_obj = BWFAN_Automation_V2::get_instance( $automationId );
			/** Check for automation exists */
			if ( ! empty( $automation_obj->error ) ) {
				wp_send_json( array(
					'results' => $final_arr
				) );
			}
			$automation_meta = $automation_obj->get_automation_meta_data();
			if ( ! isset( $automation_meta['event_meta'] ) || ! isset( $automation_meta['event_meta']['bwfan-thrive_form_id'] ) ) {
				wp_send_json( array(
					'results' => $final_arr
				) );
			}

			$fields  = [];
			$form_id = sanitize_text_field( $automation_meta['event_meta']['bwfan-thrive_form_id'] );
			if ( ! empty( $form_id ) ) {
				$obj    = BWFAN_TVE_Architect_Form_Submit::get_instance();
				$fields = $obj->get_form_fields( $form_id );

			}
			$updated_fields = [];
			foreach ( $fields as $key => $field ) {
				$updated_fields[] = [
					'key'   => $field['key'],
					'value' => $field['value']
				];
			}

			wp_send_json( array(
				'results' => $updated_fields
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
					'placeholder' => __( 'Select', 'wp-marketing-automations' ),
					"description" => "",
					"ajax_cb"     => 'bwfan_get_automation_tve_architect_form_fields',
				]
			];
		}
	}

	/**
	 * Register this merge tag to a group.
	 */

	if ( function_exists( 'bwfan_is_tve_architect_active' ) && bwfan_is_tve_architect_active() ) {
		BWFAN_Merge_Tag_Loader::register( 'thrive_architect_forms', 'BWFAN_Tve_Architect_Form_Field', null, __( 'Thrive Architect', 'wp-marketing-automations-pro' ) );
	}
}
