<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_LD_Lessons {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'ld_lessons';
	}

	public function get_options( $search ) {
		$lessons      = get_posts( array(
			'post_type'        => 'sfwd-lessons',
			'posts_per_page'   => 10,
			'status'           => 'publish',
			's'                => $search,
			'suppress_filters' => false
		) );
		$lesson_array = array();
		foreach ( $lessons as $lesson ) {
			$lesson_array[ $lesson->ID ] = $lesson->post_title;
		}

		return $lesson_array;
	}

}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_LD_Lessons' );
}
