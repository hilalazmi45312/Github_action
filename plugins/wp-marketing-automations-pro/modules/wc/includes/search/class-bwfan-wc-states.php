<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BWFAN_WC_States {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'wc_states';
	}

	/**
	 * @param $search
	 *
	 * @return array
	 */

	public function get_options( $search, $extra_data = [] ) {
		$country = ! empty( $extra_data['country'] ) ? $extra_data['country'] : '';
		$states = WC()->countries->get_states( $country );
		if ( empty( $states ) || ! is_array( $states ) ) {
			return [];
		}
		$state_array = [];

		if( $country ) {
			foreach ( $states as $state_code => $state_name ) {
				if ( stripos( $state_name, $search ) !== false || stripos( $country, $search ) !== false ) {
					$state_array[ $state_code ] = sprintf( '%s (%s)', $state_name, $country );
				}
			}
			return  $state_array;
		}

		foreach ( $states as $country => $state_list ) {
			if ( empty( $state_list ) || ! is_array( $state_list ) ) {
				continue;
			}
			foreach ( $state_list as $state_code => $state_name ) {
				if ( stripos( $state_name, $search ) !== false || stripos( $country, $search ) !== false ) {
					$state_array[ $state_code ] = sprintf( '%s (%s)', $state_name, $country );
				}
			}
		}

		return $state_array;
	}


}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_WC_States' );
}