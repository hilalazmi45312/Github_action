<?php
/**
 * Export header page
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wt-pfd-settings-header">
	<h3>
		<?php echo esc_html( $this->step_title ); ?>
				   <?php
					if ( 'post_type' != $this->step ) {
						?>
			 - <span class="wt_pf_step_head_post_type_name"></span><?php } ?>
	</h3>
	<span class="wt_pf_step_info" title="<?php echo esc_html( $this->step_summary ); ?>">
		<?php
		echo esc_html( $this->step_summary );
		?>
	</span>
</div>
