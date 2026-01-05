<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BWFBE_WC_BLOCKS' ) ) {

	/**
	 * EMAILBLOCKS
	 */
	#[AllowDynamicProperties]
	class BWFBE_WC_BLOCKS {

		/**
		 * __construct
		 *
		 * @return void
		 */
		public function __construct() {
			$this->loaded_shortcode();
		}

		/**
		 * Plugin Loaded
		 *
		 * @return void
		 */
		public function loaded_shortcode() {
			if ( class_exists( 'WooCommerce' ) ) {
				$this->load_wc_order_block();
			}
		}

		public static function load_wc_order_block() {
			$wc_block_dir = __DIR__ . '/includes/';
			foreach ( glob( $wc_block_dir . '/class-*.php' ) as $_field_filename ) {
				$file_data = pathinfo( $_field_filename );
				if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
					continue;
				}
				require_once( $_field_filename );
			}
		}

	}

	new BWFBE_WC_BLOCKS();

}
