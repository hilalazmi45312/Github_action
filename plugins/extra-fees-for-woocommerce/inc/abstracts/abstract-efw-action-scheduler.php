<?php
/**
 * Abstract EFW_Action_Scheduler Class.
 *
 * @package     WooCommerce\Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * EFW_Action_Scheduler class.
 */
abstract class EFW_Action_Scheduler {

	/**
	 * ID
	 *
	 * @var Integer
	 * */
	protected $id;

	/**
	 * Progress Bar Count.
	 *
	 * @var Integer
	 * */
	protected $progress_bar_count = 'efw_progress_bar_count';

	/**
	 * Action Scheduler Name.
	 *
	 * @var String
	 */
	protected $action_scheduler_name;

	/**
	 * Chunked Action Scheduler Name.
	 *
	 * @var String
	 */
	protected $chunked_action_scheduler_name;

	/**
	 * Option Name.
	 *
	 * @var String
	 */
	protected $option_name;

	/**
	 * Settings option Name.
	 *
	 * @var String
	 */
	protected $settings_option_name;

	/**
	 * Hook in methods.
	 */
	public function __construct() {
		add_action( $this->get_action_scheduler_name(), array( $this, 'scheduler_action' ) ); // Scheduler action.
		add_action( $this->get_chunked_action_scheduler_name(), array( $this, 'chunked_scheduler_action' ) ); // Chunked action scheduler action.
		add_action( 'admin_menu', array( $this, 'custom_dashboard_page' ) ); // Custom Dashboard menu.
		add_action( 'admin_head', array( $this, 'remove_dashboard_navigation_menu' ) ); // Remove dashboard navigation menu.
	}

	/**
	 * Get id.
	 *
	 * @return Integer
	 * */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Schedule action.
	 *
	 * @param Array $post_ids Post ID's.
	 * @param Array $setting_values Setting Values.
	 */
	public function schedule_action( $post_ids, $setting_values ) {
		if ( false === as_next_scheduled_action( $this->get_action_scheduler_name() ) || false === as_next_scheduled_action( $this->get_chunked_action_scheduler_name() ) ) {
			$this->delete_progress_count(); // Delete progress count.
			$this->update_action_scheduler_args( $post_ids, $setting_values ); // Update options.
			as_schedule_single_action( time(), $this->get_action_scheduler_name() , array( 'post_ids' => $post_ids )); // Schedule the event to update.
		}
	}

	/**
	 * Update action scheduler arguments.
	 *
	 * @param Array $post_ids Post ID's.
	 * @param Array $setting_values Setting Values.
	 */
	public function update_action_scheduler_args( $post_ids, $setting_values ) {
		update_option( $this->settings_option_name, $setting_values );
	}

	/**
	 * Scheduler action.
	 */
	public function scheduler_action( $post_ids ) {
		$chunked_data = array_filter( array_chunk( $post_ids, 10 ) );

		if ( ! efw_check_is_array( $chunked_data ) ) {
			return;
		}

		foreach ( $chunked_data as $index => $value ) {
			as_schedule_single_action( time() + $index, $this->get_chunked_action_scheduler_name(), array( 'chunked_value' => $value ) ); // Schedule the event to update.
		}
	}

	/**
	 * Get posts data.
	 */
	public function get_posts_data() {
		return get_option( $this->option_name, array() );
	}

	/**
	 * Get settings data.
	 */
	public function get_settings_data() {
		return get_option( $this->settings_option_name, array() );
	}

	/**
	 * Chunked scheduler action.
	 *
	 * @param String $chunked_value Chenked Value.
	 * @return Array
	 */
	public function chunked_scheduler_action( $chunked_value ) {
		return array();
	}

	/**
	 * Add Custom Dashboard Page
	 */
	public function custom_dashboard_page() {
		if ( ! isset( $_GET['efw_action_scheduler'] ) ) {
			return;
		}

		add_dashboard_page(
			$this->get_page_title(),
			$this->get_menu_title(),
			'read',
			$this->get_menu_slug(),
			array( $this, 'progress_bars' )
		);
	}

	/**
	 * Get page title
	 *
	 * @return String
	 */
	public function get_page_title() {
		return esc_html__( 'Action Scheduler', 'extra-fees-for-woocommerce' );
	}

	/**
	 * Get menu title
	 *
	 * @return String
	 */
	public function get_menu_title() {
		return esc_html__( 'Action Scheduler', 'extra-fees-for-woocommerce' );
	}

	/**
	 * Get menu slug
	 */
	public function get_menu_slug() {
		return isset( $_GET['efw_action_scheduler'] ) ? 'efw_settings' : '';
	}

	/**
	 * Remove dashboard navigation menu
	 */
	public function remove_dashboard_navigation_menu() {
		remove_submenu_page( 'index.php', $this->get_menu_slug() );
	}

	/**
	 * Set action scheduler name.
	 *
	 * @param String $action_scheduler_name Action Scheduler Name.
	 */
	public function set_action_scheduler_name( $action_scheduler_name ) {
		$this->action_scheduler_name = $action_scheduler_name;
	}

	/**
	 * Get action scheduler name.
	 *
	 * @return String
	 */
	public function get_action_scheduler_name() {
		return $this->action_scheduler_name;
	}

	/**
	 * Set chunked action scheduler name.
	 *
	 * @param String $chunked_action_scheduler_name Chunked Scheduler Name.
	 */
	public function set_chunked_action_scheduler_name( $chunked_action_scheduler_name ) {
		$this->chunked_action_scheduler_name = $chunked_action_scheduler_name;
	}

	/**
	 * Get chunked action scheduler name.
	 *
	 * @return String
	 */
	public function get_chunked_action_scheduler_name() {
		return $this->chunked_action_scheduler_name;
	}

	/**
	 * Delete Progress count
	 * */
	public function delete_progress_count() {
		delete_site_option( $this->progress_bar_count );
	}

	/**
	 * Get Progress count
	 *
	 * @return Integer
	 * */
	public function get_progress_count() {
		return (int) get_site_option( $this->progress_bar_count, 0 );
	}

	/**
	 * Update Progress count
	 *
	 * @param Integer $progress Progress.
	 * */
	public function update_progress_count( $progress = 0 ) {
		update_site_option( $this->progress_bar_count, $progress );
	}

	/**
	 * Get progress bar label.
	 *
	 * @return String
	 */
	public function get_progress_bar_label() {
		return esc_html__( 'Action scheduler is under process...', 'extra-fees-for-woocommerce' );
	}

	/**
	 * Display progress bar.
	 */
	public function progress_bars() {
		$action_scheduler_id = isset( $_GET['efw_action_scheduler'] ) ? wc_clean( wp_unslash( $_GET['efw_action_scheduler'] ) ) : '';

		if ( ! $action_scheduler_id ) {
			return;
		}

		$action_scheduler = EFW_Action_Scheduler_Instances::get_action_scheduler_by_id( $action_scheduler_id );

		if ( ! is_object( $action_scheduler ) ) {
			return;
		}

		include_once EFW_PLUGIN_PATH . '/inc/admin/menu/views/html-progress-bar.php';
	}

	/**
	 * Get settings url.
	 *
	 * @return URL
	 */
	public function get_settings_url() {
		return '';
	}

	/**
	 * Get redirect url.
	 *
	 * @return URL
	 */
	public function get_redirect_url() {
		return efw_get_settings_page_url();
	}

	/**
	 * Get success message.
	 *
	 * @return String
	 */
	public function get_success_message() {
		return '';
	}
}
