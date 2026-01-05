<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_FK_Fetch_Offers {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'fk_fetch_offers';
	}

	public function get_options( $search ) {
		return BWFAN_Pro_Common::get_offers( $search );
	}

	public static function get_data( $args, $search ) {
		$posts  = get_posts( $args );
		$offers = [];
		foreach ( $posts as $post ) {
			$offers[ $post->ID ] = $post->post_title . '(#' . $post->ID . ')';
		}

		return array_filter( $offers, function ( $offer ) use ( $search ) {
			return false !== strpos( strtolower( $offer ), strtolower( $search ) );
		} );
	}

}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_FK_Fetch_Offers' );
}

