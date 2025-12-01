<?php
/**
 * Admin HTML settings buttons.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<p class = 'submit'>
	<?php if ( ! isset( $GLOBALS[ 'hide_save_button' ] ) ) : ?>
		<input name='dey_save' class='button-primary dey-settings-save-btn' type='submit' value="<?php esc_attr_e( 'Save changes', 'delivery-slots-for-woocommerce' ) ; ?>" />
		<input type="hidden" name="save" value="save"/>
		<?php
		wp_nonce_field( 'dey_save_settings', '_dey_nonce', false, true ) ;
	endif ;
	?>
</p>
<?php if ( $reset ) : ?>
	</form>
	<form method='post' action='' enctype='multipart/form-data' class="dey-reset-form">
		<input id='reset' name='dey_reset' class='button-secondary dey-settings-reset-btn' type='submit' value="<?php esc_attr_e( 'Reset', 'delivery-slots-for-woocommerce' ) ; ?>"/>
		<input type="hidden" name="reset" value="reset"/>
		<?php
		wp_nonce_field( 'dey_reset_settings', '_dey_nonce', false, true ) ;
	endif;
