<?php
/**
 * Smart Coupons Block integration
 *
 * @author      StoreApps
 * @since       8.7.0
 * @version     1.12.0
 *
 * @package     woocommerce-smart-coupons/blocks/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

if ( class_exists( 'WC_SC_Blocks_Integration' ) || ! interface_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
	return;
}

/**
 * Class for integrating with WooCommerce Blocks
 */
class WC_SC_Blocks_Integration implements IntegrationInterface {

	/**
	 * Variable to hold instance of WC_SC_Blocks_Integration
	 *
	 * @var $instance
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_script_data_for_blocks' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_script_data_for_blocks' ) );
	}

	/**
	 * Get single instance of WC_SC_Blocks_Integration
	 *
	 * @return WC_SC_Blocks_Integration Singleton object of WC_SC_Blocks_Integration
	 */
	public static function get_instance() {
		// Check if instance is already exists.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Handle call to functions which is not available in this class
	 *
	 * @param string $function_name The function name.
	 * @param array  $arguments Array of arguments passed while calling $function_name.
	 * @return result of function call
	 */
	public function __call( $function_name, $arguments = array() ) {

		global $woocommerce_smart_coupon;

		if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
			return;
		}

		if ( ! empty( $arguments ) ) {
			return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
		} else {
			return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
		}

	}

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'woocommerce-smart-coupons';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		try {
			$this->register_available_coupons_integration();
			$this->register_send_coupon_form_integration();
			$this->register_main_integration();

			// register script for action tab.
			$this->register_coupon_action_tab_frontend_scripts();

			add_action( 'wp_enqueue_scripts', array( $this, 'conditionally_enqueue_frontend_assets' ) );
		} catch ( \Throwable $e ) {
			$this->sc_block_catch_error( $e );
		}
	}

	/**
	 * Conditionally enqueue frontend assets for Smart Coupons blocks.
	 *
	 * This ensures styles are loaded ONLY on pages where the relevant
	 * WooCommerce blocks are present (Cart / Checkout), instead of
	 *
	 * Hooked into: wp_enqueue_scripts
	 *
	 * @return void
	 */
	public function conditionally_enqueue_frontend_assets() {
		if ( is_admin() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		if ( has_block( 'woocommerce/cart', $post_id ) || has_block( 'woocommerce/checkout', $post_id ) ) {
			wp_enqueue_style( 'woocommerce-smart-coupons-available-coupons-block' );
			wp_enqueue_style( 'woocommerce-smart-coupons-action-tab-frontend' );
		}

		if ( has_block( 'woocommerce/checkout', $post_id ) ) {
			wp_enqueue_style( 'woocommerce-smart-coupons-send-coupon-form-block' );
		}
	}

	/**
	 * Register available coupons integration
	 */
	public function register_available_coupons_integration() {
		$this->register_available_coupons_block_frontend_scripts();
		$this->register_available_coupons_block_editor_scripts();
		$this->register_available_coupons_block_editor_styles();
	}

	/**
	 * Register send coupon for integration
	 */
	public function register_send_coupon_form_integration() {
		require_once __DIR__ . '/class-wc-sc-extend-store-endpoint.php';
		$this->register_send_coupon_form_block_frontend_scripts();
		$this->register_send_coupon_form_block_editor_scripts();
		$this->register_send_coupon_form_block_editor_styles();
		$this->extend_store_api();
	}

	/**
	 * Register available coupons block frontend scripts
	 */
	public function register_available_coupons_block_frontend_scripts() {
		$script_path       = '/blocks/build/woocommerce-smart-coupons-available-coupons-block-frontend.js';
		$script_url        = $this->get_plugin_directory_url( $script_path );
		$script_asset_path = $this->get_plugin_directory( '/blocks/build/woocommerce-smart-coupons-available-coupons-block-frontend.asset.php' );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array( 'wp-i18n' ),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'woocommerce-smart-coupons-available-coupons-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'woocommerce-smart-coupons-available-coupons-block-frontend', // script handle.
			'woocommerce-smart-coupons', // text domain.
			$this->get_plugin_directory( '/languages' )
		);
	}

	/**
	 * Register available coupons block editor scripts
	 */
	public function register_available_coupons_block_editor_scripts() {
		$script_path       = '/blocks/build/woocommerce-smart-coupons-available-coupons-block.js';
		$script_url        = $this->get_plugin_directory_url( $script_path );
		$script_asset_path = $this->get_plugin_directory( '/blocks/build/woocommerce-smart-coupons-available-coupons-block.asset.php' );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array( 'wp-i18n' ),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'woocommerce-smart-coupons-available-coupons-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'woocommerce-smart-coupons-available-coupons-block-editor', // script handle.
			'woocommerce-smart-coupons', // text domain.
			$this->get_plugin_directory( '/languages' )
		);
	}

	/**
	 * Register available coupons block editor styles
	 */
	public function register_available_coupons_block_editor_styles() {
		$style_path = '/blocks/build/style-woocommerce-smart-coupons-available-coupons-block.css';
		$style_url  = $this->get_plugin_directory_url( $style_path );
		wp_register_style(
			'woocommerce-smart-coupons-available-coupons-block',
			$style_url,
			array(),
			$this->get_file_version( $this->get_plugin_directory( $style_path ) )
		);
	}

	/**
	 * Register send coupon form block frontend scripts
	 */
	public function register_send_coupon_form_block_frontend_scripts() {
		$script_path       = '/blocks/build/woocommerce-smart-coupons-send-coupon-form-block-frontend.js';
		$script_url        = $this->get_plugin_directory_url( $script_path );
		$script_asset_path = $this->get_plugin_directory( '/blocks/build/woocommerce-smart-coupons-send-coupon-form-block-frontend.asset.php' );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array( 'wp-i18n' ),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'woocommerce-smart-coupons-send-coupon-form-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'woocommerce-smart-coupons-send-coupon-form-block-frontend', // script handle.
			'woocommerce-smart-coupons', // text domain.
			$this->get_plugin_directory( '/languages' )
		);
	}

	/**
	 * Register send coupon form block editor scripts
	 */
	public function register_send_coupon_form_block_editor_scripts() {
		$script_path       = '/blocks/build/woocommerce-smart-coupons-send-coupon-form-block.js';
		$script_url        = $this->get_plugin_directory_url( $script_path );
		$script_asset_path = $this->get_plugin_directory( '/blocks/build/woocommerce-smart-coupons-send-coupon-form-block.asset.php' );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array( 'wp-i18n' ),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'woocommerce-smart-coupons-send-coupon-form-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'woocommerce-smart-coupons-send-coupon-form-block-editor', // script handle.
			'woocommerce-smart-coupons', // text domain.
			$this->get_plugin_directory( '/languages' )
		);
	}

	/**
	 * Register send coupon form block editor styles
	 */
	public function register_send_coupon_form_block_editor_styles() {
		$style_path = '/blocks/build/style-woocommerce-smart-coupons-send-coupon-form-block.css';
		$style_url  = $this->get_plugin_directory_url( $style_path );
		wp_register_style(
			'woocommerce-smart-coupons-send-coupon-form-block',
			$style_url,
			array( 'wp-components' ),
			$this->get_file_version( $this->get_plugin_directory( $style_path ) )
		);
	}

	/**
	 * Register coupon action tab frontend scripts
	 */
	public function register_coupon_action_tab_frontend_scripts() {
		$script_path       = '/blocks/build/woocommerce-smart-coupons-action-tab-frontend.js';
		$script_url        = $this->get_plugin_directory_url( $script_path );
		$script_asset_path = $this->get_plugin_directory( '/blocks/build/woocommerce-smart-coupons-action-tab-frontend.asset.php' );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array( 'wp-i18n' ),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'woocommerce-smart-coupons-action-tab',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		$style_path = '/blocks/build/style-woocommerce-smart-coupons-action-tab-frontend.css';

		$style_url = $this->get_plugin_directory_url( $style_path );
		wp_register_style(
			'woocommerce-smart-coupons-action-tab-frontend',
			$style_url,
			array( 'wp-components' ),
			$this->get_file_version( $this->get_plugin_directory( $style_path ) )
		);

		wp_set_script_translations(
			'woocommerce-smart-coupons-action-tab', // script handle.
			'woocommerce-smart-coupons', // text domain.
			$this->get_plugin_directory( '/languages' )
		);
	}

	/**
	 * Extends the cart schema to include the shipping-workshop value.
	 */
	private function extend_store_api() {
		WC_SC_Extend_Store_Endpoint::init();
	}

	/**
	 * Save data from Send coupon form
	 */
	private function save_send_coupon_data() {
		/**
		 * 📝 Write a hook, using the `woocommerce_store_api_checkout_update_order_from_request` action
		 * that will update the order metadata with the shipping-workshop alternate shipping instruction.
		 *
		 * The documentation for this hook is at: https://github.com/woocommerce/woocommerce-blocks/blob/b73fbcacb68cabfafd7c3e7557cf962483451dc1/docs/third-party-developers/extensibility/hooks/actions.md#woocommerce_store_api_checkout_update_order_from_request
		 */
		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function( \WC_Order $order, \WP_REST_Request $request ) {
				try {
					$send_coupon_data = $request['extensions']['woocommerce-smart-coupons'];
					if ( ! empty( $send_coupon_data['is_gift'] ) ) {
						$order->update_meta_data( 'is_gift', $send_coupon_data['is_gift'] );
					}
					if ( ! empty( $send_coupon_data['wc_sc_schedule_gift_sending'] ) ) {
						$order->update_meta_data( 'wc_sc_schedule_gift_sending', $send_coupon_data['wc_sc_schedule_gift_sending'] );
					}
					if ( ! empty( $send_coupon_data['gift_receiver_email'] ) ) {
						$gift_receiver_email = array();
						if ( ! is_scalar( $send_coupon_data['gift_receiver_email'] ) ) {
							foreach ( $send_coupon_data['gift_receiver_email'] as $key => $email ) {
								list( $coupon_id, $index ) = explode( '_', $key );
								$coupon_id                 = ( ! empty( $coupon_id ) ) ? intval( $coupon_id ) : 0;
								$index                     = ( ! empty( $index ) ) ? intval( $index ) : 0;
								if ( empty( $gift_receiver_email[ $coupon_id ] ) || ! is_array( $gift_receiver_email[ $coupon_id ] ) ) {
									$gift_receiver_email[ $coupon_id ] = array();
								}
								$gift_receiver_email[ $coupon_id ][ $index ] = $email;
							}
						}
						if ( ! empty( $gift_receiver_email ) ) {
							$order->update_meta_data( 'gift_receiver_email', $gift_receiver_email );
						}
					}
					if ( ! empty( $send_coupon_data['gift_sending_timestamp'] ) ) {
						$gift_sending_timestamp = array();
						if ( ! is_scalar( $send_coupon_data['gift_sending_timestamp'] ) ) {
							foreach ( $send_coupon_data['gift_sending_timestamp'] as $key => $timestamp ) {
								list( $coupon_id, $index ) = explode( '_', $key );
								$coupon_id                 = ( ! empty( $coupon_id ) ) ? intval( $coupon_id ) : 0;
								$index                     = ( ! empty( $index ) ) ? intval( $index ) : 0;
								if ( empty( $gift_sending_timestamp[ $coupon_id ] ) || ! is_array( $gift_sending_timestamp[ $coupon_id ] ) ) {
									$gift_sending_timestamp[ $coupon_id ] = array();
								}
								$gift_sending_timestamp[ $coupon_id ][ $index ] = $timestamp;
							}
						}
						if ( ! empty( $gift_sending_timestamp ) ) {
							$order->update_meta_data( 'gift_sending_timestamp', $gift_sending_timestamp );
						}
					}
					if ( ! empty( $send_coupon_data['gift_receiver_message'] ) ) {
						$gift_receiver_message = array();
						if ( ! is_scalar( $send_coupon_data['gift_receiver_message'] ) ) {
							foreach ( $send_coupon_data['gift_receiver_message'] as $key => $message ) {
								list( $coupon_id, $index ) = explode( '_', $key );
								$coupon_id                 = ( ! empty( $coupon_id ) ) ? intval( $coupon_id ) : 0;
								$index                     = ( ! empty( $index ) ) ? intval( $index ) : 0;
								if ( empty( $gift_receiver_message[ $coupon_id ] ) || ! is_array( $gift_receiver_message[ $coupon_id ] ) ) {
									$gift_receiver_message[ $coupon_id ] = array();
								}
								$gift_receiver_message[ $coupon_id ][ $index ] = $message;
							}
						}
						if ( ! empty( $gift_receiver_message ) ) {
							$order->update_meta_data( 'gift_receiver_message', $gift_receiver_message );
						}
					}
					$order->save();
				} catch ( \Throwable $e ) {
					$this->sc_block_catch_error( $e );
				}
			},
			10,
			2
		);
	}

	/**
	 * Registers the main JS file required to add filters and Slot/Fills.
	 */
	public function register_main_integration() {
		$script_path = '/blocks/build/index.js';

		$script_url = $this->get_plugin_directory_url( $script_path );

		$script_asset_path = $this->get_plugin_directory( '/blocks/build/index.asset.php' );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array( 'wp-i18n' ),
				'version'      => $this->get_file_version( $script_asset_path ),
			);

		wp_register_script(
			'woocommerce-smart-coupons-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'woocommerce-smart-coupons-blocks-integration',
			'woocommerce-smart-coupons',
			$this->get_plugin_directory( '/languages' )
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'woocommerce-smart-coupons-blocks-integration', 'woocommerce-smart-coupons-available-coupons-block-frontend', 'woocommerce-smart-coupons-send-coupon-form-block-frontend', 'woocommerce-smart-coupons-action-tab' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'woocommerce-smart-coupons-blocks-integration', 'woocommerce-smart-coupons-available-coupons-block-editor', 'woocommerce-smart-coupons-send-coupon-form-block-editor' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$data = array(
			'woocommerce-smart-coupons-blocks-active' => true,
		);

		return $data;

	}

	/**
	 * Load script data for blocks
	 */
	public function load_script_data_for_blocks() {
		global $pagenow;
		// Skip non-front-end requests.
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_rest_endpoint() || defined( 'REST_REQUEST' ) ) {
			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				if ( ! empty( $screen->post_type ) && 'product' === $screen->post_type ) {
					return; // No need to run the following code when working with products.
				}
			}
			$get_post      = ( ! empty( $_GET['post'] ) ) ? wc_clean( wp_unslash( $_GET['post'] ) ) : '';            // phpcs:ignore
			$get_post_type = ( ! empty( $_GET['post_type'] ) ) ? wc_clean( wp_unslash( $_GET['post_type'] ) ) : '';  // phpcs:ignore
			if ( 'post.php' === $pagenow && ! empty( $get_post ) && 'product' === get_post_type( $get_post ) ) {
				return;  // No need to run the following code when working with products.
			}
			if ( 'post-new.php' === $pagenow && ! empty( $get_post_type ) && 'product' === $get_post_type ) {
				return;  // No need to run the following code when working with products.
			}
			return;  // No need to run the following code when working with products. Giving preference to detect product, if unable to detect then return.
		}
		$page_id = absint( get_the_ID() );
		if ( has_block( 'woocommerce/cart', $page_id ) || has_block( 'woocommerce/checkout', $page_id ) || has_block( 'woocommerce-smart-coupons/available-coupons', $page_id ) || has_block( 'woocommerce-smart-coupons/send-coupon-form', $page_id ) ) {
			$this->load_available_coupons_data();
			$this->load_send_coupon_form_data();
		}
	}

	/**
	 * Load data for available coupons block
	 */
	public function load_available_coupons_data() {
		try {
			$available_coupons_html = '';
			$sample_coupon_html     = '';
			$auto_applied_coupons   = array();

			if ( ! class_exists( 'WC_SC_Coupon_Message' ) ) {
				if ( file_exists( WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-coupon-message.php' ) ) {
					include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-coupon-message.php';
				}
			}

			if ( ! class_exists( 'WC_SC_Display_Coupons' ) ) {
				if ( file_exists( WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-display-coupons.php' ) ) {
					include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-display-coupons.php';
				}
			}

			if ( ! class_exists( 'WC_SC_Auto_Apply_Coupon' ) ) {
				if ( file_exists( WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-auto-apply-coupon.php' ) ) {
					include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-auto-apply-coupon.php';
				}
			}

			if ( ! class_exists( 'WC_SC_Settings' ) ) {
				if ( file_exists( WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-settings.php' ) ) {
					include_once WC_SC_PLUGIN_DIRPATH . 'includes/class-wc-sc-settings.php';
				}
			}

			$is_cart_block_default     = is_callable( array( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_cart_block_default' ) ) ? CartCheckoutUtils::is_cart_block_default() : false;
			$is_checkout_block_default = is_callable( array( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_checkout_block_default' ) ) ? CartCheckoutUtils::is_checkout_block_default() : false;

			$show_on_cart     = get_option( 'smart_coupons_show_on_cart', 'yes' ) === 'yes';
			$show_on_checkout = get_option( 'smart_coupons_show_on_checkout', 'yes' ) === 'yes';

			if ( ! is_admin() && ! wp_doing_ajax() && ! WC()->is_rest_api_request() && ( $is_cart_block_default || $is_checkout_block_default ) ) {

				if ( class_exists( 'WC_SC_Coupon_Message' ) ) {
					$wc_sc_coupon_message = WC_SC_Coupon_Message::get_instance();
					ob_start();
					$wc_sc_coupon_message->wc_coupon_message_display();
					$available_coupons_html .= ob_get_clean();
				}

				if ( class_exists( 'WC_SC_Display_Coupons' ) ) {
					if (
						( is_cart() && has_block( 'woocommerce/cart' ) && $show_on_cart ) ||
						( is_checkout() && has_block( 'woocommerce/checkout' ) && $show_on_checkout )
					) {
						$wc_sc_display_coupons = WC_SC_Display_Coupons::get_instance();
						ob_start();
						$wc_sc_display_coupons->show_available_coupons();
						$available_coupons_html .= ob_get_clean();
					}
				}

				if ( class_exists( 'WC_SC_Auto_Apply_Coupon' ) ) {
					$wc_sc_auto_apply_coupon = WC_SC_Auto_Apply_Coupon::get_instance();
					$auto_applied_coupons    = $wc_sc_auto_apply_coupon->get_auto_applied_coupons();
					if ( ! empty( $auto_applied_coupons ) ) {
						foreach ( $auto_applied_coupons as $index => $coupon_code ) {
							if ( 'no' !== $wc_sc_auto_apply_coupon->is_auto_apply_coupon_removable( $coupon_code ) ) {
								unset( $auto_applied_coupons[ $index ] );
							}
						}
					}
					$auto_applied_coupons = array_values( $auto_applied_coupons );
				}

				$design = get_option( 'wc_sc_setting_coupon_design', 'basic' );

				if ( class_exists( 'WC_SC_Settings' ) ) {
					$wc_sc_settings = WC_SC_Settings::get_instance();
					ob_start();
					for ( $i = 0; $i < 2; $i++ ) {
						$wc_sc_settings->coupon_design_html( $design );
					}
					$sample_coupon_html = ob_get_clean();
				}
			}

			$script_data = array(
				'html'                        => $available_coupons_html,
				'background_color'            => get_option( 'wc_sc_setting_coupon_background_color', '#39cccc' ),
				'foreground_color'            => get_option( 'wc_sc_setting_coupon_foreground_color', '#30050b' ),
				'third_color'                 => get_option( 'wc_sc_setting_coupon_third_color', '#39cccc' ),
				'coupon_section_title'        => $this->wc_sc_get_available_coupons_title(),
				'sample_html'                 => $sample_coupon_html,
				'forced_auto_applied_coupons' => $auto_applied_coupons,
				'show_on_cart'                => $show_on_cart,
				'show_on_checkout'            => $show_on_checkout,
			);

			wp_localize_script( 'woocommerce-smart-coupons-available-coupons-block-frontend', 'wc_sc_available_coupons_block_editor', $script_data );
			wp_localize_script( 'woocommerce-smart-coupons-available-coupons-block-editor', 'wc_sc_available_coupons_block_editor', $script_data );
			wp_localize_script( 'woocommerce-smart-coupons-blocks-integration', 'wc_sc_available_coupons_block_editor', $script_data );
		} catch ( \Throwable $e ) {
			$this->sc_block_catch_error( $e );
		}
	}

	/**
	 * Load send coupon form data
	 *
	 * @return void
	 */
	public function load_send_coupon_form_data() {
		try {
			$form_title = $this->sc_get_option( 'smart_coupon_gift_certificate_form_page_text', _x( 'Send Coupons to...', 'Block editor & frontend', 'woocommerce-smart-coupons' ) );
			$form_title = ( ! empty( $form_title ) ) ? $form_title : _x( 'Send Coupons to...', 'Block editor & frontend', 'woocommerce-smart-coupons' );

			$all_discount_types = wc_get_coupon_types();

			$coupon_details_to_be_sent  = array();
			$cart_contents              = ( ! empty( WC()->cart->cart_contents ) ) ? WC()->cart->cart_contents : array();
			$coupon_ids_to_be_generated = array();
			if ( ! empty( $cart_contents ) ) {
				$sell_sc_at_less_price = get_option( 'smart_coupons_sell_store_credit_at_less_price', 'no' );
				foreach ( $cart_contents as $product ) {
					if ( ! empty( $product['variation_id'] ) ) {
						$_product = wc_get_product( $product['variation_id'] );
					} elseif ( ! empty( $product['product_id'] ) ) {
						$_product = wc_get_product( $product['product_id'] );
					} else {
						continue;
					}

					$coupon_titles = $this->get_coupon_titles( array( 'product_object' => $_product ) );

					$price = $_product->get_price();

					if ( $coupon_titles ) {

						foreach ( $coupon_titles as $coupon_title ) {

							$coupon = new WC_Coupon( $coupon_title );
							if ( ! is_object( $coupon ) || ! is_callable( array( $coupon, 'get_id' ) ) ) {
								continue;
							}
							$coupon_id = $coupon->get_id();
							if ( empty( $coupon_id ) ) {
								continue;
							}
							$discount_type = $coupon->get_discount_type();

							$coupon_amount = $this->get_amount( $coupon, true );

							$pick_price_of_prod = ( $this->is_callable( $coupon, 'get_meta' ) ) ? $coupon->get_meta( 'is_pick_price_of_product' ) : get_post_meta( $coupon_id, 'is_pick_price_of_product', true );

							if ( array_key_exists( $discount_type, $all_discount_types ) || ( 'yes' === $pick_price_of_prod && '' === $price ) || ( 'yes' === $pick_price_of_prod && '' !== $price && $coupon_amount > 0 ) ) {
								$coupon_data = $this->get_coupon_meta_data( $coupon );

								$coupon_id        = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_id' ) ) ) ? $coupon->get_id() : '';
								$coupon_code      = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_code' ) ) ) ? $coupon->get_code() : '';
								$product_price    = ( is_object( $product['data'] ) && is_callable( array( $product['data'], 'get_price' ) ) ) ? $product['data']->get_price() : 0;
								$is_free_shipping = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_free_shipping' ) ) ) ? ( ( $coupon->get_free_shipping() ) ? 'yes' : 'no' ) : '';
								$discount_type    = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_discount_type' ) ) ) ? $coupon->get_discount_type() : '';

								$coupon_amount = $this->get_amount( $coupon, true );

								for ( $i = 0; $i < $product['quantity']; $i++ ) {

									if ( $this->is_coupon_amount_pick_from_product_price( array( $coupon_code ) ) ) {
										if ( 'yes' === $sell_sc_at_less_price ) {
											$_coupon_amount = ( is_object( $product['data'] ) && is_callable( array( $product['data'], 'get_regular_price' ) ) ) ? $product['data']->get_regular_price() : 0;
											if ( empty( $_coupon_amount ) && ! empty( $product_price ) ) {
												$_coupon_amount = $product_price;
											}
										} else {
											$_coupon_amount = $product_price;
										}
										if ( empty( $_coupon_amount ) && ! empty( $product['credit_amount'] ) ) {
											$_coupon_amount = (float) $product['credit_amount'];
											$_coupon_amount = $this->read_price( $_coupon_amount, true );
										}
									} else {
										$_coupon_amount = $coupon_amount;
									}

									if ( '' !== $_coupon_amount || $_coupon_amount > 0 || $coupon_amount > 0 || 'yes' === $is_free_shipping ) {
										$formatted_coupon_text   = '';
										$formatted_coupon_amount = 0;
										if ( ! empty( $_coupon_amount ) || ! empty( $coupon_amount ) ) {
											$formatted_coupon_amount = ( $coupon_amount <= 0 ) ? wc_price( $_coupon_amount ) : $coupon_data['coupon_amount'];
											$formatted_coupon_text  .= $coupon_data['coupon_type'];
											if ( 'yes' === $is_free_shipping ) {
												$formatted_coupon_text .= ' &amp; ';
											}
										}
										if ( 'yes' === $is_free_shipping ) {
											$formatted_coupon_text .= _x( 'Free Shipping coupon', 'Block editor & frontend', 'woocommerce-smart-coupons' );
										}
										if ( 'smart_coupon' !== $discount_type && strpos( $formatted_coupon_text, 'coupon' ) === false ) {
											$formatted_coupon_text .= ' ' . _x( 'coupon', 'Block editor & frontend', 'woocommerce-smart-coupons' );
										}
										$count_coupon_ids             = array_count_values( $coupon_ids_to_be_generated );
										$coupon_details_to_be_sent[]  = (object) array(
											'title'     => _x( 'Send', 'Block editor & frontend', 'woocommerce-smart-coupons' ) . ' ' . $formatted_coupon_text . ' ' . _x( 'of', 'Block editor & frontend', 'woocommerce-smart-coupons' ) . ' ' . wp_strip_all_tags( $formatted_coupon_amount ),
											'coupon_id' => $coupon_id,
											'index'     => ( ! empty( $count_coupon_ids ) && array_key_exists( $coupon_id, $count_coupon_ids ) ) ? $count_coupon_ids[ $coupon_id ] : 0,
										);
										$coupon_ids_to_be_generated[] = $coupon_id;
									}
								}
							}
						}
					}
				}
			}

			$script_data = array(
				'is_display'                      => $this->sc_get_option( 'smart_coupons_display_coupon_receiver_details_form', 'yes' ),
				'title'                           => $form_title,
				'custom_text'                     => $this->sc_get_option( 'smart_coupon_gift_certificate_form_details_text', '' ),
				'description'                     => _x( 'Your order contains coupons. What would you like to do?', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
				'is_schedule'                     => $this->sc_get_option( 'smart_coupons_schedule_store_credit', 'no' ),
				'is_gift'                         => array(
					(object) array(
						'label' => _x( 'Send to me', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
						'value' => 'no',
					),
					(object) array(
						'label' => _x( 'Gift to someone else', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
						'value' => 'yes',
					),
				),
				'sc_send_to'                      => array(
					(object) array(
						'label' => _x( 'Send to one person', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
						'value' => 'one',
					),
					(object) array(
						'label' => _x( 'Send to different people', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
						'value' => 'many',
					),
				),
				'wc_sc_schedule_gift_sending'     => array(
					(object) array(
						'label' => _x( 'now', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
						'value' => 'now',
					),
					(object) array(
						'label' => _x( 'later', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
						'value' => 'later',
					),
				),
				'deliver_coupon_label'            => _x( 'Deliver coupon', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
				'gift_receiver_email_placeholder' => _x( 'Enter recipient e-mail address', 'Block editor & frontend', 'woocommerce-smart-coupons' ),
				'coupon_details_to_be_sent'       => $coupon_details_to_be_sent,
			);

			wp_localize_script( 'woocommerce-smart-coupons-send-coupon-form-block-frontend', 'wc_sc_send_coupon_form_block_frontend', $script_data );
			wp_localize_script( 'woocommerce-smart-coupons-send-coupon-form-block-editor', 'wc_sc_send_coupon_form_block_editor', $script_data );
		} catch ( \Throwable $e ) {
			$this->sc_block_catch_error( $e );
		}

	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return $this->get_version();
	}
}
