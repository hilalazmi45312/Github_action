<?php

final class BWFAN_AFFWP_Change_Affiliate_Status extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Change Affiliate Status', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action changes the affiliate\'s status', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'affiliate_id', 'affiliate_status' );

		$this->support_v1 = false;
		$this->support_v2 = true;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set = array();

		$data_to_set['affiliate_status'] = $step_data['affiliate_status'];
		$data_to_set['affiliate_id']     = isset( $automation_data['global']['affiliate_id'] ) ? $automation_data['global']['affiliate_id'] : 0;
		$data_to_set['user_id']          = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		$data_to_set['email']            = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		if ( empty( $data_to_set['affiliate_id'] ) && intval( $data_to_set['user_id'] ) > 0 ) {
			$data_to_set['affiliate_id'] = affwp_get_affiliate_id( $data_to_set['user_id'] );
		}
		if ( empty( $data_to_set['affiliate_id'] ) && ! empty( $data_to_set['email'] ) ) {
			/** @var WP_User $user */
			$user = get_user_by( 'email', $data_to_set['email'] );
			if ( $user instanceof WP_User ) {
				$data_to_set['user_id']      = $user->ID;
				$data_to_set['affiliate_id'] = affwp_get_affiliate_id( $user->ID );
			}
		}

		return $data_to_set;
	}

	public function process_v2() {
		/** Perform Promotional checking */
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->skipped_response( __( 'Required fields missing.' ) );
		}

		if ( empty( $this->data['affiliate_id'] ) ) {
			return $this->skipped_response( __( 'Affiliate not found.' ) );
		}
		$affiliate = affwp_get_affiliate( $this->data['affiliate_id'] );
		if ( empty( $affiliate ) ) {
			return $this->skipped_response( __( 'Affiliate not found.' ) );
		}

		affiliate_wp()->affiliates->update( $affiliate->ID, array(
			'status' => $this->data['affiliate_status'],
		), '', 'affiliate' );

		return $this->success_message( __( 'Affiliate rate changed.' ) );
	}

	public function get_fields_schema() {
		$status = BWFAN_PRO_Common::prepared_field_options( $this->get_view_data() );

		return [
			[
				'id'          => 'affiliate_status',
				'type'        => 'wp_select',
				'label'       => __( 'Affiliate Status', 'wp-marketing-automations-pro' ),
				'options'     => $status,
				'placeholder' => __( 'Select Status', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			],
		];
	}

	public function get_view_data() {
		return [
			'active'   => 'Active',
			'inactive' => 'Inactive',
			'pending'  => 'Pending',
		];
	}

	/** set default values */
	public function get_default_values() {
		return [
			'affiliate_status' => 'active',
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['affiliate_status'] ) || empty( $data['affiliate_status'] ) ) {
			return '';
		}
		$status = $this->get_view_data();

		return $status[ $data['affiliate_status'] ];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select action dropdown.
 */
return 'BWFAN_AFFWP_Change_Affiliate_Status';
