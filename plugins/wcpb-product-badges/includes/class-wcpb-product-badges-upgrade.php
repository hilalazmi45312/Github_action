<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'WCPB_Product_Badges_Upgrade' ) ) {

	class WCPB_Product_Badges_Upgrade {

		public function __construct() {

			add_action( 'wp_loaded', array( $this, 'upgrade' ) );

		}

		public static function upgrade() {

			$version = get_option( 'wcpb_product_badges_version' );

			if ( WCPB_PRODUCT_BADGES_VERSION !== $version ) {

				global $wpdb;

				if ( version_compare( $version, '2.1.0', '<' ) ) {

					if ( get_option( 'wcpb_product_badges_compatibility_mode' ) === false ) {

						update_option( 'wcpb_product_badges_compatibility_mode', 'no' ); // Populate new compatibility mode option (was added in 2.0.0 but not populated during that upgrade)

					}

					if ( get_option( 'wcpb_product_badges_multiple_badges_per_product' ) === false ) {

						update_option( 'wcpb_product_badges_multiple_badges_per_product', 'no' );

					}

				}

				if ( version_compare( $version, '3.0.0', '<' ) ) {

					if ( 'yes' == get_option( 'wcpb_product_badges_compatibility_mode' ) ) {

						update_option( 'wcpb_product_badges_compatibility_mode_product_loops_position', 'yes' ); // If wcpb_product_badges_compatibility_mode was yes (the only previous compatibility mode setting), we set wcpb_product_badges_compatibility_mode_product_loops_position to yes as this was the only thing previously that wcpb_product_badges_compatibility_mode did, we can then delete wcpb_product_badges_compatibility_mode as now split into multiple settings

					} else {

						update_option( 'wcpb_product_badges_compatibility_mode_product_loops_position', 'no' ); // If wcpb_product_badges_compatibility_mode was not yes (the only previous compatibility mode setting), we set wcpb_product_badges_compatibility_mode_product_loops_position to no

					}

					delete_option( 'wcpb_product_badges_compatibility_mode' ); // As now split into multiple settings

					if ( get_option( 'wcpb_product_badges_compatibility_mode_product_pages' ) === false ) {

						update_option( 'wcpb_product_badges_compatibility_mode_product_pages', '' ); // Empty for disabled

					}

				}

				update_option( 'wcpb_product_badges_version', WCPB_PRODUCT_BADGES_VERSION );

			}

		}

	}

}
