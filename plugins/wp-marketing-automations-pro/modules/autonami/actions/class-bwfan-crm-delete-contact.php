<?php

final class BWFAN_CRM_Delete_Contact extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Delete Contact', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action deletes the Contact', 'wp-marketing-automations-pro' );
		$this->action_priority = 50;
		$this->support_v2      = true;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data['contact_id'] = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$data['email']      = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';

		return $data;
	}

	public function process_v2() {
		$id_or_email = isset( $this->data['contact_id'] ) && absint( $this->data['contact_id'] ) > 0 ? $this->data['contact_id'] : $this->data['email'];
		$contact     = new BWFCRM_Contact( $id_or_email );

		if ( ! $contact->is_contact_exists() ) {
			return $this->success_message( __( 'Contact not found', 'wp-marketing-automations-pro' ) );
		}

		$status = BWFCRM_Model_Contact::delete_contact( $contact->get_id() );
		if ( ! $status ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Error occurred during contact deletion', 'wp-marketing-automations-pro' ) );
		}

		BWFAN_Common::$end_v2_current_contact_automation = true;

		return $this->success_message( __( 'Contact deleted', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				'id'       => 'title',
				'type'     => 'para',
				"children" => __( 'Note: This action will delete the contact and all related data, including engagements and conversions. If the contact is active in any automation, it will also be removed.', 'wp-marketing-automations-pro' ),
			],
		];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_CRM_Delete_Contact';
