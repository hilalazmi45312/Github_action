<?php

final class BWFAN_WC_Store_Credit_Decrease extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Decrease Store Credit', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action decreases store credit for user', 'wp-marketing-automations-pro' );
		$this->action_priority = 5;
		$this->support_v2      = true;
		$this->support_v1      = false;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set = array();

		$data_to_set['amount'] = BWFAN_Common::decode_merge_tags( $step_data['amount'] );

		$email = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';
		$user  = is_email( $email ) ? get_user_by( 'email', $email ) : '';

		$data_to_set['user_id'] = $user instanceof WP_User ? $user->ID : 0;
		$data_to_set['email']   = $email;

		return $data_to_set;
	}

	public function process_v2() {
		$credits_updated = $this->update_user_balance( $this->data );
		if ( true === $credits_updated ) {
			return $this->success_message( __( 'Store credits decreased.', 'wp-marketing-automations-pro' ) );
		}

		if ( is_wp_error( $credits_updated ) ) {
			$this->error_response( $credits_updated->get_error_message() );
		}

		return $this->error_response( __( 'Some error occurred', 'wp-marketing-automations-pro' ) );
	}

	public function update_user_balance( $data ) {
		if ( ! class_exists( 'ACFWF\Models\Objects\Store_Credit_Entry' ) ) {
			return false;
		}
		if ( ! isset( $data['user_id'] ) || 0 === intval( $data['user_id'] ) ) {
			return false;
		}
		if ( ! isset( $data['amount'] ) || 0 === floatval( $data['amount'] ) ) {
			return false;
		}

		$store_credit_entry = new ACFWF\Models\Objects\Store_Credit_Entry();

		$params = [
			'amount'  => floatval( $data['amount'] ),
			'user_id' => $data['user_id'],
			'type'    => 'decrease',
			'action'  => 'admin_decrease',

		];
		foreach ( $params as $prop => $value ) {
			$store_credit_entry->set_prop( $prop, $value );
		}

		$check = $store_credit_entry->save();
		if ( $check ) {
			return true;
		}

		return $check;
	}

	public function get_fields_schema() {
		return [
			[
				"id"       => 'amount',
				"label"    => __( 'Credit Amount', 'wp-marketing-automations-pro' ),
				"type"     => 'text',
				"class"    => 'bwfan-input-wrapper',
				"required" => true,
			],
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['amount'] ) || empty( $data['amount'] ) ) {
			return '';
		}

		return $data['amount'];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WC_Store_Credit_Decrease';
