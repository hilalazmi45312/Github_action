<?php

class BWFAN_ADV_Coupon_Source extends BWFAN_Source {
	private static $instance = null;

	public function __construct() {
		$this->event_dir  = __DIR__;
		$this->nice_name  = __( 'Advanced Coupons', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'WooCommerce', 'wp-marketing-automations-pro' );
		$this->group_slug = 'wc';
		$this->priority   = 100;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_ADV_Coupon_Source|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *
	 * @param $user_id
	 *
	 * @return int|mixed
	 */
	public static function fetch_user_total_credit( $user_id ) {
		if ( empty( $user_id ) ) {
			return 0;
		}

		$credits = self::get_user_credits( $user_id );

		return ! empty( $credits ) && isset( $credits['increase'] ) ? $credits['increase'] : 0;
	}

	/**
	 * Get user credits
	 *
	 * @param $user_id
	 *
	 * @return array|false|null
	 */
	public static function get_user_credits( $user_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT SUM(`entry_amount`) AS `amount`, `entry_type` FROM {$wpdb->prefix}acfw_store_credits WHERE `user_id` = %d GROUP BY `entry_type`", $user_id );

		$result = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return ! empty( $result ) ? array_column( $result, 'amount', 'entry_type' ) : [];
	}
}

/**
 * Register this as a source.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_advanced_coupon_for_woocommerce_active() ) {
	BWFAN_Load_Sources::register( 'BWFAN_ADV_Coupon_Source' );
}
