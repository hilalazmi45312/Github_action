<?php

/**
 * WooCommerce Blocks Integration.
 *
 * @since 3.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks scripts
 *
 * @since 3.7.0
 */
class DEY_WC_Blocks_Integration implements IntegrationInterface {

	/**
	 * Whether the integration has been initialized.
	 *
	 * @since 3.7.0
	 * @var boolean
	 */
	protected $is_initialized;

	/**
	 * The single instance of the class.
	 *
	 * @since 3.7.0
	 * @var DEY_WC_Blocks_Integration
	 */
	protected static $_instance = null;

	/**
	 * Main DEY_WC_Blocks_Integration instance. Ensures only one instance of DEY_WC_Blocks_Integration is loaded or can be loaded.
	 *
	 * @since 3.7.0
	 * @static
	 * @return DEY_WC_Blocks_Integration
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 3.7.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'delivery-slots-for-woocommerce' ), '3.7.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 3.7.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'delivery-slots-for-woocommerce' ), '3.7.0' );
	}

	/**
	 * The name of the integration.
	 *
	 * @since 3.7.0
	 * @return string
	 */
	public function get_name() {
		return 'dey-wc-blocks';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 *
	 * @since 3.7.0
	 */
	public function initialize() {
		if ( $this->is_initialized ) {
			return;
		}

		// Enqueue block assets for the editor.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		// Enqueue block assets for the front-end.
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @since 3.7.0
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'dey-wc-blocks' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @since 3.7.0
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'dey-wc-blocks' );
	}

	/**
	 * Enqueue block assets for the editor.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		// Load script.
		$script_asset_details = $this->get_script_asset_details( 'admin' );

		wp_register_script(
			'dey-wc-blocks',
			DEY_PLUGIN_URL . '/assets/blocks/admin/index.js',
			$script_asset_details['dependencies'],
			$script_asset_details['version'],
			true
		);

		wp_enqueue_style(
			'dey-wc-blocks',
			DEY_PLUGIN_URL . '/assets/blocks/admin/index.css',
			'',
			$script_asset_details['version']
		);
	}

	/**
	 * Get the script asset details from the file if exists.
	 *
	 * @since 3.7.0
	 * @param string $site
	 * @return array
	 */
	private function get_script_asset_details( $site = 'frontend' ) {
		$script_asset_path = DEY_PLUGIN_PATH . '/assets/blocks/' . $site . '/index.asset.php';

		return file_exists( $script_asset_path ) ? require $script_asset_path : array(
			'dependencies' => array(),
			'version'      => DEY_VERSION,
		);
	}

	/**
	 * Enqueue block assets for the front-end.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {
		// Load script.
		$script_asset_details = $this->get_script_asset_details();

		wp_register_script(
			'dey-wc-blocks',
			DEY_PLUGIN_URL . '/assets/blocks/frontend/index.js',
			$script_asset_details['dependencies'],
			$script_asset_details['version'],
			true
		);
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @since 3.7.0
	 * @return array
	 */
	public function get_script_data() {
		if ( is_admin() ) {
			return array(
				'order_tip_title_label'        => dey_get_order_tip_title_label(),
				'scheduler_block_preview_html' => $this->get_scheduler_block_preview_html(),
				'order_tip_block_preview_html' => $this->get_order_tip_block_preview_html(),
			);
		} else {
			return array(
				'scheduler_rule_id'            => dey_get_scheduler_rule_id_by_shipping_method(),
				'default_order_scheduler_type' => dey_get_selected_order_scheduler_data_from_session( 'order_scheduler_type' ),
			);
		}
	}

	/**
	 * Get the scheduler block preview HTML.
	 *
	 * @since 3.7.0
	 * @return HTML
	 */
	private function get_scheduler_block_preview_html() {
		ob_start();
		include_once DEY_ABSPATH . 'inc/admin/menu/views/blocks/html-scheduler-block-preview-template.php';
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	/**
	 * Get the order tip block preview HTML.
	 *
	 * @since 3.7.0
	 * @return HTML
	 */
	private function get_order_tip_block_preview_html() {
		ob_start();
		include_once DEY_ABSPATH . 'inc/admin/menu/views/blocks/html-order-tip-block-preview-template.php';
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}
}
