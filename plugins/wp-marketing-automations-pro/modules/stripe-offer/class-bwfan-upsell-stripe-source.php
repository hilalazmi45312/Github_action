<?php
if ( ! class_exists( 'BWFAN_Stripe_Upsell_Source' ) ) {
	class BWFAN_Stripe_Upsell_Source extends BWFAN_Source {
		private static $instance = null;

		public function __construct() {
			$this->event_dir  = __DIR__;
			$this->nice_name  = __( 'Stripe', 'wp-marketing-automations-pro' );
			$this->group_name = __( 'Stripe', 'wp-marketing-automations-pro' );
			$this->group_slug = 'stripe';
			$this->priority   = 11;
		}

		/**
		 * Ensures only one instance of the class is loaded or can be loaded.
		 *
		 * @return BWFAN_Stripe_Upsell_Source|null
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function get_group_notice_text() {
			return __( "Every missed offer is a missed sale, don't let it stay that way! With FunnelKit Stripe, you can follow up via Email or SMS and recover rejected Order Bumps or Upsells. Go to connectors now to connect Stripe and recover more revenue!", 'wp-marketing-automations-pro' );
		}
	}

	/**
	 * Register this as a source.
	 */
	if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_builder_pro_active() && bwfan_is_fk_stripe_active() ) {
		BWFAN_Load_Sources::register( 'BWFAN_Stripe_Upsell_Source' );
	}
}
