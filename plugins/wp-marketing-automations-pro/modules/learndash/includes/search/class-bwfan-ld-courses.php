<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_LD_Courses {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'ld_courses';
	}

	public function get_options( $search = '' ) {
		$courses       = BWFAN_Learndash_Common::get_learndash_courses( $search, 10 );
		$data          = isset( $courses['results'] ) ? $courses['results'] : [];
		$prepared_data = [];
		foreach ( $data as $course ) {
			$prepared_data[ $course['id'] ] = $course['text'];
		}

		return $prepared_data;
	}

}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_LD_Courses' );
}

