<?php

/**
 * Plugin Name: FunnelKit Automations Pro
 * Plugin URI: https://funnelkit.com/wordpress-marketing-automation-autonami/
 * Description: Unlock deep integration with feature-rich WP & Woo plugins like WooCommerce Subscriptions, Gravity Forms, Affiliate WP, UpStroke, Zapier and many more that we'll keep on adding to the list.
 * Version: 3.6.5
 * Author: FunnelKit
 * Author URI: https://funnelkit.com
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-marketing-automations-pro
 * Requires Plugins: wp-marketing-automations
 *
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * WooFunnels: true
 */

final class BWFAN_Pro {

	private static $_instance = null;

	private function __construct() {

		add_action( 'plugins_loaded', function () {
			define( 'BWFAN_PRO_VERSION', '3.6.5' );
		}, 0 );

		/** Initialize Localization */
		add_action( 'init', array( $this, 'localization' ) );

		add_action( 'bwfan_before_register_modules', array( $this, 'load_classes_before_register' ) );
		add_action( 'plugins_loaded', [ $this, 'load_pro_dependencies_support' ], 5 );
		add_action( 'bwfan_loaded', [ $this, 'init_pro' ] );
		add_action( 'bwfan_before_automations_loaded', [ $this, 'add_modules' ] );
		add_action( 'bwfan_merge_tags_loaded', [ $this, 'load_merge_tags' ] );
		add_filter( 'bwfan_event_wc_comment_post_merge_tag_group', [ $this, 'add_wc_affiliate_merge' ], 999 );
		add_filter( 'bwfan_event_wc_new_order_merge_tag_group', [ $this, 'add_wc_affiliate_merge' ], 999 );
		add_filter( 'bwfan_event_wc_order_note_added_merge_tag_group', [ $this, 'add_wc_affiliate_merge' ], 999 );
		add_filter( 'bwfan_event_wc_order_status_change_merge_tag_group', [ $this, 'add_wc_affiliate_merge' ], 999 );
		add_filter( 'bwfan_event_wc_product_purchased_merge_tag_group', [ $this, 'add_wc_affiliate_merge' ], 999 );
		add_filter( 'bwfan_event_wc_product_refunded_merge_tag_group', [ $this, 'add_wc_affiliate_merge' ], 999 );
		add_filter( 'bwfan_event_wc_product_stock_reduced_merge_tag_group', [ $this, 'add_wc_affiliate_merge' ], 999 );


		define( 'BWFAN_PRO_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'BWFAN_PRO_PUBLIC_DIR', BWFAN_PRO_PLUGIN_DIR . '/public' );
		require __DIR__ . '/crm/class-marketing-automation-crm.php';

		add_action( 'wfco_load_connectors', [ $this, 'load_connector_classes' ] );
		add_action( 'bwfan_load_custom_search_classes', [ $this, 'load_custom_search_classes' ] );

		/** Stripe offer files load */
		add_action( 'plugins_loaded', [ $this, 'load_stripe_offer_files' ], 20 );
	}

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
	}

	public function init_pro() {
		$this->define_plugin_properties();
		$this->core();

		$this->load_rules();
		$this->load_compatibilities();
	}

	public function load_compatibilities() {
		require BWFAN_PRO_PLUGIN_DIR . '/compatibilities/class-bwfan-pro-compatibilities.php';
	}

	public function add_wc_affiliate_merge( $event_merge_group ) {
		if ( ! bwfan_is_affiliatewp_active() ) {
			return $event_merge_group;
		}
		if ( empty( $event_merge_group ) ) {
			$event_merge_group = array( 'wc_aff_affiliate' );

			return $event_merge_group;
		}

		array_push( $event_merge_group, 'wc_aff_affiliate' );

		return $event_merge_group;
	}

	public function define_plugin_properties() {
		define( 'BWFAN_PRO_FULL_NAME', 'FunnelKit Automations Pro' );
		define( 'BWFAN_PRO_PLUGIN_FILE', __FILE__ );
		define( 'BWFAN_PRO_PLUGIN_URL', untrailingslashit( plugin_dir_url( BWFAN_PRO_PLUGIN_FILE ) ) );
		define( 'BWFAN_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		define( 'BWFAN_PRO_IS_DEV', true );
		define( 'BWFAN_PRO_DB_VERSION', '1.0' );
		define( 'BWFAN_PRO_ENCODE', sha1( BWFAN_PRO_PLUGIN_BASENAME ) );
	}

	/*
	 * load plugin dependency
	 */
	public function load_pro_dependencies_support() {
		if ( ! did_action( 'bwfan_loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'show_activate_autonami_notice' ) );

			return;
		}

		require BWFAN_PRO_PLUGIN_DIR . '/includes/bwfan-pro-functions.php';
		require BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-pro-plugin-dependency.php';

	}

	public function show_activate_autonami_notice() {
		$screen = get_current_screen();

		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		$plugin = 'wp-marketing-automations/wp-marketing-automations.php';
		if ( $this->autonami_install_check() ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
			?>
            <div class="notice notice-error" style="display: block!important;">
                <p>
					<?php
					echo '<p>' . __( 'The <b>FunnelKit Automations Pro</b> plugin requires <b>FunnelKit Automations</b> plugin to be activated.', 'wp-marketing-automations-pro' ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, __( 'Activate FunnelKit Automations Now', 'wp-marketing-automations-pro' ) ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
                </p>
            </div>

			<?php
		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}
			$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=wp-marketing-automations' ), 'install-plugin_wp-marketing-automations' );
			?>
            <div class="notice notice-error" style="display: block!important;">
                <p>
					<?php
					echo '<p>' . __( 'The <b>FunnelKit Automations Pro</b> plugin requires <b>FunnelKit Automations</b> plugin to be installed.', 'wp-marketing-automations-pro' ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, __( 'Install FunnelKit Automations Now', 'wp-marketing-automations-pro' ) ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
                </p>
            </div>
			<?php
		}
	}

	public function autonami_install_check() {
		$path    = 'wp-marketing-automations/wp-marketing-automations.php';
		$plugins = get_plugins();

		return isset( $plugins[ $path ] );
	}

	public function load_classes_before_register() {
		require BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-pro-common.php';
		if ( ! BWFAN_PRO_Common::is_lite_3_0() ) {
			include BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-conversions.php';
			include BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-email-conversations.php';
			include BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-message.php';
		}

		include BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-engagement-tracking.php';
		include BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-fix-collation.php';
	}

	private function core() {
		include BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-pro-woofunnel-support.php';

		require BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-pro-db.php';
		require BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-pro-db-update.php';

		BWFAN_PRO_Common::init();

		if ( bwfan_is_learndash_active() ) {
			$this->load_learndash();
		}

		if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
			$this->load_wlm();
		}

		// loading common file for divi forms
		if ( function_exists( 'bwfan_is_divi_forms_active' ) && bwfan_is_divi_forms_active() ) {
			$this->load_divi_forms();
		}

		if ( is_admin() ) {
			include BWFAN_PRO_PLUGIN_DIR . '/admin/class-bwfan-pro-admin.php';
		}
	}

	public function load_stripe_offer_files() {
		if ( ! did_action( 'bwfan_loaded' ) ) {
			return;
		}
		if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_builder_pro_active() && bwfan_is_fk_stripe_active() ) {
			require_once( BWFAN_PRO_PLUGIN_DIR . '/modules/stripe-offer/includes/class-bwfan-generate-upsell-offer-link.php' );
			require_once( BWFAN_PRO_PLUGIN_DIR . '/modules/stripe-offer/includes/class-bwfan-generate-offer-link-handler.php' );
		}
	}

	private function load_learndash() {
		require BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-learndash-common.php';
		BWFAN_Learndash_Common::init();
	}

	private function load_wlm() {
		require BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-wlm-common.php';
		BWFAN_Wlm_Common::init();
	}

	/**
	 * including common file for divi forms
	 * @return void
	 */
	private function load_divi_forms() {
		require BWFAN_PRO_PLUGIN_DIR . '/includes/class-bwfan-divi-forms-common.php';
	}

	/**
	 * Include all modules
	 */
	public function add_modules() {
		$integration_dir = BWFAN_PRO_PLUGIN_DIR . '/modules';
		foreach ( glob( $integration_dir . '/*/class-*.php' ) as $_field_filename ) {
			if ( strpos( $_field_filename, 'index.php' ) !== false ) {
				continue;
			}
			require_once( $_field_filename );
		}
	}

	public function load_custom_search_classes() {
		$resource_dir = BWFAN_PRO_PLUGIN_DIR . '/modules';

		foreach ( glob( $resource_dir . '/*' ) as $module ) {
			if ( strpos( $module, 'index.php' ) !== false ) {
				continue;
			}
			foreach ( glob( $module . '/includes/search/class-*.php' ) as $_field_filename ) {
				require_once( $_field_filename );
			}
		}

		do_action( 'bwfan_pro_custom_search_classes_loaded', $this );
	}

	/**
	 * Include Merge Tags files
	 */
	public function load_merge_tags() {
		/** Merge tags in root folder */
		$dir = BWFAN_PRO_PLUGIN_DIR . '/merge_tags';
		foreach ( glob( $dir . '/class-*.php' ) as $_field_filename ) {
			require_once( $_field_filename );
		}

		/** Merge tags inside modules */
		$dir = BWFAN_PRO_PLUGIN_DIR . '/modules';
		foreach ( glob( $dir . '/*/merge-tags/class-*.php' ) as $_field_filename ) {
			require_once( $_field_filename );
		}
	}

	public function load_connector_classes() {
		$resource_dir = BWFAN_PRO_PLUGIN_DIR . '/connectors';
		foreach ( glob( $resource_dir . '/*/*' ) as $_field_filename ) {
			if ( strpos( $_field_filename, 'index.php' ) !== false ) {
				continue;
			}
			require_once( $_field_filename );
		}

		do_action( 'bwfan_upsell_stripe_connector_loaded', $this );
	}

	private function load_rules() {
		include_once BWFAN_PRO_PLUGIN_DIR . '/rules/class-bwfan-rules.php';
	}

	public function localization() {
		load_plugin_textdomain( 'wp-marketing-automations-pro', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * to avoid unserialize of the current class
	 */
	public function __wakeup() {
		throw new ErrorException( esc_html__( 'BWFAN_Core Pro can`t converted to string', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * to avoid serialize of the current class
	 */
	public function __sleep() {
		throw new ErrorException( esc_html__( 'BWFAN_Core Pro can`t converted to string', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * To avoid cloning of current class
	 */
	protected function __clone() {
	}
}

BWFAN_Pro::get_instance();
