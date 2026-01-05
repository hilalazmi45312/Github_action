<?php

if ( ! class_exists( 'BWFCO_Stripe' ) ) {
	#[AllowDynamicProperties]
	class BWFCO_Stripe extends BWF_CO {

		private static $ins = null;
		public static $headers = null;
		public $is_setting = true;
		public $v2 = true;
		public $is_pro = false;
		public $direct_connect = true;
		public $is_new = 1;
		public static $plugin_zip_url = 'https://downloads.wordpress.org/plugin/funnelkit-stripe-woo-payment-gateway.zip';
		public static $plugin = 'funnelkit-stripe-woo-payment-gateway/funnelkit-stripe-woo-payment-gateway.php';

		public function __construct() {
			$this->is_setting        = true;
			$this->dir               = __DIR__;
			$this->connector_url     = BWFAN_PRO_PLUGIN_URL;
			$this->autonami_int_slug = 'BWFAN_Upsell_Stripe_Integration';
			$this->priority          = 5;

			add_filter( 'wfco_connectors_loaded', array( $this, 'add_card' ) );
			add_action( 'fkwcs_after_connect_with_stripe', array( $this, 'connect_disconnect_on_strip_config' ) );
		}

		/**
		 * @return BWFCO_Stripe|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		public function add_card( $available_connectors ) {
			$available_connectors['autonami']['connectors']['bwfco_stripe'] = array(
				'name'            => 'Stripe',
				'desc'            => __( 'Now recover more orders when users rejects upsell offers or bumps and follow up with special one click link when they pay via Stripe.', 'wp-marketing-automations-pro' ),
				'connector_class' => 'bwfco_stripe',
				'image'           => BWFAN_PLUGIN_URL . '/includes/connectors-logo/stripe.png',
				'source'          => '',
				'file'            => '',
			);

			return $available_connectors;
		}

		public function connect_disconnect_on_strip_config( $status ) {
			if ( $status === 'success' ) {
				WFCO_Common::save_connector_data( [], $this->get_slug(), 1 );

				return;
			}
			$stripe_connect = WFCO_Load_Connectors::get_connector( $this->get_slug() );
			if ( method_exists( $stripe_connect, 'disconnect' ) ) {
				$stripe_connect->disconnect();
			}
		}

		/**
		 * Handles the settings form submission
		 *
		 * @param $data
		 * @param $type
		 *
		 * @return array|string[]
		 */
		public function handle_settings_form( $data, $type = 'save' ) {
			/** Stripe upsell offer table obj */
			BWFAN_DB::load_table_classes();
			require_once BWFAN_PRO_PLUGIN_DIR . '/includes/db/tables/class-bwfan-db-table-stripe-offer.php';

			$table_obj = new BWFAN_DB_Table_Stripe_Offer();
			if ( ! $table_obj->is_exists() ) {
				$table_obj->create_table();
			}

			$resp = $this->bwf_install_activate_fkstripe();
			$resp = array_merge( array(
				'status'  => 'success',
				'message' => '',
			), $resp );

			if ( $resp['status'] === 'success' && ! isset( $resp['redirect'] ) ) {
				$resp['id'] = WFCO_Common::save_connector_data( [], $this->get_slug(), 1 );
			}

			return $resp;
		}

		public function get_fields_schema() {
			$is_connected = isset( WFCO_Common::$connectors_saved_data[ $this->get_slug() ] ) && true === $this->has_settings();

			return array(
				array(
					'id'       => 'bwfan_upsell_strip_info',
					'type'     => 'para',
					'class'    => 'bwfan_upsell_strip_info',
					'required' => false,
					'children' => ! $is_connected ? __( 'To connect with upsell stripe click on connect', 'wp-marketing-automations-pro' ) : __( 'Already Connected. To reconnect, press the disconnect button and connect again.', 'wp-marketing-automations-pro' ),
				),
			);
		}

		public function get_settings_fields_values() {
			return [];
		}

		public function bwf_install_activate_fkstripe() {
			$all_plugins = get_plugins();
			$resp        = [];
			if ( ! array_key_exists( self::$plugin, $all_plugins ) ) {
				$resp = $this->bwfan_install_stripe();
			}

			if ( isset( $resp['status'] ) && ( ! $resp['status'] || $resp['status'] === 'rerun_api' ) ) {
				return $resp;
			}

			$resp = true;
			if ( ! is_plugin_active( self::$plugin ) ) {
				$resp = $this->maybe_activate_stripe( self::$plugin );
				if ( $resp ) {
					return [
						'status' => 'rerun_api'
					];
				}
			}

			if ( is_array( $resp ) && isset( $resp['status'] ) && $resp['status'] === 'rerun_api' ) {
				return $resp;
			}

			if ( $resp && \FKWCS\Gateway\Stripe\Admin::get_instance()->is_stripe_connected() ) {
				return [
					'status' => 'success',
				];
			}

			return [
				'status'   => 'not_connected',
				'redirect' => \FKWCS\Gateway\Stripe\Admin::get_instance()->get_connect_url()
			];
		}

		public function bwfan_install_stripe() {
			$resp            = array();
			$installed       = $this->maybe_install_stripe( self::$plugin, self::$plugin_zip_url );
			$resp['status']  = true;
			$resp['message'] = __( 'Funnelkit stripe gateway plugin installed successfully', 'wp-marketing-automations-pro' );

			if ( ! $installed ) {
				return [
					'status'  => false,
					'message' => __( "Funnelkit stripe gateway plugin could not be installed", 'wp-marketing-automations-pro' )
				];
			}
			// update option after installing stripe plugin
			update_option( 'fkwcs_wp_stripe', 'c7ffb6c58db95d6f19059ae19a0a85bf', false );
			$resp = $this->maybe_activate_stripe( self::$plugin );

			if ( $resp ) {
				return [
					'status' => 'rerun_api'
				];
			}

			return $resp;
		}

		public function maybe_install_stripe( $plugin, $plugin_url ) {
			if ( empty( $plugin_url ) ) {
				return false;
			}

			$all_plugins = get_plugins();

			if ( array_key_exists( $plugin, $all_plugins ) ) {
				return true;
			}

			require_once BWFAN_PLUGIN_DIR . '/includes/plugin_helpers/class-bwfan-plugin-install-skin.php';
			require_once BWFAN_PLUGIN_DIR . '/includes/plugin_helpers/class-bwfan-plugin-silent-upgrader.php';

			// Do not allow WordPress to search/download translations, as this will break JS output.
			remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );

			// Create the plugin upgrader with our custom skin.
			$installer = new BWFAN_Plugin_Silent_Upgrader( new BWFAN_Plugin_Install_Skin() );
			if ( ! method_exists( $installer, 'install' ) ) {
				return false;
			}

			if ( ! $installer->install( $plugin_url ) ) {
				return false;
			}

			/**
			 * Fire after plugin installing via the BWFAN installer.
			 */
			return true;
		}

		public function maybe_activate_stripe( $plugin ) {
			if ( empty( $plugin ) ) {
				return false;
			}

			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			// Activate the plugin silently.
			$activated = activate_plugin( $plugin );

			if ( is_wp_error( $activated ) ) {
				return false;
			}

			return true;
		}

		public function get_required_plugins() {
			return [
				[
					"slug" => "stripe",
					"name" => "Stripe"
				],
				[
					"slug" => "fb",
					"name" => "Funnel Builder"
				]
			];
		}
	}

	if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_builder_pro_active() ) {
		WFCO_Load_Connectors::register( 'BWFCO_Stripe' );
	}
}
