<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC Detection
 */
if ( ! function_exists( 'bwfcrm_is_woocommerce_active' ) ) {
	function bwfcrm_is_woocommerce_active() {
		return BWFCRM_Plugin_Dependency::woocommerce_active_check();
	}
}

if ( ! function_exists( 'bwfcrm_maybe_get_datetime' ) ) {
	/**
	 * @param $date
	 * @param string $format
	 *
	 * @return DateTime|false
	 */
	function bwfcrm_maybe_get_datetime( $date, $format = 'Y-m-d' ) {
		if ( ! $date || empty( $date ) ) {
			return false;
		}

		return DateTime::createFromFormat( $format, $date );
	}
}
