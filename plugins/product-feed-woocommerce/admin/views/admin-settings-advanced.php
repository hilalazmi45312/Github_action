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
?>
<div class="wt-pfd-tab-content" data-id="<?php echo esc_html( $target_id ); ?>">
	<?php
	$fields = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings_fields();

	$advanced_settings = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_advanced_settings();
	?>
	<table class="form-table wt-pfd-form-table">
		<?php
		Webtoffee_Product_Feed_Sync_Pro_Common_Helper::field_generator( $fields, $advanced_settings );
		?>
	</table>
	<?php
	include 'admin-settings-save-button.php';
	?>
</div>
