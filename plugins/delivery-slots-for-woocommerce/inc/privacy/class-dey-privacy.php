<?php
/*
 * GDPR Compliance
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

if ( ! class_exists( 'DEY_Privacy' ) ) :

	/**
	 * DEY_Privacy class
	 */
	class DEY_Privacy {

		/**
		 * DEY_Privacy constructor.
		 */
		public function __construct() {
			$this->init_hooks() ;
		}

		/**
		 * Register plugin
		 */
		public function init_hooks() {
			// This hook registers plugin privacy content
			add_action( 'admin_init' , array( __CLASS__, 'register_privacy_content' ) , 20 ) ;
		}

		/**
		 * Register Privacy Content
		 */
		public static function register_privacy_content() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return ;
			}

			$content = self::get_privacy_message() ;
			if ( $content ) {
				wp_add_privacy_policy_content( __( 'Delivery and Pickup Scheduler for WooCommerce' , 'delivery-slots-for-woocommerce' ) , $content ) ;
			}
		}

		/**
		 * Prepare Privacy Content
		 */
		public static function get_privacy_message() {

			return self::get_privacy_message_html() ;
		}

		/**
		 * Get Privacy Content
		 */
		public static function get_privacy_message_html() {
			ob_start() ;
			?>
			<p><?php esc_html_e( 'This includes the basics of what personal data your store may be collecting, storing and sharing. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your store will vary.' , 'delivery-slots-for-woocommerce' ) ; ?></p>
			<h2><?php esc_html_e( 'WHAT DOES THE PLUGIN DO?' , 'delivery-slots-for-woocommerce' ) ; ?></h2>
			<p><?php esc_html_e( 'Delivery and Pickup Scheduler for WooCommerce helps your users to select a date and time of delivery for orders and products. You can also display an estimated delivery date for the same.' , 'delivery-slots-for-woocommerce' ) ; ?> </p>
			<h2><?php esc_html_e( 'WHAT WE COLLECT AND STORE?' , 'delivery-slots-for-woocommerce' ) ; ?></h2>
			<h3><?php esc_html_e( 'First Name, Last Name and Email ID' , 'delivery-slots-for-woocommerce' ) ; ?></h3>
			<p><?php esc_html_e( 'We record the First Name, Last Name and Email ID of the users who have placed an order on your site by selecting their delivery date and time or when a delivery estimate is displayed.' , 'delivery-slots-for-woocommerce' ) ; ?></p>
			<h3><?php esc_html_e( 'Order ID' , 'delivery-slots-for-woocommerce' ) ; ?></h3>
			<p><?php esc_html_e( "We record the Order ID's of the Orders placed by selecting a delivery date and time or when a delivery estimate is displayed." , 'delivery-slots-for-woocommerce' ) ; ?></p>
			<?php
			$contents = ob_get_contents() ;
			ob_end_clean() ;

			return $contents ;
		}
	}

	new DEY_Privacy() ;

endif;
