<?php

final class BWFAN_Add_To_Automation extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Add To Automation', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'Add Contact into Automation', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'email' );
		$this->support_v2      = true;
		$this->action_priority = 20;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_view_data() {
		$automations = BWFAN_Core()->automations->get_active_v1_automation_names();
		if ( empty( $automations ) ) {
			return array();
		}

		$automations_to_return = array();
		foreach ( $automations as $automation ) {
			$automations_to_return[ $automation['ID'] ] = ( ! empty( $automation['meta_value'] ) ? $automation['meta_value'] : '' );
		}

		return $automations_to_return;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set                        = array();
		$data_to_set['selected_automation'] = isset( $step_data['selected_automation'] ) && ! empty( $step_data['selected_automation'] ) ? $step_data['selected_automation'] : [];
		$data_to_set['cid']                 = isset( $automation_data['global']['cid'] ) ? $automation_data['global']['cid'] : 0;
		$data_to_set['email']               = $automation_data['global']['email'];
		if ( ! is_email( $data_to_set['email'] ) && isset( $automation_data['global']['user_id'] ) ) {
			$data_to_set['email'] = ( get_user_by( 'ID', $automation_data['global']['user_id'] ) )->user_email;
		}

		if ( empty( $data_to_set['cid'] ) && is_email( $data_to_set['email'] ) ) {
			$contact            = bwf_get_contact( '', $data_to_set['email'] );
			$data_to_set['cid'] = $contact->get_id();
		}

		return $data_to_set;
	}

	public function process_v2() {
		$selected_automations = $this->data['selected_automation'] ?? [];
		$contact_id           = intval( $this->data['cid'] );

		if ( empty( $selected_automations ) || empty( $contact_id ) ) {
			return $this->skipped_response( __( 'No Automation selected in action.', 'wp-marketing-automations-pro' ) );
		}

		$automation_ids = array_column( $selected_automations, 'id' );
		$automation_id  = $automation_ids[0] ?? null;

		if ( $automation_id === null ) {
			return $this->skipped_response( __( 'No Automation ID found.', 'wp-marketing-automations-pro' ) );
		}

		$ins      = new BWFAN_Add_Contact_To_Automation_Controller( $automation_id, $contact_id );
		$response = $ins->add_contact_to_automation();
		if ( 200 !== $response['code'] ) {
			return $this->skipped_response( __( $response['message'], 'wp-marketing-automations-pro' ) );
		}

		return $this->success_message( $response['message'] );
	}

	public function get_fields_schema() {
		return [
			[
				'id'                  => 'selected_automation',
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'allowed_automations',
					'slug'      => 'allowed_automations',
					'labelText' => __( 'Automations', 'wp-marketing-automations-pro' ),
				],
				'label'               => __( 'Select Automation', 'wp-marketing-automations-pro' ),
				'placeholder'         => __( 'Select', 'wp-marketing-automations-pro' ),
				"tip"                 => __( "Any scheduled tasks for the selected automation will be removed for the user.", 'wp-marketing-automations-pro' ),
				"required"            => true,
				"allowFreeTextSearch" => false,
				"multiple"            => false,
			]
		];
	}


	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['selected_automation'] ) || empty( $data['selected_automation'] ) ) {
			return '';
		}
		$automations = [];
		foreach ( $data['selected_automation'] as $automation ) {
			if ( ! isset( $automation['name'] ) || empty( $automation['name'] ) ) {
				continue;
			}
			$automations[] = $automation['name'];
		}

		return $automations;
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_Add_To_Automation';
