<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'WCPB_Product_Badges_Translation' ) ) {

	class WCPB_Product_Badges_Translation {

		public function __construct() {

			add_action( 'init', array( $this, 'textdomain' ) );

		}

		public function textdomain() {

			load_plugin_textdomain( 'wcpb-product-badges', false, dirname( plugin_basename( __DIR__ ) ) . '/languages' );

		}

	}

}
