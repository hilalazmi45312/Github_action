<?php

/**
 * Custom Post List Table.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Admin_Meta_Boxes' ) ) {
	require_once DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-admin-meta-box.php' ;
}

if ( ! class_exists( 'DEY_Post_List_Table' ) ) {

	/**
	 * DEY_Post_List_Table Class.
	 * 
	 */
	abstract class DEY_Post_List_Table extends DEY_Admin_Meta_Boxes {

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
		 * Object.
		 * 
		 * @var Object
		 */
		protected $object ;

		/**
		 * Database.
		 * 
		 * @var Object
		 */
		protected $database ;

		/**
		 * Class initialization.
		 */
		public function __construct() {

			// Post type is exists.
			if ( ! $this->post_type ) {
				return ;
			}

			global $wpdb ;

			$this->database = $wpdb ;

			// Init hooks.
			$this->init_hooks() ;
			// Init extra hooks.
			$this->init_extra_hooks() ;

			parent::__construct() ;
		}

		/**
		 * Initialize the hooks.
		 */
		protected function init_hooks() {
			add_filter( 'disable_months_dropdown' , array( $this, 'disable_months_dropdown' ) , 10 , 2 ) ;
			add_action( 'manage_posts_extra_tablenav' , array( $this, 'extra_tablenav' ) ) ;
			add_filter( 'view_mode_post_types' , array( $this, 'disable_view_mode' ) ) ;
			add_filter( 'views_edit-' . $this->post_type , array( $this, 'handle_views' ) ) ;
			add_action( 'restrict_manage_posts' , array( $this, 'restrict_manage_posts' ) ) ;
			add_filter( 'request' , array( $this, 'request_query' ) ) ;
			add_filter( 'admin_footer', array( $this, 'footer_content' ));
			add_filter( 'posts_search' , array( $this, 'search_filters' ) ) ;
			add_filter( 'post_row_actions' , array( $this, 'row_actions' ) , 100 , 2 ) ;
			add_filter( 'default_hidden_columns' , array( $this, 'default_hidden_columns' ) , 10 , 2 ) ;
			add_filter( 'list_table_primary_column' , array( $this, 'list_table_primary_column' ) , 10 , 2 ) ;

			add_filter( 'post_updated_messages' , array( $this, 'post_updated_messages' ) ) ;
			add_filter( 'bulk_post_updated_messages' , array( $this, 'bulk_post_updated_messages' ) , 10 , 2 ) ;
			add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns' , array( $this, 'define_sortable_columns' ) ) ;
			add_filter( 'manage_' . $this->post_type . '_posts_columns' , array( $this, 'define_columns' ) ) ;
			add_filter( 'bulk_actions-edit-' . $this->post_type , array( $this, 'define_bulk_actions' ) ) ;
			add_action( 'manage_' . $this->post_type . '_posts_custom_column' , array( $this, 'render_columns' ) , 10 , 2 ) ;
			add_filter( 'handle_bulk_actions-edit-' . $this->post_type , array( $this, 'handle_bulk_actions' ) , 10 , 3 ) ;
			// Actions.
			add_action( 'post_action_duplicate', array( $this, 'duplicate_post' ) );
			add_action( 'post_action_activate', array( $this, 'activate_post' ) );
			add_action( 'post_action_deactivate', array( $this, 'deactivate_post' ) );
		}

		/**
		 * Initialize the extra hooks based on child class.
		 */
		protected function init_extra_hooks() {
		}

		/**
		 * Disable Month Dropdown.
		 */
		public function disable_months_dropdown( $bool, $post_type ) {
			return false ;
		}

		/**
		 * Render extra tablenav.
		 *
		 */
		public function extra_tablenav( $which ) {
		}

		/**
		 * Removes this type from list of post types that support "View Mode" switching.
		 * View mode is seen on posts where you can switch between list or excerpt. Our post types don't support
		 * it, so we want to hide the useless UI from the screen options tab.
		 *
		 * @return array
		 */
		public function disable_view_mode( $post_types ) {
			unset( $post_types[ $this->post_type ] ) ;

			return $post_types ;
		}

		/**
		 * Handle the views.
		 */
		public function handle_views( $views ) {

			// Products do not have authors.
			unset( $views[ 'mine' ] ) ;

			return $views ;
		}

		/**
		 * See if we should render search filters or not.
		 */
		public function restrict_manage_posts() {
			global $typenow ;

			if ( $this->post_type === $typenow ) {
				$this->render_filters() ;
			}
		}

		/**
		 * Render any custom filters and search inputs for the list table.
		 */
		protected function render_filters() {
		}

		/**
		 * Handle any filters.
		 *
		 * @return array
		 */
		public function request_query( $query_vars ) {
			global $typenow ;

			if ( $this->post_type === $typenow ) {
				$query_vars = $this->query_filters( $query_vars ) ;
				$query_vars = $this->orderby_filters( $query_vars ) ;
			}

			return $query_vars ;
		}

		/**
		 * Handle any custom filters.
		 *
		 * @return array
		 */
		protected function query_filters( $query_vars ) {
			return $query_vars ;
		}

		/**
		 * Handle search filters.
		 *
		 * @return array
		 */
		public function search_filters( $where ) {
			global $pagenow, $wp ;

			if ( 'edit.php' != $pagenow || ! is_search() || ! isset( $wp->query_vars[ 's' ] ) || $this->post_type != $wp->query_vars[ 'post_type' ] ) {
				return $where ;
			}

			$search_ids = array() ;
			$terms      = explode( ',' , $wp->query_vars[ 's' ] ) ;

			// Get the search post ids.
			$post_ids = $this->get_search_post_ids( $terms ) ;

			if ( count( $post_ids ) > 0 ) {
				$where = str_replace( 'AND (((' , "AND ( ({$this->database->posts}.ID IN (" . implode( ',' , $post_ids ) . ')) OR ((' , $where ) ;
			}

			return $where ;
		}

		/**
		 * Get the search post IDs.
		 *
		 * @return array
		 */
		protected function get_search_post_ids( $terms ) {
			return array( 0 ) ;
		}

		/**
		 * Handle order by filters.
		 *
		 * @return array
		 */
		protected function orderby_filters( $query_vars ) {
			return $query_vars ;
		}

		/**
		 * Set row actions.
		 *
		 * @return array
		 */
		public function row_actions( $actions, $post ) {
			if ( $this->post_type === $post->post_type ) {
				return $this->get_row_actions( $actions , $post ) ;
			}

			return $actions ;
		}

		/**
		 * Get row actions to show in the list table.
		 *
		 * @return array
		 */
		protected function get_row_actions( $actions, $post ) {
			return $actions ;
		}

		/**
		 * Adjust which columns are displayed by default.
		 *
		 * @return array
		 */
		public function default_hidden_columns( $hidden, $screen ) {
			if ( isset( $screen->id ) && 'edit-' . $this->post_type === $screen->id ) {
				$hidden = array_merge( $hidden , $this->define_hidden_columns() ) ;
			}

			return $hidden ;
		}

		/**
		 * Set list table primary column.
		 *
		 * @return string
		 */
		public function list_table_primary_column( $default, $screen_id ) {
			if ( 'edit-' . $this->post_type === $screen_id && $this->get_primary_column() ) {
				return $this->get_primary_column() ;
			}

			return $default ;
		}

		/**
		 * Define primary column.
		 *
		 * @return array
		 */
		protected function get_primary_column() {
			return '' ;
		}

		/**
		 * Define hidden columns.
		 *
		 * @return array
		 */
		protected function define_hidden_columns() {
			return array() ;
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @return array
		 */
		public function post_updated_messages( $messages ) {
			return $messages ;
		}

		/**
		 * Specify custom bulk actions messages for different post types.
		 *
		 * @return array
		 */
		public function bulk_post_updated_messages( $messages, $bulk_counts ) {
			return $messages ;
		}

		/**
		 * Define which columns are sortable.
		 *
		 * @return array
		 */
		public function define_sortable_columns( $columns ) {
			return $columns ;
		}

		/**
		 * Define which columns to show on this screen.
		 *
		 * @return array
		 */
		public function define_columns( $columns ) {
			return $columns ;
		}

		/**
		 * Define bulk actions.
		 * 
		 * @return array
		 */
		public function define_bulk_actions( $actions ) {
			return $actions ;
		}

		/**
		 * Pre-fetch any data for the row each column has access to it.
		 */
		protected function prepare_row_data( $post_id ) {
		}

		/**
		 * Render individual columns.
		 */
		public function render_columns( $column, $post_id ) {
			$this->prepare_row_data( $post_id ) ;

			if ( ! $this->object ) {
				return ;
			}

			if ( is_callable( array( $this, 'render_' . $column . '_column' ) ) ) {
				$this->{"render_{$column}_column"}() ;
			}
		}

		/**
		 * Handle bulk actions.
		 *
		 * @return string
		 */
		public function handle_bulk_actions( $redirect_to, $action, $ids ) {
			return esc_url_raw( $redirect_to ) ;
		}

		/**
		 * Duplicate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id
		 */
		public function duplicate_post( $post_id ) {
			check_admin_referer( 'duplicate-post_' . $post_id );

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to edit this item.', 'delivery-slots-for-woocommerce' ) );
			}

			$duplicated_id = $this->process_duplicate_post( $post_id );
			if ( ! $duplicated_id ) {
				$sendback = add_query_arg( 'post_type', $this->post_type, admin_url( 'edit.php' ) );
			} else {
				$sendback = add_query_arg( array( 'post' => $duplicated_id, 'action' => 'edit' ), admin_url( 'post.php' ) );
			}

			wp_safe_redirect( $sendback );
			exit();
		}

		/**
		 * Process duplicate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id Post ID.
		 * @return int|boolean
		 */
		public function process_duplicate_post( $post_id ) {
			return false;
		}

		/**
		 * Activate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public function activate_post( $post_id ) {
			check_admin_referer( 'activate-post_' . $post_id );

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to edit this item.' ) );
			}

			$this->process_activate_post( $post_id );

			wp_safe_redirect( add_query_arg( 'post_type', $this->post_type, admin_url( 'edit.php' ) ) );
			exit();
		}

		/**
		 * Process activate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id Post ID.
		 */
		public function process_activate_post( $post_id ) {
		}

		/**
		 * Deactivate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public function deactivate_post( $post_id ) {
			check_admin_referer( 'deactivate-post_' . $post_id );

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to edit this item.' ) );
			}

			$this->process_deactivate_post( $post_id );

			wp_safe_redirect( add_query_arg( 'post_type', $this->post_type, admin_url( 'edit.php' ) ) );
			exit();
		}

		/**
		 * Process deactivate the post.
		 *
		 * @since 4.0.0
		 * @param int $post_id Post ID.
		 */
		public function process_deactivate_post( $post_id ) {
		}

		/**
		 * Display the footer contents.
		 * 
		 * @since 4.0.0
		 */
		public function footer_content() {
		}
	}

}
