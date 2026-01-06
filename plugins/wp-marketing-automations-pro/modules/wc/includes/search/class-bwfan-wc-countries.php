<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_WC_Countries {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'wc_countries';
	}

	public function get_options( $search ) {
		$countries     = WC()->countries->get_countries();
		$country_array = array();
		if ( ! empty( $countries ) && is_array( $countries ) ) {
			foreach ( $countries as $code => $country ) {
				$country_array[ $code ] = $country;
			}
		}

		return BWFAN_PRO_Common::search_srting_from_data( $country_array, $search );

	}


}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_WC_Countries' );
}

