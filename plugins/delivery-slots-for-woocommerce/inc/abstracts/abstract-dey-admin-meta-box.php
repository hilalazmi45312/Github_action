<?php

/**
 * Admin Meta Boxes.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Admin_Meta_Boxes' ) ) {

	/**
	 * Class.
	 */
	abstract class DEY_Admin_Meta_Boxes {

		/**
		 * Post Type.
		 * 
		 * @var String
		 */
		protected $post_type = '' ;

		/**
		 * Plugin Slug.
		 * 
		 * @var String
		 */
		protected $plugin_slug = '' ;

		/**
		 * Saved meta boxes.
		 * 
		 * @var String
		 */
		private static $saved_meta_boxes = false ;

		/**
		 * Class initialization.
		 */
		public function __construct() {

			// Post type is exists.
			if ( ! $this->post_type ) {
				return ;
			}

			// Init meta box hooks.
			$this->init_meta_box_hooks() ;
		}

		/**
		 * Initialize the meta box hooks.
		 */
		protected function init_meta_box_hooks() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 30 ) ;
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 ) ;
			add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 ) ;
		}

		/**
		 * Enqueue scripts for this post type.
		 * 
		 * @return void
		 */
		public function enqueue_scripts() {
		}

		/**
		 * Add meta boxes for this post type.
		 * 
		 * @return void
		 */
		public function add_meta_boxes() {
		}

		/**
		 * Save meta boxes for this post type.
		 * 
		 * @return void
		 */
		public function save_meta_boxes( $post_id, $post ) {
			$post_id = absint( $post_id ) ;

			// $post_id and $post are required.
			if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
				return ;
			}

			// Check the nonce.
			if ( empty( $_POST[ $this->plugin_slug . '_meta_nonce' ] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST[ $this->plugin_slug . '_meta_nonce' ] ) ), $this->plugin_slug . '_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return ;
			}

			// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
			if ( empty( $_POST[ 'post_ID' ] ) || absint( $_POST[ 'post_ID' ] ) !== $post_id ) {
				return ;
			}

			// Check user has permission to edit.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return ;
			}

			// We need this save event to run once to avoid potential endless loops. This would have been perfect:
			self::$saved_meta_boxes = true ;

			// Save meta box data.
			$this->save_current_meta_boxes( $post_id, $post ) ;

			/**
			 * This hook is used to do extra action after current meta box saved.
			 * 
			 * @since 1.0
			 */
			do_action( 'dey_after_save_' . $this->post_type . '_data', $post_id, $post ) ;
		}

		/**
		 * Save meta boxes for this post type.
		 * 
		 * @return void
		 */
		public function save_current_meta_boxes( $post_id, $post ) {
		}
	}

}
