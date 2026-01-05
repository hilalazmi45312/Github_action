<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_LD_Topics {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'ld_topics';
	}

	public function get_options( $search ) {
		$topics = get_posts( array(
			'post_type'        => 'sfwd-topic',
			'posts_per_page'   => 10,
			'status'           => 'publish',
			's'                => $search,
			'suppress_filters' => false
		) );

		$topic_array = array();
		foreach ( $topics as $topic ) {
			$topic_array[ $topic->ID ] = $topic->post_title;
		}

		return $topic_array;
	}

}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_LD_Topics' );
}

