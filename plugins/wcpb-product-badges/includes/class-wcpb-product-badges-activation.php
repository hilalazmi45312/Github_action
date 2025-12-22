<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'WCPB_Product_Badges_Activation' ) ) {

	class WCPB_Product_Badges_Activation {

		public function __construct() {

			register_activation_hook( plugin_dir_path( __DIR__ ) . 'wcpb-product-badges.php', array( $this, 'transients' ) );

		}

		public function transients() {

			set_transient( 'wcpb_product_badges_activation_notice', true, 604800 );

		}

	}

}
