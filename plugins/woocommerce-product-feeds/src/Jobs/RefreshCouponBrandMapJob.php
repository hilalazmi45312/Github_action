<?php

namespace Ademti\WoocommerceProductFeeds\Jobs;

use Ademti\WoocommerceProductFeeds\Helpers\CouponRepository;

class RefreshCouponBrandMapJob extends AbstractJob {

	public string $action_hook = 'woocommerce_product_feeds_refresh_coupon_brand_map';

	/**
	 * @var CouponRepository
	 */
	private CouponRepository $coupon_repository;

	/**
	 * @param CouponRepository $coupon_repository
	 */
	public function __construct( CouponRepository $coupon_repository ) {
		$this->coupon_repository = $coupon_repository;

		parent::__construct();
	}

	public function task(): void {
		update_option( 'woocommerce_gpf_coupon_brand_map', $this->coupon_repository->generate_brand_coupon_map() );
	}
}
