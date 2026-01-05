<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_WLM_Pay_Per_Post {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'wlm_pay_per_post';
	}

	public function get_options( $search ) {
		$action = BWFAN_Core()->integration->get_action( 'wlm_add_user_to_pay_per_post' );

		if ( ! $action instanceof BWFAN_Action ) {
			return [];
		}

		$data = $action->get_view_data();

		if ( empty( $search ) ) {
			return $data;
		}

		return BWFAN_PRO_Common::search_srting_from_data( $data, $search );
	}


}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_WLM_Pay_Per_Post' );
}

