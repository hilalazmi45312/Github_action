<?php

if ( ! class_exists( 'BWFAN_Generate_Offer_Link_Handler' ) ) {
	final class BWFAN_Generate_Offer_Link_Handler {
		public static $instance = null;

		public function __construct() {
			add_action( 'wp_loaded', [ $this, 'handle_offer_link' ], 101 );
			add_filter( 'wfocu_get_funnel_option', [ $this, 'funnel_settings' ], 10, 2 );
			add_filter( 'bwfan_automation_node_analytics', array( $this, 'action_node_analytics' ), 10, 4 );

			/** Ensure Thank you page open of Funnel */
			add_action( 'wfocu_offer_accepted_and_processed', array( $this, 'maybe_set_current_order' ), 1, 5 );
			add_filter( 'wfocu_modify_error_json_response', array( $this, 'provide_pay_link_on_error' ), 10, 4 );
			add_filter( 'wffn_wfty_filter_page_ids', array( $this, 'maybe_filter_thankyou' ), 99, 2 );
			add_action( 'woocommerce_thankyou', array( $this, 'maybe_log_thankyou_visited' ), 999 );

			add_action( 'wfocu_offer_rejected_event', array( $this, 'redirect_to_home' ), 1000000 );
			add_filter( 'wfocu_get_offer_id_filter', array( $this, 'return_offer_zero' ), 1000000 );
		}

		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle offer recovery link
		 *
		 * @return void
		 * @throws DateMalformedStringException
		 */
		public function handle_offer_link() {

			/** Display notice if link is expired */
			if ( function_exists( 'wc_add_notice' ) && isset( $_GET['bwfan-link-expired'] ) ) {
				wc_add_notice( 'Link has been expired!', 'notice' );

				return;
			}

			if ( ! isset( $_GET['bwfan-offer-code'] ) || empty( $_GET['bwfan-offer-code'] ) ) {
				return;
			}

			$offer_hash = filter_input( INPUT_GET, 'bwfan-offer-code' );
			$order_id   = filter_input( INPUT_GET, 'order_id' );
			$link_obj   = new BWFAN_Generate_Offer_Link( 0, $offer_hash, [ 'clicked' => 1 ] );
			/** Save click */
			$link_obj->save();

			$expiry_days = $link_obj->get_expiry_days();
			$create_time = DateTime::createFromFormat( 'Y-m-d H:i:s', $link_obj->get_create_date() );
			$expiry_date = clone $create_time;
			$expiry_date = $expiry_date->modify( "+$expiry_days days" );

			/** Checking if link is expired */
			if ( current_time( 'timestamp' ) > $expiry_date->getTimestamp() ) {
				wp_safe_redirect( home_url( '?bwfan-link-expired=1' ) );
				exit;
			}

			$offer_data = $link_obj->get_data();
			if ( class_exists( 'WFFN_Core' ) && is_array( $offer_data ) && isset( $offer_data['fid'] ) ) {
				/**
				 * Get wffn funnel id by upsell id for increase funnel view
				 */
				$funnel_id = get_post_meta( $offer_data['fid'], '_bwf_in_funnel', true );
				if ( ! empty( $funnel_id ) && class_exists( 'WFFN_Core' ) ) {
					WFFN_Core()->public->increase_funnel_visit_session_view( $funnel_id );
				}
			}

			$offer_recovery_link = $link_obj->generate_upsell_link( $order_id );
			$offer_recovery_link = empty( $offer_recovery_link ) ? home_url() : $offer_recovery_link;

			BWFAN_PRO_Common::wp_redirect( $offer_recovery_link );
			exit;
		}

		/**
		 * Get step data
		 *
		 * @param $sid
		 *
		 * @return array
		 */
		public static function get_step_data( $sid ) {
			$step = BWFAN_Model_Automation_Step::get_step_data_by_id( $sid );
			$data = isset( $step['data'] ) && BWFAN_Common::is_json( $step['data'] ) ? json_decode( $step['data'], true ) : [];

			return isset( $data['sidebarData'] ) ? $data['sidebarData'] : [];
		}

		/**
		 * Modify upsell setting
		 *
		 * @param $options
		 * @param $key
		 *
		 * @return mixed|string
		 */
		public function funnel_settings( $options, $key ) {
			$created_via = WFOCU_Core()->data->get( 'created_via' );
			if ( 'fka_stripe_offer' !== $created_via ) {
				return $options;
			}

			$is_merge = $this->is_order_merge();
			if ( 'order_behavior' === $key && true === $is_merge ) {
				return 'batching';
			}

			if ( 'order_behavior' === $key ) {
				return 'create_order';
			}

			if ( 'is_cancel_order' === $key ) {
				return 'no';
			}

			return $options;
		}

		/**
		 * Check settings for order will merge or will create new order
		 *
		 * @return bool
		 */
		public function is_order_merge() {
			$sid         = WFOCU_Core()->data->get( 'fka_sid' );
			$create_time = WFOCU_Core()->data->get( 'fka_upsell_created_at' );
			$step_data   = self::get_step_data( $sid );
			if ( ! isset( $step_data['order-settings'] ) || 'new' === $step_data['order-settings'] ) {
				return false;
			}

			$merge_hrs = isset( $step_data['merge_hrs'] ) ? intval( $step_data['merge_hrs'] ) : 0;
			if ( empty( $merge_hrs ) ) {
				return false;
			}
			$create_time = DateTime::createFromFormat( 'Y-m-d H:i:s', $create_time );
			$expiry_date = clone $create_time;
			$expiry_date = $expiry_date->modify( "+$merge_hrs hours" );
			/** If given time is exceed */
			if ( current_time( 'timestamp' ) > $expiry_date->getTimestamp() ) {
				return false;
			}

			return true;
		}

		/**
		 * Return the node analytics of mode upsell-recovery
		 *
		 * @param $data
		 * @param $automation_id
		 * @param $step_id
		 * @param $mode
		 *
		 * @return array
		 */
		public function action_node_analytics( $data, $automation_id, $step_id, $mode ) {
			/** If step is upsell recovery */
			if ( 'upsell-recovery' !== $mode ) {
				return $data;
			}
			$data           = BWFAN_Model_Stripe_Offer::get_analytics( $step_id );
			$sent           = isset( $data['sent'] ) ? $data['sent'] : 0;
			$click_rate     = isset( $data['click_rate'] ) ? number_format( $data['click_rate'], 2 ) : 0;
			$click_count    = isset( $data['click_count'] ) ? $data['click_count'] : 0;
			$conversions    = isset( $data['conversions'] ) ? absint( $data['conversions'] ) : 0;
			$revenue        = isset( $data['revenue'] ) ? floatval( $data['revenue'] ) : 0;
			$contacts_count = isset( $data['contacts_count'] ) ? absint( $data['contacts_count'] ) : 1;
			$rev_per_person = empty( $contacts_count ) || empty( $revenue ) ? 0 : number_format( $revenue / $contacts_count, 2 );
			$tiles          = [
				[
					'label' => __( 'Created', 'wp-marketing-automations-pro' ),
					'value' => empty( $sent ) ? '-' : $sent,
				],
				[
					'label' => __( 'Click Rate', 'wp-marketing-automations-pro' ),
					'value' => empty( $click_rate ) ? '-' : $click_rate . ' (' . $click_count . ')',
				],
			];

			if ( bwfan_is_woocommerce_active() ) {
				$currency_symbol = get_woocommerce_currency_symbol();
				$revenue         = html_entity_decode( $currency_symbol . $revenue );
				$rev_per_person  = html_entity_decode( $currency_symbol . $rev_per_person );

				$revenue_tiles = [
					[
						'label' => __( 'Revenue', 'wp-marketing-automations-pro' ),
						'value' => empty( $conversions ) ? '-' : $revenue . ' (' . $conversions . ')',
					],
					[
						'label' => __( 'Revenue/Contact', 'wp-marketing-automations-pro' ),
						'value' => empty( $contacts_count ) ? '-' : $rev_per_person,
					]
				];

				$tiles = array_merge( $tiles, $revenue_tiles );
			}

			return [
				'status' => true,
				'data'   => [
					'analytics' => [],
					'tile'      => $tiles,
				]
			];
		}

		/**
		 * Set current order as the one
		 *
		 * @param $get_offer_id
		 * @param $get_package
		 * @param $get_parent_order
		 * @param $new_order
		 *
		 * @return void
		 */
		public function maybe_set_current_order( $get_offer_id, $get_package, $get_parent_order, $new_order ) {
			if ( ! $new_order instanceof WC_Order ) {
				return;
			}

			$created_via = WFOCU_Core()->data->get( 'created_via' );
			if ( 'fka_stripe_offer' !== $created_via ) {
				return;
			}
			$upsell_id = get_post_meta( $get_offer_id, '_funnel_id', true );

			/** This new order is from FKA Stripe offer */
			$new_order->update_meta_data( '_bwfan_stripe_offer', '1' );

			if ( ! empty( $upsell_id ) && absint( $upsell_id ) > 0 ) {
				$new_order->update_meta_data( '_wfocu_funnel_id', $upsell_id );
			}

			$new_order->save_meta_data();

			WFOCU_Core()->data->set( 'corder', WFOCU_WC_Compatibility::get_order_id( $new_order ), 'orders' );

			WFOCU_Core()->data->set( 'corder', $new_order, '_orders' );
			WFOCU_Core()->data->save( 'orders' );

			/** Removing the action */
			remove_action( 'wfocu_offer_accepted_event', array( WFOCU_DB_Track::get_instance(), 'add_to_order_meta' ), 990 );

			add_action( 'wfocu_offer_accepted_event', array( $this, 'add_to_order_meta' ), 899 );

			/** Save order details in offer table */
			$p_key = WFOCU_Core()->data->get( 'fka_p_key' );
			if ( intval( $p_key ) > 0 ) {
				$data = [
					'order_id'    => $new_order->get_id(),
					'order_total' => BWF_Plugin_Compatibilities::get_fixed_currency_price_reverse( $new_order->get_total(), BWF_WC_Compatibility::get_order_currency( $new_order ) ),
					'order_date'  => $new_order->get_date_created()->format( 'Y-m-d H:i:s' ),
				];

				$link_obj = new BWFAN_Generate_Offer_Link( $p_key, '', $data );
				$link_obj->save();
			}
		}

		public function add_to_order_meta( $args ) {
			$get_current_order_id = isset( $args['new_order'] ) ? $args['new_order'] : '';
			if ( empty( $get_current_order_id ) ) {
				$get_current_order_id = WFOCU_Core()->data->get( 'corder', false, 'orders' );
			}
			if ( empty( $get_current_order_id ) ) {
				return;
			}

			$get_order = wc_get_order( $get_current_order_id );
			if ( ! $get_order instanceof WC_Order ) {
				return;
			}

			$total_sum          = floatval( $args['value'] );
			$total_sum_currency = floatval( $args['value_in_currency'] );

			if ( 0 < $total_sum ) {
				$get_order->update_meta_data( '_wfocu_upsell_amount', $total_sum );
			}
			if ( 0 < $total_sum_currency ) {
				$get_order->update_meta_data( '_wfocu_upsell_amount_currency', $total_sum_currency );
			}

			$aid  = WFOCU_Core()->data->get( 'fka_aid' );
			$note = "Stripe recovery offer accepted.";
			if ( ! empty( $aid ) && intval( $aid ) > 0 ) {
				$note .= " Automation ID: " . $aid;
			}
			$get_order->add_order_note( $note );

			/** Add item meta in the order */
			$this->update_item_meta( $get_order );

			if ( 0 < $total_sum || 0 < $total_sum_currency ) {
				$get_order->save_meta_data();
			}

			global $wpdb;
			$query = $wpdb->prepare( 'UPDATE`' . $wpdb->prefix . 'wfocu_session` SET  `total` = %s WHERE `order_id` = %s', $total_sum, $get_current_order_id );
			$wpdb->query( $query );  //phpcs:ignore
			// db call ok; no-cache ok; unprepared SQL ok.
		}

		/**
		 * Helper method - Update item meta
		 *
		 * @param $order
		 *
		 * @return void
		 */
		protected function update_item_meta( $order ) {
			if ( ! $order instanceof WC_Order ) {
				return;
			}
			$items = $order->get_items();
			if ( empty( $items ) ) {
				return;
			}
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				if ( ! $product instanceof WC_Product ) {
					continue;
				}
				$item->add_meta_data( '_fk_automation_stripe_offer', 'yes', true );
				$item->save_meta_data();
			}
		}

		/**
		 * @param $arr
		 *
		 * @return array|mixed
		 */
		public function provide_pay_link_on_error( $arr ) {
			$created_via = WFOCU_Core()->data->get( 'created_via' );
			if ( 'fka_stripe_offer' !== $created_via ) {
				return $arr;
			}
			if ( WFOCU_Core()->public->failed_order !== null && isset( $arr['response'] ) && isset( $arr['response']['redirect_url'] ) ) {
				$arr['response']['redirect_url'] = WFOCU_Core()->public->failed_order->get_checkout_payment_url();
			}

			return $arr;
		}

		/**
		 * Get upsell id by order id
		 *
		 * @param $order_id
		 *
		 * @return string|null
		 */
		public function get_upsell_id( $order_id ) {
			global $wpdb;
			$query = "SELECT `meta_value` FROM {$wpdb->prefix}wfocu_event_meta WHERE `event_id` = (SELECT `event_id` FROM {$wpdb->prefix}wfocu_event_meta WHERE `meta_key`='_new_order' AND `meta_value`=%d) AND `meta_key`='_funnel_id'";

			return $wpdb->get_var( $wpdb->prepare( $query, $order_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * @param $thankyou_page_ids
		 * @param $order
		 *
		 * @return array
		 */
		public function maybe_filter_thankyou( $thankyou_page_ids, $order ) {
			if ( ! $order instanceof WC_Order ) {
				return $thankyou_page_ids;
			}

			$created_via = WFOCU_Core()->data->get( 'created_via' );
			if ( 'fka_stripe_offer' !== $created_via ) {
				return $thankyou_page_ids;
			}

			$current_step = [];
			$step_id      = BWF_WC_Compatibility::get_order_meta( $order, '_wfocu_funnel_id' );

			if ( empty( $step_id ) ) {
				return $thankyou_page_ids;
			}

			if ( ! empty( $step_id ) && 0 !== abs( $step_id ) ) {
				$funnel_id = get_post_meta( $step_id, '_bwf_in_funnel', true );
				if ( ! empty( $funnel_id ) && abs( $funnel_id ) !== 0 ) {
					$funnel = WFFN_Core()->admin->get_funnel( $funnel_id );
					if ( $funnel instanceof WFFN_Funnel ) {
						$current_step['id']   = $step_id;
						$current_step['type'] = 'wc_upsells';
						$get_type_object      = WFFN_Core()->steps->get_integration_object( 'wc_thankyou' );
						if ( $get_type_object instanceof WFFN_Step ) {
							$thankyou_page_ids = $get_type_object->maybe_get_thankyou( $current_step, $funnel );
						}
						if ( empty( $thankyou_page_ids ) ) {
							$thankyou_page_ids = $get_type_object->get_store_checkout_thankyou_page( $thankyou_page_ids, $order );
						}
					}
				}
			}

			return $thankyou_page_ids;
		}

		/**
		 * Record thank you page views in upsell recovery
		 *
		 * @param $order_id
		 *
		 * @return void
		 */
		public function maybe_log_thankyou_visited( $order_id ) {
			global $post;
			$wfty_source = filter_input( INPUT_GET, 'wfty_source' );
			if ( empty( $wfty_source ) || is_null( $post ) || ! class_exists( 'WFFN_Core' ) ) {
				return;
			}
			/**
			 * Check valid upsell session
			 */
			if ( false === WFOCU_Core()->data->get_funnel_id() ) {
				return;
			}

			$created_via = WFOCU_Core()->data->get( 'created_via' );
			if ( 'fka_stripe_offer' !== $created_via ) {
				return;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return;
			}
			$upsell_id = $order->get_meta( '_wfocu_funnel_id' );
			if ( empty( $upsell_id ) ) {
				return;
			}

			$funnel_id = get_post_meta( $upsell_id, '_bwf_in_funnel', true );
			$funnel    = new WFFN_Funnel( $funnel_id );

			/**
			 * Check valid funnel session case
			 * Return if valid funnel because in upsell recovery has not funnel valid session
			 */
			if ( ! wffn_is_valid_funnel( $funnel ) || WFFN_Core()->data->has_valid_session() ) {
				return;
			}

			WFCO_Model_Report_views::update_data( gmdate( 'Y-m-d', current_time( 'timestamp' ) ), $post->ID, 5 );
		}

		/**
		 * Upon offer reject, hook woocommerce thank you redirect code
		 *
		 * @return void
		 */
		public function redirect_to_home() {
			$created_via = WFOCU_Core()->data->get( 'created_via' );
			if ( 'fka_stripe_offer' !== $created_via ) {
				return;
			}

			remove_all_filters( 'woocommerce_get_checkout_order_received_url' );

			add_filter( 'woocommerce_get_checkout_order_received_url', array( $this, 'offer_rejected' ), 999999999 );
		}

		/**
		 * Return 0 offer id
		 * As don't want to show next offer
		 *
		 * @param $return
		 *
		 * @return int|mixed
		 */
		public function return_offer_zero( $return ) {
			$created_via = WFOCU_Core()->data->get( 'created_via' );
			if ( 'fka_stripe_offer' !== $created_via ) {
				return $return;
			}

			return 0;
		}

		/**
		 * Change thank you url to home page in case of offer reject
		 *
		 * @return string|null
		 */
		public function offer_rejected() {
			return home_url();
		}
	}

	BWFAN_Generate_Offer_Link_Handler::get_instance();
}
