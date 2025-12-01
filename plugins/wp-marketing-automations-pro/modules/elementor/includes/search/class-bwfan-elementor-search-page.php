<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_Elementor_Search_Page {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'elementor_search_page';
	}

	public function get_options( $search, $data = [] ) {
		$post_types = get_post_types( [ 'public' => true ] );
		$post_types = array_diff( $post_types, [ 'attachment', 'revision', 'nav_menu_item' ] );
		$args       = [
			'post_type'      => array_values( $post_types ),
			'posts_per_page' => $data['limit'] ?? 10,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
			's'              => $search,
			'search_columns' => 'post_title',
		];

		$page  = [];
		$posts = get_posts( $args );
		if ( empty( $posts ) ) {
			return [];
		}
		foreach ( $posts as $post ) {
			$page[] = [
				'id'    => $post->ID,
				'title' => wp_strip_all_tags( $post->post_title ),
				'type'  => $post->post_type,
				'url'   => get_permalink( $post->ID ),
			];
		}

		return $page;
	}


}

if ( class_exists( 'BWFAN_Elementor_Search_Page' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_Elementor_Search_Page' );
}


