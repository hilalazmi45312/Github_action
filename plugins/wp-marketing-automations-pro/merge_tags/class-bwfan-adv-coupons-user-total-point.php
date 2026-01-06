<?php
if ( ! class_exists( 'BWFAN_ADV_Coupons_User_Total_points' ) ) {
	class BWFAN_ADV_Coupons_User_Total_points extends BWFAN_Merge_Tag {

		private static $instance = null;

		public function __construct() {
			$this->tag_name        = 'user_total_points';
			$this->tag_description = __( 'Total Points (All Time)', 'wp-marketing-automations-pro' );
			add_shortcode( 'bwfan_user_total_points', [ $this, 'parse_shortcode' ] );
		}

		/**
		 * @return self|null
		 */

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function parse_shortcode( $attr ) {
			if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
				return $this->parse_shortcode_output( $this->get_dummy_preview(), $attr );
			}

			$get_data = BWFAN_Merge_Tag_Loader::get_data();
			$user_id  = $this->get_user_id_from_data( $get_data );

			if ( empty( $user_id ) ) {
				return $this->parse_shortcode_output( '', $attr );
			}
			$points = self::bwfan_get_total_points( $user_id );

			return $this->parse_shortcode_output( $points, $attr );
		}

		/**
		 * @param $get_data
		 *
		 * @return int
		 */

		private function get_user_id_from_data( $get_data ) {
			$user_id = 0;

			if ( isset( $get_data['wp_user'] ) && $get_data['wp_user'] instanceof WP_User ) {
				$user_id = $get_data['wp_user']->ID;
			}

			if ( empty( $user_id ) && isset( $get_data['user_id'] ) ) {
				$user_id = absint( $get_data['user_id'] );
			}

			if ( empty( $user_id ) ) {
				$order = $this->get_order_object( $get_data );
				if ( ! empty( $order ) ) {
					$user_id = $order->get_user_id();
				}
			}

			if ( empty( $user_id ) && isset( $get_data['email'] ) ) {
				$contact = bwf_get_contact( 0, $get_data['email'] );
				if ( $contact && absint( $contact->get_id() ) > 0 ) {
					$user_id = $contact->get_wpid();
				}
			}

			return $user_id;
		}

		public static function bwfan_get_total_points( $user_id ) {
			global $wpdb;

			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_points = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(entry_amount) FROM {$wpdb->prefix}acfw_loyalprog_entries WHERE user_id = %d AND entry_type = 'earn'", $user_id ) );

			return is_null( $total_points ) ? 0 : $total_points;
		}

		/**
		 * @return int
		 */
		public function get_dummy_preview() {
			return 001;
		}
	}

	/**
	 * Register this merge tag to a group.
	 */
	if ( bwfan_is_advanced_coupon_for_woocommerce_active() ) {
		if ( version_compare( BWFAN_VERSION, '3.5.4', '>=' ) ) {
			BWFAN_Merge_Tag_Loader::register( 'bwfan_adv_coupon', 'BWFAN_ADV_Coupons_User_Total_points', null, __( 'Advanced Coupons', 'wp-marketing-automations-pro' ) );
		} else {
			BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_ADV_Coupons_User_Total_points', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
		}
	}
}
