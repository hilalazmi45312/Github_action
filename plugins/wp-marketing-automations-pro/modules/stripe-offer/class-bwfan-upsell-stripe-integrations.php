<?php
if ( ! class_exists( 'BWFAN_Upsell_Stripe_Integration' ) ) {
	final class BWFAN_Upsell_Stripe_Integration extends BWFAN_Integration {
		private static $ins = null;
		protected $need_connector = false;

		private function __construct() {
			$this->action_dir = __DIR__;
			$this->nice_name  = __( 'Stripe', 'wp-marketing-automations-pro' );
			$this->group_name = __( 'Stripe', 'wp-marketing-automations-pro' );
			$this->group_slug = 'stripe';
		}

		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

	}

	/**
	 * Register this class as an integration.
	 */
	if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_builder_pro_active() && bwfan_is_fk_stripe_active() ) {
		BWFAN_Load_Integrations::register( 'BWFAN_Upsell_Stripe_Integration' );
		
	}
}
