<?php

if ( ! class_exists( 'BWFAN_Generate_Offer_Link' ) ) {
	class BWFAN_Generate_Offer_Link {

		public $id = 0;
		private $data = [];

		public function __construct( $id = 0, $hash_code = '', $data = [] ) {
			if ( intval( $id ) > 0 ) {
				$this->data = BWFAN_Model_Stripe_Offer::get( $id );
			}

			if ( ! empty( $hash_code ) ) {
				$db_data    = BWFAN_Model_Stripe_Offer::get_specific_rows( 'hash_code', $hash_code );
				$this->data = isset( $db_data[0] ) ? $db_data[0] : $this->data;
			}
			if ( ! empty( $data ) ) {
				$this->set_data( $data );
			}
		}

		/**
		 * @return mixed
		 */
		public function get_expiry_days() {
			return $this->data['expiry_days'];
		}

		/**
		 * @return mixed
		 */
		public function get_create_date() {
			return $this->data['created_at'];
		}

		/**
		 * @return array|mixed|object|stdClass|null
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * @param $data
		 *
		 * @return void
		 */
		public function set_data( $data ) {
			foreach ( $data as $key => $value ) {
				$this->data[ $key ] = $value;
			}
			if ( empty( $data['updated_at'] ) ) {
				$this->data['updated_at'] = current_time( 'mysql' );
			}
		}

		/**
		 * Generate upsell link
		 *
		 * @param $order_id
		 *
		 * @return string
		 */
		public function generate_upsell_link( $order_id ) {
			if ( ! class_exists( 'WFOCU_Core' ) ) {
				return '';
			}

			$order = wc_get_order( $order_id );

			WFOCU_Core()->data->set( 'funnel_id', $this->data['fid'] );
			$funnel_offers = WFOCU_Core()->offers->get_offers( $this->data['fid'] );
			/** Get offer from funnel */
			$offer_data = isset( $funnel_offers[ $this->data['oid'] ] ) ? $funnel_offers[ $this->data['oid'] ] : [];

			if ( empty( $offer_data ) ) {
				return '';
			}
			$offer = [ $this->data['oid'] => $offer_data ];

			WFOCU_Core()->data->set( 'funnel', $offer );
			/** Set flag and step id */
			WFOCU_Core()->data->set( 'created_via', 'fka_stripe_offer' );
			WFOCU_Core()->data->set( 'fka_upsell_created_at', $this->data['created_at'] );
			WFOCU_Core()->data->set( 'fka_sid', $this->data['sid'] );
			WFOCU_Core()->data->set( 'fka_p_key', $this->data['ID'] );

			/** Set automation id if found in url query argument */
			if ( isset( $_GET['automation_id'] ) && intval( $_GET['automation_id'] ) > 0 ) {
				WFOCU_Core()->data->set( 'fka_aid', intval( $_GET['automation_id'] ) );
			}

			$ins = BWFAN_Generate_Offer_Link_Handler::get_instance();
			remove_filter( 'wfocu_get_offer_id_filter', array( $ins, 'return_offer_zero' ), 1000000 );

			$get_payment_gateway     = WFOCU_WC_Compatibility::get_payment_gateway_from_order( $order );
			$get_integration         = WFOCU_Core()->gateways->get_integration( $get_payment_gateway );
			$get_compatibility_class = WFOCU_Plugin_Compatibilities::get_compatibility_class( 'subscriptions' );
			remove_filter( 'wfocu_front_payment_gateway_integration_enabled', array( $get_compatibility_class, 'maybe_disable_integration_when_subscription_in_cart' ), 10 );
			$offer_url = '';
			if ( WFOCU_Core()->data->is_funnel_exists() && $get_integration instanceof WFOCU_Gateway && $get_integration->is_enabled( $order ) && ( $get_integration->has_token( $order ) || $get_integration->is_run_without_token() ) ) {

				$get_offer = WFOCU_Core()->offers->get_the_first_offer();
				if ( 0 === absint( $get_offer ) ) { //integer check done
					return $offer_url;
				}

				/**
				 * Check if parent order is same as the one saved in the session
				 * check if offer is the one saved in the session
				 * If both matches then we need to only return the upsell url we have and skip setting up upsell funnel again
				 */
				$get_order_id      = WFOCU_Core()->data->get( 'porder', 0, 'orders' );
				$get_current_offer = WFOCU_Core()->data->get( 'current_offer' );
				if ( $get_order_id === WFOCU_WC_Compatibility::get_order_id( $order ) && ! empty( $get_current_offer ) && $get_current_offer === $get_offer ) {
					return $this->get_the_upsell_url( $get_offer );
				}

				WFOCU_Core()->data->set( 'porder', $order, '_orders' );
				WFOCU_Core()->data->set( 'porder', WFOCU_WC_Compatibility::get_order_id( $order ), 'orders' );

				WFOCU_Core()->data->set( 'current_offer', $get_offer );
				WFOCU_Core()->data->set( 'useremail', WFOCU_WC_Compatibility::get_order_data( $order, 'billing_email' ) );
				WFOCU_Core()->data->save( 'orders' );
				WFOCU_Core()->data->save();

				do_action( 'wfocu_funnel_init_event', WFOCU_Core()->data->get_funnel_id(), 0, WFOCU_Core()->data->get( 'useremail' ), $order->get_payment_method(), $order->get_meta( '_woofunnel_cid' ) );

				return $this->get_the_upsell_url( $get_offer );
			}

			return $offer_url;
		}

		/**
		 * Get upsell url
		 *
		 * @param $offer
		 *
		 * @return string
		 */
		public function get_the_upsell_url( $offer ) {
			if ( empty( $offer ) ) {
				return '';
			}

			$offer_data = WFOCU_Core()->offers->get_offer( $offer );
			$link       = WFOCU_Core()->offers->get_the_link( $offer );
			if ( 'custom-page' === $offer_data->template ) {
				$custom_page_id = get_post_meta( $offer, '_wfocu_custom_page', true );
				if ( ! empty( $custom_page_id ) && intval( $custom_page_id ) > 0 ) {
					$get_custom_page_post = get_post( $custom_page_id );
					if ( is_null( $get_custom_page_post ) || ( is_object( $get_custom_page_post ) && 'publish' !== $get_custom_page_post->post_status ) ) {
						return '';
					}
				}
			}

			return add_query_arg( array(
				'wfocu-key' => WFOCU_Core()->data->get_funnel_key(),
				'wfocu-si'  => WFOCU_Core()->data->get_transient_key(),
			), $link );
		}

		/**
		 * @return int|mixed
		 */
		public function save() {
			if ( 0 === $this->get_id() ) {
				BWFAN_Model_Stripe_Offer::insert( $this->data );

				return BWFAN_Model_Stripe_Offer::insert_id();
			}
			$data = $this->data;
			if ( isset( $data['ID'] ) ) {
				unset( $data['ID'] );
			}
			BWFAN_Model_Stripe_Offer::update( $data, [ 'ID' => $this->get_id() ] );

			return $this->id;
		}

		/**
		 * @return mixed
		 */
		public function get_id() {
			$this->id = isset( $this->data['ID'] ) ? intval( $this->data['ID'] ) : 0;

			return $this->id;
		}

	}
}