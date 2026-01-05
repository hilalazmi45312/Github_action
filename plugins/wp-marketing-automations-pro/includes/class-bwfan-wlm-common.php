<?php

class BWFAN_Wlm_Common {

	public static function init() {
		add_filter( 'bwfan_select2_ajax_callable', array( __CLASS__, 'get_callable_object' ), 1, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_assets' ), 99 );
	}

	public static function admin_enqueue_assets() {
		//phpcs:disable WordPress.Security.NonceVerification

		if ( ! isset( $_GET['page'] ) || 'autonami' !== $_GET['page'] ) {
			return;
		}

		/** Prevent wlm styles to interfere with Autonami Styles */
		wp_dequeue_style( 'wlm-admin-settings-page' );

		//phpcs:enable WordPress.Security.NonceVerification
	}

	public static function get_callable_object( $is_empty, $data ) {

		if ( 'ppp' === $data['type'] ) {
			return [ __CLASS__, 'get_all_pay_per_posts' ];
		}

		return $is_empty;
	}

	/**
	 * Get quizzes by searched term
	 *
	 * @param $searched_term
	 */
	public static function get_all_pay_per_posts( $searched_term = '' ) {
		global $WishListMemberInstance;
		$searched_term = '%' . $searched_term . '%';
		$pay_per_posts = $WishListMemberInstance->get_pay_per_posts( array( 'ID', 'post_title' ), false, $searched_term );

		$results = array();
		if ( ! empty( $pay_per_posts ) ) {
			foreach ( $pay_per_posts as $ppp ) {
				$results[] = array(
					'id'   => $ppp->ID,
					'text' => $ppp->post_title,
				);
			}
		}

		/*
		 * data should be return like this using result as the key
		 * when getting data using search
		 */

		return array( 'results' => $results );
	}

}
