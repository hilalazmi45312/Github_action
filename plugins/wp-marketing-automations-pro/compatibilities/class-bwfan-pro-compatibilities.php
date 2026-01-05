<?php

/**
 * Class BWFAN_Pro_Compatibilities
 * Loads all the compatibilities files we have in Autonami against plugins
 */
class BWFAN_Pro_Compatibilities {

	public static function load_merge_tags_compatibilities() {

		$compatibilities = [
			'merge_tags/class-bwfan-pro-compatibility-handl-utm-grabber-data.php'        => function_exists( 'bwfan_is_utm_grabber_active' ) && bwfan_is_utm_grabber_active(),
			'merge_tags/class-bwfan-pro-compatibility-wc-advanced-shipment-tracking.php' => function_exists( 'wc_advanced_shipment_tracking' ) || class_exists( 'Ast_Pro' ),
			'merge_tags/class-bwfan-pro-compatibility-wc-order-tracking.php'             => bwfan_is_woocommerce_active() && function_exists( 'bwfan_wc_order_tracking_active' ) && bwfan_wc_order_tracking_active(),
			'merge_tags/class-bwfan-pro-compatibility-wc-sequential-order-number.php'    => class_exists( 'WC_Sequential_Order_Numbers_Pro_Loader' ) || class_exists( 'WC_Seq_Order_Number' ),
			'merge_tags/class-bwfan-pro-compatibility-wc-services.php'                   => defined( 'WOOCOMMERCE_CONNECT_MINIMUM_WOOCOMMERCE_VERSION' ),
			'merge_tags/class-bwfan-pro-compatibility-wc-shipment-tracking.php'          => function_exists( 'wc_shipment_tracking' ),
			'plugins/class-bwfan-pro-compatibility-translatepress.php'                   => class_exists( 'TRP_Translate_Press' ),
		];

		self::add_files( array_filter( $compatibilities ) );
	}

	/**
	 * Include valid compatibility files
	 *
	 * @param $paths
	 *
	 * @return void
	 */
	public static function add_files( $paths ) {
		/** Compatibilities folder */
		$dir = BWFAN_PRO_PLUGIN_DIR . '/compatibilities';
		try {
			foreach ( $paths as $file => $condition ) {
				if ( ! file_exists( $dir . '/' . $file ) ) {
					continue;
				}
				include_once $dir . '/' . $file;
			}
		} catch ( Exception|Error $e ) {
			BWF_Logger::get_instance()->log( 'Error while loading compatibility files: ' . $e->getMessage(), 'compatibilities-load-error', 'fka-files-load-error' );
		}
	}
}

add_action( 'bwfan_merge_tags_loaded', array( 'BWFAN_Pro_Compatibilities', 'load_merge_tags_compatibilities' ), 999 );
