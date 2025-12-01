<?php

namespace Ademti\WoocommerceProductFeeds\Helpers;

use WC_Logger_Interface;

class DebugService {

	/**
	 * The destination for debug output.
	 */
	private string $destination;

	/**
	 * Whether debug is enabled or not.
	 */
	private bool $enabled;

	/**
	 * @var bool
	 */
	private bool $ready = false;

	/**
	 * The context to use when writing to the logger.
	 */
	private array $wc_context;

	/**
	 * WC_Logger instance to use.
	 */
	private WC_Logger_Interface $wc_logger;

	/**
	 * WoocommerceGpfDebugService constructor.
	 */
	public function __construct() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$debug_key         = get_option( 'woocommerce_gpf_debug_key' );
		$this->wc_context  = [ 'source' => 'woocommerce-product-feeds' ];
		$this->enabled     = isset( $_REQUEST['debug_key'] ) &&
							$_REQUEST['debug_key'] === $debug_key;
		$this->destination = 'wc-log';

		if ( isset( $_REQUEST['destination'] ) &&
			$_REQUEST['destination'] === 'xml'
		) {
			$this->destination = 'xml';
		}

		if ( did_action( 'plugins_loaded' ) ) {
			$this->get_logger();
		} else {
			add_action( 'plugins_loaded', [ $this, 'get_logger' ] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Grab a WC_Logger instance.
	 */
	public function get_logger(): void {
		if ( is_callable( 'wc_get_logger' ) ) {
			$this->wc_logger = wc_get_logger();
			$this->ready     = true;
		}
	}

	/**
	 * Whether debug is active.
	 *
	 * @return bool
	 */
	public function debug_active() {
		return $this->enabled;
	}

	/**
	 * Log a message with optional sprintf replacements.
	 *
	 * @param string $message The message.
	 * @param array $args Array of replacements to be sprintf'd in.
	 *
	 * @return void
	 */
	public function log( $message, $args = [] ) {
		if ( ! $this->enabled ) {
			return;
		}
		$log_msg = sprintf( $message, ...$args );
		if ( 'wc-log' === $this->destination ) {
			$this->ready && $this->wc_logger->debug(
				$log_msg,
				$this->wc_context
			);
		} elseif ( 'xml' === $this->destination ) {
			echo '<!-- ';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $log_msg;
			echo ' -->' . PHP_EOL;
		}
	}
}
