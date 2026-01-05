<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BWFAN_WCS_Coupons {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'wcs_coupons';
	}

	public function get_options( $search ) {
		$action = BWFAN_Core()->integration->get_action( 'wcs_add_coupon' );
		if ( ! $action instanceof BWFAN_Action ) {
			return [];
		}

		$data = $action->get_view_data( $search );
		if ( empty( $search ) ) {
			return $data;
		}

		return BWFAN_PRO_Common::search_srting_from_data( $data, $search );
	}
}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_WCS_Coupons' );
}
