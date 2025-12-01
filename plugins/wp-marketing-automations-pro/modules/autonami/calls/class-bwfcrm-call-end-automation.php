<?php

namespace BWFCRM\Calls\Autonami;

use BWFAN_Common;
use BWFAN_Model_Automations;
use BWFCRM\Calls\Base;

/**
 * End Automation call class
 */
class End_Automation extends Base {

	/**
	 * End Automation for contact
	 *
	 * @param $contact
	 * @param $data
	 *
	 * @return mixed
	 */
	public function process_call( $contact, $data ) {
		/**
		 * End Automation for contact
		 */

		if ( empty( $data ) ) {
			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => __( 'End Automation (Error): Invalid data', 'wp-marketing-automations-pro' ),
			);
		}
		$automations = array();

		$from    = isset( $data['from'] ) ? $data['from'] : '';
		$from_id = isset( $data['link_id'] ) ? $data['link_id'] : 0;
		if ( empty( $from_id ) && isset( $data['bulk_action_id'] ) ) {
			$from_id = isset( $data['bulk_action_id'] ) ? $data['bulk_action_id'] : 0;
			unset( $data['bulk_action_id'] );
		}
		if ( isset( $data['from'] ) ) {
			unset( $data['from'] );
		}
		if ( isset( $data['link_id'] ) ) {
			unset( $data['link_id'] );
		}

		/** Form automation array */
		foreach ( $data as $automation ) {
			if ( empty( $automation['id'] ) ) {
				continue;
			}
			/** Automation id */
			$automation_id = absint( $automation['id'] );

			/** Get Automation data */
			$automation_data = BWFAN_Model_Automations::get( $automation_id );

			/** if automation data not found */
			if ( empty( $automation_data ) ) {
				continue;
			}

			/** checking automation status before deleting tasks */
			if ( isset( $automation_data['status'] ) && 2 === absint( $automation_data['status'] ) ) {
				continue;
			}

			/** Set automation id */
			$automations[] = $automation_id;
		}

		/** Contact email */
		$email = $contact->contact->get_email();

		/** Contact phone */
		$phone = $contact->contact->get_phone();

		$tasks = [];

		/** Get task from email id */
		if ( ! empty( $email ) && is_email( $email ) ) {
			$email_tasks = BWFAN_Common::get_schedule_task_by_email( $automations, $email );
			foreach ( $email_tasks as $etask ) {
				if ( ! empty( $etask ) ) {
					$tasks = array_merge( $tasks, $etask );
				}
			}
		}

		/** Get task from phone no */
		if ( ! empty( $phone ) ) {
			$phone_tasks = BWFAN_Common::get_schedule_task_by_phone( $automations, $phone );
			foreach ( $phone_tasks as $ptask ) {
				if ( ! empty( $ptask ) ) {
					$tasks = array_merge( $tasks, $ptask );
				}
			}
		}

		/**Get Automation contact for v2 automation */
		$automation_contact = \BWFAN_Model_Automation_Contact::get_multiple_automation_contact( $automations, $contact->get_id() );

		/** If empty task skip action */
		if ( ( ! is_array( $tasks ) || 0 === count( $tasks ) ) && ( ! is_array( $automation_contact ) || 0 === count( $automation_contact ) ) ) {
			return array(
				'status'  => self::$RESPONSE_SKIPPED,
				'message' => __( 'End Automation (Skipped): No Tasks Found', 'wp-marketing-automations-pro' ),
			);
		}

		/** Form task id array  */
		$delete_tasks = array();
		foreach ( $tasks as $task ) {
			$delete_tasks[] = absint( $task['ID'] );
		}

		/** skip if delete task is empty */
		if ( 0 === count( $delete_tasks ) && 0 === count( $automation_contact ) ) {
			return array(
				'status'  => self::$RESPONSE_SKIPPED,
				'message' => __( 'End Automation (Skipped): No Tasks Found', 'wp-marketing-automations-pro' ),
			);
		}

		if ( count( $delete_tasks ) > 0 ) {
			/** Delete tasks */
			BWFAN_Core()->tasks->delete_tasks( $delete_tasks );
		}

		if ( count( $automation_contact ) > 0 ) {
			/** End v2 automations */
			foreach ( $automation_contact as $data ) {
				/** Add automation ended from */
				$type   = 'Bulk Action' === $from ? \BWFAN_Automation_Controller::$BULK_ACTION_END : \BWFAN_Automation_Controller::$LINK_TRIGGER_END;
				$reason = [
					'type' => $type,
					'data' => [
						'id' => $from_id,
					]
				];
				$data   = \BWFAN_PRO_Common::set_automation_ended_reason( $reason, $data );

				\BWFAN_Common::end_v2_automation( 0, $data );
				/** Update status as success for any step trail where status was waiting */
				\BWFAN_Model_Automation_Contact_Trail::update_all_step_trail_status_complete( $data['trail'] );
				\BWFAN_Common::update_automation_contact_fields( $data['cid'], $data['aid'] );
			}
		}

		return array(
			'status'  => self::$RESPONSE_SUCCESS,
			'message' => __( 'End Automation (Tasks Deleted): ', 'wp-marketing-automations-pro' ) . implode( ',', $delete_tasks ),
		);
	}
}

/**
 * Register call
 */
BWFCRM_Core()->calls->register_call( 'end_automation', 'BWFCRM\Calls\Autonami\End_Automation' );
