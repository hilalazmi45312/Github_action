<?php
/**
 * Admin settings save button section
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$settings_button_title = isset( $settings_button_title ) ? $settings_button_title : __( 'Update Settings', 'product-feed-woocommerce' );
$before_button_text = isset( $before_button_text ) ? $before_button_text : '';
$after_button_text = isset( $after_button_text ) ? $after_button_text : '';
?>
<div style="clear: both;"></div>
<div class="wt-pfd-plugin-toolbar bottom">
	<div class="left">
	</div>
	<div class="right">
		<?php echo esc_html( $before_button_text ); ?>
		<input type="submit" name="wt_pf_update_admin_settings_form" value="<?php echo esc_html( $settings_button_title ); ?>" class="button button-primary" style="float:right;"/>
		<?php echo esc_html( $after_button_text ); ?>
		<span class="spinner" style="margin-top:11px"></span>
	</div>
</div>
