<?php

if ( ! class_exists( 'BWFAN_Stripe_Offer_Recovery_Link' ) ) {
	class BWFAN_Stripe_Offer_Recovery_Link extends BWFAN_Merge_Tag {

		private static $instance = null;
		protected $support_v2 = true;
		protected $support_v1 = false;


		public function __construct() {
			$this->tag_name        = 'stripe_offer_recovery_link';
			$this->tag_description = __( 'Offer Recovery Link', 'wp-marketing-automations-pro' );
			add_shortcode( 'bwfan_stripe_offer_recovery_link', array( $this, 'parse_shortcode' ) );

			/** for getting the automation coupon step ids and their titles */
			add_action( 'wp_ajax_bwfan_get_fka_upsell_recovery_options', array( $this, 'bwfan_get_fka_upsell_recovery_options' ) );
			$this->priority = 25;
		}

		/**
		 * Parse the merge tag and return its value.
		 *
		 * @param $attr
		 *
		 * @return mixed|void
		 */
		public function parse_shortcode( $attr ) {
			if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
				return $this->parse_shortcode_output( $this->get_dummy_preview(), true );
			}

			$a_cid         = BWFAN_Merge_Tag_Loader::get_data( 'automation_cid' );
			$automation_id = BWFAN_Merge_Tag_Loader::get_data( 'automation_id' );
			if ( empty( $a_cid ) ) {
				/** when automation cid is not present i.e. old lite case */
				$contact_id = BWFAN_Merge_Tag_Loader::get_data( 'cid' );
				$contact_id = empty( $contact_id ) ? BWFAN_Merge_Tag_Loader::get_data( 'contact_id' ) : $contact_id;

				$automation_contact_data = BWFAN_Model_Automation_Contact::get_automation_contact( $automation_id, $contact_id );
			} else {
				$automation_contact_data = BWFAN_Model_Automation_Contact::get_data( $a_cid );
			}

			if ( ! isset( $automation_contact_data['data'] ) || empty( $automation_contact_data['data'] ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			/** check for offer links */
			$offer_link_data = json_decode( $automation_contact_data['data'], true );
			if ( ! array_key_exists( 'offer_links', $offer_link_data ) || ! isset( $offer_link_data['offer_links'] ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			$step_id = $attr['sid'] ?? 0;
			if ( empty( $step_id ) ) {
				$step_id = array_key_first( $offer_link_data['offer_links'] );
			}

			/** check for step id */
			if ( ! isset( $offer_link_data['offer_links'][ $step_id ] ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			$order_id      = BWFAN_Merge_Tag_Loader::get_data( 'order_id' );
			$offer_link_id = $offer_link_data['offer_links'][ $step_id ];

			$link_data = BWFAN_Model_Stripe_Offer::get( $offer_link_id );
			$hash_code = $link_data['hash_code'] ?? '';
			$url       = home_url();
			if ( empty( $hash_code ) ) {
				$this->parse_shortcode_output( $url, $attr );
			}

			$link = add_query_arg( array(
				'bwfan-offer-code' => $hash_code,
				'automation_id'    => $automation_id,
				'order_id'         => $order_id
			), $url );

			return $this->parse_shortcode_output( $link, $attr );
		}

		/**
		 * Show dummy value of the current merge tag.
		 *
		 * @return string
		 */
		public function get_dummy_preview() {
			return '';
		}

		/**
		 * will return the title of generate upsell recovery link in single automation
		 *
		 * @return void
		 */
		public function bwfan_get_fka_upsell_recovery_options() {
			$finalarr     = [];
			$automationId = absint( sanitize_text_field( $_POST['automationId'] ) );
			$merge_tag    = isset( $_POST['merge_tag'] ) ? sanitize_text_field( $_POST['merge_tag'] ) : '';

			/** check for automation id */
			if ( empty( $automationId ) ) {
				wp_send_json( array(
					'results' => $finalarr
				) );
				exit;
			}
			global $wpdb;

			/** To get automation step with action create coupon and stataus is 1 */
			$query   = "SELECT * FROM {$wpdb->prefix}bwfan_automation_step WHERE `aid` = {$automationId} AND `action` LIKE '%create_offer_recovery_link%' AND `status` = '1'";
			$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			/** Check for empty step */
			if ( empty( $results ) ) {
				wp_send_json( array(
					'results' => $finalarr,
				) );
				exit;
			}

			$automation_obj = BWFAN_Automation_V2::get_instance( $automationId );

			/** Get automation meta data */
			$automation_data = $automation_obj->get_automation_meta_data();
			$mapped_arr      = [];

			/** Form  mapped array with step id and node id */
			foreach ( $automation_data['steps'] as $step ) {
				if ( isset( $step['stepId'] ) ) {
					$mapped_arr[ $step['stepId'] ] = $step['id'];
				}
			}

			/** Iterating over resulting steps */
			foreach ( $results as $data ) {
				$stepid    = $data['ID'];
				$step_data = ( array ) json_decode( $data['data'], true );

				/** Checking for title in upsell recovery link sidebar data */
				if ( isset( $step_data['sidebarData'] ) && isset( $step_data['sidebarData']['title'] ) ) {
					$link_title = $step_data['sidebarData']['title'] . ' ( #' . ( ! empty( $mapped_arr ) && isset( $mapped_arr[ $stepid ] ) ? $mapped_arr[ $stepid ] : $stepid ) . ' )';
				} else {
					$link_title = '#' . ( ! empty( $mapped_arr ) && isset( $mapped_arr[ $stepid ] ) ? $mapped_arr[ $stepid ] : $stepid );
				}

				if ( ! empty( $merge_tag ) ) {
					$finalarr[] = [
						'key'   => '{{' . $this->tag_name . ' id="' . $stepid . '"}}',
						'value' => $link_title,
					];
				} else {
					$finalarr[] = [
						'key'   => $stepid,
						'value' => $link_title,
					];
				}
			}

			wp_send_json( array(
				'results' => $finalarr
			) );
			exit;
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Return mergetag schema
		 *
		 * @return array[]
		 */
		public function get_setting_schema() {
			return [
				[
					'id'          => 'sid',
					'type'        => 'ajax',
					'label'       => __( 'Step ID', 'wp-marketing-automations-pro' ),
					"class"       => 'bwfan-input-wrapper',
					"required"    => true,
					'placeholder' => 'Select',
					"description" => "",
					"ajax_cb"     => 'bwfan_get_fka_upsell_recovery_options',
				],
			];
		}

	}

	/**
	 * Register this merge tag to a group.
	 */
	if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_builder_pro_active() && bwfan_is_fk_stripe_active() ) {
		BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_Stripe_Offer_Recovery_Link', null, 'fk_stripe' );
	}
}
