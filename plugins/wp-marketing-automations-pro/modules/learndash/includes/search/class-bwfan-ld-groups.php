<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_LD_Groups {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'ld_groups';
	}

	public function get_options( $search ) {
		$groups = get_posts( array(
			'post_type'        => 'groups',
			'posts_per_page'   => 10,
			'status'           => 'publish',
			's'                => $search,
			'suppress_filters' => false
		) );

		$group_array = array();
		foreach ( $groups as $group ) {
			$group_array[ $group->ID ] = $group->post_title;
		}

		return $group_array;
	}

}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_LD_Groups' );
}

