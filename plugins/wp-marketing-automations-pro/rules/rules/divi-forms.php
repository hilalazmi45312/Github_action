<?php
if ( function_exists( 'bwfan_is_divi_forms_active' ) && bwfan_is_divi_forms_active() ) {

	class BWFAN_Rule_Divi_Form_Field extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;

			parent::__construct( 'divi_form_field' );
		}

		/**
		 * common function for v1 and v2 to define the rule operators
		 * @return array
		 */
		public function get_possible_rule_operators() {
			return array(
				'is'           => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not'       => __( 'is not', 'wp-marketing-automations-pro' ),
				'contains'     => __( 'contains', 'wp-marketing-automations-pro' ),
				'not_contains' => __( 'does not contain', 'wp-marketing-automations-pro' ),
				'starts_with'  => __( 'starts with', 'wp-marketing-automations-pro' ),
				'ends_with'    => __( 'ends with', 'wp-marketing-automations-pro' ),
				'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
				'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
			);
		}

		/** v2 Methods: START */

		/**
		 * @param $term
		 *
		 * getting the field options for the view
		 *
		 * @return array
		 */
		public function get_options( $term = '' ) {
			$meta = $this->event_automation_meta;

			$form_id = isset( $meta['bwfan-divi_form_submit_form_id'] ) ? $meta['bwfan-divi_form_submit_form_id'] : 0;
			if ( empty( $form_id ) ) {
				return array();
			}

			if ( ! isset( BWFAN_Divi_Forms_Common::extract_forms_and_fields()[ $form_id ] ) ) {
				return array();
			}

			/** @var BWFAN_Divi_Form_Submit $ins */
			$ins = BWFAN_Divi_Form_Submit::get_instance();

			return $ins->get_form_fields( $form_id );
		}

		public function get_rule_type() {
			return 'key-value';
		}

		/**
		 * @param $automation_data
		 * @param $rule_data
		 *
		 * processing the conditions for v2
		 *
		 * @return bool
		 */
		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$entry = isset( $automation_data['global']['entry'] ) ? $automation_data['global']['entry'] : [];

			$type        = $rule_data['rule'];
			$data        = $rule_data['data'];
			$key         = isset( $data[0] ) ? $data[0] : '';
			$saved_value = isset( $data[1] ) ? $data[1] : '';
			$value       = isset( $entry[ $key ] ) ? $entry[ $key ] : '';

			$value = BWFAN_Pro_Rules::make_value_as_array( $value );

			$value           = array_map( 'strtolower', array_map( 'trim', $value ) );
			$condition_value = strtolower( trim( $saved_value ) );

			/** checking if condition value contains comma */
			if ( strpos( $condition_value, ',' ) !== false ) {
				$condition_value = explode( ',', $condition_value );
				$condition_value = array_map( 'trim', $condition_value );
			}

			$result = BWFAN_PRO_Common::forms_fields_rules( $type, $condition_value, $value );

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

	}
}
