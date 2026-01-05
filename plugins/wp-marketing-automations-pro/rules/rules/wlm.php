<?php
if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {

	class BWFAN_Rule_WLM_Form_Field extends BWFAN_Rule_Base {

		public function __construct() {
			parent::__construct( 'wlm_form_field' );
		}

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			$meta    = $this->event_automation_meta;
			$form_id = isset( $meta['bwfan-wlm_form_submit_form_id'] ) ? $meta['bwfan-wlm_form_submit_form_id'] : 0;
			if ( empty( $form_id ) ) {
				return array();
			}
			/** @var BWFAN_WLM_User_Registration $ins */
			$ins = BWFAN_WLM_User_Registration::get_instance();

			return $ins->get_form_fields( $form_id );
		}

		public function get_rule_type() {
			return 'key-value';
		}


		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result = false;
			$entry  = isset( $automation_data['global']['entry'] ) ? $automation_data['global']['entry'] : [];
			$data   = $rule_data['data'];

			if ( ! is_array( $data ) && empty( $data ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$key         = isset( $data[0] ) ? $data[0] : '';
			$saved_value = isset( $data[1] ) ? $data[1] : '';
			$value       = isset( $entry[ $key ] ) ? $entry[ $key ] : '';
			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = ( strtolower( $value ) === strtolower( $saved_value ) );
					break;
				case 'is_not':
					$result = ( strtolower( $value ) !== strtolower( $saved_value ) );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function conditions_view() {
			$values     = $this->get_possible_rule_values();
			$value_args = array(
				'input'       => 'select',
				'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition][key]',
				'choices'     => $values,
				'class'       => 'bwfan_field_one_half bwfan_wlm_form_fields',
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
			switch ( $type ) {
				case 'is':
					$result = ( strtolower( $value ) === strtolower( $rule_data['condition']['value'] ) );
					break;
				case 'is_not':
					$result = ( strtolower( $value ) !== strtolower( $rule_data['condition']['value'] ) );
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
            '<%= bwfan_events_js_data["wlm_user_registration"]["selected_form_fields"][condition['key']] %>' <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
            <%= ops[operator] %> '<%= condition['value'] %>'
			<?php
		}
	}

	class BWFAN_Rule_WLM_Level extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'wlm_level' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$level_id = isset( $automation_data['global']['level_id'] ) ? $automation_data['global']['level_id'] : 0;

			$result = false;


			/** for wishlist member events to check for membership level */

			$in = false;

			$rule_data['condition'] = array_map( 'intval', $rule_data['condition'] );
			$selected_level         = array_map( function ( $level ) {
				return absint( $level['key'] );
			}, $rule_data['data'] );

			if ( in_array( absint( $level_id ), $selected_level, true ) ) {
				$in = true;
			}

			$result = 'in' === $rule_data['rule'] ? $in : ! $in;


			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'in'    => __( 'is', 'wp-marketing-automations-pro' ),
				'notin' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_possible_rule_values() {
			$result  = array();
			$levels  = wlmapi_get_levels();
			$options = array();
			if ( ! empty( $levels['levels']['level'] ) ) {
				foreach ( $levels['levels']['level'] as $level ) {
					$options[ $level['id'] ] = $level['name'];
				}
			}

			return $options;
		}

		public function get_condition_input_type() {
			return 'Chosen_Select';
		}

		/**
		 * @param $rule_data
		 *
		 * @return bool
		 */
		public function is_match( $rule_data ) {
			$result = false;

			if ( isset( $rule_data['condition'] ) && isset( $rule_data['operator'] ) ) {

				/** for wishlist member events to check for membership level */
				$level_id = BWFAN_Core()->rules->getRulesData( 'level_id' );

				$in = false;

				$rule_data['condition'] = array_map( 'intval', $rule_data['condition'] );

				if ( in_array( intval( $level_id ), $rule_data['condition'], true ) ) {
					$in = true;
				}

				$result = 'in' === $rule_data['operator'] ? $in : ! $in;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			echo esc_html__( 'Membership Level ', 'wp-marketing-automations-pro' );
			?>
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
            <%= ops[operator] %> <% var chosen = []; %>
            <% _.each(condition, function( value, key ){ %>
            <% chosen.push(uiData[value]); %>
            <% }); %>
            <%= chosen.join("/") %>
			<?php
		}
	}
}
