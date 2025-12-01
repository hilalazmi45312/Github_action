<?php

namespace Ademti\WoocommerceProductFeeds\Jobs;

use Ademti\WoocommerceProductFeeds\Dependencies\Pimple\Container;

class JobManager {

	// Dependencies.
	private Container $container;

	private static array $jobs = [];

	/**
	 * Job types to be managed.
	 *
	 * Note: This is the DI container reference, not the class name.
	 */
	private array $job_types = [
		'ClearGoogleTaxonomyJob',
		'MaybeRefreshGoogleTaxonomiesJob',
		'RefreshCouponCategoryMapJob',
		'RefreshCouponBrandMapJob',
		'RefreshGoogleTaxonomyJob',
	];

	/**
	 * @param Container $container
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * @return void
	 */
	public function run(): void {
		add_action( 'init', [ $this, 'init_workers' ], 9 );
	}

	/**
	 * @return void
	 */
	public function init_workers() {
		// Bail if we've already created instances.
		if ( ! empty( self::$jobs ) ) {
			return;
		}
		foreach ( $this->job_types as $job_type ) {
			self::$jobs[ $job_type ] = $this->container[ $job_type ];
		}
	}
}
