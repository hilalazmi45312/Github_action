<?php

if ( ! class_exists( 'BWFAN_Create_Offer_Recovery_Link' ) ) {
	final class BWFAN_Create_Offer_Recovery_Link extends BWFAN_Action {

		private static $ins = null;

		protected function __construct() {
			$this->action_name     = __( 'Create Offer Recovery Link', 'wp-marketing-automations-pro' );
			$this->action_desc     = __( 'This action creates a Stripe Offer recovery link', 'wp-marketing-automations-pro' );
			$this->required_fields = array( 'funnel_id', 'offer_id' );
			$this->action_priority = 5;
			$this->support_v2      = true;
			$this->support_v1      = false;
			$this->analytics_mode  = 'upsell-recovery';
		}

		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 *  Make all the data which is required by the current action.
		 * This data will be used while executing the task of this action.
		 *
		 * @param $automation_data
		 * @param $step_data
		 *
		 * @return array|void
		 */
		public function make_v2_data( $automation_data, $step_data ) {
			$data_to_set                   = [];
			$data_to_set['email']          = $automation_data['global']['email'];
			$data_to_set['saved_offer_id'] = isset( $step_data['offer'][0]['id'] ) ? $step_data['offer'][0]['id'] : 0;
			$contact_id                    = isset( $automation_data['global']['cid'] ) ? $automation_data['global']['cid'] : 0;
			$data_to_set['contact_id']     = empty( $contact_id ) && isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : $contact_id;
			$data_to_set['order_id']       = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
			$data_to_set['step_id']        = isset( $automation_data['step_id'] ) ? $automation_data['step_id'] : 0;
			$data_to_set['expiry']         = isset( $step_data['expiry'] ) ? $step_data['expiry'] : 0;

			return $data_to_set;
		}

		public function process_v2() {
			$data      = $this->data;
			$hash_code = md5( time() . $data['contact_id'] );

			$link_data = [
				'cid'         => $data['contact_id'],
				'hash_code'   => $hash_code,
				'oid'         => $data['saved_offer_id'],
				'fid'         => WFOCU_Core()->offers->get_parent_funnel( $data['saved_offer_id'] ), // upsell id
				'created_at'  => current_time( 'mysql' ),
				'expiry_days' => $data['expiry'],
				'updated_at'  => current_time( 'mysql' ),
				'sid'         => $data['step_id'],
				'clicked'     => 0,
			];

			$link_obj      = new BWFAN_Generate_Offer_Link( 0, '', $link_data );
			$offer_link_id = $link_obj->save();
			/** update new coupon value in automation contact row */
			$row_data = $this->get_automation_contact_row( $data['automation_contact_id'] );
			if ( ! empty( $row_data ) ) {
				$offer_links                     = isset( $row_data['offer_links'] ) && is_array( $row_data['offer_links'] ) ? $row_data['offer_links'] : [];
				$offer_links[ $data['step_id'] ] = $offer_link_id;
				$row_data['offer_links']         = $offer_links;
				BWFAN_Model_Automation_Contact::update( array(
					'data' => wp_json_encode( $row_data )
				), array(
					'ID' => $data['automation_contact_id'],
				) );
			}

			/** using special property to end the current automation process for a contact */
			BWFAN_Common::$end_v2_current_contact_automation = true;

			return $this->success_message( __( 'Offer recovery link created', 'wp-marketing-automations-pro' ) );
		}

		/**
		 * Get automation contact row data only
		 *
		 * @param $automation_contact_id
		 *
		 * @return mixed|void|null
		 */
		public function get_automation_contact_row( $automation_contact_id ) {
			if ( empty( $automation_contact_id ) ) {
				return;
			}

			$automation_contact_data = BWFAN_Model_Automation_Contact::get_data( $automation_contact_id );
			if ( empty( $automation_contact_data ) ) {
				return;
			}

			return json_decode( $automation_contact_data['data'], true );
		}

		public function get_fields_schema() {
			$filter_driven_settings = [];
			if ( true === apply_filters( 'bwfan_upsell_merge_order_setting', false ) ) {
				$filter_driven_settings = [
					[
						'id'          => 'order-settings',
						'label'       => __( 'Order Settings', 'wp-marketing-automations-pro' ),
						'type'        => 'radio',
						'options'     => [
							[
								'label' => __( 'Create a new Order', 'wp-marketing-automations-pro' ),
								'value' => 'new'
							],
							[
								'label' => __( 'Merged with the main order', 'wp-marketing-automations-pro' ),
								'value' => 'merge'
							],
						],
						"class"       => 'bwfan-input-wrapper',
						"tip"         => "",
						"required"    => true,
						"description" => ""
					],
					[
						'id'          => 'merge_hrs',
						'type'        => 'number',
						'value'       => "5",
						'label'       => __( 'Merge order if parent order was purchased following hrs', 'wp-marketing-automations-pro' ),
						"description" => "",
						'toggler'     => [
							'fields' => [
								[
									'id'    => 'order-settings',
									'value' => 'merge',
								]
							]
						],
					]
				];
			}

			$settings = [
				[
					'id'          => 'title',
					'type'        => 'text',
					'label'       => __( 'Stripe Offer Title', 'wp-marketing-automations-pro' ),
					"description" => "",
					"tip"         => __( 'Stripe Offer title is for internal usage, visible only to admins.', 'wp-marketing-automations-pro' ),
					"required"    => true,
				],
				[
					'id'             => 'mergetag',
					'type'           => 'dynamic_merge_tag',
					'innerText'      => __( 'This offer link can be used in emails or other actions using merge tag :', 'wp-marketing-automations-pro' ),
					'merge_data'     => [
						'sid' => 'stepid',
					],
					'merge_tag_slug' => 'stripe_offer_recovery_link',
					'toggler'        => [
						'fields'   => array(
							[
								'id'    => 'title',
								'value' => '',
							],
						),
						'relation' => 'AND',
					]
				],
				[
					"id"                  => 'offer',
					"label"               => __( 'Select Offer', 'wp-marketing-automations-pro' ),
					"type"                => 'custom_search',
					'autocompleterOption' => [
						'path'      => 'fk_fetch_offers',
						'slug'      => 'fk_fetch_offers',
						'labelText' => 'Offer'
					],
					"allowFreeTextSearch" => false,
					"required"            => true,
					"multiple"            => false,
				],
				[
					'id'       => 'expiry',
					'type'     => 'number',
					'label'    => __( 'Offer Link Expiry in Days', 'wp-marketing-automations-pro' ),
					"hint"     => __( 'Default expiry is 10 days', 'wp-marketing-automations-pro' ),
					"required" => true,
				],
			];

			return array_merge( $settings, $filter_driven_settings );
		}

		/**
		 * Default values for goal values
		 *
		 * @return array
		 */
		public function get_default_values() {
			return [
				'order-settings' => 'new',
				'expiry'         => '10',
			];
		}

		public function get_desc_text( $data ) {
			$data = json_decode( wp_json_encode( $data ), true );
			if ( ! isset( $data['coupon_data'] ) || empty( $data['coupon_data'] ) || ! isset( $data['coupon_data']['general'] ) ) {
				return '';
			}
			$text = isset( $data['coupon_data']['general']['coupon_prefix'] ) ? $data['coupon_data']['general']['coupon_prefix'] : '';

			return $text . '{random_6_digits}';
		}
	}

	/**
	 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
	 */
	return 'BWFAN_Create_Offer_Recovery_Link';
}