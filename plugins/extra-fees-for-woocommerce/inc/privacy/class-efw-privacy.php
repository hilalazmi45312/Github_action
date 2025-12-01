<?php
/*
 * GDPR Compliance.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Privacy' ) ) :

	/**
	 * EFW_Privacy class.
	 */
	class EFW_Privacy {

		/**
		 * EFW_Privacy constructor.
		 */
		public function __construct() {
			$this->init_hooks() ;
		}

		/**
		 * Register plugin.
		 */
		public function init_hooks() {
			//This hook registers Booking System privacy content.
			add_action( 'admin_init' , array( __CLASS__, 'register_privacy_content' ) , 20 ) ;
		}

		/**
		 * Register Privacy Content.
		 */
		public static function register_privacy_content() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return ;
			}

			$content = self::get_privacy_message() ;
			if ( $content ) {
				wp_add_privacy_policy_content( esc_html__( 'Exta Fees for WooCommerce' , 'extra-fees-for-woocommerce' ) , $content ) ;
			}
		}

		/**
		 * Prepare Privacy Content.
		 */
		public static function get_privacy_message() {

			return self::get_privacy_message_html() ;
		}

		/**
		 * Get Privacy Content.
		 */
		public static function get_privacy_message_html() {
			ob_start() ;
			?>
			<p><?php esc_html_e( 'This includes the basics of what personal data your store may be collecting, storing and sharing. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your store will vary.' , 'extra-fees-for-woocommerce' ) ; ?></p>
			<h2><?php esc_html_e( 'WHAT DOES THE PLUGIN DO?' , 'extra-fees-for-woocommerce' ) ; ?></h2>
			<p><?php esc_html_e( 'Using this plugin, you can charge extra fees for products, payment gateways and order total.' , 'extra-fees-for-woocommerce' ) ; ?> </p>
			<h2><?php esc_html_e( 'WHAT WE COLLECT AND STORE?' , 'extra-fees-for-woocommerce' ) ; ?></h2>
			<p><?php esc_html_e( 'This plugin does not collect or store any personal information about the users.' , 'extra-fees-for-woocommerce' ) ; ?></p>
			<?php
			$contents = ob_get_contents() ;
			ob_end_clean() ;

			return $contents ;
		}
	}

	new EFW_Privacy() ;

endif;
