<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_LD_Quiz {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'ld_quiz';
	}

	public function get_options( $search ) {

		$quizzes = get_posts( array(
			'post_type'        => 'sfwd-quiz',
			'posts_per_page'   => 10,
			'status'           => 'publish',
			's'                => $search,
			'suppress_filters' => false
		) );

		$quiz_array = array();
		foreach ( $quizzes as $quiz ) {
			$quiz_array[ $quiz->ID ] = $quiz->post_title;
		}

		return $quiz_array;
	}

}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_LD_Quiz' );
}

