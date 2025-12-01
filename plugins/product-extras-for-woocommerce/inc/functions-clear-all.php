<?php
/**
 * Functions for Clear All button
 * @since 3.21.7
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if Clear All Options is enabled
 * @since 3.21.7
 */
function pewc_clear_all_enabled( $product_id=false ) {
	$enabled = get_option( 'pewc_enable_clear_all_button', 'no' );
	return apply_filters( 'pewc_enable_clear_all_button', $enabled === 'yes', $product_id );
}

/**
 * Outputs the Clear All Options button on the product page
 * @since 3.21.7
 */
function pewc_display_clear_all_button( $product_id, $product, $summary_panel ) {

	if ( ! pewc_clear_all_enabled() || 1 > apply_filters( 'pewc_conditions_timer', 0 ) ) {
		return;
	}

	?>
	<button type="button" class="pewc-clear-all"><?php echo apply_filters( 'pewc_clear_all_button_text', __( 'Clear All Options', 'pewc' ), $product_id ); ?></button>
	<?php

}
add_action( 'pewc_after_group_wrap', 'pewc_display_clear_all_button', 21, 3 );
