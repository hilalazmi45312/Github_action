<?php

if ( bwfan_is_elementorpro_active() ) {

	class BWFAN_Rule_Elementor_Form_Field extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'elementor_form_field' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			$meta    = $this->event_automation_meta;
			$page_id = isset( $meta['elementor_popup_submit_page_id'] ) ? $meta['elementor_popup_submit_page_id'] : 0;
			$form_id = isset( $meta['elementor_submit_form_id'] ) ? $meta['elementor_submit_form_id'] : 0;
			$form_id = empty( $form_id ) && isset( $meta['elementor_popup_submit_form_id'] ) ? $meta['elementor_popup_submit_form_id'] : $form_id;

			/** @var BWFAN_Elementor_Form_Submit $ins */
			$ins    = BWFAN_Elementor_Form_Submit::get_instance();
			$fields = $ins->get_form_fields( $form_id, $page_id );

			if ( empty( $fields ) ) {
				return [];
			}

			$formatted_fields = [];
			foreach ( $fields[ $form_id ] as $field ) {
				$formatted_fields[ $field['field_slug'] ] = $field['field_label'];
			}

			return $formatted_fields;
		}

		public function get_rule_type() {
			return 'key-value';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$entry       = isset( $automation_data['global']['entry'] ) ? $automation_data['global']['entry'] : [];
			$form_id     = isset( $automation_data['global']['form_id'] ) ? $automation_data['global']['form_id'] : '';
			$type        = $rule_data['rule'];
			$data        = $rule_data['data'];
			$key         = isset( $data[0] ) ? $data[0] : '';
			$saved_value = isset( $data[1] ) ? $data[1] : '';
			$value       = isset( $entry[ $key ] ) ? $entry[ $key ] : '';

			/**
			 * handling for popup form submit
			 */
			if ( 'elementor_popup_form_submit' === $automation_data['event_data']['event_slug'] ) {
				$field_slugs = empty( $automation_data['global']['fields'][ $form_id ] ) ? [] : array_column( $automation_data['global']['fields'][ $form_id ], 'field_slug' );
				$key_index   = array_search( $key, $field_slugs );
				$value       = ( false !== $key_index && isset( $entry[ $key ] ) ) ? $entry[ $key ] : $value;
			}

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

		public function get_condition_input_type() {
			return 'Text';
		}

		public function conditions_view() {
			$values     = $this->get_possible_rule_values();
			$value_args = array(
				'input'       => 'select',
				'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition][key]',
				'choices'     => $values,
				'class'       => 'bwfan_field_one_half bwfan_elementor_form_fields',
				'placeholder' => __( 'Field', 'wp-marketing-automations-pro' ),
			);

			bwfan_Input_Builder::create_input_field( $value_args );

			$condition_input_type = $this->get_condition_input_type();
			$values               = $this->get_possible_rule_values();
			$value_args           = array(
				'input'       => $condition_input_type,
				'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition][value]',
				'choices'     => $values,
				'class'       => 'bwfan_field_one_half',
				'placeholder' => __( 'Value', 'wp-marketing-automations-pro' ),
			);

			bwfan_Input_Builder::create_input_field( $value_args );
		}

		public function is_match( $rule_data ) {
			$entry = BWFAN_Core()->rules->getRulesData( 'entry' );
			$type  = $rule_data['operator'];
			$value = isset( $entry[ $rule_data['condition']['key'] ] ) ? $entry[ $rule_data['condition']['key'] ] : '';

			$value = BWFAN_Pro_Rules::make_value_as_array( $value );

			$value           = array_map( 'strtolower', $value );
			$condition_value = strtolower( trim( $rule_data['condition']['value'] ) );

			/** checking if condition value contains comma */
			if ( strpos( $condition_value, ',' ) !== false ) {
				$condition_value = explode( ',', $condition_value );
				$condition_value = array_map( 'trim', $condition_value );
			}

			switch ( $type ) {
				case 'is':
					if ( is_array( $condition_value ) && is_array( $value ) ) {
						$result = count( array_intersect( $condition_value, $value ) ) > 0;
					} else {
						$result = in_array( $condition_value, $value );
					}
					break;
				case 'is_not':
					if ( is_array( $condition_value ) && is_array( $value ) ) {
						$result = count( array_intersect( $condition_value, $value ) ) === 0;
					} else {
						$result = ! in_array( $condition_value, $value );
					}
					break;
				case 'contains':
					$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
					$result = strpos( $value, $condition_value ) !== false;
					break;
				case 'not_contains':
					$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
					$result = strpos( $value, $condition_value ) === false;
					break;
				case 'starts_with':
					$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
					$length = strlen( $condition_value );
					$result = substr( $value, 0, $length ) === $condition_value;
					break;
				case 'ends_with':
					$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
					$length = strlen( $condition_value );

					if ( 0 === $length ) {
						$result = true;
					} else {
						$result = substr( $value, - $length ) === $condition_value;
					}
					break;
				case 'is_blank':
					$result = empty( $value[0] );
					break;
				case 'is_not_blank':
					$result = ! empty( $value[0] );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			?>
            Form Field
            <% var event = (_.has(BWFAN_Auto, 'uiDataDetail') && _.has(BWFAN_Auto.uiDataDetail, 'trigger') && _.has(BWFAN_Auto.uiDataDetail.trigger, 'event')) ? BWFAN_Auto.uiDataDetail.trigger.event : ''; %>
            '<%= bwfan_events_js_data[event]["selected_form_fields"][condition['key']] %>' <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
            <%= ops[operator] %> '<%= condition['value'] %>'
			<?php
		}
	}
}
