<?php
/**
 * SMS Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_SMS
 */
class BWFCRM_SMS {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function is_twilio_connected() {
		/**
		 * As there are more than one SMS Connectors, need to check for active one.
		 * Using this method in is_twilio_connected() because if lite is old, and new pro,
		 * then no one should get error
		 */
		return ! empty( BWFCRM_Common::get_sms_provider_slug() );


		// if ( ! class_exists( 'WFCO_Autonami_Connectors_Core' ) || ! class_exists( 'WFCO_Load_Connectors' ) ) {
		// return false;
		// }

		// global $wpdb;
		// $twilio_connector = $wpdb->get_results('SELECT * from '.$wpdb->prefix.'wfco_connectors where slug = \'bwfco_twilio\'');
		// return ! empty( $twilio_connector ) ? 1 : 0;
	}
}

if ( class_exists( 'BWFCRM_SMS' ) ) {
	BWFCRM_Core::register( 'sms', 'BWFCRM_SMS' );
}
