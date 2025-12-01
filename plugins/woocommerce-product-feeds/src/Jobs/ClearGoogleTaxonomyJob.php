<?php

namespace Ademti\WoocommerceProductFeeds\Jobs;

class ClearGoogleTaxonomyJob extends AbstractJob {

	/**
	 * @var string
	 */
	public string $action_hook = 'woocommerce_product_feeds_clear_google_taxonomy';

	/**
	 * @param $locale
	 *
	 * @return bool
	 */
	public function task( $locale ): bool {
		global $wpdb, $table_prefix;

		$sql = 'DELETE FROM %i WHERE locale = %s';
		$wpdb->query(
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( $sql, [ $table_prefix . 'woocommerce_gpf_google_taxonomy', $locale ] )
		);

		// Clear the cache expiry timestamp to force refresh.
		delete_option( 'woocommerce_gpf_tax_ts_' . $locale );

		return true;
	}
}
