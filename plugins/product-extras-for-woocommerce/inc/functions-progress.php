<?php
/**
 * Functions for the progress bar
 * @since 3.19.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the progress bar if enabled
 * @since 3.18.0
 */
function pewc_add_progress_bar( $args ) {
	$product_id = $args[0];
	$groups = $args[1];
	if( pewc_enable_progress_bar( $product_id, $groups ) == 'no' ) {
		return;
	}
	$progress_bar_layout = pewc_get_progress_bar_layout( $product_id );
	$percent_steps = apply_filters( 'pewc_progress_bar_layout_percent_steps', array( 1, 2, 3, 4 ) );
	switch( $progress_bar_layout ) {
		case 'bar': ?>
			<div class="pewc-progress-wrapper">
				<div class="progress"><div class="progress-bar"></div><span class="pewc-progress-text"></span></div>
			</div>
		<?php		
			break;
		case 'steps': ?>
			<div class="pewc-progress-wrapper-percent-steps">
				<div class="progress-percent-steps">
					<div class="progress-bar-percent-steps" id="progress-bar-percent-steps"> </div>
					<?php 
					foreach( $percent_steps as $percent ) { ?>
						<div class="circle" data-percent="<?php echo $percent ?>"><?php echo $percent ?></div>	
					<?php } ?>
				</div>
			</div>
		<?php
		break;
	}
}
add_action( 'pewc_start_groups', 'pewc_add_progress_bar' );

/**
 * Get the field types to exclude from the progress bar
 * @since 3.19.0
 */
function pewc_progress_bar_exclude_field_types( $product ) {
	return apply_filters( 'pewc_progress_bar_exclude_field_types', '[ "calculation", "information" ]', $product );
}

/**
 * Exclude groups from being counted
 * @since 3.19.0
 */
function pewc_progress_bar_exclude_groups( $product ) {
	return apply_filters( 'pewc_progress_bar_exclude_groups', '[]', $product );
}

/**
 * Use required fields only for progress bar
 * @since 3.19.0
 */
function pewc_progress_bar_required_fields_only( $product ) {
	$required = get_option( 'pewc_progress_required', 'no' ) == 'yes' ? true : false;
	return apply_filters( 'pewc_progress_bar_required_fields_only', $required, $product );
}

/**
 * Enable progress bar by groups
 * @since 3.19.0
 */
function pewc_percentage_complete_by_groups( $product ) {
	$groups = false;
	if( get_option( 'pewc_progress_bar', 'no' ) == 'groups' ) {
		$groups = true;
	}
	return apply_filters( 'pewc_percentage_complete_by_groups', $groups, $product );
}

/**
 * Use the progress bar log
 * @since 3.19.0
 */
function pewc_progress_bar_log( $product ) {
	return apply_filters( 'pewc_progress_bar_log', false, $product );
}

/**
 * Set progress bar timeout
 * @since 3.19.0
 */
function pewc_progress_bar_timeout( $product ) {
	return apply_filters( 'pewc_progress_bar_timeout', 150, $product );
}

/**
 * Progress bar text
 * @since 3.19.0
 */
function pewc_progress_bar_progress_text( $product ) {
	return apply_filters( 'pewc_progress_bar_progress_text', json_encode(''), $product );
}

/**
 * Add group progress
 * @since 3.19.0
 */
function pewc_add_group_progress( $product ) {
	return apply_filters( 'pewc_add_group_progress', false, $product );
}

/**
 * Get the progress bar layout
 * @since 3.20.0
 */
function pewc_get_progress_bar_layout( $product ) {
	$layout = get_option( 'pewc_progress_bar_layout', 'bar' );
	return apply_filters( 'pewc_progress_bar_layout', $layout, $product );
}