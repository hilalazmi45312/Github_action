<?php

namespace Ademti\WoocommerceProductFeeds\Features\SetupTasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Admin\PageController;

class SetupTasks {
	/**
	 * The base directory for importing JS files from.
	 */
	private string $base_dir;

	/**
	 * Run the feature.
	 */
	public function initialise(): void {
		$this->base_dir = dirname( __DIR__, 3 );

		add_action( 'init', [ $this, 'register_tasks' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Register our tasks.
	 */
	public function register_tasks(): void {
		if ( ! class_exists( 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists' ) ) {
			return;
		}
		TaskLists::add_task(
			'extended',
			new ConfigureSettingsTask(
				TaskLists::get_list( 'extended' ),
			)
		);
		TaskLists::add_task(
			'extended',
			new FeedSetupTask(
				TaskLists::get_list( 'extended' ),
			)
		);
	}

	/**
	 * Enqueue the JS.
	 */
	public function enqueue_scripts(): void {
		if (
			! class_exists( 'Automattic\WooCommerce\Internal\Admin\Loader' ) ||
			! PageController::is_admin_or_embed_page()
		) {
			return;
		}

		/**
		 * Setup tasks
		 */
		$asset_file = require $this->base_dir . '/js/dist/setup-tasks.asset.php';
		wp_register_script(
			'woocommerce-gpf-setup-tasks',
			plugins_url( basename( $this->base_dir ) . '/js/dist/setup-tasks.js' ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
		$l10n_data = [
			'settings_link' => admin_url( 'admin.php?page=wc-settings&tab=gpf' ),
		];
		wp_localize_script(
			'woocommerce-gpf-setup-tasks',
			'woocommerce_gpf_setup_tasks_data',
			$l10n_data
		);
		wp_enqueue_script( 'woocommerce-gpf-setup-tasks' );

		/**
		 * Store management links
		 */
		$asset_file = require $this->base_dir . '/js/dist/store-management-links.asset.php';
		wp_register_script(
			'woocommerce-gpf-store-management-links',
			plugins_url( basename( $this->base_dir ) . '/js/dist/store-management-links.js' ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
		wp_localize_script(
			'woocommerce-gpf-store-management-links',
			'woocommerce_gpf_store_management_links_data',
			[
				'settings_link' => admin_url( 'admin.php?page=wc-settings&tab=gpf' ),
			]
		);
		wp_enqueue_script( 'woocommerce-gpf-store-management-links' );
	}
}
