<?php
if ( ! class_exists( 'BWFAN_Stripe_Offer_Rejected' ) ) {
	final class BWFAN_Stripe_Offer_Rejected extends BWFAN_Event {
		private static $instance = null;
		public $order = null;
		public $order_id = 0;
		public $funnel_id = null;
		public $object_id = null;
		public $offer_type = null;

		private function __construct() {
			$this->event_merge_tag_groups = array( 'bwf_contact', 'wc_order', 'wc_funnel', 'wc_offer' );

			$this->optgroup_label    = esc_html__( 'Stripe', 'wp-marketing-automations-pro' );
			$this->event_name        = esc_html__( 'Recover Offers', 'wp-marketing-automations-pro' );
			$this->event_desc        = esc_html__( 'This event runs after an offer is rejected by the customer and payment gateway is FunnelKit Stripe.', 'wp-marketing-automations-pro' );
			$this->event_rule_groups = array(
				'wc_order',
				'wc_customer',
				'bwf_contact_segments',
				'bwf_contact',
				'bwf_contact_fields',
				'bwf_contact_user',
				'bwf_contact_wc',
				'bwf_contact_geo',
				'bwf_engagement',
				'bwf_broadcast',
				'woofunnels',
				'fk_stripe_upsell',
			);
			$this->support_lang      = true;
			$this->priority          = 45.4;
			$this->v2                = true;
			$this->support_v1        = false;
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function load_hooks() {
			add_action( 'bwf_normalize_contact_meta_before_save', [ $this, 'capture_rejected_offer' ], 9999, 3 );
			add_action( 'woocommerce_thankyou', [ $this, 'capture_rejected_bump' ], 98, 1 );
		}

		/**
		 * @param $contact
		 * @param $order_id
		 * @param $order WC_Order
		 *
		 * @return void
		 */
		public function capture_rejected_offer( $contact, $order_id, $order ) {

			BWFAN_Core()->public->load_active_v2_automations( $this->get_slug() );
			if ( ! is_array( $this->automations_v2_arr ) || count( $this->automations_v2_arr ) === 0 ) {
				return;
			}

			/** Check automation is already run on this order  */
			$this->automations_v2_arr = $this->maybe_automation_has_already_run( $this->automations_v2_arr, $order );
			if ( count( $this->automations_v2_arr ) === 0 ) {
				return;
			}

			/** Get rejected offers */
			$offers = $this->get_rejected_offers( $order_id );
			if ( empty( $offers ) ) {
				return;
			}

			$this->process( $offers );
		}

		/**
		 * Check automation has already run for order
		 *
		 * @param $automations_v2_arr
		 * @param $order
		 *
		 * @return array
		 */
		public function maybe_automation_has_already_run( $automations_v2_arr, $order ) {
			if ( ! $order instanceof WC_Order ) {
				return [];
			}

			$already_run_automation = $order->get_meta( 'fka_offer_already_rejected' );
			$exclude_aids           = is_array( $already_run_automation ) ? array_map( 'intval', array_keys( $already_run_automation ) ) : [];
			if ( empty( $exclude_aids ) ) {
				return $automations_v2_arr;
			}

			return array_filter( $automations_v2_arr, function ( $v2_automation ) use ( $exclude_aids ) {
				if ( in_array( intval( $v2_automation['id'] ), $exclude_aids, true ) ) {
					return false;
				}

				return $v2_automation;
			} );
		}

		public function get_rejected_offers( $order_id ) {
			global $wpdb;
			$query = "SELECT session.*, event.object_type,GROUP_CONCAT(DISTINCT event.object_id) as object_id FROM {$wpdb->prefix}wfocu_event AS event LEFT JOIN {$wpdb->prefix}wfocu_session AS session ON event.sess_id = session.id WHERE event.action_type_id=6 AND session.order_id = %d GROUP BY session.order_id, session.email ORDER BY session.cid DESC";
			$res   = $wpdb->get_results( $wpdb->prepare( $query, $order_id ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( empty( $res ) ) {
				return [];
			}

			/** Convert comma separate object id in as array */
			$offer = array_map( function ( $offer ) {
				$offer['object_id'] = explode( ',', $offer['object_id'] );

				return $offer;
			}, $res );

			return isset( $offer[0] ) ? $offer[0] : [];
		}

		/**
		 * Make the required data for the current event and run it.
		 *
		 * @param $data
		 */
		public function process( $data ) {
			$order = wc_get_order( $data['order_id'] );
			if ( ! $order instanceof WC_Order ) {
				return;
			}

			/** Checking payment gateway */
			if ( false === strpos( $order->get_payment_method(), 'fkwcs' ) ) {
				return;
			}
			$this->order_id  = $data['order_id'];
			$this->order     = $order;
			$this->funnel_id = isset( $data['fid'] ) ? $data['fid'] : 0;
			$this->object_id = isset( $data['object_id'] ) ? $data['object_id'] : 0;
			$contact_data_v2 = array(
				'order_id'  => $this->order_id,
				'email'     => $order->get_billing_email(),
				'user_id'   => $order->get_user_id(),
				'event'     => $this->get_slug(),
				'object_id' => $this->object_id,
				'fid'       => $this->funnel_id,
				'type'      => isset( $data['object_type'] ) ? $data['object_type'] : 'bump',
				'version'   => 2
			);
			BWFAN_Common::maybe_run_v2_automations( $this->get_slug(), $contact_data_v2 );

			/** Set flag to run once per order in order meta */
			if ( ! empty( BWFAN_Common::$last_automation_cid ) ) {
				$saved_aids = ! empty( $order->get_meta( 'fka_offer_already_rejected' ) ) ? $order->get_meta( 'fka_offer_already_rejected' ) : [];
				$aids       = ! empty( $saved_aids ) ? array_replace( $saved_aids, BWFAN_Common::$last_automation_cid ) : BWFAN_Common::$last_automation_cid;

				$order->update_meta_data( 'fka_offer_already_rejected', $aids );
				$order->save();
			}
		}

		/**
		 * @param $order_id
		 *
		 * @return void
		 */

		public function capture_rejected_bump( $order_id ) {
			BWFAN_Core()->public->load_active_v2_automations( $this->get_slug() );
			if ( ! is_array( $this->automations_v2_arr ) || count( $this->automations_v2_arr ) === 0 ) {
				return;
			}
			$order = wc_get_order( $order_id );

			$this->automations_v2_arr = $this->maybe_automation_has_already_run( $this->automations_v2_arr, $order );
			if ( count( $this->automations_v2_arr ) === 0 ) {
				return;
			}

			/** Get bumps from order */
			$bumps = $order->get_meta( '_wfob_report_data' );
			if ( empty( $bumps ) ) {
				return;
			}

			$data = [
				'order_id' => $order_id,
			];
			foreach ( $bumps as $bump ) {
				/** Continue if bump is accepted */
				if ( 0 !== intval( $bump['converted'] ) ) {
					continue;
				}

				$data['fid']         = $bump['fid'];
				$data['object_id'][] = $bump['bid'];

			}
			$this->process( $data );
		}

		/**
		 * Get data
		 *
		 * @return array|array[]
		 */
		public function get_event_data() {
			$data_to_send                        = [ 'global' => [] ];
			$this->order                         = ! $this->order instanceof WC_Order ? wc_get_order( $this->order_id ) : $this->order;
			$data_to_send['global']['order_id']  = $this->order_id;
			$data_to_send['global']['wc_order']  = $this->order instanceof WC_Order ? $this->order : '';
			$data_to_send['global']['email']     = $this->order instanceof WC_Order ? BWFAN_Woocommerce_Compatibility::get_billing_email( $this->order ) : '';
			$data_to_send['global']['funnel_id'] = $this->order instanceof WC_Order ? $this->order->get_meta( '_wfocu_funnel_id' ) : $this->funnel_id;
			$data_to_send['global']['object_id'] = $this->object_id;

			return $data_to_send;
		}

		/**
		 * Validate v2 event setting
		 *
		 * @param $automation_data
		 *
		 * @return bool
		 */
		public function validate_v2_event_settings( $automation_data ) {
			if ( ! isset( $automation_data['event_meta'] ) ) {
				return false;
			}
			if ( 'both' !== $automation_data['event_meta']['type'] && $automation_data['type'] !== $automation_data['event_meta']['type'] ) {
				return false;
			}

			/** Validate automation if contact is already exists with same order */
			if ( false === $this->validate_automation( $automation_data ) ) {
				return false;
			}

			/** If type is offer */
			if ( ( 'both' === $automation_data['event_meta']['type'] && 'offer' === $automation_data['type'] ) || 'offer' === $automation_data['event_meta']['type'] ) {
				return $this->validate_offer_setting( $automation_data );
			}

			/** If type is bump */
			return $this->validate_offer_setting( $automation_data, 'bump' );
		}

		/**
		 * @param $automation_data
		 *
		 * @return bool
		 */
		public function validate_automation( $automation_data ) {
			$global_data = BWFAN_Common::get_global_data( $this->get_event_data() );
			$cid         = isset( $global_data['global']['contact_id'] ) ? $global_data['global']['contact_id'] : 0;
			$cid         = empty( $cid ) && isset( $global_data['global']['cid'] ) ? $global_data['global']['cid'] : $cid;
			if ( empty( $cid ) ) {
				return false;
			}

			/** Check if contact is already exists in automation with same order */
			if ( BWFAN_Common::is_contact_in_automation( $automation_data['id'], $cid, $automation_data['order_id'], 0, 'stripe_upsell_offer_rejected' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Validate offer and bump settings
		 *
		 * @param $automation_data
		 * @param string $type
		 *
		 * @return bool
		 */
		public function validate_offer_setting( $automation_data, string $type = 'offer' ) {
			if ( ! isset( $automation_data['event_meta'][ $type . '-contains' ] ) ) {
				return false;
			}

			if ( 'any' === $automation_data['event_meta'][ $type . '-contains' ] ) {
				return true;
			}

			if ( ! isset( $automation_data['event_meta'][ $type . '_id' ] ) || ! isset( $automation_data['object_id'] ) ) {
				return false;
			}

			$offer_ids = array_map( 'intval', array_column( $automation_data['event_meta'][ $type . '_id' ], 'id' ) );

			if ( is_array( $automation_data['object_id'] ) ) {
				return count( array_intersect( $automation_data['object_id'], $offer_ids ) ) > 0;
			}

			return in_array( intval( $automation_data['object_id'] ), $offer_ids, true );
		}

		/**
		 * Get setting's field schema
		 *
		 * @return array[]
		 */
		public function get_fields_schema() {
			return [
				[
					'id'          => 'type',
					'label'       => __( 'Type', 'wp-marketing-automations-pro' ),
					'type'        => 'radio',
					'options'     => [
						[
							'label' => __( 'Offer Rejected', 'wp-marketing-automations-pro' ),
							'value' => 'offer'
						],
						[
							'label' => __( 'Bump Rejected', 'wp-marketing-automations-pro' ),
							'value' => 'bump'
						],
						[
							'label' => __( 'Both', 'wp-marketing-automations-pro' ),
							'value' => 'both'
						],
					],
					"class"       => 'bwfan-input-wrapper',
					"tip"         => "",
					"required"    => true,
					"description" => ""
				],
				[
					'id'          => 'offer-contains',
					'label'       => __( 'Offer Contains', 'wp-marketing-automations-pro' ),
					'type'        => 'radio',
					'options'     => [
						[
							'label' => __( 'Any Offer', 'wp-marketing-automations-pro' ),
							'value' => 'any'
						],
						[
							'label' => __( 'Specific Offers', 'wp-marketing-automations-pro' ),
							'value' => 'selected_offer'
						],
					],
					"class"       => 'bwfan-input-wrapper',
					"tip"         => "",
					"required"    => true,
					"description" => "",
					'toggler'     => [
						'fields'   => [
							[
								'id'    => 'type',
								'value' => [ 'offer', 'both' ],
							],
						],
						'relation' => 'OR'
					],
				],
				[
					'id'                  => 'offer_id',
					'label'               => __( 'Select Offers', 'wp-marketing-automations-pro' ),
					"type"                => 'custom_search',
					'autocompleterOption' => [
						'path'      => 'fk_fetch_offers',
						'slug'      => 'fk_fetch_offers',
						'labelText' => 'Offer'
					],
					'class'               => '',
					'placeholder'         => '',
					'required'            => true,
					'toggler'             => [
						'fields'   => [
							[
								'id'    => 'offer-contains',
								'value' => 'selected_offer',
							],
							[
								'id'    => 'type',
								'value' => [ 'offer', 'both' ],
							],
						],
						'relation' => 'AND'
					],
				],
				[
					'id'          => 'bump-contains',
					'label'       => __( 'Bump Contains', 'wp-marketing-automations-pro' ),
					'type'        => 'radio',
					'options'     => [
						[
							'label' => __( 'Any Bump', 'wp-marketing-automations-pro' ),
							'value' => 'any'
						],
						[
							'label' => __( 'Specific Bumps', 'wp-marketing-automations-pro' ),
							'value' => 'selected_bump'
						],
					],
					"class"       => 'bwfan-input-wrapper',
					"tip"         => "",
					"required"    => true,
					"description" => "",
					'toggler'     => [
						'fields'   => [
							[
								'id'    => 'type',
								'value' => [ 'bump', 'both' ],
							],
						],
						'relation' => 'OR'
					],
				],
				[
					'id'                  => 'bump_id',
					'label'               => __( 'Select Bumps', 'wp-marketing-automations-pro' ),
					"type"                => 'custom_search',
					'autocompleterOption' => [
						'path'      => 'fk_fetch_bumps',
						'slug'      => 'fk_fetch_bumps',
						'labelText' => 'Bump'
					],
					'class'               => '',
					'placeholder'         => '',
					'required'            => true,
					'toggler'             => [
						'fields'   => [
							[
								'id'    => 'bump-contains',
								'value' => 'selected_bump',
							],
							[
								'id'    => 'type',
								'value' => [ 'bump', 'both' ],
							],
						],
						'relation' => 'AND'
					],
				],
			];
		}

		/**
		 * Default values for goal values
		 *
		 * @return array
		 */
		public function get_default_values() {
			return [
				'type'           => 'offer',
				'offer-contains' => 'any',
				'order-settings' => 'new',
				'bump-contains'  => 'any',
			];
		}
	}

	/**
	 * Register this event to a source.
	 * This will show the current event in dropdown in single automation screen.
	 */
	if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_builder_pro_active() && bwfan_is_fk_stripe_active() ) {
		return 'BWFAN_Stripe_Offer_Rejected';
	}
}
