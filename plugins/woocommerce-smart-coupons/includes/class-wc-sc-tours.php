<?php
/**
 * Handle Smart Coupons tours
 *
 * @author      StoreApps
 * @since       9.9.0
 * @version     1.7.0
 *
 * @package     woocommerce-smart-coupons/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Tours' ) ) {

	/**
	 * Class for handling coupon columns
	 */
	class WC_SC_Tours {

		/**
		 * Variable to hold instance of WC_SC_Tours
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Variable to hold all available tour scripts (JS file names)
		 *
		 * @var $tour_scripts
		 */
		private $tour_scripts = array();

		/**
		 * Variable to hold all available tour script tags (JS file names)
		 *
		 * @var $tour_scripts
		 */
		private $tour_script_tags = array();

		/**
		 * Constructor
		 */
		private function __construct() {

			add_action( 'current_screen', array( $this, 'load_tour_scripts' ) );

		}

		/**
		 * Get single instance of WC_SC_Tours
		 *
		 * @return WC_SC_Tours Singleton object of WC_SC_Tours
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return result of function call
		 */
		public function __call( $function_name, $arguments = array() ) {

			global $woocommerce_smart_coupon;

			if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
			} else {
				return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
			}

		}

		/**
		 * Conditionally load tour scripts and buttons on specific WooCommerce admin pages.
		 *
		 * This function is hooked to the 'current_screen' action and runs only when
		 * the current admin screen is available.
		 *
		 * @param WP_Screen $screen Current screen object.
		 */
		public function load_tour_scripts( $screen ) {
			try {
				global $pagenow;
				// Skip non-front-end requests.
				if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_rest_endpoint() || defined( 'REST_REQUEST' ) ) {
					if ( function_exists( 'get_current_screen' ) ) {
						$screen = get_current_screen();
						if ( ! empty( $screen->post_type ) && 'product' === $screen->post_type ) {
							return; // No need to run the following code when working with products.
						}
					}
					$get_post      = ( ! empty( $_GET['post'] ) ) ? wc_clean( wp_unslash( $_GET['post'] ) ) : '';            // phpcs:ignore
					$get_post_type = ( ! empty( $_GET['post_type'] ) ) ? wc_clean( wp_unslash( $_GET['post_type'] ) ) : '';  // phpcs:ignore
					if ( 'post.php' === $pagenow && ! empty( $get_post ) && 'product' === get_post_type( $get_post ) ) {
						return;  // No need to run the following code when working with products.
					}
					if ( 'post-new.php' === $pagenow && ! empty( $get_post_type ) && 'product' === $get_post_type ) {
						return;  // No need to run the following code when working with products.
					}
				}

				if ( empty( $screen->id ) ) {
					return;
				}

				$screen_id = $screen->id;

				$allowed_screens = array(
					'woocommerce_page_wc-settings',
					'edit-shop_coupon',
					'shop_coupon',
					'marketing_page_wc-smart-coupons',
					'marketing_page_sc-tour',
				);

				if ( in_array( $screen_id, $allowed_screens, true ) ) {
					$this->set_tour_scripts();
					$this->set_tour_script_tags();

					add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
					add_action( 'admin_head', array( $this, 'add_tour_button' ) );
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}


		/**
		 * Function to get tour script JS files
		 *
		 * @return array
		 */
		public function get_tour_scripts() {
			if ( empty( $this->tour_scripts ) ) {
				$this->set_tour_scripts();
			}

			return $this->tour_scripts;
		}

		/**
		 * Function to set tour script JS files
		 */
		public function set_tour_scripts() {
			$this->tour_scripts = glob( WC_SC_PLUGIN_DIRPATH . 'assets/js/tours/tour-*.min.js' );
		}

		/**
		 * Function to get tour script tags
		 *
		 * @return array
		 */
		public function get_tour_script_tags() {
			if ( empty( $this->tour_script_tags ) ) {
				$this->set_tour_script_tags();
			}

			return $this->tour_script_tags;
		}

		/**
		 * Function to get tour script tags
		 */
		public function set_tour_script_tags() {
			$files     = $this->get_tour_scripts();
			$file_tags = array_map(
				function( $file ) {
					$file_name = basename( $file, '.js' );
					return $file_name . '-js';
				},
				$files
			);

			$this->tour_script_tags = array_merge( $file_tags, array( 'wc-sc-admin-shepherd-js' ) );
		}

		/**
		 * Enqueue styles and scripts
		 */
		public function enqueue_styles_and_scripts() {
			try {
				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

				if ( ! wp_style_is( 'wc-sc-admin-shepherd-css', 'registered' ) ) {
					$style_path = '/assets/css/shepherd.css';
					$style_url  = $this->get_plugin_directory_url( $style_path );
					wp_register_style( 'wc-sc-admin-shepherd-css', $style_url, array(), $this->get_smart_coupons_version() );
				}
				if ( ! wp_style_is( 'wc-sc-admin-shepherd-css' ) ) {
					wp_enqueue_style( 'wc-sc-admin-shepherd-css' );
				}

				if ( ! wp_style_is( 'wc-sc-tour-css', 'registered' ) ) {
					$style_path = '/assets/css/wc-sc-tour' . $suffix . '.css';
					$style_url  = $this->get_plugin_directory_url( $style_path );
					wp_register_style( 'wc-sc-tour-css', $style_url, array( 'wc-sc-admin-shepherd-css' ), $this->get_smart_coupons_version() );
				}
				if ( ! wp_style_is( 'wc-sc-tour-css' ) ) {
					wp_enqueue_style( 'wc-sc-tour-css' );
				}

				if ( ! wp_script_is( 'wc-sc-admin-shepherd-js', 'registered' ) ) {
					$script_path = '/assets/js/shepherd.min.js';
					$script_url  = $this->get_plugin_directory_url( $script_path );
					wp_register_script( 'wc-sc-admin-shepherd-js', $script_url, array(), $this->get_smart_coupons_version(), true );
				}
				if ( ! wp_script_is( 'wc-sc-admin-shepherd-js' ) ) {
					wp_enqueue_script( 'wc-sc-admin-shepherd-js' );
				}

				$files = $this->get_tour_scripts();

				foreach ( $files as $file ) {
					$file_name = basename( $file, '.js' );
					if ( ! wp_script_is( $file_name . '-js', 'registered' ) ) {
						$script_path = '/assets/js/tours/' . $file_name . '.js';
						$script_url  = $this->get_plugin_directory_url( $script_path );
						wp_register_script( $file_name . '-js', $script_url, array( 'wc-sc-admin-shepherd-js' ), $this->get_smart_coupons_version(), true );
					}
					if ( ! wp_script_is( $file_name . '-js' ) ) {
						wp_enqueue_script( $file_name . '-js' );
					}
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Add 'Take a tour' button on Coupon dashboard.
		 */
		public function add_tour_button() {
			$screen = get_current_screen();

			if ( ! in_array( $screen->id, array( 'edit-shop_coupon', 'marketing_page_wc-smart-coupons' ), true ) ) {
				return;
			}
			if ( ! wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery' );
			}
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Find the 'Add New' button and append a new link after it
					var tourButton = $('.wrap .page-title-action');
					if (tourButton.length) {
						$('<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-tour' ), admin_url( 'admin.php' ) ) ); ?>" style="margin: -.4em 1em; vertical-align: text-bottom;" class="button"><?php echo esc_html__( 'Take a tour', 'woocommerce-smart-coupons' ); ?></a>').insertAfter(tourButton);
					} else if ($('h2 .add-new-h2').length) {
						$('<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-tour' ), admin_url( 'admin.php' ) ) ); ?>" style="margin: -.2em 1em; vertical-align: text-bottom;" class="button"><?php echo esc_html__( 'Take a tour', 'woocommerce-smart-coupons' ); ?></a>').insertAfter($('h2 .add-new-h2'));
					}
				});
			</script>
			<?php
		}

	}

}

WC_SC_Tours::get_instance();
