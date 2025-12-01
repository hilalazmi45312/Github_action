<?php
add_filter( 'woocommerce_reset_variations_link', 'hpy_fdv_remove_clear_text', 10, 1 );

/**
 * Remove the Clear selection link.
 *
 * @param $value
 *
 * @return string
 */
function hpy_fdv_remove_clear_text( $value ) {

	$value = '';
	return $value;

}

