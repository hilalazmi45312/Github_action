<?php

/**
 * Plugin: https://wordpress.org/plugins/translatepress-multilingual/
 * Class BWFAN_Pro_Compatibility_TranslatePress
 */
class BWFAN_Pro_Compatibility_TranslatePress {

	private static $instance = null;

	public function __construct() {
		add_action( 'bwfan_before_executing_broadcast', [ $this, 'unhook_wp_mail_filter' ] );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function unhook_wp_mail_filter() {
		BWFAN_PRO_Common::remove_actions( 'wp_mail', 'TRP_Translation_Render', 'wp_mail_filter' );
	}
}

BWFAN_Pro_Compatibility_TranslatePress::get_instance();