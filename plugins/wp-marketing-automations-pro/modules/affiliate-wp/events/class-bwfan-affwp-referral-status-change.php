<?php
if ( ! class_exists( 'BWFAN_AFFWP_Referral_Status_Change' ) ) {
	#[AllowDynamicProperties]
	final class BWFAN_AFFWP_Referral_Status_Change extends BWFAN_Event {
		private static $instance = null;
		public $referral_id = null;
		public $affiliate_id = null;
		public $from_status = null;
		public $to_status = null;
		public $email = null;

		private function __construct() {
			$this->event_name             = __( 'Referral Status Changed', 'wp-marketing-automations-pro' );
			$this->event_desc             = __( 'This event runs after an referrals status is changed.', 'wp-marketing-automations-pro' );
			$this->event_merge_tag_groups = array( 'aff_affiliate', 'aff_report', 'bwf_contact' );
			$this->event_rule_groups      = array(
				'affiliatewp',
				'affiliate_report',
				'bwf_contact_segments',
				'bwf_contact',
				'bwf_contact_fields',
				'bwf_contact_user',
				'bwf_contact_wc',
				'bwf_contact_geo',
				'bwf_engagement',
				'bwf_broadcast'
			);
			$this->optgroup_label         = __( 'AffiliateWP', 'wp-marketing-automations-pro' );
			$this->priority               = 61;
			$this->is_time_independent    = true;
			$this->v2                     = true;
			$this->support_v1             = false;
		}

		public function load_hooks() {
			add_action( 'affwp_set_referral_status', array( $this, 'affwp_referral_status_changed' ), 11, 3 );
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function affwp_referral_status_changed( $referral_id, $to_status, $from_status ) {
			$this->referral_id = $referral_id;
			$this->from_status = $from_status;
			$this->to_status   = $to_status;
			$this->process( $this->referral_id, $to_status, $from_status );
		}

		/**
		 * Make the required data for the current event and send it asynchronously.
		 *
		 * @param $referral_id
		 * @param $from_status
		 * @param $to_status
		 */
		public function process( $referral_id, $to_status, $from_status ) {
			$referral = affwp_get_referral( $referral_id );
			if ( ! $referral ) {
				return;
			}
			$affiliate_id = $referral->affiliate_id;
			if ( empty( $referral->affiliate_id ) ) {
				return;
			}
			$data                 = $this->get_default_data();
			$data['affiliate_id'] = $affiliate_id;
			$data['referral_id']  = $referral_id;
			$data['from_status']  = $from_status;
			$data['to_status']    = $to_status;
			/** Run v2 automation */
			$this->send_async_call( $data );
		}

		public function capture_v2_data( $automation_data ) {
			$this->affiliate_id = BWFAN_Common::$events_async_data['affiliate_id'];
			$this->referral_id  = BWFAN_Common::$events_async_data['referral_id'];
			$this->referral_id  = BWFAN_Common::$events_async_data['referral_id'];

			$referral = affwp_get_referral( $this->referral_id );
			if ( ! $referral || empty( $referral->customer_id ) ) {
				return '';
			}

			$customer_data = BWFAN_PRO_Common::get_referral_data( $referral->customer_id );
			$email         = isset( $customer_data['email'] ) ? $customer_data['email'] : '';
			$this->email   = ! empty( $email ) ? $email : affwp_get_affiliate_email( $this->affiliate_id );

			$automation_data['affiliate_id']   = $this->affiliate_id;
			$automation_data['referral_count'] = $this->referral_id;
			$automation_data['email']          = $this->email;

			return $automation_data;
		}

		public function get_event_data() {
			$data_to_send                           = [ 'global' => [] ];
			$data_to_send['global']['affiliate_id'] = $this->affiliate_id;
			$data_to_send['global']['referral_id']  = $this->referral_id;
			$data_to_send['global']['email']        = $this->email;

			return $data_to_send;
		}

		/**
		 * Set global data for all the merge tags which are supported by this event.
		 *
		 * @param $task_meta
		 */
		public function set_merge_tags_data( $task_meta ) {
			$referral_id = BWFAN_Merge_Tag_Loader::get_data( 'referral_id' );
			if ( empty( $referral_id ) ) {
				$set_data = array(
					'referral_id'  => $task_meta['global']['referral_id'],
					'affiliate_id' => $task_meta['global']['affiliate_id'],
					'email'        => $task_meta['global']['email'],
				);
				BWFAN_Merge_Tag_Loader::set_data( $set_data );
			}
		}

		/**
		 * validate v2 event settings
		 * @return bool
		 */
		public function validate_v2_event_settings( $automation_data ) {
			if ( ! isset( $automation_data['event_meta'] ) || empty( $automation_data['event_meta'] ) || ! is_array( $automation_data['event_meta'] ) ) {
				return false;
			}
			$current_automation_event_meta = $automation_data['event_meta'];
			$current_status_to             = isset( $current_automation_event_meta['to'] ) ? $current_automation_event_meta['to'] : '';
			$current_status_from           = isset( $current_automation_event_meta['from'] ) ? $current_automation_event_meta['from'] : '';
			/** from any to any status checking */
			if ( ( 'affwp-any' === $current_status_from && 'affwp-any' === $current_status_to ) ) {
				return true;
			}
			/** checking any status to selected status */
			if ( 'affwp-any' === $current_status_from ) {
				return ( $automation_data['to_status'] === $current_status_to );
			}

			/** checking selected status to any status */
			if ( 'affwp-any' === $current_status_to ) {
				return ( $automation_data['from_status'] === $current_status_from );
			}

			/** checking selected status to selected status */
			return ( ( $automation_data['from_status'] === $current_status_from ) && ( $automation_data['to_status'] === $current_status_to ) );
		}

		/**
		 * v2 Method: Get fields schema
		 * @return array[][]
		 */
		public function get_fields_schema() {
			$default = [
				'affwp-any' => 'Any'
			];
			$status  = array_replace( $default, self::get_referral_status() );
			$status  = BWFAN_Common::prepared_field_options( $status );

			return [
				[
					'id'          => 'from',
					'type'        => 'wp_select',
					'options'     => $status,
					'placeholder' => __( 'Select Status', 'wp-marketing-automations-pro' ),
					'label'       => __( 'From Status', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-3-col-item',
					"required"    => true,
					"description" => ""
				],
				[
					'id'          => 'to',
					'type'        => 'wp_select',
					'options'     => $status,
					'label'       => __( 'To Status', 'wp-marketing-automations-pro' ),
					'placeholder' => __( 'Select Status', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper bwf-3-col-item',
					"required"    => true,
					"description" => ""
				],
			];
		}

		public static function get_referral_status() {
			return array(
				'pending'  => __( 'Pending', 'wp-marketing-automations-pro' ),
				'unpaid'   => __( 'Unpaid (Accepted)', 'wp-marketing-automations-pro' ),
				'paid'     => __( 'Paid', 'wp-marketing-automations-pro' ),
				'rejected' => __( 'Rejected', 'wp-marketing-automations-pro' )
			);
		}

		/** set default values */
		public function get_default_values() {
			return [
				'from' => 'affwp-any',
				'to'   => 'affwp-any',
			];
		}
	}

	/**
	 * Register this event to a source.
	 * This will show the current event in dropdown in single automation screen.
	 */
	if ( bwfan_is_affiliatewp_active() ) {
		return 'BWFAN_AFFWP_Referral_Status_Change';
	}
}
