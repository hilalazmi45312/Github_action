<?php
/**
 * Compatibility.
 *
 * @since 3.7.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Compatibility' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.7.0
	 */
	abstract class DEY_Compatibility {

		/**
		 * ID
		 *
		 * @since 3.7.0
		 * @var string
		 * */
		protected $id;

		/**
		 * Plugin slug.
		 *
		 * @since 3.7.0
		 * @var string
		 * */
		protected $plugin_slug = 'dey';

		/**
		 * Class constructor.
		 *
		 * @since 3.7.0
		 * */
		public function __construct() {
			$this->process_actions();
		}

		/**
		 * Get ID.
		 *
		 * @since 3.7.0
		 * @return string
		 * */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Is enabled?
		 *
		 * @since 3.7.0
		 * @return bool
		 * */
		public function is_enabled() {
			return $this->is_plugin_enabled();
		}

		/**
		 * Is plugin enabled?.
		 *
		 * @since 3.7.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return true;
		}

		/**
		 * Actions.
		 *
		 * @since 3.7.0
		 * @return void
		 * */
		public function process_actions() {
			if ( ! $this->is_enabled() ) {
				return;
			}

			$this->actions();
			if ( is_admin() ) {
				$this->admin_action();

				// Add action for external js files in backend.
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			}

			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
				$this->frontend_action();

				// Add action for external js files in backend.
				add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );
			}
		}

		/**
		 * Admin actions.
		 *
		 * @since 3.7.0
		 * */
		public function admin_action() {
		}

		/**
		 * Actions.
		 *
		 * @since 3.7.0
		 * */
		public function actions() {
		}

		/**
		 * Frontend actions.
		 *
		 * @since 3.7.0
		 * */
		public function frontend_action() {
		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @since 3.7.0
		 * */
		public function admin_enqueue_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$this->admin_external_js_files( $suffix );
			$this->admin_external_css_files( $suffix );
		}

		/**
		 * Enqueue frontend scripts.
		 *
		 * @since 3.7.0
		 * */
		public function frontend_enqueue_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$this->frontend_external_js_files( $suffix );
			$this->frontend_external_css_files( $suffix );
		}

		/**
		 * Enqueue frontend JS files.
		 *
		 * @since 3.7.0
		 * @param string $suffix The suffix.
		 * */
		public function frontend_external_js_files( $suffix ) {
		}

		/**
		 * Enqueue frontend CSS files.
		 *
		 * @since 3.7.0
		 * @param string $suffix The suffix.
		 * */
		public function frontend_external_css_files( $suffix ) {
		}

		/**
		 * Enqueue admin JS files.
		 *
		 * @since 3.7.0
		 * @param string $suffix The suffix.
		 * */
		public function admin_external_js_files( $suffix ) {
		}

		/**
		 * Enqueue admin CSS files.
		 *
		 * @since 3.7.0
		 * @param string $suffix The suffix.
		 * */
		public function admin_external_css_files( $suffix ) {
		}
	}
}
