<?php

/**
 * Check WooCommerce Version
 *
 * @param string $version
 *
 * @return bool
 */
function hpy_fdv_wc_version_check( $version = '3.0' ) {

	$version_number = hpy_check_wc_version();

	// Make sure $version_number is greater than or equal to the specified version.
	if ( version_compare( $version_number, $version, '>=' ) ) {
		return true;
	}

	return false;
}
